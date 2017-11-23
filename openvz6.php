<?php
/**
 * Description: Module for OpenVZ 6 integration
 * Author: Albert Thalidzhokov
 * URL: https://github.com/thalidzhokov/whmcs-openvz6
 */

if (!defined('WHMCS')) {
	die('This file cannot be accessed directly');
}

defined('PATH') or define('PATH', __DIR__);

require_once PATH . '/classes/WHMCS_OpenVZ6.php';

/**
 * @param $params https://developers.whmcs.com/provisioning-modules/module-parameters/
 * Module public methods
 */

/**
 * @return array
 */
function openvz6_MetaData()
{
	return [
		'DisplayName' => 'OpenVZ6',
		'APIVersion' => '1.1',
		'RequiresServer' => False,
		'DefaultSSLPort' => '22'
	];
}

/**
 * Setup > Products/services > Products/services > Edit > Module settings
 * @return array
 */
function openvz6_ConfigOptions()
{
	$configarray = [
		'ONBOOT' => [
			'Type' => 'dropdown',
			'Options' => [
				'yes' => 'Yes',
				'no' => 'No'
			],
			'Description' => 'yes or no'
		],
		'CPUUNITS' => [
			'Type' => 'text',
			'Default' => '1000',
			'Description' => 'Units, e.g. 1000'
		],
		'CPUS' => [
			'Type' => 'text',
			'Default' => '2',
			'Description' => ''
		],
		'CPULIMIT' => [
			'Type' => 'text',
			'Default' => '50',
			'Description' => 'Percentage, e.g. 50'
		],
		'PHYSPAGES' => [
			'Type' => 'text',
			'Default' => '2G',
			'Description' => 'RAM in MB or GB, e.g. 2G'
		],
		'SWAPPAGES' => [
			'Type' => 'text',
			'Default' => '2G',
			'Description' => 'Swap in MB or GB, e.g. 2G, max. 8G',
		],
		'DISKSPACE' => [
			'Type' => 'text',
			'Default' => '31G:32G',
			'Description' => 'Disk space in MB or GB, e.g. 31G:32G'
		],
		'DISKINODES' => [
			'Type' => 'text',
			'Default' => '3100000:3200000',
			'Description' => 'Disk nodes, e.g. 1 m. per 1GB, max. 10 m.'
		],
		'QUOTAUGIDLIMIT' => [
			'Type' => 'text',
			'Default' => '1000',
			'Description' => 'Recommend 1000'
		],
		'QUOTATIME' => [
			'Type' => 'text',
			'Default' => '0',
			'Description' => ''
		],
		/*
		'KMEMSIZE' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'LOCKEDPAGES' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'SHMPAGES' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'NUMPROC' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'VMGUARPAGES' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'OOMGUARPAGES' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'NUMTCPSOCK' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'NUMFLOCK' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'NUMPTY' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'NUMSIGINFO' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'TCPSNDBUF' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'TCPRCVBUF' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'OTHERSOCKBUF' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'DGRAMRCVBUF' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'NUMOTHERSOCK' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'DCACHESIZE' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'NUMFILE' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'AVNUMPROC' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		'NUMIPTENT' => [
			'Type' => 'text',
			'Default' => 'unlimited',
			'Description' => ''
		],
		*/
		'VE_LAYOUT' => [
			'Type' => 'dropdown',
			'Options' => [
				'simfs' => 'simfs',
				'ploop' => 'ploop'
			],
			'Description' => 'simfs or ploop'
		]
	];

	return $configarray;
}

/**
 * @param array $params
 * @return string
 */
