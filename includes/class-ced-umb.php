<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 *
 * @package    Amazon Product Lister
 * @subpackage amazon-product-lister/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Amazon Product Lister
 * @subpackage amazon-product-lister/includes
 * @author     CedCommerce <plugins@cedcommerce.com>
 */
class ced_umb_amazon {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      ced_umb_amazon_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;
	
	/**
	 * The helper that contains helper functions.
	 * 
	 * @since 	1.0.0
	 * @access 	public
	 */
	public $helper;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The instance of this class.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private static $_instance;
	
	/**
	 * ced_umb_amazon_Helper Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return ced_umb_amazon - Main instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'Amazon Product Lister';
		$this->version = '1.0.0';

		$this->define_constants();
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Define constants of this plugin.
	 *
	 * @since 1.0.0
	 */
	public function define_constants(){
	
		$this->define( 'ced_umb_amazon_LOG_DIRECTORY', wp_upload_dir()['basedir']."/ced_umb_amazon_logs");
		$this->define( 'ced_umb_amazon_VERSION', $this->version );
		$this->define( 'ced_umb_amazon_PREFIX', 'ced_umb_amazon' );
		$this->define( 'ced_umb_amazon_DIRPATH', plugin_dir_path( dirname( __FILE__ ) ) );
		$this->define( 'ced_umb_amazon_URL', plugin_dir_url( dirname( __FILE__ ) ) );
		$this->define( 'ced_umb_amazon_ABSPATH', untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) ) );
	}
	
	/**
	 * Define constant if not already set.
	 *
	 * @since  1.0.0
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
	
	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - ced_umb_amazon_Loader. Orchestrates the hooks of the plugin.
	 * - ced_umb_amazon_i18n. Defines internationalization functionality.
	 * - ced_umb_amazon_Admin. Defines all hooks for the admin area.
	 * - ced_umb_amazon_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for generate html of attributes of category assigned
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/ced-umb-render-attributes.php';
		

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ced-umb-loader.php';
		
		/**
		 * The helper class responsible for all the common function of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ced-umb-helper-functions.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ced-umb-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ced-umb-admin.php';

		$this->loader = ced_umb_amazon_Loader::get_instance();
		
		$this->helper = ced_umb_amazon_Helper::get_instance();
		
		$GLOBALS['cedumbamazonhelper'] = ced_umb_amazon_Helper::get_instance();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Plugin_Name_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$ced_umb_amazon_i18n = ced_umb_amazon_i18n::get_instance();

		$this->loader->add_action( 'plugins_loaded', $ced_umb_amazon_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$ced_umb_amazon_admin = new ced_umb_amazon_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $ced_umb_amazon_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $ced_umb_amazon_admin, 'enqueue_scripts' );
		
		$ced_umb_amazon_admin_hooks = $ced_umb_amazon_admin->get_admin_hooks();
		if(is_array( $ced_umb_amazon_admin_hooks) ){
			foreach( $ced_umb_amazon_admin_hooks as $actions_data ){
				$this->load_hooks( $actions_data );
			}
		}
	}
	
	/**
	 * Register the hook related to the plugin functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_hooks( $actions_data = array() ){
	
		if(is_array($actions_data)){
			$type = isset($actions_data['type']) ? esc_attr($actions_data['type']) : 'action';
			$action = isset($actions_data['action']) ? esc_attr($actions_data['action']) : null;
			$instance = isset($actions_data['instance']) && is_object($actions_data['instance']) ? $actions_data['instance'] : null;
			$function_name = isset($actions_data['function_name']) ? esc_attr($actions_data['function_name']) : null;
			$priority = isset($actions_data['priority']) ? esc_attr($actions_data['priority']) : 10;
			$accepted_args = isset($actions_data['accepted_args']) ? esc_attr($actions_data['accepted_args']) : 1;
	
			if( is_null($action) || is_null($instance) || is_null($function_name) ){
				return;
			}else{
				switch( $type ){
					case 'filter':
						$this->loader->add_filter( $action, $instance, $function_name, $priority, $accepted_args );
						break;
	
					default:
						$this->loader->add_action( $action, $instance, $function_name, $priority, $accepted_args );
				}
			}
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    ced_umb_amazon_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
?>