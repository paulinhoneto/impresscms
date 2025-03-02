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
 * ImpressCMS Block Persistable Class
 *
 * @copyright 	The ImpressCMS Project <http://www.impresscms.org>
 * @license	GNU General Public License (GPL) <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @author	Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 */

namespace ImpressCMS\Core\Models;

use icms;
use ImpressCMS\Core\Database\Criteria\CriteriaCompo;
use ImpressCMS\Core\Database\Criteria\CriteriaItem;

/**
 * ImpressCMS Core Block Object Handler Class
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org>
 * @license	GNU GPL v2
 * @package	ICMS\View\Block
 * @since	ImpressCMS 1.2
 * @author	Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 */
class BlockHandler extends AbstractExtendedHandler {

	private $block_positions;
	private $modules_name;

	public function __construct(& $db) {
		parent::__construct($db, 'view_block', 'bid', 'title', 'content', 'icms', 'newblocks');
	}

	// The next methods are for backwards compatibility

	/**
	 * getBlockPositions
	 *
	 * @param bool $full
	 * @return array
	 */
	public function getBlockPositions($full = false) {
		if (empty($this->block_positions)) {
			// TODO: Implement IPF for block_positions
			$icms_blockposition_handler = icms::handler('icms_view_block_position');
			//			$sql = 'SELECT * FROM '.$this->db->prefix('block_positions').' ORDER BY id ASC';
			//			$result = $this->db->query($sql);
			//			while ($row = $this->db->fetchArray($result)) {
			$block_positions = $icms_blockposition_handler->getObjects();
			foreach ($block_positions as $bp) {
				$this->block_positions[$bp->id]['pname'] = $bp->pname;
				$this->block_positions[$bp->id]['title'] = $bp->title;
				$this->block_positions[$bp->id]['description'] = $bp->description;
				$this->block_positions[$bp->id]['block_default'] = $bp->block_default;
				$this->block_positions[$bp->id]['block_type'] = $bp->block_type;
			}
		}
		if (!$full) {
			foreach ($this->block_positions as $k => $block_position) {
				$rtn[$k] = $block_position['pname'];
			}
		} else {
			$rtn = $this->block_positions;
		}
		return $rtn;
	}

	/**
	 * getByModule
	 *
	 * @param unknown_type $mid
	 * @param boolean $asObject
	 * @return array
	 *
	 * @see $this->getObjects($criteria, false, $asObject);
	 * @todo Rewrite all the core to dont use any more this method.
	 */
	public function getByModule($mid, $asObject = true) {
		$mid = (int) $mid;
		$criteria = new CriteriaCompo();
		$criteria->add(new CriteriaItem('mid', $mid));
		$ret = $this->getObjects($criteria, false, $asObject);
		return $ret;
	}

	/**
	 * getAllBlocks
	 *
	 * @param string $rettype
	 * @param string $side
	 * @param bool $visible
	 * @param string $orderby
	 * @param bool $isactive
	 * @return array
	 *
	 * @todo Implement IPF for block_positions.
	 * @todo Rewrite all the core to dont use any more this method.
	 */
	public function getAllBlocks($rettype = 'object', $side = null, $visible = null, $orderby = 'side, weight, bid', $isactive = 1) {
		$ret = array();
		$where_query = " WHERE isactive='" . (int) $isactive . "'";

		if (isset($side)) {
			// get both sides in sidebox? (some themes need this)
			$tp = ($side == -2)?'L':(($side == -6)?'C':'');
			if ($tp != '') {
			 	$q_side = '';
				$icms_blockposition_handler = icms::handler('icms_view_block_position');
				$criteria = new CriteriaCompo();
				$criteria->add(new CriteriaItem('block_type', $tp));
				$blockpositions = $icms_blockposition_handler->getObjects($criteria);
				foreach ($blockpositions as $bp) {
					$q_side .= "side='" . (int) $bp->id . "' OR ";
				}
				$q_side = "('" . substr($q_side, 0, strlen($q_side) - 4) . "')";
			} else {
				$q_side = "side='" . (int) $side . "'";
			}
			$where_query .= ' AND ' . $q_side;
		}

		if (isset($visible)) {
			$where_query .= " AND visible='" . (int) $visible . "'";
		}
		$where_query .= " ORDER BY $orderby";
		switch ($rettype) {
			case 'object':
				$sql = 'SELECT * FROM ' . $this->db->prefix("newblocks") . "" . $where_query;
				$result = $this->db->query($sql);
				while ($myrow = $this->db->fetchArray($result)) {
					// @todo this is causing to many SQL queries. In case this section is still needed,
					// we should switch it just like it's done in the list case
					$ret[] = $this->get($myrow['bid']);
				}
				break;

			case 'list':
				$sql = 'SELECT * FROM ' . $this->db->prefix('newblocks') . $where_query;
				$result = $this->db->query($sql);
				if ($this->db->getRowsNum($result) > 0) {
					$blockids = array();
					while ($myrow = $this->db->fetchArray($result)) {
						$blockids[] = $myrow['bid'];
					}
					$criteria = new CriteriaCompo();
					$criteria->add(new CriteriaItem('bid', '(' . implode(',', $blockids) . ')', 'IN'));
					$blocks = $this->getObjects($criteria, true, true);
					foreach ($blocks as $block) {
						$ret[$block->bid] = $block->title;
					}
					unset($blockids, $blocks);
				}
				break;

			case 'id':
				$sql = 'SELECT bid FROM ' . $this->db->prefix('newblocks') . $where_query;
				$result = $this->db->query($sql);
				while ($myrow = $this->db->fetchArray($result)) {
					$ret[] = $myrow['bid'];
				}
				break;

			default:
				break;
		}
		return $ret;
	}

