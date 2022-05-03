<?php
namespace CP_Locations;

use CP_Locations\Admin\Settings;

/**
 * Provides the global $cp_library object
 *
 * @author costmo
 */
class Init {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * @var Setup\Init
	 */
	public $setup;

	/**
	 * @var API\Init
	 */
	public $api;

	/**
	 * @var 
	 */
	public $geoAPI;
	
	public $enqueue;

	/**
	 * Only make one instance of Init
	 *
	 * @return Init
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Init ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Class constructor: Add Hooks and Actions
	 *
	 */
	protected function __construct() {
		$this->enqueue = new \WPackio\Enqueue( 'cpLocations', 'dist', $this->get_version(), 'plugin', CP_LOCATIONS_PLUGIN_FILE );
		add_action( 'plugins_loaded', [ $this, 'maybe_setup' ], - 9999 );
		add_action( 'init', [ $this, 'maybe_init' ] );
	}

	/**
	 * Plugin setup entry hub
	 *
	 * @return void
	 */
	public function maybe_setup() {
		if ( ! $this->check_required_plugins() ) {
			return;
		}

		$this->includes();
		$this->actions();
		$this->app_init();
	}

	/**
	 * Actions that must run through the `init` hook
	 *
	 * @return void
	 * @author costmo
	 */
	public function maybe_init() {

		if ( ! $this->check_required_plugins() ) {
			return;
		}

	}

	/**
	 * Entry point for initializing the React component
	 *
	 * @return void
	 * @author costmo
	 */
	protected function app_init() {
		add_filter( 'script_loader_tag', [ $this, 'app_load_scripts' ], 10, 3 );
		add_action( 'wp_enqueue_scripts', [ $this, 'app_enqueue' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'init', [ $this, 'rewrite_rules' ], 100 );
	}

	public function rewrite_rules() {
		return;
		
		if ( $this->setup->post_types->item_type_enabled() ) {
			$type = get_post_type_object( $this->setup->post_types->item_type->post_type )->rewrite['slug'];
			add_rewrite_tag( '%type-item%', '([^&]+)' );
			add_rewrite_rule("^$type/([^/]*)/([^/]*)?",'index.php?cpl_item_type=$matches[1]&type-item=$matches[2]','top');
		}

		$flush = '1';

		if ( get_option( '_cpl_needs_flush' ) != $flush ) {
			flush_rewrite_rules(true);
			update_option( '_cpl_needs_flush', $flush );
		}
	}

	/**
	 * `script_loader_tag` filters for the app
	 *
	 * @param String $tag
	 * @param String $handle
	 * @param String $src
	 * @return String
	 * @author costmo
	 */
	public function app_load_scripts( $tag, $handle, $src ) {

		if( 1 !== preg_match( '/^' . CP_LOCATIONS_PREFIX . '-/', $handle ) ) {
			return $tag;
		}

		return str_replace( ' src', ' async defer src', $tag );
	}

	public function admin_scripts() {
		$this->enqueue->enqueue( 'styles', 'admin', [] );
		$this->enqueue->enqueue( 'scripts', 'admin', [] );
	}

	/**
	 * `wp_enqueue_scripts` actions for the app's compiled sources
	 *
	 * @return void
	 * @author costmo
	 */
	public function app_enqueue() {
		wp_enqueue_script( 'cploc-leaflet', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js' );
		$this->enqueue->enqueue( 'styles', 'main', [] );
		$this->enqueue->enqueue( 'scripts', 'main', [] );
		$scripts = $this->enqueue->enqueue( 'app', 'main', [ 'js_dep' => ['jquery'] ] );

		$cpl_vars = apply_filters( 'cploc_app_vars', [
			'site' => [
				'title' => get_bloginfo( 'name', 'display' ),
//				'thumb' => Settings::get( 'default_thumbnail', CP_LOCATIONS_PLUGIN_URL . 'assets/images/cpl-logo.jpg' ),
//				'logo'  => Settings::get( 'logo', CP_LOCATIONS_PLUGIN_URL . 'assets/images/cpl-logo.jpg' ),
				'url'   => get_site_url(),
				'path'  => '',
			],
			'components' => [
				'mobileTop' => ''
			],
			'i18n' => [
				'playAudio' => __( 'Play Audio', 'cp-locations' ),
				'playVideo' => __( 'Play Video', 'cp-locations' ),
			],
		] );

		if ( isset( $scripts['js'], $scripts['js'][0], $scripts['js'][0]['handle'] ) ) {
			wp_localize_script( $scripts['js'][0]['handle'], 'cplocVars', $cpl_vars );
		}

	}

	/**
	 * Includes
	 *
	 * @return void
	 */
	protected function includes() {
		require_once( 'Templates.php' );
		Admin\Init::get_instance();
		$this->setup = Setup\Init::get_instance();
		$this->geoAPI = new GeoLocation\MapBox\MapBox();
		API\Init::get_instance();
	}

	/**
	 * Actions and Filters
	 *
	 * @return void
	 */
	protected function actions() {
		add_action( 'wp_head', [ $this, 'global_css_vars' ] );
	}

	/** Actions **************************************/

	public function global_css_vars() {
		?>
		<style>
			:root {
				--cpl-primary: <?php echo Settings::get( 'color_primary', '#333333' ); ?>;
			}
		</style>
		<?php
	}

	/**
	 * Required Plugins notice
	 *
	 * @return void
	 */
	public function required_plugins() {
		printf( '<div class="error"><p>%s</p></div>', __( 'Your system does not meet the requirements for Church Plugins - Locations', 'cp-locations' ) );
	}

	/** Helper Methods **************************************/

	public function get_default_thumb() {
		return CP_LOCATIONS_PLUGIN_URL . '/app/public/logo512.png';
	}

	/**
	 * Make sure required plugins are active
	 *
	 * @return bool
	 */
	protected function check_required_plugins() {

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		// @todo check for requirements before loading
		if ( 1 ) {
			return true;
		}

		add_action( 'admin_notices', array( $this, 'required_plugins' ) );

		return false;
	}

	/**
	 * Gets the plugin support URL
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_support_url() {
		return 'https://churchplugins.com/support';
	}

	/**
	 * Returns the plugin name, localized
	 *
	 * @since 1.0.0
	 * @return string the plugin name
	 */
	public function get_plugin_name() {
		return __( 'Church Plugins - Locations', 'cp-locations' );
	}

	/**
	 * Returns the plugin name, localized
	 *
	 * @since 1.0.0
	 * @return string the plugin name
	 */
	public function get_plugin_path() {
		return CP_LOCATIONS_PLUGIN_DIR;
	}

	/**
	 * Provide a unique ID tag for the plugin
	 *
	 * @return string
	 */
	public function get_id() {
		return 'cp-locations';
	}

	/**
	 * Provide a unique ID tag for the plugin
	 *
	 * @return string
	 */
	public function get_version() {
		return '0.0.1';
	}

	/**
	 * Get the API namespace to use
	 *
	 * @return string
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_api_namespace() {
		return $this->get_id() . '/v1';
	}

	/**
	 * Get the key for the geo api
	 * 
	 * @return string
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_api_key() {
		return 'pk.eyJ1IjoidGFubmVybW91c2hleSIsImEiOiJjbDFlaTkwdWcwcm9yM2NueGRhdmR3M3Y1In0.Su6h_mXCh6WfLO4aJ5uMFg';
	}

}
