/**
 * Uamplified Tools Script
 * @since 1.0
 * @version 1.0
 */
jQuery(function($){

	var resultsel   = $( '#wpbody-content .wrap h1' );

	var run_uamp_tool = function( tool, buttonel ) {

		var buttonlabel = buttonel.text();

		$.ajax({
			type       : "POST",
			data       : {
				action    : 'uamplified-run-admin-tool',
				token     : Uamp.token,
				toolid    : tool
			},
			dataType   : "JSON",
			url        : Uamp.ajaxurl,
			beforeSend : function() {
				console.log( 'Running tool: ' + tool );
				buttonel.attr( 'disabled', 'disabled' ).text( Uamp.running );
				$( '#setting-error' ).remove();
			},
			success    : function( response ) {

				$( "html, body" ).animate({ scrollTop: 0 });
				if ( response.success ) {

					resultsel.after( '<div id="setting-error" class="updated settings-updated notice"><p><strong>' + response.data.message + '</strong></p></div>' );

					if ( response.data.fields !== false ) {
						$.each( response.data.fields, function(elementid, elementhtml){
							$( '#' + elementid ).text( elementhtml );
						});
					}

				}

				else {

					resultsel.after( '<div id="setting-error" class="error settings-error notice"><p><strong>' + response.data + '</strong></p></div>' );

				}

				console.log( response );

			},
			error      : function(){
				$( "html, body" ).animate({ scrollTop: 0 });
				resultsel.after( '<div id="setting-error" class="error settings-error notice"><p><strong>' + Uamp.ajaxerror + '</strong></p></div>' );
			},
			complete   : function() {
				buttonel.removeAttr( 'disabled' ).text( buttonlabel );
			}
		});

	};

	$(document).ready(function(){

		$( 'a.trigger-uamp-tool' ).click(function(e){

			e.preventDefault();

			var toolbutton  = $(this);
			var toolid      = toolbutton.attr( 'id' );
			var confirmtext = Uamp.confirm[ toolid ];

			if ( confirmtext.length > 0 && confirm( confirmtext ) )
				run_uamp_tool( toolid, toolbutton );

			else if ( confirmtext.length == 0 )
				run_uamp_tool( toolid, toolbutton );

		});

	});

});