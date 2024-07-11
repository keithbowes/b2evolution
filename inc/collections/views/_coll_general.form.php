<?php
/**
 * This file implements the UI view for the General blog properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;
global $action, $next_action, $blogtemplate, $blog, $tab, $admin_url, $locales, $duplicating_collection_name;
global $Settings;

$Form = new Form();

$form_title = '';
$is_creating = ( $edited_Blog->ID == 0 || $action == 'copy' );
if( $edited_Blog->ID == 0 )
{ // "New blog" form: Display a form title and icon to close form
	global $kind;
	$kind_title = get_collection_kinds( $kind );
	$form_title = sprintf( TB_('New "%s" collection'), $kind_title ).':';

	$Form->global_icon( TB_('Abort creating new collection'), 'close', $admin_url.'?ctrl=collections', ' '.sprintf( TB_('Abort new "%s" collection'), $kind_title ), 3, 3 );
}
elseif( $action == 'copy' )
{	// Copy collection form:
	$form_title = sprintf( TB_('Duplicate "%s" collection'), $duplicating_collection_name ).':';

	$Form->global_icon( TB_('Abort duplicating collection'), 'close', $admin_url.'?ctrl=collections', ' '.TB_('Abort duplicating collection'), 3, 3 );
}

$Form->begin_form( 'fform', $form_title );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', $next_action );
$Form->hidden( 'tab', $tab );
if( $next_action == 'create' )
{
	$Form->hidden( 'kind', get_param('kind') );
	$Form->hidden( 'skin_ID', get_param('skin_ID') );
}
else
{
	$Form->hidden( 'blog', $edited_Blog->ID );
}

if( ! empty( $edited_Blog->confirmation ) )
{	// Display a confirmation message:
	$form_fieldset_begin = $Form->fieldset_begin;
	$Form->fieldset_begin = str_replace( 'panel-default', 'panel-danger', $Form->fieldset_begin );
	$Form->begin_fieldset( TB_('Confirmation') );

		echo '<h3 class="evo_confirm_delete__title">'.$edited_Blog->confirmation['title'].'</h3>';

		if( ! empty( $edited_Blog->confirmation['messages'] ) )
		{
			echo '<div class="log_container delete_messages"><ul>';
			foreach( $edited_Blog->confirmation['messages'] as $confirmation_message )
			{
				echo '<li>'.$confirmation_message.'</li>';
			}
			echo '</ul></div>';
		}

		echo '<p class="warning text-danger">'.TB_('Do you confirm?').'</p>';
		echo '<p class="warning text-danger">'.TB_('THIS CANNOT BE UNDONE!').'</p>';

		// Fake button to submit form by key "Enter" without autoconfirm this:
		$Form->button_input( array(
				'name'  => 'submit',
				'style' => 'position:absolute;left:-10000px'
			) );
		// Real button to confirm:
		$Form->button( array( 'submit', 'actionArray[update_confirm]', TB_('I am sure!'), 'DeleteButton btn-danger' ) );
		$Form->button( array( 'button', '', TB_('CANCEL'), 'CancelButton', 'location.href="'.$admin_url.'?ctrl=coll_settings&tab=general&blog='.$edited_Blog->ID.'"' ) );

	$Form->end_fieldset();
	$Form->fieldset_begin = $form_fieldset_begin;
}


$Form->begin_fieldset( TB_('Collection type').get_manual_link( 'collection-type-panel' ) );
	$collection_kinds = get_collection_kinds();
	if( isset( $collection_kinds[ $edited_Blog->get( 'type' ) ] ) )
	{ // Display type of this blog
		echo '<p>'
			.sprintf( TB_('This is a "%s" collection'), $collection_kinds[ $edited_Blog->get( 'type' ) ]['name'] )
			.' &ndash; '
			.$collection_kinds[ $edited_Blog->get( 'type' ) ]['desc']
		.'</p>';
		if( ! $is_creating && $action != 'copy' )
		{	// Display a link to change collection kind:
			echo '<p><a href="'.$admin_url.'?ctrl=coll_settings&amp;tab=general&action=type&amp;blog='.$edited_Blog->ID.'">'
					.TB_('Change collection type / Reset')
			.'</a></p>';
		}
	}
	if( $edited_Blog->get( 'type' ) == 'main' )
	{ // Only show when collection is of type 'Main'
		$set_as_checked = 0;
		switch( $action )
		{
			case 'edit':
				$set_as_checked = 0;
				break;

			case 'new-name':
			case 'create':
				$set_as_checked = 1;
				break;
		}

		$set_as_options = array();
		if( ! $Settings->get( 'login_blog_ID' ) )
		{
			$set_as_options[] = array( 'set_as_login_blog', 1, TB_('Collection for login/registration'), param( 'set_as_login_blog', 'boolean', $set_as_checked ) );
		}
		if( ! $Settings->get( 'msg_blog_ID' ) )
		{
			$set_as_options[] = array( 'set_as_msg_blog', 1, TB_('Collection for profiles/messaging'), param( 'set_as_msg_blog', 'boolean', $set_as_checked ) );
		}
		if( ! $Settings->get( 'info_blog_ID' ) )
		{
			$set_as_options[] = array( 'set_as_info_blog', 1, TB_('Collection for shared content blocks'), param( 'set_as_info_blog', 'boolean', $set_as_checked ) );
		}

		if( $set_as_options )
		{
			$Form->checklist( $set_as_options, 'set_as_options', TB_('Automatically set as') );
		}

		if( $is_creating )
		{
			echo '<p>'.TB_('The Home collection typically aggregates the contents of all other collections on the site.').'</p>';
			$aggregate_coll_IDs = $edited_Blog->get_setting( 'aggregate_coll_IDs' );
			$Form->radio( 'blog_aggregate', empty( $aggregate_coll_IDs ) ? 0 : 1,
			array(
				array( 1, TB_('Set to aggregate contents of all other collections') ),
				array( 0, TB_('Do not aggregate') ),
			), TB_('Aggregate'), true, '' );
		}
	}
$Form->end_fieldset();

if( in_array( $action, array( 'create', 'new-name' ) ) && $ctrl = 'collections' )
{	// Only show demo content option when creating a new collection
	load_funcs( 'dashboard/model/_dashboard.funcs.php' );

	$Form->begin_fieldset( TB_( 'Demo contents' ).get_manual_link( 'collection-demo-content' ) );
		$Form->radio( 'create_demo_contents', param( 'create_demo_contents', 'integer', -1 ),
					array(
						array( 1, TB_('Initialize this collection with some demo contents') ),
						array( 0, TB_('Create an empty collection') ),
					), TB_('New contents'), true, '', true );

		if( get_table_count( 'T_users__organization' ) === 0 )
		{
			if( check_user_perm( 'orgs', 'create', false ) && check_user_perm( 'blog_admin', 'editall', false ) )
			{	// Permission to create organizations
				$Form->checkbox( 'create_demo_org', param( 'create_demo_org', 'integer', 1 ),
						TB_( 'Create demo organization' ), TB_( 'Create a demo organization if none exists.' ) );
			}
		}

		if( get_table_count( 'T_users', 'user_ID != 1' ) === 0 )
		{
			if( check_user_perm( 'users', 'edit', false ) && check_user_perm( 'blog_admin', 'editall', false ) )
			{	// Permission to edit users
				$Form->checkbox( 'create_demo_users', param( 'create_demo_users', 'integer', 1 ),
						TB_( 'Create demo users' ), TB_( 'Create demo users as comment authors.' ) );
			}
		}
	$Form->end_fieldset();
}

$Form->begin_fieldset( TB_('Branding').get_manual_link( 'collection-settings-branding' ), array( 'class'=>'fieldset clear' ) );

	$name_chars_count = utf8_strlen( html_entity_decode( $edited_Blog->get( 'name' ) ) );
	$Form->text( 'blog_name', $edited_Blog->get( 'name' ), 50, TB_('Title'), TB_('Will be displayed on top of the blog.')
		.' ('.sprintf( TB_('%s characters'), '<span id="blog_name_chars_count">'.$name_chars_count.'</span>' ).')', 255 );

	$blog_shortname = $action == 'copy' ? NULL : $edited_Blog->get( 'shortname' );
	$Form->text( 'blog_shortname', $blog_shortname, 15, TB_('Short name'), TB_('Will be used in selection menus and throughout the admin interface.'), 255 );

	if( check_user_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) ||
	    check_user_perm( 'blogs', 'create', false, $edited_Blog->sec_ID ) )
	{ // Permission to edit advanced admin settings
		$blog_urlname = $action == 'copy' ? NULL : $edited_Blog->get( 'urlname' );
		$Form->text( 'blog_urlname', $blog_urlname, 20, TB_('URL "filename"'),
				sprintf( TB_('"slug" used to uniquely identify this blog in URLs. Also used as <a %s>default media folder</a>.'),
					'href="?ctrl=coll_settings&amp;tab=advanced&amp;blog='.$blog.'"'), 255 );
	}
	else
	{
		$Form->info( TB_('URL Name'), '<span id="urlname_display">'.$edited_Blog->get( 'urlname' ).'</span>', TB_('Used to uniquely identify this blog in URLs.') /* Note: message voluntarily shorter than admin message */ );
		if( $is_creating )
		{
			$Form->hidden( 'blog_urlname', $edited_Blog->get( 'urlname' ) );
		}
	}

	if( $is_creating )
	{
		$blog_urlname = $action == 'copy' ? NULL : $edited_Blog->get( 'urlname' );
		?>
		<script>
		var shortNameInput = jQuery( '#blog_shortname');
		var timeoutId = 0;

		function getAvailableUrlName( urlname )
		{
			if( urlname )
			{
				var urlNameInput = jQuery( 'input#blog_urlname' );
				urlNameInput.addClass( 'loader_img' );

				evo_rest_api_request( 'tools/available_urlname',
				{
					'urlname': urlname
				},
				function( data )
				{
					jQuery( 'span#urlname_display' ).html( data.urlname );
					jQuery( 'input[name="blog_urlname"]' ).val( data.urlname );
					urlNameInput.removeClass( 'loader_img' );
				}, 'GET' );
			}
		}

		shortNameInput.on( 'keyup', function( ) {
			clearTimeout( timeoutId );
			timeoutId = setTimeout( function() { getAvailableUrlName( shortNameInput.val() ) }, 500 );
		} );

		jQuery( document ).ready( function() {
			getAvailableUrlName( '<?php echo format_to_js( $blog_urlname ); ?>' );
		} );
		</script>
		<?php
	}

	$collection_logo_params = array( 'file_type' => 'image', 'max_file_num' => 1, 'window_title' => TB_('Select collection logo/image'), 'root' => 'shared_0', 'size_name' => 'fit-320x320' );
	$Form->fileselect( 'collection_logo_file_ID', $edited_Blog->get_setting( 'collection_logo_file_ID' ), TB_('Collection logo/image'), NULL, $collection_logo_params );

	$collection_favicon_params = array( 'file_type' => 'image', 'max_file_num' => 1, 'window_title' => TB_('Select collection favicon'), 'root' => 'shared_0', 'size_name' => 'fit-128x128' );
	$Form->fileselect( 'collection_favicon_file_ID', $edited_Blog->get_setting( 'collection_favicon_file_ID' ), TB_('Collection favicon'), NULL, $collection_favicon_params );

	// Section:
	$blog_section_id = $action == 'copy' ? 1 : $edited_Blog->get( 'sec_ID' );
	$SectionCache = & get_SectionCache();
	$SectionCache->load_available( $blog_section_id );
	if( count( $SectionCache->cache_available ) > 1 )
	{ // If we have only one option in the list do not show select input
		$Form->select_input_object( 'sec_ID', $blog_section_id, $SectionCache, TB_('Section'), array( 'required' => true ) );
	}

