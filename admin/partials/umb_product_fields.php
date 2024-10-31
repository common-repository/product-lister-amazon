<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$required_fields = $this->get_custom_fields('required',false);
//print_r($required_fields);
$extra_fields = $this->get_custom_fields('extra',false);
//print_r($extra_fields);
$framework_fields = $this->get_custom_fields('framework_specific',false);
//print_r($framework_fields);
?>
<div id="ced_umb_amazon_fields" class="panel woocommerce_options_panel">
	<div id="ced_umb_amazon_accordian">
	<!-- --------------------------Required fields-------------------------------- -->
		<?php if(count($required_fields)) : ?>
		<div class="ced_umb_amazon_panel">
			<div class="ced_umb_amazon_panel_heading">
				<h4><?php _e('Required Fields','ced-amazon-lister'); ?></h4>
			</div>
			<div class="ced_umb_amazon_collapse">
				<div class="options_group ced_umb_amazon_label_data">
				<?php 

				$requiredInAnyCase = array('_amazon_umb_id_type','_amazon_umb_id_val','_amazon_umb_brand');
							
				

				foreach ($required_fields as $field_array):
					if(isset($field_array['id']) && isset($field_array['type']) && isset($field_array['fields'])){
						$type = esc_attr($field_array['type']);
						$fields = is_array($field_array['fields']) ? $field_array['fields'] : array();
						$id = isset($fields['id']) ? $fields['id'] : isset($field_array['id']) ? $field_array['id'] : '';
						$label = isset($fields['label']) ? esc_attr($fields['label']) : '';
						
						if($type=='_amazon_umb_select'){
							global $post;
							
							$optionValue = get_post_meta($post->ID,$id,true);
							
							$options = isset($fields['options']) ? $fields['options'] : array();
							$optionsHtml = '';
							$optionsHtml .= '<option value="null">'.__('Amazon Subcategory','ced-amazon-lister').'</option>';
							if(is_array($options)){
								foreach($options as $industry => $subcats){
									
									if(is_array($subcats)){
										$optionsHtml .= '<option value="null" class="umb_parent" disabled>'.$industry.'</option>';
										foreach($subcats as $Sid => $name){
											
											$optionsHtml .= '<option value="'.$Sid.'" "'.selected($optionValue,$Sid,false).'">'.$name.'</option>';
										}
									}
								}
							}

							
							echo '<p class="form-field '.$id.'">';
								echo '<label for="'.$id.'">'.$label.'</label>';
								echo '<select name="'.$id.'" id="'.$id.'">';
									echo $optionsHtml;
								echo '</select>';
							echo '</p>';
							
							
						}else{
							
								
							if(in_array($fields['id'], $requiredInAnyCase)) {
								$nameToRender = ucfirst($fields['label']);
								$nameToRender .= '<span class="ced_umb_amazon_wal_required"> [ Required ]</span>';
								$fields['label'] = $nameToRender;
							}
							
							$function_name = "woocommerce_wp$type";
							if(function_exists($function_name))
								$function_name($fields);
							
							
						}
						
						//amazon
						do_action("ced_umb_amazon_product_page_render", $type, $field_array);
						
					}
				endforeach;
				?>
				</div>
			</div>
		</div>
		<?php endif; ?>
	<!-- --------------------------End of Required fields-------------------------------- -->
	<!-- --------------------------Framework Specific fields-------------------------------- -->	
		<?php if(count($framework_fields)) :?>
		<div class="ced_umb_amazon_panel">
			<div class="ced_umb_amazon_panel_heading">
				<h4><?php _e('Framework Specific Fields','ced-amazon-lister'); ?></h4>
			</div>
			<div class="ced_umb_amazon_collapse">
			<?php foreach($framework_fields as $fname=> $ffields_details): ?>
				<?php if(count($ffields_details)) :?>
				<div class="ced_umb_amazon_sub_accordion">
					<div class="ced_umb_amazon_sub_panel">
						<div class="ced_umb_amazon_sub_panel_heading">	
							<h4><?php echo esc_attr($fname); ?></h4>
						</div>
						<div class="ced_umb_amazon_sub_collapse">
						<?php 
							foreach($ffields_details as $ffields_array){
								if(isset($ffields_array['id']) && isset($ffields_array['type']) && isset($ffields_array['fields'])){
									$ftype = esc_attr($ffields_array['type']);
									$ffields = is_array($ffields_array['fields']) ? $ffields_array['fields'] : array();
									$ffunction_name = "woocommerce_wp$ftype";

									
									if(function_exists($ffunction_name))
									$ffunction_name($ffields);
									
								}
							}
						?>
						</div>
					</div>
				</div>
				<?php endif;?>
			<?php endforeach;?>
			</div>
		</div>
		<?php endif;?>
		<!-- -------------------------- End of framework specific fields-------------------------------- -->
		<!-- --------------------------Extra fields-------------------------------- -->
		<?php if(count($extra_fields)) :?>
		<div class="ced_umb_amazon_panel">
			<div class="ced_umb_amazon_panel_heading">
				<h4><?php _e('Extra Fields','ced-amazon-lister'); ?></h4>
			</div>
			<div class="ced_umb_amazon_collapse">
				<div class="options_group ced_umb_amazon_label_data">
					<?php 
					foreach($extra_fields as $efield_array):
						if(isset($efield_array['id']) && isset($efield_array['type']) && isset($efield_array['fields'])){
							$etype = esc_attr($efield_array['type']);
							$efields = is_array($efield_array['fields']) ? $efield_array['fields'] : array();
							$efunction_name = "woocommerce_wp$etype";
							
							
							if(function_exists($efunction_name)) {
								$efunction_name($efields);
							}
							else{
								switch($etype){
									case 'lwh' :
										$id = esc_attr($efield_array['id']);
										$label = isset($efields['label']) ? esc_attr( $efields['label'] ) : '';
										$desc_tip = isset($efields['desc_tip']) ? $efields['desc_tip'] : 0;
										$desc = isset($efields['description']) ? $efields['description'] : 0;
										?><p class="form-field dimensions_field">
											<label for="<?php $id; ?>"><?php echo $label . ' (' . get_option( 'woocommerce_dimension_unit' ) . ')'; ?></label>
											<span class="wrap">
												<input id="<?php $id; ?>" placeholder="<?php esc_attr_e( 'Length', 'ced-amazon-lister' ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="<?php echo $id ?>_length" value="<?php echo esc_attr( wc_format_localized_decimal( get_post_meta( $post->ID, $id.'_length', true ) ) ); ?>" />
												<input placeholder="<?php esc_attr_e( 'Width', 'ced-amazon-lister' ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="<?php echo $id ?>_width" value="<?php echo esc_attr( wc_format_localized_decimal( get_post_meta( $post->ID, $id.'_width', true ) ) ); ?>" />
												<input placeholder="<?php esc_attr_e( 'Height', 'ced-amazon-lister' ); ?>" class="input-text wc_input_decimal last" size="6" type="text" name="<?php echo  $id ?>_height" value="<?php echo esc_attr( wc_format_localized_decimal( get_post_meta( $post->ID, $id.'_height', true ) ) ); ?>" />
											</span>
											<?php if(isset($desc_tip)): echo wc_help_tip($desc); endif;?>
										</p><?php
										break;
								}
							}
							
						}
					endforeach; ?>
				</div>
			</div>
		</div>
		<?php endif;?>
		<!-- --------------------------End of extra fields-------------------------------- -->
	</div>
</div>