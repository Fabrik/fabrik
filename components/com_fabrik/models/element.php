<?php

/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');
jimport('joomla.filesystem.file');

/**
 * Fabrik Elemenet Model
 *
 * @package  Fabrik
 * @since    3.0
 */

class plgFabrik_Element extends FabrikPlugin
{
	/** @var int element id */
	var $_id = null;

	/** @var array javascript actions to attach to element */
	var $_jsActions = null;

	/** @var object params */
	protected $_params = null;

	/** @var array validation objects associated with the element */
	var $_aValidations = null;

	/** @var bol */
	var $_editable = null;

	/** @var bol */
	protected $_is_upload = 0;

	/** @var bol */
	var $_recordInDatabase = 1;

	/** @var object to contain access rights **/
	var $_access = null;

	/**@var string validation error **/
	var $_validationErr = null;

	/** @var array stores possible element names to avoid repeat db queries **/
	var $_aFullNames = array();

	/** @var object group model*/
	var $_group = null;

	/** @var object form model*/
	var $_form = null;

	/** @var object table model*/
	protected $_list = null;

	/** @var object JTable element object */
	var $_element = null;

	/**
	* If the element 'Include in search all' option is set to 'default' then this states if the
	* element should be ignored from search all.
	* @var bool  True, ignore in advanced search all.
	*/
	protected $ignoreSearchAllDefault = false;

	/**
	 * Does the element have a label
	 * @var bool
	 */
	protected $hasLabel = true;

	/** @var bol does the element contain sub elements e.g checkboxes radiobuttons */
	var $hasSubElements = false;

	var $_imageExtensions = array('jpg', 'jpeg', 'gif', 'bmp', 'png');

	/** @var bol is the element in a detailed view? **/
	var $_inDetailedView = false;

	var $defaults = array();

	var $_HTMLids = null;

	/** @var bol is a join element */
	var $_isJoin = false;

	var $_inRepeatGroup = null;

	var $_default = null;

	/** @var object join model */
	var $_joinModel = null;

	var $iconsSet = false;

	/** @var object parent element row - if no parent returns elemnt */
	var $parent = null;

	/** @var string actual table name (table or joined tables db table name)*/
	var $actualTable = null;

	/** @var bool ensures the query values are only escaped once */
	var $escapedQueryValue = false;

	/** @var  string  db table field type */
	protected $fieldDesc = 'VARCHAR(%s)';

