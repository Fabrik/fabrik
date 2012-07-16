<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.rating
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to render rating widget
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.rating
 */

class plgFabrik_ElementRating extends plgFabrik_Element
{

	protected $fieldDesc = 'TINYINT(%s)';

	protected $fieldSize = '1';

	/** @var array average ratings */
	var $avgs = null;

	/** @bool can the rating element be used by the current user*/
	var $canRate = null;

	/** @var array creator id */
	var $creatorIds = null;

	/**
	 * (non-PHPdoc)
	 * @see plgFabrik_Element::renderListData()
	 */

	public function renderListData($data, &$thisRow)
	{
		$user = JFactory::getUser();
		$params = $this->getParams();
		$ext = $params->get('rating-pngorgif', '.png');
		$imagepath = JUri::root() . '/plugins/fabrik_element/rating/images/';
		$data = FabrikWorker::JSONtoData($data, true);

		$url = COM_FABRIK_LIVESITE
			. 'index.php?option=com_fabrik&amp;format=raw&amp;view=plugin&amp;task=pluginAjax&amp;g=element&amp;plugin=rating&amp;method=ajax_rate&amp;element_id='
			. $this->getElement()->id;
		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/rating/images/', 'image', 'list', false);
		$insrc = FabrikHelperHTML::image("star_in$ext", 'list', @$this->tmpl, array(), true);
		$outsrc = FabrikHelperHTML::image("star_out$ext", 'list', @$this->tmpl, array(), true);

		$url .= '&amp;row_id=' . $thisRow->__pk_val;
		$url .= '&amp;elementname=' . $this->getElement()->id;
		$url .= '&amp;userid=' . $user->get('id');
		$url .= '&amp;nonajax=1';
		$row_id = isset($thisRow->__pk_val) ? $thisRow->__pk_val : $thisRow->id;
		$ids = JArrayHelper::getColumn($this->getListModel()->getData(), '__pk_val');
		$canRate = $this->canRate($row_id, $ids);
		for ($i = 0; $i < count($data); $i++)
		{
			$avg = $this->_renderListData($data[$i], $thisRow);
			if (!$canRate)
			{
				$atpl = '';
				$a2 = '';
			}
			else
			{
				$atpl = "<a href=\"{$url}&amp;rating={r}\">";
				$a2 = "</a>";
			}
			$str = array();
			$str[] = '<div style="width:101px">';
			for ($s = 0; $s < $avg; $s++)
			{
				$r = $s + 1;
				$a = str_replace('{r}', $r, $atpl);
				$str[] = $a . '<img src="' . $imagepath . 'star_in' . $ext . '" style="padding-left:1px;" alt="' . $r . '" class="starRating rate_'
					. $r . '"/>' . $a2;
			}
			for ($s = $avg; $s < 5; $s++)
			{
				$r = $s + 1;
				$a = str_replace('{r}', $r, $atpl);
				$str[] = $a . '<img src="' . $imagepath . 'star_out' . $ext . '" style="padding-left:1px;" alt="' . $r . '" class="starRating rate_'
					. $r . '"/>' . $a2;
			}
			if ($params->get('rating-mode') != 'creator-rating')
			{
				$str[] = '<div class="ratingMessage">' . $avg . '</div>';
			}
			$str[] = '</div>';
			$data[$i] = implode("\n", $str);
		}
		$data = json_encode($data);
		return parent::renderListData($data, $thisRow);
	}

	private function _renderListData($data, $thisRow)
	{
		$params = $this->getParams();
		if ($params->get('rating-mode') == 'creator-rating')
		{
			return $data;
		}
		else
		{
			$list = $this->getlistModel()->getTable();
			$listid = $list->id;
			$formid = $list->form_id;
			$ids = JArrayHelper::getColumn($this->getListModel()->getData(), '__pk_val');
			$row_id = isset($thisRow->__pk_val) ? $thisRow->__pk_val : $thisRow->id;
			list($avg, $total) = $this->getRatingAverage($data, $listid, $formid, $row_id, $ids);
			return $avg;
		}
	}

	/**
	 * @param $data string/int
	 * @param $listid int table id
	 * @param $formid int form id
	 * @param $row_id int row id
	 * @param $ids array all row ids
	 * @return array(int average rating, int total)
	 */

