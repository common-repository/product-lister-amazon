<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}


//header file.
require_once ced_umb_amazon_DIRPATH.'admin/pages/header.php';

$activated_marketplaces	 = get_enabled_marketplacesamazon();

?>
<div id="ced_umb_amazon_marketplace_loader" class="loading-style-bg" style="display: none;">
	<img src="<?php echo plugin_dir_url(__dir__);?>/images/BigCircleBall.gif">
</div>
<?php 

if(is_array($activated_marketplaces) && !empty($activated_marketplaces)){
	$count = 1;
	echo '<div class="ced_umb_amazon_wrap">';
	foreach($activated_marketplaces as $marketplace){
		
		$file_path = ced_umb_amazon_DIRPATH.'marketplaces/'.$marketplace.'/partials/ced-umb-cat-mapping.php';
		if(file_exists($file_path)){
			require_once $file_path;
		}else{
			if($count == count($marketplace)){
				_e('This process is not required for currently activated marketplaces.','ced-amazon-lister');
			}
		}
		$count++;
	}
	echo '</div>';
}else{
	_e('<h3>Please validate Amazon marketplace first.</h3>','ced-amazon-lister');
} 