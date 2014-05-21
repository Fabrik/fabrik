<?php
/**
 * Database Join Element
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.databasejoin
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 *  Plugin element to render list of data looked up from a database table
 *  Can render as checkboxes, radio buttons, select lists, multi select lists and autocomplete
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.databasejoin
 * @since       3.0
 */

class PlgFabrik_ElementDatabasejoin extends PlgFabrik_ElementList
{
	/**
	 * connection
	 *
	 * @var FabrikFEModelConnection
	 */
	protected $cn = null;

	protected $joinDb = null;

	/**
	 * Created in getJoin
	 *
	 * @var FabrikFEModelJoin
	 */
	protected $join = null;

	/**
	 * Simple join query
	 *
	 * @var array
	 */
	protected $sql = array();

	/**
	 * Option values
	 *
	 * @var array
	 */
	protected $optionVals = array();

	/**
	 * Linked form data
	 *
	 * @var array
	 */
	protected $linkedForms = null;

	/**
	 * Additional where for auto-complete query
	 *
	 * @var string
	 */
	public $autocomplete_where = '';

	/**
	 * Name of the join db to connect to
	 *
	 * @var string
	 */
	protected $dbname = null;

	/**
	 * J Parameter name for the field containing the cdd label value
	 *
	 * @var string
	 */
	protected $labelParam = 'join_val_column';

	/**
	 * J Parameter name for the field containing the concat label
	 *
	 * @var string
	 */
	protected $concatLabelParam = 'join_val_column_concat';

	/**
	 * The value's required format (int/string/json/array/object)
	 *
	 * @since 3.1b2
	 *
	 * @var  string
	 */
	protected $valueFormat = 'array';

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
			 * this wasn't working for test case:
			* events -> (db join) event_artists -> el join (artist)

			* $$$ rob in csv import keytable not set
			* $$$ hugh - if keytable isn't set, the safeColName blows up!
			* Trying to debug issue with linked join elements, which don't get detected by
			* getJoins or getJoin 'cos element ID doesn't match element_id in fabrik_joins
			*$k = isset($join->keytable) ? $join->keytable : $join->join_from_table;
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
				$data = array();
				$opts = array();
				$v = $app->input->get('value', '', 'string');

				/*
				 * $$$ hugh (and Joe) - added 'autocomplete_how', currently just "starts_with" or "contains"
				* default to "contains" for backward compat.
				* http://fabrikar.com/forums/showthread.php?p=165192&posted=1#post165192
				*/
				$params = $this->getParams();
				$this->autocomplete_where = $this->_autocompleteWhere($params->get('dbjoin_autocomplete_how', 'contains'), $label, $v);
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
		/**
		 * $$$ hugh - bandaid for inlineedit, problem where $join isn't loaded, as per comments in getJoin().
		 * for now, just avoid this code if $join isn't an object.
		*/
		if (is_object($join) && ($params->get($this->concatLabelParam) != ''))
		{
			if ($app->input->get('override_join_val_column_concat') != 1)
			{
				$val = str_replace("{thistable}", $join->table_join_alias, $params->get($this->concatLabelParam));
				$w = new FabrikWorker;
				$val = $w->parseMessageForPlaceHolder($val, array(), false);
				$this->joinLabelCols[(int) $useStep] = 'CONCAT_WS(\'\', ' . $val . ')';

				return 'CONCAT_WS(\'\', ' . $val . ')';
			}
			else
			{
				/*
				 * A boolean search is in progress - we can't use concat might need to do something
				*  else here (http://fabrikar.com/forums/index.php?threads/search-plugin-sql-error.35177/)
				*/
			}
		}

		$label = $this->getJoinLabel();

		// Depending on the plugin getJoinLabel() returns a params property or the actual name, so default to it if we cant find a property
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
	 * @return  JTable  join table or false if not loaded
	 */

