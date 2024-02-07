<?php

namespace CP_Locations\Admin;

use ChurchPlugins\Exception;

/**
 * Admin-only plugin initialization
 */
class Multisite {

	/**
	 * @var Multisite
	 */
	protected static $_instance;

	/**
	 * The key for the linked id metadata
	 *
	 * @var string
	 */
	public static $_link_key = '_cploc_linked_id';

	public static $_is_duplicating = false;

	public static $_main_site = 1;

	public static $_current_site;

	/**
	 * Only make one instance of Multisite
	 *
	 * @return Multisite
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Multisite ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Class constructor
	 *
	 */
	protected function __construct() {
		self::$_current_site = get_current_blog_id();
		self::$_main_site = apply_filters( 'cploc_main_site_id', get_main_site_id() );
		$this->actions();
	}

	/**
	 * @return void
	 */
	protected function actions() {

		if ( ! cp_locations()->taxonomy_enabled() ) {
			return;
		}

		// hook in after CMB2
		add_action( 'save_post', [ $this, 'sync_content' ], 20 );
		add_filter( 'cp_origin_id_sql', [ $this, 'origin_id_sql' ] );
		add_filter( 'cp_controller_origin_id', [ $this, 'origin_id_controller' ], 10, 2 );
		add_filter( 'cpl_update_item_type_date', [ $this, 'maybe_disable_item_type_date' ] );
		add_filter( 'cpl_item_type_get_item_ids', [ $this, 'item_ids_for_subsites' ] );
		add_filter( 'cpl_item_type_show_in_menu', [ $this, 'hide_series_from_menu' ] );
		add_filter( 'cpl_save_post_date_redirect', [ $this, 'prevent_subsite_redirect' ] );
		add_filter( 'cploc_get_rewrite_location', [ $this, 'set_rewrite_location' ] );

		// make sure we are on the main site for our shutdown process to save the post dates
		add_action( 'shutdown', [ __CLASS__, 'switch_to_main_site' ], 98 );
		add_action( 'shutdown', [ __CLASS__, 'restore_current_blog' ], 100 );

		add_action( 'cploc_multisite_switch_to_main_site', [ __CLASS__, 'switch_to_main_site' ] );
		add_action( 'cploc_multisite_restore_current_blog', [ __CLASS__, 'restore_current_blog' ] );

	}

	/**
	 * Switch to the main site
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function switch_to_main_site( $return = null ) {
		switch_to_blog( self::$_main_site );
		return $return;
	}

	/**
	 * wrapper for restore current blog, supports hooking with filters
	 *
	 * @param $return
	 *
	 * @return mixed|null
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function restore_current_blog( $return = null ) {
		restore_current_blog();
		return $return;
	}

	/**
	 * Whether or not the provided site is the main site
	 *
	 * @param $site_id
	 *
	 * @return bool
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function is_main_site( $site_id = false ) {
		if ( ! $site_id ) {
			$site_id = get_current_blog_id();
		}

		return $site_id === self::$_main_site;
	}

	/**
	 * Return the map that associates location terms with subsites
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function get_location_site_map( $id = null ) {
		$map = apply_filters( 'cploc_get_location_site_map', [] );

		if ( ! $id ) {
			return $map;
		}

		return isset( $map[ $id ] ) ? $map[ $id ] : false;
	}

	/** Actions ***************************************************/

