<?php
/**
 * Class to handle feature Coupons By Product Quantity
 *
 * @category    Admin
 * @author      StoreApps
 * @package     woocommerce-smart-coupons/includes
 * @since       5.0.0
 * @version     1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Coupons_By_Product_Quantity' ) ) {

	/**
	 * Class WC_SC_Coupons_By_Product_Quantity
	 */
	class WC_SC_Coupons_By_Product_Quantity {

		/**
		 * Variable to hold instance of this class
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		private function __construct() {
			add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'usage_restriction' ), 99, 2 );
			add_action( 'admin_footer', array( $this, 'styles_and_scripts' ) );
			add_action( 'woocommerce_coupon_options_save', array( $this, 'process_meta' ), 10, 2 );
			add_filter( 'woocommerce_coupon_is_valid', array( $this, 'validate' ), 11, 3 );

			add_filter( 'is_protected_meta', array( $this, 'make_sc_meta_protected' ), 10, 3 );
			add_filter( 'sc_generate_coupon_meta', array( $this, 'generate_coupon_meta' ), 10, 2 );
			add_filter( 'smart_coupons_parser_postmeta_defaults', array( $this, 'postmeta_defaults' ) );
			add_filter( 'wc_sc_export_coupon_meta', array( $this, 'export_coupon_meta' ), 10, 2 );
			add_filter( 'wc_sc_process_coupon_meta_value_for_import', array( $this, 'process_coupon_meta_value_for_import' ), 10, 2 );
			add_filter( 'wc_smart_coupons_export_headers', array( $this, 'export_headers' ) );
			add_action( 'wc_sc_new_coupon_generated', array( $this, 'copy_meta' ) );
		}

		/**
		 * Get single instance of this class
		 *
		 * @return this class Singleton object of this class
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Handle call to functions which is not available in this class
		 *
		 * @param string $function_name The function name.
		 * @param array  $arguments Array of arguments passed while calling $function_name.
		 * @return result of function call
		 */
		public function __call( $function_name = '', $arguments = array() ) {

			global $woocommerce_smart_coupon;

			if ( ! is_callable( array( $woocommerce_smart_coupon, $function_name ) ) ) {
				return;
			}

			if ( ! empty( $arguments ) ) {
				return call_user_func_array( array( $woocommerce_smart_coupon, $function_name ), $arguments );
			} else {
				return call_user_func( array( $woocommerce_smart_coupon, $function_name ) );
			}

		}

		/**
		 * Display field for coupon by product quantity
		 *
		 * @param integer   $coupon_id The coupon id.
		 * @param WC_Coupon $coupon The coupon object.
		 */
		public function usage_restriction( $coupon_id = 0, $coupon = null ) {
			if ( ! is_a( $coupon, 'WC_Coupon' ) || empty( $coupon_id ) ) {
				return;
			}

			if ( ! is_a( $coupon, 'WC_Coupon' ) ) {
				$coupon = ( ! empty( $coupon_id ) ) ? new WC_Coupon( $coupon_id ) : null;
			}

			if ( $this->is_callable( $coupon, 'get_meta' ) ) {
				$product_quantity_restrictions = $coupon->get_meta( 'wc_sc_product_quantity_restrictions' );
			} else {
				$product_quantity_restrictions = $this->get_post_meta( $coupon_id, 'wc_sc_product_quantity_restrictions', true );
			}

			if ( ! is_array( $product_quantity_restrictions ) ) {
				$product_quantity_restrictions = array();
			}

			$cart_min_quantity                  = ! empty( $product_quantity_restrictions['values']['cart']['min'] ) ? intval( $product_quantity_restrictions['values']['cart']['min'] ) : '';
			$cart_max_quantity                  = ! empty( $product_quantity_restrictions['values']['cart']['max'] ) ? intval( $product_quantity_restrictions['values']['cart']['max'] ) : '';
			$product_quantity_restrictions_type = ! empty( $product_quantity_restrictions['type'] ) ? $product_quantity_restrictions['type'] : 'cart';
			?>
			<div class="options_group smart-coupons-field">
				<h3 class="smart-coupons-field" style="padding-left: 10px;"><?php echo esc_html__( 'Product quantity based restrictions', 'woocommerce-smart-coupons' ); ?></h3>
				<p class="form-field">
					<span class='left_column'><label><?php echo esc_html__( 'Validate quantity of', 'woocommerce-smart-coupons' ); ?></label></span>
					<label for="wc_sc_product_quantity_type_cart" class="cart_quantity">
						<input type="radio" name="wc_sc_product_quantity_restrictions[type]" id="wc_sc_product_quantity_type_cart" class="wc_sc_product_quantity_type wc_sc_product_quantity_type_cart" value="cart" <?php checked( 'cart', $product_quantity_restrictions_type, true ); ?> />
						<?php echo esc_html__( 'Cart', 'woocommerce-smart-coupons' ); ?>
					</label>
					<label for="wc_sc_product_quantity_type_product" class="product_quantity">
						<input type="radio" name="wc_sc_product_quantity_restrictions[type]" id="wc_sc_product_quantity_type_product" class="wc_sc_product_quantity_type wc_sc_product_quantity_type_product" value="product" <?php checked( 'product', $product_quantity_restrictions_type, true ); ?>>
						<?php echo esc_html__( 'Product', 'woocommerce-smart-coupons' ); ?>
					</label>
					<?php
					$tooltip_text = esc_html__( 'Choose whether to validate the quantity, cart-wise or product-wise', 'woocommerce-smart-coupons' );
                    echo wc_help_tip($tooltip_text); // phpcs:ignore ?>
				</p>
				<p class="form-field wc_sc_cart_quantity" style="<?php echo ( 'cart' === $product_quantity_restrictions_type ) ? '' : 'display: none;'; ?>">
					<label for="wc_sc_min_product_quantity"><?php echo esc_html__( 'Minimum quantity', 'woocommerce-smart-coupons' ); ?></label>
					<input type="number" name="wc_sc_product_quantity_restrictions[values][cart][min]" id="wc_sc_min_product_quantity" class="wc_sc_min_product_quantity" placeholder="<?php echo esc_attr__( 'No minimum', 'woocommerce-smart-coupons' ); ?>" value="<?php echo esc_attr( $cart_min_quantity ); ?>" min="0">
				</p>
				<p class="form-field wc_sc_cart_quantity" style="<?php echo ( 'cart' === $product_quantity_restrictions_type ) ? '' : 'display: none;'; ?>">
					<label for="wc_sc_max_product_quantity"><?php echo esc_html__( 'Maximum quantity', 'woocommerce-smart-coupons' ); ?></label>
					<input type="number" name="wc_sc_product_quantity_restrictions[values][cart][max]" id="wc_sc_max_product_quantity" class="wc_sc_max_product_quantity" placeholder="<?php echo esc_attr__( 'No maximum', 'woocommerce-smart-coupons' ); ?>" value="<?php echo esc_attr( $cart_max_quantity ); ?>" min="0">
				</p>
				<div class=" wc_sc_product_quantity" style="<?php echo isset( $product_quantity_restrictions['type'] ) && ( 'product' === $product_quantity_restrictions['type'] ) ? '' : 'display: none;'; ?>">
					<?php
					$product_quantities = ! empty( $product_quantity_restrictions['values']['product'] ) ? $product_quantity_restrictions['values']['product'] : array();
					if ( ! empty( $product_quantities ) ) {
						$display_label = true;
						foreach ( $product_quantities as $product_id => $value ) {
							if ( 0 !== $product_id ) {
								$product = wc_get_product( $product_id );
								if ( ! empty( $product ) && is_object( $product ) ) {
									$product_name         = is_callable( array( $product, 'get_name' ) ) ? $product->get_name() : '';
									$product_max_quantity = ! empty( $value['max'] ) ? intval( $value['max'] ) : '';
									$product_min_quantity = ! empty( $value['min'] ) ? intval( $value['min'] ) : '';
									?>
									<p class="form-field" data-index="<?php echo esc_attr( $product_id ); ?>">
										<?php if ( true === $display_label ) { ?>
											<label><?php echo esc_html__( 'Products', 'woocommerce-smart-coupons' ); ?></label>
										<?php } ?>
										<span>
											<input type="text" name="wc_sc_product_quantity_restrictions[values][product][<?php echo esc_attr( $product_id ); ?>][product_id]" placeholder="<?php echo esc_attr( $product_id ); ?>" value="<?php echo esc_attr( $product_name ); ?>" disabled>
											<input type="number" name="wc_sc_product_quantity_restrictions[values][product][<?php echo esc_attr( $product_id ); ?>][min]" class="product_min_quantity_field" placeholder="<?php echo esc_attr__( 'No minimum', 'woocommerce-smart-coupons' ); ?>" value="<?php echo esc_attr( $product_min_quantity ); ?>" min="0">
											<input type="hidden" name="wc_sc_product_quantity_restrictions[values][product][<?php echo esc_attr( $product_id ); ?>][max]" class="product_max_quantity_field" placeholder="<?php echo esc_attr__( 'No maximum', 'woocommerce-smart-coupons' ); ?>" value="<?php echo esc_attr( $product_max_quantity ); ?>" min="0">
										</span>
									</p>
									<?php
								}
							}
							$display_label = false;
						}
					} else {
						?>
						<p class="form-field">
							<span class='left_column'><label><?php echo esc_html__( 'Products', 'woocommerce-smart-coupons' ); ?></label></span>
							<label class="hypertext_css wc_sc_click_select_product">
								<?php echo esc_html__( 'Please select some products', 'woocommerce-smart-coupons' ); ?>
							</label>
						</p>
						<?php
					}
					?>
				</div>
				<div class=" wc_sc_category_quantity" style="<?php echo isset( $product_quantity_restrictions['type'] ) && ( 'product' === $product_quantity_restrictions['type'] ) ? '' : 'display: none;'; ?>">
					<?php
					$product_category_quantities = ! empty( $product_quantity_restrictions['values']['product_category'] ) ? $product_quantity_restrictions['values']['product_category'] : array();
					if ( ! empty( $product_category_quantities ) ) {
						$i = 0;
						foreach ( $product_category_quantities as $category_id => $value ) {
							if ( 0 !== $category_id ) {
								$term = get_term_by( 'id', $category_id, 'product_cat', ARRAY_A );
								if ( ! empty( $term ) && is_array( $term ) ) {
									$category_name = ! empty( $term['name'] ) ? $term['name'] : '';
									?>
									<p class="form-field" data-index="<?php echo esc_attr( $category_id ); ?>">
										<?php if ( 0 === $i ) { ?>
											<label><?php echo esc_html__( 'Categories', 'woocommerce-smart-coupons' ); ?></label>
										<?php } ?>
										<span>
											<input type="text" name="wc_sc_product_quantity_restrictions[values][product_category][<?php echo esc_attr( $category_id ); ?>][category_id]" placeholder="<?php echo esc_attr( $category_id ); ?>" value="<?php echo esc_attr( $category_name ); ?>" disabled>
											<input type="number" name="wc_sc_product_quantity_restrictions[values][product_category][<?php echo esc_attr( $category_id ); ?>][min]" class="product_min_quantity_field" placeholder="<?php echo esc_attr__( 'No minimum', 'woocommerce-smart-coupons' ); ?>" value="<?php echo esc_attr( ! empty( $value['min'] ) ? intval( $value['min'] ) : '' ); ?>" min="0">
											<input type="hidden" name="wc_sc_product_quantity_restrictions[values][product_category][<?php echo esc_attr( $category_id ); ?>][max]" class="product_max_quantity_field" placeholder="<?php echo esc_attr__( 'No maximum', 'woocommerce-smart-coupons' ); ?>" value="<?php echo esc_attr( ! empty( $value['max'] ) ? intval( $value['max'] ) : '' ); ?>" min="0">
										</span>
									</p>
									<?php

								}
							}
							$i++;
						}
					} else {
						?>
						<p class="form-field">
							<span class='left_column'><label><?php echo esc_html__( 'Categories', 'woocommerce-smart-coupons' ); ?></label></span>
							<label for="product_categories" class="hypertext_css">
								<?php echo esc_html__( 'Please select some categories', 'woocommerce-smart-coupons' ); ?>
							</label>
						</p>
						<?php
					}
					?>
				</div>
			</div>


			<div class="wc_sc_auto_generate_product_quantity" style="display: none;">
				<p class="form-field" data-index="{i}">
					<label>{p}</label>
					<span>
						<input type="text" name="wc_sc_product_quantity_restrictions[values][product][{i}][product_id]" placeholder="{i}" value="{n}" disabled>
						<input type="number" name="wc_sc_product_quantity_restrictions[values][product][{i}][min]" class="product_min_quantity_field" placeholder="<?php echo esc_attr__( 'No minimum', 'woocommerce-smart-coupons' ); ?>" min="0">
						<input type="hidden" name="wc_sc_product_quantity_restrictions[values][product][{i}][max]" class="product_max_quantity_field" placeholder="<?php echo esc_attr__( 'No maximum', 'woocommerce-smart-coupons' ); ?>" min="0">
					</span>
				</p>
			</div>


			<div class="wc_sc_auto_generate_category_quantity" style="display: none;">
				<p class="form-field" data-index="{i}">
					<label>{c}</label>
					<span>
						<input type="text" name="wc_sc_product_quantity_restrictions[values][product_category][{i}][category_id]" placeholder="{i}" value="{n}" disabled>
						<input type="number" name="wc_sc_product_quantity_restrictions[values][product_category][{i}][min]" class="product_min_quantity_field" placeholder="<?php echo esc_attr__( 'No minimum', 'woocommerce-smart-coupons' ); ?>" min="0">
						<input type="hidden" name="wc_sc_product_quantity_restrictions[values][product_category][{i}][max]" class="product_max_quantity_field" placeholder="<?php echo esc_attr__( 'No maximum', 'woocommerce-smart-coupons' ); ?>" min="0">
					</span>
				</p>
			</div>

			<div class="wc_sc_empty_products" style="display: none;">
				<p class="form-field">
					<span class='left_column'><label><?php echo esc_html__( 'Products', 'woocommerce-smart-coupons' ); ?></label></span>
					<label class="hypertext_css wc_sc_click_select_product">
						<?php echo esc_html__( 'Please select some products', 'woocommerce-smart-coupons' ); ?>
					</label>
				</p>
			</div>
			<div class="wc_sc_empty_categories" style="display: none;">
				<p class="form-field">
					<span class='left_column'><label><?php echo esc_html__( 'Categories', 'woocommerce-smart-coupons' ); ?></label></span>
					<label for="product_categories" class="hypertext_css">
						<?php echo esc_html__( 'Please select some categories', 'woocommerce-smart-coupons' ); ?>
					</label>
				</p>
			</div>
			<div class="wc_sc_product_section_title" style="display: none;">
				<?php echo esc_html__( 'Products', 'woocommerce-smart-coupons' ); ?>
			</div>
			<div class="wc_sc_category_section_title" style="display: none;">
				<?php echo esc_html__( 'Categories', 'woocommerce-smart-coupons' ); ?>
			</div>
			<?php
		}


		/**
		 * Styles and scripts
		 */
		public function styles_and_scripts() {
			if ( ! wp_script_is( 'jquery' ) ) {
				wp_enqueue_script( 'jquery' );
			}
			?>
			<style type="text/css">
				.wc_sc_product_quantity_type_product {
					margin-left: 10px !important;
				}

				.product_min_quantity_field {
					width: 15% !important;
					margin-left: 10px !important;
				}
				.hypertext_css {
					width: 50% !important;
					color: #1870f0 !important;
					text-decoration: underline !important;
					width: 275px !important;
					margin-left: 1px !important;
				}
				.width_32{
					width: 32% !important;
				}
				.padding_left_3{
					padding-left: 3px;
				}
				label.cart_quantity{
					width: 75px !important;
					margin-left: 1px !important;
				}

				label.product_quantity {
					margin-left: 0px !important;
				}
			</style>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {
					/**
					 *Change quantity type cart or product.
					 */
					$(document).on('change', '.wc_sc_product_quantity_type', function () {
						let value = $('input[name="wc_sc_product_quantity_restrictions[type]"]:checked').val();
						if ("product" === value) {
							$('.wc_sc_cart_quantity').hide();
							$('.wc_sc_product_quantity, .wc_sc_category_quantity').show();
						} else {
							$('.wc_sc_cart_quantity').show();
							$('.wc_sc_product_quantity, .wc_sc_category_quantity').hide();
						}
					});
					/**
					 *Auto add product field.
					 */
					$(document).on('change', 'select[name="product_ids[]"]', function () {
						var product_element_count = 0;
						$('.wc_sc_product_quantity').empty();
						let options =  $("select[name='product_ids[]'] option:selected").val();
						if( options === undefined ){
							let product_empty = $('.wc_sc_empty_products').html();
							$('.wc_sc_product_quantity').append(product_empty);
						}

						$("select[name='product_ids[]'] option:selected").each(function () {
							var $this = $(this);
							if ($this.length) {
								product_element_count = parseInt(product_element_count) + parseInt(1);
								let product_name = $this.text();
								let product_id = $this.val();
								let section_title = $('.wc_sc_product_section_title').text();
								let wc_sc_product_quantity = $('.wc_sc_auto_generate_product_quantity').html();
								wc_sc_product_quantity = wc_sc_product_quantity.replace(new RegExp('{i}', 'g'), product_id);
								wc_sc_product_quantity = wc_sc_product_quantity.replace(new RegExp('{n}', 'g'), product_name);

								if (product_element_count >= 2) {
									wc_sc_product_quantity = wc_sc_product_quantity.replace(new RegExp('{p}', 'g'), '');
								} else {
									wc_sc_product_quantity = wc_sc_product_quantity.replace(new RegExp('{p}', 'g'), section_title);
								}
								$('.wc_sc_product_quantity').append(wc_sc_product_quantity);
							}
						});
					});

					/**
					 *Auto add category field.
					 */
					$(document).on('change', 'select[name="product_categories[]"]', function () {
						var category_element_count = 0;
						$('.wc_sc_category_quantity').empty();
						let options =  $("#product_categories option:selected").val();
						if( options === undefined ){
							let category_empty = $('.wc_sc_empty_categories').html();
							$('.wc_sc_category_quantity').append(category_empty);
						}
						$("#product_categories option:selected").each(function () {
							var $this = $(this);
							if ($this.length) {
								category_element_count = parseInt(category_element_count) + parseInt(1);
								let category_name = $this.text();
								let category_id = $this.val();
								let section_title = $('.wc_sc_category_section_title').text();
								let wc_sc_category_quantity = $('.wc_sc_auto_generate_category_quantity').html();
								wc_sc_category_quantity = wc_sc_category_quantity.replace(new RegExp('{i}', 'g'), category_id);
								wc_sc_category_quantity = wc_sc_category_quantity.replace(new RegExp('{n}', 'g'), category_name);

								if (category_element_count >= 2) {
									wc_sc_category_quantity = wc_sc_category_quantity.replace(new RegExp('{c}', 'g'), '');
								} else {
									wc_sc_category_quantity = wc_sc_category_quantity.replace(new RegExp('{c}', 'g'), section_title);
								}
								$('.wc_sc_category_quantity').append(wc_sc_category_quantity);
							}
						});
					});

					$("body").on('click', '.wc_sc_click_select_product', function() {
						$(".wc-product-search").parent('p:nth-child(1)').css('background-color', '#ffffcc').animate({
							backgroundColor: 'transparent'
						}, 2000);
						$('html, body').animate({
							'scrollTop' : $(".wc-product-search").parent('p:nth-child(1)').position().top,
						});
					});
				});
			</script>
			<?php
		}

		/**
		 * Save coupon by product quantity data in meta
		 *
		 * @param Integer   $post_id The coupon post ID.
		 * @param WC_Coupon $coupon The coupon object.
		 */
		public function process_meta( $post_id = 0, $coupon = null ) {

			if ( empty( $post_id ) ) {
				return;
			}

			$coupon = new WC_Coupon( $coupon );

            $product_quantity_restrictions = (isset($_POST['wc_sc_product_quantity_restrictions'])) ? wc_clean(wp_unslash($_POST['wc_sc_product_quantity_restrictions'])) : array(); // phpcs:ignore

			if ( ! isset( $product_quantity_restrictions['condition'] ) ) {
				$product_quantity_restrictions['condition'] = 'any'; // Values: any, all.
			}

			if ( isset( $product_quantity_restrictions['values']['product']['{i}'] ) ) {
				unset( $product_quantity_restrictions['values']['product']['{i}'] );
			}

			if ( isset( $product_quantity_restrictions['values']['product_category']['{i}'] ) ) {
				unset( $product_quantity_restrictions['values']['product_category']['{i}'] );
			}

			if ( ! empty( $product_quantity_restrictions ) ) {
				foreach ( $product_quantity_restrictions as $restriction_key => $restrictions ) {
					if ( 'values' === $restriction_key ) {
						// Max quantity feature not included for product quantity.
						foreach ( $restrictions['product'] as $id => $restriction ) {
							$id = absint( $id );
							if ( 0 !== $id && isset( $product_quantity_restrictions['values']['product'][ $id ]['max'] ) ) {
								$product_quantity_restrictions['values']['product'][ $id ]['max'] = ! empty( $restriction['min'] ) ? intval( $restriction['min'] ) : '';
							}
						}

						// Max quantity feature not included for product category quantity.
						foreach ( $restrictions['product_category'] as $id => $restriction ) {
							$id = absint( $id );
							if ( 0 !== $id && isset( $product_quantity_restrictions['values']['product_category'][ $id ]['max'] ) ) {
								$product_quantity_restrictions['values']['product_category'][ $id ]['max'] = ! empty( $restriction['min'] ) ? intval( $restriction['min'] ) : '';
							}
						}
					}
				}
			}

			if ( $this->is_callable( $coupon, 'update_meta_data' ) && $this->is_callable( $coupon, 'save' ) ) {
				$coupon->update_meta_data( 'wc_sc_product_quantity_restrictions', $product_quantity_restrictions );
				$coupon->save();
			} else {
				$this->update_post_meta( $post_id, 'wc_sc_product_quantity_restrictions', $product_quantity_restrictions );
			}
		}

		/**
		 * Validate the coupon based on product quantity
		 *
		 * @param boolean      $valid Is valid or not.
		 * @param WC_Coupon    $coupon The coupon object.
		 * @param WC_Discounts $wc_discounts The discounts object.
		 *
		 * @return boolean           Is valid or not
		 * @throws Exception If the coupon is invalid.
		 */
		public function validate( $valid = false, $coupon = null, $wc_discounts = null ) {

			// If coupon is invalid already, no need for further checks.
			if ( false === $valid ) {
				return $valid;
			}

			if ( ! is_a( $coupon, 'WC_Coupon' ) ) {
				return $valid;
			}
			if ( ! is_a( $wc_discounts, 'WC_Discounts' ) ) {
				return $valid;
			}

			$items_to_validate = array();
			$cart_quantity     = 0;

			if ( is_callable( array( $wc_discounts, 'get_items_to_validate' ) ) ) {
				$items_to_validate = $wc_discounts->get_items_to_validate();
			} else {
				return $valid;
			}

			if ( ! empty( $items_to_validate ) ) {
				foreach ( $items_to_validate as $key => $cart_content ) {
					$cart_item      = ! empty( $cart_content->object ) ? $cart_content->object : array();
					$cart_quantity += ! empty( $cart_item['quantity'] ) ? intval( $cart_item['quantity'] ) : 0;
				}
			} else {
				return $valid;
			}

			// If the cart quantity is empty the rule will not work.
			if ( $cart_quantity <= 0 ) {
				return $valid;
			}

			$coupon_id = is_callable( array( $coupon, 'get_id' ) ) ? $coupon->get_id() : 0;

			if ( $this->is_callable( $coupon, 'get_meta' ) ) {
				$product_quantity_restrictions = $coupon->get_meta( 'wc_sc_product_quantity_restrictions' );
			} else {
				$product_quantity_restrictions = $this->get_post_meta( $coupon_id, 'wc_sc_product_quantity_restrictions', true );
			}
			if ( is_array( $product_quantity_restrictions ) && ! empty( $product_quantity_restrictions ) ) {
				$type      = ! empty( $product_quantity_restrictions['type'] ) ? $product_quantity_restrictions['type'] : '';
				$values    = ! empty( $product_quantity_restrictions['values'] ) ? $product_quantity_restrictions['values'] : '';
				$condition = ! empty( $product_quantity_restrictions['condition'] ) ? $product_quantity_restrictions['condition'] : 'any';

				switch ( $type ) {
					case 'cart':
						$min      = ! empty( $product_quantity_restrictions['values']['cart']['min'] ) ? intval( $product_quantity_restrictions['values']['cart']['min'] ) : 0;
						$max      = ! empty( $product_quantity_restrictions['values']['cart']['max'] ) ? intval( $product_quantity_restrictions['values']['cart']['max'] ) : 0;
						$messages = array(
							__( 'Your cart does not meet the quantity requirement.', 'woocommerce-smart-coupons' ),
						);

						if ( empty( $min ) && empty( $max ) ) {
							return $valid;
						} elseif ( empty( $min ) && ! empty( $max ) && $cart_quantity <= $max ) {
							return $valid;
						} elseif ( empty( $max ) && ! empty( $min ) && $cart_quantity >= $min ) {
							return $valid;
						} elseif ( ! empty( $min ) && ! empty( $max ) && $cart_quantity >= $min && $cart_quantity <= $max ) {
							return $valid;
						} else {
							if ( $cart_quantity > $max ) {
								/* translators: 1. Number of quantity 2. Singular or plural text based on number of quantities */
								$messages[] = sprintf( __( 'Your cart should have a maximum of %1$d %2$s in total.', 'woocommerce-smart-coupons' ), $max, _n( 'quantity', 'quantities', $max ) );
							}
							if ( $cart_quantity < $min ) {
								/* translators: 1. Number of quantity 2. Singular or plural text based on number of quantities */
								$messages[] = sprintf( __( 'Your cart should have a minimum of %1$d %2$s in total.', 'woocommerce-smart-coupons' ), $min, _n( 'quantity', 'quantities', $min ) );
							}
							throw new Exception( implode( ' ', $messages ) );
						}
						break;
					default:
					case 'product':
						$product_quantity_restrictions          = ! empty( $values['product'] ) ? $values['product'] : array();
						$product_category_quantity_restrictions = ! empty( $values['product_category'] ) ? $values['product_category'] : array();

						$params                     = array(
							'condition'         => $condition,
							'items_to_validate' => $items_to_validate,
						);
						$product_condition          = $this->process_product_quantities( $product_quantity_restrictions, $params );
						$product_category_condition = $this->process_category_quantities( $product_category_quantity_restrictions, $params );

						if ( false === $product_condition && false === $product_category_condition ) {
							throw new Exception( __( 'Your cart does not meet the product quantity requirement.', 'woocommerce-smart-coupons' ) );
						} elseif ( 'empty' === $product_condition && false === $product_category_condition ) {
							throw new Exception( __( 'Your cart does not meet the product quantity requirement.', 'woocommerce-smart-coupons' ) );
						} elseif ( false === $product_condition && 'empty' === $product_category_condition ) {
							throw new Exception( __( 'Your cart does not meet the product quantity requirement.', 'woocommerce-smart-coupons' ) );
						}
						break;
				}
			}

			return $valid;

		}

		/**
		 * Make meta data of this plugin, protected
		 *
		 * @param bool   $protected Is protected.
		 * @param string $meta_key the meta key.
		 * @param string $meta_type The meta type.
		 * @return bool $protected
		 */
		public function make_sc_meta_protected( $protected, $meta_key, $meta_type ) {
			if ( 'wc_sc_product_quantity_restrictions' === $meta_key ) {
				return true;
			}
			return $protected;
		}

		/**
		 * Add product quantity restriction meta with value in coupon meta
		 *
		 * @param  array $data The row data.
		 * @param  array $post The POST values.
		 * @return array Modified data
		 */
		public function generate_coupon_meta( $data = array(), $post = array() ) {

			if ( isset( $post['wc_sc_product_quantity_restrictions'] ) && is_array( $post['wc_sc_product_quantity_restrictions'] ) ) {
				$data['wc_sc_product_quantity_restrictions'] = $this->product_quantity_restrictions_encode( $post['wc_sc_product_quantity_restrictions'] );
			}

			return $data;
		}

		/**
		 * Post meta defaults for product quantity restriction meta
		 *
		 * @param  array $defaults Existing postmeta defaults.
		 * @return array
		 */
		public function postmeta_defaults( $defaults = array() ) {
			return array_merge( $defaults, array( 'wc_sc_product_quantity_restrictions' => '' ) );
		}



		/**
		 * Function to handle coupon meta data during export of existing coupons
		 *
		 * @param  mixed $meta_value The meta value.
		 * @param  array $args       Additional arguments.
		 * @return string Processed meta value
		 */
		public function export_coupon_meta( $meta_value = '', $args = array() ) {

			$index       = ( ! empty( $args['index'] ) ) ? $args['index'] : -1;
			$meta_keys   = ( ! empty( $args['meta_keys'] ) ) ? $args['meta_keys'] : array();
			$meta_values = ( ! empty( $args['meta_values'] ) ) ? $args['meta_values'] : array();

			if ( $index >= 0 && ! empty( $meta_keys[ $index ] ) && 'wc_sc_product_quantity_restrictions' === $meta_keys[ $index ] && ! empty( $meta_values[ $index ] ) ) {
				$meta_value = maybe_unserialize( $meta_values[ $index ] ); // phpcs:ignore
				$meta_value = $this->product_quantity_restrictions_encode( $meta_value );
			}

			return $meta_value;

		}

		/**
		 * Process coupon meta value for import
		 *
		 * @param  mixed $meta_value The meta value.
		 * @param  array $args       Additional Arguments.
		 * @return mixed $meta_value
		 */
		public function process_coupon_meta_value_for_import( $meta_value = null, $args = array() ) {

			if ( ! empty( $args['meta_key'] ) && 'wc_sc_product_quantity_restrictions' === $args['meta_key'] && ! empty( $args['postmeta']['wc_sc_product_quantity_restrictions'] ) ) {
				$meta_value = $this->product_quantity_restrictions_decode( $args['postmeta']['wc_sc_product_quantity_restrictions'] );
			}

			return $meta_value;
		}

		/**
		 * Add product quantity restriction meta in export headers
		 *
		 * @param  array $headers Existing headers.
		 * @return array
		 */
		public function export_headers( $headers = array() ) {

			$product_quantity_restriction_headers = array(
				'wc_sc_product_quantity_restrictions' => __( 'Product quantity based restrictions', 'woocommerce-smart-coupons' ),
			);

			return array_merge( $headers, $product_quantity_restriction_headers );

		}

		/**
		 * Function to copy coupon product quantity restriction meta in newly generated coupon
		 *
		 * @param  array $args The arguments.
		 */
		public function copy_meta( $args = array() ) {

			$new_coupon_id = ( ! empty( $args['new_coupon_id'] ) ) ? absint( $args['new_coupon_id'] ) : 0;
			$coupon        = ( ! empty( $args['ref_coupon'] ) ) ? $args['ref_coupon'] : false;

			if ( empty( $new_coupon_id ) || empty( $coupon ) ) {
				return;
			}

			$add_product_details = array();
			if ( $this->is_wc_gte_30() && $this->is_callable( $coupon, 'get_meta' ) && $this->is_callable( $coupon, 'update_meta_data' ) && $this->is_callable( $coupon, 'save' ) ) {
				$add_product_details = $coupon->get_meta( 'wc_sc_product_quantity_restrictions' );
				$coupon->update_meta_data( 'wc_sc_product_quantity_restrictions', $add_product_details );
				$coupon->save();
			} else {
				$old_coupon_id       = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
				$add_product_details = $this->get_post_meta( $old_coupon_id, 'wc_sc_product_quantity_restrictions', true );
				$this->update_post_meta( $new_coupon_id, 'wc_sc_product_quantity_restrictions', $add_product_details );
			}

		}

		/**
		 * Process cart product quantities
		 *
		 * @param array $product_quantity_restrictions values.
		 * @param array $params condition and cart contents.
		 * @return bool
		 * @throws Exception If empty product quantities.
		 */
		public function process_product_quantities( $product_quantity_restrictions = array(), $params = array() ) {
			if ( ! empty( $product_quantity_restrictions ) ) {
				$status            = array();
				$condition         = ! empty( $params['condition'] ) ? $params['condition'] : 'any';
				$items_to_validate = ! empty( $params['items_to_validate'] ) ? $params['items_to_validate'] : array();
				foreach ( $product_quantity_restrictions as $id => $restriction ) {
					if ( 0 === $id ) {
						continue;
					}
					$min_quantity            = ! empty( $restriction['min'] ) ? intval( $restriction['min'] ) : 0;
					$max_quantity            = ! empty( $restriction['max'] ) ? intval( $restriction['max'] ) : 0;
					$cart_product_quantities = $this->cart_product_quantities( $items_to_validate );
					$product_quantity        = ! empty( $cart_product_quantities[ $id ] ) ? intval( $cart_product_quantities[ $id ] ) : 1;

					if ( empty( $min_quantity ) && empty( $max_quantity ) ) {
						$status[] = 'empty';
					} elseif ( empty( $min_quantity ) && ! empty( $max_quantity ) && $product_quantity <= $max_quantity ) {
						$status[] = 'true';
					} elseif ( empty( $max_quantity ) && ! empty( $min_quantity ) && $product_quantity >= $min_quantity ) {
						$status[] = 'true';
					} elseif ( ! empty( $min_quantity ) && ! empty( $max_quantity ) && $product_quantity >= $min_quantity && $product_quantity <= $max_quantity ) {
						$status[] = 'true';
					} else {
						$status[] = 'false';
					}
				}

				switch ( $condition ) {
					case 'all':
						if ( in_array( 'false', $status, true ) ) {
							return false;
						} elseif ( in_array( 'true', $status, true ) ) {
							return true;
						} else {
							return 'empty';
						}
					default:
					case 'any':
						if ( in_array( 'true', $status, true ) ) {
							return true;
						} elseif ( in_array( 'false', $status, true ) ) {
							return false;
						} else {
							return 'empty';
						}
				}
			}
			return 'empty';
		}

		/**
		 * Process cart category quantities
		 *
		 * @param array $product_category_quantity_restrictions values.
		 * @param array $params condition and cart contents.
		 * @return bool
		 * @throws Exception If empty cart category quantities.
		 */
		public function process_category_quantities( $product_category_quantity_restrictions = array(), $params = array() ) {

			if ( ! empty( $product_category_quantity_restrictions ) ) {
				$status                             = array();
				$condition                          = ! empty( $params['condition'] ) ? $params['condition'] : 'any';
				$items_to_validate                  = ! empty( $params['items_to_validate'] ) ? $params['items_to_validate'] : array();
				$cart_product_categories_quantities = $this->cart_product_categories_quantities( $items_to_validate );
				foreach ( $product_category_quantity_restrictions as $id => $restriction ) {
					$min_quantity = ! empty( $restriction['min'] ) ? intval( $restriction['min'] ) : '';
					$max_quantity = ! empty( $restriction['max'] ) ? intval( $restriction['max'] ) : '';
					if ( 0 === $id ) {
						continue;
					}
					$category_quantity = ! empty( $cart_product_categories_quantities[ $id ] ) ? intval( $cart_product_categories_quantities[ $id ] ) : 0;

					if ( empty( $min_quantity ) && empty( $max_quantity ) ) {
						$status[] = 'empty';
					} elseif ( empty( $min_quantity ) && ! empty( $max_quantity ) && $category_quantity <= $max_quantity ) {
						$status[] = 'true';
					} elseif ( empty( $max_quantity ) && ! empty( $min_quantity ) && $category_quantity >= $min_quantity ) {
						$status[] = 'true';
					} elseif ( ! empty( $min_quantity ) && ! empty( $max_quantity ) && $category_quantity >= $min_quantity && $category_quantity <= $max_quantity ) {
						$status[] = 'true';
					} else {
						$status[] = 'false';
					}
				}

				switch ( $condition ) {
					case 'all':
						if ( in_array( 'false', $status, true ) ) {
							return false;
						} elseif ( in_array( 'true', $status, true ) ) {
							return true;
						} else {
							return 'empty';
						}
					default:
					case 'any':
						if ( in_array( 'true', $status, true ) ) {
							return true;
						} elseif ( in_array( 'false', $status, true ) ) {
							return false;
						} else {
							return 'empty';
						}
				}
			}
			return 'empty';
		}

		/**
		 * Calculate product quantities
		 *
		 * @param array $cart_contents cart contents.
		 * @return array
		 */
		public function cart_product_quantities( $cart_contents = array() ) {
			if ( empty( $cart_contents ) ) {
				return $cart_contents;
			}
			$cart_product_quantities = array();
			foreach ( $cart_contents as $key => $cart_content ) {
				$cart_item    = ! empty( $cart_content->object ) ? $cart_content->object : array();
				$quantity     = ! empty( $cart_item['quantity'] ) ? intval( $cart_item['quantity'] ) : 1;
				$product_id   = ! empty( $cart_item['product_id'] ) ? intval( $cart_item['product_id'] ) : 0;
				$variation_id = ! empty( $cart_item['variation_id'] ) ? intval( $cart_item['variation_id'] ) : 0;
				if ( ! empty( $variation_id ) ) {
					if ( isset( $cart_product_quantities[ $variation_id ] ) ) {
						$cart_product_quantities[ $variation_id ] = $cart_product_quantities[ $variation_id ] + $quantity;
					} else {
						$cart_product_quantities[ $variation_id ] = $quantity;
					}
				}

				if ( isset( $cart_product_quantities[ $product_id ] ) ) {
					$cart_product_quantities[ $product_id ] = $cart_product_quantities[ $product_id ] + $quantity;
				} else {
					$cart_product_quantities[ $product_id ] = $quantity;
				}
			}
			return $cart_product_quantities;
		}

		/**
		 * Calculate category quantities
		 *
		 * @param array $cart_contents cart contents.
		 * @return array
		 */
		public function cart_product_categories_quantities( $cart_contents = array() ) {
			if ( empty( $cart_contents ) ) {
				return $cart_contents;
			}
			$categories_quantities = array();
			foreach ( $cart_contents as $key => $cart_content ) {
				$cart_item = ! empty( $cart_content->object ) ? $cart_content->object : array();
				$product   = ! empty( $cart_item['data'] ) ? $cart_item['data'] : array();
				$quantity  = ! empty( $cart_item['quantity'] ) ? intval( $cart_item['quantity'] ) : 1;

				if ( is_object( $product ) && is_callable( array( $product, 'get_category_ids' ) ) ) {
					$product_variation = ( is_callable( array( $product, 'is_type' ) ) ) ? $product->is_type( 'variation' ) : false;
					if ( $product_variation ) {
						$parent_id = ( is_callable( array( $product, 'get_parent_id' ) ) ) ? $product->get_parent_id() : 0;
						if ( ! empty( $parent_id ) ) {
							$product = wc_get_product( $parent_id );
						}
					}
					$categories = $product->get_category_ids();
					if ( ! empty( $categories ) ) {
						foreach ( $categories as $category ) {
							if ( isset( $categories_quantities[ $category ] ) ) {
								$categories_quantities[ $category ] = $categories_quantities[ $category ] + $quantity;
							} else {
								$categories_quantities[ $category ] = $quantity;
							}
						}
					}
				}
			}
			return $categories_quantities;
		}

		/**
		 * Calculate category quantities
		 *
		 * @param array $input_array .
		 * @return string
		 */
		public function product_quantity_restrictions_encode( $input_array = array() ) {
			if ( ! is_array( $input_array ) || empty( $input_array ) || ! isset( $input_array['type'] ) ) {
				return '';
			}

			$encode_string = '';

			switch ( $input_array['type'] ) {
				case 'cart':
					if ( isset( $input_array['values'] ) && isset( $input_array['values']['cart'] ) && ! empty( $input_array['values']['cart'] ) ) {
						$cart_data     = $input_array['values']['cart'];
						$min           = isset( $cart_data['min'] ) && ! empty( $cart_data['min'] ) && is_scalar( $cart_data['min'] ) ? (int) $cart_data['min'] : 0;
						$max           = isset( $cart_data['max'] ) && ! empty( $cart_data['max'] ) && is_scalar( $cart_data['max'] ) ? (int) $cart_data['max'] : 0;
						$encode_string = 'cart@' . $min . '_' . $max;
					}
					break;
				case 'product':
					$segment = array();
					if ( isset( $input_array['values'] ) && isset( $input_array['values']['product'] ) && ! empty( $input_array['values']['product'] ) ) {
						$item_product = array();
						foreach ( $input_array['values']['product'] as $product_id => $product_data ) {
							$product_id = (int) $product_id;
							$min        = isset( $product_data['min'] ) && ! empty( $product_data['min'] ) && is_scalar( $product_data['min'] ) ? (int) $product_data['min'] : 0;
							$max        = isset( $product_data['max'] ) && ! empty( $product_data['max'] ) && is_scalar( $product_data['max'] ) ? (int) $product_data['max'] : 0;
							if ( ! empty( $product_id ) ) {
								$item_product[] = $product_id . '-' . $min . '_' . $max;
							}
						}
						if ( ! empty( $item_product ) ) {
							$segment[] = 'product@' . implode( '#', $item_product );
						}
					}

					if ( isset( $input_array['values'] ) && isset( $input_array['values']['product_category'] ) && ! empty( $input_array['values']['product_category'] ) ) {
						$item_category = array();
						foreach ( $input_array['values']['product_category'] as $category_id => $category_data ) {
							$category_id = (int) $category_id;
							$min         = isset( $category_data['min'] ) && ! empty( $category_data['min'] ) && is_scalar( $category_data['min'] ) ? (int) $category_data['min'] : 0;
							$max         = isset( $category_data['max'] ) && ! empty( $category_data['max'] ) && is_scalar( $category_data['max'] ) ? (int) $category_data['max'] : 0;
							if ( ! empty( $category_id ) ) {
								$item_category[] = $category_id . '-' . $min . '_' . $max;
							}
						}
						if ( ! empty( $item_category ) ) {
							$segment[] = 'category@' . implode( '#', $item_category );
						}
					}

					if ( ! empty( $segment ) ) {
						$encode_string = implode( '|', $segment );
					}
					break;
			}

			return $encode_string;

		}

		/**
		 * Calculate category quantities
		 *
		 * @param string $decode_string .
		 * @return array
		 */
		public function product_quantity_restrictions_decode( $decode_string = '' ) {
			if ( empty( $decode_string ) || ! is_string( $decode_string ) ) {
				return array();
			}
			$input_array = array(
				'type'      => '',
				'values'    => array(),
				'condition' => 'any',
			);

			$segments = explode( '|', $decode_string );
			if ( is_array( $segments ) && ! empty( $segments ) ) {
				foreach ( $segments as $segment ) {
					$parts = explode( '@', $segment );

					switch ( $parts[0] ) {
						case 'cart':
							$cart_values                   = explode( '_', $parts[1] );
							$input_array['type']           = 'cart';
							$input_array['values']['cart'] = array(
								'min' => (int) ( $cart_values[0] ?? 0 ),
								'max' => (int) ( $cart_values[1] ?? 0 ),
							);
							break;
						case 'product':
							$product_values = explode( '#', $parts[1] );
							foreach ( $product_values as $product_item ) {
								$product_data        = explode( '-', $product_item );
								$input_array['type'] = 'product';
								$min_max             = isset( $product_data[1] ) && ! empty( $product_data[1] ) ? explode( '_', $product_data[1] ) : array();
								$input_array['values']['product'][ $product_data[0] ] = array(
									'min' => (int) ( $min_max[0] ?? 0 ),
									'max' => (int) ( $min_max[1] ?? 0 ),
								);
							}
							break;
						case 'category':
							$category_values = explode( '#', $parts[1] );
							foreach ( $category_values as $category_item ) {
								$category_data       = explode( '-', $category_item );
								$input_array['type'] = 'product';
								$min_max             = isset( $category_data[1] ) && ! empty( $category_data[1] ) ? explode( '_', $category_data[1] ) : array();
								$input_array['values']['product_category'][ $category_data[0] ] = array(
									'min' => (int) ( $min_max[0] ?? 0 ),
									'max' => (int) ( $min_max[1] ?? 0 ),
								);
							}
							break;
					}
				}
			}

			return $input_array;
		}

	}
}

WC_SC_Coupons_By_Product_Quantity::get_instance();
