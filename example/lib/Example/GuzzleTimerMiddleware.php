<?php
/**
 * Guzzle Timer Middleware
 * ========================
 * 
 * Guzzle middleware that tracks the total time spent on HTTP requests.
 * This is useful for performance analysis and identifying bottlenecks.
 * 
 * How It Works:
 * - onRequest(): Called before each request, records start time
 * - onResponse(): Called after each response, calculates duration
 * - getTotal(): Returns cumulative time spent on all requests
 * 
 * Usage:
 * ```php
 * $timerMiddleware = new GuzzleTimerMiddleware();
 * $guzzleClient = $spider->getDownloader()->getRequestHandler()->getClient();
 * $tapMiddleware = Middleware::tap(
 *     [$timerMiddleware, 'onRequest'],
 *     [$timerMiddleware, 'onResponse']
 * );
 * $guzzleClient->getConfig('handler')->push($tapMiddleware, 'timer');
 * 
 * // After crawling
 * echo "Request time: " . $timerMiddleware->getTotal() . "s\n";
 * ```
 * 
 * Performance Metrics:
 * This middleware helps break down where time is spent:
 * - Total Time = Request Time + Politeness Delay + Processing Time
 * - Request Time = Time spent waiting for HTTP responses (from this middleware)
 * - Politeness Delay = Time spent in politeness delays
 * - Processing Time = Time spent processing responses
 */
namespace Example;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;

class GuzzleTimerMiddleware
{
    /** @var float Total time spent on all requests (in seconds) */
    private float $total = 0.0;

    /** @var float Start time of current request (microtime) */
    private float $start = 0.0;

    /**
     * Called before each HTTP request
     * Records the start time
     * 
     * @param RequestInterface $request The HTTP request
     * @param array $options Request options
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onRequest(RequestInterface $request, array $options): void
    {
        $this->start = microtime(true);
    }

    /**
     * Called after each HTTP response
     * Calculates duration and adds to total
     * 
     * @param RequestInterface $request The HTTP request
     * @param array $options Request options
     * @param PromiseInterface $response The HTTP response promise
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onResponse(RequestInterface $request, array $options, PromiseInterface $response): void
    {
        $duration = microtime(true) - $this->start;
        $this->total = $this->total + $duration;
    }

    /**
     * Get the total time spent on all HTTP requests
     * 
     * @return float Total time in seconds (rounded to 2 decimal places)
     */
    public function getTotal(): float
    {
        return round($this->total, 2);
    }
}
