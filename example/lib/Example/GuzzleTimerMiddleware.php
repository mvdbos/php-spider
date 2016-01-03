<?php
namespace Example;

use GuzzleHttp\Promise\FulfilledPromise;
use Psr\Http\Message\RequestInterface;

class GuzzleTimerMiddleware
{
    private $total = 0;

    private $start = 0;

    public function onRequest(RequestInterface $request, array $options)
    {
        $this->start = microtime(true);
    }

    public function onResponse(RequestInterface $request, array $options, FulfilledPromise $response)
    {
        $duration = microtime(true) - $this->start;
        $this->total = $this->total + $duration;
    }

    public function getTotal()
    {
        return round($this->total, 2);
    }
}
