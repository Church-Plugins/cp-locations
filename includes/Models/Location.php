<?php

namespace CP_Locations\Models;

use CP_Locations\Exception;
use ChurchPlugins\Setup\Tables\SourceMeta as SourceMetaTable;
use ChurchPlugins\Models\Source;
use ChurchPlugins\Models\SourceType;

/**
 * Source DB Class
 *
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Source Class
 *
 * @since 1.0.0
 */
class Location extends Source {

	public static $type_key = 'location';

	public function init() {
		$this->post_type = 'cploc_location';

		parent::init();
	}

	/**
	 * Get all types
	 *
	 * @return array
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function get_all_locations( $origin = false ) {
		global $wpdb;


		$type_id  = self::get_type_id();
		$meta     = SourceMetaTable::get_instance();
		$instance = new self();

		$sql = sprintf( 'SELECT %1$s.* FROM %1$s
INNER JOIN %2$s
ON %1$s.id = %2$s.source_id
WHERE %2$s.key = "source_type" AND %2$s.source_type_id = %3$d
ORDER BY %2$s.order ASC', $instance->table_name, $meta->table_name, $type_id );

		$locations = $wpdb->get_results( $sql );

		if ( ! $locations ) {
			$locations = [];
		}
		
		if ( $origin ) {
			do_action( 'cploc_multisite_switch_to_main_site' );
			$ids = wp_list_pluck( $locations, 'origin_id' );
			$locations = get_posts( [ 'post_type' => $instance->post_type, 'post__in' => $ids, 'posts_per_page' => 999, 'orderby' => 'menu_order', 'order' => 'ASC' ] );
			do_action( 'cploc_multisite_restore_current_blog' );
		}

		return apply_filters( 'cpl_get_all_locations', $locations, $origin );
	}

	public static function get_type_id() {
		if ( ! $type = SourceType::get_by_title( self::$type_key ) ) {
			try {
				$type = SourceType::insert( [ 'title' => self::$type_key ] );
			} catch ( Exception $e ) {
				error_log( $e );
			}
		}

		return $type->id;
	}

	public static function insert( $data ) {
		$speaker = parent::insert( $data ); // TODO: Change the autogenerated stub
		$speaker->add_type();
		return $speaker;
	}

	public function update( $data = array() ) {
		$this->add_type();
		return parent::update( $data ); // TODO: Change the autogenerated stub
	}

	public function add_type() {
		$this->update_meta( [
			'key' => 'source_type',
			'value' => 1, // just set some positive value
			'source_type_id' => self::get_type_id()
		] );
	}
}
