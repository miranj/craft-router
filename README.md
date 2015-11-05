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

1. Place the `router` folder inside your `craft/plugins/` folder.
2. Go to Settings > Plugins inside your Control Panel and install **Router**.



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



Example
-------

```php
/* craft/config/routes.php */

return array(
  
  
  // URI pattern with named subpatterns
  '(?P<sectionHandle>blog)'
    .'(/(?P<foodCategorySlug>[^/]+))?'
    .'(/(?P<yearPublished>\d{4}))?'
    
  => array(
    'action' => 'router/lists/applyFilters',
    'params' => array(
      
      // template file
      'template' => 'blog/_archive',
      
      // array of filters that are activated when
      // the key matches a subpattern variable declared in
      // the route's regular expression
      'filters' => array(
        
        // Restrict entries to the selected section
        'sectionHandle' => array(
          'type' => 'section',
        ),
        
        // Filter entries by year
        'yearPublished' => array(
          'type' => 'year',
          'field'=> 'postDate',
        ),
        
        // Filter entries by categories
        // from the group with the handle 'food'
        'foodCategorySlug' => array(
          'type' => 'category',
          'group'=> 'food',
        ),
      ),
    ),
  ),

);
```



[craft]:http://buildwithcraft.com/
