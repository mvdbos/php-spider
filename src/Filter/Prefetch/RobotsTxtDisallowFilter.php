<?php

namespace VDB\Spider\Filter\Prefetch;

use Exception;
use Spatie\Robots\RobotsTxt;
use Uri\Rfc3986\Uri;
use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
class RobotsTxtDisallowFilter implements PreFetchFilterInterface
{
    private const FILE_SCHEMES = ['file'];
    private const HTTP_SCHEMES = ['http', 'https'];

    private RobotsTxt $parser;
    private ?string $userAgent;
    private Uri $seedUri;

    /**
     * @param string $seedUrl The robots.txt file will be loaded from this domain.
     * @param string|null $userAgent
     */
    public function __construct(string $seedUrl, ?string $userAgent = null)
    {
        $this->seedUri = new Uri($seedUrl);
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
     */
    private static function extractRobotsTxtUri(string $seedUrl): string
    {
        $uri = new Uri($seedUrl);
        if (in_array($uri->getScheme(), self::FILE_SCHEMES, true)) {
            return (new Uri($seedUrl . '/robots.txt'))->toString();
        } elseif (in_array($uri->getScheme(), self::HTTP_SCHEMES, true)) {
            return $uri->withPath('/robots.txt')->withQuery(null)->withFragment(null)->toString();
        } else {
            throw new ExtractRobotsTxtException(
                "Seed URL scheme must be one of " .
                implode(', ', array_merge(self::FILE_SCHEMES, self::HTTP_SCHEMES))
            );
        }
    }

    public function match(DiscoveredUri $uri): bool
    {
        // Make the uri relative to $this->seedUri, so it will match with the rules in the robots.txt
        $relativeUri = str_replace($this->seedUri->toString(), '', $uri->toString());
        return !$this->parser->allows($relativeUri, $this->userAgent);
    }
}
