<?php

namespace TRFConsumer\Exceptions;

use TRFConsumer\Interfaces\MQMessage;

class ConsumeException extends \Exception implements \JsonSerializable
{
    const KEY_ORIGIN_EXCEPTION = 'exception';

    const KEY_EXCEPTION_MESSAGE = 'message';

    /**
     * @var MQMessage
     */
    private $mqMessage;
    /**
     * @var string
     */
    private $exceptionClass;

    /**
     * ConsumeException constructor.
     * @param string $message
     * @param string $originExceptionClass
     * @param MQMessage $mqMessage
     */
    public function __construct(string $message, string $originExceptionClass, MQMessage $mqMessage)
    {
        $this->mqMessage = $mqMessage;
        $this->exceptionClass = $originExceptionClass;
        parent::__construct($message);
    }

    /**
     * @return MQMessage
     */
    public function getMQMessage(): MQMessage
    {
        return $this->mqMessage;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            self::KEY_ORIGIN_EXCEPTION => $this->exceptionClass,
            self::KEY_EXCEPTION_MESSAGE => $this->getMessage()
        ];
    }
}
