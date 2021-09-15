<?php

namespace Scout\Solr\Events;

use Solarium\QueryType\Select\Query\Query;

class BeforeSelect
{
    public Query $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }
}
