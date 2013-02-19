<?php
use VDB\Spider\Processor;
use Symfony\Component\DomCrawler\Crawler;

require 'vendor/autoload.php';

class SkipReplyFormsCondition implements VDB\Spider\SkipCondition
{
    public function match($uri)
    {
        if (strstr($uri, 'replytocom')) {
            echo "\n - Skipped reply form " . $uri;
            return true;
        }
        return false;
    }
}

class PrintfProcessor implements Processor
{
    public $visited = array();
    /**
     * @param string $uri
     * @return boolean
     */
    public function execute($uri, Crawler $crawler)
    {
//        printf("\nProcessed: %s", $uri);
        $this->visited[] = $uri;

//        $html = '';

//        foreach ($crawler as $domElement) {
//            $html.= $domElement->ownerDocument->saveHTML();
//        }
//        echo "\n\nHTML:\n";
//        echo $html;
//        echo "\n\n";
    }
}

$skipReplyFormCondition = new SkipReplyFormsCondition();
$printfProcessor = new PrintfProcessor();

$spider = new VDB\Spider\Spider('http://blog.vandenbos.org');
$spider->addProcessor($printfProcessor);
$spider->addSkipCondition($skipReplyFormCondition);
$spider->crawl();

echo "\n\nDONE\n\n";

arsort($printfProcessor->visited);

print_r($printfProcessor->visited);