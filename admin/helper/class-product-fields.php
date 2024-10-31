<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * single product managment related functionality helper class.
 *
 * @since      1.0.0
 *
 * @package    Amazon Product Lister
 /helper
 */

if( !class_exists( 'ced_umb_amazon_product_fields' ) ) :

/**
 * single product related functionality.
 *
 * Manage all single product related functionality required for listing product on marketplaces.
 *
 * @since      1.0.0
 * @package    Amazon Product Lister
 /helper
 * @author     CedCommerce <cedcommerce.com>
 */
class ced_umb_amazon_product_fields{
	
	/**
	 * The Instace of ced_umb_amazon_product_fields.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      $_instance   The Instance of ced_umb_amazon_product_fields class.
	 */
	private static $_instance;
	
	/**
	 * ced_umb_amazon_product_fields Instance.
	 *
	 * Ensures only one instance of ced_umb_amazon_product_fields is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return ced_umb_amazon_product_fields instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	 * Adding tab on product edit page.
	 * 
	 * @since 1.0.0
	 * @param array   $tabs   single product page tabs.
	 * @return array  $tabs
	 */
	public function umb_required_fields_tab( $tabs ){
		
		$tabs['umb_amazon_required_fields'] = array(
			'label'  => __( 'Amazon', 'ced-amazon-lister' ),
			'target' => 'ced_umb_amazon_fields',
			'class'  => array( 'show_if_simple','ced_umb_amazon_required_fields' ),
		);
			
		return $tabs;
	}
	
	/**
	 * Fields on UMB Required Fields product edit page tab.
	 * 
	 * @since 1.0.0
	 */
	public function umb_required_fields_panel() {
		
		global $post;
		
		if ( $terms = wp_get_object_terms( $post->ID, 'product_type' ) ) {
				$product_type = sanitize_title( current( $terms )->name );
		} else {
				$product_type = apply_filters( 'default_product_type', 'simple' );
		}
		
		if($product_type == 'simple' ){
			require_once ced_umb_amazon_DIRPATH.'admin/partials/umb_product_fields.php';
		}
	}

	

	
	
	/**
	 * processing product meta required fields for listing
	 * product on marketplace.
	 * 
	 * @since 1.0.0
	 * @var int  $post_id
	 */
	public function umb_required_fields_process_meta( $post_id ){
		
		if($_POST['product-type'] != 'variable') {

			$required_fields_ids = $this->get_custom_fields('required',true);
			$extra_fields_ids = $this->get_custom_fields('extra',true);
			$framework_fields = array();
			$framework_fields_ids = array();
			
			$framework_fields = $this->get_custom_fields('framework_specific',false);
			if(count($framework_fields)){
				foreach($framework_fields as $fields_data){
					if(is_array($fields_data)){
						foreach($fields_data as $fields_array){
							if(isset($fields_array['id']))
								$framework_fields_ids[] = esc_attr($fields_array['id']);
						}
					}
				}
			}
			$all_fields = array();
			$all_fields = array_merge($required_fields_ids,$extra_fields_ids,$framework_fields_ids );
			
			foreach($all_fields as $field_name){
				if(isset($_POST[$field_name]))
					update_post_meta( $post_id, $field_name, sanitize_text_field( $_POST[$field_name] ) );
				else 
					update_post_meta( $post_id, $field_name, false);
			}

			do_action( 'ced_umb_amazon_required_fields_process_meta_simple', $post_id );
		}
	}

