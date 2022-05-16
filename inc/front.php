<?php
namespace Lowest_Price;
use Lowest_Price;
use WC_Product_Variation;

class Front {

    public static $asset_name = 'lowest-price';

    public function __construct() {


        add_filter( 'woocommerce_product_is_on_sale', array( $this, 'is_on_sale' ), 10, 2 );

        if( WPLP_DISPLAY_TYPE == 'regular' ) {

            add_filter( 'woocommerce_product_get_regular_price', array( $this, 'custom_price' ), 10, 2 );

            add_filter( 'woocommerce_product_variation_get_regular_price', array( $this, 'custom_price' ), 10, 2 );

        } elseif( WPLP_DISPLAY_TYPE == 'alt' ) {

            add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_style' ) );

            add_action( 'woocommerce_product_meta_end', array( $this, 'display_lowest_price_in_meta' ) );

        }

    }

    public function get_lowest_price( $object ) {

        global $wpdb;

        if( $object->get_type() == 'variable' && get_class( $object ) != 'WC_Product_Variation' ) {
            $prices = $object->get_variation_prices();
            $price = $prices['regular_price'];
        } else {
            $price = $object->get_regular_price( 'lowest_price' );
        }

        $ts_30_days_ago = time() - 30 * 24 * 60 * 60;

        if( $result = $wpdb->get_row( $wpdb->prepare( "SELECT price FROM {$wpdb->prefix}price_history WHERE product_id = %d AND timestamp_end > %d ORDER BY price ASC", $object->get_id(), $ts_30_days_ago ), ARRAY_A ) ) {

            if( $result['price'] < $price ) {
                $price = $result['price'];
            }
        }

        return $price;
    }

    public function display_lowest_price_in_meta() { 

        global $product;

        if( !$product->is_on_sale( 'lowest_price' ) ) {
            return;
        }

        if( $product->get_type() == 'variable' ) {

            $variations = $product->get_children();

            $prices_arr = array();

            foreach ( $variations as $variation ) {

                $single_variation = new WC_Product_Variation( $variation );

                $prices_arr[ 0 ] = __( 'N/A', 'lowest-price' );
                if( $single_variation->is_on_sale() ) {
                    $prices_arr[ $variation ] = strip_tags( wc_price( $this->get_lowest_price( $single_variation ) ) );
                } else {
                    $prices_arr[ $variation ] = __( 'N/A', 'lowest-price' );
                }

            }

            $price = "<span class='js-variable-price' data-variations='" . json_encode($prices_arr) . "'>" . $prices_arr[ 0 ] . "</span>";

        } else {

            $price = strip_tags( wc_price( $this->get_lowest_price( $product ) ) );

        }

        echo '<span class="lowest_price">' . sprintf( __( 'Lowest price in last 30 days: %s', 'lowest-price' ), $price ) . '</span>';
    }

    public function custom_price( $price, $object ) {

        if( is_admin() || !$object->is_on_sale( 'lowest_price' ) ) {
            return $price;
        }

        return $this->get_lowest_price( $object );
    }

    public function is_on_sale( $on_sale, $product ) {

        if( is_admin() ) {
            return $on_sale;
        }

        if( $product->get_type() == 'variable' ) {

            $prices = $product->get_variation_prices();
            $regular_price = $prices['regular_price'];
            $sale_price = $prices['sale_price'];

        } else {

            $regular_price = $product->get_regular_price();
            $sale_price = $product->get_price();

        }

        // WooCommerce by default doesn't show that item is on sale if “regular” price was lower than “sale” price
        if( $regular_price != $sale_price ) {
            $on_sale = true;
        }

        if( $product->get_date_on_sale_from() && $product->get_date_on_sale_from()->getTimestamp() > time() ) {
            $on_sale = false;
        }

        if( $product->get_date_on_sale_to() && $product->get_date_on_sale_to()->getTimestamp() < time() ) {
            $on_sale = false;
        }

        return $on_sale;

    }

    public function wp_enqueue_style() {

        $plugin_version = Lowest_Price::$plugin_version;

        wp_enqueue_script( self::$asset_name, Lowest_Price::$plugin_url . '/asset/public/script.js', array( 'jquery' ), $plugin_version, false );
    }
}
