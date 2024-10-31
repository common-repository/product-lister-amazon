<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

//header file.
require_once ced_umb_amazon_DIRPATH.'admin/pages/header.php';

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class ced_umb_amazon_Activity_Feed_Table_List extends WP_List_Table {

	public $currentMarketPlace = '';

	/** Class constructor */
	public function __construct($marketPlace='') {
		$this->currentMarketPlace = $marketPlace;
		parent::__construct( [
			'singular' => __( 'Activity feed', 'ced-amazon-lister' ), //singular name of the listed records
			'plural'   => __( 'Activity feeds', 'ced-amazon-lister' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );
	}

	/**
	 * Retrieve feeds 
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public function get_feeds( $per_page = 5, $page_number = 1 ) {

		global $wpdb;
		$prefix = $wpdb->prefix . ced_umb_amazon_PREFIX;
		$tableName = $prefix.'fileTracker';

		$sql = "SELECT * FROM `$tableName` WHERE `framework` LIKE '".$this->currentMarketPlace."' ORDER BY `id` DESC ";
		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

		$result = $wpdb->get_results($sql,'ARRAY_A');
		return $result;

	}

	public function get_count( ) {
		global $wpdb;
		$prefix = $wpdb->prefix . ced_umb_amazon_PREFIX;
		$tableName = $prefix.'fileTracker';
		$sql = "SELECT * FROM `$tableName` WHERE `framework` LIKE '".$this->currentMarketPlace."'";
		$result = $wpdb->get_results($sql,'ARRAY_A');
		return count($result);
	}


	
	/** Text displayed when no customer data is available */
	public function no_items() {
		_e( 'No feeds avaliable.', 'ced-amazon-lister' );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'status':
			case 'framework':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="feed_ids[]" value="%s" />', $item['id']
		);
	}

	function column_feedTime( $item ) {
		echo $item['time'];
		//do_action('umb_file_status_time',$item,$this->currentMarketPlace);
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_feedID( $item ) {
		$title = '<strong>' . $item['name'] . '</strong>';
		$actions = [
			'viewDetails' => sprintf( '<a href="?page=%s&action=%s&feedID=%s&section=%s">'.__('View Details','ced-amazon-lister').'</a>', esc_attr( $_REQUEST['page'] ), 'viewDetails', $item['id'], $this->currentMarketPlace ),
			'delete' => sprintf( '<a href="?page=%s&action=%s&feedID=%s&section=%s">'.__('Delete','ced-amazon-lister').'</a>', esc_attr( $_REQUEST['page'] ), 'delete', $item['id'], $this->currentMarketPlace )
		];
		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'feedID'    => __( 'Feed ID', 'ced-amazon-lister' ),
			'feedTime' => __( 'Feed Date & Time', 'ced-amazon-lister' ),
			//'product_ids' => __( 'Product Ids', 'ced-amazon-lister' ),
			//'framework' => __( 'Marketplace', 'ced-amazon-lister' ),
		];
		$columns = apply_filters( 'ced_umb_amazon_alter_feed_table_columns', $columns );
		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return $sortable_columns = array();
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk-delete' => 'Delete'
		];
		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		global $wpdb;

		$per_page = apply_filters( 'ced_umb_amazon_list_feeds_per_page', 10 );
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		
		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}
		
		$this->items = self::get_feeds( $per_page, $current_page );

		$count = self::get_count( );


		// Set the pagination
		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page )
		) );


		if(!$this->current_action()) {
			$this->items = self::get_feeds( $per_page, $current_page );
			$this->renderHTML();
		}
		else {
			$this->process_bulk_action();
		}
		
	}

	public function renderHTML() {
		?>
		<div class="ced_umb_amazon_wrap">
			<?php renderMarketPlacesLinksOnTopamazon($_GET['page']); ?>
			<h2 class="ced_umb_amazon_setting_header"><?php _e('Feed Status','ced-amazon-lister');?></h2>
			<div >
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
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								$this->display();
								?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
		<?php
	}

	public function process_bulk_action() {
		if(!session_id()) {
	        session_start();
	    }
		/** render configuration setup html of marketplace **/
		if( 'viewDetails' === $this->current_action() || ( isset($_GET['action']) && 'viewDetails' === $_GET['action'] )) {
			$urlToUse = get_admin_url().'admin.php?page=umb-amazon-fileStatus';
			( isset($_GET['section']) ) ? $urlToUse .= '&section='.$_GET['section'] : $urlToUse = $urlToUse;
			echo '<div class="ced_umb_amazon_wrap">';
				echo '<div class="back"><a href="'.$urlToUse.'">'.__('Go Back','ced-amazon-lister').'</a></div>';
				do_action( 'ced_umb_amazon_render_marketplace_feed_details', $_GET['feedID'], $this->currentMarketPlace );
			echo '<div>';
		}

		if( 'delete' === $this->current_action() || ( isset($_GET['action']) && 'delete' === $_GET['action'] ) ) {
			do_action( 'ced_umb_amazon_before_marketplace_feed_delete', $_GET['feedID'], $this->currentMarketPlace );
			
			$feedId = sanitize_text_field( $_GET['feedID'] );
			global $wpdb;
			$prefix = $wpdb->prefix . ced_umb_amazon_PREFIX;
			$tableName = $prefix.'fileTracker';
			$deleteStatus = $wpdb->delete($tableName,array('id'=>$feedId));
			if($deleteStatus) {
				$notice['message'] = __('Feed Deleted Successfully.','ced-amazon-lister');
				$notice['classes'] = "notice notice-success";
				$validation_notice[] = $notice;
				$_SESSION['ced_umb_amazon_validation_notice'] = $validation_notice;
			}
			else {
				$notice['message'] = __('Some Error Encountered.','ced-amazon-lister');
				$notice['classes'] = "notice notice-error";
				$validation_notice[] = $notice;
				$_SESSION['ced_umb_amazon_validation_notice'] = $validation_notice;
			}

			do_action( 'ced_umb_amazon_after_marketplace_feed_delete', $_GET['feedID'], $this->currentMarketPlace );

			$redirectURL = get_admin_url()."admin.php?page=umb-amazon-fileStatus";
			if(isset($_GET['section'])) {
				$redirectURL .= "&section=".$_GET['section'];
			}
			wp_redirect($redirectURL);
		}

		if( 'bulk-delete' === $this->current_action() ) {
			if(isset($_POST['feed_ids'])) {
				$feedsToDelete = $_POST['feed_ids'];

				global $wpdb;
				$prefix = $wpdb->prefix . ced_umb_amazon_PREFIX;
				$tableName = $prefix.'fileTracker';
				$sql = "DELETE FROM `".$tableName."` WHERE `id` IN (";
				foreach ($feedsToDelete as $id) {
					$sql .= $id.',';
				}
				$sql = rtrim($sql, ",");
				$sql .= ')';
				$deleteStatus = $wpdb->query($sql);
				if($deleteStatus) {
					$notice['message'] = __('Feeds Deleted Successfully.','ced-amazon-lister');
					$notice['classes'] = "notice notice-success";
					$validation_notice[] = $notice;
					$_SESSION['ced_umb_amazon_validation_notice'] = $validation_notice;
				}
				else {
					$notice['message'] = __('Some Error Encountered.','ced-amazon-lister');
					$notice['classes'] = "notice notice-error";
					$validation_notice[] = $notice;
					$_SESSION['ced_umb_amazon_validation_notice'] = $validation_notice;
				}

				$redirectURL = get_admin_url()."admin.php?page=umb-amazon-fileStatus";
				if(isset($_GET['section'])) {
					$redirectURL .= "&section=".$_GET['section'];
				}
				wp_redirect($redirectURL);
			}else {
				$notice['message'] = __('Select atleast one entry.','ced-amazon-lister');
				$notice['classes'] = "notice notice-error";
				$validation_notice[] = $notice;
				$_SESSION['ced_umb_amazon_validation_notice'] = $validation_notice;
				$redirectURL = get_admin_url()."admin.php?page=umb-amazon-fileStatus";
				wp_redirect($redirectURL);
			}
		}
	}

}

$availableMarketPlaces = get_enabled_marketplacesamazon();
if(is_array($availableMarketPlaces) && !empty($availableMarketPlaces)) {
	$section = $availableMarketPlaces[0];
	if(isset($_GET['section'])) {
		$section = esc_attr($_GET['section']);
	}
	$ced_umb_amazon_activity_feed_table_list = new ced_umb_amazon_Activity_Feed_Table_List($section);
	$ced_umb_amazon_activity_feed_table_list->prepare_items();
}
else{
	_e('<h3>Please validate Amazon marketplace first.</h3>','ced-amazon-lister');
}
?>