function openvz6_CreateAccount($params = [])
{
	$serviceId = $params['serviceid'];
	$ctid = $params['customfields']['ctid'];

	if (empty($ctid) || !is_numeric($ctid)) {
		$ctid = _openvz6_generateCTID($params);
	}

	$template = $params['customfields']['template'];

	if (empty($template) || !is_string($template)) {
		$template = 'centos-6-x86_64-minimal';
	}

	$query = 'SELECT * FROM `tblhosting` WHERE `id` = "' . $serviceId . '"';
	$results = mysql_query($query);
	$ips = [];

	while (($row = mysql_fetch_assoc($results)) !== False) {
		$ips[] = $row['dedicatedip'];

		$assignedIps = trim($row['assignedips']);
		$assignedIps = explode("\n", $assignedIps);
		$assignedIps = array_filter($assignedIps);

		foreach ($assignedIps as $assignedIp) {
			$ips[] = trim($assignedIp);
		}
	}

	$serverId = $params['serverid'];

	if (empty($ips) || !is_array($ips)) {
		$productIps = WHMCS_OpenVZ6::getAvailableIp($serverId);
	} else {
		$productIps = $ips;
	}

	$packageId = $pid = $params['packageid'];

	// Create CT
	$execCreate = _openvz6_createCT($ctid, $template, $params);

	if ($execCreate === 'success') {
		// Write CTID into DB
		$ctidCF = WHMCS_OpenVZ6::getProductField($packageId, 'ctid', 'test');

		if (!empty($ctidCF['id']) && is_numeric($ctidCF['id'])) {
			$ctidCFVal = WHMCS_OpenVZ6::getProductFieldValue($ctidCF['id'], $serviceId);

			if (!empty($ctidCF['id']) && empty($ctidCFVal['value'])) {
				WHMCS_OpenVZ6::addProductFieldValue($ctidCF['id'], $serviceId, $ctid);
			}
		}

		// Write Template into DB
		$templateCF = WHMCS_OpenVZ6::getProductField($packageId, 'template', 'dropdown');

		if (!empty($templateCF['id']) && is_numeric($templateCF['id'])) {
			$templateCFVal = WHMCS_OpenVZ6::getProductFieldValue($templateCF['id'], $serviceId);

			if (!empty($templateCF['id']) && empty($templateCFVal['value'])) {
				WHMCS_OpenVZ6::addProductFieldValue($templateCF['id'], $serviceId, $ctid);
			}
		}

		// Set CT params
		$execParams = _openvz6_paramsCT($ctid, $params);

		if ($execParams === 'success') {
			// Write root AS username
			$query = 'UPDATE `tblhosting` SET `username` = "root" WHERE `id` = ' . $serviceId;
			mysql_query($query);

			$execIp = [];
			$i = 0;

			foreach ($productIps as $productIpKey => $productIp) {
				$vzctlSetIp = 'vzctl set ' . $ctid . ' --ipadd ' . $productIp . ' --save';
				$execIp[$productIpKey] = _openvz6_exec($params, $vzctlSetIp);

				$successMsg = [
					'CT configuration saved to /etc/vz/conf/' . $ctid . '.conf'
				];

				if (WHMCS_OpenVZ6::validateMsg($execIp[$productIpKey], $successMsg) === 'success') {
					if ($i > 0) { // Assigned IPs
						$query = 'SELECT * FROM `tblhosting` WHERE `id` = ' . $serviceId;
						$results = mysql_query($query);
						$services = [];

						while (($row = mysql_fetch_assoc($results)) !== False) {
							$services[] = $row;
						}

						$assignedIps = !empty($services[0]['assignedips'])
							? explode("\n", $services[0]['assignedips'])
							: [];

						if (in_array($productIp, $assignedIps)) {
							continue;
						} else {
							$assignedIps[] = $productIp;
							$assignedIps = implode("\n", $assignedIps);

							$query = 'UPDATE `tblhosting` SET `assignedips` = "' . $assignedIps . '" WHERE `id` = "' . $serviceId . '"';
							mysql_query($query);
						}
					} else { // Dedicated IP
						$query = 'UPDATE `tblhosting` SET `dedicatedip` = "' . $productIp . '" WHERE `id` = "' . $serviceId . '"';
						mysql_query($query);
					}

					$i += 1;
				} else {

					return $execIp[$productIpKey];
				}
			}

			$execIp = implode("\n", $execIp);

			// Start CT
			$execStart = openvz6_vzctlStart($params);
			$execStart === 'success' && logActivity($execCreate . "\n" . $execParams . "\n" . $execIp . "\n" . $execStart, 0);

			return $execStart;
		} else {
			$rtn = $execParams;
		}
	} else {
		$rtn = $execCreate;
	}

	return $rtn;
}

