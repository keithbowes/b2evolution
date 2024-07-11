<?php
/**
 * This file implements the Item history view to compare two revisions
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url;

global $edited_Item, $Revision_1, $Revision_2;

global $revisions_difference_title, $revisions_difference_content, $revisions_difference_custom_fields, $revisions_difference_links;

$post_statuses = get_visibility_statuses();

$Form = new Form( NULL, 'history', 'post', 'compact' );

$Form->global_icon( T_('Cancel comparing!'), 'close', regenerate_url( 'action', 'action=history' ) );

$Form->begin_form( 'fform', sprintf( T_('Difference between revisions for: %s'), $edited_Item->get_title() ) );
?>
<table border="0" width="100%" cellpadding="0" cellspacing="4" class="diff">
	<col class="diff-marker" />
	<col class="diff-content" />
	<col class="diff-marker" />
	<col class="diff-content" />
	<tr><td colspan="4">&nbsp;</td></tr>
	<tr>
		<td colspan="4" class="diff-title-addedline diff-section-title"><b><?php echo T_('Version').':'; ?></b></td>
	</tr>
	<tr>
		<td colspan="2" class="diff-otitle">
			<p><?php echo get_item_version_title( $Revision_1 ); ?></p>
		</td>
		<td colspan="2" class="diff-ntitle">
			<p><?php echo get_item_version_title( $Revision_2 ); ?></p>
		</td>
	</tr>
	<tr><td colspan="4">&nbsp;</td></tr>
	<tr>
		<td colspan="4" class="diff-title-addedline diff-section-title"><b><?php echo T_('Status').':'; ?></b></td>
	</tr>
	<tr>
		<td colspan="2" class="diff-otitle">
			<div class="center"><?php echo $post_statuses[ $Revision_1->iver_status ]; ?></div>
		</td>
		<td colspan="2" class="diff-ntitle">
			<div class="center"><span<?php echo $Revision_1->iver_status != $Revision_2->iver_status ? ' style="color:#F00;font-weight:bold"' : ''; ?>><?php echo $post_statuses[ $Revision_2->iver_status ]; ?></span></div>
		</td>
	</tr>
	<tr><td colspan="4">&nbsp;</td></tr>
	<tr>
		<td colspan="4" class="diff-title-addedline diff-section-title"><b><?php echo T_('Title').':'; ?></b></td>
	</tr>
<?php
	if( ! empty( $revisions_difference_title ) )
	{	// Display title difference:
		echo $revisions_difference_title;
	}
	else
	{	// No title difference
	?>
	<tr>
		<td colspan="2" class="diff-title-deletedline"><?php echo $Revision_1->iver_title ?></td>
		<td colspan="2" class="diff-title-addedline"><?php echo $Revision_2->iver_title ?></td>
	</tr>
	<?php
	}
?>
	<tr><td colspan="4">&#160;</td></tr>
	<tr>
		<td colspan="4" class="diff-title-addedline diff-section-title"><b><?php echo T_('Text').':'; ?></b></td>
	</tr>
<?php
if( ! empty( $revisions_difference_content ) )
{	// Display content difference:
	echo $revisions_difference_content;
}
else
{	// No content difference
	echo '<tr><td colspan="4" class="center">'.T_('No difference.').'</td></tr>';
}

if( is_array( $revisions_difference_custom_fields ) )
{	// Display custom fields difference only if Item revision had at least one custom field:
?>
		<tr><td colspan="4">&nbsp;</td></tr>
		<tr>
			<td colspan="4" class="diff-title-addedline diff-section-title"><b><?php echo T_('Custom fields').':'; ?></b></td>
		</tr>
<?php
if( empty( $revisions_difference_custom_fields ) )
{	// No difference in custom fields:
	echo '<tr><td colspan="4" class="center">'.T_('No difference.').'</td></tr>';
}
else
{	// Display custom fields difference:
	foreach( $revisions_difference_custom_fields as $revisions_diff_data )
	{
		if( isset( $revisions_diff_data['diff_label'] ) )
		{	// Display a difference between two field labels:
			echo $revisions_diff_data['diff_label'];
		}
		else
		{	// No difference between two field labels:
			echo '<tr>'
					.'<td colspan="2"><b>'.$revisions_diff_data['r1_label'].':</b></td>'
					.'<td colspan="2"><b>'.$revisions_diff_data['r2_label'].':</b></td>'
				.'</tr>';
		}

		if( isset( $revisions_diff_data['diff_value'] ) )
		{	// Display a difference between two field values:
			echo $revisions_diff_data['diff_value'];
		}
		else
		{	// No difference because one revision has no the custom field:
			echo '<tr>';
			for( $r = 1; $r <= 2; $r++ )
			{
				echo '<td></td>';
				if( isset( $revisions_diff_data['r'.$r.'_value'] ) )
				{	// Display a field value if the revision has it:
					echo '<td class="diff-context">'.nl2br( htmlspecialchars( $revisions_diff_data['r'.$r.'_value'] ) ).'</td>';
				}
				elseif( $r == 1 )
				{	// If field exists only in new revision:
					echo '<td class="red"><b>'.sprintf( T_('The field "%s" did not exist'), $revisions_diff_data['r'.$r.'_label'] ).'</b></td>';
				}
				else
				{	// If field exists only in old revision:
					echo '<td class="violet"><b>'.sprintf( T_('The field "%s" has been removed'), $revisions_diff_data['r'.$r.'_label'] ).'</b></td>';
				}
			}
			echo '</tr>';
		}
	}
}
}

if( is_array( $revisions_difference_links ) )
{	// Display links/attachments difference only if Item revision had at least one attached File:
?>
		<tr><td colspan="4">&nbsp;</td></tr>
		<tr>
			<td colspan="4" class="diff-title-addedline diff-section-title"><b><?php echo T_('Images &amp; Attachments').':'; ?></b></td>
		</tr>
	<?php
	if( empty( $revisions_difference_links ) )
	{	// No difference in attached files:
		echo '<tr><td colspan="4" class="center">'.T_('No difference.').'</td></tr>';
	}
	else
	{	// Display links/attachments difference:
	?>
		<tr>
			<td colspan="4">
				<table class="table table-striped table-bordered table-condensed">
					<thead>
						<tr>
						<?php
						for( $r = 1; $r <= 2; $r++ )
						{	// Print out table headers for two revisions:
							echo '<th class="nowrap">'.T_('Icon/Type').'</th>';
							echo '<th class="nowrap" width="50%">'.T_('Destination').'</th>';
							echo '<th class="nowrap">'.T_('Order').'</th>';
							echo '<th class="nowrap">'.T_('Position').'</th>';
							if( $r == 1 )
							{	// Use ID column as separator between the compared revisions:
								echo '<th class="nowrap">'.T_('Link ID').'</th>';
							}
						}
						?>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach( $revisions_difference_links as $link_ID => $links_data )
					{
						echo '<tr>';
						for( $r = 1; $r <= 2; $r++ )
						{	// Print out differences of the compared revisions:
							if( isset( $links_data['r'.$r] ) )
							{	// If the revision has the Link/Attachment:
								$link = $links_data['r'.$r];
								foreach( $links_data['r'.$r] as $link_key => $link_value )
								{	// Print out each link propoerty:
									if( $link_key == 'file_ID' )
									{	// Skip column with file ID:
										continue;
									}
									$r2 = $r == 1 ? 2 : 1;
									$class = '';
									if( isset( $links_data['r'.$r2][ $link_key ] ) && $link_value != $links_data['r'.$r2][ $link_key ] )
									{	// Mark the different property with red background color:
										$class .= 'bg-danger';
									}
									if( $link_key == 'order' )
									{	// Order value must be aligned to the right:
										$class .= ' text-right';
									}
									elseif( $link_key == 'path' && $link_value === false )
									{	// If file was deleted:
										$link_value = '<b class="red">'.sprintf( T_('The file "%s" was deleted'), '#'.$links_data['r'.$r]['file_ID'] ).'</b>';
										$class .= ' bg-danger';
									}
									$class = trim( $class );
									echo '<td'.( empty( $class ) ? '' : ' class="'.$class.'"' ).'>'.$link_value.'</td>';
								}
							}
							else
							{	// If the revision has no the Link/Attachment:
								echo '<td colspan="4"><b class="violet">'.sprintf( T_('The attachment "%s" is not used in this revision'), '#'.$link_ID ).'</b></td>';
							}
							if( $r == 1 )
							{	// Use ID column as separator between the compared revisions:
								echo '<td class="bg-info text-right"><b>'.$link_ID.'</b></td>';
							}
						}
						echo '</tr>';
					}
					?>
					</tbody>
				</table>
			</td>
		</tr>
<?php
	}
}
?>
</table>
<?php

$Form->end_form();

// JS code for merge button:
echo_item_merge_js();
?>
