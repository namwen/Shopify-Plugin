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
	/* 
		TODO: change this whole thing so it outputs the variant correctly
	*/	
	function widget( $args, $instance){
		extract($args,  EXTR_SKIP);
		// Hold the product id
		$product_id = empty($instance['variant']) ? ' ' : apply_filters('widget_title', $instance['variant']); 
		// Hold the image src
		$image = empty($instance['product-image']) ? ' ' :  $instance['product-image']; 
		// Get the variant object from the id
		$variant = get_variant( $product_id );
		// Get the product object from the id
		$product = get_product( $product_id );
		// Store some useful properties to be used
		$link  	 = $product->url;
		$title   = $product->title; 
		$price 	 = $variant->variant_price;
		/*
			ID given belongs to the variant
			-	Create an object of the parent using get_variant_parent_by_id( $variant_id )
			-	Store properties of the parent: link, description.
			- 	Store properties of the variant: title, options, and price

		*/



		echo $before_widget;

		?>		
		<div class="widget-image">
			<a href="http://<?php echo $link;?>"> <img width="170" src="<?php echo $image;?>"></a>
		</div>	
		<?php 
		if( !empty($title) ){
			echo $args['before_title']. $title . $args['after_title'];	
		}
		if( !empty( $price) ){
			echo $args['before_title']. $price . $args['after_title'];	
		}
		?>
		<?php
		echo $after_widget;
	}
	
	function update( $new_instance, $old_instance ){
		$instance = $old_instance;
		$instance['variant'] = strip_tags($new_instance['variant']);
		$instance['product-image'] = $new_instance['product-image'];
		return $instance;
	}
	function form( $instance ){
		$instance = wp_parse_args( (array) $instance, array( 'variant' => '') );
		$the_variant =  strip_tags( $instance['variant']);
		$products = get_products(); // Get all the products from the DB

		/* 
			Loop through all the products. Each parent should act as a selection group.
			The parent is not selectable. 
				Variant titles are selectable. Save the variant ID as the value of the option.

			Images should be visible on immediate load. No need to de-select then reselect to view them.
		*/
		?>
		  <p>
		  	<label for="<?php echo $this->get_field_id('product'); ?>">Featured Product: </label>
		  	<div id="product-selection-dropdown">
			  <select class="widefat" name="<?php echo $this->get_field_name('variant'); ?>" id="<?php echo $this->get_field_id('variant'); ?>">
				  <option value="none">None</option>
				  <?php
				  	  $k = 0;
					  foreach( $products as $product){ // Cycle through each product
					  		echo '<optgroup label="'.$product->title.'">';
					  		// if( $product->ID == $variants[$k]->variant_parent_id){
					  		// 	echo $variants[$k]->variant_title;
					  		// }
					  		/*
								Cycle through all the variants belonging to this particular product
								Somehow.
					  		*/
								$variants = array();
								$variants = get_variants_of_product( $product->id );
								foreach( $variants as $variant) {
									$variant = get_variant( $variant );
						  			echo "<option value='".$variant->variant_id."'"; 
						  			echo ($the_variant == $variant->variant_id) ? 'selected="selected"' : ''; // If the variant ID is the same as the currently featured product
						  			echo " data-parent='".$product->id."'>".$variant->variant_title."</option>";
								}

						  	echo '</optgroup>';
					  }
				   ?>
		     </select>
		  	</div>
		</p>
		<div id="selected-image">
			<input checked type="hidden" value="" name="<?php echo $this->get_field_name('product-image');?>" id="<?php echo $this->get_field_id('product-image');?>">
		</div>
		<div id="shopify-images-holder">
			<div id="product-images">
			<?php
				$images = unserialize(get_product_images(get_variant_parent_by_id($the_variant)->id));
				foreach( $images as $image){
					echo '<a href="#_" data-img-src="'.$image.'" class="image-box"><img src="'.$image.'" class="product-image"></a>';
				}
			?>
			</div>
		</div>
		<?php
	}
}

add_action( 'widgets_init', create_function('', 'return register_widget("Featured_Product");') );


?>