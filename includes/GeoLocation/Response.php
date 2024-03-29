<?php
/**
 * Define the Response class
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace CP_Locations\GeoLocation;

use ChurchPlugins\RequestAPI\ResponseJSON;

defined( 'ABSPATH' ) or exit;

/**
 * The AvaTax API request class.
 *
 * @since 1.0.0
 */
class Response extends ResponseJSON {
	
	public function get_geo_data() {
		return [];
	}
}
