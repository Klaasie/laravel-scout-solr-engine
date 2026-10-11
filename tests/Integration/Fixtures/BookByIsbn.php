<?php

namespace Scout\Solr\Tests\Integration\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

/**
 * Same table as Book, but uses the ISBN as its scout key and leaves `id`
 * out of its searchable array entirely.
 */
class BookByIsbn extends Model
{
    use Searchable;

    public const CORE = 'scout_books_by_isbn_test';

    public $timestamps = false;

    protected $table = 'books';

    protected $guarded = [];

    public function searchableAs(): string
    {
        return self::CORE;
    }

    public function getScoutKey(): mixed
    {
        return $this->isbn;
    }

    public function getScoutKeyName(): mixed
    {
        return 'isbn';
    }

    public function getScoutKeyType(): string
    {
        return 'string';
    }

    public function toSearchableArray(): array
    {
        return [
            'title_t' => $this->title,
            'genre_s' => $this->genre,
        ];
    }
}
