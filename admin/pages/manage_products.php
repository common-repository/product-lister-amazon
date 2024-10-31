<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$working_section = isset($_GET['repricing']) ? $_GET['repricing'] : '';
$marketPlaces = get_enabled_marketplacesamazon();
$marketPlace = is_array($marketPlaces) && !empty($marketPlaces) ? $marketPlaces[0] : -1;
$marketplace = isset($_REQUEST['section']) ? $_REQUEST['section'] : $marketPlace;
if( $working_section == $marketplace.'_repricing' ) {
	//including header file.
	require_once ced_umb_amazon_DIRPATH.'admin/pages/header.php';

	
	$urlToUse = get_admin_url().'admin.php?page=umb-pro-mgmt&section='.$marketplace;
	
	
	?>
	<div class="ced_umb_amazon_wrap ced_umb_amazon_wrap_opt">
		<div class="back">
			<a href="<?php echo $urlToUse;?>"><?php _e('Go Back', 'ced-amazon-lister');?></a>
		</div>
		<div>
		
		<?php do_action("ced_umb_amazon_".$marketplace."_repricing");?>
		</div>
	</div>
	<?php
	return;
}

//product listing class.
require_once ced_umb_amazon_DIRPATH.'admin/helper/class-ced-umb-product-listing.php';
//feed manager helper class for handling bulk actions.
require_once ced_umb_amazon_DIRPATH.'admin/helper/class-feed-manager.php';
//header file.
require_once ced_umb_amazon_DIRPATH.'admin/pages/header.php';

$notices = array();

if(isset($_POST['doaction'])){
	check_admin_referer('bulk-ced_umb_amazon_mps');
	
	$action = isset($_POST['action']) ? $_POST['action'] : -1;
	//$marketPlaces = get_option('ced_umb_amazon_activated_marketplaces',true);
	$marketPlaces = get_enabled_marketplacesamazon();
	$marketPlace = is_array($marketPlaces) && !empty($marketPlaces) ? $marketPlaces[0] : -1;
	$marketplace = isset($_REQUEST['section']) ? $_REQUEST['section'] : $marketPlace;
	$proIds = isset($_POST['post']) ? $_POST['post'] : array();
	$allset = true;
	
	if(empty($action) || $action== -1){
		$allset = false;
		$message = __('Please select the bulk actions to perform action!','ced-amazon-lister');
		$classes = "error is-dismissable";
		$notices[] = array('message'=>$message, 'classes'=>$classes);
	}
	
	if(empty($marketplace) || $marketplace== -1){
		$allset = false;
		$message = __('Any marketplace is not activated!','ced-amazon-lister');
		$classes = "error is-dismissable";
		$notices[] = array('message'=>$message, 'classes'=>$classes);
	}
	
	if(!is_array($proIds)){
		$allset = false;
		$message = __('Please select products to perform bulk action!','ced-amazon-lister');
		$classes = "error is-dismissable";
		$notices[] = array('message'=>$message, 'classes'=>$classes);
	}
	
	
	if($allset)
	{
		if( class_exists( 'ced_umb_amazon_feed_manager' ) )
		{
			$feed_manager = ced_umb_amazon_feed_manager::get_instance();
			$notice = $feed_manager->process_feed_request($action,$marketplace,$proIds);
			
			$notice_array = json_decode($notice,true);
			if(is_array($notice_array))
			{
				$message = isset($notice_array['message']) ? $notice_array['message'] : '' ;
				$classes = isset($notice_array['classes']) ? $notice_array['classes'] : 'error is-dismissable';
				$notices[] = array('message'=>$message, 'classes'=>$classes);
			}
			else
			{
				$message = __('Unexpected error encountered, please try again!','ced-amazon-lister');
				$classes = "error is-dismissable";
				$notices[] = array('message'=>$message, 'classes'=>$classes);
			}
		}
	}
	
}

