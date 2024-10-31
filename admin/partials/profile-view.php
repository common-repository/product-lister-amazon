<?php

require_once 'save-profile-view-data.php';

$profileID = (isset($_GET['profileID'])?$_GET['profileID']:'');
$profile_data = array();
if($profileID){
	$query = "SELECT * FROM `$table_name` WHERE `id`=$profileID";
	$profile_data = $wpdb->get_results($query,'ARRAY_A');
	if(is_array($profile_data)) {
		$profile_data = isset($profile_data[0]) ? $profile_data[0] : $profile_data;
		
		/* fetcing basic information */
		$profile_name = isset($profile_data['name']) ? esc_attr($profile_data['name']) : '';
		$enable = isset($profile_data['active']) ? $profile_data['active'] : false;
		$enable = ($enable) ? "yes" : "no";
		$marketplaceName = isset($profile_data['marketplace']) ? esc_attr($profile_data['marketplace']) : 'all';
		$all_marketplaces = get_enabled_marketplacesamazon();
		array_unshift($all_marketplaces, 'all');

		$data = isset($profile_data['profile_data']) ? json_decode($profile_data['profile_data'],true) : array();
		
	}
}
else {
	/* fetcing basic information */
	$profile_name = isset($profile_data['name']) ? esc_attr($profile_data['name']) : '';
	$enable = isset($profile_data['active']) ? $profile_data['active'] : false;
	$enable = ($enable) ? "yes" : "no";
	$marketplaceName = isset($profile_data['marketplace']) ? esc_attr($profile_data['marketplace']) : 'null';
	$all_marketplaces = get_enabled_marketplacesamazon();
	array_unshift($all_marketplaces, 'all');
}

