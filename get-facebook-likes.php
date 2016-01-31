<?php
/*
Plugin Name: Get Facebook Likes
Plugin URI: http://binaty.org/plugins/get-facebook-likes
Description: Simple, Fast and Powerful solution to get Facebook likes, shares and comments count of your content
Version: 1.1
Author: Tan Nguyen
Author URI: http://www.binaty.org
Tags: facebook, likes, comments, shares, widget, Post, page, shortcode
License: GPL2+
Text Domain: gfl
*/

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

//----------------------------------------------------------
// Define plugin URL for loading static files or doing AJAX
//------------------------------------------------------------
if ( ! defined( 'GFL_URL' ) )
	define( 'GFL_URL', plugin_dir_url( __FILE__ ) );

define( 'GFL_JS_URL', trailingslashit( GFL_URL . 'js' ) );
// ------------------------------------------------------------
// Plugin paths, for including files
// ------------------------------------------------------------
if ( ! defined( 'GFL_DIR' ) )
	define( 'GFL_DIR', plugin_dir_path( __FILE__ ) );

// Load the conditional logic and assets
include GFL_DIR . 'helpers.php';
include GFL_DIR . 'class-gfl-main.php';
include GFL_DIR . 'class-gfl-settings.php';

new GFL_Main;