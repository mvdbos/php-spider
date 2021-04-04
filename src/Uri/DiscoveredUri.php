<?php

namespace VDB\Spider\Uri;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */
class DiscoveredUri extends UriDecorator
{
    /** @var int */
    private $depthFound;

    /**
     * @return int The depth this Uri was found on
     */
    public function getDepthFound(): ?int
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
