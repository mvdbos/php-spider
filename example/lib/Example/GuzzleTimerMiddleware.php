<?php
namespace Example;

use GuzzleHttp\Promise\FulfilledPromise;
use Psr\Http\Message\RequestInterface;

class GuzzleTimerMiddleware
{
    private float $total = 0.0;

    private float $start = 0.0;

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onRequest(RequestInterface $request, array $options)
    {
        $this->start = microtime(true);
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @param FulfilledPromise $response
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onResponse(RequestInterface $request, array $options, FulfilledPromise $response)
    {
        $duration = microtime(true) - $this->start;
        $this->total = $this->total + $duration;
    }

    public function getTotal(): float
    {
        return round($this->total, 2);
    }
}
