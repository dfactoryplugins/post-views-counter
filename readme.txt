=== Post Views Counter ===
Contributors: dfactory
Donate link: http://dfactory.eu/
Tags: counter, hits, posts, postviews, post views, views, count, statistics, stats, analytics, pageviews, tracking
Requires at least: 5.0
Requires PHP: 5.3.0
Tested up to: 5.9
Stable tag: 1.3.11
License: MIT License
License URI: http://opensource.org/licenses/MIT

Post Views Counter allows you to display how many times a post, page or custom post type had been viewed in a simple, fast and reliable way.

== Description ==

[Post Views Counter](http://www.dfactory.eu/plugins/post-views-counter/) allows you to display how many times a post, page or custom post type had been viewed with this simple, fast and easy to use plugin.

For more information, check out plugin page at [dFactory](http://dfactory.eu/) or plugin [support forum](http://www.dfactory.eu/support/forum/post-views-counter/).

= Features include: =

* Option to select post types for which post views will be counted and displayed.
* 3 methods of collecting post views data: PHP, Javascript and REST API for greater flexibility
* Compatible with data privacy regulations
* Possibility to manually set views count for each post
* Dashboard post views stats widget
* Full Data Privacy compliance
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
* Multisite compatibile
* W3 Cache/WP SuperCache compatible
* Optional object cache support
* WPML and Polylang compatible
* .pot file for translations included

= Get involved =

Feel free to contribute to the source code on the [dFactory GitHub Repository](https://github.com/dfactoryplugins).


== Installation ==

1. Install Post Views Counter either via the WordPress.org plugin directory, or by uploading the files to your server
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the Post Views Counter settings and set your options.

== Frequently Asked Questions ==

No questions yet.

== Screenshots ==

1. screenshot-1.png
2. screenshot-2.png

== Changelog ==

= 1.3.11 =
* Fix: Potentailly incorrect counting of post views in edge case db queries
* Fix: Possible empty chart in dashboard
* Fix: Incorrect saving of dashboard widget user options
* Tweak: Updated Chart.js to version 3.7.0

= 1.3.10 =
* Fix: Post views column not working properly
* Tweak: Switched to openssl_encrypt method for IP encryption
* Tweak: Improved user input escaping

= 1.3.9 =
* Tweak: Remove unnecessary plugin files

= 1.3.8 =
* Tweak: Improved user input escaping

= 1.3.7 =
* Tweak: Implemented internal settings API

= 1.3.6 =
* Fix: Option to hide admin bar chart

= 1.3.5 =
* New: Option to hide admin bar chart
* Fix: Small security bug with views label
* Tweak: Remove unnecessary CSS on every page

= 1.3.4 =
* New: Post Views stats preview in the admin bar
* New: Top Posts data available in the dashboard widget
* Tweak: Improved privacy using IP encrypting
* Tweak: PHP 8.x compatibility

= 1.3.3 =
* Fix: PHP Notice: Trying to get property 'colors' of non-object
* Fix: PHP Notice: register_rest_route was called incorrectly

= 1.3.2 =
* New: Introducing dashboard widget navigation
* New: Counter support for Media (attachments)
* Tweak: Extended views query for handling complex date/time requests

= 1.3.1 =
* Fix: Gutenberg CSS file missing
* Tweak: POT translation file update

= 1.3 =
* New: Gutenberg compatibility
* New: Additional options in widgets: post author and display style
* Fix: Undefined variables when IP saving enabled
* Fix: Check cookie not being triggered in Fast Ajax mode
* Fix: Invalid arguments in implode function causing warning
* Fix: Thumbnail size option did not show up after thumbnail checkbox was checked
* Fix: Saving post (in quick edit mode too) did not update post views

= 1.2.14 =
* Fix: Bulk edit post views count reset issue

= 1.2.13 =
* New: Experimental Fast AJAX counter method (10+ times faster)

= 1.2.12 =
* New: GDPR compatibility with Cookie Notice plugin

= 1.2.11 =
* Tweak: Additional IP expiration checks added as an option

= 1.2.10 =
* New: Additional transient based IP expiration checks
* Tweak: Chart.js script update to 2.7.1

= 1.2.9 =
* Fix: WooCommerce products list table broken

= 1.2.8 =
* New: Multisite compatibility
* Fix: Undefined index post_views_column on post_views_counter/includes/settings.php
* Tweak: Improved user IP handling

= 1.2.7 =
* Fix: Chart data not updating for object cached installs due to missing expire parameter
* Fix: Bug preventing hiding the counter based on user role.
* Fix: Undefined notice in the admin dashboard request

= 1.2.6 =
* Fix: Hardcoded post_views database table prefix

= 1.2.5 =
* New: REST API counter mode
* New: Adjust dashboard chart colors to admin color scheme
* Tweak: Dashboard chart query optimization
* Tweak: post_views database table optimization
* Tweak: Added plugin documentation link

= 1.2.4 =
* New: Advanced crawler detection
* Tweak: Chart.js script update to 2.4.0

= 1.2.3 =
* New: IP wildcard support
* Tweak: Delete post_views database table on deactivation

= 1.2.2 =
* Fix: Notice undefined variable: post_ids, thanks to [zytzagoo](https://github.com/zytzagoo)
* Tweak: Switched translation files storage, from local to WP repository

= 1.2.1 =
* New: Option to display post views on select page types
* Tweak: Dashboard widget query optimization

= 1.2.0 =
* New: Dashboard post views stats widget
* Fix: A couple of typos in method names

= 1.1.4 =
* Fix: Dashicons link broken.
* Tweak: Confirmed WordPress 4.4 compatibility 

= 1.1.3 =
* Fix: Duplicated views count in custom post types
* Fix: Exclude visitors checkboxes not working

= 1.1.2 =
* Fix: Most viewed posts widget broken

= 1.1.1 =
* Tweak: Enable edit views on new post.
* Tweak: Extend WP_Query post data with post_views

= 1.1.0 =
* New: Quick post views edit
* New: Bulk post views edit
* Tweak: Admin UI improvements

= 1.0.12 =
* New: Italian translation, thanks to [Rene Querin](http://www.q-design.it)

= 1.0.11 =
* New: French translation, thanks to [Theophil Bethel](http://reseau-chretien-gironde.fr/)

= 1.0.10 =
* New: Option to limit post views editing to admins only 

= 1.0.9 =
* New: Spanish translation, thanks to [Carlos Rodriguez](http://cglevel.com/)

= 1.0.8 =
* New: Croation translation, thanks to [Tomas Trkulja](http://zytzagoo.net/blog/)

= 1.0.7 =
* New: Possibility to manually set views count for each post
* New: Plugin development moved to [dFactory GitHub Repository](https://github.com/dfactoryplugins)

= 1.0.6 =
* New: Object cache support, thanks to [Tomas Trkulja](http://zytzagoo.net/blog/)
* New: Hebrew translation, thanks to [Ahrale Shrem](http://atar4u.com/)

= 1.0.5 =
* Tweak: Added number_format_i18n for displayed views count
* Tweak: Additional action hook for developers

= 1.0.4 =
* Fix: Possible issue with remove_post_views_count function

= 1.0.3 =
* New: Russian translation, thanks to moonkir
* Fix: Remove [post-views] shortcode from post excerpts if excerpt is empty

= 1.0.2 =
* Fix: Pluggable functions initialized too lately

= 1.0.0 =
Initial release

== Upgrade Notice ==

= 1.3.11 =
* Fix: Potentailly incorrect counting of post views in edge case db queries
* Fix: Possible empty chart in dashboard
* Fix: Incorrect saving of dashboard widget user options
* Tweak: Updated Chart.js to version 3.7.0