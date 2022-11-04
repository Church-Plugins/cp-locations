<?php
namespace CP_Locations\Setup\PostTypes;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use ChurchPlugins\Setup\Tables\SourceMeta;
use CP_Locations\Admin\Settings;
use CP_Locations\Controllers\Location as Controller;

use ChurchPlugins\Setup\PostTypes\PostType;

/**
 * Setup for custom post type: Speaker
 *
 * @author costmo
 * @since 1.0
 */
class Location extends PostType {
	
	/**
	 * @var array 
	 */
	protected static $_locations_regex = false;
	
	/**
	 * Child class constructor. Punts to the parent.
	 *
	 * @author costmo
	 */
	protected function __construct() {
		$this->post_type = "cploc_location";

		$this->single_label = apply_filters( "cploc_single_{$this->post_type}_label", Settings::get_location( 'singular_label', 'Location' ) );
		$this->plural_label = apply_filters( "cploc_plural_{$this->post_type}_label", Settings::get_location( 'plural_label', 'Locations' ) );

		parent::__construct();
	}

	public function add_actions() {
		add_filter( 'cp_source_meta_keys_enum', [ $this, 'location_meta_keys' ] );

		parent::add_actions();
	}

	/**
	 * Add custom meta keys for locations
	 * 
	 * @param $keys
	 *
	 * @return mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function location_meta_keys( $keys ) {
//		$keys[] = 'address';
		return $keys;
	}
	
	/**
	 * Get the slug for this taxonomy
	 * 
	 * @return false|mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_slug() {
		if ( ! $type = get_post_type_object( $this->post_type ) ) {
			return false;
		}
		
		return false;
	}	

	/**
	 * Return custom meta keys
	 *
	 * @return array|mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function meta_keys() {
		return SourceMeta::get_keys();
	}

	/**
	 * Setup arguments for this CPT
	 *
	 * @return array
	 * @author costmo
	 */
	public function get_args() {
		$args               = parent::get_args();
		$args['menu_icon']  = apply_filters( "{$this->post_type}_icon", 'dashicons-location' );
		$args['has_archive'] = false;
		$args['supports'][] = 'page-attributes';
		$args['rewrite'] = false; // we handle this in register_post_type
		return $args;
	}
	
	public function locations_regex() {
		if ( false === self::$_locations_regex ) {
			$locations = \CP_Locations\Models\Location::get_all_locations( true );
			self::$_locations_regex = implode( '|', wp_list_pluck( $locations, 'post_name' ) ); 
		}
		
		return self::$_locations_regex;
	}

