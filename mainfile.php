<?php
/**
 * All information in order to connect to database are going through here.
 *
 * Be careful if you are changing data's in this file.
 */
if (!defined('ICMS_MAINFILE_INCLUDED')) {
	define('ICMS_MAINFILE_INCLUDED', true);

	// Including libs with composer
	include_once __DIR__ . '/vendor/autoload.php';

	define('ICMS_GROUP_ADMIN', 1);
	define('ICMS_GROUP_USERS', 2);
	define('ICMS_GROUP_ANONYMOUS', 3);

	if (!isset($xoopsOption['nocommon'])) {
		include ICMS_ROOT_PATH . '/include/common.php';
	}
}