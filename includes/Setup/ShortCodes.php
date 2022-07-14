<?php

namespace CP_Locations\Setup;

/**
 * Setup plugin ShortCodesialization
 */
class ShortCodes {

	/**
	 * @var ShortCodes
	 */
	protected static $_instance;

	/**
	 * Only make one instance of ShortCodes
	 *
	 * @return ShortCodes
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof ShortCodes ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Class constructor
	 *
	 */
	protected function __construct() {
		add_shortcode( 'cp-location-data', [ $this, 'location_data_cb' ] );
	}

	protected function actions() {}

	/** Actions ***************************************************/
	
	public function location_data_cb( $atts ) {
		$atts = shortcode_atts( [
			'field' => '',
			'location' => false,
		], $atts, 'cp-location-data' );
		
		$location_id = get_query_var( 'cp_location_id' );
		
		if ( ! empty( $atts['location'] ) ) {
			$location_id = $atts['location'];
		}
		
		if ( empty( $location_id ) ) {
			return;
		}
		
		switch_to_blog( get_main_site_id() );
		
		switch ( $atts['field'] ) {
			case 'title':
				$data = get_the_title( $location_id );
				break;
			case 'service_times':
				$data = self::format_times( get_post_meta( $location_id, 'service_times', true ) );
				break;
			default:
				$data = get_post_meta( $location_id, $atts['field'], true );
				break;
		}
		
		restore_current_blog();
		
		if ( ! $data = apply_filters( 'cp-location-data', $data, $location_id ) ) {
			return;
		}
		
		return $data;
	}

	public static function format_times( $times ) {
		if ( empty( $times ) || ! is_array( $times ) ) {
			return $times;
		}
		
		$days = [];
		
		foreach( $times as $time ) {
			if ( ! empty( $time['is_special'] ) ) {
				continue;
			}
			
			$day = ucwords( $time['day'] );
			if ( empty( $days[ $day ] ) ) {
				$days[ $day ] = [];
			}
			
			$t = new \DateTime( '2000-01-01 ' . $time['time'] );
			$days[ $day ][] = empty( $time['time_desc'] ) ? $t->format( 'g:ia' ) : $time['time_desc'];
		}
		
		$formatted_times = [];
		foreach( $days as $day => $times ) {
			if ( count( $times ) > 1 ) {
				$day .= 's'; // make day plural
				$times[ count( $times ) - 1 ] = 'and ' . $times[ count( $times ) - 1 ]; // add conjunction to last time if we have multiple
			} 
			
			$separator = ' ';
			if ( count( $times ) > 2 ) {
				$separator = ', ';
			}
			
			$formatted_times[] = sprintf( '%s at %s', $day, implode( $separator, $times ) );
		}
		
		return apply_filters( 'cploc_format_times', implode( '<br />', $formatted_times ) );
	}
}
