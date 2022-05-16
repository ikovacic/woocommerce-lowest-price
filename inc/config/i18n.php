<?php

namespace Lowest_Price\config;
class i18n {

    public $text_domain;

    public function __construct( $text_domain ) {

        if ( \Lowest_Price::$use_i18n === false ) {
            return;
        }

        $this->text_domain = $text_domain;

        add_action( 'init', array( $this, 'i18n' ) );
    }

    public function i18n() {
        load_plugin_textdomain( $this->text_domain, false, wp_normalize_path( \Lowest_Price::$plugin_path . '/languages' ) );
    }

}