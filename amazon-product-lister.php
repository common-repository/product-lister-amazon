<?php
/**
 * 
 * @since             1.0.0
 * @package           amazon-product-lister
 *
 * @wordpress-plugin
 * Plugin Name:       Amazon Product Lister
 * Description:       List you products over Amazon in few easy steps
 * Version:           1.0.0
 * Author:            CedCommerce <cedcommerce.com>
 * Author URI:        cedcommerce.com
 * Text Domain:       ced-amazon-lister
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
define('CED_MAIN_DIRPATH', plugin_dir_path( __FILE__ ));
define('CED_MAIN_URL', plugin_dir_url( __FILE__ ));
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ced-umb-activator.php
 * @name activate_ced_umb_amazon_amazon
 * @author CedCommerce
 * @since 1.0.0
 */
function activate_ced_umb_amazon_amazon() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ced-umb-activator.php';
	ced_umb_amazon_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ced-umb-deactivator.php
 * @name deactivate_ced_umb_amazon_amazon
 * @author CedCommerce
 * @since 1.0.0
 */
function deactivate_ced_umb_amazon_amazon() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ced-umb-deactivator.php';
	ced_umb_amazon_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ced_umb_amazon_amazon' );
register_deactivation_hook( __FILE__, 'deactivate_ced_umb_amazon_amazon' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ced-umb.php';

/**
* This file includes core functions to be used globally in plugin.
* @author CedCommerce <plugins@cedcommerce.com>
* @link  http://www.cedcommerce.com/
*/
require_once plugin_dir_path(__FILE__).'includes/ced_umb_amazon_core_functions.php';


add_action('ced_umb_amazon_scheduled_mail', 'ced_umb_amazon_scheduled_process');
/**
 * function to handle scheduled process
 * @name ced_umb_amazon_scheduled_process
 * @author CedCommerce
 * 
 */
function ced_umb_amazon_scheduled_process()
{
	do_action('ced_umb_amazon_track_schedule');
}


/**
 * Check WooCommerce is Installed and Active.
 *
 * since CED UMB is extension for WooCommerce it's necessary,
 * to check that WooCommerce is installed and activated or not,
 * if yes allow extension to execute functionalities and if not
 * let deactivate the extension and show the notice to admin.
 * 
 * @author CedCommerce
 */
if(ced_umb_amazon_check_woocommerce_active()){

	run_ced_umb_amazon();
}else{

	add_action( 'admin_init', 'deactivate_ced_umb_amazon_woo_missing' );
}
?>