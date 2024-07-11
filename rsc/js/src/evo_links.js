/**
 * This file implements links specific Javascript functions.
 * (Used only in back-office)
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */


/**
 * Initialize attachments fieldset to set proper height and handler to resize it
 *
 * @param string Fieldset prefix, e-g 'meta_' when two forms are used on the same page
 */
function evo_link_initialize_fieldset( fieldset_prefix )
{
	if( jQuery( '#' + fieldset_prefix + 'attachments_fieldset_table' ).length > 0 )
	{	// Only if the attachments block exists on the loading page:
		var height = jQuery( '#' + fieldset_prefix + 'attachments_fieldset_table' ).height();
		height = ( height > 320 ) ? 320 : ( height < 97 ? 97 : height );
		jQuery( '#' + fieldset_prefix + 'attachments_fieldset_wrapper' ).height( height );

		jQuery( '#' + fieldset_prefix + 'attachments_fieldset_wrapper' ).resizable(
		{	// Make the attachments fieldset wrapper resizable:
			minHeight: 80,
			handles: 's',
			zIndex: 0,
			resize: function( e, ui )
			{	// Limit max height by table of attachments:
				jQuery( '#' + fieldset_prefix + 'attachments_fieldset_wrapper' ).resizable( 'option', 'maxHeight', jQuery( '#' + fieldset_prefix + 'attachments_fieldset_table' ).height() );
				evo_link_update_overlay( fieldset_prefix );
			}
		} );
		jQuery( document ).on( 'click', '#' + fieldset_prefix + 'attachments_fieldset_wrapper .ui-resizable-handle', function()
		{	// Increase attachments fieldset height on click to resizable handler:
			var max_height = jQuery( '#' + fieldset_prefix + 'attachments_fieldset_table' ).height();
			var height = jQuery( '#' + fieldset_prefix + 'attachments_fieldset_wrapper' ).height() + 80;
			jQuery( '#' + fieldset_prefix + 'attachments_fieldset_wrapper' ).css( 'height', height > max_height ? max_height : height );
			evo_link_update_overlay( fieldset_prefix );
		} );
	}
}


/**
 * Update position and size of overlay which restrict edit of attachments
 *
 * @param string Fieldset prefix, e-g 'meta_' when two forms are used on the same page
 */
function evo_link_update_overlay( fieldset_prefix )
{
	if( jQuery( '#' + fieldset_prefix + 'attachments_fieldset_overlay' ).length )
	{	// Update height of restriction overlay if it exists:
		jQuery( '#' + fieldset_prefix + 'attachments_fieldset_overlay' ).css( 'height', jQuery( '#' + fieldset_prefix + 'attachments_fieldset_wrapper' ).closest( '.panel' ).height() );
	}
}


/**
 * Fix height of attachments wrapper
 * Used after content changing by AJAX loading
 *
 * @param string Fieldset prefix, e-g 'meta_' when two forms are used on the same page
 */
function evo_link_fix_wrapper_height( fieldset_prefix )
{
	var prefix = typeof( fieldset_prefix ) == 'undefined' ? '' : fieldset_prefix;
	var table_height = jQuery( '#' + prefix + 'attachments_fieldset_table' ).height();
	var wrapper_height = jQuery( '#' + prefix + 'attachments_fieldset_wrapper' ).height();
	if( wrapper_height != table_height )
	{
		jQuery( '#' + prefix + 'attachments_fieldset_wrapper' ).height( jQuery( '#' + prefix + 'attachments_fieldset_table' ).height() );
	}
}


/**
 * Change link position
 *
 * @param object Select element
 * @param string URL
 * @param string Crumb
 */
function evo_link_change_position( selectInput, url, crumb )
{
	var oThis = selectInput;
	var new_position = selectInput.value;
	var link_ID = selectInput.id.substr(17);

	jQuery.get( url + 'anon_async.php?action=set_object_link_position&link_ID=' + link_ID + '&link_position=' + new_position + '&crumb_link=' + crumb, {
	}, function(r, status) {
		r = ajax_debug_clear( r );
		if( r == "OK" ) {
			evoFadeSuccess( jQuery(oThis).closest('tr') );
			jQuery(oThis).closest('td').removeClass('error');
			if( new_position == 'cover' || new_position == 'background' )
			{ // Position "Cover" can be used only by one link
				jQuery( 'select[name=link_position][id!=' + selectInput.id + '] option[value=cover]:selected' ).each( function()
				{ // Replace previous position with "Inline"
					jQuery( this ).parent().val( 'aftermore' );
					evoFadeSuccess( jQuery( this ).closest('tr') );
				} );
			}
		} else {
			jQuery(oThis).val(r);
			evoFadeFailure( jQuery(oThis).closest('tr') );
			jQuery(oThis.form).closest('td').addClass('error');
		}
	} );
	return false;
}


