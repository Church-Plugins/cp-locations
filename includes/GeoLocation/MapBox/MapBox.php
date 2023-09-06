<?php

namespace CP_Locations\GeoLocation\MapBox;

use ChurchPlugins\RequestAPI\RequestJSON;
use CP_Locations\Exception;

class MapBox extends \CP_Locations\GeoLocation\GeoAPI {
	

	/**
	 * Add Hooks and Actions
	 */
	public function __construct() {
		$this->api_key = '';
		$this->request_uri = 'https://api.mapbox.com/geocoding/v5/';
		parent::__construct();

		$this->set_response_handler( 'CP_Locations\GeoLocation\MapBox\Response' );
	}

	protected function get_location_data( $location, $type ) {
		$request = $this->get_new_request();
		$data = false;
		
		switch( $type ) {
			case 'postcode' : 
				$request->setup_postcode_geo( $location );
				break;
			default :
				$request->setup_address_geo( $location );
		}
		
		try {
			/** @var $response Response */
			$response = $this->perform_request( $request, false );
			$data = $response->get_geo_data();
		} catch( \Exception $e ) {
			error_log( $e );
		}
		
		return $data;
	}
	
	/**
	 * @return Request
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	protected function get_new_request( $args = [] ) {
		return new Request();
	}

}