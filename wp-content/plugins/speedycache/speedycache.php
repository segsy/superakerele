<?php
/*
Plugin Name: SpeedyCache
Plugin URI: https://speedycache.com
Description: SpeedyCache is a plugin that helps you reduce the load time of your website by means of caching, minification, and compression of your website.
Version: 1.0.8
Author: Softaculous Team
Author URI: https://speedycache.com/
Text Domain: speedycache
*/

// We need the ABSPATH
if (!defined('ABSPATH')) exit;

if(!function_exists('add_action')){
	echo 'You are not allowed to access this page directly.';
	exit;
}

$_tmp_plugins = get_option('active_plugins');

// Is the premium plugin loaded ?
if(in_array('speedycache-pro/speedycache-pro.php', $_tmp_plugins)){
	return;
}

// If SPEEDYCACHE_VERSION exists then the plugin is loaded already !
if(defined('SPEEDYCACHE_VERSION')) {
	return;
}

define('SPEEDYCACHE_FILE', __FILE__);

include_once(dirname(__FILE__).'/init.php');
