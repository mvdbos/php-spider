<?php
namespace VDB\Spider\EventListener;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */
class PolitenessPolicyListener
{
    /** @var string */
    private $previousHostname;

    /** @var int the delay in microseconds between requests to the same domain */
    private $requestDelay;

    public $totalDelay = 0;

    /**
     * @param int $requestDelay the delay in milliseconds between requests to the same domain
     */
    public function __construct($requestDelay)
    {
        $this->requestDelay = $requestDelay * 1000;
    }

    /**
     * @param GenericEvent $event
     */
    public function onCrawlPreRequest(GenericEvent $event)
    {
        $currentHostname = $event->getArgument('uri')->getHost();

        if ($currentHostname === $this->previousHostname) {
            $this->totalDelay = $this->totalDelay + $this->requestDelay;
            usleep($this->requestDelay);
        }
        $this->previousHostname = $currentHostname;
    }
}
