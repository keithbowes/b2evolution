
<?php
/**
 * This file displays the links attached to an Object, which can be an Item, Comment, ... (called within the attachment_frame)
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
global $retrict_tag;
global $link_ID, $tag_type, $Plugins;

$tag_types = array(
	'image'     => TB_('Image'),
	'thumbnail' => TB_('Thumbnail'),
	'inline'    => TB_('Basic inline'),
);

// Get additional tabs from active Plugins:
$plugins_tabs = $Plugins->trigger_collect( 'GetImageInlineTags', array(
	'link_ID' => $link_ID,
	'active_tag' => $tag_type
) );
foreach( $plugins_tabs as $plugin_ID => $plugin_tabs )
{
	$tag_types = array_merge( $tag_types, $plugin_tabs );
}

?>
<div class="container-fluid">
	<?php if( ! $restrict_tag ): ?>
	<ul class="nav nav-tabs">
		<?php foreach( $tag_types as $tag_type_key => $tag_type_title ): ?>
		<li role="presentation"<?php echo $tag_type == $tag_type_key ? ' class="active"' : '' ;?>><a href="#<?php echo $tag_type_key; ?>" role="tab" data-toggle="tab"><?php echo $tag_type_title; ?></a></li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>
	<div style="margin-top: 20px; display: flex; flex-flow: row nowrap; align-items: flex-start;">
		<div id="image_preview" style="display: flex; align-items: center; min-height: 192px; margin-right: 10px;">
		<?php echo $File->get_thumb_imgtag( 'fit-192x192' ); ?>
		</div>
		<div style="flex-grow: 1">
			<?php
			$Form = new Form( NULL, 'form' );
			$Form->begin_form( 'fform' );
			$Form->hidden( 'link_ID', $link_ID );
			$Form->begin_fieldset( TB_('Parameters') );
			echo '<div class="tab-content">';
			foreach( $tag_types as $tag_type_key => $tag_type_title )
			{
				echo '<div id="'.$tag_type_key.'" class="tab-pane'.( $tag_type == $tag_type_key ? ' active' : '' ).'">';
				switch( $tag_type_key )
				{
					case 'image':
						// Caption:
						$image_caption_params = array();
						if( get_param( 'image_disable_caption' ) )
						{	// Disable input of Caption on initialize this edit form:
							$image_caption_params['disabled'] = 'disabled';
						}
						$Form->text_input( 'image_caption', get_param( 'image_caption' ), 40, TB_('Caption'), '<br>
							<span style="display: flex; flex-flow: row; align-items: center; margin-top: 8px;">
								<input type="checkbox" name="image_disable_caption" id="image_disable_caption" value="1" style="margin: 0 8px 0 0;"'.( get_param( 'image_disable_caption' ) ? ' checked="checked"' : '' ).'>
								<span>'.TB_('Disable caption').'</span></span>', $image_caption_params );
						// Alt text:
						$image_alt_params = array();
						if( get_param( 'image_disable_alt' ) )
						{	// Disable input of Alt text on initialize this edit form:
							$image_alt_params['disabled'] = 'disabled';
						}
						$Form->text_input( 'image_alt', get_param( 'image_alt' ), 40, TB_('Alt text'), '<br>
							<span style="display: flex; flex-flow: row; align-items: center; margin-top: 8px;">
								<input type="checkbox" name="image_disable_alt" id="image_disable_alt" value="1" style="margin: 0 8px 0 0;"'.( get_param( 'image_disable_alt' ) ? ' checked="checked"' : '' ).'>
								<span>'.TB_('Disable alt text').'</span></span>', $image_alt_params );
						// HRef:
						$Form->text( 'image_href', get_param( 'image_href' ), 40, TB_('HRef') );
						// TODO: Size:
						// Class:
						$image_class = get_param( 'image_class' );
						$Form->text( 'image_class', $image_class, 40, TB_('Styles'), '<br><div class="style_buttons" style="margin-top: 8px;">
							<button class="btn btn-default btn-xs">border</button>
							<button class="btn btn-default btn-xs">noborder</button>
							<button class="btn btn-default btn-xs">rounded</button>
							<button class="btn btn-default btn-xs">squared</button></div>', '' );
						break;

					case 'thumbnail':
						// Alt text:
						$thumbnail_alt_params = array();
						if( get_param( 'thumbnail_disable_alt' ) )
						{	// Disable input of Alt text on initialize this edit form:
							$thumbnail_alt_params['disabled'] = 'disabled';
						}
						$Form->text_input( 'thumbnail_alt', get_param( 'thumbnail_alt' ), 40, TB_('Alt text'), '<br>
							<span style="display: flex; flex-flow: row; align-items: center; margin-top: 8px;">
								<input type="checkbox" name="thumbnail_disable_alt" id="thumbnail_disable_alt" value="1" style="margin: 0 8px 0 0;"'.( get_param( 'thumbnail_disable_alt' ) ? ' checked="checked"' : '' ).'>
								<span>'.TB_('Disable alt text').'</span></span>', $thumbnail_alt_params );
						// HRef:
						$Form->text( 'thumbnail_href', get_param( 'thumbnail_href' ), 40, TB_('HRef') );
						// Size:
						$Form->radio( 'thumbnail_size', get_param( 'thumbnail_size' ), array(
								array( 'small', 'small' ),
								array( 'medium', 'medium' ),
								array( 'large', 'large' )
							), TB_( 'Size') );
						// Alignment:
						$Form->radio( 'thumbnail_alignment', get_param( 'thumbnail_alignment' ), array(
								array( 'left', 'left' ),
								array( 'right', 'right' )
							), TB_( 'Alignment') );
						// Class:
						$thumbnail_class = get_param( 'thumbnail_class' );
						$Form->text( 'thumbnail_class', get_param( 'thumbnail_class' ), 40, TB_('Styles'), '<br><div class="style_buttons" style="margin-top: 8px;">
							<button class="btn btn-default btn-xs">border</button>
							<button class="btn btn-default btn-xs">noborder</button>
							<button class="btn btn-default btn-xs">rounded</button>
							<button class="btn btn-default btn-xs">squared</button></div>', '' );
						break;

					case 'inline':
						// Alt text:
						$inline_alt_params = array();
						if( get_param( 'inline_disable_alt' ) )
						{	// Disable input of Alt text on initialize this edit form:
							$inline_alt_params['disabled'] = 'disabled';
						}
						$Form->text_input( 'inline_alt', get_param( 'inline_alt' ), 40, TB_('Alt text'), '<br>
							<span style="display: flex; flex-flow: row; align-items: center; margin-top: 8px;">
								<input type="checkbox" name="inline_disable_alt" id="inline_disable_alt" value="1" style="margin: 0 8px 0 0;"'.( get_param( 'inline_disable_alt' ) ? ' checked="checked"' : '' ).'>
								<span>'.TB_('Disable alt text').'</span></span>', $inline_alt_params );
						// Class:
						$inline_class = get_param( 'inline_class' );
						$Form->text( 'inline_class', get_param( 'inline_class' ), 40, TB_('Styles'), '<br><div class="style_buttons" style="margin-top: 8px;">
								<button class="btn btn-default btn-xs">border</button>
								<button class="btn btn-default btn-xs">noborder</button>
								<button class="btn btn-default btn-xs">rounded</button>
								<button class="btn btn-default btn-xs">squared</button></div>', '' );
						break;

					default:
						// Display additional inline tag form from active plugins:
						$Plugins->trigger_event( 'DisplayImageInlineTagForm', array(
								'link_ID'     => $link_ID,
								'active_tag'  => $tag_type,
								'display_tag' => $tag_type_key,
								'Form'        => $Form,
							) );
				}
				echo '</div>';
			}
			echo '</div>';

			$Form->submit( array( 'value' => 'Insert', 'onclick' => 'return evo_image_submit()' ) );

			$Form->end_fieldset();
			$Form->end_form();
			?>

			<script>
			jQuery( document ).ready( function() {
				var img = jQuery( '#modal_window #image_preview img' );
				var tagType = jQuery( '#modal_window .tab-content .tab-pane.active' ).attr( 'id' );

				// Add class to text input
				jQuery( '#modal_window div.tab-pane div.style_buttons button' ).click( function() {
					return evo_image_add_class( this, jQuery( this ).text() );
				} );

				// Apply class to preview image
				jQuery( '#modal_window input[name$="_class"]' ).on( 'change keydown', debounce( function() {
					apply_image_class( jQuery( this ).val() );
				}, 200 ) );

				jQuery( '#modal_window input[name$="_disable_caption"]' ).click( function() {
						var active_tag_type = jQuery( '#modal_window .tab-content .tab-pane.active' ).attr( 'id' );
						jQuery( '#modal_window input[name="' + active_tag_type + '_caption"]' ).prop( 'disabled', jQuery( this ).is( ':checked' ) );
					});

				// Update preview on tab change
				jQuery( '#modal_window a[data-toggle="tab"]' ).on( 'shown.bs.tab', function( e ) {
					var target = jQuery( e.target ).attr( 'href' );
					apply_image_class( jQuery( '#modal_window div' + target + ' input[name$="_class"]' ).val() );
				} );

				<?php
				// Apply existing classes
				if( ! empty( $image_class ) )
				{
					echo 'apply_image_class( "'.$image_class.'" );';
				}
				if( ! empty( $thumbnail_class ) )
				{
					echo 'apply_image_class( "'.$thumbnail_class.'" );';
				}
				if( ! empty( $inline_class ) )
				{
					echo 'apply_image_class( "'.$inline_class.'" );';
				}
				?>
			} );

			function apply_image_class( imageClasses )
			{
				var img = jQuery( '#modal_window #image_preview img' );

				imageClasses = imageClasses.split( '.' );
				img.removeClass();
				for( var i = 0; i < imageClasses.length; i++ )
				{
					img.addClass( imageClasses[i] );
				}
			}

			function evo_image_add_class( event_obj, className )
			{
				var input = jQuery( 'input[name$="_class"]', jQuery( event_obj ).closest( 'div.tab-pane' ) );
				var styles = input.val();

				styles = styles.split( '.' );
				if( styles.indexOf( className ) == -1 )
				{
					styles.push( className );
				}

				input.val( styles.join( '.' ) );
				apply_image_class( input.val() );
				return false;
			}

			function evo_image_submit()
			{
				// Get active tab pane
				var tagType = jQuery( '#modal_window .tab-content .tab-pane.active' ).attr( 'id' );
				var linkID = jQuery( '#modal_window input[name="link_ID"]' ).val();
				if( tagType == 'image' )
				{
					var caption = jQuery( '#modal_window input[name="' + tagType + '_caption"]' ).val();
					var noCaption = jQuery( '#modal_window input[name="' + tagType + '_disable_caption"]' ).is( ':checked' );
				}
				var alt = jQuery( '#modal_window input[name="' + tagType + '_alt"]' ).val();
				var noAlt = jQuery( '#modal_window input[name="' + tagType + '_disable_alt"]' ).is( ':checked' );
				if( tagType == 'thumbnail' )
				{
					var alignment = jQuery( '#modal_window input[name="' + tagType + '_alignment"]:checked' ).val();
					var size = jQuery( '#modal_window input[name="' + tagType + '_size"]:checked' ).val();
				}
				var href = jQuery( '#modal_window input[name="' + tagType + '_href"]' ).val();
				var classes = jQuery( '#modal_window input[name="' + tagType + '_class"]' ).val();
				var tag_caption = false;

				var options = '';

				// Caption (only for image):
				if( tagType == 'image' )
				{
					if( noCaption )
					{
						options += '-';
					}
					else
					{
						options += caption;
					}
				}

				// Alt text:
				if( alt != '' || noAlt )
				{
					if( tagType == 'image' )
					{	// For image Caption is always before Alt text:
						options += ':';
					}
					if( noAlt )
					{
						options += '-';
					}
					else
					{
						options += alt;
					}
				}

				// HRef:
				if( href && href.match( /^(https?:\/\/.+|\(\((.*?)\)\))$/i ) )
				{
					if( options == '' )
					{	// Insert empty option for default Caption before HRef (only for image):
						options += ( tagType == 'image' ? ':' : '' );
					}
					else
					{	// Insert separator between previous option and HRef:
						options += ':';
					}
					options += href;
				}

				if( tagType == 'thumbnail' )
				{
					// Size:
					options += ( options == '' ? '' : ':' ) + size;

					// Alignment:
					options += ':' + alignment;
				}

				// TODO: Size (for image):

				// Styles:
				if( classes )
				{
					if( tagType == 'image' )
					{
						options += ':' + classes;
					}
					else
					{
						options += ( options == '' ? '' : ':' ) + classes;
					}
				}
				<?php
				// Display additional JavaScript code from plugins before submit/insert inline tag:
				$plugins_javascript = $Plugins->trigger_collect( 'GetInsertImageInlineTagJavaScript', array( 'link_ID' => $link_ID ) );
				foreach( $plugins_javascript as $plugin_ID => $plugin_javascript )
				{
					echo "\n\n".'// START JS from plugin #'.$plugin_ID.':'."\n";
					echo $plugin_javascript;
					echo "\n".'// END JS from plugin #'.$plugin_ID.'.'."\n\n";
				}
				?>
				window.parent.evo_link_insert_inline( tagType, linkID, options, <?php echo $replace;?>, tag_caption, <?php echo param( 'prefix', 'string' ); ?>b2evoCanvas );

				closeModalWindow();

				return false;
			}
			</script>
		</div>
	</div>
</div>