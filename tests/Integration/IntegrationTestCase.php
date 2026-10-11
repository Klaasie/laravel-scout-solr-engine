<?php

namespace Scout\Solr\Tests\Integration;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Scout\EngineManager;
use Laravel\Scout\ScoutServiceProvider;
use Orchestra\Testbench\TestCase;
use Scout\Solr\Engines\SolrEngine;
use Scout\Solr\ScoutSolrServiceProvider;
use Scout\Solr\Tests\Integration\Fixtures\Book;
use Solarium\Exception\HttpException;

/**
 * Runs against a real Solr instance. Skipped unless SOLR_HOST is set, e.g.:
 *
 *   docker compose up -d
 *   SOLR_HOST=localhost vendor/bin/phpunit --testsuite Integration
 */
abstract class IntegrationTestCase extends TestCase
{
    protected function setUp(): void
    {
        if (!getenv('SOLR_HOST')) {
            $this->markTestSkipped('Set SOLR_HOST to run the Solr integration tests.');
        }

        parent::setUp();

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author');
            $table->string('genre');
            $table->integer('year');
            $table->string('isbn')->nullable();
        });

        $this->ensureCoreExists(Book::CORE);
        Book::removeAllFromSearch();
    }

    protected function getPackageProviders($app): array
    {
        return [ScoutServiceProvider::class, ScoutSolrServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'scout_solr_testing');
        $app['config']->set('database.connections.scout_solr_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('scout.driver', 'solr');

        // Top-level keys set here replace the package defaults, so set them in full.
        $app['config']->set('scout-solr.endpoints', [
            'default' => [
                'host' => getenv('SOLR_HOST') ?: 'localhost',
                'port' => (int) (getenv('SOLR_PORT') ?: 8983),
                'path' => '/',
                'config_set' => '_default',
            ],
        ]);
        $app['config']->set('scout-solr.unload', [
            'delete_index' => true,
            'delete_data_dir' => true,
            'delete_instance_dir' => true,
        ]);
    }

    protected function engine(): SolrEngine
    {
        return $this->app->make(EngineManager::class)->engine('solr');
    }

    protected function ensureCoreExists(string $name): void
    {
        if ($this->coreExists($name)) {
            return;
        }

        try {
            $this->engine()->createIndex($name);
        } catch (HttpException $e) {
            // Created by a concurrent run in the meantime.
            if (!$this->coreExists($name)) {
                throw $e;
            }
        }
    }

    protected function coreExists(string $name): bool
    {
        $url = sprintf(
            'http://%s:%d/solr/admin/cores?action=STATUS&wt=json&core=%s',
            getenv('SOLR_HOST') ?: 'localhost',
            (int) (getenv('SOLR_PORT') ?: 8983),
            urlencode($name)
        );

        $status = json_decode((string) file_get_contents($url), true);

        return !empty($status['status'][$name]);
    }

    protected function seedBooks(): void
    {
        Book::create(['title' => 'The Hobbit', 'author' => 'J.R.R. Tolkien', 'genre' => 'fantasy', 'year' => 1937]);
        Book::create([
            'title' => 'The Fellowship of the Ring',
            'author' => 'J.R.R. Tolkien',
            'genre' => 'fantasy',
            'year' => 1954,
        ]);
        Book::create(['title' => 'Tree and Leaf', 'author' => 'J.R.R. Tolkien', 'genre' => 'essay', 'year' => 1964]);
        Book::create([
            'title' => 'A Wizard of Earthsea',
            'author' => 'Ursula K. Le Guin',
            'genre' => 'fantasy',
            'year' => 1968,
        ]);
        Book::create(['title' => 'Dune', 'author' => 'Frank Herbert', 'genre' => 'scifi', 'year' => 1965]);
    }
}
