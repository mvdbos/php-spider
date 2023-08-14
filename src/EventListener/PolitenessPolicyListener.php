<?php

namespace VDB\Spider\EventListener;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */
class PolitenessPolicyListener
{
    private ?string $previousHostname = null;

    /** @var int the delay in microseconds between requests to the same domain */
    private int $requestDelay;

    public int $totalDelay = 0;

    /**
     * @param int $requestDelay the delay in milliseconds between requests to the same domain
     */
    public function __construct(int $requestDelay)
    {
        $this->requestDelay = $requestDelay * 1000;
    }

    /**
     * @param GenericEvent $event
     */
    public function onCrawlPreRequest(GenericEvent $event): void
    {
        $currentHostname = $event->getArgument('uri')->getHost();

        if ($currentHostname === $this->previousHostname) {
            $this->totalDelay = $this->totalDelay + $this->requestDelay;
            usleep($this->requestDelay);
        }
        $this->previousHostname = $currentHostname;
    }
}
