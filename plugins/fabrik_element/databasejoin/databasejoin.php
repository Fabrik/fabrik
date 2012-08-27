<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.databasejoin
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 *  Plugin element to render list of data looked up from a database table
 *  Can render as checboxes, radio buttons, select lists, multi select lists and autocomplete
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.databasejoin
 * @since       3.0
 */

class plgFabrik_ElementDatabasejoin extends plgFabrik_ElementList
{

	/** @var object connection */
	var $_cn = null;

	var $_joinDb = null;

	/** @var created in getJoin **/
	var $_join = null;

	/** @var string for simple join query*/
	var $_sql = array();

	/** @var array option values **/
	var $_optionVals = array();

	/** @var bol is a join element */
	var $_isJoin = true;

	/** @var array linked form data */
	var $_linkedForms = null;

	/** @var additionl where for auto-complete query */
	var $_autocomplete_where = "";

	/** @var string name of the join db to connect to */
	protected $dbname = null;

	/**
	 * Create the SQL select 'name AS alias' segment for list/form queries
	 *
	 * @param   array  &$aFields    array of element names
	 * @param   array  &$aAsFields  array of 'name AS alias' fields
	 * @param   array  $opts        options
	 *
	 * @return  void
	 */

	public function getAsField_html(&$aFields, &$aAsFields, $opts = array())
	{
		if ($this->isJoin())
		{
			// $$$ rob was commented out - but meant that the SELECT GROUP_CONCAT subquery was never user
			return parent::getAsField_html($aFields, $aAsFields, $opts);
		}
		$table = $this->actualTableName();
		$params = $this->getParams();
		$db = FabrikWorker::getDbo();
		$listModel = $this->getlistModel();
		$element = $this->getElement();
		$tableRow = $listModel->getTable();
		$joins = $listModel->getJoins();
		foreach ($joins as $tmpjoin)
		{
			if ($tmpjoin->element_id == $element->id)
			{
				$join = $tmpjoin;
				break;
			}
		}
		$connection = $listModel->getConnection();

		// Make sure same connection as this table
		$fullElName = JArrayHelper::getValue($opts, 'alias', $table . '___' . $element->name);
		if ($params->get('join_conn_id') == $connection->get('_id') || $element->plugin != 'databasejoin')
		{
			$join = $this->getJoin();
			if (!$join)
			{
				return false;
			}
			$joinTableName = $join->table_join_alias;

			$tables = $this->getForm()->getLinkedFabrikLists($params->get('join_db_name'));

			//	store unjoined values as well (used in non-join group table views)
			//this wasnt working for test case:
			//events -> (db join) event_artists -> el join (artist)

			// $$$ rob in csv import keytable not set
			// $$$ hugh - if keytable isn't set, the safeColName blows up!
			// Trying to debug issue with linked join elements, which don't get detected by
			// getJoins or getJoin 'cos element ID doesn't match element_id in fabrik_joins
			//$k = isset($join->keytable ) ? $join->keytable : $join->join_from_table;
			//$k = FabrikString::safeColName("`$join->keytable`.`$element->name`");
			$keytable = isset($join->keytable) ? $join->keytable : $join->join_from_table;
			$k = FabrikString::safeColName($keytable . '.' . $element->name);

			$k2 = $this->getJoinLabelColumn();

			if (JArrayHelper::getValue($opts, 'inc_raw', true))
			{
				$aFields[] = $k . ' AS ' . $db->quoteName($fullElName . '_raw');
				$aAsFields[] = $db->quoteName($fullElName . '_raw');
			}
			$aFields[] = $k2 . ' AS ' . $db->quoteName($fullElName);
			$aAsFields[] = $db->quoteName($fullElName);
		}
		else
		{
			$aFields[] = $db->quoteName($table) . '.' . $db->quoteName($element->name) . ' AS ' . $db->quoteName($fullElName);
			$aAsFields[] = $db->quoteName($fullElName);
		}
	}

	/**
	 * @since 3.0.6
	 * get the field name to use in the list's slug url
	 * @param   bool	$raw
	 */

	public function getSlugName($raw = false)
	{
		return $raw ? parent::getSlugName($raw) : $this->getJoinLabelColumn();
	}

	/**
	 * Get raw column name
	 *
	 * @param   bool  $useStep  use step in name
	 *
	 * @return string
	 */

	public function getRawColumn($useStep = true)
	{
		$join = $this->getJoin();
		if (!$join)
		{
			return;
		}
		$element = $this->getElement();
		$k = isset($join->keytable) ? $join->keytable : $join->join_from_table;
		$name = $element->name . '_raw';
		return $useStep ? $k . '___' . $name : FabrikString::safeColName($k . '.' . $name);
	}

	/**
	 * Create an array of label/values which will be used to populate the elements filter dropdown
	 * returns only data found in the table you are filtering on
	 *
	 * @param   bool    $normal     do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  table name to use - defaults to element's current table
	 * @param   string  $label      field to use, defaults to element name
	 * @param   string  $id         field to use, defaults to element name
	 * @param   bool    $incjoin    include join
	 *
	 * @return  array	filter value and labels
	 */

	protected function filterValueList_Exact($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		if ($this->isJoin())
		{
			$rows = array_values($this->checkboxRows());
		}
		else
		{
			$rows = parent::filterValueList_Exact($normal, $tableName, $label, $id, $incjoin);
		}
		return $rows;
	}

	/**
	 * Get the field name to use as the column that contains the join's label data
	 *
	 * @param   bool	use step in element name
	 *
	 * @return  string	join label column either returns concat statement or quotes `tablename`.`elementname`
	 */

	function getJoinLabelColumn($useStep = false)
	{
		if (!isset($this->joinLabelCols))
		{
			$this->joinLabelCols = array();
		}
		if (array_key_exists((int) $useStep, $this->joinLabelCols))
		{
			return $this->joinLabelCols[$useStep];
		}
		$params = $this->getParams();
		$db = $this->getDb();
		$join = $this->getJoin();
		if (($params->get('join_val_column_concat') != '') && JRequest::getVar('overide_join_val_column_concat') != 1)
		{
			$val = str_replace("{thistable}", $join->table_join_alias, $params->get('join_val_column_concat'));
			$w = new FabrikWorker;
			$val = $w->parseMessageForPlaceHolder($val, array(), false);
			return 'CONCAT(' . $val . ')';
		}
		$label = $this->getJoinLabel();
		$joinTableName = is_object($join) ? $join->table_join_alias : '';
		$this->joinLabelCols[(int) $useStep] = $useStep ? $joinTableName . '___' . $label : $db->quoteName($joinTableName . '.' . $label);
		return $this->joinLabelCols[(int) $useStep];
	}

	protected function getJoinLabel()
	{
		$join = $this->getJoin();
		if (!$join)
		{
			return false;
		}
		$label = FabrikString::shortColName($join->_params->get('join-label'));
		if ($label == '')
		{
			if (!$this->isJoin())
			{
				JError::raiseWarning(500, 'Could not find the join label for ' . $this->getElement()->name . ' try unlinking and saving it');
			}
			$label = $this->getElement()->name;
		}
		return $label;
	}

	/**
	 * Get as field for csv export
	 * can be overwritten in the plugin class - see database join element for example
	 * testing to see that if the aFields are passed by reference do they update the table object?
	 *
	 * @param   array	containing field sql
	 * @param   array	containing field aliases
	 * @param   string	table name (depreciated)
	 */

	function getAsField_csv(&$aFields, &$aAsFields, $table = '')
	{
		$this->getAsField_html($aFields, $aAsFields, $table);
	}

	/**
	 * Get join row
	 *
	 * @return  object	join table or false if not loaded
	 */

	protected function getJoin()
	{
		if (isset($this->_join))
		{
			return $this->_join;
		}
		$params = $this->getParams();
		$element = $this->getElement();
		if ($element->published == 0)
		{
			return false;
		}
		$listModel = $this->getlistModel();
		$table = $listModel->getTable();
		$joins = $listModel->getJoins();
		foreach ($joins as $join)
		{
			if ($join->element_id == $element->id)
			{
				$this->_join = $join;
				return $this->_join;
			}
		}
		if (!in_array(JRequest::getVar('task'), array('inlineedit', 'form.inlineedit')))
		{
			/*
			 * suppress error for inlineedit, something not quiet right as groupModel::getPublishedElements() is limited by the elementid request va
			 * but the list model is calling getAsFields() and loading up the db join element.
			 * so test case would be an inline edit list with a database join element and editing anything but the db join element
			 */
			JError::raiseError(500, 'unable to process db join element id:' . $element->id);
		}
		return false;
	}

	/**
	 * load this elements joins
	 */

	function getJoins()
	{
		$db = FabrikWorker::getDbo(true);
		if (!isset($this->_aJoins))
		{
			$query = $db->getQuery(true);
			$query->select('*')->from('#__{package}_joins')->where('element_id = ' . (int) $this->_id)->orderby('id');
			$db->setQuery($query);
			$this->_aJoins = $db->LoadObjectList();
		}
		return $this->_aJoins;
	}

