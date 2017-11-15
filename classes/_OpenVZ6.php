<?php
/**
 * Author: Albert Thalidzhokov
 */

/**
 * Class _OpenVZ6
 */
class _OpenVZ6
{
	const TYPE = 'openvz6';
	const PORT = 22;
	const TEMPLATES_URL = 'https://download.openvz.org/template/precreated/';

	/**
	 * @param string $host
	 * @return bool
	 */
	public static function ping($host = '')
	{
		$status = False;

		if (
			filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ||
			filter_var($host, FILTER_SANITIZE_URL)
		) {
			$fp = fsockopen($host, self::PORT, $errcode, $errstr, 1);

			if ($fp) {
				$status = True;
			}

			fclose($fp);
		}

		return $status;
	}

	/**
	 * @param int $serverGroup
	 * @return array
	 */
	public static function servers($serverGroup = 0)
	{
		$servers = [];

		if (is_numeric($serverGroup)) {
			$query = 'SELECT * FROM `tblservers` WHERE `type` = "' . self::TYPE . '"';
			$results = mysql_query($query);

			while (($row = mysql_fetch_assoc($results)) !== False) {
				$servers += $row;
			}
		}

		return $servers;
	}

	/**
	 * Get OS Templates from openvz.org
	 * @return array
	 */
	public static function templates()
	{
		$templates = [];
		$file = file_get_contents(self::TEMPLATES_URL);
		$pattern = '/<a href="(.+)\.tar\.gz">(.+)\.tar\.gz<\/a>/mu';
		preg_match_all($pattern, $file, $matches);

		if (!empty($matches[1]) && !empty($matches[2])) {
			$templates = array_intersect($matches[1], $matches[2]);
			$templates = array_unique($templates);
		}

		return $templates;
	}

	/**
	 * @param int $pid
	 * @param string $fieldName
	 * @param string $fieldType
	 * @param array $fieldOptions
	 * @param bool $adminOnly
	 * @return array|mixed
	 */
	public static function productField($pid = 0, $fieldName = '', $fieldType = '', $fieldOptions = [], $adminOnly = False)
	{
		$field = [];

		if (!empty($pid) && is_numeric($pid) &&
			!empty($fieldName) && is_string($fieldName)
		) {
			$field = self::getProductField($pid, $fieldName, $fieldType);

			if (!$field) {
				self::addProductField($pid, $fieldName, $fieldType, $fieldOptions, $adminOnly);
				$field = self::getProductField($pid, $fieldName, $fieldType);
			}
		}

		return $field;
	}

	/**
	 * @param int $pid
	 * @param string $fieldName
	 * @param string $fieldType
	 * @return array|mixed
	 */
	public static function getProductField($pid = 0, $fieldName = '', $fieldType = '')
	{
		$field = [];
		$fields = [];

		if ($fieldName && filter_var($fieldName, FILTER_SANITIZE_STRING)) {

			$query = 'SELECT * FROM `tblcustomfields` WHERE `type` = "product" AND `fieldname` = "' . $fieldName . '"';

			if ($pid && is_numeric($pid)) {
				$query .= ' AND `relid` = "' . $pid . '"';
			}

			if ($fieldType && is_string($fieldType)) {
				$fieldType = filter_var($fieldType, FILTER_SANITIZE_STRING);
				$query .= ' AND `fieldtype` = "' . $fieldType . '"';
			}

			$results = mysql_query($query);

			while (($row = mysql_fetch_assoc($results)) !== False) {
				$fields[] = $row;
			}

			if (count($fields) > 0) {
				$field = $fields[0];
			}
		}

		return $field;
	}

