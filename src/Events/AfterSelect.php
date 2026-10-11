<?php

namespace Scout\Solr\Events;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder;
use Solarium\QueryType\Select\Result\Result;

class AfterSelect
{
    /**
     * @param Builder<Model>|null $builder
     */
    public function __construct(
        public Result $result,
        public Model $model,
        public ?Builder $builder = null,
    ) {
    }
}
