<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */

namespace VDB\Spider\QueueManager;

use VDB\Spider\Uri\DiscoveredUri;
use VDB\Spider\Exception\QueueException;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Event\SpiderEvents;

class InMemoryQueueManager implements QueueManagerInterface
{
    /** @var int The maximum size of the process queue for this spider. 0 means infinite */
    public $maxQueueSize = 0;

    /** @var int the amount of times a Resource was enqueued */
    private $currentQueueSize = 0;

    /** @var DiscoveredUri[] the list of URIs to process */
    private $traversalQueue = array();

    /** @var int The traversal algorithm to use. Choose from the class constants
     */
    private $traversalAlgorithm = self::ALGORITHM_DEPTH_FIRST;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /**
     * @param int $traversalAlgorithm Choose from the class constants
     * TODO: This should be extracted to a Strategy pattern
     */
    public function setTraversalAlgorithm($traversalAlgorithm)
    {
        $this->traversalAlgorithm = $traversalAlgorithm;
    }

    /**
     * @return int
     */
    public function getTraversalAlgorithm()
    {
        return $this->traversalAlgorithm;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @return $this
     */
    public function setDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->dispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher()
    {
        if (!$this->dispatcher) {
            $this->dispatcher = new EventDispatcher();
        }
        return $this->dispatcher;
    }

    /**
     * @param DiscoveredUri
     */
    public function addUri(DiscoveredUri $uri)
    {
        if ($this->maxQueueSize != 0 && $this->currentQueueSize >= $this->maxQueueSize) {
            throw new QueueException('Maximum Queue Size of ' . $this->maxQueueSize . ' reached');
        }

        $this->currentQueueSize++;
        array_push($this->traversalQueue, $uri);

        $this->getDispatcher()->dispatch(
            SpiderEvents::SPIDER_CRAWL_POST_ENQUEUE,
            new GenericEvent($this, array('uri' => $uri))
        );
    }

    public function next()
    {
        if ($this->traversalAlgorithm === static::ALGORITHM_DEPTH_FIRST) {
            return array_pop($this->traversalQueue);
        } elseif ($this->traversalAlgorithm === static::ALGORITHM_BREADTH_FIRST) {
            return array_shift($this->traversalQueue);
        } else {
            throw new \LogicException('No search algorithm set');
        }
    }
}
