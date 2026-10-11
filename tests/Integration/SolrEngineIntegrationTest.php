<?php

namespace Scout\Solr\Tests\Integration;

use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use ReflectionMethod;
use Scout\Solr\Tests\Integration\Fixtures\Book;

class SolrEngineIntegrationTest extends IntegrationTestCase
{
    public function test_saved_models_are_indexed_and_found(): void
    {
        $this->seedBooks();

        $titles = Book::search('author_t:tolkien')->get()->pluck('title')->sort()->values()->all();

        $this->assertSame(['The Fellowship of the Ring', 'The Hobbit', 'Tree and Leaf'], $titles);
    }

    public function test_empty_search_matches_all_documents(): void
    {
        $this->seedBooks();

        $this->assertSame(5, Book::search('')->raw()->getNumFound());
    }

    public function test_where_filters_keep_the_search_text(): void
    {
        $this->seedBooks();

        $titles = Book::search('author_t:tolkien')
            ->where('genre_s', 'fantasy')
            ->get()->pluck('title')->sort()->values()->all();

        $this->assertSame(['The Fellowship of the Ring', 'The Hobbit'], $titles);
    }

    public function test_where_in(): void
    {
        $this->seedBooks();

        $titles = Book::search('')
            ->whereIn('genre_s', ['essay', 'scifi'])
            ->get()->pluck('title')->sort()->values()->all();

        $this->assertSame(['Dune', 'Tree and Leaf'], $titles);
    }

    public function test_empty_where_in_matches_nothing(): void
    {
        $this->seedBooks();

        $this->assertCount(0, Book::search('')->whereIn('genre_s', [])->get());
    }

    public function test_where_not_in(): void
    {
        $this->seedBooks();

        $titles = Book::search('author_t:tolkien')
            ->whereNotIn('genre_s', ['essay'])
            ->get()->pluck('title')->sort()->values()->all();

        $this->assertSame(['The Fellowship of the Ring', 'The Hobbit'], $titles);
    }

    public function test_where_operators(): void
    {
        if ((new ReflectionMethod(Builder::class, 'where'))->getNumberOfParameters() < 3) {
            $this->markTestSkipped('where() operators require laravel/scout 11 or newer.');
        }

        $this->seedBooks();

        $years = Book::search('')
            ->where('year_i', '>', 1937)
            ->where('year_i', '<=', 1965)
            ->where('genre_s', '!=', 'essay')
            ->orderBy('year_i', 'asc')
            ->get()->pluck('year')->all();

        $this->assertSame([1954, 1965], $years);
    }

    public function test_results_keep_solr_sort_order(): void
    {
        $this->seedBooks();

        $years = Book::search('')->orderBy('year_i', 'desc')->get()->pluck('year')->all();

        $this->assertSame([1968, 1965, 1964, 1954, 1937], $years);
    }

    public function test_paginate(): void
    {
        $this->seedBooks();

        $page = Book::search('')->orderBy('year_i', 'asc')->paginate(2, 'page', 2);

        $this->assertSame(5, $page->total());
        $this->assertSame([1964, 1965], $page->getCollection()->pluck('year')->all());
    }

    public function test_cursor_returns_lazy_collection_in_solr_order(): void
    {
        $this->seedBooks();

        $results = Book::search('author_t:tolkien')->orderBy('year_i', 'asc')->cursor();

        $this->assertInstanceOf(LazyCollection::class, $results);
        $this->assertSame([1937, 1954, 1964], $results->pluck('year')->all());
    }

    public function test_keys_returns_matching_ids(): void
    {
        $this->seedBooks();

        $expected = Book::where('genre', 'fantasy')->pluck('id')->sort()->values()->all();
        $keys = Book::search('')->where('genre_s', 'fantasy')->keys()
            ->map(fn ($id) => (int) $id)->sort()->values()->all();

        $this->assertSame($expected, $keys);
    }

    public function test_updating_a_model_updates_the_index(): void
    {
        $this->seedBooks();

        Book::where('title', 'Dune')->first()->update(['genre' => 'classic']);

        $this->assertCount(0, Book::search('')->where('genre_s', 'scifi')->get());
        $this->assertSame(['Dune'], Book::search('')->where('genre_s', 'classic')->get()->pluck('title')->all());
    }

    public function test_deleting_a_model_removes_it_from_the_index(): void
    {
        $this->seedBooks();

        Book::where('title', 'Dune')->first()->delete();

        $this->assertSame(4, Book::search('')->raw()->getNumFound());
        $this->assertCount(0, Book::search('title_t:dune')->get());
    }

    public function test_remove_all_from_search_flushes_the_core(): void
    {
        $this->seedBooks();

        Book::removeAllFromSearch();

        $this->assertSame(0, Book::search('')->raw()->getNumFound());
    }

    public function test_create_and_delete_index(): void
    {
        $name = 'scout_tmp_' . bin2hex(random_bytes(4));

        $this->engine()->createIndex($name);
        $this->assertTrue($this->coreExists($name));

        $this->engine()->deleteIndex($name);
        $this->assertFalse($this->coreExists($name));
    }
}
