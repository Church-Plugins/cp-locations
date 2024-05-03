<?php

namespace CP_Locations\Admin;

/**
 * Plugin settings
 *
 */
class Settings {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * License
	 *
	 * @var \ChurchPlugins\Setup\Admin\License
	 */
	public $license;

	/**
	 * Only make one instance of \CP_Locations\Settings
	 *
	 * @return Settings
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Settings ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get a value from the options table
	 *
	 * @param $key
	 * @param $default
	 * @param $group
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function get( $key, $default = '', $group = 'cploc_main_options' ) {
		$options = get_option( $group, [] );

		if ( isset( $options[ $key ] ) ) {
			$value = $options[ $key ];
		} else {
			$value = $default;
		}

		return apply_filters( 'cploc_settings_get', $value, $key, $group );
	}

	/**
	 * Get advanced options
	 *
	 * @param $key
	 * @param $default
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function get_advanced( $key, $default = '' ) {
		return self::get( $key, $default, 'cploc_advanced_options' );
	}

	public static function get_location( $key, $default = '' ) {
		return self::get( $key, $default, 'cploc_location_options' );
	}

	/**
	 * Class constructor. Add admin hooks and actions
	 *
	 */
	protected function __construct() {
		add_action( 'admin_menu', [ $this, 'settings_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		$this->license = new \ChurchPlugins\Setup\Admin\License( 'cploc_license', 442, CP_LOCATIONS_STORE_URL, CP_LOCATIONS_PLUGIN_FILE, get_admin_url( null, 'admin.php?page=cploc_license' ) );
	}

	/**
	 * Outputs the react entrypoint.
	 *
	 * @since 1.1.0
	 */
	public function settings_page() {
		add_submenu_page(
			'edit.php?post_type=' . cp_locations()->setup->post_types->locations->post_type,
			__( 'CP Locations Settings', 'cp-locations' ),
			__( 'Settings', 'cp-locations' ),
			'manage_options',
			'cploc_settings',
			[ $this, 'settings_page_content' ]
		);
	}

	/**
	 * Outputs the settings page content.
	 */
	public function settings_page_content() {
		?>
		<div class="cp_settings_root cp-locations"></div>
		<?php
	}

	/**
	 * Enqueue scripts and styles for the settings page.
	 *
	 * @since 1.1.0
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		if ( 'cploc_location_page_cploc_settings' !== $screen->id ) {
			return;
		}

		cp_locations()->enqueue_script( 'admin-settings', [ 'jquery', 'wp-api-request', 'wp-util', 'wp-data' ], [ 'wp-components' ] );
	}

	/**
	 * Registers the REST API routes for the plugin.
	 *
	 * @since 1.1.0
	 */
	public function register_rest_routes() {
		register_rest_route(
			'cp-locations/v1',
			'/settings/(?P<group>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => 'POST',
				'callback'            => [ $this, 'update_settings' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'cp-locations/v1',
			'/settings/(?P<group>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_settings' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Retrieves the settings for the plugin.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 * @since 1.1.0
	 */
	public function get_settings( \WP_REST_Request $request ) {
		$group = $request->get_param( 'group' );

		if ( ! $group ) {
			$group = 'cploc_main_options';
		}

		$options = get_option( 'cploc_' . $group, [] );

		return rest_ensure_response( $options );
	}

	/**
	 * Updates the settings for the plugin.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 * @since 1.1.0
	 */
	public function update_settings( \WP_REST_Request $request ) {
		$group   = $request->get_param( 'group' );
		$options = $request->get_json_params();

		if ( ! $group ) {
			$group = 'cploc_main_options';
		}

		update_option( 'cploc_' . $group, $options );

		return rest_ensure_response( $options );
	}
}