	public function &get($id, $as_object = true, $debug = false, $criteria = false)
	{
		$obj = parent::get($id, $as_object, $debug, $criteria);
		$sql = "SELECT module_id, page_id FROM " . $this->db->prefix('block_module_link')
			. " WHERE block_id='" . (int)$obj->bid . "'";
		$result = $this->db->query($sql);
		$modules = $bcustomp = array();
		while ($row = $this->db->fetchArray($result)) {
			$modules[] = (int)$row['module_id'] . '-' . (int)$row['page_id'];
		}
		$obj->visiblein = $modules;
		return $obj;
	}

	/**
	 * getAllByGroupModule gets all blocks visible on a page, based on group permissions
	 *
	 * @param unknown_type $groupid
	 * @param unknown_type $module_id
	 * @param unknown_type $toponlyblock
	 * @param unknown_type $visible
	 * @param unknown_type $orderby
	 * @param unknown_type $isactive
	 * @return unknown
	 *
	 * @todo rewrite
	 */
	public function getAllByGroupModule($groupid, $module_id = '0-0', $toponlyblock = false, $visible = null, $orderby = 'b.weight, b.bid', $isactive = 1) {
		// TODO: use $this->getObjects($criteria);

		$isactive = (int) $isactive;
		$ret = array();
		$sql = 'SELECT DISTINCT gperm_itemid FROM ' . $this->db->prefix('group_permission')
			. " WHERE gperm_name = 'block_read' AND gperm_modid = '1'";
		if (is_array($groupid)) {
			$gid = array_map('intval', $groupid);
			$sql .= ' AND gperm_groupid IN (' . implode(',', $gid) . ')';
		} else {
			if ((int) $groupid > 0) {
				$sql .= " AND gperm_groupid='" . (int) $groupid . "'";
			}
		}
		$result = $this->db->query($sql);
		$blockids = array();
		while ($myrow = $this->db->fetchArray($result)) {
			$blockids[] = $myrow['gperm_itemid'];
		}

		if (!empty($blockids)) {
			$sql = 'SELECT b.* FROM ' . $this->db->prefix('newblocks') . ' b, ' . $this->db->prefix('block_module_link')
				. ' m WHERE m.block_id=b.bid';
			$sql .= " AND b.isactive='" . $isactive . "'";
			if (isset($visible)) {
				$sql .= " AND b.visible='" . (int) ($visible) . "'";
			}

			$arr = explode('-', $module_id);
			$module_id = (int) $arr[0];
			$page_id = (int) $arr[1];
			if ($module_id == 0) {
				//Entire Site
				if ($page_id == 0) {
					//All pages
					$sql .= " AND m.module_id='0' AND m.page_id=0";
				} elseif ($page_id == 1) {
//Top Page
					$sql .= " AND ((m.module_id='0' AND m.page_id=0) OR (m.module_id='0' AND m.page_id=1))";
				}
			} else {
				//Specific Module (including system)
				if ($page_id == 0) {
					//All pages of this module
					$sql .= " AND ((m.module_id='0' AND m.page_id=0) OR (m.module_id='$module_id' AND m.page_id=0))";
				} else {
					//Specific Page of this module
					$sql .= " AND ((m.module_id='0' AND m.page_id=0) OR (m.module_id='$module_id' AND m.page_id=0) OR (m.module_id='$module_id' AND m.page_id=$page_id))";
				}
			}

			$sql .= ' AND b.bid IN (' . implode(',', $blockids) . ')';
			$sql .= ' ORDER BY ' . $orderby;
			$result = $this->db->query($sql);

			// old method of gathering block data. Since this could result in a whole bunch of queries, a new method was introduced
			/*while ($myrow = $this->db->fetchArray($result)) {
				$block =& $this->get($myrow['bid']);
				$ret[$myrow['bid']] =& $block;
				unset($block);
			}*/

			if ($this->db->getRowsNum($result) > 0) {
				unset($blockids);
				while ($myrow = $this->db->fetchArray($result)) {
					$blockids[] = $myrow['bid'];
				}
				$ret = $this->getMultiple($blockids);
			}
		}
		return $ret;

	}

