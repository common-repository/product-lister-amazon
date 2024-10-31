<?php 
/**
 * Exit if accessed directly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$current_tab = "sku-settings";

if(isset($_GET['tab']))
{
	$current_tab = $_GET['tab'];
}
if(isset($_POST['ced_sku_settings_save'])){
	if($current_tab=="sku-settings"){
		$sku_settings_array = array();
		$ced_sku_prefix = (isset($_POST['ced_sku_settings_prefix']) && $_POST['ced_sku_settings_prefix'] != null) ? trim($_POST['ced_sku_settings_prefix']) : "";
		$ced_simple_sku = (isset($_POST['ced_sku_simple_product']) && $_POST['ced_sku_simple_product'] != null) ? $_POST['ced_sku_simple_product'] : "ced_first_letter";
		

		$sku_settings_array['ced_sku_prefix'] = $ced_sku_prefix;
		$sku_settings_array['ced_simple_sku'] = $ced_simple_sku;
		

		if(is_array($sku_settings_array))
			update_option('ced_sku_settings_array',$sku_settings_array);

		$notice['message'] = __('Settings Saved Successfully','ced-amazon-lister');
		$notice['classes'] = "notice notice-success";
		$validation_notice[] = $notice;
		global $cedumbamazonhelper;
		$cedumbamazonhelper->umb_print_notices($validation_notice);
	}
}
$sku_settings = get_option('ced_sku_settings_array',true);

if(!is_array($sku_settings)): $sku_settings = array(); endif;
?>
<table class="form-table">
	<?php 
	$ced_sku_prefix = (isset($sku_settings['ced_sku_prefix']) && $sku_settings['ced_sku_prefix'] != null) ? $sku_settings['ced_sku_prefix'] : "";
	$ced_simple_sku = (isset($sku_settings['ced_simple_sku']) && $sku_settings['ced_simple_sku'] != null) ? $sku_settings['ced_simple_sku'] : "ced_first_letter";
	
	?>
	<tbody>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="ced_sku_settings_prefix"><?php _e('SKU Prefix', 'ced-amazon-lister');?></label>
			</th>
			<td class="forminp forminp-text">
				<label for="ced_sku_settings_prefix">
					<input type="text" name="ced_sku_settings_prefix" id="ced_sku_settings_prefix" class="input-text" value="<?php echo $ced_sku_prefix; ?>">
				</label>						
			</td>

		</tr>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="ced_sku_simple_product"><?php _e('Simple Product', 'ced-amazon-lister');?></label>
			</th>
			<td class="forminp forminp-text">
				<label for="ced_sku_simple_product">
					<select name="ced_sku_simple_product">
						<option value="ced_first_letter" <?php selected($ced_simple_sku,'ced_first_letter');?>><?php _e('First letter of every word','ced-amazon-lister'); ?></option>
						<option value="ced_first_two_letter" <?php selected($ced_simple_sku,'ced_first_two_letter');?>><?php _e('First two letter of every word','ced-amazon-lister'); ?></option>
					</select>
				</label>						
			</td>
		</tr>
		
		</tbody>
</table>
<p class="submit">
	<input type="submit" value="<?php _e('Save changes','ced-amazon-lister'); ?>" class="button-primary woocommerce-save-button" name="ced_sku_settings_save">
</p>