	function getJoinsToThisKey(&$table)
	{
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('*, t.label AS tablelabel')->from('#__{package}_elements AS el')
			->join('LEFT', '#__{package}_formgroup AS fg ON fg.group_id = el.group_id')->join('LEFT', '#__{package}_forms AS f ON f.id = fg.form_id')
			->join('LEFT', ' #__{package}_tables AS t ON t.form_id = f.id')
			->where(
				'plugin = ' . $db->quote('databasejoin') . ' AND join_db_name = ' . $db->quote($table->db_table_name) . ' AND join_conn_id = '
					. (int) $table->connection_id);
		$db->setQuery($query);
		return $db->loadObjectList();
	}
	/**
	 * Get array of option values
	 *
	 * @param   array  $data           data
	 * @param   int    $repeatCounter  repeat group counter
	 * @param   bool   $incWhere       do we add custom where statement into sql
	 *
	 * @return  array	option values
	 */

	protected function _getOptionVals($data = array(), $repeatCounter = 0, $incWhere = true)
	{
		$params = $this->getParams();
		$db = $this->getDb();

		// $$$ hugh - attempting to make sure we never do an uncontrained query for auto-complete
		$displayType = $params->get('database_join_display_type', 'dropdown');
		$value = (array) $this->getValue($data, $repeatCounter);

		/*
		 *  $$$ rob 20/08/2012 - removed empty test - seems that this method is called more than one time, when in auto-complete filter
		 *  First time sends the label in data second time sends the value (which is the correct one)
		 */
		//if ($displayType === 'auto-complete' && empty($this->_autocomplete_where))
		if ($displayType === 'auto-complete')
		{
			if (!empty($value) && $value[0] !== '')
			{
				$quoteV = array();
				foreach ($value as $v)
				{
					$quoteV[] = $db->quote($v);
				}
				$this->_autocomplete_where = $this->getJoinValueColumn() . ' IN (' . implode(', ', $quoteV) . ')';
			}
		}
		// $$$ rob 18/06/2012 cache the option vals on a per query basis (was previously incwhere but this was not ok
		// for auto-completes in repeating groups
		$sql = $this->_buildQuery($data, $incWhere);
		if (isset($this->_optionVals[$sql]))
		{
			return $this->_optionVals[$sql];
		}

		$db->setQuery($sql);
		FabrikHelperHTML::debug($db->getQuery(), $this->getElement()->name . 'databasejoin element: get options query');
		$this->_optionVals[$sql] = $db->loadObjectList();
		if ($db->getErrorNum() != 0)
		{
			JError::raiseNotice(500, $db->getErrorMsg());
		}
		FabrikHelperHTML::debug($this->_optionVals, 'databasejoin elements');
		if (!is_array($this->_optionVals[$sql]))
		{
			$this->_optionVals[$sql] = array();
		}
		$eval = $params->get('dabase_join_label_eval');
		if (trim($eval) !== '')
		{
			foreach ($this->_optionVals[$sql] as $key => &$opt)
			{
				// $$$ hugh - added allowing removing an option by returning false
				if (eval($eval) === false)
				{
					unset($this->_optionVals[$sql][$key]);
				}
			}
		}

		// Remove tags from labels
		if ($this->canUse())
		{
			foreach ($this->_optionVals[$sql] as $key => &$opt)
			{
				$opt->text = strip_tags($opt->text);
			}
		}
		return $this->_optionVals[$sql];
	}

	/**
	 * Fix html validation warning on empty options labels
	 *
	 * @param   array   &$rows  option objects $rows
	 * @param   string  $txt    object label
	 *
	 * @return  null
	 */

	private function addSpaceToEmptyLabels(&$rows, $txt = 'text')
	{
		foreach ($rows as &$t)
		{
			if ($t->$txt == '')
			{
				$t->$txt = '&nbsp;';
			}
		}
	}

	/**
	 * Get a list of the HTML options used in the database join drop down / radio buttons
	 *
	 * @param   array  $data           from current record (when editing form?)
	 * @param   int    $repeatCounter  repeat group counter
	 * @param   bool   $incWhere       do we include custom where in query
	 *
	 * @return  array	option objects
	 */

	protected function _getOptions($data = array(), $repeatCounter = 0, $incWhere = true)
	{
		$element = $this->getElement();
		$params = $this->getParams();
		$showBoth = $params->get('show_both_with_radio_dbjoin', '0');
		$this->_joinDb = $this->getDb();
		$col = $element->name;
		$tmp = array();
		$aDdObjs = $this->_getOptionVals($data, $repeatCounter, $incWhere);
		foreach ($aDdObjs as &$o)
		{
			// For values like '1"'
			// $$$ hugh - added second two params so we set double_encode false
			$o->text = htmlspecialchars($o->text, ENT_NOQUOTES, 'UTF-8', false);
		}
		$table = $this->getlistModel()->getTable()->db_table_name;
		if (is_array($aDdObjs))
		{
			$tmp = array_merge($tmp, $aDdObjs);
		}
		$this->addSpaceToEmptyLabels($tmp);
		if ($this->showPleaseSelect())
		{
			array_unshift($tmp, JHTML::_('select.option', $params->get('database_join_noselectionvalue'), $this->_getSelectLabel()));
		}
		return $tmp;
	}

	/**
	 * Get select option label
	 *
	 * @return  string
	 */

	protected function _getSelectLabel()
	{
		return $this->getParams()->get('database_join_noselectionlabel', JText::_('COM_FABRIK_PLEASE_SELECT'));
	}

	/**
	 * Do you add a please select option to the list
	 *
	 * @since 3.0b
	 *
	 * @return  bool
	 */

	protected function showPleaseSelect()
	{
		$params = $this->getParams();
		$displayType = $params->get('database_join_display_type', 'dropdown');
		if ($displayType == 'dropdown' && $params->get('database_join_show_please_select', true))
		{
			return true;
		}
		return false;
	}

	/**
	 * Check to see if prefilter should be applied
	 * Kind of an inverse access lookup
	 *
	 * @param   int     $gid  group id to check against
	 * @param   string  $ref  for filter
	 *
	 * @return  bool	must apply filter - true, ignore filter (user has enough access rights) false;
	 */

	protected function mustApplyWhere($gid, $ref)
	{
		// $$$ hugh - adding 'where when' so can control whether to apply WHERE either on
		// new, edit or both (1, 2 or 3)
		$params = $this->getParams();
		$wherewhen = $params->get('database_join_where_when', '3');
		$isnew = JRequest::getInt('rowid', 0) === 0;
		if ($isnew && $wherewhen == '2')
		{
			return false;
		}
		elseif (!$isnew && $wherewhen == '1')
		{
			return false;
		}
		return in_array($gid, JFactory::getUser()->authorisedLevels());
	}

	/**
	 * create the sql query used to get the join data
	 *
	 * @param   array  $data      data
	 * @param   bool   $incWhere  include where
	 *
	 * @return  mixed	string or false if query can't be built
	 */

	function _buildQuery($data = array(), $incWhere = true)
	{
		$sig = isset($this->_autocomplete_where) ? $this->_autocomplete_where . '.' . $incWhere : $incWhere;

		$db = FabrikWorker::getDbo();
		if (isset($this->_sql[$sig]))
		{
			return $this->_sql[$sig];
		}
		$params = $this->getParams();
		$element = $this->getElement();
		$formModel = $this->getForm();
		$where = $this->_buildQueryWhere($data, $incWhere);

		// $$$rob not sure these should be used anyway?
		$table = $params->get('join_db_name');
		$key = $this->getJoinValueColumn();
		$val = $this->_getValColumn();
		$join = $this->getJoin();
		if ($table == '')
		{
			$table = $join->table_join;
			$key = $join->table_join_key;
			$val = $db->quoteName($join->_params->get('join-label', $val));
		}
		if ($key == '' || $val == '')
		{
			return false;
		}
		$query = $db->getQuery(true);
		$sql = "SELECT DISTINCT($key) AS value, $val AS text";
		$desc = $params->get('join_desc_column', '');
		if ($desc !== '')
		{
			$desc = "REPLACE(" . $db->quoteName($desc) . ", '\n', '<br />')";
			$sql .= ', ' . $desc . ' AS description';
		}
		$sql .= $this->getAdditionalQueryFields();
		$sql .= ' FROM ' . $db->quoteName($table) . ' AS ' . $db->quoteName($join->table_join_alias);
		$sql .= $this->buildQueryJoin();
		$sql .= ' ' . $where . ' ';
		/* $$$ hugh - let them specify an order by, i.e. don't append default if the $where already has an 'order by'
		 * @TODO - we should probably split 'order by' out in to another setting field, because if they are using
		 * the 'apply where beneath' and/or 'apply where when' feature, any custom ordering will not be applied
		 * if the 'where' is not being applied, which probably isn't what they want.
		 */
		if (!JString::stristr($where, 'order by'))
		{
			$sql .= $this->getOrderBy();
		}
		$this->_sql[$sig] = $sql;
		return $this->_sql[$sig];
	}

