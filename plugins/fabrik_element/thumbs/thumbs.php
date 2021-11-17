<?php
/**
 * Plugin element to render thumbs-up/down widget
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.thumbs
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to render thumbs-up/down widget
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.thumbs
 * @since       3.0
 */
class PlgFabrik_ElementThumbs extends PlgFabrik_Element
{
	/**
	 * States the element should be ignored from advanced search all queries.
	 *
	 * @var bool  True, ignore in advanced search all.
	 */
	protected $ignoreSearchAllDefault = true;

	/**
	 * Formid - override for comments plugin
	 *
	 * @var int
	 */
	public $formId = null;

	/**
	 * List id - override for comments plugin
	 *
	 * @var int
	 */
	public $listId = null;

	/**
	 * Reference for comments plugin
	 *
	 * @var string
	 */
	public $special = null;

	/**
	 * Comment id
	 *
	 * @var int
	 */
	public $commentId = null;

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
        $profiler = JProfiler::getInstance('Application');
        JDEBUG ? $profiler->mark("renderListData: {$this->element->plugin}: start: {$this->element->name}") : null;

        $input = $this->app->input;
		$j3 = FabrikWorker::j3();
		$params = $this->getParams();
		$imagePath = COM_FABRIK_LIVESITE . 'plugins/fabrik_element/thumbs/images/';
		$data = FabrikWorker::JSONtoData($data, true);
		$listId = $this->getlistModel()->getTable()->id;
		$formModel = $this->getFormModel();
		$formId = $formModel->getId();

		$rowId = $thisRow->__pk_val;

		if (empty($data))
		{
			$data = array(0);
		}

		for ($i = 0; $i < count($data); $i++)
		{
			$input->set('rowid', $rowId);
			$myThumb                     = $this->getMyThumb($listId, $formId, $rowId);
			$count                       = $this->_renderListData($data[$i], $thisRow);
			$count                       = FabrikWorker::JSONtoData($count, true);
			$layout                      = $this->getLayout('list');
			$layoutData                  = new stdClass;
			$layoutData->commentdata     = 'data-fabrik-thumb-rowid="' . $rowId . '"';
			$layoutData->formId          = $formId;
			$layoutData->upActiveClass   = $myThumb === 'up' ? ' btn-success' : '';;
			$layoutData->downActiveClass = $myThumb === 'down' ? ' btn-danger' : '';;
			$layoutData->countUp         = $count[0];
			$layoutData->countDown       = $count[1];
			$layoutData->showDown        = $params->get('show_down', 1);
			$layoutData->tmpl            = isset ($this->tmpl) ? $this->tmpl : '';
			$layoutData->elementModel    = $this;
			$data[$i]                    = $layout->render($layoutData);
		}

		$data = json_encode($data);

