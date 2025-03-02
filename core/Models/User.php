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
// Author: Kazumi Ono (AKA onokazu)                                          //
// URL: http://www.myweb.ne.jp/, http://www.xoops.org/, http://jp.xoops.org/ //
// Project: The XOOPS Project                                                //
// ------------------------------------------------------------------------- //
/**
 * Manage users
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 */

namespace ImpressCMS\Core\Models;

use icms;
use ImpressCMS\Core\Database\Criteria\CriteriaItem;
use ImpressCMS\Core\DataFilter;
use ImpressCMS\Core\Facades\Aura;
use ImpressCMS\Core\Facades\Member;
use ImpressCMS\Core\Messaging\MessageSender;

/**
 * Class for users
 *
 * @author	Kazumi Ono <onokazu@xoops.org>
 * @copyright	Copyright (c) 2000 XOOPS.org
 * @package	ICMS\Member\User
 *
 * @property int    $uid               User ID
 * @property string $name              Name
 * @property string $uname             Username
 * @property string $email             Email
 * @property string $url               Homepage URL
 * @property array  $user_avatar       Avatar
 * @property int    $user_regdate      Registration date
 * @property string $user_from         From
 * @property string $user_sig          Signature
 * @property bool   $user_viewemail    Can others view email?
 * @property string $actkey            Activation key
 * @property string $pass              Encoded password
 * @property int    $posts             Post count written by this user
 * @property bool   $attachsig         Attach signature?
 * @property int    $rank              Rank
 * @property float  $level             Level
 * @property string $theme             Selected theme
 * @property float  $timezone_offset   Timezone offset
 * @property int    $last_login        Last login time
 * @property int    $umode             Comments display mode
 * @property int    $uorder            Comments order mode
 * @property int    $notify_method     Notification method
 * @property int    $notify_mode       Notification mode
 * @property string $user_occ          Occupation
 * @property string $bio               BIO
 * @property string $user_intrest      Interests
 * @property int    $user_mailok       Are sending mails ok?
 * @property string $language          Language
 * @property bool   $pass_expired      Is password expired?
 * @property string $login_name        Login name
 */
class User extends AbstractExtendedModel {

	/**
	 * @var bool is the user admin?
	 */
	static private $_isAdmin = array();
	/**
	 * Array of groups that user belongs to
	 * @var array
	 */
	private $_groups = array();
	/**
	 * @var string user's rank
	 */
	private $_rank = null;

	/**
	 * @var bool is the user online?
	 */
	private $_isOnline = null;

