<?php


namespace VDB\Spider\Event;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

trait DispatcherTrait
{
    private ?EventDispatcherInterface $dispatcher = null;

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher(): EventDispatcherInterface
    {
        if ($this->dispatcher == null) {
            $this->dispatcher = new EventDispatcher();
        }

        return $this->dispatcher;
    }

    /**
     * A shortcut for EventDispatcher::dispatch()
     *
     * @param GenericEvent $event
     * @param string $eventName
     */
    public function dispatch(GenericEvent $event, string $eventName): void
    {
        $this->getDispatcher()->dispatch($event, $eventName);
    }
}
