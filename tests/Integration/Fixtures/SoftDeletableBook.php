<?php

namespace Scout\Solr\Tests\Integration\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class SoftDeletableBook extends Model
{
    use Searchable;
    use SoftDeletes;

    public const CORE = 'scout_soft_deletable_books_test';

    public $timestamps = false;

    protected $table = 'books';

    protected $guarded = [];

    public function searchableAs(): string
    {
        return self::CORE;
    }

    public function toSearchableArray(): array
    {
        return [
            'title_t' => $this->title,
            'year_i' => $this->year,
        ];
    }
}
