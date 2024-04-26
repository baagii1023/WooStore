<?php
$wishlist       = new WC_Wishlists_Wishlist( $_GET['wlid'] );
$wishlist_items = WC_Wishlists_Wishlist_Item_Collection::get_items( $wishlist->id );
// only include the items where the product is published
$wishlist_items = array_filter( $wishlist_items, function ( $item ) {
	$product = wc_get_product( $item['product_id'] );
	if ( $product ) {
		return $product->get_status() == 'publish';
	}

	return false;
} );

$wishlist_item_categories = WC_Wishlists_Wishlist_Item_Collection::get_items_categories( $wishlist->id );
?>

<?php
$current_owner_key = WC_Wishlists_User::get_wishlist_key();
$sharing           = $wishlist->get_wishlist_sharing();
$sharing_key       = $wishlist->get_wishlist_sharing_key();
$wl_owner          = $wishlist->get_wishlist_owner();


$is_visible = true;

if ( ! current_user_can( 'manage_woocommerce' ) ) :
	if ( $sharing == 'Shared' && ( $current_owner_key != $wl_owner ) ) :
		if ( ! isset( $_GET['wlkey'] ) || $_GET['wlkey'] != $sharing_key ) :
			$is_visible = false;
		endif;
    elseif ( $sharing == 'Private' && $current_owner_key != $wl_owner ) :
		if ( ! isset( $_GET['wlkey'] ) || $_GET['wlkey'] != $sharing_key || $current_owner_key != $wl_owner ) :
			$is_visible = false;
		endif;
	endif;
endif;

if ( $is_visible ) :
	if ( $wishlist->post->post_status != 'publish' ) {
		$is_visible = current_user_can( 'read_post', $wishlist->id );
	}
endif;


$wlitemsort = isset( $_GET['wlitemsort'] ) ? $_GET['wlitemsort'] : 'date';
$wlitemcat  = isset( $_GET['wlitemcat'] ) ? $_GET['wlitemcat'] : 0;
?>
<?php do_action( 'woocommerce_wishlists_before_wrapper' ); ?>
<?php if ( ! $is_visible ) : ?>
    <div id="wl-wrapper" class="woocommerce">

		<?php if ( function_exists( 'wc_print_messages' ) ) : ?>
			<?php wc_print_messages(); ?>
		<?php else : ?>
			<?php WC_Wishlist_Compatibility::wc_print_notices(); ?>
		<?php endif; ?>

        <ul class="woocommerce_error woocommerce-errror">
            <li>
				<?php _e( 'Unable to locate the requested list', 'wc_wishlist' ); ?>
            </li>
        </ul>

    </div>
