=== Plugin Name ===
Contributors: tanng
Tags: facebook, posts, shortcodes
Requires at least: 3.6
Tested up to: 4.4.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple, Fast and Powerful solution to get Facebook likes, shares and comments count of your content.

== Description ==

Get Facebook Likes interacts with Facebook Graph API and saves likes, shares, and comments count to post meta right after users hit Like/Share button or leaves a Facebook comment. Therefore you can do some awesome stuff that you think it's impossible before like order post, order search results, vote, deeper analytics... by Facebook likes (and shares, comments too).

As we're both developers and end users, we're obviously love fast & ease of use plugins. Get Facebook Likes uses native Facebook JS SDK and works right after activated, no cronjob setup is required. Just a few options to tweak your script to the highest speed.

### Features
* Basic Mode which compatibility with all WP Sites 
* Advanced Mode which better performance and more accurate
* Use native Facebook API so listening user event and get facebook likes/shares/comments
* Save likes/shares/comments to post meta for further use
* Built-in shortcodes `[likes]`, `[shares]`, and `[comments]`
* Likes custom column
* Most favourite content on the Dashboard area which display top 10 posts which have most likes+shares+comments
* Display likes/shares/comments to Post Editing screen if Meta Box plugin is installed

### Plugin Links
- [Project Page](http://binaty.org/plugins/get-facebook-likes)
- [Github](https://github.com/tanng/get-facebook-likes)
- [Documentation](https://github.com/tanng/get-facebook-likes/wiki)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/get-facebook-likes` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the `Settings\Get Facebook Likes` screen to configure the plugin

== Frequently Asked Questions ==

= Does it requires Facebook App ID? =

No, Get Facebook Likes works without App ID. But it's recommended to use more advanced feature of Facebook like App Tracking, Moderate Comments, etc...

= I have already installed Facebook JS SDK, what option I should use? =
If you've already installed Facebook JS SDK by placing their script right after open `body` tag or by another plugin. Just uncheck `Auto add Facebook JS SDK to wp_head` option and place `GetFacebookLikes.init();` after `FB.init();` in `window.fbAsyncInit` method like **Setup Guide** in `Settings\Get Facebook Likes`


== Changelog ==

= 1.0 =
* Initial Release