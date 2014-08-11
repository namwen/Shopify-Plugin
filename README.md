# Shopify for WordPress #

This is a pretty simple WordPress plugin for pulling in and displaying Shopify products on your website / blog / whatever.

The plugin uses [ this nice little PHP Shopify client library  ](https://github.com/sandeepshetty/shopify.php) made by [Sandeep Shetty](https://github.com/sandeepshetty).

* * *

## About ##

My motivations for creating this were pretty straightforward: I needed a way to combine a Shopify-based ecommerce store with a custom, content heavy, WordPress theme.

The plugin is pretty simple in how it works:

1. Creates an options page for you to enter your Shopify API information.
2. Creates a couple new tables in the WordPress database.
3. Fills the tables with your products and their variants.
4. Lets you output the products through the widget or included functions.


## Installation & Usage ##

1. Drop the folder into your plugins directory ( ../wp-content/plugins )
2. Activate it on the plugins admin page. The widget needs to be activated as a plugin as well.
3. Go to the newly created "Shopify Products" option page and enter your API info.
4. Populate the database from the options page by clicking the button that says "populate database".
5. Begin using either the widget or functions available to to you.

## TODOs and Future Development ##

If there's enough interest or people want to contribute I'd be happy to make a more formal list of things to do.

Off the top of my head there are a few things I have in mind:

More robust product features - As of now there is no way to store product options, skus, quantity, etc.

Add to cart / View cart ( with items and totals ) functionality.

Display products on admin options page?

More output functions. i.e show all product info in one function call.
