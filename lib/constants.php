<?php
/**
 * Constants used by this plugin

 * @author jamie3d
 */

// The current version of this plugin
if( !defined( 'RAGE_AVATARS_VERSION' ) ) define( 'RAGE_AVATARS_VERSION', '1.0.3' );

// The cache prefix
if( !defined( 'RAGE_AVATARS_CACHE_PREFIX' ) ) define( 'RAGE_AVATARS_CACHE_PREFIX', 'rage-cache' );

// The directory the plugin resides in
if( !defined( 'RAGE_AVATARS_DIRNAME' ) ) define( 'RAGE_AVATARS_DIRNAME', dirname( dirname( __FILE__ ) ) );

// The URL path of this plugin
if( !defined( 'RAGE_AVATARS_URLPATH' ) ) define( 'RAGE_AVATARS_URLPATH', WP_PLUGIN_URL . "/" . plugin_basename( RAGE_AVATARS_DIRNAME ) );

if( !defined( 'IS_AJAX_REQUEST' ) ) define( 'IS_AJAX_REQUEST', ( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) );