	/**
	 * Register post type and handle custom permastructure
	 * 
	 * @throws \ChurchPlugins\Exception
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function register_post_type() {
		parent::register_post_type(); // TODO: Change the autogenerated stub

		// we need to filter the auto generated rules
		add_filter( "{$this->post_type}_rewrite_rules", [ $this, 'permastructure' ] );
		
		// grab locations and create our custom rewrite tag
		$locations = $this->locations_regex();
		add_rewrite_tag( "%$this->post_type%", "($locations)", "post_type=$this->post_type&name=" );
		add_permastruct( $this->post_type, "/%$this->post_type%", [ 'with_front' => false ] );
	}

	/**
	 * Default permastructure functionality strips out "(" and ")", we need these re-inserted for our location match
	 *
	 * @param $rules
	 *
	 * @return array
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function permastructure( $rules ) {
		$locations = $this->locations_regex();
		
		$new_rules = [];
		foreach( $rules as $match => $rule ) {
			// re-insert non-matching group
			$new_match = str_replace( $locations . '/', "(?:$locations)/", $match );
			$new_rules[ $new_match ] = $rule;
		}
		
		return $new_rules;
	}
	
	public function register_metaboxes() {
		$this->meta_details();
	}

	protected function meta_details() {
		$cmb = new_cmb2_box( [
			'id' => 'location_meta',
			'title' => $this->single_label . ' ' . __( 'Details', 'cp-locations' ),
			'object_types' => [ $this->post_type ],
			'context' => 'normal',
			'priority' => 'high',
			'show_names' => true,
		] );

		$cmb->add_field( [
			'name' => __( 'Subtitle', 'cp-locations' ),
			'desc' => __( 'The text to show under the location name on dropdowns and maps.', 'cp-locations' ),
			'id'   => 'subtitle',
			'type' => 'textarea_small',
			'attributes' => [
				'rows' => 1,
			],
		], 5 );

		$cmb->add_field( [
			'name' => __( 'Pastor', 'cp-locations' ),
			'desc' => __( 'The name of the lead pastor at this location.', 'cp-locations' ),
			'id'   => 'pastor',
			'type' => 'text',
		], 5 );

		$cmb->add_field( [
			'name' => __( 'Address', 'cp-locations' ),
			'desc' => __( 'The address of this location.', 'cp-locations' ),
			'id'   => 'address',
			'type' => 'textarea_small',
			'attributes' => [
				'rows' => 2,
			],
		], 10 );

		$cmb->add_field( [
			'name' => __( 'Geolocation', 'cp-locations' ),
			'desc' => __( 'The geo coordinates of this location.', 'cp-locations' ),
			'id'   => 'geo_coordinates',
			'type' => 'text',
		], 10 );

		$cmb->add_field( [
			'name' => __( 'Phone Number', 'cp-locations' ),
			'desc' => __( 'The phone number for this location.', 'cp-locations' ),
			'id'   => 'phone',
			'type' => 'text_medium',
		], 15 );

		$cmb->add_field( [
			'name' => __( 'Email', 'cp-locations' ),
			'desc' => __( 'The email for this location.', 'cp-locations' ),
			'id'   => 'email',
			'type' => 'text_email',
		], 20 );

		$group_field_id = $cmb->add_field( [
			'name' => __( 'Service Times', 'cp-locations' ),
			'id'   => 'service_times',
			'type' => 'group',
			'options' => array(
				'group_title'   => __( 'Time {#}', 'cp-locations' ),
				'add_button'    => __( 'Add Another Time', 'cp-locations' ),
				'remove_button' => __( 'Remove Time', 'cp-locations' ),
				'sortable'      => true,
			),
		], 25 );
	
		$cmb->add_group_field( $group_field_id, [
			'name' => __( 'Day of Week', 'cp-locations' ),
			'id'   => 'day',
			'type' => 'select',
			'default' => 'sunday',
			'options' => [
				'sunday'    => __( 'Sunday', 'cp-locations' ),
				'monday'    => __( 'Monday', 'cp-locations' ),
				'tuesday'   => __( 'Tuesday', 'cp-locations' ),
				'wednesday' => __( 'Wednesday', 'cp-locations' ),
				'thursday'  => __( 'Thursday', 'cp-locations' ),
				'friday'    => __( 'Friday', 'cp-locations' ),
				'saturday'  => __( 'Saturday', 'cp-locations' ),
			],
		], 30 );
		
		$cmb->add_group_field( $group_field_id, [
			'name'    => __( 'Time', 'cp-locations' ),
			'id'      => 'time',
			'type'    => 'text_time',
			'description' => __( 'HH:mm A', 'cp-locations' ),
		] );

		$cmb->add_group_field( $group_field_id, [
			'name'        => __( 'Time Description', 'cp-locations' ),
			'id'          => 'time_desc',
			'type'        => 'text',
			'description' => __( 'Description of the time. When this is not blank it will be used instead of Time.', 'cp-locations' ),
		] );
		
		$cmb->add_group_field( $group_field_id, [
			'name'        => __( 'Special Service', 'cp-locations' ),
			'id'          => 'is_special',
			'type'        => 'checkbox',
			'description' => __( 'Check to designate this as a special service that shouldn\'t show in the normal times list.', 'cp-locations' ),
		] );
		
		do_action( 'cploc_location_meta_details', $cmb, $this );

	}

	/**
	 * Save term data and update rewrite rules
	 * 
	 * @param $post_id
	 *
	 * @return bool|\ChurchPlugins\Models\Item|\ChurchPlugins\Models\ItemType|\ChurchPlugins\Models\Source
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function save_post( $post_id ) {
		$post = get_post();
		$tax = cp_locations()->setup->taxonomies->location->taxonomy;
		if ( ! $term = get_term_by( 'slug', 'location_' . $post_id, $tax ) ) {
			wp_insert_term( $post->post_title, $tax, [ 'slug' => 'location_' . $post_id ] );
		} else {
			wp_update_term( $term->term_id, $tax, [ 'name' => $post->post_title, 'slug' => 'location_' . $post_id ] );
		}
		
		// save geo data
		if ( ! get_post_meta( $post_id, 'geo_coordinates', true ) ) {
			$location = new Controller( $post_id );
			$geo      = $location->get_geo( true );
			
			if ( ! empty( $geo['center'] ) ) {
				update_post_meta( $post_id, 'geo_coordinates', implode( ', ', $geo['center'] ) );
			}
		}
		
		// our permastructure is based on this 
		flush_rewrite_rules( true );
		return parent::save_post( $post_id );
	}
	
}
