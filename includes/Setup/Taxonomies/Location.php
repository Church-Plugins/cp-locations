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
	 * @var string
	 */
	public static $_taxonomy = 'cp_location';

	/**
	 * Child class constructor. Punts to the parent.
	 *
	 * @author costmo
	 */
	protected function __construct() {
		$this->taxonomy = self::$_taxonomy;

		$this->single_label = apply_filters( "{$this->taxonomy}_single_label", 'Location' );
		$this->plural_label = apply_filters( "{$this->taxonomy}_plural_label", 'Locations' );

		$this->field_type = 'multicheck';

		parent::__construct();

		// run these actions every time, even if we aren't fully enabled
		add_filter( 'body_class', [ $this, 'body_class' ] );
		add_action( 'wp_head', [ $this, 'location_css' ] );
		add_action( 'template_redirect', [ $this, 'add_location_query_var' ] );
		add_action( 'pre_get_posts', [ $this, 'include_global_items'] );
		add_filter( 'posts_results', [ $this, 'remove_duplicate_posts' ], 10, 2 );
	}

	/**
	 * Remove duplicate posts from the query
	 *
	 * @param $posts
	 *
	 * @return array
	 * @since  1.0.10
	 *
	 * @author Tanner Moushey
	 */
	public function remove_duplicate_posts( $posts, $query ) {

		// make sure this is a locations query
		$locations = $query->get( $this->taxonomy );
		if ( empty( $locations ) ) {
			return $posts;
		}

		// this is not a location page, bale
		if ( ! $current_location = self::get_rewrite_location() ) {
			return $posts;
		}

		$urls       = [];
		$duplicates = [];

		// find posts with duplicate permalinks
		foreach( $posts as $post ) {
			// if we have a duplicate, add it to the list
			if ( in_array( get_the_permalink( $post->ID ), $urls ) ) {
				$duplicates[] = get_the_permalink( $post->ID );
			}

			$urls[] = get_the_permalink( $post->ID );
		}

		// no duplicates found, proceed as normal
		if ( empty( $duplicates ) ) {
			return $posts;
		}

		// loop through and remove any duplicates that don't have the current location term
		foreach( $posts as $index => $post ) {
			if ( ! in_array( get_the_permalink( $post->ID ), $duplicates ) ) {
				continue;
			}

			// if this post does not have the location term, remove the duplicate
			if ( ! has_term( $current_location['term'], $this->taxonomy, $post ) ) {
				unset( $posts[ $index ] );
			}
		}

		// update the found post count
		$query->found_posts = count( $posts );

		// reset the indexes
		return array_values( $posts );
	}

	public function get_args() {
		$args = parent::get_args();

		if ( isset( $_GET['debug-locations'] ) ) {
			$args['show_ui'] = true;
		}

		return $args;
	}

	/**
	 * Get terms with those selected already at the top
	 *
	 * @return array
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_terms_for_metabox() {
		$terms = $this->get_terms();

		if ( ! empty( $_GET['post'] ) ) {
			$post_id = absint( $_GET['post'] );
			$set_terms = wp_list_pluck( wp_get_post_terms( $post_id, $this->taxonomy ), 'name', 'slug' );

			foreach( $set_terms as $slug => $name ) {
				if ( ! isset( $terms[ $slug ] ) ) {
					$terms = array_merge( [ $slug => $name ], $terms );
				}
			}
		}

		return $terms;
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
		$terms = [
			'global' => __( 'Global (All Locations)', 'cp-location' ),
		];

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
		add_filter( 'post_type_link', [ $this, 'location_permalink' ], 20, 2 );

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

		$request_uri = apply_filters( 'cploc_parse_location_request_uri', $_SERVER['REQUEST_URI'] );

		// only update the request URI if it hasn't been filtered.
		$update_request_uri = ( $request_uri === $_SERVER['REQUEST_URI'] );

		// make sure we have a ? to explode
		if ( !strstr( $request_uri, '?' ) ) {
			$request_uri .= '?';
		}

		list( $req_uri, $query_params ) = explode( '?', $request_uri );

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
				if ( $update_request_uri &&
					 (
						! empty( $matches[2] )
						|| isset( $_GET['fl_builder'], $_GET['page_id'] ) // page, post
						|| isset( $_GET['fl_builder'], $_GET['p'] ) // custom post type
					 )
				) {
					$_SERVER['REQUEST_URI'] = '/' . ltrim( $matches[2], '/' );

					if ( $query_params ) {
						$_SERVER['REQUEST_URI'] .= '?' . $query_params;
					}
					add_action( 'template_redirect', [ $this, 'reset_request_uri' ], 11 );
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

		if ( ! isset( $query->query_vars[ 'post_type' ] ) || in_array( $query->query_vars[ 'post_type' ], $this->get_object_types() ) ) {
			$query->query_vars[ $this->taxonomy ] = self::$_rewrite_location['term'];
		}

	}

	/**
	 * Reset REQUEST_URI
	 *
	 * @since  1.0.1
	 *
	 * @author Tanner Moushey
	 */
	public function reset_request_uri() {
		// reset REQUEST_URI
		if ( empty( $_GET['fl_builder'] ) ) {
			$_SERVER['REQUEST_URI'] = self::$_request_uri;
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
	 * @since  1.0.2
	 *
	 * @author Tanner Moushey
	 */
	public function include_global_items( $query ) {

		if ( is_admin() ) {
			return;
		}

		// allow short circuit of global query add
		if ( ! apply_filters( 'cploc_show_global_in_all_queries', true, $query ) ) {
			return;
		}

		$locations = $query->get( $this->taxonomy );
		if ( empty( $locations ) ) {
			return;
		}

		if ( ! is_array( $locations ) ) {
			$locations = [ $locations ];
		}

		if ( false !== array_search( 'global', $locations ) ) {
			return;
		}

		// add global term to all locations queries
		$locations[] = 'global';
		$query->set( $this->taxonomy, $locations );
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

		if ( empty( $query->query_vars['post_type'] ) ) {
			return;
		}

		// add location if we only have location post_types
		$add_location = empty( array_diff( (array) $query->query_vars['post_type'], $this->get_object_types() ) );

		if ( apply_filters( 'cploc_add_location_to_query', $add_location, $query ) ) {
			$query->set( $this->taxonomy, [ self::$_rewrite_location['term'], 'global' ] );
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

		$path = isset( self::$_rewrite_location['path'] ) ? self::$_rewrite_location['path'] : false;
		$term = isset( self::$_rewrite_location['term'] ) ? self::$_rewrite_location['term'] : false;

		// if we are looking at a location page and the url already has the location path, return early
		if ( $path && strstr( $link, $path ) ) {
			return $link;
		}

		$is_global = has_term( 'global', $this->taxonomy, $post );

		// use the default link if it is a global item and we are not on a location page
		if ( ! $term && $is_global ) {
			return $link;
		}

		$locations = get_the_terms( $post, $this->taxonomy );
		$found     = false;

		if ( is_wp_error( $locations ) || ! $locations ) {
			return $link;
		}

		$location    = apply_filters( 'cp_loc_default_location_term', $locations[0], $post->ID );
		$location_id = self::get_id_from_term( $location->slug );
		foreach ( $locations as $loc ) {
			if ( $loc->slug === $term ) {
				$location    = $loc;
				$found       = true;
				$location_id = self::get_id_from_term( $location->slug );
				break;
			}
		}

		// if we didn't find a match, we are on a location page, and the current content is global, use the current location term
		if ( ! $found && $term && $is_global ) {
			$location_id = self::get_id_from_term( $term );
		}

		if ( empty( $location_id ) || ! $loc = get_post( $location_id ) ) {
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

		// get the existing permalink
		$permalink = str_replace( $slug, $original_slug, get_permalink( $post_ID ) );

		// Get all content in this post type with the same name (excluding the current item)
		$check_sql = "SELECT * FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND ID != %d LIMIT 999";
		$posts     = $wpdb->get_results( $wpdb->prepare( $check_sql, $original_slug, $post_type, $post_ID ) );

		foreach( $posts as $post ) {
			// if the permalink of an existing post matches (including location), let WP do its thing
			if ( get_the_permalink( $post->ID ) == $permalink && ! has_term( 'global', $this->taxonomy, $post ) ) {
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

		// make sure we are only modifying queries for post_types that are using this tax
		// @todo make this support arrays
		if ( isset( $query->query['post_type'] ) && ! in_array( $query->query['post_type'], $this->get_object_types() ) ) {
			return $where;
		}

		// does this query already have the location set?
		$has_tax = isset( $query->query[ $this->taxonomy ] );

		// if the queried post has the correct taxonomy, return early
		if ( ! empty( $query->queried_object_id ) && $has_tax ) {
			if ( has_term( $query->query[ $this->taxonomy ], $this->taxonomy, $query->queried_object_id ) ) {
				return $where;
			}
		}

		$post_type  = empty( $query->queried_object_id ) ? false : get_post_type( $query->queried_object_id );
		$id         = false;

		//@todo need to mimick get_page_by_path
		if ( isset( $query->query['pagename'] ) ) {
			$id = $this->get_page_id_by_path( $query->query['pagename'], $post_type );
		} elseif ( isset( $query->query['name'] ) ) {

			global $wpdb;

			$sql = "
				SELECT ID, post_name, post_parent, post_type
				FROM $wpdb->posts
				WHERE 1=1 $where
			";

			$posts = $wpdb->get_results( $sql, OBJECT_K );

			if ( ! empty( $posts ) ) {
				foreach ( $posts as $post ) {
					// set the global post initially as a fallback, we'll overwrite the variable if we find a better option
					if ( has_term( 'global', $this->taxonomy, $post->ID ) ) {
						$id = $post->ID;
					}

					// we have a location and it matches this post
					if ( $has_tax && has_term( $query->query[ $this->taxonomy ], $this->taxonomy, $post->ID ) ) {
						$id = $post->ID;
						break;
					}

					// we do not have a location and neither does this post
					if ( ! $has_tax && ! has_term( '', $this->taxonomy, $post->ID ) ) {
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
			$query->queried_object = get_post( $id );
		} else { // if ( 'tribe_events' !== get_post_type( $id ) ) { - not sure why this was here originally, removing for now
			$where .= " AND $wpdb->posts.ID = '$id'";
		}

		return $where;
	}

	/**
	 * Print location visibility CSS
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function location_css() {
		$locations = \CP_Locations\Models\Location::get_all_locations( true );

		echo '<!-- CP Location visibility styles -->';
		echo '<style id="cp-location-visibility">';
		echo '.cp_location-found .location-hide { display: none !important; }';
		echo '.cp_location-none .location-show { display: none !important; }';

		foreach ( $locations as $location ) {
			printf( '.cp_location-%1$s .location-%1$s-hide { display: none !important; }', $location->ID );
			foreach ( $locations as $loc ) {
				if ( $loc->ID == $location->ID ) {
					continue;
				}

				printf( '.cp_location-%s .location-%s-show { display: none !important; }', $loc->ID, $location->ID );
			}
		}

		echo '</style>';
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

		if ( 0 && false !== $cached ) {
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

			$is_global = has_term( 'global', $this->taxonomy, $page );

			// if the location_id is not set but this page has a location and is not global, continue
			// we don't allow location pages to be accessed outside of the location context
			if ( ! $location_id &&
				 ( ! $is_global && has_term( '', $this->taxonomy, $page ) )
			) {
				continue;
			}

			if ( $location_id && has_term( "location_$location_id", $this->taxonomy, $page ) ) {
				$valid_pages[ $id ] = $page;
			}

			// use global pages as a fallback
			if ( ! $location_id || $is_global  ) {
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