echo '<div class="ced_umb_amazon_wrap ced_umb_amazon_wrap_opt">';
	echo '<div class="back"><a href="'.get_admin_url().'admin.php?page=umb-amazon-profile">'.__('Go Back','ced-amazon-lister').'</a></div>';
	?>
	<?php
	global $cedumbamazonhelper;
	if(!session_id()) {
		session_start();
	}
	if(isset($_SESSION['ced_umb_amazon_validation_notice'])) {
		$value = $_SESSION['ced_umb_amazon_validation_notice'];
		$cedumbamazonhelper->umb_print_notices($value);
		unset($_SESSION['ced_umb_amazon_validation_notice']);
	}
	?>
	<div class="ced_umb_amazon_toggle_section_wrapper">
		<div class="ced_umb_amazon_toggle">
			<span>Instructions To Use</span>	
		</div>	
		<div class="ced_umb_amazon_toggle_div ced_umb_amazon_instruct" style="display:none;">
			<p><?php _e('1. Use "Select Product And Corresponding MetaKeys" section to select the metakeys of product you consider can be useful in mapping. This step is not always necessary. If you have done it before, you can skip it for the next time you create a profile.','ced-amazon-lister'); ?></p>
			<p><?php _e('2. Under "Profile Information" section\'s "BASIC" tab, you have option to setup basic information for your profile. Here you can give your profile a name and enable/disable it.','ced-amazon-lister'); ?></p>
			<p><?php _e('3. Under "Profile Information" section\'s "ADVANCE" tab, you have option to select marketplaces category, for which you want to create profile for. As soon as you select marketplace category, you are good to go to next sections. Sections below "Profile Information" are marketplace specific and depends upon the selected category of marketplace.','ced-amazon-lister'); ?></p>
			<p><?php _e('4. You can break done the steps of completing a profile in chunks by using unique save button provided in each section. At the same time, you can fill all data in one shot and press the final save button to complete profile in single step.','ced-amazon-lister'); ?></p>
			<p><?php _e('5. If you have read above instructions carefully, you are good to go.','ced-amazon-lister'); ?></p>
			
		</div>
	</div>
	<?php
	echo '<form method="post">';
	$products_IDs = array();
	$all_products = new WP_Query( 
		array(
			'post_type' => array('product', 'product_variation'),
			'post_status' => 'publish',
			'posts_per_page' => 10
			) 
		);
	$products = $all_products->posts;
	$selectedProID  = $all_products->posts['0']->ID;
	foreach ( $products as $product ) {
		$product_IDs[] = $product->ID;
	}
	
	if(isset($data['selected_product_id'])) {
		$selectedProID = $data['selected_product_id'];
		$selectedProName = $data['selected_product_name'];
	}
	else{
		$selectedProID = $product_IDs[0];
		$selectedProName = '';
	}
	
	?>
	<div class="ced_umb_amazon_toggle_section_wrapper">
		<div class="ced_umb_amazon_toggle">
			<span><?php _e('Select Product And Corresponding MetaKeys','ced-amazon-lister'); ?></span>	
		</div>	
		<div class="ced_umb_amazon_toggle_div" style="display:none;">
				<input type="hidden" name="profileID" id="profileID"value="<?php echo $profileID;?>">
				<div class="ced_umb_amazon_pro_search_div">
					<div class="ced_umb_amazon_inline_box">
						<label for="ced_umb_amazon_pro_search_box"><?php _e('Type Product Name Here','ced-amazon-lister'); ?></label>
						<div class="ced_umb_amazon_wrap_div">
							<input type="hidden" name="selected_product_id" id="selected_product_id" value="<?php echo $selectedProID;?>">
							<input type="text" autocomplete="off" id="ced_umb_amazon_pro_search_box" name="ced_umb_amazon_pro_search_box" placeholder="<?php _e('Product Name','ced-amazon-lister'); ?>" value="<?php echo $selectedProName; ?>"/>
							<div id="ced_umb_amazon_suggesstion_box" style="display: none;"></div>
						</div>
						<img class="ced_umb_amazon_ajax_pro_search_loader" src="<?php echo ced_umb_amazon_URL.'admin/images/ajax-loader.gif'?>" style="display: none;">
					</div>	
				</div>
				<?php renderMetaKeysTableOnProfilePageamazon($selectedProID); ?>
				<div class="">
					<p class="submit">
						<input class="button button-ced_umb_amazon" value="<?php _e('Save Meta Keys','ced-amazon-lister'); ?>" name="add_meta_keys" type="submit">
					</p>
				</div>
		</div>
	</div>

	<div class="ced_umb_amazon_toggle_section_wrapper">
		<div class="ced_umb_amazon_toggle">
			<span><?php _e('Profile Information','ced-amazon-lister'); ?></span>	
		</div>	
		<div class="ced_umb_amazon_toggle_div" style="display:none;">
			<div class="ced_umb_amazon_tabbed_head_wrapper">
				<ul>
					<li class="active"><?php _e('Basic','ced-amazon-lister'); ?></li>
					<li><?php _e('Advance','ced-amazon-lister'); ?></li>	
				</ul>
			</div>
			<div class="ced_umb_amazon_tabbed_section_wrapper">
				<div class="ced_umb_amazon_cmn active">
					<input type="hidden" name="profileID" id="profileID"value="<?php echo $profileID;?>">
					<table class="wp-list-table widefat fixed striped">
						<tbody>
						</tbody>
						<tbody>
							<tr>
								<th>
									<?php 
									_e('Profile Name','ced-amazon-lister'); 
									$attribute_description = __('Give a name to your profile here.','ced-amazon-lister');
									echo wc_help_tip( __( $attribute_description, 'ced-amazon-lister' ) ); 
									?>
								</th>
								<td>
									<input type="text" name="profile_name" value="<?php echo $profile_name; ?>">
									<span class="ced_umb_amazon_wal_required"><?php _e('[ Required ]','ced-amazon-lister')?></span>
								</td>
							</tr>
							<tr>
								<th>
									<?php
									_e('Enable Profile','ced-amazon-lister'); 
									$attribute_description = 'Make profile status enable/disable here.';
									echo wc_help_tip( __( $attribute_description, 'ced-amazon-lister' ) ); 
									?>
								</th>
								<?php $checked = ($enable == "yes") ? 'checked="checked"' : ''; ?>
								<td>
									<input type="checkbox" name="enable" id="ced_umb_amazon_enable_marketpalce" <?php echo $checked;?> > <label for="ced_umb_amazon_enable_marketpalce"><?php _e('Enable Profile','ced-amazon-lister');?></label>
								</td>
							</tr>
						</tbody>
						<tfoot>
						</tfoot>
					</table>
				</div>	
				<div class="ced_umb_amazon_cmn">
					<?php
					$pFieldInstance = ced_umb_amazon_product_fields::get_instance();
					if(is_wp_error($pFieldInstance)){
						$message = _e('Something went wrong please try again later!','ced-amazon-lister');
						wp_die($message);
					}
					$fields = $pFieldInstance->get_custom_fields('required',false);
					//print_r($fields);
					?>
					<table class="wp-list-table widefat fixed striped">
						<tbody>
						</tbody>
						<tbody>
							<?php
							$requiredInAnyCase = array('_umb_id_type','_umb_id_val','_umb_brand');
							global $global_ced_umb_amazon_Render_Attributes;
							$marketPlace = "ced_umb_amazon_required_common";
							$productID = 0;
							$categoryID = '';
							$indexToUse = 0;
							$selectDropdownHTML= renderMetaSelectionDropdownOnProfilePageamazon();
							foreach ($fields as $value) {
								$isText = true;
								$field_id = trim($value['fields']['id'],'_');
								if(in_array($value['fields']['id'], $requiredInAnyCase)) {
									$attributeNameToRender = ucfirst($value['fields']['label']);
									$attributeNameToRender .= '<span class="ced_umb_amazon_wal_required">'.__('[ Required ]','ced-amazon-lister').'</span>';
								}
								else {
									$attributeNameToRender = ucfirst($value['fields']['label']);
								}
								//$default = isset($data[$value['fields']['id']]) ? $data[$value['fields']['id']] : '';
								$default = isset($data[$value['fields']['id']]['default']) ? $data[$value['fields']['id']]['default'] : '';
								echo '<tr>';
								echo '<td>';
								if( $value['type'] == "_select" ) {
									$valueForDropdown = $value['fields']['options'];
									if($value['fields']['id'] == '_umb_id_type'){
										unset($valueForDropdown['null']);
									}
									$valueForDropdown = apply_filters('ced_umb_amazon_alter_data_to_render_on_profile', $valueForDropdown, $field_id);
									$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($field_id,$attributeNameToRender,$valueForDropdown,$categoryID,$productID,$marketPlace,$value['fields']['description'],$indexToUse,array('case'=>'profile','value'=>$default));
									$isText = false;
								}
								else if( $value['type'] == "_text_input" ) {

									// if( ucfirst($value['fields']['label']) == "Identifier Value" ) {
									// 	$value['fields']['label'] = '';
									// }
									$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($field_id,$attributeNameToRender,$categoryID,$productID,$marketPlace,$value['fields']['description'],$indexToUse,array('case'=>'profile','value'=>$default));
								}
								else {
									do_action('ced_umb_amazon_render_extra_data_on_profile', $value, $pFieldInstance);
									$isText = false;
								} 
								echo '</td>';
								echo '<td>';
								if($isText) {
									$previousSelectedValue = 'null';
									/* if( isset($data[$value['fields']['id'].'_attibuteMeta']) && $data[$value['fields']['id'].'_attibuteMeta'] != 'null') {
										$previousSelectedValue = $data[$value['fields']['id'].'_attibuteMeta'];
									} */
									if( isset($data[$value['fields']['id']]['metakey']) && $data[$value['fields']['id']]['metakey'] != 'null') {
										$previousSelectedValue = $data[$value['fields']['id']]['metakey'];
									}
									$updatedDropdownHTML = str_replace('{{*fieldID}}', $value['fields']['id'], $selectDropdownHTML);
									$updatedDropdownHTML = str_replace('value="'.$previousSelectedValue.'"', 'value="'.$previousSelectedValue.'" selected="selected"', $updatedDropdownHTML);
									echo $updatedDropdownHTML;
								}
								echo '</td>';
								echo '</tr>';
							}	
							?>
						</tbody>
						<tfoot>
						</tfoot>
					</table>
				</div>
			</div>	
			
		</div>
	</div>

	<?php
	$enableMarketPlaces = get_enabled_marketplacesamazon();
	foreach ($enableMarketPlaces as $marketPlaceKey) {
		echo '<div class="ced_umb_amazon_toggle_section_wrapper ced_umb_amazon_'.$marketPlaceKey.'_attribute_section">';
			echo '<div class="ced_umb_amazon_toggle">';
				echo '<span>'.strtoupper($marketPlaceKey).'</span>';	
			echo '</div>';
			echo '<div class="ced_umb_amazon_toggle_div" style="display:none;">';
				?>
				<div class="ced_umb_amazon_tabbed_head_wrapper">
					<ul>
						<li class="active">Category Specific</li>
						<!-- <li>Framework Specific</li>	 -->
					</ul>
				</div>
				<div class="ced_umb_amazon_tabbed_section_wrapper">
					<div id="ced_umb_amazon_<?php echo $marketPlaceKey; ?>_attribute_section_id">
					</div>	
				</div>
				
				<?php
			echo '</div>';
		echo '</div>';
	}
	?>
	<div class="">
		<p class="ced_umb_amazon_button_right">
			<input class="button button-ced_umb_amazon" value="<?php _e('Save Profile','ced-amazon-lister'); ?>" name="saveProfile" type="submit">
		</p>
	</div>
	<?php
	echo '</form>';
echo '</div>';
?>
