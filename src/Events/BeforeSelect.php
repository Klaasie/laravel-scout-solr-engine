<?php

namespace Scout\Solr\Events;

use Laravel\Scout\Builder;
use Solarium\QueryType\Select\Query\Query;

class BeforeSelect
{
    public Query $query;
    public Builder $builder;

    public function __construct(Query $query, Builder $builder)
    {
        $this->query = $query;
        $this->builder = $builder;
    }
}
