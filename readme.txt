=== Above and Below ===
Contributors: bnmng
Tags: post content
Donate link: http://bnmng.com/donations/
Requires at least: 4.0
Tested up to: 5.0
Requires PHP: 7.0
Stable tag: 1.1
License: GPLv2 or later

Adds text above and below post content, customizable for different post types, categories, authors, etc..

== Description ==

This plugin is used by to add text above and below post content.  You can add different text to different selections of posts,
which can be selected based on post type, author, or taxonomies such as category.

This plugin does not alter the post in the database.

== Frequently Asked Questions ==

= Will this work with custom post types? =

This will work with some custom post types, and can use custom taxonomies of those post types.

Generally, the more like a regular post the custom post type is, the more likely it is that this plugin will work as expected.

Two post types that this plugin was tested with are Portfolio Post Type by Devin Price and The Events Calendar by Tribe Events.

This plugin works with Portfolio Post Types as well as it does for regular posts.

This plugin also works with The Events Calendar, but for events that don't have post content (for example, events with just a title and a date),
this plugin will not do anything.

= Does this work with HTML and javascript? =

This plugins allows you to add HTML, but filters the added text through wp_kses_post, which strips out scripts. 

You can use HTML to open an element, like a div element above the post and close it below.  This can be used if, for example,
you want to make all posts of a certain category display in red letters.

You have to ensure that you properly format you HTML - make sure you close your tags.

== Screenshots ==

1. Settings for an instance
2. Adding a custom post type

== Changelog ==
1.0
Initial Submission

1.1
Re-submission.
Refactored code to comply with WP coding standards
Moved the move and delete functions from PHP to javascript
