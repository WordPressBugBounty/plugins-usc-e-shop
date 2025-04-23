<?php
/**
 * Welcart CSV Importer.
 *
 * @package Welcart
 */

// phpcs:disable WordPress.WP.AlternativeFunctions
// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

// Only run in the admin area.
if ( is_admin() ) {
	wel_create_uploads_folder();
}

require_once USCES_PLUGIN_DIR . 'includes/importer/csv-importer-class.php';

/**
 * Start output buffering for admin notices.
 */
function wel_csv_importer_start_admin_notices_buffer() {
	ob_start(); // Start output buffering.
}
add_action( 'admin_notices', 'wel_csv_importer_start_admin_notices_buffer', 1 );

/**
 * End and clean output buffering for admin notices.
 */
function wel_csv_importer_end_admin_notices_buffer() {
	ob_end_clean(); // Discard output buffer contents.
}
add_action( 'all_admin_notices', 'wel_csv_importer_end_admin_notices_buffer', PHP_INT_MAX );

/**
 * Enqueue JS/CSS assets for the admin screen.
 *
 * @param string $hook Page hook.
 */
function wel_csv_importer_enqueue_assets( $hook ) {
	// Load assets only on the welcart-csv-importer admin page.
	if ( 'admin_page_welcart-csv-importer' !== $hook ) {
		return;
	}

	wp_enqueue_script(
		'wcex-csv-importer-js',
		USCES_PLUGIN_URL . 'includes/importer/js/csv-importer.js',
		array( 'jquery' ),
		'1.12',
		true
	);
	wp_enqueue_style(
		'wcex-csv-importer-css',
		USCES_PLUGIN_URL . 'includes/importer/css/csv-importer.css',
		array(),
		'1.8'
	);

	$script_data = array(
		'ajax_url'  => admin_url( 'admin-ajax.php' ),
		'admin_url' => admin_url( 'admin.php' ),
	);
	wp_localize_script( 'wcex-csv-importer-js', 'wel_csv_importer_data', $script_data );
}
add_action( 'admin_enqueue_scripts', 'wel_csv_importer_enqueue_assets' );

/**
 * Create the Welcart uploads folder.
 */
function wel_create_uploads_folder() {
	// Retrieve WordPress upload directory information.
	$upload_dir = wp_upload_dir();
	$base_dir   = $upload_dir['basedir'];

	// Path for the folder to be created.
	$welcart_dir = $base_dir . '/welcart-uploads';

	// Create the folder if it does not exist.
	if ( ! file_exists( $welcart_dir ) ) {
		wp_mkdir_p( $welcart_dir );
	}

	// Path for the .htaccess file.
	$htaccess_file = $welcart_dir . '/.htaccess';
	// If .htaccess does not exist, create it and write "deny from all".
	if ( ! file_exists( $htaccess_file ) ) {
		file_put_contents( $htaccess_file, 'deny from all' );
	}

	// Path for index.html.
	$index_file = $welcart_dir . '/index.html';
	// If index.html does not exist, create an empty file.
	if ( ! file_exists( $index_file ) ) {
		file_put_contents( $index_file, '' );
	}

	if ( ! defined( 'WELCART_UPLOADS_DIR' ) ) {
		define( 'WELCART_UPLOADS_DIR', $welcart_dir );
	}
}
