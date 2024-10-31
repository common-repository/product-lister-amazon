<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
global $cedumbamazonhelper;
$current_page = 'umb-amazon';

if(isset($_GET['page'])){
	$current_page = $_GET['page'];
}
?>

<div id="ced_umb_amazon_marketplace_loader" class="loading-style-bg" style="display: none;">
	<img src="<?php echo plugin_dir_url(__dir__);?>/images/BigCircleBall.gif">
</div>

<?php 
if($current_page!="umb-amazon"){
	$activated_marketplaces = ced_umb_amazon_available_marketplace();

	$validation_notice = array();
	foreach($activated_marketplaces as $activeMarketplace){
		$isValidate = get_option('ced_umb_amazon_validate_'.$activeMarketplace);
		if(!$isValidate){
			$message = __('Configuration details of '.$activeMarketplace.' either empty or not validated successfully, please validate the configuration otherwise some processes might not work properly.','ced-amazon-lister');
			$classes = "notice notice-error";
			$validation_notice[] = array('message'=>$message, 'classes'=>$classes);
		}
	}
	
	if(count($validation_notice)){
	
		$cedumbamazonhelper->umb_print_notices($validation_notice);
		unset($validation_notice);
	}	
}
?>