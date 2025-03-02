<?php
/**
 * Installer tables creation page
 *
 * See the enclosed file license.txt for licensing information.
 * If you did not receive this file, get it at http://www.fsf.org/copyleft/gpl.html
 *
 * @copyright    The ImpressCMS project http://www.impresscms.org/
 * @license      http://www.fsf.org/copyleft/gpl.html GNU General Public License (GPL)
 * @package        installer
 * @since        1.0
 * @author        Martijn Hertog (AKA wtravel) <martin@efqconsultancy.com>
 */

use ImpressCMS\Core\Extensions\SetupSteps\OutputDecorator;
use ImpressCMS\Core\Models\ModuleHandler;
use ImpressCMS\Core\Models\UserHandler;
use Symfony\Component\Console\Output\BufferedOutput;

define('INSTALLER_INCLUDE_MAIN', true);
require_once 'common.inc.php';

if (!defined('XOOPS_INSTALL')) {
	exit();
}

$wizard->setPage('modulesinstall');
$pageHasForm = true;
$pageHasHelp = false;

$vars = & $_SESSION['settings'];
include_once ICMS_ROOT_PATH . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR . "common.php";
include_once __DIR__ . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'dbmanager.php';

$dbm = new db_manager();

if (!$dbm->isConnectable()) {
	$wizard->redirectToPage('-3');
	exit();
}
$process = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$process = 'install';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// If there's nothing to do: switch to next page
	if (empty($process)) {
		$wizard->redirectToPage('+1');
		exit();
	}
	if ((int)$_POST['mod'] === 1) {
		icms_loadLanguageFile('system', 'modules', true);

		if (!icms::$user) {
			/**
			 * @var UserHandler $usersHandler
			 */
			$usersHandler = icms::handler('icms_member_user');
			icms::$user = $usersHandler->get(1);
		}

		/**
		 * Automatically updating the system module before installing the selected modules
		 * @since 1.3
		 *
		 * @var ModuleHandler $module_handler
		 */
		$module_handler = icms::handler('icms_module');

		$buffer = new BufferedOutput();
		$output = new OutputDecorator($buffer);

		$module_handler->update('system', $output);

		$install_mods = $_POST['install_mods'] ?? '';
		$anon_accessible_mods = $_POST['anon_accessible_mods'] ?? '';
		if (isset($_POST['install_mods'])) {
			for ($i = 0; $i <= count($install_mods) - 1; $i++) {
				$module_handler->install($install_mods[$i], $output);
				impresscms_get_adminmenu();
			}
		}

		$tables = array();
		$content = nl2br(
			$buffer->fetch()
		);
		$content .= "<div style='height:auto;max-height:400px;overflow:auto;'>" . $dbm->report() . "</div>";
	} else {
		$wizard->redirectToPage('+1');
		exit();
	}
} else {
	$langarr = ModuleHandler::getAvailable();

	$content .= '<div>' . _INSTALL_SELECT_MODS_INTRO . '</div>';
	$content .= '<div class="dbconn_line">';
	$content .= '<h3>' . _INSTALL_SELECT_MODULES . '</h3>';
	$content .= '<div id="modinstall" name="install_mods[]">';

	foreach ($langarr as $lang) {
		if ($lang === 'system') {
			$content .= "<div class=\"langselect\" style=\"text-decoration: none;\"><a href=\"javascript:void(0);\" style=\"text-decoration: none;\"><img src=\"../modules/$lang/images/icon_small.png\" alt=\"$lang\" /><br />$lang <br /><input type=\"checkbox\" checked=\"checked\" name=\"update_mods[]\" checked value=\"$lang\" disabled /></a></div>";
			continue;
		}
		$content .= "<div class=\"langselect\" style=\"text-decoration: none;\"><a href=\"javascript:void(0);\" style=\"text-decoration: none;\"><img src=\"../modules/$lang/images/icon_small.png\" alt=\"$lang\" /><br />$lang <br /><input type=\"checkbox\" checked=\"checked\" name=\"install_mods[]\" value=\"$lang\" /></a></div>";
	}
	$content .= "</div><div class='clear'>&nbsp;</div>";
	$content .= '</div>';
	$content .= '<input type="hidden" name="mod" value="1" />';
}

include 'install_tpl.php';
