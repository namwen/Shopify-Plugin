<?php
	function form( $instance ){
		$instance = wp_parse_args( (array) $instance, array( 'product' => '') );
		$the_variant =  strip_tags( $instance['variant']);
		$products = get_products(); // Get all the products from the DB
		//$variants = get_variants(); // Get all the product variants from the DB	

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
			  <select class="widefat" name="<?php echo $this->get_field_name('product'); ?>" id="<?php echo $this->get_field_id('product'); ?>">
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
								$variants = get_variants_of_product( $product->id);
								foreach ( $variant in $variants) {
						  			echo "<option value='".$variant->variant_id."'"; 
						  			echo ($the_variant == $variant->variant_id) ? 'selected' : ''; // If the variant ID is the same as the currently featured product
						  			echo ">".$variant->variant_title."</option>";
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

		</div>
		<?php
	}
?>