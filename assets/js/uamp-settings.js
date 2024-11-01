/**
 * Uamplified Settings Script
 * @since 1.0
 * @version 1.0
 */
jQuery(function($){

	var resultsel   = $( '#wpbody-content .wrap h1' );

	$(document).ready(function(){

		$( '#product-api-table' ).on( 'click', '.validate-product-api-key', function(e){

			e.preventDefault();

			var validbutton = $(this);
			var validlabel  = validbutton.text();
			var apikeyfield = $( '#uamp-api-key-new' );

			$.ajax({
				type       : "POST",
				data       : {
					action    : 'uamplified-verify-api-key',
					token     : Uamp.verify,
					api       : apikeyfield.val()
				},
				dataType   : "JSON",
				url        : Uamp.ajaxurl,
				beforeSend : function() {
					validbutton.attr( 'disabled', 'disabled' ).text( Uamp.validating );
					apikeyfield.attr( 'disabled', 'disabled' );
					$( '#setting-error' ).remove();
				},
				success    : function( response ) {

					if ( response.success ) {

						$( '#uamplified-product-new' ).before( response.data );
						apikeyfield.val( '' );

					}

					else {

						$( "html, body" ).animate({ scrollTop: 0 });
						resultsel.after( '<div id="setting-error" class="error settings-error notice"><p><strong>' + response.data + '</strong></p></div>' );

					}

				},
				error      : function(){
					$( "html, body" ).animate({ scrollTop: 0 });
					resultsel.after( '<div id="setting-error" class="error settings-error notice"><p><strong>' + Uamp.ajaxerror + '</strong></p></div>' );
				},
				complete   : function() {
					validbutton.removeAttr( 'disabled' ).text( validlabel );
					apikeyfield.removeAttr( 'disabled' );
				}
			});

		});

		$( '#product-api-table' ).on( 'click', '.remove-product-api-key', function(e){

			e.preventDefault();

			var removebutton = $(this);
			var removelabel  = removebutton.text();
			var productid    = removebutton.data( 'id' );
			var apikeyfield  = $( 'input#uamp-api-key' + apikeyid );

			$.ajax({
				type       : "POST",
				data       : {
					action    : 'uamplified-remove-api-key',
					token     : Uamp.remove,
					productid : productid
				},
				dataType   : "JSON",
				url        : Uamp.ajaxurl,
				beforeSend : function() {
					removebutton.attr( 'disabled', 'disabled' ).text( Uamp.removing );
					$( '#setting-error' ).remove();
				},
				success    : function( response ) {

					if ( response.success ) {

						$( 'tr#uamplified-product' + productid ).slideUp( 500, function(){ $(this).remove(); });

					}

					else {

						$( "html, body" ).animate({ scrollTop: 0 });
						resultsel.after( '<div id="setting-error" class="error settings-error notice"><p><strong>' + response.data + '</strong></p></div>' );
						removebutton.removeAttr( 'disabled' ).text( removelabel );

					}

				},
				error      : function(){
					$( "html, body" ).animate({ scrollTop: 0 });
					resultsel.after( '<div id="setting-error" class="error settings-error notice"><p><strong>' + Uamp.ajaxerror + '</strong></p></div>' );
					removebutton.removeAttr( 'disabled' ).text( removelabel );
				}
			});

		});

		$( '#product-api-table' ).on( 'click', '.sync-product', function(e){

			e.preventDefault();

			var syncbutton = $(this);
			var synclabel  = syncbutton.text();
			var productid  = syncbutton.data( 'id' );
			var syncfield  = $( 'input#uamp-api-key' + productid + ' p.description' );

			$.ajax({
				type       : "POST",
				data       : {
					action    : 'uamplified-sync-product',
					token     : Uamp.sync,
					productid : productid
				},
				dataType   : "JSON",
				url        : Uamp.ajaxurl,
				beforeSend : function() {
					syncbutton.attr( 'disabled', 'disabled' ).text( Uamp.syncing );
					$( '#setting-error' ).remove();
				},
				success    : function( response ) {

					if ( response.success ) {

						$( 'tr#uamplified-product' + productid ).slideUp( 500, function(){ $(this).replaceWith( response.data ); });

					}

					else {

						$( "html, body" ).animate({ scrollTop: 0 });
						resultsel.after( '<div id="setting-error" class="error settings-error notice is-dismissible"><p><strong>' + response.data + '</strong></p></div>' );
						syncbutton.removeAttr( 'disabled' ).text( synclabel );

					}

				},
				error      : function(){
					$( "html, body" ).animate({ scrollTop: 0 });
					resultsel.after( '<div id="setting-error" class="error settings-error notice is-dismissible"><p><strong>' + Uamp.ajaxerror + '</strong></p></div>' );
					syncbutton.removeAttr( 'disabled' ).text( synclabel );
				}
			});

		});

	});

});