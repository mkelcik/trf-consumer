<?php

namespace TRFDrivers\RabbitMQ;


use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use TRFConsumer\Interfaces\MQMessage;

class Message implements MQMessage
{
    const APPLICATION_HEADERS = 'application_headers';
    const X_DEATH = 'x-death';
    const COUNT = 'count';

    const FAIL_REASON = 'fail-reason';

    const DELIVERY_TAG = 'delivery_tag';

    /**
     * @var AMQPMessage
     */
    private $message;

    public function __construct(AMQPMessage $message)
    {
        $this->message = $message;
    }

    public function retryCount(): int
    {
        $xDeath = $this->getHeaders()[static::X_DEATH] ?? [];
        $record = reset($xDeath);
        return $record[static::COUNT] ?? 0;
    }

    public function body(): string
    {
        return $this->message->getBody();
    }

    public function getBodySize(): int
    {
        return $this->message->getBodySize();
    }

    public function setWaitTime(?int $waitTime): void
    {
        $this->message->set('expiration', $waitTime);
    }

    public function setError(string $error): void
    {
        /** @var AMQPTable $headers */
        $headers = $this->message->get(static::APPLICATION_HEADERS);
        $headers->set(static::FAIL_REASON, $error, AMQPTable::T_STRING_LONG);
        $this->message->set(static::APPLICATION_HEADERS, $headers);
    }

    public function deliveryTag(): string
    {
        return $this->message->delivery_info[self::DELIVERY_TAG];
    }

    protected function getHeaders(): array
    {
        return $this->message->has(static::APPLICATION_HEADERS) ? $this->message->get(static::APPLICATION_HEADERS)->getNativeData() : [];
    }

    /**
     * @return AMQPMessage
     */
    public function original()
    {
        return $this->message;
    }
}
