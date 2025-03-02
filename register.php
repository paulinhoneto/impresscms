<?php
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //

/**
 * Registration process for new users
 * Gathers required information and validates the new user
 *
 * @copyright    http://www.xoops.org/ The XOOPS Project
 * @copyright    http://www.impresscms.org/ The ImpressCMS Project
 * @license        http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since        XOOPS
 * @author        http://www.xoops.org The XOOPS Project
 * @author      skenow <skenow@impresscms.org>
 * @package        Member
 * @subpackage    Users
 */

use ImpressCMS\Core\DataFilter;

$xoopsOption['pagetype'] = 'user';

if (icms_get_module_status('profile') && file_exists(ICMS_MODULES_PATH . '/profile/register.php')) {
	header('Location: ' . ICMS_MODULES_URL . '/profile/register.php');
	exit();
}

if ($icmsConfigUser['allow_register'] == 0 && $icmsConfigUser['activation_type'] != 3) {
	redirect_header('index.php', 6, _US_NOREGISTER);
}
if (is_object(icms::$user)) {
	redirect_header('index.php', 6, _US_ALREADY_LOGED_IN);
}
$op = !isset($_POST['op']) ? 'register' : filter_input(INPUT_POST, 'op');
$login_name = isset($_POST['login_name']) ? DataFilter::stripSlashesGPC($_POST['login_name']) : '';
$uname = isset($_POST['uname']) ? DataFilter::stripSlashesGPC($_POST['uname']) : '';
$email = isset($_POST['email']) ? trim(DataFilter::stripSlashesGPC($_POST['email'])) : '';
$url = isset($_POST['url']) ? trim(DataFilter::stripSlashesGPC($_POST['url'])) : '';
$pass = isset($_POST['pass']) ? DataFilter::stripSlashesGPC($_POST['pass']) : '';
$vpass = isset($_POST['vpass']) ? DataFilter::stripSlashesGPC($_POST['vpass']) : '';
$timezone_offset = isset($_POST['timezone_offset']) ? (float)($_POST['timezone_offset']) : $icmsConfig['default_TZ'];
$user_viewemail = (isset($_POST['user_viewemail']) && (int)$_POST['user_viewemail']) ? 1 : 0;
$user_mailok = (isset($_POST['user_mailok']) && (int)$_POST['user_mailok']) ? 1 : 0;
$agree_disc = (isset($_POST['agree_disc']) && (int)$_POST['agree_disc']) ? 1 : 0;
$actkey = isset($_POST['actkey']) ? trim(DataFilter::stripSlashesGPC($_POST['actkey'])) : '';

