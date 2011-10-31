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
jimport('joomla.filesystem.file');

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

	/** @var bol does the element have a label */
	var $hasLabel = true;

	/** @var bol does the element contain sub elements e.g checkboxes radiobuttons */
	var $hasSubElements = false;

	var $_imageExtensions = array('jpg', 'jpeg', 'gif', 'bmp', 'png');

	/** @var bol is the element in a detailed view? **/
	var $_inDetailedView = false;

	var $defaults = null;

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

	protected $fieldDesc = 'VARCHAR(%s)';

	protected $fieldSize = '255';

	/**
	 * Method to set the element id
	 *
	 * @access	public
	 * @param	int	element ID number
	 */

	public function setId($id)
	{
		// Set new element ID
		$this->_id = $id;
	}

	/**
	 * get the element table object
	 *
	 * @param bol default false - force load the element
	 * @return object element table
	 */

	function &getElement($force = false)
	{
		if (!$this->_element || $force) {
			JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables');
			$row = FabTable::getInstance('Element', 'FabrikTable');
			$row->load($this->_id);
			$this->_element = $row;
		}
		return $this->_element;
	}

	public function getParent()
	{
		if (!isset($this->parent)) {
			$element = $this->getElement();
			if ((int)$element->parent_id !== 0) {
				$this->parent = FabTable::getInstance('element', 'FabrikTable');
				$this->parent->load($element->parent_id);
			} else {
				$this->parent = $element;
			}
		}
		return $this->parent;
	}

	/**
	 * bind data to the _element variable - if possible we should run one query to get all the forms
	 * element data and then iterrate over that, creating an element plugin for each row
	 * and bind each record to that plugins _element. This is instead of using getElement() which
	 * reloads in the element increasing the number of queries run
	 *
	 * @param mixed $row (object or assoc array)
	 * @return object element table
	 */

	function bindToElement(&$row)
	{
		if (!$this->_element) {
			JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables');
			$this->_element = FabTable::getInstance('Element', 'FabrikTable');
		}
		if (is_object($row)) {
			$row = JArrayHelper::fromObject($row);
		}
		$this->_element->bind($row);
		return $this->_element;
	}

	/**
	 * set the context in which the element occurs
	 *
	 * @param object group table
	 * @param object form model
	 * @param object table model
	 */

	function setContext($groupModel, $formModel, &$listModel)
	{
		//dont assign these with &= as they already are when passed into the func
		$this->_group 	=& $groupModel;
		$this->_form 		=& $formModel;
		$this->_list 	=& $listModel;
	}

	/**
	 * get the element's fabrik table model
	 *
	 * @return object table model
	 */

	function getListModel()
	{
		if (is_null($this->_list)) {
			$groupModel = $this->getGroup();
			$this->_list = $groupModel->getListModel();
		}
		return $this->_list;
	}

	/**
	 * load in the group model
	 *
	 * @param int group id
	 * @return object group model
	 */

	function &getGroup($group_id = null)
	{

		if (is_null($group_id)) {
			$element = $this->getElement();
			$group_id = $element->group_id;
		}
		if (is_null($this->_group) || $this->_group->getId() != $group_id) {
			$model = JModel::getInstance('Group', 'FabrikFEModel');
			$model->setId($group_id);
			$model->getGroup();
			$this->_group = $model;
		}
		return $this->_group;
	}

	/**
	 * get the elements form model
	 * @deprecated use getFormModel
	 * @return object form model
	 */

	function getForm()
	{
		return $this->getFormModel();
	}

	function getFormModel()
	{
		if (is_null($this->_form)) {
			$listModel = $this->getListModel();
			$table = $listModel->getTable();
			$this->_form = JModel::getInstance('form', 'FabrikFEModel');
			$this->_form->setId($table->form_id);
			$this->_form->getForm();
		}
		return $this->_form;

	}

	/**
	 * shows the RAW list data - can be overwritten in plugin class
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderRawListData($data, $oAllRowsData)
	{
		return $data;
	}

	/**
	 * replace labels shown in table view with icons (if found)
	 * @since 3.0 - icon_folder is a bool - search through template folders for icons
	 * @param string data
	 * @return string data
	 */

	function _replaceWithIcons($data)
	{
		if ($data == '') {
			$this->iconsSet = false;
			return $data;
		}

		$params =$this->getParams();
		if ($params->get('icon_folder', 0) == 0) {
			$this->iconsSet = false;
			return $data;
		}
		$iconfile = $params->get('icon_file'); //Jaanus added this and following if/else; sometimes we need permanent image (e.g logo of the website where the link always points, like Wikipedia's W)

		if ($iconfile == '') {	
		$cleanData = FabrikString::clean($data); //this is original	
		}	
		else {	
		$cleanData = $iconfile;
		}

		foreach ($this->_imageExtensions as $ex) {
			$f = JPath::clean($cleanData.'.'.$ex);
			$img = FabrikHelperHTML::image($cleanData.'.'.$ex);
			if ($img !== '') {
				$this->iconsSet = true;
				return $img;
			}
		}
		$this->iconsSet = false;
		return $data;
	}

	/**
	 * @since 2.1.1
	 * build the sub query which is used when merging in in repeat element records from their joined table into the one field.
	 * Overwritten in database join element to allow for building the join to the table containing the stored values required labels
	 * @param string $jkey
	 * @return string sub query
	 */

	protected function _buildQueryElementConcat($jkey)
	{
		$jointable = $this->getJoinModel()->getJoin()->table_join;
		$dbtable = $this->actualTableName();
		$db = JFactory::getDbo();
		$table = $this->getListModel()->getTable();
		//$fullElName = $db->nameQuote("$dbtable" . "___" . $this->_element->name);//wasnt working for filepload elements in list view.
		$fullElName = $db->nameQuote($jointable."___".$this->_element->name);
		return "(SELECT GROUP_CONCAT(".$jkey." SEPARATOR '".GROUPSPLITTER."') FROM $jointable WHERE parent_id = " . $table->db_primary_key . ") AS $fullElName";
	}

	/**
	 * @since 2.1.1
	 * build the sub query which is used when merging in in repeat element records from their joined table into the one field.
	 * Overwritten in database join element to allow for building the join to the talbe containing the stored values required ids
	 * @param string $jkey
	 * @return string sub query
	 */

	protected function _buildQueryElementConcatId()
	{
		$jointable = $this->getJoinModel()->getJoin()->table_join;
		$dbtable = $this->actualTableName();
		$db = JFactory::getDbo();
		$table = $this->getListModel()->getTable();
		$fullElName = $db->nameQuote($jointable."___".$this->_element->name."_raw");
		return "(SELECT GROUP_CONCAT(id SEPARATOR '".GROUPSPLITTER."') FROM $jointable WHERE parent_id = " . $table->db_primary_key . ") AS $fullElName";
	}

	/**
	 * @since 2.1.1
	 * used in form model setJoinData.
	 * @return array of element names to search data in to create join data array
	 * can be overridden in element - see database join for example
	 */

	public function getJoinDataNames()
	{
		$group = $this->getGroup()->getGroup();
		$name = $this->getFullName(false, true, false);
		$fv_name = 'join['.$group->join_id.']['.$name.']';
		$rawname = $name."_raw";
		$fv_rawname = 'join['.$group->join_id.']['.$rawname.']';
		return array(
		array($name, $fv_name),
		array($rawname, $fv_rawname)
		);
	}

	/**
	 * can be overwritten in the plugin class - see database join element for example
	 * @param array
	 * @param array
	 * @param array options
	 */

	function getAsField_html(&$aFields, &$aAsFields, $opts = array())
	{
		$dbtable = $this->actualTableName();
		$db = FabrikWorker::getDbo();
		$table = $this->getListModel()->getTable();
		$fullElName = JArrayHelper::getValue($opts, 'alias', $db->nameQuote("$dbtable" . "___" . $this->_element->name));
		$k = $db->nameQuote($dbtable).".".$db->nameQuote($this->_element->name);
		$secret = JFactory::getConfig()->getValue('secret');
		if ($this->encryptMe()) {
			$k = "AES_DECRYPT($k, ".$db->Quote($secret).")";
		}

		if ($this->isJoin()) {

			$jkey = $this->_element->name;
			if ($this->encryptMe()) {
				$jkey = "AES_DECRYPT($jkey, ".$db->Quote($secret).")";
			}
			$jointable = $this->getJoinModel()->getJoin()->table_join;
			$fullElName = JArrayHelper::getValue($opts, 'alias', $db->nameQuote("$jointable" . "___" . $this->_element->name));
			$str = $this->_buildQueryElementConcat($jkey);
		} else {
			$str = "$k AS $fullElName";
		}
		if ($table->db_primary_key == $fullElName) {
			array_unshift($aFields, $fullElName);
			array_unshift($aAsFields, $fullElName);
		} else {
			if (!in_array($str, $aFields)) {
				$aFields[] 	= $str;
				$aAsFields[] = $fullElName;
			}
			$k = $db->nameQuote($dbtable).".".$db->nameQuote($this->_element->name);
			if ($this->encryptMe()) {
				$k = "AES_DECRYPT($k, ".$db->Quote($secret).")";
			}

			if ($this->isJoin()) {
				$str = $this->_buildQueryElementConcatId();
				$aFields[] 	= $str;
				$aAsFields[] = $fullElName;

				$fullElName = $db->nameQuote($jointable."___params");
				$str = "(SELECT GROUP_CONCAT(params SEPARATOR '".GROUPSPLITTER."') FROM $jointable WHERE parent_id = " . $table->db_primary_key . ") AS $fullElName";
				$aFields[] 	= $str;
				$aAsFields[] = $fullElName;
			} else {
				$fullElName = $db->nameQuote($dbtable."___".$this->_element->name."_raw");
				$str = "$k AS $fullElName";
			}
			if (!in_array($str, $aFields)) {
				$aFields[] 	= $str;
				$aAsFields[] = $fullElName;
			}
		}
	}

	public function getRawColumn($useStep = true)
	{
		$n = $this->getFullName(false, $useStep, false);
		$n .= "_raw`";
		return $n;
	}

	/**
	 * check user can view the read only element & view in table view
	 * @return bol can view or not
	 */

	function canView()
	{
		if (!is_object($this->_access) || !array_key_exists('view', $this->_access)) {
			$user = JFactory::getUser();
			$groups = $user->authorisedLevels();
			$this->_access->view = in_array($this->getParams()->get('view_access'), $groups);
		}
		return $this->_access->view;
	}

	/**
	 * check user can use the active element
	 * @return bol can use or not
	 */

	function canUse()
	{
		$element = $this->getElement();
		if (!is_object($this->_access) || !array_key_exists('use', $this->_access)) {
			$user = JFactory::getUser();
			$groups = $user->getAuthorisedViewLevels();
			$this->_access->use = in_array($this->getElement()->access, $groups);
		}
		return $this->_access->use;
	}

	/**
	 * Defines if the user can use the filter related to the element
	 *
	 * @return bol true if you can use
	 */

	function canUseFilter()
	{
		$params = $this->getParams();
		$element = $this->getElement();
		if (!is_object($this->_access) || !array_key_exists('filter', $this->_access)) {
			$user = JFactory::getUser();
			$groups = $user->authorisedLevels();
			$this->_access->filter = in_array($this->getParams()->get('filter_access'), $groups);
		}
		return $this->_access->filter;
	}

	/* overwritten in add on classes */

	function setIsRecordedInDatabase()
	{
		return true;
	}

	/** overwrite in plugin **/

	function validate($data, $repeatCounter = 0)
	{
		return true;
	}

	function getValidationErr()
	{
		return $this->_validationErr;
	}

	/**
	 * can be overwritten by plugin class
	 *
	 * Examples of where this would be overwritten include drop downs whos "please select" value might be "-1"
	 * @param string data posted from form to check
	 * @param int repeat group counter
	 * @return bol if data is considered empty then returns true
	 */

	function dataConsideredEmpty($data, $repeatCounter)
	{
		if ($data == '') {
			return true;
		}
		return false;
	}

	/**
	 * can be overwritten by plugin class
	 *
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 * @param int repeat group counter
	 * @return array html ids to watch for validation
	 */

	function getValidationWatchElements($repeatCounter)
	{
		$id 			= $this->getHTMLId($repeatCounter);
		$ar = array(
			'id' 			=> $id,
			'triggerEvent' => 'blur'
		);
		return array($ar);
	}

	/**
	 * can be overwritten in add on classes
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat($val, $data)
	{
		if (is_array($val) && count($val) === 1) {
			//$val = $val[0];
			$val = array_shift($val);
		}
		if (is_array($val) || is_object($val)) {
			return json_encode($val);
		} else {
			return $val;
		}
	}

	/**
	 * can be overwritten in add on classes
	 * @param array data
	 * @param string table column heading
	 * @param bool data is raw
	 * @return array data
	 */

	function prepareCSVData($data, $key, $is_raw = false)
	{
		return $data;
	}

	/**
	 * can be overwritten in plugin class
	 * determines if the data in the form element is used when updating a record
	 * @param mixed element forrm data
	 * @return bol - true if ignored on update, default = false
	 */

	function ignoreOnUpdate($val = null)
	{
		return false;
	}

	/**
	 * can be overwritten in plugin class
	 * determines if the element can contain data used in sending receipts, e.g. fabrikfield returns true
	 */

	function isReceiptElement()
	{
		return false;
	}

	/**
	 * can be overwritten in adddon class
	 *
	 * checks the posted form data against elements INTERNAL validataion rule - e.g. file upload size / type
	 * @param array existing errors
	 * @param object group model
	 * @param object form model
	 * @param array posted data
	 * @return array updated errors
	 */

	function validateData($aErrors, &$groupModel, &$formModel, $data)
	{
		return $aErrors;
	}

	/**
	 * can be overwritten by plugin class
	 * determines the label used for the browser title
	 * in the form/detail views
	 * @param array data
	 * @param int when repeating joinded groups we need to know what part of the array to access
	 * @param array options
	 * @return string default value
	 */

	function getTitlePart($data, $repeatCounter = 0, $opts = array())
	{
		return $this->getValue($data, $repeatCounter, $opts);
	}

	/**
	 * this really does get just the default value (as defined in the element's settings)
	 * @param array data to use as parsemessage for placeholder
	 * @return unknown_type
	 */

	function getDefaultValue($data = array())
	{
		if (!isset($this->_default)) {
			$w = new FabrikWorker();
			$element = $this->getElement();
			$default = $w->parseMessageForPlaceHolder($element->default, $data);
			if ($element->eval == "1") {
				FabrikHelperHTML::debug($default, 'element eval default:' . $element->label);
				$default = @eval(stripslashes($default));
				FabrikWorker::logEval($default, 'Caught exception on eval of ' . $element->name . ': %s');
			}
			$this->_default = $default;
		}
		return $this->_default;
	}

	/**
	 * can be overwritten in plug-in class (see link element)
	 * @param array $value, previously encrypted values
	 * @param array data
	 * @param int repeat group counter
	 * @return null
	 */

	function getValuesToEncrypt(&$values, $data, $c)
	{
		$name = $this->getFullName(false, true, false);
		$group = $this->getGroup();
		if ($group->canRepeat()) {
			if (!array_key_exists($name, $values)) {
				$values[$name]['data'] = array();
			}
			$values[$name]['data'][$c] = $this->getValue($data, $c);
		} else {
			$values[$name]['data'] = $this->getValue($data, $c);
		}
	}

	/**
	 * element plugin specific method for setting unecrypted values baack into post data
	 * @param aray $post data passed by ref
	 * @param string $key
	 * @param string $data elements unencrypted data
	 * @return null
	 */

	function setValuesFromEncryt(&$post, $key, $data)
	{
		$group = $this->getGroup();
		if ($group->isJoin()) {
			$key = 'join.'.$group->getGroup()->join_id.'.'.$key;
			FArrayHelper::setValue($post, $key, $data);
			FArrayHelper::setValue($_REQUEST, $key, $data);
		}else{
			FArrayHelper::setValue($post, $key, $data);
			FArrayHelper::setValue($_REQUEST, $key, $data);
		}
		// $$$rob even though $post is passed by reference - by adding in the value
		// we arent actually modifiying the $_POST var that post was created from
		JRequest::setVar($key, $data);
	}

	/**
	 * used in json when in detaile view currently overwritten in db join element
	 * @param $data
	 * @param $repeatCounter
	 */
	function getROValue($data, $repeatCounter = 0)
	{
		return $this->getValue($data, $repeatCounter);
	}

	/**
	 * can be overwritten by plugin class
	 * determines the value for the element in the form view
	 * @param array data
	 * @param int when repeating joinded groups we need to know what part of the array to access
	 * @param array options
	 * @return string value
	 */

	function getValue($data, $repeatCounter = 0, $opts = array())
	{
		//@TODO rename $this->defaults to $this->values
		if (!isset($this->defaults)) {
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults)) {
			$groupModel = $this->getGroup();
			$group = $groupModel->getGroup();
			$joinid = $this->isJoin() ? $this->getJoinModel()->getJoin()->id : $group->join_id;
			$formModel = $this->getFormModel();
			$element = $this->getElement();

			// $$$rob - if no search form data submitted for the search element then the default
			// selection was being applied instead
			//otherwise get the default value so if we don't find the element's value in $data we fall back on this value
			$value = JArrayHelper::getValue($opts, 'use_default', true) == false ? '' : $this->getDefaultValue($data);

			$name = $this->getFullName(false, true, false);
			$rawname = $name . "_raw";
			if ($groupModel->isJoin() || $this->isJoin()) {
				// $$$ rob 22/02/2011 this test barfed on fileuploads which weren't repeating
				//if ($groupModel->canRepeat() || !$this->isJoin()) {
				if ($groupModel->canRepeat()) {
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists($name, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name])) {
						$value = $data['join'][$joinid][$name][$repeatCounter];
					} else {
						if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists($name, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name])) {
							$value = $data['join'][$joinid][$name][$repeatCounter];
						}
					}
				} else {
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists($name, $data['join'][$joinid])) {
						$value = $data['join'][$joinid][$name];
					} else {
						if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists($rawname, $data['join'][$joinid])) {
							$value = $data['join'][$joinid][$rawname];
						}
					}
					// $$$ rob if you have 2 tbl joins, one repeating and one not
					// the none repeating one's values will be an array of duplicate values
					// but we only want the first value
					if (is_array($value) && !$this->isJoin()) {
						$value = array_shift($value);
					}
				}

			} else {
				if ($groupModel->canRepeat()) {
					//repeat group NO join
					$thisname = $name;
					if (!array_key_exists($name, $data)) {
						$thisname = $rawname;
					}
					if (array_key_exists($thisname, $data)) {
						if (is_array($data[$thisname])) {
							//occurs on form submission for fields at least
							$a = $data[$thisname];
						} else {
							//occurs when getting from the db
							$a = json_decode($data[$thisname]);
						}
						$value = JArrayHelper::getValue($a, $repeatCounter, $value);
					}

				} else {
					$value = !is_array($data) ? $data : JArrayHelper::getValue($data, $name, JArrayHelper::getValue($data, $rawname, $value));
				}
			}
			if (is_array($value) && !$this->isJoin()) {
				$value = implode(',', $value);
			}
			// $$$ hugh - don't know what this is for, but was breaking empty fields in repeat
			// groups, by rendering the //..*..// seps.
			// if ($value === '') { //query string for joined data
			if ($value === '' && !$groupModel->canRepeat()) {
				//query string for joined data
				$value = JArrayHelper::getValue($data, $name);
			}
			if (is_array($value) && !$this->isJoin()) {
				$value = implode(',', $value);
			}
			//@TODO perhaps we should change this to $element->value and store $element->default as the actual default value
			//stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1) {
				FabrikWorker::getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
			}
			$this->defaults[$repeatCounter] = $value;
		}
		return $this->defaults[$repeatCounter];
	}

	/**
	 * is the element hidden or not - if not set then return false
	 *
	 * @return bol
	 */

	function isHidden()
	{
		$element = $this->getElement();
		return ($element->hidden == true) ? true : false;
	}

	/**
	 * @abstract
	 * used in things like date when its id is suffixed with _cal
	 * called from getLabel();
	 * @param initial id
	 */
	protected function modHTMLId(&$id){

	}

	/**
	 * can be overwritten in the plugin class
	 * @param int repeat counter
	 * @param string template
	 */

	function getLabel($repeatCounter, $tmpl = '')
	{
		$config = JComponentHelper::getParams('com_fabrik');
		$bLabel = $config->get('fbConf_wysiwyg_label', false) ? false : $this->get('hasLabel');

		$element = $this->getElement();
		$elementHTMLId = $this->getHTMLId($repeatCounter);
		$this->modHTMLId($elementHTMLId);

		$view = JRequest::getVar('view', 'form');
		if ($view == 'form' && ! ($this->canUse() || $this->canView())) {
			return '';
		}
		if ($view == 'details' && !$this->canView()) {
			return '';
		}
		$params = $this->getParams();
		$elementid = "fb_el_" . $elementHTMLId;
		$str = '';

		if ($this->canView() || $this->canUse()) {

			$rollOver = $params->get('rollover') !== '' && $this->getFormModel()->getParams()->get('tiplocation', 'tip') == 'tip';
			$labelClass = "fabrikLabel ";
			if (empty($element->label)) {
				$labelClass .= " fabrikEmptyLabel";
			}

			if ($rollOver) {
				$labelClass .= " fabrikHover";
			}
			if ($bLabel && !$this->isHidden()) {
				$str .= '<label for="'.$elementHTMLId.'" class="'.$labelClass.'">';
			}
			$l = $element->label;
			if ($rollOver) {
				$l .= FabrikHelperHTML::image('questionmark.png', 'form', $tmpl);
			}
			if ($this->_editable) {
				$validations = $this->getValidations();
				foreach ($validations as $validation) {
					$l .= $validation->getIcon($this, $repeatCounter, $tmpl);
				}
			}
			$model = $this->getFormModel();
			$str .= $this->rollover($l, $model->_data);
			if ($bLabel && !$this->isHidden()) {
				$str .= "</label>";
			}
		}
		return $str;
	}

	protected function addErrorHTML($repeatCounter, $tmpl = '')
	{
		$err = $this->_getErrorMsg($repeatCounter);
		$str = '<span class="fabrikErrorMessage">';
		if ($err !== '') {
			$err = '<span>'.$err.'</span>';
			$str .= '<a href="#" class="fabrikTip" title="'.$err.'">'.
			FabrikHelperHTML::image('alert.png', 'form', $tmpl).
			'</a>';
		}
		$str .= '</span>';
		return $str;
	}

	/**
	 * add tips on element labels
	 * does ACL check on element's label in details setting
	 * @param string label
	 * @param array row data
	 * @return string label with tip
	 */

	protected function rollover($txt, $data = array(), $mode = 'form')
	{
		if (is_object($data)) {
			$data = JArrayHelper::fromObject($data);
		}
		$params = $this->getParams();
		$formModel = $this->getFormModel();
		if ($formModel->getParams()->get('tiplocation', 'tip') == 'tip' && (($mode == 'form' && ($formModel->_editable || $params->get('labelindetails', true))) || $params->get('labelinlist', false))) {
			$rollOver = $this->getTip($data);
			$pos = $params->get('tiplocation', 'top');
			$opts = "{position:'$pos'}";
			return ($rollOver != '') ? '<span class="fabrikTip" opts="'.$opts.'" title="'.$rollOver.'">'.$txt.'</span>' : $txt;
		} else {
			return $txt;
		}
	}

	protected function getTip($data = null)
	{
		if (is_null($data)) {
			$data = $this->getFormModel()->_data;
		}
		$params = $this->getParams();
		$w = new FabrikWorker();
		$tip = $w->parseMessageForPlaceHolder($params->get('rollover'), $data);
		if ($params->get('tipseval')) {
			$tip = @eval($tip);
			FabrikWorker::logEval($tip, 'Caught exception on eval of ' . $this->getElement()->name . ' tip: %s');
		}
		$tip = trim(JText::_($tip));
		$tip = htmlspecialchars($tip, ENT_QUOTES);
		return $tip;
	}

	/**
	 * used for the name of the filter fields
	 * For element this is an alias of getFullName()
	 * Overridden currently only in databasejoin class
	 *
	 * @return string element filter name
	 */

	function getFilterFullName()
	{
		return FabrikString::safeColName($this->getFullName(false, true, false));
	}

	/**
	 * refractored from group class - can be overwritten by plugins
	 * If already run then stored value returned
	 * @param bol add join[joinid][] to element name (default true)
	 * @param bol concat name with form's step element (true) or with '.' (false) default true
	 * @param bol include "[]" at the end of the name (used for repeat group elements) default true
	 */

	function getFullName($includeJoinString = true, $useStep = true, $incRepeatGroup = true)
	{
		$db	= FabrikWorker::getDbo();
		$groupModel = $this->getGroup();
		$formModel 	= $this->getFormModel();
		$listModel = $this->getListModel();
		$element = $this->getElement();

		$key = $element->name . $groupModel->get('id') . "_" . $formModel->getId() . "_" .$includeJoinString . "_" . $useStep . "_" . $incRepeatGroup;
		if (isset($this->_aFullNames[$key])) {
			return $this->_aFullNames[$key];
		}
		$table = $listModel->getTable();
		$db_table_name = $table->db_table_name;

		$thisStep = ($useStep) ? $formModel->_joinTableElementStep : '.';
		$group = $groupModel->getGroup();
		if ($groupModel->isJoin() || $this->isJoin()) {
			if ($this->isJoin()) {
				$joinModel = $this->getJoinModel();
			} else {
				$joinModel = $groupModel->getJoinModel();
			}
			$join = $joinModel->getJoin();
			if ($includeJoinString) {
				$fullName = 'join['.$join->id.']['.$join->table_join.$thisStep.$element->name.']';
			} else {
				$fullName = $join->table_join.$thisStep.$element->name;
			}
		} else {
			//$$$rob this is a HUGH query strain e.g. 20 - 50 odd extra select * from jos_fabrik_lists where id = x!
			//when rendering a table view. Hugh I guess you put this in for a specific fix but I don't see why?
			//I've tried storing the table name in $db_table_name at the beginning of the function to see if that might help
			// whatever case was giving you an error but I doubt that will fix things

			// $$$ hugh - ooops - but it's to do with what now appears to be a PHP version issue, where I'm getting
			// all kinds of screwed up results on my joined tables, with the wrong table name being used. Seems to be
			// as soon as we do a getTable on the joined table, then the joined table name is used everywhere,
			// regardless of which model we pass around. So what should be maintable___foo ends up as joinedtable___foo
			// I'm still in the process of trying to nail this down, and some change you made recently seems to have
			// fixed at least the more obvious occurences of this problem.

			//$table = $listModel->getTable(true);
			//$fullName = $table->db_table_name . $thisStep . $element->name;
			$fullName = $db_table_name . $thisStep . $element->name;
		}
		if ($groupModel->canRepeat() == 1 && $incRepeatGroup) {
			$fullName .= "[]";
		}

		$this->_aFullNames[$key] = $fullName;
		return $fullName;
	}


	/**
	 * - can be overwritten by plugins
	 * @param bol add join[joinid][] to element name (default true)
	 * @param bol concat name with form's step element (true) or with '.' (false) default true
	 *
	 */

	function getOrderbyFullName($includeJoinString = true, $useStep = true)
	{
		return $this->getFullName($includeJoinString , $useStep);
	}

	/**
	 * helper function to draw hidden field, used by any plugin that requires to draw a hidden field
	 * @param string hidden field name
	 * @param string hidden field value
	 * @param string hidden field id
	 * @return string hidden field
	 */

	function getHiddenField($name, $value, $id = '', $class = '')
	{
		if ($id != '') {
			$id = 'id="'.$id.'"';
		}
		if ($class !== '') {
			$class = 'class="'.$class.'"';
		}
		$str = '<input type="hidden" name="'.$name.'" '.$id.' value="'.$value.'" '.$class.' />'."\n";
		return $str;
	}

	function check()
	{
		return true;
	}

	/**
	 * when copying elements from an existing table
	 * once a copy of all elements has been made run them through this method
	 * to ensure that things like watched element id's are updated
	 *
	 * @param array copied element ids (keyed on original element id)
	 */

	function finalCopyCheck($newElements)
	{
		//overwritten in element class
	}

	/**
	 * copy an element table row
	 *
	 * @param int $id
	 * @param string $copytxt
	 * @param int $groupid
	 * @parma string $name
	 * @return mixed error or new row
	 */

	function copyRow($id, $copytxt = 'Copy of %s', $groupid = null, $name = null)
	{
		$app = JFactory::getApplication();
		$rule	= FabTable::getInstance('Element', 'FabrikTable');
		if ($rule->load((int)$id)) {
			$rule->id = null;
			$rule->label = sprintf($copytxt, $rule->label);
			if (!is_null($groupid)) {
				$rule->group_id = $groupid;
			}
			if (!is_null($name)) {
				$rule->name = $name;
			}

			$groupModel = JModel::getInstance('Group', 'FabrikFEModel');
			$groupModel->setId($groupid);
			$groupListModel = $groupModel->getListModel();
			// $$$ rob - if its a joined group then it can have the same element names
			if ((int)$groupModel->getGroup()->is_join === 0) {
				if ($groupListModel->fieldExists($rule->name)) {
					return JError::raiseWarning(500, JText::_('COM_FABRIK_ELEMENT_NAME_IN_USE'));
				}
			}
			$date = JFactory::getDate();
			$date->setOffset($app->getCfg('offset'));
			$rule->created = $date->toMySQL();
			$params = $rule->params == '' ? new stdClass() : json_decode($rule->params);
			$params->parent_linked = 1;
			$rule->params = json_encode($params);

			$rule->parent_id = $id;
			if (!$rule->store()) {
				return JError::raiseWarning($rule->getError());
			}
		}
		else {
			return JError::raiseWarning(500, $rule->getError());
		}

		// I thought we did this in an overridden element model method, like onCopy?
		//if its a database join then add in a new join record
		if (is_a($this, 'plgFabrik_ElementDatabasejoin')) {
			$join = FabTable::getInstance('Join', 'FabrikTable');
			$join->load(array('element_id' => $id));
			$join->id = null;
			$join->element_id = $rule->id;
			$join->group_id = $rule->group_id;
			$join->store();
		}

		//copy js events
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('id')->from('#__{package}_jsactions')->where('element_id = '.(int)$id);
		$db->setQuery($query);
		$actions = $db->loadResultArray();
		foreach ($actions as $id) {
			$jscode = FabTable::getInstance('Jsaction', 'FabrikTable');
			$jscode->load($id);
			$jscode->id = 0;
			$jscode->element_id = $rule->id;
			$jscode->store();
		}
		return $rule;
	}

	/**
	 * this was in the views display and _getElement code but seeing as its used
	 * by multiple views its safer to have it here
	 * @param int repeat group counter
	 * @param int order in which the element is shown in the form
	 * @param string template
	 * @return mixed - false if you shouldnt continue to render the element
	 */

	function preRender($c, $elCount, $tmpl)
	{
		$model = $this->getFormModel();
		$groupModel = $this->getGroup();
		if (!$this->canUse() && !$this->canView()) {
			return false;
		}

		if (!$this->canUse()) {
			$this->_editable = false;
		} else {
			$this->_editable = ($model->_editable) ? true : false;
		}
		$params = $this->getParams();
		//force reload?
		$this->_HTMLids = null;

		// $$$ rob Massive resource hog - produces one query per element
		// think Hugh had this in for some wierd PHP5.x.x issue - but it makes long forms
		// very unloadable - eg 103 queries compared to 49 queries, plus an additional second load time on the form
		//$elementTable 	=& $this->getElement(true);
		$elementTable = $this->getElement();
		$element = new stdClass();
		$element->startRow = false;
		$element->endRow = false;
		$elHTMLName	= $this->getFullName(true, true);

		//if the element is in a join AND is the join's foreign key then we don't show the element

		if ($elementTable->name == $this->_foreignKey) {
			$element->label	= '';
			$element->error	= '';
			$this->_element->hidden = true;
		} else {
			//@TODO
			$element->error	= $this->_getErrorMsg($model->_arErrors, $c);
		}

		$element->plugin = $elementTable->plugin;
		$element->hidden = $this->isHidden();
		$element->id = $this->getHTMLId($c);
		$element->className = "fb_el_" . $element->id;
		$element->containerClass = $this->containerClass($element);

		$element->element = $this->_getElement($model->_data, $c, $groupModel);

		if ($params->get('tipsoverelement', false)) {
			$element->element = $this->rollover($element->element, $model->_data);
		}
		$element->label_raw = $this->_element->label;
		//getLabel needs to know if the element is editable
		if ($elementTable->name != $this->_foreignKey) {
			$l = $this->getLabel($c, $tmpl);
			$w = new FabrikWorker();
			$element->label = $w->parseMessageForPlaceHolder($l, $model->_data);
		}
		$element->errorTag = $this->addErrorHTML($c, $tmpl);
		$groupParams = $groupModel->getParams();
		//style attribute for group columns
		$element->column = '';
		$colcount = (int)$groupParams->get('group_columns');
		if ($colcount > 1) {
			$widths = $groupParams->get('group_column_widths');
			$w = floor((100- ($colcount * 6)) / $colcount) ."%";
			if ($widths != '') {
				$widths = explode(',', $widths);
				$w = JArrayHelper::getValue($widths, $elCount % $colcount, $w);
			}
			$element->column = ' style="float:left;width:'.$w.';';
			if (($elCount % $colcount == 0) || $element->hidden) {
				$element->startRow = true;
				$element->column .= "clear:both;";
			}
			if (($elCount % $colcount === $colcount - 1) || $element->hidden) {
				$element->endRow = true;
			}
			$element->column .= '" ';
		} else {
			$element->column .= ' style="clear:both;width:100%;"';
		}
		$element->element_ro = $this->_getROElement($model->_data, $c);

		$element->value = $this->getValue($model->_data, $c);

		if (array_key_exists($elHTMLName . "_raw", $model->_data)) {
			$element->element_raw = $model->_data[$elHTMLName . "_raw"];
		} else {
			if (array_key_exists($elHTMLName, $model->_data)) {
				$element->element_raw = $model->_data[$elHTMLName];
			} else {
				$element->element_raw = $element->value;
			}
		}
		if ($this->dataConsideredEmpty($element->element_ro, $c)) {
			$element->containerClass .= ' fabrikDataEmpty';

			// $$$rob added fabrikHide (21/09/2009) class as if readonly
			// data is empty then no point showing just the label
			// $$$ hugh - DISAGREE ... don't think we should make assumptions like this.
			if (!$this->canUse()) {
				$element->containerClass .= ' fabrikHide';
			}
		}
		//tips (if nto rendered as hovers)
		$tip = $this->getTip();
		if ($tip !== '') {
			$tip = '<div class="fabrikInlineTip">'.FabrikHelperHTML::image('questionmark.png', 'form', $tmpl).$tip.'</div>';
		}
		switch ($model->getParams()->get('tiplocation')) {
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
	 * @since 3.0
	 * get the class name for the element wrapping dom object
	 * @return string of class names
	 */

	protected function containerClass($element)
	{
		$item	= $this->getElement();
		$c = array('fabrikElementContainer', $item->plugin);

		if ($element->hidden) {
			$c[] = 'fabrikHide';
		}
		if ($element->error != '') {
			$c[] ='fabrikError';
		}
		return implode(' ', $c);
	}

	/**
	 * merge the rendered element into the views element storage arrays
	 * @param object element to merget
	 * @param array $aElements
	 * @param array $namedData
	 * @param array $aSubGroupElements
	 */

	function stockResults($element, &$aElements, &$namedData, &$aSubGroupElements)
	{
		$elHTMLName = $this->getFullName(true, true);
		$aElements[$this->getElement()->name] = $element;
		$namedData[$elHTMLName] = $element;
		if ($elHTMLName) {
			// $$$ rob was key'd on int but thats not very useful for templating
			$aSubGroupElements[$this->getElement()->name] = $element;
		}
	}

	/**
	 * @access private
	 * @param array data
	 * @param int repeat group counter
	 */

	function _getElement($data, $repeatCounter = 0, &$groupModel)
	{
		if (!$this->canView() && !$this->canUse()) {
			return '';
		}

		//used for working out if the element should behave as if it was in a new form (joined grouped) even when editing a record
		$this->_inRepeatGroup = $groupModel->canRepeat();
		$this->_inJoin = $groupModel->isJoin();

		$opts = array('runplugins' => 1);
		$this->getValue($data, $repeatCounter, $opts);


		if ($this->_editable) {
			return $this->render($data, $repeatCounter);
		} else {
			$htmlid = $this->getHTMLId($repeatCounter);
			//$$$ rob even when not in ajax mode the element update() method may be called in which case we need the span
			// $$$ rob changed from span wrapper to div wrapper as element's content may contain divs which give html error
			return '<div id="'.$htmlid.'">'.$this->_getROElement($data, $repeatCounter).'</div>'; //placeholder to be updated by ajax code
		}
	}

	/**
	 * @access private
	 * @param array data
	 * @param int repeat group counter
	 */

	function _getROElement($data, $repeatCounter = 0)
	{
		$groupModel = $this->getGroup();
		if (!$this->canView() && !$this->canUse()) {
			return '';
		}
		$this->_editable 	= false;
		$v = $this->render($data, $repeatCounter);
		$this->addCustomLink($v, $data, $repeatCounter);
		return $v;
	}

	/**
	 *
	 * add custom link to element - must be uneditable for link to be added
	 * @param string value
	 * @param array row data
	 * @param int repeat counter
	 */

	protected function addCustomLink(&$v, $data, $repeatCounter = 0)
	{
		if ($this->_editable) {
			return $v;
		}
		$params = $this->getParams();
		$customLink = $params->get('custom_link');
		if ($customLink !== '' && $this->getElement()->link_to_detail == '1') {
			$w = new FabrikWorker();
			$repData = array();
			//merge join data down for current repetCounter so that parseing repeat joined data
			//only inserts current record
			foreach ($data as $k => $val) {
				if ($k == 'join') {
					foreach ($val as $joindata) {
						foreach ($joindata as $k2 => $val2) {
							$repData[$k2] = JArrayHelper::getValue($val2, $repeatCounter);
						}
					}
				} else {
					//repeating group not joined
					$val = JArrayHelper::getValue(json_decode($val, true), $repeatCounter);
					$repData[$k] = $val;
				}
			}
			$customLink = $w->parseMessageForPlaceHolder($customLink, $repData);
			$customLink = $this->getListModel()->parseMessageForRowHolder($customLink, $repData);
			$v = '<a href="'.$customLink.'">'.$v.'</a>';
		}
		return $v;
	}

	/**
	 * get any html errror messages for form element
	 * @param array error messages
	 * @param int repeat count
	 * @return string error messages
	 */

	function _getErrorMsg(&$arErrors, $repeatCount = 0)
	{
		$arErrors = $this->getFormModel()->_arErrors;
		$parsed_name = $this->getFullName(true, true);
		$err_msg = '';
		$parsed_name = FabrikString::rtrimword($parsed_name, "[]");
		if (isset($arErrors[$parsed_name])) {
			if (array_key_exists($repeatCount, $arErrors[$parsed_name])) {
				if (is_array($arErrors[$parsed_name][$repeatCount])) {
					$err_msg = implode('<br />', $arErrors[$parsed_name][$repeatCount]);
				} else {
					$err_msg .= $arErrors[$parsed_name][$repeatCount];
				}
			}
		}
		return $err_msg;
	}

	/**
	 * draws out the html form element - overwritten in plugin
	 * @param array data to preopulate element with
	 * @param int repeat group counter
	 * @return string returns field element
	 */

	function render($data, $repeatCounter = 0)
	{
		return 'need to overwrite in element plugin class';
	}

	/**
	 * get the id used in the html element
	 * @param int repeat group counter
	 * @return string
	 */

	function getHTMLId($repeatCounter = 0)
	{
		if (!is_array($this->_HTMLids)) {
			$this->_HTMLids = array();
		}
		if (!array_key_exists((int)$repeatCounter, $this->_HTMLids)) {
			$groupModel = $this->getGroup();
			$listModel = $this->getListModel();
			$table = $listModel->getTable();
			$groupTable = $groupModel->getGroup();
			$element = $this->getElement();
			if ($groupModel->isJoin() || $this->isJoin()) {
				if ($this->isJoin()){
					$joinModel = $this->getJoinModel();
				}else{
					$joinModel = $groupModel->getJoinModel();
				}
				$joinTable = $joinModel->getJoin();
				$fullName = 'join___' . $joinTable->id . '___' . $joinTable->table_join . '___' . $element->name;
			} else {
				$fullName = $table->db_table_name . '___' . $element->name;
			}
			//change the id for detailed view elements
			if ($this->_inDetailedView) {
				$fullName .= "_ro";
			}
			if ($groupModel->canRepeat()) {
				$fullName .= "_" . $repeatCounter;
			}
			$this->_HTMLids[$repeatCounter] = $fullName;
		}
		return $this->_HTMLids[$repeatCounter];
	}

	/**
	 * get the element html name
	 * @param int repeat group counter
	 * @return string
	 */

	function getHTMLName($repeatCounter = 0)
	{
		$groupModel = $this->getGroup();
		$params 		=& $this->getParams();
		$table 			=& $this->getListModel()->getTable();
		$group 			=& $groupModel->getGroup();
		$element 		= $this->getElement();
		if ($groupModel->isJoin() || $this->isJoin()) {
			$joinModel 	= $this->isJoin() ? $this->getJoinModel() : $groupModel->getJoinModel();
			$joinTable 	=& $joinModel->getJoin();
			$fullName 	= 'join[' . $joinTable->id . '][' . $joinTable->table_join . '___' . $element->name . ']';
		} else {
			$fullName 	= $table->db_table_name . '___' . $element->name;
		}
		if ($groupModel->canRepeat()) {
			// $$$ rob - always use repeatCounter in html names - avoids ajax post issues with mootools1.1
			$fullName .= "[$repeatCounter]";
		}

		if ($this->hasSubElements) {
			$fullName .= "[]";
		}

		//@TODO: check this - repeated elements do need to have something applied to thier
		// id based on their order in the repeated groups

		$this->_elementHTMLName = $fullName;
		return $this->_elementHTMLName;
	}

	/**
	 * load element params
	 * also loads _pluginParams for good measure
	 * @return object default element params
	 */

	function getParams()
	{
		if (!isset($this->_params)) {
			$this->_params = new fabrikParams($this->getElement()->params, JPATH_SITE . '/administrator/components/com_fabrik/xml/element.xml' , 'component');
			$this->getPluginParams();
		}
		return $this->_params;
	}

	/**
	 * get specific plugin params (lazy loading)
	 *
	 * @return object plugin parameters
	 */

	function getPluginParams()
	{
		if (!isset($this->_pluginParams)) {
			$this->_pluginParams = $this->_loadPluginParams();
		}
		return $this->_pluginParams;
	}

	function _loadPluginParams()
	{
		if (isset($this->_xmlPath)) {
			$element = $this->getElement();
			$pluginParams = new fabrikParams($element->params, $this->_xmlPath, 'fabrikplugin');
			$pluginParams->bind($element);
			return $pluginParams;
		}
		return false;
	}

	/**
	 * loads in elements validation objects
	 * @return array validation objects
	 */

	public function getValidations()
	{
		if (isset($this->_aValidations)) {
			return $this->_aValidations;
		}
		$element = $this->getElement();
		$params = $this->getParams();
		$validations = $params->get('validations', '', '_default', 'array');
		$usedPlugins = JArrayHelper::getValue($validations, 'plugin', array());
		$pluginManager = FabrikWorker::getPluginManager();
		$pluginManager->getPlugInGroup('validationrule');
		$c = 0;
		$this->_aValidations = array();

		$dispatcher = JDispatcher::getInstance();
		$ok = JPluginHelper::importPlugin('fabrik_validationrule');
		foreach ($usedPlugins as $usedPlugin) {
			if ($usedPlugin !== '') {
				$class = 'plgFabrik_Validationrule'.JString::ucfirst($usedPlugin);
				$conf = array();
				$conf['name'] = strtolower($usedPlugin);
				$conf['type'] = strtolower('fabrik_Validationrule');
				$plugIn = new $class($dispatcher, $conf);

				$oPlugin = JPluginHelper::getPlugin('fabrik_validationrule', $usedPlugin);
				$plugIn->elementModel = $this;
				$this->_aValidations[] = $plugIn;
				$c ++;
			}
		}
		return $this->_aValidations;
	}

	/**
	 * get javasscript actions
	 * @return array js actions
	 */

	function getJSActions()
	{
		if (!isset($this->_jsActions)) {
			$query = $this->_db->getQuery();
			$query->select('*')->from('#__{package}_jsactions')->where('element_id = '.(int)$this->_id);
			$this->_db->setQuery($query);
			$this->_jsActions = $this->_db->loadObjectList();
		}
		return $this->_jsActions;
	}

	/**
	 *create the js code to observe the elements js actions
	 * @param string either form_ or _details
	 * @param int repeat counter
	 * @return string js events
	 */

	function getFormattedJSActions($jsControllerKey, $repeatCount)
	{
		$jsStr = '';
		$allJsActions = $this->getFormModel()->getJsActions();
		// $$$ hugh - only needed getParent when we weren't saving changes to parent params to child
		// which we should now be doing ... and getParent() causes an extra table lookup for every child
		// element on the form.
		//$element = $this->getParent();
		$jsControllerKey = "Fabrik.blocks['".$jsControllerKey."']";
		$element = $this->getElement();
		$form = $this->_form->getForm();
		$w = new FabrikWorker();
		if (array_key_exists($element->id, $allJsActions)) {
			$fxadded = array();
			$elId = $this->getHTMLId($repeatCount);
			foreach ($allJsActions[$element->id] as $jsAct) {
				$js = addslashes($jsAct->code);
				$js = str_replace(array("\n", "\r"), "", $js);
				if ($jsAct->action == 'load') {
					$js = preg_replace('#\bthis\b#', "\$(\\'$elId\\')", $js);
				}
				if ($jsAct->action != '' && $js !== '') {
					$jsStr .= $jsControllerKey.".dispatchEvent('$element->plugin', '$elId', '$jsAct->action', '$js');\n";
				}

				//build wysiwyg code
				if (isset($jsAct->js_e_event) && $jsAct->js_e_event != '') {
					// $$$ rob get the correct element id based on the repeat counter
					$triggerEl = $this->getFormModel()->getElement(str_replace('fabrik_trigger_element_', '', $jsAct->js_e_trigger));
					if (is_object($triggerEl)) {
						$triggerid = 'element_'.$triggerEl->getHTMLId($repeatCount);
					} else {
						$triggerid = $jsAct->js_e_trigger;
					}
					if (!array_key_exists($jsAct->js_e_trigger, $fxadded)) {
						$jsStr .= $jsControllerKey.".addElementFX('$triggerid', '$jsAct->js_e_event');\n";
						$fxadded[$jsAct->js_e_trigger] = true;
					}
					$jsAct->js_e_value = $w->parseMessageForPlaceHolder($jsAct->js_e_value, JRequest::get('post'));
					//$js = "if (this.getValue() $jsAct->js_e_condition '$jsAct->js_e_value') {";
					$js = "if (this.get('value') $jsAct->js_e_condition '$jsAct->js_e_value') {";
					$js .= $jsControllerKey.".doElementFX('$jsAct->js_e_trigger', '$jsAct->js_e_event')";
					$js .= "}";
					$js = addslashes($js);
					$js = str_replace(array("\n", "\r"), "", $js);
					$jsStr .= $jsControllerKey.".dispatchEvent('$element->plugin', '$elId', '$jsAct->action', '$js');\n";
				}
			}
		}
		return $jsStr;
	}

	/**
	 * get the default value for the table filter
	 * @param bol is the filter a normal or advanced filter
	 * @param int filter order
	 */

	function getDefaultFilterVal($normal = true, $counter = 0)
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
		//see if the data is in the request array - can use tablename___elementname=filterval in query string
		if ($groupModel->isJoin()) {
			if (array_key_exists('join', $data) && array_key_exists($group->join_id, $data['join'])) {
				$data = $data['join'][$group->join_id];
			}
		}
		$default = "";
		if (array_key_exists($elName, $data)) {
			if (is_array($data[$elName])) {
				$default = @$data[$elName]['value'];
			}
		}
		$context = 'com_fabrik.list'.$listModel->getRenderContext().'.filter.'.$elid;
		$context .= $normal ? '.normal' : '.advanced';
		//we didnt find anything - lets check the filters
		if ($default == '') {
			if (empty($filters)) {
				return '';
			}
			if (array_key_exists('elementid', $filters)) {
				// $$$ hugh - if we have one or more pre-filters on the same element that has a normal filter,
				// the following line doesn't work. So in 'normal' mode we need to get all the keys,
				// and find the 'normal' one.
				//$k = $normal == true ? array_search($elid, $filters['elementid']) : $counter;
				$k = false;
				if ($normal) {
					$keys = array_keys($filters['elementid'], $elid);
					foreach($keys as $key) {
						// $$$ rob 05/09/2011 - just testing for 'normal' is not enough as there are several search_types - ie I've added a test for
						//querystring filters as without that the search values were not being shown in ranged filter fields
						if ($filters['search_type'][$key] == 'normal' || $filters['search_type'][$key] == 'querystring') {
							$k = $key;
							continue;
						}
					}
				}
				else {
					//$k = $count;
					$k = $counter;
				}
				//is there a filter with this elements name
				if ($k !== false) {
					// $$$ rob comment out if statement as otherwise no value returned on advanced filters
					// prob not right for n advanced filters on the same element though

					//if ($normal && $filters['search_type'][$k] != 'advanced') {
					//if its a search all filter dont use its value.
					//if we did the next time the filter form is submitted its value is turned
					//from a search all filter into an element filter
					$searchType = JArrayHelper::getValue($filters['search_type'], $k);
					if (!is_null($searchType) && $searchType != 'searchall') {
						if ($searchType != 'prefilter') {
							$default = $filters['origvalue'][$k];
						}
					}
					//}
				}
			}
		}

		$default = $app->getUserStateFromRequest($context, $elid, $default);
		if ($this->getElement()->filter_type !== 'range') {
			$default = (is_array($default) && array_key_exists('value', $default)) ? $default['value'] : $default;
			if (is_array($default)) {
				$default = ''; //wierd thing on meow where when you first load the task list the id element had a date range filter applied to it????
			}
			$default = stripslashes($default);
		}
		return $default;
	}

	/**
	 * if the search value isnt what is stored in the database, but rather what the user
	 * sees then switch from the search string to the db value here
	 * overwritten in things like checkbox and radio plugins
	 * @param string $filterVal
	 * @return string
	 */

	function prepareFilterVal($value)
	{
		return $value;
	}

	/**
	 * can be overwritten by plugin class
	 * Get the table filter for the element
	 * @param int filter order
	 * @param bol do we render as a normal filter or as an advanced searc filter
	 * if normal include the hidden fields as well (default true, use false for advanced filter rendering)
	 * @return string filter html
	 */

	public function getFilter($counter = 0, $normal = true)
	{
		$listModel = $this->getListModel();
		$formModel = $listModel->getFormModel();
		$dbElName	= $this->getFullName(false, false, false);
		if (!$formModel->hasElement($dbElName)) {
			return '';
		}

		$table = $listModel->getTable();
		$element = $this->getElement();

		$elName = $this->getFullName(false, true, false);
		$id = $this->getHTMLId().'value';
		$v = 'fabrik___filter[list_'.$listModel->getRenderContext().'][value]';
		$v .= ($normal) ? '['.$counter.']' : '[]';

		//corect default got
		$default = $this->getDefaultFilterVal($normal, $counter);
		$return = '';

		if (in_array($element->filter_type, array('range', 'dropdown'))) {
			$rows = $this->filterValueList($normal);
			$this->unmergeFilterSplits($rows);
			array_unshift($rows, JHTML::_('select.option', '', $this->filterSelectLabel()));
		}
		$size = (int)$this->getParams()->get('filter_length', 20);

		switch ($element->filter_type)
		{
			case "range":
				$attribs = 'class="inputbox fabrik_filter" size="1" ';
				$default1 = is_array($default) ? $default['value'][0] : '';
				$return = JText::_('COM_FABRIK_BETWEEN') . JHTML::_('select.genericlist', $rows, $v.'[]', $attribs, 'value', 'text', $default1, $element->name . "_filter_range_0");
				$default1 = is_array($default) ? $default['value'][1] : '';
				$return .= '<br /> '.JText::_('COM_FABRIK_AND').' '.JHTML::_('select.genericlist', $rows, $v.'[]', $attribs, 'value', 'text', $default1, $element->name . "_filter_range_1");
				break;

			case "dropdown":
				$return = JHTML::_('select.genericlist', $rows, $v, 'class="inputbox fabrik_filter" size="1" ', 'value', 'text', $default, $id);
				break;

			case "field":
			default:
				// $$$ rob - if searching on "O'Fallon" from querystring filter the string has slashes added regardless
				//if (get_magic_quotes_gpc()) {
				$default = stripslashes($default);
				//}
				$default = htmlspecialchars($default);
				$return = '<input type="text" name="'.$v.'" class="inputbox fabrik_filter" size="'.$size.'" value="'.$default.'" id="'.$id.'" />';
				break;

			case "auto-complete":
				$default = stripslashes($default);
				$default = htmlspecialchars($default);
				// $$$ rob 28/10/2011 using selector rather than element id so we can have n modules with the same filters showing and not produce invald html & duplicate js calls
				$return = '<input type="hidden" name="'.$v.'" class="inputbox fabrik_filter '.$id.'" value="'.$default.'" />';
				$return .= '<input type="text" name="'.$v.'-auto-complete" class="inputbox fabrik_filter autocomplete-trigger '.$id.'-auto-complete" size="'.$size.'" value="'.$default.'" />';
				$selector = '#list_'.$listModel->getRenderContext().' .'.$id;
				FabrikHelperHTML::autoComplete($selector, $this->getElement()->id);
				break;
		}
		if ($normal) {
			$return .= $this->getFilterHiddenFields($counter, $elName);
		} else {
			$return .= $this->getAdvancedFilterHiddenFields();
		}
		return $return;
	}

	protected function filterSelectLabel()
	{
		$params = $this->getParams();
		return $params->get('filter_required') == 1 ? JText::_('COM_FABRIK_PLEASE_SELECT') : JText::_('COM_FABRIK_FILTER_PLEASE_SELECT');
	}

	/**
	 * checks if filter option values are in json format
	 * if so explode those values into new options
	 * @param array $rows filter options
	 * @return null
	 */

	protected function unmergeFilterSplits(&$rows)
	{
		// $$ rob not working with dropdown el dropdown filter
		//takes rows which may be in format :
		/*
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
		foreach ($rows as $row) {
			$allvalues[] = $row->value;
		}
		$c = count($rows) - 1;
		for ($j = $c; $j >= 0; $j--) {
			$vals = FabrikWorker::JSONtoData($rows[$j]->value, true);
			$txt = FabrikWorker::JSONtoData($rows[$j]->text, true);
			if (is_array($vals)) {
				$found = false;
				for ($i=0; $i< count($vals); $i++) {
					$vals2 = FabrikWorker::JSONtoData($vals[$i], true);
					$txt2 = FabrikWorker::JSONtoData($txt[$i], true);
					for ($jj=0; $jj<count($vals2); $jj++) {
						if (!in_array($vals2[$jj], $allvalues)) {
							$found = true;
							$allvalues[] = $vals2[$jj];
							$rows[] = JHTML::_('select.option', $vals2[$jj], $txt2[$jj]);
						}
					}
				}
				if ($found) {
					unset($rows[$j]); // $$$ r4ob 01/08/2011 - caused empty list in advanced search on dropdown element
				}
			}
		}
	}

	/**
	 * run after unmergeFilterSplits to ensure filter dropdown labels are correct
	 * @param array filter options
	 * @return null
	 */

	protected function reapplyFilterLabels(&$rows)
	{
		$element = $this->getElement();
		$values = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();
		foreach ($rows as &$row) {
			$k = array_search($row->value, $values);
			if ($k !== false) {
				$row->text = $labels[$k];
			}
		}
		$rows = array_values($rows);
	}

	protected function getSubOptionValues()
	{
		$params = $this->getParams();
		$opts = $params->get('sub_options', '');
		return $opts == '' ? array() : (array)$opts->sub_values;
	}

	protected function getSubOptionLabels()
	{
		$params = $this->getParams();
		$opts = $params->get('sub_options', '');
		return $opts == '' ? array() : (array)$opts->sub_labels;
	}

	/**
	 * get the radio buttons possible values
	 * @return array of radio button values
	 * needed for inlien edit list plugin
	 */

	public function getOptionValues()
	{
		return $this->getSubOptionValues();
	}

	/**
	 * get the radio buttons possible labels
	 * needed for inlien edit list plugin
	 * @return array of radio button labels
	 */

	protected function getOptionLabels()
	{
		return $this->getSubOptionLabels();
	}

	/**
	 * used by radio and dropdown elements to get a dropdown list of their unique
	 * unique values OR all options - basedon filter_build_method
	 * @param bol do we render as a normal filter or as an advanced search filter
	 * @param string table name to use - defaults to element's current table
	 * @param string label field to use, defaults to element name
	 * @param string id field to use, defaults to element name
	 * @return array text/value objects
	 */

	public function filterValueList($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$params = $this->getParams();
		$filter_build = $params->get('filter_build_method', 0);
		if ($filter_build == 0) {
			$filter_build = $usersConfig->get('filter_build_method');
		}
		if ($filter_build == 2 && $this->hasSubElements) {
			return $this->filterValueList_All($normal, $tableName, $label, $id, $incjoin);
		} else {
			return $this->filterValueList_Exact($normal, $tableName, $label, $id, $incjoin);
		}
	}

	/**
	 * @abstract used by database join element
	 * if filterValueList_Exact incjoin value = false, then this method is called
	 * to ensure that the query produced in filterValueList_Exact contains at least the database join element's
	 * join
	 * @return string required join text to ensure exact filter list code produces a valid query.
	 */

	protected function _buildFilterJoin()
	{
		return '';
	}

	/**
	 * create an array of label/values which will be used to populate the elements filter dropdown
	 * returns only data found in the table you are filtering on
	 * @param unknown_type $normal
	 * @param string $tableName
	 * @param string $label
	 * @param mixed $id
	 * @param bool $incjoin
	 * @return array filter value and labels
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
		if (!$this->isJoin()) {
			$ids = $listModel->getColumnData($elName2);
			//for ids that are text with apostrophes in
			for ($x=count($ids) -1; $x >= 0; $x--) {
				if ($ids[$x] == '') {
					unset($ids[$x]);
				} else {
					$ids[$x] = addslashes($ids[$x]);
				}
			}
		}
		$incjoin = $this->isJoin() ? false : $incjoin;
		//filter the drop downs lists if the table_view_own_details option is on
		//other wise the lists contain data the user should not be able to see
		// note, this should now use the prefilter data to filter the list

		// check if the elements group id is on of the table join groups if it is then we swap over the table name
		$fromTable = $this->isJoin() ? $this->getJoinModel()->getJoin()->table_join : $origTable;
		$joinStr = $incjoin ? $listModel->_buildQueryJoin() : $this->_buildFilterJoin();
		$groupBy = "GROUP BY ".$params->get('filter_groupby', 'text')." ASC";
		foreach ($listModel->getJoins() as $aJoin) {
			// not sure why the group id key wasnt found - but put here to remove error
			if (array_key_exists('group_id', $aJoin)) {
				if ($aJoin->group_id == $element->group_id && $aJoin->element_id == 0) {
					$fromTable = $aJoin->table_join;
					$elName = str_replace($origTable . '.', $fromTable . '.', $elName2);
				}
			}
		}
		$elName = FabrikString::safeColName($elName);
		if ($label == '') {
			$label = $this->isJoin() ? $this->getElement()->name : $elName;
		}
		if ($id == '') {
			$id = $this->isJoin() ? 'id' : $elName;
		}
		if ($this->encryptMe()) {
			$secret = JFactory::getConfig()->getValue('secret');
			$label = "AES_DECRYPT($label, '".$secret."')";
			$id = "AES_DECRYPT($id, '".$secret."')";
		}
		$origTable = $tableName == '' ? $origTable: $tableName;
		// $$$ rob - 2nd sql was blowing up for me on my test table - why did we change to it?
		// http://localhost/fabrik2.0.x/index.php?option=com_fabrik&view=table&listid=12&calculations=0&resetfilters=0&Itemid=255&lang=en
		// so added test for intial fromtable in join str and if found use origtable
		if (strstr($joinStr, 'JOIN '.$fabrikDb->nameQuote($fromTable))) {
			$sql = "SELECT DISTINCT($label) AS " . $fabrikDb->nameQuote('text') . ", $id AS " . $fabrikDb->nameQuote('value') . " FROM ". $fabrikDb->nameQuote($origTable). " $joinStr\n";
		} else {
			$sql = "SELECT DISTINCT($label) AS " . $fabrikDb->nameQuote('text') . ", $id AS " . $fabrikDb->nameQuote('value') . " FROM ". $fabrikDb->nameQuote($fromTable). " $joinStr\n";
		}
		if (!$this->isJoin()) {
			$sql .= "WHERE $id IN ('" . implode("','", $ids) . "')";
		}
		$sql .= "\n $groupBy";

		$sql = $listModel->pluginQuery($sql);
		$fabrikDb->setQuery($sql);
		$rows = $fabrikDb->loadObjectList();
		if ($fabrikDb->getErrorNum() != 0) {
			JError::raiseNotice(500, 'filter query error: ' . $fabrikDb->getErrorMsg());
		}
		return $rows;

	}

	protected function filterValueList_All($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		$element = $this->getElement();
		$vals = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();
		$return = array();
		for ($i=0; $i<count($vals); $i++) {
			$return[] = JHTML::_('select.option', $vals[$i], $labels[$i]);
		}
		return $return;
	}

	/**
	 * get the hidden fields for a normal filter
	 * @param int filter counter
	 * @param string $elName full element name will be converted to tablename.elementname format
	 * @param has the filter been added due to a search form value with no corresponding filter set up in the table
	 * if it has we need to know so that when we do a search from a 'fabrik_list_filter_all' field that search term takes prescidence
	 * @return string html hidden fields
	 */

	function getFilterHiddenFields($counter, $elName, $hidden = false)
	{
		$params = $this->getParams();
		$element = $this->getElement();

		// $$$ rob this caused issues if your element was a dbjoin with a concat label, but then you save it as a field
		//if ($params->get('join_val_column_concat') == '') {
		if ($element->plugin != 'fabrikdatabasejoin') {
			$elName = FabrikString::safeColName($elName);
		}
		$hidden = $hidden ? 1 : 0;

		$table = $this->getListModel()->getTable();

		$match = $this->isExactMatch(array('match' => $element->filter_exact_match));
		$cond = $this->getFilterCondition();
		$return = array();
		$prefix = '<input type="hidden" name="fabrik___filter[list_'.$this->getListModel()->getRenderContext().']';
		$return[] = $prefix.'[condition]['.$counter.']" value="'.$cond.'" />';
		$return[] = $prefix.'[join]['.$counter.']" value="AND" />';
		$return[] = $prefix.'[key]['.$counter.']" value="'.$elName.'" />';
		$return[] = $prefix.'[search_type]['.$counter.']" value="normal" />';
		$return[] = $prefix.'[match]['.$counter.']" value="'.$match.'" />';
		$return[] = $prefix.'[full_words_only]['.$counter.']" value="'.$params->get('full_words_only', '0').'" />';
		$return[] = $prefix.'[eval]['.$counter.']" value="'.FABRIKFILTER_TEXT.'" />';
		$return[] = $prefix.'[grouped_to_previous]['.$counter.']" value="0" />';
		$return[] = $prefix.'[hidden]['.$counter.']" value="'.$hidden.'" />';
		$return[] = $prefix.'[elementid]['.$counter.']" value="'.$element->id.'" />';
		return implode("\n", $return);
	}

	/**
	 * get the condition statement to use in the filters hidden field
	 * @return string =, begins or contains
	 */

	protected function getFilterCondition()
	{
		if ($this->getElement()->filter_type == 'auto-complete') {
			$cond = 'contains';
		} else {
			$match = $this->isExactMatch(array('match' => $this->getElement()->filter_exact_match));
			$cond = ($match == 1) ? '=' : 'contains';
		}
		return $cond;
	}

	/**
	 * get the hidden fields for an advanced filter
	 * @param int filter counter
	 * @return string html hidden fields
	 */

	function getAdvancedFilterHiddenFields()
	{
		$listModel = $this->getListModel();
		$element = $this->getElement();
		return "\n".'<input type="hidden" name="fabrik___filter[list_'.$listModel->getRenderContext().'][elementid][]" value="'.$element->id.'" />';
	}

	/**
	 * this builds an array containing the filters value and condition
	 * when using a ranged search
	 * @param string initial $value
	 * @return array(value condition)
	 */

	function getRangedFilterValue($value)
	{
		$db = FabrikWorker::getDbo();
		if (is_numeric($value[0]) && is_numeric($value[1])) {
			$value = $value[0] . " AND " . $value[1];
		} else {
			$value = $db->Quote($value[0]) . " AND " . $db->Quote($value[1]);
		}
		$condition = 'BETWEEN';
		return array($value, $condition);
	}

	/**
	 * esacepes the a query search string
	 * @param string $condition
	 * @param value $value (passed by ref)
	 * @return null
	 */
	private function escapeQueryValue($condition, &$value)
	{
		// $$$ rob 30/06/2011 only escape once !
		if ($this->escapedQueryValue) {
			return;
		}
		$this->escapedQueryValue = true;
		if (is_array($value)) {
			//if doing a search via a querystring for O'Fallon then the ' is backslahed in FabrikModelListfilter::getQuerystringFilters()
			//but the mySQL regexp needs it to be backquoted three times
			foreach ($value as &$val) {
				if ($condition == 'REGEXP') {
					$val = preg_quote($val);
				}
				$val = str_replace("\\", "\\\\\\", $val);
				// $$$rob check things havent been double quoted twice (occurs now that we are doing preg_quote() above to fix searches on '*'
				$val = str_replace("\\\\\\\\\\\\", "\\\\\\", $val);
			}
		} else {
			if ($condition == 'REGEXP') {
				$value = preg_quote($value);
			}
			//if doing a search via a querystring for O'Fallon then the ' is backslahed in FabrikModelListfilter::getQuerystringFilters()
			//but the mySQL regexp needs it to be backquoted three times
			$value = str_replace("\\", "\\\\\\", $value);
			// $$$rob check things havent been double quoted twice (occurs now that we are doing preg_quote() above to fix searches on '*'
			$value = str_replace("\\\\\\\\\\\\", "\\\\\\", $value);
		}
	}

	/**
	 * this builds an array containing the filters value and condition
	 * @param string initial $value
	 * @param string intial $condition
	 * @param string eval - how the value should be handled
	 * @return array(value condition)
	 */

	function getFilterValue($value, $condition, $eval)
	{
		$this->escapeQueryValue($condition, $value);
		$db = FabrikWorker::getDbo();
		if (is_array($value)) {
			//ranged search
			list($value, $condition) = $this->getRangedFilterValue($value);
		} else {
			switch ($condition) {
				case 'notequals':
				case '<>':
					$condition = "<>";
					// 2 = subquery so dont quote
					$value = ($eval == FABRIKFILTER_QUERY) ? "($value)" : $db->Quote($value);
					break;
				case 'equals':
				case '=':
					$condition = "=";
					$value = ($eval == FABRIKFILTER_QUERY) ? "($value)" : $db->Quote($value);
					break;
				case 'begins':
				case 'begins with':
					$condition = "LIKE";
					$value = $eval == FABRIKFILTER_QUERY ? "($value)" : $db->Quote($value.'%');
					break;
				case 'ends':
				case 'ends with':
					// @TODO test this with subsquery
					$condition = "LIKE";
					$value = $eval == FABRIKFILTER_QUERY ? "($value)" : $db->Quote('%'.$value);
					break;
				case 'contains':
					// @TODO test this with subsquery
					$condition = "LIKE";
					$value = $eval == FABRIKFILTER_QUERY ? "($value)" : $db->Quote('%'.$value.'%');
					break;
				case '>':
				case '&gt;':
					$condition = '>';
					break;
				case '<':
				case '&lt;':
					$condition = '<';
					break;
				case '>=':
				case '&gt;=':
					$condition = '>=';
					break;
				case '<=':
				case '&lt;=':
					$condition = '<=';
					break;
				case 'in':
					$condition = 'IN';
					$value = ($eval == FABRIKFILTER_QUERY) ? "($value)" : "($value)";
					break;
				case 'not_in':
					$condition = 'NOT IN';
					$value = ($eval == FABRIKFILTER_QUERY) ? "($value)" : "($value)";
					break;

			}
			switch ($condition) {
				case '>':
				case '<':
				case '>=':
				case '<=':
					if ($eval == FABRIKFILTER_QUERY) {
						$value = "($value)";
					} else {
						if (!is_numeric($value)) {
							$value = $db->Quote($value);
						}
					}
					break;
			}
			// $$$ hugh - if 'noquotes' (3) selected, strip off the quotes again!
			if ($eval == FABRKFILTER_NOQUOTES) {
				# $$$ hugh - darn, this is stripping the ' of the end of things like "select & from foo where bar = '123'"
				$value = ltrim($value, "'");
			$value = rtrim($value, "'");
			}
			if ($condition == '=' && $value == "'_null_'") {
				$condition = " IS NULL ";
				$value = '';
			}
		}
		return array($value, $condition);
	}

	/**
	 * build the filter query for the given element.
	 * Can be overwritten in plugin - e.g. see checkbox element which checks for partial matches
	 * @param $key element name in format `tablename`.`elementname`
	 * @param $condition =/like etc
	 * @param $value search string - already quoted if specified in filter array options
	 * @param $originalValue - original filter value without quotes or %'s applied
	 * @param string filter type advanced/normal/prefilter/search/querystring/searchall
	 * @return string sql query part e,g, "key = value"
	 */

	function getFilterQuery($key, $condition, $value, $originalValue, $type = 'normal')
	{
		$this->encryptFieldName($key);
		switch ($condition) {
			case 'earlierthisyear':
				$query = " DAYOFYEAR($key) <= DAYOFYEAR($value) ";
				break;
			case 'laterthisyear':
				$query = " DAYOFYEAR($key) >= DAYOFYEAR($value) ";
				break;
			default:
				if ($this->isJoin()) {
				// query the joined table concatanating into one field
				$jointable = $this->getJoinModel()->getJoin()->table_join;
				$pk = $this->getListModel()->getTable()->db_primary_key;
				$key = "(SELECT GROUP_CONCAT(id SEPARATOR '//..*..//') FROM $jointable WHERE parent_id = $pk)";
				$value = str_replace("'", '', $value);
				$query = "($key = '$value' OR $key LIKE '$value".GROUPSPLITTER."%' OR
				$key LIKE '".GROUPSPLITTER."$value".GROUPSPLITTER."%' OR
				$key LIKE '%".GROUPSPLITTER."$value')";
			} else {
				$query = " $key $condition $value ";
			}
			break;
		}
		return $query;
	}

	function encryptFieldName(&$key)
	{
		if ($this->encryptMe()) {
			$secret = JFactory::getConfig()->getValue('secret');
			$key = "AES_DECRYPT($key, '".$secret."')";
		}
	}

	/**
	 * if no filter condition supplied (either via querystring or in posted filter data
	 * return the most appropriate filter option for the element.
	 * @return string default filter condition ('=', 'REGEXP' etc)
	 */

	function getDefaultFilterCondition()
	{
		$params = $this->getParams();
		$fieldDesc = $this->getFieldDescription();

		if (JString::stristr($fieldDesc, 'INT') || $this->getElement()->filter_exact_match == 1) {
			return '=';
		}
		return 'REGEXP';
	}

	/**
	 * $$$ rob testing not using this as elements can only be in one group
	 * $$$ hugh - still called from import.php
	 *
	 * @TODO Fabrik 3 - loadFromFormId() might need to pass in a package id
	 *
	 * when adding a new element this will ensure its added to all tables that the
	 * elements group is associated with
	 * @param string original column name leave null to ignore
	 */

	function addToDBTable($origColName = null)
	{
		$db = FabrikWorker::getDbo();
		$user = JFactory::getUser();

		// don't bother if the element has no name as it will cause an sql error'
		if ($this->_element->name == '') {
			return;
		}

		$groupModel = JModel::getInstance('Group', 'FabrikFEModel');
		$groupModel->setId($this->_element->group_id);
		$groupTable 	=& $groupModel->getGroup();

		$formTable 	= FabTable::getInstance('Form', 'FabrikTable');
		$listModel	= JModel::getInstance('List', 'FabrikFEModel');
		$afFormIds 	= $groupModel->getFormsIamIn();
		if ($groupModel->isJoin()) {
			$joinModel = $groupModel->getJoinModel();
			$joinTable = $joinModel->getJoin();
			if ($joinTable->list_id != 0) {
				$listModel->setId($joinTable->list_id);
				$table = $listModel->getTable();
				$table->db_table_name = $joinTable->table_join;
				$listModel->alterStructure($this, $origColName);
			}
		} else {
			if (is_array($afFormIds)) {
				foreach ($afFormIds as $formId) {
					$formTable->load($formId);
					if ($formTable->record_in_database) {
						$tableTable = $listModel->loadFromFormId($formId);
						$listModel->alterStructure($this, $origColName);
					}
				}
			}
		}
	}

	/**
	 * called from admin element controller when element saved
	 * @abstract
	 * @return bool save ok or not
	 */

	function onSave()
	{
		$params = $this->getParams();
		if (!$this->canEncrypt() && $params->get('encrypt')) {
			JError::raiseNotice(500, 'The encryption option is only available for field and text area plugins');
			return false;
		}
		//overridden in element plugin if needed
		return true;
	}

	/**
	 * called from admin element controller when element is removed
	 * @bool has the user elected to drop column?
	 * @return bool save ok or not
	 */

	function onRemove($drop = false)
	{
		//delete js actions
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$id =(int)$this->getElement()->id;
		$query->delete()->from('#__{package}_jsactions')->where('element_id ='.$id);
		$db->setQuery($query);
		if (!$db->query()) {
			JError::raiseNotice(500, 'didnt delete js actions for element '.$id);
			return false;
		}
		return true;
	}

	/**
	 * states if the elemnt contains data which is recorded in the database
	 * some elements (eg buttons) dont
	 * @param array posted data
	 */

	function recordInDatabase($data = null)
	{
		return $this->_recordInDatabase;
	}

	/**
	 * used by elements with suboptions
	 *
	 * @param string value
	 * @param string default label
	 * @param array submitted data (only needed in some overrided element models like CDD)
	 * @return string label
	 */

	public function getLabelForValue($v, $defaultLabel = '', $data = array())
	{
		// $$$ hugh - only needed getParent when we weren't saving changes to parent params to child
		// which we should now be doing ... and getParent() causes an extra table lookup for every child
		// element on the form.
		//$element = $this->getParent();
		$element = $this->getElement();
		$params = $this->getParams();
		$values = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();
		$key = array_search($v, $values);
		// $$$ rob if we allow adding to the dropdown but not recording
		// then there will be no $key set to revert to the $val instead
		return ($key === false) ? $v : JArrayHelper::getValue($labels, $key, $defaultLabel);
	}

	/**
	 * build the query for the avg caclculation - can be overwritten in plugin class (see date element for eg)
	 * @param model $listModel
	 * @param string $label the label to apply to each avg
	 * @return string sql statement
	 */

	protected function getAvgQuery(&$listModel, $label = "'calc'")
	{
		$item = $listModel->getTable();
		$joinSQL = $listModel->_buildQueryJoin();
		$whereSQL = $listModel->_buildQueryWhere();
		$name = $this->getFullName(false, false, false);
		$groupModel = $this->getGroup();
		if ($groupModel->isJoin()) {
			//element is in a joined column - lets presume the user wants to sum all cols, rather than reducing down to the main cols totals
			return "SELECT ROUND(AVG($name)) AS value, $label AS label FROM ".FabrikString::safeColName($item->db_table_name)." $joinSQL $whereSQL";
		} else {
			// need to do first query to get distinct records as if we are doing left joins the sum is too large
			return "SELECT ROUND(AVG(value)) AS value, label
FROM (SELECT DISTINCT $item->db_primary_key, $name AS value, $label AS label FROM ".FabrikString::safeColName($item->db_table_name)." $joinSQL $whereSQL) AS t";

		}
	}

	protected function getSumQuery(&$listModel, $label = "'calc'")
	{
		$item = $listModel->getTable();
		$joinSQL = $listModel->_buildQueryJoin();
		$whereSQL = $listModel->_buildQueryWhere();
		$name = $this->getFullName(false, false, false);
		$groupModel = $this->getGroup();
		if ($groupModel->isJoin()) {
			//element is in a joined column - lets presume the user wants to sum all cols, rather than reducing down to the main cols totals
			return "SELECT SUM($name) AS value, $label AS label FROM ".FabrikString::safeColName($item->db_table_name)." $joinSQL $whereSQL";
		} else {
			// need to do first query to get distinct records as if we are doing left joins the sum is too large
			return "SELECT SUM(value) AS value, label
	FROM (SELECT DISTINCT $item->db_primary_key, $name AS value, $label AS label FROM ".FabrikString::safeColName($item->db_table_name)." $joinSQL $whereSQL) AS t";
		}
	}


	protected function getMedianQuery(&$listModel, $label = "'calc'")
	{
		$item = $listModel->getTable();
		$joinSQL = $listModel->_buildQueryJoin();
		$whereSQL = $listModel->_buildQueryWhere();
		return "SELECT {$this->getFullName(false, false, false)} AS value, $label AS label FROM ".FabrikString::safeColName($item->db_table_name)." $joinSQL $whereSQL ";
	}

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
		if (!empty($count_condition)) {
			if (!empty($whereSQL)) {
				$whereSQL .= " AND $name = ". $db->Quote($count_condition);
			}
			else {
				$whereSQL = "WHERE $name = ". $db->Quote($count_condition);
			}
		}
		$groupModel = $this->getGroup();
		if ($groupModel->isJoin()) {
			//element is in a joined column - lets presume the user wants to sum all cols, rather than reducing down to the main cols totals
			return "SELECT COUNT($name) AS value, $label AS label FROM ".FabrikString::safeColName($item->db_table_name)." $joinSQL $whereSQL";
		} else {
			// need to do first query to get distinct records as if we are doing left joins the sum is too large
			$query = "SELECT COUNT(value) AS value, label
	FROM (SELECT DISTINCT $item->db_primary_key, $name AS value, $label AS label FROM ".FabrikString::safeColName($item->db_table_name)." $joinSQL $whereSQL) AS t";
		}
		return $query;
	}

	/**
	 * calculation: sum
	 * can be overridden in element class
	 * @param object table model
	 * @return array
	 */

	function sum(&$listModel)
	{
		$db = $listModel->getDb();
		$params = $this->getParams();
		$item = $listModel->getTable();
		$splitSum	= $params->get('sum_split', '');
		$split = $splitSum == '' ? false : true;
		$calcLabel 	= $params->get('sum_label', JText::_('COM_FABRIK_SUM'));
		if ($split) {
			$pluginManager = FabrikWorker::getPluginManager();
			$plugin = $pluginManager->getElementPlugin($splitSum);
			$splitName = method_exists($plugin, 'getJoinLabelColumn') ? $plugin->getJoinLabelColumn() : $plugin->getFullName(false, false, false);
			$splitName = FabrikString::safeColName($splitName);
			$sql = $this->getSumQuery($listModel, $splitName) . " GROUP BY label";
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$results2 = $db->loadObjectList('label');
			$results = $this->formatCalcSplitLabels($results2, $plugin, 'sum');
		} else {
			// need to add a group by here as well as if the ONLY_FULL_GROUP_BY SQL mode is enabled
			// an error is produced
			$sql = $this->getSumQuery($listModel). " GROUP BY label";
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
	 * @param object table model
	 * @return string result
	 */

	function avg(&$listModel)
	{
		$db = $listModel->getDb();
		$params	= $this->getParams();
		$splitAvg	= $params->get('avg_split', '');
		$item = $listModel->getTable();
		$calcLabel = $params->get('avg_label', JText::_('COM_FABRIK_AVERAGE'));
		$split = $splitAvg == '' ? false : true;
		if ($split) {
			$pluginManager = FabrikWorker::getPluginManager();
			$plugin = $pluginManager->getElementPlugin($splitAvg);
			$splitName = method_exists($plugin, 'getJoinLabelColumn') ? $plugin->getJoinLabelColumn() : $plugin->getFullName(false, false, false);
			$splitName = FabrikString::safeColName($splitName);
			$sql = $this->getAvgQuery($listModel, $splitName) . " GROUP BY label";
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$results2 = $db->loadObjectList('label');
			$results = $this->formatCalcSplitLabels($results2, $plugin, 'avg');
		} else {
			// need to add a group by here as well as if the ONLY_FULL_GROUP_BY SQL mode is enabled
			// an error is produced
			$sql = $this->getAvgQuery($listModel) . " GROUP BY label";
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$results = $db->loadObjectList('label');
		}
		$res = $this->formatCalcs($results, $calcLabel, $split);
		return array($res, $results);
	}

	/**
	 * calculation: median
	 * can be overridden in element class
	 * @param object table model
	 * @return string result
	 */

	function median(&$listModel)
	{
		$db = $listModel->getDb();
		$item = $listModel->getTable();
		$element = $this->getElement();
		$joinSQL = $listModel->_buildQueryJoin();
		$whereSQL = $listModel->_buildQueryWhere();
		$params = $this->getParams();
		$splitMedian = $params->get('median_split', '');
		$split = $splitMedian == '' ? false : true;
		$format = $params->get('text_format_string');
		$res = '';
		$calcLabel = $params->get('median_label', JText::_('COM_FABRIK_MEDIAN'));
		$results = array();
		if ($split) {
			$pluginManager = FabrikWorker::getPluginManager();
			$plugin = $pluginManager->getElementPlugin($splitMedian);
			$splitName = method_exists($plugin, 'getJoinLabelColumn') ? $plugin->getJoinLabelColumn() : $plugin->getFullName(false, false, false);
			$splitName = FabrikString::safeColName($splitName);
			$sql = $this->getMedianQuery($listModel, $splitName) . " ORDER BY $splitName ";
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$results2 = $db->loadObjectList();
			$results = $this->formatCalcSplitLabels($results2, $plugin, 'median');

		} else {
			$sql = $this->getMedianQuery($listModel);
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$res = $this->_median($db->loadResultArray());
			$o = new stdClass();
			if ($format != '') {
				$res = sprintf($format, $res);
			}
			$o->value 	= $res;
			$label = $params->get('alt_list_heading') == '' ? $element->label : $params->get('alt_list_heading');
			$o->elLabel = $label;
			$o->calLabel = $calcLabel;
			$o->label 	= 'calc';
			$results = array('calc' => $o);
		}
		$res = $this->formatCalcs($results, $calcLabel, $split);
		return array($res, $results);
	}

	/**
	 * calculation: count
	 * can be overridden in element class
	 * @param object table model
	 * @return string result
	 */

	function count(&$listModel)
	{
		$db	= $listModel->getDb();
		$item = $listModel->getTable(true);
		$element = $this->getElement();
		$params = $this->getParams();
		$calcLabel = $params->get('count_label', JText::_('COM_FABRIK_COUNT'));
		$splitCount = $params->get('count_split', '');
		$split = $splitCount == '' ? false : true;
		if ($split) {
			$pluginManager = FabrikWorker::getPluginManager();
			$plugin = $pluginManager->getElementPlugin($splitCount);
			$name = $plugin->getFullName(false, true, false);
			$splitName = method_exists($plugin, 'getJoinLabelColumn') ? $plugin->getJoinLabelColumn() : $plugin->getFullName(false, false, false);
			$splitName = FabrikString::safeColName($splitName);
			$sql = $this->getCountQuery($listModel, $splitName) . " GROUP BY label ";
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$results2 = $db->loadObjectList('label');
			$results = $this->formatCalcSplitLabels($results2, $plugin, 'count');
		} else {
			// need to add a group by here as well as if the ONLY_FULL_GROUP_BY SQL mode is enabled
			// an error is produced
			$sql = $this->getCountQuery($listModel). " GROUP BY label ";
			$sql = $listModel->pluginQuery($sql);
			$db->setQuery($sql);
			$results = $db->loadObjectList('label');
		}
		$res = $this->formatCalcs($results, $calcLabel, $split, false);
		return array($res, $results);
	}

	/**
	 *
	 * @param array $results
	 * @param object plugin element that the data is SPLIT on
	 * @param string $type of calculation
	 * @return unknown_type
	 */
	protected function formatCalcSplitLabels(&$results2, &$plugin, $type = '')
	{
		$results = array();
		$tomerge = array();
		$name = $plugin->getFullName(false, true, false);
		// $$$ hugh - avoid PHP warning if $results2 is NULL
		if (empty($results2)) {
			return $results;
		}
		foreach ($results2 as $key => $val) {
			if ($plugin->hasSubElements) {
				$val->label = ($type == 'median') ? $plugin->getLabelForValue($val->label) : $plugin->getLabelForValue($key);
			} else {
				$d = new stdClass();
				$d->$name = $val->label;
				$val->label = $plugin->renderListData($val->label, $d);
			}
			if (array_key_exists($val->label, $results)) {
				// $$$ rob the $result data is keyed on the raw database result - however, we are intrested in
				// keying on the formatted table result (e.g. allows us to group date entries by year)
				if ($results[$val->label] !== '') {
					$tomerge[$val->label][] = $results[$val->label]->value;
				}
				//unset($results[$val->label]);
				$results[$val->label] = '';
				$tomerge[$val->label][] = $val->value;
			} else {
				$results[$val->label] = $val;
			}
		}
		foreach ($tomerge as $label => $data) {
			$o = new stdClass();
			switch ($type) {
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
	 * @param array $data to average
	 * @return string average result
	 */

	public function simpleAvg($data)
	{
		return $this->simpleSum($data)/count($data);
	}

	/**
	 * find the sum from a set of data
	 * can be overwritten in plugin - see date for example of averaging dates
	 * @param array $data to sum
	 * @return string sum result
	 */

	public function simpleSum($data)
	{
		return array_sum($data);
	}

	/**
	 * take the results form a calc and create the string that can be used to summarize them
	 * @param array calculation results
	 * @param string $calcLabel
	 * @param bol is the data split
	 * @param bol should we applpy any number formatting
	 * @return string
	 */

	protected function formatCalcs(&$results, $calcLabel, $split = false, $numberFormat = true)
	{
		settype($results, 'array');
		$res = '<span class="calclabel">'.$calcLabel.'</span>';
		if ($split) {
			$res .= '<br />';
		}
		$params = $this->getParams();
		$element = $this->getElement();
		$format = $params->get('text_format_string');
		$label = $params->get('alt_list_heading') == '' ? $element->label : $params->get('alt_list_heading');
		foreach ($results as $key => $o) {
			$o->label = ($o->label == 'calc') ? '' : $o->label;
			$o->elLabel = $label . ' ' . $o->label;
			if ($numberFormat) {
				$o->value = $this->numberFormat($o->value);
			}
			if ($format != '') {
				$o->value = sprintf( $format, $o->value);
			}
			$o->calLabel = $calcLabel;
			$res .= '<span class="calclabel">'.$o->label.':</span> '.$o->value.'<br />';
		}
		ksort($results);
		return $res;
	}

	/**
	 * @access private
	 *
	 * @param array $results
	 * @return string median value
	 */

	function _median($results)
	{
		$results = (array)$results;
		sort($results);
		if ((count($results) % 2) == 1) {
			/* odd */
			$midKey = floor(count($results) / 2);
			return $results[$midKey];
		} else {
			$midKey = floor(count($results) / 2) - 1;
			$midKey2 = floor(count($results) / 2);
			return $this->simpleAvg(array(JArrayHelper::getValue($results, $midKey), JArrayHelper::getValue($results, $midKey2)));
		}
	}

	/**
	 * overwritten in plugin classes
	 * @abstract
	 *@param int repeat group counter
	 */

	function elementJavascript($repeatCounter)
	{
	}

	function elementListJavascript()
	{
		return '';
	}

	/**
	 * create a class for the elements default javascript options
	 * @param int repeat group counter
	 *	@return object options
	 */

	function getElementJSOptions($repeatCounter)
	{
		$element = $this->getElement();
		$opts = new stdClass();
		$data = $this->_form->_data;
		$opts->repeatCounter = $repeatCounter;
		if ($this->canView() && !$this->canUse()) {
			$opts->editable = false;
		} else {
			$opts->editable = $this->_editable;
		}
		$opts->value = $this->getValue($data, $repeatCounter);
		$opts->defaultVal = $this->getDefaultValue($data);
		$validationEls = array();
		$validations = $this->getValidations();
		if (!empty($validations) && $this->_editable) {
			$watchElements = $this->getValidationWatchElements($repeatCounter);
			foreach ($watchElements as $watchElement) {
				$o = new stdClass();
				$o->id = $watchElement['id'];
				$o->triggerEvent = $watchElement['triggerEvent'];
				$validationEls[] = $o;
			}
		}
		$opts->watchElements = $validationEls;
		return $opts;
	}

	/**
	 * overwritten in plugin classes
	 * @return bol use wysiwyg editor
	 */
	function useEditor()
	{
		return false;
	}

	/**
	 * overwritten in plugin classes
	 * processes uploaded data
	 */

	function processUpload()
	{
	}

	/**
	 * @param array of scripts previously loaded (load order is important as we are loading via head.js
	 * and in ie these load async. So if you this class extends another you need to insert its location in $srcs above the
	 * current file
	 *
	 * get the class to manage the form element
	 * if a plugin class requires to load another elements class (eg user for dbjoin then it should
	 * call FabrikModelElement::formJavascriptClass('plugins/fabrik_element/databasejoin/databasejoin.js', true);
	 * to ensure that the file is loaded only once
	 */

	function formJavascriptClass(&$srcs, $script = '')
	{
		static $elementclasses;

		if (!isset($elementclasses)) {
			$elementclasses = array();
		}
		//load up the default scipt
		if ($script == '') {
			$script = 'plugins/fabrik_element/'.$this->getElement()->plugin.'/'.$this->getElement()->plugin.'.js';
		}
		if (empty($elementclasses[$script])) {
			$srcs[] = $script;
			$elementclasses[$script] = 1;
		}
	}

	function tableJavascriptClass()
	{
		$p = $this->getElement()->plugin;
		FabrikHelperHTML::script('plugins/fabrik_element/'.$p.'/list-'.$p.'.js');
	}

	/**
	 * can be overwritten in plugin classes
	 * eg if changing from db join to field we need to remove the join
	 * entry from the #__{package}_joins table
	 * @param object row that is going to be updated
	 */

	function beforeSave(&$row)
	{
		$maskbits = 4;
		$post	= JRequest::get('post', $maskbits);
		$post = $post['jform'];
		
		$dbjoinEl = (is_subclass_of($this, 'plgFabrik_ElementDatabasejoin') || get_class($this) == 'plgFabrik_ElementDatabasejoin');

		if (!$this->isJoin() && !$dbjoinEl) {
			$this->deleteJoins((int)$post['id']);
		}
	}
	
	protected function deleteJoins($id)
	{
		$element = $this->getElement();
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->delete('#__{package}_joins')->where('element_id = '.$id);
		$db->setQuery($query);
		$db->query();
			
		$query->clear();
		$query->select('j.id AS jid')->from('#__{package}_elements AS e')
		->join('LEFT', ' #__{package}_joins AS j ON j.element_id = e.id')
		->where('e.parent_id = '.$id);
		$db->setQuery($query);
		$join_ids = $db->loadResultArray();
		
		if (!empty($join_ids)) {
			$query->clear();
			$query->delete('#__{package}_joins')->where('id IN ('.implode(',', $join_ids).')');
			$db->setQuery($query);
			$db->query();
		}
	}

	/**
	 * OPTIONAL
	 * If your element risks not to post anything in the form (e.g. check boxes with none checked)
	 * the this function will insert a default value into the database
	 * @param object params
	 * @param array form data
	 * @return array form data
	 */

	function getEmptyDataValue(&$data)
	{
	}

	/**
	 * used to format the data when shown in the form's email
	 * @param mixed element's data
	 * @param array form records data
	 * @param int repeat group counter
	 * @return string formatted value
	 */

	function getEmailValue($value, $data, $repeatCounter)
	{
		if ($this->_inRepeatGroup) {
			$val = array();
			foreach ($value as $v2) {
				$val[] = $this->_getEmailValue($v2, $data, $repeatCounter);
			}
		} else {
			$val = $this->_getEmailValue($value, $data, $repeatCounter);
		}
		return $val;
	}

	protected function _getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		return $value;
	}

	function isUpload()
	{
		return $this->_is_upload;
	}

	/**
	 * can be overwritten in plugin class
	 * If a database join element's value field points to the same db field as this element
	 * then this element can, within modifyJoinQuery, update the query.
	 * E.g. if the database join element points to a file upload element then you can replace
	 * the file path that is the standard $val with the html to create the image
	 *
	 * @param string $val
	 * @param string view form or table
	 * @return string modified val
	 */

	function modifyJoinQuery($val, $view='form')
	{
		return $val;
	}

	function ajax_loadTableFields()
	{
		$db = FabrikWorker::getDbo();
		$listModel = JModel::getInstance('List', 'FabrikFEModel');
		$this->_cnnId = JRequest::getInt('cid', 0);
		$tbl = $db->nameQuote(JRequest::getVar('table'));
		$fieldDropDown = $listModel->getFieldsDropDown($this->_cnnId, $tbl, '-', false, 'params[join_val_column]');
		$fieldDropDown2 = $listModel->getFieldsDropDown($this->_cnnId, $tbl, '-', false, 'params[join_key_column]');
		echo "$('addJoinVal').innerHTML = '$fieldDropDown';";
		echo "$('addJoinKey').innerHTML = '$fieldDropDown2';";
	}

	/**
	 * CAN BE OVERWRITTEN IN PLUGIN CLASS
	 * create sql join string to append to table query
	 * @return string join statement
	 */

	function getJoin($tableName = '')
	{
		return null;
	}

	/**
	 * should no longer normally BE OVERWRITTEN IN PLUGIN CLASS
	 * get db field type
	 */

	function getFieldDescription()
	{
		$p = $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		$group = $this->getGroup();
		if ($group->isJoin() == 0 && $group->canRepeat()) {
			return "TEXT";
		} else {
			$size = $p->get('maxlength', $this->fieldSize);
			$objtype = sprintf($this->fieldDesc, $size);
		}
		return $objtype;
	}

	/**
	 * CAN BE OVERWRITTEN IN PLUGIN CLASS
	 * trigger called when a row is deleted, can be used to delete images previously uploaded
	 */

	function onDeleteRows()
	{

	}

	/**
	 * CAN BE OVERWRITTEN IN PLUGIN CLASS
	 * trigger called when a row is stored
	 * @param array data to store
	 */

	function onStoreRow($data)
	{

	}

	/**
	 * CAN BE OVERWRITTEN IN PLUGIN CLASS
	 *
	 * child classes can then call this function with
	 * return parent::renderListData($data, $oAllRowsData);
	 * to perform rendering that is applicable to all plugins
	 *
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderListData($data, $oAllRowsData)
	{
		$params = $this->getParams();
		$listModel = $this->getListModel();
		$data = FabrikWorker::JSONtoData($data, true);
		foreach ($data as $i => &$d) {
			if ($params->get('icon_folder') != -1 && $params->get('icon_folder') != '') {
				// $$$ rob was returning here but that stoped us being able to use links and icons together
				$d = $this->_replaceWithIcons($d);
			}
			$d = $this->rollover($d, $oAllRowsData, 'list');
			$d = $listModel->_addLink($d, $this, $oAllRowsData, $i);
		}

		if (is_array($data) && count($data) > 1) {
			if (!array_key_exists(0, $data)){
				//occurs if we have created a list from an exisitng table whose data contains json objects (e.g. jos_users.params)
				$obj = JArrayHelper::toObject($data);
				$data = array();
				$data[0] = $obj;
			}
			//if we are storing info as json the data will contain an array of objects
			if (is_object($data[0])) {
				foreach ($data as &$o) {
					$this->convertDataToString($o);
				}
			}
			$r = '<ul class="fabrikRepeatData"><li>'.implode('</li><li>', $data).'</li></ul>';
		} else {
			$r = empty($data) ? '' : array_shift($data);
		}
		return $r;
	}

	protected function convertDataToString(&$o)
	{
		if (is_object($o)) {
			$s = '<ul>';
			foreach ($o as $k => $v) {
				$s .= '<li>'.$v.'</li>';
			}
			$s .= '</ul>';
			$o = $s;
		}
	}


	function renderListData_csv($data, $oAllRowsData)
	{
		return $data;
	}

	/**
	 * determines if the element should be shown in the table view
	 *
	 * @param object $listModel
	 * @return bol
	 */

	function inTableFields(&$listModel)
	{
		$params = $this->getParams();
		$element = $this->getElement();
		$table = $listModel->getTable();
		$elFullName = $this->getFullName(true, false, false);

		if ($listModel->_outPutFormat == 'rss') {
			$bAddElement = ($params->get('show_in_rss_feed') == '1');
			/* if its the date ordering col we should add it to the list of allowed elements */
			if ($elFullName == $listModel->getParams()->get('feed_date', '')) {
				$bAddElement = true;
			}
		} else {
			$bAddElement = $element->show_in_list_summary;
		}
		if ($table->db_primary_key == $elFullName) {
			$listModel->_temp_db_key_addded = true;
		}
		return $bAddElement;
	}

	/**
	 * builds some html to allow certain elements to display the option to add in new options
	 * e.g. pciklists, dropdowns radiobuttons
	 *
	 * @param bol if true show one field which is used for both the value and label, otherwise show
	 * separate value and label fields
	 * @param int repeat group counter
	 */

	function getAddOptionFields($repeatCounter)
	{
		$params = $this->getParams();
		if (!$params->get('allow_frontend_addto')) {
			return;
		}
		$id = $this->getHTMLId($repeatCounter);
		$valueid = $id.'_ddVal';
		$labelid = $id.'_ddLabel';
		$value = '<input class="inputbox text" id="'.$valueid.'" name="addPicklistValue" />';
		$label = '<input class="inputbox text" id="'.$labelid.'" name="addPicklistLabel" />';
		$str[] = '<a href="#" title="'.JText::_('add option').'" class="toggle-addoption">';
		$str[] = '<img src="'.COM_FABRIK_LIVESITE.'media/com_fabrik/images/action_add.png" alt="'.JText::_('COM_FABRIK_ADD').'"/>';
		$str[] = '</a>';
		$str[] = '<br style="clear:left"/>';
		$str[] = '<div class="addoption"><div>'.JText::_('COM_FABRIK_ADD_A_NEW_OPTION_TO_THOSE_ABOVE').'</div>';
		if (!$params->get('allowadd-onlylabel') && $params->get('savenewadditions')) {
			// $$$ rob dont wrap in <dl> as the html is munged when rendered inside form tab template
			$str[] = '<label for="'.$valueid.'">'.JText::_('COM_FABRIK_VALUE').'</label>';
			$str[] = $value;
			$str[] = '<label for="'.$labelid.'">'.JText::_('COM_FABRIK_LABEL').'</label>';
			$str[] = $label;
		} else {
			$str[] = $label;
		}
		$str[] = '<input class="button" type="button" id="'.$id.'_dd_add_entry" value="'.JText::_('COM_FABRIK_ADD').'" />';
		$str[] = $this->getHiddenField($id . "_additions", '', $id . "_additions");
		$str[] = '</div>';
		return implode("\n", $str);
	}

	/**
	 * overwritten in plugins
	 * @return bol true if the element type forces the form to
	 * run in ajax submit mode (e.g. fancy upload file uploader)
	 */

	function requiresAJAXSubmit()
	{
		return false;
	}

	/**
	 * Can be overwritten by plugin - see date plugin
	 * called on failed form validation.
	 * Ensures submitted form data is converted back into the format
	 * that the form would expect to get it in, if the data had been
	 * draw from the database record
	 * @param string submitted form value
	 * @return string formated value
	 */

	function toDbVal($str)
	{
		return $str;
	}

	/**
	 * determine if the element should run its validation plugins on form submission
	 * can be overwritten by plugin class (see user plugin)
	 * @return bol default true
	 */

	function mustValidate()
	{
		return true;
	}

	/**
	 * get the name of the field to order the table data by
	 * can be overwritten in plugin class - but not currently done so
	 * @return string column to order by tablename___elementname and yes you can use aliases in the order by clause
	 */

	function getOrderByName()
	{
		return $this->getFullName(false, true, false);
	}

	function getFilterLabel($rawval)
	{
		return $rawval;
	}

	/**
	 * store the element params
	 * @return boolean
	 */

	function storeAttribs()
	{
		$element = $this->getElement();
		if (!$element) {
			return false;
		}
		$db = FabrikWorker::getDbo(true);
		$element->params = $this->getParams()->toString();
		$query = $db->getQuery(true);
		$query->update('#__{package}_elements')->set("params = ".$db->Quote($element->params))->where("id = ".(int)$element->id);
		$db->setQuery($query);
		$res = $db->query();
		if (!$res) {
			JError::raiseError(500, $db->getErrorMsg());
		}
		return $res;
	}

	/**
	 * load a new set of default properites and params for the element
	 * can be overridden in plugin class
	 * @return object element (id = 0)
	 */

	public function getDefaultProperties()
	{
		$user = JFactory::getUser();
		$now = JFactory::getDate()->toMySQL();
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
	 * get a json encoded string of the element default parameters
	 * @return string
	 */

	function getDefaultAttribs()
	{
		$o = new stdClass();
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
		$o->filter_access = 0;
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
	 * do we need to include the lighbox js code
	 *
	 * @return bol
	 */

	function requiresLightBox()
	{
		return false;
	}

	/**
	 * ca be overridden in plugin
	 * @return array key=>value options
	 */
	function getJoomfishOptions()
	{
		return array();
	}

	/**
	 * can be overridden in plug-in
	 * when filtering a table determine if the element's filter should be an exact match
	 * should take into account if the element is in a non-joined repeat group
	 * @return bol
	 */

	function isExactMatch($val)
	{
		$element = $this->getElement();
		$filterExactMatch = isset($val['match'])? $val['match'] : $element->filter_exact_match;
		$group = $this->getGroup();
		if (!$group->isJoin() && $group->canRepeat()) {
			$filterExactMatch = false;
		}
		return $filterExactMatch;
	}

	function ajax_getFolders()
	{
		$rDir = JRequest::getVar('dir');
		$folders = JFolder::folders($rDir);
		if ($folders === false) {
			// $$$ hugh - need to echo empty JSON array otherwise we break JS which assumes an array
			echo json_encode(array());
			return false;
		}
		sort($folders);
		echo json_encode($folders);
	}

	/**
	 * if used as a filter add in some JS code to watch observed filter element's changes
	 * when it changes update the contents of this elements dd filter's options
	 * @abstract
	 * @param bol is the filter a normal (true) or advanced filter
	 * @param string container
	 */

	function _filterJS($normal, $container)
	{
		//overwritten in plugin
	}

	/**
	 * @abstract
	 */

	function includeInSearchAll()
	{
		return $this->getParams()->get('inc_in_search_all', true);
	}

	/**
	 * get the value to use for graph calculations
	 * can be overwritten in plugin
	 * see fabriktimer which converts the value into seconds
	 * @param string $v
	 * @return mixed
	 */

	public function getCalculationValue($v)
	{
		return (float)$v;
	}

	/**
	 * run on formModel::setFormData()
	 * @param int repeat group counter
	 * @return null
	 */
	public function preProcess($c)
	{
	}

	/**
	 * @abstract
	 * overwritten in plugin
	 * called when copy row table plugin called
	 * @param mixed value to copy into new record
	 * @return mixed value to copy into new record
	 */

	public function onCopyRow($val)
	{
		return $val;
	}

	/**
	 * @abstract
	 * overwritten in plugin
	 * called when save as copy form button clicked
	 * @param mixed value to copy into new record
	 * @return mixed value to copy into new record
	 */

	public function onSaveAsCopy($val)
	{
		return $val;
	}

	/**
	 * from ajax call to get auto complete options
	 * @returns string json encoded optiosn
	 */

	public function onAutocomplete_options()
	{
		//needed for ajax update (since we are calling this method via dispatcher element is not set
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
		$where .= "$name LIKE " . $db->Quote(addslashes('%'.JRequest::getVar('value').'%'));
		$query = "SELECT DISTINCT($name) AS value, $name AS text FROM $tableName $join $where";
		$query = $listModel->pluginQuery($query);
		$db->setQuery($query);
		$tmp = $db->loadObjectList();
		foreach ($tmp as &$t) {
			$this->toLabel($t->text);
		}
		echo json_encode($tmp);
	}

	/**
	 * get the table name that the element stores to
	 * can be the main table name or the joined table name
	 */

	protected function getTableName()
	{
		$listModel = $this->getListModel();
		$table = $listModel->getTable();
		$groupModel = $this->getGroup();
		if ($groupModel->isJoin()) {
			$joinModel = $groupModel->getJoinModel();
			$join = $joinModel->getJoin();
			$name = $join->table_join;
		} else {
			$name = $table->db_table_name;
		}
		return $name;
	}

	/**
	 * takes a raw value and returns its label equivalent
	 * @param string $v
	 */

	protected function toLabel(&$v)
	{

	}

	/**
	 * @abstract
	 */

	public function getGroupByQuery()
	{
		return '';
	}

	public function appendTableWhere(&$whereArray)
	{
		$params = $this->getParams();
		$where = '';
		if ($params->get('append_table_where', false)) {
			if (method_exists($this, '_buildQueryWhere')) {
				$where = trim($this->_buildQueryWhere(array()));

				if ($where != '') {
					$where = substr($where, 5, strlen($where) - 5);
					if (!in_array($where, $whereArray)) {
						$whereArray[] = $where;
					}
				}
			}
		}
	}

	/**
	 * used by validations
	 * @param string this elements data
	 * @param string what condiion to apply
	 * @param string data to compare element's data to
	 */

	public function greaterOrLessThan($data, $cond, $compare)
	{
		if ($cond == '>') {
			return $data > $compare;
		} else {
			return $data < $compare;
		}
	}

	/**
	 * can the element's data be encrypted
	 * @abstract
	 */

	public function canEncrypt()
	{
		return false;
	}

	/**
	 * should the element's data be encrypted
	 * @return bool
	 */

	public function encryptMe()
	{
		$params = $this->getParams();
		return ($this->canEncrypt() && $params->get('encrypt', false));
	}

	/**
	 * format a number value
	 * @param mixed (double/int) $data
	 * @return string formatted number
	 */

	protected function numberFormat($data)
	{
		$params = $this->getParams();
		if (!$params->get('field_use_number_format', false)) {
			return $data;
		}
		$decimal_length = (int)$params->get('decimal_length', 2);
		$decimal_sep = $params->get('field_decimal_sep', '.');
		$thousand_sep = $params->get('field_thousand_sep', ',');
		// workaround for params not letting us save just a space!
		if ($thousand_sep == '#32') {
			$thousand_sep = ' ';
		}
		return number_format((float)$data, $decimal_length, $decimal_sep, $thousand_sep);
	}

	/**
	 * strip number format from a number value
	 * @param mixed (double/int) $data
	 * @return string formatted number
	 */
	function unNumberFormat($val)
	{
		$params = $this->getParams();
		if (!$params->get('field_use_number_format', false)) {
			return $val;
		}
		// might think about rounding to decimal_length, but for now let MySQL do it
		$decimal_length = (int)$params->get('decimal_length', 2);
		// swap dec and thousand seps back to Normal People Decimal Format!
		$decimal_sep = $params->get('field_decimal_sep', '.');
		$thousand_sep = $params->get('field_thousand_sep', ',');
		$val = str_replace($thousand_sep, '', $val);
		$val = str_replace($decimal_sep, '.', $val);
		return $val;
	}

	/**
	 *
	 * Recursively get all linked children of an element
	 *
	 * @param $id element id
	 */
	function getElementDescendents($id = 0)
	{
		if (empty($id)) {
			$id = $this->_id;
		}
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('id')->from('#__{package}_elements')->where('parent_id = '.(int)$id);
		$db->setQuery($query);
		$kids = $db->loadObjectList();
		$all_kids = array();
		foreach ($kids as $kid) {
			$all_kids[] = $kid->id;
			$all_kids = array_merge($this->getElementDescendents($kid->id), $all_kids);
		}
		return $all_kids;
	}

	/**
	 * get the actual table name to use when building select queries
	 * so if in a joined group get the joined to table's name otherwise return the
	 * table's db table name
	 */

	protected function actualTableName()
	{
		if (isset($this->actualTable)) {
			return $this->actualTable;
		}
		$groupModel = $this->getGroup();
		if ($groupModel->isJoin()) {
			$joinModel = $groupModel->getJoinModel();
			return $joinModel->getJoin()->table_join;

		}
		$listModel = $this->getListModel();
		$this->actualTable = $listModel->getTable()->db_table_name;
		return $this->actualTable;
	}

	/**
	 * when creating crud query in tableModel::storeRow() each element has the chance
	 * to alter the row id - used by sugarid plugin to fudge rowid
	 * @param unknown_type $rowId
	 */

	public function updateRowId(&$rowId)
	{
	}

	/**
	 * @deprecated
	 * fabrik3: moved to Admin Element Model
	 * @return string table name
	 */

	protected function getRepeatElementTableName()
	{
	}

	/**
	 * is the element a repeating element
	 * @return bool
	 */

	public function isRepeatElement()
	{
		return $this->isJoin();
	}

	/**
	 * @depreciated
	 * fabrik3: moved to Admin Element Model
	 * if repeated element we need to make a joined db table to store repeated data in
	 */

	public function createRepeatElement()
	{
	}

	/**
	 * get the element's associated join model
	 *
	 * @return object join model
	 */

	public function getJoinModel()
	{
		if (is_null($this->_joinModel)) {
			$this->_joinModel = JModel::getInstance('Join', 'FabrikFEModel');
			// $$$ rob ensure we load the join by asking for the parents id, but then ensure we set the element id back to this elements id
			$this->_joinModel->getJoinFromKey('element_id', $this->getParent()->id);
			$this->_joinModel->_join->element_id = $this->getElement()->id;
		}
		return $this->_joinModel;
	}


	public function isJoin()
	{
		return $this->getParams()->get('repeat', false);
	}

	/**
	 * used by inline edit table plugin
	 * If returns yes then it means that there are only two possible options for the
	 * ajax edit, so we should simply toggle to the alternative value and show the
	 * element rendered with that new value (used for yes/no element)
	 */

	public function canToggleValue()
	{
		return false;
	}

	/**
	 * encrypt an enitre columns worth of data, used when updating an element to encrypted
	 * with existing data in the column
	 */

	public function encryptColumn()
	{
		$secret = JFactory::getConfig()->getValue('secret');
		$listModel = $this->getListModel();
		$db = $listModel->getDb();
		$tbl = $this->actualTableName();
		$name = $this->getElement()->name;
		$db->setQuery("UPDATE $tbl SET ".$name." = AES_ENCRYPT($name, '$secret')");
		$db->query();
	}

	/**
	 * decrypt an enitre columns worth of data, used when updating an element from encrypted to decrypted
	 * with existing data in the column
	 */

	public function decryptColumn()
	{
		// @TODO this query looks right but when going from encrypted blob to decrypted field the values are set to null
		$secret = JFactory::getConfig()->getValue('secret');
		$listModel = $this->getListModel();
		$db = $listModel->getDb();
		$tbl = $this->actualTableName();
		$name = $this->getElement()->name;
		$db->setQuery("UPDATE $tbl SET ".$name." = AES_DECRYPT($name, '$secret')");
		$db->query();
	}

	/**
	 * PN 19-Jun-11: Construct an element error string.
	 * @return string
	 */
	public function selfDiagnose()
	{
		$retStr= '';
		$this->_db->setQuery
		(
			"SELECT COUNT(*) FROM #__fabrik_groups ".
			"WHERE (id = ".$this->_element->group_id.");"
		);
		$group_id = $this->_db->loadResult();

		if (!$group_id)
		{
			$retStr = 'No valid group assignment';
		}
		else if (!$this->_element->plugin)
		{
			$retStr = 'No plugin';
		}
		else if (!$this->_element->label)
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
	 * @deprecated - should be in form view now as you can have > 1 element in inlineedit plugin
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
		if (!$this->canUse()) {
			if (JRequest::getVar('task') != 'element.save') {
				echo JText::_("JERROR_ALERTNOAUTHOR");
				return;
			}
			$this->_editable = false;
		} else {
			$this->_editable = true;
		}
		$groupModel = $this->getGroup();

		$repeatCounter = 0;
		$html = '';
		$key = $this->getFullName();

		$template = JFactory::getApplication()->getTemplate();
		FabrikHelperHTML::addPath(JPATH_SITE.DS.'administrator/templates/'.$template.'/images/', 'image', 'list');

		//@TODO add acl checks here
		$task = JRequest::getVar('task');
		$saving = ($task == 'element.save' || $task == 'save') ? true : false;
		$htmlid = $this->getHTMLId($repeatCounter);
		if ($this->canToggleValue() && ($task !== 'element.save' && $task !== 'save')) {
			// ok for yes/no elements activating them (double clicking in cell)
			// should simply toggle the stored value and return the new html to show
			$toggleValues = $this->getOptionValues();
			$currentIndex = array_search($data[$key], $toggleValues);
			if ($currentIndex === false || $currentIndex == count($toggleValues)-1) {
				$nextIndex = 0;
			} else {
				$nextIndex = $currentIndex + 1;
			}
			$newvalue = $toggleValues[$nextIndex];
			$data[$key] = $newvalue;
			$shortkey = array_pop(explode("___", $key));
			$listModel->storeCell($rowid, $shortkey, $newvalue);
			$this->mode = 'readonly';
			$html = $this->renderListData($data[$key], $data);

			$script = array();
			$script[] = '<script type="text/javasript">';
			$script[] = "Fabrik.fireEvent('fabrik.list.inlineedit.stopEditing');"; //makes the inlined editor stop editing the cell
			$script[] = '</script>';

			echo $html.implode("\n", $script);
			return;
		}
		$listModel->clearCalculations();
		$listModel->doCalculations();
		$doCalcs = "\nFabrik.blocks['list_".$listModel->getRenderContext()."'].updateCals(".json_encode($listModel->getCalculations()).")";

		if(!$saving) {
			// so not an element with toggle values, so load up the form widget to enable user
			// to select/enter a new value
			//wrap in fabriKElement div to ensure element js code works

			$html .= '<div class="floating-tip" style="position:absolute">
			<ul class="fabrikElementContainer">';
			$html .= '<li class="fabrikElement">';
			$html .= $this->_getElement($data, $repeatCounter, $groupModel);
			$html .= '</li>';
			$html .= '</ul>';
				
			if (JRequest::getBool('inlinesave') || JRequest::getBool('inlinecancel')) {
				$html .= '<ul class="fabrik_buttons">';

				if (JRequest::getBool('inlinecancel') == true) {
					$html .= '<li class="ajax-controls inline-cancel">';
					$html .= '<a href="#" class="">';
					$html .= FabrikHelperHTML::image('delete.png', 'list', @$this->tmpl, array('alt' => JText::_('COM_FABRIK_CANCEL'))).'<span></span></a>';
					$html .= '</li>';
				}

				if (JRequest::getBool('inlinesave') == true) {
					$html .= '<li class="ajax-controls inline-save">';
					$html .= '<a href="#" class="">';
					$html .= FabrikHelperHTML::image('save.png', 'list', @$this->tmpl, array('alt' => JText::_('COM_FABRIK_SAVE')));
					$html .= '<span>'.JText::_('COM_FABRIK_SAVE').'</span></a>';
					$html .= '</li>';
				}


				$html .= '</ul>';
			}
				
			$html .= '</div>';
			$onLoad = "Fabrik.inlineedit_$elementid = ".$this->elementJavascript($repeatCounter).";\n".
			"Fabrik.inlineedit_$elementid.select();
			Fabrik.inlineedit_$elementid.focus();
			Fabrik.inlineedit_$elementid.token = '".JUtility::getToken()."';\n";

			$onLoad .= "Fabrik.fireEvent('fabrik.list.inlineedit.setData');\n";
			$srcs = array();
			$this->formJavascriptClass($srcs);
			FabrikHelperHTML::script($srcs, $onLoad);
		} else {
			$html .= $this->renderListData($data[$key], $data);
			$html .= '<script type="text/javasript">';
			$html .= $doCalcs;
			$html .= "</script>\n";
		}
		echo $html;
	}

	/**
	 * since 3.0b
	 * @deprecated
	 * Shortcut to get plugin manager
	 */
	public function getPluginManager()
	{
		return FabrikWorker::getPluginManager();
	}
}
?>