	/** @var  string  db table field size */
	protected $fieldSize = '255';

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 *
	 * @since       1.5
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		$this->_access = new stdClass;
	}

	/**
	 * Method to set the element id
	 *
	 * @param   int  $id  element ID number
	 *
	 * @return  void
	 */

	public function setId($id)
	{
		// Set new element ID
		$this->_id = $id;
	}

	/**
	 * Get the element id
	 *
	 * @return  int	element id
	 */

	public function getId()
	{
		return $this->_id;
	}

	/**
	 * Get the element table object
	 *
	 * @param   bool  $force  default false - force load the element
	 *
	 * @return  object  element table
	 */

	public function &getElement($force = false)
	{
		if (!$this->_element || $force)
		{
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
			$row = FabTable::getInstance('Element', 'FabrikTable');
			$row->load($this->_id);
			$this->_element = $row;
		}
		return $this->_element;
	}

	/**
	 * Get parent element
	 *
	 * @return  object  element table
	 */

	public function getParent()
	{
		if (!isset($this->parent))
		{
			$element = $this->getElement();
			if ((int) $element->parent_id !== 0)
			{
				$this->parent = FabTable::getInstance('element', 'FabrikTable');
				$this->parent->load($element->parent_id);
			}
			else
			{
				$this->parent = $element;
			}
		}
		return $this->parent;
	}

	/**
	 * Bind data to the _element variable - if possible we should run one query to get all the forms
	 * element data and then iterrate over that, creating an element plugin for each row
	 * and bind each record to that plugins _element. This is instead of using getElement() which
	 * reloads in the element increasing the number of queries run
	 *
	 * @param   mixed  &$row  (object or assoc array)
	 *
	 * @return  object  element table
	 */

	public function bindToElement(&$row)
	{
		if (!$this->_element)
		{
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
			$this->_element = FabTable::getInstance('Element', 'FabrikTable');
		}
		if (is_object($row))
		{
			$row = JArrayHelper::fromObject($row);
		}
		$this->_element->bind($row);
		return $this->_element;
	}

	/**
	 * Set the context in which the element occurs
	 *
	 * @param   object  &$groupModel  group model
	 * @param   object  &$formModel   form model
	 * @param   object  &$listModel   list model
	 *
	 * @return  void
	 */

	public function setContext(&$groupModel, &$formModel, &$listModel)
	{
		// Dont assign these with &= as they already are when passed into the func
		$this->_group = $groupModel;
		$this->_form = $formModel;
		$this->_list = $listModel;
	}

	/**
	 * Get the element's fabrik list model
	 *
	 * @return  object	list model
	 */

	public function getListModel()
	{
		if (is_null($this->_list))
		{
			$groupModel = $this->getGroup();
			$this->_list = $groupModel->getListModel();
		}
		return $this->_list;
	}

	/**
	 * load in the group model
	 *
	 * @param   int  $groupId  group id
	 *
	 * @return  object	group model
	 */

	public function &getGroup($groupId = null)
	{
		if (is_null($groupId))
		{
			$element = $this->getElement();
			$groupId = $element->group_id;
		}
		if (is_null($this->_group) || $this->_group->getId() != $groupId)
		{
			$model = JModel::getInstance('Group', 'FabrikFEModel');
			$model->setId($groupId);
			$model->getGroup();
			$this->_group = $model;
		}
		return $this->_group;
	}

	/**
	 * Get the elements group model
	 *
	 * @param   int  $group_id  If not set uses elements default group id
	 *
	 * @return  object  group model
	 */

	public function getGroupModel($group_id = null)
	{
		return $this->getGroup($group_id);
	}

	/**
	 * Set the group model
	 *
	 * @param   object  $group  group model
	 *
	 * @since 3.0.6
	 *
	 * @return  null
	 */

	public function setGroupModel($group)
	{
		$this->_group = $group;
	}

	/**
	 * get the elements form model
	 *
	 * @deprecated use getFormModel
	 *
	 * @return  object	form model
	 */

	public function getForm()
	{
		return $this->getFormModel();
	}

	/**
	 * get the element's form model
	 *
	 * @return  object  form model
	 */

	public function getFormModel()
	{
		if (is_null($this->_form))
		{
			$listModel = $this->getListModel();
			$table = $listModel->getTable();
			$this->_form = JModel::getInstance('form', 'FabrikFEModel');
			$this->_form->setId($table->form_id);
			$this->_form->getForm();
		}
		return $this->_form;

	}

	/**
	 * Set form model
	 *
	 * @param   object  $model  form model
	 *
	 * @return  void
	 */

	public function setFormModel($model)
	{
		$this->form = $model;
	}

	/**
	 * Shows the RAW list data - can be overwritten in plugin class
	 *
	 * @param   string  $data     element data
	 * @param   object  $thisRow  all the data in the tables current row
	 *
	 * @return  string	formatted value
	 */

	public function renderRawListData($data, $thisRow)
	{
		return $data;
	}

	/**
	 * Replace labels shown in table view with icons (if found)
	 *
	 * @param   string  $data  data
	 * @param   string  $view  list/details
	 * @param   string  $tmpl  template
	 *
	 * @since 3.0 - icon_folder is a bool - search through template folders for icons
	 *
	 * @return  string	data
	 */

	protected function _replaceWithIcons($data, $view = 'list', $tmpl = null)
	{
		if ($data == '')
		{
			$this->iconsSet = false;
			return $data;
		}
		$params = $this->getParams();
		if ((int) $params->get('icon_folder', 0) === 0)
		{
			$this->iconsSet = false;
			return $data;
		}
		$listModel = $this->getListModel();
		if (in_array($listModel->getOutPutFormat(), array('csv', 'rss')))
		{
			$this->iconsSet = false;
			return $data;
		}

		/**
		 * Jaanus added this and following if/else; sometimes we need permanent image
		 * (e.g logo of the website where the link always points, like Wikipedia's W)
		 */
		$iconfile = $params->get('icon_file', '');

		$cleanData = $iconfile === '' ? FabrikString::clean(strip_tags($data)) : $iconfile;
		foreach ($this->_imageExtensions as $ex)
		{
			$f = JPath::clean($cleanData . '.' . $ex);
			$img = FabrikHelperHTML::image($cleanData . '.' . $ex, $view, $tmpl);
			if ($img !== '')
			{
				$this->iconsSet = true;
				$opts = new stdClass;
				$opts->position = 'top';
				$opts = json_encode($opts);
				$data = '<span>' . $data . '</span>';
				if ($params->get('icon_hovertext', true))
				{
					$data = htmlspecialchars($data, ENT_QUOTES);
					$img = '<a class="fabrikTip" href="#" opts=\'' . $opts . '\' title="' . $data . '">' . $img . '</a>';
				}
				elseif (!empty($iconfile))
				{
					/**
					 * $$$ hugh - kind of a hack, but ... if this is an upload element, it may already be a link, and
					 * we'll need to replace the text in the link with the image
					 * After ages dicking around with a regex to do this, decided to use DOMDocument instead!
					 */
					$html = new DOMDocument;
					$html->loadXML($data);
					$as = $html->getElementsBytagName('a');
					if ($as->length)
					{
						$img = $html->createElement('img');
						$img->setAttribute('src', FabrikHelperHTML::image($cleanData . '.' . $ex, $view, $tmpl, array(), true));
						$as->item(0)->nodeValue = '';
						$as->item(0)->appendChild($img);
						return $html->saveHTML();
					}
				}
				return $img;
			}
		}
		$this->iconsSet = false;
		return $data;
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
		$jointable = $this->getJoinModel()->getJoin()->table_join;
		$dbtable = $this->actualTableName();
		$db = JFactory::getDbo();
		$table = $this->getListModel()->getTable();
		$fullElName = $db->quoteName($jointable . '___' . $this->_element->name);
		$sql = '(SELECT GROUP_CONCAT(' . $jkey . ' SEPARATOR \'' . GROUPSPLITTER . '\') FROM ' . $jointable . ' WHERE parent_id = '
			. $table->db_primary_key . ')';
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
		$jointable = $this->getJoinModel()->getJoin()->table_join;
		$dbtable = $this->actualTableName();
		$db = JFactory::getDbo();
		$table = $this->getListModel()->getTable();
		$fullElName = $db->quoteName($jointable . '___' . $this->_element->name . '_raw');
		return '(SELECT GROUP_CONCAT(id SEPARATOR \'' . GROUPSPLITTER . '\') FROM ' . $jointable . ' WHERE parent_id = ' . $table->db_primary_key
			. ') AS ' . $fullElName;
	}

	/**
	 * Used in form model setJoinData.
	 *
	 * @since 2.1.1
	 *
	 * @return  array	element names to search data in to create join data array
	 */

	public function getJoinDataNames()
	{
		$group = $this->getGroup()->getGroup();
		$name = $this->getFullName(false, true, false);
		$fv_name = 'join[' . $group->join_id . '][' . $name . ']';
		$rawname = $name . '_raw';
		$fv_rawname = 'join[' . $group->join_id . '][' . $rawname . ']';
		return array(array($name, $fv_name), array($rawname, $fv_rawname));
	}

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
		$dbtable = $this->actualTableName();
		$db = FabrikWorker::getDbo();
		$table = $this->getListModel()->getTable();
		$fullElName = JArrayHelper::getValue($opts, 'alias', $db->quoteName($dbtable . '___' . $this->_element->name));
		$k = $db->quoteName($dbtable) . '.' . $db->quoteName($this->_element->name);
		$secret = JFactory::getConfig()->getValue('secret');
		if ($this->encryptMe())
		{
			$k = 'AES_DECRYPT(' . $k . ', ' . $db->quote($secret) . ')';
		}
		if ($this->isJoin())
		{
			$jkey = $this->_element->name;
			if ($this->encryptMe())
			{
				$jkey = 'AES_DECRYPT(' . $jkey . ', ' . $db->quote($secret) . ')';
			}
			$jointable = $this->getJoinModel()->getJoin()->table_join;
			$fullElName = JArrayHelper::getValue($opts, 'alias', $db->quoteName($jointable . '___' . $this->_element->name));
			$str = $this->buildQueryElementConcat($jkey);
		}
		else
		{
			$str = $k . ' AS ' . $fullElName;
		}
		if ($table->db_primary_key == $fullElName)
		{
			array_unshift($aFields, $fullElName);
			array_unshift($aAsFields, $fullElName);
		}
		else
		{
			if (!in_array($str, $aFields))
			{
				$aFields[] = $str;
				$aAsFields[] = $fullElName;
			}
			$k = $db->quoteName($dbtable) . '.' . $db->quoteName($this->_element->name);
			if ($this->encryptMe())
			{
				$k = 'AES_DECRYPT(' . $k . ', ' . $db->quote($secret) . ')';
			}
			if ($this->isJoin())
			{
				$str = $this->buildQueryElementConcatId();
				$aFields[] = $str;
				$aAsFields[] = $fullElName;
				$fullElName = $db->quoteName($jointable . '___params');
				$str = '(SELECT GROUP_CONCAT(params SEPARATOR \'' . GROUPSPLITTER . '\') FROM ' . $jointable . ' WHERE parent_id = '
					. $table->db_primary_key . ') AS ' . $fullElName;
				$aFields[] = $str;
				$aAsFields[] = $fullElName;
			}
			else
			{
				$fullElName = $db->quoteName($dbtable . '___' . $this->_element->name . '_raw');
				$str = $k . ' AS ' . $fullElName;
			}
			if (!in_array($str, $aFields))
			{
				$aFields[] = $str;
				$aAsFields[] = $fullElName;
			}
		}
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
		$n = $this->getFullName(false, $useStep, false);
		$n .= '_raw`';
		return $n;
	}

	/**
	 * Check user can view the read only element & view in list view
	 *
	 * @return  bool  can view or not
	 */

	public function canView()
	{
		if (!is_object($this->_access) || !array_key_exists('view', $this->_access))
		{
			$user = JFactory::getUser();
			$groups = $user->authorisedLevels();
			$this->_access->view = in_array($this->getParams()->get('view_access'), $groups);
		}
		return $this->_access->view;
	}

	/**
	 * Check if the user can use the active element
	 *
	 * @param   object  &$model    calling the plugin list/form
	 * @param   string  $location  to trigger plugin on
	 * @param   string  $event     to trigger plugin on
	 *
	 * @return  bool can use or not
	 */

	public function canUse(&$model = null, $location = null, $event = null)
	{
		$element = $this->getElement();
		if (!is_object($this->_access) || !array_key_exists('use', $this->_access))
		{
			// $$$ hugh - testing new "Option 5" for group show, "Always show read only"
			// So if element's group show is type 5, then element is de-facto read only.
			if ($this->getGroup()->getParams()->get('repeat_group_show_first', '1') == '5')
			{
				$this->_access->use = false;
			}
			else {
				$user = JFactory::getUser();
				$groups = $user->getAuthorisedViewLevels();
				$this->_access->use = in_array($this->getElement()->access, $groups);
			}
		}
		return $this->_access->use;
	}

	/**
	 * Defines if the user can use the filter related to the element
	 *
	 * @return  bool	true if you can use
	 */

	public function canUseFilter()
	{
		$params = $this->getParams();
		$element = $this->getElement();
		if (!is_object($this->_access) || !array_key_exists('filter', $this->_access))
		{
			$user = JFactory::getUser();
			$groups = $user->authorisedLevels();
			// $$$ hugh - fix for where certain elements got created with 0 as the
			// the default for filter_access, which isn't a legal value, should be 1
			$filter_access = $this->getParams()->get('filter_access');
			$filter_access = $filter_access == '0' ? '1' : $filter_access;
			$this->_access->filter = in_array($filter_access, $groups);
		}
		return $this->_access->filter;
	}

	/**
	 * Set/get if element should record its data in the dabase
	 *
	 * @deprecated - not used
	 *
	 * @return bool
	 */

	public function setIsRecordedInDatabase()
	{
		return true;
	}

	/**
	 * Internal element validation
	 *
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  repeeat group counter
	 *
	 * @return bool
	 */

	public function validate($data, $repeatCounter = 0)
	{
		return true;
	}

	/**
	 * Get validation error - run through JText
	 *
	 * @return  string
	 */

	public function getValidationErr()
	{
		return JText::_($this->_validationErr);
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
		return ($data == '') ? true : false;
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
		$ar = array('id' => $id, 'triggerEvent' => 'blur');
		return array($ar);
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
		if (is_array($val) && count($val) === 1)
		{
			$val = array_shift($val);
		}
		if (is_array($val) || is_object($val))
		{
			return json_encode($val);
		}
		else
		{
			return $val;
		}
	}

	/**
	 * When importing csv data you can run this function on all the data to
	 * format it into the format that the form would have submitted the date
	 *
	 * @param   array   &$data  to prepare
	 * @param   string  $key    list column heading
	 * @param   bool    $isRaw  data is raw
	 *
	 * @return  array  data
	 */

	public function prepareCSVData(&$data, $key, $isRaw = false)
	{
		return $data;
	}

	/**
	 * Determines if the data in the form element is used when updating a record
	 *
	 * @param   mixed  $val  element forrm data
	 *
	 * @return  bool  true if ignored on update, default = false
	 */

	public function ignoreOnUpdate($val)
	{
		return false;
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
		return false;
	}

	/**
	 * can be overwritten in adddon class
	 *
	 * checks the posted form data against elements INTERNAL validataion rule - e.g. file upload size / type
	 *
	 * @param   array   $aErrors      existing errors
	 * @param   object  &$groupModel  group model
	 * @param   object  &$formModel   form model
	 * @param   array   $data         posted data
	 *
	 * @deprecated - not used
	 *
	 * @return  array	updated errors
	 */

	public function validateData($aErrors, &$groupModel, &$formModel, $data)
	{
		return $aErrors;
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
		return $this->getValue($data, $repeatCounter, $opts);
	}

	/**
	 * This really does get just the default value (as defined in the element's settings)
	 *
	 * @param   array  $data  form data
	 *
	 * @return mixed
	 */

	public function getDefaultValue($data = array())
	{
		if (!isset($this->_default))
		{
			$w = new FabrikWorker;
			$element = $this->getElement();
			$default = $w->parseMessageForPlaceHolder($element->default, $data);
			if ($element->eval == "1")
			{
				FabrikHelperHTML::debug($default, 'element eval default:' . $element->label);
				$default = @eval(stripslashes($default));
				FabrikWorker::logEval($default, 'Caught exception on eval of ' . $element->name . ': %s');
			}
			$this->_default = $default;
		}
		return $this->_default;
	}

	/**
	 * Called by form model to build an array of values to encrypt
	 *
	 * @param   array  &$values  previously encrypted values
	 * @param   array  $data     form data
	 * @param   int    $c        repeat group counter
	 *
	 * @return  void
	 */

	public function getValuesToEncrypt(&$values, $data, $c)
	{
		$name = $this->getFullName(false, true, false);
		$group = $this->getGroup();
		if ($group->canRepeat())
		{
			if (!array_key_exists($name, $values))
			{
				$values[$name]['data'] = array();
			}
			$values[$name]['data'][$c] = $this->getValue($data, $c);
		}
		else
		{
			$values[$name]['data'] = $this->getValue($data, $c);
		}
	}

	/**
	 * Element plugin specific method for setting unecrypted values baack into post data
	 *
	 * @param   array   &$post  data passed by ref
	 * @param   string  $key    key
	 * @param   string  $data   elements unencrypted data
	 *
	 * @return  void
	 */

	public function setValuesFromEncryt(&$post, $key, $data)
	{
		$group = $this->getGroup();
		if ($group->isJoin())
		{
			$jkey = 'join.' . $group->getGroup()->join_id . '.' . $key;
			FArrayHelper::setValue($post, $jkey, $data);
			FArrayHelper::setValue($_REQUEST, $jkey, $data);

			// Seems the only way to add it into $post? FArrayHelper bug I guess but too scared to alter that at the moement
			$post['join'][$group->getGroup()->join_id][$key] = $data;
		}
		else
		{
			FArrayHelper::setValue($post, $key, $data);
			FArrayHelper::setValue($_REQUEST, $key, $data);
		}
		// $$$rob even though $post is passed by reference - by adding in the value
		// we arent actually modifiying the $_POST var that post was created from
		JRequest::setVar($key, $data);
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
		return $this->getValue($data, $repeatCounter);
	}

	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  when repeating joinded groups we need to know what part of the array to access
	 * @param   array  $opts           options
	 *
	 * @return  string	value
	 */

	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		// @TODO rename $this->defaults to $this->values
		if (!isset($this->defaults))
		{
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults))
		{
			$groupModel = $this->getGroup();
			$group = $groupModel->getGroup();
			$joinid = $this->isJoin() ? $this->getJoinModel()->getJoin()->id : $group->join_id;
			$formModel = $this->getFormModel();
			$element = $this->getElement();
			/**
			 * $$$rob - if no search form data submitted for the search element then the default
			 * selection was being applied instead
			 * otherwise get the default value so if we don't find the element's value in $data we fall back on this value
			 */
			$value = JArrayHelper::getValue($opts, 'use_default', true) == false ? '' : $this->getDefaultValue($data);

			$name = $this->getFullName(false, true, false);
			$rawname = $name . '_raw';
			if ($groupModel->isJoin() || $this->isJoin())
			{
				$nameKey = 'join.' . $joinid . '.' . $name;
				$rawNameKey = 'join.' . $joinid . '.' . $rawname;

				// $$$ rob 22/02/2011 this test barfed on fileuploads which weren't repeating
				// if ($groupModel->canRepeat() || !$this->isJoin()) {
				if ($groupModel->canRepeat())
				{
					$v = FArrayHelper::getNestedValue($data, $nameKey . '.' . $repeatCounter, null);
					if (is_null($v))
					{
						$v = FArrayHelper::getNestedValue($data, $rawNameKey . '.' . $repeatCounter, null);
					}
					if (!is_null($v))
					{
						$value = $v;
					}
				}
				else
				{
					$v = FArrayHelper::getNestedValue($data, $nameKey, null);
					if (is_null($v))
					{
						$v = FArrayHelper::getNestedValue($data, $rawNameKey, null);
					}
					if (!is_null($v))
					{
						$value = $v;
					}
					/* $$$ rob if you have 2 tbl joins, one repeating and one not
					 * the none repeating one's values will be an array of duplicate values
					 * but we only want the first value
					 */

					if (is_array($value) && !$this->isJoin())
					{
						$value = array_shift($value);
					}
				}
			}
			else
			{
				if ($groupModel->canRepeat())
				{
					// Repeat group NO join
					$thisname = $name;
					if (!array_key_exists($name, $data))
					{
						$thisname = $rawname;
					}
					if (array_key_exists($thisname, $data))
					{
						if (is_array($data[$thisname]))
						{
							// Occurs on form submission for fields at least
							$a = $data[$thisname];
						}
						else
						{
							// Occurs when getting from the db
							$a = json_decode($data[$thisname]);
						}
						$value = JArrayHelper::getValue($a, $repeatCounter, $value);
					}

				}
				else
				{
					$value = !is_array($data) ? $data : JArrayHelper::getValue($data, $name, JArrayHelper::getValue($data, $rawname, $value));
				}
			}
			if (is_array($value) && !$this->isJoin())
			{
				$value = implode(',', $value);
			}
			if ($value === '' && !$groupModel->canRepeat())
			{
				// Query string for joined data
				$value = JArrayHelper::getValue($data, $name);
			}
			if (is_array($value) && !$this->isJoin())
			{
				$value = implode(',', $value);
			}
			/*@TODO perhaps we should change this to $element->value and store $element->default as the actual default value
			 *stops this getting called from form validation code as it messes up repeated/join group validations
			 */
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1)
			{
				FabrikWorker::getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
			}
			$this->defaults[$repeatCounter] = $value;
		}
		return $this->defaults[$repeatCounter];
	}

	/**
	 * Is the element hidden or not - if not set then return false
	 *
	 * @return  bool
	 */

	protected function isHidden()
	{
		$element = $this->getElement();
		return ($element->hidden == true) ? true : false;
	}

	/**
	 * Used in things like date when its id is suffixed with _cal
	 * called from getLabel();
	 *
	 * @param   string  &$id  initial id
	 *
	 * @return  void
	 */

	protected function modHTMLId(&$id)
	{
	}

	/**
	 * Should the element be tipped?
	 *
	 * @param   string  $mode  form/list render context
	 *
	 * @since	3.0.6
	 *
	 * @return  bool
	 */

	private function isTipped($mode = 'form')
	{
		$formModel = $this->getFormModel();
		if ($formModel->getParams()->get('tiplocation', 'tip') !== 'tip' && $mode === 'form')
		{
			return false;
		}
		$params = $this->getParams();
		if ($params->get('rollover', '') === '')
		{
			return false;
		}
		if ($mode == 'form' && (!$formModel->_editable && $params->get('labelindetails', true) == false))
		{
			return false;
		}
		if ($mode === 'list' && $params->get('labelinlist', false) == false)
		{
			return false;
		}
		return true;
	}

	/**
	 * Get the element's HTML label
	 *
	 * @param   int     $repeatCounter  group repeat counter
	 * @param   string  $tmpl           form template
	 *
	 * @return  string  label
	 */

	public function getLabel($repeatCounter, $tmpl = '')
	{
		$config = JComponentHelper::getParams('com_fabrik');
		$bLabel = $config->get('fbConf_wysiwyg_label', false) ? false : $this->get('hasLabel');
		$element = $this->getElement();
		$elementHTMLId = $this->getHTMLId($repeatCounter);
		$this->modHTMLId($elementHTMLId);
		$view = JRequest::getVar('view', 'form');
		if ($view == 'form' && !($this->canUse() || $this->canView()))
		{
			return '';
		}
		if ($view == 'details' && !$this->canView())
		{
			return '';
		}
		$params = $this->getParams();
		$elementid = 'fb_el_' . $elementHTMLId;
		$str = '';
		if ($this->canView() || $this->canUse())
		{
			$rollOver = $this->isTipped();
			$labelClass = 'fabrikLabel ';
			if (empty($element->label))
			{
				$labelClass .= ' fabrikEmptyLabel';
			}
			if ($rollOver)
			{
				$labelClass .= ' fabrikHover';
			}
			if ($bLabel && !$this->isHidden())
			{
				$str .= '<label for="' . $elementHTMLId . '" class="' . $labelClass . '">';
			}
			elseif (!$bLabel && !$this->isHidden())
			{
				$str .= '<span class="' . $labelClass . ' faux-label">';
			}
			$l = $element->label;
			if ($rollOver)
			{
				$l .= FabrikHelperHTML::image('questionmark.png', 'form', $tmpl);
			}
			if ($this->_editable)
			{
				$validations = array_unique($this->getValidations());
				if (count($validations) > 0)
				{
					$validationHovers = array('<div><ul class="validation-notices" style="list-style:none">');
					foreach ($validations as $pluginc => $validation)
					{
						$validationHovers[] = '<li>' . $validation->getHoverText($this, $pluginc, $tmpl) . '</li>';
					}
					$validationHovers[] = '</ul></div>';
					$title = implode('', $validationHovers);
					$opts = new stdClass;
					$opts->position = 'top';
					$opts = json_encode($opts);
					$l .= FabrikHelperHTML::image('notempty.png', 'form', $tmpl, array('class' => 'fabrikTip', 'opts' => $opts, 'title' => $title));
				}
			}
			$model = $this->getFormModel();
			$str .= $this->rollover($l, $model->_data);
			if ($bLabel && !$this->isHidden())
			{
				$str .= '</label>';
			}
			elseif (!$bLabel && !$this->isHidden())
			{
				$str .= '</span>';
			}
		}
		return $str;
	}

	/**
	 * set fabrikErrorMessage div with potential error messages
	 *
	 * @param   int     $repeatCounter  repeat counter
	 * @param   string  $tmpl           template
	 *
	 * @return  string
	 */

	protected function addErrorHTML($repeatCounter, $tmpl = '')
	{
		$err = $this->_getErrorMsg($repeatCounter);
		$str = '<span class="fabrikErrorMessage">';
		if ($err !== '')
		{
			$err = '<span>' . $err . '</span>';
			$str .= '<a href="#" class="fabrikTip" title="' . $err . '" opts="{notice:true}">' . FabrikHelperHTML::image('alert.png', 'form', $tmpl)
				. '</a>';
		}
		$str .= '</span>';
		return $str;
	}

	/**
	 * Add tips on element labels
	 * does ACL check on element's label in details setting
	 *
	 * @param   string  $txt   label
	 * @param   array   $data  row data
	 * @param   string  $mode  form/list render context
	 *
	 * @return  string  label with tip
	 */

	protected function rollover($txt, $data = array(), $mode = 'form')
	{
		if (is_object($data))
		{
			$data = JArrayHelper::fromObject($data);
		}
		if ($this->isTipped($mode))
		{
			$params = $this->getParams();
			$formModel = $this->getFormModel();
			$rollOver = $this->getTip($data);
			$pos = $params->get('tiplocation', 'top');
			$opts = "{position:'$pos', notice:true}";
			if ($rollOver == '')
			{
				return $txt;
			}
			$rollOver = '<span>' . $rollOver . '</span>';

			// $$$ rob - looks like htmlspecialchars is needed otherwise invalid markup created and pdf output issues.
			$rollOver = htmlspecialchars($rollOver, ENT_QUOTES);
			return '<span class="fabrikTip" opts="' . $opts . '" title="' . $rollOver . '">' . $txt . '</span>';
		}
		else
		{
			return $txt;
		}
	}

	/**
	 * Get the element tip HTML
	 *
	 * @param   array  $data  to use in parse holders - defaults to form's data
	 *
	 * @return  string  tip HTML
	 */

	protected function getTip($data = null)
	{
		if (is_null($data))
		{
			$data = $this->getFormModel()->_data;
		}
		$params = $this->getParams();
		$w = new FabrikWorker;
		$tip = $w->parseMessageForPlaceHolder($params->get('rollover'), $data);
		if ($params->get('tipseval'))
		{
			$tip = @eval($tip);
			FabrikWorker::logEval($tip, 'Caught exception on eval of ' . $this->getElement()->name . ' tip: %s');
		}
		$tip = trim(JText::_($tip));
		$tip = JText::_($tip);
		$tip = htmlspecialchars($tip, ENT_QUOTES);
		return $tip;
	}

	/**
	 * Used for the name of the filter fields
	 * For element this is an alias of getFullName()
	 * Overridden currently only in databasejoin class
	 *
	 * @return  string	element filter name
	 */

	public function getFilterFullName()
	{
		return FabrikString::safeColName($this->getFullName(false, true, false));
	}

	/**
	 * Get the field name to use in the list's slug url
	 *
	 * @param   bool  $raw  raw
	 *
	 * @since   3.0.6
	 *
	 * @return  string  element slug name
	 */

	public function getSlugName($raw = false)
	{
		return $this->getFilterFullName();
	}

	/**
	 * If already run then stored value returned
	 *
	 * @param   bool  $includeJoinString  add join[joinid][] to element name (default true)
	 * @param   bool  $useStep            concat name with form's step element (true) or with '.' (false) default true
	 * @param   bool  $incRepeatGroup     include '[]' at the end of the name (used for repeat group elements) default true
	 *
	 * @return  string  element full name
	 */

	public function getFullName($includeJoinString = true, $useStep = true, $incRepeatGroup = true)
	{
		$db = FabrikWorker::getDbo();
		$groupModel = $this->getGroup();
		$formModel = $this->getFormModel();
		$listModel = $this->getListModel();
		$element = $this->getElement();

		$key = $element->name . $groupModel->get('id') . '_' . $formModel->getId() . '_' . $includeJoinString . '_' . $useStep . '_'
			. $incRepeatGroup;
		if (isset($this->_aFullNames[$key]))
		{
			return $this->_aFullNames[$key];
		}
		$table = $listModel->getTable();
		$db_table_name = $table->db_table_name;

		$thisStep = ($useStep) ? $formModel->joinTableElementStep : '.';
		$group = $groupModel->getGroup();
		if ($groupModel->isJoin() || $this->isJoin())
		{
			$joinModel = $this->isJoin() ? $this->getJoinModel() : $groupModel->getJoinModel();
			$join = $joinModel->getJoin();
			if ($includeJoinString)
			{
				$fullName = 'join[' . $join->id . '][' . $join->table_join . $thisStep . $element->name . ']';
			}
			else
			{
				$fullName = $join->table_join . $thisStep . $element->name;
			}
		}
		else
		{
			$fullName = $db_table_name . $thisStep . $element->name;
		}
		if ($groupModel->canRepeat() == 1 && $incRepeatGroup)
		{
			$fullName .= '[]';
		}
		$this->_aFullNames[$key] = $fullName;
		return $fullName;
	}

	/**
	 * Get order by full name
	 *
	 * @param   bool  $includeJoinString  add join[joinid][] to element name (default true)
	 * @param   bool  $useStep            concat name with form's step element (true) or with '.' (false) default true
	 *
	 * @return  string  oder by full name
	 */

	public function getOrderbyFullName($includeJoinString = true, $useStep = true)
	{
		return $this->getFullName($includeJoinString, $useStep);
	}

	/**
	 * Get a hidden field
	 *
	 * @param   string  $name   name
	 * @param   string  $value  value
	 * @param   string  $id     HTML id
	 * @param   string  $class  class name
	 *
	 * @return  void
	 */

	protected function getHiddenField($name, $value, $id = '', $class = '')
	{
		if ($id != '')
		{
			$id = 'id="' . $id . '"';
		}
		if ($class !== '')
		{
			$class = 'class="' . $class . '"';
		}
		$str = '<input type="hidden" name="' . $name . '" ' . $id . ' value="' . $value . '" ' . $class . ' />' . "\n";
		return $str;
	}

	/**
	 * When copying elements from an existing table
	 * once a copy of all elements has been made run them through this method
	 * to ensure that things like watched element id's are updated
	 *
	 * @param   array  $newElements  copied element ids (keyed on original element id)
	 *
	 * @return  voidfunction check(
	 */

	public function finalCopyCheck($newElements)
	{
		// Overwritten in element class
	}

	/**
	 * Copy an element table row
	 *
	 * @param   int     $id       element id to copy
	 * @param   string  $copytxt  feedback msg
	 * @param   int     $groupid  group model id
	 * @param   string  $name     new element name
	 *
	 * @return  mixed	error or new row
	 */

	public function copyRow($id, $copytxt = 'Copy of %s', $groupid = null, $name = null)
	{
		$app = JFactory::getApplication();
		$rule = FabTable::getInstance('Element', 'FabrikTable');
		if ($rule->load((int) $id))
		{
			$rule->id = null;
			$rule->label = sprintf($copytxt, $rule->label);
			if (!is_null($groupid))
			{
				$rule->group_id = $groupid;
			}
			if (!is_null($name))
			{
				$rule->name = $name;
			}
			$groupModel = JModel::getInstance('Group', 'FabrikFEModel');
			$groupModel->setId($groupid);
			$groupListModel = $groupModel->getListModel();

			// $$$ rob - if its a joined group then it can have the same element names
			if ((int) $groupModel->getGroup()->is_join === 0)
			{
				if ($groupListModel->fieldExists($rule->name))
				{
					return JError::raiseWarning(500, JText::_('COM_FABRIK_ELEMENT_NAME_IN_USE'));
				}
			}
			$date = JFactory::getDate();
			$tz = new DateTimeZone($app->getCfg('offset'));
			$date->setTimezone($tz);
			$rule->created = $date->toSql();
			$params = $rule->params == '' ? new stdClass : json_decode($rule->params);
			$params->parent_linked = 1;
			$rule->params = json_encode($params);
			$rule->parent_id = $id;
			if (!$rule->store())
			{
				return JError::raiseWarning($rule->getError());
			}
		}
		else
		{
			return JError::raiseWarning(500, $rule->getError());
		}

		/**
		 * I thought we did this in an overridden element model method, like onCopy?
		 * if its a database join then add in a new join record
		 */
		if (is_a($this, 'plgFabrik_ElementDatabasejoin'))
		{
			$join = FabTable::getInstance('Join', 'FabrikTable');
			$join->load(array('element_id' => $id));
			$join->id = null;
			$join->element_id = $rule->id;
			$join->group_id = $rule->group_id;
			$join->store();
		}

		// Copy js events
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('id')->from('#__{package}_jsactions')->where('element_id = ' . (int) $id);
		$db->setQuery($query);
		$actions = $db->loadColumn();
		foreach ($actions as $id)
		{
			$jscode = FabTable::getInstance('Jsaction', 'FabrikTable');
			$jscode->load($id);
			$jscode->id = 0;
			$jscode->element_id = $rule->id;
			$jscode->store();
		}
		return $rule;
	}

	/**
	 * This was in the views display and _getElement code but seeing as its used
	 * by multiple views its safer to have it here
	 *
	 * @param   int     $c        repeat group counter
	 * @param   int     $elCount  order in which the element is shown in the form
	 * @param   string  $tmpl     template
	 *
	 * @return  mixed	- false if you shouldnt continue to render the element
	 */

	public function preRender($c, $elCount, $tmpl)
	{
		$model = $this->getFormModel();
		$groupModel = $this->getGroup();
		if (!$this->canUse() && !$this->canView())
		{
			return false;
		}
		if (!$this->canUse())
		{
			$this->_editable = false;
		}
		else
		{
			$this->_editable = ($model->_editable) ? true : false;
		}
		$params = $this->getParams();

		// Force reload?
		$this->_HTMLids = null;
		$elementTable = $this->getElement();
		$element = new stdClass;
		$element->startRow = false;
		$element->endRow = false;
		$elHTMLName = $this->getFullName(true, true);

		// If the element is in a join AND is the join's foreign key then we don't show the element
		if ($elementTable->name == $this->_foreignKey)
		{
			$element->label = '';
			$element->error = '';
			$this->_element->hidden = true;
		}
		else
		{
			$element->error = $this->_getErrorMsg($c);
		}

		$element->plugin = $elementTable->plugin;
		$element->hidden = $this->isHidden();
		$element->id = $this->getHTMLId($c);
		$element->className = 'fb_el_' . $element->id;
		$element->containerClass = $this->containerClass($element);
		$element->element = $this->_getElement($model->_data, $c);
		if ($params->get('tipsoverelement', false))
		{
			$element->element = $this->rollover($element->element, $model->_data);
		}
		$element->label_raw = $this->_element->label;

		// GetLabel needs to know if the element is editable
		if ($elementTable->name != $this->_foreignKey)
		{
			$l = $this->getLabel($c, $tmpl);
			$w = new FabrikWorker;
			$element->label = $w->parseMessageForPlaceHolder($l, $model->_data);
		}
		$element->errorTag = $this->addErrorHTML($c, $tmpl);
		$element->element_ro = $this->_getROElement($model->_data, $c);
		$element->value = $this->getValue($model->_data, $c);

		if (array_key_exists($elHTMLName . '_raw', $model->_data))
		{
			$element->element_raw = $model->_data[$elHTMLName . '_raw'];
		}
		else
		{
			if (array_key_exists($elHTMLName, $model->_data))
			{
				$element->element_raw = $model->_data[$elHTMLName];
			}
			else
			{

				$element->element_raw = $element->value;
			}
		}
		if ($this->dataConsideredEmpty($element->element_ro, $c))
		{
			$element->containerClass .= ' fabrikDataEmpty';
		}

		// Tips (if not rendered as hovers)
		$tip = $this->getTip();
		if ($tip !== '')
		{
			$tip = '<div class="fabrikInlineTip">' . FabrikHelperHTML::image('questionmark.png', 'form', $tmpl) . $tip . '</div>';
		}
		switch ($model->getParams()->get('tiplocation'))
		{
			default:
			case 'tip':
				$element->tipAbove = '';
				$element->tipBelow = '';
				$element->tipSide = '';
				break;
			case 'above':
				$element->tipAbove = $tip;
				$element->tipBelow = '';
				$element->tipSide = '';
				break;
			case 'below':
				$element->tipAbove = '';
				$element->tipBelow = $tip;
				$element->tipSide = '';
				break;
			case 'side':
				$element->tipAbove = '';
				$element->tipBelow = '';
				$element->tipSide = $tip;
				break;
		}
		return $element;
	}

	/**
	 * Get the class name for the element wrapping dom object
	 *
	 * @param   object  $element  element row
	 *
	 * @since   3.0
	 *
	 * @return  string	class names
	 */

	protected function containerClass($element)
	{
		$item = $this->getElement();
		$c = array('fabrikElementContainer', $item->plugin);
		if ($element->hidden)
		{
			$c[] = 'fabrikHide';
		}
		if ($element->error != '')
		{
			$c[] = 'fabrikError';
		}
		return implode(' ', $c);
	}

	/**
	 * Merge the rendered element into the views element storage arrays
	 *
	 * @param   object  $element             to merge
	 * @param   array   &$aElements          element array
	 * @param   array   &$namedData          element HTML names
	 * @param   array   &$aSubGroupElements  sub group element array
	 *
	 * @return  void
	 */

	public function stockResults($element, &$aElements, &$namedData, &$aSubGroupElements)
	{
		$elHTMLName = $this->getFullName(true, true);
		$aElements[$this->getElement()->name] = $element;
		$namedData[$elHTMLName] = $element;
		if ($elHTMLName)
		{
			// $$$ rob was key'd on int but thats not very useful for templating
			$aSubGroupElements[$this->getElement()->name] = $element;
		}
	}

	/**
	 * Pre-render just the element (no labels etc)
	 * Was _getElement but this was ambiguous with getElement() and method is public
	 *
	 * @param   array  $data           data
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string
	 */

	protected function _getElement($data, $repeatCounter = 0)
	{
		$groupModel = $this->getGroupModel();
		if (!$this->canView() && !$this->canUse())
		{
			return '';
		}
		// Used for working out if the element should behave as if it was in a new form (joined grouped) even when editing a record
		$this->_inRepeatGroup = $groupModel->canRepeat();
		$this->_inJoin = $groupModel->isJoin();
		$opts = array('runplugins' => 1);
		$this->getValue($data, $repeatCounter, $opts);
		if ($this->_editable)
		{
			return $this->render($data, $repeatCounter);
		}
		else
		{
			$htmlid = $this->getHTMLId($repeatCounter);

			// $$$ rob even when not in ajax mode the element update() method may be called in which case we need the span
			// $$$ rob changed from span wrapper to div wrapper as element's content may contain divs which give html error

			// Placeholder to be updated by ajax code
			return '<div id="' . $htmlid . '">' . $this->_getROElement($data, $repeatCounter) . '</div>';
		}
	}

	/**
	 * Get read-only element
	 * Was _getROElement() but is a public method
	 *
	 * @param   array  $data           data
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string
	 */

	public function _getROElement($data, $repeatCounter = 0)
	{
		$groupModel = $this->getGroup();
		if (!$this->canView() && !$this->canUse())
		{
			return '';
		}
		$this->_editable = false;
		$v = $this->render($data, $repeatCounter);
		$this->addCustomLink($v, $data, $repeatCounter);
		return $v;
	}

	/**
	 * Add custom link to element - must be uneditable for link to be added
	 *
	 * @param   string  &$v             value
	 * @param   array   $data           row data
	 * @param   int     $repeatCounter  repeat counter
	 *
	 * @return  string
	 */

	protected function addCustomLink(&$v, $data, $repeatCounter = 0)
	{
		if ($this->_editable)
		{
			return $v;
		}
		$params = $this->getParams();
		$customLink = $params->get('custom_link');
		if ($customLink !== '' && $this->getElement()->link_to_detail == '1' && $params->get('custom_link_indetails', true))
		{
			$w = new FabrikWorker;
			$repData = array();
			/*
			 * merge join data down for current repetCounter so that parseing repeat joined data
			 * only inserts current record
			 */
			foreach ($data as $k => $val)
			{
				if ($k == 'join')
				{
					foreach ($val as $joindata)
					{
						foreach ($joindata as $k2 => $val2)
						{
							$repData[$k2] = JArrayHelper::getValue($val2, $repeatCounter);
						}
					}
				}
				else
				{
					$repData[$k] = $val;
				}
			}
			$customLink = $w->parseMessageForPlaceHolder($customLink, $repData);
			$customLink = $this->getListModel()->parseMessageForRowHolder($customLink, $repData);
			$v = '<a href="' . $customLink . '">' . $v . '</a>';
		}
		return $v;
	}

	/**
	 * Get any html error messages
	 *
	 * @param   int  $repeatCount  group repeat count
	 *
	 * @return  string	error messages
	 */

	protected function _getErrorMsg($repeatCount = 0)
	{
		$arErrors = $this->getFormModel()->_arErrors;
		$parsed_name = $this->getFullName(true, true);
		$err_msg = '';
		$parsed_name = FabrikString::rtrimword($parsed_name, '[]');
		if (isset($arErrors[$parsed_name]))
		{
			if (array_key_exists($repeatCount, $arErrors[$parsed_name]))
			{
				if (is_array($arErrors[$parsed_name][$repeatCount]))
				{
					$err_msg = implode('<br />', $arErrors[$parsed_name][$repeatCount]);
				}
				else
				{
					$err_msg .= $arErrors[$parsed_name][$repeatCount];
				}
			}
		}
		return $err_msg;
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
		return 'need to overwrite in element plugin class';
	}

	/**
	 * Helper method to build an input field
	 *
	 * @param   string  $node  input type default 'input'
	 * @param   array   $bits  input property => value
	 *
	 * @return  string  input
	 */

	protected function buildInput($node = 'input', $bits = array())
	{
		$str = '<' . $node . ' ';
		foreach ($bits as $key => $val)
		{
			$str .= $key . ' = "' . $val . '" ';
		}
		$str .= '/>';
		return $str;
	}

	/**
	 * Helper function to build the property array used in buildInput()
	 *
	 * @param   int    $repeatCounter  repeat group counter
	 * @param   mixed  $type           null/string $type property (if null then password/text applied as default)
	 *
	 * @return  array  input properties key/value
	 */

	protected function inputProperties($repeatCounter, $type = null)
	{
		$bits = array();
		$element = $this->getElement();
		$params = $this->getParams();
		$size = $element->width;
		if (!isset($type))
		{
			$type = $params->get('password') == "1" ? 'password' : 'text';
		}
		$maxlength = $params->get('maxlength');
		if ($maxlength == "0" or $maxlength == '')
		{
			$maxlength = $size;
		}
		$class = '';
		if (isset($this->_elementError) && $this->_elementError != '')
		{
			$class .= ' elementErrorHighlight';
		}
		if ($element->hidden == '1')
		{
			$class .= ' hidden';
			$type = 'hidden';
		}
		$bits['type'] = $type;
		$bits['id'] = $this->getHTMLId($repeatCounter);
		$bits['name'] = $this->getHTMLName($repeatCounter);
		$bits['size'] = $size;
		$bits['maxlength'] = $maxlength;
		$bits['class'] = "fabrikinput inputbox $class";
		if ($params->get('placeholder', '') !== '')
		{
			$bits['placeholder'] = $params->get('placeholder');
		}
		if ($params->get('autocomplete', 1) == 0)
		{
			$bits['autocomplete'] = 'off';
		}
		// Cant be used with hidden element types
		if ($element->hidden != '1')
		{
			if ($params->get('readonly'))
			{
				$bits['readonly'] = "readonly";
				$bits['class'] .= " readonly";
			}
			if ($params->get('disable'))
			{
				$bits['class'] .= " disabled";
				$bits['disabled'] = 'disabled';
			}
		}
		return $bits;
	}

	/**
	 * get the id used in the html element
	 *
	 * @param   int  $repeatCounter  group counter
	 *
	 * @return  string
	 */

	public function getHTMLId($repeatCounter = 0)
	{
		if (!is_array($this->_HTMLids))
		{
			$this->_HTMLids = array();
		}
		if (!array_key_exists((int) $repeatCounter, $this->_HTMLids))
		{
			$groupModel = $this->getGroup();
			$listModel = $this->getListModel();
			$table = $listModel->getTable();
			$groupTable = $groupModel->getGroup();
			$element = $this->getElement();
			if ($groupModel->isJoin() || $this->isJoin())
			{
				$joinModel = $this->isJoin() ? $this->getJoinModel() : $groupModel->getJoinModel();
				$joinTable = $joinModel->getJoin();
				$fullName = 'join___' . $joinTable->id . '___' . $joinTable->table_join . '___' . $element->name;
			}
			else
			{
				$fullName = $table->db_table_name . '___' . $element->name;
			}

			// Change the id for detailed view elements
			if ($this->_inDetailedView)
			{
				$fullName .= '_ro';
			}
			if ($groupModel->canRepeat())
			{
				$fullName .= '_' . $repeatCounter;
			}
			$this->_HTMLids[$repeatCounter] = $fullName;
		}
		return $this->_HTMLids[$repeatCounter];
	}

	/**
	 * get the element html name
	 *
	 * @param   int  $repeatCounter  group counter
	 *
	 * @return  string
	 */

	public function getHTMLName($repeatCounter = 0)
	{
		$groupModel = $this->getGroup();
		$params = $this->getParams();
		$table = $this->getListModel()->getTable();
		$group = $groupModel->getGroup();
		$element = $this->getElement();
		if ($groupModel->isJoin() || $this->isJoin())
		{
			$joinModel = $this->isJoin() ? $this->getJoinModel() : $groupModel->getJoinModel();
			$joinTable = $joinModel->getJoin();
			$fullName = 'join[' . $joinTable->id . '][' . $joinTable->table_join . '___' . $element->name . ']';
		}
		else
		{
			$fullName = $table->db_table_name . '___' . $element->name;
		}
		if ($groupModel->canRepeat())
		{
			// $$$ rob - always use repeatCounter in html names - avoids ajax post issues with mootools1.1
			$fullName .= '[' . $repeatCounter . ']';
		}
		if ($this->hasSubElements)
		{
			$fullName .= '[]';
		}
		// @TODO: check this - repeated elements do need to have something applied to thier id based on their order in the repeated groups

		$this->_elementHTMLName = $fullName;
		return $this->_elementHTMLName;
	}

	/**
	 * Load element params
	 *
	 * @return  object  default element params
	 */

	public function getParams()
	{
		if (!isset($this->_params))
		{
			$this->_params = new JRegistry($this->getElement()->params);
		}
		return $this->_params;
	}

	/**
	 * Not used
	 *
	 * @deprecated
	 *
	 * @return  mixed
	 */

	public function getPluginParams()
	{
		if (!isset($this->_pluginParams))
		{
			$this->_pluginParams = $this->loadPluginParams();
		}
		return $this->_pluginParams;
	}

	/**
	 * Load plugin params
	 *
	 * @deprecated
	 *
	 * @return  object  params
	 */

	protected function loadPluginParams()
	{
		if (isset($this->_xmlPath))
		{
			$element = $this->getElement();
			$pluginParams = new JRegistry($element->params);
			return $pluginParams;
		}
		return false;
	}

	/**
	 * Loads in elements validation objects
	 *
	 * @return  array	validation objects
	 */

	public function getValidations()
	{
		if (isset($this->_aValidations))
		{
			return $this->_aValidations;
		}
		$element = $this->getElement();
		$params = $this->getParams();
		$validations = (array) $params->get('validations', 'array');
		$usedPlugins = JArrayHelper::getValue($validations, 'plugin', array());
		$pluginManager = FabrikWorker::getPluginManager();
		$pluginManager->getPlugInGroup('validationrule');
		$c = 0;
		$this->_aValidations = array();

		$dispatcher = JDispatcher::getInstance();
		$ok = JPluginHelper::importPlugin('fabrik_validationrule');
		foreach ($usedPlugins as $usedPlugin)
		{
			if ($usedPlugin !== '')
			{
				$class = 'plgFabrik_Validationrule' . JString::ucfirst($usedPlugin);
				$conf = array();
				$conf['name'] = JString::strtolower($usedPlugin);
				$conf['type'] = JString::strtolower('fabrik_Validationrule');
				$plugIn = new $class($dispatcher, $conf);
				$oPlugin = JPluginHelper::getPlugin('fabrik_validationrule', $usedPlugin);
				$plugIn->elementModel = $this;
				$this->_aValidations[] = $plugIn;
				$c++;
			}
		}
		return $this->_aValidations;
	}

	/**
	 * get javasscript actions
	 *
	 * @deprecated ?
	 *
	 * @return  array  js actions
	 */

	public function getJSActions()
	{
		if (!isset($this->_jsActions))
		{
			$query = $this->_db->getQuery();
			$query->select('*')->from('#__{package}_jsactions')->where('element_id = ' . (int) $this->_id);
			$this->_db->setQuery($query);
			$this->_jsActions = $this->_db->loadObjectList();
		}
		return $this->_jsActions;
	}

	/**
	 *create the js code to observe the elements js actions
	 *
	 * @param   string  $jsControllerKey  either form_ or _details
	 * @param   int     $repeatCount      counter
	 *
	 * @return  string	js events
	 */

	public function getFormattedJSActions($jsControllerKey, $repeatCount)
	{
		$jsStr = '';
		$allJsActions = $this->getFormModel()->getJsActions();
		/**
		 * $$$ hugh - only needed getParent when we weren't saving changes to parent params to child
		 * which we should now be doing ... and getParent() causes an extra table lookup for every child
		 * element on the form.
		 * $element = $this->getParent();
		 */
		$jsControllerKey = "Fabrik.blocks['" . $jsControllerKey . "']";
		$element = $this->getElement();
		$form = $this->_form->getForm();
		$w = new FabrikWorker;
		if (array_key_exists($element->id, $allJsActions))
		{
			$fxadded = array();
			$elId = $this->getHTMLId($repeatCount);
			foreach ($allJsActions[$element->id] as $jsAct)
			{
				$js = addslashes($jsAct->code);
				$js = str_replace(array("\n", "\r"), "", $js);
				if ($jsAct->action == 'load')
				{
					$js = preg_replace('#\bthis\b#', "\$(\\'$elId\\')", $js);
				}
				if ($jsAct->action != '' && $js !== '')
				{
					$jsStr .= $jsControllerKey . ".dispatchEvent('$element->plugin', '$elId', '$jsAct->action', '$js');\n";
				}

				// Build wysiwyg code
				if (isset($jsAct->js_e_event) && $jsAct->js_e_event != '')
				{
					// $$$ rob get the correct element id based on the repeat counter
					$triggerEl = $this->getFormModel()->getElement(str_replace('fabrik_trigger_element_', '', $jsAct->js_e_trigger));
					if (is_object($triggerEl))
					{
						$triggerid = 'element_' . $triggerEl->getHTMLId($repeatCount);
					}
					else
					{
						$triggerid = $jsAct->js_e_trigger;
					}
					if (!array_key_exists($jsAct->js_e_trigger, $fxadded))
					{
						$jsStr .= $jsControllerKey . ".addElementFX('$triggerid', '$jsAct->js_e_event');\n";
						$fxadded[$jsAct->js_e_trigger] = true;
					}
					$jsAct->js_e_value = $w->parseMessageForPlaceHolder($jsAct->js_e_value, JRequest::get('post'));
					$js = "if (this.get('value') $jsAct->js_e_condition '$jsAct->js_e_value') {";

					// $$$ need to use ciorrected triggerid here as well
					// $js .= $jsControllerKey . ".doElementFX('$jsAct->js_e_trigger', '$jsAct->js_e_event')";
					if (preg_match('#^fabrik_trigger#', $triggerid))
					{
						$js .= $jsControllerKey . ".doElementFX('" . $triggerid . "', '$jsAct->js_e_event')";
					}
					else
					{
						$js .= $jsControllerKey . ".doElementFX('fabrik_trigger_" . $triggerid . "', '$jsAct->js_e_event')";
					}
					$js .= "}";
					$js = addslashes($js);
					$js = str_replace(array("\n", "\r"), "", $js);
					$jsStr .= $jsControllerKey . ".dispatchEvent('$element->plugin', '$elId', '$jsAct->action', '$js');\n";
				}
			}
		}
		return $jsStr;
	}

	/**
	 * get the default value for the list filter
	 *
	 * @param   bool  $normal   is the filter a normal or advanced filter
	 * @param   int   $counter  filter order
	 *
	 * @return  string
	 */

	protected function getDefaultFilterVal($normal = true, $counter = 0)
	{
		$app = JFactory::getApplication();
		$listModel = $this->getListModel();
		$filters = $listModel->getFilterArray();

		// $$$ rob test for db join fields
		$elName = $this->getFilterFullName();
		$elid = $this->getElement()->id;
		$data = JRequest::get('request');
		$groupModel = $this->getGroup();
		$group = $groupModel->getGroup();

		// See if the data is in the request array - can use tablename___elementname=filterval in query string
		if ($groupModel->isJoin())
		{
			if (array_key_exists('join', $data) && array_key_exists($group->join_id, $data['join']))
			{
				$data = $data['join'][$group->join_id];
			}
		}
		$default = '';
		if (array_key_exists($elName, $data))
		{
			if (is_array($data[$elName]))
			{
				$default = @$data[$elName]['value'];
			}
		}
		$context = 'com_fabrik.list' . $listModel->getRenderContext() . '.filter.' . $elid;
		$context .= $normal ? '.normal' : '.advanced';

		// We didnt find anything - lets check the filters
		if ($default == '')
		{
			if (empty($filters))
			{
				return '';
			}
			if (array_key_exists('elementid', $filters))
			{
				/**
				 * $$$ hugh - if we have one or more pre-filters on the same element that has a normal filter,
				 * the following line doesn't work. So in 'normal' mode we need to get all the keys,
				 * and find the 'normal' one.
				 * $k = $normal == true ? array_search($elid, $filters['elementid']) : $counter;
				 */
				$k = false;
				if ($normal)
				{
					$keys = array_keys($filters['elementid'], $elid);
					foreach ($keys as $key)
					{
						/**
						 * $$$ rob 05/09/2011 - just testing for 'normal' is not enough as there are several search_types - ie I've added a test for
						 * Querystring filters as without that the search values were not being shown in ranged filter fields
						 */
						if (in_array($filters['search_type'][$key], array('normal', 'querystring', 'jpluginfilters')))
						{
							$k = $key;
							continue;
						}
					}
				}
				else
				{
					$k = $counter;
				}
				// Is there a filter with this elements name
				if ($k !== false)
				{
					/**
					 * if its a search all filter dont use its value.
					 * if we did the next time the filter form is submitted its value is turned
					 * from a search all filter into an element filter
					 */
					$searchType = JArrayHelper::getValue($filters['search_type'], $k);
					if (!is_null($searchType) && $searchType != 'searchall')
					{
						if ($searchType != 'prefilter')
						{
							$default = $filters['origvalue'][$k];
						}
					}
				}
			}
		}

		$default = $app->getUserStateFromRequest($context, $elid, $default);
		if ($this->getElement()->filter_type !== 'range')
		{
			$default = (is_array($default) && array_key_exists('value', $default)) ? $default['value'] : $default;
			if (is_array($default))
			{
				// Wierd thing on meow where when you first load the task list the id element had a date range filter applied to it????
				$default = '';
			}
			$default = stripslashes($default);
		}
		return $default;
	}

	/**
	 * if the search value isnt what is stored in the database, but rather what the user
	 * sees then switch from the search string to the db value here
	 * overwritten in things like checkbox and radio plugins
	 *
	 * @param   string  $value  filterVal
	 *
	 * @return  string
	 */

	protected function prepareFilterVal($value)
	{
		return $value;
	}

	/**
	 * Get the filter name
	 *
	 * @param   int   $counter  filter order
	 * @param   bool  $normal   do we render as a normal filter or as an advanced search filter
	 *
	 * @return  string
	 */

	protected function filterName($counter = 0, $normal = true)
	{
		$listModel = $this->getListModel();
		$v = 'fabrik___filter[list_' . $listModel->getRenderContext() . '][value]';
		$v .= ($normal) ? '[' . $counter . ']' : '[]';
		return $v;
	}

	/**
	 * Get the table filter for the element
	 *
	 * @param   int   $counter  filter order
	 * @param   bool  $normal   do we render as a normal filter or as an advanced search filter
	 * if normal include the hidden fields as well (default true, use false for advanced filter rendering)
	 *
	 * @return  string	filter html
	 */

	public function getFilter($counter = 0, $normal = true)
	{
		$listModel = $this->getListModel();
		$formModel = $listModel->getFormModel();
		$dbElName = $this->getFullName(false, false, false);
		if (!$formModel->hasElement($dbElName))
		{
			return '';
		}
		$table = $listModel->getTable();
		$element = $this->getElement();
		$elName = $this->getFullName(false, true, false);
		$id = $this->getHTMLId() . 'value';
		$v = $this->filterName($counter, $normal);

		// Corect default got
		$default = $this->getDefaultFilterVal($normal, $counter);
		$return = array();

		if (in_array($element->filter_type, array('range', 'dropdown')))
		{
			$rows = $this->filterValueList($normal);
			$this->unmergeFilterSplits($rows);
			array_unshift($rows, JHTML::_('select.option', '', $this->filterSelectLabel()));
		}
		$size = (int) $this->getParams()->get('filter_length', 20);
		switch ($element->filter_type)
		{
			case "range":
				$attribs = 'class="inputbox fabrik_filter" size="1" ';
				$default1 = is_array($default) ? $default['value'][0] : '';
				$return[] = JText::_('COM_FABRIK_BETWEEN')
					. JHTML::_('select.genericlist', $rows, $v . '[]', $attribs, 'value', 'text', $default1, $element->name . "_filter_range_0");
				$default1 = is_array($default) ? $default['value'][1] : '';
				$return[] = '<br /> ' . JText::_('COM_FABRIK_AND') . ' '
					. JHTML::_('select.genericlist', $rows, $v . '[]', $attribs, 'value', 'text', $default1, $element->name . "_filter_range_1");
				break;

			case "dropdown":
				$return[] = JHTML::_('select.genericlist', $rows, $v, 'class="inputbox fabrik_filter" size="1" ', 'value', 'text', $default, $id);
				break;

			case "field":
			default:
			// $$$ rob - if searching on "O'Fallon" from querystring filter the string has slashes added regardless
				$default = stripslashes($default);
				$default = htmlspecialchars($default);
				$return[] = '<input type="text" name="' . $v . '" class="inputbox fabrik_filter" size="' . $size . '" value="' . $default . '" id="'
					. $id . '" />';
				break;

			case "hidden":
				$default = stripslashes($default);
				$default = htmlspecialchars($default);
				$return[] = '<input type="hidden" name="' . $v . '" class="inputbox fabrik_filter" value="' . $default . '" id="' . $id . '" />';
				break;

			case "auto-complete":
				$default = stripslashes($default);
				$default = htmlspecialchars($default);

				/**
				 * $$$ rob 28/10/2011 using selector rather than element id so we can have n modules with the same filters
				 * showing and not produce invald html & duplicate js calls
				 */
				$return[] = '<input type="hidden" name="' . $v . '" class="inputbox fabrik_filter ' . $id . '" value="' . $default . '" />';
				$return[] = '<input type="text" name="' . $v . '-auto-complete" class="inputbox fabrik_filter autocomplete-trigger ' . $id
					. '-auto-complete" size="' . $size . '" value="' . $default . '" />';
				$selector = '#listform_' . $listModel->getRenderContext() . ' .' . $id;
				FabrikHelperHTML::autoComplete($selector, $this->getElement()->id);
				break;
		}
		$return[] = $normal ? $this->getFilterHiddenFields($counter, $elName) : $this->getAdvancedFilterHiddenFields();
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
		return $params->get('filter_required') == 1 ? JText::_('COM_FABRIK_PLEASE_SELECT') : JText::_('COM_FABRIK_FILTER_PLEASE_SELECT');
	}

	/**
	 * Checks if filter option values are in json format
	 * if so explode those values into new options
	 *
	 * @param   array  &$rows  filter options
	 *
	 * @return null
	 */

	protected function unmergeFilterSplits(&$rows)
	{
		/*
		 * takes rows which may be in format :
		 *
		 * [0] => stdClass Object
		(
		[text] => ["1"]
		[value] => ["1"]
		)
		and converts them into
		[0] => JObject Object
		(
		[_errors:protected] => Array
		(
		)
		[value] => 1
		[text] => 1
		[disable] =>
		)
		 */
		$allvalues = array();
		foreach ($rows as $row)
		{
			$allvalues[] = $row->value;
		}
		$c = count($rows) - 1;
		for ($j = $c; $j >= 0; $j--)
		{
			$vals = FabrikWorker::JSONtoData($rows[$j]->value, true);
			$txt = FabrikWorker::JSONtoData($rows[$j]->text, true);
			if (is_array($vals))
			{
				$found = false;
				for ($i = 0; $i < count($vals); $i++)
				{
					$vals2 = FabrikWorker::JSONtoData($vals[$i], true);
					$txt2 = FabrikWorker::JSONtoData(JArrayHelper::getValue($txt, $i), true);
					for ($jj = 0; $jj < count($vals2); $jj++)
					{
						if (!in_array($vals2[$jj], $allvalues))
						{
							$found = true;
							$allvalues[] = $vals2[$jj];
							$rows[] = JHTML::_('select.option', $vals2[$jj], $txt2[$jj]);
						}
					}
				}
				if ($found)
				{
					// $$$ rob 01/08/2011 - caused empty list in advanced search on dropdown element
					unset($rows[$j]);
				}
			}
			if (count($vals) > 1)
			{
				unset($rows[$j]);
			}
		}
	}

	/**
	 * Run after unmergeFilterSplits to ensure filter dropdown labels are correct
	 *
	 * @param   array  &$rows  filter options
	 *
	 * @return  null
	 */

	protected function reapplyFilterLabels(&$rows)
	{
		$element = $this->getElement();
		$values = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();
		foreach ($rows as &$row)
		{
			$k = array_search($row->value, $values);
			if ($k !== false)
			{
				$row->text = $labels[$k];
			}
		}
		$rows = array_values($rows);
	}

	/**
	 * Get sub option values
	 *
	 * @return  array
	 */

	protected function getSubOptionValues()
	{
		$params = $this->getParams();
		$opts = $params->get('sub_options', '');
		return $opts == '' ? array() : (array) @$opts->sub_values;
	}

	/**
	 * Get sub option labels
	 *
	 * @return  array
	 */

	protected function getSubOptionLabels()
	{
		$params = $this->getParams();
		$opts = $params->get('sub_options', '');
		return $opts == '' ? array() : (array) @$opts->sub_labels;
	}

	/**
	 * Get the radio buttons possible values
	 * needed for inline edit list plugin
	 *
	 * @return  array  of radio button values
	 */

	public function getOptionValues()
	{
		return $this->getSubOptionValues();
	}

	/**
	 * get the radio buttons possible labels
	 * needed for inline edit list plugin
	 *
	 * @return  array  of radio button labels
	 */

	protected function getOptionLabels()
	{
		return $this->getSubOptionLabels();
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
		if ($filter_build == 2 && $this->hasSubElements)
		{
			return $this->filterValueList_All($normal, $tableName, $label, $id, $incjoin);
		}
		else
		{
			return $this->filterValueList_Exact($normal, $tableName, $label, $id, $incjoin);
		}
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
		return '';
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
		$listModel = $this->getListModel();
		$fabrikDb = $listModel->getDb();
		$table = $listModel->getTable();
		$element = $this->getElement();
		$origTable = $table->db_table_name;
		$elName = $this->getFullName(false, true, false);
		$params = $this->getParams();
		$elName2 = $this->getFullName(false, false, false);
		if (!$this->isJoin())
		{
			$ids = $listModel->getColumnData($elName2);

			// For ids that are text with apostrophes in
			for ($x = count($ids) - 1; $x >= 0; $x--)
			{
				if ($ids[$x] == '')
				{
					unset($ids[$x]);
				}
				else
				{
					$ids[$x] = addslashes($ids[$x]);
				}
			}
		}
		$incjoin = $this->isJoin() ? false : $incjoin;
		/**
		 * filter the drop downs lists if the table_view_own_details option is on
		 * other wise the lists contain data the user should not be able to see
		 * note, this should now use the prefilter data to filter the list
		 */

		// Check if the elements group id is on of the table join groups if it is then we swap over the table name
		$fromTable = $this->isJoin() ? $this->getJoinModel()->getJoin()->table_join : $origTable;
		$joinStr = $incjoin ? $listModel->_buildQueryJoin() : $this->_buildFilterJoin();

		// New option not to order elements - required if you want to use db joins 'Joins where and/or order by statement'
		$groupBy = $this->getOrderBy('filter');

		foreach ($listModel->getJoins() as $aJoin)
		{
			// Not sure why the group id key wasnt found - but put here to remove error
			if (array_key_exists('group_id', $aJoin))
			{
				if ($aJoin->group_id == $element->group_id && $aJoin->element_id == 0)
				{
					$fromTable = $aJoin->table_join;
					$elName = str_replace($origTable . '.', $fromTable . '.', $elName2);
				}
			}
		}
		$elName = FabrikString::safeColName($elName);
		if ($label == '')
		{
			$label = $this->isJoin() ? $this->getElement()->name : $elName;
		}
		if ($id == '')
		{
			$id = $this->isJoin() ? 'id' : $elName;
		}
		if ($this->encryptMe())
		{
			$secret = JFactory::getConfig()->getValue('secret');
			$label = 'AES_DECRYPT(' . $label . ', ' . $fabrikDb->quote($secret) . ')';
			$id = 'AES_DECRYPT(' . $id . ', ' . $fabrikDb->quote($secret) . ')';
		}
		$origTable = $tableName == '' ? $origTable : $tableName;
		/**
		 * $$$ rob - 2nd sql was blowing up for me on my test table - why did we change to it?
		 * http://localhost/fabrik2.0.x/index.php?option=com_fabrik&view=table&listid=12&calculations=0&resetfilters=0&Itemid=255&lang=en
		 * so added test for intial fromtable in join str and if found use origtable
		 */
		if (strstr($joinStr, 'JOIN ' . $fabrikDb->quoteName($fromTable)))
		{
			$sql = 'SELECT DISTINCT(' . $label . ') AS ' . $fabrikDb->quoteName('text') . ', ' . $id . ' AS ' . $fabrikDb->quoteName('value')
				. ' FROM ' . $fabrikDb->quoteName($origTable) . $joinStr . "\n";
		}
		else
		{
			$sql = 'SELECT DISTINCT(' . $label . ') AS ' . $fabrikDb->quoteName('text') . ', ' . $id . ' AS ' . $fabrikDb->quoteName('value')
				. ' FROM ' . $fabrikDb->quoteName($fromTable) . $joinStr . "\n";
		}
		if (!$this->isJoin())
		{
			$sql .= 'WHERE ' . $id . ' IN (\'' . implode("','", $ids) . '\')';
		}

		// Apply element where/order by statements to the filter (e.g. dbjoins 'Joins where and/or order by statement')
		$elementWhere = $this->_buildQueryWhere(array(), true, null, array('mode' => 'filter'));
		if (JString::stristr($sql, 'WHERE ') && JString::stristr($elementWhere, 'WHERE '))
		{
			$elementWhere = JString::str_ireplace('WHERE ', 'AND ', $elementWhere);
		}
		$sql .= $elementWhere;

		$sql .= "\n" . $groupBy;
		$sql = $listModel->pluginQuery($sql);
		$fabrikDb->setQuery($sql);
		$rows = $fabrikDb->loadObjectList();
		if ($fabrikDb->getErrorNum() != 0)
		{
			JError::raiseNotice(500, 'filter query error: ' . $this->getElement()->name . ' ' . $fabrikDb->getErrorMsg());
		}
		return $rows;

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
		if (isset($this->orderBy))
		{
			return $this->orderBy;
		}
		else
		{
			return "ORDER BY text ASC ";
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
		$element = $this->getElement();
		$vals = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();
		$return = array();
		for ($i = 0; $i < count($vals); $i++)
		{
			$return[] = JHTML::_('select.option', $vals[$i], $labels[$i]);
		}
		return $return;
	}

	/**
	 * Get the hidden fields for a normal filter
	 *
	 * @param   int     $counter  filter counter
	 * @param   string  $elName   full element name will be converted to tablename.elementname format
	 * @param   bool    $hidden   has the filter been added due to a search form value with no corresponding filter set up in the table
	 * if it has we need to know so that when we do a search from a 'fabrik_list_filter_all' field that search term takes prescidence
	 *
	 * @return  string	html hidden fields
	 */

	protected function getFilterHiddenFields($counter, $elName, $hidden = false)
	{
		$params = $this->getParams();
		$element = $this->getElement();

		// $$$ needs to apply to CDD's as well, so just making this an overideable method.
		if ($this->quoteLabel())
		{
			$elName = FabrikString::safeColName($elName);
		}
		$hidden = $hidden ? 1 : 0;

		$table = $this->getListModel()->getTable();
		$match = $this->isExactMatch(array('match' => $element->filter_exact_match));
		$return = array();
		$filters = $this->getListModel()->getFilterArray();
		$eval = JArrayHelper::getValue($filters, 'eval', array());
		$eval = JArrayHelper::getValue($eval, $counter, FABRIKFILTER_TEXT);

		/**
		 * $$$ hugh - these two lines are preventing the "exact match" setting on an element filter working,
		 * as we always end up with an = condition, so exact match No nev er works.  I've "fixed" it by just using
		 * the element's getFilterCondition(), but I don't know what side effects this might have.
		 * So BOLO for filtering oddities!
		 *
		 * $condition = JArrayHelper::getValue($filters, 'condition', array());
		 * $condition = JArrayHelper::getValue($condition, $counter, $this->getFilterCondition());
		 */
		$condition = $this->getFilterCondition();
		$prefix = '<input type="hidden" name="fabrik___filter[list_' . $this->getListModel()->getRenderContext() . ']';
		$return[] = $prefix . '[condition][' . $counter . ']" value="' . $condition . '" />';
		$return[] = $prefix . '[join][' . $counter . ']" value="AND" />';
		$return[] = $prefix . '[key][' . $counter . ']" value="' . $elName . '" />';
		$return[] = $prefix . '[search_type][' . $counter . ']" value="normal" />';
		$return[] = $prefix . '[match][' . $counter . ']" value="' . $match . '" />';
		$return[] = $prefix . '[full_words_only][' . $counter . ']" value="' . $params->get('full_words_only', '0') . '" />';
		$return[] = $prefix . '[eval][' . $counter . ']" value="' . $eval . '" />';
		$return[] = $prefix . '[grouped_to_previous][' . $counter . ']" value="0" />';
		$return[] = $prefix . '[hidden][' . $counter . ']" value="' . $hidden . '" />';
		$return[] = $prefix . '[elementid][' . $counter . ']" value="' . $element->id . '" />';
		return implode("\n", $return);
	}

	/**
	 * Get the condition statement to use in the filters hidden field
	 *
	 * @return  string	=, begins or contains
	 */

	protected function getFilterCondition()
	{
		if ($this->getElement()->filter_type == 'auto-complete')
		{
			$cond = 'contains';
		}
		else
		{
			$match = $this->isExactMatch(array('match' => $this->getElement()->filter_exact_match));
			$cond = ($match == 1) ? '=' : 'contains';
		}
		return $cond;
	}

	/**
	 * Get the hidden fields for an advanced filter
	 *
	 * @return  string	html hidden fields
	 */

	protected function getAdvancedFilterHiddenFields()
	{
		$element = $this->getElement();
		$elName = $this->getFilterFullName();
		if ($element->plugin != 'fabrikdatabasejoin')
		{
			$elName = FabrikString::safeColName($elName);
		}
		$listModel = $this->getListModel();
		$element = $this->getElement();
		$return = array();
		$prefix = '<input type="hidden" name="fabrik___filter[list_' . $this->getListModel()->getRenderContext() . ']';
		$return[] = $prefix . '[elementid][]" value="' . $element->id . '" />';
		/**
		 * already added in advanced filter
		 * $return[] = $prefix . '[key][]" value="'.$elName.'" />';
		 * $return[] = $prefix . '[join][]" value="AND" />';
		 * $return[] = $prefix . '[grouped_to_previous][]" value="0" />';
		 */
		return implode("\n", $return);
	}

	/**
	 * This builds an array containing the filters value and condition
	 * when using a ranged search
	 *
	 * @param   string  $value  initial value
	 *
	 * @return  array  (value condition)
	 */

	protected function getRangedFilterValue($value)
	{
		$db = FabrikWorker::getDbo();
		$element = $this->getElement();
		if ($element->filter_type === 'range')
		{
			if (is_numeric($value[0]) && is_numeric($value[1]))
			{
				$value = $value[0] . ' AND ' . $value[1];
			}
			else
			{
				$value = $db->quote($value[0]) . ' AND ' . $db->quote($value[1]);
			}
			$condition = 'BETWEEN';
		}
		else
		{
			if (is_array($value) && !empty($value))
			{
				array_walk($value, array($db, 'quote'));
				$value = ' (' . implode(',', $value) . ')';
			}
			$condition = 'IN';
		}
		return array($value, $condition);
	}

	/**
	 * Esacepes the a query search string
	 *
	 * @param   string  $condition  filter condition
	 * @param   value   &$value     value to esacpe
	 *
	 * @return  null
	 */

	private function escapeQueryValue($condition, &$value)
	{
		// $$$ rob 30/06/2011 only escape once !
		if ($this->escapedQueryValue)
		{
			return;
		}
		$this->escapedQueryValue = true;
		if (is_array($value))
		{
			/**
			 * if doing a search via a querystring for O'Fallon then the ' is backslahed in
			 *  FabrikModelListfilter::getQuerystringFilters()
			 * but the mySQL regexp needs it to be backquoted three times
			 */
			foreach ($value as &$val)
			{
				if ($condition == 'REGEXP')
				{
					$val = preg_quote($val);
				}
				$val = str_replace("\\", "\\\\\\", $val);

				// $$$rob check things havent been double quoted twice (occurs now that we are doing preg_quote() above to fix searches on '*'
				$val = str_replace("\\\\\\\\\\\\", "\\\\\\", $val);
			}
		}
		else
		{
			if ($condition == 'REGEXP')
			{
				$value = preg_quote($value);
			}
			/**
			 * If doing a search via a querystring for O'Fallon then the ' is backslahed
			 * in FabrikModelListfilter::getQuerystringFilters()
			 * but the mySQL regexp needs it to be backquoted three times
			 */
			$value = str_replace("\\", "\\\\\\", $value);

			// $$$rob check things havent been double quoted twice (occurs now that we are doing preg_quote() above to fix searches on '*'
			$value = str_replace("\\\\\\\\\\\\", "\\\\\\", $value);
		}
	}

	/**
	 * Builds an array containing the filters value and condition
	 *
	 * @param   string  $value      initial value
	 * @param   string  $condition  intial $condition
	 * @param   string  $eval       how the value should be handled
	 *
	 * @return  array	(value condition)
	 */

	public function getFilterValue($value, $condition, $eval)
	{
		$this->escapeQueryValue($condition, $value);
		$db = FabrikWorker::getDbo();
		if (is_array($value))
		{
			// Ranged search
			list($value, $condition) = $this->getRangedFilterValue($value);
		}
		else
		{
			switch ($condition)
			{
				case 'notequals':
				case '<>':
					$condition = "<>";

					// 2 = subquery so dont quote
					$value = ($eval == FABRIKFILTER_QUERY) ? '(' . $value . ')' : $db->quote($value);
					break;
				case 'equals':
				case '=':
					$condition = "=";
					$value = ($eval == FABRIKFILTER_QUERY) ? '(' . $value . ')' : $db->quote($value);
					break;
				case 'begins':
				case 'begins with':
					$condition = "LIKE";
					$value = $eval == FABRIKFILTER_QUERY ? '(' . $value . ')' : $db->quote($value . '%');
					break;
				case 'ends':
				case 'ends with':
				// @TODO test this with subsquery
					$condition = "LIKE";
					$value = $eval == FABRIKFILTER_QUERY ? '(' . $value . ')' : $db->quote('%' . $value);
					break;
				case 'contains':
				// @TODO test this with subsquery
					$condition = "LIKE";
					$value = $eval == FABRIKFILTER_QUERY ? '(' . $value . ')' : $db->quote('%' . $value . '%');
					break;
				case '>':
				case '&gt;':
				case 'greaterthan':
					$condition = '>';
					break;
				case '<':
				case '&lt;':
				case 'lessthan':
					$condition = '<';
					break;
				case '>=':
				case '&gt;=':
				case 'greaterthanequals':
					$condition = '>=';
					break;
				case '<=':
				case '&lt;=':
				case 'lessthanequals':
					$condition = '<=';
					break;
				case 'in':
					$condition = 'IN';
					$value = ($eval == FABRIKFILTER_QUERY) ? '(' . $value . ')' : '(' . $value . ')';
					break;
				case 'not_in':
					$condition = 'NOT IN';
					$value = ($eval == FABRIKFILTER_QUERY) ? '(' . $value . ')' : '(' . $value . ')';
					break;

			}
			switch ($condition)
			{
				case '>':
				case '<':
				case '>=':
				case '<=':
					if ($eval == FABRIKFILTER_QUERY)
					{
						$value = '(' . $value . ')';
					}
					else
					{
						if (!is_numeric($value))
						{
							$value = $db->quote($value);
						}
					}
					break;
			}
			// $$$ hugh - if 'noquotes' (3) selected, strip off the quotes again!
			if ($eval == FABRKFILTER_NOQUOTES)
			{
				// $$$ hugh - darn, this is stripping the ' of the end of things like "select & from foo where bar = '123'"
				$value = JString::ltrim($value, "'");
				$value = JString::rtrim($value, "'");
			}
			if ($condition == '=' && $value == "'_null_'")
			{
				$condition = " IS NULL ";
				$value = '';
			}
		}
		return array($value, $condition);
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
		$this->encryptFieldName($key);
		switch ($condition)
		{
			case 'earlierthisyear':
				$query = ' DAYOFYEAR(' . $key . ') <= DAYOFYEAR(now()) ';
				break;
			case 'laterthisyear':
				$query = ' DAYOFYEAR(' . $key . ') >= DAYOFYEAR(now()) ';
				break;
			case 'today':
				$query = ' (' . $key . ' >= CURDATE() AND ' . $key . ' < CURDATE() + INTERVAL 1 DAY) ';
				break;
			case 'yesterday':
				$query = ' (' . $key . ' >= CURDATE() - INTERVAL 1 DAY AND ' . $key . ' < CURDATE()) ';
				break;
			case 'tomorrow':
				$query = ' (' . $key . ' >= CURDATE() + INTERVAL 1 DAY  AND ' . $key . ' < CURDATE() + INTERVAL 2 DAY ) ';
				break;
			case 'thismonth':
				$query = ' (' . $key . ' >= DATE_ADD(LAST_DAY(DATE_SUB(now(), INTERVAL 1 MONTH)), INTERVAL 1 DAY)  AND ' . $key
					. ' <= LAST_DAY(NOW()) ) ';
				break;
			case 'lastmonth':
				$query = ' (' . $key . ' >= DATE_ADD(LAST_DAY(DATE_SUB(now(), INTERVAL 2 MONTH)), INTERVAL 1 DAY)  AND ' . $key
					. ' <= LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH)) ) ';
				break;
			case 'nextmonth':
				$query = ' (' . $key . ' >= DATE_ADD(LAST_DAY(now()), INTERVAL 1 DAY)  AND ' . $key
					. ' <= DATE_ADD(LAST_DAY(NOW()), INTERVAL 1 MONTH) ) ';
				break;
			default:
				if ($this->isJoin())
				{
					// Query the joined table concatanating into one field
					$jointable = $this->getJoinModel()->getJoin()->table_join;
					$pk = $this->getListModel()->getTable()->db_primary_key;
					$key = "(SELECT GROUP_CONCAT(id SEPARATOR '//..*..//') FROM $jointable WHERE parent_id = $pk)";
					$value = str_replace("'", '', $value);
					$query = "($key = '$value' OR $key LIKE '$value" . GROUPSPLITTER . "%' OR
					$key LIKE '" . GROUPSPLITTER . "$value" . GROUPSPLITTER . "%' OR
					$key LIKE '%" . GROUPSPLITTER . "$value')";
				}
				else
				{
					$query = " $key $condition $value ";
				}
				break;
		}
		return $query;
	}

	/**
	 * Get the AES decrypt sql segment for the element
	 *
	 * @param   string  &$key  field name
	 *
	 * @return  void
	 */

	public function encryptFieldName(&$key)
	{
		if ($this->encryptMe())
		{
			$db = FabrikWorker::getDbo();
			$secret = JFactory::getConfig()->get('secret');
			$key = 'AES_DECRYPT(' . $key . ', ' . $db->quote($secret) . ')';
		}
	}

	/**
	 * If no filter condition supplied (either via querystring or in posted filter data
	 * return the most appropriate filter option for the element.
	 *
	 * @return  string	default filter condition ('=', 'REGEXP' etc)
	 */

	public function getDefaultFilterCondition()
	{
		$params = $this->getParams();
		$fieldDesc = $this->getFieldDescription();
		if (JString::stristr($fieldDesc, 'INT') || $this->getElement()->filter_exact_match == 1)
		{
			return '=';
		}
		return 'REGEXP';
	}

	/**
	 * $$$ rob testing not using this as elements can only be in one group
	 * $$$ hugh - still called from import.php
	 * when adding a new element this will ensure its added to all tables that the
	 * elements group is associated with
	 *
	 * @param   string  $origColName  original column name leave null to ignore
	 *
	 * @TODO Fabrik 3 - loadFromFormId() might need to pass in a package id
	 *
	 * @deprecated
	 *
	 * @return  null
	 */

	public function addToDBTable($origColName = null)
	{
		$db = FabrikWorker::getDbo();
		$user = JFactory::getUser();

		// Don't bother if the element has no name as it will cause an sql error'
		if ($this->_element->name == '')
		{
			return;
		}
		$groupModel = JModel::getInstance('Group', 'FabrikFEModel');
		$groupModel->setId($this->_element->group_id);
		$groupTable = $groupModel->getGroup();
		$formTable = FabTable::getInstance('Form', 'FabrikTable');
		$listModel = JModel::getInstance('List', 'FabrikFEModel');
		$afFormIds = $groupModel->getFormsIamIn();
		if ($groupModel->isJoin())
		{
			$joinModel = $groupModel->getJoinModel();
			$joinTable = $joinModel->getJoin();
			if ($joinTable->list_id != 0)
			{
				$listModel->setId($joinTable->list_id);
				$table = $listModel->getTable();
				$table->db_table_name = $joinTable->table_join;
				$listModel->alterStructure($this, $origColName);
			}
		}
		else
		{
			if (is_array($afFormIds))
			{
				foreach ($afFormIds as $formId)
				{
					$formTable->load($formId);
					if ($formTable->record_in_database)
					{
						$tableTable = $listModel->loadFromFormId($formId);
						$listModel->alterStructure($this, $origColName);
					}
				}
			}
		}
	}

	/**
	 * Called from admin element controller when element saved
	 *
	 * @param   array  $data  posted element save data
	 *
	 * @return  bool  save ok or not
	 */

	public function onSave($data)
	{
		$params = $this->getParams();
		if (!$this->canEncrypt() && $params->get('encrypt'))
		{
			JError::raiseNotice(500, 'The encryption option is only available for field and text area plugins');
			return false;
		}
		// Overridden in element plugin if needed
		return true;
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
		// Delete js actions
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$id = (int) $this->getElement()->id;
		$query->delete()->from('#__{package}_jsactions')->where('element_id =' . $id);
		$db->setQuery($query);
		if (!$db->query())
		{
			JError::raiseNotice(500, 'didnt delete js actions for element ' . $id);
			return false;
		}
		return true;
	}

	/**
	 * States if the element contains data which is recorded in the database
	 * some elements (eg buttons) dont
	 *
	 * @param   array  $data  posted data
	 *
	 * @return  bool
	 */

	public function recordInDatabase($data = null)
	{
		return $this->_recordInDatabase;
	}

	/**
	 * used by elements with suboptions
	 *
	 * @param   string  $v             value
	 * @param   string  $defaultLabel  default label
	 *
	 * @return  string	label
	 */

	public function getLabelForValue($v, $defaultLabel = '')
	{
		/**
		 * $$$ hugh - only needed getParent when we weren't saving changes to parent params to child
		 * which we should now be doing ... and getParent() causes an extra table lookup for every child
		 * element on the form.
		 * $element = $this->getParent();
		 */
		$element = $this->getElement();
		$params = $this->getParams();
		$values = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();
		$key = array_search($v, $values);
		/**
		 * $$$ rob if we allow adding to the dropdown but not recording
		 * then there will be no $key set to revert to the $val instead
		 */
		if ($v === $params->get('sub_default_value'))
		{
			$v = $params->get('sub_default_label');
		}
		return ($key === false) ? $v : JArrayHelper::getValue($labels, $key, $defaultLabel);
	}

	/**
	 * Build the query for the avg calculation
	 *
	 * @param   model   &$listModel  list model
	 * @param   string  $label       the label to apply to each avg
	 *
	 * @return  string	sql statement
	 */

	protected function getAvgQuery(&$listModel, $label = "'calc'")
	{
		$item = $listModel->getTable();
		$joinSQL = $listModel->_buildQueryJoin();
		$whereSQL = $listModel->_buildQueryWhere();
		$name = $this->getFullName(false, false, false);
		$groupModel = $this->getGroup();
		$roundTo = (int) $this->getParams()->get('avg_round');
		if ($groupModel->isJoin())
		{
			// Element is in a joined column - lets presume the user wants to sum all cols, rather than reducing down to the main cols totals
			return "SELECT ROUND(AVG($name), $roundTo) AS value, $label AS label FROM " . FabrikString::safeColName($item->db_table_name)
				. " $joinSQL $whereSQL";
		}
		else
		{
			// Need to do first query to get distinct records as if we are doing left joins the sum is too large
			return "SELECT ROUND(AVG(value), $roundTo) AS value, label
FROM (SELECT DISTINCT $item->db_primary_key, $name AS value, $label AS label FROM " . FabrikString::safeColName($item->db_table_name)
				. " $joinSQL $whereSQL) AS t";

		}
	}

	/**
	 * Get sum query
	 *
	 * @param   object  &$listModel  list model
	 * @param   string  $label       label
	 *
	 * @return string
	 */

	protected function getSumQuery(&$listModel, $label = "'calc'")
	{
		$item = $listModel->getTable();
		$joinSQL = $listModel->_buildQueryJoin();
		$whereSQL = $listModel->_buildQueryWhere();
		$name = $this->getFullName(false, false, false);
		$groupModel = $this->getGroup();
		if ($groupModel->isJoin())
		{
			// Element is in a joined column - lets presume the user wants to sum all cols, rather than reducing down to the main cols totals
			return "SELECT SUM($name) AS value, $label AS label FROM " . FabrikString::safeColName($item->db_table_name) . " $joinSQL $whereSQL";
		}
		else
		{
			// Need to do first query to get distinct records as if we are doing left joins the sum is too large
			return "SELECT SUM(value) AS value, label
	FROM (SELECT DISTINCT $item->db_primary_key, $name AS value, $label AS label FROM " . FabrikString::safeColName($item->db_table_name)
				. " $joinSQL $whereSQL) AS t";
		}
	}

	/**
	 * Get a custom query
	 *
	 * @param   object  &$listModel  list
	 * @param   string  $label       label
	 *
	 * @return  string
	 */

	protected function getCustomQuery(&$listModel, $label = "'calc'")
	{
		$params = $this->getParams();
		$custom_query = $params->get('custom_calc_query', '');
		$item = $listModel->getTable();
		$joinSQL = $listModel->_buildQueryJoin();
		$whereSQL = $listModel->_buildQueryWhere();
		$name = $this->getFullName(false, false, false);
		$groupModel = $this->getGroup();
		if ($groupModel->isJoin())
		{
			// Element is in a joined column - lets presume the user wants to sum all cols, rather than reducing down to the main cols totals
			$custom_query = sprintf($custom_query, $name);
			return "SELECT $custom_query AS value, $label AS label FROM " . FabrikString::safeColName($item->db_table_name) . " $joinSQL $whereSQL";
		}
		else
		{
			// Need to do first query to get distinct records as if we are doing left joins the sum is too large
			$custom_query = sprintf($custom_query, 'value');
			return "SELECT $custom_query AS value, label FROM (SELECT DISTINCT " . FabrikString::safeColName($item->db_table_name)
				. ".*, $name AS value, $label AS label FROM " . FabrikString::safeColName($item->db_table_name) . " $joinSQL $whereSQL) AS t";
		}
	}

	/**
	 * Get a query for our media query
	 *
	 * @param   object  &$listModel  list
	 * @param   string  $label       label
	 *
	 * @return string
	 */

	protected function getMedianQuery(&$listModel, $label = "'calc'")
	{
		$item = $listModel->getTable();
		$joinSQL = $listModel->_buildQueryJoin();
		$whereSQL = $listModel->_buildQueryWhere();
		return "SELECT {$this->getFullName(false, false, false)} AS value, $label AS label FROM " . FabrikString::safeColName($item->db_table_name)
			. " $joinSQL $whereSQL ";
	}

	/**
	 * Get a query for our count method
	 *
	 * @param   object  &$listModel  list
	 * @param   string  $label       label
	 *
	 * @return string
	 */

	protected function getCountQuery(&$listModel, $label = "'calc'")
	{
		$db = FabrikWorker::getDbo();
		$item = $listModel->getTable();
		$joinSQL = $listModel->_buildQueryJoin();
		$whereSQL = $listModel->_buildQueryWhere();
		$name = $this->getFullName(false, false, false);

		// $$$ hugh - need to account for 'count value' here!
		$params = $this->getParams();
		$count_condition = $params->get('count_condition', '');
		if (!empty($count_condition))
		{
			if (!empty($whereSQL))
			{
				$whereSQL .= " AND $name = " . $db->quote($count_condition);
			}
			else
			{
				$whereSQL = "WHERE $name = " . $db->quote($count_condition);
			}
		}
		$groupModel = $this->getGroup();
		if ($groupModel->isJoin())
		{
			// Element is in a joined column - lets presume the user wants to sum all cols, rather than reducing down to the main cols totals
			return "SELECT COUNT($name) AS value, $label AS label FROM " . FabrikString::safeColName($item->db_table_name) . " $joinSQL $whereSQL";
		}
		else
		{
			// Need to do first query to get distinct records as if we are doing left joins the sum is too large
			$query = "SELECT COUNT(value) AS value, label
	FROM (SELECT DISTINCT $item->db_primary_key, $name AS value, $label AS label FROM " . FabrikString::safeColName($item->db_table_name)
				. " $joinSQL $whereSQL) AS t";
		}
		return $query;
	}

	/**
	 * calculation: sum
	 * can be overridden in element class
	 *
	 * @param   object  &$listModel  list model
	 *
	 * @return  array
	 */

	public function sum(&$listModel)
	{
		$db = $listModel->getDb();
		$params = $this->getParams();
		$item = $listModel->getTable();
		$splitSum = $params->get('sum_split', '');
		$split = trim($splitSum) == '' ? false : true;
		$calcLabel = $params->get('sum_label', JText::_('COM_FABRIK_SUM'));
		if ($split)
		{
			$pluginManager = FabrikWorker::getPluginManager();
			$plugin = $pluginManager->getElementPlugin($splitSum);
			$splitName = method_exists($plugin, 'getJoinLabelColumn') ? $plugin->getJoinLabelColumn() : $plugin->getFullName(false, false, false);
			$splitName = FabrikString::safeColName($splitName);
			$sql = $this->getSumQuery($listModel, $splitName) . " GROUP BY label";
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$results2 = $db->loadObjectList('label');
			$uberTotal = 0;
			foreach ($results2 as $pair)
			{
				$uberTotal += $pair->value;
			}
			$uberObject = new stdClass;
			$uberObject->value = $uberTotal;
			$uberObject->label = JText::_('COM_FABRIK_TOTAL');
			$uberObject->class = 'splittotal';
			$uberObject->special = true;
			$results2[] = $uberObject;
			$results = $this->formatCalcSplitLabels($results2, $plugin, 'sum');
		}
		else
		{
			// Need to add a group by here as well as if the ONLY_FULL_GROUP_BY SQL mode is enabled an error is produced
			$sql = $this->getSumQuery($listModel) . " GROUP BY label";
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$results = $db->loadObjectList('label');
		}
		$res = $this->formatCalcs($results, $calcLabel, $split);
		return array($res, $results);
	}

	/**
	 * calculation: avarage
	 * can be overridden in element class
	 *
	 * @param   object  &$listModel  list model
	 *
	 * @return  string	result
	 */

	public function avg(&$listModel)
	{
		$db = $listModel->getDb();
		$params = $this->getParams();
		$splitAvg = $params->get('avg_split', '');
		$item = $listModel->getTable();
		$calcLabel = $params->get('avg_label', JText::_('COM_FABRIK_AVERAGE'));
		$split = trim($splitAvg) == '' ? false : true;
		if ($split)
		{
			$pluginManager = FabrikWorker::getPluginManager();
			$plugin = $pluginManager->getElementPlugin($splitAvg);
			$splitName = method_exists($plugin, 'getJoinLabelColumn') ? $plugin->getJoinLabelColumn() : $plugin->getFullName(false, false, false);
			$splitName = FabrikString::safeColName($splitName);
			$sql = $this->getAvgQuery($listModel, $splitName) . " GROUP BY label";
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$results2 = $db->loadObjectList('label');
			$uberTotal = 0;
			foreach ($results2 as $pair)
			{
				$uberTotal += $pair->value;
			}
			$uberObject = new stdClass;
			$uberObject->value = $uberTotal / count($results2);
			$uberObject->label = JText::_('COM_FABRIK_AVERAGE');
			$uberObject->special = true;
			$uberObject->class = 'splittotal';
			$results2[] = $uberObject;

			$results = $this->formatCalcSplitLabels($results2, $plugin, 'avg');
		}
		else
		{
			// Need to add a group by here as well as if the ONLY_FULL_GROUP_BY SQL mode is enabled an error is produced
			$sql = $this->getAvgQuery($listModel) . " GROUP BY label";
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$results = $db->loadObjectList('label');
		}
		$res = $this->formatCalcs($results, $calcLabel, $split);
		return array($res, $results);
	}

	/**
	 * Get the sprintf format string
	 *
	 * @since 3.0.4
	 *
	 * @return string
	 */

	public function getFormatString()
	{
		$params = $this->getParams();
		return $params->get('text_format_string');
	}

	/**
	 * calculation: median
	 * can be overridden in element class
	 *
	 * @param   object  &$listModel  list model
	 *
	 * @return  string 	result
	 */

	public function median(&$listModel)
	{
		$db = $listModel->getDb();
		$item = $listModel->getTable();
		$element = $this->getElement();
		$joinSQL = $listModel->_buildQueryJoin();
		$whereSQL = $listModel->_buildQueryWhere();
		$params = $this->getParams();
		$splitMedian = $params->get('median_split', '');
		$split = $splitMedian == '' ? false : true;
		$format = $this->getFormatString();
		$res = '';
		$calcLabel = $params->get('median_label', JText::_('COM_FABRIK_MEDIAN'));
		$results = array();
		if ($split)
		{
			$pluginManager = FabrikWorker::getPluginManager();
			$plugin = $pluginManager->getElementPlugin($splitMedian);
			$splitName = method_exists($plugin, 'getJoinLabelColumn') ? $plugin->getJoinLabelColumn() : $plugin->getFullName(false, false, false);
			$splitName = FabrikString::safeColName($splitName);
			$sql = $this->getMedianQuery($listModel, $splitName) . ' ORDER BY ' . $splitName . ' ';
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$results2 = $db->loadObjectList();
			$results = $this->formatCalcSplitLabels($results2, $plugin, 'median');
		}
		else
		{
			$sql = $this->getMedianQuery($listModel);
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$res = $this->_median($db->loadColumn());
			$o = new stdClass;
			if ($format != '')
			{
				$res = sprintf($format, $res);
			}
			$o->value = $res;
			$label = $params->get('alt_list_heading') == '' ? $element->label : $params->get('alt_list_heading');
			$o->elLabel = $label;
			$o->calLabel = $calcLabel;
			$o->label = 'calc';
			$results = array('calc' => $o);
		}
		$res = $this->formatCalcs($results, $calcLabel, $split, true, false);
		return array($res, $results);
	}

	/**
	 * calculation: count
	 * can be overridden in element class
	 *
	 * @param   object  &$listModel  list model
	 *
	 * @return  string	result
	 */

	public function count(&$listModel)
	{
		$db = $listModel->getDb();
		$item = $listModel->getTable(true);
		$element = $this->getElement();
		$params = $this->getParams();
		$calcLabel = $params->get('count_label', JText::_('COM_FABRIK_COUNT'));
		$splitCount = $params->get('count_split', '');
		$split = $splitCount == '' ? false : true;
		if ($split)
		{
			$pluginManager = FabrikWorker::getPluginManager();
			$plugin = $pluginManager->getElementPlugin($splitCount);
			$name = $plugin->getFullName(false, true, false);
			$splitName = method_exists($plugin, 'getJoinLabelColumn') ? $plugin->getJoinLabelColumn() : $plugin->getFullName(false, false, false);
			$splitName = FabrikString::safeColName($splitName);
			$sql = $this->getCountQuery($listModel, $splitName) . " GROUP BY label ";
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$results2 = $db->loadObjectList('label');
			$uberTotal = 0;
			foreach ($results2 as $k => &$r)
			{
				if ($k == '')
				{
					unset($results2[$k]);
				}
			}
			foreach ($results2 as $pair)
			{
				$uberTotal += $pair->value;
			}
			$uberObject = new stdClass;
			$uberObject->value = count($results2) == 0 ? 0 : $uberTotal;
			$uberObject->label = JText::_('COM_FABRIK_TOTAL');
			$uberObject->class = 'splittotal';
			$uberObject->special = true;
			$results = $this->formatCalcSplitLabels($results2, $plugin, 'count');
			$results[JText::_('COM_FABRIK_TOTAL')] = $uberObject;
		}
		else
		{
			// Need to add a group by here as well as if the ONLY_FULL_GROUP_BY SQL mode is enabled an error is produced
			$sql = $this->getCountQuery($listModel) . ' GROUP BY label ';
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$results = $db->loadObjectList('label');
		}
		$res = $this->formatCalcs($results, $calcLabel, $split, false, false);
		return array($res, $results);
	}

	/**
	 * calculation: custom_calc
	 * can be overridden in element class
	 *
	 * @param   object  &$listModel  list model
	 *
	 * @return  array
	 */

	public function custom_calc(&$listModel)
	{
		$db = $listModel->getDb();
		$params = $this->getParams();
		$item = $listModel->getTable();
		$splitCustom = $params->get('custom_calc_split', '');
		$split = $splitCustom == '' ? false : true;
		$calcLabel = $params->get('custom_calc_label', JText::_('COM_FABRIK_CUSTOM'));
		if ($split)
		{
			$pluginManager = FabrikWorker::getPluginManager();
			$plugin = $pluginManager->getElementPlugin($splitCustom);
			$splitName = method_exists($plugin, 'getJoinLabelColumn') ? $plugin->getJoinLabelColumn() : $plugin->getFullName(false, false, false);
			$splitName = FabrikString::safeColName($splitName);
			$sql = $this->getCustomQuery($listModel, $splitName) . ' GROUP BY label';
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$results2 = $db->loadObjectList('label');
			$results = $this->formatCalcSplitLabels($results2, $plugin, 'custom_calc');
		}
		else
		{
			// Need to add a group by here as well as if the ONLY_FULL_GROUP_BY SQL mode is enabled an error is produced
			$sql = $this->getCustomQuery($listModel) . ' GROUP BY label';
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$results = $db->loadObjectList('label');
		}
		$res = $this->formatCalcs($results, $calcLabel, $split);
		return array($res, $results);
	}

	/**
	 * Format the labels for calculations when they are split
	 *
	 * @param   array   &$results2  calculation results
	 * @param   object  &$plugin    element that the data is SPLIT on
	 * @param   string  $type       of calculation
	 *
	 * @return  unknown_type
	 */

	protected function formatCalcSplitLabels(&$results2, &$plugin, $type = '')
	{
		$results = array();
		$tomerge = array();
		$name = $plugin->getFullName(false, true, false);

		// $$$ hugh - avoid PHP warning if $results2 is NULL
		if (empty($results2))
		{
			return $results;
		}
		foreach ($results2 as $key => $val)
		{
			if (isset($val->special) && $val->special)
			{
				// Don't inlcude special values (ubers) in $tomerge, otherwise total sum added to first value
				$results[$val->label] = $val;
				continue;
			}
			if ($plugin->hasSubElements)
			{
				$val->label = ($type == 'median') ? $plugin->getLabelForValue($val->label) : $plugin->getLabelForValue($key);
			}
			else
			{
				$d = new stdClass;
				$d->$name = $val->label;
				$val->label = $plugin->renderListData($val->label, $d);
			}
			if (array_key_exists($val->label, $results))
			{
				/** $$$ rob the $result data is keyed on the raw database result - however, we are intrested in
				 * keying on the formatted table result (e.g. allows us to group date entries by year)
				 */
				if ($results[$val->label] !== '')
				{
					$tomerge[$val->label][] = $results[$val->label]->value;
				}
				$results[$val->label] = '';
				$tomerge[$val->label][] = $val->value;
			}
			else
			{
				$results[$val->label] = $val;
			}
		}
		foreach ($tomerge as $label => $data)
		{
			$o = new stdClass;
			switch ($type)
			{
				case 'avg':
					$o->value = $this->simpleAvg($data);
					break;
				case 'sum':
					$o->value = $this->simpleSum($data);
					break;
				case 'median':
					$o->value = $this->_median($data);
					break;
				case 'count':
					$o->value = count($data);
					break;
				case 'custom_calc':
					$params = $this->getParams();
					$custom_calc_php = $params->get('custom_calc_php', '');
					if (!empty($custom_calc_php))
					{
						$o->value = @eval(stripslashes($custom_calc_php));
						FabrikWorker::logEval($custom_calc_php, 'Caught exception on eval of ' . $name . ': %s');
					}
					else
					{
						$o->value = $data;
					}
					break;
				default:
					$o->value = $data;
					break;

			}
			$o->label = $label;
			$results[$label] = $o;
		}
		return $results;
	}

	/**
	 * find an average from a set of data
	 * can be overwritten in plugin - see date for example of averaging dates
	 *
	 * @param   array  $data  to average
	 *
	 * @return  string  average result
	 */

	public function simpleAvg($data)
	{
		return $this->simpleSum($data) / count($data);
	}

	/**
	 * find the sum from a set of data
	 * can be overwritten in plugin - see date for example of averaging dates
	 *
	 * @param   array  $data  to sum
	 *
	 * @return  string  sum result
	 */

	public function simpleSum($data)
	{
		return array_sum($data);
	}

	/**
	 * Take the results form a calc and create the string that can be used to summarize them
	 *
	 * @param   array   &$results       calculation results
	 * @param   string  $calcLabel      calc label
	 * @param   bool    $split          is the data split
	 * @param   bool    $numberFormat   should we applpy any number formatting
	 * @param   bool    $sprintFFormat  should we apply the text_format_string ?
	 *
	 * @return  string
	 */

	protected function formatCalcs(&$results, $calcLabel, $split = false, $numberFormat = true, $sprintFFormat = true)
	{
		settype($results, 'array');
		$res = array();
		$res[] = $split ? '<dl>' : '<ul class="fabrikRepeatData">';
		$l = '<span class="calclabel">' . $calcLabel . '</span>';
		$res[] = $split ? '<dt>' . $l . '</dt>' : '<li>' . $l;
		$params = $this->getParams();
		$element = $this->getElement();
		$format = $this->getFormatString();
		$label = $params->get('alt_list_heading') == '' ? $element->label : $params->get('alt_list_heading');
		foreach ($results as $key => $o)
		{
			$o->label = ($o->label == 'calc') ? '' : $o->label;
			$o->elLabel = $label . ' ' . $o->label;
			if ($numberFormat)
			{
				$o->value = $this->numberFormat($o->value);
			}
			if ($format != '' && $sprintFFormat)
			{
				$o->value = sprintf($format, $o->value);
			}
			$o->calLabel = $calcLabel;
			$class = isset($o->class) ? ' class="' . $o->class . '"' : '';
			if ($split)
			{
				$res[] = '<dd' . $class . '><span class="calclabel">' . $o->label . ':</span> ' . $o->value . '</dd>';
			}
			else
			{
				$res[] = $o->value . '</li>';
			}
		}
		ksort($results);
		$res[] = $split ? '</dl>' : '</ul>';
		return implode("\n", $res);
	}

	/**
	 * Get median
	 *
	 * @param   array  $results  set of results to get median from
	 *
	 * @return  string	median value
	 */

	private function _median($results)
	{
		$results = (array) $results;
		sort($results);
		if ((count($results) % 2) == 1)
		{
			/* odd */
			$midKey = floor(count($results) / 2);
			return $results[$midKey];
		}
		else
		{
			$midKey = floor(count($results) / 2) - 1;
			$midKey2 = floor(count($results) / 2);
			return $this->simpleAvg(array(JArrayHelper::getValue($results, $midKey), JArrayHelper::getValue($results, $midKey2)));
		}
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
	}

	/**
	 * Get JS code for ini element list js
	 * Overwritten in plugin classes
	 *
	 * @return string
	 */

	public function elementListJavascript()
	{
		return '';
	}

	/**
	 * Create a class for the elements default javascript options
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  object	options
	 */

	public function getElementJSOptions($repeatCounter)
	{
		$element = $this->getElement();
		$opts = new stdClass;
		$data = $this->_form->_data;
		$opts->repeatCounter = $repeatCounter;
		$opts->editable = ($this->canView() && !$this->canUse()) ? false : $this->_editable;
		$opts->value = $this->getValue($data, $repeatCounter);
		$opts->defaultVal = $this->getDefaultValue($data);
		$opts->inRepeatGroup = $this->getGroup()->canRepeat() == 1;
		$validationEls = array();
		$validations = $this->getValidations();
		if (!empty($validations) && $this->_editable)
		{
			$watchElements = $this->getValidationWatchElements($repeatCounter);
			foreach ($watchElements as $watchElement)
			{
				$o = new stdClass;
				$o->id = $watchElement['id'];
				$o->triggerEvent = $watchElement['triggerEvent'];
				$validationEls[] = $o;
			}
		}
		$opts->watchElements = $validationEls;
		$groupModel = $this->getGroup();
		$opts->canRepeat = (bool) $groupModel->canRepeat();
		$opts->isGroupJoin = (bool) $groupModel->isJoin();
		if ($this->isJoin())
		{
			$opts->joinid = (int) $this->getJoinModel()->getJoin()->id;
		}
		else
		{
			$opts->joinid = (int) $groupModel->getGroup()->join_id;
		}
		return $opts;
	}

	/**
	 * Does the element use the WYSWYG editor
	 *
	 * @return  bool	use wysiwyg editor
	 */

	public function useEditor()
	{
		return false;
	}

	/**
	 * Processes uploaded data
	 *
	 * @return  void
	 */

	public function processUpload()
	{
	}

	/**
	 * Get the class to manage the form element
	 * if a plugin class requires to load another elements class (eg user for dbjoin then it should
	 * call FabrikModelElement::formJavascriptClass('plugins/fabrik_element/databasejoin/databasejoin.js', true);
	 * to ensure that the file is loaded only once
	 *
	 * @param   array   &$srcs   scripts previously loaded (load order is important as we are loading via head.js
	 * and in ie these load async. So if you this class extends another you need to insert its location in $srcs above the
	 * current file
	 * @param   string  $script  script to load once class has loaded
	 *
	 * @return void
	 */

	public function formJavascriptClass(&$srcs, $script = '')
	{
		static $elementclasses;
		if (!isset($elementclasses))
		{
			$elementclasses = array();
		}
		// Load up the default scipt
		if ($script == '')
		{
			$script = 'plugins/fabrik_element/' . $this->getElement()->plugin . '/' . $this->getElement()->plugin . '.js';
		}
		if (empty($elementclasses[$script]))
		{
			$srcs[] = $script;
			$elementclasses[$script] = 1;
		}
	}

	/**
	 * load js file for element when in list view
	 *
	 * @return  null
	 */

	public function tableJavascriptClass()
	{
		$p = $this->getElement()->plugin;
		FabrikHelperHTML::script('plugins/fabrik_element/' . $p . '/list-' . $p . '.js');
	}

	/**
	 * can be overwritten in plugin classes
	 * eg if changing from db join to field we need to remove the join
	 * entry from the #__{package}_joins table
	 *
	 * @param   object  &$row  that is going to be updated
	 *
	 * @return null
	 */

	public function beforeSave(&$row)
	{
		$maskbits = 4;
		$post = JRequest::get('post', $maskbits);
		$post = $post['jform'];
		$dbjoinEl = (is_subclass_of($this, 'plgFabrik_ElementDatabasejoin') || get_class($this) == 'plgFabrik_ElementDatabasejoin');
		/**
		 * $$$ hugh - added test for empty id, i.e. new element, otherwise we try and delete a crapload of join table rows
		 * we shouldn't be deleting!  Also adding defensive code to deleteJoins() to test for empty ID.
		 */
		if (!empty($post['id']) && !$this->isJoin() && !$dbjoinEl)
		{
			$this->deleteJoins((int) $post['id']);
		}
	}

	/**
	 * Delete joins
	 *
	 * @param   int  $id  element id
	 *
	 * @return  null
	 */

	protected function deleteJoins($id)
	{
		// $$$ hugh - bail if no $id specified
		if (empty($id))
		{
			return;
		}
		$element = $this->getElement();
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->delete('#__{package}_joins')->where('element_id = ' . $id);
		$db->setQuery($query);
		$db->query();

		$query->clear();
		$query->select('j.id AS jid')->from('#__{package}_elements AS e')->join('LEFT', ' #__{package}_joins AS j ON j.element_id = e.id')
			->where('e.parent_id = ' . $id);
		$db->setQuery($query);
		$join_ids = $db->loadColumn();
		if (!empty($join_ids))
		{
			$query->clear();
			$query->delete('#__{package}_joins')->where('id IN (' . implode(',', $join_ids) . ')');
			$db->setQuery($query);
			$db->query();
		}
	}

	/**
	 * If your element risks not to post anything in the form (e.g. check boxes with none checked)
	 * the this function will insert a default value into the database
	 *
	 * @param   array  &$data  form data
	 *
	 * @return  array  form data
	 */

	public function getEmptyDataValue(&$data)
	{
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

	public function getEmailValue($value, $data, $repeatCounter)
	{
		if ($this->_inRepeatGroup)
		{
			$val = array();
			foreach ($value as $v2)
			{
				$val[] = $this->_getEmailValue($v2, $data, $repeatCounter);
			}
		}
		else
		{
			$val = $this->_getEmailValue($value, $data, $repeatCounter);
		}
		return $val;
	}

	/**
	 * Turn form value into email formatted value
	 *
	 * @param   mixed  $value          element value
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  group repeat counter
	 *
	 * @return  string  email formatted value
	 */

	protected function _getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		return $value;
	}

	/**
	 * Is the element an upload element
	 *
	 * @return boolean
	 */

	public function isUpload()
	{
		return $this->_is_upload;
	}

	/**
	 * If a database join element's value field points to the same db field as this element
	 * then this element can, within modifyJoinQuery, update the query.
	 * E.g. if the database join element points to a file upload element then you can replace
	 * the file path that is the standard $val with the html to create the image
	 *
	 * @param   string  $val   value
	 * @param   string  $view  form or list
	 *
	 * @deprecated - doesn't seem to be used
	 *
	 * @return  string	modified val
	 */

	protected function modifyJoinQuery($val, $view = 'form')
	{
		return $val;
	}

	/**
	 * not used
	 *
	 * @deprecated
	 *
	 * @return void
	 */

	public function ajax_loadTableFields()
	{
		$db = FabrikWorker::getDbo();
		$listModel = JModel::getInstance('List', 'FabrikFEModel');
		$this->_cnnId = JRequest::getInt('cid', 0);
		$tbl = $db->quoteName(JRequest::getVar('table'));
		$fieldDropDown = $listModel->getFieldsDropDown($this->_cnnId, $tbl, '-', false, 'params[join_val_column]');
		$fieldDropDown2 = $listModel->getFieldsDropDown($this->_cnnId, $tbl, '-', false, 'params[join_key_column]');
		echo "$('addJoinVal').innerHTML = '$fieldDropDown';";
		echo "$('addJoinKey').innerHTML = '$fieldDropDown2';";
	}

	/**
	 * Get join row
	 *
	 * @return  object	join table or false if not loaded
	 */

	protected function getJoin()
	{
		return null;
	}

	/**
	 * Get database field description
	 *
	 * @return  string  db field type
	 */

	public function getFieldDescription()
	{
		$plugin = JPluginHelper::getPlugin('fabrik_element', 'dropdown');
		$fparams = new JRegistry($plugin->params);
		$p = $this->getParams();
		if ($this->encryptMe())
		{
			return 'BLOB';
		}
		$group = $this->getGroup();
		if ($group->isJoin() == 0 && $group->canRepeat())
		{
			return "TEXT";
		}
		else
		{
			$size = $p->get('maxlength', $this->fieldSize);
			$objtype = sprintf($this->fieldDesc, $size);
		}
		$objtype = $fparams->get('defaultFieldType', $objtype);
		return $objtype;
	}

	/**
	 * Trigger called when a row is deleted, can be used to delete images previously uploaded
	 *
	 * @param   array  $groups  grouped data of rows to delete
	 *
	 * @return  void
	 */

	public function onDeleteRows($groups)
	{

	}

	/**
	 * Trigger called when a row is stored.
	 * Check if new options have been added and if so store them in the element for future use.
	 *
	 * @param   array  &$data  to store
	 *
	 * @return  void
	 */

	public function onStoreRow(&$data)
	{
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
		$listModel = $this->getListModel();
		$data = FabrikWorker::JSONtoData($data, true);
		foreach ($data as $i => &$d)
		{
			if ($params->get('icon_folder') == '1')
			{
				// $$$ rob was returning here but that stoped us being able to use links and icons together
				$d = $this->_replaceWithIcons($d, 'list', $listModel->getTmpl());
			}
			$d = $this->rollover($d, $thisRow, 'list');
			$d = $listModel->_addLink($d, $this, $thisRow, $i);
		}
		return $this->renderListDataFinal($data);
	}

	/**
	 * Final prepare data function called from renderListData(), converts data to string and if needed
	 * encases in <ul> (for repeating data)
	 *
	 * @param   array  $data  list cell data
	 *
	 * @return  string	cell data
	 */

	protected function renderListDataFinal($data)
	{
		if (is_array($data) && count($data) > 1)
		{
			if (!array_key_exists(0, $data))
			{
				// Occurs if we have created a list from an exisitng table whose data contains json objects (e.g. #__users.params)
				$obj = JArrayHelper::toObject($data);
				$data = array();
				$data[0] = $obj;
			}
			// If we are storing info as json the data will contain an array of objects
			if (is_object($data[0]))
			{
				foreach ($data as &$o)
				{
					$this->convertDataToString($o);
				}
			}
			$r = '<ul class="fabrikRepeatData"><li>' . implode('</li><li>', $data) . '</li></ul>';
		}
		else
		{
			$r = empty($data) ? '' : array_shift($data);
		}
		return $r;
	}

	/**
	 * Convert an object or array into a <ul>
	 *
	 * @param   mixed  &$o  data to convert
	 *
	 * @return  void
	 */

	protected function convertDataToString(&$o)
	{
		if (is_object($o))
		{
			$s = '<ul>';
			foreach ($o as $k => $v)
			{
				$s .= '<li>' . $v . '</li>';
			}
			$s .= '</ul>';
			$o = $s;
		}
	}

	/**
	 * Prepares the element data for CSV export
	 *
	 * @param   string  $data      element data
	 * @param   object  &$thisRow  all the data in the lists current row
	 *
	 * @return  string	formatted value
	 */

	public function renderListData_csv($data, &$thisRow)
	{
		return $data;
	}

	/**
	 * Determines if the element should be shown in the list view
	 *
	 * @param   object  &$listModel  list model
	 *
	 * @deprecated - not used
	 *
	 * @return  bool
	 */

	public function inTableFields(&$listModel)
	{
		$params = $this->getParams();
		$element = $this->getElement();
		$table = $listModel->getTable();
		$elFullName = $this->getFullName(true, false, false);
		if ($listModel->getOutPutFormat() === 'rss')
		{
			$bAddElement = ($params->get('show_in_rss_feed') == '1');
			/* if its the date ordering col we should add it to the list of allowed elements */
			if ($elFullName == $listModel->getParams()->get('feed_date', ''))
			{
				$bAddElement = true;
			}
		}
		else
		{
			$bAddElement = $element->show_in_list_summary;
		}
		if ($table->db_primary_key == $elFullName)
		{
			$listModel->_temp_db_key_addded = true;
		}
		return $bAddElement;
	}

	/**
	 * Builds some html to allow certain elements to display the option to add in new options
	 * e.g. pciklists, dropdowns radiobuttons
	 *
	 * @param   bool  $repeatCounter  repeat group counter
	 * @param   bool  $onlylabel      only show the label - overrides standard element settings
	 *
	 * @return  string
	 */

	protected function getAddOptionFields($repeatCounter, $onlylabel = false)
	{
		$params = $this->getParams();
		if (!$params->get('allow_frontend_addto'))
		{
			return;
		}
		$id = $this->getHTMLId($repeatCounter);
		$valueid = $id . '_ddVal';
		$labelid = $id . '_ddLabel';
		$value = '<input class="inputbox text" id="' . $valueid . '" name="addPicklistValue" />';
		$label = '<input class="inputbox text" id="' . $labelid . '" name="addPicklistLabel" />';
		$str[] = '<a href="#" title="' . JText::_('COM_FABRIK_ADD') . '" class="toggle-addoption">';
		$str[] = '<img src="' . COM_FABRIK_LIVESITE . 'media/com_fabrik/images/action_add.png" alt="' . JText::_('COM_FABRIK_ADD') . '"/>';
		$str[] = '</a>';
		$str[] = '<div style="clear:left">';
		$str[] = '<div class="addoption"><div>' . JText::_('COM_FABRIK_ADD_A_NEW_OPTION_TO_THOSE_ABOVE') . '</div>';
		if (!$params->get('allowadd-onlylabel') && $params->get('savenewadditions'))
		{
			// $$$ rob dont wrap in <dl> as the html is munged when rendered inside form tab template
			$str[] = '<label for="' . $valueid . '">' . JText::_('COM_FABRIK_VALUE') . '</label>';
			$str[] = $value;
			if (!$onlylabel)
			{
				$str[] = '<label for="' . $labelid . '">' . JText::_('COM_FABRIK_LABEL') . '</label>';
				$str[] = $label;
			}
		}
		else
		{
			$str[] = $label;
		}
		$str[] = '<input class="button btn" type="button" id="' . $id . '_dd_add_entry" value="' . JText::_('COM_FABRIK_ADD') . '" />';
		$str[] = $this->getHiddenField($id . "_additions", '', $id . "_additions");
		$str[] = '</div>';
		$str[] = '</div>';
		return implode("\n", $str);
	}

	/**
	 * Does the element force the form to submit via AJAX
	 *
	 * @deprecated - not used
	 *
	 * @return  bool	true if the element type forces the form to
	 */

	public function requiresAJAXSubmit()
	{
		return false;
	}

	/**
	 * Called on failed form validation.
	 * Ensures submitted form data is converted back into the format
	 * that the form would expect to get it in, if the data had been
	 * draw from the database record
	 *
	 * @param   string  $str  submitted form value
	 *
	 * @return  string	formated value
	 */

	public function toDbVal($str)
	{
		return $str;
	}

	/**
	 * Determine if the element should run its validation plugins on form submission
	 *
	 * @return  bool	default true
	 */

	public function mustValidate()
	{
		return true;
	}

	/**
	 * Get the name of the field to order the table data by
	 * can be overwritten in plugin class - but not currently done so
	 *
	 * @return string column to order by tablename___elementname and yes you can use aliases in the order by clause
	 */

	public function getOrderByName()
	{
		return $this->getFullName(false, true, false);
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
		return $rawval;
	}

	/**
	 * Store the element params
	 *
	 * @return  bool
	 */

	public function storeAttribs()
	{
		$element = $this->getElement();
		if (!$element)
		{
			return false;
		}
		$db = FabrikWorker::getDbo(true);
		$element->params = $this->getParams()->toString();
		$query = $db->getQuery(true);
		$query->update('#__{package}_elements')->set('params = ' . $db->quote($element->params))->where('id = ' . (int) $element->id);
		$db->setQuery($query);
		$res = $db->query();
		if (!$res)
		{
			JError::raiseError(500, $db->getErrorMsg());
		}
		return $res;
	}

	/**
	 * load a new set of default properites and params for the element
	 * can be overridden in plugin class
	 *
	 * @return  object	element (id = 0)
	 */

	public function getDefaultProperties()
	{
		$user = JFactory::getUser();
		$now = JFactory::getDate()->toSql();
		$this->setId(0);
		$item = $this->getElement();
		$item->plugin = $this->_name;
		$item->params = $this->getDefaultAttribs();
		$item->created = $now;
		$item->created_by = $user->get('id');
		$item->created_by_alias = $user->get('username');
		$item->published = '1';
		$item->show_in_list_summary = '1';
		$item->link_to_detail = '1';
		return $item;
	}

	/**
	 * Get a json encoded string of the element default parameters
	 *
	 * @return  string
	 */

	public function getDefaultAttribs()
	{
		$o = new stdClass;
		$o->rollover = '';
		$o->comment = '';
		$o->sub_default_value = '';
		$o->sub_default_label = '';
		$o->element_before_label = 1;
		$o->allow_frontend_addtocheckbox = 0;
		$o->database_join_display_type = 'dropdown';
		$o->joinType = 'simple';
		$o->join_conn_id = -1;
		$o->date_table_format = '%Y-%m-%d';
		$o->date_form_format = '%Y-%m-%d %H:%M:%S';
		$o->date_showtime = 0;
		$o->date_time_format = '%H:%M';
		$o->date_defaulttotoday = 1;
		$o->date_firstday = 0;
		$o->multiple = 0;
		$o->allow_frontend_addtodropdown = 0;
		$o->password = 0;
		$o->maxlength = 255;
		$o->text_format = 'text';
		$o->integer_length = 6;
		$o->decimal_length = 2;
		$o->guess_linktype = 0;
		$o->disable = 0;
		$o->readonly = 0;
		$o->ul_max_file_size = 16000;
		$o->ul_email_file = 0;
		$o->ul_file_increment = 0;
		$o->upload_allow_folderselect = 1;
		$o->fu_fancy_upload = 0;
		$o->upload_delete_image = 1;
		$o->make_link = 0;
		$o->fu_show_image_in_table = 0;
		$o->image_library = 'gd2';
		$o->make_thumbnail = 0;
		$o->imagepath = '/';
		$o->selectImage_root_folder = '/';
		$o->image_front_end_select = 0;
		$o->show_image_in_table = 0;
		$o->image_float = 'none';
		$o->link_target = '_self';
		$o->radio_element_before_label = 0;
		$o->options_per_row = 4;
		$o->ck_options_per_row = 4;
		$o->allow_frontend_addtoradio = 0;
		$o->use_wysiwyg = 0;
		$o->my_table_data = 'id';
		$o->update_on_edit = 0;
		$o->view_access = 1;
		$o->show_in_rss_feed = 0;
		$o->show_label_in_rss_feed = 0;
		$o->use_as_fake_key = 0;
		$o->icon_folder = -1;
		$o->use_as_row_class = 0;
		$o->filter_access = 1;
		$o->full_words_only = 0;
		$o->inc_in_adv_search = 1;
		$o->sum_on = 0;
		$o->sum_access = 0;
		$o->avg_on = 0;
		$o->avg_access = 0;
		$o->median_on = 0;
		$o->median_access = 0;
		$o->count_on = 0;
		$o->count_access = 0;
		return json_encode($o);
	}

	/**
	 * Do we need to include the lighbox js code
	 *
	 * @return  bool
	 */

	public function requiresLightBox()
	{
		return false;
	}

	/**
	 * Get Joomfish options
	 *
	 * @deprecated - not supporting joomfish
	 *
	 * @return  array	key=>value options
	 */

	public function getJoomfishOptions()
	{
		return array();
	}

	/**
	 * When filtering a table determine if the element's filter should be an exact match
	 * should take into account if the element is in a non-joined repeat group
	 *
	 * @param   string  $val  element value
	 *
	 * @return  bool
	 */

	public function isExactMatch($val)
	{
		$element = $this->getElement();
		$filterExactMatch = isset($val['match']) ? $val['match'] : $element->filter_exact_match;
		$group = $this->getGroup();
		if (!$group->isJoin() && $group->canRepeat())
		{
			$filterExactMatch = false;
		}
		return $filterExactMatch;
	}

	/**
	 * Not used
	 *
	 * @deprecated - not used
	 *
	 * @return boolean
	 */

	public function onAjax_getFolders()
	{
		$rDir = JRequest::getVar('dir');
		$folders = JFolder::folders($rDir);
		if ($folders === false)
		{
			// $$$ hugh - need to echo empty JSON array otherwise we break JS which assumes an array
			echo json_encode(array());
			return false;
		}
		sort($folders);
		echo json_encode($folders);
	}

	/**
	 * If used as a filter add in some JS code to watch observed filter element's changes
	 * when it changes update the contents of this elements dd filter's options
	 *
	 * @param   bool    $normal     is the filter a normal (true) or advanced filter
	 * @param   string  $container  container
	 *
	 * @return  void
	 */

	public function filterJS($normal, $container)
	{
		// Overwritten in plugin
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
		if ($this->isJoin() && $advancedMode)
		{
			return false;
		}
		$params = $this->getParams();
		$inc = $params->get('inc_in_search_all', 1);

		if ($inc == 2 && $advancedMode)
		{
			if ($this->ignoreSearchAllDefault)
			{
				$inc = false;
			}
			else
			{
				$format = $params->get('text_format');
				if ($format == 'integer' || $format == 'decimal')
				{
					$inc = false;
				}
			}
		}

		return ($inc == 1 || $inc == 2) ? true : false;
	}

	/**
	 * Get the value to use for graph calculations
	 * see timer which converts the value into seconds
	 *
	 * @param   string  $v  standard value
	 *
	 * @return  mixed calculation value
	 */

	public function getCalculationValue($v)
	{
		return (float) $v;
	}

	/**
	 * run on formModel::setFormData()
	 *
	 * @param   int  $c  repeat group counter
	 *
	 * @return void
	 */

	public function preProcess($c)
	{
	}

	/**
	 * Called when copy row list plugin called
	 *
	 * @param   mixed  $val  value to copy into new record
	 *
	 * @return mixed value to copy into new record
	 */

	public function onCopyRow($val)
	{
		return $val;
	}

	/**
	 * Called when save as copy form button clicked
	 *
	 * @param   mixed  $val  value to copy into new record
	 *
	 * @return  mixed  value to copy into new record
	 */

	public function onSaveAsCopy($val)
	{
		return $val;
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
		$listModel = $this->getListModel();
		$db = $listModel->getDb();
		$name = $this->getFullName(false, false, false);

		// $$$ rob - previous method to make query did not take into account prefilters on main table
		$tableName = $listModel->getTable()->db_table_name;
		$this->encryptFieldName($name);
		$where = trim($listModel->_buildQueryWhere(false));
		$where .= ($where == '') ? ' WHERE ' : ' AND ';
		$join = $listModel->_buildQueryJoin();
		$where .= $name . ' LIKE ' . $db->quote(addslashes('%' . JRequest::getVar('value') . '%'));
		$query = "SELECT DISTINCT($name) AS value, $name AS text FROM $tableName $join $where";
		$query = $listModel->pluginQuery($query);
		$db->setQuery($query);
		$tmp = $db->loadObjectList();
		foreach ($tmp as &$t)
		{
			$this->toLabel($t->text);
		}
		echo json_encode($tmp);
	}

	/**
	 * Get the table name that the element stores to
	 * can be the main table name or the joined table name
	 *
	 * @return  string
	 */

	protected function getTableName()
	{
		$listModel = $this->getListModel();
		$table = $listModel->getTable();
		$groupModel = $this->getGroup();
		if ($groupModel->isJoin())
		{
			$joinModel = $groupModel->getJoinModel();
			$join = $joinModel->getJoin();
			$name = $join->table_join;
		}
		else
		{
			$name = $table->db_table_name;
		}
		return $name;
	}

	/**
	 * Converts a raw value into its label equivalent
	 *
	 * @param   string  &$v  raw value
	 *
	 * @return  void
	 */

	protected function toLabel(&$v)
	{

	}

	/**
	 * Build group by query to append to list query
	 *
	 * @return  stringgetGroupByQuery
	 */

	public function getGroupByQuery()
	{
		return '';
	}

	/**
	 * Append element where statement to lists where array
	 *
	 * @param   array  &$whereArray  list models where statements
	 *
	 * @return  void
	 */

	public function appendTableWhere(&$whereArray)
	{
		$params = $this->getParams();
		$where = '';
		if ($params->get('append_table_where', false))
		{
			if (method_exists($this, '_buildQueryWhere'))
			{
				$where = trim($this->_buildQueryWhere(array()));
				if ($where != '')
				{
					$where = JString::substr($where, 5, JString::strlen($where) - 5);
					if (!in_array($where, $whereArray))
					{
						$whereArray[] = $where;
					}
				}
			}
		}
	}

	/**
	 * Used by validations
	 *
	 * @param   string  $data     this elements data
	 * @param   string  $cond     what condiion to apply
	 * @param   string  $compare  data to compare element's data to
	 *
	 * @return bool
	 */

	public function greaterOrLessThan($data, $cond, $compare)
	{
		if ($cond == '>')
		{
			return $data > $compare;
		}
		elseif ($cond == '>=')
		{
			return $data >= $compare;
		}
		elseif ($cond == '<')
		{
			return $data < $compare;
		}
		elseif ($cond == '<=')
		{
			return $data <= $compare;
		}
		elseif ($cond == '==')
		{
			return $data == $compare;
		}
		return false;
	}

	/**
	 * Can the element plugin encrypt data
	 *
	 * @return  bool
	 */

	public function canEncrypt()
	{
		return false;
	}

	/**
	 * Should the element's data be encrypted
	 *
	 * @return  bool
	 */

	public function encryptMe()
	{
		$params = $this->getParams();
		return ($this->canEncrypt() && $params->get('encrypt', false));
	}

	/**
	 * Format a number value
	 *
	 * @param   mixed  $data  (double/int)
	 *
	 * @return  string	formatted number
	 */

	protected function numberFormat($data)
	{
		$params = $this->getParams();
		if (!$params->get('field_use_number_format', false))
		{
			return $data;
		}
		$decimal_length = (int) $params->get('decimal_length', 2);
		$decimal_sep = $params->get('field_decimal_sep', '.');
		$thousand_sep = $params->get('field_thousand_sep', ',');

		// Workaround for params not letting us save just a space!
		if ($thousand_sep == '#32')
		{
			$thousand_sep = ' ';
		}
		return number_format((float) $data, $decimal_length, $decimal_sep, $thousand_sep);
	}

	/**
	 * Strip number format from a number value
	 *
	 * @param   mixed  $val  (double/int)
	 *
	 * @return  string	formatted number
	 */

	public function unNumberFormat($val)
	{
		$params = $this->getParams();
		if (!$params->get('field_use_number_format', false))
		{
			return $val;
		}
		// Might think about rounding to decimal_length, but for now let MySQL do it
		$decimal_length = (int) $params->get('decimal_length', 2);

		// Swap dec and thousand seps back to Normal People Decimal Format!
		$decimal_sep = $params->get('field_decimal_sep', '.');
		$thousand_sep = $params->get('field_thousand_sep', ',');
		$val = str_replace($thousand_sep, '', $val);
		$val = str_replace($decimal_sep, '.', $val);
		return $val;
	}

	/**
	 * Recursively get all linked children of an element
	 *
	 * @param   int  $id  element id
	 *
	 * @return  array
	 */

	protected function getElementDescendents($id = 0)
	{
		if (empty($id))
		{
			$id = $this->_id;
		}
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('id')->from('#__{package}_elements')->where('parent_id = ' . (int) $id);
		$db->setQuery($query);
		$kids = $db->loadObjectList();
		$all_kids = array();
		foreach ($kids as $kid)
		{
			$all_kids[] = $kid->id;
			$all_kids = array_merge($this->getElementDescendents($kid->id), $all_kids);
		}
		return $all_kids;
	}

	/**
	 * Get the actual table name to use when building select queries
	 * so if in a joined group get the joined to table's name otherwise return the
	 * table's db table name
	 *
	 * @return  string
	 */

	protected function actualTableName()
	{
		if (isset($this->actualTable))
		{
			return $this->actualTable;
		}
		$groupModel = $this->getGroup();
		if ($groupModel->isJoin())
		{
			$joinModel = $groupModel->getJoinModel();
			return $joinModel->getJoin()->table_join;
		}
		$listModel = $this->getListModel();
		$this->actualTable = $listModel->getTable()->db_table_name;
		return $this->actualTable;
	}

	/**
	 * When creating crud query in tableModel::storeRow() each element has the chance
	 * to alter the row id - used by sugarid plugin to fudge rowid
	 *
	 * @param   string  &$rowId  row id
	 *
	 * @deprecated - not used
	 *
	 * @return  void
	 */

	public function updateRowId(&$rowId)
	{
	}

	/**
	 * Fabrik3: moved to Admin Element Model
	 *
	 * @deprecated - not used
	 *
	 * @return  string	table name
	 */

	protected function getRepeatElementTableName()
	{
	}

	/**
	 * Is the element a repeating element
	 *
	 * @return  bool
	 */

	public function isRepeatElement()
	{
		return $this->isJoin();
	}

	/**
	 * fabrik3: moved to Admin Element Model
	 * if repeated element we need to make a joined db table to store repeated data in
	 *
	 * @depreciated
	 *
	 * @return  void
	 */

	public function createRepeatElement()
	{
	}

	/**
	 * get the element's associated join model
	 *
	 * @return  object	join model
	 */

	public function getJoinModel()
	{
		if (is_null($this->_joinModel))
		{
			$this->_joinModel = JModel::getInstance('Join', 'FabrikFEModel');

			// $$$ rob ensure we load the join by asking for the parents id, but then ensure we set the element id back to this elements id
			$this->_joinModel->getJoinFromKey('element_id', $this->getParent()->id);
			$this->_joinModel->_join->element_id = $this->getElement()->id;
		}
		return $this->_joinModel;
	}

	/**
	 * when saving an element pk we need to update any join which has the same params->pk
	 *
	 * @param   string  $oldName  (prevoius element name)
	 * @param   string  $newName  (new element name)
	 *
	 * @since	3.0.6
	 *
	 * @return  void
	 */

	public function updateJoinedPks($oldName, $newName)
	{
		$db = $this->getListModel()->getDb();
		$item = $this->getListModel()->getTable();
		$query = $db->getQuery(true);

		// Update linked lists id.
		$query->update('#__{package}_joins')->set('table_key = ' . $db->quote($newName))
			->where('join_from_table = ' . $db->quote($item->db_table_name))->where('table_key = ' . $db->quote($oldName));
		$db->setQuery($query);
		$db->query();

		// Update join pk parameter
		$query->clear();
		$query->select('id')->from('#__{package}_joins')->where('table_join = ' . $db->quote($item->db_table_name));
		$db->setQuery($query);
		$ids = $db->loadColumn();
		$teskPk = $db->quoteName($item->db_table_name . '.' . $oldName);
		$newPk = $db->quoteName($item->db_table_name . '.' . $newName);
		foreach ($ids as $id)
		{
			$join = FabTable::getInstance('Join', 'FabrikTable');
			$join->load($id);
			$params = new JRegistry($join->params);
			if ($params->get('pk') === $teskPk)
			{
				$params->set('pk', $newPk);
				$join->params = (string) $params;
				$join->store();
			}
		}
	}

	/**
	 * is the element a join
	 *
	 * @return  bool
	 */

	public function isJoin()
	{
		return $this->getParams()->get('repeat', false);
	}

	/**
	 * Used by inline edit table plugin
	 * If returns yes then it means that there are only two possible options for the
	 * ajax edit, so we should simply toggle to the alternative value and show the
	 * element rendered with that new value (used for yes/no element)
	 *
	 * @deprecated - only called in a deprecated element method
	 *
	 * @return  bool
	 */

	protected function canToggleValue()
	{
		return false;
	}

	/**
	 * encrypt an enitre columns worth of data, used when updating an element to encrypted
	 * with existing data in the column
	 *
	 * @return  null
	 */

	public function encryptColumn()
	{
		$secret = JFactory::getConfig()->get('secret');
		$listModel = $this->getListModel();
		$db = $listModel->getDb();
		$tbl = $this->actualTableName();
		$name = $this->getElement()->name;
		$db->setQuery("UPDATE $tbl SET " . $name . " = AES_ENCRYPT(" . $name . ", " . $db->quote($secret) . ")");
		$db->query();
	}

	/**
	 * decrypt an enitre columns worth of data, used when updating an element from encrypted to decrypted
	 * with existing data in the column
	 *
	 * @return  null
	 */

	public function decryptColumn()
	{
		// @TODO this query looks right but when going from encrypted blob to decrypted field the values are set to null
		$secret = JFactory::getConfig()->get('secret');
		$listModel = $this->getListModel();
		$db = $listModel->getDb();
		$tbl = $this->actualTableName();
		$name = $this->getElement()->name;
		$db->setQuery("UPDATE $tbl SET " . $name . " = AES_DECRYPT(" . $name . ", " . $db->quote($secret) . ")");
		$db->query();
	}

	/**
	 * PN 19-Jun-11: Construct an element error string.
	 *
	 * @return  string
	 */

	public function selfDiagnose()
	{
		$retStr = '';
		$this->_db->setQuery("SELECT COUNT(*) FROM #__fabrik_groups " . "WHERE (id = " . $this->_element->group_id . ");");
		$group_id = $this->_db->loadResult();

		if (!$group_id)
		{
			$retStr = 'No valid group assignment';
		}
		elseif (!$this->_element->plugin)
		{
			$retStr = 'No plugin';
		}
		elseif (!$this->_element->label)
		{
			$retStr = 'No element label';
		}
		else
		{
			$retStr = '';
		}

		return $retStr;
	}

	/**
	 * Inline edit
	 *
	 * @deprecated - should be in form view now as you can have > 1 element in inlineedit plugin
	 *
	 * @return null
	 */

	public function inLineEdit()
	{
		$listModel = JModel::getInstance('List', 'FabrikFEModel');
		$listid = JRequest::getInt('listid');
		$rowid = JRequest::getVar('rowid');
		$elementid = $this->getElement()->id;
		$listModel->setId($listid);
		$data = JArrayHelper::fromObject($listModel->getRow($rowid));
		$className = JRequest::getVar('plugin');
		if (!$this->canUse())
		{
			if (JRequest::getVar('task') != 'element.save')
			{
				echo JText::_("JERROR_ALERTNOAUTHOR");
				return;
			}
			$this->_editable = false;
		}
		else
		{
			$this->_editable = true;
		}
		$groupModel = $this->getGroup();

		$repeatCounter = 0;
		$html = '';
		$key = $this->getFullName();

		$template = JFactory::getApplication()->getTemplate();
		FabrikHelperHTML::addPath(JPATH_SITE . '/administrator/templates/' . $template . '/images/', 'image', 'list');

		// @TODO add acl checks here
		$task = JRequest::getVar('task');
		$saving = ($task == 'element.save' || $task == 'save') ? true : false;
		$htmlid = $this->getHTMLId($repeatCounter);
		if ($this->canToggleValue() && ($task !== 'element.save' && $task !== 'save'))
		{
			/**
			 * Ok for yes/no elements activating them (double clicking in cell)
			 * should simply toggle the stored value and return the new html to show
			 */
			$toggleValues = $this->getOptionValues();
			$currentIndex = array_search($data[$key], $toggleValues);
			if ($currentIndex === false || $currentIndex == count($toggleValues) - 1)
			{
				$nextIndex = 0;
			}
			else
			{
				$nextIndex = $currentIndex + 1;
			}
			$newvalue = $toggleValues[$nextIndex];
			$data[$key] = $newvalue;
			$shortkey = array_pop(explode('___', $key));
			$listModel->storeCell($rowid, $shortkey, $newvalue);
			$this->mode = 'readonly';
			$html = $this->renderListData($data[$key], $data);

			$script = array();
			$script[] = '<script type="text/javasript">';

			// Makes the inlined editor stop editing the cell
			$script[] = "Fabrik.fireEvent('fabrik.list.inlineedit.stopEditing');";
			$script[] = '</script>';

			echo $html . implode("\n", $script);
			return;
		}
		$listModel->clearCalculations();
		$listModel->doCalculations();
		$listRef = 'list_' . JRequest::getVar('listref');
		$doCalcs = "\n
		Fabrik.blocks['" . $listRef . "'].updateCals(" . json_encode($listModel->getCalculations()) . ')';

		if (!$saving)
		{
			/**
			 * so not an element with toggle values, so load up the form widget to enable user
			 * to select/enter a new value
			 * wrap in fabriKElement div to ensure element js code works
			 */
			$html .= '<div class="floating-tip" style="position:absolute">
			<ul class="fabrikElementContainer">';
			$html .= '<li class="fabrikElement">';
			$html .= $this->_getElement($data, $repeatCounter);
			$html .= '</li>';
			$html .= '</ul>';

			if (JRequest::getBool('inlinesave') || JRequest::getBool('inlinecancel'))
			{
				$html .= '<ul class="fabrik_buttons">';
				if (JRequest::getBool('inlinecancel') == true)
				{
					$html .= '<li class="ajax-controls inline-cancel">';
					$html .= '<a href="#" class="">';
					$html .= FabrikHelperHTML::image('delete.png', 'list', @$this->tmpl, array('alt' => JText::_('COM_FABRIK_CANCEL')))
						. '<span></span></a>';
					$html .= '</li>';
				}
				if (JRequest::getBool('inlinesave') == true)
				{
					$html .= '<li class="ajax-controls inline-save">';
					$html .= '<a href="#" class="">';
					$html .= FabrikHelperHTML::image('save.png', 'list', @$this->tmpl, array('alt' => JText::_('COM_FABRIK_SAVE')));
					$html .= '<span>' . JText::_('COM_FABRIK_SAVE') . '</span></a>';
					$html .= '</li>';
				}
				$html .= '</ul>';
			}

			$html .= '</div>';
			$onLoad = "Fabrik.inlineedit_$elementid = " . $this->elementJavascript($repeatCounter) . ";\n"
				. "Fabrik.inlineedit_$elementid.select();
			Fabrik.inlineedit_$elementid.focus();
			Fabrik.inlineedit_$elementid.token = '" . JSession::getFormToken() . "';\n";

			$onLoad .= "Fabrik.fireEvent('fabrik.list.inlineedit.setData');\n";
			$srcs = array();
			$this->formJavascriptClass($srcs);
			FabrikHelperHTML::script($srcs, $onLoad);
		}
		else
		{
			$html .= $this->renderListData($data[$key], $data);
			$html .= '<script type="text/javasript">';
			$html .= $doCalcs;
			$html .= "</script>\n";
		}
		echo $html;
	}

	/**
	 * Shortcut to get plugin manager
	 *
	 * @since 3.0b
	 *
	 * @deprecated
	 *
	 * @return  object  plugin manager
	 */

	public function getPluginManager()
	{
		return FabrikWorker::getPluginManager();
	}

	/**
	 * When the element is a repeatble join (e.g. db join checkbox) then figure out how many
	 * records have been selected
	 *
	 * @param   array   $data   data
	 * @param   object  $oJoin  join current join
	 *
	 * @since 3.0rc1
	 *
	 * @return  int  number of records selected
	 */

	public function getJoinRepeatCount($data, $oJoin)
	{
		return count(JArrayHelper::getValue($data, $oJoin->table_join . '___id', array()));
	}

	/**
	 * When we do ajax requests from the element - as the plugin controller uses the J dispatcher
	 * the element hasnt loaded up itself, so any time you have a function onAjax_doSomething() call this
	 * helper function first to load up the element. Otherwise things like parameters will not be loaded
	 *
	 * @since 3.0rc1
	 *
	 * @return  null
	 */

	protected function loadMeForAjax()
	{
		$this->_form = JModel::getInstance('form', 'FabrikFEModel');
		$this->_form->setId(JRequest::getVar('formid'));
		$this->setId(JRequest::getInt('element_id'));
		$this->getElement();
	}

	/**
	 * get the element's cell class
	 *
	 * @since 3.0.4
	 *
	 * @return  string	css classes
	 */

	public function getCellClass()
	{
		$params = $this->getParams();
		$classes = array();
		$classes[] = $this->getFullName(false, true, false);
		$classes[] = 'fabrik_element';
		$classes[] = 'fabrik_list_' . $this->getListModel()->getId() . '_group_' . $this->getGroupModel()->getId();
		$c = $params->get('tablecss_cell_class', '');
		if ($c !== '')
		{
			$classes[] = $c;
		}
		return implode(' ', $classes);
	}

	/**
	 * get the elements list heading class
	 *
	 * @since 3.0.4
	 *
	 * @return  string	css classes
	 */

	public function getHeadingClass()
	{
		$params = $this->getParams();
		$classes = array();
		$classes[] = 'fabrik_ordercell';
		$classes[] = $this->getFullName(false, true, false);
		$classes[] = $this->getElement()->id . '_order';
		$classes[] = 'fabrik_list_' . $this->getListModel()->getId() . '_group_' . $this->getGroupModel()->getId();
		$classes[] = $this->getParams()->get('tablecss_header_class');
		return implode(' ', $classes);
	}

	/**
	 * convert XML format data into fabrik data (used by web services)
	 *
	 * @param   mixed  $v  data
	 *
	 * @return  mixed  data
	 */

	public function fromXMLFormat($v)
	{
		return $v;
	}

	/**
	 * Allows the element to pre-process a rows data before and join mergeing of rows
	 * occurs. Used in calc element to do cals on actual row rather than merged row
	 *
	 * @param   string  $data  elements data for the current row
	 * @param   object  $row   current row's data
	 *
	 * @since	3.0.5
	 *
	 * @return  string	formatted value
	 */

	public function preFormatFormJoins($data, $row)
	{
		return $data;
	}

	/**
	 * return an array of parameter names which should not get updated if a linked element's parent is saved
	 * notably any paramter which references another element id should be returned in this array
	 * called from admin element model updateChildIds()
	 * see cascadingdropdown element for example
	 *
	 * @return  array	parameter names to not alter
	 */

	public function getFixedChildParameters()
	{
		return array();
	}

	/**
	 * Set row class
	 *
	 * @param   array  &$data  row data to set class for
	 *
	 * @return  null
	 */

	public function setRowClass(&$data)
	{
		$rowclass = $this->getParams()->get('use_as_row_class');
		if ($rowclass == 1)
		{
			$col = $this->getFullName(false, true, false);
			$rawcol = $col . '_raw';
			foreach ($data as $groupk => $group)
			{
				for ($i = 0; $i < count($group); $i++)
				{
					$c = false;
					if (isset($data[$groupk][$i]->data->$rawcol))
					{
						$c = $data[$groupk][$i]->data->$rawcol;
					}
					elseif (isset($data[$groupk][$i]->data->$col))
					{
						$c = $data[$groupk][$i]->data->$col;
					}
					if ($c !== false)
					{
						$c = preg_replace('/[^A-Z|a-z|0-9]/', '-', $c);
						$c = FabrikString::ltrim($c, '-');
						$c = FabrikString::rtrim($c, '-');

						// $$$ rob 24/02/2011 can't have numeric class names so prefix with element name
						if (is_numeric($c))
						{
							$c = $this->getElement()->name . $c;
						}
						$data[$groupk][$i]->class .= ' ' . $c;
					}
				}
			}
		}
	}

	/**
	 * unset the element models access
	 *
	 * @return  null
	 */

	public function clearAccess()
	{
		unset($this->access);
	}

	/**
	 * Forces reset of defaults, etc.
	 *
	 * @return  null
	 */

	public function reset()
	{
		$this->defaults = null;
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
		return true;
	}

	/**
	 * Create the where part for the query that selects the list options (not applicable for most elements except joins)
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
		return '';
	}

}
