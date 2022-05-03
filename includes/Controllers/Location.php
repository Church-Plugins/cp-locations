<?php

namespace CP_Locations\Controllers;

use CP_Locations\Models\Location as LocationModel;
use CP_Locations\Exception;
use function CP_Locations\get_template_part;

class Location {

	/**
	 * @var bool|LocationModel
	 */
	public $model;

	/**
	 * @var array|\WP_Post|null
	 */
	public $post;

	/**
	 * Location constructor.
	 *
	 * @param $id
	 * @param bool $use_origin whether or not to use the origin / post id
	 *
	 * @throws Exception
	 */
	public function __construct( $id, $use_origin = true ) {
		$this->model = $use_origin ? LocationModel::get_instance_from_origin( $id ) : LocationModel::get_instance( $id );
		$this->post  = get_post( $this->model->origin_id );
	}

	protected function filter( $value, $function ) {
		return apply_filters( 'cploc_location_' . $function, $value, $this );
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
	
	public function get_geo() {
		
		if ( ! $this->address ) {
			return [];
		}

		$cach_key    = 'cploc_geo_data';
		$address_key = md5( $this->address );
		$geo_data    = get_option( $cach_key, [] );
		
		if ( empty( $geo_data[ $address_key ] ) ) {
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
			
			$geo_data[ $address_key ] = $data;
			update_option( $cach_key, $geo_data, false );
		}
		
		return $geo_data[ $address_key ];
	}
	
	public function __get( $name ) {
		$value = '';
		
		$value = get_post_meta( $this->post->ID, $name, true );
		return $this->filter( $value, "get_$name" );
	}
	
	public function get_thumbnail() {
		return  [
			'thumb' => get_the_post_thumbnail_url( $this->post->ID ),
			'medium' => get_the_post_thumbnail_url( $this->post->ID, 'medium' ),
			'large' => get_the_post_thumbnail_url( $this->post->ID, 'large' ),
			'full' => get_the_post_thumbnail_url( $this->post->ID, 'full' ),
		];
	}

	public function get_api_data( $templates = true ) {
		$data = [
			'id'        => $this->model->id,
			'originID'  => $this->post->ID,
			'permalink' => $this->get_permalink(),
			'slug'      => $this->post->post_name,
			'thumb'     => $this->get_thumbnail(),
			'title'     => htmlspecialchars_decode( $this->get_title(), ENT_QUOTES | ENT_HTML401 ),
			'desc'      => $this->get_content(),
			'address'   => wp_kses_post( $this->address ),
			'phone'     => $this->phone,
			'email'     => $this->email,
			'times'     => $this->service_times,
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
