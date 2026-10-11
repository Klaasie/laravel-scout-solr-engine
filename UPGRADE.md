# Upgrade guide

## From 3.x to 4.0

### Requirements

4.0 requires PHP 8.2+, Laravel 12 or 13, Laravel Scout 10 or 11, and Solarium 6.3 or 7. PHP 8.0/8.1, Laravel 9–11 and Scout 9 are no longer supported; stay on 3.x if you need them.

### Filter values are quoted

String values passed to `where`, `whereIn` and `whereNotIn` are now quoted, so `where('genre', 'science fiction')` matches that exact value. Numbers are sent unchanged, booleans as `true`/`false`, dates in Solr's date format.

If you passed raw Solr syntax as a value, for example a range like `where('year', '[1950 TO 1960]')`, either:
- use Scout 11's operators: `->where('year', '>=', 1950)->where('year', '<=', 1960)`,
- build the filter in a `BeforeSelect` listener, or
- set `SOLR_ESCAPE_FILTER_VALUES=false` to restore the old behaviour.

### Documents are indexed by scout key

Solr's `id` field is now always set to `getScoutKey()`. If any of your models override `getScoutKey()`, re-import them:

```bash
php artisan scout:flush "App\Models\YourModel"
php artisan scout:import "App\Models\YourModel"
```

Models using their normal primary key need no action.

### Client: `setCore()` and `getEndpointFromConfig()` are removed

The engine no longer changes a shared client's core before each request; it resolves an endpoint per model and passes it along. If you used the client or engine directly:

```diff
- $select = $engine->setCore($model)->createSelect();
- $result = $engine->select($select, $engine->getEndpointFromConfig($model->searchableAs()));
+ $select = $engine->createSelect();
+ $result = $engine->select($select, $engine->endpointFor($model));
```

`endpointFor()` accepts a model or a core name. Models whose `searchableAs()` returned an array are no longer supported.

### Search callbacks

Because the client's default endpoint no longer points at the model's core, pass the endpoint from the callback's options:

```diff
  Book::search('dune', function ($client, $query, $options) {
      $select = $client->createSelect()->setQuery($query);
-     return $client->select($select);
+     return $client->select($select, $options['endpoint']);
  });
```

### Config

Re-publish or merge the config file if you published it (`php artisan vendor:publish --tag=scout-solr-config --force`, then reapply your changes). New keys:

- `http.adapter`, `http.timeout`, `http.connection_timeout` (`SOLR_HTTP_ADAPTER`, `SOLR_TIMEOUT`, `SOLR_CONNECTION_TIMEOUT`)
- `select.escape_filter_values` (`SOLR_ESCAPE_FILTER_VALUES`)

The `core` key of a per-model endpoint is now optional; it defaults to the endpoint's key.

### Events

`BeforeSelect` and `AfterSelect` now use promoted constructor properties; the public property names are unchanged. `AfterSelect` has a new `$builder` property.
