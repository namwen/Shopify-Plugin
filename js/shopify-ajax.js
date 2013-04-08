jQuery(document).ready(function($) {
	$("#populateDB").submit( function(event){
		event.preventDefault();
		var data = {	
		    action: 'my_action',
		    submitted: true
		};		
		jQuery.post(ajaxurl, data, function(response) {
		    console.log('Got this from the server: ' + response);
		});
		
	});

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php

});