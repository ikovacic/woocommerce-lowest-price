<?php
/**
 * Plugin Name: WooCommerce Lowest Price
 * Description: Display lowest price in last 30 days
 * Plugin URI:  #
 * Version:     1.0.0
 * Author:      Igor Kovacic
 * Author URI:  https://www.applause.hr
 * Text Domain: lowest-price
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WPLP_DISPLAY_TYPE' ) ) {
    define( 'WPLP_DISPLAY_TYPE', 'regular' );
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

    }

    public function variation_update( $variation_id ) {

        global $wpdb;

        $single_variation = new WC_Product_Variation( $variation_id );

        $new_price = $single_variation->get_price();

        $last_price = array(
            'id' => 0,
            'price' => 0,
        );

        if( $result = $wpdb->get_row( $wpdb->prepare( "SELECT price_history_id AS id, price FROM {$wpdb->prefix}price_history WHERE product_id = %d AND timestamp_end = 0", $variation_id ), ARRAY_A ) ) {

            $last_price = $result;

        }

        if( $new_price && ( $new_price != $last_price['price'] ) ) {

            if( $last_price['id'] ) {

                $wpdb->query( 
                    $wpdb->prepare( 
                        "UPDATE {$wpdb->prefix}price_history SET timestamp_end = %d WHERE price_history_id = %d",
                        time(),
                        $last_price['id']
                    )
                );

            }

            $wpdb->insert("{$wpdb->prefix}price_history", array(
                "product_id" => $variation_id,
                "price" => $new_price,
                "timestamp" => time(),
            ));

        }

    }

    public function object_before_update( $object ) {

        if( $object->get_type() == 'variable' ) {
            return;
        }

        global $wpdb;

        if( !$wpdb->get_row( $wpdb->prepare( "SELECT price_history_id FROM {$wpdb->prefix}price_history WHERE product_id = %d LIMIT 0, 1", $object->get_id() ), ARRAY_A ) ) {

            $wpdb->insert("{$wpdb->prefix}price_history", array(
                "product_id" => $object->get_id(),
                "price" => $object->get_price(),
                "timestamp" => 0,
            ));

        }

    }

    public function product_update( $product_id ) {

        global $wpdb;

        $product = wc_get_product( $product_id );

        if( $product->get_type() == 'variable' ) {
            return;
        }

        $new_price = $product->get_price();

        $last_price = array(
            'id' => 0,
            'price' => 0,
        );

        if( $result = $wpdb->get_row( $wpdb->prepare( "SELECT price_history_id AS id, price FROM {$wpdb->prefix}price_history WHERE product_id = %d AND timestamp_end = 0", $product_id ), ARRAY_A ) ) {

            $last_price = $result;

        }

        if( $new_price && ( $new_price != $last_price['price'] ) ) {

            if( $last_price['id'] ) {

                $wpdb->query( 
                    $wpdb->prepare( 
                        "UPDATE {$wpdb->prefix}price_history SET timestamp_end = %d WHERE price_history_id = %d",
                        time(),
                        $last_price['id']
                    )
                );

            }

            $wpdb->insert("{$wpdb->prefix}price_history", array(
                "product_id" => $product_id,
                "price" => $new_price,
                "timestamp" => time(),
            ));

        }
    }
}

function lowest_price() {
    return Lowest_Price::instance();
}

// Backwards compatibility
$GLOBALS['lowest-price'] = lowest_price();
