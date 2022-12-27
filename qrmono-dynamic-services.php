<?php
/**
 * Plugin Name: QrMono Dynamic Services
 * Plugin URI: https://qrmono.com/
 * Description: QrMono Dynamic Services.
 * Version: 1.0.0
 * Author: Ionel
 * Author URI: https://qrmono.com
 * Text Domain: qrmono
 * Domain Path: /i18n/languages/
 * Requires at least: 5.8
 * Requires PHP: 8.0
 *
 * @package QrMono
 */

define( 'WPQRMONO_VERSION', '1.0.0' );

define( 'WPQRMONO_REQUIRED_WP_VERSION', '6.0' );

define( 'WPQRMONO_TEXT_DOMAIN', 'qrmono-form' );

define( 'WPQRMONO_PLUGIN', __FILE__ );

define( 'WPQRMONO_PLUGIN_BASENAME', plugin_basename( WPQRMONO_PLUGIN ) );

define( 'WPQRMONO_PLUGIN_NAME', trim( dirname( WPQRMONO_PLUGIN_BASENAME ), '/' ) );

define( 'WPQRMONO_PLUGIN_DIR', untrailingslashit( dirname( WPQRMONO_PLUGIN ) ) );

define( 'WPQRMONO_PLUGIN_SECTIONS_DIR', WPQRMONO_PLUGIN_DIR . '/sections' );

if ( ! defined( 'WPQRMONO_LOAD_JS' ) ) {
	define( 'WPQRMONO_LOAD_JS', true );
}

if ( ! defined( 'WPQRMONO_LOAD_CSS' ) ) {
	define( 'WPQRMONO_LOAD_CSS', true );
}

if ( ! defined( 'WPQRMONO_AUTOP' ) ) {
	define( 'WPQRMONO_AUTOP', true );
}

if ( ! defined( 'WPQRMONO_ADMIN_READ_CAPABILITY' ) ) {
	define( 'WPQRMONO_ADMIN_READ_CAPABILITY', 'edit_posts' );
}

if ( ! defined( 'WPQRMONO_ADMIN_READ_WRITE_CAPABILITY' ) ) {
	define( 'WPQRMONO_ADMIN_READ_WRITE_CAPABILITY', 'publish_pages' );
}

// Deprecated, not used in the plugin core. Use WPQRMONO_plugin_url() instead.
define( 'WPQRMONO_PLUGIN_URL', untrailingslashit( plugins_url( '', WPQRMONO_PLUGIN ) ) );

require_once WPQRMONO_PLUGIN_DIR . '/load.php';
