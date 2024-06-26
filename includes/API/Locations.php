<?php

namespace CP_Locations\API;

use CP_Locations\Controllers\Location;
use CP_Locations\Exception;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * REST API Controller for Locations objects
 *
 * @since 1.0.0
 *
 * @see WP_REST_Controller
 */
class Locations extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		$this->namespace 	= cp_locations()->get_api_namespace();
		$this->rest_base 	= 'locations';
		$this->post_type	=  cp_locations()->setup->post_types->locations->post_type;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_locations' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
			),
			// 'schema' => array( $this, 'get_public_schema' ),
		) );

		register_rest_route( $this->namespace, $this->rest_base . '/(?P<location_id>[\d]+)', array(
			'args' => array(
				'location_id' => array(
					'description' => __( 'The ID of the location.', 'cp-locations' ),
					'type'        => 'integer',
					'required'    => true,
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_location' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
			),

			// 'schema' => array( $this, 'get_public_schema' ),
		) );

		register_rest_route( $this->namespace, $this->rest_base . '/postcode/(?P<postcode>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_postcode' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
			),
			// 'schema' => array( $this, 'get_public_schema' ),
		) );

	}

	/**
	 * Checks if a given request has access to read and manage the user's passwords.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function get_permissions_check( $request ) {
		return true;
	}

	/**
	 * Checks if a given request has access to read and manage the user's passwords.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function create_permissions_check( $request ) {
		return is_user_logged_in();
	}

	/**
	 * Retrieves the passwords.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array|WP_Error Array on success, or WP_Error object on failure.
	 */
	public function get_locations( $request ) {

		$posts = \CP_Locations\Models\Location::get_all_locations( true );
		
		$return_value = [
			'count' => count( $posts ),
			'locations' => [],
		];

		foreach( $posts as $post ) {
			try {
				$location = new Location( $post->ID );
				$return_value['locations'][] = $location->get_api_data();
			} catch( Exception $e ) {
				$return_value['error'] = $e->getMessage();
				error_log( $e->getMessage() );
			}
		}

		return $return_value;

	}

	/**
	 * Retrieves the passwords.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array|WP_Error Array on success, or WP_Error object on failure.
	 */
	public function get_location( $request ) {
		$location_id = $request->get_param( 'location_id' );

		try {
			$location                    = new Location( $location_id );
			return $location->get_api_data();
		} catch ( Exception $e ) {
			error_log( $e->getMessage() );
			return new WP_Error( $e->getMessage() );
		}
	}

	/**
	 * Get the geo for the provided postcode
	 * 
	 * @param $request
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_postcode( $request ) {
		$postcode = $request->get_param( 'postcode' );
		return cp_locations()->geoAPI->get_location( $postcode, 'postcode' );
	}
	
	/**
	 * Expose protected namesapce property
	 *
	 * @return string
	 * @author costmo
	 */
	public function get_namespace() {
		return $this->namespace;
	}

	/**
	 * Expose protected rest_base property
	 *
	 * @return string
	 * @author costmo
	 */
	public function get_rest_base() {
		return $this->rest_base;
	}
}