	public function sync_content( $post_id ) {

		if ( self::$_is_duplicating ) {
			return;
		}

		// don't sync revisions or autosave
		if ( 'auto-draft' == get_post_status( $post_id ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$post_type    = get_post_type( $post_id );
		$sync_content = apply_filters( 'cploc_sync_content', in_array( $post_type, cp_locations()->setup->taxonomies->location->get_object_types() ), $post_id );

		// only sync content attached to the location taxonomy
		if ( ! $sync_content ) {
			return;
		}

		self::$_is_duplicating = true;

		if ( get_current_blog_id() === self::$_main_site ) {
			$sites = self::get_location_site_map();

			$locations = apply_filters( 'cploc_sync_content_locations', get_the_terms( $post_id, cp_locations()->setup->taxonomies->location->taxonomy ), $post_id, $sites );

			foreach( $locations as $location ) {
				$location_id = absint( str_replace( 'location_', '', $location->slug ) );
				if ( $blog_id = array_search( $location_id, $sites ) ) {
					self::sync_post( $post_id, $blog_id );
				}
			}
		} else if ( ! empty( self::get_location_site_map( get_current_blog_id() ) ) ) {
			self::sync_post( $post_id, self::$_main_site );
		}

		self::$_is_duplicating = false;

	}

	/**
	 * Sync the post
	 *
	 * @param $post_id
	 * @param $site_id
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function sync_post( $post_id, $site_id ) {
		if ( ! $linked_ids = get_post_meta( $post_id, self::$_link_key, true ) ) {
			$linked_ids = [];
		}

		$post         = get_post( $post_id, ARRAY_A );
		$current_site = get_current_blog_id();
		$taxonomies   = self::taxonomies_to_copy( $post_id );
		$meta         = self::meta_to_copy( $post_id );
		$cploc_tax    = cp_locations()->setup->taxonomies->location->taxonomy;
		$synced_ids   = [];

		// sync the post_parent first
		if ( $post['post_parent'] ) {
			$post['post_parent'] = self::sync_post( $post['post_parent'], $site_id );
		}

		// if we are cloning to the main site, be sure to include the location taxonomy
		if ( self::is_main_site( $site_id ) && $location_term = self::get_location_site_map( get_current_blog_id() ) ) {
			if ( empty( $taxonomies[ $cploc_tax ] ) ) {
				$taxonomies[ $cploc_tax ] = [];
			}

			$taxonomies[ $cploc_tax ][] = 'location_' . $location_term;

			// make sure the taxonomy is registered for the cloning process
			cp_locations()->setup->taxonomies->location->register_taxonomy();
		}

		// switch to the site to copy to and let the magic begin
		switch_to_blog( $site_id );

		if ( isset( $linked_ids[ $site_id ] ) ) {
			$post['ID'] = $linked_ids[ $site_id ];
			// save the original post id as the linked ID for this site
			if ( ! $synced_ids = get_post_meta( $post['ID'], self::$_link_key, true ) ) {
				$synced_ids = [];
			}
		} else {
			unset( $post['ID'] );
		}

		// save the synced ids with the initial insert post so that it is there when we run our save actions
		$synced_ids[ $current_site ] = $post_id;
		$post['meta_input'] = [ self::$_link_key => $synced_ids ];

		$synced_post_id = wp_insert_post( $post, true );

		if ( is_wp_error( $synced_post_id ) ) {
			restore_current_blog();
			wp_die( $synced_post_id );
		}

		// copy taxonomies
		foreach ( $taxonomies as $taxonomy => $terms ) {
			if ( ! is_object_in_taxonomy( get_post_type( $synced_post_id ), $taxonomy ) ) {
				continue;
			}

			wp_set_object_terms( $synced_post_id, $terms, $taxonomy );
		}

		// copy meta
		foreach ( $meta as $key => $values ) {
			// don't copy over our link key
			if ( $key === self::$_link_key ) {
				continue;
			}

			// delete and re-add so that we can support non-unique postmeta
			delete_post_meta( $synced_post_id, $key );

			foreach( $values as $value ) {
				$value = maybe_unserialize( $value );
				add_post_meta( $synced_post_id, $key, $value );
			}
		}

		do_action( 'cploc_multisite_sync_post_after', $synced_post_id, $post_id );

		// switch back to the original site
		restore_current_blog();

		// add the newly synced post to the linked_ids array
		$linked_ids[ $site_id ] = $synced_post_id;
		update_post_meta( $post_id, self::$_link_key, $linked_ids );

		return $synced_post_id;
	}

	/**
	 * Get post taxonomies to copy
	 *
	 * @param $post_id
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function taxonomies_to_copy( $post_id ) {
		$taxonomies = [];

		$post_type       = get_post_type( $post_id );
		$post_taxonomies = get_object_taxonomies( $post_type );

		// Several plugins just add support to post-formats but don't register post_format taxonomy.
		if ( post_type_supports( $post_type, 'post-formats' ) && ! in_array( 'post_format', $post_taxonomies, true ) ) {
			$post_taxonomies[] = 'post_format';
		}

		$post_taxonomies = apply_filters( 'cploc_multisite_taxonomies_to_copy_keys', $post_taxonomies );

		foreach ( $post_taxonomies as $taxonomy ) {
			$post_terms = wp_get_object_terms( $post_id, $taxonomy, [ 'orderby' => 'term_order' ] );
			$terms      = [];
			$num_terms  = count( $post_terms );
			for ( $i = 0; $i < $num_terms; $i ++ ) {
				$terms[] = $post_terms[ $i ]->name;
			}

			$taxonomies[ $taxonomy ] = $terms;
		}

		return apply_filters( 'cploc_multisite_taxonomies_to_copy', $taxonomies, $post_id );
	}

	/**
	 * Get the post meta to copy
	 *
	 * @param $post_id
	 *
	 * @return array|mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function meta_to_copy( $post_id ) {
		$meta = [];

		$post_meta_keys = apply_filters( 'cploc_multisite_meta_to_copy_keys', get_post_custom_keys( $post_id ) );

		if ( empty( $post_meta_keys ) ) {
			return $meta;
		}

		foreach ( $post_meta_keys as $meta_key ) {
			$meta[ $meta_key ] = get_post_custom_values( $meta_key, $post_id );
		}

		return apply_filters( 'cploc_multisite_meta_to_copy', $meta, $post_id );
	}

	/**
	 * Retrieve object from the main site if one exists
	 *
	 * This makes it so all of our objects are linked to the main site post instead of the various multisites
	 *
	 * @param $origin_id
	 *
	 * @return mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function origin_id( $origin_id ) {
		if ( ! is_multisite() ) {
			return $origin_id;
		}

		if ( self::is_main_site() ) {
			return $origin_id;
		}

		self::switch_to_main_site();
		$linked_ids = get_post_meta( $origin_id, self::$_link_key, true );
		restore_current_blog();

		if ( $linked_ids ) {
			if ( isset( $linked_ids[ get_current_blog_id() ] ) ) {
				return $linked_ids[ get_current_blog_id() ];
			}
		}

		return $origin_id;
	}

	/**
	 * Retrieve object from the main site if one exists
	 *
	 * This makes it so all of our objects are linked to the main site post instead of the various multisites
	 *
	 * @param $origin_id
	 *
	 * @return mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function origin_id_sql( $origin_id ) {
		if ( ! is_multisite() ) {
			return $origin_id;
		}

		if ( self::is_main_site() ) {
			return $origin_id;
		}

		$key = self::$_link_key;

		if ( $linked_ids = get_post_meta( $origin_id, $key, true ) ) {
			if ( isset( $linked_ids[ self::$_main_site ] ) ) {
				return $linked_ids[ self::$_main_site ];
			}
		}

		return $origin_id;
	}

	/**
	 * Retrieve the correct origin_id in case we are not on the main site
	 *
	 * @param $id
	 * @param $use_origin
	 *
	 * @return mixed
	 * @throws Exception
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function origin_id_controller( $id, $use_origin ) {
		if ( $use_origin ) {
			return $id;
		}

		// if we are on the main site, we are good to go
		if ( get_current_blog_id() === self::$_main_site ) {
			return $id;
		}

		// get the linked ids from the main site
		switch_to_blog( self::$_main_site );

		// if we don't have linked IDs, return the post
		if ( ! $linked_ids = get_post_meta( $id, self::$_link_key, true ) ) {
			$post = get_post( $id );
			restore_current_blog();
			return $post;
		}

		restore_current_blog();

		// make sure we have a linked ID for the subsite we are on
		if ( empty( $linked_ids[ get_current_blog_id() ] ) ) {
			throw new Exception( 'The item could not be found on this blog.' );
		}

		return $linked_ids[ get_current_blog_id() ];
	}

	/**
	 * disable item_type date calculation when not on the main site.
	 *
	 * @param $return
	 *
	 * @return false|mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function maybe_disable_item_type_date( $return ) {
		if ( self::is_main_site() ) {
			return $return;
		}

		return false;
	}

	/**
	 * Return the linked Ids if we are on a subsite
	 *
	 * @param $item_ids
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function item_ids_for_subsites( $item_ids ) {
		if ( self::is_main_site() || empty( $item_ids ) ) {
			return $item_ids;
		}

		$current_blog = get_current_blog_id();
		$ids = [];

		switch_to_blog( self::$_main_site );

		foreach( $item_ids as $id ) {
			$linked_ids = get_post_meta( $id, self::$_link_key, true );

			if ( isset( $linked_ids[ $current_blog ] ) ) {
				$ids[] = $linked_ids[ $current_blog ];
			}
		}

		restore_current_blog();

		return $ids;
	}

	/**
	 * Hide the ItemType menu item if we are on a subsite
	 *
	 * @param $show
	 *
	 * @return false|mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function hide_series_from_menu( $show ) {
		if ( self::is_main_site() ) {
			return $show;
		}

		return false;
	}

	/**
	 * Prevent redirect on a subsite
	 *
	 * @param $redirect
	 *
	 * @return false|mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function prevent_subsite_redirect( $redirect ) {
		if ( self::is_main_site() ) {
			return $redirect;
		}

		return false;
	}

	public function set_rewrite_location( $location ) {
		if ( self::is_main_site() ) {
			return $location;
		}

		return [
			'ID'   => 164,
			'term' => 'location_164',
			'path' => '/',
		];
	}
}
