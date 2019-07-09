# Post Views Counter

Post Views Counter allows you to display how many times a post, page or custom post type had been viewed in a simple, fast and reliable way.

## Description

[Post Views Counter](http://www.dfactory.eu/plugins/post-views-counter/) allows you to display how many times a post, page or custom post type had been viewed in a simple, fast and reliable way.

For more information, check out plugin page at [dFactory](http://dfactory.eu/) or plugin [support forum](http://dfactory.eu/support/forum/post-views-counter/).

### Features include:

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

## Installation

1. Install Post Views Counter either via the WordPress.org plugin directory, or by uploading the files to your server
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the Post Views Counter settings and set your options.

## Changelog

#### 1.3
* New: Gutenberg compatibility
* New: Additional options in widgets: post author and display style
* Fix: Undefined variables when IP saving enabled
* Fix: Check cookie not being triggered in Fast Ajax mode
* Fix: Invalid arguments in implode function causing warning
* Fix: Thumbnail size option did not show up after thumbnail checkbox was checked
* Fix: Saving post (in quick edit mode too) did not update post views

#### 1.2.14
* Fix: Bulk edit post views count reset issue

#### 1.2.13
* New: Experimental Fast AJAX counter method (10+ times faster)

#### 1.2.12
* New: GDPR compatibility with Cookie Notice plugin

#### 1.2.11
* Tweak: Additional IP expiration checks added as an option

#### 1.2.10
* New: Additional transient based IP expiration checks
* Tweak: Chart.js script update to 2.7.1

#### 1.2.9
* Fix: WooCommerce products list table broken

#### 1.2.8
* New: Multisite compatibility
* Fix: Undefined index post_views_column on post_views_counter/includes/settings.php
* Tweak: Improved user IP handling

#### 1.2.7
* Fix: Chart data not updating for object cached installs due to missing expire parameter
* Fix: Bug preventing hiding the counter based on user role.
* Fix: Undefined notice in the admin dashboard request