

jQuery(document).ready(function() {
	jQuery(document.body).on( 'click', 'input:checkbox[class=ced_umb_amazon_add_del_meta_keys]', function() {
		//var profileID = jQuery('#profileID').val();
		if(jQuery(this).is(':checked')) {
			var metaKey = jQuery(this).attr('id');
			updateMetaKeysInDBForProfile( metaKey, 'append' );
		}
		else {
			var metaKey = jQuery(this).attr('id');
			updateMetaKeysInDBForProfile( metaKey, 'delete' );
		}
	});
});	

/**
* updating Amazon categories
* 
*/
function updateMetaKeysInDBForProfile( metaKey , actionToDo ) {
	jQuery("#ced_umb_amazon_marketplace_loader").show();
	jQuery.ajax({
		url : ced_umb_amazon_profile_edit_add_script_AJAX.ajax_url,
		type : 'post',
		data : {
			action : 'ced_umb_amazon_updateMetaKeysInDBForProfile',
			metaKey : metaKey,
			actionToDo : actionToDo
		},
		success : function( response ) 
		{
			jQuery("#ced_umb_amazon_marketplace_loader").hide();
		}
	});
}


jQuery(document).ready(function() {
    
    jQuery('#ced_umb_amazon_metakeys_list').DataTable({
    	"pageLength": 10,
    	"aaSorting": [ ],
    	"columnDefs": [ {
			"targets": 1,
			"orderable": false
			} ]
    });

});

jQuery(document).ready(function() {
    
    jQuery(document.body).on('click','div.ced_umb_amazon_tabbed_head_wrapper ul li', function(){
    	
    	jQuery(this).siblings('li.active').removeClass();
    	jQuery(this).addClass('active');
    	var currentIndex = jQuery( this ).index();
    	var nextDivRef = jQuery(this).parents('div.ced_umb_amazon_tabbed_head_wrapper').next();
    	var v = jQuery("div",jQuery(nextDivRef)).eq( currentIndex ).html();
    	jQuery("div",jQuery(nextDivRef)).eq( currentIndex ).siblings('div.active').removeClass('active');
    	jQuery("div",jQuery(nextDivRef)).eq( currentIndex ).addClass('active');
    
    });

});

jQuery(document).ready(function() {

	jQuery(document.body).on('click','table#ced_umb_amazon_products_matched td', function(){
    	var selectedProductId = jQuery(this).attr('product-id');
    	jQuery("#selected_product_id").val(selectedProductId);
    	var productName = jQuery(this).text();
    	jQuery("#ced_umb_amazon_pro_search_box").val(productName);
    	jQuery("#ced_umb_amazon_suggesstion_box").hide();
		jQuery("#ced_umb_amazon_suggesstion_box").html('');
    	renderMarketplaceAttributesSectionHTML( jQuery(this), selectedProductId, jQuery('input#profileID').val() );
    });

	// jQuery(document.body).on( 'change', 'select[name=linked_proID]', function() {
	// 	var productName = jQuery("select[name=linked_proID] option:selected").text();
	// 	//jQuery('h2#ced_umb_amazon_selected_pro_name b').text(productName);
	// 	//jQuery('input#ced_umb_amazon_hidden_linked_proID').val(jQuery(this).val());
	// 	renderMarketplaceAttributesSectionHTML( jQuery(this), jQuery(this).val(), jQuery('input#profileID').val() );
	// });

	function renderMarketplaceAttributesSectionHTML( thisRef, selectedProductId, profileID ) {
		jQuery.ajax({
			url : ced_umb_amazon_profile_edit_add_script_AJAX.ajax_url,
			type : 'post',
			data : {
				action : 'fetch_all_meta_keys_related_to_selected_product',
				selectedProductId : selectedProductId,
				profileID : profileID
			},
			success : function( response ) {
				
				jQuery('div#ced_umb_amazon_metakeys_list_wrapper').replaceWith(response);
				//jQuery('table#ced_umb_amazon_metakeys_list').replaceWith(response);
				jQuery('#ced_umb_amazon_metakeys_list').DataTable({
			    	"pageLength": 10,
			    	"aaSorting": [ ],
			    	"columnDefs": [ {
						"targets": 1,
						"orderable": false
						} ]
			    });

			}
		});
	}

});


/*** product search ***/

var ced_umb_amazon_currentRequest = null;

jQuery(document.body).on('keyup',"#ced_umb_amazon_pro_search_box",function(){

	if(jQuery("#ced_umb_amazon_pro_search_box").val() == "") {
		jQuery("#ced_umb_amazon_suggesstion_box").hide();
		jQuery("#ced_umb_amazon_suggesstion_box").html('');
		return false;
	}

	jQuery(".ced_umb_amazon_ajax_pro_search_loader").show();

	ced_umb_amazon_currentRequest = jQuery.ajax({
		url : ced_umb_amazon_profile_edit_add_script_AJAX.ajax_url,
		type : 'post',
		data : {
			action : 'ced_umb_amazon_searchProductAjaxify',
			term : jQuery(this).val()
		},
		beforeSend : function() {           
			if(ced_umb_amazon_currentRequest != null) {
				ced_umb_amazon_currentRequest.abort();
			}
		},		
		success : function( data ) {	
			jQuery(".ced_umb_amazon_ajax_pro_search_loader").hide();

			jQuery("#ced_umb_amazon_suggesstion_box").show();
			jQuery("#ced_umb_amazon_suggesstion_box").html(data);
		}

	});

	if(jQuery(this).val() == '') {
		jQuery("#ced_umb_amazon_suggesstion_box").hide();
		jQuery("#ced_umb_amazon_suggesstion_box").html('');
	}

});

jQuery(document.body).on('click','span.ccas_pro_cross_class',function() {
	jQuery("#ced_umb_amazon_suggesstion_box").hide();
	jQuery("#ced_umb_amazon_suggesstion_box").html('');
	jQuery("#ced_umb_amazon_pro_search_box").val("");
});
