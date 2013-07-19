jQuery(document).ready(function($) {
	$("#populateDB").submit( function(event){
		event.preventDefault();
		$("#populateDB .ajax-loading-gif").show();
		var data = {	
		    action: 'my_action',
		    submitted: true
		};		
		jQuery.post(ajaxurl, data, function(response) {
		    console.log('Database populated.\n' + response);
			$("#populateDB .ajax-loading-gif").hide();
		});
		
	});

	$("#updateDB").submit( function(event){
		event.preventDefault();
		$("#updateDB .ajax-loading-gif").show();
		var data = {	
		    action: 'update_db',
		    submitted: true
		};		
		jQuery.post(ajaxurl, data, function(response) {
		    console.log('Database updated.\n '+ response);
			$("#updateDB .ajax-loading-gif").hide();
		});
		
	});
	
	$(".widget-content #shopify-images-holder").append('<div id="product-images"></div>');

	/* Handle state change of the product selection form
	   We Want to get the images associated with the product and allow the user to select one.
	   
	   TODO:
	   	CHange selector so it is universal. This doesn't work because it specifies an ID which does 
	   	not exist on every installation.
	*/
	var product_selector_id = $("#product-selection-dropdown select")[1];

	$("#product-selection-dropdown .widefat").change(function(){
		$("#shopify-images-holder #product-images").empty();
	var selectedID = $(product_selector_id).val();
		console.log( selectedID );
		var data = {
			action: 'get_product_images',
			theID: selectedID
		};
		var getImages = $.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			data: data
		});

		getImages.done(function(response){
			$.each(response,function( i, val){
				console.log(val);
				jQuery("#shopify-images-holder #product-images").append('<a href="#_" data-img-src="'+val+'" class="image-box"><img src="'+val +'" class="product-image"></a>');
			})
		});
		getImages.fail(function( jqXHR, textStatus){
			console.log("failed: "+ textStatus);
		});
	})
	$(document).on('click','.image-box', function(e){
		e.preventDefault();
		$('.image-box').removeClass('selected');	
		$("#widget-featured_product-2-product-image").val($(this).data('img-src'));
		$(this).addClass('selected');
	});
});