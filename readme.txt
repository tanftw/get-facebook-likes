=== Plugin Name ===
Contributors: tanng
Tags: facebook, likes, comments, shares, widget, Post, posts, admin, page, shortcode, plugin, get facebook likes, custom field
Requires at least: 3.6
Tested up to: 4.4.1
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple, Fast and Powerful solution to get Facebook likes, shares and comments count of your content.

== Description ==

[Get Facebook Likes](http://binaty.org/plugins/get-facebook-likes) interacts with Facebook Graph API and saves likes, shares, and comments count to post meta right after users hit Like/Share buttons or leaves a Facebook comment. Therefore you can do some awesome stuff that you think it's impossible before like sort posts, order search results, vote, deeper analytics... by Facebook likes (and shares, comments too).

Get Facebook Likes also comes with powerful event system which lets you expand and build robust addons on it. 

As we're both developers and end users, we're obviously love fast & ease of use plugins. Get Facebook Likes uses native Facebook JS SDK and works right after activated, no cronjob setup is required. Just a few options to tweak your script to the highest speed.

### Features
* Basic Mode which compatibility with all WP Sites 
* Advanced Mode which have better performance and more accurate
* Use native Facebook API to listening user event and get facebook likes/shares/comments
* Save likes/shares/comments to post meta for further use
* Built-in shortcodes `[likes]`, `[shares]`, and `[comments]`
* Likes custom column and sorting
* Most favourite content on the Dashboard area which display top 10 posts which have most likes+shares+comments
* Display likes/shares/comments in Post Editing screen if [Meta Box](https://wordpress.org/plugins/meta-box) plugin is installed


### Plugin Links
- [Project Page and Documentation](http://binaty.org/plugins/get-facebook-likes)
- [Github](https://github.com/tanng/get-facebook-likes)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/get-facebook-likes` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the `Settings\Get Facebook Likes` screen to configure the plugin

== Frequently Asked Questions ==

= Does it requires Facebook App ID? =
No, Get Facebook Likes works without App ID. But it's recommended to use more advanced feature of Facebook like App Tracking, Moderate Comments, etc...

= I have already installed Facebook JS SDK, what option I should use? =
If you've already installed Facebook JS SDK by placing their script right after open `body` tag or by another plugin. Just uncheck `Auto add Facebook JS SDK to wp_head` option and place `GFL_Main.init()` after `FB.init();` in `window.fbAsyncInit` method like **Setup Guide** in `Settings\Get Facebook Likes`

= Does the plugin count the likes from the beginning of time? =
Yes, each time the update like event was fired (also applied to share, comment), it makes a `GET` request to Facebook to retrieve total count of them. This because some users may like or share your post outside of your website.


== Screenshots ==
1. Settings Page
1. Tracking Meta Box
1. Custom Column and Sorting
1. Most Favourited Content


== Changelog ==


= 1.1 (Feb 02, 2016) =
* **New** Add JS Events
* **New** Add I18N support
* **New** Allows users set JS SDK Locale
* **Improvement** Use `gfl_count()` instead of `gfl_facebook_count()`;

= 1.0 (Jan, 24, 2016)=
* Initial Release