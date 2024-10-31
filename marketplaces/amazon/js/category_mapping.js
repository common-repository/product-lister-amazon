jQuery(document).ready(function(){

	jQuery(document.body).on( 'click', 'input:checkbox[class=ced_umb_amazon_amazon_cat_select]', function() {
		if(jQuery(this).is(':checked')) {
			updateamazonCategoriesInDB( jQuery(this).val(), jQuery(this).attr('name'), 'append' );
		}
		else {
			updateamazonCategoriesInDB( jQuery(this).val(), jQuery(this).attr('name'), 'delete' );
		}
	});

	
	/**
	 * updating amazon categories
	 * 
	 */
	function updateamazonCategoriesInDB( categoryNAME, categoryID , actionToDo ) {
		jQuery("#ced_umb_amazon_marketplace_loader").show();
		jQuery.ajax({
			url : umb_amazon_cat_map.ajax_url,
			type : 'post',
			data : {
				action : 'updateamazonCategoriesInDB',
				categoryID : categoryID,
				categoryNAME : categoryNAME,
				actionToDo : actionToDo
			},
			success : function( response ) 
			{
				jQuery("#ced_umb_amazon_marketplace_loader").hide();
			}
		});
	}

});	