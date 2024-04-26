<?php

// Option 2 to remove SKU
// remove_action( 
//     'woocommerce_single_product_summary', //hook
//      'woocommerce_template_single_meta',  //function to remove
//       40                                  //priority
//     );

//  This will output after the Add to Cart buttonfunction 
// fwd_custom_function() {
//     echo 'Hello World!’;
// };

// add_action( 
//     'woocommerce_single_product_summary', 
//     'fwd_custom_function', 
//     31 
// );


// Move price on single product page below excerpt
remove_action( 
    'woocommerce_single_product_summary', 
    'woocommerce_template_single_price', 
    10 
);

// Re-add Price by changing PRIORITY
add_action( 'woocommerce_single_product_summary',
    'woocommerce_template_single_price',
    21 
);



// Add additional content to My Account Dashboard
add_action(
    'woocommerce_account_dashboard', //hook
    'storefront_child_account_dashboard' // function to run
);

function storefront_child_account_dashboard() {

    $email = 'info@example.com';

    if ( function_exists ('get_field')) {
        if( get_field('email',143)) {
            $email = get_field('email',143);
        }
    }

    echo '<p>Please contact us at <a href="mailto:'. $email . '">' . $email .'</a> with any issues.</p>';
    //shortcode with contact form
    echo do_shortcode('[contact-form-7 id="e17896b" title="Contact form 1"]');
}

// review star below add cart
// Move price on single product page below excerpt
remove_action( 
    'woocommerce_single_product_summary', 
    'woocommerce_template_single_rating', 
    10 
);

// Re-add Price by changing PRIORITY
add_action( 'woocommerce_single_product_summary',
    'woocommerce_template_single_rating',
    31 
);

// On the Shop page, have the star ratings from reviews appear below the price.
remove_action( 
    'woocommerce_after_shop_loop_item_title', 
    'woocommerce_template_loop_rating', 
    5 
);

// Re-add Price by changing PRIORITY
add_action( 'woocommerce_after_shop_loop_item_title',
    'woocommerce_template_loop_rating',
    11
);

add_action(
    'woocommerce_after_shop_loop_item', //hook
    'storefront_child_categories'// function to run
);

function storefront_child_categories() {

    global $product;

    $categories = wc_get_product_category_list( $product->get_id(), 'product_cat', '', ', ', '' );

    if ( $categories ) {
        echo '<div class="product-categories">' . $categories . '</div>';
    }
    
}