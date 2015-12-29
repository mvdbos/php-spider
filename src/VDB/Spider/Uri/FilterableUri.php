<?php
namespace VDB\Spider\Uri;

use VDB\Spider\FilterableInterface;
use VDB\Uri\Uri;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
class FilterableUri extends Uri implements FilterableInterface
{
    /** @var bool if the link should be skipped */
    private $isFiltered = false;

    /** @var string */
    private $filterReason = '';

    /**
     * @var int
     */
    private $depthFound;

    /**
     * @param boolean $filtered
     * @param string $reason
     */
    public function setFiltered($filtered = true, $reason = '')
    {
        $this->isFiltered = $filtered;
        $this->filterReason = $reason;
    }

    /**
     * @return boolean whether the object was filtered
     */
    public function isFiltered()
    {
        return $this->isFiltered;
    }

    /**
     * @return string
     */
    public function getFilterReason()
    {
        return $this->filterReason;
    }

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

    /**
     * Get a unique identifier for the filterable item
     * Used for reporting
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->toString();
    }
}