	/**
	 * get product custom fields for preparing
	 * product data information to send on different
	 * marketplaces accoding to there requirement.
	 * 
	 * @since 1.0.0
	 * @param string  $type  required|framework_specific|common
	 * @param bool	  $ids  true|false
	 * @return array  fields array
	 */
	public static function get_custom_fields( $type, $is_fields=false ){
		global $post;
		$fields = array();
		
		if($type=='required'){
			
			$required_fields = array(
					array(
							'type' => '_select',
							'id' => '_amazon_umb_id_type',
							'fields' => array(
									'id' => '_amazon_umb_id_type',
									'label' => __( 'Identifier Type', 'ced-amazon-lister' ),
									'options' => array(
											'null' => __('--select--','ced-amazon-lister'),
											'ASIN' => __( 'ASIN', 'ced-amazon-lister' ),
											'UPC' => __( 'UPC', 'ced-amazon-lister' ),
											'EAN' => __( 'EAN', 'ced-amazon-lister' ),
											'ISBN-10' => __( 'ISBN-10', 'ced-amazon-lister' ),
											'ISBN-13' => __( 'ISBN-13', 'ced-amazon-lister' ),
											'GTIN-14' => __( 'GTIN-14', 'ced-amazon-lister' ),
									),
									'desc_tip' => true,
									'description' => __( 'Unique identifier type your product must have to list on marketplaces. Note: for Amazon please select UPC only and if you want to select ISBN then please insert it into manufacturer part number field below.', 'ced-amazon-lister' ),
							),
					),
					array(
							'type' => '_text_input',
							'id' => '_amazon_umb_id_val',
							'fields' => array(
									'id'      	  => '_amazon_umb_id_val',
									'label'       => __( 'Identifier Value', 'ced-amazon-lister' ),
									'desc_tip'    => true,
									'description' => __( 'Identifier value, for the selected "Identifier Type" above.', 'ced-amazon-lister' ),
							),
					),
					array(
							'type' => '_text_input',
							'id' => '_amazon_umb_brand',
							'fields' => array(
									'id'            => '_amazon_umb_brand',
									'label'         => __( 'Product Brand', 'ced-amazon-lister' ),
									'desc_tip'      => true,
									'description'   => __( 'Product brand for sending on marketplaces.', 'ced-amazon-lister' ),
							),
					),
					array(
							'type' => '_text_input',
							'id' => '_amazon_umb_manufacturer',
							'fields' => array(
									'id'                => '_amazon_umb_manufacturer',
									'label'             => __( 'Product Manufacturer', 'ced-amazon-lister' ),
									'desc_tip'          => true,
									'description'       => __( 'Manufacturer of the product.', 'ced-amazon-lister' ),
							),
					),
					array(
							'type' => '_text_input',
							'id' => '_amazon_umb_mpr',
							'fields' => array(
									'id'                => '_amazon_umb_mpr',
									'label'             => __( 'Manufacturer Part Number', 'ced-amazon-lister' ),
									'desc_tip'          => true,
									'description'       => __( 'Manufacturer defined unique identifier for an item. An alphanumeric string, max 20 characters including space.', 'ced-amazon-lister' ),
							),
					),
					array(
							'type' => '_text_input',
							'id' => '_amazon_umb_packsorsets',
							'fields' => array(
									'id'                => '_amazon_umb_packsorsets',
									'label'             => __( 'Packs Or Sets', 'ced-amazon-lister' ),
									'desc_tip'          => true,
									'description'       => __( 'Identify the package count of this product.', 'ced-amazon-lister' ),
									'type'				=> 'number'
							),
					),
			);
			
			$fields = is_array( apply_filters('ced_umb_amazon_required_product_fields', $required_fields, $post) ) ? apply_filters('ced_umb_amazon_required_product_fields', $required_fields, $post) : array() ;
		}
		else if($type=='extra'){
			$extra_fields = array(
					array(
							'type' => '_text_input',
							'id' => '_amazon_umb_price',
							'fields' => array(
									'id'                => '_amazon_umb_price',
									'label'             => __( 'Marketplace Product Price', 'ced-amazon-lister' ),
									'desc_tip'          => true,
									'description'       => __( 'Product price you want to send to the marketplaces.', 'ced-amazon-lister' ),
									'type'              => 'number',
							),
					),
					array(
							'type' => '_text_input',
							'id' => '_amazon_umb_stock',
							'fields' => array(
									'id'                => '_amazon_umb_stock',
									'label'             => __( 'Marketplace Product Stock', 'ced-amazon-lister' ),
									'desc_tip'          => true,
									'description'       => __( 'Number of product quantity you want to send on marketplaces.', 'ced-amazon-lister' ),
									'type'              => 'number',
							),
					),
					array(
							'type' => '_text_input',
							'id' => '_amazon_umb_coo',
							'fields' => array(
									'id'                => '_amazon_umb_coo',
									'label'             => __( 'Country Of Origin', 'ced-amazon-lister' ),
									'desc_tip'          => true,
									'description'       => __( 'The country that the item was manufactured in.', 'ced-amazon-lister' ),
							),
					),
					array(
							'type' => '_checkbox',
							'id' => '_amazon_umb_prop65',
							'fields' => array(
									'id'                => '_amazon_umb_prop65',
									'label'             => __( 'Prop 65', 'ced-amazon-lister' ),
									'desc_tip'          => true,
									'description'       => __( 'Check this if your product is subject to Proposition 65 rules and regulations
								Proposition 65 requires merchants to provide California consumers with special warnings for products
								that contain chemicals known to cause cancer, birth defects, or other reproductive harm, if those products
								expose consumers to such materials above certain threshold levels..', 'ced-amazon-lister' ),
							),
					),
					array(
							'type' => '_select',
							'id' => '_amazon_umb_cpsia_cause',
							'fields' => array(
									'id' => '_amazon_umb_cpsia_cause',
									'label' => __( 'CPSIA cautionary Statements', 'ced-amazon-lister' ),
									'options' => array(
											'0' => __( 'no warning applicable', 'ced-amazon-lister' ),
											'1' => __( 'choking hazard small parts', 'ced-amazon-lister' ),
											'2' => __( 'choking hazard is a small ball', 'ced-amazon-lister' ),
											'3' => __( 'choking hazard is a marble', 'ced-amazon-lister' ),
											'4' => __( 'choking hazard contains a small ball', 'ced-amazon-lister' ),
											'5' => __( 'choking hazard contains a marble', 'ced-amazon-lister' ),
											'6' => __( 'choking hazard balloon', 'ced-amazon-lister' ),
									),
									'desc_tip' => true,
									'description' => __( 'Use this field to indicate if a cautionary statement relating to the choking hazards of children\'s
								toys and games applies to your product. These cautionary statements are defined in Section 24 of the Federal Hazardous
								Substances Act and Section 105 of the Consumer Product Safety Improvement Act of 2008. They must be displayed on the
								product packaging and in certain online and catalog advertisements.', 'ced-amazon-lister' )
							),
					),
					array(
							'type' => '_text_input',
							'id' => '_amazon_umb_safety_warning',
							'fields' => array(
									'id'                => '_amazon_umb_safety_warning',
									'label'             => __( 'Safety Warning', 'ced-amazon-lister' ),
									'desc_tip'          => true,
									'description'       => __( 'If applicable, use to supply any associated warnings for your product.', 'ced-amazon-lister' ),
							),
					),
					array(
							'type' => '_text_input',
							'id' => '_amazon_umb_msrp',
							'fields' => array(
									'id'                => '_amazon_umb_msrp',
									'label'             => __( 'Manufacturer\'s Suggested Retail Price', 'ced-amazon-lister' ),
									'desc_tip'          => true,
									'description'       => __( 'The manufacturer\'s suggested retail price or list price for the product.', 'ced-amazon-lister' ),
									'type'              => 'number',
							),
					),
					array(
							'type' => '_text_input',
							'id' => '_amazon_umb_map',
							'fields' => array(
									'id'                => '_amazon_umb_map',
									'label'             => __( 'Minimum advertised price', 'ced-amazon-lister' ),
									'desc_tip'          => true,
									'description'       => __( 'The default Minimum advertised price for the United States. If the Selling Price is below the defined MAP, the website will ask customer to add item to shopping cart to see the item\'s price. If you want to remove MAP, input �0.00� or �0� in this field. If null, no change to current setting. 
N .', 'ced-amazon-lister' ),
									'type'              => 'number',
							),
					),
					array(
							'type' => '_text_input',
							'id' => '_amazon_umb_bullet_1',
							'fields' => array(
									'id'                => '_amazon_umb_bullet_1',
									'label'             => __( 'Bullet 1', 'ced-amazon-lister' ),
									'desc_tip'          => true,
									'description'       => __( 'bullet points of this product.', 'ced-amazon-lister' ),
							),
					),
					array(
							'type' => '_text_input',
							'id' => '_amazon_umb_bullet_2',
							'fields' => array(
									'id'                => '_amazon_umb_bullet_2',
									'label'             => __( 'Bullet 2', 'ced-amazon-lister' ),
									'desc_tip'          => true,
									'description'       => __( 'bullet points of this product.', 'ced-amazon-lister' ),
							),
					),
					array(
							'type' => '_text_input',
							'id' => '_amazon_umb_bullet_3',
							'fields' => array(
									'id'                => '_amazon_umb_bullet_3',
									'label'             => __( 'Bullet 3', 'ced-amazon-lister' ),
									'desc_tip'          => true,
									'description'       => __( 'bullet points of this product.', 'ced-amazon-lister' ),
							),
					),
					array(
							'type' => '_text_input',
							'id' => '_amazon_umb_bullet_4',
							'fields' => array(
									'id'                => '_amazon_umb_bullet_4',
									'label'             => __( 'Bullet 4', 'ced-amazon-lister' ),
									'desc_tip'          => true,
									'description'       => __( 'bullet points of this product.', 'ced-amazon-lister' ),
							),
					),
					array(
							'type' => '_text_input',
							'id' => '_amazon_umb_bullet_5',
							'fields' => array(
									'id'                => '_amazon_umb_bullet_5',
									'label'             => __( 'Bullet 5', 'ced-amazon-lister' ),
									'desc_tip'          => true,
									'description'       => __( 'bullet points of this product.', 'ced-amazon-lister' ),
							),
					),
					array(
							'type' => '_checkbox',
							'id' => '_amazon_umb_overage18verification',
							'fields' => array(
									'id'                => '_amazon_umb_overage18verification',
									'label'             => __( 'Over 18 Age Verification', 'ced-amazon-lister' ),
									'desc_tip'          => true,
									'description'       => __( 'Used if the product contains graphics or adult content that is inappropriate for person under 18 years old.', 'ced-amazon-lister' ),
							),
					),
			);
			//let us decide the other fields depends on the marketplaces added in the future.
			$fields = is_array( apply_filters('ced_umb_amazon_extra_product_fields', $extra_fields, $post) ) ? apply_filters('ced_umb_amazon_extra_product_fields', $extra_fields, $post) : array() ;
		}
		else if($type=='framework_specific'){
				
			$framework_fields = array();
			$fields = is_array( apply_filters('ced_umb_amazon_framework_product_fields', $framework_fields, $post) ) ? apply_filters('ced_umb_amazon_framework_product_fields', $framework_fields, $post) : array() ;
			return $fields;
		}
		if($is_fields){
			$fields_array = array();
			if(is_array($fields)){
		
				foreach($fields as $field_data){
					$fieldID = isset($field_data['id']) ? esc_attr($field_data['id']) : null;
					if(!is_null($fieldID))
						$fields_array[] = $fieldID;
				}
				return $fields_array;
			}else{
				return array();
			}
				
		}else{
			if(is_array($fields)){
				return $fields;
			}else{
				return array();
			}
		}
	}

	/**
	 * Custom fields html.
	 * 
	 * @since 1.0.0
	 * @param array
	 */
	public function custom_field_html($fieldsArray){
		if(is_array($fieldsArray)){
			foreach($fieldsArray as $data){
				$type = isset($data['type']) ? esc_attr($data['type']) : '_text_input';
				$fields = isset($data['fields']) ? is_array($data['fields']) ? $data['fields'] : array() : array();
				$label = isset($fields['label']) ? esc_attr($fields['label']) : '';
				$description = isset($fields['description']) ? esc_attr($fields['description']) : '';
				$desc_tip = isset($fields['desc_tip']) ? intval($fields['desc_tip']) : !empty($description) ? 1 : 0;
				$fieldvalue = isset($fields['value']) ? $fields['value'] : null;
				echo '<div class="ced_umb_amazon_profile_field">';
				echo '<label class="ced_umb_amazon_label">';
				echo '<span>'.$label.'</span>';
				echo '</label>';
				switch($type){
					case '_select':
						$id = isset($fields['id']) ? esc_attr($fields['id']) : isset($data['id']) ? esc_attr($data['id']) : null;
						if(!is_null($id)){
							$select_values = isset($fields['options']) ? is_array($fields['options']) ? $fields['options'] : array() : array();
		
							echo '<select name="'.$id.'" id="'.$id.'">';
							if(is_array($select_values)){
								foreach($select_values as $key=>$value){
									echo '<option value="'.$key.'"'.selected($fieldvalue,$key,false).'>';
									echo $value;
									echo '</option>';
								}
							}
							echo '</select>';
						}
						break;
					case '_text_input':
						$id = isset($fields['id']) ? esc_attr($fields['id']) : isset($data['id']) ? esc_attr($data['id']) : null;
						if(!is_null($id)){
							echo '<input type="text" id="'.$id.'" name="'.$id.'" value="'.$fieldvalue.'">';
						}
						break;
					case '_checkbox':
						$id = isset($fields['id']) ? esc_attr($fields['id']) : isset($data['id']) ? esc_attr($data['id']) : null;
						if(!is_null($id)){
							echo '<input type="checkbox" id="'.$id.'" name="'.$id.'" '.checked($fieldvalue,'on').'>';
						}
						break;
					case '_amazon_umb_select':
						$id = isset($fields['id']) ? esc_attr($fields['id']) : isset($data['id']) ? esc_attr($data['id']) : null;
						$options = isset($fields['options']) ? $fields['options'] : array();
						$optionsHtml = '';
						$optionsHtml .= '<option value="null">'.__('--select--','ced-amazon-lister').'</option>';
						if(is_array($options)){
							foreach($options as $industry => $subcats){
									
								if(is_array($subcats)){
									$optionsHtml .= '<option value="null" class="umb_parent" disabled>'.$industry.'</option>';
									foreach($subcats as $subcatid => $name){
											
										$optionsHtml .= '<option value="'.$subcatid.'" '.selected($fieldvalue,$subcatid,false).'>'.$name.'</option>';
									}
								}
							}
						}
						echo '<p class="form-field '.$id.'">';
						echo '<select name="'.$id.'" id="'.$id.'">';
						echo $optionsHtml;
						echo '</select>';
						echo '</p>';
						break;
				}
				echo '</div>';
			}
		}
	}

	/**
	 * Quick edit save product data from manage product
	 * page of umb so that admin can quickly change the product
	 * entries and upload them to selected marketplace with minimal
	 * required changes.
	 * 
	 * @since 1.0.0
	 * @param int $post_id
	 * @param object $post
	 */
	public function quick_edit_save_data( $post_id, $post ){
		
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		
		// Don't save revisions and autosaves
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}
		
		// Check post type is product
		if ( 'product' != $post->post_type && 'product_variation' != $post->post_type ) {
			return $post_id;
		}
		
		// Check user permission
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
		
		// Check nonces
		if ( ! isset( $_REQUEST['ced_umb_amazon_quick_edit_nonce'] ) && ! isset( $_REQUEST['ced_umb_amazon_quick_edit_nonce'] ) ) {
			return $post_id;
		}
		
		// Get the product and save
		$product = wc_get_product( $post );
		
		if ( ! empty( $_REQUEST['ced_umb_amazon_quick_edit'] ) ) {
			$request_data = $_REQUEST;
			$this->process_quick_edit($request_data,$product);
			$this->response_updated_product_html( $post, $product );
		} 
		
		// Clear transient
		wc_delete_product_transients( $post_id );
		
		wp_die();
	}
	
	/**
	 * processing the data edited by admin
	 * from quick edit link of product listed in
	 * manage product page of UMB.
	 * 
	 * @since 1.0.0
	 * @param array $request_data
	 * @param object $product
	 */
	public function process_quick_edit($data,$product){
		
		$product_id = isset($product->variation_id) ? intval($product->variation_id) : 0;
		if(!$product_id) {
			$product_id = isset($product->id) ? intval($product->id) : 0;
		}
		if(!$product_id){
			return;
		}
		$required_fields = $this->get_custom_fields('required',true);
		if(!is_array($required_fields))
			return;
		
		$required_fields[] = '_sku';

		foreach($data as $key=>$value){
			$key = esc_attr($key);
			$value = sanitize_text_field($value);
			if(in_array($key,$required_fields)){
				update_post_meta($product_id,$key,$value);
			}
		}
	}
	
	/**
	 * updated product html after quick edit 
	 * for listing on manage products page of UMB.
	 * 
	 * @since 1.0.0
	 */
	public function response_updated_product_html($post, $product){
		
		if(!class_exists('ced_umb_amazon-product-lister')){
			require_once ced_umb_amazon_DIRPATH.'admin/helper/class-ced-umb-product-listing.php';
			$product_lister = new ced_umb_amazon-product-lister();
			if($post->post_type == 'product_variation') {
				$variation_id = $post->ID;
				$post = get_post($post->post_parent);
				return $product_lister->get_product_row_html_variation($post,$variation_id);
			}
			else {
				return $product_lister->get_product_row_html($post);
			}
		}
		return $post->ID;
	}
}

endif;
