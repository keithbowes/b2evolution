<?php
/**
 * This file implements the UI view for the cron job form.
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

global $localtimenow, $edited_Cronjob;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

$Form = new Form( NULL, 'cronjob' );

$Form->global_icon( TB_('Cancel').'!', 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ? TB_('New scheduled job') : TB_('Edit scheduled job') );

	$Form->add_crumb( 'crontask' );
	$Form->hiddens_by_key( get_memorized( 'action' ) );
	$Form->hidden( 'action', $creating ? 'create' : 'update' );

	$Form->begin_fieldset( TB_('Job details').get_manual_link('scheduled-job-form') );

		if( $creating && $action != 'copy' )
		{ // New cronjob
			$cron_jobs_names = get_cron_jobs_config( 'name' );
			// Exclude these cron jobs from manual creating
			unset( $cron_jobs_names['send-post-notifications'] );
			unset( $cron_jobs_names['send-comment-notifications'] );
			unset( $cron_jobs_names['send-email-campaign'] );
			$Form->select_input_array( 'cjob_type', get_param( 'cjob_type' ), $cron_jobs_names, TB_('Job type') );
		}
		else
		{ // Edit cronjob
			if( $action == 'edit' )
			{
				$Form->info( TB_('Job #'), $edited_Cronjob->ID );
			}

			$Form->info( TB_('Default job name'), cron_job_name( $edited_Cronjob->key, '', $edited_Cronjob->params ) );

			$Form->text_input( 'cjob_name', $edited_Cronjob->name, 50, TB_('Job name'), '', array( 'maxlength' => 255 ) );
		}

		$Form->begin_line( TB_('Schedule date'), 'cjob_date', '', array( 'required' => true ) );

			$Form->date_input( 'cjob_date', date2mysql( $edited_Cronjob->start_timestamp ), '', array( 'required' => true ) );

			echo ' '.TB_('at').' ';

			$Form->time_input( 'cjob_time', date2mysql( $edited_Cronjob->start_timestamp ), '', array( 'required' => true ) );

		$Form->end_line();

		$Form->begin_line( TB_('Repeat every') );
			$Form->duration_input( 'cjob_repeat_after', $edited_Cronjob->repeat_after, '', 'days', 'minutes', array( 'minutes_step' => 1 ) );

			if( $edited_Cronjob->key == 'poll-antispam-blacklist' )
			{ // Don't allow to edit this param for cron job "Poll the antispam blacklist"
				$Form->info( TB_('+/- variation of:'), TB_('Auto') );
			}
			else
			{
				$Form->duration_input( 'cjob_repeat_variation', $edited_Cronjob->repeat_variation, TB_('+/- variation of:'), 'days', 'minutes', array( 'minutes_step' => 1 ) );
			}
		$Form->end_line();

	$Form->end_fieldset();

	if( !$creating )
	{	// We can edit only pending cron jobs, Show this field just for info
		$Form->begin_fieldset( TB_('Execution details').get_manual_link('scheduled-job-execution-details') );

			$Form->info( TB_('Status'), 'pending' );

		$Form->end_fieldset();
	}

$Form->end_form( array( array( 'submit', 'submit', $creating ? TB_('Create') : TB_('Save Changes!'), 'SaveButton' ) ) );

?>