		return parent::renderListData($data, $thisRow, $opts);
	}

	private function setParentIDs(&$elementId, &$formId, &$listId)
	{
		$element = $this->getElement();
		static $row = null;

		if (!empty($element->parent_id))
		{
			if (!isset($row))
			{
				$db    = FabrikWorker::getDbo();
				$query = $db->getQuery(true);
				$query->select('e.id as element_id, fg.form_id, l.id as list_id')
					->from('#__fabrik_elements as e')
					->leftJoin('#__fabrik_formgroup as fg on fg.group_id = e.group_id')
					->leftJoin('#__fabrik_lists as l on l.form_id = fg.form_id')
					->where('e.id = ' . (int) $element->parent_id);
				$db->setQuery($query);
				$row       = $db->loadObject();
			}

			$listId    = $row->list_id;
			$formId    = $row->form_id;
			$elementId = $row->element_id;
		}
	}

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string  $data     elements data
	 * @param   object  $thisRow  all the data in the lists current row
	 *
	 * @return  string	formatted value
	 */
	private function _renderListData($data, $thisRow)
	{
		$list = $this->getlistModel()->getTable();
		$listId = isset($this->listid) ? $this->listid : $list->id;
		$formId = isset($this->formid) ? $this->formid : $list->form_id;
		$rowId = isset($thisRow->__pk_val) ? $thisRow->__pk_val : $thisRow->id;

		return $this->getThumbsCount($data, $listId, $formId, $rowId);
	}

	/**
	 * Get the # of likes
	 *
	 * @param   array   $data    Not used!
	 * @param   int     $listId  List id
	 * @param   int     $formId  Form id
	 * @param   string  $rowId  Row id
	 *
	 * @return count thumbs-up, count thumbs-down
	 */
	protected function getThumbsCount($data, $listId, $formId, $rowId)
	{
		$db = FabrikWorker::getDbo();
		$elementId = $this->getElement()->id;
		$this->setParentIDs($elementId, $formId, $listId);

		$sql = isset($this->special) ? " AND special = " . $db->q($this->special) : '';

		// @TODO JQueryBuilder this
		$db
			->setQuery(
				"SELECT COUNT(thumb) FROM #__{package}_thumbs WHERE listid = " . (int) $listId . " AND formid = " . (int) $formId . " AND row_id = "
					. $db->q($rowId) . " AND element_id = " . (int) $elementId . $sql . " AND thumb = 'up'");
		$resup = $db->loadResult();
		$db
			->setQuery(
				"SELECT COUNT(thumb) FROM #__{package}_thumbs WHERE listid = " . (int) $listId . " AND formid = " . (int) $formId . " AND row_id = "
					. $db->q($rowId) . " AND element_id = " . (int) $elementId . $sql . " AND thumb = 'down'");

		$resdown = $db->loadResult();

		return json_encode(array($resup, $resdown));
	}

	/**
	 * Get All lists thumbs in 2 queries.
	 *
	 * @return  array
	 */
	public function getListThumbsCount()
	{
		$elementId = $this->getElement()->id;
		$listId = isset($this->listid) ? $this->listid : $this->getListModel()->getId();
		$formId = isset($this->formid) ? $this->formid : $this->getFormModel()->getId();
		$this->setParentIDs($elementId, $formId, $listId);
		$db = FabrikWorker::getDbo();
		$return = array();

		foreach (array('up', 'down') as $dir)
		{
			$query = $db->getQuery(true);
			$query->select('COUNT(thumb) AS up, row_id')->from('#__{package}_thumbs')
			->where('listid = ' . (int) $listId . ' AND formid = ' . (int) $formId . ' AND thumb = ' . $db->q($dir));

			if (isset($this->special))
			{
				$query->where('special = ' . $db->q($this->special));
			}

			$query->group('row_id');

			$db->setQuery($query);
			$return[$dir] = $db->loadObjectList('row_id');
		}

		return $return;
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           To pre-populate element with
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string	Elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$input = $this->app->input;
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		$j3 = FabrikWorker::j3();

		if ($input->get('view') == 'form' && ((bool) $params->get('rate_in_from', false) === false || $this->getFormModel()->isNewRecord()))
		{
			return '';
		}

		$listId = $this->getListModel()->getTable()->id;
		$formModel = $this->getFormModel();
		$formId = isset($this->formid) ? $this->formid : $formModel->getId();
		$rowId = $input->getInt('commentId', $formModel->getRowId());

		if (!isset($thisRow))
		{
			$thisRow = new stdClass;
			$thisRow->__pk_val = $rowId;
		}

		$myThumb = $this->getMyThumb($listId, $formId, $rowId);

		// @TODO use Fabrikimage rather than hardwired image path
		$imagePath = COM_FABRIK_LIVESITE . 'plugins/fabrik_element/thumbs/images/';

		$imageFileUp = 'thumb_up_out.gif';
		$imageFileDown = 'thumb_down_out.gif';

		if ($myThumb == 'up')
		{
			$imageFileUp = 'thumb_up_in.gif';
			$imageFileDown = 'thumb_down_out.gif';
		}
		elseif ($myThumb == 'down')
		{
			$imageFileUp = 'thumb_up_out.gif';
			$imageFileDown = 'thumb_down_in.gif';
		}


		$id2 = FabrikString::rtrimword($id, '_ro');
		$count = $this->_renderListData(FArrayHelper::getValue($data, $id2), $thisRow);
		$count = FabrikWorker::JSONtoData($count, true);


		$layout                    = $this->getLayout('form');
		$layoutData                = new stdClass;
		$layoutData->j3            = $j3;
		$layoutData->name          = $name;
		$layoutData->id            = $id;
		$layoutData->commentdata   = 'data-fabrik-thumb-rowid="' . $rowId . '"';
		$layoutData->formId        = $formId;
		$layoutData->upActiveClass = $myThumb === 'up' ? ' btn-success' : '';;
		$layoutData->downActiveClass = $myThumb === 'down' ? ' btn-danger' : '';;
		$layoutData->countUp       = $count[0];
		$layoutData->countDown     = $count[1];
		$layoutData->countDiff     = $layoutData->countUp - $layoutData->countDown;
		$layoutData->showDown      = $params->get('show_down', 1);
		$layoutData->imagepath     = $imagePath;
		$layoutData->imagefileup   = $imageFileUp;
		$layoutData->imagefiledown = $imageFileDown;
		$layoutData->elementModel  = $this;
		$layoutData->tmpl          = isset ($this->tmpl) ? $this->tmpl : '';

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
		$input = $this->app->input;
		$listId = $input->getInt('listid');
		$formId = $input->getInt('formid');
		$rowId = $input->getString('rowid', '', 'string');

		if ($params->get('rating-mode') != 'creator-rating')
		{
			// $val = $this->getRatingAverage($val, $listId, $formId, $rowId);
		}

		return $val;
	}

	/**
	 * Get thumb value
	 *
	 * @param   int     $listId  List id
	 * @param   int     $formId  Form id
	 * @param   string  $rowId  Row id
	 *
	 * @return string  Thumb value
	 */
	protected function getMyThumb($listId, $formId, $rowId)
	{
		$db = FabrikWorker::getDbo();
		$elementId = $this->getElement()->id;
		$this->setParentIDs($elementId, $formId, $listId);
		$userId = $this->user->get('id');
		$query = $db->getQuery(true);

		if ($userId == 0)
		{
			$userId = $this->getCookieName($listId, $rowId);
		}

		$query->select('thumb')->from('#__{package}_thumbs')
		->where('listid = ' . (int) $listId . ' AND formid = ' . (int) $formId . ' AND row_id = '
		. $db->q($rowId) . ' AND element_id = ' . (int) $elementId . ' AND user_id = ' . $db->q($userId)
			);
		$db->setQuery($query);
		$ret = $db->loadResult();

		return $ret;
	}

	/**
	 * Called via widget ajax, stores the selected thumb
	 * stores the diff (thumbs-up minus thumbs-down)
	 *
	 * @return  number  The new count for up and down
	 */
	public function onAjax_rate()
	{
		$input = $this->app->input;
		$this->setId($input->getInt('element_id'));
		$this->loadMeForAjax();

		$listId = $this->getListModel()->getId();
		$formId = $this->getFormModel()->getId();
		$rowId = $input->get('row_id');
		$thumb = $input->get('thumb');
		$add = $input->get('add', 'true');

		if ($add === 'true')
		{
			$this->doThumb($listId, $formId, $rowId, $thumb);
		}
		else
		{
			$this->deleteThumb($listId, $formId, $rowId, $thumb);
		}

		echo $this->getThumbsCount('', $listId, $formId, $rowId);
	}

	/**
	 * Get the cookie name
	 *
	 * @param   int     $listId  List id
	 * @param   string  $rowId  Row id
	 *
	 * @return  string
	 */
	private function getCookieName($listId, $rowId)
	{
		$cookieName = 'thumb-table_' . $listId . '_row_' . $rowId . '_ip_' . FabrikString::filteredIp();
		jimport('joomla.utilities.utility');
		$version = new JVersion;

		if (version_compare($version->RELEASE, '3.1', '>'))
		{
			return JApplicationHelper::getHash($cookieName);
		}
		else
		{
			return JApplication::getHash($cookieName);
		}
	}

	/**
	 * Main method to delete a rating
	 *
	 * @param   int     $listId  List id
	 * @param   int     $formId  Form id
	 * @param   string  $rowId  Row id
	 * @param   string  $thumb   Thumb value
	 *
	 * @return  void
	 */
	private function deleteThumb($listId, $formId, $rowId, $thumb)
	{
		$elementId = $this->getElement()->id;
		$this->setParentIDs($elementId, $formId, $listId);
		$userId = $this->getUserId($listId, $rowId);
		$db = FabrikWorker::getDbo();
		$query = $db->getQuery(true);
		$query->delete('#__{package}_thumbs')->where('user_id = ' . $db->q($userId))
		->where('listid = ' . $listId . ' AND row_id = ' . $rowId . ' AND thumb = ' . $db->q($thumb));
		$db->setQuery($query);
		$db->execute();
		$elementId = $this->getElement()->id;
		$this->updateDB($listId, $formId, $rowId, $elementId);
	}

	/**
	 * Get the user's id - if not logged in get uuid.
	 * If not logged in sets cookie as well
	 *
	 * @param   int     $listId  List id
	 * @param   string  $rowId  Row id
	 *
	 * @return  string
	 */
	private function getUserId($listId, $rowId)
	{
		$userId = (int) $this->user->get('id');

		if ($userId == 0)
		{
			$userId = $this->getCookieName($listId, $rowId);

			// Set cookie
			$lifetime = time() + 365 * 24 * 60 * 60;
			setcookie($userId, '1', $lifetime, '/');
		}

		return $userId;
	}

	/**
	 * Main method to store a rating
	 *
	 * @param   int     $listId  List id
	 * @param   int     $formId  Form id
	 * @param   string  $rowId  Row id
	 * @param   string  $thumb   Thumb value
	 *
	 * @return  void
	 */
	private function doThumb($listId, $formId, $rowId, $thumb)
	{
		if (!$this->canUse())
		{
			return;
		}

		$db = FabrikWorker::getDbo();
		$date = $this->date->toSql();
		$userId = $this->getUserId($listId, $rowId);
		$elementId = $this->getElement()->id;
		$this->setParentIDs($elementId, $formId, $listId);
		$special = $this->app->input->get('special');
		$db->setQuery(
			"INSERT INTO #__{package}_thumbs
				(user_id, listid, formid, row_id, thumb, date_created, element_id, special)
				values (
					" . $db->q($userId) . ",
					" . $db->q($listId) . ",
					" . $db->q($formId) . ",
					" . $db->q($rowId) . ",
					" . $db->q($thumb) . ",
					" . $db->q($date) . ",
					" . $db->q($elementId) . ",
					" . $db->q($special) . "
				)
				ON DUPLICATE KEY UPDATE
					date_created = " . $db->q($date) . ",
					thumb = " . $db->q($thumb)
		);

		try
		{
			$db->execute();
		}
		catch (RuntimeException $e)
		{
			$err = new stdClass;
			$err->error = $e->getMessage();
			echo json_encode($err);
			exit;
		}

		$this->updateDB($listId, $formId, $rowId, $elementId);
	}

	/**
	 * Update the db record
	 *
	 * @param   int     $listId     List id
	 * @param   int     $formId     Form id
	 * @param   string  $rowId     Row ref
	 * @param   int     $elementId  Element id
	 *
	 * @return boolean
	 */
	private function updateDB($listId, $formId, $rowId, $elementId)
	{
		$db = FabrikWorker::getDbo();
		$name = $this->getElement()->name;

		// Name can be blank for comments
		if ($name != '')
		{
			$db
				->setQuery(
					"UPDATE " . $db->quoteName($this->getlistModel()->getTable()->db_table_name) . "
	                    SET " . $db->quoteName($this->getElement()->name) . " = ((SELECT COUNT(thumb) FROM #__{package}_thumbs WHERE listid = " . (int) $listId
						. " AND formid = " . (int) $formId . " AND row_id = " . $db->q($rowId) . " AND element_id = " . (int) $elementId
						. " AND thumb = 'up') - (SELECT COUNT(thumb) FROM #__{package}_thumbs WHERE listid = " . (int) $listId . " AND formid = "
						. (int) $formId . " AND row_id = " . $db->q($rowId) . " AND element_id = " . (int) $elementId
						. " AND thumb = 'down'))
	                    WHERE " . $this->getlistModel()->getPrimaryKey() . " = " . $db->q($rowId) . "
	                        LIMIT 1");

			try
			{
				$db->execute();
			}
			catch (RuntimeException $e)
			{
				$err = new stdClass;
				$err->error = $e->getMessage();
				echo json_encode($err);
				exit;
			}
		}

		return true;
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

		if ($input->get('view') == 'form' && ((bool) $params->get('rate_in_from', false) === false || $this->getFormModel()->isNewRecord()))
		{
			return '';
		}

		$id = $this->getHTMLId($repeatCounter);
		$listId = $this->getlistModel()->getTable()->id;
		$formModel = $this->getFormModel();
		$formId = $formModel->getId();
		$rowId = $formModel->getRowId();
		$this->lang->load('plg_fabrik_element_thumbs', JPATH_BASE . '/plugns/fabrik_element/thumbs');
		$opts = new stdClass;
		$opts->canUse = $this->canUse();
		$opts->noAccessMsg = trim(FText::_($params->get('thumbs_no_access_msg', FText::_('PLG_ELEMENT_THUMBS_NO_ACCESS_MSG_DEFAULT'))));
		$opts->row_id = $rowId;
		$opts->myThumb = $this->getMyThumb($listId, $formId, $rowId);
		$opts->elid = $this->getElement()->id;
		$opts->userid = (int) $this->user->get('id');
		$opts->view = $input->get('view');
		$opts->listid = $listId;
		$opts->formid = $this->getFormModel()->getId();

		return array('FbThumbs', $id, $opts);
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
		$list = $this->getlistModel()->getTable();
		$formId = $list->form_id;
		$listMyThumbs = array();
		$idFromCookie = null;
		$data = $this->getListModel()->getData();
		$groupKeys = array_keys($data);

		foreach ($groupKeys as $gKey)
		{
			foreach ($data[$gKey] as $rowKey)
			{
				if (!$idFromCookie && $this->user->get('id') == 0)
				{
					$idFromCookie = $this->getCookieName($list->id, $rowKey->__pk_val);
				}

				$listMyThumbs[$rowKey->__pk_val] = $this->getMyThumb($list->id, $formId, $rowKey->__pk_val);
			}
		}

		if ($this->user->get('id') == 0)
		{
			$userId = $idFromCookie;
		}
		else
		{
			$userId = $this->user->get('id');
		}

		$this->lang->load('plg_fabrik_element_thumbs', JPATH_BASE . '/plugns/fabrik_element/thumbs');

		$opts = new stdClass;
		$opts->canUse = $this->canUse();
		$opts->noAccessMsg = FText::_($params->get('thumbs_no_access_msg', FText::_('PLG_ELEMENT_THUMBS_NO_ACCESS_MSG_DEFAULT')));
		$opts->listid = $list->id;
		$opts->formid = $this->getFormModel()->getId();
		$opts->imagepath = COM_FABRIK_LIVESITE . 'plugins/fabrik_element/thumbs/images/';
		$opts->elid = $this->getElement()->id;
		$opts->myThumbs = $listMyThumbs;
		$opts->userid = $userId;
		$opts->renderContext = $this->getListModel()->getRenderContext();
		$opts = json_encode($opts);

		return "new FbThumbsList('$id', $opts);\n";
	}

	/**
	 * Used by radio and dropdown elements to get a dropdown list of their unique
	 * unique values OR all options - based on filter_build_method
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

	/**
	 * Render the element admin settings
	 *
	 * @param   array   $data           admin data
	 * @param   int     $repeatCounter  repeat plugin counter
	 * @param   string  $mode           how the fieldsets should be rendered currently support 'nav-tabs' (@since 3.1)
	 *
	 * @return  string	admin html
	 */
	public function onRenderAdminSettings($data = array(), $repeatCounter = null, $mode = null)
	{
		$this->install();

		return parent::onRenderAdminSettings($data, $repeatCounter, $mode);
	}

	/**
	 * Install the plugin db table
	 *
	 * @return  void
	 */
	public function install()
	{
		$db = FabrikWorker::getDbo();
		$query = "CREATE TABLE IF NOT EXISTS  `#__{package}_thumbs` (
	`user_id` VARCHAR( 40 ) NOT NULL ,
	`listid` INT( 6 ) NOT NULL ,
	`formid` INT( 6 ) NOT NULL ,
	`row_id` INT( 6 ) NOT NULL ,
	`thumb` VARCHAR( 255 ) NOT NULL,
	`date_created` DATETIME NOT NULL,
	`element_id` INT( 6 ) NOT NULL,
	`special` VARCHAR(30),
	 PRIMARY KEY ( `user_id` , `listid` , `formid` , `row_id`, `element_id`, `special` )
);";
		$db->setQuery($query);
		$db->execute();

		/**
		 * Check if we need to update the table ...
		 *
		 * Update for comments plugin needs 'special' column adding,,
		 * Check for older versions of the table needing tableid chenged to listid
		 */

		$cols = $db->getTableColumns('#__{package}_thumbs');

		if (array_key_exists('tableid', $cols))
		{
			$db->setQuery('ALTER TABLE #__{package}_thumbs CHANGE ' . $db->qn('tableid') . ' ' . $db->qn('listid') . ' INT(6)');
			$db->execute();
		}

		if (!array_key_exists('special', $cols))
		{
			$db->setQuery('ALTER TABLE #__{package}_thumbs ADD COLUMN ' . $db->qn('special') . ' VARCHAR(30)');
			$db->execute();

			$db->setQuery('ALTER TABLE #__{package}_thumbs DROP PRIMARY KEY');
			$db->execute();

			$db->setQuery('ALTER TABLE #__{package}_thumbs ADD PRIMARY KEY (`user_id`, `listid`, `formid`, `row_id`, `element_id`, `special`)');
			$db->execute();
		}
	}
}
