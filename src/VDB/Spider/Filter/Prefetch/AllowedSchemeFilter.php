<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilter;
use VDB\Spider\Uri\FilterableUri;

/**
 * @author matthijs
 */
class AllowedSchemeFilter implements PreFetchFilter
{
    private $allowedSchemes = array();

    /**
     * @param string[] $schemes
     */
    public function __construct(array $schemes)
    {
        $this->allowedSchemes = $schemes;
    }

    /**
     * @return bool
     */
    public function match(FilterableUri $uri)
    {
        $scheme = $uri->getScheme();
        if (!in_array($scheme, $this->allowedSchemes)) {
            $uri->setFiltered(true, 'Scheme not allowed');
            return true;
        }
        return false;
    }
}
