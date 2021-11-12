<?php
/**
 * Plugin Name: WooCommerce Name Your Price - Per Product Text Strings
 * Plugin URI:  http://github.com/kathyisawesome/wc-nyp-product-texts
 * Description: Mini-extension to add custom Name Your Price text fields per product.
 * Version: 1.0.0-beta-1
 * Author:      Kathy Darling
 * Author URI:  http://www.kathyisawesome.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wc-nyp-product-texts
 * Domain Path: /languages
 * Requires at least: 5.8.0
 * Tested up to: 5.8.0
 * WC requires at least: 5.7.0
 * WC tested up to: 5.9.0   
 */

/**
 * Copyright: © 2021 Kathy Darling.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * The Main WC_NYP_Product_Texts class
 **/
if ( ! class_exists( 'WC_NYP_Product_Texts' ) ) :

class WC_NYP_Product_Texts {

	const VERSION = '1.0.0-beta-1';

	const KEYS = [
		'suggested_text',
		'minimum_text',
		'label_text',
		'button_text',
		'button_text_single',
	];

	/**
	 * Attach hooks and filters
	 */
	public static function init() {

		if ( ! did_action( 'wc_nyp_loaded' ) ) {
			return false;
		}

		// Load translation files.
		add_action( 'init', [ __CLASS__, 'load_plugin_textdomain' ], 20 );

		// Admin/
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'custom_scripts' ], 20 );
		add_action( 'wc_nyp_options_pricing', [ __CLASS__, 'add_nav' ], 0, 2 );
		add_action( 'wc_nyp_options_pricing', [ __CLASS__, 'add_extra_inputs' ], 50, 2 );
		add_action( 'woocommerce_admin_process_product_object', [ __CLASS__, 'save_product_meta' ] );
        		
		// Frontend.
		add_action( 'the_post', [ __CLASS__, 'attach_filters' ], 11 );
		
	}








	/*-----------------------------------------------------------------------------------*/
	/* Localization */
	/*-----------------------------------------------------------------------------------*/


	/**
	 * Make the plugin translation ready
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/plugins/wc-nyp-tickets-LOCALE.mo
	 *      - WP_CONTENT_DIR/plugins/woocommerce-name-your-price-event-tickets/languages/wc-nyp-tickets-LOCALE.mo
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public static function load_plugin_textdomain() {
		load_plugin_textdomain( 'wc-nyp-product-texts' , false , dirname( plugin_basename( __FILE__ ) ) .  '/languages/' );
	}

    
	/*-----------------------------------------------------------------------------------*/
	/* Admin */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Add styles and scripts.
	 */
	public static function custom_scripts() {

		$custom_css = "
			.nyp-advanced .nav-tab { cursor: pointer; }
			.nyp-advanced .nav-tab-active, .nyp-advanced .nav-tab-active:hover, .nyp-advanced .nav-tab-active:focus, .nyp-advanced .nav-tab-active:focus:active {
				border-bottom: 1px solid #FFF;
				background: #FFF;
			}
			.form-field.show_if_nyp_advanced {
				display: none;
			}
		";
        wp_add_inline_style( 'woocommerce_admin_styles', $custom_css );

		$custom_js = "
			jQuery( document ).ready( function( $ ) {

				$( '.nyp-advanced a' ).on( 'click', function( e ) {

					var link = $( this );
					var options_group = link.closest( '.options_group' );

					e.preventDefault();
					$( '.nyp-advanced a' ).removeClass( 'nav-tab-active' );
					$( this ).addClass( 'nav-tab-active' );

					if ( 'advanced' === link.data( 'target' ) ) {
						// Stash currently visible fields.
						$( '.nyp-advanced a:first' ).data( 'restore', options_group.find( '.form-field:not(.show_if_nyp_advanced):visible' ) );
						
						options_group.find( '.form-field:not(.show_if_nyp_advanced)' ).hide();
						options_group.find( '.show_if_nyp_advanced' ).show();
					} else {
						// Restore previously visible fields.
						if ( 'undefined' !== link.data( 'restore' ) ) {
							$( link.data( 'restore' ) ).show();
						} else {
							options_group.find( '.form-field:not(.show_if_nyp_advanced)' ).show();
						}
						options_group.find( '.show_if_nyp_advanced' ).hide();
					}

				} );

                
			} );
		";
		wp_add_inline_script( 'wc-admin-product-meta-boxes', $custom_js );
	
	}


	/**
	 * Add panel control
	 *
	 * @param  object WC_Product $product_object
	 * @param  bool              $show_billing_period_options
	 * @param  mixed int|false   $loop - for use in variations
	 */
	public static function add_nav( $product_object, $show_billing_period_options = false, $loop = false ) {
		?>
			<nav class="nav-tab-wrapper woo-nav-tab-wrapper nyp-advanced">
				<a class="nav-tab nav-tab-active" data-target=""><?php esc_html_e( 'Prices', 'wc-nyp-product-texts' );?></a>
				<a class="nav-tab" data-target="advanced"><?php esc_html_e( 'Name Your Price Advanced', 'wc-nyp-product-texts' );?></a>
			</nav>
		<?php
			
	}

	/**
	 * Add maximum inputs to product metabox
	 *
	 * @param  object WC_Product $product_object
	 * @param  bool              $show_billing_period_options
	 * @param  mixed int|false   $loop - for use in variations
	 */
	public static function add_extra_inputs( $product_object, $show_billing_period_options = false, $loop = false ) {

		// Suggested price string.
		woocommerce_wp_text_input(
			array(
				'id'            => 'wc_nyp_suggested_text',
				'class'         => '',
				'wrapper_class' => 'show_if_nyp_advanced',
				'label'         => esc_html__( 'Suggested price text', 'wc-nyp-product-texts' ),
				'desc_tip'      => 'true',
				'description'   => esc_html__( 'This is the text to display before the suggested price. You can use the placeholder %PRICE% to display the suggested price.', 'wc-nyp-product-texts' ),
				'value'         => $product_object->get_meta( '_wc_nyp_suggested_text', true ),
				//'placeholder'   => get_option( 'woocommerce_nyp_suggested_text' ),
			)
		);

        // Minimum price string.
		woocommerce_wp_text_input(
			array(
				'id'            => 'wc_nyp_minimum_text',
				'class'         => '',
				'wrapper_class' => 'show_if_nyp_advanced',
				'label'         => esc_html__( 'Minimum price text', 'wc-nyp-product-texts' ),
				'desc_tip'      => 'true',
				'description'   => esc_html__( 'This is the text to display before the minimum accepted price. You can use the placeholder %PRICE% to display the suggested price.', 'wc-nyp-product-texts' ),
				'value'         => $product_object->get_meta( '_wc_nyp_minimum_text', true ),
				//'placeholder'   => get_option( 'woocommerce_nyp_minimum_text' ),
			)
		);

		// Call to action "name your price" string.
		woocommerce_wp_text_input(
			array(
				'id'            => 'wc_nyp_label_text',
				'class'         => '',
				'wrapper_class' => 'show_if_nyp_advanced',
				'label'         => esc_html__( 'Call to action text', 'wc-nyp-product-texts' ),
				'desc_tip'      => 'true',
				'description'   => esc_html__( 'This is the text that appears above the Name Your Price input field.', 'wc-nyp-product-texts' ),
				'value'         => $product_object->get_meta( '_wc_nyp_label_text', true ),
				//'placeholder'   => get_option( 'woocommerce_nyp_button_text' ),
			)
		);

		// Shop Add to cart button text.
		woocommerce_wp_text_input(
			array(
				'id'            => 'wc_nyp_button_text',
				'class'         => '',
				'wrapper_class' => 'show_if_nyp_advanced',
				'label'         => esc_html__( 'Add to Cart Button Text for Shop', 'wc-nyp-product-texts' ),
				'desc_tip'      => 'true',
				'description'   => esc_html__( 'This is the text that appears on the Add to Cart buttons on the Shop Pages.', 'wc-nyp-product-texts' ),
				'value'         => $product_object->get_meta( '_wc_nyp_button_text', true ),
				//'placeholder'   => get_option( 'woocommerce_nyp_button_text' ),
			)
		);

		// Single product add to cart text.
		woocommerce_wp_text_input(
			array(
				'id'            => 'wc_nyp_button_text_single',
				'class'         => '',
				'wrapper_class' => 'show_if_nyp_advanced',
				'label'         => esc_html__( 'Add to Cart Button Text for Single Product', 'wc-nyp-product-texts' ),
				'desc_tip'      => 'true',
				'description'   => esc_html__( 'This is the text that appears on the Add to Cart buttons on the Single Product Pages.', 'wc-nyp-product-texts' ),
				'value'         => $product_object->get_meta( '_wc_nyp_button_text_single', true ),
				//'placeholder'   => get_option( 'woocommerce_nyp_button_text_single' ),
			)
		);
	}
    
	/**
	 * Save extra meta info
	 *
	 * @param object $product
	 */
	public static function save_product_meta( $product ) {

		foreach ( self::KEYS as $key ) {
			if ( isset( $_POST['wc_nyp_' . $key] ) ) {
                $text = trim( wc_clean( wp_unslash( $_POST['wc_nyp_' . $key] ) ) );
            
                if ( '' !== $text ) {
                    $product->update_meta_data( '_wc_nyp_' . $key, $text );
                } else {
                    $product->delete_meta_data( '_wc_nyp_' . $key );
                }
            }
		}

	}

	/*-----------------------------------------------------------------------------------*/
	/* Front End */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Save extra meta info
	 *
	 * @param mixed $post Post Object.
	 */
	public static function attach_filters( $product ) {

		if ( ! is_admin() ) {
			foreach ( [
				'suggested_text',
				'minimum_text',
				'label_text',
				'button_text',
				'button_text_single',
			] as $key ) {
				//Filter the value.
				add_filter( 'pre_option_woocommerce_nyp_' . $key, function( $value, $option ) {
					global $product;
					$key = str_replace( 'woocommerce_nyp_', '', $option );
					$new = $product instanceof WC_Product ? $product->get_meta( '_wc_nyp_' . $key, true ) : '';
					return $new ? $new : $value;
				}, 10, 2 );
			}
		}
	}

} // End class: do not remove or there will be no more guacamole for you.

endif; // End class_exists check.




// Launch the whole plugin.
add_action( 'plugins_loaded', [ 'WC_NYP_Product_Texts', 'init' ], 20 );

