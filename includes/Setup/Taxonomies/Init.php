<?php

namespace CP_Locations\Setup\Taxonomies;

/**
 * Setup plugin initialization for Taxonomies
 */
class Init {

	/**
	 * @var Init
	 */
	protected static $_instance;

	/**
	 * Setup Topic taxonomy
	 *
	 * @var Location
	 */
	public $location;

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
	 * Run includes and actions on instantiation
	 *
	 */
	protected function __construct() {
		$this->includes();
		$this->actions();
	}

	/**
	 * Plugin init includes
	 *
	 * @return void
	 */
	protected function includes() {}

	/**
	 * Plugin init actions
	 *
	 * @return void
	 * @author costmo
	 */
	protected function actions() {
		add_action( 'init', [ $this, 'register_taxonomies' ], 5 );
	}

	/**
	 * Return array of taxonomy objects
	 *
	 * @return array
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_objects() {
		return [ $this->location ];
	}

	/**
	 * Return array of taxonomies
	 *
	 * @return array
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_taxonomies() {
		$tax = [];

		foreach( $this->get_objects() as $object ) {
			$tax[] = $object->taxonomy;
		}

		return $tax;
	}

	public function register_taxonomies() {

		$this->location = Location::get_instance();

		if ( cp_locations()->taxonomy_enabled() ) {
			$this->location->add_actions();
			do_action( 'cp_register_taxonomies' );
		} else {
			// still register the taxonomy, but do so in the background
			add_filter( $this->location->taxonomy . '_args', function( $args ){
				$args['show_admin_column'] = false;
				return $args;
			} );
			$this->location->register_taxonomy();
		}

	}

}
