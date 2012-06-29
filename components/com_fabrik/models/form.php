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
require_once('fabrikmodelform.php');

//was FabModel
class FabrikFEModelForm extends FabModelForm
{

	/** @var int id */
	public $id = null;

	/** @var int set to -1 if form in ajax module, set to 1+ if in package */
	public $packageId = 0;

	/* not used in database (need to be prefixed with "_")*/
	/** @var array form's group elements*/
	var $_elements = null;

	/** @var object table model assocated with form*/
	protected $_listModel = null;

	/** @var array of group ids that are actually tablejoins [groupid->joinid]*/
	var $_aJoinGroupIds = array();

	/** @var bol true if editable if 0 then show view only verion of form */
	var $_editable = true;

	/** @var string encoding type */
	var $_enctype = "application/x-www-form-urlencoded";

	/** @var array validation rule classes */
	var $_validationRuleClasses = null;

	/**@var bol is the form running as a mambot or module(true)*/
	var $isMambot = false;

	/** @var array of join objects for the form */
	var $_aJoinObjs = array();

	public $joinTableElementStep = '___';

	/** @var object parameters */
	protected $_params = null;

	/** @var int row id to submit */
	var $_rowId = null;

	/** @since 3.0
	 * @var bool submitted as ajax*/
	var $ajax = null;

	/** @var object form **/
	var $_form = null;

	/** @var object last current element found in hasElement()*/
	var $_currentElement = null;

	/** @var bol if true encase table and element names with "`" when getting elemenet list */
	var $_addDbQuote = false;

	var $_formData = null;

	/** @var array form errors */
	var $_arErrors = array();

	/** @var object uploader helper */
	protected $uploader = null;

	/** @var array pages (array containing group ids for each page in the form **/
	var $pages = null;

	/** @var object session model deals with storing incomplete pages **/
	var $sessionModel = null;

	/** @var array modified data by any validation rule that uses replace functionality */
	var $_modifiedValidationData = null;

	var $groups = null;

	/** store the form's previous data when processing */
	var $_origData = null;

	/** @var array stores elements not shown in table **/
	var $_elementsNotInTable = null;

	var $_data = null;

	var $_formDataWithTableName = null;

	/** @var bool should the form store the main row? Set to false in juser plugin if fabrik table is also jos_users */
	var $_storeMainRow = true;

	/** @var string query used to load form record */
	var $query = null;

	/** #var array specifies element name that have been overridden from a form plugin, so encrypted RO data should be ignored */
	var $_pluginUpdatedElements = array();

	var $_linkedFabrikLists = null;

	/** @var bool are we copying a row?  i.e. using form's Copy button.  Plugin manager needs to know. */
	var $_copyingRow = false;

	/**
	 * Constructor
	 *
	 * @since 1.5
	 */

	function __construct()
	{
		parent::__construct();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$id = JRequest::getInt('formid', $usersConfig->get('formid'));
		$this->setId($id);
	}

	/**
	 * Method to set the form id
	 * @since fabrk 3 - DEPRECIATE - SHOULD USE POPULATE STATE
	 * @access	public
	 * @param	int	table ID number
	 */

	function setId($id)
	{
		// Set new form ID
		$this->id = $id;
		$this->setState('form.id', $id);
		// $$$ rob not sure why but we need this getState() here
		// when assinging id from admin view
		$this->getState();
	}

	/**
	 * Method to get the form id
	 *
	 * @access	public
	 */
	function getId()
	{
		return $this->getState('form.id');
	}

	/**
	 * get form table (alias to getTable())
	 *
	 * @return object form table
	 */

	function getForm()
	{
		return $this->getTable();
	}

	/**
	 * checks if the params object has been created and if not creates and returns it
	 * @return object params
	 */

	function getParams()
	{
		if (!isset($this->_params))
		{
			$form = $this->getForm();
			$this->_params = new JRegistry($form->params);
		}
		return $this->_params;
	}

	/**
	 * makes sure that the form is not viewable based on the table's access settings
	 * @return int 0 = no access, 1 = view only , 2 = full form view, 3 = add record only
	 */

	function checkAccessFromListSettings()
	{
		$form = $this->getForm();
		if ($form->record_in_database == 0)
		{
			return 2;
		}
		$listModel = $this->getListModel();
		if (!is_object($listModel))
		{
			return 2;
		}
		$ret = 0;
		if ($listModel->canViewDetails())
		{
			$ret = 1;
		}
		$pRowid = FabrikWorker::getMenuOrRequestVar('rowid', '', $this->isMambot);
		/* new form can we add?*/
		if ($this->_rowId == 0 || $pRowid == '-1')
		{
			/*if they can edit can they also add?*/
			if ($listModel->canAdd())
			{
				$ret = 3;
			}
			// $$$ hugh - corner case for rowid=-1, where they DON'T have add perms, but DO have edit perms
			else if ($pRowid == '-1' && $listModel->canEdit($this->_data))
			{
				$ret = 2;
			}
		}
		else
		{
			/*editing from - can we edit?*/
			if ($listModel->canEdit($this->_data))
			{
				$ret = 2;
			}
		}
		//$$$rob refractored from view
		$this->_editable = ($ret == 1 && $this->_editable == '1') ? false : true;
		if (JRequest::getVar('view', 'form') == 'details')
		{
			$this->_editable = false;
		}
		return $ret;
	}

	/**
	 * @since 3.0
	 * get the template name
	 * @return string tmpl name
	 */

	public function getTmpl()
	{
		$app = JFactory::getApplication();
		$params = $this->getParams();
		$item = $this->getForm();
		$tmpl = '';
		$document = JFactory::getDocument();
		if ($document->getType() === 'pdf')
		{
			$tmpl = $params->get('pdf_template');
		}
		else
		{
			if ($app->isAdmin())
			{
				$tmpl = $this->_editable ? $params->get('admin_form_template') : $params->get('admin_details_template');
			}
			if ($tmpl == '')
			{
				if ($this->_editable)
				{
					$tmpl = $item->form_template == '' ? 'default' : $item->form_template;
				}
				else
				{
					$tmpl = $item->view_only_template == '' ? 'default' : $item->view_only_template;
				}
			}
			if (JRequest::getVar('mjmarkup') == 'iphone')
			{
				$tmpl = 'iwebkit';
			}
		}
		$tmpl = FabrikWorker::getMenuOrRequestVar('fabriklayout', $tmpl, $this->isMambot);
		//finally see if the options are overridden by a querystring var
		$tmpl = JRequest::getVar('layout', $tmpl);
		//test it exists - otherwise revert to default tmpl
		if (!JFolder::exists(JPATH_SITE . '/components/com_fabrik/views/form/tmpl/' . $tmpl))
		{
			$tmpl = 'default';
		}
		$item->form_template = $tmpl;
		return $tmpl;
	}

	/**
	 * loads form's css files
	 * Checks : custom css file, template css file. Including them if found
	 */

	public function getFormCss()
	{
		$app = JFactory::getApplication();
		$tmpl = $this->getTmpl();
		$v = $this->_editable ? 'form' : 'details';
		/* check for a form template file (code moved from view) */
		if ($tmpl != '')
		{
			$qs = '?c=' . $this->getId();
			// $$$ need &amp; for pdf output which is parsed through xml parser otherwise fails (if ajax loaded then dont do &amp;
			$qs .= JRequest::getVar('ajax') == 1  ? '&view=' . $v : '&amp;view=' . $v;
			if (!FabrikHelperHTML::stylesheetFromPath('templates/' . $app->getTemplate() . '/html/com_fabrik/form/' . $tmpl . '/template_css.php' . $qs))
			{
				FabrikHelperHTML::stylesheetFromPath('components/com_fabrik/views/form/tmpl/' . $tmpl . '/template_css.php' . $qs);
			}
			// $$$ hugh - as per Skype convos with Rob, decided to re-instate the custom.css convention.  So I'm adding two files:
			// custom.css - for backward compat with existing 2.x custom.css
			// custom_css.php - what we'll recommend people use for custom css moving foward.
			FabrikHelperHTML::stylesheetFromPath('components/com_fabrik/views/form/tmpl/' . $tmpl . '/custom.css' . $qs);
			FabrikHelperHTML::stylesheetFromPath('components/com_fabrik/views/form/tmpl/' . $tmpl . '/custom_css.php' . $qs);
		}
		if ($app->isAdmin() && JRequest::getVar('tmpl') === 'components')
		{
			FabrikHelperHTML::stylesheet('administrator/templates/system/css/system.css');
		}
	}

	/**
	 * load the JS files into the document
	 * @param	array	reference: js script srcs to load in the head
	 * @return null
	 */

	function getCustomJsAction(&$srcs)
	{
		// $$$ hugh - added ability to use form_XX, as am adding custom list_XX
		if (JFile::exists(COM_FABRIK_FRONTEND . '/js/' . $this->getId() . '.js'))
		{
			$srcs[] = 'components/com_fabrik/js/' . $this->getId() . '.js';
		}
		else if (JFile::exists(COM_FABRIK_FRONTEND . '/js/form_' . $this->getId() . '.js'))
		{
			$srcs[] = 'components/com_fabrik/js/form_' . $this->getId() . '.js';
		}
	}

	/**
	 * set the page title for form
	 * @return	string	page title
	 */

