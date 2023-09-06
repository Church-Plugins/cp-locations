<?php

namespace CP_Locations\Integrations;

use CP_Locations\Integrations\TheEventsCalendar\FilterLocation;
use CP_Locations\Setup\Taxonomies\Location;
use PHP_CodeSniffer\Filters\Filter;

class TheEventsCalendar {

	/**
	 * @var TheEventsCalendar
	 */
	protected static $_instance;

	protected static $_redirected = false;
	
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
		if ( class_exists( 'Tribe__Events__Filterbar__Settings' ) ) {
			add_filter( 'option_' . \Tribe__Events__Filterbar__Settings::OPTION_ACTIVE_FILTERS, [ $this, 'active_filters' ] );
		}
		
		add_filter( 'home_url', [ $this, 'event_location_url' ], 10, 2 );
		add_filter( 'cploc_parse_location_request_uri', [ $this, 'event_api_request_location' ] );
		add_filter( 'tribe_events_views_v2_request_uri', [ $this, 'clean_views_request_uri' ] );
		add_filter( 'old_slug_redirect_url', [ $this, 'old_slug_redirect_occurence' ], 11 );
		add_filter( 'redirect_canonical', [ $this, 'canonical_occurence' ], 10, 2 );
	}

	/** Actions ***************************************************/

	/**
	 * Make sure canonical redirect for events point to the correct occurence.
	 *
	 * By default TEC will set the canonical link to the first event an in a series. When we
	 * force a location prefix on an event, the canonical redirect is triggered and we need to 
	 * make sure that we maintain the correct event date.
	 *
	 * @param $redirect_url
	 * @param $requested_url
	 *
	 * @return false|mixed|string
	 * @since  1.0.1
	 *
	 * @author Tanner Moushey
	 */
	public function canonical_occurence( $redirect_url, $requested_url ) {
		global $wp;
		
		// redirect_canonical double checks the redirected url to make sure a redirect loop
		// isn't triggered. We need to short circuit that check. 
		if ( self::$_redirected ) {
			return false;
		}
		
		// make sure we are looking at a recurring event
		if ( empty( $wp->query_vars['eventDate'] ) ) {
			return $redirect_url;
		}
		
		$slug = '/' . \Tribe__Events__Main::instance()->getRewriteSlugSingular() . '/';
		
		$redirect = explode( $slug, $redirect_url );
		$request = explode( $slug, $requested_url );
		
		if ( count( $redirect ) > 1 && $redirect[0] !== $request[0] ) {
			$redirect_url = $redirect[0] . $slug . $request[1];
			self::$_redirected = true;
		}
		
		return $redirect_url;
	}
	
	/**
	 * Old slug redirect does not work correctly with recurring events. Disable it.
	 * 
	 * @param $link
	 *
	 * @return false|mixed
	 * @since  1.0.1
	 *
	 * @author Tanner Moushey
	 */
	public function old_slug_redirect_occurence( $link ) {
		global $wp;
		
		if ( ! empty( $wp->query_vars['eventDate'] ) ) {
			return false;
		}
		
		return $link;
	}

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

	/**
	 * Don't show location filter if we are on a location page
	 * 
	 * @param $filters
	 *
	 * @return mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function active_filters( $filters ) {
		if ( ! cp_locations()->setup->taxonomies->location::get_rewrite_location() ) {
			return $filters;
		}
		
		if ( apply_filters( 'cploc_show_location_filter_on_location', false, $filters ) ) {
			return $filters;
		}

		if ( empty( $filters ) ) {
			return $filters;
		}
		
		// unset filterbar filter if it exists
		unset( $filters[ 'filterbar_cploc_location' ] );
		
		return $filters;
	}
	
	/**
	 * Rewrite Event path to use location url
	 * 
	 * @param $url
	 * @param $path
	 *
	 * @return array|mixed|string|string[]
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function event_location_url( $url, $path ) {
		if ( empty( $path ) || '/' === $path || ! function_exists( 'tribe_get_option' ) ) {
			return $url;
		}

		// make sure this is an event URL
		$events_archive_base = tribe_get_option( 'eventsSlug', 'events' );
		if ( ! strstr( $path, $events_archive_base ) ) {
			return $url;
		}

		// make sure we are on a location page
		$rewrite_location = cp_locations()->setup->taxonomies->location::get_rewrite_location();
		if ( empty( $rewrite_location['path'] ) ) {
			return $url;
		}
		
		// location has already been added to this url
		if ( strpos( $url, $rewrite_location['path'] ) ) {
			return $url;
		}

		$locations_regex = cp_locations()->setup->taxonomies->location->locations_regex();

		$slug = trim( cp_locations()->setup->post_types->locations->get_slug(), '/' );

		// don't rewrite for urls with location already set
		if ( preg_match( "/$slug\/($locations_regex)/", $url ) ) {
			return $url;
		}

		$url = str_replace( $path, $rewrite_location['path'] . '/' . $path, $url );
		$url = str_replace( '//', '/', $url );
		$url = str_replace( ':/', '://', $url );
		
		return $url;
	}

	/**
	 * Handle API requests from event calendar on location pages
	 * 
	 * @param $request_uri
	 *
	 * @return array|mixed|string|string[]
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function event_api_request_location( $request_uri ) {
		if ( ! strstr( $request_uri, 'wp-json/tribe' ) || empty( $_POST['url'] ) ) {
			return $request_uri;
		}
		
		$url = $_POST['url'];

		$locations_regex = cp_locations()->setup->taxonomies->location->locations_regex();
		$slug            = trim( cp_locations()->setup->post_types->locations->get_slug(), '/' );

		// don't rewrite for urls with location already set
		if ( ! preg_match( "/$slug\/($locations_regex)/", $url, $matches ) ) {
			return $url;
		}		
		
		$_POST['url'] = str_replace( $matches[0], '', $url );
		
		if ( isset( $_POST['prev_url'] ) ) {
			$_POST['prev_url'] = str_replace( $matches[0], '', $_POST['prev_url'] );
		}
		
		return str_replace( home_url( '/' ), '', $url );
	}

	/**
	 * Filter views request_uri to remove location param
	 * 
	 * @param $request_uri
	 *
	 * @return array|mixed|string|string[]
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function clean_views_request_uri( $request_uri ) {
		$locations_regex = cp_locations()->setup->taxonomies->location->locations_regex();
		$slug            = trim( cp_locations()->setup->post_types->locations->get_slug(), '/' );

		// don't rewrite for urls with location already set
		if ( ! preg_match( "/$slug\/($locations_regex)/", $request_uri, $matches ) ) {
			return $request_uri;
		}

		return str_replace( $matches[0], '', $request_uri );
	}
}
