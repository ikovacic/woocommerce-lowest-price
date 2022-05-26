<?php
namespace Lowest_Price;
use Lowest_Price;

class Front {

    public static $asset_name = 'lowest-price';

    public function __construct() {

        if( WPLP_DISPLAY_TYPE == 'alt' ) {

            add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_style' ) );

            add_action( 'woocommerce_product_meta_end', array( $this, 'display_lowest_price_in_meta' ) );

        } else {

            add_filter( 'woocommerce_get_price_html', array( $this, 'get_price_html' ), 1000, 2 );

        }

    }

    public function get_lowest_price( $object_id, $regular_price ) {

        if( $lowest_price_30_days = get_post_meta( $object_id, '_lowest_price_30_days', true ) ) {
            return $lowest_price_30_days;
        }

        return $regular_price;
    }

    public function get_price_html( $price_html, $product ) {

        if( is_admin() ) {
            return $price_html;
        }

        // CHANGE PRICES ONLY IF PRODUCT IS ON SALE
        if ( !$product->is_on_sale() ) {
            return $price_html;
        }

        if( $product->get_type() == 'variable' ) {

            // VARIABLE PRODUCTS

            $prices = $product->get_variation_prices( false );

            if ( !empty( $prices['price'] ) ) {
                $min_price     = current( $prices['price'] );
                $max_price     = end( $prices['price'] );
                $min_reg_price = current( $prices['regular_price'] );
                $max_reg_price = end( $prices['regular_price'] );

                // CHANGE PRICES ONLY IF VARIANT PRICES ARE THE SAME, IN OTHER CASES USE WP DEFAULTS
                // IF PRICES ARE DIFFERENT DISPLAY RANGE & SHOW PRICES FOR EACH VARIANT
                if( !( WPLP_VARIANT_LOOP == 'min' && get_the_id() != get_queried_object()->ID ) && ( $min_reg_price !== $max_reg_price || $min_price !== $max_price  ) ) {
                    return $price_html;
                }

                $regular_price = $product->get_variation_regular_price( 'min' );

                $actual_price = wc_get_price_to_display( $product, array( 'price' => $min_price ) );

            }

        } else {

            // OTHER PRODUCTS (SIMPLE, VARIANTS, GROUPED, ETC.)

            $regular_price = $product->get_regular_price();

            $actual_price = wc_get_price_to_display( $product );

        }


        $lowest_price_in_30_days = wc_get_price_to_display( $product, array( 'price' => $this->get_lowest_price( $product->get_id(), $regular_price ) ) );

        if( WPLP_DISPLAY_TYPE == 'text' ) {

            $price_html = '<span class="lowest_price">' . __( 'Lowest price in last 30 days', 'lowest-price' ) . ': <span class="lowest_amount">' . wc_price( $lowest_price_in_30_days ) .  $product->get_price_suffix() . '</span></span><br />';
            $price_html .= '<span class="actual_price">' . __( 'Actual price', 'lowest-price' ) . ': <span class="actual_amount">' . wc_price( $actual_price ) . $product->get_price_suffix() . '</span></span>';

        } else {

            $price_html = wc_format_sale_price( $lowest_price_in_30_days, $actual_price ) . $product->get_price_suffix();

        }

        return $price_html;
    }

    public function display_lowest_price_in_meta() { 

        global $product;

        if( !$product->is_on_sale() ) {
            return;
        }

        if( $product->get_type() == 'variable' ) {

            $prices = $product->get_variation_prices( false );

            $min_price     = current( $prices['price'] );
            $max_price     = end( $prices['price'] );
            $min_reg_price = current( $prices['regular_price'] );
            $max_reg_price = end( $prices['regular_price'] );

            if( $min_reg_price === $max_reg_price && $min_price === $max_price ) {

                $regular_price = $product->get_variation_regular_price( 'min' );

                $lowest_price_in_30_days = $this->get_lowest_price( $product->get_id(), $regular_price );

                $price = '<span class="lowest_amount">' . strip_tags( wc_price( wc_get_price_to_display( $product, array( 'price' => $lowest_price_in_30_days ) ) ) . $product->get_price_suffix() ) . '</span>';

            } else {

                $prices_arr = array(
                    __( 'N/A', 'lowest-price' ),
                );

                foreach ( $prices[ 'price' ] as $k => $value) {

                    if( $prices[ 'regular_price' ][ $k ] > $value ) {

                        $lowest_price_in_30_days = $this->get_lowest_price( $k, $prices[ 'regular_price' ][ $k ] );

                        $prices_arr[ $k ] = strip_tags( wc_price( wc_get_price_to_display( $product, array( 'price' => $lowest_price_in_30_days ) ) ) . $product->get_price_suffix() );

                    } else {

                        $prices_arr[ $k ] = __( 'N/A', 'lowest-price' );
                    }

                }

                $price = '<span class="lowest_amount js-variable-price" data-variations=\'' . json_encode($prices_arr) . '\'>' . $prices_arr[ 0 ] . '</span>';

            }

        } else {

            $lowest_price_in_30_days = $this->get_lowest_price( $product->get_id(), $product->get_regular_price() );

            $price = '<span class="lowest_amount">' . strip_tags( wc_price( wc_get_price_to_display( $product, array( 'price' => $lowest_price_in_30_days ) ) ) .  $product->get_price_suffix() ) . '</span>';

        }

        echo '<span class="lowest_price">' . __( 'Lowest price in last 30 days', 'lowest-price' ) . ': ' . $price . '</span>';
    }

    public function wp_enqueue_style() {

        $plugin_version = Lowest_Price::$plugin_version;

        wp_enqueue_script( self::$asset_name, Lowest_Price::$plugin_url . '/asset/public/script.js', array( 'jquery' ), $plugin_version, false );
    }
}
