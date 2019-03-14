<?php

namespace TRFConsumer\Events;

use Symfony\Component\EventDispatcher\Event;
use TRFConsumer\Exceptions\ConsumeException;
use TRFConsumer\Interfaces\MQMessage;

abstract class EventAbstract extends Event
{
    /**
     * @var MQMessage
     */
    private $message;
    /**
     * @var \Exception
     */
    private $ex;

    public function __construct(MQMessage $message, \Exception $ex)
    {
        $this->message = $message;
        $this->ex = $ex;
    }

    public function getMessage(): MQMessage
    {
        return $this->message;
    }

    public function getException(): \Exception
    {
        return $this->ex;
    }
}
