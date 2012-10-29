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
 * @since       3.0
 */

class plgFabrik_ElementRating extends plgFabrik_Element
{

	/** @var  string  db table field type */
	protected $fieldDesc = 'TINYINT(%s)';

	/** @var  string  db table field size */
	protected $fieldSize = '1';

	/** @var array average ratings */
	protected $avgs = null;

	/** @bool can the rating element be used by the current user*/
	protected $canRate = null;

	/** @var array creator id */
	protected $creatorIds = null;

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string  $data      elements data
	 * @param   object  &$thisRow  all the data in the lists current row
	 *
	 * @return  string	formatted value
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
			$atpl = '';
			$a2 = '';
			$css = $canRate ? 'cursor:pointer;' : '';
			$str = array();
			$str[] = '<div style="width:101px;position:relative;">';
			for ($s = 0; $s < $avg; $s++)
			{
				$r = $s + 1;
				$a = str_replace('{r}', $r, $atpl);
				$str[] = $a . '<img src="' . $imagepath . 'star_in' . $ext . '" style="padding-left:1px;' . $css . '" alt="' . $r . '" class="starRating rate_'
					. $r . '"/>' . $a2;
			}
			for ($s = $avg; $s < 5; $s++)
			{
				$r = $s + 1;
				$a = str_replace('{r}', $r, $atpl);
				$str[] = $a . '<img src="' . $imagepath . 'star_out' . $ext . '" style="padding-left:1px;' . $css . '" alt="' . $r . '" class="starRating rate_'
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

	/**
	 * Display the file in the table
	 *
	 * @param   string  $data     current cell data
	 * @param   array   $thisRow  current row data
	 *
	 * @return	string
	 */

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
	 * Get average rating
	 *
	 * @param   mixed  $data    string/int
	 * @param   int    $listid  int list id
	 * @param   int    $formid  int form id
	 * @param   int    $row_id  int row id
	 * @param   array  $ids     all row ids
	 *
	 * @return array(int average rating, int total)
	 */

	protected function getRatingAverage($data, $listid, $formid, $row_id, $ids = array())
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

			$query = $db->getQuery(true);
			$query->select('row_id, AVG(rating) AS r, COUNT(rating) AS total')->from(' #__{package}_ratings')
				->where(array('rating <> -1', 'listid = ' . (int) $listid, 'formid = ' . (int) $formid, 'element_id = ' . (int) $elementid))
				->where('row_id IN (' . implode(',', $ids) . ')')->group('row_id');

			// Do this  query so that list view only needs one query to load up all ratings
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
	 * Get creator ids
	 *
	 * @param   int    $listid  int list id
	 * @param   int    $formid  int form id
	 * @param   int    $row_id  int row id
	 * @param   array  $ids     all row ids
	 *
	 * @return  int  user id
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
			$query = $db->getQuery(true);
			$query->select('row_id, user_id')->from('#__{package}_ratings')
				->where(array('rating <> -1', 'listid = ' . (int) $listid, 'formid = ' . (int) $formid, 'element_id = ' . (int) $elementid))
				->where('row_id IN (' . implode(',', $ids) . ')')->group('row_id');

			// Do this  query so that table view only needs one query to load up all ratings
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
	 * Determines if the element can contain data used in sending receipts,
	 * e.g. fabrikfield returns true
	 *
	 * @deprecated - not used
	 *
	 * @return  bool
	 */

	public function isReceiptElement()
	{
		return true;
	}

	/**
	 * Can we rate this row
	 *
	 * @param   int    $row_id  row id
	 * @param   array  $ids     list row ids
	 *
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
	 * Draws the html form element
	 *
	 * @param   array  $data           to preopulate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
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
	 * Manupulates posted form data for insertion into database
	 *
	 * @param   mixed  $val   this elements posted form data
	 * @param   array  $data  posted form data
	 *
	 * @return  mixed
	 */

	public function storeDatabaseFormat($val, $data)
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
	 * Called via widget ajax, stores the selected rating and returns the average
	 *
	 * @return  void
	 */

	public function onAjax_rate()
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

			// Store in elements table as well
			$db = $listModel->getDb();
			$element = $this->getElement();
			$query = $db->getQuery(true);
			$query->update($list->db_table_name)
			->set($element->name . '=' . $rating)->where($list->db_primary_key . ' = ' . $db->quote($row_id));
			$db->setQuery($query);
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
	 * Create the rating table if it doesn't exist.
	 *
	 * @return  void
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
	 * Main method to store a rating
	 *
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
		$strDate = $db->quote($date->toSql());
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
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  string
	 */

	public function elementJavascript($repeatCounter)
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

		$opts = new stdClass;
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
	 * Get JS code for ini element list js
	 * Overwritten in plugin classes
	 *
	 * @return string
	 */

	public function elementListJavascript()
	{
		$user = JFactory::getUser();
		$params = $this->getParams();
		$user = JFactory::getUser();
		$id = $this->getHTMLId();
		$list = $this->getlistModel()->getTable();
		$ext = $params->get('rating-pngorgif', '.png');

		$opts = new stdClass;
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

	/**
	 * Should the element's data be returned in the search all?
	 *
	 * @param   bool  $advancedMode  is the elements' list is advanced search all mode?
	 *
	 * @return  bool	true
	 */

	public function includeInSearchAll($advancedMode = false)
	{
		return false;
	}

	/**
	 * Used by radio and dropdown elements to get a dropdown list of their unique
	 * unique values OR all options - basedon filter_build_method
	 *
	 * @param   bool    $normal     do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  table name to use - defaults to element's current table
	 * @param   string  $label      field to use, defaults to element name
	 * @param   string  $id         field to use, defaults to element name
	 * @param   bool    $incjoin    include join
	 *
	 * @return  array  text/value objects
	 */

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
	 * Create an array of label/values which will be used to populate the elements filter dropdown
	 * returns all possible options
	 *
	 * @param   bool    $normal     do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  table name to use - defaults to element's current table
	 * @param   string  $label      field to use, defaults to element name
	 * @param   string  $id         field to use, defaults to element name
	 * @param   bool    $incjoin    include join
	 *
	 * @return  array	filter value and labels
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
