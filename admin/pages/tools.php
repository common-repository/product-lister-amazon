<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$skutab = "";
$skutabactive = false;
$skusettab = "";
$skusettabactive = false;
$importprotab = "";
$importprotabactive = false;
if(!session_id()) {
	session_start();
}
require_once ced_umb_amazon_DIRPATH.'admin/pages/header.php';

if($current_page == 'umb-amazon-tools')
{
	if(isset($_GET['tab']) && !empty($_GET['tab']))
	{

		$tab = $_GET['tab'];
		if($tab == 'sku-generator')
		{
			$skutab = "nav-tab-active";
			$skutabactive = true;
		}
		if($tab == 'sku-settings')
		{
			$skusettab = "nav-tab-active";
			$skusettabactive = true;
		}
		if($tab == 'import-products')
		{
			$importprotab = "nav-tab-active";
			$importprotabactive = true;
		}
	}
	if(empty($tab))
	{
		$skutab = "nav-tab-active";
		$skutabactive = true;
	}	
		
}

?>
<div class="wrap woocommerce ced_umb_amazon_pages_wrapper">
	<div class="ced_umb_amazon_wrap">
		<h2 class="ced_umb_amazon_setting_header"><?php _e('Tools','ced-amazon-lister'); ?></h2>
		<form enctype="multipart/form-data" action=""  method="post">
			<?php 
			$marketplaces = get_enabled_marketplacesamazon();
			
			if(count($marketplaces)) {
				?>
				<h2 class="nav-tab-wrapper woo-nav-tab-wrapper ced_umb_amazon_nav_tab_wrapper">
					<nav class="nav-tab-wrapper woo-nav-tab-wrapper">					
						<a class="nav-tab <?php echo $skutab;?>" href="<?php get_admin_url() ?>admin.php?page=umb-amazon-tools&tab=sku-generator"><?php _e('Table', 'ced-amazon-lister');?></a>
						<a class="nav-tab <?php echo $skusettab;?>" href="<?php get_admin_url() ?>admin.php?page=umb-amazon-tools&tab=sku-settings"><?php _e('SKU Generator Settings', 'ced-amazon-lister');?></a>
						<a class="nav-tab <?php echo $importprotab;?>" href="<?php get_admin_url() ?>admin.php?page=umb-amazon-tools&tab=import-products"><?php _e('Import Products', 'ced-amazon-lister');?></a>					
						<?php 
						do_action('ced_amazon_tool_tab');
						?>				
					</nav>
				</h2>	
				<br>	
				<?php 
				if($skutabactive == true)
				{
					?>
						<h2><?php _e('SKU Generator & Competitive Pricing', 'ced-amazon-lister'); ?></h2>
					<?php
					require_once ced_umb_amazon_DIRPATH.'admin/helper/class-ced-umb-sku-generator.php';
					
					global $cedumbamazonhelper;

					
					$sku_lister = new ced_umb_amazon_sku_generator();
					$sku_lister->prepare_items();
					?>
						<form method="get" action="">
							<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
							<?php $sku_lister->search_box(__('Search Products','ced-amazon-lister'), 'search_id');?>
						</form>
						
						<form id="ced_umb_amazon_products" method="post">
												
							<?php $sku_lister->display(); ?>

						</form>
									
					<?php
					if(isset($_SESSION['ced_umb_amazon_validation_notice'])) {
						
						$value = $_SESSION['ced_umb_amazon_validation_notice'];
						$cedumbamazonhelper->umb_print_notices($value);
						unset($_SESSION['ced_umb_amazon_validation_notice']);
					}
				}
				if($skusettabactive == true)
				{
					require_once ced_umb_amazon_DIRPATH.'admin/pages/sku-generator.php';
				}
				if($importprotabactive == true)
				{
					require_once ced_umb_amazon_DIRPATH.'admin/pages/import-products.php';
				}
			}
			else{
				_e('<h3>Please validate Amazon marketplace first.</h3>','ced-amazon-lister');
			}
			?>			
		</form>
	</div>
</div>
