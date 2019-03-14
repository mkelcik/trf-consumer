<?php

namespace TRFConsumer;

use TRFConsumer\Events\EventRetry;
use TRFConsumer\Exceptions\ConsumeException;
use TRFConsumer\Interfaces\MQDriver;
use TRFConsumer\Interfaces\MQMessage;
use TRFConsumer\Interfaces\TRFConsumer;
use TRFConsumer\Traits\ConsumerEvents;

class Consumer implements TRFConsumer
{
    use ConsumerEvents;

    const EVENT_BASE = 'TRFConsumer.msg.';
    const EVENT_RETRY = 'retry';
    const EVENT_FAIL = 'fail';

    /**
     * @var MQDriver
     */
    private $driver;

    protected $retryCount = 5;

    /**
     * message ttl in retry in ms
     *
     * @var int
     */
    protected $ttl = 5000;

    /**
     * Consumer constructor.
     * @param MQDriver $driver
     * @param string|null $consumerTag
     */
    public function __construct(MQDriver $driver, string $consumerTag = null)
    {
        $this->driver = $driver;
        $this->registerShutdown();
    }

    /**
     * @param string $queue
     * @param callable $msgProcess
     */
    public function consume(string $queue, callable $msgProcess): void
    {
        $this->driver->init();
        $this->driver->basicConsume($queue, 'test-consumer', function (MQMessage $message) use ($msgProcess, $queue) {
            $ack = false;
            try {
                $msgProcess($message);
                $ack = true;
            } catch (ConsumeException $ex) {
                //$message->incRetryCount();
                if ($message->retryCount() < $this->retryCount) {
                    $this->toRetry($queue, $message, $ex);
                } else {
                    $this->toFail($queue, $message, $ex);
                }
                $ack = true;
            } finally {
                //preventing of loosing message when retry-fail publish fails
                if ($ack) {
                    $this->driver->ackMessage($message);
                }
            }
        });
        $this->driver->wait();
    }

    /**
     * @param string $readyQueue
     * @param MQMessage $message
     * @param ConsumeException $ex
     * @throws Exceptions\InvalidOriginalMessageException
     */
    protected function toRetry(string $readyQueue, MQMessage $message, ConsumeException $ex): void
    {
        $this->dispatchEvent(static::EVENT_BASE . static::EVENT_RETRY, new EventRetry($message, $ex));
        $this->driver->publishRetry($readyQueue, $message, ($message->retryCount() + 1) * $this->ttl);
    }

    /**
     * @param string $readyQueue
     * @param MQMessage $message
     * @param ConsumeException $ex
     * @throws Exceptions\InvalidOriginalMessageException
     */
    protected function toFail(string $readyQueue, MQMessage $message, ConsumeException $ex): void
    {
        $this->dispatchEvent(static::EVENT_BASE . static::EVENT_FAIL, new EventRetry($message, $ex));
        $message->setError(json_encode($ex));
        $this->driver->publishFail($readyQueue, $message);
    }

    /**
     *
     */
    protected function registerShutdown(): void
    {
        register_shutdown_function(function () {
            $this->driver->shutdown();
        });
    }
}
