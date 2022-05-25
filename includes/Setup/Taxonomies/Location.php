<?php
namespace CP_Locations\Setup\Taxonomies;

use CP_Library\Templates;
use ChurchPlugins\Setup\Taxonomies\Taxonomy;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Setup for custom taxonomy: Location
 *
 * @author tanner moushey
 * @since 1.0
 */
class Location extends Taxonomy  {

	/**
	 * Child class constructor. Punts to the parent.
	 *
	 * @author costmo
	 */
	protected function __construct() {
		$this->taxonomy = "cp_location";

		$this->single_label = apply_filters( "{$this->taxonomy}_single_label", 'Location' );
		$this->plural_label = apply_filters( "{$this->taxonomy}_plural_label", 'Locations' );

		parent::__construct();
	}

	/**
	 * Return the object types for this taxonomy
	 *
	 * @return array
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_object_types() {
		return apply_filters( 'cp_location_taxonomy_types', [] );
	}

	/**
	 * A key value array of term data ID : "Name"
	 * @return array
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_terms() {
		$data = $this->get_term_data();
		$terms = [];
		
		foreach( $data as $location ) {
			$terms[ 'location_' . $location->origin_id ] = $location->title;
		}

		return $terms;
	}

	/**
	 * Get term data from json file
	 *
	 * @return array
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_term_data() {
		$locations = \CP_Locations\Models\Location::get_all_locations();
		
		if ( empty( $locations ) ) {
			return [];
		}
		
		return apply_filters( "{$this->taxonomy}_get_term_data", $locations );
	}

}
