# Post Views Counter #

Post Views Counter allows you to display how many times a post, page or custom post type had been viewed in a simple, fast and reliable way.

## Description ##

[Post Views Counter](http://www.dfactory.eu/plugins/post-views-counter/) allows you to display how many times a post, page or custom post type had been viewed in a simple, fast and reliable way.

For more information, check out plugin page at [dFactory](http://dfactory.eu/) or plugin [support forum](http://dfactory.eu/support/forum/post-views-counter/).

### Features include: ###

* Option to select post types for which post views will be counted and displayed.
* 3 methods of collecting post views data: PHP, Javascript or REST API for greater flexibility
* Possibility to manually set views count for each post
* Dashboard post views stats widget
* Capability to query posts according to its views count
* Custom REST API endpoints
* Option to set counts interval
* Excluding counts from visitors: bots, logged in users, selected user roles
* Excluding users by IPs
* Restricting display by user roles
* Restricting post views editing to admins
* One-click data import from WP-PostViews
* Sortable admin column
* Post views display position, automatic or manual via shortcode
* W3 Cache/WP SuperCache compatible
* Optional object cache support
* WPML and Polylang compatible
* .pot file for translations included

## Installation ##

1. Install Post Views Counter either via the WordPress.org plugin directory, or by uploading the files to your server
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the Post Views Counter settings and set your options.

## Changelog ##

#### 1.2.7 ####
* Fix: Chart data not updating for object cached installs due to missing expire parameter
* Fix: Bug preventing hiding the counter based on user role.
* Fix: Undefined notice in the admin dashboard request