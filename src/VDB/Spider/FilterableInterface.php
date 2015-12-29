<?php
namespace VDB\Spider;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
interface FilterableInterface
{
    /**
     * @param bool $filtered
     * @param string $reason
     * @return void
     */
    public function setFiltered($filtered = true, $reason = '');

    /**
     * @return boolean whether the item matched a filter
     */
    public function isFiltered();

    /**
     * Get the reason the item was filtered
     *
     * @return string
     */
    public function getFilterReason();

    /**
     * Get a unique identifier for the filterable item
     * Used for reporting
     *
     * @return string
     */
    public function getIdentifier();
}
