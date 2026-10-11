<?php

namespace Scout\Solr\Tests\Integration\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Book extends Model
{
    use Searchable;

    public const CORE = 'scout_books_test';

    public $timestamps = false;

    protected $guarded = [];

    public function searchableAs(): string
    {
        return self::CORE;
    }

    /**
     * Field names use the dynamic field suffixes from Solr's _default config set,
     * so the core needs no schema changes: *_t is text, *_s is an exact string,
     * *_i is a sortable integer.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title_t' => $this->title,
            'author_t' => $this->author,
            'genre_s' => $this->genre,
            'year_i' => $this->year,
        ];
    }
}
