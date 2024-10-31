<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if(!class_exists('WP_List_Table')) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * product listing related functionality on manage products page.
 *
 * @since      1.0.0
 *
 * @package    Amazon Product Lister
 /helper
 */

if( !class_exists( 'ced_umb_amazon_import_products' ) ) :

/**
 * product listing on manage product.
 *
 * product quick editing, listing and all other functionalities
 * to manage products.
 *
 * @since      1.0.0
 * @package    Amazon Product Lister
 /helper
 * @author     CedCommerce <cedcommerce.com>
 */
class ced_umb_amazon_import_products extends WP_List_Table {
	
	public $example_data; 
	/**
	 * Constructor.
	 * 
	 * @since 1.0.0
	 */
	function __construct(){

		
		parent::__construct( array(
				'singular'  => 'ced_umb_amazon_mp',     
				'plural'    => 'ced_umb_amazon_mps',   
				'ajax'      => true        
		) );		
	}
	/**
	 * columns for the manage product table from
	 * where you can manage products for marketplaces.
	 * 
	 * @since 1.0.0
	 * @see WP_List_Table::get_columns()
	 */
	public function get_columns(){
		$columns = array(
				'cb'        => '<input type="checkbox" />',
				'name'    => __( 'Name', 'ced-amazon-lister' ),
				
				'sku' => __('SKU', 'ced-amazon-lister'),
				'asin'	=> __('ASIN', 'ced-amazon-lister'),
				'price'=>  __('Price', 'ced-amazon-lister'),

		);
		$columns = apply_filters('ced_umb_amazon_alter_columns_in_sku_generator_section',$columns);
		return $columns;
	}
	function column_default($item, $column_name){
		
		
		switch($column_name){
			
			case 'name':
				return $item[$column_name];
            case 'sku':
				return $item[$column_name];
			case 'asin':
				return $item[$column_name];
			case 'price':	
				if($item[$column_name]){
					return get_woocommerce_currency_symbol().$item[$column_name];
				}		
				else{
					return __('Price not set','ced-amazon-lister');
				}
           	
            default:
                return false; 
        }
    }
     
	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = [
			'import_product'    => __( 'Import Product', 'ced-amazon-lister' ),
		];
		return $actions;
	}
	public function process_bulk_action()
	{
		global $cedumbamazonhelper;
		if( 'import_product' === $this->current_action() ) {
           
            if( isset( $_POST['ced_import_products_array'] ) && !empty( $_POST['ced_import_products_array'] ))
            {
            	$directorypath = CED_MAIN_DIRPATH;
				if(!class_exists("AmazonReportRequest"))
				{
					require($directorypath.'marketplaces/amazon/lib/amazon/includes/classes.php');
				}
				$marketplaceid = get_option('ced_umb_amazon_amazon_configuration',true);
				$amz = new AmazonProductList();
				$amz->setMarketplace($marketplaceid['marketplace_id']);
				$amz->setIdType('ASIN');
				

            	$all_asin = $_POST['ced_import_products_array'];
            	if(count($all_asin) <= 5)
            	{
            		$amz->setProductIds($all_asin);
	            	$amz->fetchProductList();
	            	
	                $get_product = $amz->getProduct();
	               	$all_sku = $_POST['ced_import_pass_sku'];
	               	$all_price = $_POST['ced_import_pass_price'];
	                foreach ($get_product as $key => $value)
	                {
	                	$get_product_data = $value->getProduct();
	                	if(isset($all_sku[$key])){
	                		$current_sku = $all_sku[$key];
	                	}
	                	if(isset($all_price[$key])){
	                		$current_price = $all_price[$key];
	                	}
	                	
	                	
	                	if($get_product_data['Identifiers']['Request']['status'] == 'Success')
	                	{	
	                		$data['_amazon_umb_id_type'] = $get_product_data['Identifiers']['Request']['IdType'];
	                		$data['_amazon_umb_id_val'] = $get_product_data['Identifiers']['Request']['Id'];
	                		
	                		$attributearray = $get_product_data['AttributeSets'][0];

	                		
	                		$data['_amazon_umb_brand'] = $attributearray['Brand'];
	                		$data['_amazon_umb_manufacturer'] = $attributearray['Manufacturer'];
	                		if(isset($attributearray['PartNumber'])){
	                			$data['_amazon_umb_mpr'] = $attributearray['PartNumber'];
	                		}
	                		if(isset($attributearray['NumberOfItems'])){
	                			$data['_umb_amazon_NumberOfItems'] = $attributearray['NumberOfItems'];
	                		}
	                		
	                		if(isset($attributearray['ItemDimensions'])){
	                			$dimensions = $attributearray['ItemDimensions'];
	                			if(isset($dimensions['Height'])){
	                				$data['_height'] = $dimensions['Height'];
	                			}
	                			if(isset($dimensions['Weight'])){
	                				$data['_weight'] = $dimensions['Weight'];
	                			}
	                			if(isset($dimensions['Length'])){
	                				$data['_length'] = $dimensions['Length'];
	                			}
	                			if(isset($dimensions['Width'])){
	                				$data['_width'] = $dimensions['Width'];
	                			}	                			
	                		}
	                		if(isset($attributearray['PackageQuantity'])){
	                			$data['_umb_amazon_ItemPackageQuantity'] = $attributearray['PackageQuantity'];
	                		}
	                		$ced_product_title = $attributearray['Title'];
	                		$post_data = array(
								'post_title'     => esc_attr( $ced_product_title ),
								'post_status'    => 'publish',
								'post_type'      => 'product',
							);

							$post_id = wp_insert_post( $post_data );

							foreach ($data as $key => $value) {
								if($value != null ){
									update_post_meta($post_id, $key, $value);
								}
							}
							$ced_sku = $get_product_data['Identifiers']['Request']['Id'];
							$product_type = 'simple';
							$ced_stock_status = 'outofstock';
							$ced_manage_stock = "yes";
							$ced_regular_price = 0;
							update_post_meta( $post_id, '_regular_price', $current_price );
							wp_set_object_terms( $post_id, $product_type, 'product_type' );
							update_post_meta( $post_id, 'ced_amazon_import', 'yes' );
							update_post_meta( $post_id, '_manage_stock', $ced_manage_stock );
							update_post_meta( $post_id, '_stock_status', $ced_stock_status );
							update_post_meta( $post_id, '_sku', $current_sku );
	                		update_post_meta( $post_id, '_price', $current_price );
	                	}
	                }
	                $notice['message'] = __('Product imported Successfully','ced-amazon-lister');
					$notice['classes'] = "notice notice-success";
					$validation_notice[] = $notice;
					$cedumbamazonhelper->umb_print_notices($validation_notice);	
	                
            	}
            	else
            	{
            		
            		$notice['message'] = __('You can only import 5 products at a time','ced-amazon-lister');
					$notice['classes'] = "notice notice-error";
					$validation_notice[] = $notice;
					$cedumbamazonhelper->umb_print_notices($validation_notice);	
            	}
            }
        }
	}
	/**
	 * preparing the table data for listing products
	 * so that we can manage all products form single
	 * place to all frameworks.
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
		$this->process_bulk_action();
		$this->example_data = $this->get_all_requested_products();
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
	public function get_all_requested_products(){
		$get_data = get_option('ced_list_requested_products');
		
		$all_data = array();
		if(isset($get_data) && $get_data != null && count($get_data) > 0){
			global $wpdb;
			$table_name = $wpdb->prefix . 'postmeta';
			$retrieve_sku = $wpdb->get_results(" SELECT `meta_value` FROM `wp_postmeta` WHERE `meta_key` LIKE '_sku' AND `meta_value` != ''  ");
			$sku_array = array();
			foreach ($retrieve_sku as $key => $value) {
				$sku_array[] = $value->meta_value;
			}
			foreach ($get_data as $key => $value) {
				if( $key == 0){
					continue; 
				}
				if(!in_array($value['sku'], $sku_array)){
					$all_data[] = array(
		    			'id' => $value['id'],
		    			'name' => $value['name'],
		    			'sku' => $value['sku'],
		    			'asin'=> $value['asin'],
		    			'price'=> $value['price'],
		    		);
				}	    		
	    	}
		}		
    	return $all_data;
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="ced_import_products_array[]" value="%s" /><input type="hidden" name = "ced_import_pass_sku[]" value="%s"><input type="hidden" name = "ced_import_pass_price[]" value="%s">', $item['asin'],$item['sku'],$item['price']
		);
	}
}
endif;