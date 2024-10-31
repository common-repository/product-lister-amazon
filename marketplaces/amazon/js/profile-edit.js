jQuery(document).ready(function(){

	renderMarketplaceAttributesSectionHTML( jQuery('select[name^=_umb_amazon_category]'), jQuery('select[name^=_umb_amazon_category]').val(), jQuery('input#profileID').val() );
		
	jQuery(document.body).on( 'change', 'select[name^=_umb_amazon_category]', function() {
		renderMarketplaceAttributesSectionHTML( jQuery(this), jQuery(this).val(), jQuery('input#profileID').val() );
	});
	
	jQuery(document.body).on("change", ".ced_umb_amazon_amazon_product_type select", function(){
		var catproducttype = jQuery(this).val();
		var product_id = 0;
		if(product_id == null || product_id == '')
		{
			product_id = ced_umb_amazon_amazon_edit_profile_AJAX.product_id;
		}
		ced_umb_amazon_amazon_product_type_select(catproducttype, product_id);
	});
	
	//ced_umb_amazon_amazon_product_type_select( jQuery('select[name^=_umb_amazon_category]'), jQuery('select[name^=_umb_amazon_category]').val(), jQuery('input#profileID').val() );
	
	
	function ced_umb_amazon_amazon_product_type_select(catproducttype, product_id)
	{
		
		var catid = jQuery('select[name^=_umb_amazon_category]').val();
		var profileid = jQuery('input#profileID').val()
		
		//if(catid != 0 && product_id != 0)
		//{
			
			jQuery.ajax({
				url : ced_umb_amazon_amazon_edit_profile_AJAX.ajax_url,
				type : 'post',
				data : {
					action : 'fetch_amazon_category_product_type_profile',
					catproducttype : catproducttype,
					catid : catid,
					productid:product_id,
					profileid:profileid
				},
				success : function( response ) 
				{
					jQuery(".ced_umb_amazon_amazon_product_type_wrapper").html("");
					jQuery(".ced_umb_amazon_amazon_product_type_wrapper").html(response);
				}
			});
		//}
		
	}

	function renderMarketplaceAttributesSectionHTML( thisRef, categoryID, profileID ) {
		
		//if(categoryID != 0){
			
			jQuery("#ced_umb_amazon_marketplace_loader").show();
			jQuery.ajax({
				url : ced_umb_amazon_amazon_edit_profile_AJAX.ajax_url,
				type : 'post',
				data : {
					action : 'fetch_amazon_attribute_for_selected_category_for_profile_section',
					categoryID : categoryID,
					profileID : profileID
				},
				success : function( response ) 
				{
					var parentRef = jQuery(thisRef).parents( 'div.ced_umb_amazon_toggle_section_wrapper' );
					jQuery(parentRef).siblings('div.ced_umb_amazon_amazon_attribute_section').find('div.ced_umb_amazon_tabbed_section_wrapper').html(response);
					jQuery("#ced_umb_amazon_marketplace_loader").hide();
					jQuery( document.body ).trigger( 'init_tooltips' );
					var product_id = 0;
					var catproducttype = jQuery('body .ced_umb_amazon_amazon_product_type select').val();
					ced_umb_amazon_amazon_product_type_select(catproducttype, product_id)

				}
			});
		//}
	}
});	