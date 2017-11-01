<?php
/**
 * Author: Albert Thalidzhokov
 */

if (!defined('WHMCS')) {
	die('This file cannot be accessed directly');
}

defined('PATH') or define('PATH', __DIR__);

require_once PATH . '/_OpenVZ6.php';
require_once PATH . '/SSH2.php';

/**
 * Add custom fields for product
 * http://developers.whmcs.com/hooks-reference/products-and-services/#productedit
 */

add_hook('ProductEdit', 1, function($vars) {
	$pid = $vars['pid'];
	$fieldOptions = _OpenVZ6::templates();
	_OpenVZ6::productField($pid, 'ctid', 'text', [], true);
	_OpenVZ6::productField($pid, 'template', 'dropdown', $fieldOptions, false);
});
