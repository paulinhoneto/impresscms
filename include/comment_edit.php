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
// URL: http://www.xoops.org/ http://jp.xoops.org/  http://www.myweb.ne.jp/  //
// Project: The XOOPS Project (http://www.xoops.org/)                        //
// ------------------------------------------------------------------------- //

/**
 * The edit comment include file
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 */
include_once ICMS_ROOT_PATH . '/include/comment_constants.php';
$ph = icms::handler('icms_member_groupperm');

if (('system' != $icmsModule->dirname
		&& XOOPS_COMMENT_APPROVENONE == $icmsModuleConfig['com_rule'])
	|| (!is_object(icms::$user) && !$ph->checkRight('system_admin', XOOPS_SYSTEM_COMMENT, array(ICMS_GROUP_ANONYMOUS)))
	|| !is_object($icmsModule)
) {
	redirect_header(ICMS_URL . '/user.php', 1, _NOPERM);
}

icms_loadLanguageFile('core', 'comment');
$com_id = isset($_GET['com_id'])?(int) $_GET['com_id']:0;
$com_mode = isset($_GET['com_mode'])
	? htmlspecialchars(trim($_GET['com_mode']), ENT_QUOTES, _CHARSET)
	: '';
if ($com_mode == '') {
	if (is_object(icms::$user)) {
		$com_mode = icms::$user->umode;
	} else {
		$com_mode = $icmsConfig['com_mode'];
	}
}
if (!isset($_GET['com_order'])) {
	if (is_object(icms::$user)) {
		$com_order = icms::$user->uorder;
	} else {
		$com_order = $icmsConfig['com_order'];
	}
} else {
	$com_order = (int) $_GET['com_order'];
}
$comment_handler = icms::handler('icms_data_comment');
$comment = & $comment_handler->get($com_id);
$dohtml = $comment->dohtml;
$dosmiley = $comment->dosmiley;
$dobr = $comment->dobr;
$doxcode = $comment->doxcode;
$com_icon = $comment->com_icon;
$com_itemid = $comment->com_itemid;
$com_title = $comment->getVar('com_title', 'E');
$com_text = $comment->getVar('com_text', 'E');
$com_pid = $comment->com_pid;
$com_status = $comment->com_status;
$com_rootid = $comment->com_rootid;
if ($icmsModule->dirname != 'system') {
	include ICMS_ROOT_PATH . '/header.php';
	include ICMS_ROOT_PATH . '/include/comment_form.php';
	include ICMS_ROOT_PATH . '/footer.php';
} else {
	icms_cp_header();
	include ICMS_ROOT_PATH . '/include/comment_form.php';
	icms_cp_footer();
}
