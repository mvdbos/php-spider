<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilter;
use VDB\Spider\Uri\FilterableUri;

/**
 * @author matthijs
 */
class AllowedHostsFilter implements PreFetchFilter
{
    /** @var array The hostnames to filter links with */
    private $allowedHosts;

    private $allowSubDomains;

    /**
     * @param array $seeds
     * @param bool $allowSubDomains
     */
    public function __construct(array $seeds, $allowSubDomains = false)
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

    public function match(FilterableUri $uri)
    {
        $currentHostname = $uri->getHost();

        if ($this->allowSubDomains) {
            // only use hostname.tld for comparison
            $currentHostname = join('.', array_slice(explode('.', $currentHostname), -2));
        }

        if (!in_array($currentHostname, $this->allowedHosts)) {
            $uri->setFiltered(true, 'Hostname not allowed');
            return true;
        }

        return false;
    }
}
