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
		$variant_id = empty($instance['variant']) ? ' ' : apply_filters('widget_title', $instance['variant']); 
		// Hold the image src
		$image = empty($instance['product-image']) ? ' ' :  $instance['product-image']; 
		// Get the variant object from the id
		$variant = get_variant( $variant_id );
		
		$show_name = $instance['show-name'];
		$show_price = $instance['show-price'];
		$show_description = $instance['show-description'];
		$link_image = $instance['link-image'];
		$link_title = $instance['link-title'];
		$link_price = $instance['link-price'];

		// Create a new variant object with the id
		$variant = get_variant($variant_id);
		$variant_title = $variant->variant_title;
		// Get the product object from the id
		$product = get_variant_parent_by_id( $variant_id );
		
		// Store some useful properties to be used
		$link  	 = $product->url;
		$title   = $product->title;
		$description = $product->description; 
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
			<?php
			if( $link_image && $link_image == '1'){
			?>
			<a href="http://<?php echo $link;?>"> 
			<?php
			}
			?>
				<img width="170" src="<?php echo $image;?>">
			<?php
			if( $link_image && $link_image == '1'){
			?>
			</a>
			<?php
			}
			?>				
		</div>	
		<?php 
		if( $show_name && $show_name == '1' && !empty($title) ){
			if( $link_title && $link_title = '1'){
				echo $args['before_title'];
					echo '<a href="http://'.$link.'">'. $title . '</a>';
				echo $args['after_title'];	
			}else{
				echo $args['before_title']. $title . $args['after_title'];	

			}
		}
		if( $show_description && $show_description == '1' ){
			echo apply_filters('the_content', $description);			
		}
		if( $show_price && $show_price == '1' && !empty( $price) ){
			if($link_price && $link_price == '1'){
				echo $args['before_title'];
					echo '<a href="http://'.$link.'">'. $price . '</a>';
				echo $args['after_title'];	
			}else{
				echo $args['before_title']. $price . $args['after_title'];	
			}
		}
		?>
		<?php
		echo $after_widget;
	}
	
	function update( $new_instance, $old_instance ){
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['variant'] = strip_tags($new_instance['variant']);
		$instance['product-image'] = $new_instance['product-image'];
		$instance['show-name'] = strip_tags($new_instance['show-name']);
		$instance['show-price'] = strip_tags($new_instance['show-price']);
		$instance['show-description'] = strip_tags($new_instance['show-description']);
		$instance['link-image'] = strip_tags($new_instance['link-image']);
		$instance['link-title'] = strip_tags($new_instance['link-title']);
		$instance['link-price'] = strip_tags($new_instance['link-price']);
		
		return $instance;
	}
	function form( $instance ){
		$instance = wp_parse_args( (array) $instance, array( 'variant' => '') );
		
		$the_variant =  strip_tags( $instance['variant']);
		$title = get_variant_parent_by_id($the_variant)->title; 
		$the_image = $instance['product-image'];
		$show_name = esc_attr($instance['show-name']);
		$show_price = esc_attr($instance['show-price']);
		$link_image = esc_attr($instance['link-image']);
		$link_title = esc_attr($instance['link-title']);
		$link_price = esc_attr($instance['link-price']);
		
		$show_description = esc_attr($instance['show-description']);
		$products = get_products(); // Get all the products from the DB
		/* 
			Loop through all the products. Each parent should act as a selection group.
			The parent is not selectable. 
				Variant titles are selectable. Save the variant ID as the value of the option.

			Images should be visible on immediate load. No need to de-select then reselect to view them.
		*/
		?>
		  <p>
		  	<h4>Featured Product: </h4>
		  	<div id="product-selection-dropdown">
			  <select class="widefat" name="<?php echo $this->get_field_name('variant'); ?>" id="<?php echo $this->get_field_id('variant'); ?>">
				  <option value="none">None</option>
				  <?php
				  	  $k = 0;
					  foreach( $products as $product){ // Cycle through each product
					  		echo '<optgroup label="'.$product->title.'">';
					  		/*
								Cycle through all the variants belonging to this particular product.
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
		<input type="hidden" value="<?php echo $title; ?>" name="<?php echo $this->get_field_name('title');?>" id="<?php echo $this->get_field_id('title'); ?>">
		<div id="selected-image">
			<input checked type="hidden" value="<?php echo $the_image ;?>" name="<?php echo $this->get_field_name('product-image');?>" id="<?php echo $this->get_field_id('product-image');?>">
		</div>
		<div id="shopify-images-holder">
			<h4> Select an image:</h4>
			<div id="product-images">
			<?php
			$images = unserialize(get_product_images(get_variant_parent_by_id($the_variant)->id));
			if(!empty($images)):
				foreach( $images as $image ){
					if( $the_image == $image ){
						echo '<a href="#_" data-img-src="'.$image.'" class="image-box selected"><img src="'.$image.'" class="product-image"></a>';
					}else{
						echo '<a href="#_" data-img-src="'.$image.'" class="image-box"><img src="'.$image.'" class="product-image"></a>';
					}
				}
			endif;
			?>
			</div>
		</div>
		<h4> Display Options:</h4>
		<label for="<?php echo $this->get_field_id('show-name'); ?>">Show Title: </label>
		<input type="checkbox" name="<?php echo $this->get_field_name('show-name');?>" id="<?php echo $this->get_field_id('show-name'); ?>" value="1" <?php checked( '1',$show_name );?> />
		<br/>
		<label for="<?php echo $this->get_field_id('show-description'); ?>">Show Description? </label>
		<input type="checkbox" name="<?php echo $this->get_field_name('show-description');?>" id="<?php echo $this->get_field_id('show-description'); ?>" value="1" <?php checked( '1',$show_description );?> />
		<br/>
		<label for="<?php echo $this->get_field_id('show-price'); ?>">Show Price: </label>
		<input type="checkbox" name="<?php echo $this->get_field_name('show-price');?>" id="<?php echo $this->get_field_id('show-price'); ?>" value="1" <?php checked( '1',$show_price );?> />
		<br/>
		<h4>Link Options:</h4>
		<label for="<?php echo $this->get_field_id('link-image'); ?>">Link Image </label>
		<input type="checkbox" name="<?php echo $this->get_field_name('link-image');?>" id="<?php echo $this->get_field_id('link-image'); ?>" value="1" <?php checked( '1',$link_image );?> />
		<br/>
		<label for="<?php echo $this->get_field_id('link-title'); ?>">Link Title </label>
		<input type="checkbox" name="<?php echo $this->get_field_name('link-title');?>" id="<?php echo $this->get_field_id('link-title'); ?>" value="1" <?php checked( '1',$link_title );?> />
		<br/>
		<label for="<?php echo $this->get_field_id('link-price'); ?>">Link Price </label>
		<input type="checkbox" name="<?php echo $this->get_field_name('link-price');?>" id="<?php echo $this->get_field_id('link-price'); ?>" value="1" <?php checked( '1',$link_price );?> />
		<br/>




		<?php
	}
}

add_action( 'widgets_init', create_function('', 'return register_widget("Featured_Product");') );


?>