	/**
	 * Get block data for multiple block ids
	 *
	 * @param array $blockids
	 *
	 * @return array
	 * @todo can be removed together with getAllByGroupModule and getNonGroupedBlocks. (used in theme_blocks)
	 */
	private function &getMultiple($blockids)
	{
		$criteria = new CriteriaCompo();
		$criteria->add(new CriteriaItem('bid', '(' . implode(',', $blockids) . ')', 'IN'));
		$criteria->setSort('weight');
		$ret = $this->getObjects($criteria, true, true);
		$sql = 'SELECT block_id, module_id, page_id FROM ' . $this->db->prefix('block_module_link')
			. ' WHERE block_id IN (' . implode(',', array_keys($ret)) . ') ORDER BY block_id';
		$result = $this->db->query($sql);
		$modules = array();
		$last_block_id = 0;
		while ($row = $this->db->fetchArray($result)) {
			$modules[] = (int)($row['module_id']) . '-' . (int)($row['page_id']);
			$ret[$row['block_id']]->visiblein = $modules;
			if ($row['block_id'] != $last_block_id) {
				$modules = array();
			}
			$last_block_id = $row['block_id'];
		}
		return $ret;
	}

	/**
	 * getNonGroupedBlocks
	 *
	 * @param unknown_type $module_id
	 * @param unknown_type $toponlyblock
	 * @param unknown_type $visible
	 * @param unknown_type $orderby
	 * @param unknown_type $isactive
	 * @return unknown
	 *
	 * @todo remove - this is the only instance in the core
	 */
	public function getNonGroupedBlocks($module_id = 0, $toponlyblock = false, $visible = null, $orderby = 'b.weight, b.bid', $isactive = 1) {
		$ret = array();
		$bids = array();
		$sql = 'SELECT DISTINCT(bid) from ' . $this->db->prefix('newblocks');
		if ($result = $this->db->query($sql)) {
			while ($myrow = $this->db->fetchArray($result)) {
				$bids[] = $myrow['bid'];
			}
		}
		$sql = 'SELECT DISTINCT(p.gperm_itemid) from ' . $this->db->prefix('group_permission') . ' p, '
			. $this->db->prefix('groups') . " g WHERE g.groupid=p.gperm_groupid AND p.gperm_name='block_read'";
		$grouped = array();
		if ($result = $this->db->query($sql)) {
			while ($myrow = $this->db->fetchArray($result)) {
				$grouped[] = $myrow['gperm_itemid'];
			}
		}
		$non_grouped = array_diff($bids, $grouped);
		if (!empty($non_grouped)) {
			$sql = 'SELECT b.* FROM ' . $this->db->prefix('newblocks') . ' b, '
				. $this->db->prefix('block_module_link') . ' m WHERE m.block_id=b.bid';
			$sql .= " AND b.isactive='" . (int) $isactive . "'";
			if (isset($visible)) {
				$sql .= " AND b.visible='" . (int) $visible . "'";
			}
			$module_id = (int) $module_id;
			if (!empty($module_id)) {
				$sql .= " AND m.module_id IN ('0', '" . (int) $module_id . "'";
				if ($toponlyblock) {
					$sql .= ",'-1'";
				}
				$sql .= ')';
			} else {
				if ($toponlyblock) {
					$sql .= " AND m.module_id IN ('0', '-1')";
				} else {
					$sql .= " AND m.module_id='0'";
				}
			}
			$sql .= ' AND b.bid IN (' . implode(',', $non_grouped) . ')';
			$sql .= ' ORDER BY ' . $orderby;
			$result = $this->db->query($sql);

			// old method of gathering block data. Since this could result in a whole bunch of queries, a new method was introduced
			/*while ($myrow = $this->db->fetchArray($result)) {
				$block =& $this->get($myrow['bid']);
				$ret[$myrow['bid']] =& $block;
				unset($block);
			}*/

			if ($this->db->getRowsNum($result) > 0) {
				unset($blockids);
				while ($myrow = $this->db->fetchArray($result)) {
					$blockids[] = $myrow['bid'];
				}
				$ret = $this->getMultiple($blockids);
			}
		}
		return $ret;
	}

