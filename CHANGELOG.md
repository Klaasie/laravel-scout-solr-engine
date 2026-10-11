# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Configurable commit strategy for updates, deletes and flushes (`SOLR_COMMIT`: `hard`, `soft`, `within` or `none`; `SOLR_COMMIT_WITHIN` in milliseconds). The default remains a hard commit.

### Fixed
- Scout soft deletes (`scout.soft_delete`) now work. Scout leaves adding its `__soft_deleted` flag to the engine, and this engine never did, so every default search failed with "undefined field __soft_deleted". Re-import soft-deletable models after upgrading so existing documents get the flag.

## [4.0.0] - 2026-10-11
See [UPGRADE.md](UPGRADE.md) for upgrade steps from 3.x.

### Changed (breaking)
- Requires PHP 8.2+, Laravel 12 or 13, Laravel Scout 10 or 11 and Solarium 6.3 or 7. Dropped PHP 8.0/8.1, Laravel 9–11 and Scout 9.
- The engine resolves an endpoint per model instead of changing the shared client's core before every request. `Client::setCore()` and `Client::getEndpointFromConfig()` are replaced by `Client::endpointFor()`; models whose `searchableAs()` returns an array are no longer supported.
- Search callbacks receive the model's endpoint as `$options['endpoint']` and must pass it to `select()`.
- String values passed to `where`, `whereIn` and `whereNotIn` are now quoted, so they match literally (e.g. `science fiction`, `c++`). If you relied on passing raw Solr syntax such as `[1950 TO 1960]` as a value, set `SOLR_ESCAPE_FILTER_VALUES=false` or build the filter in a `BeforeSelect` listener.
- Boolean filter values are now sent as `true`/`false` instead of `1`/empty.
- Solr's `id` field is now always set to the model's scout key. Models with a custom `getScoutKey()` were previously indexed under a different id than the one used to find and delete them; re-import those models (`scout:flush` then `scout:import`).

### Added
- Laravel 13, Scout 11, Solarium 7 and Solr 10 support.
- Scout 11 `where` operators (`=`, `!=`, `<>`, `>`, `>=`, `<`, `<=`), sent as Solr ranges or negations.
- `where('field', null)` matches documents without the field (`!=` matches documents with it).
- `DateTimeInterface` filter values are sent in Solr's date format.
- Configurable HTTP adapter (`curl` or `http`) and timeouts (`SOLR_HTTP_ADAPTER`, `SOLR_TIMEOUT`, `SOLR_CONNECTION_TIMEOUT`); an adapter bound in the container, such as Solarium's `Psr18Adapter`, is used instead.
- `AfterSelect` event carries the Scout `Builder`.
- `mapIdsFrom()` works with Solr results.
- The client is resolvable as `Scout\Solr\Client` as well as `Scout\Solr\ClientInterface`.
- The config can be published with `--tag=scout-solr-config`.
- `docker-compose.yml` that starts a local Solr ready for `scout:index`, used by the integration tests in CI.
- README usage section: model setup, a second Solr instance, and an events example.
- `whereNotIn` support (Scout 10+), sent as a negated filter query.
- `scout-solr.select.escape_filter_values` config option (`SOLR_ESCAPE_FILTER_VALUES`).

### Fixed
- `symfony/event-dispatcher` is now a declared dependency; it was used but only installed indirectly through Laravel.
- An unsupported SolrCloud `router_name` now throws `InvalidRouterNameSupplied` instead of being silently ignored.
- `update()` and `delete()` no longer fail when given an empty collection.
- A `whereIn` with an empty array no longer produces invalid Solr syntax; it matches nothing, like Eloquent.
- README: the second events-table row now correctly names `AfterSelect`.

### Removed
- Unused `phpmd/phpmd` dev dependency.
- The bundled Laravel 8 example app (it was not part of the Composer package). Its examples now live in the README.

## [3.0.1] - 2026-10-11
### Fixed
- Search text is no longer dropped when `where`/`whereIn` filters are used; filters are now sent as a Solr filter query (`fq`).
- An empty search now matches all documents (`*:*`).
- `lazyMap()` (used by `cursor()`) no longer fails with a call to `cursor()` on a collection.
- Setting `SOLR_SELECT_DEFAULT_LIMIT` in `.env` no longer causes a TypeError (the value arrives as a string).
- PHP 8.4 implicit-nullable deprecation in `Client`.

### Changed
- CI: updated GitHub Actions and added PHP 8.4 and Laravel 12 to the test matrix.

## [v1.0.0] - 2021-10-02
### Added
- Version 1.0.0 release of package. 🎉
- Dependency checking through dependabot

## [0.6.0] - 2021-09-16
_No changelog available_

## [0.5.1] - 2021-09-15
_No changelog available_

## [0.5.0] - 2021-09-15
_No changelog available_

## [0.4.0] - 2021-09-15
_No changelog available_

## [0.3.0] - 2021-09-13
_No changelog available_

## [0.2.0] - 2021-09-12
_No changelog available_

## [0.1.0] - 2021-09-11
_No changelog available_

[Unreleased]: https://github.com/Klaasie/laravel-scout-solr-engine/compare/4.0.0...HEAD
[4.0.0]: https://github.com/Klaasie/laravel-scout-solr-engine/compare/3.0.1...4.0.0
[3.0.1]: https://github.com/Klaasie/laravel-scout-solr-engine/compare/V3.0.0...3.0.1
[V1.0.0]:https://github.com/Klaasie/laravel-scout-solr-engine/compare/0.6.0...v1.0.0
[0.6.0]: https://github.com/Klaasie/laravel-scout-solr-engine/compare/0.5.1...0.6.0
[0.5.1]: https://github.com/Klaasie/laravel-scout-solr-engine/compare/0.5.0...0.5.1
[0.5.0]: https://github.com/Klaasie/laravel-scout-solr-engine/compare/0.4.0...0.5.0
[0.4.0]: https://github.com/Klaasie/laravel-scout-solr-engine/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/Klaasie/laravel-scout-solr-engine/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/Klaasie/laravel-scout-solr-engine/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/Klaasie/laravel-scout-solr-engine/releases/tag/0.1.0