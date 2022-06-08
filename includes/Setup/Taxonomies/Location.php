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
	 * Store the found location
	 * 
	 * @var bool 
	 */
	protected static $_rewrite_location = false;

	/**
	 * Store the request URI
	 * 
	 * @var bool 
	 */
	protected static $_request_uri = false;	

	/**
	 * Child class constructor. Punts to the parent.
	 *
	 * @author costmo
	 */
	protected function __construct() {
		$this->taxonomy = "cp_location";

		$this->single_label = apply_filters( "{$this->taxonomy}_single_label", 'Location' );
		$this->plural_label = apply_filters( "{$this->taxonomy}_plural_label", 'Locations' );
		
		$this->field_type = 'multicheck';

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
		return apply_filters( 'cp_location_taxonomy_types', [ 'page', 'post' ] );
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
	 * Get the id from the location slug
	 * 
	 * @param $term
	 *
	 * @return array|string|string[]
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function get_id_from_term( $term ) {
		return str_replace( 'location_', '', $term );
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

	/**
	 * return terms for metabox. Overwriting so we can get slugs instead of Names.
	 *
	 * @param $data
	 * @param $object_id
	 * @param $data_args
	 * @param $field
	 *
	 * @return array
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function meta_get_override( $data, $object_id, $data_args, $field ) {

		$type = get_post_type( $object_id );

		// break early if this is not our post type
		if ( ! in_array( $type, $this->get_object_types() ) ) {
			return $data;
		}

		if ( $data_args['field_id'] != $this->taxonomy ) {
			return $data;
		}

		$terms = wp_get_post_terms( $object_id, $this->taxonomy, [ 'fields' => 'slugs' ] );

		// @todo handle this error better
		if ( is_wp_error( $terms ) ) {
			return $data;
		}

		return $terms;
	}

	public function add_actions() {
		add_action( 'do_parse_request', [ $this, 'parse_location_request' ] );
		add_action( 'posts_where', [ $this, 'single_page_with_location' ], 10, 2 );
		add_filter( 'wp_unique_post_slug', [ $this, 'unique_slug' ], 10, 6 );
		add_filter( 'page_link', [ $this, 'location_permalink' ], 10, 2 );
		add_filter( 'post_link', [ $this, 'location_permalink' ], 10, 2 );
		add_filter( 'post_type_link', [ $this, 'location_permalink' ], 10, 2 );
		
		parent::add_actions();
	}

	public function parse_location_request() {
		self::$_request_uri = $_SERVER['REQUEST_URI'];
		
		list( $req_uri ) = explode( '?', $_SERVER['REQUEST_URI'] );
		
		$pathinfo         = isset( $_SERVER['PATH_INFO'] ) ? $_SERVER['PATH_INFO'] : '';
		list( $pathinfo ) = explode( '?', $pathinfo );
		$pathinfo         = str_replace( '%', '%25', $pathinfo );
		
		$req_uri = str_replace( $pathinfo, '', $req_uri );
		$req_uri = trim( $req_uri, '/' );
		$match = "locations/([^/]+)/(.*)$";
		
		if ( preg_match( "#^$match#", $req_uri, $matches ) ||
		     preg_match( "#^$match#", urldecode( $req_uri ), $matches ) ) {
			
			if ( $location = get_page_by_path( $matches[1], OBJECT, cp_locations()->setup->post_types->locations->post_type ) ) {
				self::$_request_uri = $_SERVER['REQUEST_URI'];
				
				self::$_rewrite_location = [
					'ID' => $location->ID,
					'term' => 'location_' . $location->ID,
					'path' => '/locations/' . $matches[1],
				];
				
				$_SERVER['REQUEST_URI'] = $matches[2];
			}
			
		}

		add_action( 'parse_request', [ $this, 'add_location_to_main_query' ] );
		add_action( 'pre_get_posts', [ $this, 'maybe_add_location_to_query' ] );
		add_filter( 'home_url', [ $this, 'location_home' ], 10, 2 );
		return true;
	}

	/**
	 * return rewrite location
	 * 
	 * @return bool
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function get_rewrite_location() {
		return self::$_rewrite_location;
	}
	
	public function add_location_to_main_query( $query ) {
		if ( ! self::$_rewrite_location ) {
			return;
		}
		
		// reset REQUEST_URI
		$_SERVER['REQUEST_URI'] = self::$_request_uri;

		$query->query_vars[ $this->taxonomy . '_id' ] = self::$_rewrite_location['ID'];
		if ( ! isset( $query->query_vars[ 'post_type' ] ) || in_array( $query->query_vars[ 'post_type' ], $this->get_object_types() ) ) {
			$query->query_vars[ $this->taxonomy ] = self::$_rewrite_location['term'];
		}
		
	}

	/**
	 * @param $query \WP_Query
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function maybe_add_location_to_query( $query ) {
		if ( ! self::$_rewrite_location ) {
			return;
		}

		if ( apply_filters( 'cploc_add_location_to_query', ( isset( $query->query_vars['post_type'] ) && in_array( $query->query_vars['post_type'], $this->get_object_types() ) ), $query ) ) {
			$query->set( $this->taxonomy, self::$_rewrite_location['term'] );
		}
	}
	
	public function location_home( $url, $path ) {
		if ( empty( $path ) || '/' === $path ) {
			return $url;
		}
		
		// location has already been added to this url
		if ( strpos( $url, self::$_rewrite_location['path'] ) ) {
			return $url;
		}
		
		$url = str_replace( $path, self::$_rewrite_location['path'] . '/' . $path, $url );
		$url = str_replace( '//', '/', $url );
		$url = str_replace( ':/', '://', $url );
		
		return $url;
	}
	
	public function location_permalink( $link, $post ) {
		$post = get_post( $post );
		
		if ( ! in_array( get_post_type( $post ), $this->get_object_types() ) ) {
			return $link;
		}
		
		// if we are looking at a location page and the url already has the location path, return early
		if ( isset( self::$_rewrite_location['path'] ) && strstr( $link, self::$_rewrite_location['path'] ) ) {
			return $link;
		}
		
		$locations = get_the_terms( $post, $this->taxonomy );
		
		if ( is_wp_error( $locations ) || ! $locations ) {
			return $link;
		}
		
		foreach ( $locations as $location ) {
			if ( $location->slug === self::$_rewrite_location['term'] ) {
				break;
			}
		}
		
		$id = self::get_id_from_term( $location->slug );
		
		if ( ! $loc = get_post( $id ) ) {
			return $link;
		}
		
		$location_url = get_permalink( $loc->ID );
		
		// check to see if the link already has the url associated
		if ( strstr( $link, $location_url ) ) {
			return $link;
		}
		
		return str_replace( home_url( '/' ), get_permalink( $loc->ID ), $link );
	}

	/**
	 * Allow non-unique post names if the post type uses our location taxonomy, we handle the unique permalink
	 * 
	 * @param $slug
	 * @param $post_ID
	 * @param $post_status
	 * @param $post_type
	 * @param $post_parent
	 * @param $original_slug
	 *
	 * @return mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function unique_slug( $slug, $post_ID, $post_status, $post_type, $post_parent, $original_slug ) {
		global $wpdb;
		
		if ( $slug === $original_slug ) {
			return $slug;
		}
		
		if ( ! in_array( $post_type, $this->get_object_types() ) ) {
			return $slug;
		}

		$permalink = str_replace( $slug, $original_slug, get_permalink( $post_ID ) );
		$check_sql = "SELECT * FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND ID != %d LIMIT 999";
		$posts     = $wpdb->get_results( $wpdb->prepare( $check_sql, $original_slug, $post_type, $post_ID ) );
		
		foreach( $posts as $post ) {
			if ( get_the_permalink( $post->ID ) == $permalink ) {
				return $slug;
			}
		}

		return $original_slug;
	}

	/**
	 * Since we are allowing for the same slug, this makes sure the right item is queried
	 * 
	 * @param $where
	 * @param $query
	 *
	 * @return mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function single_page_with_location( $where, $query ) {
		if ( empty( $query->query['pagename'] ) || empty( $query->queried_object_id ) ) {
			return $where;
		}
		
		// if the queried post has the correct taxonomy, return early
		if ( $has_tax = isset( $query->query[ $this->taxonomy ] ) ) {
			if ( has_term( $query->query[ $this->taxonomy ], $this->taxonomy, $query->queried_object_id ) ) {
				return $where;
			}
		}
		
		$query_vars = $query->query;
		unset( $query_vars['pagename'] );
		
		$query_vars['post_name__in'] = [ $query->query['pagename'] ];
		$query_vars['post_type'] = get_post_type( $query->queried_object_id );

		$posts = get_posts( $query_vars );
		$id    = false;
		if ( $has_tax && ! empty( $posts ) ) {
			$id = $posts[0]->ID;
		} else if ( ! empty( $posts ) ) {
			foreach( $posts as $post ) {
				if ( ! has_term( '', $this->taxonomy, $post ) ) {
					$id = $post->ID;
					break;
				}
			}
		}
		
		// don't allow location pages to be accessed without the location permalink
		if ( ! $has_tax && ! $id ) {
			$id = -1;
		}
		
		if ( $id ) {
			$where = str_replace( $query->queried_object_id, $id, $where );
			$query->queried_object_id = $id;
			$query->queried_object = $posts[0];
		}
		
		
		return $where;
	}
	
}