	protected function getJoin()
	{
		$app = JFactory::getApplication();
		$input = $app->input;

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

		if (!$this->getFormModel()->getForm()->record_in_database)
		{
			// Db join in form not recording to db
			$joinModel = JModelLegacy::getInstance('Join', 'FabrikFEModel');
			$this->join = $joinModel->getJoinFromKey('element_id', $element->id);

			return $this->join;
		}
		else
		{
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

			$config = array();
			$config['dbo'] = FabrikWorker::getDbo(true);
			$this->join = JTable::getInstance('Join', 'FabrikTable', $config);

			if ($this->join->load(array('element_id' => $element->id)))
			{

				if (is_string($this->join->params))
				{
					$this->join->params = new JRegistry($this->join->params);
				}

				return $this->join;
			}
		}

		if (!in_array($input->get('task'), array('inlineedit', 'form.inlineedit')) && $input->get('format') !== 'raw')
		{
			/*
			 * Suppress error for inlineedit, something not quite right as groupModel::getPublishedElements() is limited by the elementid request va
			 * but the list model is calling getAsFields() and loading up the db join element.
			 * so test case would be an inline edit list with a database join element and editing anything but the db join element
			 */
			throw new RuntimeException('unable to process db join element id:' . $element->id, 500);
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
	 * Get other joins that point to this element
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
	 * Run any eval'ed option code from element settings
	 *
	 * @param   array  $opts  array of option objects
	 */
	protected function evalOptions(&$opts) {
		$params = $this->getParams();
		$eval = $params->get('dabase_join_label_eval');
		if (trim($eval) !== '')
		{
			foreach ($opts as $key => &$opt)
			{
				if (eval($eval) === false)
				{
					unset($opts[$key]);
				}
			}
		}
	}

	/**
	 * Get array of option values
	 *
	 * @param   array  $data           Data
	 * @param   int    $repeatCounter  Repeat group counter
	 * @param   bool   $incWhere       Do we add custom where statement into sql
	 * @param   array  $opts           Additional options passed into buildQuery()
	 *
	 * @return  array	option values
	 */

	protected function _getOptionVals($data = array(), $repeatCounter = 0, $incWhere = true, $opts = array())
	{
		$params = $this->getParams();
		$db = $this->getDb();

		// $$$ hugh - attempting to make sure we never do an unconstrained query for auto-complete
		$displayType = $this->getDisplayType();
		$value = (array) $this->getValue($data, $repeatCounter);

		/*
		 *  $$$ rob 20/08/2012 - removed empty test - seems that this method is called more than one time, when in auto-complete filter
		 *  First time sends the label in data second time sends the value (which is the correct one)
		 *
		 * $$$ rob 07/01/2014 - replaced empty test - otherwise default value would override autocomplete search.
		 * I re-tested the auto-complete filter with this change and it appears to be ok.
		 */
		if ($displayType === 'auto-complete' && empty($this->autocomplete_where))
		{
			if (!empty($value) && $value[0] !== '')
			{
				$quoteV = array();

				foreach ($value as $v)
				{
					$quoteV[] = $db->quote($v);
				}

				$this->autocomplete_where = $this->getJoinValueColumn() . ' IN (' . implode(', ', $quoteV) . ')';
			}
		}
		// $$$ rob 18/06/2012 cache the option vals on a per query basis (was previously incwhere but this was not ok
		// for auto-completes in repeating groups
		$sql = $this->buildQuery($data, $incWhere, $opts);
		$sqlKey = (string) $sql;

		if (isset($this->optionVals[$sqlKey]))
		{
			return $this->optionVals[$sqlKey];
		}

		$db->setQuery($sql);
		FabrikHelperHTML::debug((string) $db->getQuery(), $this->getElement()->name . 'databasejoin element: get options query');
		$this->optionVals[$sqlKey] = $db->loadObjectList();
		FabrikHelperHTML::debug($this->optionVals, 'databasejoin elements');

		if (!is_array($this->optionVals[$sqlKey]))
		{
			$this->optionVals[$sqlKey] = array();
		}

		$eval = $params->get('dabase_join_label_eval');

		foreach ($this->optionVals[$sqlKey] as $key => &$opt)
		{
			// Check if concat label empty
			if ($this->emptyConcatString($opt->text))
			{
				$opt->text = '';
			}

			if (trim($eval) !== '')
			{
				// $$$ hugh - added allowing removing an option by returning false
				if (eval($eval) === false)
				{
					unset($this->optionVals[$sqlKey][$key]);
				}
			}
		}

		// Remove tags from labels
		if ($this->canUse())
		{
			foreach ($this->optionVals[$sqlKey] as $key => &$opt)
			{
				$opt->text = strip_tags($opt->text);
			}
		}

		return $this->optionVals[$sqlKey];
	}

	/**
	 * For fields that use the concat label, it may try to insert constants, but if no
	 * replacement data found then the concatinated constants should be conidered as emtyp
	 *
	 * @param   string  $label  Concatinate label
	 *
	 * @return boolean
	 */
	protected function emptyConcatString($label)
	{
		$params = $this->getParams();
		$concat = $params->get($this->concatLabelParam, '');

		if ($concat === '' || !$params->get('clean_concat', false))
		{
			return false;
		}

		$bits = explode(',', $concat);

		for ($i = 0; $i < count($bits); $i ++)
		{
			if (strstr(trim($bits[$i]), '{thistable}.'))
			{
				unset($bits[$i]);
			}
			else
			{
				$bits[$i] = FabrikString::ltrimword($bits[$i], "'");
				$bits[$i] = FabrikString::rtrimword($bits[$i], "'");
			}
		}

		if ($label == implode($bits))
		{
			return true;
		}

		return false;
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
	 * @param   array  $opts           Additional options passed into _getOptionVals()
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
			array_unshift($tmp, JHTML::_('select.option', $params->get('database_join_noselectionvalue', ''), $this->_getSelectLabel()));
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
		return FText::_($this->getParams()->get('database_join_noselectionlabel', FText::_('COM_FABRIK_PLEASE_SELECT')));
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
		$isnew = $this->getFormModel()->isNewRecord();

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
		$sig = isset($this->autocomplete_where) ? $this->autocomplete_where . '.' . $incWhere : $incWhere;
		$sig .= '.' . serialize($opts);
		$db = FabrikWorker::getDbo();
		$query = $db->getQuery(true);

		if (isset($this->sql[$sig]))
		{
			return $this->sql[$sig];
		}

		$params = $this->getParams();
		$element = $this->getElement();
		$formModel = $this->getForm();
		$query = $this->buildQueryWhere($data, $incWhere, null, $opts, $query);

		// $$$rob not sure these should be used anyway?
		$table = $params->get('join_db_name');
		$key = $this->getJoinValueColumn();
		$val = $this->getLabelOrConcatVal();
		$join = $this->getJoin();

		if ($table == '')
		{
			$table = $join->table_join;
			$key = $join->table_join_alias . '.' . $join->table_join_key;
			$val = $join->params->get('join-label', $val);
		}

		if ($key == '' || $val == '')
		{
			return false;
		}

		if (!strstr($val, 'CONCAT'))
		{
			$val = $db->quoteName($val);
		}

		$query->select('DISTINCT(' . $key . ') AS value, ' . $val . ' AS text');
		$this->buildQueryDescription($query, $data);
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
		$this->sql[$sig] = $query;

		return $this->sql[$sig];
	}

	/**
	 * Add the description field to the buildQuery select statement
	 *
	 * @param   JQuery  &$query  BuildQuery
	 * @param   array   $data    BuildQuery data
	 *
	 * @return  void
	 */
	protected function buildQueryDescription(&$query, $data)
	{
		$params = $this->getParams();
		$desc = $params->get('join_desc_column', '');

		if ($desc !== '')
		{
			$db = FabrikWorker::getDbo();
			$w = new FabrikWorker;
			$data = is_array($data) ? $data : array();
			$desc = $w->parseMessageForPlaceHolder($desc, $data, false);
			$desc = FabrikString::isConcat($desc) ? $desc : $db->quoteName($desc);
			$desc = "REPLACE(" . $desc . ", '\n', '<br />')";
			$query->select($desc . ' AS description');
		}
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
	 * $$$ hugh - added new "Additional join statement" option in join element, which now gets
	 * parsed here.  Should probably take the main logic in this, and put it in a helper, as this
	 * is probably something we'll need to do elsewhere.
	 *
	 * @param   mixed  $query  false to return string, or JQueryBuilder object
	 *
	 * @since 3.0rc1
	 *
	 * @return string|JQueryerBuilder join statement to add
	 */

	protected function buildQueryJoin($query = false)
	{
		if ($query !== false)
		{
			$params = $this->getParams();
			$query_join = trim($params->get('database_join_join_sql'), '');

			/*
			 * Set up RE of all possible valid MySQL join types, as per join_table spec here:
			* http://dev.mysql.com/doc/refman/5.0/en/join.html
			* EXCEPT just 'JOIN' by itself.  Don't ask.
			*/
			$re = array();
			$re[] = '(LEFT\s+JOIN)';
			$re[] = '(LEFT\s+OUTER\s+JOIN)';
			$re[] = '(RIGHT\s+JOIN)';
			$re[] = '(RIGHT\s+OUTER\s+JOIN)';
			$re[] = '(INNER\s+JOIN)';
			$re[] = '(CROSS\s+JOIN)';
			$re[] = '(STRAIGHT_JOIN)';
			$re[] = '(NATURAL\s+JOIN)';
			$re[] = '(NATURAL\s+LEFT\s+JOIN)';
			$re[] = '(NATURAL\s+RIGHT\s+JOIN)';
			$re[] = '(NATURAL\s+LEFT\s+OUTER\s+JOIN)';
			$re[] = '(NATURAL\s+RIGHT\s+OUTER\s+JOIN)';
			$re = implode('|', $re);

			/*
			 * Using NO_EMPT and SPLIT_CAPTURE, we should end up with an array which alternates
			* between the JOIN type, and the rest of the expression
			*/
			$joins = preg_split("#$re#i", $query_join, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

			if (!empty($joins))
			{
				$ojoin = $this->getJoin();
				$join_types = array();
				$join_exprs = array();

				foreach ($joins as $index => $join)
				{
					if (!($index & 1))
					{
						/*
						 * Index is odd, so should be a JOIN type.
						* If it doesn't have the word 'join' in it, something went
						* wrong, so just bail.
						*/
						if (!stristr($join, 'join'))
						{
							return '';
						}
						/*
						 * Strip out the word JOIN.  Must use preg_replace, so we don't break STRAIGHT_JOIN.
						* Trim it, and stuff it in the types array.
						*/
						$join = preg_replace("#\s+JOIN\s*#i", '', $join);
						$join_types[] = trim($join);
					}
					else
					{
						/*
						 * It's an odd index, so should be the rest of the expression.
						* Do {thistable} replacement on it, trim it, and stuff it in the exprs array
						*/
						$join = str_replace("{thistable}", $ojoin->table_join_alias, $join);
						$join_exprs[] = trim($join);
					}
				}
				/*
				 * Now just iterate through the types array, and add type and expr to the query builder.
				*/
				foreach ($join_types as $index => $join_type)
				{
					$join_expr = $join_exprs[$index];
					$query->join($join_type, $join_expr);
				}
			}

			return $query;
		}

		return "";
	}

	/**
	 * Create the where part for the query that selects the list options
	 *
	 * @param   array           $data            Current row data to use in placeholder replacements
	 * @param   bool            $incWhere        Should the additional user defined WHERE statement be included
	 * @param   string          $thisTableAlias  Db table alias
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
		$where = ($this->mustApplyWhere($whereaccess, $element->id) && $incWhere) ? $params->get('database_join_where_sql') : '';
		$join = $this->getJoin();
		$thisTableAlias = is_null($thisTableAlias) ? $join->table_join_alias : $thisTableAlias;

		// $$$rob 11/10/2011  remove order by statements which will be re-inserted at the end of buildQuery()
		if (preg_match('/(ORDER\s+BY)(.*)/i', $where, $matches))
		{
			$this->orderBy = str_replace("{thistable}", $join->table_join_alias, $matches[0]);
			$where = str_replace($this->orderBy, '', $where);
			$where = str_replace($matches[0], '', $where);
		}

		if (!empty($this->autocomplete_where))
		{
			$mode = JArrayHelper::getValue($opts, 'mode', 'form');
			$displayType = $params->get('database_join_display_type', 'dropdown');
			$filterType = $element->filter_type;

			if (($mode == 'filter' && $filterType == 'auto-complete')
				|| ($mode == 'form' && $displayType == 'auto-complete')
				|| ($mode == 'filter' && $displayType == 'auto-complete'))
			{
				$where .= JString::stristr($where, 'WHERE') ? ' AND ' . $this->autocomplete_where : ' WHERE ' . $this->autocomplete_where;
			}
		}

		if ($where == '')
		{
			return $query ? $query : $where;
		}

		$where = str_replace("{thistable}", $thisTableAlias, $where);
		$w = new FabrikWorker;
		$lang = JFactory::getLanguage();
		$data = is_array($data) ? $data : array();

		if (!isset($data['lang']))
		{
			$data['lang'] = $lang->getTag();
		}

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
	 * Get the FULL element name or concat statement used currently in sum calculations
	 *
	 * @return  string
	 */

	protected function getFullLabelOrConcat()
	{
		$params = $this->getParams();
		$join = $this->getJoin();
		$joinTable = $join->table_join_alias;
		$label = $this->getLabelOrConcatVal();

		if ($params->get($this->concatLabelParam) == '')
		{
			$label = $joinTable . '.' . $this->getLabelParamVal();
		}

		return $label;
	}

	/**
	 * Get the element name or concat statement used to build the dropdown labels or
	 * table data field
	 *
	 * @return  string
	 */

	protected function getLabelOrConcatVal()
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

			return 'CONCAT_WS(\'\', ' . $val . ')';
		}
	}

	/**
	 * Get the database object
	 *
	 * @return  object	Database
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
	 * @param   int    $repeatCounter  when repeating joined groups we need to know what part of the array to access
	 *
	 * @return  string	value
	 */

	public function getROValue($data, $repeatCounter = 0)
	{
		$v = $this->getValue($data, $repeatCounter);

		return $this->getLabelForValue($v, $v);
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
		$package = $app->getUserState('com_fabrik.package', 'fabrik');

		// For repeating groups we need to unset this where each time the element is rendered
		unset($this->autocomplete_where);

		if ($this->isJoin())
		{
			$this->hasSubElements = true;
		}

		$params = $this->getParams();
		$formModel = $this->getForm();
		$groupModel = $this->getGroup();
		$displayType = $this->getDisplayType();
		$db = $this->getDb();
		$default = (array) $this->getValue($data, $repeatCounter, array('raw' => true));
		$defaultLabels = (array) $this->getValue($data, $repeatCounter, array('raw' => false));

		$tmp = $this->_getOptions($data, $repeatCounter);
		$w = new FabrikWorker;
		$default = $w->parseMessageForPlaceHolder($default);
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);

		$optsPerRow = (int) $params->get('dbjoin_options_per_row', 0);
		$html = array();

		if (!$formModel->isEditable() || !$this->isEditable())
		{
			// Read only element formatting...
			if (JArrayHelper::getValue($defaultLabels, 0) === $params->get('database_join_noselectionlabel', FText::_('COM_FABRIK_PLEASE_SELECT')))
			{
				// No point showing 'please select' for read only
				unset($defaultLabels[0]);
			}

			// Encrypted failed validations - only the raw value is retrieved, swap it with the option text
			if ($formModel->failedValidation())
			{
				$newLabels = array();

				foreach ($tmp as $t)
				{
					if (in_array($t->value, $defaultLabels))
					{
						$newLabels[] = $t->text;
					}
				}

				$defaultLabels = $newLabels;
			}

			/*
			 * if it's a new form, labels won't be set for any defaults.
			*/
			if ($formModel->getRowId() == '')
			{
				foreach ($defaultLabels as $key => $val)
				{
					/*
					 * Calling getLabelForVaue works, but it generates a database query for each one.
					* We should already have what we need in $tmp (the result of _getOptions), so lets
					* grab it from there.
					*/
					// $defaultLabels[$key] = $this->getLabelForValue($default[$key], $default[$key], true);
					if (!empty($val))
					{
						foreach ($tmp as $t)
						{
							if ($t->value == $val)
							{
								$defaultLabels[$key] = $t->text;
								break;
							}
						}
					}
				}
			}

			$targetIds = $this->multiOptionTargetIds($data, $repeatCounter);

			if ($targetIds !== false)
			{
				$this->addReadOnlyLinks($defaultLabels, $targetIds);
			}
			else
			{
				$this->addReadOnlyLinks($defaultLabels, $default);
			}

			$html[] = count($defaultLabels) < 2 ? implode(' ', $defaultLabels) : '<ul><li>' . implode('<li>', $defaultLabels) . '</li></ul>';
		}
		else
		{
			// $$$rob should be canUse() otherwise if user set to view but not use the dd was shown
			if ($this->canUse())
			{
				$attribs = 'class="fabrikinput inputbox input ' . $params->get('bootstrap_class', 'input-large') . '" size="1"';

				// If user can access the drop down
				switch ($displayType)
				{
					case 'dropdown':
					default:
						$html[] = JHTML::_('select.genericlist', $tmp, $name, $attribs, 'value', 'text', $default, $id);
						break;
					case 'radio':
						$this->renderRadioList($data, $repeatCounter, $html, $tmp, JArrayHelper::getValue($default, 0));
						break;
					case 'checkbox':
						$this->renderCheckBoxList($data, $repeatCounter, $html, $tmp, $default);
						break;
					case 'multilist':
						$this->renderMultiSelectList($data, $repeatCounter, $html, $tmp, $default);
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
					// Set position inherit otherwise btn-group blocks selection of checkboxes
					$html[] = '<div class="btn-group" style="position:inherit">';
				}

				if ($frontEndSelect && $this->isEditable())
				{
					$forms = $this->getLinkedForms();
					$popupform = (int) $params->get('databasejoin_popupform');
					$popuplistid = (empty($popupform) || !isset($forms[$popupform])) ? '' : $forms[$popupform]->listid;
					JText::script('PLG_ELEMENT_DBJOIN_SELECT');

					if ($app->isAdmin())
					{
						$chooseUrl = 'index.php?option=com_fabrik&amp;task=list.view&amp;listid=' . $popuplistid . '&amp;tmpl=component&amp;ajax=1';
					}
					else
					{
						$chooseUrl = 'index.php?option=com_' . $package . '&amp;view=list&amp;listid=' . $popuplistid . '&amp;tmpl=component&amp;ajax=1';
					}

					$html[] = '<a href="' . $chooseUrl . '" class="toggle-selectoption btn" title="' . FText::_('COM_FABRIK_SELECT') . '">'
						. FabrikHelperHTML::image('search.png', 'form', @$this->tmpl, array('alt' => FText::_('COM_FABRIK_SELECT'))) . '</a>';
				}

				if ($frontEndAdd && $this->isEditable())
				{
					JText::script('PLG_ELEMENT_DBJOIN_ADD');
					$popupform = (int) $params->get('databasejoin_popupform');
					$addURL = 'index.php?option=com_fabrik';
					$addURL .= $app->isAdmin() ? '&amp;task=form.view' : '&amp;view=form';
					$addURL .= '&amp;tmpl=component&amp;ajax=1&amp;formid=' . $popupform;
					$html[] = '<a href="' . $addURL . '" title="' . FText::_('COM_FABRIK_ADD') . '" class="toggle-addoption btn">';
					$html[] = FabrikHelperHTML::image('plus.png', 'form', @$this->tmpl, array('alt' => FText::_('COM_FABRIK_SELECT'))) . '</a>';
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
			$default_val = JArrayHelper::getValue($default, 0);

			for ($i = 0; $i < count($opts); $i++)
			{
				$opt = $opts[$i];
				$display = $opt->value == $default_val ? '' : 'none';
				$c = $this->showPleaseSelect() ? $i + 1 : $i;
				$html[] = '<div style="display:' . $display . '" class="notice description-' . $c . '">' . $opt->description . '</div>';
			}

			$html[] = '</div>';
		}

		return implode("\n", $html);
	}

	/**
	 * Add read only links, if option set and related 'add options in front end'
	 * form found.
	 *
	 * @param   array  &$defaultLabels  Default labels
	 * @param   array  $defaultValues   Default values
	 *
	 * @return  void
	 */
	protected function addReadOnlyLinks(&$defaultLabels, $defaultValues)
	{
		$params = $this->getParams();

		if ($params->get('databasejoin_readonly_link') == 1)
		{
			if ($popUrl = $this->popUpFormUrl())
			{
				for ($i = 0; $i < count($defaultLabels); $i++)
				{
					$url = $popUrl . JArrayHelper::getValue($defaultValues, $i, '');
					$defaultLabels[$i] = '<a href="' . JRoute::_($url) . '">' . JArrayHelper::getValue($defaultLabels, $i) . '</a>';
				}
			}
		}
	}

	/**
	 * Build Pop up form URL
	 *
	 * @return boolean|string
	 */

	protected function popUpFormUrl()
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$params = $this->getParams();
		$popupformid = (int) $params->get('databasejoin_popupform');

		if ($popupformid === 0)
		{
			return false;
		}

		$db = $this->getDb();
		$query = $db->getQuery(true);
		$query->select('id')->from('#__{package}_lists')->where('form_id =' . $popupformid);
		$db->setQuery($query);
		$listid = $db->loadResult();

		$itemId = FabrikWorker::itemId($listid);
		$task = $app->isAdmin() ? 'task=details.view' : 'view=details';
		$url = 'index.php?option=com_' . $package . '&' . $task . '&formid=' . $popupformid . '&listid=' . $listid;

		if (!$app->isAdmin())
		{
			$url .= '&Itemid=' . $itemId;
		}

		$url .= '&rowid=';

		return $url;
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
		$optsPerRow = (int) $params->get('dbjoin_options_per_row', 0);

		// $$$ rob 24/05/2011 - always set one value as selected for radio button if none already set
		if ($defaultValue == '' && !empty($tmp))
		{
			$defaultValue = $tmp[0]->value;
		}

		$html[] = '<div class="fabrikSubElementContainer" id="' . $id . '">';
		$editable = $this->isEditable();
		$html[] = FabrikHelperHTML::aList('radio', $tmp, $thisElName, $attribs, $defaultValue, 'value', 'text', $optsPerRow, $editable);
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
		if ($formModel->hasErrors() || $formModel->getRowId() === '')
		{
			$label = (array) $this->getLabelForValue($label[0], $label[0], true);
		}

		$class = ' class="fabrikinput inputbox autocomplete-trigger ' . $params->get('bootstrap_class', 'input-large') . '"';

		$placeholder = ' placeholder="' . htmlspecialchars($params->get('placeholder', ''), ENT_COMPAT) . '"';
		$autoCompleteName = str_replace('[]', '', $thisElName) . '-auto-complete';
		$html[] = '<input type="text" size="' . $params->get('dbjoin_autocomplete_size', '20') . '" name="' . $autoCompleteName . '" id="' . $id
		. '-auto-complete" value="' . JArrayHelper::getValue($label, 0) . '"' . $class . $placeholder . '/>';

		// $$$ rob - class property required when cloning repeat groups - don't remove
		$html[] = '<input type="hidden" tabindex="-1" class="fabrikinput" name="' . $thisElName . '" id="' . $id . '" value="'
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
		$optsPerRow = intval($params->get('dbjoin_options_per_row', 0));
		$targetIds = $this->multiOptionTargetIds($data, $repeatCounter);
		$class = 'fabrikinput inputbox ' . $params->get('bootstrap_class', '');

		if ($targetIds !== false)
		{
			$default = $targetIds;
		}

		if ($this->isEditable())
		{
			$multiSize = (int) $params->get('dbjoin_multilist_size', 6);
			$attribs = 'class="' . $class . '" size="' . $multiSize . '" multiple="true"';
			$html[] = JHTML::_('select.genericlist', $tmp, $elName, $attribs, 'value', 'text', $default, $id);
		}
		else
		{
			$attribs = 'class="' . $class . '" size="1" id="' . $id . '"';
			$html[] = FabrikHelperHTML::aList('multilist', $tmp, $elName, $attribs, $default, 'value', 'text', $optsPerRow, $this->isEditable());
		}
	}

	/**
	 * Render checkbox list in form
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  Repeat group counter
	 * @param   array  &$html          HTML to assign output to
	 * @param   array  $tmp            List of value/label objects
	 * @param   array  $default        Default values - the lookup table's primary key values
	 *
	 * @since   3.0.7
	 *
	 * @return  void
	 */

	protected function renderCheckBoxList($data, $repeatCounter, &$html, $tmp, $default)
	{
		$id = $this->getHTMLId($repeatCounter);
		$name = $this->getHTMLName($repeatCounter);
		$params = $this->getParams();
		$optsPerRow = (int) $params->get('dbjoin_options_per_row', 0);

		$html[] = '<div class="fabrikSubElementContainer" id="' . $id . '">';
		$editable = $this->isEditable();
		$attribs = 'class="fabrikinput inputbox" id="' . $id . '"';

		$name = FabrikString::rtrimword($name, '[]');
		$targetIds = $this->multiOptionTargetIds($data, $repeatCounter);

		if ($targetIds !== false)
		{
			$default = $targetIds;
		}

		$html[] = FabrikHelperHTML::aList('checkbox', $tmp, $name, $attribs, $default, 'value', 'text', $optsPerRow, $editable);

		if (empty($tmp))
		{
			$tmpids = array();
			$o = new stdClass;
			$o->text = 'dummy';
			$o->value = 'dummy';
			$tmpids[] = $o;
			$tmp = $tmpids;
			$dummy = FabrikHelperHTML::aList('checkbox', $tmp, $name, $attribs, $default, 'value', 'text', 1, true);
			$html[] = '<div class="chxTmplNode">' . $dummy . '</div>';
		}

		$html[] = '</div>';
	}

	/**
	 * Called from within function getValue
	 * needed so we can append _raw to the name for elements such as db joins
	 *
	 * @param   array  $opts  Options
	 *
	 * @return  string  Element name inside data array
	 */

	protected function getValueFullName($opts)
	{
		$name = $this->getFullName(true, false);
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
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  When repeating joined groups we need to know what part of the array to access
	 * @param   array  $opts           Options
	 *
	 * @return  string	Text to add to the browser's title
	 */

	public function getTitlePart($data, $repeatCounter = 0, $opts = array())
	{
		// Get raw value
		$opts['raw'] = '1';
		$titleParts = (array) $this->getValue($data, $repeatCounter, $opts);

		// Replace with labels
		foreach ($titleParts as &$titlePart)
		{
			$titlePart = $this->getLabelForValue($titlePart, $titlePart, true);
		}

		return implode(', ', $titleParts);
	}

	/**
	 * Get an array of potential forms that will add data to the db joins table.
	 * Used for add in front end
	 *
	 * @return  array  db objects
	 */

	protected function getLinkedForms()
	{
		if (!isset($this->linkedForms))
		{
			$db = FabrikWorker::getDbo(true);
			$params = $this->getParams();

			// Forms for potential add record pop up form
			$query = $db->getQuery(true);
			$query->select('f.id AS value, f.label AS text, l.id AS listid')->from('#__{package}_forms AS f')
			->join('LEFT', '#__{package}_lists As l ON f.id = l.form_id')
			->where('f.published = 1 AND l.db_table_name = ' . $db->quote($params->get('join_db_name')))->order('f.label');
			$db->setQuery($query);
			$this->linkedForms = $db->loadObjectList('value');
		}

		return $this->linkedForms;
	}

	/**
	 * Get database field description
	 *
	 * @return  string  db field type
	 */

	public function getFieldDescription()
	{
		if ($this->encryptMe())
		{
			return 'BLOB';
		}

		$params = $this->getParams();
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
	 * @param   mixed  $value          Element's data
	 * @param   array  $data           Form records data
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string	Formatted value
	 */

	public function getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		$tmp = $this->_getOptions($data, $repeatCounter);

		if ($this->isJoin())
		{
			/**
			 * $$$ hugh - if it's a repeat element, we need to render it as
			 * a single entity
			 * $$$ hugh - sometimes it's empty, not an array, so check just to
			 * stop PHP whining about it.
			 */
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
						break;
					}
				}

				$val = $this->renderListData($value, new stdClass);
			}
		}

		if ($val === $this->_getSelectLabel())
		{
			$val = '';
		}

		return $val;
	}

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

