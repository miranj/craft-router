# Router

A [Craft CMS][craft] plugin to route requests to pages with a filtered, pre-loaded list of entries.

## Why

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


## Installation

1. Place the `router` folder inside your `craft/plugins/` folder.
2. Go to Settings > Plugins inside your Control Panel and install **Router**.



[craft]:http://buildwithcraft.com/