<?php
namespace CP_Locations\GeoLocation;

// Exit if accessed directly
use ChurchPlugins\RequestAPI\Base;
use CP_Locations\Exception;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Setup for custom post types
 *
 * @author costmo
 */
abstract class GeoAPI extends Base {
	
	/**
	 * The key for the database cache
	 * 
	 * @var string 
	 */
	public static $_cache_key = 'cploc_geo_data';
	
	/**
	 * Integration API Key
	 * @var string
	 */
	public $api_key = '';

	/**
	 * Get things started
	 *
	 * @since   1.0
	 */
	public function __construct() {
		$this->set_request_content_type_header( 'application/json' );
		$this->set_request_accept_header( 'application/json' );
		$this->set_response_handler( 'CP_Locations\GeoLocation\Response' );
	}

	/**
	 * Return the locations already in the cache
	 * 
	 * @return array|false|mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_locations_cache() {
		if ( ! $this->cache_enabled() ) {
			return [];
		}
		
		return get_site_option( self::$_cache_key, [] );
	}

	/**
	 * Get location geo data from cache or integration
	 * 
	 * @param $location
	 * @param $type
	 *
	 * @return false|mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_location( $location, $type = 'address' ) {
		$locations = $this->get_locations_cache();
		$location_key = md5( $location );
		
		if ( ! isset( $locations[ $location_key ] ) ) {
			try {
				if ( $locations[ $location_key ] = $this->get_location_data( $location, $type ) ) {
					if ( $this->cache_enabled() ) {
						$this->cache_location( $location_key, $locations[ $location_key ] );
					}
				}
			} catch( Exception $e ) {
				error_log( $e );
				return false;
			}
		}
		
		return apply_filters( 'cploc_get_location_geo', $locations[ $location_key ] );
	}

	/**
	 * Stub function that we'll use to get the geo data for this location
	 *
	 * @param $location
	 * @param $type
	 *
	 * @return mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	abstract protected function get_location_data( $location, $type );
	
	/**
	 * Store this location data in the database
	 * 
	 * @param $key
	 * @param $data
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function cache_location( $key, $data ) {
		$locations = $this->get_locations_cache();
		$locations[ $key ] = $data;
		update_site_option( self::$_cache_key, $locations );
	}

	/**
	 * Remove the location cache
	 * 
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function cache_break() {
		delete_option( self::$_cache_key );
	}

	/**
	 * Whether or not we should enable the location cache
	 * 
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function cache_enabled() {
		return apply_filters( 'cploc_location_cache_enabled', true );
	}
	
	protected function get_plugin() {
		return cp_locations();
	}

}
