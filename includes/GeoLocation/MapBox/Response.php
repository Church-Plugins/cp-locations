<?php
/**
 * Define the Response class
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace CP_Locations\GeoLocation\MapBox;

use ChurchPlugins\RequestAPI\ResponseJSON;

defined( 'ABSPATH' ) or exit;

/**
 * The AvaTax API request class.
 *
 * @since 1.0.0
 */
class Response extends ResponseJSON {
	
	public function get_geo_data() {
		if ( empty( $this->response_data ) ) {
			return [];
		}

		$feature = $this->response_data->features[0];
		
		$data    = [
			'id'     => $feature->id,
			'name'   => $feature->place_name,
			'center' => array_reverse( $feature->center ),
			'attr'   => [],
		];

		$types = [ 'postcode', 'place', 'region', 'country' ];
		foreach ( $feature->context as $context ) {
			foreach ( $types as $type ) {
				if ( false !== strpos( $context->id, $type ) ) {
					$data['attr'][ $type ] = $context->text;
					break;
				}
			}
		}
		
		return $data;
	}
}
