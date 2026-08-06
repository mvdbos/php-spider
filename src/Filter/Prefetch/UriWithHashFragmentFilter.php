<?php

namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
class UriWithHashFragmentFilter implements PreFetchFilterInterface
{
    public function match(DiscoveredUri $uri): bool
    {
        return null !== $uri->getFragment();
    }
}
