<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

//header file.
require_once ced_umb_amazon_DIRPATH.'admin/pages/header.php';
if( isset( $_POST['ced_umb_amazon_marketplace_configuration'] ) && isset($_POST['ced_umb_amazon_save_marketplace_config']) ) {
	do_action( 'ced_umb_amazon_save_marketplace_configuration_settings' , $_POST, 'amazon' );
}

/** validate configuration setup of marketplace **/	
if( isset( $_POST['ced_umb_amazon_marketplace_configuration'] ) && isset($_POST['ced_umb_amazon_validate_marketplace_config']) ) {
	do_action( 'ced_umb_amazon_validate_marketplace_configuration_settings' , $_POST, 'amazon' );
}
$configSettings = array();
$configSettings = apply_filters( 'ced_umb_amazon_render_marketplace_configuration_settings', $configSettings, 'amazon' );
render_marketplace_configuration( $configSettings );

function render_marketplace_configuration( $configSettingsData ) {
		
	$configSettings = $configSettingsData['configSettings'];
	$showUpdateButton = !$configSettingsData['showUpdateButton'];
	$marketPlaceName = $configSettingsData['marketPlaceName'];
	?>
	<div class="ced_umb_amazon_wrap">
		<h2 class="ced_umb_amazon_setting_header ced_umb_amazon_bottom_margin"><?php echo $marketPlaceName;?> <?php _e('Configuration','ced-amazon-lister'); ?></h2>
		<div>
			<form method="post">
				<input type="hidden" name="ced_umb_amazon_marketplace_configuration" value="1" >
				<table class="wp-list-table widefat fixed striped ced_umb_amazon_config_table">
					<tbody>
					<?php
					foreach ($configSettings as $key => $value) {
						echo '<tr>';
							echo '<th class="manage-column">';
								echo $value['name'];
							echo '</th>';
							echo '<td class="manage-column">';
								if($value['type'] == 'text') {
									echo '<input type="text" name="'.$key.'" value="'.$value['value'].'">';
								}
								do_action( 'ced_umb_amazon_render_different_input_type' , $value['type']);
							echo '</td>';
						echo '</tr>';
					}
					?>
					</tbody>
					<tfoot>
						<tr>
							<td></td>
							<td>
								<input class="button button-ced_umb_amazon" type="submit" name="ced_umb_amazon_save_marketplace_config" value="<?php _e('Update','ced-amazon-lister');?>">
								<?php if($showUpdateButton) {
									echo '<input class="button button-ced_umb_amazon" type="submit" name="ced_umb_amazon_validate_marketplace_config" value="'.__('Validate','ced-amazon-lister').'">';	
								}
								do_action('ced_umb_amazon_render_imp_links');
								?>
							</td>
						</tr>	
					</tfoot>
				<table>
			</form>
			<?php 
				$marketPlaceName = str_replace(" ", "", $marketPlaceName);
				do_action("ced_".$marketPlaceName."_additional_configuration", $marketPlaceName);
			?>			
		</div>	
	<div>
	<?php
}