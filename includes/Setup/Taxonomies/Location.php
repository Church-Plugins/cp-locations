<?php
namespace CP_Locations\Setup\Taxonomies;

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
	 * @var array 
	 */
	protected static $_locations_regex = false;

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
		
		// run these actions every time, even if we aren't fully enabled
		add_filter( 'body_class', [ $this, 'body_class' ] );
		add_action( 'template_redirect', [ $this, 'add_location_query_var' ] );
	}

	/**
	 * Get the slug for this taxonomy
	 * 
	 * @return false|mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_slug() {
		if ( ! $tax = get_taxonomy( $this->taxonomy ) ) {
			return false;
		}
		
		return $tax->rewrite['slug'];
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

	/**
	 * Build the location regex string
	 * 
	 * @return array|bool|string
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function locations_regex() {
		if ( false === self::$_locations_regex ) {
			$locations = \CP_Locations\Models\Location::get_all_locations( true );
			self::$_locations_regex = implode( '|', wp_list_pluck( $locations, 'post_name' ) ); 
		}
		
		return self::$_locations_regex;
	}

	/**
	 * Parse the request before WordPress to see if this is a location page
	 * 
	 * @return bool
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function parse_location_request() {
		
		$locations_regex = $this->locations_regex();
		
		if ( empty( $locations_regex ) ) {
			return true;
		}
		
		self::$_request_uri = $_SERVER['REQUEST_URI'];
		
		list( $req_uri, $query_params ) = explode( '?', $_SERVER['REQUEST_URI'] );
		
		$pathinfo         = isset( $_SERVER['PATH_INFO'] ) ? $_SERVER['PATH_INFO'] : '';
		list( $pathinfo ) = explode( '?', $pathinfo );
		$pathinfo         = str_replace( '%', '%25', $pathinfo );
		
		$req_uri = str_replace( $pathinfo, '', $req_uri );
		$req_uri = trailingslashit( trim( $req_uri, '/' ) );
		$slug    = trim( cp_locations()->setup->post_types->locations->get_slug(), '/' );
		
		if ( $slug ) {
			$slug = trailingslashit( $slug );
		}
		
		$match = $slug . "($locations_regex)\/(.*)$";
		
		if ( preg_match( "#^$match#", $req_uri, $matches ) ||
		     preg_match( "#^$match#", urldecode( $req_uri ), $matches ) ) {
			
			if ( $location = get_page_by_path( $matches[1], OBJECT, cp_locations()->setup->post_types->locations->post_type ) ) {
				
				self::$_rewrite_location = [
					'ID' => $location->ID,
					'term' => 'location_' . $location->ID,
					'path' => '/' . $slug . $matches[1],
				];
				
				// BB passes a page_id and expects the match to be empty
				if ( ! empty( $matches[2] ) || isset( $_GET['fl_builder'], $_GET['page_id'] ) ) { // || isset( $_GET['fl_builder'], $_GET['fl_builder_load_settings_config'] ) ) {
					$_SERVER['REQUEST_URI'] = $matches[2];
					
					if ( $query_params ) {
						$_SERVER['REQUEST_URI'] .= '?' . $query_params;
					}
				}
			}
			
			// add filters to customize for this location
			add_action( 'parse_request', [ $this, 'add_location_to_main_query' ] );
			add_action( 'pre_get_posts', [ $this, 'maybe_add_location_to_query' ] );
//			add_filter( 'body_class', [ $this, 'start_home_url' ] );
//			add_filter( 'wp_footer', [ $this, 'stop_home_url' ] );
			add_filter( 'wp_footer', [ $this, 'update_relative_urls' ] );
		}
		
		return true;
	}

	/**
	 * start home url filter
	 * 
	 * @param $classes
	 *
	 * @return mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function start_home_url( $classes ) {
		add_filter( 'home_url', [ $this, 'location_home' ], 10, 2 );
		return $classes;
	}

	/**
	 * remove home url filter
	 * 
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function stop_home_url() {
		remove_filter( 'home_url', [ $this, 'location_home' ], 10, 2 );			
	}

	/**
	 * Make relative URLs relative to the location
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function update_relative_urls() {
		if ( ! self::$_rewrite_location || ! is_main_site() ) {
			return;
		}
		?>
		<script>
			var cplocAnchors = document.getElementsByTagName('a');
			var cplocAnchorPath = "<?php echo self::$_rewrite_location['path']; ?>";
			
			for (var i = 0; i < cplocAnchors.length; i++) {
				if (cplocAnchors[i].getAttribute('href').startsWith('/') 
					&& !cplocAnchors[i].getAttribute('href').startsWith('//')
					&& !cplocAnchors[i].getAttribute('href').startsWith(cplocAnchorPath)
				) {
					cplocAnchors[i].setAttribute( 'href', cplocAnchorPath + cplocAnchors[i].getAttribute('href') );
				}
			}
		</script>
		<?php
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
		return apply_filters( 'cploc_get_rewrite_location', self::$_rewrite_location );
	}

	/**
	 * Add query params for locations 
	 * 
	 * @param $query
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function add_location_to_main_query( $query ) {
		if ( ! self::$_rewrite_location ) {
			return;
		}
		
		// reset REQUEST_URI
		if ( empty( $_GET['fl_builder'] ) ) {
			$_SERVER['REQUEST_URI'] = self::$_request_uri;
		}

		if ( ! isset( $query->query_vars[ 'post_type' ] ) || in_array( $query->query_vars[ 'post_type' ], $this->get_object_types() ) ) {
			$query->query_vars[ $this->taxonomy ] = self::$_rewrite_location['term'];
		}
		
	}

	/**
	 * Add query param for locations 
	 * 
	 * @param $query
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function add_location_query_var() {
		if ( ! $location = self::get_rewrite_location() ) {
			return;
		}
		
		set_query_var( 'cp_location_id', $location['ID'] );
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

	/**
	 * Overwrite the home link to include the location url
	 * 
	 * @param $url
	 * @param $path
	 *
	 * @return array|string|string[]
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function location_home( $url, $path ) {
		if ( empty( $path ) || '/' === $path ) {
			return $url;
		}
		
		// location has already been added to this url
		if ( strpos( $url, self::$_rewrite_location['path'] ) ) {
			return $url;
		}
		
		$locations_regex = $this->locations_regex();

		$slug    = trim( cp_locations()->setup->post_types->locations->get_slug(), '/' );
		
		// don't rewrite for urls with location already set
		if ( preg_match( "/$slug\/($locations_regex)/", $url ) ) {
			return $url;
		} 
		
		$url = str_replace( $path, self::$_rewrite_location['path'] . '/' . $path, $url );
		$url = str_replace( '//', '/', $url );
		$url = str_replace( ':/', '://', $url );
		
		return $url;
	}

	/**
	 * Customize location permalink to include location at the base
	 * 
	 * @param $link
	 * @param $post
	 *
	 * @return array|mixed|string|string[]
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
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

		$has_tax = isset( $query->query[ $this->taxonomy ] );
		
		// if the queried post has the correct taxonomy, return early
		if ( ! empty( $query->queried_object_id ) && $has_tax ) {
			if ( has_term( $query->query[ $this->taxonomy ], $this->taxonomy, $query->queried_object_id ) ) {
				return $where;
			}
		}
		
		$query_vars = $query->query;
		$post_type  = empty( $query->queried_object_id ) ? false : get_post_type( $query->queried_object_id );
		$id         = false;

		//@todo need to mimick get_page_by_path
		if ( isset( $query->query['pagename'] ) ) {
			$id = $this->get_page_id_by_path( $query->query['pagename'], $post_type );
		} elseif ( isset( $query->query['name'] ) ) {
			
			if ( $post_type ) {
				$query_vars['post_type'] = $post_type;
			}
			
			$query_vars['post_name__in'] = [ $query->query['name'] ];
			unset( $query_vars['name'] );

			$posts = get_posts( $query_vars );

			// if the taxonomy is set for this query, return the first found post
			// if the taxonomy is not set, return the first post without a taxonomy term
			if ( $has_tax && ! empty( $posts ) ) {
				$id = $posts[0]->ID;
			} else if ( ! empty( $posts ) ) {
				foreach ( $posts as $post ) {
					if ( ! has_term( '', $this->taxonomy, $post ) ) {
						$id = $post->ID;
						break;
					}
				}
			}
			
		} else {
			return $where;
		}
		
		// don't allow location pages to be accessed without the location permalink
		if ( empty( $id ) && ( ! $has_tax || ( $has_tax && empty( $posts ) ) ) ) {
			$id = -1;
		}
		
		if ( false === $id ) {
			return $where;
		}
		
		if ( $query->queried_object_id == $id ) {
			return $where;
		}
		
		global $wpdb;
		
		if ( ! empty( $query->queried_object_id ) ) {
			$where = str_replace( $query->queried_object_id, $id, $where );
			$query->queried_object_id = $id;
			$query->queried_object = $posts[0];
		} else {
			$where .= " AND $wpdb->posts.ID = '$id'";
		}
		
		return $where;
	}

	/**
	 * Add the location parameter to the body class if it exists
	 * 
	 * @param $classes
	 *
	 * @return mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function body_class( $classes ) {
		if ( ! $location_id = get_query_var( 'cp_location_id' ) ) {
			$classes[] = $this->taxonomy . '-none';
		} else {
			$classes[] = $this->taxonomy . '-' . $location_id;
			$classes[] = $this->taxonomy . '-found';
		}
		
		return $classes;
	}
	
	protected function get_page_by_name( $name, $post_type = false ) {
		
	} 

	/**
	 * Custom get_page_by_path to allow for location
	 * 
	 * @param $page_path
	 * @param $post_type
	 *
	 * @return array|void|\WP_Post|null
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	protected function get_page_id_by_path( $page_path, $post_type = 'page' ) {
		global $wpdb;
		
		if ( ! $post_type ) {
			$post_type = 'page';
		}

		$last_changed = wp_cache_get_last_changed( 'posts' );

		// add location_id to the cache_key
		$location = self::get_rewrite_location();
		
		$location_id = empty( $location['ID'] ) ? 0 : $location['ID'];
		$hash        = md5( $page_path . serialize( $post_type ) . $location_id );
		$cache_key   = "get_page_by_path:$hash:$last_changed";
		$cached      = wp_cache_get( $cache_key, 'posts' );
		
		if ( false !== $cached ) {
			// Special case: '0' is a bad `$page_path`.
			if ( '0' === $cached || 0 === $cached ) {
				return false;
			} else {
				return $cached;
			}
		}

		$page_path     = rawurlencode( urldecode( $page_path ) );
		$page_path     = str_replace( '%2F', '/', $page_path );
		$page_path     = str_replace( '%20', ' ', $page_path );
		$parts         = explode( '/', trim( $page_path, '/' ) );
		$parts         = array_map( 'sanitize_title_for_query', $parts );
		$escaped_parts = esc_sql( $parts );

		$in_string = "'" . implode( "','", $escaped_parts ) . "'";

		if ( is_array( $post_type ) ) {
			$post_types = $post_type;
		} else {
			$post_types = array( $post_type, 'attachment' );
		}

		$post_types          = esc_sql( $post_types );
		$post_type_in_string = "'" . implode( "','", $post_types ) . "'";
		$sql                 = "
		SELECT ID, post_name, post_parent, post_type
		FROM $wpdb->posts
		WHERE post_name IN ($in_string)
		AND post_type IN ($post_type_in_string)
	";

		$pages = $wpdb->get_results( $sql, OBJECT_K );

		$revparts = array_reverse( $parts );

		$foundid        = 0;
		$valid_pages    = [];
		$fallback_pages = [];
		
		// build an array of primary and fallback pages
		foreach( (array) $pages as $id => $page ) {
			if ( $page->post_name != $revparts[0] ) {
				continue;
			}
			
			// if the location_id is not set but this page has a term, continue
			// we don't allow location pages to be accessed outside of the location context
			if ( ! $location_id && has_term( '', $this->taxonomy, $page ) ) {
				continue;
			}			
			
			if ( $location_id && has_term( "location_$location_id", $this->taxonomy, $page ) ) {
				$valid_pages[ $id ] = $page;
			}
			
			// use top level pages as a fallback
			if ( ! has_term( '', $this->taxonomy, $page ) ) {
				$fallback_pages[ $id ] = $page;
			}
		}
		
		$page_sets = [ $valid_pages, $fallback_pages ];
		
		foreach( $page_sets as $page_set ) {
			
			foreach ( $page_set as $page ) {
				$count = 0;
				$p     = $page;

				/*
				 * Loop through the given path parts from right to left,
				 * ensuring each matches the post ancestry.
				 */
				while ( 0 != $p->post_parent && isset( $pages[ $p->post_parent ] ) ) {
					$count ++;
					$parent = $pages[ $p->post_parent ];
					if ( ! isset( $revparts[ $count ] ) || $parent->post_name != $revparts[ $count ] ) {
						break;
					}
					$p = $parent;
				}

				if ( 0 == $p->post_parent && count( $revparts ) == $count + 1 && $p->post_name == $revparts[ $count ] ) {
					$foundid = $page->ID;
					if ( $page->post_type == $post_type ) {
						break;
					}
				}
			}
			
			if ( $foundid ) {
				break;
			}			
		}

		// We cache misses as well as hits.
		wp_cache_set( $cache_key, $foundid, 'posts' );

		if ( $foundid ) {
			return $foundid;
		}

		return false;		
	}
}
