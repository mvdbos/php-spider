<?php

namespace VDB\Spider\Filter\Postfetch;

use VDB\Spider\Filter\PostFetchFilterInterface;
use VDB\Spider\Resource;

/**
 * Filters out resources that have been redirected to a host different from the original.
 *
 * This filter requires the Guzzle client to be configured with redirect tracking:
 * $client = new Client(['allow_redirects' => ['track_redirects' => true]]);
 *
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
class ExternalRedirectFilter implements PostFetchFilterInterface
{
    private bool $allowSubDomains;

    /**
     * @param bool $allowSubDomains If true, redirects to subdomains of the original host are allowed
     */
    public function __construct(bool $allowSubDomains = false)
    {
        $this->allowSubDomains = $allowSubDomains;
    }

    /**
     * Returns true if the resource should be filtered out (redirected to external host).
     *
     * @param Resource $resource
     * @return bool
     */
    public function match(Resource $resource): bool
    {
        $originalHost = $resource->getUri()->getHost();
        $effectiveUri = $resource->getEffectiveUri();
        $effectiveHost = parse_url($effectiveUri, PHP_URL_HOST);

        // If we can't determine the original or effective host, don't filter
        if ($originalHost === null || $effectiveHost === null || $effectiveHost === false) {
            return false;
        }

        // If hosts are the same, don't filter
        if (strcasecmp($originalHost, $effectiveHost) === 0) {
            return false;
        }

        // Check if redirect is to a subdomain of the original
        if ($this->allowSubDomains) {
            $originalBaseDomain = $this->getBaseDomain($originalHost);
            $effectiveBaseDomain = $this->getBaseDomain($effectiveHost);

            // If base domains match, don't filter (allow subdomain redirects)
            if (strcasecmp($originalBaseDomain, $effectiveBaseDomain) === 0) {
                return false;
            }
        }

        // Redirect went to a different host - filter it out
        return true;
    }

    /**
     * Extract the base domain (last two parts) from a hostname.
     *
     * @param string $host
     * @return string
     */
    private function getBaseDomain(string $host): string
    {
        $parts = explode('.', $host);
        return implode('.', array_slice($parts, -2));
    }
}