	function getPageTitle($title = '')
	{
		$params = $this->getParams();
		$label = $this->getLabel();
		if (JRequest::getVar('view') == 'details')
		{
			if (!$params->get('show-title-in-detail-view', true))
			{
				$title = '';
			}
			else
			{
				$title = ($title == '') ? $label : $title . " ";
			}
		}
		else
		{
			$title = ($title == '') ? $label : $title . " ";
		}
		$groups = $this->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();
				if ($element->use_in_page_title == '1')
				{
					$default = $elementModel->getTitlePart($this->_data);
					$s = is_array($default) ? implode(', ', $default) . " " : $default . " ";
					$title .= ' ' . $s;
				}
			}
		}
		return $title;
	}

	/**
	 * compares the forms table with its groups to see if any of the groups are in fact table joins
	 * @param	array	tables joins
	 * @return	array	array(group_id =>join_id)
	 */

	function getJoinGroupIds($joins)
	{
		$arJoinGroupIds = array();
		$groups = $this->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			foreach ($joins as $join)
			{
				if ($join->element_id == 0 && $groupModel->getGroup()->id == $join->group_id)
				{
					$arJoinGroupIds[$groupModel->getId()] = $join->id;
				}
			}
		}
		$this->_aJoinGroupIds = $arJoinGroupIds;
		return $arJoinGroupIds;
	}

	/**
	 * gets the javascript actions the forms elements
	 * @return	array	javascript actions
	 */

	function getJsActions()
	{
		if (isset($this->jsActions))
		{
			return $this->jsActions;
		}
		$this->jsActions = array();
		$db = FabrikWorker::getDbo(true);
		$j = new JRegistry();
		$aJsActions = array();
		$aElIds = array();
		$groups = $this->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				// $$$ hugh - only needed getParent when we weren't saving changes to parent params to child
				// which we should now be doing ... and getParent() causes an extra table lookup for every child
				// element on the form.
				$aJsActions[$elementModel->getElement()->id] = array();
				$aElIds[] = (int) $elementModel->getElement()->id;
			}
		}
		if (!empty($aElIds))
		{
			$query = $db->getQuery(true);
			$query->select('*')->from('#__{package}_jsactions')->where('element_id IN (' . implode(',', $aElIds) . ')');
			$db->setQuery($query);
			$res = $db->loadObjectList();
			if ($db->getErrorNum())
			{
				JError::raiseError(500, $db->getErrorMsg());
			}
		}
		else
		{
			$res = array();
		}
		if (is_array($res))
		{
			foreach ($res as $r)
			{
				//merge the js attribs back into the array
				$a = json_decode($r->params);
				foreach ($a as $k=>$v)
				{
					$r->$k = $v;
				}
				unset($r->params);
				$this->jsActions[$r->element_id][] = $r;
			}
		}
		return $this->jsActions;
	}

	/**
	 * test to try to load all group data in one query and then bind that data to group table objects
	 * in getGroups()
	 */

	function getPublishedGroups()
	{
		$db = FabrikWorker::getDbo(true);
		if (!isset($this->_publishedformGroups) || empty($this->_publishedformGroups))
		{
			$params = $this->getParams();
			$sql = "SELECT *, fg.group_id AS group_id, RAND() AS rand_order FROM #__{package}_formgroup AS fg
INNER JOIN #__{package}_groups as g ON g.id = fg.group_id
 WHERE fg.form_id = ".(int) $this->getId()." AND published = 1";
			if ($params->get('randomise_groups') == 1) {
				$sql .= " ORDER BY rand_order";
			} else {
				$sql .= " ORDER BY fg.ordering";
			}
			$db->setQuery($sql);
			$groups = $db->loadObjectList('group_id');
			if ($db->getErrorNum()) {
				JError::raiseError(500, $db->getErrorMsg());
			}
			$this->_publishedformGroups = $this->mergeGroupsWithJoins($groups);
		}
		return $this->_publishedformGroups;
	}

	/** get the ids of all the groups in the form
	 * @return array of group ids
	 */

	function getGroupIds()
	{
		$groups = $this->getPublishedGroups();
		return array_keys($groups);
	}

	/**
	 * force load in the group ids
	 * separate from getGroupIds as you need to force load these
	 * when saving the table
	 */

	function _loadGroupIds()
	{
		unset($this->_publishedformGroups);
		return $this->getGroupIds();
	}

	private function mergeGroupsWithJoins($groups)
	{
		$db = FabrikWorker::getDbo(true);
		$form = $this->getForm();
		if ($form->record_in_database) {
			$listModel = $this->getListModel();
			$listid = (int) $listModel->getId();
			if (is_object($listModel) && $listid !== 0) {
				$db->setQuery("SELECT g.id, j.id AS joinid FROM #__{package}_joins AS j INNER JOIN #__{package}_groups AS g ON g.id = j.group_id WHERE list_id = '$listid' AND g.published = 1 ");
				$joinGroups = $db->loadObjectList('id');
				foreach($joinGroups as $k=>$o) {
					if (array_key_exists($k, $groups)) {
						$groups[$k]->join_id = $o->joinid;
					}
				}
			}
		}
		return $groups;
	}

	/**
	 * get the forms published group objects
	 *
	 * @return array group model objects with table row loaded
	 */

	function getGroups()
	{
		if (!isset($this->groups))
		{
			$this->groups = array();
			$listModel = $this->getListModel();
			$groupModel = JModel::getInstance('Group', 'FabrikFEModel');
			$groupdata = $this->getPublishedGroups();
			foreach ($groupdata as $id => $groupd)
			{
				$thisGroup = clone($groupModel);
				$thisGroup->setId($id);
				$thisGroup->setContext($this, $listModel);
				// $$ rob 25/02/2011 this was doing a query per group - pointless as we bind $groupd to $row afterwards
				//$row = $thisGroup->getGroup();
				$row = FabTable::getInstance('Group', 'FabrikTable');
				$row->bind($groupd);
				$thisGroup->_group = $row;
				if ($row->published == 1)
				{
					$this->groups[$id] = $thisGroup;
				}
			}
		}
		return $this->groups;
	}

	/**
	 * gets each element in the form along with its group info
	 * @param bol included unpublished elements in the result
	 * @return array element objects
	 */

	function getFormGroups($excludeUnpublished = true)
	{
		$params = $this->getParams();
		$db = FabrikWorker::getDbo(true);
		$sql = "SELECT *, #__{package}_groups.params AS gparams, #__{package}_elements.id as element_id
		, #__{package}_groups.name as group_name, RAND() AS rand_order FROM #__{package}_formgroup
		LEFT JOIN #__{package}_groups
		ON #__{package}_formgroup.group_id = #__{package}_groups.id
		LEFT JOIN #__{package}_elements
		ON #__{package}_groups.id = #__{package}_elements.group_id
		WHERE #__{package}_formgroup.form_id = " . (int) $this->getState('form.id') . " ";
		if ($excludeUnpublished) {
			$sql .= " AND #__{package}_elements.published = '1' ";
		}
		if ($params->get('randomise_groups') == 1) {
			$sql .= " ORDER BY rand_order, #__{package}_elements.ordering";
		} else {
			$sql .= " ORDER BY #__{package}_formgroup.ordering, #__{package}_formgroup.group_id, #__{package}_elements.ordering";
		}
		$db->setQuery($sql);
		$groups = $db->loadObjectList();
		if ($db->getErrorNum()) {
			JError::raiseError(500, $db->getErrorMsg());
		}
		$this->_elements = $groups;
		return $groups;
	}

	/**
	 * similar to getFormGroups() except that this returns a data structure of
	 * form
	 * --->group
	 * -------->element
	 * -------->element
	 * --->group
	 * if run before then existing data returned
	 * @return	array	element objects
	 */

	function getGroupsHiarachy()
	{
		if (!isset($this->groups))
		{
			$this->getGroups();
			$this->groups = FabrikWorker::getPluginManager()->getFormPlugins($this);
		}
		return $this->groups;
	}

	/**
	 * get an list of elements that aren't shown in the table view
	 * @return	array	of element table objects
	 */

	function getElementsNotInTable()
	{
		if (!isset($this->_elementsNotInTable))
		{
			$this->_elementsNotInTable = array();
			$groups = $this->getGroupsHiarachy();
			foreach ($groups as $group)
			{
				$elements = $group->getPublishedElements();
				foreach ($elements as $elementModel)
				{
					if ($elementModel->canView() || $elementModel->canUse())
					{
						$element = $elementModel->getElement();
						if (!isset($element->show_in_list_summary) || !$element->show_in_list_summary)
						{
							$this->_elementsNotInTable[] = $element;
						}
					}
				}

			}
		}
		return $this->_elementsNotInTable;

	}

	/**
	 * this checks to see if the form has a file upload element
	 * and returns the correct encoding type for the form
	 * @return	string	form encoding type
	 */

	function getFormEncType()
	{
		$groups = $this->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				if ($elementModel->isUpload())
				{
					return "multipart/form-data";
				}
			}
		}
		return "application/x-www-form-urlencoded";
	}

	/**
	 * run a method on all the element plugins in the form
	 *
	 * @param	string	method to call
	 * @param	array	posted form data
	 */

	function runElementPlugins($method, $data)
	{
		$groups = $this->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$params = $elementModel->getParams();
				if (method_exists($elementModel, $method))
				{
					$elementModel->$method($params, $data);
				}
			}
		}
	}

	/**
	 * get the plugin manager
	 * @deprecated use return FabrikWorker::getPluginManager(); instead since 3.0b
	 * @return object plugin manager
	 */

	function getPluginManager()
	{
		return FabrikWorker::getPluginManager();
	}

	/**
	 * when the form is submitted we want to get the orginal record it
	 * is updating - this is used in things like the fileupload element
	 * to check for changes in uploaded files and process the difference
	 * @return	object
	 */

	function setOrigData()
	{
		if (JRequest::getInt('rowid') == 0)
		{
			$this->_origData = array(new stdClass());
		}
		else
		{
			$listModel = $this->getListModel();
			$fabrikDb = $listModel->getDb();
			$sql = $this->_buildQuery();
			$fabrikDb->setQuery($sql);
			$this->_origData = $fabrikDb->loadObjectList();
		}
	}

	function getOrigData()
	{
		if (!isset($this->_origData))
		{
			$this->setOrigData();
		}
		return $this->_origData;
	}

	/**
	 * Are we copying a row?  Usually set in controller process().
	 *
	 * @param	bool	if true, set _copyingRow to true
	 * @return	bool
	 */

	function copyingRow($set = false) {
		if ($set)
		{
			$this->_copyingRow = true;
		}
		return $this->_copyingRow;
	}

	/**
	 * processes the form data and decides what action to take
	 * @return bool false if one of the plugins reuturns an error otherwise true
	 */

	function process()
	{
		if (JRequest::getCmd('format') == 'raw')
		{
			// $$$ hugh - although this is useful, so things like harmless notices don't mess with JSON
			// formatting in AJAX calls, it causes a nasty situation on machines with Suhosin installed.
			// Seems that Suhosin will automatically flag a response with "Error 500 - Internal Server Error"
			// if there is any kind of PHP notice or warning raised, and display_errors is Off.  So this
			// breaks AJAX calls in a way that cannot be debugged, as the error message goes nowhere.
			// What we might want to consider instead is removing notices and warnings from the reporting level?
			// ini_set('display_errors', 0);
			error_reporting( error_reporting() ^ (E_WARNING | E_NOTICE) );
		}

		error_reporting( error_reporting() ^ (E_WARNING | E_NOTICE) );
		@set_time_limit(300);
		require_once(COM_FABRIK_FRONTEND . '/helpers/uploader.php');
		$form = $this->getForm();
		$pluginManager = FabrikWorker::getPluginManager();
		$params = $this->getParams();

		$sessionModel = JModel::getInstance('Formsession', 'FabrikFEModel');
		$sessionModel->setFormId($this->getId());
		$sessionModel->setRowId($this->_rowId);
		// $$$ rob _rowId can be updated by juser plugin so plugin can use check (for new/edit)
		// now looks at _origRowId
		$this->_origRowId = $this->_rowId;

		$this->getGroupsHiarachy();

		if ($form->record_in_database == '1')
		{
			$this->setOrigData();
		}

		if (in_array(false, $pluginManager->runPlugins('onBeforeProcess', $this)))
		{
			return;
		}
		$this->removeEmptyNoneJoinedGroupData($this->_formData);

		$this->setFormData();

		if (!$this->_doUpload())
		{
			return false;
		}
		// $$$ rob 27/10/2011 - moved above _doUpload as code in there is tryign to update _formData which is not yet set
		//$this->setFormData();


		if (in_array(false, $pluginManager->runPlugins('onBeforeStore', $this)))
		{
			return false;
		}

		$this->_formDataWithTableName = $this->_formData;

		if ($form->record_in_database == '1')
		{
			$this->processToDB();
		}

		// Clean the cache.
		$cache = JFactory::getCache(JRequest::getCmd('option'));
		$cache->clean();

		//$$$rob run this before as well as after onAfterProcess (ONLY for redirect plugin)
		// so that any redirect urls are available for the plugin (e.g twitter)
		$pluginManager->runPlugins('onLastProcess', $this);

		if (in_array(false, $pluginManager->runPlugins('onAfterProcess', $this)))
		{
			// $$$ rob this no longer stops default redirect (not needed any more)
			//returning false here stops the default redirect occuring
			return false;
		}
		//need to remove the form session before redirect plugins occur
		$sessionModel->remove();

		//$$$rob used ONLY for redirect plugins
		if (in_array(false, $pluginManager->runPlugins('onLastProcess', $this)))
		{
			// $$$ rob this no longer stops default redirect (not needed any more)
			//returning false here stops the default redirect occuring
			return false;
		}
		return true;
	}

	protected function _doUpload()
	{
		$oUploader = $this->getUploader();
		$oUploader->upload();
		if ($oUploader->moveError)
		{
			return false;
		}
		return true;
	}

	/**
	 * update the data that gets posted via the form and stored by the form
	 * model. Used in elements to modify posted data see fabrikfileupload
	 * @param	string	$key (in key.dot.format to set a recursive array
	 * @param	string	$val value to set to
	 * @param	bool	$update_raw automatically update _raw key as well
	 * @param	bool	$override_ro update data even if element is RO
	 * @return	null
	 */

	function updateFormData($key, $val, $update_raw = false, $override_ro = false)
	{
		if (strstr($key, '.')) {

			$nodes = explode('.', $key);
			$count = count($nodes);
			$pathNodes = $count - 1;
			if ($pathNodes < 0)
			{
				$pathNodes = 0;
			}
			$ns =& $this->_formData;
			for ($i = 0; $i <= $pathNodes; $i ++)
			{
				// If any node along the registry path does not exist, create it
				//if (!isset($this->_formData[$nodes[$i]])) { //this messed up for joined data
				if (!isset($ns[$nodes[$i]]))
				{
					$ns[$nodes[$i]] = array();
				}
				$ns =& $ns[$nodes[$i]];
			}
			$ns = $val;

			$ns =& $this->_fullFormData;
			for ($i = 0; $i <= $pathNodes; $i ++)
			{
				// If any node along the registry path does not exist, create it
				//if (!isset($this->_formData[$nodes[$i]])) { //this messed up for joined data
				if (!isset($ns[$nodes[$i]]))
				{
					$ns[$nodes[$i]] = array();
				}
				$ns =& $ns[$nodes[$i]];
			}
			$ns = $val;

			// $$$ hugh - FIXME - nope, this won't work!  We don't know which path node is the element name.
			if ($update_raw)
			{
				$key .= '_raw';
				$nodes = explode('.', $key);
				$count = count($nodes);
				$pathNodes = $count - 1;
				if ($pathNodes < 0)
				{
					$pathNodes = 0;
				}
				$ns =& $this->_formData;
				for ($i = 0; $i <= $pathNodes; $i ++)
				{
					// If any node along the registry path does not exist, create it
					//if (!isset($this->_formData[$nodes[$i]])) { //this messed up for joined data
					if (!isset($ns[$nodes[$i]]))
					{
						$ns[$nodes[$i]] = array();
					}
					$ns =& $ns[$nodes[$i]];
				}
				$ns = $val;

				$ns = $this->_fullFormData;
				for ($i = 0; $i <= $pathNodes; $i ++)
				{
					// If any node along the registry path does not exist, create it
					//if (!isset($this->_formData[$nodes[$i]])) { //this messed up for joined data
					if (!isset($ns[$nodes[$i]]))
					{
						$ns[$nodes[$i]] = array();
					}
					$ns =& $ns[$nodes[$i]];
				}
				$ns = $val;
			}
		}
		else
		{
			if (isset($this->_formData))
			{
				$this->_formData[$key] = $val;
			}
			// check if set - for case where you have a fileupload element & confirmation plugin - when plugin is trying to update none existant data
			if (isset($this->_fullFormData))
			{
				$this->_fullFormData[$key] = $val;
			}
			/*
			 * Need to allow RO (encrypted) elements to be updated.  Consensus is that
			* we should actually modify the actual encrypted element in the $_REQUEST,
			* but turns out this is a major pain in the butt (see _cryptViewOnlyElements() in the
			* form view for details!).  Main problem is we need to know if it's a join and/or repeat group,
			* which means loading up the element model.  So for now, just going to add the element name to a
			* class array, $this->_pluginUpdatedElements[], which we'll check in _addDefaultDataFromRO()
			* in the table model, or wherever else we need it.
			*/
			/*
			 if (array_key_exists('fabrik_vars', $_REQUEST)
			&& array_key_exists('querystring', $_REQUEST['fabrik_vars'])
			&& array_key_exists($key, $_REQUEST['fabrik_vars']['querystring'])) {
			$crypt = new JSimpleCrypt();
			// turns out it isn't this simple, of course!  see above
			$_REQUEST['fabrik_vars']['querystring'][$key] = $crypt->encrypt($val);
			}
			*/
			// add element name to this array, which will then cause this element to be skipped
			// during the RO data phase of writing the row.  Don't think it really matter what we set it to,
			// might as well be the value.  Note that we need the new $override_ro arg, as some elements
			// use updateFormData() as part of normal operation, which should default to NOT overriding RO.
			if ($override_ro)
			{
				$this->_pluginUpdatedElements[$key] = $val;
			}
			if ($update_raw)
			{
				$key .= '_raw';
				$this->_formData[$key] = $val;
				if (isset($this->_fullFormData))
				{
					$this->_fullFormData[$key] = $val;
				}
				if ($override_ro)
				{
					$this->_pluginUpdatedElements[$key] = $val;
				}
			}
		}
	}

	/*
	 * this will strip the html from the form data according to the
	* filter settings applied from article manager->parameters
	* see here - http://forum.joomla.org/index.php/topic,259690.msg1182219.html#msg1182219
	*/

	function &setFormData()
	{
		if (isset($this->_formData))
		{
			return $this->_formData;
		}
		list($this->dofilter, $this->filter) = FabrikWorker::getContentFilter();

		$this->ajaxPost = JRequest::getBool('fabrik_ajax');
		$aData = JRequest::get('post', JREQUEST_ALLOWRAW);
		array_walk_recursive($aData, array($this, '_clean'));

		//set here so element can call formModel::updateFormData()
		$this->_formData = $aData;
		$this->_fullFormData = $this->_formData;
		$session = JFactory::getSession();
		$session->set('com_fabrik.form.data', $this->_formData);
		return $this->_formData;
	}

	/**
	 * called from setFormData to clean up posted data from either ajax or posted form
	 * used in array_walk_recursive() method
	 * @param	mixed	$item (string or array)
	 * @param	string	$key
	 */

	protected function _clean(&$item, $key)
	{
		if (is_array($item))
		{
			array_walk_recursive($item, array($this, '_clean'));
		}
		else
		{
			if ($this->dofilter)
			{
				$item = preg_replace('/%([0-9A-F]{2})/mei', "chr(hexdec('\\1'))", $item);
				if ($this->ajaxPost)
				{
					$item = rawurldecode($item);
				}
				if ($this->dofilter)
				{
					$item = $this->filter->clean($item);
				}
			} else
			{
				if ($this->ajaxPost)
				{
					$item = rawurldecode($item);
				}
			}
		}
	}

	private function callElementPreprocess()
	{
		$repeatTotals = JRequest::getVar('fabrik_repeat_group', array(0), 'post', 'array');
		$groups = $this->getGroupsHiarachy();
		//curerntly this is just used by calculation elements
		foreach ($groups as $groupModel) {
			$group = $groupModel->getGroup();
			$repeatedGroupCount = JArrayHelper::getValue($repeatTotals, $group->id, 0, 'int');
			$elementModels = $groupModel->getPublishedElements();
			for ($c = 0; $c < $repeatedGroupCount; $c ++) {
				foreach ($elementModels as $elementModel) {
					$elementModel->preProcess($c);
				}
			}
		}
	}

	/**
	 * without this the first groups repeat data was always being saved (as it was posted but hidden
	 * on the form.
	 * @param array $data (ref)
	 */
	protected function removeEmptyNoneJoinedGroupData(&$data)
	{
		$repeats = JArrayHelper::getValue($data, 'fabrik_repeat_group', array());
		$groups = $this->getGroups();
		foreach ($repeats as $groupid => $c) {
			if ($c == 0) {
				$group = $groups[$groupid];
				if ($group->isJoin()) {
					continue;
				}
				$elements = $group->getPublishedElements();
				foreach ($elements as $elementModel) {
					$name = $elementModel->getElement()->name;
					$data[$name] = '';
					$data[$name . '_raw'] = '';
				}
			}
		}
	}

	/**
	 * process the data to the database
	 *
	 * @return null
	 */

	function processToDB()
	{
		$listModel = $this->getListModel();
		$listModel->setBigSelects();
		$item = $listModel->getTable();
		$origTableName = $item->db_table_name;
		$origTableKey = $item->db_primary_key;
		$pluginManager = FabrikWorker::getPluginManager();

		// COPY function should create new records
		if (array_key_exists('Copy', $this->_formData))
		{
			$this->_rowId = '';
			//$$$rob dont pass in $item->db_primary_key directly into safeColName as its then
			//modified permanently by this function
			$k = $item->db_primary_key;
			$k = FabrikString::safeColNameToArrayKey($k);
			$origid = $this->_formData[$k];
			$this->_formData[$k] = '';
			$this->_formData['rowid'] = '';
		}
		/* get an array of the joins to process
		 note this was processJoin() but now preProcessJoin() does the same except
		no longer stores the results - do this after the main form data has been
		saved and u have an id to use
		for the foreign key value*/
		$aPreProcessedJoins = $listModel->preProcessJoin();

		$joinKeys = array();
		//needed for plugins that are run after the data is submitted to the db
		// $$$ rob moved to outside processToDB() as this data is needed regardless of
		// whether we store in the db or not (for email data)
		//$this->_formDataWithTableName = $this->_formData;
		$this->_formData = $listModel->removeTableNameFromSaveData($this->_formData, '___');
		if ($this->_storeMainRow)
		{
			$insertId = $this->submitToDatabase($this->_rowId);
		}
		else
		{
			$insertId = $this->_rowId;
		}
		//set the redirect page to the form's url if making a copy and set the id
		//to the new insertid
		if (array_key_exists('Copy', $this->_formData))
		{
			$u = str_replace('rowid=' . $origid, 'rowid=' . $insertId, JRequest::getVar('HTTP_REFERER', '', 'server'));
			JRequest::setVar('fabrik_referrer', $u);
		}
		$tmpKey = str_replace("`", "", $item->db_primary_key);
		$joinKeys[$tmpKey] = $insertId;
		$tmpKey = str_replace('.', '___', $tmpKey);
		$this->_formData[$tmpKey] 	= $insertId;
		$this->_formData[FabrikString::shortColName($item->db_primary_key)] = $insertId;
		$this->_fullFormData[$tmpKey] = $insertId; //need for things like the redirect plugin
		$this->_fullFormData['rowid'] = $insertId;
		$this->_formData['rowid'] = $insertId;
		$this->_formDataWithTableName['rowid'] = $insertId;
		$_REQUEST[$tmpKey] = $insertId;
		$_POST[$tmpKey] = $insertId;
		$_POST['rowid'] = $insertId;
		$_REQUEST['rowid'] = $insertId;
		// $$$ hugh - pretty sure we need to unset 'usekey' now, as it is not relavent to joined data,
		// and it messing with storeRow of joins
		JRequest::setVar('usekey', '');
		$_POST['usekey'] = '';
		$_REQUEST['usekey'] = '';
		//save join data
		$this->_removeIgnoredData($this->_formData);
		$aDeleteRecordId = '';
		// $$$ hugh - can't do this, as might be repeat element with no data,
		// like checkbox join with no selections, and no other joins on form
		if (!isset($this->_formData['join']))
		{
			$this->_formData['join'] = array();
		}
		foreach ($aPreProcessedJoins as $aPreProcessedJoin)
		{
			if (!array_key_exists('join', $aPreProcessedJoin))
			{
				continue;
			}
			$oJoin = $aPreProcessedJoin['join'];
			if (array_key_exists('Copy', $this->_formData))
			{
				$this->_rowId = '';
				$this->_formData['join'][$oJoin->id][$oJoin->table_join . '___' . $oJoin->table_key] = '';
				$this->_formData['rowid'] = '';
			}
			// $$$ rob 22/02/2011 could be a mutlfileupload with no images selected?
			if (!array_key_exists($oJoin->id, $this->_formData['join']))
			{
				//continue;
			}
			$data = FArrayHelper::getValue($this->_formData['join'], $oJoin->id, array(), 'array');

			$groups = $this->getGroupsHiarachy();
			$repeatTotals = JRequest::getVar('fabrik_repeat_group', array(0), 'post', 'array');
			// 3.0 test on repeatElement param type
			if (is_string($oJoin->params))
			{
				$oJoin->params = json_decode($oJoin->params);
			}
			$joinType = isset($oJoin->params->type) ? $oJoin->params->type : '';
			if ((int) $oJoin->group_id !== 0 && $joinType !== 'repeatElement')
			{
				$joinGroup = $groups[$oJoin->group_id];
				//find the primary key for the join table
				$listModel->getTable()->db_table_name = $oJoin->table_join;
			}
			else
			{
				//repeat element join
				$elementModel = $this->getElement($oJoin->element_id, true);
				// $$$ hugh - covers case where repeat element is read only,
				// so isn't submitting any join data, versus editable element
				// which is empty (like checkbox join), so isn't submitting any data.
				if (!$elementModel->canUse())
				{
					continue;
				}
				$joinGroup = JModel::getInstance('Group', 'FabrikFEModel');

				//need to set the fake group's form and id to that of the current elements form/group
				//$joinGroup->set('_form', $this);
				//$joinGroup->setId($elementModel->getGroup()->getId());

				$joinGroup->getGroup()->id = -1;
				$joinGroup->getGroup()->is_join = 1;

				//set join groups repeat to that of the elements options
				if ($elementModel->isJoin())
				{
					$joinGroup->getParams()->set('repeat_group_button', 1);
					//set repeat count
					if ($elementModel->getGroup()->isJoin())
					{
						//repeat element in a repeat group :S
						$groupJoin = $elementModel->getGroup()->getJoinModel();
						$dataPks = JArrayHelper::getValue($data, $oJoin->table_join . '___id', array());
						for ($r = 0; $r < count($dataPks); $r ++)
						{
							$repeatTotals['el' . $elementModel->getId()][$r] = count($dataPks[$r]);
						}
					} else {
						$repeatTotals[$oJoin->group_id] = $elementModel->getJoinRepeatCount($data, $oJoin);
					}
				}
				else
				{
					// "Not a repeat element (el id = $oJoin->element_id)<br>";
				}
				//copy the repeating element into the join group
				$idElementModel = $pluginManager->getPlugIn('internalid', 'element');
				$idElementModel->getElement()->name = 'id';
				$idElementModel->getElement()->group_id = $elementModel->getGroup()->getGroup()->id;
				$idElementModel->_group = $elementModel->getGroup();
				$idElementModel->_group = $elementModel->_group;
				$idElementModel->_aFullNames['id1_1__1_'] = $oJoin->table_join . '___id';

				$parentElement = $pluginManager->getPlugIn('field', 'element');
				$parentElement->getElement()->name = 'parent_id';
				$parentElement->getElement()->group_id = $elementModel->getGroup()->getGroup()->id;
				$parentElement->_group = $elementModel->getGroup();
				$parentElement->_group = $elementModel->_group;
				$parentElement->_aFullNames['parent_id1_1__1_'] = $oJoin->table_join . '___parent_id';

				$joinGroup->publishedElements = array();
				$joinGroup->publishedElements[''] = array($elementModel, $idElementModel, $parentElement);
				$data[$oJoin->table_join . '___' . $oJoin->table_join_key]  = JArrayHelper::getValue($repeatTotals, $oJoin->group_id, 0) === 0 ? array() : array_fill(0, $repeatTotals[$oJoin->group_id], $insertId);
				$this->groups[] = $joinGroup;

				$listModel->getTable()->db_table_name = $oJoin->table_join;
			}

			$joinGroupTable = $joinGroup->getGroup();

			// $$$ rob - erm is $fields needed?
			$fields = $listModel->getDBFields($listModel->getTable()->db_table_name);
			$aKey = $listModel->getPrimaryKeyAndExtra();
			if (!$aKey) {
				// $$$ hugh - most likely a view, so lets not try and store it
				continue;
			}
			$aKey = $aKey[0];
			$listModel->getTable()->db_primary_key = $aKey['colname'];
			$joinDb = $listModel->getDb();

			//back on track
			if (is_array($data) && array_key_exists($oJoin->table_join . '___' . $oJoin->table_join_key, $data))
			{
				//$$$rob get the join tables ful primary key
				$joinDb->setQuery('DESCRIBE ' . $oJoin->table_join);
				$oJoinPk = $oJoin->table_join . '___';
				$cols = $joinDb->loadObjectList();
				foreach ($cols as $col)
				{
					if ($col->Key == 'PRI')
					{
						$oJoinPk .= $col->Field;
						break;
					}
				}
				$fullforeginKey = $oJoin->table_join . '___' . $oJoin->table_join_key;

				$paramKey = $listModel->getTable()->db_table_name . '___params';
				$repeatParams = JArrayHelper::getValue($data, $paramKey, array());

				if ($joinGroup->canRepeat())
				{
					//find out how many repeated groups were entered

					$repeatedGroupCount = JArrayHelper::getValue($repeatTotals, $oJoin->group_id, 0, 'int');
					$elementModels = $joinGroup->getPublishedElements();

					$aUpdatedRecordIds = array();
					$joinCnn = $listModel->getConnection();
					$joinDb = $joinCnn->getDb();

					for ($c = 0; $c < $repeatedGroupCount; $c ++)
					{
						//get the data for each group and record it seperately
						$repData = array();
						foreach ($elementModels as $elementModel)
						{
							$element = $elementModel->getElement();
							$n = $elementModel->getFullName(false, true, false);
							$v = (is_array($data[$n]) && array_key_exists($c, $data[$n])) ? $data[$n][$c] : '';
							$repData[$element->name] = $v;
							$n_raw = $n . '_raw';
							// $$$ rob 11/04/2012 - repeat elements don't have raw values so use the value as the default raw value
							$defaultRaw = $joinType == 'repeatElement' ? $v : '';
							$v_raw = (is_array($data[$n_raw]) && array_key_exists($c, $data[$n_raw])) ? $data[$n_raw][$c] : $defaultRaw;
							$repData[$element->name . '_raw'] = $v_raw;

							//store any params set in the individual plug-in (see fabrikfileupload::processUpload()->crop()
							if ($elementModel->isJoin())
							{
								$repData['params'] = JArrayHelper::getValue($repeatParams, $c);
							}
						}
						// $$$ rob didn't work for 2nd joined data set
						//$repData[$oJoin->table_join_key] = $insertId;
						unset($repData[$oJoin->table_join_key . '_raw']);
						$repData[$oJoin->table_join_key] = JArrayHelper::getValue($joinKeys, $oJoin->join_from_table . '.' . $oJoin->table_key, $insertId);
						// $$$ rob test for issue with importing joined csv data
						if (is_array($repData[$oJoin->table_join_key]))
						{
							$repData[$oJoin->table_join_key] = $repData[$oJoin->table_join_key][$c];
						}

						//find the primary key for the join table

						$item->db_table_name = $oJoin->table_join;
						// $$$ rob - erm is $fields needed -perhaps just pass $item->db_table_name into getPrimaryKeyAndExtra?
						$fields = $listModel->getDBFields($item->db_table_name);
						$aKey = $listModel->getPrimaryKeyAndExtra();
						$aKey = $aKey[0];
						$item->db_primary_key = $aKey['colname'];
						$joinRowId = $repData[$item->db_primary_key];

						$aDeleteRecordId = $joinDb->Quote($repData[$oJoin->table_join_key]);
						//$$$ hugh - need to give it the table name!!
						// $$$ rob no no no this is not the issue, on SOME setups $item is NOT a reference to $listModel->_table - this is where the issue is
						// not passing in the correct table name - see notes line 720 for explaination
						// $listModel->storeRow($repData, $joinRowId, true, $item->db_table_name);
						$listModel->storeRow($repData, $joinRowId, true, $joinGroupTable);
						if ((int) $joinRowId === 0)
						{
							$joinRowId = $listModel->lastInsertId;
							// $$$ hugh - need to set PK element value for things like email plugin
							$this->_formData['join'][$oJoin->id][$oJoinPk][$c] = $joinRowId;
							$this->_formDataWithTableName['join'][$oJoin->id][$oJoinPk][$c] = $joinRowId;
							$this->_fullFormData['join'][$oJoin->id][$oJoinPk][$c] = $joinRowId;
							$this->_formData['join'][$oJoin->id][$oJoinPk . '_raw'][$c] = $joinRowId;
							$this->_formDataWithTableName['join'][$oJoin->id][$oJoinPk . '_raw'][$c] = $joinRowId;
						}
						$aUpdatedRecordIds[]= $joinRowId;

						$tmpKey = $oJoin->table_join . '.' . $oJoin->table_key;
						$joinKeys[$tmpKey] = $listModel->lastInsertId;
					}

					$query = $joinDb->getQuery(true);
					if ($repeatedGroupCount === 0)
					{
						//all repeat group data was removed
						$query->delete($oJoin->table_join)->where("$oJoin->table_join_key = $insertId");
					}
					else
					{
						//remove any joins that have been deleted with the groups "delete" button
						if (!$data)
						{
							$query->delete($oJoin->table_join)->where("$oJoin->table_join_key = $aDeleteRecordId");
						}
						else
						{
							$query->delete($oJoin->table_join)->where("!($item->db_primary_key IN (" . implode(',', $aUpdatedRecordIds) . ")) AND ($oJoin->table_join_key = $aDeleteRecordId)");
						}
					}
					$joinDb->setQuery($query);
					$joinDb->query();
				}
				else
				{
					// $$$ hugh - trying to get one-to-one joins working where parent.fk = child.pk (ie where parent points to child)
					// So ... if we have that situation, what we will see next is
					// if (($fullforeginKey != $oJoinPk || (int) $data['rowid'] === 0) && ($fullforeginKey != "{$oJoin->table_join}___{$oJoin->table_key}" || $oJoin->table_key === $oJoin->table_join_key)) {}
					// which we need NOT to be true, otherwise (as per Rob's comment) we'll actually be overwriting the PK.
					// Then, after that we are going to see ...
					// if ($fullforeginKey == $oJoinPk) {}
					// which needs to be true in order for the code to go back and write the new joined rows PK
					// into the parenjt's FK element.
					// So ... although it doesn't really make sense, in the one-to-one, parent.fk = child.pk scenario,
					// we need $fullforeginKey to be the same as $oJoinPk.  So we need to work out if the FK is on parent or child ...
					// Which I think means testing to see if the $oJoinPk == $oJoin->table_join + $oJoin->table_join_key.
					// if it does, then element the user selected on the joined (child) table is NOT the FK.  Which means
					// the FK is actually the element they selected on the main table (parent).  In which case, we need to set
					// $fullforeginKey = $oJoinPk, which although it isn't, will satisfy the following code!!
					if ($oJoinPk == $oJoin->table_join . '___' . $oJoin->table_join_key)
				{
					$fullforeginKey = $oJoinPk;
				}

				// $$$rob test if the joined to table's key (as part of the join) is the same as its primary key
				// if it is then we dont want to overwrite the foreginkey as we will in fact be overwriting the pk

				// $$$ rob - 1) altered now so that this test only returns true if we are editing an existing record

				//2) also test if the foreign key isnt the same as the joins key - hard to explain cos its v confusing but
				//when you had 2 joins with both of them key'd to the main table things went horribly wrong

				//if (($fullforeginKey != $oJoinPk || (int) $data['rowid'] === 0) && $fullforeginKey != "{$oJoin->table_join}___{$oJoin->table_key}") {

				// $$$ rob - 3) hmm (2) was incorrect if your table had a pk called the same as the joined table's fk - eg.
				// tbl, venture pk venture_id, tbl access, fk venture_id
				// $$$ hugh - FIXME - something in this is hosing up when creating new one-to-one record where
				// parent.fk points to child.pk
				// OK, tried but couldn't understand why the rowid==0 test, which seems to make it impossible to do.  Trying without this.
				// Seems to work (with my change above) without the rowid test, for edit/new
				if (($fullforeginKey != $oJoinPk) && ($fullforeginKey != $oJoin->table_join . '___' . $oJoin->table_key || $oJoin->table_key === $oJoin->table_join_key))
				{
					// $$$ hugh - at this point we are assuming that we have a situation where the FK is on the joined table,
					// pointing to PK on main table.  BUT ... we may have a situation where neither of the selected keys are
					// a PK, i.e. two records are joined by some other field.  In which case we do not want to set the FK val!
					// So, we need some logic here to handle that!
					// $$$ hugh - OK, I think this is the test we need to see if neither ends of the join are a PK,
					// and if so, don't modify any data, as we're joining on some other field that isn't the PK of either table.
					if ($oJoin->join_from_table . '.' . $oJoin->table_key == $origTableKey)
					{
						$fkVal = JArrayHelper::getValue($joinKeys, $oJoin->join_from_table . '.' . $oJoin->table_key, $insertId);
						$data[$fullforeginKey] = $fkVal;
						$data[$fullforeginKey . '_raw'] = $fkVal;
					}
				}
				if ($item->db_primary_key == '')
				{
					return JError::raiseWarning(500, JText::_('COM_FABRIK_MUST_SELECT_PRIMARY_KEY'));
				}
				$joinRowId = $data[$item->db_table_name . '___' . $item->db_primary_key];

				$data = $listModel->removeTableNameFromSaveData($data);

				//$$$ hugh - need to give it the table name!!
				// $$$ rob no no no this is not the issue, on SOME setups $item is NOT a reference to $listModel->_table - this is where the issue is
				// not passing in the correct table name - see notes line 720 for explaination
				// $listModel->storeRow($repData, $joinRowId, true, $item->db_table_name);
				$listModel->storeRow($data, $joinRowId, true, $joinGroupTable);

				// $$$ Les: shouldn't we store the row id of the newly stored row back in data?????
				// Copied the following lines from the equivalent code for repeated groups
				// ...removing the $c group counter
				if ($joinRowId == '')
				{
					$joinRowId = $listModel->lastInsertId;
					$this->_formData['join'][$oJoin->id][$oJoinPk] = $joinRowId;
					$this->_formDataWithTableName['join'][$oJoin->id][$oJoinPk] = $joinRowId;
					$this->_fullFormData['join'][$oJoin->id][$oJoinPk] = $joinRowId;
					$this->_formData['join'][$oJoin->id][$oJoinPk . '_raw'] = $joinRowId;
					$this->_formDataWithTableName['join'][$oJoin->id][$oJoinPk . '_raw'] = $joinRowId;
				}

				//$$$rob if the fk was the same as the pk then go back to the main table and
				// update its fk to match the
				// pk of the inserted table

				// $$$ hugh - FIXME another point where things aren't right for one-to-one
				// where parent.fk = child.pk
				if ($fullforeginKey == $oJoinPk)
				{
					$pkVal = $listModel->lastInsertId;
					$fk = $oJoin->table_key;
					$this->_formData[$fk] = $pkVal;
					$this->_formData[$fk . '_raw'] = $pkVal; // because storeRow takes _raw if the key exists, which it does

					//reset the table's values to the main table
					// $$$ rob same issues as above with $item not being a reference to $listModel->_table
					//$item->db_table_name = $origTableName;
					//$item->db_primary_key = $origTableKey;
					$listModel->getTable()->db_table_name = $origTableName;
					$listModel->getTable()->db_primary_key = $origTableKey;
					$listModel->storeRow($this->_formData, $insertId);
					$insertId = $listModel->lastInsertId;
				}
				$tmpKey = $oJoin->table_join . '.' . $oJoin->table_key;
				$joinKeys[$tmpKey] = $listModel->lastInsertId;
				}
			}
			else
			{
				// no join data found so delete all joined records
				$k = $oJoin->join_from_table . '___' . $oJoin->table_key;
				$delPkVal = JArrayHelper::getValue($this->_formData, $k, '');
				if ($delPkVal !== '')
				{
					$query = $joinDb->getQuery(true);
					$query->delete($oJoin->table_join)->where("($oJoin->table_join_key = $delPkVal)");
					$joinDb->setQuery($query);
					$joinDb->query();
				}
			}
		}
		//testing for saving pages/
		JRequest::setVar('rowid', $insertId);
		if (in_array(false, $pluginManager->runPlugins('onBeforeCalculations', $this)))
		{
			return;
		}
		$this->_listModel->doCalculations();
	}

	/**
	 * removes any element which s set to ignore
	 * @param array form data
	 */

	function _removeIgnoredData(&$data)
	{
		$groups = $this->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$groupTable = $groupModel->getGroup();
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();
				$element->label = strip_tags($element->label);
				$params = $elementModel->getParams();

				//check if the data gets inserted on update
				$v = $elementModel->getValue($data);
				//currently only field password elements return true and file uploads when no file selected
				if ($elementModel->ignoreOnUpdate($v))
				{
					$fullName = $elementModel->getFullName(false, true, true);
					unset($data['join'][$groupTable->join_id][$fullName]);
					if (array_key_exists($element->name, $data))
					{
						unset($data[$element->name]);
					}
				}
			}
		}
	}

	/**
	 * saves the form data to the database
	 * @param	int		rowid - if 0 then insert a new row - otherwise update this row id
	 * @return	mixed	insert id (or rowid if updating existing row) if ok , else string error message
	 */

	function submitToDatabase($rowId = '0')
	{
		$this->getGroupsHiarachy();
		$pluginManager = FabrikWorker::getPluginManager();
		/*
		 *check if there is table data that is not posted by the form
		* (ie if no checkboxes were selected)
		*/
		$groups = $this->getGroupsHiarachy();
		$listModel = $this->getListModel();
		$listModel->encrypt = array();
		foreach ($groups as $groupModel)
		{
			$group = $groupModel->getGroup();
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();
				$element->label = strip_tags($element->label);
				$params = $elementModel->getParams();
				$elementModel->getEmptyDataValue($this->_formData);

				//check if the data gets inserted on update
				// $$$hugh @FIXME - at this point we've removed tablename from _formdata keys (in processTodb()),
				// but element getValue() methods assume full name in _formData
				$v = $elementModel->getValue($this->_formData);
				if ($elementModel->ignoreOnUpdate($v))
				{
					//currently only field password elements return true
					$fullName = $elementModel->getFullName(false, true, true);
					if (array_key_exists('join', $this->_formData))
					{
						unset($this->_formData['join'][$group->join_id][$fullName]);
					}
					if (array_key_exists($element->name, $this->_formData))
					{
						unset($this->_formData[$element->name]);
					}
				}
				if ($elementModel->encryptMe())
				{
					$listModel->encrypt[] = $element->name;
				}
				if ($groupModel->isJoin())
				{
					$tmpdata = $this->_formData['join'][$group->join_id];
					//maybe no joined data added so test before doing onstorerow
					if (is_array($tmpdata))
					{
						$elementModel->onStoreRow($tmpdata);
					}
				}
				else
				{
					$elementModel->onStoreRow($this->_formData);
				}
			}
		}
		$listModel = $this->getListModel();
		$listModel->setFormModel($this);
		$item = $listModel->getTable();
		$listModel->storeRow($this->_formData, $rowId);

		$usekey = JRequest::getVar('usekey', '');
		if (!empty($usekey))
		{
			return $listModel->lastInsertId;
		}
		else
		{
			return ($rowId == 0) ? $listModel->lastInsertId : $rowId;
		}
	}

	/**
	 * @depreciated as of fabrik 3.0 - use getListModel instead
	 */

	function getTableModel()
	{
		return $this->getListModel();
	}

	/**
	 * get the form's table model
	 * (was getTable but that clashed with J1.5 func)
	 *
	 * @return	object	fabrik list model
	 */

	function getListModel()
	{
		if (!isset($this->_listModel))
		{
			$this->_listModel = JModel::getInstance('List', 'FabrikFEModel');
			$item = $this->getForm();
			$this->_listModel->loadFromFormId($item->id);
			$this->_listModel->setFormModel($this);
		}
		return $this->_listModel;
	}

	/**
	 * get the class names for each of the validation rules
	 * @deprecated (was only used in element label)
	 * @return	array	(validaionruleid => classname )
	 */

	function loadValidationRuleClasses()
	{
		if (is_null($this->_validationRuleClasses))
		{
			$validationRules = FabrikWorker::getPluginManager()->getPlugInGroup('validationrule');
			$classes = array();
			foreach ($validationRules as $rule)
			{
				$classes[$rule->name] = $rule->name;
			}
			$this->_validationRuleClasses = $classes;
		}
		return $this->_validationRuleClasses;
	}

	/**
	 * 	$$$ hugh - add in any encrypted stuff, in case we fail validation ...
	 * otherwise it won't be in $data when we rebuild the page.
	 * Need to do it here, so _raw fields get added in the next chunk 'o' code.
	 * @param	array	posted form data passed by reference
	 * @return	null
	 */

	function addEncrytedVarsToArray(&$post)
	{
		if (array_key_exists('fabrik_vars', $_REQUEST) && array_key_exists('querystring', $_REQUEST['fabrik_vars']))
		{
			$groups = $this->getGroupsHiarachy();
			$gkeys = array_keys($groups);
			jimport('joomla.utilities.simplecrypt');
			$crypt = new JSimpleCrypt();
			$w = new FabrikWorker();
			foreach ($gkeys as $g)
			{
				$groupModel = $groups[$g];
				$elementModels = $groupModel->getPublishedElements();
				foreach ($elementModels as $elementModel)
				{
					$element = $elementModel->getElement();
					foreach ($_REQUEST['fabrik_vars']['querystring'] as $key => $encrypted)
					{
						if ($elementModel->getFullName(false, true, false) == $key)
						{
							// 	$$$ rob - dont test for !canUse() as confirmation plugin dynamically sets this
							//if ($elementModel->canView())
							// $$$ hugh - testing adding non-viewable, non-editable elements to encrypted vars
							if (true)
							{
								//if (!$elementModel->canUse() && $elementModel->canView()) {
								if (is_array($encrypted))
								{
									//repeat groups no join
									$v = array();
									foreach ($encrypted as $e)
									{
										//$$$ rob urldecode when posting from ajax form
										$e = urldecode($e);
										$e = empty($e) ? '' : $crypt->decrypt($e);
										$e = FabrikWorker::JSONtoData($e);
										$v[] = $w->parseMessageForPlaceHolder($e, $post);
									}
								}
								else
								{
									// $$$ rob urldecode when posting from ajax form
									$encrypted = urldecode($encrypted);
									$v = empty($encrypted) ? '' : $crypt->decrypt($encrypted);
									// $$$ hugh - things like elementlist elements (radios, etc) seem to use
									// their JSON data for encrypted read only vals, need to decode.
									$v = FabrikWorker::JSONtoData($v);
									$v = $w->parseMessageForPlaceHolder($v, $post);
								}
								$elementModel->_group = $groupModel;
								$elementModel->setValuesFromEncryt($post, $key, $v);
								// $$ rob set both normal and rawvalues to encrypted - otherwise validate method doenst
								//pick up decrypted value
								$elementModel->setValuesFromEncryt($post, $key . '_raw', $v);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * when submitting data copy values to _raw equivalent
	 * @param	array	$post data (passed by ref)
	 * @return	null
	 */

	function copyToRaw(&$post)
	{
		$groups = $this->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$elName2 = $elementModel->getFullName(false, true, false);
				if ($groupModel->isJoin())
				{
					$joinModel = $groupModel->getJoinModel();
					if (array_key_exists('join', $post) && array_key_exists($joinModel->_id, $post['join']))
					{
						if ($groupModel->canRepeat())
						{
							$v = JArrayHelper::getValue($post['join'][$joinModel->_id], $elName2, array());
						}
						else
						{
							$v = JArrayHelper::getValue($post['join'][$joinModel->_id], $elName2, '');
						}
						$joindata[$joinModel->_id][$elName2] = $v;
						$joindata[$joinModel->_id][$elName2 . '_raw'] = $v;
						$post['join'][$joinModel->_id][$elName2] = $v;
						$post['join'][$joinModel->_id][$elName2 . '_raw'] = $v;
						$_POST['join'][$joinModel->_id][$elName2] = $v;
						$_POST['join'][$joinModel->_id][$elName2 . '_raw'] = $v;
					}
				}
				else
				{
					if (!array_key_exists($elName2 . '_raw', $post))
					{
						JRequest::setVar($elName2 . '_raw', @$post[$elName2]); //post required getValue() later on
						$post[$elName2 . '_raw'] = @$post[$elName2];
					}
				}
			}
		}
	}

	public function failedValidation()
	{
		return $this->hasErrors();
	}

	/**
	 * validate the form
	 * modifies post data to include validation replace data
	 * @return	bool	true if form validated ok
	 */

	function validate()
	{
		if ((bool) JRequest::getBool('fabrik_ignorevalidation', false) === true)
		{
			//put in when saving page of form
			return true;
		}
		require_once(COM_FABRIK_FRONTEND . '/helpers/uploader.php');
		$pluginManager = JModel::getInstance('Pluginmanager', 'FabrikFEModel');
		$validationRules = $pluginManager->getPlugInGroup('validationrule');

		$post = $this->setFormData();
		//contains any data modified by the validations
		$this->_modifiedValidationData = array();
		$w = new FabrikWorker();
		//$joindata = array();
		$ok = true;

		// $$$ rob 01/07/2011 fileupload needs to examine records previous data for validations on edting records
		$this->setOrigData();

		// $$$ rob copy before addEncrytedVarsToArray as well as after
		// so that any placedholders(.._raw) contained in the encrypted vars are correctly replaced
		$this->copyToRaw($post);

		// $$$ rob for PHP 5.2.1 (and potential up to before 5.2.6) $post is not fully associated with _formData -
		// so the above copToRaw does not update $this->_formData.
		// $$$ hugh - had to add the &, otherwise replace validations weren't work, as modifying
		// $post wasn't modifying $this->_formData.  Which is weird, as I thought all array assignments
		// were by reference?
		// $$$ hugh - FIXME - wait ... what ... hang on ... we assign $this->_formData in $this->setFormData(),
		// which we assigned to $post a few ines up there ^^.  Why are we now assigning $post back to $this->_formData??
		$this->_formData =& $post;

		// $$$ hugh - add in any encrypted stuff, in case we fail validation ...
		// otherwise it won't be in $data when we rebuild the page.
		// Need to do it here, so _raw fields get added in the next chunk 'o' code.
		$this->addEncrytedVarsToArray($post);

		// $$$ hugh - moved this to after addEncryptedVarsToArray(), so read only data is
		// available to things like calc's running in preProcess phase.
		$this->callElementPreprocess();

		//add in raw fields - the data is already in raw format so just copy the values
		$this->copyToRaw($post);

		$groups = $this->getGroupsHiarachy();
		$repeatTotals = JRequest::getVar('fabrik_repeat_group', array(0), 'request', 'array');
		$ajaxPost = JRequest::getBool('fabrik_ajax');

		$joindata = isset($post['join']) ? $post['join'] : array();

		foreach ($groups as $groupModel)
		{
			$groupCounter = $groupModel->getGroup()->id;
			$elementModels = $groupModel->getPublishedElements();
			$elDbVals = array();
			if ($groupModel->isJoin())
			{
				$joinModel = $groupModel->getJoinModel();
			}
			foreach ($elementModels as $elementModel)
			{
				// if the user can't view or edit the element, then don't validate it. Otherwise user sees failed validation but no idication of what failed
				if (!$elementModel->canUse() && !$elementModel->canView())
				{
					continue;
				}
				$elDbVals = array();
				$element = $elementModel->getElement();

				$validation_rules = $elementModel->getValidations();
				// $$ rob incorrect for ajax validation on joined elements
				//$elName = $elementModel->getFullName(true, true, false);
				$elName = JRequest::getBool('fabrik_ajax') ? $elementModel->getHTMLId(0) : $elementModel->getFullName(true, true, false);
				$this->_arErrors[$elName] = array();
				$elName2 = $elementModel->getFullName(false, true, false);
				// $$$rob fix notice on validation of multipage forms
				if (!array_key_exists($groupCounter, $repeatTotals))
				{
					$repeatTotals[$groupCounter] = 1;
				}
				for ($c = 0; $c < $repeatTotals[$groupCounter]; $c++)
				{
					$this->_arErrors[$elName][$c] = array();
					// $$$ rob $this->_formData was $_POST, but failed to get anything for calculation elements
					//in php 5.2.1
					$form_data = $elementModel->getValue($this->_formData, $c, array('runplugins' => 0, 'use_default' => false));
					if (get_magic_quotes_gpc())
					{
						if (is_array($form_data))
						{
							foreach ($form_data as &$d)
							{
								if (is_string($d))
								{
									$d = stripslashes($d);
									if ($ajaxPost)
									{
										$d = rawurldecode($d);
									}
								}
							}
						}
						else
						{
							$form_data = stripslashes($form_data);
							if ($ajaxPost)
							{
								$form_data = rawurldecode($form_data);
							}
						}
					}
					//internal element plugin validations
					if (!$elementModel->validate(@$form_data, $c))
					{
						$ok = false;
						$this->_arErrors[$elName][$c][] = $elementModel->getValidationErr();
					}

					// $$$ rob 11/04/2012 was stopping multiselect/chx dbjoin elements from saving in normal group.
					//if ($groupModel->canRepeat() || $elementModel->isJoin())
					if ($groupModel->canRepeat())
					{
						// $$$ rob for repeat groups no join setting to array() menat that $_POST only contained the last repeat group data
						//$elDbVals = array();
						$elDbVals[$c] = $elementModel->toDbVal($form_data, $c);
					}
					else
					{
						$elDbVals = $elementModel->toDbVal($form_data, $c);
					}
					//validations plugins attached to elemenets
					$pluginc = 0;
					if (!$elementModel->mustValidate())
					{
						continue;
					}
					foreach ($validation_rules as $plugin)
					{
						$plugin->_formModel = $this;
						$plugin->_listModel = $this->getListModel();
						if ($plugin->shouldValidate($form_data, $pluginc))
						{
							if (!$plugin->validate($form_data, $elementModel, $pluginc, $c))
							{
								$this->_arErrors[$elName][$c][] = $w->parseMessageForPlaceHolder($plugin->getMessage($pluginc));
								$ok = false;
							}
							if (method_exists($plugin, 'replace'))
							{
								if ($groupModel->canRepeat())
								{
									$elDbVals[$c] = $elementModel->toDbVal($form_data, $c);
									$testreplace = $plugin->replace($elDbVals[$c], $elementModel, $pluginc, $c);
									if ($testreplace != $elDbVals[$c])
									{
										$elDbVals[$c] = $testreplace;
										$this->_modifiedValidationData[$elName][$c] = $testreplace;
										$joindata[$joinModel->_id][$elName2 . '_raw'][$c] = $testreplace;
									}
								}
								else
								{
									$testreplace = $plugin->replace($elDbVals, $elementModel, $pluginc, $c);
									if ($testreplace != $elDbVals)
									{
										$elDbVals = $testreplace;
										$this->_modifiedValidationData[$elName] = $testreplace;
										JRequest::setVar($elName . '_raw', $elDbVals);
										$post[$elName . '_raw'] = $elDbVals;
									}
								}
							}
						}
						$pluginc ++;
					}
				}
				if ($groupModel->isJoin())
				{
					$joindata[$joinModel->_id][$elName2] = $elDbVals;
				}
				else
				{
					if ($elementModel->isJoin())
					{
						$joinModel = $elementModel->getJoinModel();
						$join = $joinModel->getJoin();
						$joindata[$join->id][$elName2] = $elDbVals;
					}
					else
					{
						JRequest::setVar($elName, $elDbVals);
						$post[$elName] = $elDbVals;
					}
				}
				//unset the deafults or the orig submitted form data will be used (see date plugin mysql vs form format)
				$elementModel->defaults = null;
			}
		}

		//insert join data into request array
		$post['join'] = $joindata;
		JRequest::setVar('join', $joindata, 'post');
		if (!empty($this->_arErrors))
		{
			FabrikWorker::getPluginManager()->runPlugins('onError', $this);
		}
		FabrikHelperHTML::debug($this->_arErrors, 'form:errors');
		$this->setErrors($this->_arErrors);
		return $ok;
	}

	/**
	 * get form validation errors - if empty test session for errors
	 * @return	array	errors
	 */

	public function getErrors()
	{
		$context = 'com_fabrik.form.' . $this->getId() . '.';
		$session = JFactory::getSession();
		//store errors in local array as clearErrors() removes $this->_arErrors
		$errors = array();
		if (empty($this->_arErrors))
		{
			if (isset($_SERVER['HTTP_REFERER']))
			{
				$errors = $session->get($context . 'errors', array());
			}
		}
		else
		{
			$errors = $this->_arErrors;
		}
		$this->clearErrors();
		$this->_arErrors = $errors;
		return $this->_arErrors;
	}

	/**
	 * clear form validation errors
	 */

	public function clearErrors()
	{
		$session = JFactory::getSession();
		$context = 'com_fabrik.form.' . $this->getId() . '.';
		$this->_arErrors = array();
		$session->clear($context . 'errors');
		// $$$ rob this was commented out, but putting back in to test issue that if we have ajax validations on
		// and a field is validated, then we dont submit the form, and go back to add the form, the previously validated
		// values are shown in the form.
		$session->set($context . 'session.on', false);
	}

	/**
	 * set form validation errors in session
	 * @param array errors
	 */

	public function setErrors($errors)
	{
		$session = JFactory::getSession();
		$context = 'com_fabrik.form.' . $this->getId() . '.';
		$session->set($context . 'errors', $errors);
		$session->set($context . 'session.on', true);
	}

	public function getJsonErrors()
	{
		$data = array('modified' => $this->_modifiedValidationData, 'errors' => $this->_arErrors);
		return json_encode($data);;
	}

	/**
	 * should the form do a spoof check
	 * @return	bool
	 */

	public function spoofCheck()
	{
		$fbConfig = JComponentHelper::getParams('com_fabrik');
		return $this->getParams()->get('spoof_check', $fbConfig->get('spoofcheck_on_formsubmission', true));
	}

	/**
	 * get an instance of the uploader object
	 *
	 * @return	object	uploader
	 */

	function &getUploader()
	{
		if (is_null($this->uploader))
		{
			$this->uploader = new uploader($this);
		}
		return $this->uploader;
	}

	/**
	 * get the forms table name
	 *
	 * @return	string	table name
	 */

	function getTableName()
	{
		$this->getListModel();
		return $this->getListModel()->getTable()->db_table_name;
	}

	/**
	 * get the form row
	 *
	 * @return object form row
	 */

	public function getTable($name = '', $prefix = 'Table', $options = array())
	{
		if (is_null($this->_form))
		{
			$this->_form = parent::getTable('Form', 'FabrikTable');
		}
		$id = $this->getId();
		if ($this->_form->id != $id)
		{
			$this->_form->load($id);
		}
		return $this->_form;
	}

	/**
	 * depreicated
	 */
	function createFormGroup($groupId)
	{

	}

	/**
	 * depreicated
	 */
	function _getFromGroupsStr()
	{
	}

	/**
	 * depreicated
	 */

	function _loadFromGroupsStr()
	{
	}

	/**
	 * sets the variable of each of the form's group's elements to the value
	 * specified
	 * @param	string	variable name
	 * @param	string	variable value
	 * @return	bool	false if update error occurs
	 */


	function setElementVars($varName, $varVal)
	{
		if ($this->_elements == null)
		{
			$this->getFormGroups();
		}
		foreach ($this->_elements as $el)
		{
			$element = FabTable::getInstance('Element', 'FabrikTable');
			$element->load($el->id);
			if (!$element->set($varName, $varVal))
			{
				return false;
			}
			$element->store();
		}
		return true;
	}

	/**
	 * determines if the form can be published
	 * @return	bool	true if publish dates are ok
	 */

	function canPublish()
	{
		$db = FabrikWorker::getDbo();
		$form = $this->getForm();
		$nullDate = (method_exists($db, 'getNullDate')) ? $db->getNullDate() : $this->getNullDate();
		$publishup = JFactory::getDate($form->publish_up)->toUnix();
		$publishdown = JFactory::getDate($form->publish_down)->toUnix();
		$now = JFactory::getDate()->toUnix();
		if ($form->published == '1')
		{
			if ($now >= $publishup || $form->publish_up == '' || $form->publish_up == $nullDate)
			{
				if ($now <= $publishdown || $form->publish_down == '' || $form->publish_down == $nullDate)
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * create a drop down list of all the elements in the form
	 * @param	string	drop down name
	 * @param	string	current value
	 * @param	bool	add elements that are unpublished
	 * @param	bool	concat table name and el name with '___' (true) or "." (false)
	 * @param	bool	include raw labels default = true
	 * @return	string	html list
	 */

	function getElementList($name = 'order_by', $default = '', $excludeUnpublished = false, $useStep = false, $incRaw = true )
	{
		$aEls = array();
		$aEls = $this->getElementOptions($useStep, 'name', false, $incRaw);
		$aEls[] = JHTML::_('select.option', '', '-');
		asort($aEls);
		return JHTML::_('select.genericlist', $aEls, $name, 'class="inputbox" size="1" ', 'value', 'text', $default);
	}

	/**
	 * get an array of the form's element's ids
	 * @param	array	classNames to ignore e.g. array('FabrikModelFabrikCascadingdropdown')
	 * @return	array	ints ids
	 */

	function getElementIds($ignore = array())
	{
		$aEls = array();
		$groups = $this->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$class = get_class($elementModel);
				if (!in_array($class, $ignore))
				{
					$aEls[] = (int) $elementModel->getElement()->id;
				}
			}
		}
		return $aEls;
	}

	/**
	 * creates options array to be then used by getElementList to create a drop down of elements in the form
	 * sperated as elements need to collate this options from muliple forms
	 * @param	bool	concat table name and el name with '___' (true) or "." (false)
	 * @param	string	name of key to use (default "name")
	 * @param	bool	only show those elements shown in table summary
	 * @param	bool	include raw labels in list (default = false) Only works if $key = name
	 * @param	array	list of plugin names that should be included in the list - if empty include all plugin types
	 * @return	array	html options
	 */

	function getElementOptions($useStep = false, $key = 'name', $show_in_list_summary = false, $incRaw = false, $filter = array())
	{
		$groups = $this->getGroupsHiarachy();
		$aEls = array();
		$step = $useStep ? '___' : '.';
		$gkeys = array_keys($groups);
		foreach ($gkeys as $gid)
		{
			$groupModel = $groups[$gid];
			$elementModels = $groupModel->getMyElements();
			$prefix = $groupModel->isJoin() ? $groupModel->getJoinModel()->getJoin()->table_join . '.' : '';
			foreach ($elementModels as $elementModel)
			{
				$el = $elementModel->getElement();
				if (!empty($filter) && !in_array($el->plugin, $filter))
				{
					continue;
				}
				//$$$ testing
				if ($show_in_list_summary == true && $el->show_in_list_summary != 1)
				{
					continue;
				}
				$val = $el->$key;
				$label = strip_tags($prefix . $el->label);
				if ($key != 'id')
				{
					$val = $elementModel->getFullName(false, $useStep, false);
					if ($this->_addDbQuote)
					{
						$val = FabrikString::safeColName($val);
					}
					if ($incRaw && is_a($elementModel, 'plgFabrik_ElementDatabasejoin'))
					{
						// FIXME - next line had been commented out, causing undefined warning for $rawval
						// on following line.  Not sure if getrawColumn is right thing to use here tho,
						// like, it adds filed quotes, not sure if we need them.
						if ($elementModel->getElement()->published != 0)
						{
							$rawval = $elementModel->getRawColumn($useStep);
							if (!$this->_addDbQuote)
							{
								$rawval = str_replace('`', '', $rawval);
							}
							$aEls[$label . '(raw)'] = JHTML::_('select.option', $rawval, $label . '(raw)');
						}
					}
				}
				$aEls[] = JHTML::_('select.option', $val, $label);
			}
		}
		asort($aEls);
		return $aEls;
	}

	/**
	 * called via ajax nav
	 * @param int $dir (1 - move foward, 0 move back)
	 * @return bol new row id loaded.
	 */

	function paginateRowId($dir)
	{
		$db = FabrikWorker::getDbo();
		$c = $dir == 1 ? '>=' : '<=';
		$limit = $dir == 1 ? 'LIMIT 2' : '';
		$intLimit = $dir == 1 ? 2 : 0;
		$listModel = $this->getListModel();
		$order = $listModel->_buildQueryOrder();
		$item = $listModel->getTable();
		$rowid = JRequest::getInt('rowid');
		$query = $db->getQuery(true);
		$query->select($item->db_primary_key . ' AS ' . FabrikString::safeColNameToArrayKey($item->db_primary_key))
		->from($item->db_table_name)
		->where($item->db_primary_key . ' ' . $c . ' ' . $rowid);
		$query = $listModel->_buildQueryOrder($query);
		$db->setQuery($query, 0, $intLimit);
		$ids = $db->loadColumn();
		if ($dir == 1)
		{
			if (count($ids) >= 2)
			{
				JRequest::setVar('rowid', $ids[$dir]);
				return true;
			}
			else
			{
				return false;
			}
		}
		if (count($ids) - 2 >= 0)
		{
			JRequest::setVar('rowid', $ids[count($ids)-2]);
			return true;
		}
		return false;
	}

	/**
	 * get the current records row id
	 *  setting a rowid of -1 will load in the current users record (used in
	 *  conjunction wth usekey variable
	 *
	 *  setting a rowid of -2 will load in the last created record
	 *
	 * @return	string	rowid
	 */

	function getRowId()
	{
		if (isset($this->_rowId))
		{
			return $this->_rowId;
		}
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$user = JFactory::getUser();
		// $$$rob if we show a form module when in a fabrik form component view - we shouldn't use
		// the request rowid for the mambot as that value is destinded for the component
		if ($this->isMambot && JRequest::getCmd('option') == 'com_fabrik')
		{
			$this->_rowId = $usersConfig->get('rowid');
		}
		else
		{
			$this->_rowId = FabrikWorker::getMenuOrRequestVar('rowid', $usersConfig->get('rowid'), $this->isMambot);
			if ($this->_rowId == -2)
			{
				// if the default was set to -2 (load last row) then a pagination form plugin's row id should override menu settings
				$this->_rowId = FabrikWorker::getMenuOrRequestVar('rowid', $usersConfig->get('rowid'), $this->isMambot, 'request');
			}
		}
		if ($this->getListModel()->getParams()->get('sef-slug') !== '')
		{
			$this->_rowId = explode(':', $this->_rowId);
			$this->_rowId = array_shift($this->_rowId);
		}
		// $$$ hugh - for some screwed up reason, when using SEF, rowid=-1 ends up as :1
		// $$$ rob === compare as otherwise 0 == ":1" which menat that the users record was  loaded
		if ((string)$this->_rowId === ':1')
		{
			$this->_rowId = '-1';
		}
		// set rowid to -1 to load in the current users record
		switch ($this->_rowId)
		{
			case '-1':
				$this->_rowId = $user->get('id');
				break;
			case '-2':
				//set rowid to -2 to load in the last recorded record
				$this->_rowId = $this->getMaxRowId();
				break;
		}
		FabrikWorker::getPluginManager()->runPlugins('onSetRowId', $this);
		return $this->_rowId;
	}

	/**
	 * collates data to write out the form
	 * @return	mixed . bool
	 */

	function render()
	{
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark('formmodel render: start') : null;
		// $$$rob required in paolo's site when rendering modules with ajax option turned on
		$this->_listModel = null;
		@set_time_limit(300);
		$this->_rowId = $this->getRowId();
		$pluginManager = FabrikWorker::getPluginManager();
		$res = $pluginManager->runPlugins('onBeforeLoad', $this);
		if (in_array(false, $res))
		{
			return false;
		}
		JDEBUG ? $profiler->mark('formmodel render: getData start') : null;
		$data = $this->getData();
		JDEBUG ? $profiler->mark('formmodel render: getData end') : null;
		$res = $pluginManager->runPlugins('onLoad', $this);
		if (in_array(false, $res))
		{
			return false;
		}
		$this->_reduceDataForXRepeatedJoins();
		JDEBUG ? $profiler->mark('formmodel render end') : null;
		// $$$ rob return res - if its false the the form will not load
		return $res;
	}

	/**
	 * get the max row id - used when requesting rowid=-2 to return the last recorded detailed view
	 * @return	int		max row id
	 */

	protected function getMaxRowId()
	{
		if (!$this->getForm()->record_in_database)
		{
			return $this->_rowId;
		}
		$listModel = $this->getListModel();
		$fabrikDb = $listModel->getDb();
		$item = $listModel->getTable();
		$k = $fabrikDb->nameQuote($item->db_primary_key);
		$fabrikDb->setQuery("SELECT MAX($k) FROM " . FabrikString::safeColName($item->db_table_name) . $listModel->_buildQueryWhere());
		return $fabrikDb->loadResult();
	}

	/**
	 * Does the form contain user errors
	 * @return	bool
	 */
	public function hasErrors()
	{
		$errorsFound = !empty($this->_arErrors);
		//test if its a resumed paged form
		// if so _arErrors will be filled so check all elements had no errors
		$srow = $this->getSessionData();
		$multiPageErrors = false;
		if ($this->saveMultiPage() && $srow->data != '')
		{
			foreach ($this->_arErrors as $err)
			{
				if (!empty($err[0]))
				{
					$multiPageErrors = true;
				}
			}
			if (!$multiPageErrors)
			{
				$errorsFound = false;
			}
		}
		return $errorsFound;
	}

	/**
	 * main method to get the data to insert into the form
	 * @return	array	form's data
	 */

	function getData()
	{
		//if already set return it. If not was causing issues with the juser form plugin
		// when it tried to modify the form->_data info, from within its onLoad method, when sync user option turned on.
		if (isset($this->_data))
		{
			return $this->_data;
		}
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark('formmodel getData: start') : null;
		$this->_data = array();
		$data = array(FArrayHelper::toObject(JRequest::get('request')));
		$form = $this->getForm();

		$aGroups = $this->getGroupsHiarachy();
		JDEBUG ? $profiler->mark('formmodel getData: groups loaded') : null;
		if (!$form->record_in_database)
		{
			FabrikHelperHTML::debug($data, 'form:getData from $_REQUEST');
			$data = JRequest::get('request');
		}
		else
		{
			JDEBUG ? $profiler->mark('formmodel getData: start get list model') : null;
			$listModel = $this->getListModel();
			JDEBUG ? $profiler->mark('formmodel getData: end get list model') : null;
			$fabrikDb = $listModel->getDb();
			JDEBUG ? $profiler->mark('formmodel getData: db created') : null;
			$item = $listModel->getTable();
			JDEBUG ? $profiler->mark('formmodel getData: table row loaded') : null;
			$this->_aJoinObjs = $listModel->getJoins();
			JDEBUG ? $profiler->mark('formmodel getData: joins loaded') : null;

			if ($this->hasErrors())
			{
				// $$$ hugh - if we're a mambot, reload the form session state we saved in
				// process() when it banged out.
				if ($this->isMambot)
				{
					$srow = $this->getSessionData();
					$this->sessionModel->last_page = 0;
					if ($srow->data != '')
					{
						$data = FArrayHelper::toObject(unserialize($srow->data), 'stdClass', false);
						JFilterOutput::objectHTMLSafe( $data);
						$data = array($data);
						FabrikHelperHTML::debug($data, 'form:getData from session (form in Mambot and errors)');
					}
				}
				else
				{
					// $$$ rob - use setFormData rather than JRequest::get()
					//as it applies correct input filtering to data as defined in article manager parameters
					$data = $this->setFormData();
					$data = FArrayHelper::toObject($data, 'stdClass', false);
					//$$$rob ensure "<tags>text</tags>" that are entered into plain text areas are shown correctly
					JFilterOutput::objectHTMLSafe($data);
					$data = array($data);
					FabrikHelperHTML::debug($data, 'form:getData from POST (form not in Mambot and errors)');
				}
			}
			else
			{
				//test if its a resumed paged form
				$srow = $this->getSessionData();
				JDEBUG ? $profiler->mark('formmodel getData: session data loaded') : null;
				if ($this->saveMultiPage() && $srow->data != '')
				{
					$data = array(FArrayHelper::toObject(array_merge(unserialize($srow->data), JArrayHelper::fromObject($data[0]))));
					FabrikHelperHTML::debug($data, 'form:getData from session (form not in Mambot and no errors');
				}
				else
				{
					// only try and get the row data if its an active record
					//use !== 0 as rowid may be alphanumeric
					// $$$ hugh - when 'usekey', rowid can actually be 0 (like if using userid and this is guest access)
					// so go ahead and try and load the row, if it doesn't exist, we'll supress the warning
					$usekey = FabrikWorker::getMenuOrRequestVar('usekey', '', $this->isMambot);
					if (!empty($usekey) || (int) $this->_rowId !== 0 || (!is_numeric($this->_rowId) && $this->_rowId != ''))
					{
						// $$$ hugh - once we have a few join elements, our select statements are
						// getting big enough to hit default select length max in MySQL.
						$listModel->setBigSelects();

						//otherwise lets get the table record
						$sql = $this->_buildQuery();

						$fabrikDb->setQuery($sql);
						FabrikHelperHTML::debug($fabrikDb->getQuery(), 'form:render');
						$rows = $fabrikDb->loadObjectList();
						if (is_null($rows))
						{
							JError::raiseWarning(500, $fabrikDb->getErrorMsg());
						}
						JDEBUG ? $profiler->mark('formmodel getData: rows data loaded') : null;
						//$$$ rob Ack above didnt work for joined data where there would be n rows rerutned frho "this rowid = $this->_rowId  \n";
						if (!empty($rows))
						{
							// only do this if the query returned some rows (it wont if usekey on and userid = 0 for example)
							$data = array();
							foreach ($rows as &$row)
							{
								if (empty($data))
								{
									//if loading in a rowid=-1 set the row id to the actual row id
									$this->_rowId = isset($row->__pk_val) ? $row->__pk_val : $this->_rowId;
								}
								$row = empty($row) ? array() : JArrayHelper::fromObject($row);
								$data[] = FArrayHelper::toObject(array_merge($row, JRequest::get('request')));
							}
						}
						FabrikHelperHTML::debug($data, 'form:getData from querying rowid= '.$this->_rowId.' (form not in Mambot and no errors)');

						// if empty data return and trying to edit a record then show error
						//occurs if user trying to edit a record forbidden by a prefilter rull
						if (empty($data) && $this->_rowId != '')
						{
							// $$$ hugh - special case when using -1, if user doesn't have a record yet
							if (FabrikWorker::getMenuOrRequestVar('rowid', '', $this->isMambot) == '-1')
							{
								return;
							}
							else
							{
								// if no key found set rowid to 0 so we can insert a new record.
								if (empty($usekey) && !$this->isMambot)
								{
									$this->_rowId = 0;
									JError::raiseNotice(500, JText::sprintf('COULD NOT FIND RECORD IN DATABASE', $this->_rowId));
									return;
								}
								else
								{
									//if we are using usekey then theres a good possiblity that the record
									//won't yet exists- so in this case suppress this error message
									$this->_rowId = 0;
								}
							}
						}
					}
				}
				//no need to setJoinData if you are correcting a failed validation
				if (!empty($data))
				{
					$this->setJoinData($data);
				}
			}

			//set the main part of the form's default data
			if ($this->_rowId != '')
			{
				$data = JArrayHelper::fromObject($data[0]);
			}
			else
			{
				//could be a view
				if ($listModel->isView())
				{
					//@TODO test for new records from views
					$data = JArrayHelper::fromObject($data[0]);
				}
				else
				{
					if (($this->isMambot || $this->saveMultiPage()) && (!empty($data) && is_object($data[0])))
					{
						$data = JArrayHelper::fromObject($data[0]);
					}
					else
					{
						// $$$ rob was causing notices when adding record with joined groups as $data[0]->join unset if we just use request
						//$data = JRequest::get('request');
						$data = JArrayHelper::fromObject($data[0]);
					}
				}
			}

			$this->_listModel = $listModel;
		}
		//Test to allow {$my->id}'s to be evald from query strings
		$w = new FabrikWorker();
		$data = $w->parseMessageForPlaceHolder($data);
		$this->_data = $data;
		FabrikHelperHTML::debug($data, 'form:data');
		JDEBUG ? $profiler->mark('queryselect: getData() end') : null;
		return $this->_data;
	}

	/**
	 * checks if user is logged in and form multipage settings to determine
	 * if the form saves to the session table on multipage navigation
	 * @return	bool
	 */

	function saveMultiPage()
	{
		$params = $this->getParams();
		$session = JFactory::getSession();
		//set in plugins such as confirmation plugin
		if ($session->get('com_fabrik.form.' . $this->getId() . '.session.on') == true)
		{
			return true;
		}
		$save = (int) $params->get('multipage_save', 0);
		$user = JFactory::getUser();
		if ($user->get('id') !== 0)
		{
			return $save === 0 ? false : true;
		}
		else
		{
			return $save === 2 ? true : false;
		}
	}

	/**
	 *
	 * if editing a record which contains repeated join data then on start $data is an
	 * array with each records being a row in the database.
	 *
	 * We need to take this structure and convert it to the same format as when the form
	 * is submitted
	 * @param	array	data
	 */

	function setJoinData(&$data)
	{
		$this->_joinDefaultData = array();
		if (empty($data))
		{
			return;
		}
		if (!array_key_exists('join', $data[0]))
		{
			$data[0]->join = array();
		}
		// $$$ hugh - sometimes $data[0]->join is an object not an array?
		// $$$ rob - no longer as in render we use FarrayHelper to not recurse into data when setting to object
		// $$$ rob   readding back in - was needed with cdd in repeat groups
		if (is_object($data[0]->join))
		{
			$data[0]->join = JArrayHelper::fromObject($data[0]->join);
		}

		//no joins so leave !
		if (!is_array($this->_aJoinObjs))
		{
			return;
		}
		if ($this->_rowId != '')
		{
			$groups = $this->getGroupsHiarachy();
			foreach ($groups as $groupModel)
			{
				if ($groupModel->isJoin())
				{
					$group = $groupModel->getGroup();
					//$$$ rob - if loading data from session then the join structure is already in place so dont overwrite
					if (array_key_exists($group->join_id, $data[0]->join))
					{
						continue;
					}
					$data[0]->join[$group->join_id] = array();
					$elementModels = $groupModel->getMyElements();
					foreach ($elementModels as $elementModel)
					{
						$name = $elementModel->getFullName(false, true, false);
						$fv_name = 'join[' . $group->join_id . '][' . $name . ']';
						$rawname = $name  . '_raw';
						$fv_rawname = 'join[' . $group->join_id . '][' . $rawname . ']';
						foreach ($data as $row)
						{
							if (array_key_exists($name, $row))
							{
								$v = $row->$name;
								$v = FabrikWorker::JSONtoData($v, false);
								$data[0]->join[$group->join_id][$name][] = $v;
								unset($row->$name);
							}
							/* $$$ hugh - seem to have a different format if just failed validation! */
							else if (array_key_exists($fv_name, $row))
							{
								$v = $row->$fv_name;
								if (is_object($v))
								{
									$v = JArrayHelper::fromObject($v);
								}
								$data[0]->join[$group->join_id][$name] = $v;
								unset($row->$fv_name);
							}

							if (array_key_exists($rawname, $row)) {
								$v = $row->$rawname;
								$v = FabrikWorker::JSONtoData($v, false);
								$data[0]->join[$group->join_id][$rawname][] = $v;
								unset($row->$rawname);
							}
							/* $$$ hugh - seem to have a different format if just failed validation! */
							else if (array_key_exists($fv_rawname, $row))
							{
								$v = $row->$fv_rawname;
								if (is_object($v))
								{
									$v = JArrayHelper::fromObject($v);
								}
								$data[0]->join[$group->join_id][$rawname][] = $v;
								unset($row->$fv_rawname);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * get the forms session data (used when using multipage forms)
	 *
	 * @return	object	session data
	 */

	function getSessionData()
	{
		$params = $this->getParams();
		$this->sessionModel = JModel::getInstance('Formsession', 'FabrikFEModel');
		$this->sessionModel->setFormId($this->getId());
		$this->sessionModel->setRowId($this->_rowId);
		$useCookie = (int) $params->get('multipage_save', 0) === 2 ? true : false;
		if (!$useCookie)
		{
			//incase a plugin is using cookie session (e.g. confirmation plugin)
			$useCookie = $this->sessionModel->canUseCookie();
		}
		$this->sessionModel->useCookie($useCookie);
		return $this->sessionModel->load();
	}

	/**
	 * @access private
	 * create the sql query to get the rows data for insertion into the form
	 */

	function _buildQuery()
	{
		if (isset($this->query))
		{
			return $this->query;
		}
		$db = FabrikWorker::getDbo();
		$conf = JFactory::getConfig();
		$form = $this->getForm();
		if (!$form->record_in_database)
		{
			return;
		}
		$listModel = $this->getListModel();
		$item = $listModel->getTable();
		$sql = $listModel->_buildQuerySelect();
		$sql .= $listModel->_buildQueryJoin();
		$emptyRowId = $this->_rowId === '' ? true : false;
		$random = JRequest::getVar('random');
		$usekey = FabrikWorker::getMenuOrRequestVar('usekey', '', $this->isMambot);
		if ($usekey != '')
		{
			$usekey = explode('|', $usekey);
			foreach ($usekey as &$tmpk)
			{
				$tmpk = !strstr($tmpk, '.') ? $item->db_table_name . '.' . $tmpk : $tmpk;
				$tmpk = FabrikString::safeColName($tmpk);
			}
			if (!is_array($this->_rowId)) {
				$aRowIds = explode('|', $this->_rowId);
			}
		}
		$comparison = JRequest::getVar('usekey_comparison', '=');
		$viewpk = JRequest::getVar('view_primary_key');
		// $$$ hugh - changed this to !==, as in rowid=-1/usekey situations, we can have a rowid of 0
		// I don't THINK this will have any untoward side effects, but ...
		if (!$random && !$emptyRowId)
		{
			$sql .= ' WHERE ';
			if (!empty($usekey))
			{
				$sql .= "(";
				$parts = array();
				for ($k = 0; $k < count($usekey); $k++)
				{
					//ensure that the key value is not quoted as we Quote() afterwards
					if (strstr($aRowIds[$k], "'"))
					{
						$aRowIds[$k] = str_replace("'", '', $aRowIds[$k]);
					}
					if ($comparison == '=')
					{
						$parts[] = ' ' . $usekey[$k] . ' = ' . $db->quote($aRowIds[$k]);
					}
					else
					{
						$parts[] = ' ' . $usekey[$k] . ' LIKE '. $db->quote('%' . $aRowIds[$k] . '%');
					}
				}
				$sql .= implode(' AND ', $parts);
				$sql .= ')';
			}
			else
			{
				$sql .= ' ' . $item->db_primary_key . ' = ' . $db->quote($this->_rowId);
			}
		} else {
			if ($viewpk != '')
			{
				$sql .= ' WHERE ' . $viewpk . ' ';
			} else if ($random)
			{
				// $$$ rob Should this not go after prefilters have been applied ?
				$sql .= ' ORDER BY RAND() LIMIT 1 ';
			}
		}
		// get prefilter conditions from table and apply them to the record
		//the false, ignores any filters set by the table
		$where = $listModel->_buildQueryWhere(false);
		if (strstr($sql, 'WHERE') && $this->_rowId != '')
		{
			//do it this way as queries may contain subquerues which we want to keep the where
			$firstword = JString::substr($where, 0, 5);
			if ($firstword == 'WHERE')
			{
				$where = JString::substr_replace($where, 'AND', 0, 5);
			}
		}
		//set rowId to -2 to indicate random record
		if ($random)
		{
			$this->_rowId = -2;
		}
		// $$$ rob ensure that all prefilters are wrapped in brackets so that
		// only one record is loaded by the query - might need to set $word = and?
		if (trim($where) != '')
		{
			$where = explode(' ', $where);
			$word = array_shift($where);
			$sql .= $word . ' (' . implode(' ', $where) . ')';
		}
		if (!$random)
		{
			// $$$ rob if showing joined repeat groups we want to be able to order them as defined in the table
			$sql .= $listModel->_buildQueryOrder();
		}
		$this->query = $sql;
		return $sql;
	}

	/**
	 * attempts to determine if the form contains the element
	 * @param	string	element name to search for
	 * @param	bool	check search name against element id
	 * @param	bool	check short element name
	 * @return	bool	true if found, false if not found
	 */

	function hasElement($searchName, $checkInt = false, $checkShort = true)
	{
		$groups = $this->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getMyElements();
			if (!is_array($groupModel->elements))
			{
				continue;
			}
			foreach ($groupModel->elements as $elementModel)
			{
				$element = $elementModel->getElement();
				if ($checkInt)
				{
					if ($searchName == $element->id)
					{
						$this->_currentElement = $elementModel;
						return true;
					}
				}
				if ($searchName == $element->name && $checkShort)
				{
					$this->_currentElement = $elementModel;
					return true;
				}
				if ($searchName == $elementModel->getFullName(true, true, false))
				{
					$this->_currentElement = $elementModel;
					return true;
				}
				if ($searchName == $elementModel->getFullName(false, true, false))
				{
					$this->_currentElement = $elementModel;
					return true;
				}
				if ($searchName == $elementModel->getFullName(true, false, false))
				{
					$this->_currentElement = $elementModel;
					return true;
				}
				if ($searchName == $elementModel->getFullName(false, false, false))
				{
					$this->_currentElement = $elementModel;
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * get an element
	 * @param	string	$searchName
	 * @param	bool	check search name against element id
	 * @param	bool	check short element name
	 * @return	mixed	ok: element model not ok: false
	 */

	function getElement($searchName, $checkInt = false, $checkShort = true)
	{
		return $this->hasElement($searchName, $checkInt, $checkShort) ? $this->_currentElement : false;
	}

	/**
	 * @param	object	$listModel
	 */

	function setListModel(&$listModel)
	{
		$this->_listModel = $listModel;
	}

	/**
	 * is the page a multipage form?
	 * @return	bool
	 *
	 */

	function isMultiPage()
	{
		$groups = $this->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$params = $groupModel->getParams();
			if ($params->get('split_page'))
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * get an object of pages, key'd on page counter and containing an array of the page's group ids
	 *
	 * @return object
	 */

	function getPages()
	{
		if (!is_null($this->pages))
		{
			return $this->pages;
		}
		$this->pages = new stdClass();
		$pageCounter = 0;
		$groups = $this->getGroupsHiarachy();
		$c = 0;
		foreach ($groups as $groupModel)
		{
			$params = $groupModel->getParams();
			if ($params->get('split_page') && $c != 0 && $groupModel->canView())
			{
				$pageCounter ++;
			}
			if ($groupModel->canView())
			{
				if (!isset($this->pages->$pageCounter))
				{
					$this->pages->$pageCounter = array();
				}
				array_push( $this->pages->$pageCounter, $groupModel->getId());
			}
			$c ++;
		}
		return $this->pages;
	}

	/**
	 * should the form submit via ajax or not?
	 * @return	bool
	 */

	function isAjax()
	{
		if (is_null($this->ajax))
		{
			$this->ajax = JRequest::getBool('ajax', false);
			$groups = $this->getGroupsHiarachy();
			foreach ($groups as $groupModel)
			{
				$elementModels = $groupModel->getPublishedElements();
				foreach ($elementModels as $elementModel)
				{
					if ($elementModel->requiresAJAXSubmit())
					{
						$this->ajax = true;
					}
				}
			}
		}
		return (bool) $this->ajax;
	}

	/**
	 * @since fabrik2.0rc1
	 * Used in special case where you have 2 + n-n joins in a single table
	 * In this case the sql query will most likely create four rows of data for
	 * each combination of possibilities
	 *
	 * E.g.
	 *
	 * tbl classes (id, label)
	 *
	 * left joined to:
	 * tbl student_classes (id, label, student_id)
	 *
	 * left joined to
	 * tbl student_teachers (id, label, teacher_id)
	 *
	 * entering one records with 2 students and 2 teachers gives you 4 rows in the query
	 *
	 * classid  student_id, teacher_id
	 * 1        1           1
	 * 1        2	          1
	 * 1        1	          2
	 * 1        2           2
	 *
	 * @param unknown_type $data
	 */

	function _reduceDataForXRepeatedJoins()
	{
		$groups = $this->getGroupsHiarachy();
		$listModel = $this->getListModel();
		foreach ($groups as $groupModel) {
			if ($groupModel->canRepeat() && $groupModel->isJoin()) {

				$joinModel = $groupModel->getJoinModel();
				$tblJoin = $joinModel->getJoin();
				// $$$ hugh - slightly modified these lines so we don't create $this->_data['join'] if there is no
				// join data, because that then messes up code subsequent code that checks for empty($this->_data)
				if (!isset($this->_data['join'])) {
					//$this->_data['join'] = array();
					return;
				}
				if (!array_key_exists($tblJoin->id, $this->_data['join'])) {
					continue;
				}

				if ($tblJoin->table_join == '') {
					continue;
				}

				$jdata =& $this->_data['join'][$tblJoin->id];
				$db	= $listModel->getDb();
				$db->setQuery("DESCRIBE ".$db->nameQuote($tblJoin->table_join));
				$fields = $db->loadObjectList();
				foreach ($fields as $f) {
					if ($f->Key == 'PRI') {
						$pkField = $tblJoin->table_join . '___' . $f->Field;
					}
				}
				$usedkeys = array();
				if (!empty($jdata) && array_key_exists($pkField, $jdata)) {
					foreach ($jdata[$pkField] as $key=>$value) {
						/*
						 * $$$rob
						* added : || ($value === '' && !empty($this->_arErrors))
						* this was incorrectly reducing empty data
						* when re-viewing form after failed validation
						* with a form with repeating groups (with empty data in the key fields
						*
						*/
						if (!in_array($value, $usedkeys) || ($value === '' && !empty($this->_arErrors))) {
							$usedkeys[$key] = $value;
						}
					}
				}
				$keystokeep = array_keys($usedkeys);
				///remove unneeded data from array
				foreach ($jdata as $key =>$value) {
					foreach ($value as $key2=>$v) {
						if (!in_array($key2, $keystokeep)) {
							unset($jdata[$key][$key2]);
						}
					}
				}

				//reduce the keys so that we dont have keys of 0, 2
				foreach ($jdata as $key =>$array) {
					$jdata[$key] = array_values($array);
				}
			}
		}
	}

	/**
	 * query all active form plugins to see if they inject cutsom html into the top
	 * or bottom of the form
	 *
	 *return array plugin top html, plugin bottom html (inside <form>) plugin end (after form)
	 */

	public function getFormPluginHTML()
	{
		$pluginManager = FabrikWorker::getPluginManager();
		$formPlugins = $pluginManager->getPlugInGroup('form');
		$form = $this->getForm();

		$pluginManager->runPlugins('getBottomContent', $this, 'form');
		$pluginbottom = implode("<br />", array_filter($pluginManager->_data));

		$pluginManager->runPlugins('getTopContent', $this, 'form');
		$plugintop = implode("<br />", array_filter($pluginManager->_data));

		//inserted after the form's closing </form> tag
		$pluginManager->runPlugins('getEndContent', $this, 'form');
		$pluginend = implode("<br />", array_filter($pluginManager->_data));
		return array($plugintop, $pluginbottom, $pluginend);
	}

	/**
	 * Presuming that our introduction looks like this:
	 *
	 * {new:this is an intro}
	 * {edit:You're editing a record}
	 * some more text
	 *
	 * creating a new form record will show the intro text as:
	 *
	 * this is an intro
	 * some more text
	 *
	 * and editing an existing record will show:
	 *
	 * You're editing a record
	 * some more text
	 *
	 * @return string modified intro
	 */

	function getIntro()
	{
		$match = ((int) $this->_rowId === 0) ? 'new' : 'edit';
		$remove = ((int) $this->_rowId === 0) ? 'edit' : 'new';
		$match = "/{".$match.":\s*.*?}/i";
		$remove = "/{".$remove.":\s*.*?}/i";
		$intro = $this->getForm()->intro;
		$intro = preg_replace_callback( $match, array($this, '_getIntro'), $intro);
		$intro = preg_replace($remove, '', $intro);
		$intro = str_replace('[','{', $intro);
		$intro = str_replace(']','}', $intro);
		$w = new FabrikWorker();
		$intro = $w->parseMessageForPlaceHolder($intro, $this->_data, true);
		// $$$ rob 26/01/2011 - this was stopping content plugins from rendering.
		//$intro = str_replace('{','[', $intro);
		//$intro = str_replace('}',']', $intro);
		return $intro;
	}

	/**
	 * used from getIntro as preg_replace_callback function to strip
	 * undeisred text from form label intro
	 * @param array $match
	 * @return string intro text
	 */

	private function _getIntro($match)
	{
		$m = explode(":", $match[0]);
		array_shift($m);
		return FabrikString::rtrimword(implode(":", $m) , "}");
	}

	/**
	 *
	 * @return string label
	 */

	function getLabel()
	{
		$label = $this->getForm()->label;
		if (!$this->_editable) {
			return str_replace("{Add/Edit}", '', $label);
		}
		if (JString::stristr($label, "{Add/Edit}")) {
			$replace = ((int) $this->_rowId === 0) ? JText::_('COM_FABRIK_ADD') : JText::_('COM_FABRIK_EDIT');
			$label = str_replace("{Add/Edit}", $replace, $label);
		}
		return $label;
	}

	/** currently only called from listModel _createLinkedForm when copying existing table
	 * @TODO should move this to the admin modle
	 * @return object form table
	 */

	function copy()
	{
		//array key = old id value new id
		$this->groupidmap = array();
		$groupModels = $this->getGroups();
		$this->_form = null;
		$form = $this->getTable();
		$form->id = false;
		// rob newFormLabel set in table copy
		if (JRequest::getVar('newFormLabel', '') !== '') {
			$form->label = JRequest::getVar('newFormLabel');
		}
		$res = $form->store();
		if (!$res) {
			JError::raiseError(500, $form->getErrorMsg());
			return false;
		}
		$newElements = array();
		foreach ($groupModels as $groupModel) {
			$oldid = $groupModel->getId();
			// $$$rob use + rather than array_merge to preserve keys
			$groupModel->_newFormid = $form->id;
			$newElements = $newElements + $groupModel->copy();

			$this->groupidmap[$oldid] = $groupModel->getGroup()->id;
		}
		//need to do finalCopyCheck() on form elements

		$pluginManager = FabrikWorker::getPluginManager();

		//@TODO something not right here when copying a cascading dropdown element in a join group
		foreach ($newElements as $origId => $newId) {
			$plugin = $pluginManager->getElementPlugin($newId);
			$plugin->finalCopyCheck($newElements);
		}
		//update the model's table to the copied one
		$this->_form = $form;
		$this->setId($form->id);
		$this->newElements = $newElements;
		return $form;
	}

	/**
	 * if you have koowa installed their db obj doesnt have a getNullDate function
	 * @return unknown_type
	 */

	function getNullDate()
	{
		return '0000-00-00 00:00:00';
	}


	public function getRelatedTables()
	{
		$db = FabrikWorker::getDbo(true);
		$links = array();
		$params = $this->getParams();
		if (!$params->get('show-referring-table-releated-data', false))
		{
			return $links;
		}
		$listModel = $this->getListModel();
		$referringTable = JModel::getInstance('List', 'FabrikFEModel');
		// $$$ rob - not sure that referring_table is anything other than the form's table id
		// but for now just defaulting to that if no other variable found (e.g when links in sef urls)
		$tid = JRequest::getInt('referring_table', JRequest::getInt('listid', $listModel->getTable()->id));
		$referringTable->setId($tid);
		$tmpKey = '__pk_val';
		$tableParams = $referringTable->getParams();
		$table = $referringTable->getTable();
		$joinsToThisKey = $referringTable->getJoinsToThisKey();
		$linksToForms =  $referringTable->getLinksToThisKey();

		$row = $this->getData();
		$factedLinks = $tableParams->get('factedlinks', null);
		if (is_null($factedLinks))
		{
			return;
		}
		$linkedLists = $factedLinks->linkedlist;
		$aExisitngLinkedForms = $factedLinks->linkedform;
		$linkedform_linktype = $factedLinks->linkedform_linktype;
		$linkedtable_linktype = $factedLinks->linkedlist_linktype;
		$f = 0;
		$query = $db->getQuery(true);
		$query->select('id, label, db_table_name')->from('#__{package}_lists');
		$db->setQuery($query);
		$aTableNames = $db->loadObjectList('label');
		if ($db->getErrorNum())
		{
			JError::raiseError(500, $db->getErrorMsg());
		}
		foreach ($joinsToThisKey as $element)
		{
			$qsKey = $referringTable->getTable()->db_table_name . '___' . $element->name;
			$val = JRequest::getVar($qsKey);
			if ($val == '')
			{
				//default to row id if we are coming from a main link (and not a related data link)
				$val = JRequest::getVar($qsKey . '_raw', '');
				if (empty($val))
				{
					$thisKey = $this->getListModel()->getTable()->db_table_name . '___' . $element->join_key_column . '_raw';
					$val = JArrayHelper::getValue($this->_data, $thisKey, $val);
					if (empty($val))
					{
						$val = JRequest::getVar('rowid');
					}
				}
			}
			$key = $element->list_id . '-' . $element->form_id . '-' . $element->element_id;
			if (isset($linkedLists->$key))
			{
				// $$$ hugh - changed to use _raw as key, see:
				// http://fabrikar.com/forums/showthread.php?t=20020
				$linkKey = $element->db_table_name . '___' . $element->name;
				$linkKeyRaw = $linkKey . '_raw';
				$popUpLink = JArrayHelper::getValue($linkedtable_linktype->$key, $f, false);
				$recordCounts = $referringTable->getRecordCounts($element);
				$count = is_array($recordCounts) && array_key_exists($val, $recordCounts) ? $recordCounts[$val]->total : 0;
				$links[$element->list_id][] = $referringTable->viewDataLink($popUpLink, $element, null, $linkKey, $val, $count, $f);
			}
			$f ++;
		}
		$f = 0;
		//create columns containing links which point to forms assosciated with this table
		foreach ($linksToForms as $element)
		{
			if ($element !== false)
			{
				$key = $element->list_id . '-' . $element->form_id . '-' . $element->element_id;
				$linkedForm = $aExisitngLinkedForms->$key;
				$popUpLink = $linkedform_linktype->$key;
				if ($linkedForm !== '0')
				{
					if (is_object($element))
					{
						//$$$rob moved these two lines here as there were giving warnings since Hugh commented out the if ($element != '') {
						// $$$ hugh - what?  Eh?  WhaddidIdo?  Anyway, we use $linkKey up ^^ there somewhere, so we need to define it earlier!
						$linkKey	= @$element->db_table_name . '___' . @$element->name;
						$val = JRequest::getVar($linkKey);
						if ($val == '')
						{
							$val = JRequest::getVar($qsKey . '_raw', JRequest::getVar('rowid'));
						}
						$links[$element->list_id][] = $referringTable->viewFormLink($popUpLink, $element, null, $linkKey, $val, false, $f);
					}
				}
				$f ++;
			}
		}
		return $links;
	}

	/**
	 * get the url to use as the form's action property
	 * @return	string	url
	 */
	function getAction()
	{
		$app = JFactory::getApplication();
		// Get the router
		$router = $app->getRouter();
		if ($app->isAdmin())
		{
			$action = JArrayHelper::getValue($_SERVER, 'REQUEST_URI', 'index.php');
			$action =  str_replace("&", "&amp;", $action);
			// $$$rob no good for cck form?
			//return "index.php";
			return $action;
		}
		if ((int) $this->packageId !== 0)
		{
			$action = 'index.php?option=com_fabrik&view=form&formid=' . $this->getId();
			return $action;
		}
		$option = JRequest::getCmd('option');
		if ($option === 'com_fabrik')
		{
			$page = 'index.php?';
			//get array of all querystring vars
			$queryvars = $router->parse(JFactory::getURI());
			if ($this->isAjax())
			{
				$queryvars['format'] = 'raw';
				//@TODO this should prb be views or controllers now?
				//$queryvars['controller'] = "form";
				//$queryvars['view'] = 'form';
				unset($queryvars['view']);
				$queryvars['task'] = 'form.process';
			}
			$qs = array();
			foreach ($queryvars as $k => $v)
			{
				if ($k == 'rowid')
				{
					$v = $this->getRowId();
				}
				// $$$ hugh - things get weird if we have been passed a urlencoded URL as a qs arg,
				// which the $router->parse() above will have urldecoded, and it gets used as part of the URI path
				// when we JRoute::_() below.  So we need to re-urlencode stuff and junk.
				// Ooops, make sure it isn't an array, which we'll get if they have something like
				// &table___foo[value]=bar
				if (!is_array($v))
				{
					$v = urlencode($v);
				}
				$qs[] = $k . '=' . $v;
			}
			$action = $page.implode("&amp;", $qs);

			$action = JRoute::_($action);
		}
		else
		{
			//in plugin & SEF URLs
			if ((int) $router->getMode() === (int)JROUTER_MODE_SEF)
			{
				//$$$ rob if embedding a form in a form, then the embedded form's url will contain
				// the id of the main form - not sure if its an issue for now
				$action = JArrayHelper::getValue($_SERVER, 'REQUEST_URI', 'index.php');
			}
			else
			{
				// in plugin and no sef (routing dealt with in form controller)
				$action = 'index.php';
			}
		}
		return $action;
	}

	/**
	 * if the group is a joined group we want to ensure that its id field is contained with in the group's elements
	 *
	 * @param	object	$groupTable
	 * @return	string	html hidden field
	 */

	function _makeJoinIdElement(&$groupTable)
	{
		$listModel = $this->getListModel();
		$joinId = $this->_aJoinGroupIds[$groupTable->id];
		$element = new stdClass();
		//add in row id for join data
		$element->label = '';
		$element->error = '';
		$element->value = '';
		$element->id = '';
		$element->startRow = false;
		$element->endRow = false;
		$element->errorTag = '';
		$element->column = '';
		$element->className = '';
		$element->containerClass = '';
		foreach ($listModel->getJoins() as $oJoin)
		{
			if ($oJoin->id == $joinId)
			{
				$key = $oJoin->table_join . $this->joinTableElementStep . $oJoin->table_join_key;
				if (array_key_exists('join', $this->_data))
				{
					// $$$ rob if join element is a db join the data $key contains label and not foreign key value
					if (@array_key_exists($key . '_raw', $this->_data['join'][$joinId]))
					{
						$val = $this->_data['join'][$joinId][$key . '_raw'];
					}
					else
					{
						$val = @$this->_data['join'][$joinId][$key];
					}
					if (is_array($val))
					{
						if (array_key_exists(0,$val))
						{
							$val = $val[0];
						}
						else
						{
							$val = '';
						}
					}
				}
				else
				{
					$val = '';
				}
				if ($val == '')
				{
					//somethings gone wrong - lets take the main table's key
					$k = $oJoin->join_from_table . $this->joinTableElementStep . $oJoin->table_key;
					$val = @$this->_data[$k];
				}
				$element->value = $val;
				$element->element = '<input type="hidden" id="join.' . $joinId . '.rowid" name="join[' . $joinId . '][rowid]" value="' . $val . '" />';
				$element->hidden = true;
				$element->containerClass = 'fabrikElementContainer  fabrikHide';
			}
		}
		return $element;
	}

	public function getreadOnlyVals()
	{
		return $this->readOnlyVals;
	}

	/**
	 * prepare the elements for rendering
	 * @param string $tmpl @since 3.0
	 */
	public function getGroupView($tmpl = '')
	{
		if (isset($this->groupView)) {
			return $this->groupView;
		}
		// $$$rob - do regardless of whether form is editable as $data is required for hidden encrypted fields
		// and not used anywhere else (avoids a warning message)
		$data = array();
		// $$$ rob - 3.0 for some reason just using $this->_data was not right as join data was empty when editing exisitng record
		// $$$ hugh - commented this out, as a) running getData() twice is expensive, and b) it blows away any changes onLoad plugins
		// make to _data, like the juser plugin
		// Ran this change for a couple of weeks before committing, seems to work without it.
		//unset($this->_data);
		$origData = $this->getData();
		foreach ($origData as $key => $val) {
			if (is_string($val)) {
				$data[$key] = htmlspecialchars($val, ENT_QUOTES);
			}
		}

		$this->groupView = array();
		$this->readOnlyVals = array();
		// $$$ hugh - temp foreach fix
		$groups = $this->getGroupsHiarachy();
		foreach ($groups as $gkey => $groupModel) {
			$groupTable = $groupModel->getGroup();
			$group = $groupModel->getGroupProperties($this);
			$groupParams = $groupModel->getParams();
			$group->intro = $groupParams->get('intro');
			$aElements = array();
			//check if group is acutally a table join

			if (array_key_exists($groupTable->id, $this->_aJoinGroupIds)) {
				$aElements[] = $this->_makeJoinIdElement($groupTable);
			}

			$repeatGroup = 1;
			$foreignKey = null;
			$startHidden = false;
			if ($groupModel->canRepeat()) {
				if ($groupModel->isJoin()) {

					$joinTable = $groupModel->getJoinModel()->getJoin();
					$foreignKey  = '';
					if (is_object($joinTable)) {
						$fullFk = $joinTable->table_join . '___' . $joinTable->table_join_key;
						//need to duplicate this perhaps per the number of times
						//that a repeat group occurs in the default data?

						// $$$ rob added check that the join data is not empty which seems to occur on a new form, without it the warning about no
						// published fk is raised incorrectly
						// $$$ hugh - we have some code that relias on $model->_data being empty for new forms
						//if (!isset($this->_data['join'])) {
						//$this->_data['join'] = array();
						//}
						//if (!isset($this->_data['join'])) {
						//$this->_data['join'] = array();
						//}
						if (is_array($origData) && array_key_exists('join', $origData) && array_key_exists($joinTable->id, $origData['join']) && !empty($origData['join'][$joinTable->id])) {
							$elementModels = $groupModel->getPublishedElements();
							reset($elementModels);
							$tmpElement = current($elementModels);
							$smallerElHTMLName = $tmpElement->getFullName(false, true, false);
							$repeatGroup = count($origData['join'][$joinTable->id][$smallerElHTMLName]);
							if (!array_key_exists($fullFk, $this->_data['join'][$joinTable->id])) {
								JError::raiseWarning(E_ERROR, JText::sprintf('COM_FABRIK_JOINED_DATA_BUT_FK_NOT_PUBLISHED', $fullFk));
								$startHidden = false;
							} else {
								// show empty groups if we are validating a posted form
								if (JRequest::getCmd('task') !== 'process') {
									$this->getSessionData();
									if ($this->sessionModel->row->data === '') {
										$startHidden = true;
										foreach ($origData['join'][$joinTable->id] as $jData) {
											if (!empty($jData[0])) {
												$startHidden = false;
												continue;
											}
										}
									}
								}
							}
						} else {
							if (!$groupParams->get('repeat_group_show_first')) {
								continue;
							}
						}
					}
				} else {
					// repeat groups which aren't joins
					$elementModels = $groupModel->getPublishedElements();
					foreach ($elementModels as $tmpElement) {
						$smallerElHTMLName = $tmpElement->getFullName(false, true, false);
						if (array_key_exists($smallerElHTMLName . '_raw', $this->_data)) {
							$d = $this->_data[$smallerElHTMLName . '_raw'];
						} else {
							$d = @$this->_data[$smallerElHTMLName];
						}
						$d = json_decode($d, true);
						$c = count($d);
						if ($c > $repeatGroup) {
							$repeatGroup = $c;
						}
					}
				}
			}
			$groupModel->repeatTotal = $startHidden ? 0 : $repeatGroup;
			$aSubGroups = array();
			for ($c = 0; $c < $repeatGroup; $c++) {
				$aSubGroupElements = array();
				$elCount = 0;
				$elementModels = $groupModel->getPublishedElements();

				foreach ($elementModels as $elementModel) {

					// $$$ rob ensure that the element is associated with the correct form (could occur if n plugins rendering form
					// and detailed views of the same form.
					$elementModel->_form = $this;
					$elementModel->tmpl = $tmpl;
					//$$$rob test don't include the element in the form is we can't use and edit it
					//test for captcha element when user logged in

					if (!$this->_editable) {
						$elementModel->_inDetailedView = true;
					}

					if (!$this->_editable && !$elementModel->canView()) {
						continue;
					}

					//fabrik3.0 : if the element cant be seen or used then dont add it?
					// $$$ hugh - experimenting with adding non-viewable, non-editable to encrypted vars
					/*
					if (!$elementModel->canUse() && !$elementModel->canView()) {
						continue;
					}
					*/

					$elementModel->_foreignKey = $foreignKey;
					$elementModel->_repeatGroupTotal = $repeatGroup - 1;

					$element = $elementModel->preRender($c, $elCount, $tmpl);

					// $$$ hugh - experimenting with adding non-viewable, non-editable to encrypted vars
					//if (!$element || ($elementModel->canView() && !$elementModel->canUse()))
					if (!$element || !$elementModel->canUse())
					{
						// $$$ hugh - $this->data doesn't seem to always have what we need in it, but $data does.
						// can't remember exact details, was chasing a nasty issue with encrypted 'user' elements.

						// $$$ rob HTMLName seems not to work for joined data in confirmation plugin
						//$this->readOnlyVals[$elementModel->getHTMLName($c )] = $elementModel->getValue($this->data);
						$elementModel->getValuesToEncrypt($this->readOnlyVals, $data, $c);
						$this->readOnlyVals[$elementModel->getFullName(false, true, false )]['repeatgroup'] = $groupModel->canRepeat();
						$this->readOnlyVals[$elementModel->getFullName(false, true, false )]['join'] = $groupModel->isJoin();
					}
					if ($element) {
						$elementModel->stockResults($element, $aElements, $this->data, $aSubGroupElements);
					}
					if ($element && !$element->hidden) {
						$elCount ++;
					}
				}
				//if its a repeatable group put in subgroup
				if ($groupModel->canRepeat()) {

					//style attribute for group columns (need to occur after randomisation of the elements otherwise clear's are not ordered correctly)
					$ix = 1;
					foreach ($aSubGroupElements as $elKey => $element)
					{
						$ix = $groupModel->setColumnCss($element, $ix);
					}

					$aSubGroups[] = $aSubGroupElements;
				}
			}
			$groupModel->randomiseElements($aElements);

			//style attribute for group columns (need to occur after randomisation of the elements otherwise clear's are not ordered correctly)
			$ix = 1;
			foreach ($aElements as $elKey => $element)
			{
				$ix = $groupModel->setColumnCss($element, $ix);
			}
			$group->elements = $aElements;
			$group->subgroups = $aSubGroups;
			$group->startHidden = $startHidden;
			//only create the group if there are some element inside it
			if (count($aElements) != 0) {
				//28/01/2011 $$$rob and if it is published
				$showGroup = (int) $groupParams->get('repeat_group_show_first');
				if ($showGroup != -1) {
					// $$$ - hugh - testing new 'hide if no usable elements' option (4)
					//Jaanus: if not form view with "details only" option and not details view with "form only" option
					if (!($showGroup == 2 && $this->_editable)
						&& !($showGroup == 3 && JRequest::getVar('view', 'form') == 'details')
						&& !($showGroup == 4 && !$groupModel->canView())
						) {
						$this->groupView[$group->name] = $group;
					}
				}
			}
		}
		return $this->groupView;
	}


	function getLinkedFabrikLists($table)
	{
		//get any fabrik tables that link to the join table
		if (!isset($this->_linkedFabrikLists)) {
			$this->_linkedFabrikLists = array();
		}
		if (!array_key_exists($table, $this->_linkedFabrikLists)) {
			$db = FabrikWorker::getDbo(true);
			if (trim($table == '')) {
				return array();
			} else {
				$query = $db->getQuery(true);
				$query->select('*')->from('#__{package}_lists')->where("db_table_name = ".$db->quote($table));
				$db->setQuery($query);
			}
			$this->_linkedFabrikLists[$table] = $db->loadColumn();
			if ($db->getErrorNum()) {
				JError::raiseError(500, $db->getErrorMsg());
			}
		}
		return $this->_linkedFabrikLists[$table];
	}

	function updatedByPlugin($fullname = '', $value = null) {
		// used to see if something legitimate in the submission process, like a form plugin,
		// has modified an RO element value and wants to override the RO/origdata.
		// If $value is set, add it.
		if (isset($value))
		{
			$this->_pluginUpdatedElements[$fullname] = $value;
		}
		return array_key_exists($fullname, $this->_pluginUpdatedElements);
	}

	protected function populateState()
	{
		$app = JFactory::getApplication('site');
		if (!$app->isAdmin()) {
			// Load the menu item / component parameters.
			$params = $app->getParams();
			$this->setState('params', $params);

			// Load state from the request.
			$pk = JRequest::getInt('formid', $params->get('formid'));
		} else {
			$pk = JRequest::getInt('formid');
		}
		$this->setState('form.id', $pk);
	}


	public function inLineEditResult()
	{
		$listModel = $this->getListModel();
		$listid = $listModel->getId();
		$listModel->clearCalculations();
		$listModel->doCalculations();
		$elementid = JRequest::getInt('elid');
		$elmentModel = $this->getElement($elementid, true);
		$rowid = JRequest::getVar('rowid');
		$listModel->setId($listid);
		//if the inline edit stored a element join we need to reset back the table
		$listModel->getTable(true);
		$data = JArrayHelper::fromObject($listModel->getRow($rowid));
		$key = JRequest::getVar('element');
		$html= '';
		$html .= $elmentModel->renderListData($data[$key], $data);
		$listRef = 'list_'.JRequest::getVar('listref');
		$doCalcs = "\nFabrik.blocks['".$listRef."'].updateCals(".json_encode($listModel->getCalculations()).")";
		$html .= '<script type="text/javasript">';
		$html .= $doCalcs;
		$html .= "</script>\n";
		return $html;
	}

	public function isEditable() {
		return $this->_editable;
	}

	/**
	 * helper method to get the session redirect key. Redirect plugin stores this
	 * other form plugins such as twitter or paypal may need to query the session to perform the final redirect
	 * once the user has returned from those sites.
	 * @return	string	the session key to store redirect information (note: ends in '.')
	 */
	public function getRedirectContext()
	{
		return 'com_fabrik.form.' . $this->getId() . '.redirect.';

	}

	/**
	 *
	 * Resets cached form data.
	 *
	 * @param bool $unset_groups also reset group and element model cached data
	 */
	public function unsetData($unset_groups = false)
	{
		unset($this->_data);
		unset($this->query);
		if ($unset_groups)
		{
			// $$$ hugh - unset group published elements list, and clear each
			// element's default data.  Needed from content plugin, otherwise if
			// we render the same form more than once with different rowid's, we end up
			// rendering the first copy's element data X times.
			// Not sure if we need to actually unset the group published elements list,
			// but for the moment I'm just using a Big Hammer to get the content plugin working!
			$groups = $this->getGroupsHiarachy();
			foreach ($groups as $groupModel)
			{
				$groupModel->resetPublishedElements();
				$elementModels = $groupModel->getPublishedElements();
				foreach ($elementModels as $elementModel)
				{
					$elementModel->reset();
				}
			}
		}
	}

	/**
	 *
	 * Reset form's cached data, i.e. from content plugin, where we may be rendering the same
	 * form twice, with different row data.
	 */
	public function reset()
	{
		$this->unsetData(true);
	}
}

?>