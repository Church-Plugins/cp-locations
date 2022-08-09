<?php

namespace CP_Locations\Controllers;

use ChurchPlugins\Controllers\Controller;
use CP_Locations\Models\Location as LocationModel;
use CP_Locations\Exception;
use function CP_Locations\get_template_part;

class Location extends Controller {

	/**
	 * constructor.
	 *
	 * @param $id
	 * @param bool $use_origin whether or not to use the origin id
	 *
	 * @throws Exception
	 */
	public function __construct( $id, $use_origin = true ) {
		// handle our global placeholder, it doesn't have an associated post
		if ( $id == 'global' ) {
			$this->model = new LocationModel();
			$this->post  = new \WP_Post( new \stdClass() );
		} else {
			parent::__construct( $id, $use_origin );
		}
	}
	
	public function get_content( $raw = false ) {
		$content = get_the_content( null, false, $this->post );
		if ( ! $raw ) {
			$content = apply_filters( 'the_content', $content );
		}

		return $this->filter( $content, __FUNCTION__ );
	}

	public function get_title() {
		return $this->filter( get_the_title( $this->post->ID ), __FUNCTION__ );
	}

	public function get_permalink() {
		return $this->filter( get_permalink( $this->post->ID ), __FUNCTION__ );
	}
	
	public function get_geo( $force = false ) {
		
		if ( ! $this->address ) {
			return [];
		}

		$geo_data    = $this->geo;
		
		if ( $force || empty( $geo_data ) ) {
			$request_url = 'https://api.mapbox.com/geocoding/v5/mapbox.places/' . urlencode( $this->address ) . '.json';
			$request_url = add_query_arg( [
				'types'        => 'address',
				'access_token' => cp_locations()->get_api_key(),
				'limit'        => 1,
			], $request_url );
			$response    = wp_remote_get( $request_url );

			$response = json_decode( wp_remote_retrieve_body( $response ) );

			if ( empty( $response->features ) ) {
				return [];
			}

			$feature = $response->features[0];
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
			
			$geo_data = $data;
			update_post_meta( $this->post->ID, 'geo', $geo_data );
		}
		
		// allow overwriting the coordinates
		if ( $coordinates = $this->geo_coordinates ) {
			$geo_data['center'] = array_map( 'trim', explode( ',', $coordinates ) );
		}
		
		return $geo_data;
	}
	
	public function __get( $name ) {
		$value = '';
		
		$value = get_post_meta( $this->post->ID, $name, true );
		return $this->filter( $value, "get_$name" );
	}
	
	public function get_thumbnail() {
		return  [
			'thumb' => get_the_post_thumbnail_url( $this->post->ID ),
			'thumbnail' => get_the_post_thumbnail_url( $this->post->ID, 'thumbnail' ),
			'medium' => get_the_post_thumbnail_url( $this->post->ID, 'medium' ),
			'large' => get_the_post_thumbnail_url( $this->post->ID, 'large' ),
			'full' => get_the_post_thumbnail_url( $this->post->ID, 'full' ),
		];
	}

	public function get_formatted_times() {
		$times = $this->service_times;
		
		if ( empty( $times ) || ! is_array( $times ) ) {
			return $times;
		}

		$days = [];

		foreach ( $times as $time ) {
			if ( ! empty( $time['is_special'] ) ) {
				continue;
			}

			$day = ucwords( $time['day'] );
			if ( empty( $days[ $day ] ) ) {
				$days[ $day ] = [];
			}

			$t              = new \DateTime( '2000-01-01 ' . $time['time'] );
			$days[ $day ][] = empty( $time['time_desc'] ) ? $t->format( 'g:ia' ) : $time['time_desc'];
		}

		$formatted_times = [];
		foreach ( $days as $day => $times ) {
			if ( count( $times ) > 1 ) {
				$day                          .= 's'; // make day plural
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

	public function get_api_data( $templates = true ) {
		$data = [
			'id'        => $this->model->id,
			'originID'  => $this->post->ID,
			'permalink' => $this->get_permalink(),
			'slug'      => $this->post->post_name,
			'thumb'     => $this->get_thumbnail(),
			'title'     => htmlspecialchars_decode( $this->get_title(), ENT_QUOTES | ENT_HTML401 ),
			'pastor'    => $this->pastor,
			'desc'      => $this->get_content(),
			'address'   => wp_kses_post( $this->address ),
			'phone'     => $this->phone,
			'email'     => $this->email,
			'times'     => $this->get_formatted_times(),
			'geodata'   => $this->get_geo(),
			'templates' => [
				'card' => '',
				'popup' => '',
				'list-item' => '',
			]
		];
		
		if ( $templates ) {
			$data['templates'] = [
				'card'      => get_template_part( 'card', [ 'location' => $this ], true ),
				'popup'     => get_template_part( 'map/popup', [ 'location' => $this ], true ),
				'list-item' => get_template_part( 'map/list-item', [ 'location' => $this ], true ),
			];
		}

		return $this->filter( $data, __FUNCTION__ );
	}
}
