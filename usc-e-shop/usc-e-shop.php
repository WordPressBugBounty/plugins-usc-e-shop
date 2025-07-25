<?php
/**
 * Plugin Name: Welcart e-Commerce
 * Plugin URI: https://www.welcart.com/
 * Description: Welcart builds the management system with a net shop on WordPress.
 * Version: 2.11.20
 * Author: Welcart Inc.
 * Author URI: https://www.welcart.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: usces
 * Domain Path: /languages/
 * Requires at least: 5.6
 * Requires PHP: 7.4
 *
 * @package Welcart
 */

define( 'USCES_VERSION', '2.11.20.2507221' );
define( 'USCES_DB_ACCESS', '1.5' );
define( 'USCES_DB_MEMBER', '1.1' );
define( 'USCES_DB_MEMBER_META', '1.1' );
define( 'USCES_DB_ORDER', '2.0' );
define( 'USCES_DB_ORDER_META', '1.2' );
define( 'USCES_DB_ORDERCART', '1.0' );
define( 'USCES_DB_ORDERCART_META', '1.0' );
define( 'USCES_DB_LOG', '1.1' );
define( 'USCES_DB_ACTING_LOG', '1.0' );
define( 'USCES_DB_ITEM', '1.2' );
define( 'USCES_DB_SKUS', '1.2' );
define( 'USCES_DB_OPTS', '1.0' );
define( 'USCES_DB_ADMIN_LOG', '1.1' );

define( 'USCES_UP07', 1 );
define( 'USCES_UP11', 2 );
define( 'USCES_UP14', 3 );
define( 'USCES_UP141', 5 );
define( 'USCES_UP143', 1 );

define( 'USCES_WP_CONTENT_DIR', WP_CONTENT_DIR );
define( 'USCES_WP_CONTENT_URL', WP_CONTENT_URL );
define( 'USCES_WP_PLUGIN_DIR', WP_PLUGIN_DIR );
define( 'USCES_WP_PLUGIN_URL', WP_PLUGIN_URL );
define( 'USCES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'USCES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'USCES_PLUGIN_FOLDER', dirname( plugin_basename( __FILE__ ) ) );
define( 'USCES_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'USCES_CART_FOLDER', 'usces-cart' );
define( 'USCES_MEMBER_FOLDER', 'usces-member' );
define( 'USCES_ADMIN_SSL_BASE_NAME', 'admin-ssl.php' );
define( 'USCES_ADMIN_URL', site_url() . '/wp-admin/admin.php' );
define( 'USCES_EXTENSIONS_DIR', USCES_PLUGIN_DIR . '/extensions' );
define( 'USCES_EXTENSIONS_URL', USCES_PLUGIN_URL . '/extensions' );
define( 'USCES_UPDATE_INFO_URL', 'https://endpoint.welcart.org' );
define( 'USCES_UPLOAD_TEMP', '/uploads/welcart-uptemp' );

global $usces_settings, $usces_states, $usces_p, $usces_essential_mark;
require_once USCES_PLUGIN_DIR . 'functions/included_first.php';
add_filter( 'locale', 'usces_filter_locale' );
require_once USCES_PLUGIN_DIR . 'includes/settings.php';

global $usces;
$usces = new usc_e_shop();
$usces->regist_action();

require_once USCES_PLUGIN_DIR . 'functions/template_func.php';
require_once USCES_PLUGIN_DIR . 'includes/purchase/wel-purchase-functions.php';

register_activation_hook( __FILE__, array( $usces, 'set_initial' ) );
register_deactivation_hook( __FILE__, array( $usces, 'deactivate' ) );

require_once USCES_PLUGIN_DIR . 'includes/default_filters.php';
