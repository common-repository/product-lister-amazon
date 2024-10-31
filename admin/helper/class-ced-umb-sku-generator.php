<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if(!class_exists('WP_List_Table')) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * product listing related functionality on manage products page.
 *
 * @since      1.0.0
 *
 * @package    Amazon Product Lister
 /helper
 */

if( !class_exists( 'ced_umb_amazon_sku_generator' ) ) :

/**
 * product listing on manage product.
 *
 * product quick editing, listing and all other functionalities
 * to manage products.
 *
 * @since      1.0.0
 * @package    Amazon Product Lister
 /helper
 * @author     CedCommerce <cedcommerce.com>
 */
class ced_umb_amazon_sku_generator extends WP_List_Table {
	
	/**
	 * product data query response.
	 * 
	 * @since 1.0.0
	 */
	private $_loop;
	private $_current_product_id;
	private $_is_variable_product;
	private $_umbFramework;
	
	
	
	
	/**
	 * Constructor.
	 * 
	 * @since 1.0.0
	 */
	function __construct(){

		global $status, $page, $cedumbamazonhelper;
		//$marketPlaces = get_option('ced_umb_amazon_activated_marketplaces',true);
		$marketPlaces = get_enabled_marketplacesamazon();
		$marketPlace = is_array($marketPlaces) && $marketPlaces!=null ? $marketPlaces[0] : "";
		$this->_umbFramework = isset($_REQUEST['section']) ? $_REQUEST['section'] : $marketPlace;
		parent::__construct( array(
				'singular'  => 'ced_umb_amazon_mp',     
				'plural'    => 'ced_umb_amazon_mps',   
				'ajax'      => true        
		) );		
	}

	
	/**
	 * columns for the manage product table from
	 * where you can manage products for marketplaces.
	 * 
	 * @since 1.0.0
	 * @see WP_List_Table::get_columns()
	 */
	public function get_columns(){
		$columns = array(
				'cb'        => '<input type="checkbox" />',
				'thumb'     => '<span class="wc-image tips" data-tip="' . esc_attr__( 'Image', 'ced-amazon-lister' ) . '">' . __( 'Image', 'woocommerce' ) . '</span>',
				'name'    => __( 'Name', 'ced-amazon-lister' ),
				
				'current_sku' => __('Current SKU', 'ced-amazon-lister'),
				'new_sku'	=> __('New SKU', 'ced-amazon-lister'),
				'lowest_price' => __('Lowest Price' , 'ced-amazon-lister'),
				'competitive' => __('Competitive Pricing','ced-amazon-lister'),
				'buy_box'=> __('Buy Box','ced-amazon-lister'),

		);
		$columns = apply_filters('ced_umb_amazon_alter_columns_in_sku_generator_section',$columns);
		return $columns;
	}
	
	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = [
			'generate_new_sku'    => __( 'Generate SKU', 'ced-amazon-lister' ),
			'get_competitve_price' => __( 'Get Competitve Price', 'ced-amazon-lister' ),
			'lowest_price' =>__('Lowest Price','ced-amazon-lister')
		];
		return $actions;
	}
	 public function process_bulk_action() 
    {
    	
    	if( 'generate_new_sku' === $this->current_action() ) {
    		if( isset( $_POST['post'] ) && !empty( $_POST['post'] )){
    			$product_ids = $_POST['post'];
    			foreach ($product_ids as $key => $value_id) {
    				$product_data = wc_get_product($value_id);
		
					$sku_settings = get_option('ced_sku_settings_array',true);
					$ced_sku_prefix = (isset($sku_settings['ced_sku_prefix']) && $sku_settings['ced_sku_prefix'] != null) ? $sku_settings['ced_sku_prefix'] : "";
					$ced_simple_sku = (isset($sku_settings['ced_simple_sku']) && $sku_settings['ced_simple_sku'] != null) ? $sku_settings['ced_simple_sku'] : "ced_first_letter";
					$ced_variable_sku = (isset($sku_settings['ced_variable_sku']) && $sku_settings['ced_variable_sku'] != null) ? $sku_settings['ced_variable_sku'] : "ced_full_attr";

					if($ced_sku_prefix != null && $ced_sku_prefix != ""){
						$ced_sku_prefix.="-";
					}
					$product_type = $product_data->get_type();
					$ced_title = get_the_title($value_id);
					$ced_title = explode(" ", $ced_title);
					if($product_type != 'variation')
					{
						if($ced_simple_sku == "ced_first_letter")
						{
							foreach ($ced_title as $key => $value) {
								$ced_sku_prefix.=substr($value, 0,1)."-";
							}	
							$ced_sku_prefix = chop($ced_sku_prefix, '-');
							update_post_meta($value_id,'_sku',$ced_sku_prefix);
						}
						if($ced_simple_sku == "ced_first_two_letter")
						{
							foreach ($ced_title as $key => $value) {
								$ced_sku_prefix.=substr($value, 0,2)."-";
							}	
							$ced_sku_prefix = chop($ced_sku_prefix, '-');
							update_post_meta($value_id,'_sku',$ced_sku_prefix);
						}
						
						$notice['message'] = __('SKU Generated Succcessfully: ','ced-amazon-lister').$ced_sku_prefix;
						$notice['classes'] = "notice notice-success";
						$validation_notice[] = $notice;
						$_SESSION['ced_umb_amazon_validation_notice'] = $validation_notice;

					}
    			}
    		}
    	}
    	if( 'get_competitve_price' === $this->current_action() ) {
    		if( isset( $_POST['post'] ) && !empty( $_POST['post'] )){
    			$product_ids = $_POST['post'];
    			foreach ($product_ids as $key => $value_id) {
		    		$directorypath = CED_MAIN_DIRPATH;
					if(!class_exists("AmazonReportRequest"))
					{
						require($directorypath.'marketplaces/amazon/lib/amazon/includes/classes.php');
					}
					$marketplaceid = get_option('ced_umb_amazon_amazon_configuration',true);
					$compatative_pri = new AmazonProductInfo();
					$compatative_pri->setMarketplace($marketplaceid['marketplace_id']);
					$get_sku = get_post_meta($value_id,'_sku',true);
					$compatative_pri->setSKUs(array($get_sku));
					
					$compatative_pri->fetchCompetitivePricing();
					$cp = $compatative_pri->getProduct();
					$compatative_price_save = array();
					if(isset($cp['Error'])){
						
						$notice['message'] = $cp['Error'];
						$notice['classes'] = "notice notice-error";
						$validation_notice[] = $notice;
						$_SESSION['ced_umb_amazon_validation_notice'] = $validation_notice;
					}
					else
					{
						$price = $cp[0]->getProduct();
						$count =0;
						if(isset($price['CompetitivePricing']) && is_array($price['CompetitivePricing'])){
						    foreach ($price['CompetitivePricing'] as $compatative_price){
								foreach ($compatative_price as $compatative_price_single){
									if(isset($compatative_price_single['Price']['LandedPrice']['Amount'])){
										$combine_price = array();
										$compatative_price_save[$count] = $compatative_price_single['Price']['LandedPrice'];
										$count++;
									}	
								
								}
						    }
						}
						if(isset($compatative_price_save) && $compatative_price_save != null )
						{
							if($compatative_price_save[0]['Amount'] > $compatative_price_save[1]['Amount']){
								update_post_meta($value_id, 'ced_competitve_currency', $compatative_price_save[1]['CurrencyCode']);	
								update_post_meta($value_id, 'ced_competitve_amount', $compatative_price_save[1]['Amount']);

							}else{
								update_post_meta($value_id, 'ced_competitve_currency', $compatative_price_save[0]['CurrencyCode']);	
								update_post_meta($value_id, 'ced_competitve_amount', $compatative_price_save[0]['Amount']);
							}
							$notice['message'] = __('Competitive Price has been updated','ced-amazon-lister');
							$notice['classes'] = "notice notice-success";
							$validation_notice[] = $notice;
							$_SESSION['ced_umb_amazon_validation_notice'] = $validation_notice;
						}
					}					
					
				}
			}
    	}
    	if('lowest_price' === $this->current_action() ){
    		if( isset( $_POST['post'] ) && !empty( $_POST['post'] )){
    			$product_ids = $_POST['post'];
    			foreach ($product_ids as $key => $value_id) {
    				$directorypath = CED_MAIN_DIRPATH;
    				if(!class_exists("AmazonReportRequest"))
    				{
    					require($directorypath.'marketplaces/amazon/lib/amazon/includes/classes.php');
    				}
    				$marketplaceid = get_option('ced_umb_amazon_amazon_configuration',true);
    				$lowest_pri = new AmazonProductInfo();
    				$lowest_pri->setMarketplace($marketplaceid['marketplace_id']);
    				$lowest_pri->setConditionFilter("Used");
    				$get_sku = get_post_meta($value_id,'_sku',true);
    				$lowest_pri->setSKUs(array($get_sku));
    				$lowest_pri->fetchLowestPricedOffers();
    				
    				$lowest_price = $lowest_pri->getProduct();
    				
//     				print_r($lowest_price);die('ad');
					if(isset($lowest_price[0])){
		    			if(isset($lowest_price['Error'])){
								echo "<br>".$cp['Error'];
							}
							else
							{
								$low_price = $lowest_price[0]->getProduct();
		// 						print_r($low_price['Summary']);die('ds');
								$count =0;
								if(isset($low_price['Summary']['LowestPrices']) && is_array($low_price['Summary']['LowestPrices']))
								{
									foreach ($low_price['Summary']['LowestPrices'] as $key_pri => $val_pri){
		// 									print_r($product_ids);die;
										if(isset($val_pri['new']['LandedPrice'])){
		// 									print_r($val_pri['new']['LandedPrice']);die;
											update_post_meta($value_id, 'lowest_price', $val_pri['new']['LandedPrice']['Amount']);
											update_post_meta($value_id, 'lowest_price_currency', $val_pri['new']['LandedPrice']['CurrencyCode']);
										}								
									}
									
								}
		// 						print_r($low_price);die;
								if(isset($low_price['Summary']['BuyBoxPrices']) && is_array($low_price['Summary']['BuyBoxPrices']))
								{
		// 							print_r($low_price['Summary']['BuyBoxPrices']); die;
									foreach ($low_price['Summary']['BuyBoxPrices'] as $key_buybox => $val_buybox){
		// 									print_r($val_buybox);die;								
											if(isset($val_buybox['LandedPrice'])){
																				
											update_post_meta($value_id, 'buybox_price', $val_buybox['LandedPrice']['Amount']);
											update_post_meta($value_id, 'buybox_price_currency', $val_buybox['LandedPrice']['CurrencyCode']);
										}
									}
								}	
								
							}
					}
    			}
    		}
    	}
    }


	/**
	 * preparing the table data for listing products
	 * so that we can manage all products form single
	 * place to all frameworks.
	 * 
	 * @since 1.0.0
	 * @see WP_List_Table::prepare_items()
	 */	
	function prepare_items() {
		global $wpdb;
		global $cedumbamazonhelper;
		$per_page = apply_filters( 'ced_umb_amazon_sku_products_per_page', 10 );
		$post_type = 'product';
	
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
	
		$this->_column_headers = array($columns, $hidden, $sortable);
		 $this->process_bulk_action();
		$current_page = $this->get_pagenum();
		
		// Query args
		$args = array(
				'post_type'           => $post_type,
				'posts_per_page'      => $per_page,
				'ignore_sticky_posts' => true,
				'paged'               => $current_page
		);
		
		

		// Get the webhooks
		$webhooks  = new WP_Query( $args );
		
		$total_items = $webhooks->found_posts;
		
		$this->set_pagination_args( array(
				'total_items' => $total_items,                  
				'per_page'    => $per_page,                     
				'total_pages' => ceil($total_items/$per_page)  
		) );
	}
	
	/**
	 * displaying the marketplace listable products.
	 * 
	 * @since 1.0.0
	 * @see WP_List_Table::display_rows()
	 */
	public function display_rows(){
		

		if( $this->has_product_data() ){

			$loop = $this->_loop;
			if($loop->have_posts()){
				while($loop->have_posts()){
					$loop->the_post();
					$string = strtolower($loop->post->post_title);
					if(isset($_GET['s']) && !empty($_GET['s']))
					{	
						$substring = stripcslashes(strtolower($_GET['s']));
						if( strpos( $string, $substring  ) !== false ) {
							$this->get_product_row_html($loop->post);
						}
					}else{
					
						$this->get_product_row_html($loop->post);
					}
				}
			}
		}
	}
	
	

	/**
	 * get product row html.
	 * 
	 * @since 1.0.0
	 */
	public function get_product_row_html($post){
		$_product = wc_get_product( $post->ID );
		if(is_wp_error($_product))
			return;

		$product_id = $_product->get_id();
		$this->_current_product_id = $product_id;
		$this->_is_variable_product	= false;

		$columns = $this->get_columns();
		
		if($_product->get_type() != 'variable') { 
			echo '<tr id="post-'.$product_id.'" class="ced_umb_amazon_inline_edit">';
			foreach($columns as $column_id => $column_name){
				$this->print_column_data($column_id, $post, $_product);
			}
			echo '</tr>';
		 }
		
	}
	
	/**
	 * displaying product title with some links
	 * for editing, quick editing etc.
	 * 
	 * @since 1.0.0
	 * @param post object $post
	 */
	public function _colummn_title( $post,$is_variation=false ){
		
		$classes = "id column-id has-row-actions column-primary";
		$data = "data-colname=id";
		echo '<td class="'.$classes.'" '.$data.'>';
		$this->column_title($post);
		echo $this->handle_row_actions($post, 'Name', 'Name');
		echo '</td>';
	}
	
	/**
	 * Generates and displays row action links.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param object $post        Post being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @return string Row actions output for posts.
	 */
	protected function handle_row_actions( $post, $column_name, $primary ) {
		$post_type_object = get_post_type_object( $post->post_type );
		$can_edit_post = current_user_can( 'edit_post', $post->ID );
		$actions = array();
		$title = _draft_or_post_title($post);
	
		$actions['id'] = 'ID: ' . $this->_current_product_id;

		if( $post->post_type == 'product_variation' ) {
			$idToUseForLink = $post->post_parent;
		}
		else {
			$idToUseForLink = $post->ID;
		}
		if ( $can_edit_post && 'trash' != $post->post_status ) {
			$actions['edit'] = '<a href="' . get_edit_post_link( $idToUseForLink, true ) . '" title="' . esc_attr( __( 'Edit this item', 'ced-amazon-lister' ) ) . '">' . __( 'Edit', 'ced-amazon-lister' ) . '</a>';
		}
		
		return $this->row_actions( $actions );
	}
	

	/**
	 * column title.
	 * 
	 * @since 1.0.0
	 * @param post object $post
	 */
	public function column_title( $post ) {
		global $mode;
	
		if ( $this->hierarchical_display ) {
			if ( 0 === $this->current_level && (int) $post->post_parent > 0 ) {
				$find_main_page = (int) $post->post_parent;
				while ( $find_main_page > 0 ) {
					$parent = get_post( $find_main_page );
	
					if ( is_null( $parent ) ) {
						break;
					}
	
					$this->current_level++;
					$find_main_page = (int) $parent->post_parent;
	
					if ( ! isset( $parent_name ) ) {
						/** This filter is documented in wp-includes/post-template.php */
						$parent_name = apply_filters( 'the_title', $parent->post_title, $parent->ID );
					}
				}
			}
		}
	
		$pad = str_repeat( '&#8212; ', $this->current_level );
		echo "<strong>";
	
		$format = get_post_format( $post->ID );
		if ( $format ) {
			$label = get_post_format_string( $format );
	
			$format_class = 'post-state-format post-format-icon post-format-' . $format;
	
			$format_args = array(
					'post_format' => $format,
					'post_type' => $post->post_type
			);
			echo $this->get_edit_link( $format_args, $label . ':', $format_class );
		}
	
		$can_edit_post = current_user_can( 'edit_post', $post->ID );
		$title = _draft_or_post_title($post);
	
		if ( $can_edit_post && $post->post_status != 'trash') {
			printf(
			'<a class="row-title" href="%s" aria-label="%s">%s%s</a>',
			get_edit_post_link( $post->ID ),
			/* translators: %s: post title */
			esc_attr( sprintf( __( '&#8220;%s&#8221; (Edit)','ced-amazon-lister' ), $title ) ),
			$pad,
			$title
			);
		} else {
			echo $pad . $title;
		}
		_post_states( $post );
	
		if ( isset( $parent_name ) ) {
			$post_type_object = get_post_type_object( $post->post_type );
			echo ' | ' . $post_type_object->labels->parent_item_colon . ' ' . esc_html( $parent_name );
		}
		echo "</strong>\n";
	
		if ( $can_edit_post && $post->post_status != 'trash' ) {
			$lock_holder = wp_check_post_lock( $post->ID );
	
			if ( $lock_holder ) {
				$lock_holder = get_userdata( $lock_holder );
				$locked_avatar = get_avatar( $lock_holder->ID, 18 );
				$locked_text = esc_html( sprintf( __( '%s is currently editing','ced-amazon-lister' ), $lock_holder->display_name ) );
			} else {
				$locked_avatar = $locked_text = '';
			}
	
			echo '<div class="locked-info"><span class="locked-avatar">' . $locked_avatar . '</span> <span class="locked-text">' . $locked_text . "</span></div>\n";
		}
	
		if ( ! is_post_type_hierarchical( $this->screen->post_type ) && 'excerpt' === $mode && current_user_can( 'read_post', $post->ID ) ) {
			the_excerpt();
		}
	
		get_inline_data( $post );
		
		$the_product = wc_get_product($post->ID);
		
		$hidden_fields = '<div class="hidden" id="ced_umb_amazon_inline_' . $this->_current_product_id . '">';
		
		$hidden_fields .= '<div class="_sku" type="_text_input">'.$the_product->get_sku().'</div>';
		
		if(!class_exists('ced_umb_amazon_product_fields')){
			require_once ced_umb_amazon_DIRPATH.'admin/helper/class-product-fields.php';
		}
		$product_fields = ced_umb_amazon_product_fields::get_instance();
		$required_fields = $product_fields->get_custom_fields('required',false);
		if(is_array($required_fields)){
			foreach($required_fields as $fieldData){
				if(is_array($fieldData)){
					$id = isset($fieldData['id']) ? esc_attr($fieldData['id']) : '';
					$type = isset($fieldData['type']) ? esc_attr($fieldData['type']) : '';
					if(!empty($id) && !empty($type)){
						$hidden_fields .= '<div class="'.$id.'" type="'.$type.'">'.get_post_meta($this->_current_product_id,$id,true).'</div>';
					}
				}
			}
		}
		$hidden_fields .= '</div>';
		echo $hidden_fields;
	}
	
	
	
	/**
	 * printing table data.
	 * 
	 * @param string $column_name
	 * @param post object $post
	 * @param product object $the_product
	 */
	public function print_column_data( $column_name, $post, $the_product ){
		
		global $cedumbamazonhelper;
		$product_id = $the_product->get_id();
		$edit_link = get_edit_post_link( $post->ID );
		
		$classes = "$column_name column-$column_name check-column";
		
		$data = 'data-colname="'.$column_name.'"';
		
		$selectedMarketplace = $this->_umbFramework;
		switch ( $column_name ) {
			case 'cb':
				echo '<td class="'.$classes.'" '.$data.'>';
				if ( current_user_can( 'edit_post', $post->ID ) ):
					echo '<label class="screen-reader-text" for="cb-select-'.$post->ID.'">';
					echo 'Select '._draft_or_post_title($post);
					echo '</label>';
					echo '<input id="cb-select-'.$post->ID.'" type="checkbox" name="post[]" value="'.$post->ID.'" />';
					echo '<div class="locked-indicator"></div>';
			 	endif;
			 	echo '</td>';
				break;
			case 'thumb' :
				echo '<td class="ced_umb_amazon_thumbnail '.$classes.'" '.$data.'>';
				echo '<a href="' . $edit_link . '">' . $the_product->get_image( 'thumbnail' ) . '</a>';
				echo '</td>';
				break;
			case 'name' :
				$this->_colummn_title($post);
				break;
			
			
			case 'current_sku':
				echo '<td class="ced_umb_amazon_mp_td '.$classes.'" '.$data.'>';
					
					echo get_post_meta($post->ID,'_sku',true);
				echo '</td>';
				break;
			case 'new_sku':
				echo '<td class="ced_umb_amazon_new_sku '.$classes.'" '.$data.'>';
					
				$this->ced_get_new_sku($post,$post->ID);
				echo '</td>';		
				break;
			case 'lowest_price':
				echo '<td class="ced_umb_amazon_com_price '.$classes.'" '.$data.'>';
					
				$this->ced_get_lowest($post->ID);
				echo '</td>';
				break;
			case 'competitive':
				echo '<td class="ced_umb_amazon_com_price '.$classes.'" '.$data.'>';
					
				$this->ced_get_competitve($post->ID);
				echo '</td>';		
				break;
			case 'buy_box':
				echo '<td class="ced_umb_amazon_com_price '.$classes.'" '.$data.'>';
					
				$this->ced_get_buy_box($post->ID);
				echo '</td>';
				break;
			default :
				echo '<td class="'.$classes.'" '.$data.'>';
					do_action('ced_umb_amazon_render_extra_column_on_manage_product_section', $column_name, $post, $the_product );
				echo '</td>';
				break;
		}
	}
	
	function ced_get_lowest($post_ID){
		$lowest_price = get_post_meta($post_ID , 'lowest_price',true);
		if(!empty($lowest_price)){
			echo $lowest_price;
		}
		else{
			_e('No Lowest Price','ced-amazon-lister');
		}
		 

	}
	
	function ced_get_buy_box($post_ID)
	{
		
		$buybox_price = get_post_meta($post_ID, 'buybox_price',true);
		if(!empty($buybox_price)){
			echo $buybox_price;
		}
		else{
			_e('No Buy Box Price','ced-amazon-lister');
		}
	}
	function ced_get_competitve($post_ID)
	{
		$currency = get_post_meta($post_ID, 'ced_competitve_currency', true);
		$amount = get_post_meta($post_ID, 'ced_competitve_amount', true);
		if(!empty($amount)){
			echo $currency.$amount;
		}
		else{
			_e('No Competitive Price','ced-amazon-lister');
		}
		
	}
	function ced_get_new_sku($post,$post_ID)
	{
		

		$product_data = wc_get_product($post_ID);
		
		$sku_settings = get_option('ced_sku_settings_array',true);
		$ced_sku_prefix = (isset($sku_settings['ced_sku_prefix']) && $sku_settings['ced_sku_prefix'] != null) ? $sku_settings['ced_sku_prefix'] : "";
		$ced_simple_sku = (isset($sku_settings['ced_simple_sku']) && $sku_settings['ced_simple_sku'] != null) ? $sku_settings['ced_simple_sku'] : "ced_first_letter";
		$ced_variable_sku = (isset($sku_settings['ced_variable_sku']) && $sku_settings['ced_variable_sku'] != null) ? $sku_settings['ced_variable_sku'] : "ced_full_attr";

		if($ced_sku_prefix != null && $ced_sku_prefix != ""){
			$ced_sku_prefix.="-";
		}
		$product_type = $product_data->get_type();
		$ced_title = get_the_title($post_ID);
		$ced_title = explode(" ", $ced_title);
		if($product_type == 'simple')
		{
			if($ced_simple_sku == "ced_first_letter")
			{
				foreach ($ced_title as $key => $value) {
					$ced_sku_prefix.=substr($value, 0,1)."-";
				}	
				$ced_sku_prefix = chop($ced_sku_prefix, '-');
				echo $ced_sku_prefix;
			}
			if($ced_simple_sku == "ced_first_two_letter")
			{
				foreach ($ced_title as $key => $value) {
					$ced_sku_prefix.=substr($value, 0,2)."-";
				}	
				$ced_sku_prefix = chop($ced_sku_prefix, '-');
				echo $ced_sku_prefix;
			}

		}
			
	}
	/**
	 * caching mechanism for checking if 
	 * data available for listing.
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function has_product_data(){
		return !empty($this->_loop);
	}
	
	/**
	 * items available for listing.
	 * 
	 * @since 1.0.0
	 * @see WP_List_Table::has_items()
	 */
	public function has_items(){
		$per_page = apply_filters( 'ced_umb_amazon_products_per_page', 10 );
	
		$current_page = $this->get_pagenum();
		
		$args = array(
				'post_type' 		=> array('product'),
				'post_status' 		=> 'publish',
				'paged'				=> $current_page,
				'posts_per_page'    => $per_page,
				'tax_query' => array(
			        array(
			            'taxonomy' => 'product_type',
			            'field'    => 'slug',
			            'terms'    => 'simple', 
			        ),
			    ),
		);
		
		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = $_REQUEST['s'];
		}

		
		$loop = new WP_Query($args);
		
		$this->_loop = $loop;
		
		if($loop->have_posts()){
			return true;
		}else{
			return false;
		}
	}
	
	
	
	
}

endif;