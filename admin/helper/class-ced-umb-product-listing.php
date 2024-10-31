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

if( !class_exists( 'ced_umb_amazon-product-lister' ) ) :

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
class ced_umb_amazon_product_lister extends WP_List_Table {
	
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
	 * all profile associative array.
	 * 
	 * @since 1.0.1
	 */
	private $_profileArray;
	
	/**
	 * Constructor.
	 * 
	 * @since 1.0.0
	 */
	function __construct(){

		global $status, $page, $cedumbamazonhelper;
		//$marketPlaces = get_option('ced_umb_amazon_activated_marketplaces',true);
		$marketPlaces = get_enabled_marketplacesamazon();
		$marketPlace = is_array($marketPlaces) ? $marketPlaces[0] : "";
		$this->_umbFramework = isset($_REQUEST['section']) ? $_REQUEST['section'] : $marketPlace;
		parent::__construct( array(
				'singular'  => 'ced_umb_amazon_mp',     
				'plural'    => 'ced_umb_amazon_mps',   
				'ajax'      => true        
		) );
		
		wp_enqueue_script('inline-edit-post');
		wp_enqueue_script('heartbeat');
		$this->_profileArray = $cedumbamazonhelper->ced_umb_profile_details(array('name'));
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
				'profile' => __('Profile', 'ced-amazon-lister'),
				'price' => __('Selling Price', 'ced-amazon-lister'),
				'qty' => __('Inventory','ced-amazon-lister'),
				
				'isReady'  => __( 'Ready To Upload', 'ced-amazon-lister' ),
		);
		$columns = apply_filters('ced_umb_amazon_alter_columns_in_manage_product_section',$columns);
		return $columns;
	}
	
	/**
	 * supported bulk actions for managing products.
	 * 
	 * @since 1.0.0
	 * @see WP_List_Table::get_bulk_actions()
	 */
	public function bulk_actions( $which = '' ){
		
		if($which == 'top'):
			
			$actions = array(
					'upload'    => __( 'Upload', 'ced-amazon-lister' ),
			);
			
			//$marketplaces = $this->get_active_marketplaces();
			$marketplaces = get_enabled_marketplacesamazon();
			if(!count($marketplaces))
				return;
			echo '<div class="ced_umb_amazon_top_wrapper">';
			echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __( 'Select bulk action', 'ced-amazon-lister' ) . '</label>';
			echo '<select name="action" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
			echo '<option value="-1">' . __( 'Bulk Actions', 'ced-amazon-lister' ) . "</option>\n";
			
			foreach ( $actions as $name => $title ) {
				$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';
			
				echo "\t" . '<option value="' . $name . '"' . $class . '>' . $title . "</option>\n";
			}
			
			echo "</select>\n";
			
			submit_button( __( 'Apply', 'ced-amazon-lister' ), 'action', '', false, array( 'id' => "ced_umb_amazon_doaction", 'name' => 'doaction' ) );
			echo "\n";
			echo '</div>';

		endif;
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
	
		$per_page = apply_filters( 'ced_umb_amazon_products_per_page', 10 );
		$post_type = 'product';
	
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
	
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		$current_page = $this->get_pagenum();
		
		// Query args
		$args = array(
				'post_type'           => $post_type,
				'posts_per_page'      => $per_page,
				'ignore_sticky_posts' => true,
				'paged'               => $current_page
		);
		
		// Handle the status query
		if ( ! empty( $_REQUEST['status'] ) ) {
			$args['post_status'] = sanitize_text_field( $_REQUEST['status'] );
		}

		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = sanitize_text_field( $_REQUEST['s'] );
		}

		if ( ! empty( $_REQUEST['pro_cat_sorting'] ) ) {
			$pro_cat_sorting = isset($_GET['pro_cat_sorting']) ? $_GET['pro_cat_sorting'] : '';
			if( $pro_cat_sorting != '' ) {
				$selected_cat = array($pro_cat_sorting);
				$tax_query = array();
				$tax_queries = array();
				$tax_query['taxonomy'] = 'product_cat';
				$tax_query['field'] = 'id';
				$tax_query['terms'] = $selected_cat;
				$args['tax_query'][] = $tax_query;
			}	
		}


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
		

		if ( ! empty( $_REQUEST['status_sorting'] ) ) {
			$status_sorting = isset($_GET['status_sorting']) ? $_GET['status_sorting'] : '';
			$availableMarketPlaces = get_enabled_marketplacesamazon();
			if(is_array($availableMarketPlaces) && !empty($availableMarketPlaces)) {
				$tempsection = $availableMarketPlaces[0];
				if(isset($_GET['section'])) {
					$tempsection = esc_attr($_GET['section']);
				}
			}
		}
		else {
			$status_sorting = isset($_GET['status_sorting']) ? $_GET['status_sorting'] : '';
		}
		
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
							if( $status_sorting == 'notUploaded' ) {
								$idToUse = $loop->post->ID;
								$metaKey = 'ced_umb_amazon_'.$tempsection.'_status';
								$uploadStatus = get_post_meta($idToUse,$metaKey,true);
								if( $uploadStatus == 'PUBLISHED') {
									continue;
								}
							}
							$this->get_product_row_html($loop->post);
						}
					}else{
						if( $status_sorting == 'notUploaded' ) {
							$idToUse = $loop->post->ID;
							$metaKey = 'ced_umb_amazon_'.$tempsection.'_status';
							$uploadStatus = get_post_meta($idToUse,$metaKey,true);
							if( $uploadStatus == 'PUBLISHED') {
								continue;
							}
						}
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
		
		
		echo '<tr id="post-'.$product_id.'" class="ced_umb_amazon_inline_edit">';
		foreach($columns as $column_id => $column_name){
			$this->print_column_data($column_id, $post, $_product);
		}
		echo '</tr>';
		
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
			$actions['profile hide-if-no-js'] = '<a href="javascript:;" data-proid = "'.$post->ID.'" class="ced_umb_amazon_profile" title="' . esc_attr( __( 'Assign profile to this item', 'ced-amazon-lister' ) ) . '">' . __( 'Profile', 'ced-amazon-lister' ) . '</a>';
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
		$woo_ver = WC()->version;
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
		if($woo_ver < "3.0.0" && $woo_ver < "2.7.0")
		{
			$hidden_fields .= '<div class="_sku" type="_text_input">'.$the_product->sku.'</div>';
		}else
		{
			$hidden_fields .= '<div class="_sku" type="_text_input">'.$the_product->get_sku().'</div>';
		}
		
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
		
		$classes = "$column_name column-$column_name";
		
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
			
			case 'profile':
				echo '<td class="ced_umb_amazon_mp_td '.$classes.'" '.$data.'>';
				//echo 'need editing..';
				$isProfileAssigned = get_post_meta($post->ID,'ced_umb_amazon_profile',true);
				if(isset($isProfileAssigned) && !empty($isProfileAssigned) && $isProfileAssigned){
					
					$profile_name = $cedumbamazonhelper->ced_umb_profile_details(array('id'=>$isProfileAssigned));
					if(!empty($profile_name)){
						echo $profile_name;
						echo '<img width="16" height="16" src="'.ced_umb_amazon_URL.'admin/images/remove.png" data-prodid="'.$post->ID.'" class="umb_remove_profile ced_umb_amazon_IsReady">';
					}
					else{
						echo '<a href="javascript:void(0);" data-proid="'.$product_id.'" class="ced_umb_amazon_profile" title="Assign profile to this item" style="color:red;">'.__('Not Assigned','ced-amazon-lister').'</a>';
					}
					
					
				}else{
					echo '<a href="javascript:void(0);" data-proid="'.$product_id.'" class="ced_umb_amazon_profile" title="Assign profile to this item" style="color:red;">'.__('Not Assigned','ced-amazon-lister').'</a>';
				}
				echo '</td>';
				break;
			case 'price':
				echo '<td class="ced_umb_amazon_mp_td '.$classes.'" '.$data.'>';
					echo get_marketplace_price_amazon($post->ID,$selectedMarketplace);
				echo '</td>';
				break;
			case 'qty':
				echo '<td class="ced_umb_amazon_mp_td '.$classes.'" '.$data.'>';
					echo get_marketplace_qty_amazon($post->ID,$selectedMarketplace);
				echo '</td>';
				break;
		
			case 'isReady':
				$html = '<div class="">';
				$marketplace = trim($selectedMarketplace);

				 $file_name = ced_umb_amazon_DIRPATH.'marketplaces/'.$selectedMarketplace.'/class-'.$selectedMarketplace.'.php';
				if( file_exists( $file_name ) ){
					require_once $file_name;
					$class_name = 'ced_umb_amazon_'.$marketplace.'_manager';
					if( class_exists( $class_name) ){
						$instance = $class_name::get_instance();
						
						if( !is_wp_error($instance) ){
							$status = $instance->validate($post->ID);
							
							if(is_array($status)){
								$is_ready = isset($status['isReady']) ? $status['isReady'] : false;
								if($is_ready){

									$html .= '<span class="ced_umb_amazon_proReady">'.$selectedMarketplace.':'.__('Ready','ced-amazon-lister').' </span></div>';
								}else{
									$html .= '<span class="ced_umb_amazon_proMissing ced_umb_amazon_IsReady"> <b style="color:red">'.__('Missing Listing Data','ced-amazon-lister').'</b> </span><div class="ced_umb_amazon_MissingData">';

									$errorArray = isset($status['missingData']) ? $status['missingData'] : array();
									$html .= $this->printMissingData($errorArray);
									$html .= '</div>';
									$html .= '</div>';
								}
							}
						}
					}
				}
				
				echo '<td class="ced_umb_amazon_mp_td '.$classes.'" '.$data.'>';
					echo $html;
				echo '</td>';
				break;

			case 'add_to_upload_queue':
				$items_in_queue = get_option( 'ced_umb_amazon_'.$selectedMarketplace.'_upload_queue', array() );
				if( in_array($product_id, $items_in_queue) ) {
					$selectedPreviously = 'checked="checked"';
				}
				else {
					$selectedPreviously = '';
				}
				echo '<td class="'.$classes.'" '.$data.'>';
				echo '<center>';
				echo '<input type="checkbox" class="ced_umb_amazon_marketplace_add_to_upload_queue_123" data-id="'.$product_id.'" data-marketplace="'.$selectedMarketplace.'" '.$selectedPreviously.'>';
				echo '</center>';
				echo '</td>';
				break;

			default :
				echo '<td class="'.$classes.'" '.$data.'>';
					do_action('ced_umb_amazon_render_extra_column_on_manage_product_section', $column_name, $post, $the_product );
				echo '</td>';
				break;
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


		if ( ! empty( $_REQUEST['pro_cat_sorting'] ) ) {
			$pro_cat_sorting = isset($_GET['pro_cat_sorting']) ? $_GET['pro_cat_sorting'] : '';
			if( $pro_cat_sorting != '' ) {
				$selected_cat = array($pro_cat_sorting);
				$tax_query = array();
				$tax_queries = array();
				$tax_query['taxonomy'] = 'product_cat';
				$tax_query['field'] = 'id';
				$tax_query['terms'] = $selected_cat;
				$args['tax_query'][] = $tax_query;
			}	
		}

		if ( ! empty( $_REQUEST['pro_type_sorting'] ) ) {
			$pro_type_sorting = isset($_GET['pro_type_sorting']) ? $_GET['pro_type_sorting'] : '';
			if( $pro_type_sorting != '' ) {
				$selected_type = array($pro_type_sorting);
				$tax_query = array();
				$tax_queries = array();
				$tax_query['taxonomy'] = 'product_type';
				$tax_query['field'] = 'id';
				$tax_query['terms'] = $selected_type;
				$args['tax_query'][] = $tax_query;
			}	
		}
		
		
		$loop = new WP_Query($args);
		
		$this->_loop = $loop;
		
		if($loop->have_posts()){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Outputs the hidden row displayed when inline editing
	 *
	 * @since 1.0.0.
	 *
	 * @global string $mode
	 */
	public function inline_edit() {
		global $mode;
		
		$screen = $this->screen;

		$post = get_default_post_to_edit( 'product' );
		$post_type_object = get_post_type_object( 'product' );

		$m = ( isset( $mode ) && 'excerpt' === $mode ) ? 'excerpt' : 'list';
		$can_publish = current_user_can( $post_type_object->cap->publish_posts );
	}
	
	/**
	 * Outputs the hidden profile section displayed to assign profile
	 * 
	 * @since 1.0.0.
	 *
	 * @global string $mode
	 */
	public function profle_section()
	{
		global $mode;
		
		$screen = $this->screen;
		
		$post = get_default_post_to_edit( 'product' );
		$post_type_object = get_post_type_object( 'product' );
		
		$m = ( isset( $mode ) && 'excerpt' === $mode ) ? 'excerpt' : 'list';
		$can_publish = current_user_can( $post_type_object->cap->publish_posts );
		
		require_once ced_umb_amazon_DIRPATH.'admin/partials/html-profile.php';
	}
	/**
	 * prepare missing data.
	 * 
	 * @since 1.0.0
	 */
	public function printMissingData($errors=array()){
		$html = '';
		$counter = 1;
		if(is_array($errors)){
			foreach($errors as $error){
				$html .= $counter.'. '.$error.'</br>';
				$counter++;
			}
		}
		return $html;
	}
}

endif;