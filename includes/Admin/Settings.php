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
		add_action( 'cmb2_admin_init', [ $this, 'register_main_options_metabox' ] );
		add_action( 'cmb2_save_options_page_fields', 'flush_rewrite_rules' );
	}

	public function register_main_options_metabox() {

		$post_type = cp_locations()->setup->post_types->locations->post_type;
		/**
		 * Registers main options page menu item and form.
		 */
		$args = array(
			'id'           => 'cploc_main_options_page',
			'title'        => 'Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cploc_main_options',
			'tab_group'    => 'cploc_main_options',
			'tab_title'    => 'Main',
			'parent_slug'  => 'edit.php?post_type=' . $post_type,
			'display_cb'   => [ $this, 'options_display_with_tabs'],
		);

		$main_options = new_cmb2_box( $args );

		$main_options->add_field( array(
			'name'    => __( 'Mapbox API Key', 'cp-library' ),
			'desc'    => __( 'The API key to use for the MapBox integration. To create a new key, create a free account for MapBox and copy the key from you <a href="https://account.mapbox.com/">Account</a>.', 'cp-library' ),
			'id'      => 'mapbox_api_key',
			'type'    => 'text',
		) );

		$this->location_fields();
		$this->shortcode_fields();
		$this->license_fields();

	}

	protected function license_fields() {
		$license = new \ChurchPlugins\Setup\Admin\License( 'cploc_license', 442, CP_LOCATIONS_STORE_URL, CP_LOCATIONS_PLUGIN_FILE, get_admin_url( null, 'admin.php?page=cploc_license' ) );

		/**
		 * Registers settings page, and set main item as parent.
		 */
		$args = array(
			'id'           => 'cploc_options_page',
			'title'        => 'CP Locations Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cploc_license',
			'parent_slug'  => 'cploc_main_options',
			'tab_group'    => 'cploc_main_options',
			'tab_title'    => 'License',
			'display_cb'   => [ $this, 'options_display_with_tabs' ]
		);

		$options = new_cmb2_box( $args );
		$license->license_field( $options );
	}

	protected function location_fields() {
		$args = array(
			'id'           => 'cploc_location_options_page',
			'title'        => 'Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cploc_location_options',
			'tab_group'    => 'cploc_main_options',
			'parent_slug'  => 'cploc_main_options',
			'tab_title'    => 'Locations',
			'display_cb'   => [ $this, 'options_display_with_tabs' ],
		);

		$main_options = new_cmb2_box( $args );

		$main_options->add_field( array(
			'name'    => __( 'Singular Label', 'cp-library' ),
			'desc'    => __( 'The singular label to use for Locations.', 'cp-library' ),
			'id'      => 'singular_label',
			'type'    => 'text',
			'default' => 'Location',
		) );

		$main_options->add_field( array(
			'name'    => __( 'Plural Label', 'cp-library' ),
			'desc'    => __( 'The plural label to use for Locations.', 'cp-library' ),
			'id'      => 'plural_label',
			'type'    => 'text',
			'default' => 'Locations',
		) );
	}


	protected function shortcode_fields() {
		/**
		 * Registers settings page, and set main item as parent.
		 */
		$args = array(
			'id'           => 'cploc_shortcode_page',
			'title'        => 'CP Locations ShortCodes',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cploc_shortcode',
			'parent_slug'  => 'cploc_main_options',
			'tab_group'    => 'cploc_main_options',
			'tab_title'    => 'Shortcodes',
			'display_cb'   => [ $this, 'options_display_with_tabs' ]
		);

		$options = new_cmb2_box( $args );

//cp-location-data
//cp-locations

		$options->add_field( array(
			'name' => __( '[cp-locations]', 'cp-library' ),
			'desc' => __( 'Use the [cp-locations] shortcode on your main locations page to show the locations map and cards.', 'cp-library' ),
			'id'   => 'cp-locations-shortcode',
			'type' => 'title',
		) );

		$options->add_field( array(
			'name' => __( '[cp-location-data]', 'cp-library' ),
			'desc' => __( "Use the [cp-location-data] shortcode to display information about a location.
<br /><br />Args:<br />
* location (the ID of the location to show data for) <br />
* field (the data to retrieve, available options are 'title', 'service_times', 'subtitle', 'address', 'email', 'phone', 'pastor') <br /><br />
Example: [cp-locations location=23 field='service_times']
", 'cp-library' ),
			'id'   => 'cp-location-data-shortcode',
			'type' => 'title',
		) );
	}

	/**
	 * A CMB2 options-page display callback override which adds tab navigation among
	 * CMB2 options pages which share this same display callback.
	 *
	 * @param \CMB2_Options_Hookup $cmb_options The CMB2_Options_Hookup object.
	 */
	public function options_display_with_tabs( $cmb_options ) {
		$tabs = $this->options_page_tabs( $cmb_options );
		?>
		<div class="wrap cmb2-options-page option-<?php echo $cmb_options->option_key; ?>">
			<?php if ( get_admin_page_title() ) : ?>
				<h2><?php echo wp_kses_post( get_admin_page_title() ); ?></h2>
			<?php endif; ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $option_key => $tab_title ) : ?>
					<a class="nav-tab<?php if ( isset( $_GET['page'] ) && $option_key === $_GET['page'] ) : ?> nav-tab-active<?php endif; ?>"
					   href="<?php menu_page_url( $option_key ); ?>"><?php echo wp_kses_post( $tab_title ); ?></a>
				<?php endforeach; ?>
			</h2>
			<form class="cmb-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST"
				  id="<?php echo $cmb_options->cmb->cmb_id; ?>" enctype="multipart/form-data"
				  encoding="multipart/form-data">
				<input type="hidden" name="action" value="<?php echo esc_attr( $cmb_options->option_key ); ?>">
				<?php $cmb_options->options_page_metabox(); ?>
				<?php submit_button( esc_attr( $cmb_options->cmb->prop( 'save_button' ) ), 'primary', 'submit-cmb' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Gets navigation tabs array for CMB2 options pages which share the given
	 * display_cb param.
	 *
	 * @param \CMB2_Options_Hookup $cmb_options The CMB2_Options_Hookup object.
	 *
	 * @return array Array of tab information.
	 */
	public function options_page_tabs( $cmb_options ) {
		$tab_group = $cmb_options->cmb->prop( 'tab_group' );
		$tabs      = array();

		foreach ( \CMB2_Boxes::get_all() as $cmb_id => $cmb ) {
			if ( $tab_group === $cmb->prop( 'tab_group' ) ) {
				$tabs[ $cmb->options_page_keys()[0] ] = $cmb->prop( 'tab_title' )
					? $cmb->prop( 'tab_title' )
					: $cmb->prop( 'title' );
			}
		}

		return $tabs;
	}


}
