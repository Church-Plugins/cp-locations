<?php

namespace CP_Locations\Setup;

/**
 * Setup plugin initialization
 */
class Init {

	/**
	 * @var Init
	 */
	protected static $_instance;

	/**
	 * @var PostTypes\Init;
	 */
	public $post_types;
	
	/**
	 * @var Permissions\Init;
	 */
	public $permissions;
	
	/**
	 * @var Taxonomies\Init;
	 */
	public $taxonomies;
	
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
		$this->actions();
	}

	/**
	 * Admin init includes
	 *
	 * @return void
	 */
	protected function includes() {
		$this->post_types = PostTypes\Init::get_instance();
		$this->permissions = Permissions\Init::get_instance();
		$this->taxonomies = Taxonomies\Init::get_instance();
	}

	protected function actions() {}

	/** Actions ***************************************************/

}
