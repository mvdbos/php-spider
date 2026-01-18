<?php
/**
 * Log Handler
 * ===========
 * 
 * Event subscriber that logs crawl events to the console.
 * Useful for debugging and monitoring the crawl process in real-time.
 * 
 * Features:
 * - Logs when URIs are queued, persisted, filtered, or fail
 * - Configurable debug mode (off by default)
 * - Subscribes to the same events as StatsHandler
 * 
 * Debug Mode:
 * When debug mode is enabled, every event is logged with the URI.
 * When disabled, events are silently tracked (useful when using other monitoring).
 * 
 * Events Logged:
 * - SPIDER_CRAWL_POST_ENQUEUE: URI added to queue
 * - SPIDER_CRAWL_RESOURCE_PERSISTED: Resource saved
 * - SPIDER_CRAWL_FILTER_PREFETCH: URI filtered before download
 * - SPIDER_CRAWL_FILTER_POSTFETCH: Resource filtered after download
 * - SPIDER_CRAWL_ERROR_REQUEST: Request failed
 * 
 * Usage:
 * ```php
 * // Without debug mode (silent)
 * $logHandler = new LogHandler();
 * $spider->getDispatcher()->addSubscriber($logHandler);
 * 
 * // With debug mode (verbose)
 * $logHandler = new LogHandler(true);
 * $spider->getDispatcher()->addSubscriber($logHandler);
 * ```
 * 
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */

namespace Example;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Uri\UriInterface;
use VDB\Spider\Event\SpiderEvents;

class LogHandler implements EventSubscriberInterface
{
    /** @var bool Enable/disable debug logging */
    private $debug = false;

    /**
     * Constructor
     * 
     * @param bool $debug Enable debug mode for verbose logging
     */
    public function __construct($debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * Define which events this handler subscribes to
     * 
     * @return array Event name => method name mappings
     */
    public static function getSubscribedEvents(): array
    {
        return array(
            SpiderEvents::SPIDER_CRAWL_FILTER_POSTFETCH => 'logFiltered',
            SpiderEvents::SPIDER_CRAWL_FILTER_PREFETCH => 'logFiltered',
            SpiderEvents::SPIDER_CRAWL_POST_ENQUEUE => 'logQueued',
            SpiderEvents::SPIDER_CRAWL_RESOURCE_PERSISTED => 'logPersisted',
            SpiderEvents::SPIDER_CRAWL_ERROR_REQUEST => 'logFailed'
        );
    }

    /**
     * Internal method to log events to console
     * Only logs if debug mode is enabled
     * 
     * @param string $name Event name for display
     * @param GenericEvent $event Event containing the URI
     */
    protected function logEvent($name, GenericEvent $event)
    {
        if ($this->debug === true) {
            echo "\n[$name]\t:" . $event->getArgument('uri')->toString();
        }
    }

    public function logQueued(GenericEvent $event)
    {
        $this->logEvent('queued', $event);
    }

    public function logPersisted(GenericEvent $event)
    {
        $this->logEvent('persisted', $event);
    }

    public function logFiltered(GenericEvent $event)
    {
        $this->logEvent('filtered', $event);
    }

    public function logFailed(GenericEvent $event)
    {
        $this->logEvent('failed', $event);
    }
}
