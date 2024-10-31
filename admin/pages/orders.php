<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
global $wpdb, $cedumbamazonhelper;




$notices = array();
if(isset($_POST['umb_fetch_order'])){
	
	$marketplace = isset($_POST['umb_slctd_marketplace']) ? sanitize_text_field($_POST['umb_slctd_marketplace']) : "all";
	if($marketplace != "all"){
		$marketplace = trim($marketplace);
		$file_name = ced_umb_amazon_DIRPATH.'marketplaces/'.$marketplace.'/class-'.$marketplace.'.php';
		if( file_exists( $file_name ) ){
			
			require_once $file_name;
			$class_name = 'ced_umb_amazon_'.$marketplace.'_manager';
			if( class_exists( $class_name) ){
				$instance = $class_name::get_instance();
				if( !is_wp_error($instance) ){
					
					$notices = $instance->fetchOrders();

				}else{
					$message = __('An unexpected error occured, please try again!','ced-amazon-lister');
					$classes = "error is-dismissable";
					$error = array('message'=>$message,'classes'=>$classes);
					$notices[] = $error;
				}
			}else{
				$message = __('Class missing to perform operation, please check if extension configured successfully!','ced-amazon-lister');
				$classes = "error is-dismissable";
				$error = array('message'=>$message,'classes'=>$classes);
				$notices[] = $error;
			}
		}else{
			$message = __('Please check if selected marketplace is active!','ced-amazon-lister');
			$classes = "error is-dismissable";
			$error = array('message'=>$message,'classes'=>$classes);
			$notices[] = $error;
		}
	}
	}


if(isset($notices) && !empty($notices))
{
	if(count($notices)){
		$cedumbamazonhelper->umb_print_notices($notices);
		unset($notices);
	}
}	


//header file.
require_once ced_umb_amazon_DIRPATH.'admin/pages/header.php';
//profile listing class.
require_once ced_umb_amazon_DIRPATH.'admin/helper/class-ced-umb-order-listing.php';
$order_lister = new ced_umb_amazon_order_lister();
$order_lister->prepare_items();
?>
	<div class="ced_umb_amazon_wrap">
		<h2 class="ced_umb_amazon_setting_header"><?php _e('Manage Orders','ced-amazon-lister'); ?></h2>
		<form id="ced_umb_amazon_orders" method="post">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		<?php $order_lister->top_actions(); ?>
		<?php 
		$marketplaces = get_enabled_marketplacesamazon();
			
		if(count($marketplaces)) {
			$order_lister->display();
		}
		?>
		
		</form>
	</div>
<?php

