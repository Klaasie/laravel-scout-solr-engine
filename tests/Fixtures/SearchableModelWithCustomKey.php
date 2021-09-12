<?php

namespace Scout\Solr\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class SearchableModelWithCustomKey extends Model
{
    use Searchable;

    protected $fillable = ['other_id'];

    public function getScoutKey(): string
    {
        return 'custom-key.' . $this->other_id;
    }

    public function getScoutKeyName(): string
    {
        return $this->qualifyColumn('other_id');
    }
}
