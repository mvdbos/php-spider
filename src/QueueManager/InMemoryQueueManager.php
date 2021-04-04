<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */

namespace VDB\Spider\QueueManager;

use LogicException;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Event\DispatcherTrait;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\Exception\MaxQueueSizeExceededException;
use VDB\Spider\Uri\DiscoveredUri;

class InMemoryQueueManager implements QueueManagerInterface
{
    use DispatcherTrait;

    /** @var int The maximum size of the process queue for this spider. 0 means infinite */
    public $maxQueueSize = 0;

    /** @var int the amount of times a Resource was enqueued */
    private $currentQueueSize = 0;

    /** @var DiscoveredUri[] the list of URIs to process */
    private $traversalQueue = array();

    /** @var int The traversal algorithm to use. Choose from the class constants
     */
    private $traversalAlgorithm = self::ALGORITHM_DEPTH_FIRST;

    /**
     * @param int $traversalAlgorithm Choose from the class constants
     * TODO: This should be extracted to a Strategy pattern
     */
    public function setTraversalAlgorithm(int $traversalAlgorithm)
    {
        $this->traversalAlgorithm = $traversalAlgorithm;
    }

    /**
     * @return int
     */
    public function getTraversalAlgorithm(): int
    {
        return $this->traversalAlgorithm;
    }

    /**
     * @param DiscoveredUri $uri
     * @throws MaxQueueSizeExceededException
     */
    public function addUri(DiscoveredUri $uri)
    {
        if ($this->maxQueueSize != 0 && $this->currentQueueSize >= $this->maxQueueSize) {
            throw new MaxQueueSizeExceededException('Maximum Queue Size of ' . $this->maxQueueSize . ' reached');
        }

        $this->currentQueueSize++;
        array_push($this->traversalQueue, $uri);

        $this->getDispatcher()->dispatch(
            new GenericEvent($this, array('uri' => $uri)),
            SpiderEvents::SPIDER_CRAWL_POST_ENQUEUE
        );
    }

    public function next(): ?DiscoveredUri
    {
        if ($this->traversalAlgorithm === static::ALGORITHM_DEPTH_FIRST) {
            return array_pop($this->traversalQueue);
        } elseif ($this->traversalAlgorithm === static::ALGORITHM_BREADTH_FIRST) {
            return array_shift($this->traversalQueue);
        } else {
            throw new LogicException('No search algorithm set');
        }
    }
}
