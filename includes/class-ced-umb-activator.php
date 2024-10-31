<?php

/**
 * Fired during plugin activation
 *
 * @since      1.0.0
 *
 * @package    Amazon Product Lister
 * @subpackage amazon-product-lister/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Amazon Product Lister
 * @subpackage amazon-product-lister/includes
 * @author     CedCommerce <plugins@cedcommerce.com>
 */
class ced_umb_amazon_Activator {

	/**
	 * Activation actions.
	 *
	 * All required actions on plugin activation.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		self::create_tables();
	}
	
	/**
	 * Tables necessary for this plugin.
	 * 
	 * @since 1.0.0
	 */
	private static function create_tables(){
		if(defined('ced_umb_amazon_PREFIX')){
			global $wpdb;
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			
			$prefix = $wpdb->prefix . ced_umb_amazon_PREFIX;
			
			//$wpdb->hide_errors();
			
			// profile table
			$create_profile =
			"CREATE TABLE {$prefix}profiles (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL DEFAULT '',
			active bool NOT NULL DEFAULT true,
			marketplace VARCHAR(255) DEFAULT 'all',
			profile_data longtext DEFAULT NULL,
			PRIMARY KEY  (id)
			);";
			dbDelta( $create_profile );
			
			$createFileTracker = 
			"CREATE TABLE {$prefix}fileTracker (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL DEFAULT '',
			product_ids VARCHAR(1020),
			framework VARCHAR(255),
			time datetime DEFAULT CURRENT_TIMESTAMP,
			response longtext DEFAULT NULL,
			PRIMARY KEY  (id)
			);"; 
			dbDelta( $createFileTracker );
			
			$createOrderLister =
			"CREATE TABLE IF NOT EXISTS {$prefix}ListOrders (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			amazon_orderid varchar(150) DEFAULT NULL,
			amazon_sellerid int(50) DEFAULT NULL,
			purchasedate datetime DEFAULT NULL,
			lastupdatedate datetime DEFAULT NULL,
			total varchar(100) DEFAULT NULL,
			status varchar(100) DEFAULT NULL,
			buyername varchar(100) DEFAULT NULL,
			buyeremail varchar(100) DEFAULT NULL,
			shippingservice varchar(100) DEFAULT NULL,
			paymentmethod varchar(100) DEFAULT NULL,
			shippingaddress text,
			items text,
			PRIMARY KEY  (id)
			);";
			dbDelta( $createOrderLister );
			update_option('ced_umb_amazon_database_version',ced_umb_amazon_VERSION);
		}
	}
	 /**
	  * 
	  * 
	  */
	public function scheduleEvent()
	{
		if (! wp_next_scheduled ( 'ced_umb_amazon_schedule_mail' )) {
			wp_schedule_event(time(), 'daily', 'ced_umb_amazon_scheduled_mail');
		}
	}
}
?>