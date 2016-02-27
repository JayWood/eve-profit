window.eve_kill = ( function( window, document, $ ) {

	var app = {};

	app.cache = function() {
		app.$body = $( 'body' );
		app.$market_groups = $( 'ul.market-groups span' );
	};

	app.init = function() {
		app.cache();
		app.$market_groups.on( 'click', app.slide_toggle );
	};

	app.slide_toggle = function ( evt ) {
		var $that = $( this );

		if ( $that.parent().has( 'ul' ) ) {
			evt.preventDefault();
		}

		$that.next( 'ul' ).slideToggle( 0 );
	};

	$( document ).ready( app.init );
	return app;

} )( window, document, jQuery );