/**
 * Insert inline tag into the post ( example: [image:123:caption text] | [file:123:caption text] )
 *
 * @param string Type: 'image', 'file', 'video'
 * @param integer File ID
 * @param string Caption text
 * @param boolean Replace a selected text
 * @param string Caption, when this param is filled then tag is inserted in format like [image:123]Caption[/image]
 * @param object Current canvas where inline tag should be inserted
 */
function evo_link_insert_inline( type, link_ID, option, replace, caption, current_b2evoCanvas )
{
	if( replace == undefined )
	{
		replace = 0;
	}

	if( typeof( current_b2evoCanvas ) != 'undefined' )
	{	// Canvas exists
		var insert_tag = '[' + type + ':' + link_ID;

		if( option.length )
		{
			insert_tag += ':' + option;
		}

		insert_tag += ']';

		if( typeof( caption ) != 'undefined' && caption !== false )
		{	// Tag with caption:
			insert_tag += caption + '[/' + type + ']';
		}

		var $position_selector = jQuery( '#display_position_' + link_ID );
		if( $position_selector.length != 0 )
		{
			if( $position_selector.val() != 'inline' )
			{	// Not yet inline, change the position to 'Inline'
				deferInlineReminder = true;
				// We have to change the link position in the DB before we insert the image tag
				// otherwise the inline tag will not render because it is not yet in the 'inline' position
				evo_rest_api_request( 'links/' + link_ID + '/position/inline',
					function( data )
					{
						$position_selector.val( 'inline' );
						evoFadeSuccess( $position_selector.closest( 'tr' ) );
						$position_selector.closest( 'td' ).removeClass( 'error' );

						// Insert an image tag
						textarea_wrap_selection( current_b2evoCanvas, insert_tag, '', replace, window.document );
					}, 'POST' );
				deferInlineReminder = false;
			}
			else
			{	// Already an inline, insert image tag
				textarea_wrap_selection( current_b2evoCanvas, insert_tag, '', replace, window.document );
			}
		}
		else
		{
			textarea_wrap_selection( current_b2evoCanvas, insert_tag, '', replace, window.document );
		}
	}
}


/**
 * Unlink/Delete an attachment from Item or Comment
 *
 * @param object Event object
 * @param string Type: 'item', 'comment'
 * @param integer Link ID
 * @param string Action: 'unlink', 'delete'
 */
function evo_link_delete( event_object, type, link_ID, action )
{
	// Call REST API request to unlink/delete the attachment:
	evo_rest_api_request( 'links/' + link_ID,
	{
		'action': action
	},
	function( data )
	{
		if( type == 'item' || type == 'comment' || type == 'emailcampaign' || type == 'message' )
		{	// Replace the inline image placeholders when file is unlinked from Item:
			var b2evoCanvas = window.b2evoCanvas;
			if( b2evoCanvas != null )
			{ // Canvas exists
				var regexp = new RegExp( '\\\[(image|file|inline|video|audio|thumbnail):' + link_ID + ':?[^\\\]]*\\\]', 'ig' );
				textarea_str_replace( b2evoCanvas, regexp, '', window.document );
			}
		}

		// Remove attachment row from table:
		jQuery( event_object ).closest( 'tr' ).remove();

		// Update the attachment block height after deleting row:
		evo_link_fix_wrapper_height();
	},
	'DELETE' );

	return false;
}


/**
 * Change an order of the Item/Comment attachment
 *
 * @param object Event object
 * @param integer Link ID
 * @param string Action: 'move_up', 'move_down'
 */
function evo_link_change_order( event_object, link_ID, action )
{
	// Call REST API request to change order of the attachment:
	evo_rest_api_request( 'links/' + link_ID + '/' + action,
	function( data )
	{
		// Change an order in the attachments table
		var row = jQuery( event_object ).closest( 'tr' );
		var currentEl = row.find( 'span[data-order]' );
		if( action == 'move_up' )
		{	// Move up:
			var currentOrder = currentEl.attr( 'data-order' );
			var previousRow = jQuery( row.prev() );
			var previousEl = previousRow.find( 'span[data-order]' );
			var previousOrder = previousEl.attr( 'data-order' );

			row.prev().before( row );
			currentEl.attr( 'data-order', previousOrder );
			previousEl.attr( 'data-order', currentOrder );
		}
		else
		{	// Move down:
			var currentOrder = currentEl.attr( 'data-order' );
			var nextRow = jQuery( row.next() );
			var nextEl = nextRow.find( 'span[data-order]' );
			var nextOrder = nextEl.attr( 'data-order' );

			row.next().after( row );
			currentEl.attr( 'data-order', nextOrder );
			nextEl.attr( 'data-order', currentOrder );
		}
		evoFadeSuccess( row );
	},
	'POST' );

	return false;
}


