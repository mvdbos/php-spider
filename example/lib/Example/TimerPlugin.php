<?php
namespace Example;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Guzzle\Common\Event;

class TimerPlugin implements EventSubscriberInterface
{
    private $total = 0;

    private $start = 0;

    public static function getSubscribedEvents()
    {
        return array(
            'request.before_send' => 'onBeforeSend',
            'request.complete'    => 'onComplete'
        );
    }

    public function onBeforeSend(Event $event)
    {
        $this->start = microtime(true);
    }

    public function onComplete(Event $event)
    {
        $duration = microtime(true) - $this->start;
        $this->total = $this->total + $duration;
    }

    public function getTotal()
    {
        return round($this->total, 2);
    }
}