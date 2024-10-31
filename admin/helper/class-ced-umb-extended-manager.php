<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds extended functionality as needed in core plugin.
 *
 * @class    ced_umb_amazon_Extended_Manager
 * @version  1.0.0
 * @category Class
 * @author   CedCommerce
 */

class ced_umb_amazon_Extended_Manager {

	public function __construct() {
		$this->ced_umb_amazon_extended_manager_add_hooks_and_filters();
	}
	
	/**
	 * This function hooks into all filters and actions available in core plugin.
	 * @name ced_umb_amazon_extended_manager_add_hooks_and_filters()
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @link  http://www.cedcommerce.com/
	 */
	public function ced_umb_amazon_extended_manager_add_hooks_and_filters() {
		add_action('admin_enqueue_scripts',array($this,'ced_umb_amazon_extended_manager_admin_enqueue_scripts'));
		add_action('wp_ajax_fetch_all_meta_keys_related_to_selected_product', array($this,'fetch_all_meta_keys_related_to_selected_product'));
		add_action('wp_ajax_ced_umb_amazon_searchProductAjaxify', array($this,'ced_umb_amazon_searchProductAjaxify'));
	

		

		
		add_action( 'wp_ajax_ced_umb_amazon_updateMetaKeysInDBForProfile', array($this,'ced_umb_amazon_updateMetaKeysInDBForProfile' ));
	
		//adding cron timing
		add_filter('cron_schedules',array($this,'my_cron_schedules'));
	
		/*
		* Queue Upload AJAX Request Handling
		*/
		add_action( 'wp_ajax_ced_umb_amazon_render_queue_upload_main_section', array($this,'ced_umb_amazon_render_queue_upload_main_section' ));
		add_action( 'wp_ajax_ced_umb_amazon_add_product_to_upload_queue_on_marketplace', array($this,'ced_umb_amazon_add_product_to_upload_queue_on_marketplace' ));
	
	}
	
	function ced_umb_amazon_render_queue_upload_main_section() {
		$selectedMarketPlace = isset($_POST['marketplaceId'])?sanitize_text_field($_POST['marketplaceId']):'';
		if( $selectedMarketPlace ) {
			$items_in_queue = get_option( 'ced_umb_amazon_'.$selectedMarketPlace.'_upload_queue', array() );
			$items_count = count($items_in_queue);
			$ced_umb_amazon_delete_queue_after_upload = get_option( 'ced_umb_amazon_delete_queue_after_upload_'.$selectedMarketPlace, 'no' );
			if( $ced_umb_amazon_delete_queue_after_upload == 'yes' ) {
				$ced_umb_amazon_delete_queue_after_upload = 'checked="checked"';
			}
			else {
				$ced_umb_amazon_delete_queue_after_upload = '';
			}	
			?>
			<div class="ced_umb_amazon_queue_upload_main_section">
				<h3 class="ced_umb_amazon_white_txt"><?php echo __('There are ','ced-amazon-lister').$items_count.__(' items in your queue to upload.','ced-amazon-lister'); ?></h3>
				<h4 class="ced_umb_amazon_white_txt">
					<input type="checkbox" name="ced_umb_amazon_delete_queue_after_upload" id="ced_umb_amazon_delete_queue_after_upload" <?php echo $ced_umb_amazon_delete_queue_after_upload;?> >
					<label for="ced_umb_amazon_delete_queue_after_upload"><?php echo __('Delete Queue After Uplaod.','ced-amazon-lister'); ?></label>
				</h4>
				<p>
					<input type="submit" name="ced_umb_amazon_queue_upload_button" class="button button-ced_umb_amazon" value="<?php _e( 'Upload', 'ced-amazon-lister' ); ?>">
				</p>
			</div>
			<?php
		}
		wp_die();
	}

