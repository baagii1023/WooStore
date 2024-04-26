<?php
/**
 * Product Add-ons helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Product_Addons_Helper {
	/**
	 * Gets global product addons. The result is cached.
	 *
	 * @return array
	 */
	protected static function get_global_product_addons() {
		$cache_key     = 'all_products';
		$cache_group   = 'global_product_addons';
		$cache_value   = wp_cache_get( $cache_key, $cache_group );
		$last_modified = get_option( 'woocommerce_global_product_addons_last_modified' );

		if ( false === $cache_value || $last_modified !== $cache_value['last_modified'] ) {
			$args          = array(
				'posts_per_page'      => -1,
				'orderby'             => 'meta_value',
				'order'               => 'ASC',
				'meta_key'            => '_priority',
				'post_type'           => 'global_product_addon',
				'post_status'         => 'publish',
				'suppress_filters'    => true,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'fields'              => 'ids',
				'meta_query'          => array(
					array(
						'key'   => '_all_products',
						'value' => '1',
					),
				),
			);
			$return_addons = get_posts( $args );

			wp_cache_set(
				$cache_key,
				array(
					'last_modified' => $last_modified,
					'data'          => $return_addons,
				),
				$cache_group
			);
		} else {
			$return_addons = $cache_value['data'];
		}

		return (array) $return_addons;
	}

	/**
	 * Gets product addons from its terms. The result is cached.
	 *
	 * @param int $product_id The product ID.
	 * @return array
	 */
	protected static function get_product_term_addons( $product_id ) {
		$cache_key     = $product_id;
		$cache_group   = 'global_product_addons';
		$cache_value   = wp_cache_get( $cache_key, $cache_group );
		$last_modified = get_option( 'woocommerce_global_product_addons_last_modified' );

		if ( false === $cache_value || $last_modified !== $cache_value['last_modified'] ) {
			$return_addons = array();
			$product_terms = apply_filters( 'get_product_addons_product_terms', wc_get_object_terms( $product_id, 'product_cat', 'term_id' ), $product_id );

			if ( $product_terms ) {
				$args          = apply_filters(
					'get_product_addons_global_query_args',
					array(
						'posts_per_page'      => -1,
						'orderby'             => 'meta_value',
						'order'               => 'ASC',
						'meta_key'            => '_priority',
						'post_type'           => 'global_product_addon',
						'post_status'         => 'publish',
						'suppress_filters'    => true,
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
						'fields'              => 'ids',
						'tax_query'           => array(
							array(
								'taxonomy'         => 'product_cat',
								'field'            => 'id',
								'terms'            => $product_terms,
								'include_children' => false,
							),
						),
					),
					$product_terms
				);
				$return_addons = get_posts( $args );
			}
			wp_cache_set(
				$cache_key,
				array(
					'last_modified' => $last_modified,
					'data'          => $return_addons,
				),
				$cache_group
			);
		} else {
			$return_addons = $cache_value['data'];
		}

		return (array) $return_addons;
	}

	/**
	 * Gets addons assigned to a product by ID.
	 *
	 * @param  int    $post_id ID of the product to get addons for.
	 * @param  string $prefix for addon field names. Defaults to postid.
	 * @param  bool   $inc_parent Set to false to not include parent product addons.
	 * @param  bool   $inc_global Set to false to not include global addons.
	 * @return array
	 */
	public static function get_product_addons( $post_id, $prefix = false, $inc_parent = true, $inc_global = true ) {
		if ( ! $post_id ) {
			return array();
		}

		$addons     = array();
		$raw_addons = array();
		$parent_id  = wp_get_post_parent_id( $post_id );

		$product = wc_get_product( $post_id );
		if ( ! $product ) {
			return array();
		}
		$exclude        = $product->get_meta( '_product_addons_exclude_global' );
		$product_addons = array_filter( (array) $product->get_meta( '_product_addons' ) );

		// Product Parent Level Addons.
		if ( $inc_parent && $parent_id ) {
			$raw_addons[10]['parent'] = apply_filters( 'get_parent_product_addons_fields', self::get_product_addons( $parent_id, $parent_id . '-', false, false ), $post_id, $parent_id );
		}

		// Product Level Addons.
		$raw_addons[10]['product'] = apply_filters( 'get_product_addons_fields', $product_addons, $post_id );

		// Global level addons (all products and categories).
		if ( '1' !== $exclude && $inc_global ) {
			$global_addons = array_merge( self::get_global_product_addons(), self::get_product_term_addons( $product->get_id() ) );

			if ( $global_addons ) {
				foreach ( $global_addons as $global_addon_id ) {
					$priority                                    = get_post_meta( $global_addon_id, '_priority', true );
					$raw_addons[ $priority ][ $global_addon_id ] = apply_filters( 'get_product_addons_fields', array_filter( (array) get_post_meta( $global_addon_id, '_product_addons', true ) ), $global_addon_id );
				}
			}
		}

		ksort( $raw_addons );

		foreach ( $raw_addons as $addon_group ) {
			if ( $addon_group ) {
				foreach ( $addon_group as $addon ) {
					$addons = array_merge( $addons, $addon );
				}
			}
		}

		// Generate field names with unqiue prefixes.
		if ( ! $prefix ) {
			$prefix = apply_filters( 'product_addons_field_prefix', "{$post_id}-", $post_id );
		}

		// Let's avoid exceeding the suhosin default input element name limit of 64 characters.
		$max_addon_name_length = 45 - strlen( $prefix );

		// If the product_addons_field_prefix filter results in a very long prefix, then
		// go ahead and enforce sanity, exceed the default suhosin limit, and just use
		// the prefix and the field counter for the input element name.
		if ( $max_addon_name_length < 0 ) {
			$max_addon_name_length = 0;
		}

		$addon_field_counter = 0;

		foreach ( $addons as $addon_key => $addon ) {
			if ( empty( $addon['name'] ) ) {
				unset( $addons[ $addon_key ] );
				continue;
			}
			if ( empty( $addons[ $addon_key ]['field_name'] ) ) {
				$addons[ $addon_key ]['field_name'] = $prefix . $addon_field_counter;
				$addon_field_counter++;
			}
		}

		return apply_filters( 'get_product_addons', $addons );
	}

	/**
	 * Display prices according to shop settings.
	 *
	 * @version 2.8.2
	 *
	 * @param  float      $price     Price to display.
	 * @param  WC_Product $cart_item Product from cart.
	 *
	 * @return float
	 */
	public static function get_product_addon_price_for_display( $price, $cart_item = null ) {
		$product = ! empty( $GLOBALS['product'] ) && is_object( $GLOBALS['product'] ) ? clone $GLOBALS['product'] : null;

		if ( '' === $price || '0' == $price ) {
			return;
		}

		$neg = false;

		if ( $price < 0 ) {
			$neg    = true;
			$price *= -1;
		}

		if ( ( is_cart() || is_checkout() || WC_PAO_Core_Compatibility::is_store_api_request( 'cart' ) || WC_PAO_Core_Compatibility::is_store_api_request( 'checkout' ) ) && null !== $cart_item ) {
			$product = wc_get_product( $cart_item->get_id() );
		}

		if ( is_object( $product ) ) {
			// Support new wc_get_price_excluding_tax() and wc_get_price_excluding_tax() functions.
			if ( function_exists( 'wc_get_price_excluding_tax' ) ) {
				$display_price = self::get_product_addon_tax_display_mode() === 'incl' ? wc_get_price_including_tax(
					$product,
					array(
						'qty'   => 1,
						'price' => $price,
					)
				) : wc_get_price_excluding_tax(
					$product,
					array(
						'qty'   => 1,
						'price' => $price,
					)
				);

				/**
				 * When a user is tax exempt and product prices are exclusive of taxes, WooCommerce displays prices as follows:
				 * - Catalog and product pages: including taxes
				 * - Cart and Checkout pages: excluding taxes
				 */
				if ( ( is_cart() || is_checkout() ) && ! empty( WC()->customer ) && WC()->customer->get_is_vat_exempt() && ! wc_prices_include_tax() ) {
					$display_price = wc_get_price_excluding_tax(
						$product,
						array(
							'qty'   => 1,
							'price' => $price,
						)
					);
				}
			} else {
				$display_price = self::get_product_addon_tax_display_mode() === 'incl' ? $product->get_price_including_tax( 1, $price ) : $product->get_price_excluding_tax( 1, $price );
			}
		} else {
			$display_price = $price;
		}

		if ( $neg ) {
			$display_price = '-' . $display_price;
		}

		return $display_price;
	}

	/**
	 * Return tax display mode depending on context.
	 *
	 * @return string
	 */
	public static function get_product_addon_tax_display_mode() {
		if ( is_cart() || is_checkout() ) {
			return get_option( 'woocommerce_tax_display_cart' );
		}

		return get_option( 'woocommerce_tax_display_shop' );
	}

	/**
	 * Checks if addon field is required.
	 *
	 * @since 3.0.0
	 * @param array $addon
	 * @return bool
	 */
	public static function is_addon_required( $addon = array() ) {
		if ( empty( $addon ) ) {
			return false;
		}

		$type     = ! empty( $addon['type'] ) ? $addon['type'] : '';
		$required = ! empty( $addon['required'] ) ? $addon['required'] : '';

		switch ( $type ) {
			case 'heading':
				return false;
				break;
			case 'multiple_choice':
			case 'checkbox':
			case 'file_upload':
				return '1' == $required;
				break;
			default:
				return '1' == $required;
				break;
		}
	}

	/**
	 * Checks if addon should display description.
	 *
	 * @since 3.07.28
	 * @param  array $addon  Current add-on.
	 * @return bool          True if should display description.
	 */
	public static function should_display_description( $addon = array() ) {
		if ( empty( $addon ) || empty( $addon['description_enable'] ) ) {
			return false;
		}

		// True if description enabled and there is a description.
		return ( ( ! empty( $addon['description'] ) && $addon['description_enable'] ) ? true : false );
	}

	/**
	 * Get addon restriction data
	 *
	 * @param array $addon Add-on field data.
	 */
	public static function get_restriction_data( $addon ) {

		$restriction_data = array();

		if ( isset( $addon[ 'required' ] ) && 1 === $addon[ 'required' ] ) {
			$restriction_data[ 'required' ] = 'yes';
		}

		if ( isset( $addon[ 'restrictions_type' ] ) && 'any_text' !== $addon[ 'restrictions_type' ] ) {
			$restriction_data[ 'content' ] = $addon[ 'restrictions_type' ];
		}

		if ( isset( $addon[ 'restrictions' ] ) && 1 === $addon[ 'restrictions' ] ) {
			if ( isset( $addon[ 'min' ] ) && '' !== $addon[ 'min' ] && $addon[ 'min' ] >= 0 ) {
				$restriction_data[ 'min' ] = $addon[ 'min' ];
			}

			if ( isset( $addon[ 'max' ] ) && '' !== $addon[ 'max' ] && $addon[ 'max' ] > 0 ) {
				$restriction_data[ 'max' ] = $addon[ 'max' ];
			}
		}

		/**
		 * Use this filter to modify the addon restriction data.
		 *
		 * @since 6.0.0
		 *
		 * @param  array  $restriction_data
		 * @param  array  $addon
		 */
		return apply_filters( 'wc_pao_restriction_data', $restriction_data, $addon );
	}

	/**
	 * Checks WC version for backwards compatibility.
	 *
	 * @since 3.0.0
	 * @param string $version
	 */
	public static function is_wc_gte( $version ) {
		return version_compare( WC_VERSION, $version, '>=' );
	}

	/**
	 * Checks WC version for backwards compatibility.
	 *
	 * @since 3.0.0
	 * @param string $version
	 */
	public static function is_wc_gt( $version ) {
		return version_compare( WC_VERSION, $version, '>' );
	}

	/**
	 * Checks if server can handle upload filesize.
	 *
	 * @since 3.0.0
	 * @return bool
	 */
	public static function can_upload( $file ) {
		return $file < wp_max_upload_size();
	}

	/**
	 * Checks if file exceeds upload size limit.
	 *
	 * @since 3.0.33
	 * @param  array $post_file File from $_FILES.
	 * @return bool             True if over size limit.
	 */
	public static function is_filesize_over_limit( $post_file ) {
		$php_size_upload_errors = array( 1, 2 );

		if ( ! empty( $post_file['error'] ) && in_array( $post_file['error'], $php_size_upload_errors, true ) ) {
			return true;
		}

		if ( ! self::can_upload( $post_file['size'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the placeholder image URL for image swatch
	 * with no selection.
	 *
	 * @return string
	 */
	public static function no_image_select_placeholder_src() {
		$src = WC_PRODUCT_ADDONS_PLUGIN_URL . '/assets/images/no-image-select-placeholder.png';

		return apply_filters( 'woocommerce_product_addons_no_image_select_placeholder_src', $src );
	}

	/**
	 * Create a clone of the current product/cart item/order item and set its price equal to the add-on price.
	 * This will allow extensions to discount the add-on price.
	 *
	 * @param WC_Product $product
	 * @param float      $price
	 *
	 * @return WC_Product
	 *
	 */
	public static function create_product_with_filtered_addon_prices( $product, $price ) {

		$cloned_product = clone $product;
		$cloned_product->set_price( $price );
		$cloned_product->set_regular_price( $price );
		$cloned_product->set_sale_price( $price );

		// Prevent Product Bundles from changing add-on prices.
		if ( isset( $cloned_product->bundled_cart_item ) ) {
			unset( $cloned_product->bundled_cart_item );
		}

		// Prevent Composite Products from changing add-on prices.
		if ( isset( $cloned_product->composited_cart_item ) ) {
			unset( $cloned_product->composited_cart_item );
		}

		/**
		 * All Products for WooCommerce Subscriptions compatibility.
		 *
		 * If All Products for WooCommerce Subscriptions shouldn't discount add-ons, then remove flat fees from the price offset used to
		 * calculate discounts.
		 */
		if ( class_exists( 'WCS_ATT_Integration_PAO' ) && class_exists( 'WCS_ATT_Product' ) ) {
			if ( ! WCS_ATT_Integration_PAO::discount_addons( $cloned_product ) ) {

				// Create new SATT instance to avoid price offsets changing in the original product too.
				if ( isset ( $cloned_product->wcsatt_instance ) ) {
					$cloned_product->wcsatt_instance += 1;
				}

				WCS_ATT_Product::set_runtime_meta( $cloned_product, 'price_offset', $price );
			}
		}

		/*
		 * 'woocommerce_addons_cloned_product_with_filtered_price'
		 *
		 * Product Add-ons creates a dummy product with a price equal to the add-on prices.
		 * Then, it passes it through `get_price` to allow discount/price-related plugins to apply discounts.
		 * Use this filter to add a unique identifier to the dummy product, if you'd like to prevent discounts from applying to add-on prices.
		 *
		 * @since 6.5.2
		 *
		 * @param WC_Product $cloned_product
		 * @param WC_Product $product
		 * @param float      $price
		 */
		return apply_filters( 'woocommerce_addons_cloned_product_with_filtered_price', $cloned_product, $product, $price );
	}
}