if(count($notices))
{
	foreach($notices as $notice_array)
	{
		$message = isset($notice_array['message']) ? esc_html($notice_array['message']) : '';
		$classes = isset($notice_array['classes']) ? esc_attr($notice_array['classes']) : 'error is-dismissable';
		if(!empty($message))
		{?>
			 <div class="<?php echo $classes;?>">
			 	<p><?php echo $message;?></p>
			 </div>
		<?php 	
		}
	}
	unset($notices);
}

$availableMarketPlaces = get_enabled_marketplacesamazon();
if(is_array($availableMarketPlaces) && !empty($availableMarketPlaces)) {
	$section = $availableMarketPlaces[0];
	if(isset($_GET['section'])) {
		$section = esc_attr($_GET['section']);
	}
	$product_lister = new ced_umb_amazon_product_lister();
	$product_lister->prepare_items();
	?>
	<div class="ced_umb_amazon_wrap">
		<?php do_action("ced_umb_amazon_manage_product_before_start");?>
		
		<h2 class="ced_umb_amazon_setting_header"><?php _e('Manage Products','ced-amazon-lister'); ?></h2>
		
		<?php do_action("ced_umb_amazon_manage_product_after_start");?>
		<form method="get" action="">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<?php $product_lister->search_box('Search Products', 'search_id');?>
		</form>
		
		<?php renderMarketPlacesLinksOnTopamazon('umb-amazon'); ?>

		<form method="get" action="">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<?php
			/** Sorting By Status  **/
			$status_actions = array(
				'published'    => __( 'Published', 'ced-amazon-lister' ),
				'notUploaded'    => __( 'Not Uploaded', 'ced-amazon-lister' ),
			);
			$previous_selected_status = isset($_GET['status_sorting']) ? $_GET['status_sorting'] : '';
		 	
			
			$product_categories = get_terms( 'product_cat', array('hide_empty'=>false) );
		 	$temp_array = array();
		 	foreach ($product_categories as $key => $value) {
		 		$temp_array[$value->term_id] = $value->name;
		 	}
		 	$product_categories = $temp_array;
		 	$previous_selected_cat = isset($_GET['pro_cat_sorting']) ? $_GET['pro_cat_sorting'] : '';
		 	

		 	$product_types = get_terms( 'product_type', array('hide_empty'=>false) );
		 	
		 	$temp_array = array();
		 	foreach ($product_types as $key => $value) {
		 		if( $value->name == 'simple'  ) {
		 			$temp_array[$value->term_id] = ucfirst($value->name);
		 		}
		 	}
		 	$product_types = $temp_array;
		 	$previous_selected_type = isset($_GET['pro_type_sorting']) ? $_GET['pro_type_sorting'] : '';
		 	

			echo '<div class="ced_umb_amazon_top_wrapper">';
				echo '<select name="pro_cat_sorting">';
				echo '<option value="">' . __( 'Product Category', 'ced-amazon-lister' ) . "</option>";
				foreach ( $product_categories as $name => $title ) {
					$selectedCat = ($previous_selected_cat == $name) ? 'selected="selected"' : '';
					$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';
					echo '<option '.$selectedCat.' value="' . $name . '"' . $class . '>' . $title . "</option>";
				}
				echo "</select>";


				submit_button( __( 'Filter', 'ced-amazon-lister' ), 'action', '', false, array() );
			echo '</div>';
			?>
		</form>

		<form id="ced_umb_amazon_products" method="post">
		<?php $product_lister->views(); ?> 	
		<?php ?>	
		
		<?php $product_lister->display() ?>
		</form>
		<?php if($product_lister->has_items()):?>
			<?php  $product_lister->inline_edit(); ?>
			<?php  $product_lister->profle_section(); ?>
		<?php endif;?>
	</div>
	<?php
}
else{
	_e('<h3>Please validate Amazon marketplace first.</h3>','ced-amazon-lister');
}

?>