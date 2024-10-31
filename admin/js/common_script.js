
// Common toggle code
jQuery(document).ready(function(){
	jQuery(document).on('click','.ced_umb_amazon_toggle',function(){
		jQuery(this).next('.ced_umb_amazon_toggle_div').slideToggle('slow');
	});
});
// Market Place JQuery End

//jquery for file status.
jQuery(document).ready(function(){
	jQuery(document).on('click','.ced_umb_amazon_updateFileInfo',function(){
		var requestId = jQuery(this).attr('requestid');
		var marketplace = jQuery(this).attr('framework');
		var fileId = jQuery(this).attr('fileId');
		if(!requestId.length || !marketplace.length || !fileId.length){
			alert("An unexpected error occured, please try again later.");
			return;
		}
		
		jQuery.post(
				common_action_handler.ajax_url,
				{
					'action': 'umb_get_file_status',
					'requestId' : requestId,
					'fileId' : fileId,
					'marketplace' : marketplace
				},
				function(response){
					alert(response);
				}
		);
	});
	
	
	jQuery(document).on('change','.ced_umb_amazon_select_cat_profile',function(){
		jQuery(".umb_current_cat_prof").remove();
		var currentThis = jQuery(this);
		var catId  = jQuery(this).parent('td').attr('data-catId');
		var profId = jQuery(this).find(':selected').val();

		if(catId == null || typeof catId === "undefined" || catId == null || profId == "" || typeof profId === "undefined" || profId == null || profId == "--Select Profile--")
		{
			return;
		}
		jQuery('#ced_umb_amazon_marketplace_loader').show();
		jQuery.post(
				common_action_handler.ajax_url,
				{
					'action': 'ced_umb_amazon_select_cat_prof',
					'catId' : catId,
					'profId' : profId,
				},
				function(response)
				{
					jQuery('#ced_umb_amazon_marketplace_loader').hide();
					response = jQuery.parseJSON(response);
					if(response.status == "success")
					{
						currentThis.parent('td').next('td').text(response.profile);
						var successHtml = '<div class="notice notice-success umb_current_cat_prof"><p>Process Success.</p></div>';
						jQuery('.ced_umb_amazon_pages_wrapper').children('form').append(successHtml);
					}
					else{
						var errorHtml = '<div class="notice notice-error umb_current_cat_prof"><p>Process Failed.</p></div>';
						jQuery('.ced_umb_amazon_pages_wrapper').children('form').append(errorHtml);
					}
				}
		);
	});	
	jQuery(document).on('click','.ced_umb_amazon_product_status',function(){
		jQuery('#ced_umb_amazon_marketplace_loader').show();
		var pId = jQuery(this).attr('data-id');
		var marketplace = jQuery(this).attr('data-marketplace');
		var ths = jQuery(this);
		jQuery.post(
				common_action_handler.ajax_url,
				{
					'action': 'ced_umb_amazon_current_product_status',
					'prodId' : pId,
					'marketplace' : marketplace
				},
				function(response)
				{
					jQuery('#ced_umb_amazon_marketplace_loader').hide();
					var html = "<p>"+response+"</p>";
					ths.replaceWith(html);
				}
		);
	})
	jQuery(document).on('click','.ced_umb_amazon_cron',function(){
		jQuery('#ced_umb_amazon_marketplace_loader').show();
	jQuery.ajax({
		url : common_action_handler.ajax_url,
		type : 'post',
		data : {
			action : 'ced_umb_amazon_cron_job',
		},
		success : function( response ) 
		{
			jQuery('#ced_umb_amazon_marketplace_loader').hide();
		}
	});
	
	});
	
});