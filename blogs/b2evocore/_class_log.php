<?php
/**
 * This file implements the Log class, which logs notes and errors.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */

class Log
{
	var $messages;

	/**
	 * Constructor.
	 *
	 * @param string sets default level
	 */
	function Log( $level = 'error' )
	{
		$this->defaultlevel = $level;

		// create the array for this level
		$this->messages[$level] = array();
	}


	/**
	 * clears the Log
	 *
	 * @param string level, use 'all' to unset all levels
	 */
	function clear( $level = '#' )
	{
		if( $level == 'all' )
		{
			unset( $this->messages );
		}
		else
		{
			if( $level == '#' )
			{
				$level = $this->defaultlevel;
			}
			unset( $this->messages[ $level ] );
		}
	}


	/**
	 * add a message to the Log
	 *
	 * @param string the message
	 * @param string the level, default is to use the object's default level
	 */
	function add( $message, $level = '#' )
	{
		if( $level == '#' )
		{
			$level = $this->defaultlevel;
		}

		$this->messages[ $level ][] = $message;
	}


	/**
	 * display messages of the Log object.
	 *
	 * @param string header/title
	 * @param string footer
	 * @param boolean to display or return
	 * @param string the level of messages to use
	 * @param string the CSS class of the outer <div>
	 * @param string the style to use, '<ul>' (with <li> for every message) or everything else for '<br />'
	 */
	function display( $head, $foot = '', $display = true, $level = NULL, $cssclass = NULL, $style = '<ul>' )
	{
		$messages = & $this->messages( $level, true );

		if( !count($messages) )
		{
			return false;
		}

		if( $level === NULL )
		{
			$level = $this->defaultlevel;
		}

		if( $cssclass === NULL )
		{
			$cssclass = 'log_'.$level;
		}

		$disp = "\n<div class=\"$cssclass\">";

		if( !empty($head) )
		{ // header
			$disp .= '<h2>'.$head.'</h2>';
		}

		if( $style == '<ul>' )
		{
			if( count($messages) == 1 )
			{ // switch to <br>-style
				$style = '<br>';
			}
			else
			{ // open list
				$disp .= '<ul>';
			}
		}

		// implode messages
		if( $style == '<ul>' )
		{
			$disp .= '<li>'.implode( '</li><li>', $messages ).'</li>';
		}
		else
		{
			$disp .= implode( '<br />', $messages );
		}

		if( $style == '<ul>' )
		{ // close list
			$disp .= '</ul>';
		}

		if( !empty($foot) )
		{
			$disp .= '<p>'.$foot.'</p>';
		}

		$disp .= '</div>';

		if( $display )
		{
			echo $disp;
			return true;
		}

		return $disp;
	}


	/**
	 * Display messages of the Log object (conditional header/footer on message count).
	 *
	 * @param string header/title (if one message)
	 * @param string header/title (if more than one message)
	 * @param string footer (if one message)
	 * @param string footer (if more than one message)
	 * @param boolean to display or return
	 * @param string the level of messages to use
	 * @param string the style to use, '<ul>' (with <li> for every message) or everything else for '<br />'
	 */
	function display_cond( $head1, $head2, $foot1 = '', $foot2 = '', $display = true, $level = NULL, $cssclass = NULL, $style = '<ul>' )
	{
		switch( $this->count( $level ) )
		{
			case 0:
				return false;

			case 1:
				return $this->display( $head1, $foot1, $display, $level, $cssclass, $style );

			default:
				return $this->display( $head2, $foot2, $display, $level, $cssclass, $style );
		}
	}


	/**
	 * Concatenates messages of a given level to a string
	 *
	 * @param string prefix of the string
	 * @param string suffic of the string
	 * @param string the level
	 * @return string the messages, imploded. Tags stripped.
	 */
	function string( $head, $foot, $level = '#' )
	{
		if( !$this->count( $level ) )
		{
			return false;
		}

		$r = '';
		if( '' != $head )
			$r .= $head.' ';
		$r .= implode(', ', $this->messages( $level, true ));
		if( '' != $foot )
			$r .= ' '.$foot;

		return strip_tags( $r );
	}


	/**
	 * counts messages of a given level
	 *
	 * @param string the level
	 * @return number of messages
	 */
	function count( $level = '#' )
	{
		return count( $this->messages( $level, true ) );
	}


	/**
	 * returns array of messages of that level
	 *
	 * @param string the level
	 * @param boolean force one dimension
	 * @return array of messages, one-dimensional for a specific level, two-dimensional for level 'all' (depends on second param)
	 */
	function messages( $level = '#', $forceonedimension = false )
	{
		$messages = array();

		// sort by level ('error' above 'note')
		$ksortedmessages = $this->messages;
		ksort( $ksortedmessages );

		if( $level == 'all' )
		{
			foreach( $ksortedmessages as $llevel => $lmsgs )
			{
				foreach( $lmsgs as $lmsg )
				{
					if( $forceonedimension )
					{
						$messages[] = $lmsg;
					}
					else
					{
						$messages[$llevel][] = $lmsg;
					}
				}
			}
		}
		else
		{
			if( $level == '#' )
			{
				$level = $this->defaultlevel;
			}

			if( isset($this->messages[$level]) )
			{ // we have messages for this level
				$messages = $this->messages[$level];
			}
		}

		return $messages;
	}

}

?>