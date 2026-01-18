<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */

namespace VDB\Spider\QueueManager;

use VDB\Spider\Uri\DiscoveredUri;

interface QueueManagerInterface
{
    const ALGORITHM_DEPTH_FIRST = 0;
    const ALGORITHM_BREADTH_FIRST = 1;

    /**
     * @param int $traversalAlgorithm Choose from the class constants
     * TODO: This should be extracted to a Strategy pattern
     * @return void
     */
    public function setTraversalAlgorithm(int $traversalAlgorithm);

    /**
     * @return int
     */
    public function getTraversalAlgorithm(): int;

    /**
     * @param DiscoveredUri $uri
     */
    public function addUri(DiscoveredUri $uri);

    /**
     * @return null|DiscoveredUri
     */
    public function next(): ?DiscoveredUri;

    /**
     * @param int $maxQueueSize Maximum size of the queue. 0 means infinite
     */
    public function setMaxQueueSize(int $maxQueueSize);

    /**
     * @return int
     */
    public function getMaxQueueSize(): int;
}
