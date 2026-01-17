<?php

namespace VDB\Spider\Filter\Prefetch;

use ErrorException;
use Exception;
use Spatie\Robots\RobotsTxt;
use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Uri\Exception\UriSyntaxException;
use VDB\Uri\FileUri;
use VDB\Uri\Http;
use VDB\Uri\Uri;
use VDB\Uri\UriInterface;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
class RobotsTxtDisallowFilter implements PreFetchFilterInterface
{
    private RobotsTxt $parser;
    private ?string $userAgent;
    private Uri $seedUri;

    /**
     * @param string $seedUrl The robots.txt file will be loaded from this domain.
     * @param string|null $userAgent
     * @throws ErrorException
     * @throws UriSyntaxException
     */
    public function __construct(string $seedUrl, ?string $userAgent = null)
    {
        $this->seedUri = new Uri($seedUrl);
        $this->seedUri->normalize();
        $this->userAgent = $userAgent;
        $this->parser = new RobotsTxt(self::fetchRobotsTxt(self::extractRobotsTxtUri($seedUrl)));
    }

    /**
     * @param string $robotsUri
     * @return string
     */
    private static function fetchRobotsTxt(string $robotsUri): string
    {
        try {
            $robotsTxt = file_get_contents($robotsUri);
        } catch (Exception $e) {
            throw new FetchRobotsTxtException("Could not fetch $robotsUri: " . $e->getMessage());
        }

        return $robotsTxt;
    }

    /**
     * Clean up the URL and strip any parameters and fragments
     *
     * @param string $seedUrl
     * @return string
     *
     * @throws ErrorException
     * @throws UriSyntaxException
     */
    private static function extractRobotsTxtUri(string $seedUrl): string
    {
        $uri = new Uri($seedUrl);
        if (in_array($uri->getScheme(), FileUri::$allowedSchemes)) {
            return (new FileUri($seedUrl . '/robots.txt'))->toString();
        } elseif (in_array($uri->getScheme(), Http::$allowedSchemes)) {
            return $uri->toBaseUri()->toString() . '/robots.txt';
        } else {
            throw new ExtractRobotsTxtException(
                "Seed URL scheme must be one of " .
                implode(', ', array_merge(FileUri::$allowedSchemes, Http::$allowedSchemes))
            );
        }
    }

    public function match(UriInterface $uri): bool
    {
        // Make the uri relative to $this->seedUri, so it will match with the rules in the robots.txt
        $relativeUri = str_replace($this->seedUri->toString(), '', $uri->normalize()->toString());
        return !$this->parser->allows($relativeUri, $this->userAgent);
    }
}
