<?php

namespace Scout\Solr\Tests\Integration;

use Scout\Solr\Tests\Integration\Fixtures\Book;

class FilterEscapingIntegrationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Book::create(['title' => 'Dune', 'author' => 'Frank Herbert', 'genre' => 'science fiction', 'year' => 1965]);
        Book::create(['title' => 'Effective C++', 'author' => 'Scott Meyers', 'genre' => 'c++', 'year' => 1991]);
        Book::create(['title' => 'Neuromancer', 'author' => 'William Gibson', 'genre' => 'science', 'year' => 1984]);
    }

    public function test_where_value_with_a_space(): void
    {
        $titles = Book::search('')->where('genre_s', 'science fiction')->get()->pluck('title')->all();

        $this->assertSame(['Dune'], $titles);
    }

    public function test_where_value_with_query_syntax_characters(): void
    {
        $titles = Book::search('')->where('genre_s', 'c++')->get()->pluck('title')->all();

        $this->assertSame(['Effective C++'], $titles);
    }

    public function test_where_in_values_are_escaped(): void
    {
        $titles = Book::search('')->whereIn('genre_s', ['science fiction', 'c++'])
            ->orderBy('year_i', 'asc')->get()->pluck('title')->all();

        $this->assertSame(['Dune', 'Effective C++'], $titles);
    }

    public function test_numeric_where_values_still_match(): void
    {
        $titles = Book::search('')->where('year_i', 1984)->get()->pluck('title')->all();

        $this->assertSame(['Neuromancer'], $titles);
    }
}
