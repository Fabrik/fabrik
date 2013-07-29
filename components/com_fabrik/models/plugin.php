<?php
/**
 * Base Fabrik Plugin Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

// Required for fabble
require_once COM_FABRIK_FRONTEND . '/models/parent.php';

/**
 * Base Fabrik Plugin Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikPlugin extends JPlugin
{

	/**
	 * If the admin settings are visible or hidden when rendered
	 *
	 * @var bool
	 */
	var $_adminVisible = false;

	/**
	 * path to xml file
	 *
	 * @var string
	 */
	var $_xmlPath = null;

	/**
	 * Params (must be public)
	 *
	 * @var JRegistry
	 */
	public $params = null;

	var $attribs = null;

	var $_id = null;

	var $_row = null;

	/**
	 * Order that the plugin is rendered
	 *
	 * @var int
	 */
	var $renderOrder = null;

	protected $_counter;

	protected $_pluginManager = null;

	/**
	 * Form
	 *
	 * @var JForm
	 */
	public $jform = null;

	/*
	 * Plugin data
	 *
	 * @var array
	 */
	public $data = null;

	/**
	 * Set the plugin id
	 *
	 * @param   int  $id  id to use
	 *
	 * @return  void
	 */

	public function setId($id)
	{
		$this->_id = $id;
	}

	/**
	 * Get plugin id
	 *
	 * @return  int  id
	 */

	public function getId()
	{
		return $this->id;
	}

	/**
	 * Get the plugin name
	 *
	 * @return string
	 */

	function getName()
	{
		return isset($this->name) ? $this->name : get_class($this);
	}

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 */

	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	/**
	 * Get the JForm object for the plugin
	 *
	 * @return object jform
	 */

	function getJForm()
	{
		if (!isset($this->jform))
		{
			$type = str_replace('fabrik_', '', $this->_type);
			$formType = $type . '-options';
			$formName = 'com_fabrik.' . $formType;
			$controlName = 'jform';
			$this->jform = new JForm($formName, array('control' => $controlName));
		}
		return $this->jform;
	}

	/**
	 * Render the element admin settings
	 *
	 * @param   array  $data           Admin data
	 * @param   int    $repeatCounter  Repeat plugin counter
	 *
	 * @return  string	admin html
	 */

	public function onRenderAdminSettings($data = array(), $repeatCounter = null)
	{
		$document = JFactory::getDocument();
		$type = str_replace('fabrik_', '', $this->_type);
		JForm::addFormPath(JPATH_SITE . '/plugins/' . $this->_type . '/' . $this->_name);

		$xmlFile = JPATH_SITE . '/plugins/' . $this->_type . '/' . $this->_name . '/forms/fields.xml';
		$form = $this->getJForm();

		$repeatScript = '';

		// Used by fields when rendering the [x] part of their repeat name
		// see administrator/components/com_fabrik/classes/formfield.php getName()
		$form->repeatCounter = $repeatCounter;

		// Add the plugin specific fields to the form.
		$form->loadFile($xmlFile, false);

		// Copy over the data into the params array - plugin fields can have data in either
		// jform[params][name] or jform[name]
		$pluginData = array();
		if (!array_key_exists('params', $data))
		{
			$data['params'] = array();
		}
		foreach ($data as $key => $val)
		{
			if (is_object($val))
			{
				$val = isset($val->$repeatCounter) ? $val->$repeatCounter : '';
				$data['params'][$key] = $val;
			}
			else
			{
				$data['params'][$key] = is_array($val) ? JArrayHelper::getValue($val, $repeatCounter, '') : $val;
			}
		}

		// Bind the plugins data to the form
		$form->bind($data);

		// $$$ rob 27/04/2011 - listfields element needs to know things like the group_id, and
		// as bind() onlys saves the values from $data with a corresponding xml field we set the raw data as well
		$form->rawData = $data;
		$str = '';

		$repeatGroupCounter = 0;

		// Filer the forms fieldsets for those starting with the correct $serachName prefix
		foreach ($form->getFieldsets() as $fieldset)
		{
			$class = 'adminform ' . $type . 'Settings page-' . $this->_name;
			$repeat = isset($fieldset->repeatcontrols) && $fieldset->repeatcontrols == 1;

			// Bind data for repeat groups
			$repeatDataMax = 1;
			if ($repeat)
			{
				$opts = new stdClass;
				$opts->repeatmin = (isset($fieldset->repeatmin)) ? $fieldset->repeatmin : 1;
				$repeatScript[] = "new FbRepeatGroup('$fieldset->name', " . json_encode($opts) . ');';
				$repeatData = array();
				foreach ($form->getFieldset($fieldset->name) as $field)
				{
					if ($repeatDataMax < count($field->value))
					{
						$repeatDataMax = count($field->value);
					}
				}
				$form->bind($repeatData);
			}

			$id = isset($fieldset->name) ? ' id="' . $fieldset->name . '"' : '';

			$style = isset($fieldset->modal) && $fieldset->modal ? 'style="display:none"' : '';
			$str .= '<fieldset class="' . $class . '"' . $id . ' ' . $style . '>';

			$form->repeat = $repeat;
			if ($repeat)
			{
				$str .= '<a class="addButton" href="#">' . JText::_('COM_FABRIK_ADD') . '</a>';
			}
			$str .= '<legend>' . JText::_($fieldset->label) . '</legend>';
			for ($r = 0; $r < $repeatDataMax; $r++)
			{
				if ($repeat)
				{
					$str .= '<div class="repeatGroup">';
					$form->repeatCounter = $r;
				}
				$str .= '
			 <ul class="adminformlist">';
				foreach ($form->getFieldset($fieldset->name) as $field)
				{
					if ($repeat)
					{
						if (is_array($field->value))
						{
							$field->setValue($field->value[$r]);
						}
					}
					$str .= '<li>' . $field->label . $field->input . '</li>';
				}
				if ($repeat)
				{
					$str .= '<li><a class="removeButton delete" href="#">' . JText::_('COM_FABRIK_REMOVE') . '</a></li>';
				}
				$str .= '</ul>';
				if ($repeat)
				{
					$str .= "</div>";
				}
			}
			$str .= '</fieldset>';
		}
		if (!empty($repeatScript))
		{
			$repeatScript = implode("\n", $repeatScript);
			FabrikHelperHTML::script('administrator/components/com_fabrik/models/fields/repeatgroup.js', $repeatScript);
		}
		return $str;
	}

	/**
	 * Used in plugin manager runPlugins to set the correct repeat set of
	 * data for the plugin
	 *
	 * @param   object  &$params        Original params
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return   object  params
	 */

	function setParams(&$params, $repeatCounter)
	{
		$opts = $params->toArray();
		$data = array();
		foreach ($opts as $key => $val)
		{
			if (is_array($val))
			{
				$data[$key] = JArrayHelper::getValue($val, $repeatCounter);
			}
		}
		$this->_params = new JRegistry(json_encode($data));
		return $this->_params;
	}

	/**
	 * Load params
	 *
	 * @return  JRegistry  params
	 */

	public function getParams()
	{
		if (!isset($this->_params))
		{
			return $this->_loadParams();
		}
		else
		{
			return $this->_params;
		}
	}

	/**
	 * Private load params
	 *
	 * @return JRegistry
	 */

	protected function _loadParams()
	{
		if (!isset($this->attribs))
		{
			$row = $this->getRow();
			$a = $row->params;
		}
		else
		{
			$a = $this->params;
		}
		if (!isset($this->_params))
		{
			$this->_params = new JRegistry($a);
		}
		return $this->_params;
	}

	/**
	 * Get db row/item loaded with id
	 *
	 * @return  JTable
	 */

	function getRow()
	{
		if (!isset($this->_row))
		{
			$this->_row = $this->getTable($this->_type);
			$this->_row->load($this->_id);
		}
		return $this->_row;
	}

	/**
	 * Set db row/item
	 *
	 * @param   JTable  $row  db item
	 *
	 * @return  void
	 */

	function setRow($row)
	{
		$this->_row = $row;
	}

	/**
	 *  Get db row/item loaded
	 *
	 * @return  JTable
	 */

	function getTable()
	{
		return FabTable::getInstance('Extension', 'JTable');
	}

	/**
	 * Determine if we use the plugin or not
	 * both location and event criteria have to be match when form plug-in
	 *
	 * @param   object  &$model    calling the plugin table/form
	 * @param   string  $location  location to trigger plugin on
	 * @param   string  $event     event to trigger plugin on
	 *
	 * @return  bool  true if we should run the plugin otherwise false
	 */

	public function canUse(&$model = null, $location = null, $event = null)
	{
		$ok = false;
		$app = JFactory::getApplication();
		switch ($location)
		{
			case 'front':
				if (!$app->isAdmin())
				{
					$ok = true;
				}
				break;
			case 'back':
				if ($app->isAdmin())
				{
					$ok = true;
				}
				break;
			case 'both':
				$ok = true;
				break;
		}
		if ($ok)
		{
			// $$$ hugh @FIXME - added copyingRow() stuff to form model, need to do it
			// for list model as well.
			$k = array_key_exists('_origRowId', $model) ? '_origRowId' : '_rowId';
			switch ($event)
			{
				case 'new':
					if ($model->$k != 0)
					{
						$ok = isset($model->copyingRow) ? $model->copyingRow() : false;
					}
					break;
				case 'edit':
					if ($model->$k == 0)
					{
						/** $$$ hugh - don't think this is right, as it'll return true when it shouldn't.
						 * Think if this row is being copied, then by definition it's not being edited, it's new.
						 * For now, just set $ok to false;
						 * $ok = $ok = isset($model->copyingRow) ? !$model->copyingRow() : false;
						 */
						$ok = false;
					}
					break;
			}
		}
		return $ok;
	}

	/**
	 * Custom process plugin result
	 *
	 * @param   string  $method      Method
	 * @param   JModel  &$formModel  Form Model
	 *
	 * @return boolean
	 */

	public function customProcessResult($method, &$formModel)
	{
		return true;
	}

	/**
	 * J1.6 plugin wrapper for ajax_tables
	 *
	 * @return  void
	 */

	function onAjax_tables()
	{
		$this->ajax_tables();
	}

	/**
	 * Ajax function to return a string of table drop down options
	 * based on cid variable in query string
	 *
	 * @return  void
	 */

	function ajax_tables()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$cid = $input->getInt('cid', -1);
		$rows = array();
		$showFabrikLists = $input->get('showf', false);
		if ($showFabrikLists)
		{
			$db = FabrikWorker::getDbo(true);
			if ($cid !== 0)
			{
				$query = $db->getQuery(true);
				$query->select('id, label')->from('#__{package}_lists')->where('connection_id = ' . $cid)->order('label ASC');
				$db->setQuery($query);
				$rows = $db->loadObjectList();
			}
			$default = new stdClass;
			$default->id = '';
			$default->label = JText::_('COM_FABRIK_PLEASE_SELECT');
			array_unshift($rows, $default);
		}
		else
		{
			if ($cid !== 0)
			{
				$cnn = JModel::getInstance('Connection', 'FabrikFEModel');
				$cnn->setId($cid);
				$db = $cnn->getDb();
				$db->setQuery("SHOW TABLES");
				$rows = (array) $db->loadColumn();
			}
			array_unshift($rows, '');
		}
		echo json_encode($rows);
	}

	/**
	 * J1.6 plugin wrapper for ajax_fields
	 *
	 * @return  void
	 */

	function onAjax_fields()
	{
		$this->ajax_fields();
	}

	/**
	 * Get a list of fields
	 *
	 * @return  string  json encoded list of fields
	 */

	function ajax_fields()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$tid = $input->get('t');
		$keyType = $input->get('k', 1);

		// If true show all fields if false show fabrik elements
		$showAll = $input->getBool('showall', false);

		// Should we highlight the PK as a recommended option
		$highlightpk = $input->getBool('highlightpk');

		// Only used if showall = false, includes validations as separate entries
		$incCalculations = $input->get('calcs', false);
		$arr = array();
		if ($showAll)
		{
			// Show all db columns
			$cid = $input->get('cid', -1);
			$cnn = JModel::getInstance('Connection', 'FabrikFEModel');
			$cnn->setId($cid);
			$db = $cnn->getDb();
			if ($tid != '')
			{
				if (is_numeric($tid))
				{
					// If loading on a numeric list id get the list db table name
					$jDb = FabrikWorker::getDbo(true);
					$query = $jDb->getQuery(true);
					$query->select('db_table_name')->from('#__{package}_lists')->where('id = ' . (int) $tid);
					$jDb->setQuery($query);
					$tid = $jDb->loadResult();
				}
				$db->setQuery('DESCRIBE ' . $db->quoteName($tid));
				$rows = $db->loadObjectList();
				if (is_array($rows))
				{
					foreach ($rows as $r)
					{
						$c = new stdClass;
						$c->value = $r->Field;
						$c->label = $r->Field;
						if ($highlightpk && $r->Key === 'PRI')
						{
							$c->label .= ' [' . JText::_('COM_FABRIK_RECOMMENDED') . ']';
							array_unshift($arr, $c);
						}
						else
						{
							$arr[$r->Field] = $c;
						}
					}
					ksort($arr);
					$arr = array_values($arr);
				}
			}
		}
		else
		{
			/*
			 * show fabrik elements in the table
			 * $keyType 1 = $element->id;
			 * $keyType 2 = tablename___elementname
			 */
			$model = JModel::getInstance('List', 'FabrikFEModel');
			$model->setId($tid);
			$table = $model->getTable();
			$db = $model->getDb();
			$groups = $model->getFormGroupElementData();
			$published = $input->get('published', false);
			$showintable = $input->get('showintable', false);
			foreach ($groups as $g => $groupModel)
			{
				if ($groupModel->isJoin())
				{
					if ($input->get('excludejoined') == 1)
					{
						continue;
					}
					$joinModel = $groupModel->getJoinModel();
					$join = $joinModel->getJoin();
				}
				if ($published == true)
				{
					$elementModels = $groups[$g]->getPublishedElements();
				}
				else
				{
					$elementModels = $groups[$g]->getMyElements();
				}
				foreach ($elementModels as $e => $eVal)
				{
					$element = $eVal->getElement();
					if ($showintable == true && $element->show_in_list_summary == 0)
					{
						continue;
					}
					if ($keyType == 1)
					{
						$v = $element->id;
					}
					else
					{
						/*
						 * @TODO if in repeat group this is going to add [] to name - is this really
						 * what we want? In timeline viz options i've simply stripped out the [] off the end
						 * as a temp hack
						 */
						$v = $eVal->getFullName(false);
					}
					$c = new stdClass;
					$c->value = $v;
					$label = FabrikString::getShortDdLabel($element->label);
					if ($groupModel->isJoin())
					{
						$label = $join->table_join . '.' . $label;
					}
					$c->label = $label;

					// Show hightlight primary key and shift to top of options
					if ($highlightpk && $table->db_primary_key === $db->quoteName($eVal->getFullName(false, false, false)))
					{
						$c->label .= ' [' . JText::_('COM_FABRIK_RECOMMENDED') . ']';
						array_unshift($arr, $c);
					}
					else
					{
						$arr[] = $c;
					}

					if ($incCalculations)
					{
						$params = $eVal->getParams();
						if ($params->get('sum_on', 0))
						{
							$c = new stdClass;
							$c->value = 'sum___' . $v;
							$c->label = JText::_('COM_FABRIK_SUM') . ': ' . $label;
							$arr[] = $c;
						}
						if ($params->get('avg_on', 0))
						{
							$c = new stdClass;
							$c->value = 'avg___' . $v;
							$c->label = JText::_('COM_FABRIK_AVERAGE') . ': ' . $label;
							$arr[] = $c;
						}
						if ($params->get('median_on', 0))
						{
							$c = new stdClass;
							$c->value = 'med___' . $v;
							$c->label = JText::_('COM_FABRIK_MEDIAN') . ': ' . $label;
							$arr[] = $c;
						}
						if ($params->get('count_on', 0))
						{
							$c = new stdClass;
							$c->value = 'cnt___' . $v;
							$c->label = JText::_('COM_FABRIK_COUNT') . ': ' . $label;
							$arr[] = $c;
						}
						if ($params->get('custom_calc_on', 0))
						{
							$c = new stdClass;
							$c->value = 'cnt___' . $v;
							$c->label = JText::_('COM_FABRIK_CUSTOM') . ': ' . $label;
							$arr[] = $c;
						}
					}
				}
			}
		}
		array_unshift($arr, JHTML::_('select.option', '', JText::_('COM_FABRIK_PLEASE_SELECT'), 'value', 'label'));
		echo json_encode($arr);
	}

	/**
	 * Get js for managing the plugin in J admin
	 *
	 * @param   string  $name   plugin name
	 * @param   string  $label  plugin label
	 * @param   string  $html   html (not sure what this is?)
	 *
	 * @return  string  JS code to ini adminplugin class
	 */
	public function onGetAdminJs($name, $label, $html)
	{
		$opts = $this->getAdminJsOpts($html);
		$opts = json_encode($opts);
		$script = "new fabrikAdminPlugin('$name', '$label', $opts)";
		return $script;
	}

	/**
	 * Get the options to ini the J Admin js plugin controller class
	 *
	 * @param   string  $html  HTML?
	 *
	 * @return  object
	 */

	protected function getAdminJsOpts($html)
	{
		$opts = new stdClass;
		$opts->livesite = COM_FABRIK_LIVESITE;
		$opts->html = $html;
		return $opts;
	}

	/**
	 * If true then the plugin is stating that any subsequent plugin in the same group
	 * should not be run.
	 *
	 * @param   string  $method  current plug-in call method e.g. onBeforeStore
	 *
	 * @return  bool
	 */

	public function runAway($method)
	{
		return false;
	}

	/**
	 * Process the plugin, called when form is submitted
	 *
	 * @param   string             $paramName  Param name which contains the PHP code to eval
	 * @param   array              $data       Data
	 * @param   FabrikFEModelForm  $formModel  Form model
	 *
	 * @return  bool
	 */

	protected function shouldProcess($paramName, $data = null, $formModel = null)
	{
		if (is_null($data))
		{
			$data = $this->data;
		}
		$params = $this->getParams();
		$condition = $params->get($paramName);
		if (trim($condition) == '')
		{
			return true;
		}
		$w = new FabrikWorker;
		if (!is_null($formModel))
		{
			$origData = $formModel->getOrigData();
			$origData = JArrayHelper::fromObject($origData[0]);
		}
		else
		{
			$origData = array();
		}
		$condition = trim($w->parseMessageForPlaceHolder($condition, $data));
		$res = @eval($condition);
		if (is_null($res))
		{
			return true;
		}
		return $res;
	}

	/**
	 * Translates numeric entities to UTF-8
	 *
	 * @param   array  $ord  preg replace call back matched
	 *
	 * @return  string
	 */

	protected function replace_num_entity($ord)
	{
		$ord = $ord[1];
		if (preg_match('/^x([0-9a-f]+)$/i', $ord, $match))
		{
			$ord = hexdec($match[1]);
		}
		else
		{
			$ord = intval($ord);
		}
		$no_bytes = 0;
		$byte = array();
		if ($ord < 128)
		{
			return chr($ord);
		}
		elseif ($ord < 2048)
		{
			$no_bytes = 2;
		}
		elseif ($ord < 65536)
		{
			$no_bytes = 3;
		}
		elseif ($ord < 1114112)
		{
			$no_bytes = 4;
		}
		else
		{
			return;
		}
		switch ($no_bytes)
		{
			case 2:
				$prefix = array(31, 192);
				break;
			case 3:
				$prefix = array(15, 224);
				break;
			case 4:
				$prefix = array(7, 240);
				break;
		}
		for ($i = 0; $i < $no_bytes; $i++)
		{
			$byte[$no_bytes - $i - 1] = (($ord & (63 * pow(2, 6 * $i))) / pow(2, 6 * $i)) & 63 | 128;
		}
		$byte[0] = ($byte[0] & $prefix[0]) | $prefix[1];
		$ret = '';
		for ($i = 0; $i < $no_bytes; $i++)
		{
			$ret .= chr($byte[$i]);
		}
		return $ret;
	}

	/**
	 * Get the plugin manager
	 *
	 * @since 3.0
	 *
	 * @return  FabrikFEModelPluginmanager
	 */

	protected function getPluginManager()
	{
		if (!isset($this->_pluginManager))
		{
			$this->_pluginManager = JModel::getInstance('Pluginmanager', 'FabrikFEModel');
		}
		return $this->_pluginManager;
	}

	/**
	 * Get user ids from group ids
	 *
	 * @param   array   $sendTo  User group id
	 * @param   string  $field   Field to return from user group. Default = 'id'
	 *
	 * @since   3.0.7
	 *
	 * @return  array  users' property defined in $field
	 */

	protected function getUsersInGroups($sendTo, $field = 'id')
	{
		if (empty($sendTo))
		{
			return array();
		}
		$db = FabrikWorker::getDbo();
		$query = $db->getQuery(true);
		$query->select('DISTINCT(' . $field . ')')->from('#__users AS u')->join('LEFT', '#__user_usergroup_map AS m ON u.id = m.user_id')
			->where('m.group_id IN (' . implode(', ', $sendTo) . ')');
		$db->setQuery($query);
		return $db->loadColumn();
	}

	/**
	 * Make db tables if found, called from onRenderAdminSettings - seems plugins cant run their own sql files atm
	 *
	 * @since   3.1a
	 *
	 * @return  void
	 */

	protected function makeDbTable()
	{
		$db = FabrikWorker::getDbo();

		// Attempt to create the db table?
		$file = COM_FABRIK_BASE . '/plugins/' . $this->_type . '/' . $this->_name . '/sql/install.mysql.uft8.sql';
		if (JFile::exists($file))
		{
			$sql = JFile::read($file);
			$sqls = explode(";", $sql);
			if (!empty($sqls))
			{
				foreach ($sqls as $sql)
				{
					if (trim($sql) !== '')
					{
						$db->setQuery($sql);

						if (!$db->execute())
						{
							JError::raiseError(500, $db->getErrorMsg());
						}
					}
				}
			}
		}
	}
}
