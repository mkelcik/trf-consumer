<?php

namespace TRFConsumer\Traits;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TRFConsumer\Events\EventAbstract;

trait ConsumerEvents
{
    /**
     * @var null|EventDispatcherInterface
     */
    protected $dispatcher = null;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher){
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string $eventName
     * @param EventAbstract $event
     */
    protected function dispatchEvent(string $eventName, EventAbstract $event)
    {
        if (isset($this->dispatcher)) {
            $this->dispatcher->dispatch($eventName, $event);
        }
    }
}
