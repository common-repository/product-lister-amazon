<?php
if(!session_id()) {
	session_start();
}

global $wpdb;
$table_name = $wpdb->prefix.ced_umb_amazon_PREFIX.'profiles';

if( isset($_POST['add_meta_keys']) || isset($_POST['saveProfile']) ) {
	
	// if(isset($_POST['unique_post']) && count($_POST['unique_post']) > 0){
	// 	$all_meta 	=	 array();
	// 	$metaKeysToAdd 	=	 $_POST['unique_post'];
	// 	if(is_array($metaKeysToAdd)) {
	// 		$all_meta = $metaKeysToAdd;
	// 	}
	// 	update_option('CedUmbProfileSelectedMetaKeys', $all_meta);
	// }

	$profileid = isset($_POST['profileID']) ? sanitize_text_field($_POST['profileID']) : false;
	$profileName = isset($_POST['profile_name']) ? sanitize_text_field($_POST['profile_name']) : '';
	if(empty($_POST['profile_name'])){
		$notice['message'] = __('Profile Name is Required.','ced-amazon-lister');
		$notice['classes'] = "notice notice-error";
		$validation_notice[] = $notice;
		$_SESSION['ced_umb_amazon_validation_notice'] = $validation_notice;
		return;
	}
	$is_active = isset($_POST['enable']) ? '1' : '0';
	$marketplaceName = isset($_POST['marketplaceName']) ? sanitize_text_field($_POST['marketplaceName']) : 'all';
	
	$updateinfo = array();
	
	foreach ($_POST['ced_umb_amazon_required_common'] as $key) {
		$arrayToSave = array();
		isset($_POST[$key][0]) ? $arrayToSave['default']=$_POST[$key][0] : $arrayToSave['default']='';
		if($key == '_umb_'.$marketplaceName.'_subcategory') {
			isset($_POST[$key]) ? $arrayToSave['default']=$_POST[$key] : $arrayToSave['default']='';
		}
		isset($_POST[$key.'_attibuteMeta']) ? $arrayToSave['metakey']=$_POST[$key.'_attibuteMeta'] : $arrayToSave['metakey']='null';
		$updateinfo[$key] = $arrayToSave;
	}

	$updateinfo = apply_filters('umb_save_additional_profile_info',$updateinfo);
	$updateinfo['selected_product_id'] = isset($_POST['selected_product_id']) ? $_POST['selected_product_id'] : '';
	$updateinfo['selected_product_name'] = isset($_POST['ced_umb_amazon_pro_search_box']) ? $_POST['ced_umb_amazon_pro_search_box'] : '';
	
	$updateinfo = json_encode($updateinfo);

	if($profileid){
		$wpdb->update($table_name, array('name'=>$profileName,'active'=>$is_active,'marketplace'=>$marketplaceName,'profile_data'=>$updateinfo), array('id'=>$profileid));
		
		$notice['message'] = __('Profile Updated Successfully.','ced-amazon-lister');
		$notice['classes'] = "notice notice-success";
		$validation_notice[] = $notice;
		$_SESSION['ced_umb_amazon_validation_notice'] = $validation_notice;
		
	}else{
		$wpdb->insert($table_name, array('name'=>$profileName,'active'=>$is_active,'marketplace'=>$marketplaceName,'profile_data'=>$updateinfo));
		global $wpdb;
		$prefix = $wpdb->prefix . ced_umb_amazon_PREFIX;
		$tableName = $prefix.'profiles';
		$sql = "SELECT * FROM `".$tableName."` ORDER BY `id` DESC";
		$queryData = $wpdb->get_results($sql,'ARRAY_A');
		$profileid = $queryData[0]['id'];

		$notice['message'] = __('Profile Created Successfully.','ced-amazon-lister');
		$notice['classes'] = "notice notice-success";
		$validation_notice[] = $notice;
		$_SESSION['ced_umb_amazon_validation_notice'] = $validation_notice;

		$redirectURL = get_admin_url().'admin.php?page=umb-amazon-profile&action=edit&profileID='.$profileid;
		wp_redirect($redirectURL);
		die;
	}
}

?>