	function getRatingAverage($data, $listid, $formid, $row_id, $ids = array())
	{
		if (empty($ids))
		{
			$ids[] = $row_id;
		}
		if (!isset($this->avgs))
		{
			JArrayHelper::toInteger($ids);
			$db = FabrikWorker::getDbo(true);
			$elementid = $this->getElement()->id;
			// do this  query so that table view only needs one query to load up all ratings
			$query = "SELECT row_id, AVG(rating) AS r, COUNT(rating) AS total FROM #__{package}_ratings WHERE rating <> -1 AND listid = "
				. (int) $listid . " AND formid = " . (int) $formid . " AND element_id = " . (int) $elementid;
			$query .= " AND row_id IN (" . implode(',', $ids) . ") GROUP BY row_id";
			$db->setQuery($query);
			$this->avgs = (array) $db->loadObjectList('row_id');
		}
		$params = $this->getParams();
		$r = array_key_exists($row_id, $this->avgs) ? $this->avgs[$row_id]->r : 0;
		$t = array_key_exists($row_id, $this->avgs) ? $this->avgs[$row_id]->total : 0;
		$float = (int) $params->get('rating_float', 0);
		$this->avg = number_format($r, $float);
		return array(round($r), $t);
	}

	/**
	 * Enter description here ...
	 * @param unknown_type $listid
	 * @param unknown_type $formid
	 * @param unknown_type $row_id
	 * @param unknown_type $ids
	 */

	protected function getCreatorId($listid, $formid, $row_id, $ids = array())
	{
		if (!isset($this->creatorIds))
		{
			if (empty($ids))
			{
				$ids[] = $row_id;
			}
			JArrayHelper::toInteger($ids);
			$db = FabrikWorker::getDbo(true);
			$elementid = $this->getElement()->id;
			// do this  query so that table view only needs one query to load up all ratings
			$query = "SELECT row_id, user_id FROM #__{package}_ratings WHERE rating <> -1 AND listid = " . (int) $listid . " AND formid = "
				. (int) $formid . " AND element_id = " . (int) $elementid;
			$query .= " AND row_id IN (" . implode(',', $ids) . ") GROUP BY row_id";
			$db->setQuery($query);
			$this->creatorIds = $db->loadObjectList('row_id');
			if ($db->getErrorNum() != 0)
			{
				return false;
				JError::raiseNotice(500, $db->getErrorMsg());
			}
		}
		return array_key_exists($row_id, $this->creatorIds) ? $this->creatorIds[$row_id]->user_id : 0;
	}

	/**
	 * determines if the element can contain data used in sending receipts, e.g. fabrikfield returns true
	 */

	function isReceiptElement()
	{
		return true;
	}

	/**
	 * can we rate this row
	 * @param int $row_id
	 * @param array $ids
	 * @return bool
	 */

	protected function canRate($row_id = null, $ids = array())
	{
		$params = $this->getParams();
		if ($params->get('rating-mode') == 'user-rating')
		{
			$this->canRate = true;
			return true;
		}
		if (is_null($row_id))
		{
			$row_id = JRequest::getInt('rowid');
		}
		$list = $this->getListModel()->getTable();
		$listid = $list->id;
		$formid = $list->form_id;
		$creatorid = $this->getCreatorId($listid, $formid, $row_id, $ids);
		$userid = $this->getStoreUserId($listid, $row_id);
		$this->canRate = ($creatorid == $userid || $row_id == 0);
		return $this->canRate;
	}

	/**
	 * draws the form element
	 * @param array data to preopulate element with
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		if (JRequest::getVar('view') == 'form' && $params->get('rating-rate-in-form', true) == 0)
		{
			return JText::_('PLG_ELEMENT_RATING_ONLY_ACCESSIBLE_IN_DETALS_VIEW');
		}
		$ext = $params->get('rating-pngorgif', '.png');
		$element = $this->getElement();
		$css = $this->canRate() ? 'cursor:pointer;' : '';
		$value = $this->getValue($data, $repeatCounter);

		$imagepath = JUri::root() . '/plugins/fabrik_element/rating/images/';

		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/rating/images/', 'image', 'form', false);
		$insrc = FabrikHelperHTML::image("star_in$ext", 'form', @$this->tmpl, array(), true);
		$outsrc = FabrikHelperHTML::image("star_out$ext", 'form', @$this->tmpl, array(), true);
		$clearsrc = FabrikHelperHTML::image("clear_rating_out$ext", 'form', @$this->tmpl, array(), true);
		$str = array();
		$str[] = '<div id="' . $id . '_div" class="fabrikSubElementContainer">';
		if ($params->get('rating-nonefirst') && $this->canRate())
		{
			$str[] = '<img src="' . $imagepath . 'clear_rating_out' . $ext . '" style="' . $css . 'padding:3px;" alt="clear" class="rate_-1" />';
		}
		$listid = $this->getlistModel()->getTable()->id;
		$formid = JRequest::getInt('formid');
		$row_id = JRequest::getInt('rowid');
		if ($params->get('rating-mode') == 'creator-rating')
		{
			$avg = $value;
			$this->avg = $value;
		}
		else
		{
			list($avg, $total) = $this->getRatingAverage($value, $listid, $formid, $row_id);
		}
		for ($s = 0; $s < $avg; $s++)
		{
			$r = $s + 1;
			$str[] = '<img src="' . $insrc . '" style="' . $css . 'padding:3px;" alt="' . $r . '" class="starRating rate_' . $r . '" />';
		}
		for ($s = $avg; $s < 5; $s++)
		{
			$r = $s + 1;
			$str[] = '<img src="' . $outsrc . '" style="' . $css . 'padding:3px;" alt="' . $r . '" class="starRating rate_' . $r . '" />';
		}

		if (!$params->get('rating-nonefirst') && $this->canRate())
		{
			$str[] = '<img src="' . $clearsrc . '" style="' . $css . 'padding:3px;" alt="clear" class="rate_-1" />';
		}
		$str[] = '<span class="ratingScore">' . $this->avg . '</span>';
		$str[] = '<div class="ratingMessage">';
		$str[] = '</div>';
		$str[] = '<input type="hidden" name="' . $name . '" id="' . $id . '" value="' . $value . '" />';
		$str[] = '</div>';
		return implode("\n", $str);
	}

	/**
	 * (non-PHPdoc)
	 * @see components/com_fabrik/models/plgFabrik_Element#storeDatabaseFormat($val, $data)
	 */

