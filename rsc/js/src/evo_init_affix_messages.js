/**
 * This file initialize Affix Messages
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * Depends on: jQuery
 */

jQuery( document ).ready( function()
{
	var msg_obj = jQuery( ".affixed_messages" );

	if( msg_obj.length == 0 )
	{	// No Messages, exit
		return;
	}

	var msg_obj_width = msg_obj.outerWidth();
	var msg_offset = evo_affix_msg_offset;
	var evo_bar = jQuery( '#evo_toolbar' );
	var site_header = jQuery( '#evo_site_header' );

	if( evo_bar.length )
	{	// Add evobar height to offset:
		msg_offset += evo_bar.outerHeight();
	}
	if( evo_affix_fixed_header && site_header.length )
	{	// Site header is fixed, add height to offset:
		msg_offset += site_header.outerHeight();
	}

	msg_obj.wrap( "<div class=\"msg_wrapper\"></div>" );
	var wrapper = msg_obj.parent();

	msg_obj.affix( {
			offset: {
				top: function() {
					return wrapper.offset().top - msg_offset - parseInt( msg_obj.css( "margin-top" ) );
				}
			}
		} );

	msg_obj.on( "affix.bs.affix", function()
		{
			wrapper.css( { "min-height": msg_obj.outerHeight( true ) } );

			msg_obj.css( { "width": msg_obj_width, "top": msg_offset, "z-index": 99999 } );

			jQuery( window ).on( "resize", function()
				{	// This will resize the Messages based on the wrapper width
					msg_obj.css( { "width": wrapper.css( "width" ) } );
				});
		} );

	msg_obj.on( "affixed-top.bs.affix", function()
		{
			wrapper.css( { "min-height": "" } );
			msg_obj.css( { "width": "", "top": "", "z-index": "" } );
		} );

	jQuery( "div.alert", msg_obj ).on( "closed.bs.alert", function()
		{
			wrapper.css({ "min-height": msg_obj.outerHeight( true ) });
		} );

	if( msg_obj.hasClass( "affix" ) )
	{	// Manually trigger the "affix.bs.affix" event:
		msg_obj.trigger( "affix.bs.affix" );
	}
} );
