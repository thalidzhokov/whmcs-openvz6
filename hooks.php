<?php
/**
 * Author: Albert Thalidzhokov
 */

if (!defined('WHMCS')) {
	die('This file cannot be accessed directly');
}

defined('PATH') or define('PATH', __DIR__);

require_once PATH . '/classes/SSH2.php';
require_once PATH . '/classes/WHMCS_OVZ6.php';

/**
 * Add custom fields for product
 * http://developers.whmcs.com/hooks-reference/products-and-services/#productedit
 */

add_hook('ProductEdit', 1, function($vars) {
	$pid = $vars['pid'];
	$serverType = $vars['servertype'];

	if ($serverType === WHMCS_OVZ6::TYPE) {
		$fieldOptions = WHMCS_OVZ6::templates();
		WHMCS_OVZ6::productField($pid, 'ctid', 'text', [], true);
		WHMCS_OVZ6::productField($pid, 'template', 'dropdown', $fieldOptions, false);
	}
});
