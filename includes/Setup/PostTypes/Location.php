<?php
namespace CP_Locations\Setup\PostTypes;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use ChurchPlugins\Setup\Tables\SourceMeta;
use CP_Locations\Admin\Settings;

use ChurchPlugins\Setup\PostTypes\PostType;

/**
 * Setup for custom post type: Speaker
 *
 * @author costmo
 * @since 1.0
 */
class Location extends PostType {

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
		
		return $args;
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
			'name' => __( 'Address', 'cp-locations' ),
			'desc' => __( 'The address of this location.', 'cp-locations' ),
			'id'   => 'address',
			'type' => 'textarea_small',
			'attributes' => [
				'rows' => 2,
			],
		] );

		$cmb->add_field( [
			'name' => __( 'Phone Number', 'cp-locations' ),
			'desc' => __( 'The phone number for this location.', 'cp-locations' ),
			'id'   => 'phone',
			'type' => 'text_medium',
		] );

		$cmb->add_field( [
			'name' => __( 'Email', 'cp-locations' ),
			'desc' => __( 'The email for this location.', 'cp-locations' ),
			'id'   => 'email',
			'type' => 'text_email',
		] );

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
		] );
	
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
		] );
		
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

	}
	
	public function save_post( $post_id ) {
		$post = get_post();
		$tax = cp_locations()->setup->taxonomies->location->taxonomy;
		if ( ! $term = get_term_by( 'slug', 'location_' . $post_id, $tax ) ) {
			wp_insert_term( $post->post_title, $tax, [ 'slug' => 'location_' . $post_id ] );
		} else {
			wp_update_term( $term->term_id, $tax, [ 'name' => $post->post_title, 'slug' => 'location_' . $post_id ] );
		}
		
		return parent::save_post( $post_id );
	}

}