	/**
	 * @inheritDoc
	 */
	public function __construct(&$handler, $data = array()) {
		//parent::__construct($handler, $data);
		$this->initVar('uid', self::DTYPE_INTEGER, null, false, null, null, null, 'User ID');
		$this->initVar('name', self::DTYPE_STRING, null, false, 60, null, null, _US_REALNAME);
		$this->initVar('uname', self::DTYPE_STRING, null, true, 255, null, null, 'User Name');
		$this->initVar('email', self::DTYPE_STRING, null, true, 60, null, null, _US_EMAIL);
		$this->initVar('url', self::DTYPE_STRING, null, false, 255, null, null, _US_WEBSITE);
		$this->initVar('user_avatar', self::DTYPE_FILE, null, false, 30, null, null, _US_AVATAR);
		$this->initVar('user_regdate', self::DTYPE_INTEGER, null, false, null, null, null,
			'Registration date');
		$this->initVar('user_from', self::DTYPE_STRING, null, false, 100, null, null, _US_LOCATION);
		$this->initVar('user_sig', self::DTYPE_STRING, null, false, null, null, null, _US_SIGNATURE);
		$this->initVar('user_viewemail', self::DTYPE_BOOLEAN, 0, false, null, null, null, _US_ALLOWVIEWEMAIL);
		$this->initVar('actkey', self::DTYPE_STRING, null, false, 100, null, null, 'Activation key');
		$this->initVar('pass', self::DTYPE_STRING, null, false, 255, null, null, _US_PASSWORD);
		$this->initVar('posts', self::DTYPE_INTEGER, null, false, null, null, null, _US_POSTS);
		$this->initVar('attachsig', self::DTYPE_BOOLEAN, 0, false, null, null, null, _US_SHOWSIG);
		$this->initVar('rank', self::DTYPE_INTEGER, 0, false, null, null, null, _US_RANK);
		$this->initVar('level', self::DTYPE_FLOAT, 0, false, null, null, null, 'Level');
		$this->initVar('theme', self::DTYPE_STRING, null, false, null, null, null, _US_SELECT_THEME);
		$this->initVar('timezone_offset', self::DTYPE_FLOAT, null, false, null, null, null, _US_TIMEZONE);
		$this->initVar('last_login', self::DTYPE_INTEGER, 0, false, null, null, null, _US_LASTLOGIN);
		$this->initVar('umode', self::DTYPE_INTEGER, null, false, null, null, null, _US_CDISPLAYMODE);
		$this->initVar('uorder', self::DTYPE_INTEGER, 1, false, null, null, null, _US_CSORTORDER);
		// RMV-NOTIFY
		$this->initVar('notify_method', self::DTYPE_INTEGER, 1, false, null, null, null, _NOT_NOTIFYMETHOD);
		$this->initVar('notify_mode', self::DTYPE_INTEGER, 0, false, null, null, null, _NOT_NOTIFYMODE);
		$this->initVar('user_occ', self::DTYPE_STRING, null, false, 100, null, null, _US_OCCUPATION);
		$this->initVar('bio', self::DTYPE_STRING, null, false, null, null, null, null, _US_EXTRAINFO);
		$this->initVar('user_intrest', self::DTYPE_STRING, null, false, 150, null, null, _US_INTEREST);
		$this->initVar('user_mailok', self::DTYPE_INTEGER, 1, false, null, null, null, _US_MAILOK);

		$this->initVar('language', self::DTYPE_STRING, null, false, null, null, null, _US_SELECT_LANG);
		$this->initVar('pass_expired', self::DTYPE_BOOLEAN, 0, false, null, null, null, 'Pass Expired?');
		$this->initVar('login_name', self::DTYPE_STRING, null, true, 255, null, null, _US_LOGINNAME);

		if (isset($data['_rank'])) {
			$this->_rank = $data['_rank'];
			unset($data['_rank']);
		}
		if (isset($data['_groups'])) {
			$this->_groups = $data['_groups'];
			unset($data['_groups']);
		}

		parent::__construct($handler, $data);
	}

	/**
	 * Updated by Catzwolf 11 Jan 2004
	 * find the username for a given ID
	 *
	 * @param int $userid ID of the user to find
	 * @param int $usereal switch for usename or realname
	 * @return string name of the user. name for "anonymous" if not found.
	 */
	public static function getUnameFromId($userid, $usereal = 0)
	{
		trigger_error('Use same function from handler. This one is deprecahed!', E_DEPRECATED);
		$handler = icms::handler('icms_member_user');
		return $handler->getUnameFromId($userid, (bool)$usereal);
	}

	/**
	 * check if the user is a guest user
	 *
	 * @return bool returns false
	 */
	public function isGuest() {
		return false;
	}

	public function getForm($form_caption, $form_name, $form_action = false, $submit_button_caption = _CO_ICMS_SUBMIT, $cancel_js_action = false, $captcha = false) {
		$this->hideFieldFromForm('pass');

		$this->makeFieldReadOnly('posts');
		$this->makeFieldReadOnly('user_regdate');
		$this->makeFieldReadOnly('last_login');
		$this->makeFieldReadOnly('uid');
		$this->makeFieldReadOnly('actkey');

		$this->setControl('theme', 'theme');
		$this->setControl('language', 'language');
		$this->setControl('uname', 'text');
		$this->setControl('login_name', 'text');
		$this->setControl('actkey', 'text');
		$this->setControl('name', 'text');
		$this->setControl('url', 'text');
		$this->setControl('email', 'text');
		$this->setControl('timezone_offset', 'timezone');
		$this->setControl('user_from', 'country');
		$this->setControl('last_login', 'date');
		$this->setControl('user_regdate', 'date');
		$this->setControl('notify_method', 'notify_method');
		$this->setControl('pass_expired', 'yesno');
		$this->setControl('user_mailok', 'yesno');
		$this->setControl('attachsig', 'yesno');
		$this->setControl('rank', [
			'name' => 'select',
			'itemHandler' => 'member_rank',
			'module' => 'icms',
			'method' => 'getList'
		]);
		$this->setControl('notify_method', [
			'name' => 'select',
			'options' => [
				XOOPS_NOTIFICATION_METHOD_DISABLE => _NOT_METHOD_DISABLE,
				XOOPS_NOTIFICATION_METHOD_PM => _NOT_METHOD_PM,
				XOOPS_NOTIFICATION_METHOD_EMAIL => _NOT_METHOD_EMAIL
			]
		]);
		$this->setControl('notify_mode', [
			'name' => 'select',
			'options' => [
				XOOPS_NOTIFICATION_MODE_SENDALWAYS => _NOT_MODE_SENDALWAYS,
				XOOPS_NOTIFICATION_MODE_SENDONCETHENDELETE => _NOT_MODE_SENDONCE,
				XOOPS_NOTIFICATION_MODE_SENDONCETHENWAIT => _NOT_MODE_SENDONCEPERLOGIN
			]
		]);
		$this->setControl('umode', [
			'name' => 'select',
			'options' => ['nest' => _NESTED, 'flat' => _FLAT, 'thread' => _THREADED]
		]);
		$this->setControl('uorder', [
			'name' => 'select',
			'options' => ['0' => _OLDESTFIRST, '1' => _NEWESTFIRST]
		]);
		return parent::getForm($form_caption, $form_name, $form_action, $submit_button_caption, $cancel_js_action, $captcha);
	}