/**
 * Attach a file to Item/Comment
 *
 * @param string Type: 'item', 'comment'
 * @param integer ID of Item or Comment
 * @param string Root (example: 'collection_1')
 * @param string Path to the file relative to root
 * @param string Prefix, e.g. "meta_"
 */
function evo_link_attach( type, object_ID, root, path, prefix )
{
	// Call REST API request to attach a file to Item/Comment:
	evo_rest_api_request( 'links',
	{
		'action':    'attach',
		'type':      type,
		'object_ID': object_ID,
		'root':      root,
		'path':      path
	},
	function( data )
	{
		if( typeof( prefix ) == 'undefined' )
		{
			prefix = '';
		}
		var table_obj = jQuery( '#' + prefix + 'attachments_fieldset_table .results table', window.parent.document );
		var results_obj = jQuery( data.list_content );
		table_obj.replaceWith( jQuery( 'table', results_obj ) ).promise().done( function( e ) {
			// Delay for a few milleseconds after content is loaded to get the correct height
			setTimeout( function() {
				window.parent.evo_link_fix_wrapper_height();
			}, 10 );
		});
	} );

	return false;
}


/**
 * Set temporary content during ajax is loading
 *
 * @return object Overlay indicator of ajax loading
 */
function evo_link_ajax_loading_overlay( fieldset_prefix )
{
	var prefix = typeof( fieldset_prefix ) == 'undefined' ? '' : fieldset_prefix;
	var table = jQuery( '#' + prefix + 'attachments_fieldset_table' );
	var ajax_loading = false;

	if( table.find( '.results_ajax_loading' ).length == 0 )
	{	// Allow to add new overlay only when previous request is finished:
		ajax_loading = jQuery( '<div class="results_ajax_loading"><div>&nbsp;</div></div>' );
		table.css( 'position', 'relative' );
		ajax_loading.css( {
				'width':  table.width(),
				'height': table.height(),
			} );
		table.append( ajax_loading );
	}

	return ajax_loading;
}


/**
 * Refresh/Sort a list of Item/Comment attachments
 *
 * @param string Type: 'item', 'comment'
 * @param integer ID of Item or Comment
 * @param string Action: 'refresh', 'sort'
 */
function evo_link_refresh_list( type, object_ID, action, fieldset_prefix )
{
	var prefix = typeof( fieldset_prefix ) == 'undefined' ? '' : fieldset_prefix;
	var ajax_loading = evo_link_ajax_loading_overlay( prefix );
	if( typeof( action ) == 'undefined' )
	{
		action = 'refresh';
	}

	if( ajax_loading )
	{	// If new request is allowed in current time:

		// Call REST API request to attach a file to Item/Comment:
		evo_rest_api_request( 'links',
		{
			'action':    action,
			'type':      type.toLowerCase(),
			'object_ID': object_ID,
			'prefix':    prefix,
		},
		function( data )
		{
			// Refresh a content of the links list:
			jQuery( '#' + prefix + 'attachments_fieldset_table' ).html( data.html );

			// Initialize init_uploader( 'fieldset_' + prefix ) to display uploader button
			if( window.evo_init_dragdrop_button_config && window.evo_init_dragdrop_button_config['fieldset_' + prefix] )
			{
				init_uploader( window.evo_init_dragdrop_button_config['fieldset_' + prefix] );
			}

			// Update the attachment block height after refreshing:
			evo_link_fix_wrapper_height( prefix );
			

			// Remove temporary content of ajax loading indicator:
			ajax_loading.remove();	
		} );
	}

	return false;
}

/**
 * Sort list of Item/Comment attachments based on link_order
 *
 * @param string Fieldset prefix, e-g 'meta_' when two forms are used on the same page
 */
function evo_link_sort_list( fieldset_prefix )
{
	var prefix = typeof( fieldset_prefix ) == 'undefined' ? '' : fieldset_prefix;
	var rows = jQuery( '#' + prefix + 'attachments_fieldset_table tbody.filelist_tbody tr' );
	rows.sort( function( a, b )	{
		var A = parseInt( jQuery( 'span[data-order]', a ).attr( 'data-order' ) );
		var B = parseInt( jQuery( 'span[data-order]', b ).attr( 'data-order' ) );

		if( ! A ) A = rows.length;
		if( ! B ) B = rows.length;

		if( A < B )
		{
			return -1;
		}

		if( B < A )
		{
			return 1;
		}

		return 0;
	} );

	var previousRow;
	$.each( rows, function( index, row ) {
		if( index === 0 )
		{
			jQuery( row ).prependTo( '#' + prefix + 'attachments_fieldset_table tbody.filelist_tbody' );
			previousRow = row;
		}
		else
		{
			jQuery( row ).insertAfter( previousRow );
			previousRow = row;
		}
	} );
}
