=== Post Views Counter ===
Contributors: dfactory
Tags: counter, postviews, statistics, stats, analytics, pageviews, tracking
Requires at least: 5.1
Requires PHP: 7.3.0
Tested up to: 6.4.3
Stable tag: 1.4.4
License: MIT License
License URI: http://opensource.org/licenses/MIT

Post Views Counter allows you to display how many times a post, page or custom post type had been viewed in a simple, fast and reliable way.

== Description ==

[Post Views Counter](https://postviewscounter.com/) allows you to display how many times a post, page or custom post type had been viewed with this simple, fast and easy to use plugin.

= Features include: =

* Option to select post types for which post views will be counted and displayed.
* 3 methods of collecting post views data: PHP, Javascript and REST API for greater flexibility
* Compatible with data privacy regulations
* Possibility to manually set views count for each post
* Dashboard post views stats widget
* Full Privacy regulations compliance
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
* WPML and Polylang compatible
* .pot file for translations included

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

= 1.4.4 =
* New: Option to enter meta_key for importing the views
* New: Revamped Reports for Views by Date, Views by Post and Views by Author (Pro)
* New: REST API support for post, site, term and user views (Pro)
* New: Views Period option to display views from a selected time period instead of total (Pro)
* New: [site-views] shortcode for total site views display (Pro)
* Tweak: Improved icon handling
* Tweak: Updated crawler detection

= 1.4.3 =
* Tweak: Update languages file

= 1.4.2 =
* New: Option to select position of the plugin menu

= 1.4.1 =
* Fix: Frontpage views not recorded properly

= 1.4 =
* New: Introducing Post Views Counter Pro
* New: Fast Ajax views counting mode (Pro)
* New: Google AMP support (Pro)
* New: Taxonomy term views (Pro)
* New: Author archive views (Pro)
* New: Cookies/Cookieless data storage option (Pro)
* New: Dedicated Reports page (Pro)
* New: Exporting views to CSV or XML files (Pro)
* Tweak: Improved validation and sanitization
* Tweak: Chart.js updated to 4.3.0

= 1.3.13 =
* New: Compatibility with WP 6.2 and PHP 8.2
* Fix: Invalid year in seconds
* Fix: Possible invalid cookie data in views storage
* Fix: Default database prefix
* Tweak: Switch from wp_localize_script to wp_add_inline_script
* Tweak: Updated bot detection


= 1.3.12 =
* Fix: Frontend Javascript rewritten from jQuery to Vanilla JS
* Fix: Admin Bar Style loading on every page
* Fix: Network initialization process for new sites
* Fix: IP address encryption
* Fix: REST API endpoints
* Fix: Removed couple of deprecated functions
* Tweak: Updated chart.js script to version 3.9.1
* Tweak: Added SameSite attribute to cookie

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

= 1.4.4 =
Option to enter meta_key for importing the views, Revamped Reports for Views by Date, Views by Post and Views by Author (Pro), REST API support for post, site, term and user views (Pro), Views Period option to display views from a selected time period instead of total (Pro), [site-views] shortcode for total site views display (Pro)