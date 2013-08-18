<?php
/*
Plugin Name: Shopify Products
Description: Gathers Shopify products and places them in the database, to be pulled later
Version: 1.0
Author: Will Newman

*/
register_activation_hook( __FILE__, 'sp_install' );
global $sp_db_version;
$sp_db_version = 1.0;

define('PRODUCT_TABLE', $wpdb->prefix .'shopify_products');
define('VARIANT_TABLE', $wpdb->prefix .'shopify_product_variants');

//Create the Tables on installation
function sp_install(){
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'shopify_products';	
	$variant_table_name = $wpdb->prefix .'shopify_product_variants';
	
	$sql = "CREATE TABLE $table_name (
		id int(25) NOT NULL,
		title text NOT NULL,
		handle text NOT NULL,
		description text NOT NULL,
		images text DEFAULT '',
		url VARCHAR(255) DEFAULT '' NOT NULL,
		variants text DEFAULT '',
		UNIQUE KEY id (id)
	);";
	
	$variant_sql = "CREATE TABLE $variant_table_name(
		variant_id int(25) NOT NULL,
		variant_title VARCHAR(55) NOT NULL,
		variant_option_one VARCHAR(25) DEFAULT '',
		variant_option_two VARCHAR(15) DEFAULT '',
		variant_price VARCHAR(10) DEFAULT '',
		variant_parent_id int(25) NOT NULL,
		UNIQUE KEY id (variant_id),
		FOREIGN KEY (variant_parent_id) REFERENCES $table_name ( id ) 
		
	);";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	dbDelta( $variant_sql );
}	


// create custom plugin settings menu
add_action('admin_menu', 'shopify_create_menu');

function shopify_create_menu() {
	//create new top-level menu
	add_menu_page('Shopify Store Settings', 'Shopify Products', 'administrator', __FILE__, 'shopify_admin_page',plugins_url('/images/shopify-bag-icon.png', __FILE__));

	//call register settings function
	add_action( 'admin_init', 'register_mysettings' );
}


function register_mysettings() {
	//register our settings
	register_setting( 'shopify-settings-group', 'shopify_store_domain' );
	register_setting( 'shopify-settings-group', 'shopify_api_key', 'sanitize_key' );
	register_setting( 'shopify-settings-group', 'shopify_api_pass', 'sanitize_key' );
}

function shopify_admin_page() {
?>
<div class="wrap">
<h2>Shopify Products Settings</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'shopify-settings-group' ); ?>

    <table class="form-table">
        <tr valign="top">
       	 	<th scope="row">Shopify Store Domain</th>
        	<td><input type="text" name="shopify_store_domain" value="<?php echo get_option('shopify_store_domain'); ?>" /></td>
        </tr>
         
        <tr valign="top">
        	<th scope="row">Shopify API Key</th>
        	<td><input type="text" name="shopify_api_key" value="<?php echo get_option('shopify_api_key'); ?>" /></td>
        </tr>
        
        <tr valign="top">
        	<th scope="row">Shopify API Pass</th>
        	<td><input type="text" name="shopify_api_pass" value="<?php echo get_option('shopify_api_pass'); ?>" /></td>
        </tr>
        
        <tr valign="top">
        	<th scope="row"> Save the above settings</th>
        	<td><?php submit_button('Save Changes', 'primary', 'submit', false); ?></td>
        </tr>
    </table>
    

</form>
<br/>
<form  id="populateDB" method="post" action="">
	<h3> Populate the database:</h3>
	<table class="form-table">
        <tr valign="top">
       		<th scope="row">Populate the database with the products in your store:</th>
       		<td><input class="button-secondary" type="submit" name="submit" value="Populate Database"/><span class="ajax-loading-gif"><img src="images/wpspin_light.gif" /> </span></td>
        </tr>
	</table>
</form>
<br/>
<form  id="updateDB" method="post" action="">
	<h3> Update the database:</h3>
	<table class="form-table">
        <tr valign="top">
       	 <th scope="row">Update the database to reflect changes made in the store:</th>
       	 <td><input class="button-secondary" type="submit" name="submit" value="Update Database"/><span class="ajax-loading-gif"><img src="images/wpspin_light.gif" /></span></td>
        </tr>
	</table>
