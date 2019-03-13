<?php

namespace TRFConsumer\Interfaces;

interface MQMessage
{
    public function retryCount(): int;

    public function body(): string;

    /**
     * Mesg body size in bytes
     *
     * @return int
     */
    public function getBodySize(): int;

    public function setWaitTime(?int $waitTime): void;

    public function setError(string $error): void;

    public function deliveryTag(): string;

    public function original();
}