$Form->end_fieldset();

// Calculate how much locales are enabled in system
$number_enabled_locales = 0;
foreach( $locales as $locale_data )
{
	if( $locale_data['enabled'] )
	{
		$number_enabled_locales++;
	}
	if( $number_enabled_locales > 1 )
	{ // We need to know we have more than 1 locale is enabled, Stop here
		break;
	}
}

if( ! $is_creating )
{
	$Form->begin_fieldset( TB_('Language / locale').get_manual_link( 'coll-locale-settings' ), array( 'id' => 'language' ) );
		if( $number_enabled_locales > 1 )
		{ // More than 1 locale
			$blog_locale_note = ( check_user_perm( 'options', 'view' ) ) ?
				'<a href="'.$admin_url.'?ctrl=regional">'.TB_('Regional settings').' &raquo;</a>' : '';
		$Form->locale_selector( 'blog_locale', $edited_Blog->get( 'locale' ), array_keys( $edited_Blog->get_locales() ), TB_('Collection Locales'), $blog_locale_note, array( 'link_coll_ID' => $edited_Blog->ID ) );

			$Form->radio( 'blog_locale_source', $edited_Blog->get_setting( 'locale_source' ),
					array(
						array( 'blog', TB_('Always force to collection locale') ),
						array( 'user', TB_('Use browser / user locale when possible') ),
				), TB_('Navigation/Widget Display'), true );

			$Form->radio( 'blog_post_locale_source', $edited_Blog->get_setting( 'post_locale_source' ),
					array(
						array( 'post', TB_('Always force to Post locale') ),
					array( 'blog', TB_('Follow navigation locale'), '('.TB_('Navigation/Widget Display').')' ),
				), TB_('Post Details Display'), true );

			$Form->radio( 'blog_new_item_locale_source', $edited_Blog->get_setting( 'new_item_locale_source' ),
					array(
						array( 'select_coll', TB_('Default to collection\'s main locale') ),
						array( 'select_user', TB_('Default to user\'s locale') ),
				), TB_('New Posts'), true );
		}
		else
		{ // Only one locale
			echo '<p>';
			echo sprintf( TB_( 'This collection uses %s.' ), '<b>'.$locales[ $edited_Blog->get( 'locale' ) ]['name'].'</b>' );
			if( check_user_perm( 'options', 'view' ) )
			{
				echo ' '.sprintf( TB_( 'Go to <a %s>Regional Settings</a> to enable additional locales.' ), 'href="'.$admin_url.'?ctrl=regional"' );
			}
			echo '</p>';
		}

	$Form->end_fieldset();
}
else
{
	if( $number_enabled_locales > 1 )
	{
		$Form->hidden( 'blog_locale', $edited_Blog->get( 'locale' ) );
		$Form->hidden( 'blog_locale_source', $edited_Blog->get_setting( 'locale_source' ) );
		$Form->hidden( 'blog_post_locale_source', $edited_Blog->get_setting( 'post_locale_source' ) );
		$Form->hidden( 'blog_new_item_locale_source', $edited_Blog->get_setting( 'new_item_locale_source' ) );
	}
}

