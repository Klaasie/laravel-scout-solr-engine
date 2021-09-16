<?php

namespace Scout\Solr\Events;

use Illuminate\Database\Eloquent\Model;
use Solarium\QueryType\Select\Result\Result;

class AfterSelect
{
    public Result $result;
    public Model $model;

    public function __construct(Result $result, Model $model)
    {
        $this->result = $result;
        $this->model = $model;
    }
}
