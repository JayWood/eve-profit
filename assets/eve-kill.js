window.eve_kill = ( function( window, document, $ ) {

	var app = {};

	app.cache = function() {
		app.$body = $( 'body' );
		app.$market_groups = $( 'ul.market-groups div' );
		app.$market_items = $( 'ul.items li.item' );
	};

	app.init = function() {
		app.cache();
		app.$market_groups.on( 'click', app.slide_toggle );
		app.$body.on( 'click', 'li.eve-item', app.toggle_selected );
	};

	app.toggle_selected = function( evt ) {
		var $that = $( this );

		$that.toggleClass( 'selected' );
	};

	app.slide_toggle = function ( evt ) {
		var $that = $( this );

		if ( $that.parent().has( 'ul' ) ) {
			evt.preventDefault();
		}

		var parent_data = $that.parent().data( 'has-types' );
		var already_called = $that.parent().find('ul.items');
		if ( 1 === parent_data && 1 > already_called.length ) {
			// Make the ajax call
			$.ajax( {
				url: window.ek_l10n.ajaxurl,
				method: 'POST',
				data: {
					action: 'items_for_group',
					group: $that.parent().data( 'groupid' ),
				}
			} ).done( function( rsp ) {
				if ( rsp.success ) {
					$that.parent().append( rsp.data );
					app.cache(); // Rebuild cache
				}
			} );
		} else {
			$that.next( 'ul' ).slideToggle( 0 );
			$that.parent().toggleClass( 'open' );
		}

	};

	$( document ).ready( app.init );
	return app;

} )( window, document, jQuery );