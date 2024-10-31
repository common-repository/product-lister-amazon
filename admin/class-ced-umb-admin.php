<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Amazon Product Lister
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @package    Amazon Product Lister
 
 */
class ced_umb_amazon_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	
	/**
	 * helper for product management.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      ced_umb_amazon_product_manager    $product_manager    Maintains all single product related functionality.
	 */
	private $product_manager;
	
	/**
	 * helper for plugin admin pages.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      ced_umb_amazon_menu_page_manager    $menu_page_manager    Maintains all this plugin pages related functionality.
	 */
	private $menu_page_manager;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->load_admin_classes();
		$this->instantiate_admin_classes();
		add_action('wp_ajax_ced_umb_amazon_select_cat_prof', array($this,'ced_umb_amazon_select_cat_prof'));
		
		
		add_action('wp_ajax_ced_umb_amazon_current_product_status', array($this,'ced_umb_amazon_current_product_status'));
		
		
		add_action('init', array($this,'ced_order_detail_thickox'));
	}
	
	public function ced_order_detail_thickox(){
		if(isset($_GET['page']) && $_GET['page'] == 'umb-amazon-orders' && isset($_GET['action']) && $_GET['action'] == 'get_item'){
				
			if(isset($_GET['orderid']))
			{
	
				global $wpdb;
				$prefix = $wpdb->prefix . ced_umb_amazon_PREFIX;
				$tableName = $prefix.'ListOrders';
	
	
				$sql = "SELECT * FROM `$tableName` WHERE `amazon_orderid` LIKE '".$_GET['orderid']."' ";
				$result = $wpdb->get_results($sql,'ARRAY_A');
				$this->get_order_detail_html($result[0]);
	
			}
			die;
		}
	}
	public function get_order_detail_html($order_data){
		global $wpdb;
	
		$prefix = $wpdb->prefix.ced_umb_amazon_PREFIX;
		$tableName = $prefix.'ListOrders';
	
		$ordertotal = json_decode($order_data['total'],true);
		$ordercurrency = $ordertotal['currency'];
		$ordertotal = $ordertotal['total'];
		$ordershipping = json_decode($order_data['shippingaddress'],true);
		?>
				<style type="text/css">
					.orderwrap {
					  padding: 10px;
					}
					.orderwrap h2 {
					  background-color: #575757;
					  color: #fff;
					  font-weight: 500;
					  padding: 10px;
					  text-transform: uppercase;
					  font-size: 20px;
					}
					.orderwrap h3 {
					  background-color: #575757;
					  font-size: 20px;
					  color: #fff;
					  font-weight: 500;
					  padding: 10px;
					  text-transform: uppercase;
					}
					.orderwrap tr td {
					  font-size: 15px;
					  
					}
					.va tr th {
					  padding: 15px 10px;
					  text-align: left;
					  text-transform: uppercase;
					  width: 20%;
					}
					.va tr td {
					  display: table-cell;
					  padding: 10px;
					  vertical-align: top;
					  width: 20%;
					  word-break: break-all;
					}
					.orderwrap p {
					  font-size: 15px;
					  margin: 10px 0;
					}
					.va {
					  border: 1px solid #f1f1f1;
					}
					#TB_closeAjaxWindow > button span {
					  color: #fff;
					}
					.orderwrap h4 {
					  font-size: 18px;
					  margin: 0 0 8px;
					  text-transform: uppercase;
					}
					.ca {
					 
						width: 100%;
		 				margin-bottom: 25px;
					}
					.ca p {
						  margin: 0;
						  min-height: 25px;
						}
					#TB_title > div button {
					  background-color: #575757;
					}
					#TB_closeAjaxWindow > button span {
					  color: #fff;
					}
					
	
				</style>
				<div class="orderwrap">
					<h2><?php _e('Order Details','ced-amazon-lister'); ?></h2>
					<table class="ced_order_detail">
						<tr>
							<td>
								<strong><?php _e('Order ID:','ced-amazon-lister'); ?></strong>
							</td>
							<td>
								<p><?php echo $order_data['amazon_orderid']; ?></p>
							</td>
						</tr>
						<tr>
							<td>
								<strong><?php _e('Order Date:','ced-amazon-lister'); ?></strong>
							</td>
							<td>
								<p><?php echo $order_data['purchasedate']; ?></p>
							</td>
						</tr>
						<tr>
							<td>
								<strong><?php _e('Order Status:','ced-amazon-lister'); ?></strong>
							</td>
							<td>
								<p><?php echo $order_data['status']; ?></p>
							</td>
						</tr>
						<tr>
							<td>
								<strong><?php _e('Buyer Name:','ced-amazon-lister'); ?></strong>
							</td>
							<td>
								<p><?php echo $order_data['buyername']; ?></p>
							</td>
						</tr>
						<tr>
							<td>
								<strong><?php _e('Buyer Email:','ced-amazon-lister'); ?></strong>
							</td>
							<td>
								<p><?php echo $order_data['buyeremail']; ?></p>
							</td>
						</tr>
						<tr>
							<td>
								<strong><?php _e('Order Total:','ced-amazon-lister'); ?></strong>
							</td>
							<td>
								<p><?php echo $ordercurrency.$ordertotal; ?></p>
							</td>
						</tr>
					</table>
	
					<h2><?php _e('Shipping Details','ced-amazon-lister'); ?></h2>
	
					<h4><?php _e('Shipping Address','ced-amazon-lister'); ?></h4>
					<table class="ca">
					<tr>
						<td>
							<strong><?php echo __('Name:','ced-amazon-lister'); ?></strong>
						</td>
						<td>
							<p><?php echo $ordershipping['first_name']; ?></p>
						</td>
					</tr>
					<tr>
						<td>
							<strong><?php echo __('Address 1:','ced-amazon-lister'); ?></strong>
						</td>
						<td>
							<p><?php echo $ordershipping['address_1']; ?></p>
						</td>
					</tr>
					<tr>
						<td>
							<strong><?php echo __('Address 2:','ced-amazon-lister'); ?></strong>
						</td>
						<td>
							<p><?php echo $ordershipping['address_2']; ?></p>
						</td>
					</tr>
					<tr>
						<td>
							<strong><?php echo __('City','ced-amazon-lister'); ?></strong>
						</td>
						<td>
							<p><?php echo $ordershipping['city']; ?></p>
						</td>
					</tr>
					<tr>
						<td>
							<strong><?php echo __('County','ced-amazon-lister'); ?></strong>
						</td>
						<td>
							<p><?php echo $ordershipping['county']; ?></p>
						</td>
					</tr>
					<tr>
						<td>
							<strong><?php echo __('District:','ced-amazon-lister'); ?></strong>
						</td>
						<td>
							<p><?php echo $ordershipping['district']; ?></p>
						</td>
					</tr>
					<tr>
						<td>
							<strong><?php echo __('State:','ced-amazon-lister'); ?></strong>
						</td>
						<td>
							<p><?php echo $ordershipping['state']; ?></p>
						</td>
					</tr>
					<tr>
						<td>
							<strong><?php echo __('Postcode:','ced-amazon-lister'); ?></strong>
						</td>
						<td>
							<p><?php echo $ordershipping['postcode']; ?></p>
						</td>
					</tr>
					<tr>
						<td>
							<strong><?php echo __('Country:','ced-amazon-lister'); ?></strong>
						</td>
						<td>
							<p><?php echo $ordershipping['country']; ?></p>
						</td>
					</tr>
					<tr>
						<td>
							<strong><?php echo __('Phone Number:','ced-amazon-lister'); ?></strong>
						</td>
						<td>
							<p><?php echo $ordershipping['phoneno']; ?></p>
						</td>
					</tr>
					</table>
					<h4><?php echo __('Shipping Service','ced-amazon-lister');?></h4>
					<?php echo $order_data['shippingservice']; ?>
					<h3><?php _e('Item Details','ced-amazon-lister'); ?></h3>
					<table class="va">
						<?php 
						if($order_data['items'] == null )
						{
							if(!class_exists("AmazonOrderItemList"))
							{
								require(CED_MAIN_DIRPATH.'marketplaces/amazon/lib/amazon/includes/classes.php');
							}
							
							$amzitemlist = new AmazonOrderItemList();
							$amzitemlist->setOrderId($order_data['amazon_orderid']);
							$amzitemlist->setUseToken(false);
							$amzitemlist->fetchItems(false);
							$amzitemlistitems = $amzitemlist->getItems();
							
							$orderlineitems = array();
							
							if(isset($amzitemlistitems) && !empty($amzitemlistitems))
							{
								foreach($amzitemlistitems as $linenu=>$amzitemlistitem)
								{
									$asinnumber = $amzitemlistitem['ASIN'];				
									
									
									$shipping_price = $amzitemlistitem['ShippingPrice']['Amount'];
								
									$item = array(
											'title'=>$amzitemlistitem['Title'],
											'OrderedQty' => $amzitemlistitem['QuantityOrdered'],			
											'UnitPrice' => $amzitemlistitem['ItemPrice']['Amount'],
											'asin' => $asinnumber,
											'lineNumber'=>$linenu,
											'item_id'=>$amzitemlistitem['OrderItemId'],
											
											'shipping_price'=>$shipping_price,
									);							
										
									$orderlineitems[] = $item;
									
								}
							}
							$orderlineitems = json_encode($orderlineitems);
							$dataToupdate = array(
									'items'   => $orderlineitems,
							);
							$where = array('amazon_orderid' =>$order_data['amazon_orderid']);
							$update_data = $wpdb->update( $tableName, $dataToupdate, $where );
							
								
							$sql = "SELECT * FROM `$tableName` WHERE `amazon_orderid` LIKE '".$order_data['amazon_orderid']."' ";
							$result = $wpdb->get_results($sql,'ARRAY_A');
	
	
							$orderitem = json_decode($result[0]['items'],true);
	
						}
						else
						{
							$orderitem = json_decode($order_data['items'],true);
						}			
						?>
						<tr>
							<th><?php echo __('ASIN','ced-amazon-lister') ?></th>
							<th><?php echo __('Title','ced-amazon-lister') ?></td>
							<th><?php echo __('Quantity','ced-amazon-lister') ?></th>
							<th><?php echo __('Price','ced-amazon-lister') ?></th>
							<th><?php echo __('Shipping','ced-amazon-lister') ?></th>
						</tr>
						<?php
						foreach ($orderitem as $key => $value) 
						{
							?>
							<tr>
								<td><?php echo $value['asin']; ?></td>
								<td><?php echo $value['title']; ?></td>
								<td><?php echo $value['OrderedQty']; ?></td>
								<td><?php echo $value['UnitPrice']; ?></td>
								<td><?php echo $value['shipping_price']; ?></td>
							</tr>
							<?php
						}
						?>
					</table>
				</div>
			<?php
		}
	
	
	public function ced_umb_amazon_current_product_status()
	{
		$prodId = isset($_POST['prodId']) ? sanitize_text_field($_POST['prodId']) : false;
		$marketPlace = isset($_POST['marketplace']) ? sanitize_text_field($_POST['marketplace']) : false;
		if($prodId && $marketPlace){
		$filePath = ced_umb_amazon_DIRPATH.'marketplaces/'.$marketPlace.'/class-'.$marketPlace.'.php';
			if(file_exists($filePath))
				require_once $filePath;
			
			$class_name = "ced_umb_amazon_".$marketPlace."_manager";
			
			$manager = $class_name :: get_instance();
			$productstatusresponse = $manager->getProductstatus($prodId);
			echo $productstatusresponse;die;
		}

		
	}

	public function ced_umb_amazon_select_cat_prof()
	{
		global $wpdb;
		
		$catId  = isset($_POST['catId']) ? sanitize_text_field($_POST['catId']) : "";
		$profId = isset($_POST['profId']) ? sanitize_text_field($_POST['profId']) : "";
		
		if($profId == "removeProfile")
		{
			$profId = "";
		}
		$getSavedvalues = get_option('ced_umb_amazon_category_profile', false);
		if(is_array($getSavedvalues) && array_key_exists($catId, $getSavedvalues))
		{
			if($profId == "removeProfile")
			{
				unset($getSavedvalues["$catId"]);
			}
			else{
				$getSavedvalues["$catId"] = $profId;
			}
		}
		else{
			if($profId != "removeProfile")
			{
				$getSavedvalues["$catId"] = $profId;
			}
		}
		
		update_option('ced_umb_amazon_category_profile', $getSavedvalues);
		
		$table_name = $wpdb->prefix.ced_umb_amazon_PREFIX.'profiles';
		$query = "SELECT `id`, `name` FROM `$table_name` WHERE 1";
		$profiles = $wpdb->get_results($query,'ARRAY_A');

		$profName = __('Profile not selected', 'ced-amazon-lister');
		
		if(is_array($profiles) && !empty($profiles))
		{
			foreach ($profiles as $profile)
			{
				if($profile['id'] == $profId)
				{
					$profName = $profile['name'];
				}
			}
		}
		
		$tax_query['taxonomy'] = 'product_cat';
		$tax_query['field'] = 'id';
		$tax_query['terms'] = $catId;
		$tax_queries[] = $tax_query;
		$args = array( 'post_type' => 'product', 'posts_per_page' => -1, 'tax_query' => $tax_queries, 'orderby' => 'rand' );
		
		$loop = new WP_Query( $args );
		while ( $loop->have_posts() ) {
			$loop->the_post();
			global $product;
			if(is_wp_error($product))
				return;
			if($product->get_type() == 'variable') {
				$variations = $product->get_available_variations();
				if(is_array($variations) && !empty($variations)){
					foreach ($variations as $variation) {
						$var_id = $variation['variation_id'];
						update_post_meta($var_id, "ced_umb_amazon_profile", $profId);
					}
				}
			}
			$product_id = $loop->post->ID;
			$product_title = $loop->post->post_title;
			update_post_meta($product_id, "ced_umb_amazon_profile", $profId);
		}
		echo json_encode(array('status'=>__('success','ced-amazon-lister'),'profile'=> $profName));
		wp_die();
	}
	
	
	
	
	/**
	 * Including all admin related classes.
	 * 
	 * @since 1.0.0
	 */
	private function load_admin_classes(){
		
		$classes_names = array(
			'admin/helper/class-product-fields.php',
			'admin/helper/class-menu-page-manager.php',
			'admin/helper/class-order-manager.php',
			'admin/helper/class-ced-umb-extended-manager.php'
		);
		
		foreach( $classes_names as $class_name ){
			require_once ced_umb_amazon_DIRPATH . $class_name;
		}
		
		$activated_marketplaces = ced_umb_amazon_available_marketplace();
		if(is_array($activated_marketplaces)):
			foreach($activated_marketplaces as $marketplace_name){
				$file_path = ced_umb_amazon_DIRPATH.'marketplaces/'.$marketplace_name.'/class-'.$marketplace_name.'.php';
				if(file_exists($file_path))
					require_once $file_path;
			}
		endif;
	}
	
	/**
	 * storing instance of admin related functionality classes.
	 * 
	 * @since 1.0.0 
	 */
	private function instantiate_admin_classes(){
		
		if( class_exists( 'ced_umb_amazon_product_fields' ) )
			$this->product_fields = ced_umb_amazon_product_fields::get_instance();
		
		if( class_exists( 'ced_umb_amazon_menu_page_manager' ) )
			$this->menu_page_manager = ced_umb_amazon_menu_page_manager::get_instance();
		
		
		
		// creating instances of activated marketplaces classes.

		$activated_marketplaces = ced_umb_amazon_available_marketplace();
		if(is_array($activated_marketplaces)):
			foreach($activated_marketplaces as $marketplace){
				$class_name = 'ced_umb_amazon_'.$marketplace.'_manager';
				if(class_exists($class_name))
					new $class_name();
			}
		endif;
	}
	
	/**
	 * Returns all the admin hooks.
	 * 
	 * @since 1.0.0
	 * @return array admin_hook_data.
	 */
	public function get_admin_hooks(){
		
		$admin_actions = array(
				array(
						'type'	=>	'action',
						'action' => 'woocommerce_product_data_tabs',
						'instance' => $this->product_fields,
						'function_name' => 'umb_required_fields_tab'
				),
				array(
						'type'	=>	'action',
						'action' => 'woocommerce_product_data_panels',
						'instance' => $this->product_fields,
						'function_name' => 'umb_required_fields_panel'
				),
				array(
						'type'	=>	'action',
						'action' => 'woocommerce_process_product_meta',
						'instance' => $this->product_fields,
						'function_name' => 'umb_required_fields_process_meta'
				),
				array(
						'type'	=>	'action',
						'action' => 'admin_menu',
						'instance' => $this->menu_page_manager,
						'function_name' => 'create_pages'
				),
				array(
						'type'	=>	'action',
						'action' => 'save_post',
						'instance' => $this->product_fields,
						'function_name' => 'quick_edit_save_data',
						'priority' => 10,
						'accepted_args' => 2
				),
				array(
						'type'	=>	'action',
						'action' => 'wp_ajax_ced_umb_amazon_save_profile',
						'instance' => $this,
						'function_name' => 'ced_umb_amazon_save_profile',
				)
		); 
		
		return apply_filters( 'ced_umb_amazon_admin_actions', $admin_actions );
	}
	
	/**
	 * save assigned profile to the product.
	 * 
	 * @since 1.0.0
	 */
	public function ced_umb_amazon_save_profile()
	{
		$prodId    = isset($_POST['proId']) ? sanitize_text_field($_POST['proId']) : "";
		$profileId = isset($_POST['profileId']) ? sanitize_text_field($_POST['profileId']) : "";
		$_product = wc_get_product( $prodId );
		if(is_wp_error($_product))
			return;
		if($_product->get_type() == 'variable') {
			
			$variations = $_product->get_available_variations();
			if(is_array($variations) && !empty($variations)){
				foreach ($variations as $variation) {
					$var_id = $variation['variation_id'];
					update_post_meta($var_id, "ced_umb_amazon_profile", $profileId);
				}
			}
		}
		update_post_meta($prodId, "ced_umb_amazon_profile", $profileId);
		$ced_umb_amazon_profile = get_post_meta($prodId, "ced_umb_amazon_profile", true);
		if($ced_umb_amazon_profile == $profileId) {
			echo __('success','ced-amazon-lister');
		}
		else {
			echo __('fail','ced-amazon-lister');
		}
		wp_die();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';
		
		if( $screen_id == 'toplevel_page_umb-amazon' || $screen_id == 'product' )
			wp_enqueue_style( $this->plugin_name.'config_style', plugin_dir_url( __FILE__ ) . 'css/ced_umb_config_style.css', array(), $this->version, 'all' );
		
		wp_enqueue_style( $this->plugin_name.'common_style', plugin_dir_url( __FILE__ ) . 'css/common_style.css', array(), $this->version, 'all' );
		
		
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';
		
		
		
		if( $screen_id == 'toplevel_page_umb-amazon' || $screen_id == 'product' )
		{	
			wp_enqueue_script( $this->plugin_name.'config_script', plugin_dir_url( __FILE__ ) . 'js/ced_umb_config.js', array( 'jquery' ), $this->version, false );
		}	
		if( $screen_id == 'amazon_page_umb-amazon-pro-mgmt' ){
			wp_enqueue_script( $this->plugin_name.'quick_edit', plugin_dir_url( __FILE__ ) . 'js/ced_umb_quick_edit.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name.'profile', plugin_dir_url( __FILE__ ) . 'js/ced_umb_profile.js', array( 'jquery' ), $this->version, false );
			wp_localize_script( $this->plugin_name.'profile', 'profile_action_handler', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		}
		add_thickbox();
		wp_enqueue_script( $this->plugin_name.'common_script', plugin_dir_url( __FILE__ ) . 'js/common_script.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name.'common_script', 'common_action_handler', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}

}
