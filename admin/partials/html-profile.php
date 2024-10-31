<div class="ced_umb_amazon_overlay">
	<div class = "ced_umb_amazon_hidden_profile_section ced_umb_amazon_wrap">
		<p class="ced_umb_amazon_button_right">
			<span class="ced_umb_amazon_overlay_cross ced_umb_amazon_white_txt">X</span>
		<p>
		<h2 class="ced_umb_amazon_setting_header"><?php _e("Select profile for this product","ced-amazon-lister");?></h2>
		<label class="ced_umb_amazon_white_txt"><?php _e('Available Profile','ced-amazon-lister');?></label>
			<?php 
			global $wpdb;
			$table_name = $wpdb->prefix.ced_umb_amazon_PREFIX.'profiles';
			$query = "SELECT `id`, `name` FROM `$table_name` WHERE `active` = 1";
			$profiles = $wpdb->get_results($query,'ARRAY_A');
			if(count($profiles)){?>
			<select class="ced_umb_amazon_profile_select">
				<option value="0"> --<?php _e('select','ced-amazon-lister')?>-- </option>
			<?php 
				foreach($profiles as $profileInfo){
					$profileId = isset($profileInfo['id']) ? intval($profileInfo['id']) : 0;
					$profileName = isset($profileInfo['name']) ? $profileInfo['name'] : '';
					if($profileId){
						?>
						<option value = "<?php echo $profileId; ?>"><?php echo $profileName; ?></option>
						<?php 
					}
				}
				?>
				</select>
				<button type = "button" data-prodid = "" class="ced_umb_amazon_save_profile button button-ced_umb_amazon"><?php _e("Save profile","ced-amazon-lister")?></button>
				<?php 
			}else{
			?>
			<p class="ced_umb_amazon_white_txt"><?php _e('No any profile available to assign, please create and enable profile and came back to assing!','ced-amazon-lister')?></p>
		<?php }?>
	</div>
</div>