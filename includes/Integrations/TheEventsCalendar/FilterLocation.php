<?php
namespace CP_Locations\Integrations\TheEventsCalendar;

use CP_Locations\Models\Location as LocationModel;
use CP_Locations\Setup\Taxonomies\Location;
use Tribe\Events\Filterbar\Views\V2\Filters\Context_Filter;
use Tribe__Context as Context;
use Tribe__Utils__Array as Arr;

/**
 * Class FilterLocation
 */
class FilterLocation extends \Tribe__Events__Filterbar__Filter {

	// Use the trait required for filters to correctly work with Views V2 code.
    use Context_Filter;
	
	public $type = 'select';

	public function get_admin_form() {
		$title = $this->get_title_field();
		$type  = $this->get_multichoice_type_field();

		return $title . $type;
	}

	protected function get_values() {
		
		$locations = LocationModel::get_all_locations( true );
		
		if ( empty( $locations ) || is_wp_error( $locations ) ) {
			return array();
		}
		
		$list = [];

		foreach( $locations as $location ) {
			$slug = 'location_' . $location->ID;
			$list[] = array(
				'name'  => $location->post_title,
				'depth' => 0,
				'value' => $slug,
				'data'  => array( 'slug' => $slug ),
				'class' => 'tribe-parent-cat cploc-location tribe-events-category-' . $slug
			);
		}
		
		return $list;
	}

	/**
	 * This method will only be called when the user has applied the filter (during the
	 * tribe_events_pre_get_posts action) and sets up the taxonomy query, respecting any
	 * other taxonomy queries that might already have been setup (whether by The Events
	 * Calendar, another plugin or some custom code, etc).
	 *
	 * @see Tribe__Events__Filterbar__Filter::pre_get_posts()
	 *
	 * @param \WP_Query $query
	 */
	protected function pre_get_posts( \WP_Query $query ) {
		$new_rules      = array();
		$existing_rules = (array) $query->get( 'tax_query' );
		$values         = (array) $this->currentValue;

		$values      = ! empty( $values[0] ) ? explode( ',', $values[0] ) : $values;
		$new_rules[] = array(
			'taxonomy' => Location::$_taxonomy,
			'operator' => 'IN',
			'terms'    => $values,
			'field'    => 'slug',
		);

		/**
		 * Controls the relationship between different taxonomy queries.
		 *
		 * If set to an empty value, then no attempt will be made by the additional field filter
		 * to set the meta_query "relation" parameter.
		 *
		 * @var string $relation "AND"|"OR"
		 */
		$relationship = apply_filters( 'tribe_events_filter_taxonomy_relationship', 'AND' );

		/**
		 * If taxonomy filter meta queries should be nested and grouped together.
		 *
		 * The default is true in WordPress 4.1 and greater, which allows for greater flexibility
		 * when combined with taxonomy queries added by other filters/other plugins.
		 *
		 * @var bool $group
		 */
		$nest = apply_filters( 'tribe_events_filter_nest_taxonomy_queries', version_compare( $GLOBALS['wp_version'], '4.1', '>=' ) );

		if ( $nest ) {
			$new_rules = array(
				__CLASS__ => $new_rules,
			);
		}

		$tax_query = array_merge_recursive( $existing_rules, $new_rules );

		// Apply the relationship (we leave this late, or the recursive array merge would potentially cause duplicates)
		if ( ! empty( $relationship ) && $nest ) {
			$tax_query[ __CLASS__ ]['relation'] = $relationship;
		} elseif ( ! empty( $relationship ) ) {
			$tax_query['relation'] = $relationship;
		}

		// Apply our new meta query rules
		$query->set( 'tax_query', $tax_query );
	}
	
	/**
	 * Parses the raw value from the context to the format used by the filter.
	 *
	 * @since 4.9.0
	 *
	 * @param array|string $raw_value A category term ID, or an array of category term IDs.
	 *
	 * @return array An array of time of category term ids.
	 */
	protected function parse_value( $raw_value ) {
		return array_filter( (array) $raw_value );
	}

	/**
	 * Builds the value that should be set in the query argument for the Category filter.
	 *
	 * @since 4.9.0
	 *
	 * @param string|array $value       The value, as received from the context.
	 * @param string       $context_key The key used to fetch the `$value` from the Context.
	 * @param Context      $context     The context instance.
	 *
	 * @return array An array of term IDs.
	 */
	public static function build_query_arg_value( $value, $context_key, Context $context ) {
		return Arr::list_to_array( $value, ',' );
	}	
}
