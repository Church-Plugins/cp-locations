<?php
/**
 * Plugin constants
 */

/**
 * Setup/config constants
 */
if( !defined( 'CP_LOCATIONS_PLUGIN_FILE' ) ) {
	 define ( 'CP_LOCATIONS_PLUGIN_FILE',
	 	dirname( dirname( __FILE__ ) ) . "/cp-locations.php"
	);
}
if( !defined( 'CP_LOCATIONS_PLUGIN_DIR' ) ) {
	 define ( 'CP_LOCATIONS_PLUGIN_DIR',
	 	plugin_dir_path( CP_LOCATIONS_PLUGIN_FILE )
	);
}
if( !defined( 'CP_LOCATIONS_PLUGIN_URL' ) ) {
	 define ( 'CP_LOCATIONS_PLUGIN_URL',
	 	plugin_dir_url( CP_LOCATIONS_PLUGIN_FILE )
	);
}
if( !defined( 'CP_LOCATIONS_PLUGIN_VERSION' ) ) {
	 define ( 'CP_LOCATIONS_PLUGIN_VERSION',
	 	'1.0.0'
	);
}
if( !defined( 'CP_LOCATIONS_INCLUDES' ) ) {
	 define ( 'CP_LOCATIONS_INCLUDES',
	 	plugin_dir_path( dirname( __FILE__ ) ) . 'includes'
	);
}
if( !defined( 'CP_LOCATIONS_PREFIX' ) ) {
	define ( 'CP_LOCATIONS_PREFIX',
		'cpl'
   );
}
if( !defined( 'CP_LOCATIONS_TEXT_DOMAIN' ) ) {
	 define ( 'CP_LOCATIONS_TEXT_DOMAIN',
		'cp_library'
   );
}
if( !defined( 'CP_LOCATIONS_DIST' ) ) {
	 define ( 'CP_LOCATIONS_DIST',
		CP_LOCATIONS_PLUGIN_URL . "/dist/"
   );
}

/**
 * Licensing constants
 */
if( !defined( 'CP_LOCATIONS_STORE_URL' ) ) {
	 define ( 'CP_LOCATIONS_STORE_URL',
	 	'https://churchplugins.com'
	);
}
if( !defined( 'CP_LOCATIONS_ITEM_NAME' ) ) {
	 define ( 'CP_LOCATIONS_ITEM_NAME',
	 	'Church Plugins - Locations'
	);
}

/**
 * App constants
 */
if( !defined( 'CP_LOCATIONS_APP_PATH' ) ) {
	 define ( 'CP_LOCATIONS_APP_PATH',
	 	plugin_dir_path( dirname( __FILE__ ) ) . 'app'
	);
}
if( !defined( 'CP_LOCATIONS_ASSET_MANIFEST' ) ) {
	 define ( 'CP_LOCATIONS_ASSET_MANIFEST',
	 	plugin_dir_path( dirname( __FILE__ ) ) . 'app/build/asset-manifest.json'
	);
}
