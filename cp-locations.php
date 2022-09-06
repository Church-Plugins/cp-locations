<?php
/**
 * Plugin Name: Church Plugins - Locations
 * Plugin URL: https://churchplugins.com
 * Description: Church locations plugin for managing campuses
 * Version: 1.0.1
 * Author: Church Plugins
 * Author URI: https://churchplugins.com
 * Text Domain: cp-locations
 * Domain Path: languages
 */

require_once( dirname( __FILE__ ) . "/includes/Constants.php" );

require_once( CP_LOCATIONS_PLUGIN_DIR . "/includes/ChurchPlugins/init.php" );
require_once( CP_LOCATIONS_PLUGIN_DIR . 'vendor/autoload.php' );


use CP_Locations\Init as Init;

/**
 * @var CP_Locations\Init
 */
global $cp_locations;
$cp_locations = cp_locations();

/**
 * @return CP_Locations\Init
 */
function cp_locations() {
	return Init::get_instance();
}

/**
 * Load plugin text domain for translations.
 *
 * @return void
 */
function cp_locations_load_textdomain() {

	// Traditional WordPress plugin locale filter
	$get_locale = get_user_locale();

	/**
	 * Defines the plugin language locale used in RCP.
	 *
	 * @var string $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
	 *                  otherwise uses `get_locale()`.
	 */
	$locale        = apply_filters( 'plugin_locale',  $get_locale, 'cp-locations' );
	$mofile        = sprintf( '%1$s-%2$s.mo', 'cp-locations', $locale );

	// Setup paths to current locale file
	$mofile_global = WP_LANG_DIR . '/cp-locations/' . $mofile;

	if ( file_exists( $mofile_global ) ) {
		// Look in global /wp-content/languages/cp-locations folder
		load_textdomain( 'cp-locations', $mofile_global );
	}

}
add_action( 'init', 'cp_locations_load_textdomain' );
