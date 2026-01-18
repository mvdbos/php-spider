<?php
/**
 * Statistics Handler
 * ==================
 * 
 * Event subscriber that collects comprehensive statistics about the crawl.
 * This class tracks all major events during the crawl process and provides
 * easy access to the collected data for reporting and analysis.
 * 
 * Tracked Statistics:
 * - Enqueued URIs: URIs discovered and added to the crawl queue
 * - Persisted Resources: Resources successfully downloaded and saved
 * - Filtered URIs: URIs skipped by prefetch or postfetch filters
 * - Failed Requests: URIs that failed to download with error messages
 * 
 * Events Subscribed:
 * - SPIDER_CRAWL_POST_ENQUEUE: When a URI is added to the queue
 * - SPIDER_CRAWL_RESOURCE_PERSISTED: When a resource is successfully saved
 * - SPIDER_CRAWL_FILTER_PREFETCH: When a URI is filtered before download
 * - SPIDER_CRAWL_FILTER_POSTFETCH: When a resource is filtered after download
 * - SPIDER_CRAWL_ERROR_REQUEST: When a request fails
 * 
 * Usage:
 * ```php
 * $statsHandler = new StatsHandler();
 * $spider->getDispatcher()->addSubscriber($statsHandler);
 * $spider->crawl();
 * echo "Downloaded: " . count($statsHandler->getPersisted());
 * ```
 * 
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
    /** @var string Spider identifier */
    protected string $spiderId;

    /** @var array Successfully persisted resources */
    protected array $persisted = array();

    /** @var array URIs added to the crawl queue */
    protected array $queued = array();

    /** @var array URIs filtered out (not crawled) */
    protected array $filtered = array();

    /** @var array Failed requests with error messages */
    protected array $failed = array();

    /**
     * Define which events this handler subscribes to
     * 
     * @return array Event name => method name mappings
     */
    public static function getSubscribedEvents(): array
    {
        return array(
            SpiderEvents::SPIDER_CRAWL_FILTER_POSTFETCH => 'addToFiltered',
            SpiderEvents::SPIDER_CRAWL_FILTER_PREFETCH => 'addToFiltered',
            SpiderEvents::SPIDER_CRAWL_POST_ENQUEUE => 'addToQueued',
            SpiderEvents::SPIDER_CRAWL_RESOURCE_PERSISTED => 'addToPersisted',
            SpiderEvents::SPIDER_CRAWL_ERROR_REQUEST => 'addToFailed'
        );
    }

    /**
     * Called when a URI is added to the queue
     * 
     * @param GenericEvent $event Event containing the URI
     */
    public function addToQueued(GenericEvent $event): void
    {
        $this->queued[] = $event->getArgument('uri');
    }

    /**
     * Called when a resource is successfully persisted
     * 
     * @param GenericEvent $event Event containing the URI
     */
    public function addToPersisted(GenericEvent $event): void
    {
        $this->persisted[] = $event->getArgument('uri');
    }

    /**
     * Called when a URI is filtered out by a prefetch or postfetch filter
     * 
     * @param GenericEvent $event Event containing the URI
     */
    public function addToFiltered(GenericEvent $event): void
    {
        $this->filtered[] = $event->getArgument('uri');
    }

    /**
     * Called when a request fails
     * Stores both the URI and the error message
     * 
     * @param GenericEvent $event Event containing the URI and error message
     */
    public function addToFailed(GenericEvent $event): void
    {
        $this->failed[$event->getArgument('uri')->toString()] = $event->getArgument('message');
    }

    /**
     * Get all URIs that were added to the queue
     * 
     * @return UriInterface[]
     */
    public function getQueued(): array
    {
        return $this->queued;
    }

    /**
     * Get all resources that were successfully persisted
     * 
     * @return UriInterface[]
     */
    public function getPersisted(): array
    {
        return $this->persisted;
    }

    /**
     * Get all URIs that were filtered out
     * 
     * @return FilterableInterface[]
     */
    public function getFiltered(): array
    {
        return $this->filtered;
    }

    /**
     * Get all failed requests with their error messages
     * 
     * @return array Array of form ['uriString' => 'error message', ...]
     */
    public function getFailed(): array
    {
        return $this->failed;
    }

    /**
     * Generate a string representation of the statistics
     * Useful for quick reporting
     * 
     * @return string Formatted statistics summary
     */
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
