<?php

namespace Scout\Solr\Events;

use Laravel\Scout\Builder;
use Solarium\QueryType\Select\Query\Query;

class BeforeSelect
{
    /**
     * @param Builder<\Illuminate\Database\Eloquent\Model> $builder
     */
    public function __construct(
        public Query $query,
        public Builder $builder,
    ) {
    }
}
