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
 * Generates form and validation for editing users
 *
 * @copyright    http://www.xoops.org/ The Xoops Project
 * @copyright    http://www.impresscms.org/ The ImpressCMS Project
 * @license        http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package        Member
 * @subpackage    Users
 * @since        Xoops
 * @author        skalpa <psk@psykaos.net>
 */

use ImpressCMS\Core\DataFilter;

$xoopsOption['pagetype'] = 'user';
if (icms_get_module_status('profile') && file_exists(ICMS_MODULES_PATH . '/profile/edituser.php')) {
	header('Location: ' . ICMS_MODULES_URL . '/profile/edituser.php');
	exit();
}

// If not a user, redirect
if (!is_object(icms::$user)) {
	redirect_header('index.php', 3, _US_NOEDITRIGHT);
}

$op = '';
/* The following are the form elements, passed through $_POST
    'user_sig' => 'html',
    'bio'=> 'html',
	'email' => array('email', 'options' => array(0, 1)),
	'uid' => 'int',
	'uname' => 'str',
	'password' => 'str',
	'old_password'=> 'str',
	'change_pass' => 'int',
	'vpass'=> 'str',
	'name'=> 'str',
	'url' => 'url',
	'user_from'=> 'str',
	'user_viewemail' => 'int',
	'attachsig' => 'int',
	'timezone_offset'=> 'str',
	'uorder'=> 'str',
	'umode'=> 'str',
	'notify_method'=> 'str',
	'notify_mode'=> 'str',
	'user_occ'=> 'str',
	'user_intrest'=> 'str',
	'user_mailok' => 'int',
	'theme_selected'=> 'str',
	'usecookie' => 'int',
	'xoops_upload_file' => 'array'
	'user_avatar'=> 'str',
	'op' => 'str',
*/
$filter_post = array(
	'user_sig' => 'html',
	'email' => array('email', 'options' => array(0, 1)),
	'uid' => 'int',
	'change_pass' => 'int',
	'url' => 'url',
	'user_viewemail' => 'int',
	'attachsig' => 'int',
	'user_mailok' => 'int',
	'usecookie' => 'int',
);

$filter_get = array(
	'uid' => 'int',
);

if (!empty($_GET)) {
	$clean_GET = DataFilter::checkVarArray($_GET, $filter_get, false);
	extract($clean_GET);
}
if (!empty($_POST)) {
	$clean_POST = DataFilter::checkVarArray($_POST, $filter_post, false);
	extract($clean_POST);
}