	/**
	 * If _buildQuery needs additional fields then set them here, used in notes plugin
	 *
	 * @since 3.0rc1
	 *
	 * @return string fields to add e.g return ',name, username AS other'
	 */

	protected function getAdditionalQueryFields()
	{
		return '';
	}

	/**
	 * If _buildQuery needs additional joins then set them here, used in notes plugin
	 *
	 * @since 3.0rc1
	 *
	 * @return string join statement to add
	 */

	protected function buildQueryJoin()
	{
		return '';
	}

	/**
	 * Create the where part for the query that selects the list options
	 *
	 * @param   array   $data            current row data to use in placeholder replacements
	 * @param   bool    $incWhere        should the additional user defined WHERE statement be included
	 * @param   string  $thisTableAlias  db table alais
	 * @param   array   $opts            options
	 *
	 * @return string
	 */

	function _buildQueryWhere($data = array(), $incWhere = true, $thisTableAlias = null, $opts = array())
	{
		$where = '';
		$listModel = $this->getlistModel();
		$params = $this->getParams();
		$element = $this->getElement();
		$whereaccess = $params->get('database_join_where_access', 26);
		if ($this->mustApplyWhere($whereaccess, $element->id) && $incWhere)
		{
			$where = $params->get('database_join_where_sql');
		}
		else
		{
			$where = '';
		}
		$join = $this->getJoin();
		$thisTableAlias = is_null($thisTableAlias) ? $join->table_join_alias : $thisTableAlias;

		// $$$rob 11/10/2011  remove order by statements which will be re-inserted at the end of _buildQuery()
		if (preg_match('/(ORDER\s+BY)(.*)/i', $where, $matches))
		{
			$this->orderBy = str_replace("{thistable}", $join->table_join_alias, $matches[0]);
			$where = str_replace($this->orderBy, '', $where);
		}
		if (!empty($this->_autocomplete_where))
		{

			$mode = JArrayHelper::getValue($opts, 'mode', 'form');
			$filterType = $element->filter_type;
			if (($mode == 'filter' && $filterType == 'auto-complete')
				|| $mode == 'form' && $params->get('database_join_display_type') == 'auto-complete')
			{
				$where .= JString::stristr($where, 'WHERE') ? ' AND ' . $this->_autocomplete_where : ' WHERE ' . $this->_autocomplete_where;
			}
		}
		if ($where == '')
		{
			return $where;
		}
		$where = str_replace("{thistable}", $thisTableAlias, $where);
		$w = new FabrikWorker;
		$data = is_array($data) ? $data : array();
		$where = $w->parseMessageForPlaceHolder($where, $data, false);
		return $where;
	}

	/**
	 * Get the element name or concat statement used to build the dropdown labels or
	 * table data field
	 *
	 * @return  string
	 */

	protected function _getValColumn()
	{
		$params = $this->getParams();
		$join = $this->getJoin();
		if ($params->get('join_val_column_concat') == '')
		{
			return $params->get('join_val_column');
		}
		else
		{
			$val = str_replace("{thistable}", $join->table_join_alias, $params->get('join_val_column_concat'));
			$w = new FabrikWorker;
			$val = $w->parseMessageForPlaceHolder($val, array(), false);
			return 'CONCAT(' . $val . ')';
		}
	}

	/**
	 * Get the database object
	 *
	 * @return  object	database
	 */

	function getDb()
	{
		$cn = $this->getConnection();
		if (!$this->_joinDb)
		{
			$this->_joinDb = $cn->getDb();
		}
		return $this->_joinDb;
	}

	/**
	 * get connection
	 *
	 * @return	object	connection
	 */

	function &getConnection()
	{
		if (is_null($this->_cn))
		{
			$this->_loadConnection();
		}
		return $this->_cn;
	}

	protected function connectionParam()
	{
		return 'join_conn_id';
	}

	/**
	 * Load connection object
	 *
	 * @return	object	connection table
	 */

	protected function _loadConnection()
	{
		$params = $this->getParams();
		$id = $params->get('join_conn_id');
		$cid = $this->getlistModel()->getConnection()->getConnection()->id;
		if ($cid == $id)
		{
			$this->_cn = $this->getlistModel()->getConnection();
		}
		else
		{
			$this->_cn = JModel::getInstance('Connection', 'FabrikFEModel');
			$this->_cn->setId($id);
		}
		return $this->_cn->getConnection();
	}

	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  when repeating joinded groups we need to know what part of the array to access
	 *
	 * @return  string	value
	 */

