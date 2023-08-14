<?php


namespace VDB\Spider\Event;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
}