/**
 * @param int $ctid
 * @param string $template
 * @param array $params
 * @return bool|string
 */
function _openvz6_createCT($ctid = 0, $template = '', $params = [])
{
	// VE
	$VE_LAYOUT = $params['configoption11'];

	// Create CT
	$vzctlCreate = <<<CMD

vzctl create $ctid
	--ostemplate $template
	--layout $VE_LAYOUT

CMD;

	$vzctlCreate = str_replace(["\r\n", "\n"], '', $vzctlCreate);
	$exec = _openvz6_exec($params, $vzctlCreate);

	$successMsg = [
		'Creating container private area (' . $template . ') Performing postcreate actions CT configuration saved to /etc/vz/conf/' . $ctid . '.conf Container private area was created'
	];

	return WHMCS_OpenVZ6::validateMsg($exec, $successMsg);
}

/**
 * @param int $ctid
 * @param array $params
 * @return bool|string
 */
function _openvz6_paramsCT($ctid = 0, $params = [])
{
	$hostname = $params['domain'];
	$password = $params['password'];

	$ONBOOT = $params['configoption1'];

	// CPU
	$CPUUNITS = $params['configoption2'];
	$CPUS = $params['configoption3'];
	$CPULIMIT = $params['configoption4'];

	// RAM
	$PHYSPAGES = $params['configoption5'];
	$SWAPPAGES = $params['configoption6'];

	// DISK
	$DISKSPACE = $params['configoption7'];
	$DISKINODES = $params['configoption8'];
	$QUOTAUGIDLIMIT = $params['configoption9'];
	$QUOTATIME = $params['configoption10'];

	/*
	// OTHER
	KMEMSIZE="unlimited"
	LOCKEDPAGES="unlimited"
	SHMPAGES="unlimited"
	NUMPROC="unlimited"
	VMGUARPAGES="unlimited"
	OOMGUARPAGES="unlimited"
	NUMTCPSOCK="unlimited"
	NUMFLOCK="unlimited"
	NUMPTY="unlimited"
	NUMSIGINFO="unlimited"
	TCPSNDBUF="unlimited"
	TCPRCVBUF="unlimited"
	OTHERSOCKBUF="unlimited"
	DGRAMRCVBUF="unlimited"
	NUMOTHERSOCK="unlimited"
	DCACHESIZE="unlimited"
	NUMFILE="unlimited"
	AVNUMPROC="unlimited"
	NUMIPTENT="unlimited"
	*/

	// Set params
	$vzctlSet = <<<CMD

vzctl set $ctid
   --hostname $hostname
   --userpasswd root:$password
   --onboot $ONBOOT
   --cpuunits $CPUUNITS
   --cpus $CPUS
   --cpulimit $CPULIMIT
   --ram $PHYSPAGES
   --swap $SWAPPAGES
   --diskspace $DISKSPACE
   --diskinodes $DISKINODES
   --quotaugidlimit $QUOTAUGIDLIMIT
   --quotatime $QUOTATIME
   --save

CMD;

	$vzctlSet = str_replace(["\r\n", "\n"], '', $vzctlSet);
	$exec = _openvz6_exec($params, $vzctlSet);

	$successMsg = [
		'Starting container... Container is mounted Container start in progress... Changing password for user root. passwd: all authentication tokens updated successfully. Killing container ... Container was stopped Container is unmounted CT configuration saved to /etc/vz/conf/' . $ctid . '.conf'
	];

	return WHMCS_OpenVZ6::validateMsg($exec, $successMsg);
}

