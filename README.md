# Laravel Scout Apache Solr driver
[![GitHub issues](https://img.shields.io/github/issues/Klaasie/laravel-scout-solr-engine)](https://github.com/Klaasie/laravel-scout-solr-engine/issues)
[![Latest Stable Version](http://poser.pugx.org/klaasie/scout-solr-engine/v)](https://packagist.org/packages/klaasie/scout-solr-engine)
[![License](http://poser.pugx.org/klaasie/scout-solr-engine/license)](https://packagist.org/packages/klaasie/scout-solr-engine) 
[![PHP Version Require](http://poser.pugx.org/klaasie/scout-solr-engine/require/php)](https://packagist.org/packages/klaasie/scout-solr-engine)

This package provides a basic implementation of the Apache Solr search engine within Laravel Scout.

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

`php artisan vendor:publish --provider="Scout\Solr\ScoutSolrServiceProvider"`

### scout:index

By default, Solr doesn't allow indexes (cores) to be created without providing the proper folders and files on the file system first.
However, if a default config set is set up in the Solr instance this becomes possible through the API.  
The `scout:index` command will only work if the Solr instance is properly configured and the config files has the corresponding name for the config set folder.
For more information, see [https://solr.apache.org/guide/8_9/config-sets.html#config-sets](https://solr.apache.org/guide/8_9/config-sets.html#config-sets)

With the official `solr` Docker image, the config sets live outside the Solr home, so creating a core from `_default` fails until you copy them in. The included `docker-compose.yml` does this for you; with your own container, run:

`docker exec <container> cp -r /opt/solr/server/solr/configsets /var/solr/data/`

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
        'core' => 'books',
        'config_set' => '_default',
    ],
],
```

## Searching and filtering

- The search text is sent to Solr as-is, so Solr query syntax works: `Book::search('title:dune OR author:herbert')`. An empty search matches all documents.
- `where`, `whereIn` and `whereNotIn` become a Solr filter query. String values are quoted so they match literally; numbers are sent as-is and booleans as `true`/`false`. Set `SOLR_ESCAPE_FILTER_VALUES=false` to send raw values instead, or use the `BeforeSelect` event for complex filters.
- Documents are stored with Solr's `id` field set to the model's scout key (`getScoutKey()`), so custom scout keys work without adding `id` to `toSearchableArray()`.

## [Solarium](https://github.com/solariumphp/solarium)

This package uses [solarium/solarium](https://github.com/solariumphp/solarium) to handle requests to the solr instance.
This app is meant to be a simple implementation of the laravel/scout engine. For complex queries to the solr instance I would recommend initializing your own Solarium client and use that package.
Visit [https://solarium.readthedocs.io/en/stable/](https://solarium.readthedocs.io/en/stable/) to view the documentation of the solarium package.

For convenience, any unknown methods used on the engine will be forwarded to the solarium client.

```php
$model = new \App\Models\SearchableModel();

/** @var \Scout\Solr\Engines\SolrEngine $engine */
$engine = app(\Laravel\Scout\EngineManager::class)->engine();
$select = $engine->setCore($model)->createSelect();
$select->setQuery('*:*');
$result = $engine->select($select, $engine->getEndpointFromConfig($model->searchableAs())); // getEndpointFromConfig() is only necessary when your model does not use the default solr instance.
```

## Events
The Solr Engine dispatches several events allowing you to hook into specific points in the engine.

| Event | Usage |
|---------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------|
|Scout\Solr\Events\BeforeSelect|Contains the Solr `Solarium\QueryType\Select\Query\Query` object and Scout `Builder` object. This event allows you to create complex queries using the Solarium package.|
|Scout\Solr\Events\AfterSelect|Contains the Solr `Solarium\QueryType\Select\Result\Result` object and `Model` object. This event allows you to inspect or adjust the raw Solr result before it is mapped to models.|

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

`vendor/bin/phpunit` runs the unit tests. The integration tests run against a real Solr instance and are skipped unless `SOLR_HOST` is set:

```bash
docker compose up -d
SOLR_HOST=localhost vendor/bin/phpunit --testsuite Integration
```

Set `SOLR_VERSION` to test against another Solr image tag, e.g. `SOLR_VERSION=8.11 docker compose up -d`.

They create a `scout_books_test` core from Solr's `_default` config set and clear it before each test.
