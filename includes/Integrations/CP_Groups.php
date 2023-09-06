<?php

namespace CP_Locations\Integrations;

use CP_Locations\Setup\Taxonomies\Location;

class CP_Groups {

	/**
	 * @var CP_Groups
	 */
	protected static $_instance;

	/**
	 * Only make one instance of Locations
	 *
	 * @return CP_Groups
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof CP_Groups ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Class constructor
	 *
	 */
	protected function __construct() {
		$this->includes();
		$this->actions();
	}

	/**
	 * @return void
	 */
	protected function includes() {}

	protected function actions() {
		if ( cp_locations()->enabled() ) {
			add_filter( 'cp_groups_filter_facets', [ $this, 'location_facet' ] );
			add_filter( 'cp_groups_filter_facet_terms', [ $this, 'location_facet_terms' ], 10, 3 );
		}
	}

	/** Actions ***************************************************/

	/**
	 * Add location facet to groups filter
	 * 
	 * @param $facets
	 *
	 * @return mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function location_facet( $facets ) {
		if ( get_query_var( 'cp_location_id' ) ) {
			return $facets;
		}

		array_unshift( $facets, cp_locations()->setup->taxonomies->location );
			
		return $facets;
	}

	/**
	 * Remove global term from facets
	 * 
	 * @param $terms
	 * @param $object
	 * @param $tax
	 *
	 * @return mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function location_facet_terms( $terms, $object, $tax ) {
		if ( $object !== cp_locations()->setup->taxonomies->location->taxonomy ) {
			return $terms;
		}
		
		if ( is_wp_error( $terms ) ) {
			return $terms;
		}
		
		foreach( $terms as $key => $term ) {
			if ( 'global' === $term->slug ) {
				unset( $terms[ $key ] );
			}
		}
		
		return $terms;
	}
}