/**
 * @param array $params
 * @return bool|string
 */
function openvz6_SuspendAccount($params = [])
{
	$cmd = 'vzctl suspend ' . $params['customfields']['ctid'];
	$exec = _openvz6_exec($params, $cmd);

	$successMsg = [
		'Setting up checkpoint... suspend... dump... kill... Checkpointing completed successfully Container is unmounted'
	];

	return WHMCS_OpenVZ6::validateMsg($exec, $successMsg);
}

/**
 * @param $params
 * @return bool|string
 */
function openvz6_UnsuspendAccount($params)
{
	$cmd = 'vzctl resume ' . $params['customfields']['ctid'];
	$exec = _openvz6_exec($params, $cmd);

	$successMsg = [
		'Restoring container ... Container is mounted undump... Adding IP addresses: (IPS) Setting CPU limit: (CPULIMIT) Setting CPU units: (CPUUNITS) Setting CPUs: (CPUS) resume... Container start in progress... Restoring completed successfully'
	];

	return WHMCS_OpenVZ6::validateMsg($exec, $successMsg);
}

/**
 * @param $params
 * @return bool|string
 */
function openvz6_TerminateAccount($params)
{
	$ctid = $params['customfields']['ctid'];
	$cmd = 'vzctl destroy ' . $ctid;
	$exec = _openvz6_exec($params, $cmd);

	$successMsg = [
		'Destroying container private area: /vz/private/' . $ctid . ' Container private area was destroyed'
	];

	return WHMCS_OpenVZ6::validateMsg($exec, $successMsg);
}

/**
 * @param array $params
 * @return bool|string
 */
function openvz6_ChangePassword($params = [])
{
	$password = $params['password'];
	$cmd = 'vzctl set ' . $params['customfields']['ctid'] . ' --userpasswd root:' . $password;
	$exec = _openvz6_exec($params, $cmd);

	$successMsg = [
		'Starting container... Container is mounted Container start in progress... Changing password for user root. passwd: all authentication tokens updated successfully. Killing container ... Container was stopped Container is unmounted',
		'Changing password for user root. passwd: all authentication tokens updated successfully. UB limits were set successfully'
	];

	return WHMCS_OpenVZ6::validateMsg($exec, $successMsg);
}

/**
 * @param $params
 * @return string
 */
function openvz6_ClientArea($params = [])
{
	$dedicatedip = $params['templatevars']['dedicatedip'];
	$html = _openvz6_getStyle();

	// ping
	if ($dedicatedip) {
		$html .= '<i class="openvz6-ping ' .
			(WHMCS_OpenVZ6::ping($dedicatedip) ? 'true' : 'false') . '"> Ping</i>';
	}

	return $html;
}

/**
 * @param array $params
 * @return string
 */
function openvz6_AdminLink($params = [])
{
	$html = _openvz6_getStyle();
	$html .= _openvz6_getJs($params);
	$host = _openvz6_getHost($params);

	// link
	$html .= _openvz6_getLink($params) . ' ';

	// ping
	$html .= '<i class="openvz6-ping ' .
		(WHMCS_OpenVZ6::ping($host) ? 'true' : 'false') . '"> Ping</i>';

	return $html;
}

/**
 * Setup > Products/services > Servers
 * @param array $params
 * @return string
 */
function openvz6_LoginLink($params = [])
{
	$html = openvz6_AdminLink($params);
	return $html;
}

/**
 * @param $params
 * @return array
 * TODO: bug with server selection
 */
