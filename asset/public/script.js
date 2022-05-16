var lowest_price = lowest_price || {};

lowest_price.variation_switcher = function( el ) {
    let obj = this;
    obj.el = el;
    obj.variation_prices = obj.el.data( 'variations' );

    obj.init = function() {
		jQuery( ".single_variation_wrap" ).on( "hide_variation", function ( event, variation ) {

			obj.el.text( obj.variation_prices[0] );

		});

		jQuery( ".single_variation_wrap" ).on( "show_variation", function ( event, variation ) {

			let selected_variation = variation.variation_id;

			obj.el.text( obj.variation_prices[selected_variation] );

		});
	};

	obj.init();

}

jQuery(document).ready(function ($) {

	if( $( ".js-variable-price" ).length ) {

		lowest_price.variation_switcher( $( ".js-variable-price" ) );

	}

});
