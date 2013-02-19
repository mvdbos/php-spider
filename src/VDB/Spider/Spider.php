<?php

namespace VDB\Spider;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Link;

class Spider
{
    /** @var Goutte\Client */
    private $client;

    /** @var Symfony\Component\DomCrawler\Crawler */
    private $initialCrawler;

    /** @var array of \Closure */
    private $processors = array();

    /** @var array of \Closure */
    private $skipConditions = array();


    public function __construct($uri, $method = 'GET')
    {
        $this->client = new Client();
        $this->initialCrawler = $this->client->request($method, $uri);
    }

    public function addProcessor(Processor $processor) {
        $this->processors[] = $processor;
    }

    public function addSkipCondition(SkipCondition $condition) {
        $this->skipConditions[] = $condition;
    }

    public function crawl()
    {
        return $this->doCrawl($this->initialCrawler);
    }

    private function doCrawl(Crawler $crawler) {
        static $previousHostname = '';
        static $visited = array();
        static $visitedStack = array();

        $currentUri = array_pop($visitedStack);

        foreach ($this->processors as $processor) {
            $processor->execute($currentUri, $crawler);
        }

        // find all links and loop
        foreach ($crawler->filterXPath("//a")->links() as $link) {
            /** @var $link Link */
            $uri = $link->getUri();
            $currentHostname = join('.', array_slice(explode('.', parse_url($uri, PHP_URL_HOST)), -2));

            foreach ($this->skipConditions as $condition) {
                /** @var $condition \VDB\Spider\SkipCondition */
                if ($condition->match($uri)) {
                    continue 2;
                }
            }

            // if we have already visited the uri, skip it
            if (array_key_exists($uri, $visited)) {
                $visited[$uri] = $visited[$uri] + 1;
                continue;
            }

            // if the link is not on the same hostname.TLD as its parent, skip it, otherwise we will spider the entire net
            // different subdomains are allowed, so we check for hostname.tld
            if (count($visited) && ($currentHostname !== $previousHostname))  {
                continue;
            }

            $visited[$uri] = 1;
            array_push($visitedStack, $uri);
            $previousHostname = $currentHostname;

            // recurse
            $this->doCrawl($this->client->click($link));
        }
    }
}