	function ced_umb_amazon_add_product_to_upload_queue_on_marketplace() {
		$marketplaceId = isset($_POST['marketplaceId'])?sanitize_text_field($_POST['marketplaceId']):'';
		$items_in_queue = get_option( 'ced_umb_amazon_'.$marketplaceId.'_upload_queue', array() );
		$productId = isset($_POST['productId'])?sanitize_text_field($_POST['productId']):'';
		if( in_array($productId, $items_in_queue)) {
			unset($items_in_queue[$productId]);
		}
		else {
			$items_in_queue[$productId] = $productId;
		}
		update_option( 'ced_umb_amazon_'.$marketplaceId.'_upload_queue', $items_in_queue );
		wp_die();
	}

	function ced_umb_amazon_updateMetaKeysInDBForProfile() {
		$metaKey 	=	 sanitize_text_field($_POST['metaKey']);
		$actionToDo 	=	sanitize_text_field($_POST['actionToDo']);
		$allMetaKeys = get_option('CedUmbProfileSelectedMetaKeys', array());
		if($actionToDo == 'append') {
			if(!in_array($metaKey, $allMetaKeys)){
				$allMetaKeys[] = $metaKey;
			}
		}
		else{
			
			if(in_array($metaKey, $allMetaKeys)){
				if(($key = array_search($metaKey, $allMetaKeys)) !== false) {
				    unset($allMetaKeys[$key]);
				}
			}
		}
		update_option('CedUmbProfileSelectedMetaKeys', $allMetaKeys);
		wp_die();
		
	}

	function my_cron_schedules($schedules){
	    if(!isset($schedules["ced_umb_amazon_6min"])){
	        $schedules["ced_umb_amazon_6min"] = array(
	            'interval' => 10,
	            'display' => __('Once every 6 minutes','ced-amazon-lister'));
	    }
	    if(!isset($schedules["ced_umb_amazon_10min"])) {
	        $schedules["ced_umb_amazon_10min"] = array(
	            'interval' => 10*60,
	            'display' => __('Once every 10 minutes','ced-amazon-lister'));
	    }
	    if(!isset($schedules["ced_umb_amazon_15min"])){
	        $schedules["ced_umb_amazon_15min"] = array(
	            'interval' => 15*60,
	            'display' => __('Once every 15 minutes','ced-amazon-lister'));
	    }
	    if(!isset($schedules["ced_umb_amazon_30min"])){
	        $schedules["ced_umb_amazon_30min"] = array(
	            'interval' => 30*60,
	            'display' => __('Once every 30 minutes','ced-amazon-lister'));
	    }
	    return $schedules;
	}

