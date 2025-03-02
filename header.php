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
 * This file is used as header for all places where content is generated not in object way
 *
 * @copyright    http://www.xoops.org/ The XOOPS Project
 * @copyright    http://www.impresscms.org/ The ImpressCMS Project
 * @package     ImpressCMS/Core
 *
 * @todo        Remove this file in the future
 */

use ImpressCMS\Core\Models\ModuleHandler;
use ImpressCMS\Core\Response\ViewResponse;

icms::$logger->stopTime('Module init');
icms::$logger->startTime('ICMS output init');

global $xoopsOption;
$xoopsOption['theme_use_smarty'] = 1;
$xoopsOption['response'] = new ViewResponse($xoopsOption);
global $icmsTpl, $xoopsTpl;

icms::$logger->stopTime('ICMS output init');

if (icms::$module && !ModuleHandler::checkModuleAccess(icms::$module, false)) {
	return redirect_header(ICMS_URL . "/user.php", 3, _NOPERM, FALSE);
}

icms::$logger->startTime('Module display');
