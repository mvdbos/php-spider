<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */

namespace Example;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Event\SpiderEvents;
use VDB\Uri\UriInterface;

class StatsHandler implements EventSubscriberInterface
{
    /** @var string */
    protected string $spiderId;

    protected array $persisted = array();

    protected array $queued = array();

    protected array $filtered = array();

    protected array $failed = array();

    public static function getSubscribedEvents(): array
    {
        return array(
            SpiderEvents::SPIDER_CRAWL_FILTER_POSTFETCH => 'addToFiltered',
            SpiderEvents::SPIDER_CRAWL_FILTER_PREFETCH => 'addToFiltered',
            SpiderEvents::SPIDER_CRAWL_POST_ENQUEUE => 'addToQueued',
            SpiderEvents::SPIDER_CRAWL_RESOURCE_PERSISTED => 'addToPersisted',
            SpiderEvents::SPIDER_CRAWL_ERROR_REQUEST => 'addToFailed',
            SpiderEvents::SPIDER_CRAWL_ERROR_PERSIST => 'addToFailed'
        );
    }

    public function addToQueued(GenericEvent $event): void
    {
        $this->queued[] = $event->getArgument('uri');
    }

    public function addToPersisted(GenericEvent $event): void
    {
        $this->persisted[] = $event->getArgument('uri');
    }

    public function addToFiltered(GenericEvent $event): void
    {
        $this->filtered[] = $event->getArgument('uri');
    }

    public function addToFailed(GenericEvent $event): void
    {
        $this->failed[$event->getArgument('uri')->toString()] = $event->getArgument('message');
    }

    /**
     * @return UriInterface[]
     */
    public function getQueued(): array
    {
        return $this->queued;
    }

    /**
     * @return UriInterface[]
     */
    public function getPersisted(): array
    {
        return $this->persisted;
    }

    /**
     * @return FilterableInterface[]
     */
    public function getFiltered(): array
    {
        return $this->filtered;
    }

    /**
     * @return array of form array($uriString, $reason)
     */
    public function getFailed(): array
    {
        return $this->failed;
    }

    public function toString(): string
    {
        $spiderId = $this->getSpiderId();
        $queued = $this->getQueued();
        $filtered = $this->getFiltered();
        $failed = $this->getFailed();

        $string = '';

        $string .= "\n\nSPIDER ID: " . $spiderId;
        $string .= "\n  ENQUEUED:  " . count($queued);
        $string .= "\n  SKIPPED:   " . count($filtered);
        $string .= "\n  FAILED:    " . count($failed);

        return $string;
    }
}
