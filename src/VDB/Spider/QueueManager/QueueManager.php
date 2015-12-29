<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */

namespace VDB\Spider\QueueManager;

use VDB\Uri\UriInterface;

interface QueueManager
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
     * @param UriInterface
     * @return void
     */
    public function addUri(UriInterface $uri);

    /**
     * @return UriInterface
     */
    public function next();
}
