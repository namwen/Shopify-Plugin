<?php
/*
Plugin Name: Shopify Products Full Plugin
Description: Gathers shopify products and places them in the database, to be pulled later
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
		description text NOT NULL,
		image VARCHAR(255) DEFAULT'',
		url VARCHAR(255) DEFAULT '' NOT NULL,
		UNIQUE KEY id (id)
	);";
	
	$variant_sql = "CREATE TABLE $variant_table_name(
		variant_id int(25) NOT NULL,
		variant_title VARCHAR(55) NOT NULL,
		variant_color VARCHAR(25) DEFAULT '',
		variant_size VARCHAR(15) DEFAULT '',
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
    </table>
    
    <?php submit_button(); ?>

</form>
<form  id="populateDB" method="post" action="">
	<h3> Populate the database:</h3>
	<table class="form-table">

        <tr valign="top">
       	 <th scope="row">Populate the database with your shopify products:</th>
       	 <td><input type="submit" name="submit" value="Populate Database"/></td>
        </tr>
	</table>
</form>
</div>
<?php 
}

add_action( 'admin_enqueue_scripts', 'my_enqueue' );

function my_enqueue($hook) {
	wp_enqueue_script( 'ajax-script', plugins_url('Shopify%20Products/js/shopify-ajax.js', dirname(__FILE__)) , array('jquery'));
	// in javascript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
	wp_localize_script( 'ajax-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'we_value' => $email_nonce ) );
}

add_action('wp_ajax_my_action', 'populate_db_callback');

function populate_db_callback() {
	if( $_POST['submitted'] == true )
	{
		global $wpdb; // this is how you get access to the database
		$table_name = $wpdb->prefix . 'shopify_products';	
		$variant_table_name = $wpdb->prefix .'shopify_product_variants';

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
		    $product_id = $product['id'];
		    $product_description = $product['body_html'];
		    $product_url  = $shopify_domain .'/products/'.$product_title;
		    $product_image = $product['images'][0]['src'];
		    $rows_affected =  $wpdb->insert( $table_name, array('id' => $product_id, 'title'=> $product_title, 'description' => $product_description, 'url' => $product_url, 'image'=> $product_image ));
		    if( !empty($product['variants'])){
			    foreach( $product['variants'] as $variant) {
				    $variant_id = $variant['id'];
				    $variant_parent_id = $product_id;
				    $variant_title = $variant['title'];
				    $variant_color = $variant['option1'];
				    $variant_size = $variant['option2'];
				    $variant_price = $variant['price'];
				    $rows_affected = $wpdb->insert( $variant_table_name, array('variant_id' => $variant_id, 'variant_title' => $variant_title, 'variant_color' => $variant_color, 'variant_size' => $variant_size, 'variant_price' => $variant_price, 'variant_parent_id' => $variant_parent_id) );
			    }
		    }
		}
		print_r( $products);
		die();
	}
	die(); // this is required to return a proper result
}
// Function to output a product to the page
function shopify_output_product( $product_id){
	$product = get_product( $product_id );
}
function shopify_output_variant( $variant_id){
	$variant = get_variant($variant_id);
	$variant_parent = get_variant_parent( $variant );
	?>
	<h3><?php echo $variant_parent->title . "<br/>"; ?></h3>
	<p><?php echo $variant->variant_color ."<br/>"; ?></p>
	<p><?php echo $variant->variant_price ."<br/>"; ?></p>			
	<?php
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
//Returns image from the id of the product passed in
function get_product_image_by_id( $id ){
	$product = get_product($id);
	return $product->image;
}
// Returns image from variant's parent, accepts an id
function get_product_image_by_variant_id( $id ){
	$variant_parent = get_variant_parent( get_variant($id));
	return $variant_parent->image;
}
// Returns image from the variant's parent, accepts an object
function get_product_image_by_variant_object( $variant){
	$variant_parent = get_variant_parent( $variant );
	return $variant_parent->image;
}
// Returns Variant's Parent Object
function get_variant_parent( $variant){
	global $wpdb;
	$variant_parent_id = $variant->variant_parent_id;
	$variant_parent = $wpdb->get_results("SELECT * FROM ". PRODUCT_TABLE ." WHERE id = $variant_parent_id ");
	return $variant_parent[0];
}