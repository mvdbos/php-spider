<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author matthijs
 */
class UriWithHashFragmentFilter implements PreFetchFilterInterface
{
    public function match(DiscoveredUri $uri)
    {
        return null !== $uri->getFragment();
    }
}