			$data = json_encode($labeldata);
		}

		// $$$ rob add links and icons done in parent::renderListData();
		return parent::renderListData($data, $thisRow);
	}

	/**
	 * Optionally pre-format list data before rendering to <ul>
	 *
	 * @param   array  &$data    Element Data
	 * @param   array  $thisRow  Row data
	 *
	 * @return  void
	 */
	protected function listPreformat(&$data, $thisRow)
	{
		$raw = $this->getFullName(true, false);
		$displayType = $this->getDisplayType();
		$raw .= ($displayType == 'checkbox' || $displayType == 'multilist') ? '_id' : '_raw';
		$values = isset($thisRow->$raw) ? FabrikWorker::JSONtoData($thisRow->$raw, true) : array();

		foreach ($data as $key => $value)
		{
			if ($this->emptyConcatString($data[$key]))
			{
				$data[$key] = '';
			}
		}

		$this->addReadOnlyLinks($data, $values);
	}

	/**
	 * Used in things like date when its id is suffixed with _cal
	 * called from getLabel();
	 *
	 * @param   string  &$id  Initial id
	 *
	 * @return  void
	 */

	protected function modHTMLId(&$id)
	{
		$displayType = $this->getDisplayType();

		if ($displayType === 'auto-complete')
		{
			$id = $id . '-auto-complete';
		}
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
			$default = $this->getLabelForValue($default);
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

		if (in_array($element->filter_type, array('range', 'dropdown', '', 'checkbox', 'multiselect')))
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
				* Moved warning to element model filterValueList_Exact()
				* So we'll just return an otherwise empty menu with just the 'select label'
				*/
				$rows = array();
				array_unshift($rows, JHTML::_('select.option', '', $this->filterSelectLabel()));
				$return[] = JHTML::_('select.genericlist', $rows, $v, 'class="' . $class . '" size="1" ', "value", 'text', $default, $htmlid);

				return implode("\n", $return);
			}

			$this->unmergeFilterSplits($rows);
			$this->reapplyFilterLabels($rows);

			if (!in_array($element->filter_type, array('checkbox', 'multiselect')))
			{
				array_unshift($rows, JHTML::_('select.option', '', $this->filterSelectLabel()));
			}
		}

		$size = $params->get('filter_length', 20);

		switch ($element->filter_type)
		{
			case 'checkbox':
				$return[] = $this->checkboxFilter($rows, $default, $v);
				break;
			case 'dropdown':
			default:
			case '':
			case 'multiselect':
				$max = count($rows) < 7 ? count($rows) : 7;
				$size = $element->filter_type === 'multiselect' ? 'multiple="multiple" size="' . $max . '"' : 'size="1"';
				$v = $element->filter_type === 'multiselect' ? $v . '[]' : $v;
				$this->addSpaceToEmptyLabels($rows, 'text');
				$return[] = JHTML::_('select.genericlist', $rows, $v, 'class="' . $class . '" ' . $size, "value", 'text', $default, $htmlid);
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
			case 'auto-complete':
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

		if (strstr($label, '::'))
		{
			$labels = explode('::', $label);
			$label = FText::_(array_pop($labels));
		}

		if ($label == '')
		{
			$label = $params->get('filter_required') == 1 ? FText::_('COM_FABRIK_PLEASE_SELECT') : FText::_('COM_FABRIK_FILTER_PLEASE_SELECT');
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
		$elName = FabrikString::safeColName($this->getFullName(true, false));

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
		$elementName = FabrikString::safeColName($this->getFullName(false, false));
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
	 * @param   string         $view   View mode '' or 'filter'
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
					$order = $joinLabel . 'ASC ';
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
	 * @since  3.0.7
	 *
	 * @return  string
	 */

	protected function getJoinValueFieldName()
	{
		$params = $this->getParams();

		return FabrikString::shortColName($params->get('join_key_column'));
	}

	/**
	 * Builds an array containing the filters value and condition
	 *
	 * @param   string  $value      Initial value
	 * @param   string  $condition  Initial $condition
	 * @param   string  $eval       How the value should be handled
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
			$stringComparisons = array('begins', 'contains', 'equals', 'ends');

			if (in_array($condition, $stringComparisons))
			{
				// Searching on value so set to equals
				$condition = '=';
			}
		}

		return parent::getFilterValue($value, $condition, $eval);
	}

	/**
	 * Build the filter query for the given element.
	 * Can be overwritten in plugin - e.g. see checkbox element which checks for partial matches
	 *
	 * @param   string  $key            element name in format `tablename`.`elementname`
	 * @param   string  $condition      =/like etc.
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
				if (!$this->isJoin())
				{
					$join = $this->getJoin();
					$key = $this->getLabelOrConcatVal();

					if (!strstr($key, 'CONCAT'))
					{
						$key = FabrikString::safeColName($join->table_join_alias) . '.' . $db->quoteName($key);
					}
				}
			}

			$key = 'LOWER(' . $key . ')';
			$str = "$key $condition $value";
		}
		else
		{
			$group = $this->getGroup();

			if (!$group->isJoin() && $group->canRepeat())
			{
				// Deprecated I think - repeat groups are always joins.
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
						// Either look for the parent_ids in the main fabrik list or the group's list.
						$groupJoinModel = $group->getJoinModel();
						$groupFk = $groupJoinModel->getForeignKey('.');
						$lookupTable = $group->isJoin() ? $groupFk : $this->getListModel()->getTable()->db_primary_key;
						$str = $lookupTable . ' IN (' . implode(', ', $joinIds) . ')';
					}
				}
				else
				{
					if ($fType === 'auto-complete')
					{
						// If autocomplete then we should search on the element's column, not the joined label column http://fabrikar.com/forums/showthread.php?t=29977
						$key = $db->quoteName($this->getFullName(false, false));
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
	 * @param   string  $groupBy    Field name to key the results on - avoids duplicates
	 * @param   string  $condition  If supplied then filters the list (must then supply $where and $value)
	 * @param   string  $value      If supplied then filters the list (must then supply $where and $condition)
	 * @param   string  $where      If supplied then filters the list (must then supply $value and $condition)
	 * @param   int     $offset     Query offset - default 0
	 * @param   int     $limit      Query limit - default 0
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

		// If rendering as multi/checkbox then {thistable} should not refer to the joining repeat table, but the end table.
		if ($this->isJoin())
		{
			$jkey = $this->getLabelOrConcatVal();
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
		ksort($rows);

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
		$label = FabrikString::shortColName($label);
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
	 * Does the element consider the data to be empty
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
			// Check if it's an array because we are a multiselect join
			if ($this->isJoin())
			{
				return empty($data);
			}
			else
			{
				return empty($data[0]);
			}
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
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */

	public function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);

		if ($this->getParams()->get('database_join_display_type', 'dropdown') == 'auto-complete')
		{
			$autoOpts = array();
			$autoOpts['max'] = $this->getParams()->get('autocomplete_rows', '10');
			$autoOpts['storeMatchedResultsOnly'] = true;
			FabrikHelperHTML::autoComplete($id, $this->getElement()->id, $this->getFormModel()->getId(), 'databasejoin', $autoOpts);
		}

		$opts = $this->elementJavascriptOpts($repeatCounter);

		return array('FbDatabasejoin', $id, $opts);
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
	 * @param   int  $repeatCounter  Group repeat counter
	 *
	 * @return  array  Options
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
		$opts->fullName = $this->getFullName(true, false);
		$opts->key = $table . '___' . $params->get('join_key_column');
		$opts->label = $table . '___' . $this->getLabelParamVal();
		$opts->formid = $this->getForm()->getForm()->id;
		$opts->listid = $popuplistid;
		$opts->listRef = '_com_fabrik_' . $opts->listid;
		$opts->value = $arSelected;
		$opts->defaultVal = $this->getDefaultValue($data);
		$opts->popupform = $popupform;
		$opts->windowwidth = $params->get('join_popupwidth', 360);
		$opts->displayType = $this->getDisplayType();
		$opts->show_please_select = $params->get('database_join_show_please_select') === "1";
		$opts->showDesc = $params->get('join_desc_column', '') === '' ? false : true;
		$opts->autoCompleteOpts = $opts->displayType == 'auto-complete'
			? FabrikHelperHTML::autoCompleteOptions($opts->id, $this->getElement()->id, $this->getFormModel()->getId(), 'databasejoin') : null;
		$opts->allowadd = $params->get('fabrikdatabasejoin_frontend_add', 0) == 0 ? false : true;
		$opts->listName = $this->getListModel()->getTable()->db_table_name;
		$this->elementJavascriptJoinOpts($opts);
		$opts->isJoin = $this->isJoin();

		return $opts;
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
			throw new RuntimeException('The encryption option is only available for field and text area plugins');

			return false;
		}

		if (!$this->isJoin())
		{
			$this->updateFabrikJoins($data, $this->getDbName(), $this->getJoinValueFieldName(), $this->getLabelParamVal());
		}

		return parent::onSave($data);
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
	 * On save of element, update its jos_fabrik_joins record and any descendants join record
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

		$pk = $this->getListModel()->getPrimaryKeyAndExtra($join->table_join);
		$join_pk = $join->table_join;
		$join_pk .= '.' . $pk[0]['colname'];
		$db = FabrikWorker::getDbo(true);
		$join_pk = $db->quoteName($join_pk);

		$o = new stdClass;
		$l = 'join-label';
		$o->$l = $label;
		$o->type = 'element';
		$o->pk = $join_pk;
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
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();

		switch ($params->get('database_join_display_type', 'dropdown'))
		{
			case 'dropdown':
				$trigger = 'change';
				break;
			case 'auto-complete':
				$trigger = '';
				$id = str_replace('[]', '', $id) . '-auto-complete';
				break;
			default:
				$trigger = 'click';
				break;
		}

		$ar = array();

		if ($trigger !== '')
		{
			$ar[] = array('id' => $id, 'triggerEvent' => $trigger);
		}

		$ar[] = array('id' => $id, 'triggerEvent' => 'blur');

		return $ar;
	}

	/**
	 * Used by elements with suboptions, given a value, return its label
	 *
	 * @param   string  $v             Value
	 * @param   string  $defaultLabel  Default label
	 * @param   bool    $forceCheck    Force check even if $v === $defaultLabel
	 *
	 * @return  string	Label
	 */

	public function getLabelForValue($v, $defaultLabel = null, $forceCheck = false)
	{
		// Band aid - as this is called in listModel::addLabels() lets not bother - re-querying the db (label already loaded)
		if ($v === $defaultLabel && !$forceCheck)
		{
			return $v;
		}

		if ($this->isJoin())
		{
			$rows = $this->checkboxRows('id');

			if (count($v) === 0)
			{
				$v = '';
			}
			else
			{
				// In a repeat group
				if (is_array($v))
				{
					$v = JArrayHelper::getValue($v, $repeatCounter);
				}
			}

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

		if (is_array($v))
		{
			/**
			 * $$$ hugh - tweaked this a little, as IN () pitches an error in
			 * MySQL, so we need to make sure we don't end up with that.  So as
			 * IN (), if it worked, would produce no rows, just replace with 1=-1
			 * Can't just count($v), as sometimes it's an array with a single null entry.
			 */
			array_map(array($db, 'quote'), $v);
			$ins = implode(',', $v);

			if (trim($ins) === '')
			{
				$query->where('1=-1');
			}
			else
			{
				$query->where($key . ' IN (' . $ins . ')');
			}
		}
		else
		{
			$query->where($key . ' = ' . $db->quote($v));
		}

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
	 * @return  string	default filter condition ('=', 'REGEXP' etc.)
	 */

	public function getDefaultFilterCondition()
	{
		return '=';
	}

	/**
	 * Is the element's cnn the same as the main Joomla db
	 *
	 * @return  bool
	 */

	protected function inJDb()
	{
		return $this->getListModel()->inJDb();
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
		$c = $elementModel->getLabelOrConcatVal();

		if (!strstr($c, 'CONCAT'))
		{
			$c = FabrikString::safeColName($c);
		}

		$filterMethod = $elementModel->getFilterBuildMethod();

		if ($filterMethod == 1)
		{
			$join = $elementModel->getJoin();
			$joinTable = $join->table_join_alias;
			$opts = array();

			if (!strstr($c, 'CONCAT'))
			{
				$opts['label'] = strstr($c, '.') ? $c : $joinTable . '.' . $c;
			}
			else
			{
				$opts['label'] = $c;
			}

			return parent::cacheAutoCompleteOptions($elementModel, $search, $opts);
		}
		// $$$ hugh - added 'autocomplete_how', currently just "starts_with" or "contains"
		// default to "contains" for backward compat.
		$elementModel->autocomplete_where = $elementModel->_autocompleteWhere($params->get('dbjoin_autocomplete_how', 'contains'), $c, $search);
		$opts = array('mode' => 'filter');
		$tmp = $elementModel->_getOptions(array(), 0, true, $opts);

		return json_encode($tmp);
	}

	/**
	 * Get the autocomplete Where clause based on the parameter
	 *
	 * @param   string  $how     Dbjoin_autocomplete_how setting - contains, words, starts_with
	 * @param   string  $field   Field
	 * @param   string  $search  Search string
	 *
	 * @return  string  with required where clause based upon dbjoin_autocomplete_how setting
	 */

	private function _autocompleteWhere($how, $field, $search)
	{
		$db = FabrikWorker::getDbo();
		$search = strtolower($search);
		$field = 'LOWER(' . $field . ')';

		switch ($how)
		{
			case 'contains':
			default:
				$where = $field . ' LIKE ' . $db->quote('%' . $search . '%');
				break;
			case 'words':
				$words = array_filter(explode(' ', $search));

				foreach ($words as &$word)
				{
					$word = $db->quote('%' . $word . '%');
				}

				$where = $field . ' LIKE ' . implode(' AND ' . $field . ' LIKE ', $words);
				break;
			case 'starts_with':
				$where = $field . ' LIKE ' . $db->quote($search . '%');
				break;
		}

		return $where;
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
		$joinVal = $this->getLabelOrConcatVal();

		if (!strstr($joinVal, 'CONCAT'))
		{
			$return = strstr($joinVal, '___') ? FabrikString::safeColName($joinVal) : $joinTable . '.' . $joinVal;
		}
		else
		{
			$return = $joinVal;
		}

		// If storing in join table then we should use the alias created from the CONCAT select subquery
		if ($return == '.' || $this->isJoin())
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

		if (in_array($params->get('database_join_display_type', 'dropdown'), array('checkbox', 'multilist')))
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
		$jkey = $this->getLabelOrConcatVal();
		$where = $this->buildQueryWhere(array(), true, $params->get('join_db_name'));
		$where = JString::stristr($where, 'order by') ? $where : '';
		$dbName = $this->getDbName();
		/**
		 * Use lookup alias rather than directly referencing $dbName
		 * As if dbName is the same as another table in the query the
		 * Where part of this query will be incorrect.
		*/
		$jkey = !strstr($jkey, 'CONCAT') ? 'lookup.' . $jkey : $jkey;

		// If rendering as multi/checkbox then {thistable} should not refer to the joining repeat table, but the end table.
		if ($this->isJoin())
		{
			/*
			 * $$$ hugh
			* @TODO - needs to be more selective, prolly a regex with word breaks, so a $jointable of 'foo' doesn't match
			* (say) a field name 'foobar', etc.
			* Also ... I think we need to NOT do this inside a subquery!
			*/
			$jkey = str_replace($jointable, 'lookup', $jkey);
		}

		$parentKey = $this->buildQueryParentKey();
		$fullElName = $this->getFullName(true, false);
		$sql = "(SELECT GROUP_CONCAT(" . $jkey . " " . $where . " SEPARATOR '" . GROUPSPLITTER . "') FROM $jointable
		LEFT JOIN " . $dbName . " AS lookup ON lookup." . $this->getJoinValueFieldName() . " = $jointable." . $this->getElement()->name . " WHERE "
			. $jointable . ".parent_id = " . $parentKey . ")";

		if ($addAs)
		{
			$sql .= ' AS ' . $fullElName;
		}

		return $sql;
	}

	/**
	 * Get the parent key element name
	 *
	 * @return string
	 */

	protected function buildQueryParentKey()
	{
		$item = $this->getListModel()->getTable();
		$parentKey = $item->db_primary_key;

		if ($this->isJoin())
		{
			$groupModel = $this->getGroupModel();

			if ($groupModel->isJoin())
			{
				// Need to set the joinTable to be the group's table
				$groupJoin = $groupModel->getJoinModel();
				$parentKey = $groupJoin->getJoin()->params->get('pk');
			}
		}

		return $parentKey;
	}

	/**
	 * Build the sub query which is used when merging in
	 * repeat element records from their joined table into the one field.
	 * Overwritten in database join element to allow for building
	 * the join to the table containing the stored values required ids
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
		$parentKey = $this->buildQueryParentKey();
		$fullElName = $this->getFullName(true, false) . '_id';
		$str .= ", (SELECT GROUP_CONCAT(" . $this->element->name . " SEPARATOR '" . GROUPSPLITTER . "') FROM $jointable WHERE " . $jointable
		. ".parent_id = " . $parentKey . ") AS $fullElName";

		return $str;
	}

	/**
	 * Used in form model setJoinData.
	 *
	 * @since 2.1.1
	 *
	 * @return  array  of element names to search data in to create join data array
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
			$repeatName = $this->getFullName(true, false) . '___repeatnum';
			$a[] = $repeatName;

			$repeatName = $this->getFullName(true, false) . '_id';
			$a[] = $repeatName;
		}

		return $a;
	}

	/**
	 * When the element is a repeatable join (e.g. db join checkbox) then figure out how many
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
	 * Get the display type (list,checkbox,multiselect etc.)
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
	 * which may use a CONCAT'ed label which mustn't be quoted.
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
	 * @param   bool  $advancedMode  Is the list using advanced search
	 *
	 * @since   3.1b
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

	/**
	 * When rendered as a multi-select / checkbox getValue() returns the id for the x-ref table.
	 * This method gets the ids for the records in the x-ref target table.
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  array|boolean  Array of ids if found, else return false.
	 */
	protected function multiOptionTargetIds($data, $repeatCounter = 0)
	{
		$displayType = $this->getDisplayType();

		if ($displayType == 'checkbox' || $displayType == 'multilist')
		{
			$idname = $this->getFullName(true, false) . '_id';
			$formModel = $this->getFormModel();

			if ($this->isJoin() && !$formModel->hasErrors())
			{
				// Only add repeatCounter if group model repeating - otherwise we only ever select one checkbox.
				if ($this->getGroupModel()->canRepeat())
				{
					$idname .= '.' . $repeatCounter;
				}

				$default = (array) FArrayHelper::getNestedValue($data, $idname, 'not found');

				return $default;
			}
		}

		return false;
	}
}