	public function getROValue($data, $repeatCounter = 0)
	{
		$v = $this->getValue($data, $repeatCounter);
		return $this->getLabelForValue($v, $v, $repeatCounter);
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
		// For repetaing groups we need to unset this where each time the element is rendered
		unset($this->_autocomplete_where);
		if ($this->isJoin())
		{
			$this->hasSubElements = true;
		}
		$params = $this->getParams();
		$formModel = $this->getForm();
		$groupModel = $this->getGroup();
		$element = $this->getElement();
		$aGroupRepeats[$element->group_id] = $groupModel->canRepeat();
		$displayType = $params->get('database_join_display_type', 'dropdown');
		$db = $this->getDb();
		if (!$db)
		{
			JError::raiseWarning(JText::sprintf('PLG_ELEMENT_DBJOIN_DB_CONN_ERR', $element->name));
			return '';
		}
		if (isset($formModel->_aJoinGroupIds[$groupModel->getId()]))
		{
			$joinId = $formModel->_aJoinGroupIds[$groupModel->getId()];
			$joinGroupId = $groupModel->getId();
		}
		else
		{
			$joinId = '';
			$joinGroupId = '';
		}
		$default = (array) $this->getValue($data, $repeatCounter);
		$tmp = $this->_getOptions($data, $repeatCounter);
		$w = new FabrikWorker;
		foreach ($default as &$d)
		{
			$d = $w->parseMessageForPlaceHolder($d);
		}
		$thisElName = $this->getHTMLName($repeatCounter);

		// Get the default label for the drop down (use in read only templates)
		$defaultLabel = '';
		$defaultValue = '';
		foreach ($tmp as $obj)
		{
			if ($obj->value == JArrayHelper::getValue($default, 0, ''))
			{
				$defaultValue = $obj->value;
				$defaultLabel = $obj->text;
				break;
			}
		}
		$id = $this->getHTMLId($repeatCounter);

		// $$$ rob 24/05/2011 - add options per row
		$options_per_row = intval($params->get('dbjoin_options_per_row', 0));
		$html = array();

		// $$$ hugh - still need to check $this->_editable, as content plugin sets it to false,
		// as no point rendering editable view for {fabrik view=element ...} in an article.
		if (!$formModel->isEditable() || !$this->_editable)
		{
			// $$$ rob 19/03/2012 uncommented line below - needed for checkbox rendering
			$defaultLabel = $this->renderListData($default, JArrayHelper::toObject($data));
			if ($defaultLabel === $params->get('database_join_noselectionlabel', JText::_('COM_FABRIK_PLEASE_SELECT')))
			{
				// No point showing 'please select' for read only
				$defaultLabel = '';
			}
			if ($params->get('databasejoin_readonly_link') == 1)
			{
				$popupformid = (int) $params->get('databasejoin_popupform');
				if ($popupformid !== 0)
				{
					$query = $db->getQuery(true);
					$query->select('id')->from('#__{package}_lists')->where('form_id =' . $popupformid);
					$db->setQuery($query);
					$listid = $db->loadResult();
					$url = 'index.php?option=com_fabrik&view=details&formid=' . $popupformid . '&listid =' . $listid . '&rowid=' . $defaultValue;
					$defaultLabel = '<a href="' . JRoute::_($url) . '">' . $defaultLabel . '</a>';
				}
			}
			$html[] = $defaultLabel;
		}
		else
		{
			// $$$rob should be canUse() otherwise if user set to view but not use the dd was shown
			if ($this->canUse())
			{
				$idname = $this->getFullName(false, true, false) . '_id';
				$attribs = 'class="fabrikinput inputbox" size="1"';
				/*if user can access the drop down*/
				switch ($displayType)
				{
					case 'dropdown':
					default:
						$html[] = JHTML::_('select.genericlist', $tmp, $thisElName, $attribs, 'value', 'text', $default, $id);
						break;
					case 'radio':
					// $$$ rob 24/05/2011 - always set one value as selected for radio button if none already set
						if ($defaultValue == '' && !empty($tmp))
						{
							$defaultValue = $tmp[0]->value;
						}
						// $$$ rob 24/05/2011 - add options per row
						$options_per_row = intval($params->get('dbjoin_options_per_row', 0));
						$html[] = '<div class="fabrikSubElementContainer" id="' . $id . '">';
						$html[] = FabrikHelperHTML::aList($displayType, $tmp, $thisElName, $attribs . ' id="' . $id . '"', $defaultValue, 'value',
							'text', $options_per_row);
						break;
					case 'checkbox':
						$defaults = $formModel->failedValidation() ? $default : explode(GROUPSPLITTER, JArrayHelper::getValue($data, $idname));
						$html[] = '<div class="fabrikSubElementContainer" id="' . $id . '">';
						$rawname = $this->getFullName(false, true, false) . '_raw';

						$html[] = FabrikHelperHTML::aList($displayType, $tmp, $thisElName, $attribs . ' id="' . $id . '"', $defaults, 'value',
							'text', $options_per_row, $this->_editable);
						if ($this->isJoin() && $this->_editable)
						{
							$join = $this->getJoin();
							$joinidsName = 'join[' . $join->id . '][' . $join->table_join . '___id]';
							if ($groupModel->canRepeat())
							{
								$joinidsName .= '[' . $repeatCounter . '][]';
								$joinids = FArrayHelper::getNestedValue($data, 'join.' . $joinId . '.' . $rawname . '.' . $repeatCounter, 'not found');
							}
							else
							{
								$joinidsName .= '[]';
								$joinids = explode(GROUPSPLITTER, JArrayHelper::getValue($data, $rawname));
							}
							$tmpids = array();
							foreach ($tmp as $obj)
							{
								$o = new stdClass;
								$o->text = $obj->text;
								if (in_array($obj->value, $defaults))
								{
									$index = array_search($obj->value, $defaults);
									$o->value = JArrayHelper::getValue($joinids, $index);
								}
								else
								{
									$o->value = 0;
								}
								$tmpids[] = $o;
							}
							$html[] = '<div class="fabrikHide">';
							$attribs = 'class="fabrikinput inputbox" size="1" id="' . $id . '"';
							$html[] = FabrikHelperHTML::aList($displayType, $tmpids, $joinidsName, $attribs, $joinids, 'value', 'text',
								$options_per_row, $this->_editable);
							$html[] = '</div>';
						}
						$defaultLabel = implode("\n", $html);
						break;
					case 'multilist':
						$defaults = $formModel->failedValidation() ? $default : explode(GROUPSPLITTER, JArrayHelper::getValue($data, $idname));
						if ($this->_editable)
						{
							$multiSize = (int) $params->get('dbjoin_multilist_size', 6);
							$attribs = 'class="fabrikinput inputbox" size="' . $multiSize . '" multiple="true"';
							$html[] = JHTML::_('select.genericlist', $tmp, $thisElName, $attribs, 'value', 'text', $defaults, $id);
						}
						else
						{
							$attribs = 'class="fabrikinput inputbox" size="1" id="' . $id . '"';
							$html[] = FabrikHelperHTML::aList($displayType, $tmp, $thisElName, $attribs, $defaults, 'value', 'text',
								$options_per_row, $this->_editable);
						}
						$defaultLabel = implode("\n", $html);
						break;
					case 'auto-complete':
					// Get the LABEL from the form's data.
						$label = (array) $this->getValue($data, $repeatCounter, array('valueFormat' => 'label'));

						// $$$ rob 18/06/2012 if form submitted with errors - reshowing the auto-complete wont have access to the submitted values label
						if ($formModel->hasErrors())
						{
							$label = (array) $this->getLabelForValue($label[0], $label[0], $repeatCounter);
						}
						$autoCompleteName = str_replace('[]', '', $thisElName) . '-auto-complete';
						$html[] = '<input type="text" size="' . $params->get('dbjoin_autocomplete_size', '20') . '" name="' . $autoCompleteName
							. '" id="' . $id . '-auto-complete" value="' . JArrayHelper::getValue($label, 0)
							. '" class="fabrikinput inputbox autocomplete-trigger"/>';

						// $$$ rob - class property required when cloning repeat groups - don't remove
						$html[] = '<input type="hidden" class="fabrikinput" size="20" name="' . $thisElName . '" id="' . $id . '" value="'
							. JArrayHelper::getValue($default, 0, '') . '"/>';
						break;
				}

				if ($params->get('fabrikdatabasejoin_frontend_select') && $this->_editable)
				{
					JText::script('PLG_ELEMENT_DBJOIN_SELECT');
					$html[] = '<a href="#" class="toggle-selectoption" title="' . JText::_('COM_FABRIK_SELECT') . '">'
						. FabrikHelperHTML::image('search.png', 'form', @$this->tmpl, array('alt' => JText::_('COM_FABRIK_SELECT'))) . '</a>';
				}

				if ($params->get('fabrikdatabasejoin_frontend_add') && $this->_editable)
				{
					JText::script('PLG_ELEMENT_DBJOIN_ADD');
					$html[] = '<a href="#" title="' . JText::_('COM_FABRIK_ADD') . '" class="toggle-addoption">';
					$html[] = FabrikHelperHTML::image('action_add.png', 'form', @$this->tmpl, array('alt' => JText::_('COM_FABRIK_SELECT'))) . '</a>';
				}

				$html[] = ($displayType == 'radio') ? '</div>' : '';
			}
			elseif ($this->canView())
			{
				$html[] = $this->renderListData($default, JArrayHelper::toObject($data));
			}
		}
		if ($params->get('join_desc_column', '') !== '')
		{
			$html[] = '<div class="dbjoin-description">';
			$opts = $this->_getOptionVals($data, $repeatCounter);
			for ($i = 0; $i < count($opts); $i++)
			{
				$opt = $opts[$i];
				$display = $opt->value == $default ? '' : 'none';
				$c = $i + 1;
				$html[] = '<div style="display:' . $display . '" class="notice description-' . $c . '">' . $opt->description . '</div>';
			}
			$html[] = '</div>';
		}
		return implode("\n", $html);
	}

	/**
	 * called from within function getValue
	 * needed so we can append _raw to the name for elements such as db joins
	 *
	 * @param   array  $opts  options
	 *
	 * @return  string  element name inside data array
	 */

	protected function getValueFullName($opts)
	{
		$name = $this->getFullName(false, true, false);
		$params = $this->getParams();
		if (!$this->isJoin() && JArrayHelper::getValue($opts, 'valueFormat', 'raw') == 'raw')
		{
			$name .= '_raw';
		}
		return $name;
	}

	/**
	 * Determines the label used for the browser title
	 * in the form/detail views
	 *
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  when repeating joinded groups we need to know what part of the array to access
	 * @param   array  $opts           options
	 *
	 * @return  string	default value
	 */

	public function getTitlePart($data, $repeatCounter = 0, $opts = array())
	{
		// $$$ rob set ths to label otherwise we get the value/key and not label
		$opts['valueFormat'] = 'label';
		return $this->getValue($data, $repeatCounter, $opts);
	}

	/**
	 * Get an array of potential forms that will add data to the db joins table.
	 * Used for add in front end
	 *
	 *  @return  array  db objects
	 */

	protected function getLinkedForms()
	{
		if (!isset($this->_linkedForms))
		{
			$db = FabrikWorker::getDbo(true);
			$params = $this->getParams();

			// Forms for potential add record pop up form
			$query = $db->getQuery(true);
			$query->select('f.id AS value, f.label AS text, l.id AS listid')->from('#__{package}_forms AS f')
				->join('LEFT', '#__{package}_lists As l ON f.id = l.form_id')
				->where('f.published = 1 AND l.db_table_name = ' . $db->quote($params->get('join_db_name')))->order('f.label');
			$db->setQuery($query);

			$this->_linkedForms = $db->loadObjectList('value');

			// Check for a database error.
			if ($db->getErrorNum())
			{
				JError::raiseError(500, $db->getErrorMsg());
			}
		}
		return $this->_linkedForms;
	}

	/**
	 * Get database field description
	 *
	 * @return  string  db field type
	 */

	public function getFieldDescription()
	{
		$params = $this->getParams();
		if ($this->encryptMe())
		{
			return 'BLOB';
		}
		$db = $this->getDb();

		// Lets see if we can get the field type of the field we are joining to
		$join = FabTable::getInstance('Join', 'FabrikTable');
		if ((int) $this->_id !== 0)
		{
			$join->load(array('element_id' => $this->_id));
			if ($join->table_join == '')
			{
				/* $$$ hugh - this almost certainly means we are changing element type to a join,
				 * and the join row hasn't been created yet.  So let's grab the params, instead of
				 * defaulting to VARCHAR
				 * return "VARCHAR(255)";
				 */
				$dbName = $params->get('join_db_name');
				$joinKey = $params->get('join_key_column');
			}
			else
			{
				$dbName = $join->table_join;
				$joinKey = $join->table_join_key;
			}
		}
		else
		{
			$dbName = $params->get('join_db_name');
			$joinKey = $params->get('join_key_column');
		}

		$db->setQuery('DESCRIBE ' . $db->quoteName($dbName));
		$fields = $db->loadObjectList();
		if (!$fields)
		{
			echo $db->getErrorMsg();
		}
		if (is_array($fields))
		{
			foreach ($fields as $field)
			{
				if ($field->Field == $joinKey)
				{
					return $field->Type;
				}
			}
		}

		// Nope? oh well default to this:
		return "VARCHAR(255)";
	}

