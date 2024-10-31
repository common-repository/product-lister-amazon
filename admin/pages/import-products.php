<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$directorypath = CED_MAIN_DIRPATH;
if(!class_exists("AmazonReportRequest"))
{
	require($directorypath.'marketplaces/amazon/lib/amazon/includes/classes.php');
}
$marketplaceid = get_option('ced_umb_amazon_amazon_configuration',true);

global $cedumbamazonhelper;		
if(isset($_POST['request_list']))
{
	if(isset($_POST['request_type']) && $_POST['request_type'] != "")
	{
		$request_type = $_POST['request_type'];		
		$amz = new AmazonReportRequest();
		$amz->setMarketplaces($marketplaceid['marketplace_id']);
		if($request_type == "all")
		{			
			$amz->setReportType('_GET_MERCHANT_LISTINGS_ALL_DATA_');			
		}
		elseif($request_type == "active")
		{
			$amz->setReportType('_GET_MERCHANT_LISTINGS_DATA_');
		}
		elseif($request_type == "inactive")
		{
			$amz->setReportType('_GET_MERCHANT_LISTINGS_INACTIVE_DATA_');
		}
		$amz->requestReport();

		$report_response = $amz->getResponse();
		$report_response_id = $report_response['ReportRequestId'];

		update_option('ced_amazon_request_report_id',  $report_response_id);
		$notice['message'] = __('Product Report Request List Submitted','ced-amazon-lister');
		$notice['classes'] = "notice notice-info";
		$validation_notice[] = $notice;
		$cedumbamazonhelper->umb_print_notices($validation_notice);
			
	}
}
if(isset($_POST['get_request_list'])){
	$request_id = get_option('ced_amazon_request_report_id', false);
	
	if(isset($request_id) && $request_id != null){
		
		$amz2 = new AmazonReportRequestList(); 
		$amz2->setRequestIds(array($request_id));
		$amz2->fetchRequestList(false);
		$report_status = $amz2->getStatus(0);
		
		if($report_status == '_DONE_')
		{
			$report_id = $amz2->getReportId(0);
			$amz3 = new AmazonReport();
			$amz3->setReportId($report_id);

			$amz3->fetchReport();
			
			$data = $amz3->getRawReport();
			$data = explode("\n", $data);
			$fetched_data = array();


			global $wpdb;
			$table_name = $wpdb->prefix . 'postmeta';
			$retrieve_sku = $wpdb->get_results(" SELECT `meta_value` FROM `wp_postmeta` WHERE `meta_key` LIKE '_sku' AND `meta_value` != ''  ");
			$sku_array = array();
			foreach ($retrieve_sku as $key => $value) {
				$sku_array[] = $value->meta_value;
			}

			foreach ($data as $key => $value) {
				$line = explode("\t", $value);
				
				if( $line != null && $line[0] != null)
				{
					
					if(!in_array($line[3], $sku_array)){
						$fetched_data[$key]['id']=$line[2];
						$fetched_data[$key]['name']=$line[0];
						$fetched_data[$key]['sku']=$line[3];
						$fetched_data[$key]['asin']=$line[16];
						$fetched_data[$key]['price']=$line[4];
					}					
				}				
			}
			update_option('ced_list_requested_products', $fetched_data);
			$notice['message'] = __('Products fetched successfully','ced-amazon-lister');
			$notice['classes'] = "notice notice-success";
			$validation_notice[] = $notice;
			$cedumbamazonhelper->umb_print_notices($validation_notice);	
		}
		else
		{			
			$notice['message'] = __('Report is still in process','ced-amazon-lister');
			$notice['classes'] = "notice notice-info";
			$validation_notice[] = $notice;
			$cedumbamazonhelper->umb_print_notices($validation_notice);	
		}
		
	}
}

require_once ced_umb_amazon_DIRPATH.'admin/helper/class-ced-umb-import-products.php';
?>
	<div>
		<select name="request_type">
			<option value=""><?php echo __('Select','ced-amazon-lister') ?></option>
			<option value="all"><?php echo __('List All','ced-amazon-lister') ?></option>
			<option value="active"><?php echo __('Active List','ced-amazon-lister') ?></option>
			<option value="inactive"><?php echo __('Inactive List','ced-amazon-lister') ?></option>
		</select>
		<input type="submit" class="button" value="<?php echo __('Request List','ced-amazon-lister') ?>" name="request_list">
		<input type="submit" class="button" value="<?php echo __('List Requested Products','ced-amazon-lister') ?>" name="get_request_list">
	</div>
<?php
$import_lister = new ced_umb_amazon_import_products();
$import_lister->prepare_items();
$import_lister->display();