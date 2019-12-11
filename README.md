Router
======

A [Craft CMS][craft] 3 plugin to route requests to pages with a filtered, pre-loaded list of entries.

[craft]:https://craftcms.com/



Contents
--------
- [Why](#why)
- [Usage](#usage)
- [Example](#example)
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
like a blog's _yearly_ and _monthly_ archives (e.g. "blog/2015/01").
The template would fetch the list of posts from `craft.entries` and narrow the range
depending on if the year and month variables are set.

But what if the blog also added a category page (e.g. "blog/street-food")?
And what if the category pages suppored their own yearly and monthly archive pages
(e.g. "blog/dim-sums/2014")? We would either end up duplicating the code to fetch posts
by creating multiple copies of the archive template, or end up adding the logic to handle category,
year, and month _filters_ all in a single template and increasing its overall complexity.

The Router plugin attempts to solve this problem by taking on the job of filtering entries
based on URL parameters. It adds a new template variable `entries` which can be configured to,
for the URL "blog/2015/01" contain blog posts published in January 2015, or for the URL "blog/dim-sums/2014" to show blog posts published in 2014 under the category "Dim sums".

[ar]:https://docs.craftcms.com/v3/routing.html#advanced-routing-with-url-rules "Advanced Routing with URL Rules - Craft 3 Documentation"
[yii routing]:https://www.yiiframework.com/doc/guide/2.0/en/runtime-routing#named-parameters "Handling Requests: Routing and URL Creation | Yii 2.0"



Usage
-----

In order to create URL rules that automatically build an [Entry Query][eq] based on the URL,
you will need to create a `router.php` file in your config folder, adjacent to your existing
`routes.php` file.

[eq]:https://docs.craftcms.com/v3/dev/element-queries/entry-queries.html

Each rule expects the following parameters:

- `segments` — An array of optional URL segment rules. Example:
  ```php
    [
      '<year:\d{4}>',
      '<category:{slug}>',  // eg. budget, luxury, cruise, urban
      '<location:{slug}>',  // eg. asia, europe, australia
    ]
    /*
      This will match the following URL suffixes
      …/2019
      …/2019/budget
      …/2019/budget/asia
      …/2019/asia
      …/budget
      …/budget/asia
      …/asia
      
      Order is relevant, so it will *not* match the following URLs
      …/budget/2019
      …/asia/budget
      …/2019/asia/budget
    */
  ```

- `criteria` — An array of filters for the Entry Query. Example:
  ```php
    [
      'year' => [ 'type' => 'year', 'field' => 'postDate' ],
      'category' => [ 'type' => 'category', 'group' => 'tripCategories' ],
      'location' => [ 'type' => 'entry', 'section' => 'locations' ],
    ]
  ```
  
- `template` — The template path used to render the request.

A filter is activated when the corresponding trigger key (named parameter) is present in the route. Based on the type of filter, a set of conditions (criteria) are added to an [Entry Query][eq] object. This is repeated for every activated filter, and the resulting Entry Query is passed on to the template as the `entries` variable.

### Filter Types

The plugin currently supports the following different types of filters:

Type       | Description
:---       | :---
`category` | Adds a `relatedTo` criteria to the [Category][cat] with the given slug, and any of its descendants. The Category's search can be scoped by specifying a Categroy Group handle in the optional param `group`. The relation's field can be specified using the optional param `field`. Set the filter's `includeDescendants` to false if you do not wish descendant Categories to be included in the `relatedTo` criteria.
`entry`    | Adds a `relatedTo` criteria to the [Entry][] with the given slug, and any of its descendants. The Entry's search can be scoped by specifying a [Section][sec] handle in the optional param `section`. The relation's field can be specified using the optional param `field`. Set the filter's `includeDescendants` to false if you do not wish descendant Entries to be included in the `relatedTo` criteria.
`field`    | Adds a field criteria to the field specified by `handle` (required param).
`search`   | Adds a [`search`][search] criteria. Criteria value can be overidden using the optional param `value`.
`section`  | Adds a `section` criteria if the specified [Section][sec] handle is valid. Section handle value can be overidden using the optional param `value`.
`uri`      | Adds a relatedTo criteria to the entry with the given URI, and any of its descendants. The Entry's search can be scoped by specifying a [Section][sec] handle in the optional param `section`. The relation's field can be specified using the optional param `field`. Set the filter's `includeDescendants` to false if you do not wish descendant Entries to be included in the `relatedTo` criteria.
`year`     | Adds a date range criteria for the given year on optional param `field` (which defaults to `postDate`).

[cat]:https://docs.craftcms.com/v3/categories.html
[entry]:https://docs.craftcms.com/v3/sections-and-entries.html#entries
[sec]:https://docs.craftcms.com/v3/sections-and-entries.html#sections
[search]:https://docs.craftcms.com/v3/searching.html



Example
-------

```php
/* config/router.php */

return [
  'rules' => [
  
    // URI pattern with named subpatterns
    '<sectionHandle:blog>' => [
      'segments' => [
        '<foodCategorySlug:{slug}>',
        '<yearPublished:\d{4}>',
      ],
      
      // array of filters that are activated when
      // the key matches a subpattern variable declared in
      // the route's regular expression
      'criteria' => [
        
        // Restrict entries to the selected section
        'sectionHandle' => [
          'type' => 'section',
        ],
        
        // Filter entries by year
        'yearPublished' => [
          'type' => 'year',
          'field'=> 'postDate',
        ],
        
        // Filter entries by categories
        // from the group with the handle 'food'
        'foodCategorySlug' => [
          'type' => 'category',
          'group'=> 'food',
        ],
      ],
      
      // template file
      'template' => 'blog/_archive',
    ],
    
  ],
];
```



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
    composer require miranj/craft-router:"1.0.0-beta.1"
    
    # tell Craft to install the plugin
    ./craft install/plugin router



Requirements
------------
This plugin requires Craft CMS 3.0.0 or later. The Craft 2 version is availabe in [the `v0` branch](https://github.com/miranj/craft-router/tree/v0).



---

Brought to you by [Miranj](https://miranj.in/)
