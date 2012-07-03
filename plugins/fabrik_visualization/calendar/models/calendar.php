<?php

/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once(JPATH_SITE . '/components/com_fabrik/models/visualization.php');

class fabrikModelCalendar extends FabrikFEModelVisualization {
	var $_eventLists = null;

	/** @var object form model for standard add event form **/
	var $_formModel = null;

	/** js name for calendar **/
	var $calName = null;

	var $_events = null;

	/** @var array filters from url*/
	var $filters = array();

	/** @var bol can add to tables **/
	var $canAdd = null;

	var $_params = null;

	function setListIds()
	{
		if (!isset($this->listids)) {
			$this->listids = (array) $this->getParams()->get('calendar_table');
			JArrayHelper::toInteger($this->listids);
		}
	}

	function &getEventLists()
	{
		if (is_null($this->_eventLists)) {
			$this->_eventLists = array();
			$db = FabrikWorker::getDbo(true);
			$params	= $this->getPluginParams();
			$lists = (array) $params->get('calendar_table');
			JArrayHelper::toInteger($lists);
			$dateFields = (array) $params->get('calendar_startdate_element');
			$dateFields2 = (array) $params->get('calendar_enddate_element');
			$labels = (array) $params->get('calendar_label_element');
			$colours = (array) $params->get('colour');

			$db->setQuery("SELECT id AS value, label AS text FROM #__{package}_lists WHERE id IN ('" . implode("','", $lists) . "')");
			$rows = $db->loadObjectList();
			for ($i = 0; $i < count($rows); $i++) {
				if (!isset($colours[$i])) {
					$colours[$i] = '';
				}
				$rows[$i]->startdate_element = $dateFields[$i];
				$rows[$i]->enddate_element = JArrayHelper::getValue($dateFields2, $i);
				$rows[$i]->label_element = $labels[$i];
				$rows[$i]->colour = $colours[$i];
			}
			$this->_eventLists = $rows;
		}
		return $this->_eventLists;
	}

	function getAddStandardEventFormId()
	{
		$config = JFactory::getConfig();
		$prefix = $config->getValue('config.dbprefix');
		$db = FabrikWorker::getDbo();
		$db->setQuery("SELECT form_id FROM #__{package}_tables WHERE db_table_name = '{$prefix}fabrik_calendar_events' AND private = '1'");
		return $db->loadResult();
	}

	function getAddStandardEventFormInfo()
	{
		$config = JFactory::getConfig();
		$prefix = $config->getValue('config.dbprefix');
		$params = $this->getParams();
		$db = FabrikWorker::getDbo();
		$db->setQuery("SELECT form_id, id FROM #__{package}_tables WHERE db_table_name = '{$prefix}fabrik_calendar_events' AND private = '1'");
		$o = $db->loadObject();
		if (is_object($o)) {
			// there are standard events recorded
			return $o;
		} else {
			// they aren't any standards events recorded
			return null;
		}
	}

	/**
	 * Save the calendar
	 * @return boolean false if not saved, otherwise id of saved calendar
	 */

	function save()
	{
		$user	  = &JFactory::getUser();
		$post	= JRequest::get('post');
		if (!$this->bind( $post)) {
			return JError::raiseWarning(500, $this->getError());
		}

		$params = JRequest::getVar('params', array(), 'post');
		$this->params = json_encode($params);
		if ($this->id == 0) {
			$this->created 		= date( 'Y-m-d H:i:s');
			$this->created_by 	= $user->get('id');
		} else {
			$this->modified 	= date( 'Y-m-d H:i:s');
			$this->modified_by 	= $user->get('id');
		}

		if (!$this->check()) {
			return JError::raiseWarning(500, $this->getError());
		}

		if (!$this->store()) {
			return JError::raiseWarning(500, $this->getError());
		}
		$this->checkin();
		return $this->id;
	}

