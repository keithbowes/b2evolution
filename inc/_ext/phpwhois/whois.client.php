				}
			else
				$a1[$key] = $val;
			}

		return $a1;
	}

	function FixNameServer($nserver)
		{
		$dns = array();

		foreach($nserver as $val)
			{
			$val = str_replace( array('[',']','(',')'), '', trim($val));
			$val = str_replace("\t", ' ', $val);
			$parts = explode(' ', $val);
			$host = '';
			$ip = '';

			foreach($parts as $p)
				{
				if (substr($p,-1) == '.') $p = substr($p,0,-1);

				if ((ip2long($p) == - 1) or (ip2long($p) === false))
					{
					// Hostname ?
					if ($host == '' && preg_match('/^[\w\-]+(\.[\w\-]+)+$/',$p))
						{
						$host = $p;
						}
					}
				else
					// IP Address
					$ip = $p;
				}

			// Valid host name ?

			if ($host == '') continue;

			// Get ip address

			if ($ip == '')
				{
				$ip = gethostbyname($host);
				if ($ip == $host) $ip = '(DOES NOT EXIST)';
				}

			if (substr($host,-1,1) == '.') $host = substr($host,0,-1);

			$dns[strtolower($host)] = $ip;
			}

		return $dns;
		}
}
?>
