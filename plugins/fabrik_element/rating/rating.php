<?php
/**
 * Plugin element to render rating widget
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.rating
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to render rating widget
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.rating
 * @since       3.0
 */

class PlgFabrik_ElementRating extends PlgFabrik_Element
{
	/**
	 * Db table field type
	 *
	 * @var string
	 */
	protected $fieldDesc = 'TINYINT(%s)';

	/**
	 * Db table field size
	 *
	 * @var string
	 */
	protected $fieldSize = '1';

	/**
	 * Average ratings
	 *
	 * @var array
	 */
	protected $avgs = null;

	/**
	 * Can the rating element be used by the current user
	 *
	 * @var bool
	 */
	protected $canRate = null;

	/**
	 * creator id
	 *
	 * @var array
	 */
	protected $creatorIds = null;

	/**
	 * States the element should be ignored from advanced search all queries.
	 *
	 * @var bool  True, ignore in advanced search all.
	 */
	protected $ignoreSearchAllDefault = true;

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string    $data      elements data
	 * @param   stdClass  &$thisRow  all the data in the lists current row
	 *
	 * @return  string	formatted value
	 */

	public function renderListData($data, stdClass &$thisRow)
	{
		$params = $this->getParams();
		$formid = $this->getFormModel()->getId();
		$listid = $this->getListModel()->getId();
		$row_id = isset($thisRow->__pk_val) ? $thisRow->__pk_val : $thisRow->id;

		if ($params->get('rating-mode') !== 'creator-rating')
		{
			$d = $this->getListModel()->getData();
			$ids = JArrayHelper::getColumn($d, '__pk_val');
			list($data, $total) = $this->getRatingAverage($data, $listid, $formid, $row_id, $ids);
		}

		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$user = JFactory::getUser();
		$data = FabrikWorker::JSONtoData($data, true);
		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/rating/images/', 'image', 'list', false);
		$colData = $this->getListModel()->getData();
		$ids = JArrayHelper::getColumn($colData, '__pk_val');
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
				$imgOpts = array('icon-class' => 'starRating rate_' . $r);
				$imgOpts['data-fabrik-rating'] = $r;
				$img = FabrikHelperHTML::image("star.png", 'list', @$this->tmpl, $imgOpts);
				$str[] = $a . $img . $a2;
			}

