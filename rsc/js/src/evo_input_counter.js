/* 
 * This file contains general functions to display a characters counter on the input/textarea fields
 *
 * To initialize the counter helper use html attributes for input/textarea fields:
 *  - 'data-maxlength="255"' for down counter,
 *  - 'data-recommended-length="60;65"' for up counter with color label; in this example <=60 - green/success label, >60 and <=65 - orange/warning label, >65 - red/danger label.
 */

jQuery( document ).ready( function()
{
	var evo_input_counter_selector = 'input[type=text][data-maxlength], textarea[data-maxlength], input[type=text][data-recommended-length], textarea[data-recommended-length]';

	jQuery( evo_input_counter_selector ).each( function()
	{	// Initialize counter element for each input where it is required:
		jQuery( this ).after( '<span class="evo_input_counter">' + evo_input_counter_get_count( jQuery( this ) ) + '</span>' );
		// Set relative position of the parent element for proper position of the counter elements:
		jQuery( this ).parent().css( 'position', 'relative' );
		// Store original right padding because it may be changed depending on counter width:
		jQuery( this ).data( 'padding-right', parseInt( jQuery( this ).css( 'padding-right' ) ) );
		if( evo_input_counter_get_type( jQuery( this ) ) == 'recommended' )
		{	// Parse recommended data only on initialization time:
			var recommended_length = jQuery( this ).data( 'recommended-length' ).toString().split( ';', 2 );
			jQuery( this ).data( 'recommended-min', parseInt( recommended_length[0] ) );
			jQuery( this ).data( 'recommended-max', parseInt( typeof( recommended_length[1] ) != '' ? recommended_length[1] : recommended_length[0] ) );
			// Set counter status:
			evo_input_counter_update_status( jQuery( this ) );
		}
		// Set counter position:
		evo_input_counter_update_position( jQuery( this ) );
	} );

	jQuery( evo_input_counter_selector ).keyup( function()
	{	// Update counter value on key up:
		var counter_obj = jQuery( this ).next( '.evo_input_counter' );
		if( counter_obj.length == 0 )
		{	// Skip wrong input without counter element:
			return;
		}
		var prev_counter_length = counter_obj.html().length;
		counter_obj.html( evo_input_counter_get_count( jQuery( this ) ) );
		// Update counter status when its value was changed:
		evo_input_counter_update_status( jQuery( this ) );
		if( prev_counter_length != counter_obj.html().length )
		{	// Update counter position when its width was changed:
			evo_input_counter_update_position( jQuery( this ) );
		}
	} );

	jQuery( window ).resize( function()
	{	// Update positions of all initialized counters on window resize:
		jQuery( evo_input_counter_selector ).each( function()
		{
			evo_input_counter_update_position( jQuery( this ) );
		} );
	} );

	function evo_input_counter_get_count( input_obj )
	{
		if( evo_input_counter_get_type( input_obj ) == 'countdown' )
		{	// Down counter:
			return input_obj.data( 'maxlength' ) - input_obj.val().length;
		}
		else
		{	// Up counter:
			// Decode html entities in order to count strings like &hellip; &eacute; &bull; &nbsp; as single char:
			return jQuery( '<textarea/>' ).html( input_obj.val() ).text().length;
		}
	}

	function evo_input_counter_get_type( input_obj )
	{
		return ( input_obj.data( 'maxlength' ) !== undefined ) ? 'countdown' : 'recommended';
	}

	function evo_input_counter_update_status( input_obj )
	{
		if( evo_input_counter_get_type( input_obj ) != 'recommended' )
		{	// This function only for recommended length inputs:
			return;
		}

		var counter_obj = input_obj.next( '.evo_input_counter' );
		if( counter_obj.length == 0 )
		{	// Skip wrong input without counter element:
			return;
		}

		var curr_counter_val = counter_obj.html();
		if( curr_counter_val <= input_obj.data( 'recommended-min' ) )
		{	// Use green label for recommended length:
			counter_obj.removeClass( 'label-warning label-danger' ).addClass( 'label label-success' );
		}
		else if( curr_counter_val > input_obj.data( 'recommended-min' ) && curr_counter_val <= input_obj.data( 'recommended-max' ) )
		{	// Use orange label for normal length:
			counter_obj.removeClass( 'label-success label-danger' ).addClass( 'label label-warning' );
		}
		else
		{	// Use red label for very long length:
			counter_obj.removeClass( 'label-success label-warning' ).addClass( 'label label-danger' );
		}
	}

	function evo_input_counter_update_position( input_obj )
	{
		var counter_obj = input_obj.next( '.evo_input_counter' );
		if( counter_obj.length == 0 )
		{	// Skip wrong input without counter element:
			return;
		}

		// Calculate top position for counter element:
		// - for <input> use middle vertical alignemnt,
		// - for <textarea> use bottom vertical alignemnt.
		var valign_size = ( input_obj.outerHeight( true ) - parseInt( counter_obj.outerHeight( true ) ) ) / 2;
		var top = input_obj.position().top + valign_size;
		if( input_obj.prop( 'tagName' ) == 'TEXTAREA' )
		{
			top += valign_size - parseInt( input_obj.css( 'padding-bottom' ) );
		}

		// Set position for counter element:
		counter_obj.css( {
			'top': top,
			'left': input_obj.position().left + input_obj.outerWidth( true ) - counter_obj.outerWidth( true ) - ( input_obj.data( 'padding-right' ) / 2 ),
		} );

		// Update right padding of the input element depending on counter width and source right padding size:
		input_obj.css( 'padding-right', input_obj.data( 'padding-right' ) + counter_obj.outerWidth( true ) );
	}
} );