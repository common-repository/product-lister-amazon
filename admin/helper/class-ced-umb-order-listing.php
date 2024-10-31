<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Orders listing class.
 *
 * @since      1.0.0
 *
 * @package    Amazon Product Lister
 /helper
 */

if( !class_exists( 'ced_umb_amazon_order_lister' ) ) :

/**
 * order listing page.
*
*
* @since      1.0.0
* @package    Amazon Product Lister
/helper
* @author     CedCommerce <cedcommerce.com>
*/
class ced_umb_amazon_order_lister extends WP_List_Table {

	/**
	 * order data query response.
	 *
	 * @since 1.0.0
	 */
	public $example_data; 
	
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct(){
		global $status, $page;
		
		parent::__construct( array(
				'singular'  => 'ced_umb_amazon_mo',
				'plural'    => 'ced_umb_amazon_mos',
				'ajax'      => true
		) );
	}

	/**
	 * columns.
	 *
	 * @since 1.0.0
	 * @see WP_List_Table::get_columns()
	 */
	public function get_columns(){
		$columns = array(
				'orderid'     => __( 'Order ID', 'ced-amazon-lister' ),
				'purchasedate'    => __( 'Purchase Date', 'ced-amazon-lister' ),
				'total'  => __( 'Total', 'ced-amazon-lister' ),
				'paymethod'  => __( 'Payment Method', 'ced-amazon-lister' ),
				'status'    => __( 'Status', 'ced-amazon-lister' ),
				'buyer'  => __( 'Buyer', 'ced-amazon-lister' ),
				
		);
		return $columns;
	}
	
	/**
	 * supported bulk actions for managing orders.
	 *
	 * @since 1.0.0
	 * @see WP_List_Table::get_bulk_actions()
	 */
	public function top_actions( ){
			
		//$marketplaces = $this->get_active_marketplaces();
		$marketplaces = get_enabled_marketplacesamazon();
			
		if(!count($marketplaces)) {
			_e('<h3>Please validate Amazon marketplace first.</h3>','ced-amazon-lister');
			return;
		}
			
		
		echo '<select name="umb_slctd_marketplace" id="bulk_action_marketplace" style="display:none">\n"';
		echo '<option value="all">' . __( 'Marketplace', 'ced-amazon-lister' ) . "</option>\n";
		foreach ($marketplaces as $marketplace):
		echo "\t" . '<option value="' . $marketplace . '" selected>' . $marketplace . "</option>\n";
		endforeach;
		echo "</select>\n";
			
		submit_button( __( 'Fetch Orders', 'ced-amazon-lister' ), 'action', '', false, array( 'id' => "ced_umb_amazon_fetch_order", 'name' => 'umb_fetch_order' ) );
		//echo "\n";
	}
	
	
	/**
	 * preparing the table data for listing orders
	 *
	 * @since 1.0.0
	 * @see WP_List_Table::prepare_items()
	 */
	function prepare_items() {

		$per_page = 10;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
		
		$this->example_data = $this->get_all_orders();
        $data = $this->example_data;
		if($data == null){
			$data = array();
		}
         
        
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        $this->items = $data;
        $this->set_pagination_args( array(
            'total_items' => $total_items,                 
            'per_page'    => $per_page,                     
            'total_pages' => ceil($total_items/$per_page)  
        ) );   
	}
	public function get_all_orders()
	{
		global $wpdb;
		$prefix = $wpdb->prefix . ced_umb_amazon_PREFIX;
		$tableName = $prefix.'ListOrders';

		$sql = "SELECT * FROM `$tableName`";

		$result = $wpdb->get_results($sql,'ARRAY_A');

		return $result;
	}
	function column_default($item, $column_name){
		$total = json_decode($item['total'],true);
		switch($column_name){
			
			case 'orderid':
				return $this->get_order_detail($item);
            case 'purchasedate':
				return $item['purchasedate'];
			case 'total':
				return $total['currency'].$total['total'];
			case 'paymethod':			
				return $item['paymentmethod'];
			case 'status':
				return $item['status'];
			case 'buyer':			
				return $item['buyername'];
            default:
                return false; 
        }
    }

    function get_order_detail($item){
    	echo $item['amazon_orderid'];
    	echo '<br><a href="?page=umb-amazon-orders&action=get_item&orderid='.$item['amazon_orderid'].'&TB_iframe=true&width=630&height=500" rel="permalink" class="thickbox">' .  __( 'Details', 'ced-amazon-lister' ) . '</a>';
    }
}
endif;