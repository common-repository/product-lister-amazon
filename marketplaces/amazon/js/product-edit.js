var currentRequest = null;

jQuery(document).ready(function(){
	
	renderMarketplaceAttributesSectionHTML( jQuery('select[name^=_umb_amazon_category]'), jQuery('select[name^=_umb_amazon_category]').val() , ced_umb_amazon_amazon_edit_product_script_AJAX.product_id , '' );

	jQuery(document.body).on( 'change', 'select[name^=_umb_amazon_category]', function() {
		
		var product_id = jQuery(this).parents('div.woocommerce_variation').find('input:hidden[name^=variable_post_id]').val();
		if(product_id == null || product_id == '')
		{
			product_id = ced_umb_amazon_amazon_edit_product_script_AJAX.product_id;
		}
		
		var categoryID = jQuery(this).parents('div.woocommerce_variation h3').next().find('select[name^=_umb_amazon_category]').val();
		if(categoryID == null || categoryID == '')
		{
			categoryID = jQuery(this).val();
		}
		/*console.log(categoryID);*/
			
		renderMarketplaceAttributesSectionHTML( jQuery(this), categoryID,  product_id, '' );
	});

	jQuery(document.body).on( 'click', 'div.woocommerce_variation h3', function() {
		var indexToUse = jQuery(this).find('input:hidden[name^=variable_post_id]').attr('name');
		indexToUse = indexToUse.split("]")[0].split("[")[1]; 
		var product_id = jQuery(this).find('input:hidden[name^=variable_post_id]').val();
		var categoryID = jQuery(this).next().find('select[name^=_umb_amazon_category]').val();
		var thisRef = jQuery(this).next().find('select[name^=_umb_amazon_category]');
		renderMarketplaceAttributesSectionHTML( thisRef, categoryID , product_id , indexToUse );
	});

	function renderMarketplaceAttributesSectionHTML( thisRef, categoryID, productID, indexToUse ) {
		
		/*console.log(categoryID);
		console.log(productID);
		console.log(indexToUse);*/
		
		jQuery.ajax({
			url : ced_umb_amazon_amazon_edit_product_script_AJAX.ajax_url,
			type : 'post',
			data : {
				action : 'fetch_amazon_attribute_for_selected_category',
				categoryID : categoryID,
				productID : productID,
				indexToUse : indexToUse
			},
			success : function( response ) 
			{
				if( jQuery(thisRef).parent().next().hasClass('ced_umb_amazon_amazon_attribute_section') ) 
				{
					jQuery(thisRef).parent().next().remove();
				}
				jQuery(thisRef).parent().after(response);
				jQuery( document.body ).trigger( 'init_tooltips' );
			}
		});
	}
	
	jQuery(document).on('click','#ced_umb_amazon_amazon_taxcode',function(){
		jQuery(".ced_umb_amazon_taxcode_overlay_wrapper").show();
	});
	
	jQuery(document).on('click','.ced_umb_amazon_cancel',function(){
		jQuery(".ced_umb_amazon_taxcode_overlay_wrapper").hide();
	});
	
	jQuery(document).on('click','.ced_umb_amazon_taxcode_overlay_wrapper table tbody tr',function(){
	
		var taxocde = jQuery(this).attr('data-taxcode');
		jQuery(this).parents(".ced_umb_amazon_taxcode_overlay_wrapper").next().find("input[name ^= 'ToolsAndHardware_productTaxCode']").val(taxocde);
		jQuery(".ced_umb_amazon_taxcode_overlay_wrapper").hide();
	});
	
	jQuery(document.body).on('keyup', '#ced_umb_amazon_amazon_taxcode_search', function(e){
		if(e.keyCode == 37 && e.keyCode == 38 && e.keyCode == 39 && e.keyCode == 40)
		{
			return;
		}
	
		var stringTobesearched = jQuery(this).val();
		if(stringTobesearched.length <= 1)
		{
			return;
		}
	
		var data = {'action' : 'ced_umb_amazon_taxcode_search',
					'stringTobesearched' : stringTobesearched}
		 
		currentRequest = jQuery.ajax({
			url : ced_umb_amazon_amazon_edit_product_script_AJAX.ajax_url,
			type : 'post',
			data : data,
			beforeSend : function()    
			{           
				if(currentRequest != null) 
				{
		            currentRequest.abort();
		        }
		    },
			success : function( response ) 
			{
				jQuery('#ced_umb_amazon_amazon_table tbody').html(response);
			}
		});
	});


	function ced_umb_amazon_amazon_product_type_select(catproducttype, product_id)
	{
		var catid = jQuery('#_umb_amazon_category').val();
		
		jQuery.ajax({
			url : ced_umb_amazon_amazon_edit_product_script_AJAX.ajax_url,
			type : 'post',
			data : {
				action : 'fetch_amazon_category_product_type',
				catproducttype : catproducttype,
				catid : catid,
				productid:product_id
			},
			success : function( response ) 
			{
				jQuery(".ced_umb_amazon_amazon_product_type_wrapper").html("");
				jQuery(".ced_umb_amazon_amazon_product_type_wrapper").html(response);
			}
		});
	}

	jQuery(document.body).on("change", ".ced_umb_amazon_amazon_product_type select", function(){
		var catproducttype = jQuery(this).val();
		var product_id = jQuery(this).parents('div.woocommerce_variation').find('input:hidden[name^=variable_post_id]').val();
		if(product_id == null || product_id == '')
		{
			product_id = ced_umb_amazon_amazon_edit_product_script_AJAX.product_id;
		}
		ced_umb_amazon_amazon_product_type_select(catproducttype, product_id);
	});
});	