	/**
	 * Used to format the data when shown in the form's email
	 *
	 * @param   mixed  $value          element's data
	 * @param   array  $data           form records data
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	formatted value
	 */

	function getEmailValue($value, $data, $repeatCounter)
	{
		$tmp = $this->_getOptions($data, $repeatCounter);
		if ($this->isJoin())
		{
			// $$$ hugh - if it's a repeat element, we need to render it as
			// a single entity
			foreach ($value as &$v2)
			{
				foreach ($tmp as $v)
				{
					if ($v->value == $v2)
					{
						$v2 = $v->text;
						break;
					}
				}
			}
			$val = $this->renderListData($value, new stdClass);
		}
		else
		{
			if (is_array($value))
			{
				foreach ($value as &$v2)
				{
					foreach ($tmp as $v)
					{
						if ($v->value == $v2)
						{
							$v2 = $v->text;
							break;
						}
					}
					$v2 = $this->renderListData($v2, new stdClass);
				}
				$val = $value;
			}
			else
			{
				foreach ($tmp as $v)
				{
					if ($v->value == $value)
					{
						$value = $v->text;
					}
				}
				$val = $this->renderListData($value, new stdClass);
			}
		}
		return $val;
	}

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
		$params = $this->getParams();
		$groupModel = $this->getGroupModel();
		$labeldata = array();
		if (!$groupModel->isJoin() && $groupModel->canRepeat())
		{
			$opts = $this->_getOptionVals();
			$name = $this->getFullName(false, true, false) . '_raw';

			// If coming from fabrikemail plugin $thisRow is empty
			if (isset($thisRow->$name))
			{
				$data = $thisRow->$name;
			}
			if (!is_array($data))
			{
				$data = json_decode($data, true);
			}
			foreach ($data as $d)
			{
				foreach ($opts as $opt)
				{
					if ($opt->value == $d)
					{
						$labeldata[] = $opt->text;
						break;
					}
				}
			}
		}
		else
		{
			// $$$ hugh - $data may already be JSON encoded, so we don't want to double-encode.
			if (!FabrikWorker::isJSON($data))
			{
				$labeldata[] = $data;
			}
			else
			{
				// $$$ hugh - yeah, I know, kinda silly to decode right before we encode,
				// should really refactor so encoding goes in this if/else structure!
				$labeldata = json_decode($data);
			}
		}

		$data = json_encode($labeldata);

