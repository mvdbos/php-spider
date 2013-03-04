<?php
namespace Example\PostFetchSelectionPolicyFilter;

use VDB\Spider\Resource;
use VDB\Spider\Filter\PostFetchFilter;

/**
 * @author matthijs
 */
class PottyWordsFilter implements PostFetchFilter
{
    public function match(Resource $document)
    {
        return false;
    }
}
