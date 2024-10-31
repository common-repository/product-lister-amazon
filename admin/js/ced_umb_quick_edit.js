
jQuery(function( $ ) {
	
	
	$( '#the-list' ).on( 'click', '.editinline', function() {
		
		inlineEditPost.revert();
		
		var post_id = $( this ).closest( 'tr' ).attr( 'id' );
		post_id = post_id.replace( 'post-', '' );
		var $umb_inline_data = $( '#ced_umb_amazon_inline_' + post_id );
		$('div', $umb_inline_data ).each(function(index,data){
			var key = jQuery(data).attr('class');
			var value = jQuery(data).text();
			var type = jQuery(data).attr('type');
			
			if(type=='_select'){
				$( 'select[name="'+key+'"] option:selected', '.inline-edit-row' ).attr( 'selected', false ).change();
				$( 'select[name="'+key+'"] option[value="' + value + '"]' ).attr( 'selected', 'selected' ).change();
			}else{
				$( 'input[name="'+key+'"]', '.inline-edit-row' ).val( value );
			}
		});
	} );
	
	jQuery(document).ready(function(){
		 jQuery(document.body).on( 'click', 'input:checkbox[id^=cb-select-all-]', function() {
		  if(jQuery(this).is(':checked')) {
		   jQuery( 'input:checkbox[name^=post]' ).each(function() {
		    jQuery(this).attr('checked','checked');
		   });
		  }
		  else {
		   jQuery( 'input:checkbox[name^=post]' ).each(function() {
		    jQuery(this).removeAttr('checked');
		   });
		  }
		 });
		});
	
});
