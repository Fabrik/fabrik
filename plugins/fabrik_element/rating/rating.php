<?php
/**
 * Plugin element to render rating widget
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.rating
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

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
	 * @param   string    $data      Elements data
	 * @param   stdClass  &$thisRow  All the data in the lists current row
	 * @param   array     $opts      Rendering options
	 *
	 * @return  string	formatted value
	 */
	public function renderListData($data, stdClass &$thisRow, $opts = array())
	{
		$params = $this->getParams();
		$formId = $this->getFormModel()->getId();
		$listId = $this->getListModel()->getId();
		$rowId = isset($thisRow->__pk_val) ? $thisRow->__pk_val : $thisRow->id;

		if ($params->get('rating-mode') !== 'creator-rating')
		{
			$d = $this->getListModel()->getData();
			$ids = ArrayHelper::getColumn($d, '__pk_val');
			list($data, $total) = $this->getRatingAverage($data, $listId, $formId, $rowId, $ids);
		}

		$data = FabrikWorker::JSONtoData($data, true);
		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/rating/images/', 'image', 'list', false);
		$colData = $this->getListModel()->getData();
		$ids = ArrayHelper::getColumn($colData, '__pk_val');
		$canRate = $this->canRate($rowId, $ids);

		for ($i = 0; $i < count($data); $i++)
		{
			$avg = $this->_renderListData($data[$i], $thisRow);
			$atpl = '';
			$a2 = '';
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

		return parent::renderListData($data, $thisRow, $opts);
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
			$listId = $list->id;
			$formId = $list->form_id;
			$d = $this->getListModel()->getData();
			$ids = ArrayHelper::getColumn($d, '__pk_val');
			$rowId = isset($thisRow->__pk_val) ? $thisRow->__pk_val : $thisRow->id;
			list($avg, $total) = $this->getRatingAverage($data, $listId, $formId, $rowId, $ids);

			return $avg;
		}
	}

	/**
	 * Get average rating
	 *
	 * @param   mixed  $data    String/int
	 * @param   int    $listId  List id
	 * @param   int    $formId  Form id
	 * @param   int    $rowId  Row id
	 * @param   array  $ids     Row ids
	 *
	 * @return array(int average rating, int total)
	 */

	protected function getRatingAverage($data, $listId, $formId, $rowId, $ids = array())
	{
		if (empty($ids))
		{
			$ids[] = $rowId;
		}

		if (!isset($this->avgs))
		{
			ArrayHelper::toInteger($ids);
			$db = FabrikWorker::getDbo(true);
			$elementId = $this->getElement()->id;

			$query = $db->getQuery(true);
			$query->select('row_id, AVG(rating) AS r, COUNT(rating) AS total')->from(' #__{package}_ratings')
				->where(array('rating <> -1', 'listid = ' . (int) $listId, 'formid = ' . (int) $formId, 'element_id = ' . (int) $elementId));

			if (FArrayHelper::emptyIsh($ids))
			{
				$query->where('6 = -6');
			}
			else
			{
				$query->where('row_id IN (' . implode(',', $ids) . ')');
			}

			$query->group('row_id');

			// Do this  query so that list view only needs one query to load up all ratings
			$db->setQuery($query);
			$this->avgs = (array) $db->loadObjectList('row_id');
		}

		$params = $this->getParams();
		$r = array_key_exists($rowId, $this->avgs) ? $this->avgs[$rowId]->r : 0;
		$t = array_key_exists($rowId, $this->avgs) ? $this->avgs[$rowId]->total : 0;
		$float = (int) $params->get('rating_float', 0);
		$this->avg = number_format($r, $float);

		return array(round($r), $t);
	}

	/**
	 * Get creator ids
	 *
	 * @param   int    $listId  int list id
	 * @param   int    $formId  int form id
	 * @param   int    $rowId  int row id
	 * @param   array  $ids     all row ids
	 *
	 * @return  int  user id
	 */

	protected function getCreatorId($listId, $formId, $rowId, $ids = array())
	{
		if (!isset($this->creatorIds))
		{
			if (empty($ids))
			{
				$ids[] = $rowId;
			}

			ArrayHelper::toInteger($ids);
			$db = FabrikWorker::getDbo(true);
			$elementId = $this->getElement()->id;
			$query = $db->getQuery(true);
			$query->select('row_id, user_id')->from('#__{package}_ratings')
				->where(array('rating <> -1', 'listid = ' . (int) $listId, 'formid = ' . (int) $formId, 'element_id = ' . (int) $elementId));

			if (FArrayHelper::emptyIsh($ids))
			{
				$query->where('6 = -6');
			}
			else
			{
				$query->where('row_id IN (' . implode(',', $ids) . ')');
			}

			$query->group('row_id');

			// Do this  query so that table view only needs one query to load up all ratings
			$db->setQuery($query);
			$this->creatorIds = $db->loadObjectList('row_id');
		}

		if (empty($this->creatorIds))
		{
			return JFactory::getUser()->get('id');
		}
		else
		{
			return array_key_exists($rowId, $this->creatorIds) ? $this->creatorIds[$rowId]->user_id : 0;
		}
	}

	/**
	 * Can we rate this row
	 *
	 * @param   int    $rowId  row id
	 * @param   array  $ids     list row ids
	 *
	 * @return bool
	 */

	protected function canRate($rowId = null, $ids = array())
	{
		$params = $this->getParams();

		if ($params->get('rating-mode') == 'user-rating')
		{
			$gid = (int) $params->get('rating_access', '1');
			$this->canRate = in_array($gid, $this->user->getAuthorisedViewLevels());
			return $this->canRate;
		}

		if (is_null($rowId))
		{
			$rowId = $this->app->input->get('rowid', '', 'string');
		}

		$list = $this->getListModel()->getTable();
		$listId = $list->id;
		$formId = $list->form_id;
		$creatorId = $this->getCreatorId($listId, $formId, $rowId, $ids);
		$userId = $this->getStoreUserId($listId, $rowId);
		$this->canRate = ($creatorId == $userId || $rowId == 0);

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
		$input = $this->app->input;
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();

		if ($input->get('view') == 'form' && $params->get('rating-rate-in-form', true) == 0)
		{
			return FText::_('PLG_ELEMENT_RATING_ONLY_ACCESSIBLE_IN_DETAILS_VIEW');
		}

		$rowId = $this->getFormModel()->getRowId();

		/*
		if (empty($rowId))
		{
			return FText::_('PLG_ELEMENT_RATING_NO_RATING_TILL_CREATED');
		}
		*/

		$css = $this->canRate($rowId) ? 'cursor:pointer;' : '';
		$value = $this->getValue($data, $repeatCounter);

		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/rating/images/', 'image', 'form', false);

		$listId = $this->getlistModel()->getTable()->id;
		$formId = $input->getInt('formid');
		$rowId = $this->getFormModel()->getRowId();

		if ($params->get('rating-mode') == 'creator-rating')
		{
			$avg = $value;
		}
		else
		{
			list($avg, $total) = $this->getRatingAverage($value, $listId, $formId, $rowId);
		}

		$imgOpts = array('icon-class' => 'small', 'style' => $css, 'data-rating' => -1);

		$layout = $this->getLayout('form');
		$layoutData = new stdClass;
		$layoutData->id = $id;
		$layoutData->name = $name;
		$layoutData->value = $value;
		$layoutData->clearImg = FabrikHelperHTML::image('remove.png', 'list', @$this->tmpl, $imgOpts);
		$layoutData->avg = $avg;
		$layoutData->canRate = $this->canRate($rowId);
		$layoutData->ratingNoneFirst = $params->get('rating-nonefirst');
		$layoutData->css = $css;
		$layoutData->tmpl = @$this->tmpl;

		return $layout->render($layoutData);
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
		$params = $this->getParams();

		if ($params->get('rating-mode') == 'user-rating')
		{
			$input = $this->app->input;
			$listId = $input->getInt('listid');
			$formId = $input->getInt('formid');
			$rowId = $input->get('rowid', '', 'string');

			if (empty($listId))
			{
				$formModel = $this->getFormModel();
				$listId = $formModel->getListModel()->getId();
			}

			list($val, $total) = $this->getRatingAverage($val, $listId, $formId, $rowId);
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
		$input = $this->app->input;
		$this->setId($input->getInt('element_id'));
		$this->loadMeForAjax();
		$params = $this->getParams();
		$listModel = $this->getListModel();
		$list = $listModel->getTable();
		$listId = $list->id;
		$formId = $listModel->getFormModel()->getId();
		$rowId = $input->get('row_id');
		$rating = $input->getInt('rating');

		$this->doRating($listId, $formId, $rowId, $rating);

		/*
		if ($params->get('rating-mode') == 'creator-rating')
		{
			// @todo FIX for joins as well

			// Store in elements table as well
			$db = $listModel->getDb();
			$element = $this->getElement();
			$query = $db->getQuery(true);
			$query->update($list->db_table_name)
			->set($element->name . '=' . $rating)->where($list->db_primary_key . ' = ' . $db->q($rowId));
			$db->setQuery($query);
			$db->execute();
		}
		*/


		$this->getRatingAverage('', $listId, $formId, $rowId);
		echo $this->avg;
	}

	/**
	 * Get cookie name
	 *
	 * @param   int     $listId  List id
	 * @param   string  $rowId  Row id
	 *
	 * @return string  Hashed cookie name.
	 */
	private function getCookieName($listId, $rowId)
	{
		$cookieName = "rating-table_{$listId}_row_{$rowId}" . FabrikString::filteredIp();
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
	 * @param   int     $listId  List id
	 * @param   int     $formId  Form id
	 * @param   string  $rowId  Row reference
	 * @param   int     $rating  Rating
	 *
	 * @return  void
	 */
	private function doRating($listId, $formId, $rowId, $rating)
	{
		$this->createRatingTable();
		$db = FabrikWorker::getDbo(true);
		$tzOffset = $this->config->get('offset');
		$date = JFactory::getDate('now', $tzOffset);
		$strDate = $db->q($date->toSql());
		$userId = $db->q($this->getStoreUserId($listId, $rowId));
		$elementId = (int) $this->getElement()->id;
		$formId = (int) $formId;
		$listId = (int) $listId;
		$rating = (int) $rating;
		$rowId = $db->q($rowId);
		$db
			->setQuery(
				"INSERT INTO #__fabrik_ratings (user_id, listid, formid, row_id, rating, date_created, element_id)
		values ($userId, $listId, $formId, $rowId, $rating, $strDate, $elementId)
			ON DUPLICATE KEY UPDATE date_created = $strDate, rating = $rating"
		);

		$db->execute();
	}

	/**
	 * Get the stored user id
	 *
	 * @param   int     $listId  List id
	 * @param   string  $rowId  Row reference
	 *
	 * @return Mixed string/int
	 */
	private function getStoreUserId($listId, $rowId)
	{
		$userId = (int) $this->user->get('id');

		if ($userId === 0)
		{
			$hash = $this->getCookieName($listId, $rowId);

			// Set cookie
			$lifetime = time() + 365 * 24 * 60 * 60;
			setcookie($hash, '1', $lifetime, '/');
			$userId = $hash;
		}

		return $userId;
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
		$input = $this->app->input;
		$params = $this->getParams();

		if ($input->get('view') == 'form' && $params->get('rating-rate-in-form', true) == 0)
		{
			return;
		}

		$id = $this->getHTMLId($repeatCounter);
		$data = $this->getFormModel()->data;
		$listModel = $this->getlistModel();
		$listId = $listModel->getTable()->id;
		$formId = $listModel->getFormModel()->getId();
		$rowId = $input->get('rowid', '', 'string');
		$value = $this->getValue($data, $repeatCounter);

		if ($params->get('rating-mode') != 'creator-rating')
		{
			list($value, $total) = $this->getRatingAverage($value, $listId, $formId, $rowId);
		}

		$opts = new stdClass;

		if (!FabrikWorker::j3())
		{
			$opts->insrc = FabrikHelperHTML::image("star.png", 'form', @$this->tmpl, array(), true);
			$opts->outsrc = FabrikHelperHTML::image("star-empty.png", 'form', @$this->tmpl, array(), true);
			$opts->clearoutsrc = $clearsrc = FabrikHelperHTML::image("remove-sign-out.png", 'form', @$this->tmpl, array(), true);
			$opts->clearinsrc = $clearsrc = FabrikHelperHTML::image("remove-sign.png", 'form', @$this->tmpl, array(), true);
		}

		$opts->row_id = $rowId;
		$opts->elid = $this->getElement()->id;
		$opts->userid = (int) $this->user->get('id');
		$opts->formid = $formId;
		$opts->canRate = (bool) $this->canRate();
		$opts->mode = $params->get('rating-mode');
		$opts->doAjax = $params->get('rating-mode') != 'creator-rating';
		$opts->view = $input->get('view');
		$opts->rating = $value;
		$opts->listid = $listId;

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
		$params = $this->getParams();
		$id = $this->getHTMLId();
		$listModel = $this->getlistModel();
		$list = $listModel->getTable();
		$opts = new stdClass;
		$opts->listid = $list->id;
		$imagePath = JUri::root() . '/plugins/fabrik_element/rating/images/';
		$opts->imagepath = $imagePath;
		$opts->elid = $this->getElement()->id;

		if (!FabrikWorker::j3())
		{
			$opts->insrc = FabrikHelperHTML::image("star.png", 'list', @$this->tmpl, array(), true);
			$opts->outsrc = FabrikHelperHTML::image("star-empty.png", 'list', @$this->tmpl, array(), true);
		}

		$opts->canRate = $params->get('rating-mode') == 'creator-rating' ? true : $this->canRate();
		$opts->doAjax = $params->get('rating-mode') != 'creator-rating';
		$opts->ajaxloader = FabrikHelperHTML::image("ajax-loader.gif", 'list', @$this->tmpl, array(), true);
		$opts->listRef = $listModel->getRenderContext();
		$opts->formid = $listModel->getFormModel()->getId();
		$opts->userid = (int) $this->user->get('id');
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
