<?php
/**
 * Locale definition
 *
 * IMPORTANT: Try to keep the locale names short, they take away valuable space on the screen!
 *
 * Documentation of the keys:
 *  - 'messages': The directory where the locale's files are. (may seem redundant but allows to have fr-FR and fr-CA
 *                tap into the same language file.)
 *  - 'charset':  Character set of the locale's messages files.
 *  - 'transliteration_map': An array of non-ASCII chars and their transliterated ASCII equivalents.
 *                           It's used in replace_special_chars() to find and replace non-ASCII chars in post and category slugs.
 *                           You can define your own pattern, e.g. to replace "U" with "you" use 'U'=>'you'.
 *
 */

/* Display the encoded transliteration to insert in the database when running from CLI.
 * Otherwise, display nothing. */
if (function_exists('NT_'))
{
	function get_encoded_translit($str)
	{
	}
}
else
{
	function NT_($str)
	{
		return $str;
	}

	function get_encoded_translit($str)
	{
		echo base64_encode(serialize($str)) . "\n";
	}

}

$locale_defs['eo'] = array(
		'name' => NT_('Esperanto UTF-8'),
		'charset' => 'UTF-8',
		'datefmt' => 'Y-M-d',
		'timefmt' => 'H:i:s',
		'shorttimefmt' => 'H:i',
		'startofweek' => 0,
		'messages' => 'eo',
		/* When no iconv, transliterate as if there were */
		'transliteration_map' => array(
			'ĉ' => 'cx', 'ĝ' => 'gx', 'ĥ' => 'hx', 'ĵ' => 'jx', 'ŝ' => 'sx', 'ŭ' => 'ux',
			'Ĉ' => 'Cx', 'Ĝ' => 'Gx', 'Ĥ' => 'Hx', 'Ĵ' => 'Jx', 'Ŝ' => 'Sx', 'Ŭ' => 'Ux',
			'«' => '', '»' => '', '„' => '', '”' => '', '‚' => '', '’' => '',
		),
	);

get_encoded_translit($locale_defs['eo']['transliteration_map']);
?>