	/**
	 * Save a Block
	 *
	 * Overwrited Method
	 *
	 * @param unknown_type $obj
	 * @param unknown_type $force
	 * @param unknown_type $checkObject
	 * @param unknown_type $debug
	 * @return unknown
	 */
	public function insert(& $obj, $force = false, $checkObject = true, $debug = false) {
		$new = $obj->isNew();
		$obj->last_modified = time();
		$obj->isactive = true;
		if (!$new) {
			$sql = sprintf("DELETE FROM %s WHERE block_id = '%u'",
				$this->db->prefix('block_module_link'), (int) $obj->bid);
			if (false != $force) {
				$this->db->queryF($sql);
			} else {
				$this->db->query($sql);
			}
		} else {
			icms_loadLanguageFile('system', 'blocks', true);
			if ($obj->block_type === Block::BLOCK_TYPE_DUPLICATED) {
				$obj->name = _AM_CLONE;
			} else {
				switch ($obj->c_type) {
					case 'H':
						$obj->name = _AM_CUSTOMHTML;
						break;

					case 'P':
						$obj->name = _AM_CUSTOMPHP;
						break;

					case 'S':
						$obj->name = _AM_CUSTOMSMILE;
						break;

					case 'T':
						$obj->name = _AM_CUSTOMNOSMILE;
						break;
					default:
						break;
				}
			}
		}
		$status = parent::insert($obj, $force, $checkObject, $debug);
		// TODO: Make something to no query here... implement IPF for block_module_link
		$page = $obj->getVar('visiblein', 'e');
		if (!empty($page)) {
			if (is_array($obj->getVar('visiblein', 'e'))) {
				foreach ($obj->getVar('visiblein', 'e') as $bmid) {
					$page = explode('-', $bmid);
					$mid = $page[0];
					$pageid = $page[1];
					$sql = "INSERT INTO " . $this->db->prefix('block_module_link')
						. " (block_id, module_id, page_id) VALUES ('"
						. (int) $obj->bid . "', '"
						. (int) $mid . "', '"
						. (int) $pageid . "')";
					if (false != $force) {
						$this->db->queryF($sql);
					} else {
						$this->db->query($sql);
					}
				}
			} else {
				$page = explode('-', $obj->getVar('visiblein', 'e'));
				$mid = $page[0];
				$pageid = $page[1];
				$sql = "INSERT INTO " . $this->db->prefix('block_module_link') . " (block_id, module_id, page_id) VALUES ('"
					. (int) $obj->bid . "', '"
					. (int) $mid . "', '"
					. (int) $pageid . "')";
				if (false != $force) {
					$this->db->queryF($sql);
				} else {
					$this->db->query($sql);
				}
			}
		}
		return $status;

	}

	public function getCountSimilarBlocks($moduleId, $funcNum, $showFunc = null) {
		$funcNum = (int) $funcNum;
		$moduleId = (int) $moduleId;
		if ($funcNum < 1 || $moduleId < 1) {
			return 0;
		}
		$criteria = new CriteriaCompo();
		if (isset($showFunc)) {
			// showFunc is set for more strict comparison
			$criteria->add(new CriteriaItem('mid', $moduleId));
			$criteria->add(new CriteriaItem('func_num', $funcNum));
			$criteria->add(new CriteriaItem('show_func', $showFunc));
		} else {
			$criteria->add(new CriteriaItem('mid', $moduleId));
			$criteria->add(new CriteriaItem('func_num', $funcNum));
		}
		return $this->handler->getCount($criteria);

	}

}

