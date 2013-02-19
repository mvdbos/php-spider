<?php
namespace Example\PostFetchSelectionPolicyFilter;

use VDB\Spider\Document;
use VDB\Spider\Filter\PostFetchFilter;

/**
 * @author matthijs
 */
class PottyWordsFilter implements PostFetchFilter
{
    public function match(Document $document)
    {
        return false;
    }
}