</form>
</div>
<?php 
}

add_action( 'admin_enqueue_scripts', 'enqueue_shopify_scripts' );

function enqueue_shopify_scripts($hook) {
	wp_enqueue_script( 'ajax-script', plugins_url('Shopify-Plugin/js/shopify-ajax.js', dirname(__FILE__)) , array('jquery'));
	wp_localize_script( 'ajax-script', 'MyAjax', array( 'ajax_url' => admin_url( 'admin-ajax.php'), 'accessDbNonce' => wp_create_nonce('myajax-access-db-nonce')) );
}

add_action('wp_ajax_populate_db', 'populate_db_callback');
/* Populate the Database with the products from the store */
/*
	TODO
		- Change variable names and row names in the database to options
		  rather than 'color'. We don't know what the option will actually be.
*/
function populate_db_callback() {
	$nonce = $_POST['accessDbNonce'];

	if( ! wp_verify_nonce( $nonce, 'myajax-access-db-nonce')):
		die( 'nonce doesn\'t check out, brah.');
	endif;

	if( $_POST['submitted'] == true && current_user_can('activate_plugins') )
	{
		global $wpdb; // Get access to the Wordpress database
		$table_name = $wpdb->prefix . 'shopify_products';	
		$variant_table_name = $wpdb->prefix .'shopify_product_variants';
		
		// Include the file that with the Shopify API functions.
		// Setup some variables we will need to reference
		require_once(plugin_dir_path(__FILE__).'/shopify-api.php');
		$shopify_domain = get_option('shopify_store_domain');
		$shopify_api_key = get_option('shopify_api_key');
		$shopify_api_pass = get_option('shopify_api_pass');
		
		try{
			$shopify = shopify_api_client($shopify_domain, NULL, $shopify_api_key, $shopify_api_pass, true);
			$products = $shopify('GET', '/admin/products.json', array('published_status'=>'published'));
		}catch( ShopifyApiException $e){
			echo ( $e->getInfo() );
		}catch( ShopifyCurlException $e){
			echo( $e->getMessage() );
		}
		
		foreach( $products as $product){
		    $product_title = $product['title'];
		    $product_handle = $product['handle'];
		    $product_id = $product['id'];
		    $product_description = $product['body_html'];
		    $product_url  = $shopify_domain .'/products/'.$product_handle;
		   
		    /* deal with the product images */
		    $product_image_array = array();

		    if( count($product['images']) > 1){
			    foreach( $product['images'] as $image ){
				    array_push( $product_image_array, $image['src'] );
			    }
			}else{
				array_push( $product_image_array, $product['images'][0]['src'] );
			}
			$serialized_images = serialize($product_image_array);
  			
  			/* Deal with the variants for the product table */
  			$product_variant_array = array();

  			if( count($product['variants']) > 1 ){
			    foreach( $product['variants'] as $variant ) {
				   array_push($product_variant_array, $variant['id'] );
				}
		    }else{
		    	array_push( $product_variant_array, $product['variants'][0]['id'] );
		    }
		    $serialized_variants = serialize( $product_variant_array );

		   	/* Perform the SQL INSERT */	     
		    $product_rows_affected =  $wpdb->insert( $table_name, array('id' => $product_id, 'title'=> $product_title, 'handle'=> $product_handle, 'description' => $product_description, 'url' => $product_url, 'images'=> $serialized_images, 'variants' => $serialized_variants ));
		    
		    /* Populate the variant table */
		    if( !empty($product['variants'])){
			    foreach( $product['variants'] as $variant) {
				    $variant_id = $variant['id'];
				    $variant_parent_id = $product_id;
				    $variant_title = $variant['title'];
				    $variant_option_one = $variant['option1'];
				    $variant_option_two = $variant['option2'];
				    $variant_price = $variant['price'];
				    $variant_rows_affected = $wpdb->insert( $variant_table_name, array('variant_id' => $variant_id, 'variant_title' => $variant_title, 'variant_option_one' => $variant_option_one, 'variant_option_two' => $variant_option_two, 'variant_price' => $variant_price, 'variant_parent_id' => $variant_parent_id) );
			    }
		    }
		}
		echo("Product rows affected: ". $product_rows_affected ."\n Variant rows affected: ". $variant_rows_affected."\n");
		die();
	}
	die(); // this is required to return a proper result
}
add_action('wp_ajax_update_db', 'update_db_callback');
/* Update the store table with products from the store*/
// TODO:
// This doesn't account for items which were removed from the store
// Need to do something about products which are no longer inside the 
// store but are still in the DB
function update_db_callback() {

	$nonce = $_POST['accessDbNonce'];

	if( ! wp_verify_nonce( $nonce, 'myajax-access-db-nonce')):
		die( 'nonce doesn\'t check out, brah.');
	endif;

	if( $_POST['submitted'] == true && current_user_can('activate_plugins') )
	{
		global $wpdb; // this is how you get access to the database
		$table_name = $wpdb->prefix . 'shopify_products';	
		$variant_table_name = $wpdb->prefix .'shopify_product_variants';
		
		// Include the file that with the Shopify API functions.
		require_once(plugin_dir_path(__FILE__).'/shopify-api.php');
		$shopify_domain = get_option('shopify_store_domain');
		$shopify_api_key = get_option('shopify_api_key');
		$shopify_api_pass = get_option('shopify_api_pass');
		
		try{
			// Create a new Shopify client object
			$shopify = shopify_api_client($shopify_domain, NULL, $shopify_api_key, $shopify_api_pass, true);
			// Get the products from the shopify store
			$products = $shopify('GET', '/admin/products.json', array('published_status'=>'published'));
		}catch( ShopifyApiException $e){
			echo ( $e->getInfo() );
		}catch( ShopifyCurlException $e){
			echo( $e->getMessage() );
		}
		if( !empty($products) ){
				
		}
		foreach( $products as $product){
		    $product_title = $product['title'];
		    $product_handle = $product['handle'];
		    $product_id = $product['id'];
		    $product_description = $product['body_html'];
		    $product_url  = $shopify_domain .'/products/'.$product_handle;
		    /* deal with the product images */
		    $product_image_array = array();

		    if( count($product['images']) > 1){
			    foreach( $product['images'] as $image ){
				    array_push( $product_image_array, $image['src'] );
			    }
			}else{
				array_push( $product_image_array, $product['images'][0]['src'] );
			}
			$serialized_images = serialize($product_image_array);
  			
  			/* Deal with the variants for the product table */
  			$product_variant_array = array();

  			if( count($product['variants']) > 1 ){
			    foreach( $product['variants'] as $variant ) {
				   array_push($product_variant_array, $variant['id'] );
				}
		    }else{
		    	array_push( $product_variant_array, $product['variants'][0]['id'] );
		    }
		    $serialized_variants = serialize( $product_variant_array );

		    if( $wpdb->update( $table_name, array('id' => $product_id, 'title'=> $product_title, 'handle'=> $product_handle, 'description' => $product_description, 'url' => $product_url, 'images'=> $serialized_images ), array('id'=> $product_id))){

		    }else{
				$inserted = $wpdb->insert( $table_name, array('id' => $product_id, 'title'=> $product_title, 'handle'=> $product_handle, 'description' => $product_description, 'url' => $product_url, 'images'=> $serialized_images, 'variants' => $serialized_variants ));
		    }
		    //$product_rows_affected =  $wpdb->update( $table_name, array('id' => $product_id, 'title'=> $product_title, 'handle'=> $product_handle, 'description' => $product_description, 'url' => $product_url, 'images'=> $serialized_images ), array('id'=> $product_id));
		    // If the product has variants
		    if( !empty($product['variants'])){
			    foreach( $product['variants'] as $variant) {
				    $variant_id = $variant['id'];
				    $variant_parent_id = $product_id;
				    $variant_title = $variant['title'];
				    $variant_option_one = $variant['option1'];
				    $variant_option_two = $variant['option2'];
				    $variant_price = $variant['price'];
				    //$variant_rows_affected = $wpdb->update( $variant_table_name, array('variant_id' => $variant_id, 'variant_title' => $variant_title, 'variant_option_one' => $variant_option_one, 'variant_option_two' => $variant_option_two, 'variant_price' => $variant_price, 'variant_parent_id' => $variant_parent_id), array('variant_id'=>$variant_id));
			    	if( $wpdb->update( $variant_table_name, array('variant_id' => $variant_id, 'variant_title' => $variant_title, 'variant_option_one' => $variant_option_one, 'variant_option_two' => $variant_option_two, 'variant_price' => $variant_price, 'variant_parent_id' => $variant_parent_id), array('variant_id'=>$variant_id)) ){

			    	}else{
						$wpdb->insert( $variant_table_name, array('variant_id' => $variant_id, 'variant_title' => $variant_title, 'variant_option_one' => $variant_option_one, 'variant_option_two' => $variant_option_two, 'variant_price' => $variant_price, 'variant_parent_id' => $variant_parent_id) );	
			    	}
			    }
		    }
		}
/* 		echo("Product rows affected: " .$product_rows_affected . "\n Variant rows affected: ". $variant_rows_affected); */
		print_r($products);
		die();
	}
	die(); // this is required to return a proper result
}

