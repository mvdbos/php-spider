<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */

namespace VDB\Spider\PersistenceHandler;

use VDB\Spider\Resource;

/**
 * A persistence handler that stores link health check results in a JSON file.
 * This handler is designed for use cases where you want to check if pages are healthy
 * (not returning 404 or other errors) without storing the full page content.
 *
 * The JSON output contains:
 * - uri: The URL that was checked
 * - status_code: HTTP status code (200, 404, 500, etc.)
 * - reason_phrase: HTTP reason phrase ("OK", "Not Found", etc.)
 * - timestamp: When the check was performed
 * - depth: The depth at which this URI was discovered
 */
class JsonHealthCheckPersistenceHandler implements PersistenceHandlerInterface
{
    /**
     * @var string the path where the JSON file should be stored
     */
    protected string $path = '';

    protected string $spiderId = '';

    /**
     * @var array Array of health check results
     */
    private array $results = [];

    /**
     * @var int Current position in the results array for iteration
     */
    private int $position = 0;

    /**
     * @param string $path the path where the JSON file should be stored
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function setSpiderId(string $spiderId): void
    {
        $this->spiderId = $spiderId;

        // Create the directory if it doesn't exist
        if (!file_exists($this->path)) {
            mkdir($this->path, 0700, true);
        }
    }

    /**
     * Get the full path to the JSON file
     */
    protected function getJsonFilePath(): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->spiderId . '_health_check.json';
    }

    public function persist(Resource $resource): void
    {
        $result = [
            'uri' => $resource->getUri()->toString(),
            'status_code' => $resource->getResponse()->getStatusCode(),
            'reason_phrase' => $resource->getResponse()->getReasonPhrase(),
            'timestamp' => date('c'),
            'depth' => $resource->getUri()->getDepthFound()
        ];

        $this->results[] = $result;

        // Write to JSON file after each persist to ensure data is saved even if script is interrupted
        $this->writeToFile();
    }

    /**
     * Write the current results to the JSON file
     */
    protected function writeToFile(): void
    {
        $jsonData = json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->getJsonFilePath(), $jsonData);
    }

    public function count(): int
    {
        return count($this->results);
    }

    /**
     * @return mixed Array element or false
     */
    public function current(): mixed
    {
        return $this->results[$this->position] ?? false;
    }

    /**
     * @return void
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * @return boolean
     */
    public function valid(): bool
    {
        return isset($this->results[$this->position]);
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->position = 0;
    }
}