		// $$$ rob add links and icons done in parent::renderListData();
		return parent::renderListData($data, $thisRow);
	}

	/**
	 * Get the list filter for the element
	 *
	 * @param   int   $counter  filter order
	 * @param   bool  $normal   do we render as a normal filter or as an advanced search filter
	 * if normal include the hidden fields as well (default true, use false for advanced filter rendering)
	 *
	 * @return  string	filter html
	 */

	public function getFilter($counter = 0, $normal = true)
	{
		$params = $this->getParams();
		$element = $this->getElement();
		$listModel = $this->getlistModel();
		$table = $listModel->getTable();
		$elName = $this->getFilterFullName();
		$htmlid = $this->getHTMLId() . 'value';
		$v = $this->filterName($counter, $normal);
		$return = array();
		$default = $this->getDefaultFilterVal($normal, $counter);
		if (in_array($element->filter_type, array('range', 'dropdown', '')))
		{
			$joinVal = $this->getJoinLabelColumn();
			$incJoin = (trim($params->get('join_val_column_concat')) == '' && trim($params->get('database_join_where_sql') == '')) ? false : true;
			$rows = $this->filterValueList($normal, null, $joinVal, '', $incJoin);
			if (!$rows)
			{
				/* $$$ hugh - let's not raise a warning, as there are valid cases where a join may not yield results, see
				 * http://fabrikar.com/forums/showthread.php?p=100466#post100466
				 * JError::raiseWarning(500, 'database join filter query incorrect');
				 * Moved warning to element model filterValueList_Exact(), with a test for $fabrikDb->getErrorNum()
				 * So we'll just return an otherwise empty menu with just the 'select label'
				 */
				$rows = array();
				array_unshift($rows, JHTML::_('select.option', '', $this->filterSelectLabel()));
				$return[] = JHTML::_('select.genericlist', $rows, $v, 'class="inputbox fabrik_filter" size="1" ', "value", 'text', $default, $htmlid);
				return implode("\n", $return);
			}
			$this->unmergeFilterSplits($rows);
			$this->reapplyFilterLabels($rows);
			array_unshift($rows, JHTML::_('select.option', '', $this->filterSelectLabel()));
		}

		$size = $params->get('filter_length', 20);
		switch ($element->filter_type)
		{
			case "dropdown":
			default:
			case '':
				$this->addSpaceToEmptyLabels($rows, 'text');
				$return[] = JHTML::_('select.genericlist', $rows, $v, 'class="inputbox fabrik_filter" size="1" ', "value", 'text', $default, $htmlid);
				break;

			case "field":
				$return[] = '<input type="text" class="inputbox fabrik_filter" name="' . $v . '" value="' . $default . '" size="' . $size . '" id="'
					. $htmlid . '" />';
				$return[] = $this->filterHiddenFields();
				break;

			case "hidden":
				$return[] = '<input type="hidden" class="inputbox fabrik_filter" name="' . $v . '" value="' . $default . '" size="' . $size
					. '" id="' . $htmlid . '" />';
				$return[] = $this->filterHiddenFields();
				break;
			case "auto-complete":
				$defaultLabel = $this->getLabelForValue($default);
				$return[] = '<input type="hidden" name="' . $v . '" class="inputbox fabrik_filter ' . $htmlid . '" value="' . $default . '" />';
				$return[] = '<input type="text" name="' . $element->id . '-auto-complete" class="inputbox fabrik_filter autocomplete-trigger '
					. $htmlid . '-auto-complete" size="' . $size . '" value="' . $defaultLabel . '" />';
				$return[] = $this->filterHiddenFields();
				$selector = '#listform_' . $listModel->getRenderContext() . ' .' . $htmlid;
				FabrikHelperHTML::autoComplete($selector, $element->id, 'databasejoin');
				break;

		}
		if ($normal)
		{
			$return[] = $this->getFilterHiddenFields($counter, $elName);
		}
		else
		{
			$return[] = $this->getAdvancedFilterHiddenFields();
		}
		return implode("\n", $return);
	}

	protected function filterHiddenFields()
	{
		$params = $this->getParams();
		$elName = FabrikString::safeColNameToArrayKey($this->getFilterFullName());
		$return = array();
		$return[] = '<input type="hidden" name="' . $elName . '[join_db_name]" value="' . $params->get('join_db_name') . '" />';
		$return[] = '<input type="hidden" name="' . $elName . '[join_key_column]" value="' . $params->get('join_key_column') . '" />';
		$return[] = '<input type="hidden" name="' . $elName . '[join_val_column]" value="' . $params->get('join_val_column') . '" />';
		return implode("\n", $return);
	}

	/**
	 * Get dropdown filter select label
	 *
	 * @return  string
	 */

	protected function filterSelectLabel()
	{
		$params = $this->getParams();
		$label = $params->get('database_join_noselectionlabel');
		if ($label == '')
		{
			$label = $params->get('filter_required') == 1 ? JText::_('COM_FABRIK_PLEASE_SELECT') : JText::_('COM_FABRIK_FILTER_PLEASE_SELECT');
		}
		return $label;
	}

	/**
	 * If filterValueList_Exact incjoin value = false, then this method is called
	 * to ensure that the query produced in filterValueList_Exact contains at least the database join element's
	 * join
	 *
	 * @return  string  required join text to ensure exact filter list code produces a valid query.
	 */

	protected function _buildFilterJoin()
	{
		$params = $this->getParams();
		$joinTable = FabrikString::safeColName($params->get('join_db_name'));
		$join = $this->getJoin();
		$joinTableName = FabrikString::safeColName($join->table_join_alias);
		$joinKey = $this->getJoinValueColumn();
		$elName = FabrikString::safeColName($this->getFullName(false, true, false));
		return 'INNER JOIN ' . $joinTable . ' AS ' . $joinTableName . ' ON ' . $joinKey . ' = ' . $elName;
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
		if ($this->isJoin())
		{
			$rows = array_values($this->checkboxRows());
			return $rows;
		}
		/*
		 * list of all tables that have been joined to -
		 * if duplicated then we need to join using a table alias
		 */
		$listModel = $this->getlistModel();
		$table = $listModel->getTable();
		$origTable = $table->db_table_name;
		$fabrikDb = $listModel->getDb();
		$params = $this->getParams();
		$joinTable = $params->get('join_db_name');
		$joinKey = $this->getJoinValueColumn();
		$joinVal = $this->getJoinLabelColumn();

		$join = $this->getJoin();
		$joinTableName = $join->table_join_alias;
		if ($joinTable == '')
		{
			$joinTable = $joinTableName;
		}
		// $$$ hugh - select all values for performance gain over selecting distinct records from recorded data
		$sql = "SELECT DISTINCT( $joinVal ) AS text, $joinKey AS value \n FROM " . $fabrikDb->quoteName($joinTable) . ' AS '
			. $fabrikDb->quoteName($joinTableName) . " \n ";
		$where = $this->_buildQueryWhere(array(), true, null, array('mode' => 'filter'));

		// Ensure table prefilter is applied to query
		$prefilterWhere = $listModel->_buildQueryPrefilterWhere($this);
		$elementName = FabrikString::safeColName($this->getFullName(false, false, false));
		$prefilterWhere = str_replace($elementName, $joinKey, $prefilterWhere);
		if (trim($where) == '')
		{
			$prefilterWhere = str_replace('AND', 'WHERE', $prefilterWhere);
		}
		$where .= $prefilterWhere;

		$sql .= $where;
		if (!JString::stristr($where, 'order by'))
		{
			$sql .= $this->getOrderBy('filter');

		}
		$sql = $listModel->pluginQuery($sql);
		$fabrikDb->setQuery($sql);
		FabrikHelperHTML::debug($fabrikDb->getQuery(), 'fabrikdatabasejoin getFilter');
		return $fabrikDb->loadObjectList();
	}

	/**
	 * Get options order by
	 *
	 * @param   string  $view  view mode '' or 'filter'
	 *
	 * @return  string  order by statement
	 */

	protected function getOrderBy($view = '')
	{
		if ($view == 'filter')
		{
			$params = $this->getParams();
			$joinKey = $this->getJoinValueColumn();
			$joinLabel = $this->getJoinLabelColumn();
			$groupByCol = $params->get('filter_groupby', 'text');
			if ($groupByCol != -1)
			{
				$groupByCol = $groupByCol == 'text' ? $joinLabel : $joinKey;
				$order = 'ORDER BY ' . $groupByCol . ' ASC';
			}
			else
			{
				$order = $this->orderBy;
			}

			$order = $params->get('filter_groupby', 'text') == 'text' ? $joinLabel : $joinKey;
			return " ORDER BY $order ASC ";
		}
		else
		{
			if (isset($this->orderBy))
			{
				return $this->orderBy;
			}
			else
			{
				return "ORDER BY text ASC ";
			}
		}
	}

	/**
	 * Get the column name used for the value part of the db join element
	 *
	 * @return  string
	 */

	protected function getJoinValueColumn()
	{
		$params = $this->getParams();
		$join = $this->getJoin();
		$db = FabrikWorker::getDbo();

		// @TODO seems to me that actually table_join_alias is incorrect when the element is rendered as a checkbox?
		/*if ($this->isJoin()) {
		echo "<pre>";print_r($join);print_r($this->getElement());echo "</pre>";
		return $db->quoteName($params->get('join_db_name')).'.'.$db->quoteName($params->get('join_key_column'));
		} else {*/
		return $db->quoteName($join->table_join_alias . '.' . $params->get('join_key_column'));
		//}
	}

	/**
	 * Builds an array containing the filters value and condition
	 *
	 * @param   string  $value      initial value
	 * @param   string  $condition  intial $condition
	 * @param   string  $eval       how the value should be handled
	 *
	 * @since   3.0.6
	 *
	 * @return  array	(value condition)
	 */

	public function getFilterValue($value, $condition, $eval)
	{
		$fType = $this->getElement()->filter_type;
		if ($fType == 'auto-complete')
		{
			// Searching on value so set to equals
			$condition = '=';
		}
		return parent::getFilterValue($value, $condition, $eval);
	}

	/**
	 * Build the filter query for the given element.
	 * Can be overwritten in plugin - e.g. see checkbox element which checks for partial matches
	 *
	 * @param   string  $key            element name in format `tablename`.`elementname`
	 * @param   string  $condition      =/like etc
	 * @param   string  $value          search string - already quoted if specified in filter array options
	 * @param   string  $originalValue  original filter value without quotes or %'s applied
	 * @param   string  $type           filter type advanced/normal/prefilter/search/querystring/searchall
	 *
	 * @return  string	sql query part e,g, "key = value"
	 */

	public function getFilterQuery($key, $condition, $value, $originalValue, $type = 'normal')
	{
		/* $$$ rob $this->_rawFilter set in tableModel::getFilterArray()
		 used in prefilter dropdown in admin to allow users to prefilter on raw db join value */
		$str = '';
		$params = $this->getParams();
		$db = JFactory::getDBO();
		if ($type == 'querystring')
		{
			//$key2 = FabrikString::safeColNameToArrayKey($key);
			// $$$ rob no matter whether you use elementname_raw or elementname in the querystring filter
			// by the time it gets here we have normalized to elementname. So we check if the original qs filter was looking at the raw
			// value if it was then we want to filter on the key and not the label
			//if (!array_key_exists($key2, JRequest::get('get'))) {
			if (!$this->_rawFilter)
			{
				$k = $db->quoteName($params->get('join_db_name')) . '.' . $db->quoteName($params->get('join_val_column'));
			}
			else
			{
				$k = $key;
			}
			$this->encryptFieldName($k);
			return "$k $condition $value";
			//}
		}
		$this->encryptFieldName($key);
		if (!$this->_rawFilter && ($type == 'searchall' || $type == 'prefilter'))
		{
			// $$$rob wasnt working for 2nd+ db join element to same table (where key = `countries_0`.`label`)
			//$k = '`' . $params->get('join_db_name'). "`.`".$params->get('join_val_column').'`';
			$str = "$key $condition $value";
		}
		else
		{
			$group = $this->getGroup();
			if (!$group->isJoin() && $group->canRepeat())
			{
				$fval = $this->getElement()->filter_exact_match ? $originalValue : $value;
				$str = " ($key = $fval OR $key LIKE \"$originalValue',%\"" . " OR $key LIKE \"%:'$originalValue',%\""
					. " OR $key LIKE \"%:'$originalValue'\"" . " )";
			}
			else
			{
				if ($this->isJoin())
				{
					$fType = $this->getElement()->filter_type;
					if ($fType == 'auto-complete' || $fType == 'field')
					{
						$where = $db->quoteName($params->get('join_db_name')) . '.' . $db->quoteName($params->get('join_val_column'));
					}
					else
					{
						$where = $db->quoteName($params->get('join_db_name')) . '.' . $db->quoteName($params->get('join_key_column'));
					}
					$groupBy = $db->quoteName($params->get('join_db_name') . '.parent_id');
					$rows = $this->checkboxRows($groupBy, $condition, $value, $where);
					$joinIds = array_keys($rows);
					if (!empty($rows))
					{
						$str = $this->getListModel()->getTable()->db_primary_key . " IN (" . implode(', ', $joinIds) . ")";
					}
				}
				else
				{
					$str = "$key $condition $value";
				}
			}
		}
		return $str;
	}

	/**
	 * Helper function to get an array of data from the checkbox joined db table.
	 * Used for working out the filter sql and filter dropdown contents
	 *
	 * @param   string  $groupBy    field name to key the results on - avoids duplicates
	 * @param   string  $condition  if supplied then filters the list (must then supply $where and $value)
	 * @param   string  $value      if supplied then filters the list (must then supply $where and $condtion)
	 * @param   string  $where      if supplied then filters the list (must then supply $value and $condtion)
	 *
	 * @return  array	rows
	 */

	protected function checkboxRows($groupBy = null, $condition = null, $value = null, $where = null)
	{
		$params = $this->getParams();
		$db = $this->getDb();
		$query = $db->getQuery(true);
		$join = $this->getJoinModel()->getJoin();
		$jointable = $db->quoteName($join->table_join);
		$shortName = $db->quoteName($this->getElement()->name);
		if (is_null($groupBy))
		{
			$groupBy = 'value';
		}
		$to = $params->get('join_db_name');
		$key = $db->quoteName($to . '.' . $params->get('join_key_column'));
		$label = $db->quoteName($to . '.' . $params->get('join_val_column'));
		$v = $jointable . '.' . $shortName;
		$query->select($jointable . '.parent_id, ' . $v . ' AS value, ' . $label . ' AS text')->from($jointable)
			->join('LEFT', $to . ' ON ' . $key . ' = ' . $jointable . '.' . $shortName);
		if (!is_null($condition) && !is_null($value))
		{
			if (is_null($where))
			{
				$where = $label;
			}
			$query->where($where . ' ' . $condition . ' ' . $value);
		}
		$db->setQuery($query);
		$groupBy = FabrikString::shortColName($groupBy);
		$rows = $db->loadObjectList($groupBy);
		return $rows;
	}

	/**
	 * Used for the name of the filter fields
	 * Over written here as we need to get the label field for field searches
	 *
	 * @return  string	element filter name
	 */

	public function getFilterFullName()
	{
		$element = $this->getElement();
		$params = $this->getParams();
		$fields = array('auto-complete', 'field');
		if ($params->get('join_val_column_concat', '') !== '' && in_array($element->filter_type, $fields))
		{
			return htmlspecialchars($this->getJoinLabelColumn(), ENT_QUOTES);
		}
		else
		{
			$join_db_name = $params->get('join_db_name');
			$listModel = $this->getlistModel();
			$joins = $listModel->getJoins();
			foreach ($joins as $join)
			{
				if ($join->element_id == $element->id)
				{
					$join_db_name = $join->table_join_alias;
				}
			}
			if ($element->filter_type == 'field')
			{
				$elName = $join_db_name . '___' . $params->get('join_val_column');
			}
			else
			{
				$elName = parent::getFilterFullName();
			}
		}
		return FabrikString::safeColName($elName);
	}

	function getFilterLabel($rawval)
	{
		$db = $this->getDb();
		$params = $this->getParams();
		$orig = $params->get('database_join_where_sql');
		$k = $params->get('join_key_column');
		$l = $params->get('join_val_column');
		$t = $params->get('join_db_name');
		if ($k != '' && $l != '' & $t != '' && $rawval != '')
		{
			$db->setQuery("SELECT $l FROM $t WHERE $k = $rawval");
			return $db->loadResult();
		}
		else
		{
			return $rawval;
		}
	}

	/**
	 * Does the element conside the data to be empty
	 * Used in isempty validation rule
	 *
	 * @param   array  $data           data to test against
	 * @param   int    $repeatCounter  repeat group #
	 *
	 * @return  bool
	 */

	public function dataConsideredEmpty($data, $repeatCounter)
	{
		// $$$ hugh on validations (at least), we're getting arrays
		if (is_array($data))
		{
			return empty($data[0]);
		}
		if ($data == '' || $data == '-1')
		{
			return true;
		}
		return false;
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
		$id = $this->getHTMLId($repeatCounter);
		if ($this->getParams()->get('database_join_display_type') == 'auto-complete')
		{
			FabrikHelperHTML::autoComplete($id, $this->getElement()->id, 'databasejoin');
		}
		$opts = $this->elementJavascriptOpts($repeatCounter);
		return "new FbDatabasejoin('$id', $opts)";
	}

	/**
	 * Get the class name for the element wrapping dom object
	 *
	 * @param   object  $element  element model item
	 *
	 * @since 3.0
	 *
	 * @return string of class names
	 */

	protected function containerClass($element)
	{
		$c = explode(' ', parent::containerClass($element));
		$params = $this->getParams();
		$c[] = 'mode-' . $params->get('database_join_display_type', 'dropdown');
		return implode(' ', $c);
	}

	/**
	 * Get element JS options
	 *
	 * @param   int  $repeatCounter  group repeat counter
	 *
	 * @return  string  json_encoded options
	 */

	protected function elementJavascriptOpts($repeatCounter)
	{
		$params = $this->getParams();
		$element = $this->getElement();
		$opts = $this->_getOptionVals();
		$data = $this->_form->_data;
		$arSelected = $this->getValue($data, $repeatCounter);
		$arVals = $this->getSubOptionValues();
		$arTxt = $this->getSubOptionLabels();

		$table = $params->get('join_db_name');
		$opts = $this->getElementJSOptions($repeatCounter);
		$forms = $this->getLinkedForms();
		$popupform = (int) $params->get('databasejoin_popupform');
		$popuplistid = (empty($popupform) || !isset($forms[$popupform])) ? '' : $forms[$popupform]->listid;
		$opts->id = $this->_id;
		$opts->fullName = $this->getFullName(false, true, false);
		$opts->key = $table . '___' . $params->get('join_key_column');
		$opts->label = $table . '___' . $params->get('join_val_column');
		$opts->formid = $this->getForm()->getForm()->id;
		$opts->listid = $popuplistid;
		$opts->listRef = '_com_fabrik_' . $opts->listid;
		$opts->value = $arSelected;
		$opts->defaultVal = $this->getDefaultValue($data);
		$opts->popupform = $popupform;
		$opts->popwiny = $params->get('yoffset', 0);
		$opts->display_type = $params->get('database_join_display_type', 'dropdown');
		$opts->windowwidth = $params->get('join_popupwidth', 360);
		$opts->displayType = $params->get('database_join_display_type', 'dropdown');
		$opts->show_please_select = $params->get('database_join_show_please_select');
		$opts->showDesc = $params->get('join_desc_column', '') === '' ? false : true;
		$opts->autoCompleteOpts = $opts->display_type == 'auto-complete'
			? FabrikHelperHTML::autoCompletOptions($opts->id, $this->getElement()->id, 'databasejoin') : null;
		$opts->allowadd = $params->get('fabrikdatabasejoin_frontend_add', 0) == 0 ? false : true;
		if ($this->isJoin())
		{
			$join = $this->getJoin();
			$opts->joinTable = $join->table_join;

			// $$$ rob - wrong for inline edit plugin
			// $opts->elementName = $join->table_join;
			$opts->elementName = $join->table_join . '___' . $element->name;
			$opts->elementShortName = $element->name;
			$opts->joinId = $join->id;
			$opts->isJoin = true;
		}
		$opts->isJoin = $this->isJoin();
		return json_encode($opts);
	}

	/**
	 * Gets the options for the drop down - used in package when forms update
	 *
	 * @return  void
	 */

	public function onAjax_getOptions()
	{
		// Needed for ajax update (since we are calling this method via dispatcher element is not set
		$this->_id = JRequest::getInt('element_id');
		$this->getElement(true);
		echo json_encode($this->_getOptions(JRequest::get('request')));
	}

	/**
	 * Called when the element is saved
	 *
	 * @param   array  $data  posted element save data
	 *
	 * @return  bool  save ok or not
	 */

	public function onSave($data)
	{
		$params = json_decode($data['params']);
		if (!$this->isJoin())
		{
			$this->updateFabrikJoins($data, $this->getDbName(), $params->join_key_column, $params->join_val_column);
		}
		return parent::onSave();
	}

	/**
	 * get the join to database name
	 * @return  string	database name
	 */

	protected function getDbName()
	{
		if (!isset($this->dbname) || $this->dbname == '')
		{
			$params = $this->getParams();
			$id = $params->get('join_db_name');
			if (is_numeric($id))
			{
				if ($id == '')
				{
					JError::raiseWarning(500, 'Unable to get table for cascading dropdown (ignore if creating a new element)');
					return false;
				}
				$db = FabrikWorker::getDbo(true);
				$query = $db->getQuery(true);
				$query->select('db_table_name')->from('#__{package}_lists')->where('id = ' . (int) $id);
				$db->setQuery($query);
				$this->dbname = $db->loadResult();
			}
			else
			{
				$this->dbname = $id;
			}
		}
		return $this->dbname;

	}

	/**
	 * @since 3.0b
	 * on save of element, update its jos_fabrik_joins record and any decendants join record
	 * @param   array	$data
	 * @param   string	$tableJoin
	 * @param   string	$keyCol
	 * @param   string	$label
	 */

	protected function updateFabrikJoins($data, $tableJoin, $keyCol, $label)
	{
		//load join based on this element id
		$this->updateFabrikJoin($data, $this->_id, $tableJoin, $keyCol, $label);
		$children = $this->getElementDescendents($this->_id);
		foreach ($children as $id)
		{
			$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($id);
			$data['group_id'] = $elementModel->getElement()->group_id;
			$data['id'] = $id;
			$this->updateFabrikJoin($data, $id, $tableJoin, $keyCol, $label);
		}
	}

	/**
	 * @since 3.0b
	 * update an elements jos_fabrik_joins record
	 * @param   array	$data
	 * @param   int		element id
	 * @param   string	$tableJoin
	 * @param   string	$keyCol
	 * @param   string	$label
	 */

	protected function updateFabrikJoin($data, $elementId, $tableJoin, $keyCol, $label)
	{
		$params = json_decode($data['params']);
		$element = $this->getElement();
		$join = FabTable::getInstance('Join', 'FabrikTable');
		// $$$ rob 08/05/2012 - toggling from dropdown to multiselect set the list_id to 1, so if you
		// reset to dropdown then this key would not load the existing join so a secondary join record
		// would be created for the element.
		//$key = array('element_id' => $data['id'], 'list_id' => 0);
		// $$$ hugh - NOOOOOOOO!  Creating a new user element, $data['id'] is 0, so without the list_id => we end up loading the first
		// list join at random, instead of a new row, which has SERIOUSLY BAD side effects, and is responsible for the Mysterious Disappearing
		// Group issue ... 'cos the list_id gets set wrong.
		// I *think* the actual problem is that we weren't setting $data['id'] to newly created element id in the element model save() method, before
		// calling onSave(), which I've now done, but just to be on the safe side, put in some defensive code so id $data['id'] is 0, we make sure
		// we don't load a random list join row!!
		if ($data['id'] == 0)
		{
			$key = array('element_id' => $data['id'], 'list_id' => 0);
		}
		else
		{
			$key = array('element_id' => $data['id']);
		}
		$join->load($key);
		if ($join->element_id == 0)
		{
			$join->element_id = $elementId;
		}
		$join->table_join = $tableJoin;
		$join->join_type = 'left';
		$join->group_id = $data['group_id'];
		$join->table_key = str_replace('`', '', $element->name);
		$join->table_join_key = $keyCol;
		$join->join_from_table = '';
		$o = new stdClass;
		$l = 'join-label';
		$o->$l = $label;
		$o->type = 'element';
		$join->params = json_encode($o);
		$join->store();
	}

	/**
	 * called before the element is saved
	 * @ $$$ rob: moved up to element model level
	 * @param   object	row that is going to be updated
	 */

	/* 	function beforeSave(&$row)
	    {
	    } */

	function onRemove($drop = false)
	{
		$this->deleteJoins((int) $this->_id);
		parent::onRemove($drop);
	}

	/**
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  array  html ids to watch for validation
	 */

	function getValidationWatchElements($repeatCounter)
	{
		$params = $this->getParams();
		$trigger = $params->get('database_join_display_type') == 'dropdown' ? 'change' : 'click';
		$id = $this->getHTMLId($repeatCounter);
		$ar = array('id' => $id, 'triggerEvent' => $trigger);
		return array($ar);
	}

	/**
	 * Used by elements with suboptions
	 *
	 * @param   string  $v              value
	 * @param   string  $defaultLabel   default label
	 * @param   int     $repeatCounter  repeat group counter
	 *
	 * @return  string	label
	 */

	public function getLabelForValue($v, $defaultLabel = null, $repeatCounter = 0)
	{
		$n = $this->getFullName(false, true, false);
		$data = array($n => $v, $n . '_raw' => $v);
		$tmp = $this->_getOptions($data, $repeatCounter, false);
		foreach ($tmp as $obj)
		{
			if ($obj->value == $v)
			{
				$defaultLabel = $obj->text;
				break;
			}
		}
		return is_null($defaultLabel) ? $v : $defaultLabel;
	}

	/**
	 * If no filter condition supplied (either via querystring or in posted filter data
	 * return the most appropriate filter option for the element.
	 *
	 * @return  string	default filter condition ('=', 'REGEXP' etc)
	 */

	public function getDefaultFilterCondition()
	{
		return '=';
	}

	/**
	 * is the dropdowns cnn the same as the main Joomla db
	 * @return  bool
	 */

	protected function inJDb()
	{
		$config = JFactory::getConfig();
		$cnn = $this->getListModel()->getConnection()->getConnection();

		// if the table database is not the same as the joomla database then
		// we should simply return a hidden field with the user id in it.
		return $config->getValue('db') == $cnn->database;
	}

	/**
	 * Ajax call to get auto complete options
	 *
	 * @return  string  json encoded options
	 */

	public function onAutocomplete_options()
	{
		// Needed for ajax update (since we are calling this method via dispatcher element is not set
		$this->_id = JRequest::getInt('element_id');
		$this->getElement(true);
		$params = $this->getParams();
		$db = FabrikWorker::getDbo();
		$c = $this->_getValColumn();
		if (!strstr($c, 'CONCAT'))
		{
			$c = FabrikString::safeColName($c);
		}
		// $$$ hugh - added 'autocomplete_how', currently just "starts_with" or "contains"
		// default to "contains" for backward compat.
		if ($params->get('dbjoin_autocomplete_how', 'contains') == 'contains')
		{
			$this->_autocomplete_where = $c . ' LIKE ' . $db->quote('%' . JRequest::getVar('value') . '%');
		}
		else
		{
			$this->_autocomplete_where = $c . ' LIKE ' . $db->quote(JRequest::getVar('value') . '%');
		}
		$tmp = $this->_getOptions(array(), 0, true);
		echo json_encode($tmp);
	}

	/**
	 * Get the name of the field to order the table data by
	 * can be overwritten in plugin class - but not currently done so
	 *
	 * @return string column to order by tablename___elementname and yes you can use aliases in the order by clause
	 */

	function getOrderByName()
	{
		$params = $this->getParams();
		$join = $this->getJoin();
		$joinTable = $join->table_join_alias;
		$joinVal = $this->_getValColumn();
		$return = !strstr($joinVal, 'CONCAT') ? $joinTable . '.' . $joinVal : $joinVal;
		if ($return == '.')
		{
			$return = parent::getOrderByName();
		}
		return $return;
	}

	public function selfDiagnose()
	{
		$retStr = parent::selfDiagnose();
		if ($this->_pluginName == 'fabrikdatabasejoin')
		{
			//Get the attributes as a parameter object:
			$params = $this->getParams();
			//Process the possible errors returning an error string:
			if (!$params->get('join_db_name'))
			{
				$retStr .= "\nMissing Table";
			}
			if (!$params->get('join_key_column'))
			{
				$retStr .= "\nMissing Key";
			}
			if ((!$params->get('join_val_column')) && (!$params->get('join_val_column_concat')))
			{
				$retStr = "\nMissing Label";
			}
		}
		return $retStr;
	}

	/**
	 * does the element store its data in a join table (1:n)
	 * @return	bool
	 */

	public function isJoin()
	{
		$params = $this->getParams();
		if (in_array($params->get('database_join_display_type'), array('checkbox', 'multilist')))
		{
			return true;
		}
		else
		{
			return parent::isJoin();
		}
	}

	public function buildQueryElementConcat($jkey, $addAs = true)
	{
		$join = $this->getJoinModel()->getJoin();
		$jointable = $join->table_join;
		$params = $this->getParams();
		$dbtable = $this->actualTableName();
		$db = JFactory::getDbo();
		$item = $this->getListModel()->getTable();
		$jkey = $this->_getValColumn();
		$where = $this->_buildQueryWhere(array(), true, $params->get('join_db_name'));
		$where = JString::stristr($where, 'order by') ? $where : '';
		$jkey = !strstr($jkey, 'CONCAT') ? $params->get('join_db_name') . '.' . $jkey : $jkey;

		$fullElName = $this->getFullName(false, true, false);
		$sql = "(SELECT GROUP_CONCAT(" . $jkey . " " . $where . " SEPARATOR '" . GROUPSPLITTER . "') FROM $jointable
		LEFT JOIN " . $params->get('join_db_name') . " ON " . $params->get('join_db_name') . "." . $params->get('join_key_column') . " = $jointable."
			. $this->_element->name . " WHERE " . $jointable . ".parent_id = " . $item->db_primary_key . ")";
		if ($addAs)
		{
			$sql .= ' AS ' . $fullElName;
		}
		return $sql;
	}

	/**
	 * Build the sub query which is used when merging in
	 * repeat element records from their joined table into the one field.
	 * Overwritten in database join element to allow for building
	 * the join to the talbe containing the stored values required ids
	 *
	 * @since   2.1.1
	 *
	 * @return  string	sub query
	 */

	protected function buildQueryElementConcatId()
	{
		$str = parent::buildQueryElementConcatId();
		$jointable = $this->getJoinModel()->getJoin()->table_join;
		$dbtable = $this->actualTableName();
		$db = JFactory::getDbo();
		$table = $this->getListModel()->getTable();
		$fullElName = $this->getFullName(false, true, false) . "_id";
		$str .= ", (SELECT GROUP_CONCAT(" . $this->_element->name . " SEPARATOR '" . GROUPSPLITTER . "') FROM $jointable WHERE " . $jointable
			. ".parent_id = " . $table->db_primary_key . ") AS $fullElName";
		return $str;
	}

	/**
	 * @since 2.1.1
	 * used in form model setJoinData.
	 * @return array of element names to search data in to create join data array
	 * in this case append with the repeatnums data for checkboxes rendered in repeat groups
	 */

	public function getJoinDataNames()
	{
		$a = parent::getJoinDataNames();
		if ($this->isJoin())
		{
			$element = $this->getElement();
			$group = $this->getGroup()->getGroup();
			$join = $this->getJoinModel()->getJoin();
			$repeatName = $join->table_join . '___repeatnum';
			$fvRepeatName = 'join[' . $group->join_id . '][' . $repeatName . ']';
			$a[] = array($repeatName, $fvRepeatName);
		}
		return $a;
	}

	/**
	 * When the element is a repeatble join (e.g. db join checkbox) then figure out how many
	 * records have been selected
	 *
	 * @param   array  $data  data
	 * @param   object  $oJoin  join model
	 *
	 * @since 3.0rc1
	 *
	 * @return  int		number of records selected
	 */

	public function getJoinRepeatCount($data, $oJoin)
	{
		$displayType = $this->getParams()->get('database_join_display_type', 'dropdown');
		if ($displayType === 'multilist')
		{
			$join = $this->getJoinModel()->getJoin();
			$repeatName = $join->table_join . '___' . $this->getElement()->name;
			return count(JArrayHelper::getValue($data, $repeatName, array()));
		}
		else
		{
			return parent::getJoinRepeatCount($data, $oJoin);
		}
	}

	/**
	 *
	 * Should the 'label' field be quoted.  Overridden by databasejoin and extended classes,
	 * which may use a CONCAT'ed label which musn't be quoted.
	 *
	 * @since	3.0.6
	 *
	 * @return boolean
	 */

	protected function quoteLabel()
	{
		$params = $this->getParams();
		return $params->get('join_val_column_concat', '') == '';
	}

}
