	return b2edit_reload( '#item_checkchanges', newaction, null, { action: submit_action }, false );
}


/**
 * Request WHOIS information
 *
 * Opens a modal window displaying results of WHOIS query
 */
function get_whois_info( ip_address )
{
	var window_height = jQuery( window ).height();
	var margin_size_height = 20;
	var modal_height = window_height - ( margin_size_height * 2 );

	openModalWindow(
			'<span id="spinner" class="loader_img loader_user_report absolute_center" title="' + evo_js_lang_whois_title + '"></span>',
			'90%', modal_height + 'px', true, 'WHOIS - ' + ip_address, true, true );

	jQuery.ajax(
	{
		type: 'GET',
		url: htsrv_url + 'async.php',
		data: {
			action: 'get_whois_info',
			query: ip_address,
			window_height: modal_height
		},
		success: function( result )
		{
			if( ajax_response_is_correct( result ) )
			{
				result = ajax_debug_clear( result );
				openModalWindow( result, '90%', modal_height + 'px', true, 'WHOIS - ' + ip_address, true );
			}
		}
	} );

	return false;
}


/**
 * Open and highlight selected template
 */
function b2template_list_highlight( obj )
{
	var link = jQuery( obj );
	var select = link.prevAll( 'select' );
	var selected_template = select.find( ':selected' ).val();
	var link_url = link.attr('href');

	if( selected_template )
	{
		link_url += '&highlight=' + selected_template;
	}

	var new_target = link.attr('target');
	
	if ( new_target === undefined ) 
	{
		if( window.self !== window.top )
		{
			window.top.location = link_url;
		}
		else
		{
			window.location = link_url;
		}
	}
	else
	{
		window.open( link_url, new_target );
	}

	return false;
}


/**
 * Copy text of element to clipboard
 *
 * @param string Element ID
 * @param string Optional text, use this to copy instead of content of the Element
 */
function evo_copy_to_clipboard( id, custom_text )
{
	if( typeof( custom_text ) == 'undefined' )
	{	// Copy text from Element:
		var text_obj = document.getElementById( id );
	}
	else
	{	// Copy a provided Text:
		var text_obj = document.createElement( 'span' );
		text_obj.innerHTML = custom_text;
		document.body.appendChild( text_obj );
	}

	// Create range to select element by ID:
	var range = document.createRange();
	range.selectNode( text_obj );
	// Clear current selection:
	window.getSelection().removeAllRanges();
	// Select text of the element temporary:
	window.getSelection().addRange( range );
	// Copy to clipboard:
	document.execCommand( 'copy' );
	// Deselect:
	window.getSelection().removeAllRanges();
	// Highlight copied element:
	evoFadeBg( '#' + id, new Array( '#ffbf00' ), { speed: 100 } );

	if( typeof( custom_text ) != 'undefined' )
	{	// Remove temp object what was used only for copying above:
		document.body.removeChild( text_obj );
	}

	return false;
}
