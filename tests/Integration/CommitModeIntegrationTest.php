<?php

namespace Scout\Solr\Tests\Integration;

use Illuminate\Support\Facades\Config;
use Scout\Solr\Tests\Integration\Fixtures\Book;

class CommitModeIntegrationTest extends IntegrationTestCase
{
    public function test_soft_commits_are_visible_immediately(): void
    {
        Config::set('scout-solr.update.commit', 'soft');

        $this->seedBooks();

        $this->assertSame(5, Book::search('')->raw()->getNumFound());
    }

    public function test_commit_within_makes_changes_visible_shortly_after(): void
    {
        Config::set('scout-solr.update.commit', 'within');
        Config::set('scout-solr.update.commit_within', 200);

        $this->seedBooks();

        $this->assertTrue(
            $this->eventually(fn () => Book::search('')->raw()->getNumFound() === 5),
            'Documents did not become visible within the commitWithin window.'
        );

        Book::where('title', 'Dune')->first()->delete();

        $this->assertTrue(
            $this->eventually(fn () => Book::search('')->raw()->getNumFound() === 4),
            'The delete did not become visible within the commitWithin window.'
        );
    }

    private function eventually(callable $condition, int $timeoutMs = 5000): bool
    {
        $deadline = microtime(true) + $timeoutMs / 1000;

        do {
            if ($condition()) {
                return true;
            }

            usleep(100_000);
        } while (microtime(true) < $deadline);

        return false;
    }
}