			for ($s = $avg; $s < 5; $s++)
			{
				$r = $s + 1;
				$a = str_replace('{r}', $r, $atpl);
				$imgOpts = array('icon-class' => 'starRating rate_' . $r);
				$imgOpts['data-fabrik-rating'] = $r;
				$img = FabrikHelperHTML::image("star-empty.png", 'list', @$this->tmpl, $imgOpts);

				$str[] = $a . $img . $a2;
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
			$d = $this->getListModel()->getData();
			$ids = JArrayHelper::getColumn($d, '__pk_val');
			$row_id = isset($thisRow->__pk_val) ? $thisRow->__pk_val : $thisRow->id;
			list($avg, $total) = $this->getRatingAverage($data, $listid, $formid, $row_id, $ids);

			return $avg;
		}
	}

	/**
	 * Get average rating
	 *
	 * @param   mixed  $data    String/int
	 * @param   int    $listid  List id
	 * @param   int    $formid  Form id
	 * @param   int    $row_id  Row id
	 * @param   array  $ids     Row ids
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
		$app = JFactory::getApplication();
		$params = $this->getParams();

		if ($params->get('rating-mode') == 'user-rating')
		{
			$this->canRate = true;

			return true;
		}

		if (is_null($row_id))
		{
			$row_id = $app->input->get('rowid', '', 'string');
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
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();

		if ($input->get('view') == 'form' && $params->get('rating-rate-in-form', true) == 0)
		{
			return FText::_('PLG_ELEMENT_RATING_ONLY_ACCESSIBLE_IN_DETAILS_VIEW');
		}

		$element = $this->getElement();
		$css = $this->canRate() ? 'cursor:pointer;' : '';
		$value = $this->getValue($data, $repeatCounter);

		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/rating/images/', 'image', 'form', false);
		$str = array();
		$str[] = '<div id="' . $id . '_div" class="fabrikSubElementContainer">';
		$imgOpts = array('icon-class' => 'small', 'style' => $css, 'data-rating' => -1);
		$clearImg = FabrikHelperHTML::image('remove.png', 'list', @$this->tmpl, $imgOpts);

		if ($params->get('rating-nonefirst') && $this->canRate())
		{
			$str[] = $clearImg;
		}

		$listid = $this->getlistModel()->getTable()->id;
		$formid = $input->getInt('formid');
		$row_id = $this->getFormModel()->getRowId();

		if ($params->get('rating-mode') == 'creator-rating')
		{
			$avg = $value;
			$this->avg = $value;
		}
		else
		{
			list($avg, $total) = $this->getRatingAverage($value, $listid, $formid, $row_id);
		}

		$imgOpts = array('icon-class' => 'starRating', 'style' => $css);

		for ($s = 0; $s < $avg; $s++)
		{
			$imgOpts['data-rating'] = $s + 1;
			$str[] = FabrikHelperHTML::image("star.png", 'list', @$this->tmpl, $imgOpts);
		}

		for ($s = $avg; $s < 5; $s++)
		{
			$imgOpts['data-rating'] = $s + 1;
			$str[] = FabrikHelperHTML::image("star-empty.png", 'list', @$this->tmpl, $imgOpts);
		}

		if (!$params->get('rating-nonefirst') && $this->canRate())
		{
			$str[] = $clearImg;
		}

		$str[] = '<span class="ratingScore badge badge-info">' . $this->avg . '</span>';
		$str[] = '<div class="ratingMessage">';
		$str[] = '</div>';
		$str[] = '<input type="hidden" name="' . $name . '" id="' . $id . '" value="' . $value . '" />';
		$str[] = '</div>';

		return implode("\n", $str);
	}

	/**
	 * Manipulates posted form data for insertion into database
	 *
	 * @param   mixed  $val   this elements posted form data
	 * @param   array  $data  posted form data
	 *
	 * @return  mixed
	 */

	public function storeDatabaseFormat($val, $data)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$params = $this->getParams();
		$listid = $input->getInt('listid');
		$formid = $input->getInt('formid');
		$row_id = $input->get('rowid', '', 'string');

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
		$app = JFactory::getApplication();
		$input = $app->input;
		$this->setId($input->getInt('element_id'));
		$this->loadMeForAjax();
		$listModel = $this->getListModel();
		$list = $listModel->getTable();
		$listid = $list->id;
		$formid = $listModel->getFormModel()->getId();
		$row_id = $input->get('row_id');
		$rating = $input->getInt('rating');
		$this->doRating($listid, $formid, $row_id, $rating);

		if ($input->get('mode') == 'creator-rating')
		{
			// @todo FIX for joins as well

			// Store in elements table as well
			$db = $listModel->getDb();
			$element = $this->getElement();
			$query = $db->getQuery(true);
			$query->update($list->db_table_name)
			->set($element->name . '=' . $rating)->where($list->db_primary_key . ' = ' . $db->quote($row_id));
			$db->setQuery($query);
			$db->execute();
		}

		$this->getRatingAverage('', $listid, $formid, $row_id);
		echo $this->avg;
	}

	/**
	 * Get cookie name
	 *
	 * @param   int     $listid  List id
	 * @param   string  $row_id  Row id
	 *
	 * @return string  Hashed cookie name.
	 */

	private function getCookieName($listid, $row_id)
	{
		$cookieName = "rating-table_{$listid}_row_{$row_id}" . $_SERVER['REMOTE_ADDR'];
		jimport('joomla.utilities.utility');

		return JApplication::getHash($cookieName);
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
		$db->execute();
	}

	/**
	 * Main method to store a rating
	 *
	 * @param   int     $listid  List id
	 * @param   int     $formid  Form id
	 * @param   string  $row_id  Row reference
	 * @param   int     $rating  Rating
	 *
	 * @return  void
	 */

	private function doRating($listid, $formid, $row_id, $rating)
	{
		$this->createRatingTable();
		$db = FabrikWorker::getDbo(true);
		$config = JFactory::getConfig();
		$tzoffset = $config->get('offset');
		$date = JFactory::getDate('now', $tzoffset);
		$strDate = $db->quote($date->toSql());
		$userid = $db->quote($this->getStoreUserId($listid, $row_id));
		$elementid = (int) $this->getElement()->id;
		$query = $db->getQuery(true);
		$formid = (int) $formid;
		$listid = (int) $listid;
		$rating = (int) $rating;
		$row_id = $db->quote($row_id);
		$db
			->setQuery(
				"INSERT INTO #__fabrik_ratings (user_id, listid, formid, row_id, rating, date_created, element_id)
		values ($userid, $listid, $formid, $row_id, $rating, $strDate, $elementid)
			ON DUPLICATE KEY UPDATE date_created = $strDate, rating = $rating"
		);

		$db->execute();
	}

	/**
	 * Get the stored user id
	 *
	 * @param   int     $listid  List id
	 * @param   string  $row_id  Row reference
	 *
	 * @return Mixed string/int
	 */

	private function getStoreUserId($listid, $row_id)
	{
		$user = JFactory::getUser();
		$userid = (int) $user->get('id');

		if ($userid === 0)
		{
			$hash = $this->getCookieName($listid, $row_id);

			// Set cookie
			$lifetime = time() + 365 * 24 * 60 * 60;
			setcookie($hash, '1', $lifetime, '/');
			$userid = $hash;
		}

		return $userid;
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */

	public function elementJavascript($repeatCounter)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$user = JFactory::getUser();
		$params = $this->getParams();

		if ($input->get('view') == 'form' && $params->get('rating-rate-in-form', true) == 0)
		{
			return;
		}

		$id = $this->getHTMLId($repeatCounter);
		$element = $this->getElement();
		$data = $this->getFormModel()->data;
		$listModel = $this->getlistModel();
		$listid = $listModel->getTable()->id;
		$formid = $listModel->getFormModel()->getId();
		$row_id = $input->get('rowid', '', 'string');
		$value = $this->getValue($data, $repeatCounter);

		if ($params->get('rating-mode') != 'creator-rating')
		{
			list($value, $total) = $this->getRatingAverage($value, $listid, $formid, $row_id);
		}

		$opts = new stdClass;

		if (!FabrikWorker::j3())
		{
			$ext = $params->get('rating-pngorgif', '.png');
			$opts->insrc = FabrikHelperHTML::image("star.png", 'form', @$this->tmpl, array(), true);
			$opts->outsrc = FabrikHelperHTML::image("star-empty.png", 'form', @$this->tmpl, array(), true);
			$opts->clearoutsrc = $clearsrc = FabrikHelperHTML::image("remove-sign-out.png", 'form', @$this->tmpl, array(), true);
			$opts->clearinsrc = $clearsrc = FabrikHelperHTML::image("remove-sign.png", 'form', @$this->tmpl, array(), true);
		}

		$opts->row_id = $row_id;
		$opts->elid = $this->getElement()->id;
		$opts->userid = (int) $user->get('id');
		$opts->formid = $formid;
		$opts->canRate = (bool) $this->canRate();
		$opts->mode = $params->get('rating-mode');
		$opts->view = $input->get('view');
		$opts->rating = $value;
		$opts->listid = $listid;

		JText::script('PLG_ELEMENT_RATING_NO_RATING');

		return array('FbRating', $id, $opts);
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
		$id = $this->getHTMLId();
		$listModel = $this->getlistModel();
		$list = $listModel->getTable();
		$opts = new stdClass;
		$opts->listid = $list->id;
		$imagepath = JUri::root() . '/plugins/fabrik_element/rating/images/';
		$opts->imagepath = $imagepath;
		$opts->elid = $this->getElement()->id;

		if (!FabrikWorker::j3())
		{
			$opts->insrc = FabrikHelperHTML::image("star.png", 'list', @$this->tmpl, array(), true);
			$opts->outsrc = FabrikHelperHTML::image("star-empty.png", 'list', @$this->tmpl, array(), true);
		}

		$opts->ajaxloader = FabrikHelperHTML::image("ajax-loader.gif", 'list', @$this->tmpl, array(), true);
		$opts->listRef = $listModel->getRenderContext();
		$opts->formid = $listModel->getFormModel()->getId();
		$opts->userid = (int) $user->get('id');
		$opts->mode = $params->get('rating-mode');
		$opts = json_encode($opts);

		return "new FbRatingList('$id', $opts);\n";
	}

	/**
	 * Used by radio and dropdown elements to get a dropdown list of their unique
	 * unique values OR all options - based on filter_build_method
	 *
	 * @param   bool    $normal     Do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  Table name to use - defaults to element's current table
	 * @param   string  $label      Field to use, defaults to element name
	 * @param   string  $id         Field to use, defaults to element name
	 * @param   bool    $incjoin    Include join
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
	 * @param   bool    $normal     Do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  Table name to use - defaults to element's current table
	 * @param   string  $label      Field to use, defaults to element name
	 * @param   string  $id         Field to use, defaults to element name
	 * @param   bool    $incjoin    Include join
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

	/**
	 * Get the class to manage the form element
	 * to ensure that the file is loaded only once
	 *
	 * @param   array   &$srcs   Scripts previously loaded
	 * @param   string  $script  Script to load once class has loaded
	 * @param   array   &$shim   Dependant class names to load before loading the class - put in requirejs.config shim
	 *
	 * @return void
	 */

	public function formJavascriptClass(&$srcs, $script = '', &$shim = array())
	{
		$s = new stdClass;
		$s->deps = array('fab/elementlist');
		$shim['element/rating/rating'] = $s;
		parent::formJavascriptClass($srcs, $script, $shim);
	}
}