if( $action == 'copy' )
{	// Additional options for collection duplicating:
	$Form->begin_fieldset( TB_('Options').get_manual_link( 'collection-options' ) );
		$Form->checkbox( 'duplicate_items', param( 'duplicate_items', 'integer', 1 ), TB_('Duplicate contents'), TB_('Check to duplicate posts/items from source collection.') );
		$Form->checkbox( 'duplicate_comments', param( 'duplicate_comments', 'integer', 0 ), TB_('Duplicate comments'), TB_('Check to duplicate comments from source collection.'), '', 1, ! get_param( 'duplicate_items' ) );
	$Form->end_fieldset();
}

$Form->begin_fieldset( TB_('Collection permissions').get_manual_link( 'collection-permission-settings' ) );

	if( $action == 'copy' )
	{
		$owner_User = $current_User;
	}
	else
	{
		$owner_User = & $edited_Blog->get_owner_User();
	}
	if( check_user_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{ // Permission to edit advanced admin settings
		// fp> Note: There are 2 reasons why we don't provide a select here:
		// 1. If there are 1000 users, it's a pain.
		// 2. A single blog owner is not necessarily allowed to see all other users.
		$Form->username( 'owner_login', $owner_User, TB_('Owner'), TB_('Login of this blog\'s owner.') );
	}
	else
	{
		if( ! $is_creating )
		{
			$Form->info( TB_('Owner'), $owner_User->login, $owner_User->dget( 'fullname' ) );
		}
	}

	if( ! $is_creating )
	{
		$Form->radio( 'advanced_perms', $edited_Blog->get( 'advanced_perms' ),
				array(
					array( '0', TB_('Simple permissions'), sprintf( TB_('(the owner above has most permissions on this collection, except %s)'), get_admin_badge() ) ),
					array( '1', TB_('Advanced permissions'), sprintf( TB_('(you can assign granular <a %s>user</a> and <a %s>group</a> permissions for this collection)'),
											'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=perm&amp;blog='.$edited_Blog->ID.'"',
											'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=permgroup&amp;blog='.$edited_Blog->ID.'"' ) ),
			), TB_('Permission management'), true );
	}
	else
	{
		$Form->hidden( 'advanced_perms', $edited_Blog->get( 'advanced_perms' ) );
	}

	$blog_allow_access = $action == 'copy' ? 'public' : $edited_Blog->get_setting( 'allow_access' );
	$Form->radio( 'blog_allow_access', $blog_allow_access,
			array(
				array( 'public', TB_('Everyone (Public Blog)') ),
				array( 'users', TB_('Community only (Logged-in users only)') ),
				array( 'members',
									'<span id="allow_access_members_advanced_title"'.( $edited_Blog->get( 'advanced_perms' ) ? '' : ' style="display:none"' ).'>'.TB_('Members only').'</span>'.
									'<span id="allow_access_members_simple_title"'.( $edited_Blog->get( 'advanced_perms' ) ? ' style="display:none"' : '' ).'>'.TB_('Only the owner').'</span>',
									'<span id="allow_access_members_advanced_note"'.( $edited_Blog->get( 'advanced_perms' ) ? '' : ' style="display:none"' ).'>'.sprintf( TB_('(Assign membership in <a %s>user</a> and <a %s>group</a> permissions for this collection)'),
										'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=perm&amp;blog='.$edited_Blog->ID.'"',
										'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=permgroup&amp;blog='.$edited_Blog->ID.'"' ).'</span>'.
									'<span id="allow_access_members_simple_note"'.( $edited_Blog->get( 'advanced_perms' ) ? ' style="display:none"' : '' ).'>'.TB_('(Private collection)').'</span>' ),
		), TB_('Allow access to'), true );

$Form->end_fieldset();

if( ! $is_creating )
{
	$Form->begin_fieldset( TB_('Lists of collections').get_manual_link( 'collection-list-settings' ) );

		$Form->text( 'blog_order', $edited_Blog->get( 'order' ), 10, TB_('Order') );

		$Form->radio( 'blog_in_bloglist', $edited_Blog->get( 'in_bloglist' ),
								array(  array( 'public', TB_('Always (Public)') ),
												array( 'logged', TB_('For logged-in users only') ),
												array( 'member', TB_('For members only') ),
												array( 'never', TB_('Never') )
											), TB_('Show in front-office / front-end'), true, TB_('Select when you want this blog to appear in the list of blogs on this system.') );

	$Form->end_fieldset();


	$Form->begin_fieldset( TB_('Description').get_manual_link( 'collection-description' ) );

		$Form->text( 'blog_tagline', $edited_Blog->get( 'tagline' ), 50, TB_('Tagline'), TB_('This is typically displayed by a widget right under the collection name in the front-office.'), 250 );

		$Form->textarea( 'blog_notes', $edited_Blog->get( 'notes' ), 5, TB_('Dashboard notes'),
			TB_('Additional info. Appears in the backoffice.'), 50 );

	$Form->end_fieldset();
}
else
{
	$Form->hidden( 'blog_order', $edited_Blog->get( 'order' ) );
	$Form->hidden( 'blog_in_bloglist', $edited_Blog->get( 'in_bloglist' ) );
	$Form->hidden( 'blog_tagline', $edited_Blog->get( 'tagline' ) );
	$Form->hidden( 'blog_shortdesc', $edited_Blog->get( 'shortdesc' ) );
	$Form->hidden( 'blog_longdesc', $edited_Blog->get( 'longdesc' ) );
}

$Form->buttons( array( array( 'submit', 'submit', ( $action == 'copy' ? TB_('Duplicate NOW!') : TB_('Save Changes!') ), 'SaveButton' ) ) );

$Form->end_form();

?>
<script>

function updateDemoContentInputs()
{
	if( jQuery( 'input[name=create_demo_contents]:checked' ).val() == '1' )
	{
		jQuery( 'input[name=create_demo_org], input[name=create_demo_users]' ).removeAttr( 'disabled' );
	}
	else
	{
		jQuery( 'input[name=create_demo_org], input[name=create_demo_users]' ).attr( 'disabled', true );
	}
}

jQuery( 'input[name=create_demo_contents]' ).click( updateDemoContentInputs );
jQuery( 'input[name=advanced_perms]' ).click( function()
{	// Display a proper label for "Allow access to" depending on selected "Permission management":
	if( jQuery( this ).val() == '1' )
	{	// If advanced permissions are selected
		jQuery( '#allow_access_members_simple_title, #allow_access_members_simple_note' ).hide();
		jQuery( '#allow_access_members_advanced_title, #allow_access_members_advanced_note' ).show();
	}
	else
	{	// If simple permissions are selected
		jQuery( '#allow_access_members_simple_title, #allow_access_members_simple_note' ).show();
		jQuery( '#allow_access_members_advanced_title, #allow_access_members_advanced_note' ).hide();
	}
} );


updateDemoContentInputs();

jQuery( '#blog_name' ).keyup( function()
{	// Count characters of collection title(each html entity is counted as single char):
	jQuery( '#blog_name_chars_count' ).html( jQuery( this ).val().replace( /&[^;\s]+;/g, '&' ).length );
} );

jQuery( '#duplicate_items' ).click( function()
{	// Disable option for comments duplicating when items duplicating is disabled:
	jQuery( '#duplicate_comments' ).prop( 'disabled', ! jQuery( this ).is( ':checked' ) );
} );

jQuery( '#blog_shortdesc' ).keyup( function()
{	// Count characters of meta short description(each html entity is counted as single char):
	jQuery( '#blog_shortdesc_chars_count' ).html( jQuery( this ).val().replace( /&[^;\s]+;/g, '&' ).length );
} );
</script>
