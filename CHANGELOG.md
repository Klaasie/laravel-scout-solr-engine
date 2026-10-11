# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
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

[Unreleased]: https://github.com/Klaasie/laravel-scout-solr-engine/compare/v1.0.0...HEAD
[V1.0.0]:https://github.com/Klaasie/laravel-scout-solr-engine/compare/0.6.0...v1.0.0
[0.6.0]: https://github.com/Klaasie/laravel-scout-solr-engine/compare/0.5.1...0.6.0
[0.5.1]: https://github.com/Klaasie/laravel-scout-solr-engine/compare/0.5.0...0.5.1
[0.5.0]: https://github.com/Klaasie/laravel-scout-solr-engine/compare/0.4.0...0.5.0
[0.4.0]: https://github.com/Klaasie/laravel-scout-solr-engine/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/Klaasie/laravel-scout-solr-engine/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/Klaasie/laravel-scout-solr-engine/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/Klaasie/laravel-scout-solr-engine/releases/tag/0.1.0