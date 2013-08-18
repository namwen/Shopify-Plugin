jQuery(document).ready(function($) {

	$("#populateDB").submit( function(event){
		event.preventDefault();
		$("#populateDB .ajax-loading-gif").show();
		var data = {	
			action: 'populate_db',
			submitted: true,
			accessDbNonce : MyAjax.accessDbNonce
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
		    submitted: true,
		    accessDbNonce : MyAjax.accessDbNonce

		};		
		jQuery.post(ajaxurl, data, function(response) {
		    console.log('Database updated.\n '+ response);
			$("#updateDB .ajax-loading-gif").hide();
		});
		
	});
	
	/* Handle state change of the product selection form
	   We Want to get the images associated with the product and allow the user to select one.
	*/

	$(document).on('change','#product-selection-dropdown select', function(){
		var $parent = $(this).closest('.widget');

		// Clear out any images that are already in the image holder
		$("#"+$parent.attr('id') +" #shopify-images-holder #product-images").empty();
		
		var selectedID = $(this).val();
		// get the parent id 
		var parentID = $(this).find(':selected').data('parent');

		var data = {
			action: 'get_product_images',
			theID: parentID
		};
		// Make the ajax request to get the images
		// ajaxurl is already defined from the wp_localize_script call
		var getImages = $.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			data: data
		});

		// Place each of the images we received in the ajax response into the photo holder
		getImages.done(function(response){
			$.each(response,function( i, val){
				jQuery("#"+$parent.attr('id')+" #shopify-images-holder #product-images").append('<a href="#_" data-img-src="'+val+'" class="image-box"><img src="'+val +'" class="product-image"></a>');
			});
		});

		// If the request fails, let's find out why
		getImages.fail(function( jqXHR, textStatus){
			console.log("failed: "+ textStatus);
		});
	});
	// When an image is clicked on we consider it selected and mark it as such with a class
	$(document).on('click','.image-box', function(e){
		e.preventDefault();
		// Unselect any other image
		$('.image-box').removeClass('selected');	
		// Place the src of the selected image as the value of a hidden form field 
		$("#selected-image input").val($(this).data('img-src'));
		$(this).addClass('selected');
	});
});