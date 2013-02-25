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

class PlgFabrik_ElementDatabasejoin extends PlgFabrik_ElementList
{

	/** @var object connection */
	protected $cn = null;

	protected $joinDb = null;

	/** @var created in getJoin **/
	protected $join = null;

	/** @var string for simple join query*/
	var $_sql = array();

	/** @var array option values **/
	var $_optionVals = array();

	/** @var array linked form data */
	var $_linkedForms = null;

	/** @var additionl where for auto-complete query */
	var $_autocomplete_where = "";

	/** @var string name of the join db to connect to */
	protected $dbname = null;

	/**
	 * J Paramter name for the field containing the cdd label value
	 *
	 * @var string
	 */
	protected $labelParam = 'join_val_column';

	/**
	 * J Parameter name for the field containiing the concat label
	 *
	 * @var string
	 */
	protected $concatLabelParam = 'join_val_column_concat';

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
		if ($params->get('join_conn_id') == $connection->get('id') || $element->plugin != 'databasejoin')
		{
			$join = $this->getJoin();
			if (!$join)
			{
				return false;
			}
			$joinTableName = $join->table_join_alias;

			$tables = $this->getForm()->getLinkedFabrikLists($params->get('join_db_name'));

			/*	store unjoined values as well (used in non-join group table views)
			 * this wasnt working for test case:
			 * events -> (db join) event_artists -> el join (artist)

			 * $$$ rob in csv import keytable not set
			 * $$$ hugh - if keytable isn't set, the safeColName blows up!
			 * Trying to debug issue with linked join elements, which don't get detected by
			 * getJoins or getJoin 'cos element ID doesn't match element_id in fabrik_joins
			 *$k = isset($join->keytable ) ? $join->keytable : $join->join_from_table;
			 *$k = FabrikString::safeColName("`$join->keytable`.`$element->name`");
			 */
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
			$aFields[] = $db->quoteName($table . '.' . $element->name) . ' AS ' . $db->quoteName($fullElName);
			$aAsFields[] = $db->quoteName($fullElName);
		}
	}

	/**
	 * Get the field name to use in the list's slug url
	 *
	 * @param   bool  $raw  Use raw value (true) or label (false)
	 *
	 * @since 3.0.6
	 *
	 * @return  string
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
		$app = JFactory::getApplication();
		if ($this->isJoin())
		{
			$fbConfig = JComponentHelper::getParams('com_fabrik');
			$limit = $fbConfig->get('filter_list_max', 100);
			$rows = array_values($this->checkboxRows(null, null, null, null, 0, $limit));
		}
		else
		{
			// Autocomplete with concat label was not working if we called the parent method
			if ($app->input->get('method') === 'autocomplete_options')
			{
				$listModel = $this->getListModel();
				$db = $listModel->getDb();
				$data = array();
				$opts = array();
				$this->_autocomplete_where = $label . ' LIKE ' . $db->quote('%' . $app->input->get('value', '', 'string') . '%');
				$rows = $this->_getOptionVals($data, 0, true, $opts);
			}
			else
			{
				$rows = parent::filterValueList_Exact($normal, $tableName, $label, $id, $incjoin);
			}
		}
		return $rows;
	}

	/**
	 * Get the field name to use as the column that contains the join's label data
	 *
	 * @param   bool  $useStep  use step in element name
	 *
	 * @return  string	join label column either returns concat statement or quotes `tablename`.`elementname`
	 */

	public function getJoinLabelColumn($useStep = false)
	{
		$app = JFactory::getApplication();
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
		// $$$ hugh - bandaid for inlineedit, problem where $join isn't loaded, as per comments in getJoin().
		// for now, just avoid this code if $join isn't an object.
		if (is_object($join) && ($params->get($this->concatLabelParam) != '') && $app->input->get('overide_join_val_column_concat') != 1)
		{
			$val = str_replace("{thistable}", $join->table_join_alias, $params->get($this->concatLabelParam));
			$w = new FabrikWorker;
			$val = $w->parseMessageForPlaceHolder($val, array(), false);
			return 'CONCAT(' . $val . ')';
		}
		$label = $this->getJoinLabel();

		// Depending on the plugin getJoinLabel() returns a params property or the actaul name, so default to it if we cant find a property
		$label = $params->get($label, $label);
		$joinTableName = is_object($join) ? $join->table_join_alias : '';
		$this->joinLabelCols[(int) $useStep] = $useStep ? $joinTableName . '___' . $label : $db->quoteName($joinTableName . '.' . $label);
		return $this->joinLabelCols[(int) $useStep];
	}

	/**
	 * Get the join label name
	 *
	 * @return  string
	 */

	protected function getJoinLabel()
	{
		$join = $this->getJoin();
		if (!$join)
		{
			return false;
		}
		$label = FabrikString::shortColName($join->params->get('join-label'));
		if ($label == '')
		{
			if (!$this->isJoin())
			{
				JError::raiseWarning(500, 'db join: Could not find the join label for ' . $this->getElement()->name . ' try unlinking and saving it');
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
	 * @param   array   &$aFields    containing field sql
	 * @param   array   &$aAsFields  containing field aliases
	 * @param   string  $table       table name (depreciated)
	 *
	 * @return  void
	 */

	public function getAsField_csv(&$aFields, &$aAsFields, $table = '')
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
		$app = JFactory::getApplication();
		if (isset($this->join))
		{
			return $this->join;
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
				$this->join = $join;
				if (is_string($this->join->params))
				{
					$this->join->params = new JRegistry($this->join->params);
				}
				return $this->join;
			}
		}
		if (!in_array($app->input->get('task'), array('inlineedit', 'form.inlineedit')))
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
	 * Load this elements joins
	 *
	 * @return array
	 */

	public function getJoins()
	{
		$db = FabrikWorker::getDbo(true);
		if (!isset($this->_aJoins))
		{
			$query = $db->getQuery(true);
			$query->select('*')->from('#__{package}_joins')->where('element_id = ' . (int) $this->id)->orderby('id');
			$db->setQuery($query);
			$this->_aJoins = $db->LoadObjectList();
		}
		return $this->_aJoins;
	}

	/**
	 * Get other joins that point to this elemetn
	 *
	 * @param   JTable  &$table  Table
	 *
	 * @deprecated - don't think its used
	 *
	 * @return  array
	 */

	public function getJoinsToThisKey(&$table)
	{
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('*, t.label AS tablelabel')->from('#__{package}_elements AS el')
			->join('LEFT', '#__{package}_formgroup AS fg ON fg.group_id = el.group_id')
			->join('LEFT', '#__{package}_forms AS f ON f.id = fg.form_id')
			->join('LEFT', ' #__{package}_tables AS t ON t.form_id = f.id')
			->where('plugin = ' . $db->quote('databasejoin'))
			->where('join_db_name = ' . $db->quote($table->db_table_name))
			->where('join_conn_id = ' . (int) $table->connection_id);
		$db->setQuery($query);
		return $db->loadObjectList();
	}

	/**
	 * Get array of option values
	 *
	 * @param   array  $data           Data
	 * @param   int    $repeatCounter  Repeat group counter
	 * @param   bool   $incWhere       Do we add custom where statement into sql
	 * @param   array  $opts           Addtional options passed into buildQuery()
	 *
	 * @return  array	option values
	 */

	protected function _getOptionVals($data = array(), $repeatCounter = 0, $incWhere = true, $opts = array())
	{
		$params = $this->getParams();
		$db = $this->getDb();

		// $$$ hugh - attempting to make sure we never do an uncontrained query for auto-complete
		$displayType = $this->getDisplayType();
		$value = (array) $this->getValue($data, $repeatCounter);

		/*
		 *  $$$ rob 20/08/2012 - removed empty test - seems that this method is called more than one time, when in auto-complete filter
		 *  First time sends the label in data second time sends the value (which is the correct one)
		 */
		// if ($displayType === 'auto-complete' && empty($this->_autocomplete_where))
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
		$sql = $this->buildQuery($data, $incWhere, $opts);
		$sqlKey = (string) $sql;
		if (isset($this->_optionVals[$sqlKey]))
		{
			return $this->_optionVals[$sqlKey];
		}

		$db->setQuery($sql);
		FabrikHelperHTML::debug((string) $db->getQuery(), $this->getElement()->name . 'databasejoin element: get options query');
		$this->_optionVals[$sqlKey] = $db->loadObjectList();
		if ($db->getErrorNum() != 0)
		{
			JError::raiseNotice(500, $db->getErrorMsg());
		}
		FabrikHelperHTML::debug($this->_optionVals, 'databasejoin elements');
		if (!is_array($this->_optionVals[$sqlKey]))
		{
			$this->_optionVals[$sqlKey] = array();
		}
		$eval = $params->get('dabase_join_label_eval');
		if (trim($eval) !== '')
		{
			foreach ($this->_optionVals[$sqlKey] as $key => &$opt)
			{
				// $$$ hugh - added allowing removing an option by returning false
				if (eval($eval) === false)
				{
					unset($this->_optionVals[$sqlKey][$key]);
				}
			}
		}

		// Remove tags from labels
		if ($this->canUse())
		{
			foreach ($this->_optionVals[$sqlKey] as $key => &$opt)
			{
				$opt->text = strip_tags($opt->text);
			}
		}
		return $this->_optionVals[$sqlKey];
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
	 * @param   array  $data           From current record (when editing form?)
	 * @param   int    $repeatCounter  Repeat group counter
	 * @param   bool   $incWhere       Do we include custom where in query
	 * @param   array  $opts           Additional optiosn passed intto _getOptionVals()
	 *
	 * @return  array	option objects
	 */

	protected function _getOptions($data = array(), $repeatCounter = 0, $incWhere = true, $opts = array())
	{
		$element = $this->getElement();
		$params = $this->getParams();
		$showBoth = $params->get('show_both_with_radio_dbjoin', '0');
		$this->getDb();
		$col = $element->name;
		$tmp = array();
		$aDdObjs = $this->_getOptionVals($data, $repeatCounter, $incWhere, $opts);
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
		$displayType = $this->getDisplayType();
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
		$app = JFactory::getApplication();
		$params = $this->getParams();
		$wherewhen = $params->get('database_join_where_when', '3');
		$isnew = $app->input->get('rowid', 0) === 0;
		if ($isnew && $wherewhen == '2')
		{
			return false;
		}
		elseif (!$isnew && $wherewhen == '1')
		{
			return false;
		}
		return in_array($gid, JFactory::getUser()->getAuthorisedViewLevels());
	}

	/**
	 * Create the sql query used to get the join data
	 *
	 * @param   array  $data      data
	 * @param   bool   $incWhere  include where
	 * @param   array  $opts      query options
	 *
	 * @return  mixed	JDatabaseQuery or false if query can't be built
	 */

	protected function buildQuery($data = array(), $incWhere = true, $opts = array())
	{
		$sig = isset($this->_autocomplete_where) ? $this->_autocomplete_where . '.' . $incWhere : $incWhere;
		$sig .= '.' . serialize($opts);
		$db = FabrikWorker::getDbo();
		$query = $db->getQuery(true);
		if (isset($this->_sql[$sig]))
		{
			return $this->_sql[$sig];
		}
		$params = $this->getParams();
		$element = $this->getElement();
		$formModel = $this->getForm();
		$query = $this->buildQueryWhere($data, $incWhere, null, $opts, $query);

		// $$$rob not sure these should be used anyway?
		$table = $params->get('join_db_name');
		$key = $this->getJoinValueColumn();
		$val = $this->getValColumn();
		$join = $this->getJoin();
		if ($table == '')
		{
			$table = $join->table_join;
			$key = $join->table_join_key;
			$val = $join->params->get('join-label', $val);

			// Don't quote concat labels
			if ($params->get($this->concatLabelParam) == '')
			{
				$val = $db->quoteName($val);
			}
		}
		if ($key == '' || $val == '')
		{
			return false;
		}
		$query->select('DISTINCT(' . $key . ') AS value, ' . $val . ' AS text');
		$desc = $params->get('join_desc_column', '');
		if ($desc !== '')
		{
			$desc = "REPLACE(" . $db->quoteName($desc) . ", '\n', '<br />')";
			$query->select($desc . ' AS description');
		}
		$additionalFields = $this->getAdditionalQueryFields();
		if ($additionalFields != '')
		{
			$query->select($additionalFields);
		}
		$query->from($db->quoteName($table) . ' AS ' . $db->quoteName($join->table_join_alias));
		$query = $this->buildQueryJoin($query);

		/* $$$ hugh - let them specify an order by, i.e. don't append default if the $where already has an 'order by'
		 * @TODO - we should probably split 'order by' out in to another setting field, because if they are using
		 * the 'apply where beneath' and/or 'apply where when' feature, any custom ordering will not be applied
		 * if the 'where' is not being applied, which probably isn't what they want.
		 */
		$query = $this->getOrderBy('', $query);
		$this->_sql[$sig] = $query;
		return $this->_sql[$sig];
	}

	/**
	 * If buildQuery needs additional fields then set them here, used in notes plugin
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
	 * If buildQuery needs additional joins then set them here, used in notes plugin
	 *
	 * @param   mixed  $query  false to return string, or JQueryBuilder object
	 *
	 * @since 3.0rc1
	 *
	 * @return string|JQueryerBuilder join statement to add
	 */

	protected function buildQueryJoin($query = false)
	{
		return $query === false ? '' : $query;
	}

	/**
	 * Create the where part for the query that selects the list options
	 *
	 * @param   array           $data            Current row data to use in placeholder replacements
	 * @param   bool            $incWhere        Should the additional user defined WHERE statement be included
	 * @param   string          $thisTableAlias  Db table alais
	 * @param   array           $opts            Options
	 * @param   JDatabaseQuery  $query           Append where to JDatabaseQuery object or return string (false)
	 *
	 * @return string|JDatabaseQuery
	 */

	protected function buildQueryWhere($data = array(), $incWhere = true, $thisTableAlias = null, $opts = array(), $query = false)
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

		// $$$rob 11/10/2011  remove order by statements which will be re-inserted at the end of buildQuery()
		if (preg_match('/(ORDER\s+BY)(.*)/i', $where, $matches))
		{
			$this->orderBy = str_replace("{thistable}", $join->table_join_alias, $matches[0]);
			$where = str_replace($this->orderBy, '', $where);
			$where = str_replace($matches[0], '', $where);
		}
		if (!empty($this->_autocomplete_where))
		{

			$mode = JArrayHelper::getValue($opts, 'mode', 'form');
			$filterType = $element->filter_type;
			if (($mode == 'filter' && $filterType == 'auto-complete')
				|| ($mode == 'form' && $params->get('database_join_display_type') == 'auto-complete')
				|| ($mode == 'filter' && $params->get('database_join_display_type') == 'auto-complete'))
			{

				$where .= JString::stristr($where, 'WHERE') ? ' AND ' . $this->_autocomplete_where : ' WHERE ' . $this->_autocomplete_where;
			}
		}
		if ($where == '')
		{
			return $query ? $query : $where;
		}
		$where = str_replace("{thistable}", $thisTableAlias, $where);
		$w = new FabrikWorker;
		$data = is_array($data) ? $data : array();
		$where = $w->parseMessageForPlaceHolder($where, $data, false);
		if (!$query)
		{
			return $where;
		}
		else
		{
			// $where = JString::str_ireplace('WHERE', '', $where);
			$where = FabrikString::ltrimword($where, 'WHERE', true);
			$query->where($where);
			return $query;
		}
	}

	/**
	 * Get the element name or concat statement used to build the dropdown labels or
	 * table data field
	 *
	 * @return  string
	 */

	protected function getValColumn()
	{
		$params = $this->getParams();
		$join = $this->getJoin();
		if ($params->get($this->concatLabelParam) == '')
		{
			return $this->getLabelParamVal();
		}
		else
		{
			$val = str_replace("{thistable}", $join->table_join_alias, $params->get($this->concatLabelParam));
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

	public function getDb()
	{
		$cn = $this->getConnection();
		if (!$this->joinDb)
		{
			$this->joinDb = $cn->getDb();
		}
		return $this->joinDb;
	}

	/**
	 * Get connection
	 *
	 * @return  object	connection
	 */

	public function &getConnection()
	{
		if (is_null($this->cn))
		{
			$this->loadConnection();
		}
		return $this->cn;
	}

	/**
	 * Get the name of the connection parameter
	 *
	 * @return string
	 */

	protected function connectionParam()
	{
		return 'join_conn_id';
	}

	/**
	 * Load connection object
	 *
	 * @return	object	connection table
	 */

	protected function loadConnection()
	{
		$params = $this->getParams();
		$id = $params->get('join_conn_id');
		$cid = $this->getlistModel()->getConnection()->getConnection()->id;
		if ($cid == $id)
		{
			$this->cn = $this->getlistModel()->getConnection();
		}
		else
		{
			$this->cn = JModelLegacy::getInstance('Connection', 'FabrikFEModel');
			$this->cn->setId($id);
		}
		return $this->cn->getConnection();
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
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');

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
		$displayType = $this->getDisplayType();
		$db = $this->getDb();
		if (!$db)
		{
			JError::raiseWarning(JText::sprintf('PLG_ELEMENT_DBJOIN_DB_CONN_ERR', $element->name));
			return '';
		}
		if (isset($formModel->aJoinGroupIds[$groupModel->getId()]))
		{
			$joinId = $formModel->aJoinGroupIds[$groupModel->getId()];
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
		$optsPerRow = intval($params->get('dbjoin_options_per_row', 0));
		$html = array();

		// $$$ hugh - still need to check $this->editable, as content plugin sets it to false,
		// as no point rendering editable view for {fabrik view=element ...} in an article.
		if (!$formModel->isEditable() || !$this->isEditable())
		{
			// $$$ rob 19/03/2012 uncommented line below - needed for checkbox rendering
			$obj = JArrayHelper::toObject($data);
			$defaultLabel = $this->renderListData($default, $obj);
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
					$url = 'index.php?option=com_' . $package . '&view=details&formid=' . $popupformid . '&listid =' . $listid . '&rowid=' . $defaultValue;
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
						$this->renderRadioList($data, $repeatCounter, $html, $tmp, $defaultValue);
						break;
					case 'checkbox':
						$this->renderCheckBoxList($data, $repeatCounter, $html, $tmp, $default);
						$defaultLabel = implode("\n", $html);
						break;
					case 'multilist':
						$this->renderMultiSelectList($data, $repeatCounter, $html, $tmp, $default);
						$defaultLabel = implode("\n", $html);
						break;
					case 'auto-complete':
						$this->renderAutoComplete($data, $repeatCounter, $html, $default);
						break;
				}
				$frontEndSelect = $params->get('fabrikdatabasejoin_frontend_select');
				$frontEndAdd = $params->get('fabrikdatabasejoin_frontend_add');

				// If add and select put them in a button group.
				if ($frontEndSelect && $frontEndAdd && $this->isEditable())
				{
					$html[] = '<div class="btn-group">';
				}
				if ($frontEndSelect && $this->isEditable())
				{
					$forms = $this->getLinkedForms();
					$popupform = (int) $params->get('databasejoin_popupform');
					$popuplistid = (empty($popupform) || !isset($forms[$popupform])) ? '' : $forms[$popupform]->listid;

					JText::script('PLG_ELEMENT_DBJOIN_SELECT');
					if ($app->isAdmin())
					{
						$chooseUrl = 'index.php?option=com_fabrik&task=list.view&listid=' . $popuplistid . '&tmpl=component&ajax=1';
					}
					else
					{
						$chooseUrl = 'index.php?option=com_' . $package . '&view=list&listid=' . $popuplistid . '&tmpl=component&ajax=1';
					}
					$html[] = '<a href="' . ($chooseUrl) . '" class="toggle-selectoption btn" title="' . JText::_('COM_FABRIK_SELECT') . '">'
						. FabrikHelperHTML::image('search.png', 'form', @$this->tmpl, array('alt' => JText::_('COM_FABRIK_SELECT'))) . '</a>';
				}

				if ($frontEndAdd && $this->isEditable())
				{
					JText::script('PLG_ELEMENT_DBJOIN_ADD');
					$popupform = (int) $params->get('databasejoin_popupform');
					$addURL = 'index.php?option=com_fabrik';
					$addURL .= $app->isAdmin() ? '&task=form.view' : '&view=form';
					$addURL .= '&tmpl=component&ajax=1&formid=' . $popupform;
					$html[] = '<a href="' . $addURL . '" title="' . JText::_('COM_FABRIK_ADD') . '" class="toggle-addoption btn">';
					$html[] = FabrikHelperHTML::image('new.png', 'form', @$this->tmpl, array('alt' => JText::_('COM_FABRIK_SELECT'))) . '</a>';
				}
				// If add and select put them in a button group.
				if ($frontEndSelect && $frontEndAdd && $this->isEditable())
				{
					$html[] = '</div>';
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
	 * Render autocomplete in form
	 *
	 * @param   array   $data           Form data
	 * @param   int     $repeatCounter  Repeat group counter
	 * @param   array   &$html          HTML to assign output to
	 * @param   array   $tmp            List of value/label objects
	 * @param   string  $defaultValue   Default value
	 *
	 * @since   3.0.7
	 *
	 * @return  void
	 */

	protected function renderRadioList($data, $repeatCounter, &$html, $tmp, $defaultValue)
	{
		$id = $this->getHTMLId($repeatCounter);
		$thisElName = $this->getHTMLName($repeatCounter);
		$params = $this->getParams();
		$attribs = 'class="fabrikinput inputbox" size="1" id="' . $id . '"';
		$optsPerRow = intval($params->get('dbjoin_options_per_row', 0));

		// $$$ rob 24/05/2011 - always set one value as selected for radio button if none already set
		if ($defaultValue == '' && !empty($tmp))
		{
			$defaultValue = $tmp[0]->value;
		}
		$html[] = '<div class="fabrikSubElementContainer" id="' . $id . '">';
		$html[] = FabrikHelperHTML::aList('radio', $tmp, $thisElName, $attribs, $defaultValue, 'value', 'text', $optsPerRow);
	}

	/**
	 * Render autocomplete in form
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  Repeat group counter
	 * @param   array  &$html          HTML to assign output to
	 * @param   array  $default        Default values
	 *
	 * @since   3.0.7
	 *
	 * @return  void
	 */

	protected function renderAutoComplete($data, $repeatCounter, &$html, $default)
	{
		$formModel = $this->getFormModel();
		$thisElName = $this->getHTMLName($repeatCounter);
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);

		// Get the LABEL from the form's data.
		$label = (array) $this->getValue($data, $repeatCounter, array('valueFormat' => 'label'));

		/*
		 * $$$ rob 18/06/2012 if form submitted with errors - reshowing the auto-complete wont have access to the submitted values label
		 * 02/11/2012 if new form then labels not present either.
		 */
		if ($formModel->hasErrors() || $formModel->getRowId() == 0)
		{
			$label = (array) $this->getLabelForValue($label[0], $label[0], $repeatCounter);
		}
		$autoCompleteName = str_replace('[]', '', $thisElName) . '-auto-complete';
		$html[] = '<input type="text" size="' . $params->get('dbjoin_autocomplete_size', '20') . '" name="' . $autoCompleteName . '" id="' . $id
			. '-auto-complete" value="' . JArrayHelper::getValue($label, 0) . '" class="fabrikinput inputbox autocomplete-trigger"/>';

		// $$$ rob - class property required when cloning repeat groups - don't remove
		$html[] = '<input type="hidden" class="fabrikinput" size="20" name="' . $thisElName . '" id="' . $id . '" value="'
			. JArrayHelper::getValue($default, 0, '') . '"/>';
	}

	/**
	 * Render multi-select list in form
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  Repeat group counter
	 * @param   array  &$html          HTML to assign output to
	 * @param   array  $tmp            List of value/label objects
	 * @param   array  $default        Default values
	 *
	 * @since   3.0.7
	 *
	 * @return  void
	 */

	protected function renderMultiSelectList($data, $repeatCounter, &$html, $tmp, $default)
	{
		$formModel = $this->getFormModel();
		$elName = $this->getHTMLName($repeatCounter);
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$idname = $this->getFullName(false, true, false) . '_id';
		$optsPerRow = intval($params->get('dbjoin_options_per_row', 0));
		$defaults = $formModel->failedValidation() ? $default : explode(GROUPSPLITTER, JArrayHelper::getValue($data, $idname));
		if ($this->isEditable())
		{
			$multiSize = (int) $params->get('dbjoin_multilist_size', 6);
			$attribs = 'class="fabrikinput inputbox" size="' . $multiSize . '" multiple="true"';
			$html[] = JHTML::_('select.genericlist', $tmp, $elName, $attribs, 'value', 'text', $defaults, $id);
		}
		else
		{
			$attribs = 'class="fabrikinput inputbox" size="1" id="' . $id . '"';
			$html[] = FabrikHelperHTML::aList('multilist', $tmp, $elName, $attribs, $defaults, 'value', 'text', $optsPerRow, $this->isEditable());
		}
	}

	/**
	 * Render checkbox list in form
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  Repeat group counter
	 * @param   array  &$html          HTML to assign output to
	 * @param   array  $tmp            List of value/label objects
	 * @param   array  $default        Default values
	 *
	 * @since   3.0.7
	 *
	 * @return  void
	 */

	protected function renderCheckBoxList($data, $repeatCounter, &$html, $tmp, $default)
	{
		$formModel = $this->getFormModel();
		$groupModel = $this->getGroupModel();
		$id = $this->getHTMLId($repeatCounter);
		$idname = $this->getFullName(false, true, false) . '_id';
		$thisElName = $this->getHTMLName($repeatCounter);
		$params = $this->getParams();
		$optsPerRow = intval($params->get('dbjoin_options_per_row', 0));
		$defaults = $formModel->failedValidation() ? $default : explode(GROUPSPLITTER, JArrayHelper::getValue($data, $idname));
		$html[] = '<div class="fabrikSubElementContainer" id="' . $id . '">';
		$rawname = $this->getFullName(false, true, false) . '_raw';
		$editable = $this->isEditable();
		$attribs = 'class="fabrikinput inputbox" id="' . $id . '"';

		$thisElName = FabrikString::rtrimword($thisElName, '[]');

		$html[] = FabrikHelperHTML::aList('checkbox', $tmp, $thisElName, $attribs, $defaults, 'value', 'text', $optsPerRow, $editable);
		if ($this->isJoin() && $editable)
		{
			$join = $this->getJoin();
			$joinidsName = 'join[' . $join->id . '][' . $join->table_join . '___id]';
			if ($groupModel->canRepeat())
			{
				// $joinidsName .= '[' . $repeatCounter . '][]';
				$joinidsName .= '[' . $repeatCounter . ']';
				$joinids = FArrayHelper::getNestedValue($data, 'join.' . $join->id . '.' . $rawname . '.' . $repeatCounter, 'not found');
			}
			else
			{
				// $joinidsName .= '[]';
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
			$html[] = FabrikHelperHTML::aList('checkbox', $tmpids, $joinidsName, $attribs, $joinids, 'value', 'text', $optsPerRow, $editable);



			$html[] = '</div>';

			if (empty($tmp))
			{
				$tmpids= array();
				$o = new stdClass;
				$o->text = 'dummy';
				$o->value = 'dummy';
				$tmpids[] = $o;
				$tmp = $tmpids;
				$dummy = FabrikHelperHTML::aList('checkbox', $tmp, $thisElName, $attribs, $defaults, 'value', 'text', 1, true);
				$dummyId = FabrikHelperHTML::aList('checkbox', $tmpids, $joinidsName, $attribs, $joinids, 'value', 'text', 1, true);
				$html[] = '<div class="chxTmplNode">' . $dummy . '</div><div class="chxTmplIDNode">' . $dummyId . '</div>';
			}
		}
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
		if ((int) $this->id !== 0)
		{
			$join->load(array('element_id' => $this->id));
			if ($join->table_join == '')
			{
				/* $$$ hugh - this almost certainly means we are changing element type to a join,
				 * and the join row hasn't been created yet.  So let's grab the params, instead of
				 * defaulting to VARCHAR
				 * return "VARCHAR(255)";
				 */
				$dbName = $params->get('join_db_name', $this->getDbName());
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
			$dbName = $params->get('join_db_name', $this->getDbName());
			$joinKey = $params->get('join_key_column');
		}

		$db->setQuery('DESCRIBE ' . $db->quoteName($dbName));
		$fields = $db->loadObjectList();
		if (!$fields)
		{
			$db->getErrorMsg();
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

	public function getEmailValue($value, $data = array(), $repeatCounter = 0)
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
			// Wierd one http://fabrikar.com/forums/showpost.php?p=153789&postcount=16, so lets try to ensure we have a value before using getLabelForValue()
			$col = $this->getFullName(false, true, false) . '_raw';
			$row = JArrayHelper::fromObject($thisRow);
			$data = JArrayHelper::getValue($row, $col, $data);

			// Rendered as checkbox/mutliselect
			if (is_string($data) && strstr($data, GROUPSPLITTER))
			{
				$labeldata = explode(GROUPSPLITTER, $data);
			}
			else
			{
				// $$$ hugh - $data may already be JSON encoded, so we don't want to double-encode.
				if (!FabrikWorker::isJSON($data))
				{
					$labeldata = (array) $data;
				}
				else
				{
					// $$$ hugh - yeah, I know, kinda silly to decode right before we encode,
					// should really refactor so encoding goes in this if/else structure!
					$labeldata = (array) json_decode($data);
				}
			}
			foreach ($labeldata as &$l)
			{
				$l = $this->getLabelForValue($l, $l);
			}
		}

		$data = json_encode($labeldata);

		// $$$ rob add links and icons done in parent::renderListData();
		return parent::renderListData($data, $thisRow);
	}

	/**
	 * Get the default value for the list filter
	 *
	 * @param   bool  $normal   is the filter a normal or advanced filter
	 * @param   int   $counter  filter order
	 *
	 * @return  string
	 */

	protected function getDefaultFilterVal($normal = true, $counter = 0)
	{
		$default = parent::getDefaultFilterVal($normal, $counter);
		$element = $this->getElement();

		// Related data will pass a raw value in the query string but if the element filter is a field we need to change that to its label
		if ($element->filter_type == 'field')
		{
			$default = $this->getLabelForValue($default, $default, $counter);
		}
		return $default;
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
		$class = $this->filterClass();
		$default = $this->getDefaultFilterVal($normal, $counter);
		if (in_array($element->filter_type, array('range', 'dropdown', '')))
		{
			$joinVal = $this->getJoinLabelColumn();
			$incJoin = (trim($params->get($this->concatLabelParam)) == '' && trim($params->get('database_join_where_sql') == '')) ? false : true;
			$rows = $this->filterValueList($normal, null, $joinVal, '', $incJoin);
			foreach ($rows as &$r)
			{
				$r->text = strip_tags($r->text);
			}
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
				$return[] = JHTML::_('select.genericlist', $rows, $v, 'class="' . $class . '" size="1" ', "value", 'text', $default, $htmlid);
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
				$return[] = JHTML::_('select.genericlist', $rows, $v, 'class="' . $class . '" size="1" ', "value", 'text', $default, $htmlid);
				break;

			case "field":
				$return[] = '<input type="text" class="' . $class . '" name="' . $v . '" value="' . $default . '" size="' . $size . '" id="'
					. $htmlid . '" />';
				$return[] = $this->filterHiddenFields();
				break;

			case "hidden":
				$return[] = '<input type="hidden" class="' . $class . '" name="' . $v . '" value="' . $default . '" size="' . $size
					. '" id="' . $htmlid . '" />';
				$return[] = $this->filterHiddenFields();
				break;
			case "auto-complete":
				$defaultLabel = $this->getLabelForValue($default);
				$autoComplete = $this->autoCompleteFilter($default, $v, $defaultLabel, $normal);
				$return = array_merge($return, $autoComplete);
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

	/**
	 * Get the hidden fields used to store additional filter information
	 *
	 * @return string  HTML fields
	 */

	protected function filterHiddenFields()
	{
		$params = $this->getParams();
		$elName = FabrikString::safeColNameToArrayKey($this->getFilterFullName());
		$return = array();
		$return[] = '<input type="hidden" name="' . $elName . '[join_db_name]" value="' . $params->get('join_db_name') . '" />';
		$return[] = '<input type="hidden" name="' . $elName . '[join_key_column]" value="' . $params->get('join_key_column') . '" />';
		$return[] = '<input type="hidden" name="' . $elName . '[join_val_column]" value="' . $this->getLabelParamVal() . '" />';
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

	protected function buildFilterJoin()
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
		$where = $this->buildQueryWhere(array(), true, null, array('mode' => 'filter'));

		// Ensure table prefilter is applied to query
		$prefilterWhere = $listModel->buildQueryPrefilterWhere($this);
		$elementName = FabrikString::safeColName($this->getFullName(false, false, false));
		$prefilterWhere = str_replace($elementName, $joinKey, $prefilterWhere);
		if (trim($where) == '')
		{
			/* $$$ hugh - Sanity check - won't this screw things up if we have a complex prefilter with multiple filters using AND grouping? */
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
	 * @param   string         $view   Ciew mode '' or 'filter'
	 * @param   JDatabasQuery  $query  Set to false to return a string
	 *
	 * @return  string  order by statement
	 */

	protected function getOrderBy($view = '', $query = false)
	{
		if ($view == 'filter')
		{
			$params = $this->getParams();
			$joinKey = $this->getJoinValueColumn();
			$joinLabel = $this->getJoinLabelColumn();
			$order = '';
			switch ($params->get('filter_groupby', 'text'))
			{
				case 'text':
					$oder = $joinLabel . 'ASC ';
					break;
				case 'value':
					$order = $joinKey . 'ASC ';
					break;
				case '-1':
				default:
					// Check if the 'Joins where and/or order by statement' has an order by
					$joinWhere = $params->get('database_join_where_sql');
					if (JString::stristr($joinWhere, 'ORDER BY'))
					{
						$joinWhere = str_replace('order by', 'ORDER BY', $joinWhere);
						$joinWhere = explode('ORDER BY', $joinWhere);
						if (count($joinWhere) > 1)
						{
							$order = $joinWhere[count($joinWhere) - 1];
						}
					}
					break;
			}
			if (!$query)
			{
				return $order === '' ? '' : ' ORDER BY ' . $order;
			}
			else
			{
				if ($order !== '')
				{
					$query->order($order);
				}
				return $query;
			}
		}
		else
		{
			if (isset($this->orderBy))
			{
				if (!$query)
				{
					return $this->orderBy;
				}
				else
				{
					$order = JString::str_ireplace('ORDER BY', '', $this->orderBy);
					$query->order($order);
					return $query;
				}
			}
			else
			{
				if (!$query)
				{
					return "ORDER BY text ASC ";
				}
				else
				{
					$query->order('text ASC');
					return $query;
				}
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
		return $db->quoteName($join->table_join_alias . '.' . $this->getJoinValueFieldName());
	}

	/**
	 * Get the field name for the joined tables' pk
	 *
	 *  @since  3.0.7
	 *
	 * @return  string
	 */

	protected function getJoinValueFieldName()
	{
		$params = $this->getParams();
		return $params->get('join_key_column');
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

		if ($this->isJoin())
		{
			$key = $this->buildQueryElementConcat($key, false);
		}
		else
		{
			if ($type == 'querystring')
			{
				/* $$$ rob no matter whether you use elementname_raw or elementname in the querystring filter
				 * by the time it gets here we have normalized to elementname. So we check if the original qs filter was looking at the raw
				 * value if it was then we want to filter on the key and not the label
				 */
				if (!$this->_rawFilter)
				{
					$k = $db->quoteName($params->get('join_db_name')) . '.' . $db->quoteName($this->getLabelParamVal());
				}
				else
				{
					$k = $key;
				}
				$this->encryptFieldName($k);
				return "$k $condition $value";
			}
		}
		$this->encryptFieldName($key);
		if (!$this->_rawFilter && ($type == 'searchall' || $type == 'prefilter'))
		{
			if ($type !== 'prefilter')
			{
				$join = $this->getJoin();
				$key = FabrikString::safeColName($join->table_join_alias) . '.' . $db->quoteName($this->getLabelParamVal());
			}
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
				$dbName = $this->getDbName();
				$fType = $this->getElement()->filter_type;
				if ($this->isJoin())
				{
					if ($fType == 'field')
					{
						$where = $db->quoteName($dbName . '.' . $this->getLabelParamVal());
					}
					else
					{
						$where = $db->quoteName($dbName . '.' . $this->getJoinValueFieldName());
					}
					$groupBy = $db->quoteName($dbName . '.parent_id');
					$rows = $this->checkboxRows($groupBy, $condition, $value, $where);
					$joinIds = array_keys($rows);
					if (!empty($rows))
					{
						$str = $this->getListModel()->getTable()->db_primary_key . " IN (" . implode(', ', $joinIds) . ")";
					}
				}
				else
				{
					if ($fType === 'auto-complete')
					{
						// If autocomplete then we should search on the element's column, not the joined label column http://fabrikar.com/forums/showthread.php?t=29977
						$key = $db->quoteName($this->getFullName(false, false, false));
					}
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
	 * @param   int     $offset     query offset - default 0
	 * @param   int     $limit      query limit - default 0
	 *
	 * @return  array	rows
	 */

	protected function checkboxRows($groupBy = null, $condition = null, $value = null, $where = null, $offset = 0, $limit = 0)
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
		$to = $this->getDbName();
		$key = $db->quoteName($to . '.' . $this->getJoinValueFieldName());
		$label = $db->quoteName($to . '.' . $this->getLabelParamVal());
		$v = $jointable . '.' . $shortName;
		$query->select($jointable . '.id AS id');

		// If rendering as mulit/checkbox then {thistable} should not refer to the joining repeat table, but the end table.
		if ($this->isJoin())
		{
			$jkey = $this->getValColumn();
			$jkey = !strstr($jkey, 'CONCAT') ? $label : $jkey;
			$label = str_replace($join->table_join, $to, $jkey);
		}

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
		$db->setQuery($query, $offset, $limit);
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
		if ($params->get($this->concatLabelParam, '') !== '' && in_array($element->filter_type, $fields))
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
				$elName = $join_db_name . '___' . $this->getLabelParamVal();
			}
			else
			{
				$elName = parent::getFilterFullName();
			}
		}
		return FabrikString::safeColName($elName);
	}

	/**
	 * Get the label parameter's value
	 *
	 * @return string
	 */

	protected function getLabelParamVal()
	{
		if (isset($this->labelParamVal))
		{
			return $this->labelParamVal;
		}
		$params = $this->getParams();
		$label = $params->get($this->labelParam);
		if (JString::strpos($label, '___'))
		{
			// CDD is stored as full element name - db join as simple element name
			$bits = explode('___', $label);
			$label = $bits[1];
		}
		$this->labelParamVal = $label;
		return $this->labelParamVal;
	}

	/**
	 * Not used
	 *
	 * @param   string  $rawval  raw value
	 *
	 * @deprecated - not used
	 *
	 * @return string
	 */

	public function getFilterLabel($rawval)
	{
		$db = $this->getDb();
		$params = $this->getParams();
		$orig = $params->get('database_join_where_sql');
		$k = $params->get('join_key_column');
		$l = $this->getLabelParamVal();
		$t = $params->get('join_db_name');
		if ($k != '' && $l != '' & $t != '' && $rawval != '')
		{
			$query = $db->getQuery(true);
			$query->select($l)->from($t)->where($k . ' = ' . $rawval);
			$db->setQuery($query);
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
		$c[] = 'mode-' . $this->getDisplayType();
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
		$data = $this->getFormModel()->data;
		$arSelected = $this->getValue($data, $repeatCounter);
		$arVals = $this->getSubOptionValues();
		$arTxt = $this->getSubOptionLabels();

		$table = $params->get('join_db_name');
		$opts = $this->getElementJSOptions($repeatCounter);
		$forms = $this->getLinkedForms();
		$popupform = (int) $params->get('databasejoin_popupform');
		$popuplistid = (empty($popupform) || !isset($forms[$popupform])) ? '' : $forms[$popupform]->listid;
		$opts->id = $this->id;
		$opts->fullName = $this->getFullName(false, true, false);
		$opts->key = $table . '___' . $params->get('join_key_column');
		$opts->label = $table . '___' . $this->getLabelParamVal();
		$opts->formid = $this->getForm()->getForm()->id;
		$opts->listid = $popuplistid;
		$opts->listRef = '_com_fabrik_' . $opts->listid;
		$opts->value = $arSelected;
		$opts->defaultVal = $this->getDefaultValue($data);
		$opts->popupform = $popupform;
		$opts->popwiny = $params->get('yoffset', 0);
		$opts->windowwidth = $params->get('join_popupwidth', 360);
		$opts->displayType = $this->getDisplayType();
		$opts->show_please_select = $params->get('database_join_show_please_select') === "1";
		$opts->showDesc = $params->get('join_desc_column', '') === '' ? false : true;
		$opts->autoCompleteOpts = $opts->displayType == 'auto-complete'
			? FabrikHelperHTML::autoCompletOptions($opts->id, $this->getElement()->id, 'databasejoin') : null;
		$opts->allowadd = $params->get('fabrikdatabasejoin_frontend_add', 0) == 0 ? false : true;
		$opts->listName = $this->getListModel()->getTable()->db_table_name;
		$this->elementJavascriptJoinOpts($opts);
		$opts->isJoin = $this->isJoin();
		return json_encode($opts);
	}

	/**
	 * Get some common element JS options
	 *
	 * @param   object  &$opts  Options
	 *
	 * @return  void
	 */

	protected function elementJavascriptJoinOpts(&$opts)
	{
		if ($this->isJoin())
		{
			$element = $this->getElement();
			$join = $this->getJoin();
			$opts->joinTable = $join->table_join;

			// $$$ rob - wrong for inline edit plugin
			// $opts->elementName = $join->table_join;
			$opts->elementName = $join->table_join . '___' . $element->name;
			$opts->elementShortName = $element->name;
			$opts->joinId = $join->id;
			$opts->isJoin = true;
		}
	}

	/**
	 * Gets the options for the drop down - used in package when forms update
	 *
	 * @return  void
	 */

	public function onAjax_getOptions()
	{
		// Needed for ajax update (since we are calling this method via dispatcher element is not set
		$app = JFactory::getApplication();
		$this->id = $app->input->getInt('element_id');
		$this->loadMeForAjax();
		$this->getElement(true);
		$filter = JFilterInput::getInstance();
		$request = $filter->clean($_REQUEST, 'array');
		echo json_encode($this->_getOptions($request));
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
		if (!$this->canEncrypt() && !empty($params->encrypt))
		{
			JError::raiseNotice(500, 'The encryption option is only available for field and text area plugins');
			return false;
		}
		if (!$this->isJoin())
		{
			// $this->updateFabrikJoins($data, $this->getDbName(), $params->join_key_column, $params->join_val_column);
			$this->updateFabrikJoins($data, $this->getDbName(), $this->getJoinValueFieldName(), $this->getLabelParamVal());

		}
		return parent::onSave();
	}

	/**
	 * Get the join to database name
	 *
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
	 * On save of element, update its jos_fabrik_joins record and any decendants join record
	 *
	 * @param   array   $data       data
	 * @param   string  $tableJoin  join table
	 * @param   string  $keyCol     key column
	 * @param   string  $label      label
	 *
	 * @since 3.0b
	 *
	 * @return void
	 */

	protected function updateFabrikJoins($data, $tableJoin, $keyCol, $label)
	{
		// Load join based on this element id
		$this->updateFabrikJoin($data, $this->id, $tableJoin, $keyCol, $label);
		$children = $this->getElementDescendents($this->id);
		foreach ($children as $id)
		{
			$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($id);
			$data['group_id'] = $elementModel->getElement()->group_id;
			$data['id'] = $id;
			$this->updateFabrikJoin($data, $id, $tableJoin, $keyCol, $label);
		}
	}

	/**
	 * Update an elements jos_fabrik_joins record
	 *
	 * @param   array   $data       data
	 * @param   int     $elementId  element id
	 * @param   string  $tableJoin  join table
	 * @param   string  $keyCol     key
	 * @param   string  $label      label
	 *
	 * @since 3.0b
	 *
	 * @return void
	 */

	protected function updateFabrikJoin($data, $elementId, $tableJoin, $keyCol, $label)
	{
		$params = json_decode($data['params']);
		$element = $this->getElement();
		$join = FabTable::getInstance('Join', 'FabrikTable');

		/* $$$ rob 08/05/2012 - toggling from dropdown to multiselect set the list_id to 1, so if you
		 * reset to dropdown then this key would not load the existing join so a secondary join record
		 * would be created for the element.
		 * $key = array('element_id' => $data['id'], 'list_id' => 0);
		 * $$$ hugh - NOOOOOOOO!  Creating a new user element, $data['id'] is 0, so without the list_id => we end up loading the first
		 * list join at random, instead of a new row, which has SERIOUSLY BAD side effects, and is responsible for the Mysterious Disappearing
		 * Group issue ... 'cos the list_id gets set wrong.
		 * I *think* the actual problem is that we weren't setting $data['id'] to newly created element id in the element model save() method, before
		 * calling onSave(), which I've now done, but just to be on the safe side, put in some defensive code so id $data['id'] is 0, we make sure
		 * we don't load a random list join row!!
		 */
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
	 * Called from admin element controller when element is removed
	 *
	 * @param   bool  $drop  has the user elected to drop column?
	 *
	 * @return  bool  save ok or not
	 */

	public function onRemove($drop = false)
	{
		$this->deleteJoins((int) $this->id);
		parent::onRemove($drop);
	}

	/**
	 * Get an array of element html ids and their corresponding
	 * js events which trigger a validation.
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  array  html ids to watch for validation
	 */

	public function getValidationWatchElements($repeatCounter)
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
	 * @param   int     $repeatCounter  repeat group counter (3.0.7 deprecated)
	 *
	 * @return  string	label
	 */

	public function getLabelForValue($v, $defaultLabel = null, $repeatCounter = 0)
	{

		if ($this->isJoin())
		{
			$rows = $this->checkboxRows('id');
			if (is_array($rows) && array_key_exists($v, $rows))
			{
				return $rows[$v]->text;
			}
		}

		$db = $this->getDb();
		$query = $db->getQuery(true);
		$query = $this->buildQuery(array(), false);
		$key = $this->getJoinValueColumn();
		$query->clear('where');
		$query->where($key . ' = ' . $db->quote($v));
		$db->setQuery($query);
		$r = $db->loadObject();
		if (!$r)
		{
			return $defaultLabel;
		}
		$r = isset($r->text) ? $r->text : $defaultLabel;
		return $r;
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
	 * Is the dropdowns cnn the same as the main Joomla db
	 *
	 * @return  bool
	 */

	protected function inJDb()
	{
		$config = JFactory::getConfig();
		$cnn = $this->getListModel()->getConnection()->getConnection();

		/*
		 * If the table database is not the same as the joomla database then
		 * we should simply return a hidden field with the user id in it.
		 */
		return $config->get('db') == $cnn->database;
	}

	/**
	 * Cache method to populate autocomplete options
	 *
	 * @param   plgFabrik_Element  $elementModel  element model
	 * @param   string             $search        search string
	 * @param   array              $opts          options, 'label' => field to use for label (db join)
	 *
	 * @since   3.0.7
	 *
	 * @return string  json encoded search results
	 */

	public static function cacheAutoCompleteOptions($elementModel, $search, $opts = array())
	{
		$params = $elementModel->getParams();
		$db = FabrikWorker::getDbo();
		$c = $elementModel->getValColumn();
		if (!strstr($c, 'CONCAT'))
		{
			$c = FabrikString::safeColName($c);
		}
		$filterMethod = $elementModel->getFilterBuildMethod();
		if ($filterMethod == 1)
		{
			$join = $elementModel->getJoin()->table_join;
			$opts = array();
			if (!strstr($c, 'CONCAT'))
			{
				$opts['label'] = strstr($c, '.') ? $c : $join . '.' . $c;
			}
			else
			{
				$opts['label'] = $c;
			}
			return parent::cacheAutoCompleteOptions($elementModel, $search, $opts);
		}
		// $$$ hugh - added 'autocomplete_how', currently just "starts_with" or "contains"
		// default to "contains" for backward compat.
		if ($params->get('dbjoin_autocomplete_how', 'contains') == 'contains')
		{
			$elementModel->_autocomplete_where = $c . ' LIKE ' . $db->quote('%' . $search . '%');
		}
		else
		{
			$elementModel->_autocomplete_where = $c . ' LIKE ' . $db->quote($search . '%');
		}
		$opts = array('mode' => 'filter');
		$tmp = $elementModel->_getOptions(array(), 0, true, $opts);
		return json_encode($tmp);
	}

	/**
	 * Get the name of the field to order the table data by
	 * can be overwritten in plugin class - but not currently done so
	 *
	 * @return string column to order by tablename___elementname and yes you can use aliases in the order by clause
	 */

	public function getOrderByName()
	{
		$params = $this->getParams();
		$join = $this->getJoin();
		$joinTable = $join->table_join_alias;
		$joinVal = $this->getValColumn();
		if (!strstr($joinVal, 'CONCAT'))
		{
			$return = strstr($joinVal, '___') ? FabrikString::safeColName($joinVal) : $joinTable . '.' . $joinVal;
		}
		else
		{
			$return = $joinVal;
		}
		if ($return == '.')
		{
			$return = parent::getOrderByName();
		}
		return $return;
	}

	/**
	 * PN 19-Jun-11: Construct an element error string.
	 *
	 * @return  string
	 */

	public function selfDiagnose()
	{
		$retStr = parent::selfDiagnose();
		if ($this->pluginName == 'databasejoin')
		{
			$params = $this->getParams();

			// Process the possible errors returning an error string:
			if (!$params->get('join_db_name'))
			{
				$retStr .= "\nMissing Table";
			}
			if (!$params->get('join_key_column'))
			{
				$retStr .= "\nMissing Key";
			}
			if ((!$params->get($this->labelParam)) && (!$params->get($this->concatLabelParam)))
			{
				$retStr = "\nMissing Label";
			}
		}
		return $retStr;
	}

	/**
	 * Does the element store its data in a join table (1:n)
	 *
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

	/**
	 * Build the sub query which is used when merging in in repeat element
	 * records from their joined table into the one field.
	 * Overwritten in database join element to allow for building the join
	 * to the table containing the stored values required labels
	 *
	 * @param   string  $jkey   key
	 * @param   bool    $addAs  add 'AS' to select sub query
	 *
	 * @return  string  sub query
	 */

	public function buildQueryElementConcat($jkey, $addAs = true)
	{
		$join = $this->getJoinModel()->getJoin();
		$jointable = $join->table_join;
		$params = $this->getParams();
		$dbtable = $this->actualTableName();
		$db = JFactory::getDbo();
		$item = $this->getListModel()->getTable();
		$jkey = $this->getValColumn();
		$where = $this->buildQueryWhere(array(), true, $params->get('join_db_name'));
		$where = JString::stristr($where, 'order by') ? $where : '';

		$dbName = $this->getDbName();
		$jkey = !strstr($jkey, 'CONCAT') ? $dbName . '.' . $jkey : $jkey;

		// If rendering as mulit/checkbox then {thistable} should not refer to the joining repeat table, but the end table.
		if ($this->isJoin())
		{
			$jkey = str_replace($jointable, $dbName, $jkey);
		}
		$fullElName = $this->getFullName(false, true, false);
		$sql = "(SELECT GROUP_CONCAT(" . $jkey . " " . $where . " SEPARATOR '" . GROUPSPLITTER . "') FROM $jointable
		LEFT JOIN " . $dbName . " ON " . $dbName . "." . $this->getJoinValueFieldName() . " = $jointable." . $this->getElement()->name . " WHERE "
			. $jointable . ".parent_id = " . $item->db_primary_key . ")";
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
		$str .= ", (SELECT GROUP_CONCAT(" . $this->element->name . " SEPARATOR '" . GROUPSPLITTER . "') FROM $jointable WHERE " . $jointable
			. ".parent_id = " . $table->db_primary_key . ") AS $fullElName";
		return $str;
	}

	/**
	 * Used in form model setJoinData.
	 *
	 * @since 2.1.1
	 *
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
	 * @param   array   $data   data
	 * @param   object  $oJoin  join model
	 *
	 * @since 3.0rc1
	 *
	 * @return  int		number of records selected
	 */

	public function getJoinRepeatCount($data, $oJoin)
	{
		$displayType = $this->getDisplayType();

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
	 * Get the display type (list,checkbox,mulitselect etc)
	 *
	 * @since  3.0.7
	 *
	 * @return  string
	 */

	protected function getDisplayType()
	{
		return $this->getParams()->get('database_join_display_type', 'dropdown');
	}

	/**
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
		return $params->get($this->concatLabelParam, '') == '';
	}

	/**
	 * Is it possible to include the element in the  Search all query?
	 * true if basic search
	 * true/false if advanced search
	 *
	 * @since  3.1b
	 *
	 * @param  bool  $advancedMode  Is the list using advanced search
	 *
	 * @return boolean
	 */

	public function canIncludeInSearchAll($advancedMode)
	{
		if ($advancedMode)
		{
			$join = $this->getJoinModel();
			$fields = $join->getJoin()->getFields();
			$field = JArrayHelper::fromObject(JArrayHelper::getValue($fields, $this->getLabelParamVal(), array()));
			$type = JArrayHelper::getValue($field, 'Type', '');
			$notAllowed = array('int', 'double', 'decimal', 'date', 'serial', 'bit', 'boolean', 'real');
			foreach ($notAllowed as $test)
			{
				if (stristr($type, $test))
				{
					return false;
				}
			}
		}
		return parent::canIncludeInSearchAll($advancedMode);
	}

	/**
	 * Use in list model storeRow() to determine if data should be stored.
	 * Currently only supported for db join elements whose values are default values
	 * avoids casing '' into 0 for int fields
	 *
	 * @param   array  $data  Data being inserted
	 * @param   mixed  $val   Element value to insert into table
	 *
	 * @since   3.0.7
	 *
	 * @return boolean
	 */

	public function dataIsNull($data, $val)
	{
		$default = (array) $this->getDefaultValue();
		$keys = array_keys($default);
		if (is_array($default) && count($default) == 1 && $default[$keys[0]] == $val && $val == '')
		{
			return true;
		}
		return false;
	}

}
