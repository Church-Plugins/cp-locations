<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Register custom modules for other page builders
 *
 * @package CP_Locations
 */

namespace CP_Locations\Modules;

/**
 * Init class
 */
class Init {

	/**
	 * Beaver Builder instance
	 *
	 * @var BeaverBuilder\Init
	 */
	public $beaver_builder;

	/**
	 * The class instance
	 *
	 * @var Init
	 */
	protected static $instance;

	/**
	 * Get the class instance
	 *
	 * @return Init
	 */
	public static function get_instance() {
		if ( ! self::$instance instanceof Init ) {
			self::$instance = new Init();
		}

		return self::$instance;
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize the modules
	 *
	 * @return void
	 */
	public function init() {
		$this->beaver_builder = BeaverBuilder\Init::get_instance();
	}
}
