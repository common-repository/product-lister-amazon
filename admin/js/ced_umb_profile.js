
jQuery(document).ready(function(){
	jQuery(document.body).on("click",".ced_umb_amazon_profile",function(){
		var prodId = jQuery(this).attr("data-proid");
		jQuery(".ced_umb_amazon_save_profile").attr("data-prodid",prodId);
		jQuery(".ced_umb_amazon_overlay").show();
	});
	jQuery(document.body).on("click",".ced_umb_amazon_overlay_cross",function(){
		jQuery(".ced_umb_amazon_overlay").hide();
	})
	jQuery(document.body).on("click",".umb_remove_profile",function(){

		var proId     = jQuery(this).attr("data-prodid");
		jQuery("#ced_umb_amazon_marketplace_loader").show();
		var profileId = 0;
		var data  = {
						"action"    : "ced_umb_amazon_save_profile",
						"proId"     : proId,
						"profileId" : profileId
					}
		jQuery.post(
					profile_action_handler.ajax_url,
					data,
					function(response)
					{
						jQuery("#ced_umb_amazon_marketplace_loader").hide();

						jQuery(".ced_umb_amazon_overlay").hide();
						if(response != "success")
						{
							alert("Failed");
						}
						else
						{
							window.location.reload();
						}	
					}
				)
			  .fail(function() {
				  jQuery("#ced_umb_amazon_marketplace_loader").hide();
				  alert( "Failed" );

			  })
	})
	
	jQuery(document.body).on("click",".ced_umb_amazon_save_profile",function(){

		var proId     = jQuery(this).attr("data-prodid");
		jQuery("#ced_umb_amazon_marketplace_loader").show();

		var profileId = jQuery(".ced_umb_amazon_profile_select option:selected").val();
		var data  = {
						"action"    : "ced_umb_amazon_save_profile",
						"proId"     : proId,
						"profileId" : profileId
					}
		jQuery.post(
					profile_action_handler.ajax_url,
					data,
					function(response) {
						jQuery("#ced_umb_amazon_marketplace_loader").hide();

						jQuery(".ced_umb_amazon_overlay").hide();
						if(response != "success") {
							alert("Failed");
						}
						else {
							window.location.reload();
						}	
					}
				)
			  .fail(function() {
				  jQuery("#ced_umb_amazon_marketplace_loader").hide();
				  alert( "Failed" );
			  });
	});


	/*
	* JS CODE TO ADD PRODUCT TO QUEUE TO UPLOAD
	*/
	jQuery(document.body).on( 'click', '.ced_umb_amazon_marketplace_add_to_upload_queue_123', function(){
		jQuery("#ced_umb_amazon_marketplace_loader").show();
		jQuery.ajax({
			url : profile_action_handler.ajax_url,
			type : 'post',
			data : {
				action : 'ced_umb_amazon_add_product_to_upload_queue_on_marketplace',
				marketplaceId : jQuery(this).attr('data-marketplace'),
				productId : jQuery(this).attr('data-id')
			},
			success : function( response ) 
			{
				jQuery("#ced_umb_amazon_marketplace_loader").hide();
			}
		});
	});

});