add_action('wp_ajax_get_product_images', 'get_product_images_callback');

function get_product_images_callback(){
	if( !empty( $_POST['theID']) ){
		$product_images = unserialize(get_product_images($_POST['theID']));
		
		$image_array = array();
		$i = 0;

		foreach( $product_images as $img){
			$image_array[$i] = $img;
			$i++;
		}		
		echo json_encode($image_array);
		
		die();
	}
}
add_action('wp_ajax_test_echo', 'test_the_echo');
function test_the_echo(){
	echo "works";
}
// Plugin CSS File
wp_register_style('shopify-product-style', plugins_url('shopify-products-style.css', __FILE__));
wp_enqueue_style('shopify-product-style');

// Function to output a product to the page
function shopify_output_product( $product_id){
	$product = get_product( $product_id );
}
// Outputs main info needed from the variant
function shopify_output_variant( $variant_id){
	$variant = get_variant($variant_id);
	$variant_parent = get_variant_parent( $variant );
	?>
	<h3><?php echo $variant_parent->title . "<br/>"; ?></h3>
	<p><?php echo $variant->variant_option_one ."<br/>"; ?></p>
	<p><?php echo $variant->variant_price ."<br/>"; ?></p>			
	<?php
}

//Return array of all Products
function get_products(){
	global $wpdb;
	$products = $wpdb->get_results("SELECT * FROM ". PRODUCT_TABLE );
	return $products;	
}
// Return array of all Variants
function get_variants(){
	global $wpdb;
	$variants = $wpdb->get_results("SELECT * FROM ". VARIANT_TABLE );
	return $variants;	
}
// Returns Product Object
function get_product($id){
	global $wpdb;
	$product = $wpdb->get_results("SELECT * FROM ". PRODUCT_TABLE ." WHERE id = $id ");
	return $product[0];
}
// Returns Variant Object
function get_variant($id){
	global $wpdb;
	$variant = $wpdb->get_results("SELECT * FROM ". VARIANT_TABLE ." WHERE variant_id = $id ");
	return $variant[0];	
}
//Returns first image from list of images
function get_product_image_by_id( $id ){
	$product_images = get_product_images( $id);
	$product_images = unserialize($product_images);
	return $product_images[0];
}
// Return array of product images
function get_product_images( $id ){
	$product = get_product($id);
	return $product->images;
}
// Returns Variant's Parent Object
function get_variant_parent( $variant ){
	global $wpdb;
	$variant_parent_id = $variant->variant_parent_id;
	$variant_parent = $wpdb->get_results("SELECT * FROM ". PRODUCT_TABLE ." WHERE id = $variant_parent_id ");
	return $variant_parent[0];
}
// Returns Variant's Parent Object
function get_variant_parent_by_id( $variant_id ){
	global $wpdb;
	$variant = get_variant( $variant_id);
	$variant_parent_id = $variant->variant_parent_id;
	$variant_parent = $wpdb->get_results("SELECT * FROM ". PRODUCT_TABLE ." WHERE id = $variant_parent_id ");
	return $variant_parent[0];
}
// Get Variants of a Product
function get_variants_of_product( $parent_id ){
	$product = get_product($parent_id);
	$product_variants = $product->variants;
	$product_variants = unserialize( $product_variants );
	return $product_variants;
}