	/**
	 * @param int $pid
	 * @param string $fieldName
	 * @param string $fieldType
	 * @param array $fieldOptions
	 * @param bool $adminOnly
	 */
	public static function addProductField($pid = 0, $fieldName = '', $fieldType = '', $fieldOptions = [], $adminOnly = False)
	{

		if ($pid && is_numeric($pid) &&
			$fieldName && is_string($fieldName)
		) {
			$fieldName = filter_var($fieldName, FILTER_SANITIZE_STRING);

			if ($fieldType && is_string($fieldType)) {
				$fieldType = filter_var($fieldType, FILTER_SANITIZE_STRING);
			} else {
				$fieldType = 'text';
			}

			if ($fieldOptions && is_array($fieldOptions)) {
				$fieldOptions = implode(',', $fieldOptions);
				$fieldOptions = filter_var($fieldOptions, FILTER_SANITIZE_STRING);
			} else {
				$fieldOptions = '';
			}

			$fieldsArr = [
				'type' => 'product',
				'relid' => $pid,
				'fieldname' => $fieldName,
				'fieldtype' => $fieldType,
				'fieldoptions' => $fieldOptions,
				'adminonly' => $adminOnly ? 'on' : '',
				'sortorder' => 0
			];
			$fields = '';
			$values = '';

			foreach ($fieldsArr as $fKey => $f) {

				if ($fields) {
					$fields .= ',';
				}

				if ($values) {
					$values .= ', ';
				}

				$fields .= '`' . $fKey . '`';
				$values .= '"' . $f . '"';
			}

			$query = 'INSERT INTO `tblcustomfields` (' . $fields . ') VALUES (' . $values . '); ';
			mysql_query($query);
		}
	}

	/**
	 * @param int $fieldId
	 * @param int $serviceId
	 * @return array|mixed
	 */
	public static function getProductFieldValue($fieldId = 0, $serviceId = 0)
	{
		$field = [];

		if ($fieldId && is_numeric($fieldId) && $serviceId && is_numeric($serviceId)) {
			$query = 'SELECT * FROM `tblcustomfieldsvalues` WHERE `fieldid` = "' . $fieldId . '" AND `relid` = "' . $serviceId . '"';
			$results = mysql_query($query);
			$fields = [];

			while (($row = mysql_fetch_assoc($results)) !== False) {
				$fields[] = $row;
			}
			if (count($fields) > 0) {
				$field = $fields[0];
			}
		}

		return $field;
	}

	/**
	 * @param int $fieldId
	 * @param int $serviceId
	 * @param string $value
	 */
	public static function addProductFieldValue($fieldId = 0, $serviceId = 0, $value = '')
	{

		if (
			$fieldId && is_numeric($fieldId) &&
			$serviceId && is_numeric($serviceId) &&
			$value && is_string($value)
		) {
			$fields = '`fieldid`, `relid`, `value`';
			$values = '"' . $fieldId . '", "' . $serviceId . '", "' . $value . '")';
			$query = 'INSERT INTO `tblcustomfieldsvalues` (' . $fields . ') VALUES(' . $values . ')';
			mysql_query($query);
		}
	}