	function storeDatabaseFormat($val, $data)
	{
		$params = $this->getParams();
		$listid = JRequest::getInt('listid');
		$formid = JRequest::getInt('formid');
		$row_id = JRequest::getInt('rowid');
		if ($params->get('rating-mode') == 'user-rating')
		{
			list($val, $total) = $this->getRatingAverage($val, $listid, $formid, $row_id);
		}
		return $val;
	}

	/**
	 * called via widget ajax, stores the selected rating and returns the average
	 */

	function onAjax_rate()
	{
		$this->setId(JRequest::getInt('element_id'));
		$this->getElement();
		$listModel = $this->getListModel();
		$list = $listModel->getTable();
		$listid = $list->id;
		$formid = $listModel->getFormModel()->getId();
		$row_id = JRequest::getVar('row_id');
		$rating = JRequest::getInt('rating');
		$this->doRating($listid, $formid, $row_id, $rating);

		if (JRequest::getVar('mode') == 'creator-rating')
		{
			// @todo FIX for joins as well
			//store in elements table as well
			$db = $listModel->getDb();
			$element = $this->getElement();
			$db->setQuery("UPDATE $list->db_table_name SET $element->name = $rating WHERE $list->db_primary_key = " . $db->quote($row_id));
			$db->query();
		}
		$this->getRatingAverage('', $listid, $formid, $row_id);
		echo $this->avg;
	}

	private function getCookieName($listid, $row_id)
	{
		$cookieName = "rating-table_{$listid}_row_{$row_id}" . $_SERVER['REMOTE_ADDR'];
		jimport('joomla.utilities.utility');
		return JUtility::getHash($cookieName);
	}