<?php else: ?>
    <div id="wl-wrapper" class="product woocommerce">
		<?php WC_Wishlist_Compatibility::wc_print_notices(); ?>
		<?php if ( $wishlist_items && count( $wishlist_items ) ) : ?>
			<?php if ( isset( $_GET['preview'] ) && $_GET['preview'] ) : ?>
                <div class="woocommerce-info woocommerce_info">
                    <a rel="nofollow" href="<?php echo $wishlist->the_url_edit(); ?>"
                       class="button"><?php _e( 'Return to your view', 'wc_wishlist' ); ?></a> <?php _e( 'This is how other people will see your Wish List', 'wc_wishlist' ); ?>
                </div>
			<?php endif; ?>

            <div class="wl-intro">
                <h2 class="entry-title"><?php $wishlist->the_title(); ?></h2>
				<?php if ( $sharing == 'Public' || $sharing == 'Shared' ) : ?>

                    <div class="wl-meta-share">
						<?php woocommerce_wishlists_get_template( 'wishlist-sharing-menu.php', [ 'id' => $wishlist->id ] ); ?>

                    </div>

				<?php endif; ?>
                <div class="wl-intro-desc">
					<?php $wishlist->the_content(); ?>

                </div>
            </div>

            <div class="wl-row wl-clear">
                <form method="GET" action="<?php $wishlist->the_url_view(); ?>">
                    <input type="hidden" name="wlid" value="<?php echo $wishlist->id; ?>"/>
                    <table width="100%" cellpadding="0" cellspacing="0" class="wl-actions-table wl-right">
                        <tbody>
                        <tr>
                            <td><label for="sort-dropdown"><?php _e( 'Sort by:', 'wc_wishlist' ); ?></label></td>
                            <td><label for="sort-dropdown"><?php _e( 'In Category:', 'wc_wishlist' ); ?></label></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>
                                <select class="wl-sel" name="wlitemsort" id="sort-dropdown">
                                    <option value="date" <?php selected( $wlitemsort, 'date' ); ?>><?php _e( 'Date Added', 'wc_wishlist' ); ?></option>
                                    <option value='pasc' <?php selected( $wlitemsort, 'pasc' ); ?>><?php _e( 'Price (High to Low)', 'wc_wishlist' ); ?></option>
                                    <option value="pdesc" <?php selected( $wlitemsort, 'pdesc' ); ?>><?php _e( 'Price (Low to High)', 'wc_wishlist' ); ?></option>
                                </select>
                            </td>
                            <td>
								<?php wp_dropdown_categories( [
									'taxonomy'        => 'product_cat',
									'class'           => 'wl-sel',
									'name'            => 'wlitemcat',
									'show_option_all' => __( 'All', 'wc_wishlist' ),
									'selected'        => $wlitemcat,
									'hierarchical'    => true,
									'include'         => array_keys( $wishlist_item_categories )
								] ); ?>
                            </td>
                            <td>
                                <input type="submit" class="button small wl-but"
                                       value="<?php _e( 'Go', 'wc_wishlist' ); ?>"/>
                            </td>

                        </tr>
                        </tbody>

                    </table>
                </form>
            </div>

            <form method="post">

                <?php echo WC_Wishlists_Plugin::nonce_field( 'add-cart-items' ); ?>

                <input type="hidden" name="wlid" value="<?php esc_attr_e( $wishlist->id ); ?>"/>
                <input type="hidden" name="wlaction" value="add-cart-items"/>
                <input type="hidden" name="wlkey" value="<?php esc_attr_e( $wishlist->get_wishlist_sharing_key() ); ?>"/>
                <?php if (isset($_GET['preview'])) : ?>
                    <input type="hidden" name="preview" value="<?php esc_attr_e($_GET['preview']); ?>">
                <?php endif; ?>

                <table class="shop_table shop_table_responsive cart wl-table view">
                    <thead>
                    <tr>
                        <th class="product-thumbnail">&nbsp;</th>
                        <th class="product-name"><?php _e( 'Product', 'wc_wishlist' ); ?></th>
                        <th class="product-price"><?php _e( 'Price', 'wc_wishlist' ); ?></th>
                        <th class="product-quantity ctr"><?php _e( 'Qty', 'wc_wishlist' ); ?></th>
						<?php if ( WC_Wishlists_Settings::get_setting( 'wc_wishlist_show_previously_ordered', 'no' ) == 'yes' ): ?>
                            <th class="product-quantity ctr"><?php echo apply_filters( 'wc_wishlist_show_previously_ordered_column_heading', WC_Wishlists_Settings::get_setting( 'wc_wishlist_show_previously_ordered_column_heading', __( 'Ordered', 'wc_wishlist' ) ) ); ?></th>
						<?php endif; ?>
						<?php if ( ( apply_filters( 'woocommerce_wishlist_purchases_enabled', true, $wishlist ) ) ): ?>
                            <th></th>
						<?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
					<?php
					if ( $wlitemcat ) {
						$wishlist_items = _woocommerce_wishlist_filter_item_collection_category( $wishlist_items, (int) $wlitemcat );
					}

					if ( $wlitemsort == 'date' ) {
						uasort( $wishlist_items, '_woocommerce_wishlist_sort_item_collection_date' );
					} else if ( $wlitemsort == 'pasc' ) {
						uasort( $wishlist_items, '_woocommerce_wishlist_sort_item_collection_price_asc' );
					} else if ( $wlitemsort == 'pdesc' ) {
						uasort( $wishlist_items, '_woocommerce_wishlist_sort_item_collection_price_desc' );
					}


					if ( sizeof( $wishlist_items ) > 0 ) :
						foreach ( $wishlist_items as $wishlist_item_key => $item ) :
							$_product = wc_get_product( $item['data'] );
							$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $item ) : '', $item, $wishlist_item_key );

							if ( $_product->exists() && $item['quantity'] > 0 ) :
								?>
                                <tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item cart_table_item', $item, $wishlist_item_key ) ); ?>">
                                    <td class="product-thumbnail" data-title="product-thumbnail">
										<?php
										printf( '<a href="%s">%s</a>', esc_url( get_permalink( apply_filters( 'woocommerce_in_cart_product_id', $item['product_id'] ) ) ), $_product->get_image() );
										?>

                                    </td>
                                    <td class="product-name" data-title="<?php _e( 'Product', 'wc_wishlist' ); ?>">
										<?php

										if ( ! $product_permalink ) {
											echo apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $item, $wishlist_item_key ) . '&nbsp;';
										} else {
											echo apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $item, $wishlist_item_key );
										}


										// Meta data
										if ( function_exists( 'wc_get_formatted_cart_item_data' ) ) {
											echo wc_get_formatted_cart_item_data( $item );
										} else {
											echo WC()->cart->get_item_data( $item );
										}

										// Availability
										$availability = $_product->get_availability();

										if ( $availability && $availability['availability'] ) :
											echo apply_filters( 'woocommerce_stock_html', '<p class="stock ' . esc_attr( $availability['class'] ) . '">' . esc_html( $availability['availability'] ) . '</p>', $availability['availability'] );
										endif;
										?>

										<?php do_action( 'woocommerce_wishlist_after_list_item_name', $item, $wishlist ); ?>
                                    </td>
                                    <td class="product-price" data-title="<?php _e( 'Price', 'wc_wishlist' ); ?>">
										<?php
										$price = '';
										if ( WC_Wishlist_Compatibility::is_wc_version_gte_2_1() ) {
											$price = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $item, $wishlist_item_key );
										} else {
											$product_price = ( get_option( 'woocommerce_display_cart_prices_excluding_tax' ) == 'yes' ) ? wc_get_price_excluding_tax( $_product ) : $_product->get_price();
											$price         = apply_filters( 'woocommerce_cart_item_price_html', wc_price( $product_price ), $item, $wishlist_item_key );
										}
										?>

										<?php echo apply_filters( 'woocommerce_wishlist_list_item_price', $price, $item, $wishlist ); ?>

                                    </td>
                                    <!-- Quantity inputs -->
                                    <td class="product-quantity" data-title="<?php _e( 'Quantity', 'wc_wishlist' ); ?>">
										<?php
										if ( $_product->is_sold_individually() ) {
											$product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" class="input-text qty text" value="1" />', $wishlist_item_key );
										} else {
											$product_quantity_value = apply_filters( 'woocommerce_wishlist_list_item_quantity_value', $item['quantity'], $item, $wishlist );

											$step = apply_filters( 'woocommerce_quantity_input_step', '1', $_product );
											$min  = apply_filters( 'woocommerce_quantity_input_min', '', $_product );
											$max  = apply_filters( 'woocommerce_quantity_input_max', $_product->backorders_allowed() ? '' : $_product->get_stock_quantity(), $_product );

											$product_quantity = sprintf( '<div class="quantity"><input type="text" name="cart[%s][qty]" step="%s" min="%s" max="%s" value="%s" size="4" title="' . _x( 'Qty', 'Product quantity input tooltip', 'woocommerce' ) . '" class="input-text qty text" maxlength="12" /></div>', $wishlist_item_key, $step, $min, $max, esc_attr( $product_quantity_value ) );
										}
										?>

										<?php echo $product_quantity; ?>

                                    </td>

									<?php if ( WC_Wishlists_Settings::get_setting( 'wc_wishlist_show_previously_ordered', 'no' ) == 'yes' ): ?>
                                        <td class="product-quantity"
                                            data-title="<?php echo apply_filters( 'wc_wishlist_show_previously_ordered_column_heading', WC_Wishlists_Settings::get_setting( 'wc_wishlist_show_previously_ordered_column_heading', __( 'Ordered', 'wc_wishlist' ) ) ); ?>">

											<?php echo( isset( $item['ordered_total'] ) ? intval( $item['ordered_total'] ) : 0 ); ?>
                                        </td>
									<?php endif; ?>

									<?php if ( ( apply_filters( 'woocommerce_wishlist_purchases_enabled', true, $wishlist ) ) ): ?>
                                        <td class="product-purchase"
                                            data-title="<?php _e( 'Add to Cart', 'wc_wishlist' ); ?>">
											<?php if ( ! $_product->is_type( 'external' ) && $_product->is_in_stock() && apply_filters( 'woocommerce_wishlist_user_can_purcahse', true, $_product ) ) : ?>
												<?php if ( woocommerce_wishlist_use_add_to_cart_quantity_prompts( $wishlist->id, $item ) ): ?>
                                                    <a rel="nofollow"
                                                       data-qty="<?php echo apply_filters( 'woocommerce_wishlist_list_item_quantity_value', esc_attr( $item['quantity'] ), $item, $wishlist ); ?>"
                                                       href="<?php echo woocommerce_wishlist_url_item_add_to_cart( $wishlist->id, $wishlist_item_key, $wishlist->get_wishlist_sharing() == 'Shared' ? $wishlist->get_wishlist_sharing_key() : false ); ?>"
                                                       class="wishlist-add-to-cart-button wishlist-add-to-cart-button-view button"><?php _e( 'Add to Cart', 'wc_wishlist' ); ?></a>
												<?php else: ?>
                                                    <a rel="nofollow"
                                                       href="<?php echo woocommerce_wishlist_url_item_add_to_cart( $wishlist->id, $wishlist_item_key, $wishlist->get_wishlist_sharing() == 'Shared' ? $wishlist->get_wishlist_sharing_key() : false ); ?>"
                                                       class="button wishlist-add-to-cart-button"><?php _e( 'Add to Cart', 'wc_wishlist' ); ?></a>
												<?php endif; ?>
											<?php elseif ( $_product->is_type( 'external' ) == 'external' ) : ?>
                                                <a rel="nofollow"
                                                   href="<?php echo esc_url( get_permalink( $_product->get_id() ) ); ?>"
                                                   rel="nofollow"
                                                   class="single_add_to_cart_button button alt"><?php echo $_product->single_add_to_cart_text(); ?></a>
											<?php endif; ?>
                                        </td>
									<?php endif; ?>
                                </tr>
							<?php endif; ?>
						<?php endforeach; ?>
                        <tr>
                            <!-- the tds need to be individual for woo-themes responsive mode, since they target the first td specifically and hide it. WIth colspan this causes issues once in responsive mode -->
                            <td class="product-thumbnail">&nbsp;</td>
                            <td class="product-name">&nbsp;</td>
                            <td class="product-price">&nbsp;</td>
                            <td class="product-quantity">&nbsp;</td>
							<?php if ( apply_filters( 'woocommerce_wishlist_purchases_enabled', true, $wishlist ) ): ?>
                                <td class="product-purchase">
                                    <!-- Instead of a link, let's use a button -->
                                    <input type="submit"
                                             class="button alt wl-add-all"
                                             value="<?php _e( 'Add All To Cart', 'wc_wishlist' ); ?>"/>
                                </td>
							<?php endif; ?>
                        </tr>
					<?php endif; ?>
                    </tbody>
                </table>
            </form>

		<?php else : ?>
			<?php $shop_url = get_permalink( wc_get_page_id( 'shop' ) ); ?>
            <div class="woocommerce-info woocommerce_info"> <?php _e( 'This list currently contains no items.', 'wc_wishlist' ); ?>
                <a href="<?php echo WC_Wishlists_Pages::get_url_for( 'find-a-list' ); ?>"><?php _e( 'Back to find a list', 'wc_wishlist' ); ?></a>
            </div>
		<?php endif; ?>

    </div><!-- /wishlist-wrapper -->
<?php endif; ?>
<?php do_action( 'woocommerce_wishlists_after_wrapper' ); ?>

<?php woocommerce_wishlists_get_template( 'wishlist-email-form.php', [ 'wishlist' => $wishlist ] ); ?>