	/**
	 * @return mixed|string
	 */
	public static function generatePassword()
	{
		$text = <<<HTML


Man in black

Well, you wonder why I always dress in black,
Why you never see bright colors on my back,
And why does my appearance seem to have a somber tone.
Well, there's a reason for the things that I have on.

I wear the black for the poor and the beaten down,
Livin' in the hopeless, hungry side of town,
I wear it for the prisoner who has long paid for his crime,
But is there because he's a victim of the times.

I wear the black for those who never read,
Or listened to the words that Jesus said,
About the road to happiness through love and charity,
Why, you'd think He's talking straight to you and me.

Well, we're doin' mighty fine, I do suppose,
In our streak of lightnin' cars and fancy clothes,
But just so we're reminded of the ones who are held back,
Up front there ought 'a be a Man In Black.

I wear it for the sick and lonely old,
For the reckless ones whose bad trip left them cold,
I wear the black in mournin' for the lives that could have been,
Each week we lose a hundred fine young men.

And, I wear it for the thousands who have died,
Believen' that the Lord was on their side,
I wear it for another hundred thousand who have died,
Believen' that we all were on their side.

Well, there's things that never will be right I know,
And things need changin' everywhere you go,
But 'til we start to make a move to make a few things right,
You'll never see me wear a suit of white.

Ah, I'd love to wear a rainbow every day,
And tell the world that everything's OK,
But I'll try to carry off a little darkness on my back,
'Till things are brighter, I'm the Man In Black


Ghost riders in the sky

An old cowboy went riding out one dark and windy day
Upon a ridge he rested as he went along his way
When all at once a mighty herd of red eyed cows he saw
A-plowing through the ragged sky and up the cloudy draw
Their brands were still on fire and their hooves were made of steel
Their horns were black and shiny and their hot breath he could feel
A bolt of fear went through him as they thundered through the sky
For he saw the riders coming hard and he heard their mournful cry

Yippie yi ooh
Yippie yi yay
Ghost riders in the sky

Their faces gaunt, their eyes were blurred, their shirts all soaked with sweat
He's riding hard to catch that herd, but he ain't caught 'em yet
'Cause they've got to ride forever on that range up in the sky
On horses snorting fire
As they ride on hear their cry
As the riders loped on by him he heard one call his name
If you want to save your soul from hell a-riding on our range
Then cowboy change your ways today or with us you will ride
Trying to catch the devil's herd, across these endless skies

Yippie yi ooh
Yippie yi yay
Ghost riders in the sky
Ghost riders in the sky
Ghost Riders in the sky


HTML;

		$arr = explode("\n", $text);
		$arr = array_filter($arr);
		$arr = array_unique($arr, SORT_REGULAR);

		foreach ($arr as $elemKey => $elem) {

			if (strlen($elem) > 10) {
				$elem = explode(' ', $elem);
				$arr[$elemKey] = array_filter($elem);
			} else {
				unset($arr[$elemKey]);
			}
		}

		$arr = array_values($arr);

		$iMax = count($arr) - 1;
		$i = rand(0, $iMax);
		$el = $arr[$i];

		$jMax = count($el) - 1;
		$j = rand(0, $jMax);
		$passwd = '';
		$passwdLen = 0;


		while ($passwdLen < 15) {

			if ($j >= $jMax) {
				$passwd .= rand(100, 999);
				$j = 0;
			}

			$k = rand(0, 1);

			if ($k === 0) {
				$passwd .= strtolower($el[$j]);

			} else {
				$passwd .= strtoupper($el[$j]);
			}

			$passwdLen = strlen($passwd);
			$j++;
		}

		$passwd = preg_replace('/[^A-Za-z0-9]+/', rand(10, 99), $passwd);

		return $passwd;
	}

