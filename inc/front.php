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

            add_filter( 'woocommerce_get_price_html', array( $this, 'get_price_html' ), 10, 2 );

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

        // VARIABLE PRODUCTS
        if( $product->get_type() == 'variable' ) {

            $prices = $product->get_variation_prices( true );

            if ( empty( $prices['price'] ) ) {
                $price = apply_filters( 'woocommerce_variable_empty_price_html', '', $product );
            } else {
                $min_price     = current( $prices['price'] );
                $max_price     = end( $prices['price'] );
                $min_reg_price = current( $prices['regular_price'] );
                $max_reg_price = end( $prices['regular_price'] );

                // IF RANGE - RETURN AS IS
                // EVERY VARIANT DISPLAYS IT'S PRICE
                if ( $min_price !== $max_price ) {
                    $price_html = wc_format_price_range( $min_price, $max_price );

                // IF PRICES ARE THE SAME && PRODUCT IS ON SALE
                } elseif ( $product->is_on_sale() && $min_reg_price === $max_reg_price ) {

                    $lowest_price_in_30_days = $this->get_lowest_price( $product->get_id(), $min_reg_price );

                    if( WPLP_DISPLAY_TYPE == 'text' ) {
                        $price_html = '<span class="lowest_price">' . __( 'Lowest price in last 30 days', 'lowest-price' ) . ': <span class="lowest_amount">' . wc_price( $lowest_price_in_30_days ) . '</span></span><br />';
                        $price_html .= '<span class="actual_price">' . __( 'Actual price', 'lowest-price' ) . ': <span class="actual_amount">' . wc_price( $product->get_price() ) . '</span></span>';
                    } else {
                        $price_html = wc_format_sale_price( wc_get_price_to_display( $product, array( 'price' => $lowest_price_in_30_days ) ), wc_price( $min_price ) ) . $product->get_price_suffix();
                    }

                // NOT ON SALE, SAME PRICES FOR ALL VARIANTS, RETURN PRICE
                } else {
                    $price_html = wc_price( $min_price ) . $product->get_price_suffix();
                }

            }

        // OTHER PRODUCTS (SIMPLE, VARIANTS, GROUPED, ETC.)
        } else {

            if ( '' === $product->get_price() ) {
                $price_html = apply_filters( 'woocommerce_empty_price_html', '', $product );
            } elseif ( $product->is_on_sale() ) {

                $lowest_price_in_30_days = $this->get_lowest_price( $product->get_id(), $product->get_regular_price() );

                if( WPLP_DISPLAY_TYPE == 'text' ) {
                    $price_html = '<span class="lowest_price">' . __( 'Lowest price in last 30 days', 'lowest-price' ) . ': <span class="lowest_amount">' . wc_price( $lowest_price_in_30_days ) . '</span></span><br />';
                    $price_html .= '<span class="actual_price">' . __( 'Actual price', 'lowest-price' ) . ': <span class="actual_amount">' . wc_price( $product->get_price() ) . '</span></span>';
                } else {
                    $price_html = wc_format_sale_price( wc_get_price_to_display( $product, array( 'price' => $lowest_price_in_30_days ) ), wc_get_price_to_display( $product ) ) . $product->get_price_suffix();
                }

            } else {
                $price_html = wc_price( wc_get_price_to_display( $product ) ) . $product->get_price_suffix();
            }

        }

        return $price_html;
    }

    public function display_lowest_price_in_meta() { 

        global $product;

        if( !$product->is_on_sale( 'lowest_price' ) ) {
            return;
        }

        if( $product->get_type() == 'variable' ) {

            $prices = $product->get_variation_prices( true );

            $min_price     = current( $prices['price'] );
            $max_price     = end( $prices['price'] );
            $min_reg_price = current( $prices['regular_price'] );
            $max_reg_price = end( $prices['regular_price'] );

            if( $min_reg_price == $max_reg_price && $min_price == $max_price ) {

                $price = '<span class="lowest_amount">' . strip_tags( wc_price( $this->get_lowest_price( $product->get_id(), $min_reg_price ) ) ) . '</span>';
            } else {

                $prices_arr = array(
                    __( 'N/A', 'lowest-price' ),
                );

                foreach ( $prices[ 'price' ] as $k => $value) {
                    if( $prices[ 'regular_price' ][ $k ] > $value ) {
                        $prices_arr[ $k ] = strip_tags( wc_price( $this->get_lowest_price( $k, $prices[ 'regular_price' ][ $k ] ) ) );
                    } else {
                        $prices_arr[ $k ] = __( 'N/A', 'lowest-price' );
                    }
                }
                $price = '<span class="lowest_amount js-variable-price" data-variations=\'' . json_encode($prices_arr) . '\'>' . $prices_arr[ 0 ] . '</span>';

            }

        } else {

            $price = '<span class="lowest_amount">' . strip_tags( wc_price( $this->get_lowest_price( $product->get_id(), $product->get_regular_price() ) ) ) . '</span>';

        }

        echo '<span class="lowest_price">' . __( 'Lowest price in last 30 days', 'lowest-price' ) . ': ' . $price . '</span>';
    }

    public function wp_enqueue_style() {

        $plugin_version = Lowest_Price::$plugin_version;

        wp_enqueue_script( self::$asset_name, Lowest_Price::$plugin_url . '/asset/public/script.js', array( 'jquery' ), $plugin_version, false );
    }
}
