<?php

namespace Scout\Solr\Tests\Integration;

use Scout\Solr\Tests\Integration\Fixtures\SoftDeletableBook;

/**
 * With scout.soft_delete enabled, Scout keeps soft-deleted models in the index with a
 * __soft_deleted flag and filters on it.
 */
class SoftDeleteIntegrationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureCoreExists(SoftDeletableBook::CORE);
        SoftDeletableBook::removeAllFromSearch();

        foreach (['The Hobbit' => 1937, 'Dune' => 1965, 'Neuromancer' => 1984] as $title => $year) {
            SoftDeletableBook::create(['title' => $title, 'author' => 'Someone', 'genre' => 'novel', 'year' => $year]);
        }

        SoftDeletableBook::where('title', 'Dune')->first()->delete();
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('scout.soft_delete', true);
    }

    public function test_soft_deleted_models_are_excluded_by_default(): void
    {
        $this->assertSame(['The Hobbit', 'Neuromancer'], $this->titles(SoftDeletableBook::search('')));
    }

    public function test_with_trashed_includes_soft_deleted_models(): void
    {
        $this->assertSame(
            ['The Hobbit', 'Dune', 'Neuromancer'],
            $this->titles(SoftDeletableBook::search('')->withTrashed())
        );
    }

    public function test_only_trashed_returns_soft_deleted_models(): void
    {
        $this->assertSame(['Dune'], $this->titles(SoftDeletableBook::search('')->onlyTrashed()));
    }

    public function test_restoring_a_model_makes_it_searchable_again(): void
    {
        SoftDeletableBook::withTrashed()->where('title', 'Dune')->first()->restore();

        $this->assertSame(['The Hobbit', 'Dune', 'Neuromancer'], $this->titles(SoftDeletableBook::search('')));
    }

    public function test_force_deleting_removes_the_model_from_the_index(): void
    {
        SoftDeletableBook::withTrashed()->where('title', 'Dune')->first()->forceDelete();

        $this->assertSame(['The Hobbit', 'Neuromancer'], $this->titles(SoftDeletableBook::search('')->withTrashed()));
    }

    private function titles($builder): array
    {
        return $builder->orderBy('year_i', 'asc')->get()->pluck('title')->all();
    }
}
