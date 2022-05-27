<?php
/**
 * Plugin Name: WooCommerce Lowest Price
 * Description: Display lowest price in last 30 days
 * Plugin URI:  #
 * Version:     1.0.2
 * Author:      Igor Kovacic
 * Author URI:  https://www.applause.hr
 * Text Domain: lowest-price
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// @todo: move to settings
if ( ! defined( 'WPLP_DISPLAY_TYPE' ) ) {
    define( 'WPLP_DISPLAY_TYPE', 'regular' );
}

if ( ! defined( 'WPLP_VARIANT_LOOP' ) ) {
    define( 'WPLP_VARIANT_LOOP', 'range' );
}

class Lowest_Price {

    private $min_php = '5.6.0';
    public static $use_i18n = true;
    public static $providers = array( 'Front' );
    public static $plugin_url;
    public static $plugin_path;
    public static $plugin_version;
    protected static $_instance = null;

    public static function instance() {
        null === self::$_instance and self::$_instance = new self;
        return self::$_instance;
    }

    public function __construct() {

        if ( version_compare( PHP_VERSION, $this->min_php, '<=' ) ) {
            add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
            return;
        }

        /**
         * Check if WooCommerce is active
         */
        if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
            return;
        }

        $this->define_constants();
        $this->includes();
        $this->init_hooks();
        $this->init_actions();

        do_action( 'lowest_price_loaded' );
    }

    public function define_constants() {

        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        $plugin_data = get_plugin_data( __FILE__ );

        self::$plugin_version = $plugin_data['Version'];
        self::$plugin_url = plugins_url( '', __FILE__ );
        self::$plugin_path = plugin_dir_path( __FILE__ );
    }

    public function includes() {

        include_once dirname( __FILE__ ) . '/inc/config/i18n.php';
        include_once dirname( __FILE__ ) . '/inc/config/install.php';
        include_once dirname( __FILE__ ) . '/inc/config/uninstall.php';
        include_once dirname( __FILE__ ) . '/inc/front.php';

        foreach ( self::$providers as $class ) {
            $class_object = '\Lowest_Price\\' . $class;
            new $class_object;
        }

    }

    public function init_hooks() {

        register_activation_hook( __FILE__, array( '\Lowest_Price\config\install', 'run_install' ) );

        register_deactivation_hook( __FILE__, array( '\Lowest_Price\config\uninstall', 'run_uninstall' ) );

        if ( self::$use_i18n === true ) {
            new \Lowest_Price\config\i18n( 'lowest-price' );
        }

    }

    public function php_version_notice() {

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $error = sprintf( __( 'Your installed PHP Version is: %s. ', 'lowest-price' ), PHP_VERSION );
        $error .= sprintf( __( '<strong>WooCommerce Lowest Price</strong> requires PHP <strong>%s</strong> or greater.', 'lowest-price' ), $this->min_php );
        ?>
        <div class="error">
            <p><?php printf( $error ); ?></p>
        </div>
        <?php
    }

    public function woocommerce_missing_notice() {
        if( is_admin() ){
            echo '<div class="error is-dismissible"><p>' . __( '<strong>WooCommerce Lowest Price</strong> requires WooCommerce plugin to be installed and active.', 'lowest-price' ) . '</p></div>';
        }  
    }

    public static function log( $log ) {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }

    public function init_actions() {

        add_action( 'woocommerce_update_product', array( $this, 'product_update' ) );
        add_action( 'woocommerce_update_product_variation', array( $this, 'variation_update' ) );

        add_action( 'woocommerce_before_product_object_save', array( $this, 'object_before_update' ) );
        add_action( 'woocommerce_before_variation_object_save', array( $this, 'object_before_update' ) );

        add_action( 'add_meta_boxes_product', array( $this, 'show_price_history' ) );

    }

    public function show_price_history() {

        add_meta_box( 'lowest_price_history', __( 'Price history (30 days)', 'lowest-price' ), array( $this, 'price_history' ), 'product', 'normal', 'low' );

    }

    public function price_history( $product ) {

        global $wpdb;

        $ts_30_days_ago = time() - 30 * 24 * 60 * 60;

        $product_ids = array( $product->ID );

        $_product = wc_get_product( $product->ID );

        if( $_product->get_type() == 'variable' && $_product->get_children()) {

            $product_ids = array_merge( $product_ids, $_product->get_children() );
        }

        ?>

        <style>
            .price_history { text-align: right; min-width: 400px; border: 1px solid #ddd; border-collapse: collapse; }
            .price_history th, .price_history td { padding: 4px 10px; margin: 0; }
        </style>


        <?php foreach( $product_ids as $product_id ) {

            echo '<h3>' . __( 'Product', 'lowest-price' ) . ' #' . $product_id .'</h3>';

            if( $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}price_history WHERE product_id = %d AND ( timestamp_end > %d OR timestamp_end = 0 ) ORDER BY timestamp DESC", $product_id, $ts_30_days_ago ), ARRAY_A ) ) {

                ?>

                <table class="price_history">
                    <thead>
                        <tr>
                            <th><?php _e( 'Price', 'lowest-price' ); ?></th>
                            <th><?php _e( 'Valid from', 'lowest-price' ); ?></th>
                            <th><?php _e( 'Valid to', 'lowest-price' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach( $results as $k => $result ) : ?>
                        <tr<?php if( $k%2 == 0 ) : ?> class="alternate"<?php endif; ?>>
                            <td><?php echo wc_price( $result['price'] ); ?></td>
                            <td><?php echo $result['timestamp'] ? wp_date( 'd.m.Y. H:i:s', $result['timestamp'] ) : '-'; ?></td>
                            <td><?php echo $result['timestamp_end'] ? wp_date( 'd.m.Y. H:i:s', $result['timestamp_end'] ) : __( 'Active now', 'lowest-price' ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php

            } else {

                echo '<p>' . __( 'No price history yet', 'lowest-price' ) . '</p>';
            }

        }

    }

    public function update_price( $object_id, $new_price, $regular_price ) {

        global $wpdb;

        $last_price = array(
            'id' => 0,
            'price' => 0,
        );

        // GET LAST VALID PRICE

        if( $result = $wpdb->get_row( $wpdb->prepare( "SELECT price_history_id AS id, price FROM {$wpdb->prefix}price_history WHERE product_id = %d AND timestamp_end = 0", $object_id ), ARRAY_A ) ) {

            $last_price = $result;

        }

        // COMPARE IF PRICE IS CHANGED

        if( $new_price && ( $new_price != $last_price['price'] ) ) {

            if( $last_price['id'] ) {

                // UPDATE "VALID TO" TIMESTAMP

                $wpdb->query( 
                    $wpdb->prepare( 
                        "UPDATE {$wpdb->prefix}price_history SET timestamp_end = %d WHERE price_history_id = %d",
                        time(),
                        $last_price['id']
                    )
                );

            }

            // SAVE LOWEST PRICE IN LAST 30 DAYS TO POSTMETA

            update_post_meta( $object_id, '_lowest_price_30_days', self::get_lowest_price( $object_id, $regular_price ) );

            // INSERT NEW PRICE

            $wpdb->insert("{$wpdb->prefix}price_history", array(
                "product_id" => $object_id,
                "price" => $new_price,
                "timestamp" => time(),
            ));

        }

    }

    public function variation_update( $variation_id ) {

        $single_variation = new WC_Product_Variation( $variation_id );

        $new_price = $single_variation->get_price();

        $regular_price = $single_variation->get_regular_price();

        $this->update_price( $variation_id, $new_price, $regular_price );

    }

    public function product_update( $product_id ) {

        $product = wc_get_product( $product_id );

        if( $product->get_type() == 'variable' ) {
            $regular_price = $product->get_variation_regular_price( 'min' );
        } else {
            $regular_price = $product->get_regular_price();
        }

        $new_price = $product->get_price();

        $this->update_price( $product_id, $new_price, $regular_price );

    }

    public function object_before_update( $object ) {

        global $wpdb;

        // IF PRICE HISTORY DON'T HAVE ANY PRICE, SAVE PREVIOUS

        if( !$wpdb->get_row( $wpdb->prepare( "SELECT price_history_id FROM {$wpdb->prefix}price_history WHERE product_id = %d LIMIT 0, 1", $object->get_id() ), ARRAY_A ) && $object->get_price() ) {

            // INSERT REGULAR PRICE

            if( $object->get_type() == 'variable' ) {
                $regular_price = $object->get_variation_regular_price( 'min' );
            } else {
                $regular_price = $object->get_regular_price();
            }

            update_post_meta( $object->get_id(), '_lowest_price_30_days', self::get_lowest_price( $object->get_id(), $regular_price ) );

            // INSERT ACTUAL PRICE

            $wpdb->insert("{$wpdb->prefix}price_history", array(
                "product_id" => $object->get_id(),
                "price" => $object->get_price(),
                "timestamp" => 0,
            ));

        }

    }

    public static function get_lowest_price( $object_id, $price ) {

        global $wpdb;

        $ts_30_days_ago = time() - 30 * 24 * 60 * 60;

        // FETCH MIN PRICE IN LAST 30 DAYS FROM PRICE HISTORY DB TABLE

        if( $result = $wpdb->get_row( $wpdb->prepare( "SELECT price FROM {$wpdb->prefix}price_history WHERE product_id = %d AND timestamp_end > %d ORDER BY price ASC LIMIT 0, 1", $object_id, $ts_30_days_ago ), ARRAY_A ) ) {

            if( $result['price'] < $price ) {
                $price = $result['price'];
            }
        }

        return $price;
    }
}

function lowest_price() {
    return Lowest_Price::instance();
}

// BACKWARDS COMPATIBILITY
$GLOBALS['lowest-price'] = lowest_price();
