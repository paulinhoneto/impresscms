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
 * Template file object
 *
 * @copyright    http://www.impresscms.org/ The ImpressCMS Project
 * @license    LICENSE.txt
 */

namespace ImpressCMS\Core\Models;

use icms;

/**
 * Base class for all templates
 *
 * @author      Kazumi Ono (AKA onokazu)
 * @copyright    Copyright (c) 2000 XOOPS.org
 * @package    ICMS\View\Template\File
 *
 * @property int $tpl_id            Template ID
 * @property int $tpl_refid
 * @property string $tpl_tplset        Template set
 * @property string $tpl_file          Template filename
 * @property string $tpl_desc          Description
 * @property int $tpl_lastmodified  When it was last modified?
 * @property int $tpl_lastimported  When it was last imported?
 * @property string $tpl_module        Module
 * @property string $tpl_type          Type
 * */
class TemplateFile extends AbstractExtendedModel
{

	public $tpl_source = false;

	/**
	 * @inheritDoc
	 *
	 * @todo: move here tpl_source
	 */
	public function __construct($handler, $data = array())
	{
		$this->initVar('tpl_id', self::DTYPE_INTEGER, null, false);
		$this->initVar('tpl_refid', self::DTYPE_INTEGER, 0, false);
		$this->initVar('tpl_tplset', self::DTYPE_STRING, null, false, 50);
		$this->initVar('tpl_file', self::DTYPE_STRING, null, true, 100);
		$this->initVar('tpl_desc', self::DTYPE_STRING, null, false, 100);
		$this->initVar('tpl_lastmodified', self::DTYPE_INTEGER, 0, false);
		$this->initVar('tpl_lastimported', self::DTYPE_INTEGER, 0, false);
		$this->initVar('tpl_module', self::DTYPE_STRING, null, false, 25);
		$this->initVar('tpl_type', self::DTYPE_STRING, null, false, 20);
		//$this->initVar('tpl_source', self::DTYPE_DEP_SOURCE, null, false);

		parent::__construct($handler, $data);
	}

	/**
	 * Gets Template Source
	 */
	public function getSource()
	{
		$sql = "SELECT tpl_source FROM " . $this->handler->db->prefix('tplsource')
			. " WHERE tpl_id='" . $this->tpl_id . "'";

		$result = $this->handler->db->query($sql);
		if (!$result) {
			return false;
		}
		$myrow = $this->handler->db->fetchArray($result);

		if (!$myrow) {
			// trying to resolve with DB resource resolver
			$resolver = icms::getInstance()->get('smarty.helper.db_resource_resolver');
			$filename = $resolver([
				'tpl_module' => $this->tpl_module,
				'tpl_type' => $this->tpl_type,
				'tpl_file' => $this->tpl_file
			]);
			return $filename ? file_get_contents($filename) : null;
		}

		return $myrow['tpl_source'];
	}

	public function getVar($name, $format = 's')
	{
		if ($name === 'tpl_source') {
			if ($this->tpl_source === false) {
				$this->tpl_source = $this->getSource();
			}
			return $this->tpl_source;
		}

		return parent::getVar($name, $format);
	}

	public function assignVar($name, &$value)
	{
		if ($name === 'tpl_source') {
			$this->tpl_source = $value;
		} else {
			parent::assignVar($name, $value);
		}
	}

	public function setVar($name, $value, $options = null)
	{
		if ($name === 'tpl_source') {
			$this->tpl_source = $value;
		} else {
			parent::setVar($name, $value, $options);
		}
	}

	/**
	 * Gets Last Modified timestamp
	 */
	public function getLastModified()
	{
		return $this->tpl_lastmodified;
	}
}