	function setupEvents()
	{
		if (is_null($this->_events)) {
			$params = $this->getParams();
			$tables = (array) $params->get('calendar_table');
			$table_label = (array) $params->get('calendar_label_element');
			$table_startdate = (array) $params->get('calendar_startdate_element');
			$table_enddate = (array) $params->get('calendar_enddate_element');
			$colour	= (array) $params->get('colour');
			$legend	= (array) $params->get('legendtext');
			$this->_events = array();
			for ($i=0; $i<count($tables); $i++) {
				$listModel = JModel::getInstance('list', 'FabrikFEModel');
				if ($tables[$i] != 'undefined') {
					$listModel->setId($tables[$i]);
					$table = $listModel->getTable();
					$endDate = JArrayHelper::getValue($table_enddate, $i, '');
					$startDate = JArrayHelper::getValue($table_startdate, $i, '');

					$startShowTime = true;
					$startDateEl = $listModel->getFormModel()->getElement($startDate);
					if ($startDateEl !== false) {
						$startShowTime = $startDateEl->getParams()->get('date_showtime', true);
					}

					$endShowTime = true;
					if ($endDate !== '') {
						$endDateEl = $listModel->getFormModel()->getElement($endDate);
						if ($endDateEl !== false) {
							$endShowTime = $endDateEl->getParams()->get('date_showtime', true);
						}
					}

					if (!isset($colour[$i])) {
						$colour[$i] = '';
					}
					if (!isset($legend[$i])) {
						$legend[$i] = '';
					}
					if (!isset($table_label[$i])) {
						$table_label[$i] = '';
					}
					$this->_events[$tables[$i]][] = array(
						'startdate'=> $startDate,
						'enddate'=> $endDate,
						'startShowTime' => $startShowTime,
						'endShowTime' => $endShowTime,
						'label'=>$table_label[$i],
						'colour'=>$colour[$i],
						'legendtext'=>$legend[$i],
						'formid'=>$table->form_id,
						'listid'=>$tables[$i]
					);
				}
			}
		}

		return $this->_events;
	}

	function getLinkedFormIds()
	{
		$this->setUpEvents();
		$return = array();
		foreach ($this->_events as $arr) {
			foreach ($arr as $a) {
				$return[] = $a['formid'];
			}
		}
		return array_unique( $return);
	}

	/**
	 * go over all the tables whose data is displayed in the calendar
	 * if any element is found in the request data, assign it to the session
	 * This will then be used by the table to filter its data.
	 * nice :)
	 */

	function setRequestFilters()
	{
		$this->setupEvents();
		$request = JRequest::get('request');
		$listModel = JModel::getInstance('list', 'FabrikFEModel');

		foreach ($this->_events as $listid	 => $record) {
			$listModel->setId($listid);
			$table = $listModel->getTable();
			$formModel = $listModel->getFormModel();
			foreach ($request as $key=>$val) {
				if ($formModel->hasElement($key)) {
					$o = new stdClass();
					$o->key = $key;
					$o->val = $val;
					$this->filters[]  =$o;
				}
			}
		}
	}

	/**
	 * can the user add a record into the calendar
	 * @return bol
	 */
	
	function getCanAdd()
	{
		if (!isset($this->canAdd)) {
			$params = $this->getParams();
			$lists = (array) $params->get('calendar_table');
			foreach ($lists as $id) {
				$listModel = JModel::getInstance('list', 'FabrikFEModel');
				$listModel->setId($id);
				if (!$listModel->canAdd()) {
					$this->canAdd = false;
					return false;
				}
			}
			$this->canAdd = true;
		}
		return $this->canAdd;
	}
	
	/**
	 * get an arry of list ids for which the current user has delete records rights
	 * @return array list ids.
	 */
	
	public function getDeleteAccess()
	{
		$deleteables = array();
		$params = $this->getParams();
		$lists = (array) $params->get('calendar_table');
		foreach ($lists as $id)
		{
			$listModel = JModel::getInstance('list', 'FabrikFEModel');
			$listModel->setId($id);
			if ($listModel->canDelete())
			{
				$deleteables[] = $id;
			}
		}
		return $deleteables;
	}

	/**
	 * query all tables linked to the calendar and return them
	 * @return string javascript array containg json objects
	 */