	/**
	 *
	 * Create the rating table if it doesn't exist.
	 */
	private function createRatingTable()
	{
		$db = FabrikWorker::getDbo(true);
		$db
			->setQuery(
				"
			CREATE TABLE IF NOT EXISTS  `#__fabrik_ratings` (
			`user_id` VARCHAR( 255 ) NOT NULL ,
			`listid` INT( 6 ) NOT NULL ,
			`formid` INT( 6 ) NOT NULL ,
			`row_id` INT( 6 ) NOT NULL ,
			`rating` INT( 6 ) NOT NULL,
			`date_created` DATETIME NOT NULL,
			`element_id` INT( 6 ) NOT NULL,
	 		PRIMARY KEY (
	 			`user_id` , `listid` , `formid` , `row_id`, `element_id`
	 		)
		);");
		$db->query();
	}

	/**
	 * main method to store a rating
	 * @param $listid
	 * @param $formid
	 * @param $row_id
	 * @param $rating
	 */

	private function doRating($listid, $formid, $row_id, $rating)
	{
		$this->createRatingTable();
		$db = FabrikWorker::getDbo(true);
		$config = JFactory::getConfig();
		$tzoffset = $config->getValue('config.offset');
		$date = JFactory::getDate('now', $tzoffset);
		$strDate = $db->quote($date->toMySQL());
		$userid = $db->quote($this->getStoreUserId($listid, $row_id));
		$elementid = $this->getElement()->id;
		$db
			->setQuery(
				"INSERT INTO #__fabrik_ratings (user_id, listid, formid, row_id, rating, date_created, element_id)
		values ($userid, $listid, $formid, $row_id, $rating, $strDate, $elementid)
			ON DUPLICATE KEY UPDATE date_created = $strDate, rating = $rating");
		$db->query();
	}

	private function getStoreUserId($listid, $row_id)
	{
		$user = JFactory::getUser();
		$userid = (int) $user->get('id');
		if ($userid === 0)
		{
			$hash = $this->getCookieName($listid, $row_id);
			//set cookie
			$lifetime = time() + 365 * 24 * 60 * 60;
			setcookie($hash, '1', $lifetime, '/');
			$userid = $hash;
		}
		return $userid;
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @param int repeat group counter
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$user = JFactory::getUser();
		$params = $this->getParams();
		if (JRequest::getVar('view') == 'form' && $params->get('rating-rate-in-form', true) == 0)
		{
			return;
		}
		$id = $this->getHTMLId($repeatCounter);
		$element = $this->getElement();
		$data = $this->_form->_data;
		$listid = $this->getlistModel()->getTable()->id;
		$formid = JRequest::getInt('formid');
		$row_id = JRequest::getInt('rowid');
		$value = $this->getValue($data, $repeatCounter);
		if ($params->get('rating-mode') != 'creator-rating')
		{
			list($value, $total) = $this->getRatingAverage($value, $listid, $formid, $row_id);
		}

		$opts = new stdClass();
		$ext = $params->get('rating-pngorgif', '.png');
		$opts->insrc = FabrikHelperHTML::image("star_in$ext", 'form', @$this->tmpl, array(), true);
		$opts->outsrc = FabrikHelperHTML::image("star_out$ext", 'form', @$this->tmpl, array(), true);
		$opts->clearoutsrc = $clearsrc = FabrikHelperHTML::image("clear_rating_out$ext", 'form', @$this->tmpl, array(), true);
		$opts->clearinsrc = $clearsrc = FabrikHelperHTML::image("clear_rating_in$ext", 'form', @$this->tmpl, array(), true);
		$opts->row_id = JRequest::getInt('rowid');
		$opts->elid = $this->getElement()->id;
		$opts->userid = (int) $user->get('id');
		$opts->canRate = (bool) $this->canRate();
		$opts->mode = $params->get('rating-mode');
		$opts->view = JRequest::getCmd('view');
		$opts = json_encode($opts);
		JText::script('PLG_ELEMENT_RATING_NO_RATING');

		$str = "new FbRating('$id', $opts, '$value')";
		return $str;
	}

	/**
	 * get js to ini js object that manages the behaviour of the rating element (non-PHPdoc)
	 * @see components/com_fabrik/models/plgFabrik_Element#elementListJavascript()
	 */

	function elementListJavascript()
	{
		$user = JFactory::getUser();
		$params = $this->getParams();
		$user = JFactory::getUser();
		$id = $this->getHTMLId();
		$list = $this->getlistModel()->getTable();
		$ext = $params->get('rating-pngorgif', '.png');

		$opts = new stdClass();
		$opts->listid = $list->id;
		$imagepath = JUri::root() . '/plugins/fabrik_element/rating/images/';
		$opts->imagepath = $imagepath;
		$opts->elid = $this->getElement()->id;
		$opts->insrc = FabrikHelperHTML::image("star_in$ext", 'list', @$this->tmpl, array(), true);
		$opts->outsrc = FabrikHelperHTML::image("star_out$ext", 'list', @$this->tmpl, array(), true);
		$opts->ajaxloader = FabrikHelperHTML::image("ajax-loader.gif", 'list', @$this->tmpl, array(), true);
		$opts->userid = (int) $user->get('id');
		$opts->mode = $params->get('rating-mode');
		$opts = json_encode($opts);
		return "new FbRatingList('$id', $opts);\n";
	}

	function includeInSearchAll($advancedMode = false)
	{
		return false;
	}

	public function filterValueList($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$params = $this->getParams();
		$filter_build = $params->get('filter_build_method', 0);
		if ($filter_build == 0)
		{
			$filter_build = $usersConfig->get('filter_build_method');
		}
		if ($filter_build == 2)
		{
			return $this->filterValueList_All($normal, $tableName, $label, $id, $incjoin);
		}
		else
		{
			return $this->filterValueList_Exact($normal, $tableName, $label, $id, $incjoin);
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see components/com_fabrik/models/plgFabrik_Element#filterValueList_All($normal, $tableName, $label, $id, $incjoin)
	 */

	protected function filterValueList_All($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		for ($i = 0; $i < 6; $i++)
		{
			$return[] = JHTML::_('select.option', $i);
		}
		return $return;
	}
}
?>