<?php

namespace VDB\Spider\Plugin;

use Monolog\Logger;
use Symfony\Component\EventDispatcher\Event;
use VDB\Spider\Event\SpiderEvents;
use Psr\Log\LoggerInterface;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Plugin class that will add request and response logging to an HTTP request.
 *
 * The log plugin uses a message formatter that allows custom messages via template variable substitution.
 *
 * @see MessageLogger for a list of available log template variable substitutions
 */
class DebugLogPlugin implements EventSubscriberInterface
{
    /**
     * @var Logger Adapter responsible for writing log data
     */
    private $logger;

    /**
     * Construct a new DebugLogPlugin
     *
     * @param Logger     $logger Logger object used to log message
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            SpiderEvents::SPIDER_CRAWL_FILTER_PREFETCH => array('logDebugInfo'),
            SpiderEvents::SPIDER_CRAWL_FILTER_POSTFETCH => array('logDebugInfo'),
            SpiderEvents::SPIDER_CRAWL_PRE_DISCOVER => array('logDebugInfo'),
            SpiderEvents::SPIDER_CRAWL_POST_DISCOVER => array('logDebugInfo'),
            SpiderEvents::SPIDER_CRAWL_PRE_REQUEST => array('logDebugInfo'),
            SpiderEvents::SPIDER_CRAWL_POST_REQUEST => array('logDebugInfo'),
            SpiderEvents::SPIDER_CRAWL_PRE_ENQUEUE => array('logDebugInfo'),
            SpiderEvents::SPIDER_CRAWL_PRE_PROCESS_DOCUMENT => array('logDebugInfo'),
            SpiderEvents::SPIDER_CRAWL_POST_PROCESS_DOCUMENT => array('logDebugInfo'),
            SpiderEvents::SPIDER_CRAWL_START => array('logDebugInfo'),
            SpiderEvents::SPIDER_CRAWL_END => array('logDebugInfo'),
            SpiderEvents::SPIDER_PROCESS_START => array('logDebugInfo'),
            SpiderEvents::SPIDER_PROCESS_END => array('logDebugInfo'),
        );
    }

    public function logDebugInfo(Event $event)
    {
        $this->logger->debug('Event: ' . $event->getName());
    }
}
