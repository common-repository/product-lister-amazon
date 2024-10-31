(function( $ ) {
	'use strict';
	jQuery(document).ready(function(){
		//toggle fields.
		jQuery(document).on('click','#ced_umb_amazon_accordian .ced_umb_amazon_panel_heading',function(){
			var k = jQuery(this).next().slideToggle('slow');
			jQuery('.ced_umb_amazon_collapse').not(k).slideUp('slow');
		});

		// price management fields.
		jQuery("#_umb_custom_price").on('change',function(){
			if(this.checked){
				jQuery(".umb_price_fields").show();
			}else{
				jQuery(".umb_price_fields").hide();
			}
		});
		// stock management fields.
		jQuery("#_umb_custom_stock").on('change',function(){
			if(this.checked){
				jQuery(".umb_stock_fields").show();
			}else{
				jQuery(".umb_stock_fields").hide();

			}
		});

		/* Handle New MarketPlace Addition **/
		jQuery('.upload-view-toggle').on('click',function(){
			jQuery('.ced-umb-upload-addon').slideToggle();
		});
	});
})( jQuery );