	/**
	 * sends a welcome message to the user which account has just been activated
	 *
	 * return TRUE if success, FALSE if not
	 */
	public function sendWelcomeMessage() {
		global $icmsConfig, $icmsConfigUser;

		if (!$icmsConfigUser['welcome_msg']) {
					return true;
		}

        $mailer = new MessageSender();
        $mailer->useMail();
        $mailer->setBody($icmsConfigUser['welcome_msg_content']);
        $mailer->assign('UNAME', $this->uname);
        $user_email = $this->email;
        $mailer->assign('X_UEMAIL', $user_email);
        $mailer->setToEmails($user_email);
        $mailer->setFromEmail($icmsConfig['adminmail']);
        $mailer->setFromName($icmsConfig['sitename']);
        $mailer->setSubject(sprintf(_US_YOURREGISTRATION, DataFilter::stripSlashesGPC($icmsConfig['sitename'])));
        if (!$mailer->send(true)) {
            $this->setErrors(_US_WELCOMEMSGFAILED);
            return false;
        } else {
            return true;
        }
    }

	/**
	 * sends a notification to admins to inform them that a new user registered
	 *
	 * This method first checks in the preferences if we need to send a notification to admins upon new user
	 * registration. If so, it sends the mail.
	 *
	 * return TRUE if success, FALSE if not
	 */
	public function newUserNotifyAdmin() {
		global $icmsConfigUser, $icmsConfig;

        if ($icmsConfigUser['new_user_notify'] == 1 && !empty($icmsConfigUser['new_user_notify_group'])) {
            $member_handler = icms::handler('icms_member');
            $mailer = new MessageSender();
            $mailer->useMail();
            $mailer->setTemplate('newuser_notify.tpl');
            $mailer->assign('UNAME', $this->uname);
            $mailer->assign('EMAIL', $this->email);
            $mailer->setToGroups($member_handler->getGroup($icmsConfigUser['new_user_notify_group']));
            $mailer->setFromEmail($icmsConfig['adminmail']);
            $mailer->setFromName($icmsConfig['sitename']);
            $mailer->setSubject(sprintf(_US_NEWUSERREGAT, $icmsConfig['sitename']));
            if (!$mailer->send(true)) {
                $this->setErrors(_US_NEWUSERNOTIFYADMINFAIL);
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

	/**
	 * Is the user admin ?
	 *
	 * This method will return true if this user has admin rights for the specified module.<br />
	 * - If you don't specify any module ID, the current module will be checked.<br />
	 * - If you set the module_id to -1, it will return true if the user has admin rights for at least one module
	 *
	 * @param int $module_id check if user is admin of this module
	 * @staticvar array $buffer result buffer
	 * @return bool is the user admin of that module?
	 */
	public function isAdmin($module_id = null) {
		static $buffer = array();
		if ($module_id === null) {
			$module_id = isset($GLOBALS['xoopsModule'])?$GLOBALS['xoopsModule']->mid:1;
		} elseif ((int) $module_id < 1) {
			$module_id = 0;
		}

		if (!isset($buffer[$module_id])) {
			$moduleperm_handler = icms::handler('icms_member_groupperm');
			$buffer[$module_id] = $moduleperm_handler->checkRight('module_admin', $module_id, $this->getGroups());
		}
		return $buffer[$module_id];
	}

	/**
	 * get the groups that the user belongs to
	 *
	 * @return array array of groups
	 */
	public function &getGroups()
	{
		if (empty($this->_groups)) {
			/**
			 * @var Member $member_handler
			 */
			$member_handler = icms::handler('icms_member');
			$this->_groups = $member_handler->getGroupsByUser($this->uid);
		}
		return $this->_groups;
	}

	/**
	 * set the groups for the user
	 *
	 * @param array $groupsArr Array of groups that user belongs to
	 */
	public function setGroups($groupsArr)
	{
		if (is_array($groupsArr)) {
			$this->_groups = &$groupsArr;
		}
	}

	/**
	 * is the user activated?
	 * @return bool
	 */
	public function isActive() {
		return $this->level > 0;
	}

	/**
	 * is the user currently logged in?
	 * @return bool
	 */
	public function isOnline() {
		if (!isset($this->_isOnline)) {
			$onlinehandler = icms::handler('icms_core_Online');
			$this->_isOnline = $onlinehandler->getCount(new CriteriaItem('online_uid', $this->uid)) > 0;
		}
		return $this->_isOnline;
	}

	/**
	 * Gravatar plugin for ImpressCMS
	 * @param bool $rating
	 * @param bool $size (size in pixels of the image. Accept values between 1 to 80. Default 80)
	 * @param bool $default (url of default avatar. Will be used if no gravatar are found)
	 * @param bool $border (hexadecimal color)
	 *
	 * @param bool $overwrite
	 * @return string (gravatar or ImpressCMS avatar)
	 * @author TheRplima
	 *
	 */
	public function gravatar($rating = false, $size = false, $default = false, $border = false, $overwrite = false) {
		if (!$overwrite && is_file(ICMS_UPLOAD_PATH . '/' . $this->user_avatar) && $this->user_avatar != 'blank.gif') {
			return ICMS_UPLOAD_URL . '/' . $this->user_avatar;
		}
		$ret = '//www.gravatar.com/avatar/' . md5(strtolower($this->getVar('email', 'E'))) . '?d=identicon';
		if ($rating) {
			$ret .= '&amp;rating=' . $rating;
		}
		if ($size) {
			$ret .= '&amp;size=' . $size;
		}
		if ($default) {
			$ret .= '&amp;default=' . urlencode($default);
		}
		if ($border) {
			$ret .= '&amp;border=' . $border;
		}
		return $ret;
	}

	/**
	 * Returns uid of user
	 *
	 * @deprecated Use $this->uid instead! Since 2.0
	 *
	 * @return int
	 */
	public function uid() {
		trigger_error('Use $this->uid instead!', E_USER_DEPRECATED);
		return $this->uid;
	}

	/**
	 * Logs in current user
	 */
	public function login() {
		$this->last_login = time();
		$this->store();
		$data = $this->toArray();
		$data['_rank'] = $this->rank();
		$data['_groups'] = $this->getGroups();
		unset($data['itemLink'], $data['itemUrl'], $data['editItemLink'], $data['deleteItemLink'], $data['printAndMailLink']);

		/**
		 * @var Aura\Session\Session $session
		 */
		$session = icms::$session;
		$userSegment = $session->getSegment('user');
		foreach ($data as $key => $value) {
			$userSegment->set($key, $value);
		}
	}

	public function setVar($name, $value, $options = null)
	{
		parent::setVar($name, $value, $options);
		if ($this->isSameAsLoggedInUser()) {

			/**
			 * @var Aura\Session\Session $session
			 */
			$session = icms::$session;
			$userSegment = $session->getSegment('user');
			$userSegment->set($name, parent::getVar($name));
		}
	}

	/**
	 * Checks if this user is same as logged in user
	 *
	 * @return boolean
	 */
	public function isSameAsLoggedInUser()
	{
		if (!icms::$user) {
					return false;
		}
		return icms::$user->uid == $this->uid;
	}

	/**
	 * get the user's rank
	 * @return array array of rank ID and title
	 */
	public function rank()
	{
		if (!isset($this->_rank)) {
			$this->_rank = icms::handler('icms_member_rank')->getRank($this->rank, $this->posts);
		}
		return $this->_rank;
	}

	/**
	 * Logs out current user
	 *
	 * @return boolean
	 */
	public function logout()
	{
		/**
		 * @var Aura\Session\Session $session
		 */
		$session = icms::$session;
		$userSegment = $session->getSegment('user');

		if ($userid = $userSegment->get('userid')) {
					return false;
		}
		if ($userid != $this->uid) {
			return false;
		}
		$session->clear();
	}
}