	/**
	 * @param int $serverId
	 * @return array
	 */
	public static function getAvailableIp($serverId = 0)
	{
		$ips = [
			4 => [],
			6 => []
		];
		$ip = [
			4 => '',
			6 => ''
		];

		if ($serverId && is_numeric($serverId)) {
			$query = 'SELECT * FROM  `tblservers` WHERE  `id` = "' . $serverId . '"';
			$results = mysql_query($query);
			$serverIps = [
				4 => [],
				6 => []
			];

			while (($row = mysql_fetch_assoc($results)) !== False) {
				$assignedIps = $row['assignedips'];

				if ($assignedIps) {
					$assignedIps = self::assignedIps($assignedIps);
					$serverIps[4] = array_merge($serverIps[4], $assignedIps[4]);
					$serverIps[6] = array_merge($serverIps[6], $assignedIps[6]);
				}
			}

			foreach ($serverIps as $sKey => $s) {
				$s = array_unique($s);
				$s = array_values($s);
				$serverIps[$sKey] = $s;
			}

			$query = 'SELECT * FROM `tblhosting` WHERE (`dedicatedip` <> "" OR `assignedips` <> "")';
			$results = mysql_query($query);
			$allIps = [
				4 => [],
				6 => []
			];

			while (($row = mysql_fetch_assoc($results)) !== False) {
				$dedicatedIp = trim($row['dedicatedip']);

				if ($dedicatedIp) {

					if (filter_var($dedicatedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
						$allIps[4][] = $dedicatedIp;
					} else if (filter_var($dedicatedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
						$allIps[6][] = $dedicatedIp;
					}
				}

				$assignedIps = $row['assignedips'];

				if ($assignedIps) {
					$assignedIps = self::assignedIps($assignedIps);
					$allIps[4] = array_merge($allIps[4], $assignedIps[4]);
					$allIps[6] = array_merge($allIps[6], $assignedIps[6]);
				}
			}

			foreach ($allIps as $aKey => $a) {
				$a = array_unique($a);
				$a = array_values($a);
				$allIps[$aKey] = $a;
			}

			foreach ($ips as $iKey => $i) {
				$ips[$iKey] = array_diff($serverIps[$iKey], $allIps[$iKey]);
				$ips[$iKey] = array_values($ips[$iKey]);
			}

			$ip[4] = !empty($ips[4][0]) ? $ips[4][0] : '';
			$ip[6] = !empty($ips[6][0]) ? $ips[6][0] : '';
			$cidr6 = explode('/', $ip[6]);

			if (count($cidr6) === 2) {
				$ip6 = 1000;
				$i = 0;

				while ($i < 1) {

					if (in_array($cidr6[0] . $ip6, $allIps)) {
						$ip6++;
					} else {
						// TODO: Not correct IPv6 generation
						$ip[6] = $cidr6[0] . $ip6;
						$i++;
					}
				}
			}
		}

		return $ip;
	}

	/**
	 * @param string $ip e.g. 192.168.1.118
	 * @param string $type Available: array, arrayBinary, binary OR bin, array6, arrayBinary6, hex
	 * @return array|string
	 */
	public static function ipTo($ip = '', $type = 'binary')
	{
		$rtn = '';

		if ($ip && is_string($ip)) {
			$ip = trim($ip);

			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
				$arr = explode(".", $ip);
				$arrBin = [];

				foreach ($arr as $p) {
					$arrBin[] = str_pad(decbin($p), 8, '0', STR_PAD_LEFT);
				}

				$bin = implode('', $arrBin);

				$arrBin6 = [
					$arrBin[0] . $arrBin[1],
					$arrBin[2] . $arrBin[3]
				];
				$arr6 = [];

				foreach ($arrBin6 as $p6) {
					$arr6[] = dechex(bindec($p6));
				}

				$hex = implode(':', $arr6);

				switch ($type) {
					case 'array':
						$rtn = $arr;
						break;

					case 'arrayBinary':
						$rtn = $arrBin;
						break;

					case 'bin':
					case 'binary':
						$rtn = $bin;
						break;

					case 'arrayBinary6':
						$rtn = $arrBin;
						break;

					case 'array6':
						$rtn = $arr;
						break;

					default:
					case 'hex':
						$rtn = $hex;
						break;
				}
			}
		}

		return $rtn;
	}

	/**
	 * @param array $range e.g. ['192.168.1.100', '192.168.1.118']
	 * @return array
	 */
	public static function rangeToCidr($range = [])
	{
		$cidr = [];

		$num = ip2long($range[1]) - ip2long($range[0]) + 1;
		$bin = decbin($num);

		$chunk = str_split($bin);
		$chunk = array_reverse($chunk);
		$start = 0;

		while ($start < count($chunk)) {
			if ($chunk[$start] != 0) {
				$ip = isset($range) ? long2ip(ip2long($range[1]) + 1) : $range[0];
				$range = self::rangeToCidr($ip . '/' . (32 - $start));
				$cidr[] = $ip . '/' . (32 - $start);
			}
			$start++;
		}

		return $cidr;
	}

	/**
	 * @param string $cidr e.g. 192.168.1.118/27
	 * @return array
	 */
	function cidrToRange($cidr = '')
	{
		$range = [];

		if ($cidr && is_string($cidr)) {
			$cidr = explode('/', $cidr);

			if (count($cidr) === 2) {
				$cidr[0] = trim($cidr[0]);
				$cidr[1] = trim($cidr[1]);

				if (filter_var($cidr[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
					$range[0] = long2ip((ip2long($cidr[0])) & ((-1 << (32 - (int)$cidr[1]))));
					$range[1] = long2ip((ip2long($range[0])) + pow(2, (32 - (int)$cidr[1])) - 1);
				}
			}
		}
		return $range;
	}

	/**
	 * @param string $cidr e.g. 192.168.1.118/27
	 * @return array
	 */
	function cidrToArray($cidr = '')
	{
		$ips = [];

		if ($cidr && is_string($cidr)) {
			$cidr = explode('/', $cidr);

			if (count($cidr) === 2) {
				$cidr[0] = trim($cidr[0]);
				$cidr[1] = trim($cidr[1]);

				if (filter_var($cidr[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
					$bin = '';

					for ($i = 1; $i <= 32; $i++) {
						$bin .= $cidr[1] >= $i ? '1' : '0';
					}

					$cidr[1] = bindec($bin);

					$ip = ip2long($cidr[0]);
					$nm = $cidr[1];
					$nw = $ip & $nm;
					$bc = $nw | ~$nm;
					$bcLong = ip2long(long2ip($bc));

					for ($i = 1; $nw + $i <= $bcLong; $i++) {
						$ips[] = long2ip($nw + $i);
					}
				}
			}
		}

		return $ips;
	}

	/**
	 * @param string $assignedIps
	 * @return array
	 */
	public static function assignedIps($assignedIps = '')
	{
		$ips = [
			4 => [],
			6 => []
		];

		$assignedIps = explode("\n", $assignedIps);
		$assignedIps = array_filter($assignedIps);

		foreach ($assignedIps as $assignedIp) {
			$assignedIp = trim($assignedIp);

			if (filter_var($assignedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
				$ips[4][] = $assignedIp;
			} else if (filter_var($assignedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
				$ips[6][] = $assignedIp;
			} else {
				$cidr = explode('/', $assignedIp);

				if (count($cidr) === 2) {
					$cidr[0] = trim($cidr[0]);
					$cidr[1] = trim($cidr[1]);

					if (filter_var($cidr[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
						$cidrIps = self::cidrToArray($cidr[0] . '/' . $cidr[1]);

						foreach ($cidrIps as $cidrIp) {
							$ips[4][] = $cidrIp;
						}
					} else if (filter_var($cidr[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
						$ips[6][] = $cidr[0] . '/' . $cidr[1];
					}
				}
			}
		}

		return $ips;
	}

	/**
	 * @param string $exec
	 * @param array $successMsg
	 * @return string
	 */
	public static function validateMsg($exec = '', $successMsg = []) {
		$e = str_replace(
			"\n",
			' ',
			$exec);
		$e = preg_replace(
			"/(.+ IP addresses:) .+? (Setting .+)/",
			"\\1 (IPS) \\2",
			$e);
		$e = preg_replace(
			"/(.+ IP address\(es\):) .+? (Setting .+)/",
			"\\1 (IPS) \\2",
			$e);
		$e = preg_replace(
			"/(.+ CPU limit:) .+? (Setting .+)/",
			"\\1 (CPULIMIT) \\2",
			$e);
		$e = preg_replace(
			"/(.+ CPU units:) .+? (Setting .+)/",
			"\\1 (CPUUNITS) \\2",
			$e);
		$e = preg_replace(
			"/(.+ CPUs:) .+? (Container .+)/",
			"\\1 (CPUS) \\2",
			$e);

		$similarity = 0;

		foreach ($successMsg as $sKey => $s) {
			similar_text($s, $e, $percent);

			if ($percent > $similarity) {
				$similarity = $percent;
			}
		}

		return $similarity > 95 ? 'success' : $exec;
	}
}