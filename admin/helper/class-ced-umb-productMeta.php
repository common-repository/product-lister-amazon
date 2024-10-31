<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * product meta related functionalities.
 *
 * @since      1.0.0
 *
 * @package    Amazon Product Lister
 /helper
 */

if( !class_exists( 'ced_umb_amazon_productMeta' ) ) :

/**
 * product meta fields get/set functionalities
 * for each framework.
*
*
* @since      1.0.0
* @package    Amazon Product Lister
/helper
* @author     CedCommerce <cedcommerce.com>
*/
class ced_umb_amazon_productMeta{
	
	/**
	 * The Instace of ced_umb_amazon_productMeta.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      $_instance   The Instance of ced_umb_amazon_productMeta class.
	 */
	private static $_instance;
	
	/**
	 * ced_umb_amazon_productMeta Instance.
	 *
	 * Ensures only one instance of ced_umb_amazon_productMeta is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return ced_umb_amazon_productMeta instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	 * get conditional price.
	 * 
	 * @since 1.0.0
	 */
	public function get_conditional_price($ProId,$marketplace){
		
		if($ProId){
			$priceCondition = get_post_meta();
		}
	}
}
endif;