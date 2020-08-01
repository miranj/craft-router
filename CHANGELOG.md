# Router Changelog

Release notes for the Router Craft CMS plugin.



## Unreleased

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