function openvz6_AdminCustomButtonArray($params = [])
{
	$status = _openvz6_getStatus($params);

	switch ($status) {
		default:
		case 'suspended':
			$buttons = [
				'Status' => 'vzctlStatus'
			];
			break;

		case 'down':
			$buttons = [
				'Start' => 'vzctlStart',
				'Restart' => 'vzctlRestart',
				'Status' => 'vzctlStatus'
			];
			break;

		case 'running':
			$buttons = [
				'Restart' => 'vzctlRestart',
				'Stop' => 'vzctlStop',
				'Status' => 'vzctlStatus',
				'Check Point' => 'vzctlChkpnt'
			];
			break;
	}

	return $buttons;
}

/**
 * @param array $params
 * @return array
 */
function openvz6_ClientAreaCustomButtonArray($params = [])
{
	$status = _openvz6_getStatus($params);

	switch ($status) {
		default:
		case 'suspended':
			$buttons = [];
			break;

		case 'down':
			$buttons = [
				'Start' => 'vzctlStart',
				'Restart' => 'vzctlRestart'
			];
			break;

		case 'running':
			$buttons = [
				'Restart' => 'vzctlRestart',
				'Stop' => 'vzctlStop'
			];
			break;
	}

	return $buttons;
}

/**
 * Module private methods
 */

/**
 * @param $params
 * @return string
 */
function _openvz6_getLink($params = [])
{
	$login = !empty($params['serverusername'])
		? $params['serverusername']
		: 'root';
	$host = _openvz6_getHost($params);
	$link = '<a href="ssh://' . $login . '@' . $host . '" target="_blank">' .
		$login . '@' . $host .
		'</a>';

	return $link;
}

/**
 * @param array $params
 * @return mixed
 */
function _openvz6_getHost($params = [])
{
	$host = '';

	if (
		!empty($params['serverhostname']) &&
		filter_var($params['serverhostname'], FILTER_SANITIZE_URL)
	) {
		$host = $params['serverhostname'];
	} else if (
		!empty($params['serverip']) &&
		filter_var($params['serverhostname'], FILTER_VALIDATE_IP)
	) {
		$host = $params['serverip'];
	}

	return $host;
}

/**
 * @return string
 */
function _openvz6_getStyle()
{
	ob_start(); ?>

	<style>
		.openvz6-ping {
			color: rgba(0, 0, 0, .5);
			font-size: 11px;
			text-transform: uppercase;
		}

		.openvz6-ping::before {
			content: '';
			display: inline-block;
			width: 1rem;
			height: 1rem;
			background-color: rgba(0, 0, 0, .1);
			border-radius: 50%;
		}

		.openvz6-ping.true::before {
			background-color: green;
		}

		.openvz6-ping.false::before {
			background-color: red;
		}
	</style>

	<?php $style = ob_get_clean();

	return $style;
}

/**
 * @param array $params
 * @return string
 */
function _openvz6_getJs($params = [])
{
	$serverId = !empty($params['serverid']) ? $params['serverid'] : 0;

	ob_start(); ?>

	<script type="text/javascript">
		jQuery(document).ready(function ($) {

			<?php if ($serverId) { ?>
			function selectedServer() {
				var $select = $('select[name="server"]'),
					$selected = $('option[value="<?= $serverId; ?>"]', $select);

				if ($selected.length > 0) {
					$select.val('<?= $serverId; ?>');
				}
			}

			selectedServer();
			<?php } ?>

		});
	</script>

	<?php $script = ob_get_clean();

	return $script;
}

/**
 * @param array $params
 * @param string $cmd
 * @return bool|null|string
 */
function _openvz6_exec($params = [], $cmd = '')
{
	require_once PATH . '/classes/SSH2.php';

	$host = _openvz6_getHost($params);
	$login = $params['serverusername'];
	$password = $params['serverpassword'];

	$session = new SSH2($host);

	if ($session) {
		$stream = $session->auth($login, $password);

		if ($stream) {
			$cmd = trim($cmd);
			$session->exec($cmd);
			$output = $session->output();
		}
	}

	return isset($output) ? $output : False;
}

/**
 * Custom actions
 */

/**
 * @param array $params
 * @return bool|string
 */
