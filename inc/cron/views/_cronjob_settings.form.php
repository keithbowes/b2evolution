<?php
/**
 * This file implements the UI view for the settings of cron jobs.
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
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $Settings, $admin_url;

$Form = new Form( NULL, 'cron_settings_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'cronsettings' );
$Form->hidden( 'ctrl', 'crontab' );
$Form->hidden( 'tab', get_param( 'tab' ) );
$Form->hidden( 'action', 'settings' );

$cron_jobs = get_cron_jobs_config( 'name' );
foreach( $cron_jobs as $cron_job_key => $cron_job_name )
{
	$Form->begin_fieldset( $cron_job_name.cron_job_manual_link( $cron_job_key ) );

		// Additional settings per cron job:
		switch( $cron_job_key )
		{
			case 'send-email-campaign':
				// Send a chunk of x emails for the campaign:
				if( check_user_perm( 'emails', 'edit' ) )
				{	// Allow to edit email cron settings only if user has a permission:
					$Form->text_input( 'email_campaign_chunk_size', $Settings->get( 'email_campaign_chunk_size' ), 5, TB_('Chunk Size'), TB_('emails at a time'), array( 'maxlength' => 10 ) );
				}
				elseif( check_user_perm( 'emails', 'view' ) )
				{	// Only display setting value:
					$Form->info( TB_('Chunk Size'), $Settings->get( 'email_campaign_chunk_size' ), TB_('emails at a time') );
				}
				$Form->duration_input( 'email_campaign_cron_repeat', $Settings->get( 'email_campaign_cron_repeat' ), TB_('Delay between chunks'), 'days', 'minutes', array( 'note' => TB_('timing between scheduled job runs') ) );
				$Form->duration_input( 'email_campaign_cron_limited', $Settings->get( 'email_campaign_cron_limited' ), TB_('Delay in case all remaining recipients have reached max # of emails for the current day'), 'days', 'minutes', array( 'note' => TB_('timing between scheduled job runs') ) );
				if( check_user_perm( 'emails', 'edit' ) )
				{	// Allow to edit email cron settings only if user has a permission:
					$Form->text_input( 'email_campaign_max_domain', $Settings->get( 'email_campaign_max_domain' ), 5, TB_('Max emails to same domain'), TB_('In each chunk, avoid sending too many emails to same recipient domain (Useful to avoid balcklisting from gmail.com, hotmail.com, etc.)'), array( 'maxlength' => 10 ) );
				}
				elseif( check_user_perm( 'emails', 'view' ) )
				{	// Only display setting value:
					$Form->info( TB_('Max emails to same domain'), $Settings->get( 'email_campaign_max_domain' ) );
				}
				break;

			case 'prune-old-hits-and-sessions':
				// Prune old hits & sessions (includes OPTIMIZE):
				$oldest_session_period = max( $Settings->get( 'auto_prune_stats' ) * 86400, $Settings->get( 'timeout_sessions' ) );
				$Form->text_input( 'auto_prune_stats', $Settings->get( 'auto_prune_stats' ), 5, TB_('Keep detailed logs for'), TB_('days').'. '.sprintf( TB_('Note: <a %s>logged-in Sessions</a> will be kept for %s.'), 'href="'.$admin_url.'?ctrl=usersettings"', seconds_to_period( $oldest_session_period ) ) );
				break;

			case 'prune-recycled-comments':
				// Prune recycled comments:
				$Form->text_input( 'auto_empty_trash', $Settings->get( 'auto_empty_trash' ), 5, TB_('Prune recycled comments after'), TB_('days').'.' );
				break;

			case 'cleanup-scheduled-jobs':
				// Clean up scheduled jobs older than a threshold:
				$Form->text_input( 'cleanup_jobs_threshold', $Settings->get( 'cleanup_jobs_threshold' ), 5, TB_('Keep normally finished tasks for'), TB_('days').'. '.TB_('The successfully finished scheduled jobs older than the selected number of days will be removed.') );
				$Form->text_input( 'cleanup_jobs_threshold_failed', $Settings->get( 'cleanup_jobs_threshold_failed' ), 5, TB_('Keep other tasks for'), TB_('days').'. '.TB_('The failed scheduled jobs older than the selected number of days will be removed.') );
				break;

			case 'cleanup-email-logs':
				// Clean up email logs older than a threshold:
				$Form->duration_input( 'cleanup_email_logs_threshold', $Settings->get( 'cleanup_email_logs_threshold' ), TB_('Delete after'), 'days', 'minutes' );
				break;

			case 'send-non-activated-account-reminders':
				// Send reminders about non-activated accounts:
				$Form->duration_input( 'activate_account_reminder_threshold', $Settings->get( 'activate_account_reminder_threshold' ), TB_('Trigger after'), 'days', 'minutes', array( 'note' => TB_('A user will receive Account activation reminders if his account has remained non-activated for the selected period.') ) );
				// Get array of account activation reminder settings:
				$activate_account_reminder_config = $Settings->get( 'activate_account_reminder_config' );
				$config_count = count( $activate_account_reminder_config );
				$reminder_config_label = TB_('Reminder #%d');
				$reminder_config_params = array(
						'note'             => TB_('After last notification'),
						'none_title_label' => TB_('Don\'t send'),
					);
				foreach( $activate_account_reminder_config as $c => $config_value )
				{
					if( $c == $config_count - 3 )
					{	// This option is used for failed activation threshold:
						$Form->duration_input( 'activate_account_reminder_config_'.$c, 0, sprintf( $reminder_config_label, $c + 1 ), '', '', $reminder_config_params );
						$Form->duration_input( 'activate_account_reminder_config_'.( $c + 1 ), $config_value, TB_('Mark as Failed / Pending delete'), '', '', array_merge( $reminder_config_params, array(
								'allow_none_value' => false,
								'allow_none_title' => false,
							) ) );
					}
					elseif( $c == $config_count - 2 )
					{	// This option is used for delete warning threshold:
						$Form->duration_input( 'activate_account_reminder_config_'.( $c + 1 ), $config_value, TB_('Delete warning'), '', '', array_merge( $reminder_config_params, array(
								'note' => TB_('After marking as "Pending Delete"'),
							) ) );
					}
					elseif( $c == $config_count - 1 )
					{	// Last option is used for delete account threshold:
						$Form->duration_input( 'activate_account_reminder_config_'.( $c + 1 ), $config_value, TB_('Delete account'), '', '', array(
								'note'             => TB_('After last notification/warning'),
								'none_title_label' => TB_('Don\'t delete'),
							) );
					}
					else
					{	// Not last options for reminders:
						$Form->duration_input( 'activate_account_reminder_config_'.$c, $config_value, sprintf( $reminder_config_label, $c + 1 ), '', '', $reminder_config_params );
					}
				}
				$Form->hidden( 'activate_account_reminder_config_num', $config_count );
				break;

			case 'send-inactive-account-reminders':
				$Form->duration_input( 'inactive_account_reminder_threshold', $Settings->get( 'inactive_account_reminder_threshold' ), TB_('Trigger every'), 'days', 'minutes', array( 'note' => TB_('An inactive account is an account that has been activated but the user hasn\'t connected for an extended period.') ) );
				break;

			case 'send-unmoderated-comments-reminders':
				// Send reminders about comments awaiting moderation:
				$Form->duration_input( 'comment_moderation_reminder_threshold', $Settings->get( 'comment_moderation_reminder_threshold' ), TB_('Trigger after'), 'days', 'minutes', array( 'note' => TB_('A moderator will receive Comment moderation reminders if there are comments awaiting moderation at least as old as the selected period.') ) );
				break;

			case 'send-unmoderated-posts-reminders':
				// Send reminders about posts awaiting moderation:
				$Form->duration_input( 'post_moderation_reminder_threshold', $Settings->get( 'post_moderation_reminder_threshold' ), TB_('Trigger after'), 'days', 'minutes', array( 'note' => TB_('A moderator will receive Post moderation reminders if there are posts awaiting moderation at least as old as the selected period.') ) );
				break;

			case 'send-unread-messages-reminders':
				// Send reminders about unread messages:
				$Form->duration_input( 'unread_message_reminder_threshold', $Settings->get( 'unread_message_reminder_threshold' ), TB_('Trigger after'), 'days', 'minutes', array( 'note' => TB_('A user will receive unread message reminders if it has unread private messages at least as old as the selected period.') ) );
				// Get array of the unread private messages reminder delay settings:
				$unread_message_reminder_delay = $Settings->get( 'unread_message_reminder_delay' );
				$config_count = count( $unread_message_reminder_delay );
				$d = 1;
				foreach( $unread_message_reminder_delay as $delay_day => $delay_spacing )
				{
					$n = ( $d == $config_count ? ( 11 -  $config_count ) : 1 );
					for( $i = 0; $i < $n; $i++ )
					{
						$Form->begin_line( sprintf( TB_('Reminder #%d'), $d ) );
							$Form->text_input( 'unread_message_reminder_delay_day_'.$d, $i == 0 ? $delay_day : '', 3,
								/* TRANS: Full string is "Reminder #1: if user was not logged in last X days then sent every X days"*/TB_('if user was not logged in last'), '', array(
									'input_suffix' => ' '.TB_('days'),
									'maxlength'    => 10,
								) );
							$Form->text_input( 'unread_message_reminder_delay_spacing_'.$d, $i == 0 ? $delay_spacing : '', 3,
								/* TRANS: Full string is "Reminder #1: if user was not logged in last X days then sent every X days"*/TB_('then sent every'), '', array(
									'input_suffix' => ' '.TB_('days'),
									'note'         => TB_('Leave empty if you don\'t want to use this reminder.'),
									'maxlength'    => 5,
								) );
						$Form->end_line();
						$d++;
					}
				}
				break;

			case 'manage-email-statuses':
				// Manage email address statuses:
				$Form->duration_input( 'manage_email_statuses_min_delay', $Settings->get( 'manage_email_statuses_min_delay' ), TB_('Minimum delay since last error'), 'days', 'minutes', array( 'note' => TB_('<code>Warning</code> or <code>Suspicious</code> addresses may return to <code>Unknown</code> after this delay.') ) );
				$Form->text_input( 'manage_email_statuses_min_sends', $Settings->get( 'manage_email_statuses_min_sends' ), 5, TB_('Minimum sends since last error'), TB_('<code>Warning</code> or <code>Suspicious</code> addresses may return to <code>Unknown</code> after this number of new (presumed successful) email sends.'), array( 'type' => 'number', 'min' => 1, 'max' => 999999999 ) );
				break;
		}

		$Form->duration_input( 'cjob_timeout_'.$cron_job_key, $Settings->get( 'cjob_timeout_'.$cron_job_key ), TB_('Max execution time'), 'days', 'minutes', array( 'note' => TB_( 'Leave empty for no limit' ) ) );

		if( $Settings->get( 'cjob_maxemail_'.$cron_job_key ) !== NULL )
		{	// Setting only for cron jobs that use email sending:
			$Form->text_input( 'cjob_maxemail_'.$cron_job_key, $Settings->get( 'cjob_maxemail_'.$cron_job_key ), 10, TB_('Max emails to send'), TB_('Leave empty for no limit'), array( 'type' => 'number', 'min' => 0 ) );
		}

		if( $Settings->get( 'cjob_imap_error_'.$cron_job_key ) !== NULL )
		{	// Setting only for cron jobs that use IMAP email sending:
			$Form->text_input( 'cjob_imap_error_'.$cron_job_key, $Settings->get( 'cjob_imap_error_'.$cron_job_key ), 5, TB_('Do not notify IMAP errors before'), '', array( 'input_suffix' => ' '.TB_('consecutive errors'), 'type' => 'number', 'min' => 1 ) );
		}

	$Form->end_fieldset();
}

$buttons = array();
if( check_user_perm( 'options', 'edit' ) )
{	// Allow to save cron settings only if user has a permission:
	$buttons[] = array( 'submit', '', TB_('Save Changes!'), 'SaveButton' );
}

$Form->end_form( $buttons );
?>