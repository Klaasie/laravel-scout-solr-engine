# AGENTS.md

Guidance for AI coding agents (and humans) working on this repository.

## What this is

`klaasie/scout-solr-engine` is an Apache Solr engine for Laravel Scout, built on Solarium.

Supported in 4.x: PHP 8.2+, Laravel 12 and 13, Scout 10 and 11, Solarium 6.3 and 7. CI tests against Solr 8.11 and 9 (standalone) and Solr 10 (SolrCloud).

## Layout

- `src/Engines/SolrEngine.php`: the Scout engine. Indexing, search, filters, mapping results back to models, commits.
- `src/Client.php`: extends Solarium's client. Resolves a Solr endpoint per model (`endpointFor()`) and creates/deletes cores or collections.
- `src/ScoutSolrServiceProvider.php`: registers the client (HTTP adapter, timeouts) and the `solr` Scout driver.
- `src/Events/`: `BeforeSelect` and `AfterSelect`, dispatched around every search.
- `config/scout-solr.php`: all settings, each overridable through an env variable.
- `tests/`: unit tests (`Unit` suite). `tests/Integration/`: tests against a real Solr (`Integration` suite).
- `docker-compose.yml` + `docker/solr/`: a local Solr with config sets in place so cores can be created.
- `UPGRADE.md`: upgrade steps between major versions. `CHANGELOG.md`: Keep a Changelog format.

## Commands

```bash
composer install
vendor/bin/phpunit --testsuite Unit                 # unit tests, no Solr needed
docker compose up -d                                 # local Solr 9 (SOLR_VERSION=8.11|10 for others)
SOLR_HOST=localhost vendor/bin/phpunit --testsuite Integration
SOLR_HOST=localhost SOLR_CLOUD=true vendor/bin/phpunit --testsuite Integration   # against SolrCloud
composer phpstan                                     # PHPStan level 6 with Larastan
composer phpcs                                       # PSR-2, 120 character lines
```

Coverage, as CI checks it (needs Solr running and pcov or Xdebug):

```bash
SOLR_HOST=localhost vendor/bin/phpunit --coverage-clover clover.xml
php .github/scripts/check-coverage.php clover.xml 100
```

Integration tests skip themselves unless `SOLR_HOST` is set.

## Rules for changes

- **Line coverage must stay at 100%**, measured over unit and integration tests together. CI fails otherwise. Every new code path needs a test.
- **Prefer integration tests for behaviour that depends on Solr.** Several past bugs survived because unit tests mocked exactly the broken path. Use mocks for wiring, a real Solr for query and indexing behaviour.
- To check the exact request sent to Solr without a running instance (for example SolrCloud admin calls), use `tests/Fixtures/RecordingAdapter.php`.
- Integration test models use the dynamic field suffixes of Solr's `_default` config set (`*_t` text, `*_s` string, `*_i` integer), so no schema setup is needed.
- PHPStan (level 6) and phpcs must pass. Keep lines at 120 characters or less, including in `.github/scripts/`.
- Add user-facing changes to `CHANGELOG.md` under `## [Unreleased]`. Breaking changes also go in `UPGRADE.md`.
- Keep the README in sync with behaviour and config options.
- Don't commit `vendor/` or `composer.lock` (this is a library).

## Design decisions

These are deliberate; don't change them without discussing it first.

- **Search text is sent raw** as Solr's `q`, so Lucene syntax works. An empty search becomes `*:*`.
- **Filters go in a filter query (`fq`)**, never into `q`. String filter values are quoted (escaped); numbers are sent as-is, booleans as `true`/`false`, dates in Solr's date format. `SOLR_ESCAPE_FILTER_VALUES=false` restores raw values.
- **Solr's `id` field is always the model's scout key** (`getScoutKey()`), overriding anything in `toSearchableArray()`. `delete()`, `map()` and `keys()` rely on it.
- **Endpoints are resolved per model** via `Client::endpointFor()` and passed explicitly to every request. Never change the shared client's default endpoint. An endpoint config keyed by a model's `searchableAs()` puts that model on another Solr instance.
- **The default commit mode is a hard commit** after every update. `soft`, `within` and `none` are opt-in (`SOLR_COMMIT`).
- **The namespace stays `Scout\Solr`**, even though it lacks a vendor prefix. Renaming would break every user.

## Pitfalls

- **Scout 10 and 11 store `where` conditions differently.** Scout 10: `[field => value]`. Scout 11: a list of `['field', 'operator', 'value']` entries, with comparison operators. `SolrEngine::wheres()` normalises both; keep it that way while both are supported.
- **Scout leaves soft-delete metadata to the engine.** `update()` must call `pushSoftDeleteMetadata()` when `scout.soft_delete` is on, or every default search fails with "undefined field __soft_deleted".
- **Laravel 13 binds custom driver callbacks to the manager**, so `$this` inside an `EngineManager::extend()` callback isn't the service provider. Capture what you need in a local variable.
- **The official Solr Docker image keeps its config sets outside the Solr home**, so creating a core from `_default` fails until they're copied in. `docker/solr/copy-configsets.sh` does that.
- **Solr 10 starts in SolrCloud mode.** Set `SOLR_CLOUD=true` so cores are created as collections through the Collections API.
- `mergeConfigFrom()` only merges top-level config keys. When tests override a top-level key such as `scout-solr.endpoints`, set it in full.
