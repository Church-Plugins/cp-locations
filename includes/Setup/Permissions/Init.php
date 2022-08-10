<?php

namespace CP_Locations\Setup\Permissions;

use CP_Locations\Models\Location;

/**
 * Setup plugin initialization
 */
class Init {

	/**
	 * @var Init
	 */
	protected static $_instance;
	
	/**
	 * Only make one instance of Init
	 *
	 * @return Init
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Init ) {
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
		
		if ( cp_locations()->enabled() ) {
			$this->actions();
		}

	}

	/**
	 * Admin init includes
	 *
	 * @return void
	 */
	protected function includes() {
	}

	protected function actions() {
		add_action( 'edit_user_profile', [ $this, 'locations_permissions' ] );
		add_action( 'edit_user_profile_update', [ $this, 'locations_permissions_save' ] );
		
		add_filter( 'map_meta_cap', [ $this, 'map_cap' ], 10, 4 );
		add_action( 'pre_get_posts', [ $this, 'query_permissions' ] );
	}

	/** Actions ***************************************************/
	
	public static function get_user_locations( $user_id = false, $with_prefix = false ) {

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( ! $locations = get_user_meta( $user_id, 'cp_location_permissions', true ) ) {
			$locations = [];
		}
		
		if ( $with_prefix ) {
			foreach( $locations as &$location ) {
				$location = 'location_' . $location;
			}
		}
		
		return apply_filters( 'cploc_get_user_locations', $locations, $user_id );
	}

	public function locations_permissions( $user ) {
		$locations = Location::get_all_locations();
		$user_locs = self::get_user_locations( $user->ID );
		
		if ( empty( $locations ) ) {
			return;
		} ?>
		
		<br />
		<h2><?php _e( 'Locations Permissions' ); ?></h2>
		<p>Select the locations for which this user can manage content. If no locations are selected for this user, all locations will be accessible.</p>
		
		<table>
			<?php foreach( $locations as $location ) : ?>
				<tr>
					<td><input id="cpl_<?php echo $location->id; ?>" type="checkbox" value="<?php echo $location->origin_id; ?>" <?php checked( in_array( $location->origin_id, $user_locs ) ); ?> name="cp_location_permissions[]" /></td>
					<td><label for="cpl_<?php echo $location->id; ?>"><?php echo $location->title; ?></label></td> 
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}
	
	public function locations_permissions_save( $user_id ) {
		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update-user_' . $user_id ) ) {
			return false;
		}

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		
		if ( empty( $_POST['cp_location_permissions'] ) ) {
			delete_user_meta( $user_id, 'cp_location_permissions' );
		} else {
			update_user_meta( $user_id, 'cp_location_permissions', array_map( 'absint', $_POST['cp_location_permissions'] ) );
		}

	}
	
	public function map_cap( $caps, $cap, $user_id, $args ) {
		
		$user_locations = self::get_user_locations( $user_id );
		
		if ( empty( $user_locations ) ) {
			return $caps;
		}
		
		$check = [ 'edit_post', 'delete_post' ];
		
		if ( in_array( $cap, $check ) ) {
			$post_id = $args[0];
			$post_type = get_post_type( $post_id );
			
			if ( cp_locations()->setup->post_types->locations->post_type === $post_type ) {
				if ( in_array( $post_id, $user_locations ) ) {
					return $caps;
				} else {
					return [ 'do_not_allow' ];
				}
			}
			
			if ( ! is_object_in_taxonomy( $post_type, cp_locations()->setup->taxonomies->location->taxonomy ) ) {
				return $caps;
			}
			
			$tax = cp_locations()->setup->taxonomies->location->taxonomy;
			
			if ( isset( $_POST[ $tax ] ) ) {
				wp_set_post_terms( $post_id, $_POST[ $tax ], $tax );
			}
			
			$terms = get_the_terms( $post_id, $tax );
			
			if ( empty( $terms ) ) {
				return [ 'do_not_allow' ];
			}
			
			// if there are terms associated with the content, see if the user has one
			foreach( $terms as $term ) {
				$location_id = str_replace( 'location_', '', $term->slug );
				if ( in_array( $location_id, $user_locations ) ) {
					return $caps;
				}
			}
			
			return [ 'do_not_allow' ];
		}
		
		return $caps;
	}

	/**
	 * @param $query \WP_Query
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function query_permissions( $query ) {
		
		if ( ! is_admin() ) {
			return;
		}
	
		if ( ! $query->is_main_query() ) {
			return;
		}
		
		if ( ! in_array( $query->get('post_type'), cp_locations()->setup->taxonomies->location->get_object_types() ) ) {
			return;
		}
		
		$user_locations = self::get_user_locations( get_current_user_id(), true );
		
		if ( empty( $user_locations ) ) {
			return;
		}

		$tax_query = $query->get('tax_query');
		
		$location_query = [
			'relation' => 'AND',
			[
				'taxonomy' => cp_locations()->setup->taxonomies->location->taxonomy,
				'field' => 'slug',
				'terms' => $user_locations,
			]
		];
		
		if ( isset( $tax_query['relation'] ) ) {
			$location_query[] = $tax_query;
		} else if ( is_array( $tax_query ) ) {
			$location_query = array_merge( $location_query, $tax_query );
		}
		
		$query->set( 'tax_query', $location_query );
		
	}
	
}
