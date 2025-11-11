<img align="right" src="./src/icon.svg" width="100" height="100" alt="Router icon">

Router
======

A [Craft CMS][craft] plugin for using URL segments as filtering criteria on an entry query.

[craft]:https://craftcms.com/



Contents
--------
- [Why](#why)
  - [Demo](#demo)
- [Usage](#usage)
  - [Example](#example)
  - [Parameters](#parameters)
  - [Filters](#filter-types)
- [Installation](#installation)
- [Requirements](#requirements)
- [Changelog](./CHANGELOG.md)
- [License](./LICENSE)



Why
---

Craft makes it straightforward to declare [dynamic routes as regular expressions][ar]
and redirect them to be handled by a template. However, the templates themselves
remain dumb handlers. They may optionally be passed on some context in the form of [named
parameters][yii routing] but they have to do the heavy lifting of building the data set
required for rendering the page.

This may not be a problem for pages with one or two variables,
like a blog's _year_ and _month_ archives (e.g. `blog/2015/01`).
The template would fetch the list of posts from `craft.entries` and narrow the range
depending on if the `year` and `month` variables are set.

But what if the blog also added a category page (e.g. `blog/camping`)?
And what if the category pages supported their own yearly and monthly archive pages
(e.g. `blog/camping/2014`)? We would either end up duplicating the code to fetch posts
by creating multiple copies of the archive template, or end up adding the logic to handle category,
year, and month _filters_ all in a single template and increasing its overall complexity.

The Router plugin attempts to solve this problem by taking on the job of filtering entries
based on URL parameters. It adds a new template variable `entries` which can be configured to,
for the URL `blog/2015/01` contain blog posts published in January 2015, or for the URL
`blog/camping/2014` to show blog posts published in 2014 under the category "Camping".

[ar]:https://craftcms.com/docs/5.x/system/routing.html#advanced-routing-with-url-rules "Advanced Routing with URL Rules - Craft CMS Documentation"
[yii routing]:https://www.yiiframework.com/doc/guide/2.0/en/runtime-routing#named-parameters "Handling Requests: Routing and URL Creation | Yii 2.0"


### Demo

[<img src="./demo-poster.jpg" width="240" height="125" alt="Custom Routing with Router, Craft The Planet">][demo]

We recorded [a video (36 mins) about the plugin][demo] for Straight Up Craft.
It talks about the problems that Router is trying to solve and includes a step-by-step
tutorial + demo about using the plugin on the [Craft Blog Starter project][starter blog].

[demo]:https://www.youtube.com/watch?v=ofv67KahW_M
[starter blog]:https://github.com/craftcms/starter-blog



Usage
-----

In order to create URL rules that automatically build an [Entry Query][eq] based on the URL,
you will need to create a `router.php` file in your config folder, adjacent to your existing
`routes.php` file. The config file supports a single option `rules`, which should be set to
an array containing the URL rules.

For [multi-site Craft setups][sites], you can define site-specific URL rules by placing them in a
sub-array, with the key set to the site’s handle.

[eq]:https://craftcms.com/docs/5.x/reference/element-types/entries.html#querying-entries
[sites]:https://craftcms.com/docs/5.x/system/sites.html


### Example

```php
<?php
/* config/router.php */

return [
    'rules' => [
        
        // URI pattern with named subpatterns
        '<section:blog>' => [
            'segments' => [
                '<year:\d{4}>',
                '<category:{slug}>',
                'in:<location:{slug}>',
            ],
            
            // array of filters that are activated when
            // the key matches a subpattern variable declared in
            // the route's regular expression
            'criteria' => [
                
                // Restrict entries to the selected section
                'section' => [
                    'type' => 'section',
                ],
                
                // Filter entries by year
                'year' => [
                    'type' => 'date',
                    'field' => 'postDate',
                ],
                
                // Filter entries by related category
                // from the category group with the handle 'travel-styles'
                'category' => [
                    'type' => 'category',
                    'group' => 'travel-styles',
                ],
                
                // Filter entries by related entry
                // from the section with the handle 'locations'
                'location' => [
                    'type' => 'entry',
                    'section' => 'locations',
                ],
            ],
            
            // template file
            'template' => 'blog/_archive',
        ],
        
        // Site-specific rules can be placed in a sub-array
        'siteHandle' => [
            '<section:blog>' => …,
        ],
        
    ],
];
```


### Parameters

Each rule expects the following parameters:

#### `segments` [array]

_Default: `[]`_  
An array of optional URL segment rules. Example:

```php
'segments' => [
    '<year:\d{4}>',
    '<category:{slug}>',  // eg. budget, luxury, cruise, urban
    'in:<location:{slug}>',  // eg. asia, europe, australia
]
/*
  This will match the following URL suffixes
  …/2019
  …/2019/budget
  …/2019/budget/in:asia
  …/2019/in:asia
  …/budget
  …/budget/in:asia
  …/in:asia

  Order is relevant, so it will *not* match the following URLs
  …/budget/2019
  …/in:asia/budget
  …/2019/in:asia/budget
*/
```

#### `combineSegments` [bool]

_Default: `true`_  
Enables/disables support for URLs with segment combinations. When disabled,
only one segment match will be allowed per URL. Example:

```php
'combineSegments' => false
/*
  This will match only the following URL suffixes
  …/2019
  …/budget
  …/in:asia

  It will *not* match the following URLs
  …/2019/budget
  …/2019/budget/in:asia
  …/2019/in:asia
  …/budget/in:asia
*/
```

#### `criteria` [array]
_Default: `[]`_  
An array of filters for the Entry Query. Example:
```php
'criteria' => [
    'year' => [ 'type' => 'date', 'field' => 'postDate' ],
    'category' => [ 'type' => 'category', 'group' => 'tripCategories' ],
    'location' => [ 'type' => 'entry', 'section' => 'locations' ],
]
```
  
#### `template` [string]
_Default: `''`_  
The template path used to render the request. Example:
```php
'template' => 'blog/_archive'
```

A filter is activated when the corresponding trigger key (named parameter)
is present in the route. Based on the type of filter, a set of conditions (criteria)
are added to an [Entry Query][eq] object. This is repeated for every activated filter,
and the resulting Entry Query is passed on to the template as the `entries` variable.


### Filter Types

The plugin currently supports the following different types of filters:

#### `category`
Adds a `relatedTo` criteria to the [Category][cat] with the given slug, and any of its
descendants. The Category’s search can be scoped by specifying a Category Group handle
in the optional param `group`. The relation’s field can be specified using the optional
param `field`. Set the filter’s `includeDescendants` to false if you do not wish descendant
Categories to be included in the `relatedTo` criteria.

#### `categories`
Similar to the `category` filter but supports a comma separated list of slugs instead of
just one slug.

#### `date`
Adds a date range criteria for the given `YYYY`, `YYYY/MM`, or `YYYY/MM/DD`.
The criteria is applied to the `postDate` field by default. This can be
customised to any valid custom [Date field][date field] by specifying its handle
in the optional `field` param.

[date field]:https://craftcms.com/docs/5.x/reference/field-types/date-time.html

#### `entry`
Adds a `relatedTo` criteria to the [Entry][] with the given slug, and any of its descendants.
The Entry’s search can be scoped by specifying a [Section][sec] handle in the optional param
`section`. The relation’s field can be specified using the optional param `field`. Set the
filter’s `includeDescendants` to false if you do not wish descendant Entries to be included
in the `relatedTo` criteria.

#### `entries`
Similar to the `entry` filter but supports a comma separated list of slugs instead of just
one slug.

#### `field`
Adds a field criteria to the field specified by `handle` (required param). Since Craft 5 has
added [support for aliasing field handles][multi-instance] when added to an entry type
or field layout, the global handle may not be relevant to the query. In these cases, an
optional `alias` param can be specificed to override the handle by which the field value
should be queried.

[multi-instance]:https://craftcms.com/docs/5.x/system/fields.html#multi-instance-fields

#### `month`
Adds a numeric month criteria on the optional param `field` (which defaults to `postDate`).

> [!WARNING]
> For this filter to [work on custom (user-defined) fields in Craft 5][c5 field query], either the [`section`](#section) or [`type`](#type)/[`types`](#types) filter must be applied before the `month` filter.

[c5 field query]:https://craftcms.com/docs/5.x/development/element-queries.html#content-and-custom-fields

#### `search`
Adds a [`search`][search] criteria. Criteria value can be overidden using the optional
param `value`.

#### `section`
Adds a `section` criteria if the specified [Section][sec] handle is valid. Section handle
value can be overidden using the optional param `value`.

#### `since`
Adds a `>=` (on or later) condition for the given _date_ in any of these
supported formats: `YYYY`, `YYYY-MM`, or `YYYY-MM-DD`. The criteria is
applied to the `postDate` field by default. This can be customised to
any valid custom [Date field][date field] by specifying its handle in
the optional `field` param.

#### `tag`
Adds a `relatedTo` criteria to the [Tag][tag] with the given slug. The Tag’s search can be
scoped by specifying a Tag Group handle in the optional param `group`. The relation’s field
can be specified using the optional param `field`.

#### `tags`
Similar to the `tag` filter but supports a comma separated list of slugs instead of
just one slug.

#### `type`
Adds a `type` criteria if the specified [EntryType][type] handle is valid. EntryType handle
value can be overidden using the optional param `value`.

> [!NOTE]
> The named URL parameter is set to an [EntryType object][getEntryTypeByHandle] in Craft 5 (as expected), but an [EntryType[]][getEntryTypesByHandle] array in Craft 3 and 4. This is because entry type handles are not unique (across sections) in older versions of Craft. You can override this behaviour by setting the filter's `assumeUniqueHandles` optional param to `true`. This parameter has no effect in Craft 5.

[getEntryTypeByHandle]:https://docs.craftcms.com/api/v5/craft-services-entries.html#method-getentrytypebyhandle "Services > Entries > getEntryTypeByHandle()"
[getEntryTypesByHandle]:https://docs.craftcms.com/api/v4/craft-services-sections.html#method-getentrytypesbyhandle "Services > Sections > getEntryTypesByHandle()"

#### `types`
Similar to the `type` filter but supports a comma separated list of handles instead of
just one handle.

#### `until`
Adds a `<=` (on or earlier) condition for the given _date_ in any of these
supported formats: `YYYY`, `YYYY-MM`, or `YYYY-MM-DD`. The criteria is
applied to the `postDate` field by default. This can be customised to
any valid custom [Date field][date field] by specifying its handle in
the optional `field` param.

#### `uri`
Adds a relatedTo criteria to the entry with the given URI, and any of its descendants. The
Entry’s search can be scoped by specifying a [Section][sec] handle in the optional param
`section`. The relation's field can be specified using the optional param `field`. Set the
filter's `includeDescendants` to false if you do not wish descendant Entries to be included
in the `relatedTo` criteria.

#### `uris`
Similar to the `uri` filter but supports a comma separated list of URIs instead of just
one URI.

#### `year`
Deprecated. See [`date`](#date).

[cat]:https://craftcms.com/docs/5.x/reference/element-types/categories.html
[entry]:https://craftcms.com/docs/5.x/reference/element-types/entries.html
[sec]:https://craftcms.com/docs/5.x/reference/element-types/entries.html#sections
[tag]:https://craftcms.com/docs/5.x/reference/element-types/tags.html
[type]:https://craftcms.com/docs/5.x/reference/element-types/entries.html#entry-types
[search]:https://craftcms.com/docs/5.x/system/searching.html



Installation
------------

You can install this plugin from the [Plugin Store][ps] or with Composer.

[ps]:https://plugins.craftcms.com/router

#### From the Plugin Store
Go to the Plugin Store in your project’s Control Panel and search for “Router”.
Then click on the “Install” button in its modal window.

#### Using Composer
Open your terminal and run the following commands:

    # go to the project directory
    cd /path/to/project
    
    # tell composer to use the plugin
    composer require miranj/craft-router
    
    # tell Craft to install the plugin
    ./craft plugin/install router



Requirements
------------
This plugin requires Craft CMS 3, 4, or 5. The Craft 2 version is availabe in [the `v0` branch](https://github.com/miranj/craft-router/tree/v0).



---

Brought to you by [Miranj](https://miranj.in/)
