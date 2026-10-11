<?php

namespace Scout\Solr\Events;

use Laravel\Scout\Builder;
use Solarium\QueryType\Select\Query\Query;

class BeforeSelect
{
    public function __construct(
        public Query $query,
        public Builder $builder,
    ) {
    }
}
