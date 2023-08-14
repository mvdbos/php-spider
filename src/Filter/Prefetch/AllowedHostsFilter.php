<?php

namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Uri\UriInterface;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
class AllowedHostsFilter implements PreFetchFilterInterface
{
    /** @var array The hostnames to filter links with */
    private array $allowedHosts;

    private bool $allowSubDomains;

    /**
     * @param string[] $seeds
     * @param bool $allowSubDomains
     */
    public function __construct(array $seeds, bool $allowSubDomains = false)
    {
        $this->allowSubDomains = $allowSubDomains;

        foreach ($seeds as $seed) {
            $hostname = parse_url($seed, PHP_URL_HOST);

            if ($this->allowSubDomains) {
                // only use hostname.tld for comparison
                $this->allowedHosts[] = join('.', array_slice(explode('.', $hostname), -2));
            } else {
                // user entire *.hostname.tld for comparison
                $this->allowedHosts[] = $hostname;
            }
        }
    }

    public function match(UriInterface $uri): bool
    {
        $currentHostname = $uri->getHost();

        if ($this->allowSubDomains) {
            // only use hostname.tld for comparison
            $currentHostname = join('.', array_slice(explode('.', $currentHostname), -2));
        }

        return !in_array($currentHostname, $this->allowedHosts);
    }
}
