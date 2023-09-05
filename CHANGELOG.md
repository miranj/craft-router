# Router Changelog

Release notes for the Router Craft CMS plugin.



## Unreleased

### Fixed
- Fixed a bug where an element's descendants were incorrectly passed to the `relatedTo` criteria.



## 1.4.0 - 2023-02-22

### Added
- Added new filters for handling single and multiple Tags as `relatedTo` criteria.



## 1.3.0 - 2023-02-13

### Added
- Added support for Craft 4.



## 1.2.1 - 2023-01-03

### Fixed
- Restored pre `v1.2.0` behaviour of the `relatedTo` criteria’s `element` key being a single element  (and not an array) for Category, Entry, and URI filters (for elements without descendants).
- Fixed a bug where `relatedTo` criteria might have empty array items for elements without descendants.



## 1.2.0 - 2022-08-09

### Added
- Added new Categories, Entries, and URIs filters for handling multiple items per filter.
- Added `combineSegments` configuration parameter which can be set to `false` to disable segment combinations in routing rules.



## 1.1.0 - 2021-05-02

### Added
- Added a new Month filter.

### Changed
- Router now requires Craft 3.7 or later.

### Fixed
- Fixed a bug where the `section` parameter on the `entry` filter type would be ignored.



## 1.0.0 - 2020-08-19

### Added
- Added a plugin icon.

### Changed
- `miranj\router\controllers\DefaultController::fetchSingle()` now queries across multiple sites, along with [`unique()`](https://docs.craftcms.com/v3/dev/element-queries/entry-queries.html#parameters).



## 1.0.0-beta.3 - 2020-02-18

### Added
- Added a new Entry Type filter.



## 1.0.0-beta.2 - 2019-12-27

### Added
- Added a `router` service.
- Added a `craft.router` Twig global variable.
- Added `craft.router.params()` to access all named params from the URL.
- Added `craft.router.rawParams()` to access all named params (with raw values) from the URL.
- Added `craft.router.url()` to build a URL out of named routes and optional params.
- Added `craft.router.urlMerge()` to build a URL out of the current route and additional params.
- Added `miranj\router\services\Router`.
- Added `miranj\router\services\Router::getParams()`.
- Added `miranj\router\services\Router::getRawParams()`.
- Added `miranj\router\services\Router::getUrl()`.
- Added `miranj\router\services\Router::getUrlMerge()`.

### Fixed
- Fixed bug where top level URL rule (with no segments) would get registered twice.



## 1.0.0-beta.1 - 2019-06-05

### Added
- Added Craft 3 compatibility.
- Added shorthand for Year filter's `field` config.
- Added new Date filter with support for year/month/day.



## 0.2 - 2015-07-30

### Added
- Add the ability to filter by Section.
- Add the ability to filter by URI.
- Add the ability to filter by Field.

### Removed
- Drop support for the mandatory `list` param.



## 0.1 - 2015-02-02
- Initial release.
