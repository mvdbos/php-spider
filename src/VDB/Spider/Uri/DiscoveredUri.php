<?php
namespace VDB\Spider\Uri;

use VDB\Spider\Uri\UriDecorator;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
class DiscoveredUri extends UriDecorator
{
    /** @var int */
    private $depthFound;

    /**
     * @return int The depth this Uri was found on
     */
    public function getDepthFound()
    {
        return $this->depthFound;
    }

    /**
     * @param int The depth this Uri was found on
     */
    public function setDepthFound($depthFound)
    {
        $this->depthFound = $depthFound;
    }
}
