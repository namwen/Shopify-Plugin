<?php
/*
Plugin Name: Featured Product Widget
Description: Allows you to select a product to appear in the footer
Version: 1.0
Author: Will Newman

*/
class Featured_Product extends WP_Widget {
	function Featured_Product(){
		$widget_ops  = array(
			'classname' => 'Featured_Product',
			'description' =>' Allows you to select a product to appear in the footer' 
		);
		
		$this->WP_WIDGET(
			'Featured_Product',
			'Featured Product',
			$widget_ops
		);
	}
	
	function widget( $args, $instance){
		extract($args,  EXTR_SKIP);
		echo $before_widget;
		$product = empty($instance['product']) ? ' ' : apply_filters('widget_title', $instance['product']); // holds the product id
		$image = empty($instance['product-image']) ? ' ' :  $instance['product-image']; 
		$variant = get_variant( $product );
		$product = get_product( $product);
		$link  	 = $product->url;
		$title   = $product->title; 

		?>
	
		<div class="widget-image">
			<a href="http://<?php echo $link;?>"> <img width="170" src="<?php echo $image;?>"></a>
		</div>	
		<h4><?php echo $title; ?></h4>
		
		<?php
		echo $after_widget;
	}
	
	function update( $new_instance, $old_instance ){
		$instance = $old_instance;
		$instance['product'] = strip_tags($new_instance['product']);
		$instance['product-image'] = $new_instance['product-image'];
		return $instance;
	}
	function form( $instance ){
		$instance = wp_parse_args( (array) $instance, array( 'product' => '') );
		$the_product =  strip_tags( $instance['product']);
		$products = get_products(); // Get all the products from the DB
		$variants = get_variants(); // Get all the product variants from the DB
		?>
		  <p>
		  	<label for="<?php echo $this->get_field_id('product'); ?>">Featured Product: </label>
		  	<div id="product-selection-dropdown">
			  <select class="widefat" name="<?php echo $this->get_field_name('product'); ?>" id="<?php echo $this->get_field_id('product'); ?>">
				  <?php
					  foreach( $products as $product){ // Cycle through each product
					      $product_title = $product->title;
					      $product_id = $product->id;
						  	echo "<option value='".$product_id."'"; 
						  	echo ($the_product == $product_id) ? 'selected' : ''; // If the product id is the same as the currently featured product
						  	echo ">".$product_title."</option>";
					  }
				   ?>
		     </select>
		  	</div>
		</p>
		<div id="selected-image">
			<input checked type="hidden" value="http://cdn.shopify.com/s/files/1/0216/9534/products/navy_white_back.jpg?2402" name="<?php echo $this->get_field_name('product-image');?>" id="<?php echo $this->get_field_id('product-image');?>">
		</div>
		<div id="shopify-images-holder">

		</div>
		<?php
	}
}

add_action( 'widgets_init', create_function('', 'return register_widget("Featured_Product");') );


?>