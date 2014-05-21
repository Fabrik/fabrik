<?php
/**
 * Fabrik Admin List Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once 'fabmodeladmin.php';

/**
 * Fabrik Admin List Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikAdminModelList extends FabModelAdmin
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 */
	protected $text_prefix = 'COM_FABRIK_LIST';

	/**
	 * Front end form model
	 *
	 * @var object model
	 */
	protected $formModel = null;

	/**
	 * Front end list model
	 *
	 * @var object
	 */
	protected $feListModel = null;

	/**
	 * Currently loaded list row
	 *
	 * @var array
	 */
	protected $tables = array();

	/**
	 * Plugin type
	 *
	 * @var string
	 * @deprecated ?
	 */
	protected $pluginType = 'List';

	/**
	 * Database fields
	 *
	 * @var array
	 */
	protected $dbFields = null;

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable	A database object
	 *
	 * @since	1.6
	 */

	public function getTable($type = 'List', $prefix = 'FabrikTable', $config = array())
	{
		$sig = $type . $prefix . implode('.', $config);

		if (!array_key_exists($sig, $this->tables))
		{
			$config['dbo'] = FabrikWorker::getDbo(true);
			$this->tables[$sig] = FabTable::getInstance($type, $prefix, $config);
		}

		return $this->tables[$sig];
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array  $data      Data for the form.
	 * @param   bool   $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed	A JForm object on success, false on failure
	 *
	 * @since	1.6
	 */

	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_fabrik.list', 'list', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		$form->model = $this;

		return $form;
	}

	/**
	 * Method to get the confirm list delete form.
	 *
	 * @param   array  $data      Data for the form.
	 * @param   bool   $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 *
	 * @since	1.6
	 */

	public function getConfirmDeleteForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_fabrik.confirmdelete', 'confirmlistdelete', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed	The data for the form.
	 *
	 * @since	1.6
	 */

	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_fabrik.edit.list.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * @param   array  &$pks   A list of the primary keys to change.
	 * @param   int    $value  The value of the published state.
	 *
	 * @return  boolean	True on success.
	 *
	 * @since	1.6
	 */

	public function publish(&$pks, $value = 1)
	{
		// Initialise variables.
		$dispatcher = JDispatcher::getInstance();
		$user = JFactory::getUser();
		$table = $this->getTable();
		$pks = (array) $pks;

		// Include the content plugins for the change of state event.
		JPluginHelper::importPlugin('content');

		// Access checks.
		foreach ($pks as $i => $pk)
		{
			if ($table->load($pk))
			{
				if (!$this->canEditState($table))
				{
					// Prune items that you can't change.
					unset($pks[$i]);
					JError::raiseWarning(403, FText::_('JLIB_APPLICATION_ERROR_EDIT_STATE_NOT_PERMITTED'));
				}
			}
		}

		// Attempt to change the state of the records.
		if (!$table->publish($pks, $value, $user->get('id')))
		{
			$this->setError($table->getError());

			return false;
		}

		$context = $this->option . '.' . $this->name;

		// Trigger the onContentChangeState event.
		$result = $dispatcher->trigger($this->event_change_state, array($context, $pks, $value));

		if (in_array(false, $result, true))
		{
			$this->setError($table->getError());

			return false;
		}

		return true;
	}

	/**
	 * Build and/or dropdown list
	 *
	 * @param   bool    $addSlashes  to reutrn data
	 * @param   string  $name        input name
	 *
	 * @return string dropdown
	 */

	protected function getFilterJoinDd($addSlashes = true, $name = 'join')
	{
		$aConditions = array();
		$aConditions[] = JHTML::_('select.option', 'AND');
		$aConditions[] = JHTML::_('select.option', 'OR');
		$attribs = 'class="inputbox input-small" size="1"';
		$dd = str_replace("\n", "", JHTML::_('select.genericlist', $aConditions, $name, $attribs, 'value', 'text', ''));

		if ($addSlashes)
		{
			$dd = addslashes($dd);
		}

		return $dd;
	}

	/**
	 * Build prefilter dropdown
	 *
	 * @param   bool    $addSlashes  add slashes to reutrn data
	 * @param   string  $name        name of the drop down
	 * @param   int     $mode        states what values get put into drop down
	 *
	 * @return string dropdown
	 */

	protected function getFilterConditionDd($addSlashes = true, $name = 'conditions', $mode = 1)
	{
		$aConditions = array();

		switch ($mode)
		{
			case 1: /* used for search filter */
				$aConditions[] = JHTML::_('select.option', '<>', 'NOT EQUALS');
				$aConditions[] = JHTML::_('select.option', '=', 'EQUALS');
				$aConditions[] = JHTML::_('select.option', 'like', 'BEGINS WITH');
				$aConditions[] = JHTML::_('select.option', 'like', 'CONTAINS');
				$aConditions[] = JHTML::_('select.option', 'like', 'ENDS WITH');
				$aConditions[] = JHTML::_('select.option', '>', 'GREATER THAN');
				$aConditions[] = JHTML::_('select.option', '>=', 'GREATER THAN OR EQUALS');
				$aConditions[] = JHTML::_('select.option', '<', 'LESS THAN');
				$aConditions[] = JHTML::_('select.option', '<=', 'LESS THAN OR EQUALS');
				break;
			case 2: /* used for prefilter */
				$aConditions[] = JHTML::_('select.option', 'equals', 'EQUALS');
				$aConditions[] = JHTML::_('select.option', 'notequals', 'NOT EQUAL TO');
				$aConditions[] = JHTML::_('select.option', 'begins', 'BEGINS WITH');
				$aConditions[] = JHTML::_('select.option', 'contains', 'CONTAINS');
				$aConditions[] = JHTML::_('select.option', 'ends', 'ENDS WITH');
				$aConditions[] = JHTML::_('select.option', '>', 'GREATER THAN');
				$aConditions[] = JHTML::_('select.option', '>=', 'GREATER THAN OR EQUALS');
				$aConditions[] = JHTML::_('select.option', '<', 'LESS THAN');
				$aConditions[] = JHTML::_('select.option', 'IS NULL', 'IS NULL');
				$aConditions[] = JHTML::_('select.option', '<=', 'LESS THAN OR EQUALS');
				$aConditions[] = JHTML::_('select.option', 'in', 'IN');
				$aConditions[] = JHTML::_('select.option', 'not_in', 'NOT IN');
				$aConditions[] = JHTML::_('select.option', 'exists', 'EXISTS');
				$aConditions[] = JHTML::_('select.option', 'earlierthisyear', FText::_('COM_FABRIK_EARLIER_THIS_YEAR'));
				$aConditions[] = JHTML::_('select.option', 'laterthisyear', FText::_('COM_FABRIK_LATER_THIS_YEAR'));

				$aConditions[] = JHTML::_('select.option', 'yesterday', FText::_('COM_FABRIK_YESTERDAY'));
				$aConditions[] = JHTML::_('select.option', 'today', FText::_('COM_FABRIK_TODAY'));
				$aConditions[] = JHTML::_('select.option', 'tomorrow', FText::_('COM_FABRIK_TOMORROW'));
				$aConditions[] = JHTML::_('select.option', 'thismonth', FText::_('COM_FABRIK_THIS_MONTH'));
				$aConditions[] = JHTML::_('select.option', 'lastmonth', FText::_('COM_FABRIK_LAST_MONTH'));
				$aConditions[] = JHTML::_('select.option', 'nextmonth', FText::_('COM_FABRIK_NEXT_MONTH'));

				break;
		}

		$dd = str_replace("\n", "", JHTML::_('select.genericlist', $aConditions, $name, 'class="inputbox input-small"  size="1" ', 'value', 'text', ''));

		if ($addSlashes)
		{
			$dd = addslashes($dd);
		}

		return $dd;
	}

	/**
	 * Get connection model
	 *
	 * @return  object  connect model
	 */

	protected function getCnn()
	{
		$item = $this->getItem();
		$connModel = JModelLegacy::getInstance('Connection', 'FabrikFEModel');
		$connModel->setId($item->connection_id);
		$connModel->getConnection($item->connection_id);

		return $connModel;
	}

	/**
	 * Create the js that manages the edit list view page
	 *
	 * @return  string  js
	 */

	public function getJs()
	{
		$connModel = $this->getCnn();
		$plugins = $this->getPlugins();
		$item = $this->getItem();
		$pluginManager = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');
		JText::script('COM_FABRIK_OPTIONS');
		JText::script('COM_FABRIK_JOIN');
		JText::script('COM_FABRIK_FIELD');
		JText::script('COM_FABRIK_CONDITION');
		JText::script('COM_FABRIK_VALUE');
		JText::script('COM_FABRIK_EVAL');
		JText::script('COM_FABRIK_APPLY_FILTER_TO');
		JText::script('COM_FABRIK_DELETE');
		JText::script('JYES');
		JText::script('JNO');
		JText::script('COM_FABRIK_QUERY');
		JTEXT::script('COM_FABRIK_NO_QUOTES');
		JText::script('COM_FABRIK_TEXT');
		JText::script('COM_FABRIK_TYPE');
		JText::script('COM_FABRIK_PLEASE_SELECT');
		JText::script('COM_FABRIK_GROUPED');
		JText::script('COM_FABRIK_TO');
		JText::script('COM_FABRIK_FROM');
		JText::script('COM_FABRIK_JOIN_TYPE');
		JText::script('COM_FABRIK_FROM_COLUMN');
		JText::script('COM_FABRIK_TO_COLUMN');
		JText::script('COM_FABRIK_REPEAT_GROUP_BUTTON_LABEL');
		JText::script('COM_FABRIK_PUBLISHED');

		$joinTypeOpts = array();
		$joinTypeOpts[] = array('inner', FText::_('INNER JOIN'));
		$joinTypeOpts[] = array('left', FText::_('LEFT JOIN'));
		$joinTypeOpts[] = array('right', FText::_('RIGHT JOIN'));
		$activetableOpts[] = "";
		$activetableOpts[] = $item->db_table_name;

		$joins = $this->getJoins();

		for ($i = 0; $i < count($joins); $i++)
		{
			$j = $joins[$i];
			$activetableOpts[] = $j->table_join;
			$activetableOpts[] = $j->join_from_table;
		}

		$activetableOpts = array_unique($activetableOpts);
		$activetableOpts = array_values($activetableOpts);
		$opts = new stdClass;
		$opts->joinOpts = $joinTypeOpts;
		$opts->tableOpts = $connModel->getThisTables(true);
		$opts->activetableOpts = $activetableOpts;
		$opts->j3 = FabrikWorker::j3();
		$opts = json_encode($opts);

		$filterOpts = new stdClass;
		$filterOpts->filterJoinDd = $this->getFilterJoinDd(false, 'jform[params][filter-join][]');
		$filterOpts->filterCondDd = $this->getFilterConditionDd(false, 'jform[params][filter-conditions][]', 2);
		$filterOpts->filterAccess = JHtml::_('access.level', 'jform[params][filter-access][]', $item->access, 'class="input-small"');
		$filterOpts->filterAccess = str_replace(array("\n", "\r"), '', $filterOpts->filterAccess);
		$filterOpts->j3 = FabrikWorker::j3();
		$filterOpts = json_encode($filterOpts);

		$formModel = $this->getFormModel();
		$attribs = 'class="inputbox input-small" size="1"';
		$filterfields = $formModel->getElementList('jform[params][filter-fields][]', '', false, false, true, 'name', $attribs);
		$filterfields = addslashes(str_replace(array("\n", "\r"), '', $filterfields));

		$plugins = json_encode($this->getPlugins());

		$js = array();
		$js[] = "window.addEvent('domready', function () {";
		$js[] = "Fabrik.controller = new PluginManager($plugins, " . (int) $this->getItem()->id . ", 'list');";

		$js[] = "oAdminTable = new ListForm($opts);";
		$js[] = "oAdminTable.watchJoins();";

		for ($i = 0; $i < count($joins); $i++)
		{
			$joinGroupParams = json_decode($joins[$i]->params);
			$j = $joins[$i];
			$joinFormFields = json_encode($j->joinFormFields);
			$joinToFields = json_encode($j->joinToFields);
			$repeat = $joinGroupParams->repeat_group_button == 1 ? 1 : 0;
			$js[] = "	oAdminTable.addJoin('{$j->group_id}','{$j->id}','{$j->join_type}','{$j->table_join}',"
			. "'{$j->table_key}','{$j->table_join_key}','{$j->join_from_table}', $joinFormFields, $joinToFields, $repeat);";
		}

		$js[] = "oAdminFilters = new adminFilters('filterContainer', '$filterfields', $filterOpts);";
		$form = $this->getForm();
		$afilterJoins = $form->getValue('params.filter-join');

		// Force to arrays as single prefilters imported from 2.x will be stored as string values
		$afilterFields = (array) $form->getValue('params.filter-fields');
		$afilterConditions = (array) $form->getValue('params.filter-conditions');
		$afilterEval = (array) $form->getValue('params.filter-eval');
		$afilterValues = (array) $form->getValue('params.filter-value');
		$afilterAccess = (array) $form->getValue('params.filter-access');
		$aGrouped = (array) $form->getValue('params.filter-grouped');

		for ($i = 0; $i < count($afilterFields); $i++)
		{
			$selJoin = JArrayHelper::getValue($afilterJoins, $i, 'and');

			// 2.0 upgraded sites had quoted filter names
			$selFilter = str_replace('`', '', $afilterFields[$i]);
			$grouped = JArrayHelper::getValue($aGrouped, $i, 0);
			$selCondition = $afilterConditions[$i];
			$filerEval = (int) JArrayHelper::getValue($afilterEval, $i, '1');

			if ($selCondition == '&gt;')
			{
				$selCondition = '>';
			}

			if ($selCondition == '&lt;')
			{
				$selCondition = '<';
			}

			$selValue = JArrayHelper::getValue($afilterValues, $i, '');
			$selAccess = $afilterAccess[$i];

			// Alow for multiline js variables ?
			$selValue = htmlspecialchars_decode($selValue, ENT_QUOTES);
			$selValue = json_encode($selValue);

			// No longer check for empty $selFilter as EXISTS prefilter condition doesn't require element to be selected
			$js[] = "\toAdminFilters.addFilterOption('$selJoin', '$selFilter', '$selCondition', $selValue, '$selAccess', $filerEval, '$grouped');\n";
		}

		$js[] = "});";

		return implode("\n", $js);
	}

	/**
	 * Get the list's join objects
	 *
	 * @return  array
	 */

	protected function getJoins()
	{
		$item = $this->getItem();

		if ((int) $item->id === 0)
		{
			return array();
		}

		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('*, j.id AS id, j.params as jparams')->from('#__{package}_joins AS j')
			->join('INNER', '#__{package}_groups AS g ON g.id = j.group_id')->where('j.list_id = ' . (int) $item->id);
		$db->setQuery($query);
		$joins = $db->loadObjectList();
		$fabrikDb = $this->getFEModel()->getDb();
		$c = count($joins);

		for ($i = 0; $i < $c; $i++)
		{
			$join =& $joins[$i];
			$jparams = $join->jparams == '' ? new stdClass : json_decode($join->jparams);

			if (isset($jparams->type) && ($jparams->type == 'element' || $jparams->type == 'repeatElement'))
			{
				unset($joins[$i]);
				continue;
			}

			if (empty($join->join_from_table) || empty($join->table_join))
			{
				unset($joins[$i]);
				continue;
			}

			$fields = $fabrikDb->getTableColumns($join->join_from_table);
			$join->joinFormFields = array_keys($fields);
			$fields = $fabrikDb->getTableColumns($join->table_join);
			$join->joinToFields = array_keys($fields);
		}
		// $$$ re-index the array in case we zapped anything
		return array_values($joins);
	}

	/**
	 * Load up a front end form model - used in saving the list
	 *
	 * @return  object  front end form model
	 */

	public function getFormModel()
	{
		if (is_null($this->formModel))
		{
			$config = array();
			$config['dbo'] = FabrikWorker::getDbo(true);
			$this->formModel = JModelLegacy::getInstance('Form', 'FabrikFEModel', $config);
			$this->formModel->setDbo($config['dbo']);

			/**
			 * $$$ rob commenting out as this loads up an empty form when saving a new list
			 * $item = $this->getItem();
			 * $this->formModel->setId($this->getState('list.form_id', $item->id));
			 * $this->formModel->getForm();
			 */

			/**
			 * $$$ rob isnt this wrong as normally the front end form models list model is the fe list model?
			 * $this->formModel->setListModel($this);
			 */
		}

		return $this->formModel;
	}

	/**
	 * Set the form model
	 *
	 * @param   object  $model  form model
	 *
	 * @return  void
	 */

	public function setFormModel($model)
	{
		$this->formModel = $model;
	}

	/**
	 * Load up the front end list model so we can use some of its methods
	 *
	 * @return  object  front end list model
	 */

	public function getFEModel()
	{
		if (is_null($this->feListModel))
		{
			$this->feListModel = JModelLegacy::getInstance('List', 'FabrikFEModel');
			$this->feListModel->setState('list.id', $this->getState('list.id'));
		}

		return $this->feListModel;
	}

	/**
	 * Validate the form
	 *
	 * @param   object  $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return mixed  false or data
	 */

	public function validate($form, $data, $group = null)
	{
		$params = $data['params'];
		$data = parent::validate($form, $data, $group);

		if (!$data)
		{
			return false;
		}

		if (empty($data['_database_name']) && JArrayHelper::getValue($data, 'db_table_name') == '')
		{
			$this->setError(FText::_('COM_FABRIK_SELECT_DB_OR_ENTER_NAME'));

			return false;
		}

		// Hack - must be able to add the plugin xml fields file to $form to include in validation but cant see how at the moment
		$data['params'] = $params;

		return $data;
	}

	/**
	 * Save the form
	 *
	 * @param   array  $data  the jform part of the request data
	 *
	 * @return  bool
	 */

	public function save($data)
	{
		$this->populateState();
		$app = JFactory::getApplication();
		$input = $app->input;
		$user = JFactory::getUser();
		$config = JFactory::getConfig();
		$date = JFactory::getDate();
		$row = $this->getTable();

		$id = JArrayHelper::getValue($data, 'id');
		$row->load($id);

		$params = new JRegistry($row->params);

		$this->setState('list.id', $id);
		$this->setState('list.form_id', $row->form_id);
		$feModel = $this->getFEModel();
		$formModel = $this->getFormModel();

		// Get original collation
		$db = $feModel->getDb();

		if (!empty($data['db_table_name']))
		{
			$db->setQuery('SHOW TABLE STATUS LIKE ' . $db->quote($data['db_table_name']));
			$info = $db->loadObject();
			$origCollation = is_object($info) ? $info->Collation : $params->get('collation', 'none');
		}
		else
		{
			$origCollation = $params->get('collation', 'none');
		}

		$row->bind($data);

		$row->order_by = json_encode($input->get('order_by', array(), 'array'));
		$row->order_dir = json_encode($input->get('order_dir', array(), 'array'));

		$row->check();

		$this->collation($feModel, $origCollation, $row);
		$isNew = true;

		if ($row->id != 0)
		{
			$datenow = JFactory::getDate();
			$row->modified = $datenow->toSql();
			$row->modified_by = $user->get('id');
		}

		if ($id == 0)
		{
			if ($row->created == '')
			{
				$row->created = $date->toSql();
			}
			// Save the row now
			$row->store();

			$isNew = false;
			$newtable = trim(JArrayHelper::getValue($data, '_database_name'));

			// Mysql will force db table names to lower case even if you set the db name to upper case - so use clean()
			$newtable = FabrikString::clean($newtable);

			// Check the entered database table doesnt already exist
			if ($newtable != '' && $this->databaseTableExists($newtable))
			{
				throw new RuntimeException(FText::_('COM_FABRIK_DATABASE_TABLE_ALREADY_EXISTS'));

				return false;
			}

			if (!$this->canCreateDbTable())
			{
				throw new RuntimeException(FText::_('COM_FABRIK_INSUFFICIENT_RIGHTS_TO_CREATE_TABLE'));

				return false;
			}

			// Create fabrik form
			$formModel = $this->createLinkedForm();
			$row->form_id = $this->getState('list.form_id');

			// Create fabrik group
			$groupData = FabrikWorker::formDefaults('group');
			$groupData['name'] = $row->label;
			$groupData['label'] = $row->label;
			$input->set('_createGroup', 1, 'post');
			$groupId = $this->createLinkedGroup($groupData, false);

			if ($newtable == '')
			{
				// New fabrik list but existing db table
				$this->createLinkedElements($groupId);
			}
			else
			{
				$row->db_table_name = $newtable;
				$row->auto_inc = 1;

				$dbOpts = array();
				$params = new JRegistry($row->params);
				$dbOpts['COLLATE'] = $params->get('collation', '');
				$res = $this->createDBTable($newtable, $input->get('defaultfields', array('id' => 'internalid', 'date_time' => 'date'), 'array'), $dbOpts);

				if (is_array($res))
				{
					$row->db_primary_key = $newtable . '.' . $res[0];
				}
			}
		}

		/*
		 * // Not right - as it meant only isview parameter saved on new list. Something
		 * to do with isView() also saviing list parameters.
		 * $params = new JRegistry($row->params);
		$params->set('isview', $feModel->isView());
		$row->params = (string) $params;
		*/
		FabrikAdminHelper::prepareSaveDate($row->publish_down);
		FabrikAdminHelper::prepareSaveDate($row->created);
		FabrikAdminHelper::prepareSaveDate($row->publish_up);
		$pk = JArrayHelper::getValue($data, 'db_primary_key');

		if ($pk == '')
		{
			$pk = $feModel->getPrimaryKeyAndExtra($row->db_table_name);
			$key = $pk[0]['colname'];
			$extra = $pk[0]['extra'];

			// Store without quoteNames as thats db specific
			$row->db_primary_key = $row->db_primary_key == '' ? $row->db_table_name . "." . $key : $row->db_primary_key;
			$row->auto_inc = JString::stristr($extra, 'auto_increment') ? true : false;
		}

		$row->store();
		$pk = $row->db_primary_key;
		$this->updateJoins($data);

		// Needed to ensure pk field is not quoted
		$feModel->setTable($row);

		if (!$feModel->isView())
		{
			$this->updatePrimaryKey($row->db_primary_key, $row->auto_inc);
		}

		// Make an array of elments and a presumed index size, map is then used in creating indexes
		$map = array();
		$groups = $this->getFormModel()->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getMyElements();

			foreach ($elementModels as $element)
			{
				// Int and DATETIME elements cant have a index size attrib
				$coltype = $element->getFieldDescription();

				if (JString::stristr($coltype, 'int'))
				{
					$size = '';
				}
				elseif (JString::stristr($coltype, 'datetime'))
				{
					$size = '';
				}
				else
				{
					$size = '10';
				}

				$map[$element->getFullName(false, false)] = $size;
				$map[$element->getElement()->id] = $size;
			}
		}
		// Update indexes (added array_key_exists check as these may be during after CSV import)
		if (!empty($aOrderBy) && array_key_exists($row->order_by, $map))
		{
			foreach ($aOrderBy as $orderBy)
			{
				if (array_key_exists($orderBy, $map))
				{
					$feModel->addIndex($orderBy, 'tableorder', 'INDEX', $map[$orderBy]);
				}
			}
		}

		if ($row->group_by !== '' && array_key_exists($row->group_by, $map))
		{
			$feModel->addIndex($row->group_by, 'groupby', 'INDEX', $map["$row->group_by"]);
		}

		if (trim($params->get('group_by_order')) !== '')
		{
			$feModel->addIndex($params->get('group_by_order'), 'groupbyorder', 'INDEX', $map[$params->get('group_by_order')]);
		}

		$afilterFields = (array) $params->get('filter-fields', array());

		foreach ($afilterFields as $field)
		{
			if (array_key_exists($field, $map))
			{
				$feModel->addIndex($field, 'prefilter', 'INDEX', $map[$field]);
			}
		}

		$pkName = $row->getKeyName();

		if (isset($row->$pkName))
		{
			$this->setState($this->getName() . '.id', $row->$pkName);
		}

		/**
		 * $$$ hugh - I don't know what this state gets used for, but $iNew is
		 * currently ending up the wrong way round.  New tables it's false,
		 * existing tables it's true.
		 */

		$this->setState($this->getName() . '.new', $isNew);
		parent::cleanCache('com_fabrik');

		return true;
	}

	/**
	 * Alter the db table's collation
	 *
	 * @param   object  $feModel        Front end list model
	 * @param   string  $origCollation  Original collection name
	 * @param   string  $row            New collation
	 *
	 * @since   3.0.7
	 *
	 * @return boolean
	 */
	protected function collation($feModel, $origCollation, $row)
	{
		// Don't attempt to alter new table, or a view, or if we shouldn't alter the table
		if ($row->id == 0 || $feModel->isView() || !$feModel->canAlterFields())
		{
			return;
		}

		$params = new JRegistry($row->params);
		$newCollation = $params->get('collation');

		if ($newCollation !== $origCollation)
		{
			$db = $feModel->getDb();
			$item = $feModel->getTable();
			$db->setQuery('ALTER TABLE ' . $item->db_table_name . ' COLLATE  ' . $newCollation);
			$db->execute();
		}

		return true;
	}

	/**
	 * Check to see if a table exists
	 *
	 * @param   string  $tableName  name of table (ovewrites form_id val to test)
	 *
	 * @return  bool	false if no table found true if table found
	 */

	public function databaseTableExists($tableName = null)
	{
		if ($tableName === '')
		{
			return false;
		}

		$table = $this->getTable();

		if (is_null($tableName))
		{
			$tableName = $table->db_table_name;
		}

		$fabrikDatabase = $this->getDb();
		$sql = 'SHOW TABLES LIKE ' . $fabrikDatabase->quote($tableName);
		$fabrikDatabase->setQuery($sql);
		$total = $fabrikDatabase->loadResult();
		echo $fabrikDatabase->getError();

		return ($total == '') ? false : true;
	}

	/**
	 * Deals with ensuring joins are managed correctly when table is saved
	 *
	 * @param   array  $data  jform data
	 *
	 * @return  void
	 */

	private function updateJoins($data)
	{
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);

		// If we are creating a new list then don't update any joins - can result in groups and elements being removed.
		if ((int) $this->getState('list.id') === 0)
		{
			return;
		}
		// $$$ hugh - added "AND element_id = 0" to avoid fallout from "random join and group deletion" issue from May 2012
		$query->select('*')->from('#__{package}_joins')->where('list_id = ' . (int) $this->getState('list.id') . ' AND element_id = 0');
		$db->setQuery($query);
		$aOldJoins = $db->loadObjectList();
		$params = $data['params'];
		$aOldJoinsToKeep = array();
		$joinModel = JModelLegacy::getInstance('Join', 'FabrikFEModel');
		$joinIds = JArrayHelper::getValue($params, 'join_id', array());
		$joinTypes = JArrayHelper::getValue($params, 'join_type', array());
		$joinTableFrom = JArrayHelper::getValue($params, 'join_from_table', array());
		$joinTable = JArrayHelper::getValue($params, 'table_join', array());
		$tableKey = JArrayHelper::getValue($params, 'table_key', array());
		$joinTableKey = JArrayHelper::getValue($params, 'table_join_key', array());
		$groupIds = JArrayHelper::getValue($params, 'group_id', array());
		$repeats = JArrayHelper::getValue($params, 'join_repeat', array());
		$jc = count($joinTypes);

		// Test for repeat elements to eusure their join isnt removed from here
		foreach ($aOldJoins as $oldJoin)
		{
			if ($oldJoin->params !== '')
			{
				$oldParams = json_decode($oldJoin->params);

				if (isset($oldParams->type) && $oldParams->type == 'repeatElement')
				{
					$aOldJoinsToKeep[] = $oldJoin->id;
				}
			}
		}

		for ($i = 0; $i < $jc; $i++)
		{
			$existingJoin = false;

			foreach ($aOldJoins as $oOldJoin)
			{
				if ($joinIds[$i] == $oOldJoin->id)
				{
					$existingJoin = true;
				}
			}

			// $$$rob make an index on the join element (fk)
			$els = $this->getFEModel()->getElements();

			foreach ($els as $el)
			{
				if ($el->getElement()->name == $tableKey[$i])
				{
					$size = JString::stristr($el->getFieldDescription(), 'int') ? '' : '10';
				}
			}

			$this->getFEModel()->addIndex($tableKey[$i], 'join', 'INDEX', $size);

			if (!$existingJoin)
			{
				$this->makeNewJoin($tableKey[$i], $joinTableKey[$i], $joinTypes[$i], $joinTable[$i], $joinTableFrom[$i], $repeats[$i][0]);
			}
			else
			{
				/* load in the exisitng join
				 * if the table_join has changed we need to create a new join
				 * (with its corresponding group and elements)
				 *  and mark the loaded one as to be deleted
				 */
				$joinModel->setId($joinIds[$i]);
				$joinModel->clearJoin();
				$join = $joinModel->getJoin();

				if ($join->table_join != $joinTable[$i])
				{
					$this->makeNewJoin($tableKey[$i], $joinTableKey[$i], $joinTypes[$i], $joinTable[$i], $joinTableFrom[$i], $repeats[$i][0]);
				}
				else
				{
					// The table_join has stayed the same so we simply update the join info
					$join->table_key = str_replace('`', '', $tableKey[$i]);
					$join->table_join_key = $joinTableKey[$i];
					$join->join_type = $joinTypes[$i];
					$join->store();

					// Update group
					$group = $this->getTable('Group');
					$group->load($join->group_id);
					$gparams = json_decode($group->params);
					$gparams->repeat_group_button = $repeats[$i][0] == 1 ? 1 : 0;
					$group->params = json_encode($gparams);
					$group->store();
					$aOldJoinsToKeep[] = $joinIds[$i];
				}
			}
		}
		// Remove non exisiting joins
		if (is_array($aOldJoins))
		{
			foreach ($aOldJoins as $oOldJoin)
			{
				if (!in_array($oOldJoin->id, $aOldJoinsToKeep))
				{
					// Delete join
					$join = $this->getTable('Join');
					$joinModel->setId($oOldJoin->id);
					$joinModel->clearJoin();
					$joinModel->getJoin();
					$joinModel->deleteAll($oOldJoin->group_id);
				}
			}
		}
	}

	/**
	 * New join make the group, group elements and formgroup entries for the join data
	 *
	 * @param   string  $tableKey       table key
	 * @param   string  $joinTableKey   join to table key
	 * @param   string  $joinType       join type
	 * @param   string  $joinTable      join to table
	 * @param   string  $joinTableFrom  join table
	 * @param   bool    $isRepeat       is the group a repeat
	 *
	 * @return  void
	 */

	protected function makeNewJoin($tableKey, $joinTableKey, $joinType, $joinTable, $joinTableFrom, $isRepeat)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$formModel = $this->getFormModel();
		$groupData = FabrikWorker::formDefaults('group');
		$groupData['name'] = $this->getTable()->label . '- [' . $joinTable . ']';
		$groupData['label'] = $joinTable;
		$groupId = $this->createLinkedGroup($groupData, true, $isRepeat);

		$origTable = JArrayHelper::getValue($input->get('jform', array(), 'array'), 'db_table_name');
		$join = $this->getTable('Join');
		$join->id = null;
		$join->list_id = $this->getState('list.id');
		$join->join_from_table = $joinTableFrom;
		$join->table_join = $joinTable;
		$join->table_join_key = $joinTableKey;
		$join->table_key = str_replace('`', '', $tableKey);
		$join->join_type = $joinType;
		$join->group_id = $groupId;
		/**
		 * Create the 'pk' param.  Can't just call front end setJoinPk() for gory
		 * reasons, so do this by steam.
		 *
		 * Prolly don't really need to create a registry object here, we could just
		 * JSON-up the pk param, but might as well make the point here that it's a
		 * params object, and it may come in useful for adding other params one day.
		 */
		$join->params = new JRegistry;
		/**
		 * This is kind of expensive, as getPrimaryKeyAndExtra() method does a table lookup,
		 * but I don't think we know what the PK of the joined table is any other
		 * way at this point.
		 */
		$pk = $this->getFEModel()->getPrimaryKeyAndExtra($join->table_join);

		if ($pk !== false)
		{
			// If it didn't return false, getPrimaryKeyAndExtra will have created and array with at least one key
			$pk_col = JArrayHelper::getValue($pk[0], 'colname', '');

			if (!empty($pk_col))
			{
				$db = FabrikWorker::getDbo(true);
				$pk_col = $join->table_join . '.' . $pk_col;
				$join->params->set('pk', $db->quoteName($pk_col));
				$join->params = (string) $join->params;
			}
		}

		$join->store();

		$this->createLinkedElements($groupId, $joinTable);
	}

	/**
	 * When saving a table that links to a database for the first time we
	 * need to create all the elements based on the database table fields and their
	 * column type
	 *
	 * @param   int     $groupId    group id
	 * @param   string  $tableName  table name - if not set then use jform's db_table_name (@since 3.1)
	 *
	 * @return  void
	 */

	private function createLinkedElements($groupId, $tableName = '')
	{
		$db = FabrikWorker::getDbo(true);
		$app = JFactory::getApplication();
		$input = $app->input;
		$user = JFactory::getUser();
		$config = JFactory::getConfig();
		$createdate = JFactory::getDate();
		$createdate = $createdate->toSql();

		if ($tableName === '')
		{
			$jform = $input->get('jform', array(), 'array');
			$tableName = JArrayHelper::getValue($jform, 'db_table_name');
		}

		$formModel = $this->getFormModel();
		$pluginManager = FabrikWorker::getPluginManager();
		$ordering = 0;
		$fabrikDb = $this->getFEModel()->getDb();
		$groupTable = FabTable::getInstance('Group', 'FabrikTable');
		$groupTable->load($groupId);

		// Here we're importing directly from the database schema
		$query = $db->getQuery(true);
		$query->select('id')->from('#__{package}_lists')->where('db_table_name = ' . $db->quote($tableName));
		$db->setQuery($query);
		$id = $db->loadResult();
		$dispatcher = JDispatcher::getInstance();
		$elementModel = new PlgFabrik_Element($dispatcher);

		if ($id)
		{
			// A fabrik table already exists - so we can copy the formatting of its elements
			$groupListModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
			$groupListModel->setId($id);
			$groupListModel->getTable();
			$groups = $groupListModel->getFormGroupElementData();
			$newElements = array();
			$ecount = 0;

			foreach ($groups as $groupModel)
			{
				/**
				 * If we are saving a new table and the previously found tables group is a join
				 * then don't add its elements to the table as they don't exist in the database table
				 * we are linking to
				 * $$$ hugh - why the test for task and new table?  When creating elements for a copy of a table,
				 * surely we NEVER want to include elements which were joined to the original,
				 * regardless of whether this is a new List?  Bearing in mind that this routine gets called from
				 * the makeNewJoin() method, when adding a join to an existing list, to build the "Foo - [bar]" join
				 * group, as well as from save() when creating a new List.
				 *
				 *  if ($groupModel->isJoin() && $input->get('task') == 'save' && $input->getInt('id') == 0)
				 */
				if ($groupModel->isJoin())
				{
					continue;
				}

				$elementModels = &$groupModel->getMyElements();

				foreach ($elementModels as $elementModel)
				{
					$ecount++;
					$element = $elementModel->getElement();
					$copy = $elementModel->copyRow($element->id, $element->label, $groupId);
					$newElements[$element->id] = $copy->id;
				}
			}

			foreach ($newElements as $origId => $newId)
			{
				$plugin = $pluginManager->getElementPlugin($newId);
				$plugin->finalCopyCheck($newElements);
			}
			// Hmm table with no elements - lets create them from the structure anyway
			if ($ecount == 0)
			{
				$this->makeElementsFromFields($groupId, $tableName);
			}
		}
		else
		{
			// No previously found fabrik list
			$this->makeElementsFromFields($groupId, $tableName);
		}
	}

	/**
	 * Take a table name and make elements for all of its fields
	 *
	 * @param   int     $groupId    group id
	 * @param   string  $tableName  table name
	 *
	 * @return  void
	 */

	protected function makeElementsFromFields($groupId, $tableName)
	{
		$fabrikDb = $this->getFEModel()->getDb();
		$dispatcher = JDispatcher::getInstance();
		$app = JFactory::getApplication();
		$input = $app->input;
		$elementModel = new PlgFabrik_Element($dispatcher);
		$pluginManager = FabrikWorker::getPluginManager();
		$user = JFactory::getUser();
		$fbConfig = JComponentHelper::getParams('com_fabrik');
		$elementTypes = $input->get('elementtype', array(), 'array');
		$fields = $fabrikDb->getTableColumns($tableName, false);
		$createdate = JFactory::getDate()->toSQL();
		$key = $this->getFEModel()->getPrimaryKeyAndExtra($tableName);
		$ordering = 0;
		/**
		 * no existing fabrik table so we take a guess at the most
		 * relavent element types to  create
		 */
		$elementLabels = $input->get('elementlabels', array(), 'array');

		foreach ($fields as $label => $properties)
		{
			$plugin = 'field';
			$type = $properties->Type;
			$maxLength = 255;
			$maxLength2 = 0;

			if (preg_match("/\((.*)\)/i", $type, $matches))
			{
				$maxLength = JArrayHelper::getValue($matches, 1, 255);
				$maxLength = explode(',', $maxLength);

				if (count($maxLength) > 1)
				{
					$maxLength2 = $maxLength[1];
					$maxLength = $maxLength[0];
				}
				else
				{
					$maxLength = $maxLength[0];
					$maxLength2 = 0;
				}
			}

			// Get the basic type
			$type = explode(" ", $type);
			$type = JArrayHelper::getValue($type, 0, '');
			$type = preg_replace("/\((.*)\)/i", '', $type);
			$element = FabTable::getInstance('Element', 'FabrikTable');

			if (array_key_exists($ordering, $elementTypes))
			{
				// If importing from a CSV file then we have userselect field definitions
				$plugin = $elementTypes[$ordering];
			}
			else
			{
				// If the field is the primary key and it's an INT type set the plugin to be the fabrik internal id
				if ($key[0]['colname'] == $label && JString::strtolower(substr($key[0]['type'], 0, 3)) === 'int')
				{
					$plugin = 'internalid';
				}
				else
				{
					// Otherwise set default type
					switch ($type)
					{
						case "int":
						case "decimal":
						case "tinyint":
						case "smallint":
						case "mediumint":
						case "bigint":
						case "varchar":
						case "time":
							$plugin = 'field';
							break;
						case "text":
						case "tinytext":
						case "mediumtext":
						case "longtext":
							$plugin = 'textarea';
							break;
						case "datetime":
						case "date":
						case "timestamp":
							$plugin = 'date';
							break;
						default:
							$plugin = 'field';
							break;
					}
				}
				// Then alter if defined in Fabrik global config
				// Jaanus: but first check if there are any pk field and if yes then create as internalid
				$defType = JString::strtolower(substr($key[0]['type'], 0, 3));
				$plugin = ($key[0]['colname'] == $label && $defType === 'int') ? 'internalid' : $fbConfig->get($type, $plugin);
			}

			$element->plugin = $plugin;
			$element->hidden = $element->label == 'id' ? '1' : '0';
			$element->group_id = $groupId;
			$element->name = $label;
			$element->created = $createdate;
			$element->created_by = $user->get('id');
			$element->created_by_alias = $user->get('username');
			$element->published = '1';
			$element->show_in_list_summary = '1';

			switch ($plugin)
			{
				case 'textarea':
					$element->width = '40';
					break;
				case 'date':
					$element->width = '10';
					break;
				default:
					$element->width = '30';
					break;
			}

			if ($element->width > $maxLength)
			{
				$element->width = $maxLength;
			}

			$element->height = '6';
			$element->ordering = $ordering;
			$p = json_decode($elementModel->getDefaultAttribs());

			if (in_array($type, array('int', 'tinyint', 'smallint', 'mediumint', 'bigint')) && $plugin == 'field')
			{
				$p->integer_length = $maxLength;
				$p->text_format = 'integer';
				$p->maxlength = '255';
				$element->width = '30';
			}
			elseif ($type == 'decimal' && $plugin == 'field')
			{
				$p->text_format = 'decimal';
				$p->decimal_length = $maxLength2;
				$p->integer_length = $maxLength - $maxLength2;
				$p->maxlength = '255';
				$element->width = '30';
			}
			else
			{
				$p->maxlength = $maxLength;
			}

			$element->params = json_encode($p);
			$element->label = JArrayHelper::getValue($elementLabels, $ordering, str_replace("_", " ", $label));

			$element->store();
			$elementModel = $pluginManager->getPlugIn($element->plugin, 'element');
			$elementModel->setId($element->id);
			$elementModel->element = $element;

			// Hack for user element
			$details = array('group_id' => $element->group_id);
			$input->set('details', $details);
			$elementModel->onSave(array());
			$ordering++;
		}
	}

	/**
	 * When saving a list that links to a database for the first time we
	 * automatically create a form to allow the update/creation of that tables
	 * records
	 *
	 * @param   int  $formid  to copy from. If = 0 then create a default form. If not 0 then copy the form id passed in
	 *
	 * @return  object  form model
	 */

	private function createLinkedForm($formid = 0)
	{
		$config = JFactory::getConfig();
		$user = JFactory::getUser();
		$this->getFormModel();

		if ($formid == 0)
		{
			/**
			 * $$$ rob required otherwise the JTable is loaed with db_table_name as a property
			 * which then generates an error - not sure why its loaded like that though?
			 * 18/08/2011 - could be due to the Form table class having it in its bind method
			 * - (have now overridden form table store() to remove thoes two params)
			 */
			$this->formModel->getForm();
			jimport('joomla.utilities.date');
			$createdate = JFactory::getDate();
			$createdate = $createdate->toSql();
			$form = $this->getTable('Form');
			$item = $this->getTable('List');

			$defaults = FabrikWorker::formDefaults('form');
			$form->bind($defaults);

			$form->label = $item->label;
			$form->record_in_database = 1;
			$form->created = $createdate;
			$form->created_by = $user->get('id');
			$form->created_by_alias = $user->get('username');
			$form->error = FText::_('COM_FABRIK_FORM_ERROR_MSG_TEXT');
			$form->submit_button_label = FText::_('COM_FABRIK_SAVE');
			$form->published = $item->published;

			$version = new JVersion;
			$form->form_template = version_compare($version->RELEASE, '3.0') >= 0 ? 'bootstrap' : 'default';
			$form->view_only_template = version_compare($version->RELEASE, '3.0') >= 0 ? 'bootstrap' : 'default';

			$form->store();
			$this->setState('list.form_id', $form->id);
			$this->formModel->setId($form->id);
		}
		else
		{
			$this->setState('list.form_id', $formid);
			$this->formModel->setId($formid);
			$this->formModel->getTable();
			$this->formModel->copy();
		}

		$this->formModel->getForm();

		return $this->formModel;
	}

	/**
	 * Create a group
	 * used when creating a fabrik table from an existing db table
	 *
	 * NEW also creates the formgroup
	 *
	 * @param   array  $data      group data
	 * @param   bool   $isJoin    is the group a join default false
	 * @param   bool   $isRepeat  is the group repeating
	 *
	 * @return  int  group id
	 */

	private function createLinkedGroup($data, $isJoin = false, $isRepeat = false)
	{
		$user = JFactory::getUser();
		$createdate = JFactory::getDate();
		$group = $this->getTable('Group');
		$group->bind($data);
		$group->id = null;
		$group->created = $createdate->toSql();
		$group->created_by = $user->get('id');
		$group->created_by_alias = $user->get('username');
		$group->published = 1;
		$opts = new stdClass;
		$opts->repeat_group_button = $isRepeat ? 1 : 0;
		$opts->repeat_group_show_first = 1;
		$group->params = json_encode($opts);
		$group->is_join = ($isJoin == true) ? 1 : 0;
		$group->store();
		$group->store();

		// Create form group
		$formid = $this->getState('list.form_id');
		$formGroup = $this->getTable('FormGroup');
		$formGroup->id = null;
		$formGroup->form_id = $formid;
		$formGroup->group_id = $group->id;
		$formGroup->ordering = 999999;
		$formGroup->store();
		$formGroup->reorder(" form_id = '$formid'");

		return $group->id;
	}

	/**
	 * Test if the main J user can create mySQL tables
	 *
	 * @return  bool
	 */

	private function canCreateDbTable()
	{
		return true;
		/**
		 * @todo run create table test once when you install fabrik instead
		 * dont use method below but simply try to create a table and if you cant give error
		 * if you can remove tmp created table
		 */
		/*$db 		=& FabrikWorker::getDbo();
		$conf =& JFactory::getConfig();
		$host 		= $conf->getValue('config.host');
		$user 		= $conf->getValue('config.user');
		$db->setQuery("SELECT Create_priv FROM mysql.user WHERE (Host = '$host' OR Host = '%') AND user = '$user'");
		$res = $db->loadResult();
		if ($res == 'N' || is_null($res)) {
		return false;
		} else {
		return true;
		}*/
	}

	/**
	 * Method to copy one or more records.
	 *
	 * @return  boolean	True if successful, false if an error occurs.
	 *
	 * @since	1.6
	 */

	public function copy()
	{
		$db = FabrikWorker::getDbo(true);
		$user = JFactory::getUser();
		$app = JFactory::getApplication();
		$input = $app->input;
		$pks = $input->get('cid', array(), 'array');
		$names = $input->get('names', array(), 'array');

		foreach ($pks as $i => $pk)
		{
			$item = $this->getTable();
			$item->load($pk);
			$item->id = null;
			$input->set('newFormLabel', $names[$pk]['formLabel']);
			$input->set('newGroupNames', $names[$pk]['groupNames']);
			$formModel = $this->createLinkedForm($item->form_id);

			if (!$formModel)
			{
				return;
			}
			// $$$ rob 20/12/2011 - any element id stored in the list needs to get mapped to the new element ids

			$elementMap = $formModel->newElements;
			$params = json_decode($item->params);
			$toMaps = array(array('list_search_elements', 'search_elements'), array('csv_elements', 'show_in_csv'));

			foreach ($toMaps as $toMap)
			{
				$key = $toMap[0];
				$key2 = $toMap[1];
				$orig = json_decode($params->$key);
				$new = array();

				foreach ($orig->$key2 as $elementId)
				{
					$new[] = $elementMap[$elementId];
				}

				$c = new stdClass;
				$c->$key2 = $new;
				$params->$key = json_encode($c);
			}

			$item->form_id = $formModel->getTable()->id;
			$createdate = JFactory::getDate();
			$createdate = $createdate->toSql();
			$item->label = $names[$pk]['listLabel'];
			$item->created = $createdate;
			$item->modified = $db->getNullDate();
			$item->modified_by = $user->get('id');
			$item->hits = 0;
			$item->checked_out = 0;
			$item->checked_out_time = $db->getNullDate();
			$item->params = json_encode($params);

			if (!$item->store())
			{
				$this->setError($item->getError());

				return false;
			}

			$this->setState('list.id', $item->id);

			// Test for seeing if joins correctly stored when coping new table
			$this->copyJoins($pk, $item->id, $formModel->groupidmap);
		}

		return true;
	}

	/**
	 * When copying a table we need to copy its joins as well
	 * note that the group and elements already exists - just the join needs to be saved
	 *
	 * @param   int    $fromid      table id to copy from
	 * @param   int    $toid        table id to copy to
	 * @param   array  $groupidmap  saying which groups got copied to which new group id (key = old id, value = new id)
	 *
	 * @return null
	 */

	protected function copyJoins($fromid, $toid, $groupidmap)
	{
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('*')->from('#__{package}_joins')->where('list_id = ' . (int) $fromid);
		$db->setQuery($query);
		$joins = $db->loadObjectList();
		$feModel = $this->getFEModel();

		foreach ($joins as $join)
		{
			$size = 10;
			$els = &$feModel->getElements();

			// $$$ FIXME hugh - joined els are missing tablename
			foreach ($els as $el)
			{
				// $$$ FIXME hugh - need to make sure we pick up the element from the main table,
				// not any similarly named elements from joined tables (like 'id')
				if ($el->getElement()->name == $join->table_key)
				{
					$size = JString::stristr($el->getFieldDescription(), 'int') ? '' : '10';
				}
			}

			$feModel->addIndex($join->table_key, 'join', 'INDEX', $size);
			$joinTable = $this->getTable('Join');
			$joinTable->load($join->id);
			$joinTable->id = 0;
			$joinTable->group_id = $groupidmap[$joinTable->group_id];
			$joinTable->list_id = $toid;

			try
			{
				$joinTable->store();
			}
			catch (Exception $e)
			{
				return JError::raiseWarning(500, $e->getMessage());
			}
		}
	}

	/**
	 * Replaces the table column names with a safer name - ie removes white
	 * space and none alpha numeric characters
	 *
	 * @depreciated fabrik3.0
	 *
	 * @return  void
	 */

	private function makeSafeTableColumns()
	{
		// Going to test allowing non safe names - as they should be quoted when accessed
		return;
	}

	/**
	 * Adds a primary key to the database table
	 *
	 * @param   string  $fieldName      the column name to make into the primary key
	 * @param   bool    $autoIncrement  is the column an auto incrementing number
	 * @param   string  $type           column type definition (eg varchar(255))
	 *
	 * @return  void
	 */

	protected function updatePrimaryKey($fieldName, $autoIncrement, $type = 'int(11)')
	{
		$feModel = $this->getFEModel();
		$app = JFactory::getApplication();
		$input = $app->input;

		if (!$feModel->canAlterFields())
		{
			return;
		}

		$fabrikDatabase = $feModel->getDb();
		$jform = $input->get('jform', array(), 'array');
		$tableName = ($jform['db_table_name'] != '') ? $jform['db_table_name'] : $jform['_database_name'];
		$tableName = preg_replace('#[^0-9a-zA-Z_]#', '_', $tableName);
		$aPriKey = $feModel->getPrimaryKeyAndExtra($tableName);

		if (!$aPriKey)
		{
			// No primary key set so we should set it
			$this->addKey($fieldName, $autoIncrement, $type);
		}
		else
		{
			if (count($aPriKey) > 1)
			{
				// $$$ rob multi field pk - ignore for now

				return;
			}

			$aPriKey = $aPriKey[0];
			$shortKey = FabrikString::shortColName($fieldName);

			// $shortKey = $feModel->_shortKey($fieldName, true); // added true for second arg so it strips quotes, as was never matching colname with quotes
			if ($fieldName != $aPriKey['colname'] && $shortKey != $aPriKey['colname'])
			{
				// Primary key already exists so we should drop it
				$this->dropKey($aPriKey);
				$this->addKey($fieldName, $autoIncrement, $type);
			}
			else
			{
				// Update the key, it if we need to
				$priInc = $aPriKey['extra'] == 'auto_increment' ? '1' : '0';

				if ($priInc != $autoIncrement || $type != $aPriKey['type'])
				{
					$this->updateKey($fieldName, $autoIncrement, $type);
				}
			}
		}
	}

	/**
	 * Internal function: add a key to the table
	 *
	 * @param   string  $fieldName      primary key column name
	 * @param   bool    $autoIncrement  is the column auto incrementing
	 * @param   string  $type           the primary keys column type (if autoincrement true then int(6) is always used as the type)
	 *
	 * @return  mixed  false / JError
	 */

	private function addKey($fieldName, $autoIncrement, $type = "INT(6)")
	{
		$db = $this->getFEModel()->getDb();
		$app = JFactory::getApplication();
		$input = $app->input;
		$type = $autoIncrement != true ? $type : 'INT(6)';
		$jform = $input->get('jform', array(), 'array');
		$tableName = ($jform['db_table_name'] != '') ? $jform['db_table_name'] : $jform['_database_name'];
		$tableName = preg_replace('#[^0-9a-zA-Z_]#', '_', $tableName);
		$tableName = FabrikString::safeColName($tableName);
		$fieldName = FabrikString::shortColName($fieldName);

		if ($fieldName === "")
		{
			return false;
		}

		$fieldName = $db->quoteName($fieldName);
		$sql = 'ALTER TABLE ' . $tableName . ' ADD PRIMARY KEY (' . $fieldName . ')';

		// Add a primary key
		$db->setQuery($sql);

		if (!$db->execute())
		{
			return JError::raiseWarning(500, $db->getErrorMsg());
		}

		if ($autoIncrement)
		{
			// Add the autoinc
			$sql = 'ALTER TABLE ' . $tableName . ' CHANGE ' . $fieldName . ' ' . $fieldName . ' ' . $type . ' NOT NULL AUTO_INCREMENT';
			$db->setQuery($sql);
			$db->execute();
		}

		return true;
	}

	/**
	 * Internal function: drop the table's key
	 *
	 * @param   array  $aPriKey  existing key data
	 *
	 * @return  bool true if key droped
	 */

	private function dropKey($aPriKey)
	{
		$db = $this->getFEModel()->getDb();
		$app = JFactory::getApplication();
		$input = $app->input;
		$jform = $input->get('jform', array(), 'array');
		$tableName = FabrikString::safeColName($jform['db_table_name']);
		$sql = 'ALTER TABLE ' . $tableName . ' CHANGE ' . FabrikString::safeColName($aPriKey['colname']) . ' '
			. FabrikString::safeColName($aPriKey['colname']) . ' ' . $aPriKey['type'] . ' NOT NULL';

		// Remove the autoinc
		$db->setQuery($sql);

		if (!$db->execute())
		{
			JError::raiseWarning(500, $db->getErrorMsg());

			return false;
		}

		$sql = 'ALTER TABLE ' . $tableName . ' DROP PRIMARY KEY';

		// Drop the primary key
		$db->setQuery($sql);

		if (!$db->execute())
		{
			JError::raiseWarning(500, 'alter table: ' . $db->getErrorMsg());

			return false;
		}

		return true;
	}

	/**
	 * Internal function: update an exisitng key in the table
	 *
	 * @param   string  $fieldName      primary key column name
	 * @param   bool    $autoIncrement  is the column auto incrementing
	 * @param   string  $type           the primary keys column type
	 *
	 * @return  void
	 */

	protected function updateKey($fieldName, $autoIncrement, $type = "INT(11)")
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$jform = $input->get('jform', array(), 'array');
		$tableName = FabrikString::safeColName($jform['db_table_name']);
		$db = $this->getFEModel()->getDb();

		if (strstr($fieldName, '.'))
		{
			$fieldName = array_pop(explode(".", $fieldName));
		}

		$table = $this->getTable();
		$table->load($this->getState('list.id'));
		$sql = 'ALTER TABLE ' . $tableName . ' CHANGE ' . FabrikString::safeColName($fieldName) . ' ' . FabrikString::safeColName($fieldName) . ' '
			. $type . ' NOT NULL';

		// Update primary key
		if ($autoIncrement)
		{
			$sql .= " AUTO_INCREMENT";
		}

		$db->setQuery($sql);

		if (!$db->execute())
		{
			$this->setError('update key:' . $db->getErrorMsg());
		}
	}

	/**
	 * Translation has been turned off for the table so delete the content
	 * element xml file
	 *
	 * @return  void
	 */

	private function removeJoomfishXML()
	{
		$file = JPATH_ADMINISTRATOR . '/components/com_joomfish/contentelements/fabrik-' . $this->getTable()->db_table_name . '.xml';

		if (JFile::exists($file))
		{
			JFile::delete($file);
		}
	}

	/**
	 * Write out the Joomfish contentelement xml file for the tables elements
	 *
	 * @return  bool  true if written out ok
	 */

	private function makeJoomfishXML()
	{
		$config = JFactory::getConfig();
		$db = FabrikWorker::getDbo(true);
		$elements = $this->getElements();

		// Get all database join elements and check if we need to create xml files for them
		$table = $this->getTable();
		$tableName = str_replace($config->get('dbprefix'), '', $table->db_table_name);
		$params = $this->getParams();
		$titleElement = $params->get('joomfish-title');
		$str = '<?xml version="1.0" ?>
<joomfish type="contentelement">
  <name>Fabrik - ' . $table->label
			. '</name>
  <author>rob@pollen-8.co.uk</author>
  <version>1.0 for Fabrik 2.0</version>
  <description>Definition for Fabrik Table data - ' . $table->label . '</description>
  <reference type="content">
  	<table name="' . $tableName . '">';
		$titleset = false;

		foreach ($elements as $element)
		{
			if ($table->db_primary_key == FabrikString::safeColName($element->getFullName(false, false)))
			{
				// Primary key element
				$type = 'referenceid';
				$t = 0;
			}
			else
			{
				if (!$titleset && $titleElement == '')
				{
					$type = 'titletext';
					$titleset = true;
				}
				else
				{
					if ($titleElement == $element->getFullName(false, false))
					{
						$type = 'titletext';
						$titleset = true;
					}
					else
					{
						$type = $element->getJoomfishTranslationType();
					}
				}

				$t = $element->getJoomfishTranslatable();
			}

			$opts = $element->getJoomfishOptions();
			$el = $element->getElement();
			$str .= "\n\t\t" . '<field type="' . $type . '" name="' . $el->name . '" translate="' . $t . '"';

			foreach ($opts as $k => $v)
			{
				$str .= " $k=\"$v\"";
			}

			$str .= '>' . $el->label . '</field>';
		}

		$str .= '
  	</table>
  </reference>
</joomfish>';

		// File name HAS to be the same as the table name MINUS db extension
		return JFile::write(JPATH_ADMINISTRATOR . '/components/com_joomfish/contentelements/' . $tableName . '.xml', $str);
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param   array  &$pks  An array of record primary keys.
	 *
	 * @return  boolean	True if successful, false if an error occurs.
	 *
	 * @since	1.6
	 */

	public function delete(&$pks)
	{
		// Initialise variables.
		$dispatcher = JDispatcher::getInstance();
		$user = JFactory::getUser();
		$pks = (array) $pks;
		$table = $this->getTable();
		$app = JFactory::getApplication();

		// Include the content plugins for the on delete events.
		JPluginHelper::importPlugin('content');

		$app = JFactory::getApplication();
		$input = $app->input;
		$jform = $input->get('jform', array(), 'array');
		$deleteDepth = $jform['recordsDeleteDepth'];
		$drop = $jform['dropTablesFromDB'];

		$feModel = $this->getFEModel();
		$fabrikDatabase = $feModel->getDb();
		$dbconfigprefix = $app->getCfg('dbprefix');

		// Iterate the items to delete each one.
		foreach ($pks as $i => $pk)
		{
			$feModel->setId($pk);

			if ($table->load($pk))
			{
				$feModel->setTable($table);

				if ($drop)
				{
					if (strncasecmp($table->db_table_name, $dbconfigprefix, JString::strlen($dbconfigprefix)) == 0)
					{
						$app->enqueueMessage(JText::sprintf('COM_FABRIK_TABLE_NOT_DROPPED_PREFIX', $table->db_table_name, $dbconfigprefix), 'notice');
					}
					else
					{
						$feModel->drop();
						$app->enqueueMessage(JText::sprintf('COM_FABRIK_TABLE_DROPPED', $table->db_table_name));
					}
				}
				else
				{
					$app->enqueueMessage(JText::sprintf('COM_FABRIK_TABLE_NOT_DROPPED', $table->db_table_name));
				}

				if ($this->canDelete($table))
				{
					$context = $this->option . '.' . $this->name;

					// Trigger the onContentBeforeDelete event.
					$result = $dispatcher->trigger($this->event_before_delete, array($context, $table));

					if (in_array(false, $result, true))
					{
						$this->setError($table->getError());

						return false;
					}

					if (!$table->delete($pk))
					{
						$this->setError($table->getError());

						return false;
					}

					// Trigger the onContentAfterDelete event.
					$dispatcher->trigger($this->event_after_delete, array($context, $table));
				}
				else
				{
					// Prune items that you can't change.
					unset($pks[$i]);

					return JError::raiseWarning(403, FText::_('JLIB_APPLICATION_ERROR_EDIT_STATE_NOT_PERMITTED'));
				}

				switch ($deleteDepth)
				{
					case 0:
					default:
					// List only
						break;
					case 1:
					// List and form
						$form = $this->deleteAssociatedForm($table);
						break;
					case 2:
					// List form and groups
						$form = $this->deleteAssociatedForm($table);
						$this->deleteAssociatedGroups($form, false);
						break;
					case 3:
					// List form groups and elements
						$form = $this->deleteAssociatedForm($table);
						$this->deleteAssociatedGroups($form, true);
						break;
				}
			}
			else
			{
				$this->setError($table->getError());

				return false;
			}
		}

		return true;
	}

	/**
	 * Remove the associated form
	 *
	 * @param   object  &$item  list item
	 *
	 * @return boolean|form object
	 */

	private function deleteAssociatedForm(&$item)
	{
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$form = $this->getTable('form');
		$form->load($item->form_id);

		if ((int) $form->id === 0)
		{
			return false;
		}

		$query->delete()->from('#__{package}_forms')->where('id = ' . (int) $form->id);
		$db->setQuery($query);
		$db->execute();

		return $form;
	}

	/**
	 * Delete associated fabrik groups
	 *
	 * @param   object  &$form           item
	 * @param   bool    $deleteElements  delete group items as well
	 *
	 * @return boolean|form id
	 */

	private function deleteAssociatedGroups(&$form, $deleteElements = false)
	{
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);

		// Get group ids
		if ((int) $form->id === 0)
		{
			return false;
		}

		$query->select('group_id')->from('#__{package}_formgroup')->where('form_id = ' . (int) $form->id);
		$db->setQuery($query);
		$groupids = (array) $db->loadColumn();

		// Delete groups
		$groupModel = JModelLegacy::getInstance('Group', 'FabrikAdminModel');
		$groupModel->delete($groupids, $deleteElements);

		return $form;
	}

	/**
	 * Make a database table from  XML definition
	 *
	 * @param   string  $key   primary key
	 * @param   strng   $name  table name
	 * @param   string  $xml   xml table definition
	 *
	 * @return bool
	 */

	public function dbTableFromXML($key, $name, $xml)
	{
		$row = $xml[0];
		$data = array();

		// Get which field types to use
		foreach ($row->children() as $child)
		{
			$value = sprintf("%s", $child);
			$type = $child->attributes()->type;

			if ($type == '')
			{
				$objtype = strtotime($value) == false ? "VARCHAR(255)" : "DATETIME";

				if (strstr($value, "\n"))
				{
					$objtype = 'TEXT';
				}
			}
			else
			{
				switch (JString::strtolower($type))
				{
					case 'integer':
						$objtype = 'INT';
						break;
					case 'datetime':
						$objtype = "DATETIME";
						break;
					case 'float':
						$objtype = "DECIMAL(10,2)";
						break;
					default:
						$objtype = "VARCHAR(255)";
						break;
				}
			}

			$data[$child->getName()] = $objtype;
		}

		if (empty($data))
		{
			return false;
		}

		$db = $this->_db;
		$query = $db->getQuery();
		$query = 'CREATE TABLE IF NOT EXISTS ' . $db->quoteName($name) . ' (';

		foreach ($data as $fname => $objtype)
		{
			$query .= $db->quoteName($fname) . " $objtype, \n";
		}

		$query .= ' primary key (' . $key . '))';
		$query .= ' ENGINE = MYISAM ';
		$db->setQuery($query);
		$db->execute();

		// Get a list of existinig ids
		$query = $db->getQuery(true);
		$query->select($key)->from($name);
		$db->setQuery($query);
		$existingids = $db->loadColumn();

		// Build the row object to insert/update
		foreach ($xml as $row)
		{
			$o = new stdClass;

			foreach ($row->children() as $child)
			{
				$k = $child->getName();
				$o->$k = sprintf("%s", $child);
			}

			// Either update or add records
			if (in_array($o->$key, $existingids))
			{
				$db->updateObject($name, $o, $key);
			}
			else
			{
				$db->insertObject($name, $o, $key);
			}
		}

		return true;
	}

	/**
	 * Load list from form id
	 *
	 * @param   int  $formId  form id
	 *
	 * @return  object  JTable
	 */

	public function loadFromFormId($formId)
	{
		$item = $this->getTable();

		/**
		 * Not sure why but we need to populate and manually __state_set
		 * Otherwise list.id reverts to the form's id and not the list id
		 */
		$this->populateState();
		$this->__state_set = true;
		$item->load(array('form_id' => $formId));
		$this->table = $item;
		$this->setState('list.id', $item->id);

		return $item;
	}

	/**
	 * Load the database object associated with the list
	 *
	 * @since   3.0b
	 *
	 * @return  object database
	 */

	public function &getDb()
	{
		$listId = $this->getState('list.id');
		$item = $this->getItem($listId);

		return FabrikWorker::getConnection($item)->getDb();
	}

	/**
	 * Create a table to store the forms' data depending upon what groups are assigned to the form
	 *
	 * @param   string  $dbTableName  Taken from the table oject linked to the form
	 * @param   array   $fields       List of default elements to add. (key = element name, value = plugin
	 * @param   array   $opts         Additional options, e.g. collation
	 *
	 * @return mixed false if fail otherwise array of primary keys
	 */

	public function createDBTable($dbTableName = null, $fields = array('id' => 'internalid', 'date_time' => 'date'), $opts = array())
	{
		$db = FabrikWorker::getDbo(true);
		$fabrikDb = $this->getDb();
		$user = JFactory::getUser();
		$config = JFactory::getConfig();
		$formModel = $this->getFormModel();

		if (is_null($dbTableName))
		{
			$dbTableName = $this->getTable()->db_table_name;
		}

		$sql = 'CREATE TABLE IF NOT EXISTS ' . $db->quoteName($dbTableName) . ' (';
		$app = JFactory::getApplication();
		$input = $app->input;
		$jform = $input->get('jform', array(), 'array');

		if ($jform['id'] == 0 && array_key_exists('current_groups', $jform))
		{
			// Saving a new form
			$groupIds = $jform['current_groups'];
		}
		else
		{
			$query = $db->getQuery(true);
			$formid = (int) $this->get('form.id', $this->getFormModel()->id);
			$query->select('group_id')->from('#__{package}_formgroup')->where('form_id = ' . $formid);
			$db->setQuery($query);
			$groupIds = $db->loadColumn();
		}

		$i = 0;

		foreach ($fields as $name => $plugin)
		{
			// $$$ hugh - testing corner case where we are called from form model's updateDatabase,
			// and the underlying table has been deleted.  So elements already exist.
			$element = $formModel->getElement($name);

			if ($element === false)
			{
				// Installation demo data sets 2 groud ids
				if (is_string($plugin))
				{
					$plugin = array('plugin' => $plugin, 'group_id' => $groupIds[0]);
				}

				$plugin['ordering'] = $i;
				$element = $this->makeElement($name, $plugin);

				if (!$element)
				{
					return false;
				}
			}

			$elementModels[] = clone ($element);
			$i++;
		}

		$arAddedObj = array();
		$keys = array();
		$lines = array();

		foreach ($elementModels as $elementModel)
		{
			$element = $elementModel->getElement();

			// Replace all non alphanumeric characters with _
			$objname = FabrikString::dbFieldName($element->name);

			if ($element->primary_key)
			{
				$keys[] = $objname;
			}
			// Any elements that are names the same (eg radio buttons) can not be entered twice into the database
			if (!in_array($objname, $arAddedObj))
			{
				$arAddedObj[] = $objname;
				$objtype = $elementModel->getFieldDescription();

				if ($objname != "" && !is_null($objtype))
				{
					if (JString::stristr($objtype, 'not null'))
					{
						$lines[] = $fabrikDb->quoteName($objname) . " $objtype";
					}
					else
					{
						$lines[] = $fabrikDb->quoteName($objname) . " $objtype null";
					}
				}
			}
		}

		$func = create_function('$value', '$db = FabrikWorker::getDbo(true);;return $db->quoteName($value);');
		$sql .= implode(', ', $lines);

		if (!empty($keys))
		{
			$sql .= ', PRIMARY KEY (' . implode(',', array_map($func, $keys)) . '))';
		}
		else
		{
			$sql .= ')';
		}

		foreach ($opts as $k => $v)
		{
			if ($v != '')
			{
				$sql .= ' ' . $k . ' ' . $v;
			}
		}

		$sql .= ' ENGINE = MYISAM ';
		$fabrikDb->setQuery($sql);
		$fabrikDb->execute();

		return $keys;
	}

	/**
	 * Create an element
	 *
	 * @param   string  $name  Element name
	 * @param   array   $data  Properties
	 *
	 * @return mixed false if failed, otherwise element plugin
	 */

	public function makeElement($name, $data)
	{
		$pluginMananger = FabrikWorker::getPluginManager();
		$element = $pluginMananger->loadPlugIn($data['plugin'], 'element');
		$item = $element->getDefaultProperties();
		$item->id = null;
		$item->name = $name;
		$item->label = str_replace('_', ' ', $name);
		$item->bind($data);

		if (!$item->store())
		{
			JError::raiseWarning(500, $item->getError());

			return false;
		}

		return $element;
	}

	/**
	 * Return the default set of attributes when creating a new
	 * fabrik list
	 *
	 * @return string json enocoded Params
	 */

	public function getDefaultParams()
	{
		$a = array('advanced-filter' => 0, 'show-table-nav' => 1, 'show-table-filters' => 1, 'show-table-add' => 1, 'require-filter' => 0);
		$o = (object) $a;
		$o->admin_template = 'admin';
		$o->detaillink = 0;
		$o->empty_data_msg = 'No data found';
		$o->pdf = '';
		$o->rss = 0;
		$o->feed_title = '';
		$o->feed_date = '';
		$o->rsslimit = 150;
		$o->rsslimitmax = 2500;
		$o->csv_import_frontend = 3;
		$o->csv_export_frontend = 3;
		$o->csvfullname = 0;
		$o->access = 1;
		$o->allow_view_details = 1;
		$o->allow_edit_details = 1;
		$o->allow_add = 1;
		$o->allow_delete = 1;
		$o->group_by_order = '';
		$o->group_by_order_dir = 'ASC';
		$o->prefilter_query = '';

		return json_encode($o);
	}

	/**
	 * Alter the forms' data collection table when the forms' groups and/or
	 * elements are altered
	 *
	 * @return void|JError
	 */

	public function ammendTable()
	{
		$db = FabrikWorker::getDbo(true);
		$app = JFactory::getApplication();
		$input = $app->input;
		$query = $db->getQuery(true);
		$user = JFactory::getUser();
		$table = $this->table;
		$pluginManager = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');
		$ammend = false;
		$tableName = $table->db_table_name;
		$fabrikDb = $this->getDb();
		$columns = $fabrikDb->getTableColumns($tableName);
		$existingfields = array_keys($columns);
		$lastfield = empty($existingfields) ? '' : $existingfields[count($existingfields) - 1];
		$sql = 'ALTER TABLE ' . $db->quoteName($tableName) . ' ';
		$sqlAdd = array();

		// $$$ hugh - looks like this is now an array in jform
		$jform = $input->get('jform', array(), 'array');
		$arGroups = JArrayHelper::getValue($jform, 'current_groups', array(), 'array');

		if (empty($arGroups))
		{
			// Get a list of groups used by the form
			$query->select('group_id')->from('#__{package}_formgroup')->where('form_id = ' . (int) $this->getFormModel()->getId());
			$db->setQuery($query);
			$groups = $db->loadObjectList();
			$arGroups = array();

			foreach ($groups as $g)
			{
				$arGroups[] = $g->group_id;
			}
		}

		$arAddedObj = array();

		foreach ($arGroups as $group_id)
		{
			$group = FabTable::getInstance('Group', 'FabrikTable');
			$group->load($group_id);

			if ($group->is_join == '0')
			{
				$query->clear();
				$query->select('*')->from('#__{package}_elements')->where('group_id = ' . (int) $group_id);
				$db->setQuery($query);
				$elements = $db->loadObjectList();

				foreach ($elements as $obj)
				{
					$objname = $obj->name;

					if (!in_array($objname, $existingfields))
					{
						// Make sure that the object is not already in the table
						if (!in_array($objname, $arAddedObj))
						{
							// Any elements that are names the same (eg radio buttons) can not be entered twice into the database
							$arAddedObj[] = $objname;
							$objtypeid = $obj->plugin;
							$pluginClassName = $obj->plugin;
							$plugin = $pluginManager->getPlugIn($pluginClassName, 'element');

							if (is_object($plugin))
							{
								$plugin->setId($obj->id);
								$objtype = $plugin->getFieldDescription();
							}
							else
							{
								$objtype = 'VARCHAR(255)';
							}

							if ($objname != "" && !is_null($objtype))
							{
								$ammend = true;
								$add = "ADD COLUMN " . $db->quoteName($objname) . " $objtype null";

								if ($lastfield !== '')
								{
									$add .= " AFTER " . $db->quoteName($lastfield);
								}

								$sqlAdd[] = $add;
							}
						}
					}
				}
			}
		}

		if ($ammend)
		{
			$sql .= implode(', ', $sqlAdd);
			$fabrikDb->setQuery($sql);

			try
			{
				$fabrikDb->execute();
			}
			catch (Exception $e)
			{
				JError::raiseWarning(500, 'amend table: ' . $e->getMessage());
			}
		}
	}

	/**
	 * Gets the field names for the given table
	 * $$$ hugh - added this to backend, as I need it in some places where we have
	 * a backend list model, and until now only existed in the FE model.
	 *
	 * @param   string  $tbl       Table name
	 * @param   string  $key       Field to key return array on
	 * @param   bool    $basetype  Deprecated - not used
	 *
	 * @return  array  table fields
	 */

	public function getDBFields($tbl = null, $key = null, $basetype = false)
	{
		if (is_null($tbl))
		{
			$table = $this->getTable();
			$tbl = $table->db_table_name;
		}

		if ($tbl == '')
		{
			return array();
		}

		$sig = $tbl . $key;
		$tbl = FabrikString::safeColName($tbl);

		if (!isset($this->dbFields[$sig]))
		{
			$db = $this->getDb();
			$tbl = FabrikString::safeColName($tbl);
			$db->setQuery("DESCRIBE " . $tbl);
			$this->dbFields[$sig] = $db->loadObjectList($key);

			/**
			 * $$$ hugh - added BaseType, which strips (X) from things like INT(6) OR varchar(32)
			 * Also converts it to UPPER, just to make things a little easier.
			 */
			foreach ($this->dbFields[$sig] as &$row)
			{
				/**
				 * Boil the type down to just the base type, so "INT(11) UNSIGNED" becomes just "INT"
				 * I'm sure there's other cases than just UNSIGNED I need to deal with, but for now that's
				 * what I most care about, as this stuff is being written handle being more specific about
				 * the elements the list PK can be selected from.
				 */
				$row->BaseType = strtoupper(preg_replace('#(\(\d+\))$#', '', $row->Type));
				$row->BaseType = preg_replace('#(\s+SIGNED|\s+UNSIGNED)#', '', $row->BaseType);
			}
		}

		return $this->dbFields[$sig];
	}
}
