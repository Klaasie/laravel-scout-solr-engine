<?php

namespace Scout\Solr\Events;

use Solarium\QueryType\Select\Result\Result;

class AfterSelect
{
    public Result $result;

    public function __construct(Result $result)
    {
        $this->result = $result;
    }
}