function openvz6_vzctlStart($params = [])
{
	$cmd = 'vzctl start ' . $params['customfields']['ctid'];
	$exec = _openvz6_exec($params, $cmd);

	$successMsg = [
		'Starting container... Container is mounted Adding IP addresses: (IPS) Setting CPU limit: (CPULIMIT) Setting CPU units: (CPUUNITS) Setting CPUs: (CPUS) Container start in progress...'
	];

	return WHMCS_OpenVZ6::validateMsg($exec, $successMsg);
}

/**
 * @param array $params
 * @return bool|string
 */
function openvz6_vzctlRestart($params = [])
{
	$cmd = 'vzctl restart ' . $params['customfields']['ctid'];
	$exec = _openvz6_exec($params, $cmd);

	$successMsg = [
		'Restarting container Stopping container ... Container was stopped Container is unmounted Starting container... Container is mounted Adding IP address(es): (IPS) Setting CPU limit: (CPULIMIT) Setting CPU units: (CPUUNITS) Setting CPUs: (CPUS) Container start in progress...',
		'Restarting container Starting container... Container is mounted Adding IP addresses: (IPS) Setting CPU limit: (CPULIMIT) Setting CPU units: (CPUUNITS) Setting CPUs: (CPUS) Container start in progress...'
	];

	return WHMCS_OpenVZ6::validateMsg($exec, $successMsg);
}

/**
 * @param array $params
 * @return bool|string
 */
function openvz6_vzctlStop($params = [])
{
	$cmd = 'vzctl stop ' . $params['customfields']['ctid'];
	$exec = _openvz6_exec($params, $cmd);

	$successMsg = [
		'Stopping container ... Container was stopped Container is unmounted'
	];

	return WHMCS_OpenVZ6::validateMsg($exec, $successMsg);
}

/**
 * @param array $params
 * @return bool|string
 */
function openvz6_vzctlStatus($params = [])
{
	$cmd = 'vzctl status ' . $params['customfields']['ctid'];
	return _openvz6_exec($params, $cmd);

	// CTID 109127 exist mounted running
	// CTID 109127 exist unmounted down
	// CTID 109127 exist unmounted down suspended
}

function _openvz6_getStatus($params = [])
{
	$exec = openvz6_vzctlStatus($params);
	$status = trim($exec);
	$status = explode(' ', $status);
	$status = array_filter($status);
	$status = array_pop($status);

	return $status ? $status : $exec;
}

/**
 * @param $params
 * @return bool|string
 */
function openvz6_vzctlChkpnt($params = [])
{
	$ctid = $params['customfields']['ctid'];
	$cmd = 'vzctl chkpnt ' . $ctid;
	$exec = _openvz6_exec($params, $cmd);

	return $exec;
}

/**
 * @param array $params
 * @return string
 */
function openvz_reinstallCT($params = [])
{
	// stop CT
	$stopCT = openvz6_vzctlStop($params);

	// terminate CT
	$terminateCT = openvz6_TerminateAccount($params);

	// create
	$createCT = openvz6_CreateAccount($params);

	return $createCT;
}

/**
 * @param array $params
 * @return int
 */
function _openvz6_generateCTID($params = [])
{
	$serviceId = !empty($params['serviceid'])
		? $params['serviceid']
		: 0;
	$CTID = 0;

	if ($serviceId && is_numeric($serviceId)) {
		$CTID = 10000 + $serviceId;
		$i = 0;

		while ($i < 1) {
			// check db
			$query = 'SELECT * FROM  `tblhosting` WHERE `id` = "' . $CTID . '"';
			$results = mysql_query($query);
			$services = [];

			while (($row = mysql_fetch_assoc($results)) !== False) {
				$services[] = $row;
			}

			if (count($services) === 0) {
				$i += 1;
			} else {
				$CTID *= 10;

				if ($CTID > pow(10, 9) - 1) {
					$i += 1;
				}
			}
		}
	}

	return $CTID;
}

