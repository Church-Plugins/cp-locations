<?php
/**
 * Define the Request class
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace CP_Locations\GeoLocation\MapBox;

use ChurchPlugins\RequestAPI\RequestJSON;

defined( 'ABSPATH' ) or exit;

/**
 * The CP Locations request class.
 *
 * @since 1.0.0
 */
class Request extends RequestJSON {

	protected $api_key = '';
	
	/**
	 * Construct the request object.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->method = 'GET';
		$this->api_key = apply_filters( 'cp_loc_mapbox_api_key', '' );
	}

	/**
	 * Get address geo data
	 *
	 * @since 1.0.0
	 * @param string $address
	 */
	public function setup_address_geo( $address ) {
		$this->path   = 'mapbox.places/' . urlencode( $address ) . '.json';
		$this->params = [
			'types'        => 'address',
			'access_token' => $this->api_key,
			'limit'        => 1,
		];
	}

	/**
	 * Get postcode / zip code geo data
	 *
	 * @since 1.0.0
	 * @param string $address
	 */
	public function setup_postcode_geo( $postcode ) {
		$this->path   = 'mapbox.places/' . urlencode( $postcode ) . '.json';
		$this->params = [
			'country'      => 'us',
			'types'        => 'postcode',
			'access_token' => $this->api_key,
			'limit'        => 1,
		];
	}
	
}
