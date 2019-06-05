Router
======

A [Craft CMS][craft] plugin to route requests to pages with a filtered, pre-loaded list of entries.



Why
---

Craft makes it straightforward to declare [dynamic routes as regular expressions][ar]
and redirect them to be handled by a template file. However, the templates themselves
remain dumb handlers. They may optionally be passed on some context in the form of (named)
subpattern matches variables but they have to do the heavy lifting of building the data set
required for rendering the page.

This is not a problem for pages with one or two variables, like a blog's year and month archive
(e.g. "blog/2015/01").
The template would fetch the list of posts from `craft.entries` and narrow the range
depending on if the year and month variables are set.

But what if the blog also added a category page (e.g. "blog/street-food")?
And what if the category pages suppored their own yearly and monthly archive pages
(e.g. "blog/dim-sums/2014")? We would either end up duplicating the code to fetch posts
by creating a copy of the archive template, or end up adding the logic to handle category,
year and month _filters_ while fetching posts in the same template and increasing its overall complexity.

This plugin exists to handle the filtering of entries based on URL parameters.
It adds a new template variable `entries` which can be configured to,
for the URL "blog/2015/01" contain blog posts published in January 2015, or for the URL "blog/dim-sums/2014" show blog posts published in 2014 under the category "Dim sums".

[ar]:http://buildwithcraft.com/docs/routing#advanced-routing "Advanced Routing - Craft Docs"



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
    ./craft install/plugin router



Usage
-----

The plugin works by [routing requests through new controller actions][rca].
It currently offers one controller `router/lists` with one action `applyFilters`.

[rca]:http://buildwithcraft.com/docs/routing#routing-to-controller-actions "Routing to Controller Actions - Craft Docs"

The `applyFilters` action takes three parameters:

- `$template` — The template path that is used to render the request.
    
- `$variables` — An array of variables specific to the current URL / route being handled. _This parameter is automatically set by Craft_, based on the named subpatterns in the route's regular expression.

- `$filters` — An array of filter specifications. This is the plugin's workhorse parameter. Filters follow this syntax:
  ```php  
    'trigger_key' => array(
        'type' => '',
        … // extra filter parameters
    ),
  ```

A filter is activated when the corresponding trigger key (named subpattern) is present in the route. Based on the type of filter, a set of conditions (criteria) are added to an [ElementCriteriaModel][ecm] object. This is repeated for every activated filter, and the resulting ElementCriteriaModel object is passed on the template as the `entries` variable.

[ecm]:http://buildwithcraft.com/docs/templating/elementcriteriamodel

### Filter Types

The plugin currently supports the following different types of filters:

- `category` — Adds a `relatedTo` criteria to the [Category][cat] with the given slug, and any of its descendants. The Category's search can be scoped by specifying a Categroy Group handle in the optional param `group`. The relation's field can be specified using the optional param `field`. Set the filter's `includeDescendants` to false if you do not wish descendant Categories to be included in the `relatedTo` criteria.
- `entry` — Adds a `relatedTo` criteria to the [Entry][] with the given slug, and any of its descendants. The Entry's search can be scoped by specifying a [Section][sec] handle in the optional param `section`. The relation's field can be specified using the optional param `field`. Set the filter's `includeDescendants` to false if you do not wish descendant Entries to be included in the `relatedTo` criteria.
- `field` — Adds a field criteria to the field specified by `handle` (required param).
- `search` — Adds a [`search`][search] criteria. Criteria value can be overidden using the optional param `value`.
- `section` — Adds a `section` criteria if the specified [Section][sec] handle is valid. Section handle value can be overidden using the optional param `value`.
- `uri` — Adds a relatedTo criteria to the entry with the given URI, and any of its descendants. The Entry's search can be scoped by specifying a [Section][sec] handle in the optional param `section`. The relation's field can be specified using the optional param `field`. Set the filter's `includeDescendants` to false if you do not wish descendant Entries to be included in the `relatedTo` criteria.
- `year` — Adds a date range criteria for the given year on optional param `field` (which defaults to `postDate`).

[cat]:http://buildwithcraft.com/docs/categories
[entry]:http://buildwithcraft.com/docs/templating/entrymodel
[sec]:http://buildwithcraft.com/docs/sections-and-entries#sections
[search]:http://buildwithcraft.com/docs/searching



Example
-------

```php
/* config/router.php */

return [
  'rules' => [
  
    // URI pattern with named subpatterns
    '<sectionHandle:blog>' => [
      'segments' => [
        '<foodCategorySlug:[^/]+>',
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



[craft]:https://craftcms.com/