switch ($op) {
		case 'saveuser':
			if (!icms::$security->check()) {
				redirect_header('index.php', 3, _US_NOEDITRIGHT . "<br />" . implode('<br />', icms::$security->getErrors()));
			}

			if (icms::$user->uid != $uid) {
				redirect_header('index.php', 3, _US_NOEDITRIGHT);
			}

			$errors = array();

			if ($icmsConfigUser['allow_chgmail'] == 1) {
				if (!empty($email)) {
					$email = DataFilter::stripSlashesGPC(trim($email));
				}

				if ($email == '' || !DataFilter::checkVar($email, 'email', 0, 1)) {
					$errors[] = _US_INVALIDMAIL;
				}

				$count = 0;
				if ($email) {
					$sql = sprintf('SELECT COUNT(*) FROM %s WHERE email = %s',
						icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString(addslashes($email)));
					$result = icms::$xoopsDB->query($sql);
					list($count) = icms::$xoopsDB->fetchRow($result);
					if ($count > 1) {
						$errors[] .= _US_EMAILTAKEN . "<br />";
					}
				}
			}

			if ($icmsConfigUser['allow_chguname'] == 1) {
				if (!empty($uname)) {
					$uname = DataFilter::stripSlashesGPC(trim($uname));
				}

				if ($uname == '') {
					$errors[] = _US_INVALIDNICKNAME;
				}
				if (strlen($uname) > $icmsConfigUser['maxuname']) {
					$errors[] .= sprintf(_US_NICKNAMETOOLONG, $icmsConfigUser['maxuname']) . "<br />";
				}

				if (strlen($uname) < $icmsConfigUser['minuname']) {
					$errors[] .= sprintf(_US_NICKNAMETOOSHORT, $icmsConfigUser['minuname']) . "<br />";
				}

				foreach ($icmsConfigUser['bad_unames'] as $bu) {
					if (!empty($bu) && preg_match("/" . $bu . "/i", $uname)) {
						$errors[] .= _US_NAMERESERVED . "<br />";
						break;
					}
				}

				$count = 0;
				if ($uname) {
					$sql = sprintf('SELECT COUNT(*) FROM %s WHERE uname = %s',
							icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString(addslashes($uname)));
					$result = icms::$xoopsDB->query($sql);
					list($count) = icms::$xoopsDB->fetchRow($result);
					if ($count > 1) {
						$errors[] .= _US_NICKNAMETAKEN . "<br />";
					}
				}
			}

			if (!empty($password)) {
				$password = DataFilter::stripSlashesGPC(trim($password));
				$oldpass = !empty($old_password)
					? DataFilter::stripSlashesGPC(trim($old_password))
					: '';

				$member_handler = icms::handler('icms_member');
				$username = $member_handler->getUser($uid)->login_name;
				if (!$member_handler->loginUser(addslashes($username), $oldpass)) {
					$errors[] = _US_SORRYINCORRECTPASS;
				}

				if (strlen($password) < $icmsConfigUser['minpass']) {
					$errors[] = sprintf(_US_PWDTOOSHORT, $icmsConfigUser['minpass']);
				}

				if (!empty($vpass)) {
					$vpass = DataFilter::stripSlashesGPC(trim($vpass));
				}

				if ($password != $vpass) {
					$errors[] = _US_PASSNOTSAME;
				}

				if ($password == $username
					|| $password == DataFilter::utf8_strrev($username, true)
					|| strripos($password, $username) === true
					) {
					$errors[] = _US_BADPWD;
				}
			}

			if (count($errors) > 0) {
				/** Include the header that starts page rendering */
				include ICMS_ROOT_PATH . '/header.php';
				icms_core_Message::error($errors);
				echo "<a href='edituser.php' title='" . _US_EDITPROFILE . "'>" . _US_EDITPROFILE . "</a>";
				include ICMS_ROOT_PATH . '/footer.php';
			} else {
				$member_handler = icms::handler('icms_member');
				$edituser = & $member_handler->getUser($uid);
				$edituser->name = $name;
				if ($icmsConfigUser['allow_chgmail'] == 1) {
					$edituser->setVar('email', $email, true);
				}

				if ($icmsConfigUser['allow_chguname'] == 1) {
					$edituser->setVar('uname', $uname, true);
				}

				$edituser->url = formatURL($url);
				$edituser->user_from = $user_from;
				if ($icmsConfigUser['allwshow_sig'] == 1) {
					if ($icmsConfigUser['allow_htsig'] == 0) {
						$signature = strip_tags(DataFilter::checkVar($user_sig, 'text', 'input'));
						$edituser->user_sig = DataFilter::icms_substr($signature, 0, (int)$icmsConfigUser['sig_max_length']);
					} else {
						$signature = DataFilter::checkVar($user_sig, 'html', 'input');
						$edituser->user_sig = $signature;
					}
				}

				$user_viewemail = (!empty($user_viewemail))?1:0;
				$edituser->user_viewemail = $user_viewemail;
				if ($password != '') {
					$icmspass = new icms_core_Password();
					$pass = $icmspass->encryptPass($password);
					$edituser->setVar('pass', $pass, true);
				}

				$attachsig = !empty($attachsig) ? 1 : 0;
				$edituser->attachsig = $attachsig;
				$edituser->timezone_offset = $timezone_offset;
				$edituser->uorder = $uorder;
				$edituser->umode = $umode;
				$edituser->notify_method = $notify_method;
				$edituser->notify_mode = $notify_mode;
				$edituser->bio = DataFilter::icms_substr($bio, 0, 255);
				$edituser->user_occ = $user_occ;
				$edituser->user_intrest = $user_intrest;
				$edituser->user_mailok = $user_mailok;
				if (isset($theme_selected)) {
					$edituser->theme = $theme_selected;

					/**
					 * @var Aura\Session\Session $session
					 */
					$session = icms::$session;
					$userSegment = $session->getSegment('user');
					$userSegment->set('theme', $theme_selected);
					$icmsConfig['theme_set'] = $theme_selected;
				} else {
					$edituser->theme = $icmsConfig['theme_set'];
				}

				if (!empty($usecookie)) {
					setcookie($icmsConfig['usercookie'], icms::$user->login_name, time() + 31536000);
				} else {
					setcookie($icmsConfig['usercookie']);
				}

				if (!$member_handler->insertUser($edituser)) {
					/** Include the header that starts page rendering */
					include ICMS_ROOT_PATH . '/header.php';
					echo $edituser->getHtmlErrors();
					/** Include the footer file to complete page rendering */
					include ICMS_ROOT_PATH . '/footer.php';
				} else {
					redirect_header('userinfo.php?uid=' . $uid, 1, _US_PROFUPDATED);
				}
				exit();
		}
		break;

		default:
		case 'editprofile':
			/** Include the header that starts page rendering */
			include_once ICMS_ROOT_PATH . '/header.php';
			include_once ICMS_INCLUDE_PATH . '/comment_constants.php';
			if ($icmsConfigUser['pass_level'] > 20) {
				icms_PasswordMeter();
			}

			echo '<a href="userinfo.php?uid=' .  icms::$user->uid . '">' . _US_PROFILE . '</a>&nbsp;
			<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;' . _US_EDITPROFILE . '<br /><br />';
			$form = new icms_form_Theme(_US_EDITPROFILE, 'userinfo', 'edituser.php', 'post', true);
			$login_name_label = new icms_form_elements_Label(_US_LOGINNAME, icms::$user->login_name);
			$form->addElement($login_name_label);
			$form->addElement(new icms_form_elements_Hidden("uname", icms::$user->login_name));
			$email_tray = new icms_form_elements_Tray(_US_EMAIL, '<br />');
			if ($icmsConfigUser['allow_chgmail'] == 1) {
				$email_text = new icms_form_elements_Text('', 'email', 30, 60, icms::$user->email);
			} else {
				$email_text = new icms_form_elements_Label('', icms::$user->email);
			}

			$email_tray->addElement($email_text);
			$email_cbox_value = icms::$user->user_viewemail?1:0;
			$email_cbox = new icms_form_elements_Checkbox('', 'user_viewemail', $email_cbox_value);
			$email_cbox->addOption(1, _US_ALLOWVIEWEMAIL);
			$email_tray->addElement($email_cbox);
			$form->addElement($email_tray);

			if ($icmsConfigUser['allow_chguname'] == 1) {
				$uname_label = new icms_form_elements_Text(_US_NICKNAME, 'uname', 30, 60, icms::$user->getVar('uname', 'E'));
			} else {
				$uname_label = new icms_form_elements_Label(_US_NICKNAME, icms::$user->uname);
			}

			$form->addElement($uname_label);
			$name_text = new icms_form_elements_Text(_US_REALNAME, 'name', 30, 60, icms::$user->getVar('name', 'E'));
			$form->addElement($name_text);
			$url_text = new icms_form_elements_Text(_US_WEBSITE, 'url', 30, 100, icms::$user->getVar('url', 'E'));
			$form->addElement($url_text);

			$timezone_select = new icms_form_elements_select_Timezone(_US_TIMEZONE, 'timezone_offset', icms::$user->timezone_offset);
			$location_text = new icms_form_elements_Text(_US_LOCATION, 'user_from', 30, 100, icms::$user->getVar('user_from', 'E'));
			$occupation_text = new icms_form_elements_Text(_US_OCCUPATION, 'user_occ', 30, 100, icms::$user->getVar('user_occ', 'E'));
			$interest_text = new icms_form_elements_Text(_US_INTEREST, 'user_intrest', 30, 150, icms::$user->getVar('user_intrest', 'E'));
			if ($icmsConfigUser['allwshow_sig'] == 1) {
				if ($icmsConfigUser['allow_htsig'] == 0) {
					$sig_tray = new icms_form_elements_Tray(_US_SIGNATURE, '<br />');
					$sig_tarea = new icms_form_elements_Textarea('', 'user_sig', icms::$user->getVar('user_sig', 'E'));
					$sig_tray->addElement($sig_tarea);
					$sig_cbox_value = icms::$user->attachsig?1:0;
					$sig_cbox = new icms_form_elements_Checkbox('', 'attachsig', $sig_cbox_value);
					$sig_cbox->addOption(1, _US_SHOWSIG);
					$sig_tray->addElement($sig_cbox);
				} else {
					$sig_tray = new icms_form_elements_Tray(_US_SIGNATURE, '<br />');
					$sig_tarea = new icms_form_elements_Dhtmltextarea('', 'user_sig', icms::$user->getVar('user_sig', 'E'));
					$sig_tray->addElement($sig_tarea);
					$sig_cbox_value = icms::$user->attachsig?1:0;
					$sig_cbox = new icms_form_elements_Checkbox('', 'attachsig', $sig_cbox_value);
					$sig_cbox->addOption(1, _US_SHOWSIG);
					$sig_tray->addElement($sig_cbox);
				}
			}

			$umode_select = new icms_form_elements_Select(_US_CDISPLAYMODE, 'umode', icms::$user->umode);
			$umode_select->addOptionArray(array('nest'=>_NESTED, 'flat'=>_FLAT, 'thread'=>_THREADED));
			$uorder_select = new icms_form_elements_Select(_US_CSORTORDER, 'uorder', icms::$user->uorder);
			$uorder_select->addOptionArray(array(XOOPS_COMMENT_OLD1ST => _OLDESTFIRST, XOOPS_COMMENT_NEW1ST => _NEWESTFIRST));
			$selected_theme = new icms_form_elements_Select(_US_SELECT_THEME, 'theme_selected', icms::$user->theme);
			foreach ($icmsConfig['theme_set_allowed'] as $theme) {
				$selected_theme->addOption($theme, $theme);
			}

			$selected_language = new icms_form_elements_Select(_US_SELECT_LANG, 'language_selected', icms::$user->language);
			foreach (icms_core_Filesystem::getDirList(ICMS_ROOT_PATH . "/language/") as $language) {
				$selected_language->addOption($language, $language);
			}

			// TODO: add this to admin user-edit functions...
			icms_loadLanguageFile('core', 'notification');
			include_once ICMS_INCLUDE_PATH . '/notification_constants.php';
			$notify_method_select = new icms_form_elements_Select(_NOT_NOTIFYMETHOD, 'notify_method', icms::$user->notify_method);
			$notify_method_select->addOptionArray(array(XOOPS_NOTIFICATION_METHOD_DISABLE=>_NOT_METHOD_DISABLE, XOOPS_NOTIFICATION_METHOD_PM=>_NOT_METHOD_PM, XOOPS_NOTIFICATION_METHOD_EMAIL=>_NOT_METHOD_EMAIL));
			$notify_mode_select = new icms_form_elements_Select(_NOT_NOTIFYMODE, 'notify_mode', icms::$user->notify_mode);
			$notify_mode_select->addOptionArray(array(XOOPS_NOTIFICATION_MODE_SENDALWAYS=>_NOT_MODE_SENDALWAYS, XOOPS_NOTIFICATION_MODE_SENDONCETHENDELETE=>_NOT_MODE_SENDONCE, XOOPS_NOTIFICATION_MODE_SENDONCETHENWAIT=>_NOT_MODE_SENDONCEPERLOGIN));
			$bio_tarea = new icms_form_elements_Textarea(_US_EXTRAINFO, 'bio', icms::$user->getVar('bio', 'E'));
			$cookie_radio_value = empty($_COOKIE[$icmsConfig['usercookie']])?0:1;
			$cookie_radio = new icms_form_elements_Radioyn(_US_USECOOKIE, 'usecookie', $cookie_radio_value, _YES, _NO);
			$pwd_text = new icms_form_elements_Password('', 'password', 10, 255, "", false, ($icmsConfigUser['pass_level']?'password_adv':''));
			$pwd_text2 = new icms_form_elements_Password('', 'vpass', 10, 255);
			$pwd_tray = new icms_form_elements_Tray(_US_PASSWORD . '<br />' . _US_TYPEPASSTWICE);
			$pwd_tray->addElement($pwd_text);
			$pwd_tray->addElement($pwd_text2);
			$pwd_text_old = new icms_form_elements_Password(_US_OLD_PASSWORD, 'old_password', 10, 255);
	$mailok_radio = new icms_form_elements_Radioyn(_US_MAILOK, 'user_mailok', icms::$user->user_mailok);
	$uid_hidden = new icms_form_elements_Hidden('uid', (int)icms::$user->uid);
	$op_hidden = new icms_form_elements_Hidden('op', 'saveuser');
	$submit_button = new icms_form_elements_Button('', 'submit', _US_SAVECHANGES, 'submit');

	$form->addElement($timezone_select);
	$form->addElement($location_text);
	$form->addElement($occupation_text);
	$form->addElement($interest_text);
	$form->addElement($sig_tray);
	if (!empty($icmsConfig['theme_set_allowed'])) {
		$form->addElement($selected_theme);
	}

	if ($icmsConfigMultilang['ml_enable']) {
		$form->addElement($selected_language);
	}

	$form->addElement($umode_select);
	$form->addElement($uorder_select);
	$form->addElement($notify_method_select);
			$form->addElement($notify_mode_select);
			$form->addElement($bio_tarea);
			$form->addElement($pwd_change_radio);
			$form->addElement($pwd_text_old);
			$form->addElement($pwd_tray);
			$form->addElement($pwd_tray_old);
			$form->addElement($cookie_radio);
			$form->addElement($mailok_radio);
			$form->addElement($uid_hidden);
			$form->addElement($op_hidden);
			$form->addElement($token_hidden);
			$form->addElement($submit_button);
			if ($icmsConfigUser['allow_chgmail'] == 1) {
				$form->setRequired($email_text);
			}
			$form->display();
			/** Include the footer file to complete page rendering */
			include ICMS_ROOT_PATH . '/footer.php';
			break;

		case 'avatarform':
			/** Include the header that starts page rendering */
			include ICMS_ROOT_PATH . '/header.php';
			echo "<h4>" . _US_AVATAR . "</h4>";
			echo '<p><a href="userinfo.php?uid=' . (int) icms::$user->uid . '">' . _US_PROFILE . '</a>
			<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;' . _US_UPLOADMYAVATAR . '</p>';
			$oldavatar = icms::$user->user_avatar;
			if (!empty($oldavatar) && $oldavatar != 'blank.gif') {
				echo '<div style="text-align:center;"><h4 style="color:#ff0000; font-weight:bold;">' . _US_OLDDELETED . '</h4>';
				echo '<img src="' . ICMS_UPLOAD_URL . '/' . $oldavatar . '" alt="" /></div>';
			}

			if ($icmsConfigUser['avatar_allow_upload'] == 1 && icms::$user->posts >= $icmsConfigUser['avatar_minposts']) {
				$form = new icms_form_Theme(_US_UPLOADMYAVATAR, 'uploadavatar', 'edituser.php', 'post', true);
				$form->setExtra('enctype="multipart/form-data"');
				$form->addElement(new icms_form_elements_Label(_US_MAXPIXEL, icms_conv_nr2local($icmsConfigUser['avatar_width']) . ' x ' . icms_conv_nr2local($icmsConfigUser['avatar_height'])));
				$form->addElement(new icms_form_elements_Label(_US_MAXIMGSZ, icms_conv_nr2local($icmsConfigUser['avatar_maxsize'])));
				$form->addElement(new icms_form_elements_File(_US_SELFILE, 'avatarfile', icms_conv_nr2local($icmsConfigUser['avatar_maxsize'])), true);
				$form->addElement(new icms_form_elements_Hidden('op', 'avatarupload'));
				$form->addElement(new icms_form_elements_Hidden('uid', icms::$user->uid));
				$form->addElement(new icms_form_elements_Button('', 'submit', _SUBMIT, 'submit'));
				$form->display();
			}
			$avatar_handler = icms::handler('icms_data_avatar');
			$form2 = new icms_form_Theme(_US_CHOOSEAVT, 'uploadavatar', 'edituser.php', 'post', true);
			$avatar_select = new icms_form_elements_Select('', 'user_avatar', icms::$user->user_avatar);
			$avatar_select->addOptionArray($avatar_handler->getList('S'));
			$avatar_select->setExtra("onchange='showImgSelected(\"avatar\", \"user_avatar\", \"uploads\", \"\", \"" . ICMS_URL . "\")'");
			$avatar_tray = new icms_form_elements_Tray(_US_AVATAR, '&nbsp;');
			$avatar_tray->addElement($avatar_select);
			$avatar_tray->addElement(new icms_form_elements_Label('', "<img src='" . ICMS_UPLOAD_URL . "/" . icms::$user->getVar("user_avatar", "E") . "' name='avatar' id='avatar' alt='' /> <a href=\"javascript:openWithSelfMain('" . ICMS_URL . "/misc.php?action=showpopups&amp;type=avatars','avatars',600,400);\">" . _LIST . "</a>"));
			if ($icmsConfigUser['avatar_allow_upload'] == 1 && icms::$user->posts < $icmsConfigUser['avatar_minposts']) {
				$form2->addElement(new icms_form_elements_Label(sprintf(_US_POSTSNOTENOUGH, icms_conv_nr2local($icmsConfigUser['avatar_minposts'])), _US_UNCHOOSEAVT));
			}
				$form2->addElement($avatar_tray);
				$form2->addElement(new icms_form_elements_Hidden('uid', icms::$user->uid));
				$form2->addElement(new icms_form_elements_Hidden('op', 'avatarchoose'));
				$form2->addElement(new icms_form_elements_Button('', 'submit2', _SUBMIT, 'submit'));
				$form2->display();
				/** Include the footer file to complete page rendering */
				include ICMS_ROOT_PATH . '/footer.php';
		break;

		case 'avatarupload':
			if (!icms::$security->check()) {
				redirect_header('index.php', 3, _US_NOEDITRIGHT . "<br />" . implode('<br />', icms::$security->getErrors()));
			}
			$xoops_upload_file = array();
			if (!empty($_POST['xoops_upload_file']) && is_array($_POST['xoops_upload_file'])) {
				$xoops_upload_file = $_POST['xoops_upload_file'];
			}

			if (!empty($uid)) {
				$uid = (int) $uid;
			}

			if (empty($uid) || icms::$user->uid != $uid) {
				redirect_header('index.php', 3, _US_NOEDITRIGHT);
			}
			if ($icmsConfigUser['avatar_allow_upload'] == 1 && icms::$user->posts >= $icmsConfigUser['avatar_minposts']) {
				$uploader = new icms_file_MediaUploadHandler(ICMS_UPLOAD_PATH, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png', 'image/png'), $icmsConfigUser['avatar_maxsize'], $icmsConfigUser['avatar_width'], $icmsConfigUser['avatar_height']);
				if ($uploader->fetchMedia($_POST['xoops_upload_file'][0])) {
					$uploader->setPrefix('cavt');
					if ($uploader->upload()) {
						$avt_handler = icms::handler('icms_data_avatar');
						$avatar = & $avt_handler->create();
						$avatar->avatar_file = $uploader->getSavedFileName();
						$avatar->avatar_name = icms::$user->uname;
						$avatar->avatar_mimetype = $uploader->getMediaType();
						$avatar->avatar_display = 1;
						$avatar->avatar_type = 'C';
						if (!$avt_handler->insert($avatar)) {
							@unlink($uploader->getSavedDestination());
						} else {
							$oldavatar = icms::$user->user_avatar;
							if (!empty($oldavatar) && preg_match("/^cavt/", strtolower($oldavatar))) {
								$avatars = & $avt_handler->getObjects(new icms_db_criteria_Item('avatar_file', $oldavatar));
								if (!empty($avatars) && count($avatars) == 1 && is_object($avatars[0])) {
									$avt_handler->delete($avatars[0]);
									$oldavatar_path = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH . '/' . $oldavatar));
									if (0 === strpos($oldavatar_path, ICMS_UPLOAD_PATH) && is_file($oldavatar_path)) {
										unlink($oldavatar_path);
									}
								}
							}
							$sql = sprintf("UPDATE %s SET user_avatar = %s WHERE uid = '%u'",
							 	icms::$xoopsDB->prefix('users'),
							 	icms::$xoopsDB->quoteString($uploader->getSavedFileName()),
							 	(int) icms::$user->uid
							);
							icms::$xoopsDB->query($sql);
							$avt_handler->addUser($avatar->avatar_id, (int) icms::$user->uid);
							redirect_header('userinfo.php?t=' . time() . '&amp;uid=' . (int) icms::$user->uid, 0, _US_PROFUPDATED);
						}
					}
				}
				/** Include the header that starts page rendering */
				include ICMS_ROOT_PATH . '/header.php';
				echo $uploader->getErrors();
				/** Include the footer file to complete page rendering */
				include ICMS_ROOT_PATH . '/footer.php';
			}
		break;

		case 'avatarchoose':
			if (!icms::$security->check()) {
				redirect_header('index.php', 3, _US_NOEDITRIGHT . "<br />" . implode('<br />', icms::$security->getErrors()));
			}

			if (!empty($uid)) {
				$uid = (int) $uid;
			}

			if (empty($uid) || icms::$user->uid != $uid) {
				redirect_header('index.php', 3, _US_NOEDITRIGHT);
			}

			$avt_handler = icms::handler('icms_data_avatar');
			if (!empty($user_avatar)) {
				$user_avatar = DataFilter::addSlashes(trim($user_avatar));
				$criteria_avatar = new icms_db_criteria_Compo(new icms_db_criteria_Item('avatar_file', $user_avatar));
				$criteria_avatar->add(new icms_db_criteria_Item('avatar_type', "S"));
				$avatars = &$avt_handler->getObjects($criteria_avatar);
				if (!is_array($avatars) || !count($avatars)) {
					$user_avatar = 'blank.gif';
				}
				unset($avatars, $criteria_avatar);
			}

			$user_avatarpath = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH . '/' . $user_avatar));
			if (0 === strpos($user_avatarpath, ICMS_UPLOAD_PATH) && is_file($user_avatarpath)) {
				$oldavatar = icms::$user->user_avatar;
				icms::$user->user_avatar = $user_avatar;
				$member_handler = icms::handler('icms_member');
				if (!$member_handler->insertUser(icms::$user)) {
					/** Include the header that starts page rendering */
					include ICMS_ROOT_PATH . '/header.php';
					echo icms::$user->getHtmlErrors();
					/** Include the footer file to complete page rendering */
					include ICMS_ROOT_PATH . '/footer.php';
					exit();
				}
				if ($oldavatar && preg_match("/^cavt/", strtolower($oldavatar))) {
					$avatars = & $avt_handler->getObjects(new icms_db_criteria_Item('avatar_file', $oldavatar));
					if (!empty($avatars) && count($avatars) == 1 && is_object($avatars[0])) {
						$avt_handler->delete($avatars[0]);
						$oldavatar_path = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH . '/' . $oldavatar));
						if (0 === strpos($oldavatar_path, ICMS_UPLOAD_PATH) && is_file($oldavatar_path)) {
							unlink($oldavatar_path);
						}
					}
				}
				if ($user_avatar != 'blank.gif') {
					$avatars = & $avt_handler->getObjects(new icms_db_criteria_Item('avatar_file', $user_avatar));
					if (is_object($avatars[0])) {
						$avt_handler->addUser($avatars[0]->avatar_id, icms::$user->uid);
					}
				}
			}
			redirect_header('userinfo.php?uid=' . $uid, 0, _US_PROFUPDATED);
		break;
}
