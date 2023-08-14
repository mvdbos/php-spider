<?php
namespace VDB\Spider;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */
interface FilterableInterface
{
    /**
     * @param bool $filtered
     * @param string $reason
     * @return void
     */
    public function setFiltered(bool $filtered = true, string $reason = ''): void;

    /**
     * @return boolean whether the item matched a filter
     */
    public function isFiltered(): bool;

    /**
     * Get the reason the item was filtered
     *
     * @return string
     */
    public function getFilterReason(): string;

    /**
     * Get a unique identifier for the filterable item
     * Used for reporting
     *
     * @return string
     */
    public function getIdentifier(): string;
}
