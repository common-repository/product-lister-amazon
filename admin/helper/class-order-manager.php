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

if( !class_exists( 'ced_umb_amazon_order_manager' ) ) :

/**
 * Order related functionalities.
*
*
* @since      1.0.0
* @package    Amazon Product Lister
/helper
* @author     CedCommerce <cedcommerce.com>
*/
class ced_umb_amazon_order_manager{

	/**
	 * The Instace of ced_umb_amazon_feed_manager.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      $_instance   The Instance of ced_umb_amazon_order_manager class.
	 */
	private static $_instance;

	/**
	 * ced_umb_amazon_feed_manager Instance.
	 *
	 * Ensures only one instance of ced_umb_amazon_order_manager is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return ced_umb_amazon_order_manager instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function save_order_listing($orderMeta=array()){

		
		global $wpdb;
		$prefix = $wpdb->prefix . ced_umb_amazon_PREFIX;
		$tableName = $prefix.'ListOrders';
		if(isset($orderMeta['amazon_order_id']))
		{
			$sql = "SELECT * FROM `$tableName` WHERE `amazon_orderid` LIKE '".$orderMeta['amazon_order_id']."' ";
			$result = $wpdb->get_results($sql,'ARRAY_A');
			$order_data = $orderMeta['order_detail'];
			if(!empty($result))
			{				
				if($result[0]['status'] != $order_data['orderstatus']){
					$dataToupdate = array(
							'status'   => $result[0]['status'],
					);
					$where = array('amazon_order_id' =>$orderMeta['amazon_order_id']);
					$update_data = $wpdb->update( $tableName, $dataToupdate, $where );
				}
			}
			else
			{
				
				$ordertotal = array(
					'currency'=> $order_data['currency'],
					'total'=>$order_data['total']
				);
			
				
				$wpdb->insert(
						$tableName,
						array(
								'amazon_orderid'   => $orderMeta['amazon_order_id'],
								'amazon_sellerid' => $order_data['sellerid'],
								'purchasedate'   =>$order_data['purchasedate'],
								'lastupdatedate'=> $order_data['lastupdate'],
								'total'    => json_encode($ordertotal),
								'status'    => $order_data['orderstatus'],
								'buyername'   => $order_data['billing']['first_name'],
								'buyeremail'    => $order_data['billing']['email'],
								'shippingservice'  => $order_data['shipservicelevel'],
								'paymentmethod'   => $order_data['paymethod'],
								'shippingaddress'  => json_encode($order_data['shipping']),
						)
				);

			}
			
		}
		
	}
}
endif;