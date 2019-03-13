<?php

namespace TRFDrivers\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use TRFConsumer\Exceptions\InvalidOriginalMessageException;
use TRFConsumer\Interfaces\MQDriver;
use TRFConsumer\Interfaces\MQMessage;

class Driver implements MQDriver
{
    const MASK_QUEUE_RETRY = "%s-retry";
    const MASK_QUEUE_FAIL = "%s-fail";

    const QUEUE_DECLARE_EXCHANGE = 'x-dead-letter-exchange';
    const QUEUE_DECLARE_ROUTING = 'x-dead-letter-routing-key';

    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /** @var AMQPChannel */
    private $channel;
    /**
     * @var bool
     */
    private $setupQueue;

    public function __construct(AMQPStreamConnection $connection, bool $setupQueue = true)
    {
        $this->connection = $connection;
        $this->setupQueue = $setupQueue;
    }

    public function init(): void
    {

    }

    /**
     * Get channel
     *
     * @return AMQPChannel
     */
    protected function getChannel(): AMQPChannel
    {
        if (!isset($this->channel)) {
            $this->channel = $this->connection->channel();
        }
        return $this->channel;
    }

    /**
     * @param string $queue
     * @param string $consumerTag
     * @param callable $process
     */
    public function basicConsume(string $queue, string $consumerTag, callable $process): void
    {
        if ($this->setupQueue) {
            $this->declareQueues($queue);
        }

        $this->channel->basic_consume(
            $queue,
            $consumerTag,
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) use ($process) {
                $process(new Message($message));
            }
        );
    }

    protected function declareQueues(string $readyQueue)
    {
        $this->getChannel()->queue_declare(sprintf(static::MASK_QUEUE_RETRY, $readyQueue), false, true, false, false, false, new AMQPTable([
            static::QUEUE_DECLARE_EXCHANGE => '',
            static::QUEUE_DECLARE_ROUTING => $readyQueue
        ]));
        $this->getChannel()->queue_declare(sprintf(static::MASK_QUEUE_FAIL, $readyQueue), false, true, false, false, false);
    }

    /**
     * @throws \ErrorException
     */
    public function wait(): void
    {
        while (count($this->getChannel()->callbacks)) {
            $this->getChannel()->wait();
        }
    }

    public function ackMessage(MQMessage $message): void
    {
        $this->getChannel()->basic_ack($message->deliveryTag());
    }

    public function shutdown(): void
    {
        if (isset($this->channel)) {
            $this->getChannel()->close();
        }
    }

    /**
     * @param string $queue
     * @param MQMessage $message
     * @param int|null $ttl
     * @throws InvalidOriginalMessageException
     */
    protected function rePublish(string $queue, MQMessage $message, ?int $ttl = null)
    {
        $originalMsg = $message->original();
        if ($originalMsg instanceof AMQPMessage) {
            if (!empty($ttl)) {
                $originalMsg->set('expiration', $ttl);
            }
            $this->getChannel()->basic_publish($originalMsg, '', $queue);
        } else {
            throw new InvalidOriginalMessageException(sprintf('Expected %s got %s.', AMQPMessage::class, get_class($message->original())));
        }
    }

    /**
     * @param string $queue
     * @param MQMessage $message
     * @param int $ttl
     * @throws InvalidOriginalMessageException
     */
    public function publishRetry(string $queue, MQMessage $message, int $ttl): void
    {
        $this->rePublish(sprintf(static::MASK_QUEUE_RETRY, $queue), $message, $ttl);
    }

    /**
     * @param string $queue
     * @param MQMessage $message
     * @throws InvalidOriginalMessageException
     */
    public function publishFail(string $queue, MQMessage $message): void
    {
        $this->rePublish(sprintf(static::MASK_QUEUE_FAIL, $queue), $message);
    }
}
