<?php
namespace VDB\Spider\Event;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
final class SpiderEvents
{
    /**
     * The spider.crawl.filter.prefetch event fires when the URI is not yet fetched and is not yet filtered
     *
     * The event listener receives an
     * VDB\Spider\Subscriber\CrawlFilterPreFetchEvent instance.
     *
     * Note: any listener for this event could stop propagation when its filter matches the event information
     * This means you can't assume your listener will be called
     *
     * @var string
     */
    const SPIDER_CRAWL_FILTER_PREFETCH = 'spider.crawl.filter.prefetch';

    /**
     * The spider.crawl.filter.postfetch event fires when the Resource is already fetched and is not yet filtered
     *
     * The event listener receives an
     * VDB\Spider\Subscriber\CrawlFilterPostFetchEvent instance.
     *
     * Note: any listener for this event could stop propagation when its filter matches the event information
     * This means you can't assume your listener will be called
     *
     * @var string
     */
    const SPIDER_CRAWL_FILTER_POSTFETCH = 'spider.crawl.filter.postfetch';

    const SPIDER_CRAWL_PRE_DISCOVER = 'spider.crawl.pre_discover';

    const SPIDER_CRAWL_POST_DISCOVER = 'spider.crawl.post_discover';

    const SPIDER_CRAWL_PRE_REQUEST = 'spider.crawl.pre_request';

    const SPIDER_CRAWL_POST_REQUEST = 'spider.crawl.post_request';

    /**
     * The spider.crawl.pre_enqueue event fires when the Resource is ready to be enqueued
     *
     * The event listener receives an
     * VDB\Spider\Subscriber\CrawlPreEnqueueEvent instance.
     *
     * The event contains an instance of the Resource being enqueued.
     * An example use case for this event would be to change the Resources queue priority based on certain rules
     *
     * Note: any listener for this event could stop propagation when its filter matches the event information
     * This means you can't assume your listener will be called
     *
     * @var string
     */
    const SPIDER_CRAWL_PRE_ENQUEUE = 'spider.crawl.pre_enqueue';

    const SPIDER_CRAWL_START = 'spider.crawl.start';

    const SPIDER_CRAWL_END = 'spider.crawl.end';

    const SPIDER_CRAWL_ERROR_REQUEST = 'spider.error.request';
}
