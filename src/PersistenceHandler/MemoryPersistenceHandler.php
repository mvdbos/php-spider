<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */

namespace VDB\Spider\PersistenceHandler;

use VDB\Spider\Resource;

class MemoryPersistenceHandler implements PersistenceHandlerInterface
{
    /**
     * @var Resource[]
     */
    private array $resources = array();

    /**
     * @param string $spiderId
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSpiderId(string $spiderId): void
    {
        // memory handler ignores this. Only interesting for true persistence as some kind of key or prefix
    }

    public function count(): int
    {
        return count($this->resources);
    }

    public function persist(Resource $resource): bool
    {
        $this->resources[] = $resource;
        return true;
    }

    /**
     * @return mixed Returns Resource or false
     * @suppress PhanTypeMismatchDeclaredReturn Can be fixed by setting return type to mixed when lowest PHP version is 8
     */
    public function current(): Resource
    {
        return current($this->resources);
    }

    /**
     * @return void Any returned value is ignored.
     */
    public function next(): void
    {
        next($this->resources);
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return key($this->resources);
    }

    /**
     * @return boolean
     */
    public function valid(): bool
    {
        return (bool)current($this->resources);
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        reset($this->resources);
    }
}
