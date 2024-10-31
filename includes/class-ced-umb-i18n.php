<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 *
 * @package    Amazon Product Lister
 * @subpackage amazon-product-lister/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Amazon Product Lister
 * @subpackage amazon-product-lister/includes
 * @author     CedCommerce <plugins@cedcommerce.com>
 */
class ced_umb_amazon_i18n {

	/**
	 * The instance of ced_umb_amazon_Loader.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private static $_instance;
	
	/**
	 * ced_umb_amazon_i18n Instance.
	 *
	 * Ensures only one instance of ced_umb_amazon_i18n is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return ced_umb_amazon_i18n - Main instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'ced-amazon-lister',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
?>