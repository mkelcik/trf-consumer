<?php

namespace TRFConsumer\Interfaces;

use TRFConsumer\Exceptions\InvalidOriginalMessageException;

interface MQDriver
{
    public function init(): void;

    /**
     * Consumer waits for messages
     */
    public function wait(): void;

    /**
     * Start basic messages consume
     *
     * @param string $queue
     * @param string $consumerTag
     * @param callable $process
     */
    public function basicConsume(string $queue, string $consumerTag, callable $process): void;

    /**
     * Graceful shutdown
     */
    public function shutdown(): void;

    /**
     * @param string $queue
     * @param MQMessage $message
     * @param int $ttl
     * @throws InvalidOriginalMessageException
     */
    public function publishRetry(string $queue, MQMessage $message, int $ttl): void;

    /**
     * @param string $queue
     * @param MQMessage $message
     * @throws InvalidOriginalMessageException
     */
    public function publishFail(string $queue, MQMessage $message): void;

    /**
     * Acknowledge message
     *
     * @param MQMessage $message
     */
    public function ackMessage(MQMessage $message): void;
}
