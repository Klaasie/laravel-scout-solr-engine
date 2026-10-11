# Laravel Scout Apache Solr driver
[![GitHub issues](https://img.shields.io/github/issues/Klaasie/laravel-scout-solr-engine)](https://github.com/Klaasie/laravel-scout-solr-engine/issues)
[![Latest Stable Version](http://poser.pugx.org/klaasie/scout-solr-engine/v)](https://packagist.org/packages/klaasie/scout-solr-engine)
[![License](http://poser.pugx.org/klaasie/scout-solr-engine/license)](https://packagist.org/packages/klaasie/scout-solr-engine) 
[![PHP Version Require](http://poser.pugx.org/klaasie/scout-solr-engine/require/php)](https://packagist.org/packages/klaasie/scout-solr-engine)

This package provides a basic implementation of the Apache Solr search engine within Laravel Scout.

| Package | PHP | Laravel | Scout | Solarium | Solr (tested) |
|---------|-----|---------|-------|----------|---------------|
| 4.x | 8.2+ | 12, 13 | 10, 11 | 6.3, 7 | 8.11, 9, 10 (SolrCloud) |
| 3.x | 8.0+ | 9 – 12 | 9, 10 | 6 | |

Upgrading from 3.x? See [UPGRADE.md](UPGRADE.md).

## Installation

`composer require klaasie/scout-solr-engine`

Then tell Scout to use the engine in your `.env`:

```dotenv
SCOUT_DRIVER=solr
SOLR_HOST=localhost
SOLR_PORT=8983
```

### Running Solr locally

This repository includes a `docker-compose.yml` that starts Solr with the config sets in place, so `scout:index` can create cores straight away. Copy it (and the `docker/` folder) into your project, or run it from a clone of this repository:

```bash
docker compose up -d
php artisan scout:index books
```

## Usage

Add Scout's `Searchable` trait to a model. `searchableAs()` is the name of the Solr core, and `toSearchableArray()` is the document that gets indexed:

```php
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Book extends Model
{
    use Searchable;

    public function searchableAs(): string
    {
        return 'books';
    }

    public function toSearchableArray(): array
    {
        // Field names here use the dynamic field suffixes of Solr's _default
        // config set (*_t text, *_s exact string, *_i integer). With your own
        // schema, use whatever fields it defines.
        return [
            'title_t' => $this->title,
            'author_t' => $this->author,
            'genre_s' => $this->genre,
            'year_i' => $this->year,
        ];
    }
}
```

Saving, updating and deleting models keeps the core in sync, and searching works as in any Scout engine:

```php
Book::search('author_t:tolkien')
    ->where('genre_s', 'fantasy')
    ->whereIn('year_i', [1937, 1954])
    ->orderBy('year_i', 'desc')
    ->paginate(15);
```

The integration tests in [`tests/Integration`](tests/Integration) are working examples of indexing, filtering, sorting, pagination and custom scout keys against a real Solr.

## Configuration

This package provides a config file that can be modified using .env variables.  
You can initialize your own config file with: 

`php artisan vendor:publish --tag=scout-solr-config`

### scout:index

Solr 10 starts in SolrCloud mode by default. Set `SOLR_CLOUD=true` so `scout:index` creates collections through the Collections API instead of cores; `SOLR_ROUTER_NAME` and `SOLR_NUM_SHARDS` control how they're created.

By default, Solr doesn't allow indexes (cores) to be created without providing the proper folders and files on the file system first.
However, if a default config set is set up in the Solr instance this becomes possible through the API.  
The `scout:index` command will only work if the Solr instance is properly configured and the config files has the corresponding name for the config set folder.
For more information, see [https://solr.apache.org/guide/8_9/config-sets.html#config-sets](https://solr.apache.org/guide/8_9/config-sets.html#config-sets)

With the official `solr` Docker image, the config sets live outside the Solr home, so creating a core from `_default` fails until you copy them in. The included `docker-compose.yml` does this for you; with your own container, run:

`docker exec <container> cp -r /opt/solr/server/solr/configsets /var/solr/data/`

### Commits and bulk imports

By default every update is followed by a hard commit, so changes are searchable straight away. For large `scout:import` runs that is slow; choose another strategy in `.env`:

```dotenv
SOLR_COMMIT=within        # hard (default), soft, within or none
SOLR_COMMIT_WITHIN=1000   # milliseconds, used by "within"
```

- `soft` commits with every update but defers flushing to disk, so changes are still visible immediately.
- `within` lets Solr batch commits and make changes visible within the given time; the fastest option for imports.
- `none` leaves committing to the `autoCommit`/`autoSoftCommit` settings in your `solrconfig.xml`.

### Soft deletes

Scout's `soft_delete` option works as usual: soft-deleted models stay in the index with a `__soft_deleted` flag, are excluded from searches by default, and can be included with `withTrashed()` or `onlyTrashed()`. With your own schema, define `__soft_deleted` as an integer field; Solr's schemaless mode creates it automatically.

### HTTP adapter and timeouts

By default Solarium's cURL adapter is used with a 5 second timeout. Change it in `.env`:

```dotenv
SOLR_HTTP_ADAPTER=curl   # or "http" to use PHP streams instead of ext-curl
SOLR_TIMEOUT=10
SOLR_CONNECTION_TIMEOUT=2
```

To use any other Solarium adapter, such as the PSR-18 adapter, bind it in a service provider; the settings above are then ignored:

```php
use Solarium\Core\Client\Adapter\AdapterInterface;
use Solarium\Core\Client\Adapter\Psr18Adapter;

$this->app->singleton(AdapterInterface::class, fn () => new Psr18Adapter(
    $httpClient,      // any PSR-18 client
    $requestFactory,  // PSR-17 request factory
    $streamFactory    // PSR-17 stream factory
));
```

### Cores (indexes)

Within the config file a core (index) is not provided. The engine will determine which core to connect to using the `searchableAs()` method on the model.

Alternatively, if a specific model is on a different Solr instance, another configuration can be provided for this model.
It's important for the configuration key to match the `searchableAs()` of the model. In your published `config/scout-solr.php`:

```php
'endpoints' => [
    'default' => [
        // ...
    ],
    // Used for models whose searchableAs() returns 'books'
    'books' => [
        'host' => env('SOLR_BOOKS_HOST', 'solr2'),
        'port' => env('SOLR_BOOKS_PORT', 8983),
        'path' => '/',
        'config_set' => '_default',
        // 'core' => 'books_v2', // only needed when the core name differs from the key
    ],
],
```

## Searching and filtering

- The search text is sent to Solr as-is, so Solr query syntax works: `Book::search('title:dune OR author:herbert')`. An empty search matches all documents.
- `where`, `whereIn` and `whereNotIn` become a Solr filter query. String values are quoted so they match literally; numbers are sent as-is, booleans as `true`/`false` and dates in Solr's date format. Set `SOLR_ESCAPE_FILTER_VALUES=false` to send raw values instead, or use the `BeforeSelect` event for complex filters.
- On Scout 11, `where` also accepts comparison operators, which become Solr ranges: `where('year_i', '>=', 1950)` sends `year_i:[1950 TO *]`. Supported: `=`, `!=`, `<>`, `>`, `>=`, `<`, `<=`.
- `where('field', null)` matches documents without a value for that field; with `!=` it matches documents that have one.
- Documents are stored with Solr's `id` field set to the model's scout key (`getScoutKey()`), so custom scout keys work without adding `id` to `toSearchableArray()`.

## [Solarium](https://github.com/solariumphp/solarium)

This package uses [solarium/solarium](https://github.com/solariumphp/solarium) to handle requests to the solr instance.
This app is meant to be a simple implementation of the laravel/scout engine. For complex queries to the solr instance I would recommend initializing your own Solarium client and use that package.
Visit [https://solarium.readthedocs.io/en/stable/](https://solarium.readthedocs.io/en/stable/) to view the documentation of the solarium package.

For convenience, any unknown methods used on the engine will be forwarded to the solarium client.

```php
$model = new \App\Models\Book();

/** @var \Scout\Solr\Engines\SolrEngine $engine */
$engine = app(\Laravel\Scout\EngineManager::class)->engine();
$select = $engine->createSelect();
$select->setQuery('*:*');

// endpointFor() resolves the model's core and Solr instance.
$result = $engine->select($select, $engine->endpointFor($model));
```

The client is also available from the container as `Scout\Solr\Client` (or `Scout\Solr\ClientInterface`).

A search callback receives the model's endpoint in its options, so it can query the right core:

```php
Book::search('dune', function ($client, string $query, array $options) {
    $select = $client->createSelect()->setQuery($query);

    return $client->select($select, $options['endpoint']);
})->get();
```

## Events
The Solr Engine dispatches several events allowing you to hook into specific points in the engine.

| Event | Usage |
|---------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------|
|Scout\Solr\Events\BeforeSelect|Contains the Solr `Solarium\QueryType\Select\Query\Query` object and Scout `Builder` object. This event allows you to create complex queries using the Solarium package.|
|Scout\Solr\Events\AfterSelect|Contains the Solr `Solarium\QueryType\Select\Result\Result` object, the `Model` and the Scout `Builder`. This event allows you to inspect or adjust the raw Solr result before it is mapped to models.|

For example, a subscriber that shares Solr's query time with every view:

```php
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\View;
use Scout\Solr\Events\AfterSelect;
use Scout\Solr\Events\BeforeSelect;

class ShareSolrQueryTime
{
    public function subscribe(Dispatcher $events): void
    {
        // Solr only reports the query time when the response header is included.
        $events->listen(BeforeSelect::class, fn (BeforeSelect $event) => $event->query->setOmitHeader(false));

        $events->listen(AfterSelect::class, fn (AfterSelect $event) => View::share(
            'solrQueryTime',
            $event->result->getQueryTime()
        ));
    }
}
```

Register it with `Event::subscribe(ShareSolrQueryTime::class)` in a service provider.


## Testing

`vendor/bin/phpunit` runs the unit tests and `composer phpstan` runs static analysis (PHPStan level 6 with Larastan). The integration tests run against a real Solr instance and are skipped unless `SOLR_HOST` is set:

```bash
docker compose up -d
SOLR_HOST=localhost vendor/bin/phpunit --testsuite Integration
```

Set `SOLR_VERSION` to test against another Solr image tag, e.g. `SOLR_VERSION=8.11 docker compose up -d`.

They create a `scout_books_test` core from Solr's `_default` config set and clear it before each test.

CI requires 100% line coverage across the unit and integration tests together; uncovered lines are marked on the pull request. To check locally (with Solr running and pcov or Xdebug enabled):

```bash
SOLR_HOST=localhost vendor/bin/phpunit --coverage-clover clover.xml
php .github/scripts/check-coverage.php clover.xml 100
```
