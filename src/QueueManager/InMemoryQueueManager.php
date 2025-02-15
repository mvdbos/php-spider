<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */

namespace VDB\Spider\QueueManager;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Event\DispatcherTrait;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\Exception\MaxQueueSizeExceededException;
use VDB\Spider\Logging\LoggingTrait;
use VDB\Spider\Uri\DiscoveredUri;

class InMemoryQueueManager implements QueueManagerInterface
{
    use DispatcherTrait, LoggingTrait;

    /** @var int The maximum size of the process queue for this spider. 0 means infinite */
    public int $maxQueueSize = 0;

    /** @var int the amount of times a Resource was enqueued */
    private int $currentQueueSize = 0;

    /** @var DiscoveredUri[] the list of URIs to process */
    private array $traversalQueue = array();

    /** @var int The traversal algorithm to use. Choose from the class constants
     */
    private int $traversalAlgorithm = self::ALGORITHM_DEPTH_FIRST;

    /**
     * InMemoryQueueManager constructor.
     * @param int $traversalAlgorithm
     */
    public function __construct(int $traversalAlgorithm = self::ALGORITHM_DEPTH_FIRST, LoggerInterface $logger = null)
    {
        if (null !== $logger) {
            $this->setLogger($logger);
        }
        $this->setTraversalAlgorithm($traversalAlgorithm);
    }

    /**
     * @return int
     */
    public function getTraversalAlgorithm(): int
    {
        return $this->traversalAlgorithm;
    }

    /**
     * @param int $traversalAlgorithm Choose from the class constants
     */
    public function setTraversalAlgorithm(int $traversalAlgorithm): void
    {
        if ($traversalAlgorithm != QueueManagerInterface::ALGORITHM_DEPTH_FIRST
            && $traversalAlgorithm != QueueManagerInterface::ALGORITHM_BREADTH_FIRST) {
            throw new InvalidArgumentException("Invalid traversal algorithm. See QueueManagerInterface for options.");
        }
        $this->traversalAlgorithm = $traversalAlgorithm;
    }

    /**
     * @param DiscoveredUri $uri
     * @throws MaxQueueSizeExceededException
     */
    public function addUri(DiscoveredUri $uri): void
    {
        if ($this->maxQueueSize != 0 && $this->currentQueueSize >= $this->maxQueueSize) {
            throw new MaxQueueSizeExceededException('Maximum Queue Size of ' . $this->maxQueueSize . ' reached');
        }

        $this->currentQueueSize++;
        $this->traversalQueue[] = $uri;

        $this->getDispatcher()->dispatch(
            new GenericEvent($this, array('uri' => $uri)),
            SpiderEvents::SPIDER_CRAWL_POST_ENQUEUE
        );
    }

    public function next(): ?DiscoveredUri
    {
        $uri = null;
        if ($this->traversalAlgorithm === static::ALGORITHM_DEPTH_FIRST) {
            $uri = array_pop($this->traversalQueue);
        } elseif ($this->traversalAlgorithm === static::ALGORITHM_BREADTH_FIRST) {
            $uri = array_shift($this->traversalQueue);
        }
        return $uri;
    }
}
