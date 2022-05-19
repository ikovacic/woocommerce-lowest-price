<?php

namespace Lowest_Price\config;

class install {

    public static function run_install() {

        global $wpdb;

        if ( ! function_exists( 'dbDelta' ) ) {
            require( ABSPATH . 'wp-admin/includes/upgrade.php' );
        }

        $collate = $wpdb->get_charset_collate();

        $create_tbl = ( "
            CREATE TABLE `{$wpdb->prefix}price_history` (
                `price_history_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `product_id` bigint(20) UNSIGNED NOT NULL,
                `price` double NOT NULL DEFAULT 0,
                `timestamp` bigint(20) UNSIGNED DEFAULT NULL,
                `timestamp_end` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
                PRIMARY KEY (`price_history_id`),
                KEY `product_id` (`product_id`)
            ) {$collate}" );

        dbDelta( $create_tbl );

        // @todo: save initial prices for products on sale?

    }

}