$thisuser = icms::handler('icms_member_user');
switch ($op) {
	case 'newuser':
		include 'header.php';
		$xoTheme->addScript('', array('type' => ''), '
				$(".password").passStrength({
					shortPass: 		"top_shortPass",
					badPass:		"top_badPass",
					goodPass:		"top_goodPass",
					strongPass:		"top_strongPass",
					baseStyle:		"top_testresult",
					messageloc:		0
				});
			});
		');
		$stop = '';
		if (!icms::$security->check()) {
			$stop .= implode('<br />', icms::$security->getErrors()) . "<br />";
		}
		if ($icmsConfigUser['reg_dispdsclmr'] != 0 && $icmsConfigUser['reg_disclaimer'] != '') {
			if (empty($agree_disc)) {
				$stop .= _US_UNEEDAGREE . '<br />';
			}
		}
		$stop .= $thisuser->userCheck($login_name, $uname, $email, $pass, $vpass);
		if (empty($stop)) {
			echo _US_LOGINNAME . ": " . DataFilter::htmlSpecialChars($login_name) . "<br />"
				. _US_NICKNAME . ": " . DataFilter::htmlSpecialChars($uname) . "<br />"
				. _US_EMAIL . ": " . DataFilter::htmlSpecialChars($email) . "<br />";
			if ($url != '') {
				$url = formatURL($url);
				echo _US_WEBSITE . ': ' . DataFilter::htmlSpecialChars($url) . '<br />';
			}
			$f_timezone = ($timezone_offset < 0) ? 'GMT ' . $timezone_offset : 'GMT +' . $timezone_offset;
			echo _US_TIMEZONE . ": $f_timezone<br />";
			echo "<form action='register.php' method='post'><input type='hidden' name='login_name' value='"
				. DataFilter::htmlSpecialChars($login_name)
				. "' /><input type='hidden' name='uname' value='" . DataFilter::htmlSpecialChars($uname)
				. "' /><input type='hidden' name='email' value='" . DataFilter::htmlSpecialChars($email)
				. "' /><input type='hidden' name='user_viewemail' value='" . (int)$user_viewemail
				. "' /><input type='hidden' name='timezone_offset' value='" . $timezone_offset
				. "' /><input type='hidden' name='url' value='" . DataFilter::htmlSpecialChars($url)
				. "' /><input type='hidden' name='pass' value='" . DataFilter::htmlSpecialChars($pass)
				. "' /><input type='hidden' name='vpass' value='" . DataFilter::htmlSpecialChars($vpass)
				. "' /><input type='hidden' name='user_mailok' value='" . (int)$user_mailok
				. "' /><input type='hidden' name='actkey' value='" . DataFilter::htmlSpecialChars($actkey)
				. "' /><input type='hidden' name='agree_disc' value='" . (int) $agree_disc
				. "' /><br /><br /><input type='hidden' name='op' value='finish' />" . icms::$security->getTokenHTML()
				. "<input type='submit' value='" . _US_FINISH . "' /></form>";
		} else {
			echo "<div id='registerstop' style='color:#ff0000;'>$stop</div>";
			include 'include/registerform.php';
			$reg_form->display();
		}
		$xoopsTpl->assign('icms_pagetitle', _US_USERREG);
		include 'footer.php';
		break;

	case 'finish':
		include 'header.php';
		$stop = $thisuser->userCheck($login_name, $uname, $email, $pass, $vpass);
		if (!icms::$security->check()) {
			$stop .= implode('<br />', icms::$security->getErrors()) . "<br />";
		}
		if ($icmsConfigUser['use_captcha'] == 1) {
			$icmsCaptcha = icms_form_elements_captcha_Object::instance();
			if (!$icmsCaptcha->verify()) {
				$stop .= $icmsCaptcha->getMessage() . '<br />';

			}
		}

		if ($icmsConfigUser['reg_dispdsclmr'] != 0 && $icmsConfigUser['reg_disclaimer'] != '') {
			if (empty($agree_disc)) {
				$stop .= _US_UNEEDAGREE . '<br />';
			}
		}

		if (empty($stop)) {
			/**
			 * @var \ImpressCMS\Core\Facades\Member $member_handler
			 */
			$member_handler = icms::handler('icms_member');
			$newuser = & $member_handler->createUser();
			$newuser->setVar('user_viewemail', $user_viewemail, true);
			$newuser->setVar('login_name', $login_name, true);
			$newuser->setVar('uname', $uname, true);
			$newuser->setVar('email', $email, true);
			if ($url != '') {
				$newuser->setVar('url', formatURL($url), true);
			}
			$newuser->setVar('user_avatar', 'blank.gif', true);
			include_once 'include/checkinvite.php';
			$valid_actkey = check_invite_code($actkey);
			$newuser->setVar('actkey', $valid_actkey?$actkey:substr(md5(uniqid(mt_rand(), 1)), 0, 8), true);

			$icmspass = new icms_core_Password();

			$pass1 = $icmspass->encryptPass($pass);
			$newuser->setVar('pass', $pass1, true);
			$newuser->setVar('timezone_offset', $timezone_offset, true);
			$newuser->setVar('user_regdate', time(), true);
			$newuser->setVar('uorder', $icmsConfig['com_order'], true);
			$newuser->setVar('umode', $icmsConfig['com_mode'], true);
			$newuser->setVar('user_mailok', $user_mailok, true);
			$newuser->notify_method = 2;
			if ($valid_actkey || $icmsConfigUser['activation_type'] == 1) {
				$newuser->setVar('level', 1, true);
			}
			if (!$newuser->store()) {
				echo "<div id='registerng'>" . _US_REGISTERNG . '</div>';
				include 'footer.php';
				exit();
			}
			$newid = (int) $newuser->uid;
			if (!$member_handler->addUserToGroup(ICMS_GROUP_USERS, $newid)) {
				echo "<div id='registerng'>" . _US_REGISTERNG . '</div>';
				include 'footer.php';
				exit();
			}

			// Send notification about the new user register to the selected group if config is true on admin preferences
			if ($icmsConfigUser['new_user_notify'] == 1) {
				$newuser->newUserNotifyAdmin();
			}

			// update invite_code (if any)
			if ($valid_actkey) {
				update_invite_code($actkey, $newid);
			}
			if ($icmsConfigUser['activation_type'] == 1 || $icmsConfigUser['activation_type'] == 3) {
				redirect_header('index.php', 4, _US_ACTLOGIN);
				exit();
			}

			$thisuser = \icms::handler('icms_member')->getUser($newid);

			// Activation by user
			if ($icmsConfigUser['activation_type'] == 0) {
				$mailer = new icms_messaging_Handler();
				$mailer->useMail();
				$mailer->setTemplate('register.tpl');
				$mailer->setToUsers(\icms::handler('icms_member')->getUser($newid));
				$mailer->setFromEmail($icmsConfig['adminmail']);
				$mailer->setFromName($icmsConfig['sitename']);
				$mailer->setSubject(sprintf(_US_USERKEYFOR, $uname));
				if (!$mailer->send()) {
					echo "<div id='yourregmailng'>" . _US_YOURREGMAILNG . "</div>";
				} else {
					echo "<div id='yourregistered'>" . _US_YOURREGISTERED . "</div>";
				}
				// activation by admin
			} elseif ($icmsConfigUser['activation_type'] == 2) {
				$mailer = new icms_messaging_Handler();
				$mailer->useMail();
				$mailer->setTemplate('adminactivate.tpl');
				$mailer->assign('USERNAME', $uname);
				$mailer->assign('USERLOGINNAME', $login_name);
				$mailer->assign('USEREMAIL', $email);
				$mailer->assign('USERACTLINK', ICMS_URL . '/user.php?op=actv&amp;id=' . $newid . '&amp;actkey=' . $newuser->actkey);
				$member_handler = icms::handler('icms_member');
				$mailer->setToGroups($member_handler->getGroup($icmsConfigUser['activation_group']));
				$mailer->setFromEmail($icmsConfig['adminmail']);
				$mailer->setFromName($icmsConfig['sitename']);
				$mailer->setSubject(sprintf(_US_USERKEYFOR, $uname));
				if (!$mailer->send()) {
					echo "<div id='yourregmailng'>" . _US_YOURREGMAILNG . "</div>";
				} else {
					echo "<div id='yourregistered2'>" . _US_YOURREGISTERED2 . "</div>";
				}
			}
		} else {
			echo "<div id='registerstop' style='color:#ff0000; font-weight:bold;'>$stop</div>";
			include 'include/registerform.php';
			$reg_form->display();
		}
		$xoopsTpl->assign('icms_pagetitle', _US_USERREG);
		include 'footer.php';
		break;

	case 'register':
	default:
		$invite_code = isset($_GET['code'])? filter_input(INPUT_GET, 'code'):null;
		if ($icmsConfigUser['activation_type'] == 3 || !empty($invite_code)) {
			include 'include/checkinvite.php';
			load_invite_code($invite_code);
		}
		// invite is ok, show register form
		include 'header.php';
		include 'include/registerform.php';
		$reg_form->display();
		$xoopsTpl->assign('icms_pagetitle', _US_USERREG);
		include 'footer.php';
		break;
}
