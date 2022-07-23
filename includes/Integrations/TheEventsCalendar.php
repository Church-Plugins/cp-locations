<?php

namespace CP_Locations\Integrations;

use CP_Locations\Integrations\TheEventsCalendar\FilterLocation;
use PHP_CodeSniffer\Filters\Filter;

class TheEventsCalendar {

	/**
	 * @var TheEventsCalendar
	 */
	protected static $_instance;

	/**
	 * Only make one instance of Locations
	 *
	 * @return TheEventsCalendar
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof TheEventsCalendar ) {
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
		
		// filterbar
		add_filter( 'tribe_context_locations', [ $this, 'filter_context_locations' ] );
		add_filter( 'tribe_events_filter_bar_context_to_filter_map', [ $this, 'filter_map' ] );
		add_action( 'tribe_events_filters_create_filters', [ $this, 'create_filter' ] );

	}

	/** Actions ***************************************************/

	/**
	 * Filters the Context locations to let the Context know how to fetch the value of the filter from a request.
	 *
	 * Here we add the `time_of_day_custom` as a read-only Context location: we'll not need to write it.
	 *
	 * @param array<string,array> $locations A map of the locations the Context supports and is able to read from and write
	 *
	 * @return array<string,array> The filtered map of Context locations, with the one required from the filter added to it.
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function filter_context_locations( array $locations ) {
		// Read the filter selected values, if any, from the URL request vars.
		$locations['filterbar_cploc_location'] = [
			'read' => [
				\Tribe__Context::REQUEST_VAR => [ 'tribe_filterbar_cploc_location' ]
			]
		];

		// Return the modified $locations.
		return $locations;		
	}
	
	/**
	 * Filters the map of filters available on the front-end to include the custom one.
	 *
	 * @param array<string,string> $map A map relating the filter slugs to their respective classes.
	 *
	 * @return array<string,string> The filtered slug to filter class map.
	 */	
	public function filter_map( array $map ) {
		if ( ! class_exists( 'Tribe__Events__Filterbar__Filter' ) ) {
			// This would not make much sense, but let's be cautious.
			return $map;
		}

		// Include the filter class.
		include_once __DIR__ . '/TheEventsCalendar/FilterLocation.php';

		// Add the filter class to our filters map.
		$map['filterbar_cploc_location'] = FilterLocation::class;

		// Return the modified $map.
		return $map;
	}

	/**
	 * Includes the custom filter class and creates an instance of it.
	 */
	function create_filter() {
		if ( ! class_exists( 'Tribe__Events__Filterbar__Filter' ) ) {
			return;
		}

		new TheEventsCalendar\FilterLocation(
			__( 'Locations', 'cp-locations' ),
			'filterbar_cploc_location'
		);
	}
}