	function getEvents()
	{
		$app = JFactory::getApplication();
		$Itemid	= @(int) $app->getMenu('site')->getActive()->id;
		$config = JFactory::getConfig();
		$tzoffset = (int) $config->getValue('config.offset');

		$this->setupEvents();

		$calendar = $this->_row;
		$aLegend = "$this->calName.addLegend([";
		$jsevents = array();
		foreach ($this->_events as $listid	=> $record) {
			$listModel = JModel::getInstance('list', 'FabrikFEModel');
			$listModel->setId($listid);
			if (!$listModel->canView()) {
				continue;
			}
			$table = $listModel->getTable();

			$els = $listModel->getElements();
			foreach ($record as $data) {

				$db = $listModel->getDb();
				$startdate = trim($data['startdate']) !== '' ? FabrikString::safeColName($data['startdate']) : "''";
				$enddate = trim($data['enddate']) !== '' ? FabrikString::safeColName($data['enddate']) : "''";
				$label = trim($data['label']) !== '' ? FabrikString::safeColName($data['label']) : "''";
				$qlabel = FabrikString::safeColName($label);
				if (array_key_exists($qlabel, $els)) {
					// if db join selected for the label we need to get the label element and not the value
					$label = FabrikString::safeColName($els[$qlabel]->getOrderByName());
					// $$$ hugh @TODO doesn't seem to work for join elements, so adding hack till I can talk
					// to rob about this one.
					if (method_exists($els[$qlabel], 'getJoinLabelColumn')) {
						$label = $els[$qlabel]->getJoinLabelColumn();
					}
					else {
						$label = FabrikString::safeColName($els[$qlabel]->getOrderByName());
					}
				}
				//$pk = $listModel->getPrimaryKeyAndExtra();
				//$pk = $table->db_table_name.'.'.$pk['colname'];
				//above wont work in a view
				$pk = $listModel->getTable()->db_primary_key;
				$where = $listModel->_buildQueryWhere();
				$join = $listModel->_buildQueryJoin();
				$sql = "SELECT $pk AS id, $startdate AS startdate, $enddate AS enddate, '' AS link, $label AS 'label', '{$data['colour']}' AS colour, 0 AS formid FROM $table->db_table_name $join $where ORDER BY $startdate ASC";

				$db->setQuery($sql);
				$formdata = $db->loadObjectList();
				if (is_array($formdata))
				{
					foreach ($formdata as $row)
					{
						if ($row->startdate != '')
						{
							$row->link = ("index.php?option=com_fabrik&Itemid=$Itemid&view=form&formid=$table->form_id&rowid=$row->id&tmpl=component");
							$row->_listid = $table->id;
							$row->_canDelete = (bool)$listModel->canDelete();
							$row->_canEdit = (bool)$listModel->canEdit($row);

							// $$$ rob added timezone offset how on earth was this not picked up before :o
							// $$$ hugh because we suck?
							if ($row->startdate !== $db->getNullDate() && $data['startShowTime'] == true)
							{
								$date = JFactory::getDate($row->startdate);
								$row->startdate = $date->toMySQL();
								$date = JFactory::getDate($row->startdate);
								$date->setOffset($tzoffset);
								$row->startdate =  $date->toFormat('%Y-%m-%d %H:%M:%S');
							}

							if ($row->enddate !== $db->getNullDate() && $row->enddate !== '')
							{
								if ($data['endShowTime'] == true)
								{
									$date = JFactory::getDate($row->enddate);
									$date->setOffset($tzoffset);
									$row->enddate =  $date->toFormat('%Y-%m-%d %H:%M:%S');
								}
							}
							 else
							 {
								$row->enddate = $row->startdate;
							}
							$jsevents[$table->id . '_' . $row->id . '_' . $row->startdate] = clone($row);
						}
					}
				}
			}
		}
		$params = $this->getParams();
		$addEvent = json_encode($jsevents);
		return $addEvent;
	}

	//@TODO: json encode the returned value and move the $this->calName.addLegend to the view
	function getLegend()
	{
		$db	= FabrikWorker::getDbo();
		$params = $this->getParams();
		$this->setupEvents();
		$tables = (array) $params->get('calendar_table');
		$colour = (array) $params->get('colour');
		$legend = (array) $params->get('legendtext');    
		$calendar = $this->_row;
		$aLegend = "$this->calName.addLegend([";
		$jsevents = array();
		foreach ($this->_events as $listid => $record) {
			$listModel = JModel::getInstance('list', 'FabrikFEModel');
			$listModel->setId($listid);
			$table = $listModel->getTable();
			foreach ($record as $data) {
				$rubbish = $table->db_table_name . '___';
				$colour = FabrikString::ltrimword($data['colour'], $rubbish);
				$legend = FabrikString::ltrimword($data['legendtext'], $rubbish);      
				$label = ( empty( $legend )) ? $table->label : $legend;	
				$aLegend  .= "{'label':'" . $label . "','colour':'" . $colour . "'},";
			}
		}
		$aLegend = rtrim($aLegend, ","). "]);";
		return $aLegend;
	}

	function getCalName()
	{
		if(is_null($this->calName)) {
			$calendar = $this->_row;
			$this->calName = "oCalendar{$calendar->id}";
		}
		return $this->calName;
	}

	function updateevent()
	{
		$oPluginManager = FabrikWorker::getPluginManager();
	}

	/**
	 * delete an event
	 */

	public function deleteEvent()
	{
		$id = (int)JRequest::getVar('id');
		$listid = JRequest::getInt('listid');
		$listModel = JModel::getInstance('list', 'FabrikFEModel');
		$listModel->setId($listid);
		$list = $listModel->getTable();
		$tableDb = $listModel->getDb();
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('db_table_name')
		->from('#__{package}_lists')
		->where('id = '.$listid);
		$db->setQuery($query);
		$tablename = $db->loadResult();
		$tableDb->setQuery("DELETE FROM ".FabrikString::safeColName($tablename)." WHERE $list->db_primary_key = $id");
		$tableDb->query();
	}

}

?>