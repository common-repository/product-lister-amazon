<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * plugin admin pages related functionality helper class.
 *
 * @since      1.0.0
 *
 * @package    Amazon Product Lister
 /helper
 */

if( !class_exists( 'ced_umb_amazon_menu_page_manager' ) ) :

/**
 * Admin pages related functionality.
 *
 * Manage all admin pages related functionality of this plugin.
 *
 * @since      1.0.0
 * @package    Amazon Product Lister
 /helper
 * @author     CedCommerce <cedcommerce.com>
 */
class ced_umb_amazon_menu_page_manager{
	
	/**
	 * The Instace of ced_umb_amazon_menu_page_manager.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      $_instance   The Instance of ced_umb_amazon_menu_page_manager class.
	 */
	private static $_instance;
	
	/**
	 * ced_umb_amazon_menu_page_manager Instance.
	 *
	 * Ensures only one instance of ced_umb_amazon_menu_page_manager is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return ced_umb_amazon_menu_page_manager instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	 * Creating admin pages of amazon-product-lister.
	 * 
	 * @since 1.0.0
	 */
	public function create_pages(){

		add_menu_page('Amazon', 'Amazon', __('manage_woocommerce','ced-amazon-lister'), 'umb-amazon', array( $this, 'ced_umb_amazon_marketplace_page' ),'', 53.556 );
		add_submenu_page('umb-amazon', __('Marketplaces','ced-amazon-lister'), __('Configuration','ced-amazon-lister'), 'manage_woocommerce', 'umb-amazon', array( $this, 'ced_umb_amazon_marketplace_page' ) );
		add_submenu_page('umb-amazon', __('Category Mapping','ced-amazon-lister'), __('Category Mapping','ced-amazon-lister'), 'manage_woocommerce', 'umb-amazon-cat-map', array( $this, 'ced_umb_amazon_category_map_page' ) );
		add_submenu_page('umb-amazon', __('Profile','ced-amazon-lister'), __('Profile','ced-amazon-lister'), 'manage_woocommerce', 'umb-amazon-profile', array( $this, 'ced_umb_amazon_profile_page' ) );
		add_submenu_page('umb-amazon', __('Manage Products','ced-amazon-lister'), __('Manage Products','ced-amazon-lister'), 'manage_woocommerce', "umb-amazon-pro-mgmt", array( $this, 'ced_umb_amazon_pro_mgmt_page' ) );
		add_submenu_page('umb-amazon', __('Feed Status','ced-amazon-lister'), __('Feed Status','ced-amazon-lister'), 'manage_woocommerce', 'umb-amazon-fileStatus', array( $this, 'ced_umb_amazon_file_status_page' ) );
		add_submenu_page('umb-amazon', __('Orders','ced-amazon-lister'), __('Orders','ced-amazon-lister'), 'manage_woocommerce', 'umb-amazon-orders', array( $this, 'ced_umb_amazon_orders_page' ) );
		add_submenu_page('umb-amazon', __('Tools','ced-amazon-lister'), __('Tools','ced-amazon-lister'), 'manage_woocommerce', 'umb-amazon-tools', array( $this, 'ced_umb_amazon_tools_page' ) );
		add_submenu_page('umb-amazon', __('Send data','ced-amazon-lister'), __('Send data','ced-umb'), 'manage_woocommerce', 'umb-amazon-auto_acknowledge', array( $this, 'ced_umb_amazon_auto_acknowledge_page' ) );
	}
	
	/**
	 * Auto Acknowledge page.
	 *
	 * @since 1.0.0
	 */
	public function ced_umb_amazon_auto_acknowledge_page(){
		require_once ced_umb_amazon_DIRPATH.'admin/pages/auto_acknowledge.php';
	}

	/**
	 * Orders page.
	 * 
	 * @since 1.0.0
	 */
	public function ced_umb_amazon_orders_page(){
		
		require_once ced_umb_amazon_DIRPATH.'admin/pages/orders.php';
	}
	/**
	 * file status page.
	 * 
	 * @since 1.0.0
	 */
	public function ced_umb_amazon_file_status_page(){
		
		require_once ced_umb_amazon_DIRPATH.'admin/pages/fileStatus.php';
	}
	/**
	 * Upload product in Bulk
	 * 
	 * @since 1.0.0
	 */
	
	public function ced_umb_amazon_tools_page(){
		require_once ced_umb_amazon_DIRPATH.'admin/pages/tools.php';
	}
	
	/**
	 * Marketplaces page.
	 * 
	 * @since 1.0.0
	 */
	public function ced_umb_amazon_marketplace_page(){
		
		require_once ced_umb_amazon_DIRPATH.'admin/pages/marketplaces.php';
	}
	
	
	/**
	 * Category mapping page panel.
	 * 
	 *  @since 1.0.0
	 */
	public function ced_umb_amazon_category_map_page(){
		
		require_once ced_umb_amazon_DIRPATH.'admin/pages/category_mapping.php';
	}
	
	/**
	 * Products management page panel.
	 *
	 *  @since 1.0.0
	 */
	public function ced_umb_amazon_pro_mgmt_page(){
	
		require_once ced_umb_amazon_DIRPATH.'admin/pages/manage_products.php';
	}
	
	/**
	 * Profile page for easy product uploading.
	 * 
	 * @since 1.0.0
	 */
	public function ced_umb_amazon_profile_page(){
		
		require_once ced_umb_amazon_DIRPATH.'admin/pages/profile.php';
	}
	
}

endif;