<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
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
    public function setTraversalAlgorithm($traversalAlgorithm);

    /**
     * @return int
     */
    public function getTraversalAlgorithm();

    /**
     * @param DiscoveredUri
     * @return void
     */
    public function addUri(DiscoveredUri $uri);

    /**
     * @return DiscoveredUri
     */
    public function next();
}
