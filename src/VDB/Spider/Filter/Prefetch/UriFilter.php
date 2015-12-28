<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilter;
use VDB\Spider\Uri\FilterableUri;

/**
 * @author matthijs
 */
class UriFilter implements PreFetchFilter
{
    /**
     * @var array An array of regexes
     */
    public $regexes = array();

    public function __construct(array $regexes = array())
    {
        $this->regexes = $regexes;
    }

    public function match(FilterableUri $uri)
    {
        foreach ($this->regexes as $regex) {
            if (preg_match($regex, $uri->toString())) {
                $uri->setFiltered(true, "Matched URI regex '$regex'");
                return true;
            }
        }
        return false;
    }
}