	function ced_umb_amazon_searchProductAjaxify( $x='',$post_types = array( 'product' ) ) {
		global $wpdb;
		
		ob_start();
		
		$term = (string) wc_clean( stripslashes( $_POST['term'] ) );
		if ( empty( $term ) ) {
			die();
		}
		
		$like_term = '%' . $wpdb->esc_like( $term ) . '%';
		
		if ( is_numeric( $term ) ) {
			$query = $wpdb->prepare( "
					SELECT ID FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id
					WHERE posts.post_status = 'publish'
					AND (
					posts.post_parent = %s
					OR posts.ID = %s
					OR posts.post_title LIKE %s
					OR (
					postmeta.meta_key = '_sku' AND postmeta.meta_value LIKE %s
					)
					)
					", $term, $term, $term, $like_term );
		} else {
			$query = $wpdb->prepare( "
					SELECT ID FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id
					WHERE posts.post_status = 'publish'
					AND (
					posts.post_title LIKE %s
					or posts.post_content LIKE %s
					OR (
					postmeta.meta_key = '_sku' AND postmeta.meta_value LIKE %s
					)
					)
					", $like_term, $like_term, $like_term );
		}
		
		$query .= " AND posts.post_type IN ('" . implode( "','", array_map( 'esc_sql', $post_types ) ) . "')";
		
		$posts = array_unique( $wpdb->get_col( $query ) );
		$found_products = array();
		
		global $product;
		
		$proHTML = '';
		if ( ! empty( $posts ) ) {
			$proHTML .= '<table class="wp-list-table fixed striped" id="ced_umb_amazon_products_matched">';
			foreach ( $posts as $post ) {
				$product = wc_get_product( $post );
				if( $product->get_type() == 'variable' ) {
					$variations = $product->get_available_variations();
					foreach ($variations as $variation) {
						$proHTML .= '<tr><td product-id="'.$variation['variation_id'].'">'.get_the_title( $variation['variation_id'] ).'</td></tr>';
					}
				}
				else{
					$proHTML .= '<tr><td product-id="'.$post.'">'.get_the_title( $post ).'</td></tr>';
				}
			}
			$proHTML .= '</table>';
		}
		else {
			$proHTML .= '<ul class="woocommerce-error ccas_searched_product_ul"><li class="ccas_searched_pro_list"><strong>'.__('No Matches Found','ced-amazon-lister').'</strong><br/></li></ul>';
		}	
		echo $proHTML;
		wp_die();
	}


	/**
	 * This function to get all meta keys related to a product
	 * @name fetch_all_meta_keys_related_to_selected_product()
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @link  http://www.cedcommerce.com/
	 */
	function fetch_all_meta_keys_related_to_selected_product() {
		renderMetaKeysTableOnProfilePageamazon(sanitize_text_field($_POST['selectedProductId']));
		wp_die();
	}


	/**
	 * This function includes custom js needed by module.
	 * @name ced_umb_amazon_extended_manager_admin_enqueue_scripts()
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @link  http://www.cedcommerce.com/
	 */
	public function ced_umb_amazon_extended_manager_admin_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';
// 		print_r($screen_id);die;
		if( $screen_id == 'amazon_page_umb-amazon-pro-mgmt' ){
			wp_enqueue_style('ced_umb_amazon_manage_products_css', ced_umb_amazon_URL.'/admin/css/manage_products.css');
		}

		if( $screen_id == 'amazon_page_umb-amazon-cat-map' ){
			wp_enqueue_style('ced_umb_amazon_category_mapping_css', ced_umb_amazon_URL.'/admin/css/category_mapping.css');
		}

		if( $screen_id == 'amazon_page_umb-amazon-profile' && isset($_GET['action'])){	
			
		

			wp_enqueue_script( 'ced_umb_amazon_profile_edit_add_js', ced_umb_amazon_URL.'/admin/js/profile-edit-add.js', array('jquery'), '1.0', true );
			wp_localize_script( 'ced_umb_amazon_profile_edit_add_js', 'ced_umb_amazon_profile_edit_add_script_AJAX', array(
				'ajax_url' => admin_url( 'admin-ajax.php' )
			));
			wp_enqueue_script( 'ced_umb_amazon_profile_jquery_dataTables_js', ced_umb_amazon_URL.'/admin/js/jquery.dataTables.min.js', array('jquery'), '1.0', true );
			wp_enqueue_style( 'ced_umb_amazon_profile_jquery_dataTables_css', ced_umb_amazon_URL.'/admin/css/jquery.dataTables.min.css');
			wp_enqueue_style( 'ced_umb_amazon_profile_page_css', ced_umb_amazon_URL.'/admin/css/profile_page_css.css');
			
			/**
			** woocommerce scripts to show tooltip :: start
			*/
			
			/* woocommerce style */
			wp_register_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
			wp_enqueue_style( 'woocommerce_admin_menu_styles' );
			wp_enqueue_style( 'woocommerce_admin_styles' );
			
			/* woocommerce script */
			$suffix = '';
			wp_register_script( 'woocommerce_admin', WC()->plugin_url() . '/assets/js/admin/woocommerce_admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), WC_VERSION );
			wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), WC_VERSION, true );
			wp_enqueue_script( 'woocommerce_admin' );	
			
			/**
			** woocommerce scripts to show tooltip :: end
			*/	
		}
		
		
	}
				
}
new ced_umb_amazon_Extended_Manager();
?>