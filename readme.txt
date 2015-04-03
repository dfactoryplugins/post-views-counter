=== Post Views Counter ===
Contributors: dfactory
Donate link: http://www.dfactory.eu/
Tags: counter, hits, postviews, post views, views, count
Requires at least: 3.8.0
Tested up to: 4.1.1
Stable tag: 1.0.5
License: MIT License
License URI: http://opensource.org/licenses/MIT

Forget WP-PostViews. Display how many times a post, page or custom post type had been viewed in a simple, fast and reliable way.

== Description ==

[Post Views Counter](http://www.dfactory.eu/plugins/post-views-counter/) allows you to display how many times a post, page or custom post type had been viewed with this simple, fast and easy to use plugin.

For more information, check out plugin page at [dFactory](http://www.dfactory.eu/) or plugin [support forum](http://www.dfactory.eu/support/forum/post-views-counter/).

= Features include: =

* Option to select post types for which post views will be counted and displayed.
* 2 methods of collecting post views data: PHP and Javascript, for greater flexibility
* Option to set time between counts
* Excluding counts from visitors: bots, logged in users, selected user roles
* Excluding users by IPs
* Restricting display by user roles
* One-click data import from WP-PostViews
* Post views display position, automatic or manual via shortcode
* W3 Cache/WP SuperCache compatible
* WPML and Polylang compatible
* .pot file for translations included

= Translations: =
* Polish - by Bartosz Arendt
* Russian - by moonkir


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

= 1.0.5 =
* Tweak: Added number_format_i18n for displayed views count
* Tweak: Additional action hook for developers