<?php

namespace VDB\Spider\Event;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */
final class SpiderEvents
{
    /**
     * The spider.crawl.filter.prefetch event fires when the URI is not yet fetched and filtered
     *
     * Note: any listener for this event could stop propagation when its filter matches the event information
     * This means you can't assume your listener will be called
     *
     * @var string
     */
    const SPIDER_CRAWL_FILTER_PREFETCH = 'spider.crawl.filter.prefetch';

    /**
     * The spider.crawl.filter.postfetch event fires when the Resource is already fetched and filtered
     *
     * Note: any listener for this event could stop propagation when its filter matches the event information
     * This means you can't assume your listener will be called
     *
     * @var string
     */
    const SPIDER_CRAWL_FILTER_POSTFETCH = 'spider.crawl.filter.postfetch';

    /**
     * The spider.crawl.pre_request event fires before the Spider starts it crawl
     */
    const SPIDER_CRAWL_PRE_CRAWL = 'spider.crawl.pre_crawl';

    /**
     * The spider.crawl.pre_request event fires just before the [RequestHandlerInterface] executes
     * the request for a specific URI
     */
    const SPIDER_CRAWL_PRE_REQUEST = 'spider.crawl.pre_request';

    /**
     * The spider.crawl.post_request event fires immediately the [RequestHandlerInterface] executes
     * the request for a specific URI
     */
    const SPIDER_CRAWL_POST_REQUEST = 'spider.crawl.post_request';

    /**
     * The spider.crawl.pre_enqueue event fires after the URI was added to the queue
     *
     * The event contains an instance of the Resource being enqueued.
     * An example use case for this event would be to change the Resources queue priority based on certain rules
     *
     * Note: any listener for this event could stop propagation when its filter matches the event information
     * This means you can't assume your listener will be called
     *
     * @var string
     */
    const SPIDER_CRAWL_POST_ENQUEUE = 'spider.crawl.post.enqueue';

    const SPIDER_CRAWL_ERROR_REQUEST = 'spider.error.request';

    const SPIDER_CRAWL_RESOURCE_PERSISTED = 'spider.crawl.resource.persisted';

    const SPIDER_CRAWL_ERROR_PERSIST = 'spider.crawl.error.persist';

    /**
     * The spider.crawl.user.stopped event fires when the spider was stopped by a user action
     *
     * @var string
     */
    const SPIDER_CRAWL_USER_STOPPED = 'spider.crawl.user.stopped';
}
