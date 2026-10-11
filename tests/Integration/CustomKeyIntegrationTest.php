<?php

namespace Scout\Solr\Tests\Integration;

use Scout\Solr\Tests\Integration\Fixtures\BookByIsbn;

class CustomKeyIntegrationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureCoreExists(BookByIsbn::CORE);
        BookByIsbn::removeAllFromSearch();
    }

    public function test_models_with_a_custom_key_are_found(): void
    {
        $this->seedIsbnBooks();

        $isbns = BookByIsbn::search('')->where('genre_s', 'fantasy')
            ->get()->pluck('isbn')->sort()->values()->all();

        $this->assertSame(['978-0261102217', '978-0547928227'], $isbns);
    }

    public function test_keys_returns_custom_keys(): void
    {
        $this->seedIsbnBooks();

        $keys = BookByIsbn::search('title_t:dune')->keys()->all();

        $this->assertSame(['978-0441013593'], $keys);
    }

    public function test_deleting_a_model_with_a_custom_key_removes_it(): void
    {
        $this->seedIsbnBooks();

        BookByIsbn::where('isbn', '978-0441013593')->first()->delete();

        $this->assertSame(2, BookByIsbn::search('')->raw()->getNumFound());
        $this->assertCount(0, BookByIsbn::search('title_t:dune')->get());
    }

    private function seedIsbnBooks(): void
    {
        foreach (
            [
                ['The Hobbit', 'fantasy', 1937, '978-0547928227'],
                ['The Fellowship of the Ring', 'fantasy', 1954, '978-0261102217'],
                ['Dune', 'scifi', 1965, '978-0441013593'],
            ] as [$title, $genre, $year, $isbn]
        ) {
            BookByIsbn::create([
                'title' => $title,
                'author' => 'Someone',
                'genre' => $genre,
                'year' => $year,
                'isbn' => $isbn,
            ]);
        }
    }
}
