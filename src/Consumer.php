<?php

namespace TRFConsumer;

use TRFConsumer\Events\EventRetry;
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

    /**
     * @var int
     */
    protected $numberOfAttempts;

    /**
     * message ttl in retry in ms
     *
     * @var int
     */
    protected $delay;

    /**
     * @var string
     */
    private $consumerTag;

    /**
     * Consumer constructor.
     * @param MQDriver $driver
     * @param string $consumerTag
     * @param int $numberOfAttempts
     * @param int $delay
     */
    public function __construct(MQDriver $driver, string $consumerTag = '', int $numberOfAttempts = 5, int $delay = 5000)
    {
        $this->driver = $driver;
        $this->numberOfAttempts = $numberOfAttempts;
        $this->delay = $delay;
        $this->consumerTag = $consumerTag;

        $this->registerShutdown();
    }

    /**
     * @param string $queue
     * @param callable $msgProcess
     */
    public function consume(string $queue, callable $msgProcess): void
    {
        $this->driver->init();
        $this->driver->basicConsume($queue, $this->consumerTag, function (MQMessage $message) use ($msgProcess, $queue) {
            try {
                $msgProcess($message);
            } catch (\Exception $ex) {
                if ($message->retryCount() < $this->numberOfAttempts) {
                    $this->toRetry($queue, $message, $ex);
                } else {
                    $this->toFail($queue, $message, $ex);
                }
            } finally {
                $this->driver->ackMessage($message);
            }
        });
        $this->driver->wait();
    }

    /**
     * @param string $readyQueue
     * @param MQMessage $message
     * @param \Exception $ex
     * @throws Exceptions\InvalidOriginalMessageException
     */
    protected function toRetry(string $readyQueue, MQMessage $message, \Exception $ex): void
    {
        $this->dispatchEvent(static::EVENT_BASE . static::EVENT_RETRY, new EventRetry($message, $ex));
        $this->driver->publishRetry($readyQueue, $message, ($message->retryCount() + 1) * $this->delay);
    }

    /**
     * @param string $readyQueue
     * @param MQMessage $message
     * @param \Exception $ex
     * @throws Exceptions\InvalidOriginalMessageException
     */
    protected function toFail(string $readyQueue, MQMessage $message, \Exception $ex): void
    {
        $this->dispatchEvent(static::EVENT_BASE . static::EVENT_FAIL, new EventRetry($message, $ex));
        $message->setError((string)json_encode(['exception' => get_class($ex), 'message' => $ex->getMessage()]));
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
