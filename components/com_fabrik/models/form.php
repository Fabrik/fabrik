<?php
/**
 * Fabrik Form Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Uploader;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.model');
require_once 'fabrikmodelform.php';

/**
 * Fabrik Form Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikFEModelForm extends FabModelForm
{
	/**
	 * id
	 * @var int
	 */
	public $id = null;

	/**
	 * Set to -1 if form in ajax module, set to 1+ if in package
	 *
	 * @var int
	 */
	public $packageId = 0;

	/**
	 * Form's group elements
	 *
	 * @var array
	 */
	protected $elements = null;

	/**
	 * List model associated with form
	 *
	 * @var FabrikFEModelList
	 */
	protected $listModel = null;

	/**
	 * Group ids that are actually tablejoins [groupid->joinid]
	 *
	 * @var array
	 */
	public $aJoinGroupIds = array();

	/**
	 * If editable if 0 then show view only version of form
	 *
	 * @var bol true
	 */
	public $editable = true;

	/**
	 * Validation rule classes
	 *
	 * @var array
	 */
	protected $validationRuleClasses = null;

	/**
	 * The form running as a mambot or module(true)
	 *
	 * @var bool
	 */
	public $isMambot = false;

	/**
	 * Join objects for the form
	 *
	 * @var array
	 */
	protected $aJoinObjs = array();

	/**
	 * Concat string to create full element names
	 *
	 * @var string
	 */
	public $joinTableElementStep = '___';

	/**
	 * Parameters
	 *
	 * @var Registry
	 */
	protected $params = null;

	/**
	 * Row id to submit
	 *
	 * @var int
	 */
	public $rowId = null;

	/**
	 * Submitted as ajax
	 *
	 * @since 3.0
	 * @var bool
	 */
	public $ajax = null;

	/**
	 * Form table
	 *
	 * @var JTable
	 */
	public $form = null;

	/**
	 * Last current element found in hasElement()
	 *
	 * @var object
	 */
	protected $currentElement = null;

	/**
	 * @var JFilterInput
	 */
	protected $filter;

	/**
	 * If true encase table and element names with "`" when getting element list
	 *
	 * @var bool
	 */
	protected $addDbQuote = false;

	/**
	 * Form Data
	 *
	 * @var array
	 */
	public $formData = null;

	/**
	 * Form errors
	 *
	 * @var array
	 */
	public $errors = array();

	/**
	 * Uploader helper
	 *
	 * @var Uploader
	 */
	protected $uploader = null;

	/**
	 * Pages (array containing group ids for each page in the form)
	 *
	 * @var array
	 */
	protected $pages = null;

	/**
	 * Session model deals with storing incomplete pages
	 *
	 * @var FabrikFEModelFormsession
	 */
	public $sessionModel = null;

	/**
	 * Modified data by any validation rule that uses replace functionality
	 *
	 * @var array
	 */
	public $modifiedValidationData = null;

	/**
	 * Group Models
	 *
	 * @var array
	 */
	public $groups = null;

	/**
	 * Store the form's previous data when processing
	 *
	 * @var array
	 */
	public $origData = null;

	/**
	 * Stores elements not shown in the list view
	 * @var array
	 */
	protected $elementsNotInList = null;

	/**
	 * Form data
	 *
	 * @var array
	 */
	public $data = null;

	/**
	 * Form data - ready for use in template. Contains HTML output for listname___elementname
	 * and raw value for listname___elementname_raw
	 *
	 * @var array
	 */
	public $tmplData = array();

	/**
	 * Form data - keys use the full element name (listname___elementname)
	 * @var array
	 */
	public $formDataWithTableName = null;

	/**
	 * Should the form store the main row? Set to false in juser
	 * plugin if fabrik table is also #__users
	 *
	 * @var bool
	 */
	public $storeMainRow = true;

	/**
	 * Query used to load form record.
	 *
	 * @var string
	 */
	public $query = null;

	/**
	 * Specifies element name that have been overridden from a form plugin,
	 * so encrypted RO data should be ignored
	 *
	 * @var array
	 */
	protected $pluginUpdatedElements = array();

	/**
	 * Linked fabrik lists
	 *
	 * @var array
	 */
	protected $linkedFabrikLists = null;

	/**
	 * Are we copying a row?  i.e. using form's Copy button.  Plugin manager needs to know.
	 *
	 * @var bool
	 */
	public $copyingRow = false;

	/**
	 * Container string for form plugin JS ini code
	 *
	 * @since 3.1b
	 *
	 * @var array
	 */
	public $formPluginJS = array();

	/**
	 * Form plugin files to load
	 *
	 * @since 3.1b
	 *
	 * @var array
	 */
	public $formPluginShim = array();

	/**
	 * JS options on load, only used when calling onJSOpts plugin
	 * so plugin code can access and modify them
	 *
	 * @since 3.2
	 *
	 * @var array
	 */
	public $jsOpts = null;

	/**
	 * @var array
	 */
	public $_origData;

	/**
	 * Original Row id before form is saved.
	 *
	 * @var string
	 */
	public $origRowId;

	/**
	 * Is the form being posted via ajax.
	 *
	 * @var bool
	 */
	protected $ajaxPost = false;

	/**
	 * Posted form data with full names?
	 *
	 * @var array
	 */
	public $fullFormData = array();

	/**
	 * Use this lastInsertId to store the main table's lastInsertId, so we can use this rather
	 * than the list model lastInsertId, which could be for the last joined table rather than
	 * the form's main table.
	 *
	 * @since 3.3
	 *
	 * @var mixed
	 */
	public $lastInsertId = null;

	/**
	 * Form plugins can set this to trigger a validation fail which isn't specific to an element
	 *
	 * @since 3.4
	 *
	 * @var mixed
	 */
	public $formErrorMsg = null;

	/**
	 * Form sessionData
	 *
	 * @var array
	 */
	public $sessionData = null;

	/**
	 * cache tmpl name
	 *
	 * @since 3.7
	 *
	 * @var string
	 */
	public $tmpl = null;

	/**
	 * did we find any data in getData()
	 *
	 * @since 3.8
	 *
	 * @var bool
	 */
	public $noData = false;

	/**
	 * Constructor
	 *
	 * @param   array  $config  An array of configuration options (name, state, dbo, table_path, ignore_request).
	 *
	 * @since       1.5
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$id = $this->app->input->getInt('formid', $usersConfig->get('formid'));
		$this->setId($id);
	}

	/**
	 * Method to set the form id
	 *
	 * @param   int  $id  list ID number
	 *
	 * @since 3.0
	 *
	 * @return  void
	 */
	public function setId($id)
	{
		// Set new form ID
		$this->id = $id;
		$this->setState('form.id', $id);

		// $$$ rob not sure why but we need this getState() here when assigning id from admin view
		$this->getState();
	}

	/**
	 * Set row id
	 *
	 * @param   string  $id  primary key value
	 *
	 * @since   3.0.7
	 *
	 * @return  void
	 */
	public function setRowId($id)
	{
		$this->rowId = $id;
	}

	/**
	 * Method to get the form id
	 *
	 * @return  int
	 */
	public function getId()
	{
		return $this->getState('form.id');
	}

	/**
	 * Get form table (alias to getTable())
	 *
	 * @return  FabTable  form table
	 */
	public function getForm()
	{
		return $this->getTable();
	}

	/**
	 * Checks if the params object has been created and if not creates and returns it
	 *
	 * @return  object  params
	 */
	public function getParams()
	{
		if (!isset($this->params))
		{
			$form = $this->getForm();
			$this->params = new Registry($form->params);
		}

		return $this->params;
	}

	/**
	 * Should the form load up rowid=-1 usekey=foo
	 *
	 * @param   string  $priority  Request priority menu or request
	 *
	 * @return boolean
	 */
	protected function isUserRowId($priority = 'menu')
	{
		$rowId = FabrikWorker::getMenuOrRequestVar('rowid', '', $this->isMambot, $priority);

		return $rowId === '-1' || $rowId === ':1';
	}

	/**
	 * Makes sure that the form is not viewable based on the list's access settings
	 *
	 * Also sets the form's editable state, if it can record in to a db table
	 *
	 * @return  int  0 = no access, 1 = view only , 2 = full form view, 3 = add record only
	 */
	public function checkAccessFromListSettings()
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

		$data = $this->getData();
		$ret = 0;

		if ($listModel->canViewDetails(FArrayHelper::toObject($data)))
		{
			$ret = 1;
		}
		else
		{
			if ($this->app->input->get('view', 'form') == 'details')
			{
				return 0;
			}
		}

		//$isUserRowId = $this->isUserRowId();

		/* New form can we add?
		 *
		 * NOTE - testing to see if $data exists rather than looking at rowid to decide if editing, as when using
		 * rowid=-1, things get funky, as rowid is never empty, even for new form, as it's set to user id
		 */
		if (empty($data) || !array_key_exists('__pk_val', $data) || empty($data['__pk_val']))
		{
			if ($listModel->canAdd())
			{
				$ret = 3;
			}
			else if ($listModel->canEdit($data))
			{
				$ret = 2;
			}
		}
		else
		{
			// Editing from - can we edit
			if ($listModel->canEdit($data))
			{
				$ret = 2;
			}
		}
		// If no access (0) or read only access (1) set the form to not be editable
		$editable = ($ret <= 1) ? false : true;
		$this->setEditable($editable);

		if ($this->app->input->get('view', 'form') == 'details')
		{
			$this->setEditable(false);
		}

		return $ret;
	}

	/**
	 * Get the template name
	 *
	 * @since 3.0
	 *
	 * @return string tmpl name
	 */
	public function getTmpl()
	{
		if (!isset($this->tmpl))
		{
			$input       = $this->app->input;
			$params      = $this->getParams();
			$item        = $this->getForm();
			$tmpl        = '';
			$default     = FabrikWorker::j3() ? 'bootstrap' : 'default';
			$jTmplFolder = FabrikWorker::j3() ? 'tmpl' : 'tmpl25';
			$document    = JFactory::getDocument();

			if ($document->getType() === 'pdf')
			{
				$tmpl = $params->get('pdf_template', '') !== '' ? $params->get('pdf_template') : $default;
			}
			else
			{
				if ($this->app->isAdmin())
				{
					$tmpl = $this->isEditable() ? $params->get('admin_form_template') : $params->get('admin_details_template');
					$tmpl = $tmpl == '' ? $default : $tmpl;
				}

				if ($tmpl == '')
				{
					if ($this->isEditable())
					{
						$tmpl = $item->form_template == '' ? $default : $item->form_template;
					}
					else
					{
						$tmpl = $item->view_only_template == '' ? $default : $item->view_only_template;
					}
				}

				$tmpl = FabrikWorker::getMenuOrRequestVar('fabriklayout', $tmpl, $this->isMambot);
			}

			// Finally see if the options are overridden by a querystring var
			$baseTmpl = $tmpl;
			$tmpl     = $input->get('layout', $tmpl);

			// Test it exists - otherwise revert to baseTmpl tmpl
			$folder = $this->isEditable() ? 'form' : 'details';

			if (!JFolder::exists(JPATH_SITE . '/components/com_fabrik/views/' . $folder . '/' . $jTmplFolder . '/' . $tmpl))
			{
				$tmpl = $baseTmpl;
			}

			$this->isEditable() ? $item->form_template = $tmpl : $item->view_only_template = $tmpl;
			$this->tmpl = $tmpl;
		}

		return $this->tmpl;
	}

	/**
	 * loads form's css files
	 * Checks : custom css file, template css file. Including them if found
	 *
	 * @return  void
	 */
	public function getFormCss()
	{
		$input = $this->app->input;
		$jTmplFolder = FabrikWorker::j3() ? 'tmpl' : 'tmpl25';
		$tmpl = $this->getTmpl();
		$v = $this->isEditable() ? 'form' : 'details';

		// Check for a form template file (code moved from view)
		if ($tmpl != '')
		{
			$qs = '?c=' . $this->getId();
			$qs .= '&amp;rowid=' . $this->getRowId();

			/* $$$ need &amp; for pdf output which is parsed through xml parser otherwise fails
			 * If FabrikHelperHTML::styleSheetajax loaded then don't do &amp;
			 */
			$view = $this->isEditable() ? 'form' : 'details';

			if (FabrikHelperHTML::cssAsAsset())
			{
				$qs .= '&view=' . $v;
				$qs .= '&rowid=' . $this->getRowId();
			}
			else
			{
				$qs .= '&amp;view=' . $v;
				$qs .= '&amp;rowid=' . $this->getRowId();
			}

			$tmplPath = 'templates/' . $this->app->getTemplate() . '/html/com_fabrik/' . $view . '/' . $tmpl . '/template_css.php' . $qs;

			if (!FabrikHelperHTML::stylesheetFromPath($tmplPath))
			{
				FabrikHelperHTML::stylesheetFromPath('components/com_fabrik/views/' . $view . '/' . $jTmplFolder . '/' . $tmpl . '/template_css.php' . $qs);
			}

			/* $$$ hugh - as per Skype convos with Rob, decided to re-instate the custom.css convention.  So I'm adding two files:
			 * custom.css - for backward compat with existing 2.x custom.css
			 * custom_css.php - what we'll recommend people use for custom css moving forward.
			 */

			if (!FabrikHelperHTML::stylesheetFromPath('templates/' . $this->app->getTemplate() . '/html/com_fabrik/' . $view . '/' . $tmpl . '/custom.css' . $qs))
			{
				FabrikHelperHTML::stylesheetFromPath('components/com_fabrik/views/' . $view . '/' . $jTmplFolder . '/' . $tmpl . '/custom.css' . $qs);
			}

			$path = 'templates/' . $this->app->getTemplate() . '/html/com_fabrik/' . $view . '/' . $tmpl . '/custom_css.php' . $qs;

			if (!FabrikHelperHTML::stylesheetFromPath($path))
			{
				$displayData              = new stdClass;
				$displayData->view        = $view;
				$displayData->tmpl        = $tmpl;
				$displayData->qs          = $qs;
				$displayData->jTmplFolder = $jTmplFolder;
				$displayData->formModel   = $this;
				$layout = $this->getLayout('form.fabrik-custom-css-qs');
				$path = $layout->render($displayData);

				FabrikHelperHTML::stylesheetFromPath($path);
			}
		}

		if ($this->app->isAdmin() && $input->get('tmpl') === 'components')
		{
			FabrikHelperHTML::stylesheet('administrator/templates/system/css/system.css');
		}
	}

	/**
	 * Load the JS files into the document
	 *
	 * @param   array  &$scripts  Js script sources to load in the head
	 *
	 * @return null
	 */
	public function getCustomJsAction(&$scripts)
	{
		// $$$ hugh - added ability to use form_XX, as am adding custom list_XX
		$view = $this->isEditable() ? 'form' : 'details';

		/**
		 * $$$ hugh - need to use an assoc key name for the scripts array, as it gets used in the requirejs
		 * to pass in as a function arg, which then blows up with "unexpected number" if we don't use a key name
		 */
		$scriptsKey = $view . '_' . $this->getId();

		if (JFile::exists(COM_FABRIK_FRONTEND . '/js/' . $this->getId() . '.js'))
		{
			$scripts[$scriptsKey] = 'components/com_fabrik/js/' . $this->getId() . '.js';
		}
		elseif (JFile::exists(COM_FABRIK_FRONTEND . '/js/' . $view . '_' . $this->getId() . '.js'))
		{
			$scripts[$scriptsKey] = 'components/com_fabrik/js/' . $view . '_' . $this->getId() . '.js';
		}
	}

	/**
	 * Set the browser title
	 *
	 * @param   string  $title  Default browser title set by menu items' 'page_title' property
	 *
	 * @return	string	Browser title
	 */
	public function getPageTitle($title = '')
	{
		$title = $title == '' ? $this->getLabel() : $title;
		$groups = $this->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();

				if ($element->use_in_page_title == '1')
				{
					$title .= ' ' . $elementModel->getTitlePart($this->data);
				}
			}
		}

		return $title;
	}

	/**
	 * Compares the forms table with its groups to see if any of the groups are in fact table joins
	 *
	 * @param   array  $joins  tables joins
	 *
	 * @return	array	array(group_id =>join_id)
	 */
	public function getJoinGroupIds($joins = null)
	{
		$listModel = $this->getlistModel();

		if (is_null($joins))
		{
			$joins = $listModel->getJoins();
		}

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

		$this->aJoinGroupIds = $arJoinGroupIds;

		return $arJoinGroupIds;
	}

	/**
	 * Gets the javascript actions the forms elements
	 *
	 * @return  array  javascript actions
	 */
	public function getJsActions()
	{
		if (isset($this->jsActions))
		{
			return $this->jsActions;
		}

		$this->jsActions = array();
		$db = FabrikWorker::getDbo(true);
		$aJsActions = array();
		$aElIds = array();
		$groups = $this->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				/* $$$ hugh - only needed getParent when we weren't saving changes to parent params to child
				 * which we should now be doing ... and getParent() causes an extra table lookup for every child
				 * element on the form.
				 */
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
		}
		else
		{
			$res = array();
		}

		if (is_array($res))
		{
			foreach ($res as $r)
			{
				// Merge the js attributes back into the array
				$a = json_decode($r->params);

				foreach ($a as $k => $v)
				{
					$r->$k = $v;
				}

				unset($r->params);

				if (!isset($r->js_published) || (int) $r->js_published === 1)
				{
					$this->jsActions[$r->element_id][] = $r;
				}
			}
		}

		return $this->jsActions;
	}

	/**
	 * Test to try to load all group data in one query and then bind that data to group table objects
	 * in getGroups()
	 *
	 * @return  array
	 */
	public function getPublishedGroups()
	{
		$db = FabrikWorker::getDbo(true);

		if (!isset($this->_publishedformGroups) || empty($this->_publishedformGroups))
		{
			$params = $this->getParams();
			$query = $db->getQuery(true);
			$query->select(' *, fg.group_id AS group_id, RAND() AS rand_order')
			->from('#__{package}_formgroup AS fg')
			->join('INNER', '#__{package}_groups as g ON g.id = fg.group_id')
			->where('fg.form_id = ' . (int) $this->getId() . ' AND published = 1');

			if ($params->get('randomise_groups') == 1)
			{
				$query->order('rand_order');
			}
			else
			{
				$query->order('fg.ordering');
			}

			$db->setQuery($query);
			$sql = (string)$query;
			$groups = $db->loadObjectList('group_id');
			$this->_publishedformGroups = $this->mergeGroupsWithJoins($groups);
		}

		return $this->_publishedformGroups;
	}

	/**
	 * Get the ids of all the groups in the form
	 *
	 * @return  array  group ids
	 */
	public function getGroupIds()
	{
		$groups = $this->getPublishedGroups();

		return array_keys($groups);
	}

	/**
	 * Merge in Join Ids into an array of groups
	 *
	 * @param   array  $groups  form groups
	 *
	 * @return  array
	 */
	private function mergeGroupsWithJoins($groups)
	{
		$db = FabrikWorker::getDbo(true);
		$form = $this->getForm();

		if ($form->record_in_database)
		{
			$listModel = $this->getListModel();
			$listId = (int) $listModel->getId();

			if (is_object($listModel) && $listId !== 0)
			{
				$query = $db->getQuery(true);
				$query->select('g.id, j.id AS joinid')->from('#__{package}_joins AS j')
					->join('INNER', '#__{package}_groups AS g ON g.id = j.group_id')->where('list_id = ' . $listId . ' AND g.published = 1');

				// Added as otherwise you could potentially load a element joinid as a group join id. 3.1
				$query->where('j.element_id = 0');
				$db->setQuery($query);
				$joinGroups = $db->loadObjectList('id');

				foreach ($joinGroups as $k => $o)
				{
					if (array_key_exists($k, $groups))
					{
						$groups[$k]->join_id = $o->joinid;
					}
				}
			}
		}

		return $groups;
	}

	/**
	 * Get the forms published group objects
	 *
	 * @return  FabrikFEModelGroup[]  Group model objects with table row loaded
	 */
	public function getGroups()
	{
		if (!isset($this->groups))
		{
			$this->groups = array();
			$listModel = $this->getListModel();
			$groupModel = JModelLegacy::getInstance('Group', 'FabrikFEModel');
			$groupData = $this->getPublishedGroups();

			foreach ($groupData as $id => $groupD)
			{
				$thisGroup = clone ($groupModel);
				$thisGroup->setId($id);
				$thisGroup->setContext($this, $listModel);

				// $$ rob 25/02/2011 this was doing a query per group - pointless as we bind $groupD to $row afterwards
				// $row = $thisGroup->getGroup();
				$row = FabTable::getInstance('Group', 'FabrikTable');
				$row->bind($groupD);
				$thisGroup->setGroup($row);

				if ($row->published == 1)
				{
					$this->groups[$id] = $thisGroup;
				}
			}
		}

		return $this->groups;
	}

	/**
	 * Gets each element in the form along with its group info
	 *
	 * @param   bool  $excludeUnpublished  included unpublished elements in the result
	 *
	 * @return  array  element objects
	 */
	public function getFormGroups($excludeUnpublished = true)
	{
		$params = $this->getParams();
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query
			->select(
				'*, #__{package}_groups.params AS gparams, #__{package}_elements.id as element_id
		, #__{package}_groups.name as group_name, RAND() AS rand_order')->from('#__{package}_formgroup')
			->join('LEFT', '#__{package}_groups	ON #__{package}_formgroup.group_id = #__{package}_groups.id')
			->join('LEFT', '#__{package}_elements ON #__{package}_groups.id = #__{package}_elements.group_id')
			->where('#__{package}_formgroup.form_id = ' . (int) $this->getState('form.id'));

		if ($excludeUnpublished)
		{
			$query->where('#__{package}_elements.published = 1');
		}

		if ($params->get('randomise_groups') == 1)
		{
			$query->order('rand_order, #__{package}_elements.ordering');
		}
		else
		{
			$query->order('#__{package}_formgroup.ordering, #__{package}_formgroup.group_id, #__{package}_elements.ordering');
		}

		$db->setQuery($query);
		$groups = $db->loadObjectList();
		$this->elements = $groups;

		return $groups;
	}

	/**
	 * Similar to getFormGroups() except that this returns a data structure of
	 * form
	 * --->group
	 * -------->element
	 * -------->element
	 * --->group
	 * if run before then existing data returned
	 *
	 * @return  FabrikFEModelGroup[]  Group & element objects
	 */
	public function getGroupsHiarachy()
	{
		if (!isset($this->groups))
		{
			$this->getGroups();
			$this->groups = FabrikWorker::getPluginManager()->getFormPlugins($this);
		}

		return $this->groups;
	}

	/**
	 * Get an list of elements that aren't shown in the table view
	 *
	 * @return  array  of element table objects
	 */
	public function getElementsNotInTable()
	{
		if (!isset($this->elementsNotInList))
		{
			$this->elementsNotInList = array();
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
							$this->elementsNotInList[] = $element;
						}
					}
				}
			}
		}

		return $this->elementsNotInList;
	}

	/**
	 * This checks to see if the form has a file upload element
	 * and returns the correct encoding type for the form
	 *
	 * @return  string  form encoding type
	 */
	public function getFormEncType()
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
	 * Get the plugin manager
	 *
	 * @deprecated use return FabrikWorker::getPluginManager(); instead since 3.0b
	 *
	 * @return  object  plugin manager
	 */

	public function getPluginManager()
	{
		return FabrikWorker::getPluginManager();
	}

	/**
	 * When the form is submitted we want to get the original record it
	 * is updating - this is used in things like the file upload element
	 * to check for changes in uploaded files and process the difference
	 *
	 * @return	array
	 */
	protected function setOrigData()
	{
		$input = $this->app->input;

		if ($this->isNewRecord() || !$this->getForm()->record_in_database)
		{
			$this->_origData = array(new stdClass);
		}
		else
		{
			/*
			 * $$$ hugh - when loading origdata on editing of a rowid=-1/usekey form,
			 * the rowid will be set to the actual form tables's rowid, not the userid,
			 * so we need to unset 'usekey', otherwise we end up with the wrong row.
			 * I thought we used to take care of this elsewhere?
			 *
			 * $$$ 7/25/2017 - don't think this is true any more?
			 *
			 * $$$ 9/5/2017 = yup, still seems to be necesaasy, definitely when submitting with a juser plugin.  So
			 * reverted changes made on 7/25, and will keep an eye out for situations where doing this causes problems.
			 */

			$isUserRow = $this->isUserRowId();

			if ($isUserRow)
			{
				$origUseKey = $input->get('usekey', '');
				$input->set('usekey', '');
			}

			$listModel = $this->getListModel();
			$fabrikDb = $listModel->getDb();
			$sql = $this->buildQuery();
			$fabrikDb->setQuery($sql);
			$this->_origData = $fabrikDb->loadObjectList();

			if ($isUserRow)
			{
				$input->set('usekey', $origUseKey);
			}
		}
	}

	/**
	 * Get the form record's original data - before any alterations were made to it
	 * in the form
	 *
	 * @return  array
	 */
	public function getOrigData()
	{
		if (!isset($this->_origData))
		{
			$this->setOrigData();
		}

		return $this->_origData;
	}

	/**
	 * test if orig data is empty.  Made this a function, as it's not a simple test
	 * for empty(), and code outside the model shouldn't need to know it'll be a one
	 * entry array with an empty stdClass in it.
	 *
	 * @return  bool
	 */
	public function origDataIsEmpty()
	{
		if (!isset($this->_origData))
		{
			$this->setOrigData();
		}

		return (empty($this->_origData) || (count($this->_origData) == 1 && count((array) $this->_origData[0]) == 0));
	}

	/**
	 * Are we copying a row?  Usually set in controller process().
	 *
	 * @param   bool  $set  if true, set copyingRow to true
	 *
	 * @return	bool
	 */
	public function copyingRow($set = false)
	{
		if ($set)
		{
			$this->copyingRow = true;
		}

		return $this->copyingRow;
	}

	/**
	 * Processes the form data and decides what action to take
	 *
	 * @return  bool  false if one of the plugins returns an error otherwise true
	 */
	public function process()
	{
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark('process: start') : null;
		$input = $this->app->input;

		error_reporting(error_reporting() ^ (E_WARNING | E_NOTICE));
		@set_time_limit(300);
		$form = $this->getForm();
		$pluginManager = FabrikWorker::getPluginManager();

		$sessionModel = JModelLegacy::getInstance('Formsession', 'FabrikFEModel');
		$sessionModel->setFormId($this->getId());
		$sessionModel->setRowId($this->rowId);
		/* $$$ rob rowId can be updated by jUser plugin so plugin can use check (for new/edit)
		 * now looks at origRowId
		 */
		$this->origRowId = $this->rowId;

		JDEBUG ? $profiler->mark('process, getGroupsHiarachy: start') : null;
		$this->getGroupsHiarachy();

		if ($form->record_in_database == '1')
		{
			JDEBUG ? $profiler->mark('process, setOrigData: start') : null;
			$this->setOrigData();
		}

		/*
		 * $$$ hugh - we do this prior to processToDb(), but turns out we need formDataWithTableName in
		 * some plugins, like 'php', which run $formModel->getProcessData().  But it's kind of a chicken
		 * and egg, because those same plugins my change $formData.  Anyway, only solution for now is
		 * set up $this->formDataWithTaleName here, so they at least have the posted data to work with,
		 * then do it again after all the plugins have run.  So, rule of thumb ... plugins running onBeforeProcess
		 * or onBeforeStore need to modify formData, not formDataWithTableName.
		 */
		$this->formDataWithTableName = $this->formData;

		JDEBUG ? $profiler->mark('process, onBeforeProcess plugins: start') : null;
		if (in_array(false, $pluginManager->runPlugins('onBeforeProcess', $this)))
		{
			return false;
		}

		$this->removeEmptyNoneJoinedGroupData($this->formData);
		JDEBUG ? $profiler->mark('process, setFormData: start') : null;
		$this->setFormData();

		JDEBUG ? $profiler->mark('process, _doUpload: start') : null;
		if (!$this->_doUpload())
		{
			return false;
		}

		/** $$$ rob 27/10/2011 - moved above _doUpload as code in there is trying to update formData which is not yet set
		 * this->setFormData();
		 */

		JDEBUG ? $profiler->mark('process, onBeforeStore plugins: start') : null;
		if (in_array(false, $pluginManager->runPlugins('onBeforeStore', $this)))
		{
			return false;
		}

		$this->formDataWithTableName = $this->formData;

		if ($form->record_in_database == '1')
		{
			$rowid = $this->processToDB();
			/*
			 * I want to add the following, but have a feeling some things will break if I do.
			 * Currently when adding a new form, getRowId() will never give you the new rowid,
			 * which kinda sucks.  But ... I'm pretty sure if I set it, then there's existing code
			 * which will break it does yield the new rowid after submission.  Need to test this
			 * thoroughly before enabling it.
			 */
			/*
			if ($this->getRowId() === '')
			{
				$this->setRowId($rowid);
			}
			*/
		}

		// Clean the cache.
		$cache = JFactory::getCache($input->get('option'));
		$cache->clean();

		// $$$rob run this before as well as after onAfterProcess (ONLY for redirect plugin)
		// so that any redirect urls are available for the plugin (e.g twitter)
		JDEBUG ? $profiler->mark('process, onLastProcess plugins: start') : null;
		$pluginManager->runPlugins('onLastProcess', $this);

		JDEBUG ? $profiler->mark('process, onAfterProcess plugins: start') : null;
		if (in_array(false, $pluginManager->runPlugins('onAfterProcess', $this)))
		{
			// $$$ rob this no longer stops default redirect (not needed any more)
			// returning false here stops the default redirect occurring
			return false;
		}
		// Need to remove the form session before redirect plugins occur
		$sessionModel->remove();

		// $$$rob used ONLY for redirect plugins
		JDEBUG ? $profiler->mark('process, onLastProcess plugins: start') : null;
		if (in_array(false, $pluginManager->runPlugins('onLastProcess', $this)))
		{
			// $$$ rob this no longer stops default redirect (not needed any more)
			// returning false here stops the default redirect occurring
			return false;
		}

		// Clean both admin and front end cache.
		parent::cleanCache('com_' . $this->package, 1);
		parent::cleanCache('com_' . $this->package, 0);

		JDEBUG ? $profiler->mark('process: end') : null;

		return true;
	}

	/**
	 * Perform file uploads
	 *
	 * @return bool
	 */
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
	 * Update the data that gets posted via the form and stored by the form
	 * model. Used in elements to modify posted data see file upload
	 *
	 * @param   string  $key          in key.dot.format to set a recursive array
	 * @param   string  $val          value to set to
	 * @param   bool    $update_raw   automatically update _raw key as well
	 * @param   bool    $override_ro  update data even if element is RO
	 *
	 * @return  void
	 */
	public function updateFormData($key, $val, $update_raw = false, $override_ro = false)
	{
		if (strstr($key, '.'))
		{
			$nodes = explode('.', $key);
			$count = count($nodes);
			$pathNodes = $count - 1;

			if ($pathNodes < 0)
			{
				$pathNodes = 0;
			}

			$ns = &$this->formData;

			for ($i = 0; $i <= $pathNodes; $i++)
			{
				// If any node along the registry path does not exist, create it
				if (!isset($ns[$nodes[$i]]))
				{
					$ns[$nodes[$i]] = array();
				}

				$ns = &$ns[$nodes[$i]];
			}

			$ns = $val;

			// $$$ hugh - changed name of $ns, as re-using after using it to set by reference was borking things up!
			$nsTable = &$this->formDataWithTableName;

			for ($i = 0; $i <= $pathNodes; $i++)
			{
				// If any node along the registry path does not exist, create it
				if (!isset($nsTable[$nodes[$i]]))
				{
					$nsTable[$nodes[$i]] = array();
				}

				$nsTable = &$nsTable[$nodes[$i]];
			}

			$nsTable = $val;

			// $$$ hugh - changed name of $ns, as re-using after using it to set by reference was borking things up!
			$nsFull = &$this->fullFormData;

			for ($i = 0; $i <= $pathNodes; $i++)
			{
				// If any node along the registry path does not exist, create it
				if (!isset($nsFull[$nodes[$i]]))
				{
					$nsFull[$nodes[$i]] = array();
				}

				$nsFull = &$nsFull[$nodes[$i]];
			}

			$nsFull = $val;

			// $$$ hugh - FIXME - nope, this won't work!  We don't know which path node is the element name.
			// $$$ hugh again - should now work, with little preg_replace hack, if last part is numeric, then second to last will be element name
			if ($update_raw)
			{
				if (preg_match('#\.\d+$#', $key))
				{
					$key = preg_replace('#(.*)(\.\d+)$#', '$1_raw$2', $key);
				}
				else
				{
					$key .= '_raw';
				}

				$nodes = explode('.', $key);
				$count = count($nodes);
				$pathNodes = $count - 1;

				if ($pathNodes < 0)
				{
					$pathNodes = 0;
				}

				$nsRaw = &$this->formData;

				for ($i = 0; $i <= $pathNodes; $i++)
				{
					// If any node along the registry path does not exist, create it
					if (!isset($nsRaw[$nodes[$i]]))
					{
						$nsRaw[$nodes[$i]] = array();
					}

					$nsRaw = &$nsRaw[$nodes[$i]];
				}

				$nsRaw = $val;

				$nsRawFull = $this->fullFormData;

				for ($i = 0; $i <= $pathNodes; $i++)
				{
					// If any node along the registry path does not exist, create it
					if (!isset($nsRawFull[$nodes[$i]]))
					{
						$nsRawFull[$nodes[$i]] = array();
					}

					$nsRawFull = &$nsRawFull[$nodes[$i]];
				}

				$nsRawFull = $val;
			}
		}
		else
		{
			if (isset($this->formData))
			{
				$this->formData[$key] = $val;
				$this->formDataWithTableName[$key] = $val;
			}
			// Check if set - for case where you have a fileupload element & confirmation plugin - when plugin is trying to update non-existent data
			if (isset($this->fullFormData))
			{
				$this->fullFormData[$key] = $val;
			}
			/*
			 * Need to allow RO (encrypted) elements to be updated.  Consensus is that
			 * we should actually modify the actual encrypted element in the $_REQUEST,
			 * but turns out this is a major pain in the butt (see _cryptViewOnlyElements() in the
			 * form view for details!).  Main problem is we need to know if it's a join and/or repeat group,
			 * which means loading up the element model.  So for now, just going to add the element name to a
			 * class array, $this->pluginUpdatedElements[], which we'll check in addDefaultDataFromRO()
			 * in the table model, or wherever else we need it.
			 */
			/*
			 if (array_key_exists('fabrik_vars', $_REQUEST)
			&& array_key_exists('querystring', $_REQUEST['fabrik_vars'])
			&& array_key_exists($key, $_REQUEST['fabrik_vars']['querystring'])) {
			$crypt = FabrikWorker::getCrypt();
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
				$this->pluginUpdatedElements[$key] = $val;
			}

			if ($update_raw)
			{
				$key .= '_raw';
				$this->formData[$key] = $val;
				$this->formDataWithTableName[$key] = $val;

				if (isset($this->fullFormData))
				{
					$this->fullFormData[$key] = $val;
				}

				if ($override_ro)
				{
					$this->pluginUpdatedElements[$key] = $val;
				}
			}
		}
	}

	/**
	 * Intended for use by things like PHP form plugin code, PHP validations, etc.,
	 * so folk don't have to access formData directly.
	 *
	 * @param   string  $fullName     full element name
	 * @param   bool    $raw          get raw data
	 * @param   mixed   $default      value
	 * @param   string  $repeatCount  repeat count if needed
	 *
	 * @since	3.0.6
	 *
	 * @return mixed
	 */
	public function getElementData($fullName, $raw = false, $default = '', $repeatCount = null)
	{
		$data = isset($this->formData) ? $this->formData : $this->data;
		$value = null;

		if ($raw)
		{
			$fullName .= '_raw';
		}
		// Simplest case, element name exists in main group
		if (is_array($data) && array_key_exists($fullName, $data))
		{
			$value = $data[$fullName];
		}
        /* Maybe we are being called from onAfterProcess hook, or somewhere else
         * running after store, when non-joined data names have been reduced to short
         * names in formData, so peek in formDataWithTableName
         */
        elseif (isset($this->formDataWithTableName) && array_key_exists($fullName, $this->formDataWithTableName))
        {
            $value = $this->formDataWithTableName[$fullName];
        }

		if (isset($value) && isset($repeatCount) && is_array($value))
		{
			$value = FArrayHelper::getValue($value, $repeatCount, $default);
		}

		// If we didn't find it, set to default
		if (!isset($value))
		{
			$value = $default;
		}

		return $value;
	}

	/**
	 * This will strip the html from the form data according to the
	 * filter settings applied from article manager->parameters
	 * see here - http://forum.joomla.org/index.php/topic,259690.msg1182219.html#msg1182219
	 *
	 * @return  array  form data
	 */
	public function &setFormData()
	{
		if (isset($this->formData))
		{
			return $this->formData;
		}

		list($this->dofilter, $this->filter) = FabrikWorker::getContentFilter();

		$this->ajaxPost = $this->app->input->getBool('fabrik_ajax');

		// Set up post data, and copy values to raw (for failed form submissions)
		$data = $_POST;
		$this->copyToRaw($data);

		/**
		 * $$$ hugh - quite a few places in code that runs after this want __pk_val,
		 * so if it doesn't exist, grab it from the PK element.
		 */
		if (!array_key_exists('__pk_val', $data))
		{
			/**
			 * $$$ hugh - There HAS to be an easier way of getting the PK element name, that doesn't involve calling getPrimaryKeyAndExtra(),
			 * which is a horribly expensive operation.
			 */
			$primaryKey = $this->getListModel()->getPrimaryKey(true);
			$data['__pk_val'] = FArrayHelper::getValue($data, $primaryKey . '_raw', FArrayHelper::getValue($data, $primaryKey, ''));
		}

		// Apply querystring values if not already in post (so qs values doesn't overwrite the submitted values for dbjoin elements)
		$data = array_merge($data, $_REQUEST);
		array_walk_recursive($data, array($this, '_clean'));

		// Set here so element can call formModel::updateFormData()
		$this->formData = $data;
		$this->fullFormData = $this->formData;
		$this->session->set('com_' . $this->package . '.form.data', $this->formData);

		return $this->formData;
	}

	/**
	 * Called from setFormData to clean up posted data from either ajax or posted form
	 * used in array_walk_recursive() method
	 *
	 * @param   mixed  &$item  (string or array)
	 *
	 * @return  void
	 */
	protected function _clean(&$item)
	{
		if (is_array($item))
		{
			array_walk_recursive($item, array($this, '_clean'));
		}
		else
		{
			if ($this->dofilter)
			{
				//$item = preg_replace('/%([0-9A-F]{2})/mei', "chr(hexdec('\\1'))", $item);
				$item = preg_replace_callback('/%([0-9A-F]{2})/mi',  function ($matches) { return chr(hexdec($matches[1])); }, $item);
				if ($this->ajaxPost)
				{
					$item = rawurldecode($item);
				}

				if ($this->dofilter)
				{
					@$item = $this->filter->clean($item);
				}
			}
			else
			{
				if ($this->ajaxPost)
				{
					$item = rawurldecode($item);
				}
			}
		}
	}

	/**
	 * Loop over elements and call their preProcess() method
	 *
	 * @return  void
	 */
	private function callElementPreProcess()
	{
		$input = $this->app->input;
		$repeatTotals = $input->get('fabrik_repeat_group', array(0), 'array');
		$groups = $this->getGroupsHiarachy();

		// Currently this is just used by calculation elements
		foreach ($groups as $groupModel)
		{
			$group = $groupModel->getGroup();
			$repeatedGroupCount = FArrayHelper::getValue($repeatTotals, $group->id, 0, 'int');
			$elementModels = $groupModel->getPublishedElements();

			for ($c = 0; $c < $repeatedGroupCount; $c++)
			{
				foreach ($elementModels as $elementModel)
				{
					$elementModel->preProcess($c);
				}
			}
		}
	}

	/**
	 * Without this the first groups repeat data was always being saved (as it was posted but hidden
	 * on the form.
	 *
	 * @param   array  &$data  posted form data
	 *
	 * @return  void
	 */
	protected function removeEmptyNoneJoinedGroupData(&$data)
	{
		$repeats = FArrayHelper::getValue($data, 'fabrik_repeat_group', array());
		$groups = $this->getGroups();

		foreach ($repeats as $groupId => $c)
		{
			if ($c == 0)
			{
				$group = $groups[$groupId];

				if ($group->isJoin())
				{
					continue;
				}

				$elements = $group->getPublishedElements();

				foreach ($elements as $elementModel)
				{
					$name = $elementModel->getElement()->name;
					$data[$name] = '';
					$data[$name . '_raw'] = '';
				}
			}
		}
	}

	/**
	 * Prepare the submitted form data for copying
	 *
	 * @return  string  Original records reference
	 */
	protected function prepareForCopy()
	{
		$listModel = $this->getListModel();
		$item = $listModel->getTable();
		$k = $item->db_primary_key;
		$k = FabrikString::safeColNameToArrayKey($k);
		$origId = FArrayHelper::getValue($this->formData, $k, '');

		// COPY function should create new records
		if (array_key_exists('Copy', $this->formData))
		{
			$this->rowId = '';
			$this->formData[$k] = '';
			$this->formData['rowid'] = '';
		}

		// set the repeat group counts (mutated from the base view code which creates the hidden input)
		$groups   = $this->getGroupsHiarachy();
		$fabrikRepeatGroup = array();

		foreach ($groups as $groupModel)
		{
			// Check if group is actually a table join
			$repeatGroup = 1;
			$foreignKey  = null;

			if ($groupModel->canRepeat())
			{
				if ($groupModel->isJoin())
				{
					$joinModel  = $groupModel->getJoinModel();
					$joinTable  = $joinModel->getJoin();

					if (is_object($joinTable))
					{
						$elementModels = $groupModel->getPublishedElements();
						reset($elementModels);
						$tmpElement        = current($elementModels);
						$smallerElHTMLName = $tmpElement->getFullName(true, false);
						$repeatGroup       = count($this->formData[$smallerElHTMLName]);
					}
				}
			}

			$groupModel->repeatTotal = $repeatGroup;
			$fabrikRepeatGroup[$groupModel->getId()] = $repeatGroup;
		}

		$this->app->input->set('fabrik_repeat_group', $fabrikRepeatGroup);

		return $origId;
	}

	/**
	 * As part of the form process we may need to update the referring url if making a copy
	 *
	 * @param   string  $origId    Original record ref
	 * @param   string  $insertId  New insert reference
	 *
	 * @return  void referrer
	 */
	protected function updateReferrer($origId, $insertId)
	{
		$input = $this->app->input;

		// Set the redirect page to the form's url if making a copy and set the id to the new insert id
		if (array_key_exists('Copy', $this->formData))
		{
			$u = str_replace('rowid=' . $origId, 'rowid=' . $insertId, $input->get('HTTP_REFERER', '', 'string'));
			$input->set('fabrik_referrer', $u);
		}
	}

	/**
	 * Set various request / input arrays with the main records insert id
	 *
	 * @param   string  $insertId  The records insert id
	 *
	 * @return  void
	 */
	public function setInsertId($insertId)
	{
		$input = $this->app->input;
		$listModel = $this->getListModel();
		$item = $listModel->getTable();
		$tmpKey = str_replace("`", "", $item->db_primary_key);
		$tmpKey = str_replace('.', '___', $tmpKey);
		$this->formData[$tmpKey] = $insertId;
		$this->formData[$tmpKey . '_raw'] = $insertId;
		$this->formData[FabrikString::shortColName($item->db_primary_key)] = $insertId;
		$this->formData[FabrikString::shortColName($item->db_primary_key) . '_raw'] = $insertId;

		$this->fullFormData[$tmpKey] = $insertId;
		$this->fullFormData[$tmpKey . '_raw'] = $insertId;
		$this->fullFormData['rowid'] = $insertId;
		$this->formData['rowid'] = $insertId;
		$this->formDataWithTableName[$tmpKey] = $insertId;
		$this->formDataWithTableName[$tmpKey . '_raw'] = $insertId;
		$this->formDataWithTableName['rowid'] = $insertId;

		$input->set($tmpKey, $insertId);
		$input->set('rowid', $insertId);

		// $$$ hugh - pretty sure we need to unset 'usekey' now, as it is not relevant to joined data,
		// and it messing with storeRow of joins
		$input->set('usekey', '');
	}

	/**
	 * Process groups when the form is submitted
	 *
	 * @param   int  $parentId  insert ID of parent table
	 *
	 * @return  void
	 */
	protected function processGroups($parentId = null)
	{
		$groupModels = $this->getGroups();

		foreach ($groupModels as $groupModel)
		{
			// Jaanus: if group is visible
			if ($groupModel->canView() && $groupModel->canEdit())
			{
				$groupModel->process($parentId);
			}
		}
	}

	/**
	 * Process individual elements when submitting the form
	 * Used for multi-select join elements which need to store data in
	 * related tables
	 *
	 * @since   3.1rc2
	 *
	 * @return  void
	 */
	protected function processElements()
	{
		$groups = $this->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$elementModel->onFinalStoreRow($this->formData);
			}
		}
	}

	/**
	 * Process the form to the database
	 *
	 * @return string Insert id
	 */
	public function processToDB()
	{
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark('processToDb: start') : null;

		$pluginManager = FabrikWorker::getPluginManager();
		$listModel = $this->getListModel();
		$origId = $this->prepareForCopy();
		$this->formData = $listModel->removeTableNameFromSaveData($this->formData, '___');

		JDEBUG ? $profiler->mark('processToDb, submitToDatabase: start') : null;
		$insertId = $this->storeMainRow ? $this->submitToDatabase($this->rowId) : $this->rowId;

		$this->updateReferrer($origId, $insertId);
		$this->setInsertId($insertId);

		// Store join data
		JDEBUG ? $profiler->mark('processToDb, processGroups: start') : null;
		$this->processGroups($insertId);

		// Enable db join checkboxes in repeat groups to save data
		JDEBUG ? $profiler->mark('processToDb, processElements: start') : null;
		$this->processElements();

		JDEBUG ? $profiler->mark('processToDb, onBeforeCalculations plugins: start') : null;

		if (in_array(false, $pluginManager->runPlugins('onBeforeCalculations', $this)))
		{
			return $insertId;
		}

		JDEBUG ? $profiler->mark('processToDb, doCalculations: start') : null;
		$this->listModel->doCalculations();

		JDEBUG ? $profiler->mark('processToDb: end') : null;

		return $insertId;
	}

	/**
	 * Saves the form data to the database
	 *
	 * @param   string  $rowId  If '' then insert a new row - otherwise update this row id
	 *
	 * @return	mixed	insert id (or rowid if updating existing row) if ok, else string error message
	 */
	protected function submitToDatabase($rowId = '')
	{
		$this->getGroupsHiarachy();
		$groups = $this->getGroupsHiarachy();
		$listModel = $this->getListModel();
		$listModel->encrypt = array();
		$data = array();

		foreach ($groups as $groupModel)
		{
			// Joined groups stored in groupModel::process();
			if ($groupModel->isJoin())
			{
				continue;
			}

			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				if ($elementModel->encryptMe())
				{
					$listModel->encrypt[] = $elementModel->getElement()->name;
				}
				// Following line added to fix importcsv where data from first row is used for every row.
				$elementModel->defaults = null;
				$elementModel->onStoreRow($data);
			}
		}

		$listModel = $this->getListModel();
		$listModel->setFormModel($this);
		$listModel->getTable();
		$listModel->storeRow($data, $rowId);
		$this->lastInsertId = $listModel->lastInsertId;
		$useKey = $this->app->input->get('usekey', '');

		if (!empty($useKey))
		{
			return $listModel->lastInsertId;
		}
		else
		{
			return ($rowId == '') ? $listModel->lastInsertId : $rowId;
		}
	}

	/**
	 * Get the form's list model
	 * (was getTable but that clashed with J1.5 func)
	 *
	 * @return  FabrikFEModelList  fabrik list model
	 */
	public function getListModel()
	{
		if (!isset($this->listModel))
		{
			$this->listModel = JModelLegacy::getInstance('List', 'FabrikFEModel');
			$item = $this->getForm();
			$this->listModel->loadFromFormId($item->id);
			$this->listModel->setFormModel($this);
		}

		return $this->listModel;
	}

	/**
	 * Get the class names for each of the validation rules
	 *
	 * @deprecated (was only used in element label)
	 *
	 * @return	array	(validationruleid => classname )
	 */
	public function loadValidationRuleClasses()
	{
		if (is_null($this->validationRuleClasses))
		{
			$validationRules = FabrikWorker::getPluginManager()->getPlugInGroup('validationrule');
			$classes = array();

			foreach ($validationRules as $rule)
			{
				$classes[$rule->name] = $rule->name;
			}

			$this->validationRuleClasses = $classes;
		}

		return $this->validationRuleClasses;
	}

	/**
	 * Give elements a way to re-instate data after a validation failure, for example upload elements,
	 * where the value won't be in the submitted data, in order to preserve state
	 **
	 * @return	void
	 */
	public function setValidationFailedData()
	{
		$this->getGroupsHiarachy();
		$groups = $this->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$elementModel->setValidationFailedData($this->formData);
			}
		}
	}

	/**
	 * Add in any encrypted stuff, in case we fail validation ...
	 * otherwise it won't be in $data when we rebuild the page.
	 * Need to do it here, so _raw fields get added in the next chunk 'o' code.
	 *
	 * @param   array  &$post  posted form data passed by reference
	 *
	 * @return	null
	 */
	public function addEncrytedVarsToArray(&$post)
	{
		if (array_key_exists('fabrik_vars', $_REQUEST) && array_key_exists('querystring', $_REQUEST['fabrik_vars']))
		{
			$groups = $this->getGroupsHiarachy();
			$crypt = FabrikWorker::getCrypt();
			$w = new FabrikWorker;

			foreach ($groups as $g => $groupModel)
			{
				$elementModels = $groupModel->getPublishedElements();

				foreach ($elementModels as $elementModel)
				{
					$elementModel->getElement();

					foreach ($_REQUEST['fabrik_vars']['querystring'] as $key => $encrypted)
					{
						if ($elementModel->getFullName(true, false) == $key)
						{
							/* 	$$$ rob - don't test for !canUse() as confirmation plugin dynamically sets this
							 * if ($elementModel->canView())
							 * $$$ hugh - testing adding non-viewable, non-editable elements to encrypted vars
							 */

							if (is_array($encrypted))
							{
								// Repeat groups
								$v = array();

								foreach ($encrypted as $e)
								{
									// $$$ rob urldecode when posting from ajax form
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

								/*
								 * $$$ hugh - things like element list elements (radios, etc) seem to use
								 * their JSON data for encrypted read only values, need to decode.
								 */

								if (is_subclass_of($elementModel, 'PlgFabrik_ElementList'))
								{
									$v = FabrikWorker::JSONtoData($v, true);
								}

								$v = $w->parseMessageForPlaceHolder($v, $post);
							}

							$elementModel->setGroupModel($groupModel);
							$elementModel->setValuesFromEncryt($post, $key, $v);
							/* $$ rob set both normal and rawvalues to encrypted - otherwise validate method doesn't
							 * pick up decrypted value
							 */
							$elementModel->setValuesFromEncryt($post, $key . '_raw', $v);
						}
					}
				}
			}
		}
	}

	/**
	 * When submitting data copy values to _raw equivalent
	 *
	 * @param   array  &$post     Form data
	 * @param   bool   $override  Override existing raw data when copying to raw
	 *
	 * @return	null
	 */
	public function copyToRaw(&$post, $override = false)
	{
		$this->copyToFromRaw($post, 'toraw', $override);
	}

	/**
	 * Copy raw data to non-raw data
	 *
	 * @param   array  &$post     Form data
	 * @param   bool   $override  Override existing raw data when copying from raw
	 *
	 * @return	null
	 */
	public function copyFromRaw(&$post, $override = false)
	{
		$this->copyToFromRaw($post, 'fromraw', $override);
	}

	/**
	 * Copy raw data to non-raw data OR none-raw to raw
	 *
	 * @param   array   &$post      Form data
	 * @param   string  $direction  Either - toraw OR fromraw - defines which data to copy to where raw/none-raw
	 * @param   bool    $override   Override existing raw data when copying from raw
	 *
	 * @return	null
	 */
	protected function copyToFromRaw(&$post, $direction = 'toraw', $override = false)
	{
		$groups = $this->getGroupsHiarachy();
		$input = $this->app->input;

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$elName2 = $elementModel->getFullName(true, false);
				$elName2Raw = $elName2 . '_raw';

				if ($direction === 'toraw')
				{
					if (!array_key_exists($elName2Raw, $post) || $override)
					{
						// Post required getValue() later on
						$input->set($elName2Raw, FArrayHelper::getValue($post, $elName2, ''));
						$post[$elName2Raw] = FArrayHelper::getValue($post, $elName2, '');
					}
				}
				else
				{
					if (!array_key_exists($elName2 . '_raw', $post) || $override)
					{
						// Post required getValue() later on
						$input->set($elName2, FArrayHelper::getValue($post, $elName2Raw, ''));
						$post[$elName2] = FArrayHelper::getValue($post, $elName2Raw, '');
					}
				}
			}
		}
	}

	/**
	 * Has the form failed a validation
	 *
	 * @return bool
	 */
	public function failedValidation()
	{
		return $this->hasErrors();
	}

	/**
	 * Validate the form
	 * modifies post data to include validation replace data
	 *
	 * @return  bool  true if form validated ok
	 */
	public function validate()
	{
		$input = $this->app->input;

		if ((bool) $input->getBool('fabrik_ignorevalidation', false) === true)
		{
			// Put in when saving page of form
			return true;
		}

		$pluginManager = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');
		$pluginManager->getPlugInGroup('validationrule');

		$post = $this->setFormData();

		// Contains any data modified by the validations
		$this->modifiedValidationData = array();
		$w = new FabrikWorker;
		$ok = true;

		// $$$ rob 01/07/2011 fileupload needs to examine records previous data for validations on editing records
		$this->setOrigData();

		// $$$ rob copy before addEncrytedVarsToArray as well as after
		// so that any placeholders(.._raw) contained in the encrypted vars are correctly replaced
		$this->copyToRaw($post);

		/* $$$ rob for PHP 5.2.1 (and potential up to before 5.2.6) $post is not fully associated with formData -
		 * so the above copToRaw does not update $this->formData.
		 * $$$ hugh - had to add the &, otherwise replace validations weren't work, as modifying
		 * $post wasn't modifying $this->formData.  Which is weird, as I thought all array assignments
		 * were by reference?
		 * $$$ hugh - FIXME - wait ... what ... hang on ... we assign $this->formData in $this->setFormData(),
		 * which we assigned to $post a few lines up there ^^.  Why are we now assigning $post back to $this->formData??
		 */
		$this->formData = &$post;

		/* $$$ hugh - add in any encrypted stuff, in case we fail validation ...
		 * otherwise it won't be in $data when we rebuild the page.
		 * Need to do it here, so _raw fields get added in the next chunk 'o' code.
		 */
		$this->addEncrytedVarsToArray($post);

		// $$$ hugh - moved this to after addEncryptedVarsToArray(), so read only data is
		// available to things like calcs running in preProcess phase.
		$this->callElementPreProcess();

		// Add in raw fields - the data is already in raw format so just copy the values
		$this->copyToRaw($post);

		$groups = $this->getGroupsHiarachy();
		$repeatTotals = $input->get('fabrik_repeat_group', array(0), 'array');
		$ajaxPost = $input->getBool('fabrik_ajax');
		$joinData = array();

		foreach ($groups as $groupModel)
		{
			$groupCounter = $groupModel->getGroup()->id;
			$elementModels = $groupModel->getPublishedElements();

			if ($groupModel->isJoin())
			{
				$joinModel = $groupModel->getJoinModel();
			}

			foreach ($elementModels as $elementModel)
			{
				// If the user can't view or edit the element, then don't validate it. Otherwise user sees failed validation but no indication of what failed
				if (!$elementModel->canUse() && !$elementModel->canView())
				{
					continue;
				}

				$elDbValues = array();
				$elementModel->getElement();
				$validationRules = $elementModel->validator->findAll();

				// $$ rob incorrect for ajax validation on joined elements
				// $elName = $elementModel->getFullName(true, false);
				$elName = $input->getBool('fabrik_ajax') ? $elementModel->getHTMLId(0) : $elementModel->getFullName(true, false);
				$this->errors[$elName] = array();
				$elName2 = $elementModel->getFullName(true, false);

				// $$$rob fix notice on validation of multi-page forms
				if (!array_key_exists($groupCounter, $repeatTotals))
				{
					$repeatTotals[$groupCounter] = 1;
				}

				for ($c = 0; $c < $repeatTotals[$groupCounter]; $c++)
				{
					$this->errors[$elName][$c] = array();

					// $$$ rob $this->formData was $_POST, but failed to get anything for calculation elements in php 5.2.1
					$formData = $elementModel->getValue($this->formData, $c, array('runplugins' => 0, 'use_default' => false, 'use_querystring' => false));

					if (get_magic_quotes_gpc())
					{
						if (is_array($formData))
						{
							foreach ($formData as &$d)
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
							$formData = stripslashes($formData);

							if ($ajaxPost)
							{
								$formData = rawurldecode($formData);
							}
						}
					}

					// Internal element plugin validations
					if (!$elementModel->validate(@$formData, $c))
					{
						$ok = false;
						$this->errors[$elName][$c][] = $elementModel->getValidationErr();
					}

					/**
					 * $$$ rob 11/04/2012 was stopping multiselect/chx dbjoin elements from saving in normal group.
					 * if ($groupModel->canRepeat() || $elementModel->isJoin())
					 */
					if ($groupModel->canRepeat())
					{
						// $$$ rob for repeat groups no join setting to array() means that $_POST only contained the last repeat group data
						// $elDbValues = array();
						$elDbValues[$c] = $formData;
					}
					else
					{
						$elDbValues = $formData;
					}
					// Validations plugins attached to elements
					if (!$elementModel->mustValidate())
					{
						continue;
					}

					foreach ($validationRules as $plugin)
					{
						$plugin->formModel = $this;

						if ($plugin->shouldValidate($formData, $c))
						{
							if (!$plugin->validate($formData, $c))
							{
								$this->errors[$elName][$c][] = $w->parseMessageForPlaceHolder($plugin->getMessage());
								$ok = false;
							}

							if (method_exists($plugin, 'replace'))
							{
								if ($groupModel->canRepeat())
								{
									$elDbValues[$c] = $formData;
									$testReplace = $plugin->replace($elDbValues[$c], $c);

									if ($testReplace != $elDbValues[$c])
									{
										$elDbValues[$c] = $testReplace;
										$this->modifiedValidationData[$elName][$c] = $testReplace;
										$joinData[$elName2 . '_raw'][$c] = $testReplace;
										$post[$elName . '_raw'][$c] = $testReplace;
									}
								}
								else
								{
									$testReplace = $plugin->replace($elDbValues, $c);

									if ($testReplace != $elDbValues)
									{
										$elDbValues = $testReplace;
										$this->modifiedValidationData[$elName] = $testReplace;
										$input->set($elName . '_raw', $elDbValues);
										$post[$elName . '_raw'] = $elDbValues;
									}
								}
							}
						}
					}
				}

				if ($groupModel->isJoin() || $elementModel->isJoin())
				{
					$joinData[$elName2] = $elDbValues;
				}
				else
				{
					$input->set($elName, $elDbValues);
					$post[$elName] = $elDbValues;
				}
				// Unset the defaults or the orig submitted form data will be used (see date plugin mysql vs form format)
				$elementModel->defaults = null;
			}
		}
		// Insert join data into request array
		foreach ($joinData as $key => $val)
		{
			$input->set($key, $val);
			$post[$key] = $val;
		}

		if (!empty($this->errors))
		{
			FabrikWorker::getPluginManager()->runPlugins('onError', $this);
		}

		FabrikHelperHTML::debug($this->errors, 'form:errors');
		//echo "<pre>";print_r($this->errors);exit;
		$this->setErrors($this->errors);

		return $ok;
	}

	/**
	 * Helper method to get the session context - apply row id only if not '' as
	 * accessing session data with a path '..' appears not to be possible
	 *
	 * @return string
	 */
	public function getSessionContext()
	{
		$context = 'com_' . $this->package . '.form.' . $this->getId() . '.';
		$rowId = $this->getRowId();

		if ($rowId !== '')
		{
			$context .= $rowId . '.';
		}

		return $context;
	}

	/**
	 * Get form validation errors - if empty test session for errors
	 * 31/01/13 - no longer restoring from session errors - see http://fabrikar.com/forums/showthread.php?t=31377
	 * 19/02/13 - Changed from http_referer test to this->isMambot to restore session errors when redirecting from a non-ajax form
	 * in module that has failed validation - see http://fabrikar.com/forums/showthread.php?t=31870
	 *
	 * @return  array  errors
	 */
	public function getErrors()
	{
		// Store errors in local array as clearErrors() removes $this->errors
		$errors = array();

		if (empty($this->errors))
		{
			if ($this->isMambot)
			{
				$errors = $this->session->get($this->getSessionContext() . 'errors', array());
			}
		}
		else
		{
			$errors = $this->errors;
		}
		$this->clearErrors();
		$this->errors = $errors;

		return $this->errors;
	}

	/**
	 * Clear form validation errors
	 *
	 * @return  void
	 */
	public function clearErrors()
	{
		$this->errors = array();
		$context = $this->getSessionContext();
		$this->session->clear($context . 'errors');
		/* $$$ rob this was commented out, but putting back in to test issue that if we have ajax validations on
		 * and a field is validated, then we don't submit the form, and go back to add the form, the previously validated
		 * values are shown in the form.
		 */
		$this->session->set($context . 'session.on', false);
	}

	/**
	 * Set form validation errors in session
	 *
	 * @param   array  $errors  error messages
	 *
	 * @return void
	 */
	public function setErrors($errors)
	{
		$context = $this->getSessionContext();
		$this->session->set($context . 'errors', $errors);
		$this->session->set($context . 'session.on', true);
	}

	/**
	 * Get a JSON encoded string of error and modified data messages
	 *
	 * @return string
	 */
	public function getJsonErrors()
	{
		$data = array('modified' => $this->modifiedValidationData, 'errors' => $this->errors);

		return json_encode($data);
	}

	/**
	 * Should the form do a spoof check
	 *
	 * @return	bool
	 */
	public function spoofCheck()
	{
		$fbConfig = JComponentHelper::getParams('com_fabrik');

		return $this->getParams()->get('spoof_check', $fbConfig->get('spoofcheck_on_formsubmission', true));
	}

	/**
	 * Get an instance of the uploader object
	 *
	 * @return  object  uploader
	 */
	public function &getUploader()
	{
		if (is_null($this->uploader))
		{
			$this->uploader = new Uploader($this);
		}

		return $this->uploader;
	}

	/**
	 * Get the forms table name
	 *
	 * @deprecated - not used?
	 *
	 * @return  string  table name
	 */
	public function getTableName()
	{
		$this->getListModel();

		return $this->getListModel()->getTable()->db_table_name;
	}

	/**
	 * Get the form row
	 *
	 * @param   string  $name     table name
	 * @param   string  $prefix   table name prefix
	 * @param   array   $options  initial state options
	 *
	 * @return FabTable form row
	 */
	public function getTable($name = '', $prefix = 'Table', $options = array())
	{
		if (is_null($this->form))
		{
			$this->form = parent::getTable('Form', 'FabrikTable');
		}

		$id = $this->getId();

		if ($this->form->id != $id)
		{
			$this->form->load($id);
		}

		return $this->form;
	}

	/**
	 * Determines if the form can be published
	 *
	 * @return  bool  true if publish dates are ok
	 */
	public function canPublish()
	{
		$db = FabrikWorker::getDbo();
		$form = $this->getForm();
		$nullDate = $db->getNullDate();
		$publishUp = JFactory::getDate($form->publish_up)->toUnix();
		$publishDown = JFactory::getDate($form->publish_down)->toUnix();
		$now = $this->date->toUnix();

		if ($form->published == '1')
		{
			if ($now >= $publishUp || $form->publish_up == '' || $form->publish_up == $nullDate)
			{
				if ($now <= $publishDown || $form->publish_down == '' || $form->publish_down == $nullDate)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Create a drop down list of all the elements in the form
	 *
	 * @param   string  $name                Drop down name
	 * @param   string  $default             Current value
	 * @param   bool    $excludeUnpublished  Add elements that are unpublished
	 * @param   bool    $useStep             Concat table name and el name with '___' (true) or "." (false)
	 * @param   bool    $incRaw              Include raw labels default = true
	 * @param   string  $key                 What value should be used for the option value 'name' (default) or 'id' @since 3.0.7
	 * @param   string  $attribs             Select list attributes @since 3.1b
	 *
	 * @return	string	html list
	 */
	public function getElementList($name = 'order_by', $default = '', $excludeUnpublished = false,
		$useStep = false, $incRaw = true, $key = 'name', $attribs = 'class="inputbox" size="1"')
	{
		$aEls = $this->getElementOptions($useStep, $key, false, $incRaw);
		asort($aEls);

		// Paul - Prepend rather than append "none" option.
		array_unshift($aEls, JHtml::_('select.option', '', '-'));

		return JHtml::_('select.genericlist', $aEls, $name, $attribs, 'value', 'text', $default);
	}

	/**
	 * Get an array of the form's element's ids
	 *
	 * @param   array  $ignore  ClassNames to ignore e.g. array('FabrikModelFabrikCascadingdropdown')
	 * @param   array  $opts    Property 'includePublished' can be set to 0; @since 3.0.7
	 *                          Property 'loadPrefilters' @since 3.0.7.1 - used to ensure that pre-filter elements are loaded in inline edit
	 *
	 * @return  array  int ids
	 */
	public function getElementIds($ignore = array(), $opts = array())
	{
		$aEls = array();
		$groups = $this->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$this->getElementIds_check($elementModel, $ignore, $opts, $aEls);
			}
		}

		if (FArrayHelper::getValue($opts, 'loadPrefilters', false))
		{
			$listModel = $this->getListModel();
			list($afilterFields, $afilterConditions, $afilterValues, $afilterAccess, $afilterEval, $afilterJoins, $aFilterType) = $listModel->prefilterSetting();

			foreach ($afilterFields as $name)
			{
				$raw = preg_match("/_raw$/", $name) > 0;
				$name = $name ? FabrikString::rtrimword($name, '_raw') : $name;
				$elementModel = $this->getElement($name);
			}
		}

		return $aEls;
	}

	/**
	 * Helper function for getElementIds(), test if the element should be added
	 *
	 * @param   plgFabrik_Element  $elementModel  Element model
	 * @param   array              $ignore        ClassNames to ignore e.g. array('FabrikModelFabrikCascadingdropdown')
	 * @param   array              $opts          Filter options
	 * @param   array              &$aEls         Array of element ids to load
	 *
	 * @return  void
	 */
	private function getElementIds_check($elementModel, $ignore, $opts, &$aEls)
	{
		$class = get_class($elementModel);

		if (!in_array($class, $ignore))
		{
			$element = $elementModel->getElement();

			if (!(FArrayHelper::getValue($opts, 'includePublised', true) && $element->published == 0))
			{
				$aEls[] = (int) $element->id;
			}
		}
	}

	/**
	 * Creates options array to be then used by getElementList to create a drop down of elements in the form
	 * separated as elements need to collate this options from multiple forms
	 *
	 * @param   bool    $useStep               concat table name and el name with '___' (true) or "." (false)
	 * @param   string  $key                   name of key to use (default "name")
	 * @param   bool    $show_in_list_summary  only show those elements shown in table summary
	 * @param   bool    $incRaw                include raw labels in list (default = false) Only works if $key = name
	 * @param   array   $filter                list of plugin names that should be included in the list - if empty include all plugin types
	 * @param   string  $labelMethod           An element method that if set can alter the option's label
	 *                                         Used to only show elements that can be selected for search all
	 * @param   bool    $noJoins               do not include elements in joined tables (default false)
	 * @param   bool    $translate             run label through translation (default true)
	 *
	 * @return	array	html options
	 */
	public function getElementOptions($useStep = false, $key = 'name', $show_in_list_summary = false, $incRaw = false,
		$filter = array(), $labelMethod = '', $noJoins = false, $translate = true)
	{
		$groups = $this->getGroupsHiarachy();
		$aEls = array();

		foreach ($groups as $gid => $groupModel)
		{
			if ($noJoins && $groupModel->isJoin())
			{
				continue;
			}

			$elementModels = $groupModel->getMyElements();
			$prefix = $groupModel->isJoin() ? $groupModel->getJoinModel()->getJoin()->table_join . '.' : '';

			foreach ($elementModels as $elementModel)
			{
				$el = $elementModel->getElement();

				if (!empty($filter) && !in_array($el->plugin, $filter))
				{
					continue;
				}

				if ($show_in_list_summary == true && $el->show_in_list_summary != 1)
				{
					continue;
				}

				$val = $el->$key;
				$label = strip_tags($prefix . $el->label);

				if ($translate)
				{
					$label = FText::_($label);
				}

				if ($labelMethod !== '')
				{
					$elementModel->$labelMethod($label);
				}

				if ($key != 'id')
				{
					$val = $elementModel->getFullName($useStep, false);

					if ($this->addDbQuote)
					{
						$val = FabrikString::safeColName($val);
					}

					if ($incRaw && is_a($elementModel, 'PlgFabrik_ElementDatabasejoin'))
					{
						/* @FIXME - next line had been commented out, causing undefined warning for $rawVal
						 * on following line.  Not sure if getrawColumn is right thing to use here though,
						 * like, it adds filed quotes, not sure if we need them.
						 */
						if ($elementModel->getElement()->published != 0)
						{
							$rawVal = $elementModel->getRawColumn($useStep);

							if (!$this->addDbQuote)
							{
								$rawVal = str_replace('`', '', $rawVal);
							}

							$aEls[$label . '(raw)'] = JHtml::_('select.option', $rawVal, $label . '(raw)');
						}
					}
				}

				$aEls[] = JHtml::_('select.option', $val, $label);
			}
		}
		// Paul - Sort removed so that list is presented in group/id order regardless of whether $key is name or id
		// asort($aEls);

		return $aEls;
	}

	/**
	 * Called via ajax nav
	 *
	 * @param   int  $dir  1 - move forward, 0 move back
	 *
	 * @return  bool  new row id loaded.
	 */
	public function paginateRowId($dir)
	{
		$db = FabrikWorker::getDbo();
		$input = $this->app->input;
		$c = $dir == 1 ? '>=' : '<=';
		$intLimit = $dir == 1 ? 2 : 0;
		$listModel = $this->getListModel();
		$item = $listModel->getTable();
		$rowId = $input->getString('rowid', '', 'string');
		$query = $db->getQuery(true);
		$query->select($item->db_primary_key . ' AS ' . FabrikString::safeColNameToArrayKey($item->db_primary_key))->from($item->db_table_name)
			->where($item->db_primary_key . ' ' . $c . ' ' . $db->q($rowId));
		$query = $listModel->buildQueryOrder($query);
		$db->setQuery($query, 0, $intLimit);
		$ids = $db->loadColumn();

		if ($dir == 1)
		{
			if (count($ids) >= 2)
			{
				$input->set('rowid', $ids[$dir]);

				return true;
			}
			else
			{
				return false;
			}
		}

		if (count($ids) - 2 >= 0)
		{
			$input->set('rowid', $ids[count($ids) - 2]);

			return true;
		}

		return false;
	}

	/**
	 * Get the last insert id, for situations where we need the 'rowid' for newly inserted forms,
	 * and can't use getRowId() because it caches rowid as empty.  For example, in plugins running
	 * onAfterProcess, like upsert.
	 *
	 * Note that $this->lastInsertId is getting set in the
	 */
	public function getInsertId()
	{
		return $this->lastInsertId;
	}

	/**
	 * Are we creating a new record or editing an existing one?
	 * Put here to ensure compat when we go from 3.0 where rowid = 0 = new, to row id '' = new
	 *
	 * @since   3.0.9
	 *
	 * @return  boolean
	 */
	public function isNewRecord()
	{
		if ($this->getRowId() === '')
		{
			return true;
		}
		else
		{
			/*
			 * special case when using 'useley', rowid will be set on submission, even on a new record,
			 * so test for hidden 'nodata' field, which is set on form load if getData() finds no existing data.
			 */
			if ($this->app->input->get('task', '') === 'form.process')
			{
				$opts   = array(
					'formid' => $this->getId()
				);
				$useKey = FabrikWorker::getMenuOrRequestVar('usekey', '', $this->isMambot, 'var', $opts);

				if ($useKey && $this->app->input->get('nodata', '') === '1')
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get the current records row id
	 * setting a rowid of -1 will load in the current users record (used in
	 * conjunction with usekey variable
	 *
	 * setting a rowid of -2 will load in the last created record
	 *
	 * @return  string  rowid
	 */
	public function getRowId()
	{
		if (isset($this->rowId))
		{
			return $this->rowId;
		}

		$input = $this->app->input;
		$usersConfig = JComponentHelper::getParams('com_fabrik');

		// $$$rob if we show a form module when in a fabrik form component view - we shouldn't use
		// the request rowid for the content plugin as that value is destined for the component
		if ($this->isMambot && $input->get('option') == 'com_' . $this->package)
		{
			$this->rowId = $usersConfig->get('rowid');
		}
		else
		{
			$this->rowId = FabrikWorker::getMenuOrRequestVar('rowid', $usersConfig->get('rowid'), $this->isMambot);

			if ($this->rowId == -2)
			{
				// If the default was set to -2 (load last row) then a pagination form plugin's row id should override menu settings
				$this->rowId = FabrikWorker::getMenuOrRequestVar('rowid', $usersConfig->get('rowid'), $this->isMambot, 'request');
			}
		}

		if ($this->getListModel()->getParams()->get('sef-slug', '') !== '')
		{
			$this->rowId = explode(':', $this->rowId);
			$this->rowId = array_shift($this->rowId);
		}
		// $$$ hugh - for some screwed up reason, when using SEF, rowid=-1 ends up as :1
		// $$$ rob === compare as otherwise 0 == ":1" which meant that the users record was loaded
		if ($this->isUserRowId())
		{
			$this->rowId = '-1';
		}
		// Set rowid to -1 to load in the current users record
		switch ($this->rowId)
		{
			case '-1':
				// New rows (no logged in user) should be ''
				$this->rowId = $this->user->get('id') == 0 ? '' : $this->user->get('id');
				break;
			case '-2':
				// Set rowid to -2 to load in the last recorded record
				$this->rowId = $this->getMaxRowId();
				break;
		}

		/**
		 * $$$ hugh - added this as a Hail Mary sanity check, make sure
		 * rowId is an empty string if for whatever reason it's still null,
		 * as we have code in various place that checks for $this->rowId === ''
		 * to detect adding new form.  So if at this point rowid is null, we have
		 * to assume it's a new form, and set rowid to empty string.
		 */
		if (is_null($this->rowId))
		{
			$this->rowId = '';
		}

		/**
		 * $$$ hugh - there's a couple of places, like calendar viz, that add &rowid=0 to
		 * query string for new form, so check for that and set to empty string.
		 */
		if ($this->rowId === '0')
		{
			$this->rowId = '';
		}

		FabrikWorker::getPluginManager()->runPlugins('onSetRowId', $this);

		return $this->rowId;
	}

	/**
	 * Collates data to write out the form
	 *
	 * @return  mixed  bool
	 */
	public function render()
	{
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark('formmodel render: start') : null;

		// $$$rob required in paolo's site when rendering modules with ajax option turned on
		$this->listModel = null;
		$this->setRowId($this->getRowId());

		/*
		 * $$$ hugh - need to call this here as we set $this->editable here, which is needed by some plugins
		 * hmmmm, this means that getData() is being called from checkAccessFromListSettings(),
		 * so plugins running onBeforeLoad will have to unset($formModel->_data) if they want to
		 * do something funky like change the rowid being loaded.  Not a huge problem, but caught me out
		 * when a custom PHP onBeforeLoad plugin I'd written for a client suddenly broke.
		 */
		$this->checkAccessFromListSettings();
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

		// @TODO - relook at this:
		// $this->_reduceDataForXRepeatedJoins();
		JDEBUG ? $profiler->mark('formmodel render end') : null;

		$this->session->set('com_' . $this->package . '.form.' . $this->getId() . '.data', $this->data);

		// $$$ rob return res - if its false the the form will not load
		return $res;
	}

	/**
	 * Get the max row id - used when requesting rowid=-2 to return the last recorded detailed view
	 *
	 * @return  int  max row id
	 */
	protected function getMaxRowId()
	{
		if (!$this->getForm()->record_in_database)
		{
			return $this->rowId;
		}

		$listModel = $this->getListModel();
		$fabrikDb = $listModel->getDb();
		$item = $listModel->getTable();
		$k = FabrikString::safeNameQuote($item->db_primary_key);

		// @TODO JQuery this
		$fabrikDb->setQuery("SELECT MAX($k) FROM " . FabrikString::safeColName($item->db_table_name) . $listModel->buildQueryWhere());

		return $fabrikDb->loadResult();
	}

	/**
	 * If a submit plugin wants to fail validation not specific to an element
	 *
	 * @param  string  $errMsg
	 */
	public function setFormErrorMsg($errMsg)
	{
		$this->formErrorMsg = $errMsg;
	}

	/**
	 * Does the form contain user errors
	 *
	 * @return  bool
	 */
	public function hasErrors()
	{
		$errorsFound = false;

		if (isset($this->formErrorMsg))
		{
			$errorsFound = true;
		}

		$allErrors = $this->isMambot ? $this->session->get($this->getSessionContext() . 'errors', array()) : $this->errors;

		foreach ($allErrors as $field => $errors)
		{
			if (!empty($errors) & is_array($errors))
			{
				foreach ($errors as $error)
				{
					if (!empty($error[0]))
					{
						$errorsFound = true;
					}
				}
			}
		}

		if ($this->saveMultiPage(false))
		{
			$sessionRow = $this->getSessionData();
			/*
			 * Test if its a resumed paged form
			 * if so _arErrors will be filled so check all elements had no errors
			 */
			$multiPageErrors = false;

			if ($sessionRow->data != '')
			{
				foreach ($this->errors as $err)
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
		}

		return $errorsFound;
	}

	/**
	 * Main method to get the data to insert into the form
	 *
	 * @return  array  Form's data
	 */

	public function getData()
	{
		// If already set return it. If not was causing issues with the juser form plugin
		// when it tried to modify the form->data info, from within its onLoad method, when sync user option turned on.

		if (isset($this->data))
		{
			return $this->data;
		}

		$this->getRowId();
		$input = $this->app->input;
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark('formmodel getData: start') : null;
		$this->data = array();
		$f = JFilterInput::getInstance();

		/*
		 * $$$ hugh - we need to remove any elements from the query string,
		 * if the user doesn't have access, otherwise ACL's on elements can
		 * be bypassed by just setting value on form load query string!
		 *
		 * Also remove all form data if task is form.process, in case some plugin is trying to reload
		 * form data.
		 */

		$clean_request = $f->clean($_REQUEST, 'array');
		$qs_request = array();

		foreach ($clean_request as $key => $value)
		{
			$test_key = FabrikString::rtrimword($key, '_raw');
			$elementModel = $this->getElement($test_key, false, false);

			if ($elementModel !== false)
			{
				if (!$elementModel->canUse()
					|| $this->app->input->get('task', '') === 'form.process'
					|| $this->app->input->get('task', '') === 'process'
					|| $this->hasErrors()
				)
				{
					unset($clean_request[$key]);
				}
				else
				{
					$qs_request[$key] = $value;
				}
			}
		}

		$data = $clean_request;
		$form = $this->getForm();
		$this->getGroupsHiarachy();
		JDEBUG ? $profiler->mark('formmodel getData: groups loaded') : null;

		if (!$form->record_in_database)
		{
			FabrikHelperHTML::debug($data, 'form:getData from $_REQUEST');
			$data = $f->clean($_REQUEST, 'array');
		}
		else
		{
			JDEBUG ? $profiler->mark('formmodel getData: start get list model') : null;
			$listModel = $this->getListModel();
			JDEBUG ? $profiler->mark('formmodel getData: end get list model') : null;
			$fabrikDb = $listModel->getDb();
			JDEBUG ? $profiler->mark('formmodel getData: db created') : null;
			$listModel->getTable();
			JDEBUG ? $profiler->mark('formmodel getData: table row loaded') : null;
			$this->aJoinObjs = $listModel->getJoins();
			JDEBUG ? $profiler->mark('formmodel getData: joins loaded') : null;

			if ($this->hasErrors())
			{
				// $$$ hugh - if we're a mambot, reload the form session state we saved in
				// process() when it banged out.
				if ($this->isMambot)
				{
					$sessionRow = $this->getSessionData();
					$this->sessionModel->last_page = 0;

					if ($sessionRow->data != '')
					{
						$sData = unserialize($sessionRow->data);
						$data = FArrayHelper::toObject($sData, 'stdClass', false);
						JFilterOutput::objectHTMLSafe($data);
						$data = array($data);
						FabrikHelperHTML::debug($data, 'form:getData from session (form in Mambot and errors)');
					}
				}
				else
				{
					// $$$ rob - use setFormData rather than $_GET
					// as it applies correct input filtering to data as defined in article manager parameters
					$this->setValidationFailedData($this->formData);
					$data = $this->setFormData();
					$data = FArrayHelper::toObject($data, 'stdClass', false);

					// $$$rob ensure "<tags>text</tags>" that are entered into plain text areas are shown correctly
					JFilterOutput::objectHTMLSafe($data);
					$data = ArrayHelper::fromObject($data);
					FabrikHelperHTML::debug($data, 'form:getData from POST (form not in Mambot and errors)');
				}
			}
			else
			{
				$sessionLoaded = false;

				// Test if its a resumed paged form
				if ($this->saveMultiPage())
				{
					$sessionRow = $this->getSessionData();
					JDEBUG ? $profiler->mark('formmodel getData: session data loaded') : null;

					if ($sessionRow->data != '')
					{
						$sessionLoaded = true;
						/*
						 * $$$ hugh - this chunk should probably go in setFormData, but don't want to risk any side effects just now
						 * problem is that later failed validation, non-repeat join element data is not formatted as arrays,
						 * but from this point on, code is expecting even non-repeat join data to be arrays.
						 */
						$tmp_data = unserialize($sessionRow->data);

						/*
						$groups = $this->getGroupsHiarachy();

						foreach ($groups as $groupModel)
						{
							if ($groupModel->isJoin() && !$groupModel->canRepeat())
							{
								foreach ($tmp_data['join'][$groupModel->getJoinId()] as &$el)
								{
									$el = array($el);
								}
							}
						}
						*/

						$bits = $data;
						$bits = array_merge($tmp_data, $bits);
						//$data = array(FArrayHelper::toObject($bits));
						$data = $bits;
						FabrikHelperHTML::debug($data, 'form:getData from session (form not in Mambot and no errors');
					}
				}

				if (!$sessionLoaded)
				{
					/* Only try and get the row data if its an active record
					 * use !== '' as rowid may be alphanumeric.
					 * Unlike 3.0 rowId does equal '' if using rowid=-1 and user not logged in
					 */
                    $opts = array(
                        'formid' => $this->getId()
                    );
					$useKey = FabrikWorker::getMenuOrRequestVar('usekey', '', $this->isMambot, 'var', $opts);

					if (!empty($useKey) || $this->rowId !== '')
					{
						// $$$ hugh - once we have a few join elements, our select statements are
						// getting big enough to hit default select length max in MySQL.
						$listModel->setBigSelects();

						// Otherwise lets get the table record

						/**
						 * $$$ hugh - 11/14/2015 - ran into issue with the order by from a list being added to the form query, when
						 * rendering a form with a content plugin in a list intro.  And I don't think we ever need to
						 * apply ordering to a form's select, by definition it's only one row.  Leaving this here for
						 * now just as a reminder in case there's any unforeseen side effects.
						 *
						 * $$$ hugh - 4/25/2016 - yes, there is an issue, as (duh!) ordering is needed for repeat groups.
						 * Changing this back to original for now, will need to work out how to handle that corner case
						 */
						$opts = $input->get('task') == 'form.inlineedit' ? array('ignoreOrder' => true) : array();
						// $opts = array('ignoreOrder' => true);
						$sql = $this->buildQuery($opts);
						$fabrikDb->setQuery($sql);
						FabrikHelperHTML::debug((string) $fabrikDb->getQuery(), 'form:render');
						$rows = $fabrikDb->loadObjectList();

						if (is_null($rows))
						{
							JError::raiseWarning(500, $fabrikDb->getErrorMsg());
						}

						JDEBUG ? $profiler->mark('formmodel getData: rows data loaded') : null;

						// $$$ rob Ack above didn't work for joined data where there would be n rows returned for "this rowid = $this->rowId  \n";
						if (!empty($rows))
						{
							// Only do this if the query returned some rows (it wont if usekey on and userid = 0 for example)
							$data = array();

							foreach ($rows as &$row)
							{
								if (empty($data))
								{
									// If loading in a rowid=-1 set the row id to the actual row id
									$this->rowId = isset($row->__pk_val) ? $row->__pk_val : $this->rowId;
								}

								$row = empty($row) ? array() : ArrayHelper::fromObject($row);
								$request = $clean_request;
								$request = array_merge($row, $request);
								$data[] = FArrayHelper::toObject($request);
							}

							$this->noData = false;
						}
						else
						{
							$this->noData = true;
						}

						FabrikHelperHTML::debug($data, 'form:getData from querying rowid= ' . $this->rowId . ' (form not in Mambot and no errors)');

						// If empty data return and trying to edit a record then show error
						JDEBUG ? $profiler->mark('formmodel getData: empty test') : null;

						// Was empty($data) but that is never empty. Had issue where list prefilter meant record was not loaded, but no message shown in form
						if (empty($rows) && $this->rowId != '')
						{
							// $$$ hugh - special case when using -1, if user doesn't have a record yet
							if ($this->isUserRowId())
							{
							    // set data to just elements that have been set on the qs (and "cleaned" / ACL checked)
							    $this->data = $qs_request;
								return;
							}
							else
							{
								// If no key found set rowid to 0 so we can insert a new record.
								if (empty($useKey) && !$this->isMambot && in_array($input->get('view'), array('form', 'details')))
								{
									$this->rowId = '';
									/**
									 * runtime exception is a little obtuse for people getting here from legitimate links,
									 * like from an email, but aren't logged in so run afoul of a pre-filter, etc
									 * So do the 3.0 thing, and raise a warning
									 */
									//throw new RuntimeException(FText::_('COM_FABRIK_COULD_NOT_FIND_RECORD_IN_DATABASE'));
									JError::raiseWarning(500, FText::_('COM_FABRIK_COULD_NOT_FIND_RECORD_IN_DATABASE'));
								}
								else
								{
									// If we are using usekey then there's a good possibility that the record
									// won't yet exist - so in this case suppress this error message
									$this->rowId = '';
								}
							}
						}
					}

					// No need to setJoinData if you are correcting a failed validation
					if (!empty($data))
					{
						$this->setJoinData($data);
					}
				}

			}
		}

		$this->data = $data;
		FabrikHelperHTML::debug($data, 'form:data');
		JDEBUG ? $profiler->mark('queryselect: getData() end') : null;

		return $this->data;
	}

	/**
	 * Checks if user is logged in and form multi-page settings to determine
	 * if the form saves to the session table on multi-page navigation
	 *
	 * @param   bool  $useSessionOn  Return true if JSession contains session.on - used in confirmation
	 * plugin to re-show the previously entered form data. Not used in $this->hasErrors() otherwise logged in users
	 * can not get the confirmation plugin to work
	 *
	 * @return  bool
	 */
	public function saveMultiPage($useSessionOn = true)
	{
		$params = $this->getParams();

		// Set in plugins such as confirmation plugin
		$pluginManager = FabrikWorker::getPluginManager();
		$pluginManager->runPlugins('usesSession', $this, 'form');

		if (in_array(true, $pluginManager->data))
		{
			if ($this->session->get($this->getSessionContext() . 'session.on') == true && $useSessionOn)
			{
				return true;
			}
		}

		$save = (int) $params->get('multipage_save', 0);

		if ($this->user->get('id') !== 0)
		{
			return $save === 0 ? false : true;
		}
		else
		{
			return $save === 2 ? true : false;
		}
	}

	/**
	 * If editing a record which contains repeated join data then on start $data is an
	 * array with each records being a row in the database.
	 *
	 * We need to take this structure and convert it to the same format as when the form
	 * is submitted
	 *
	 * @param   array  &$data  form data
	 *
	 * @return  void
	 */
	public function setJoinData(&$data)
	{
		$this->_joinDefaultData = array();

		if (empty($data))
		{
			return;
		}

		// No joins so leave !
		if (!is_array($this->aJoinObjs) || $this->rowId === '')
		{
			return;
		}

		if (!array_key_exists(0, $data))
		{
			$data[0] = new stdClass;
		}

		$groups = $this->getGroupsHiarachy();
		/**
		 * $$$ hugh - adding the "PK's seen" stuff, otherwise we end up adding multiple
		 * rows when we have multiple repeat groups.  For instance, if we had two repeated
		 * groups, one with 2 repeats and one with 3, we ended up with 6 repeats for each
		 * group, with 3 and 2 copies of each respectively.  So we need to track which
		 * instances of each repeat we have already copied into the main row.
		 *
		 * So $joinPksSeen will be indexed by $joinPksSeen[groupid][elementid]
		 */
		$joinPksSeen = array();
		/**
		 * Have to copy the data for the PK's seen stuff, as we're modifying the original $data
		 * as we go, which screws up the PK logic once we've modified the PK value itself in the
		 * original $data.  Probably only needed for $data[0], as that's the only row we actually
		 * modify, but for now I'm just copying the whole thing, which then gets used for doing the ...
		 * $joinPkVal = $data_copy[$row_index]->$joinPk;
		 * ... inside the $data iteration below.
		 *
		 * PS, could probably just do a $data_copy = $data, as our usage of the copy isn't going to
		 * involve nested arrays (which get copied by reference when using =), but I've been burned
		 * so many times with array copying, I'm going to do a "deep copy" using serialize/unserialize!
		 */
		$data_copy = unserialize(serialize($data));

		foreach ($groups as $groupId => $groupModel)
		{
			$group = $groupModel->getGroup();
			$joinPksSeen[$groupId] = array();
			$elementModels = $groupModel->getMyElements();

			foreach ($elementModels as $elementModelID => $elementModel)
			{
				if ($groupModel->isJoin() || $elementModel->isJoin())
				{
					if ($groupModel->isJoin())
					{
						$joinModel = $groupModel->getJoinModel();
						$joinPk = $joinModel->getForeignID();
						$joinPksSeen[$groupId][$elementModelID] = array();
					}

					$names = $elementModel->getJoinDataNames();

					foreach ($data as $row_index => $row)
					{
						// Might be a string if new record ?
						$row = (object) $row;

						if ($groupModel->isJoin())
						{
							/**
							 * If the join's PK element isn't published or for any other reason not
							 * in $data, we're hosed!
							 */
							if (!isset($data_copy[$row_index]->$joinPk))
							{
								continue;
							}

							$joinPkVal = $data_copy[$row_index]->$joinPk;
							/**
							 * if we've seen the PK value for this element's row before, skip it.
							 * Check for empty as well, just in case - as we're loading existing data,
							 * it darn well should have a value!
							 */
							if (empty($joinPkVal) || in_array($joinPkVal, $joinPksSeen[$groupId][$elementModelID]))
							{
								continue;
							}
						}

						for ($i = 0; $i < count($names); $i ++)
						{
							$name = $names[$i];

							if (array_key_exists($name, $row))
							{
								$v = $row->$name;
								$v = FabrikWorker::JSONtoData($v, $elementModel->isJoin());

								// New record or csv export
								if (!isset($data[0]->$name))
								{
									$data[0]->$name = $v;
								}

								if (!is_array($data[0]->$name))
								{
									if ($groupModel->isJoin() && $groupModel->canRepeat())
									{
										$v = array($v);
									}

									$data[0]->$name = $v;
								}
								else
								{
									if ($groupModel->isJoin() && $groupModel->canRepeat())
									{
										$n =& $data[0]->$name;
										$n[] = $v;
									}
								}
							}
						}

						if ($groupModel->isJoin())
						{
							/**
							 * Make a Note To Self that we've now handled the data for this element's row,
							 * and can skip it from now on.
							 */
							$joinPksSeen[$groupId][$elementModelID][] = $joinPkVal;
						}
					}
				}
			}
		}

		// Remove the additional rows - they should have been merged into [0] above. if no [0] then use main array
		$data = ArrayHelper::fromObject(FArrayHelper::getValue($data, 0, $data));
	}

	/**
	 * Get the forms session data (used when using multi-page forms)
	 *
	 * @return  object	session data
	 */
	protected function getSessionData()
	{
		if (isset($this->sessionData))
		{
			return $this->sessionData;
		}

		$params = $this->getParams();
		$this->sessionModel = JModelLegacy::getInstance('Formsession', 'FabrikFEModel');
		$this->sessionModel->setFormId($this->getId());
		$this->sessionModel->setRowId($this->rowId);
		$useCookie = (int) $params->get('multipage_save', 0) === 2 ? true : false;

		if (!$useCookie)
		{
			// In case a plugin is using cookie session (e.g. confirmation plugin)
			$useCookie = $this->sessionModel->canUseCookie();
		}

		$this->sessionModel->useCookie($useCookie);

		$this->sessionData = $this->sessionModel->load();

		return $this->sessionData;
	}

	/**
	 * Create the sql query to get the rows data for insertion into the form
	 *
	 * @param   array  $opts  key: ignoreOrder ignores order by part of query
	 *                        Needed for inline edit, as it only selects certain fields, order by on a db join element returns 0 results
	 *
	 * @deprecated	use buildQuery() instead
	 *
	 * @return  string	sql query to get row
	 */
	public function _buildQuery($opts = array())
	{
		return $this->buildQuery($opts);
	}

	/**
	 * Create the sql query to get the rows data for insertion into the form
	 *
	 * @param   array  $opts  key: ignoreOrder ignores order by part of query
	 *                        Needed for inline edit, as it only selects certain fields, order by on a db join element returns 0 results
	 *
	 * @return  string  query
	 */
	public function buildQuery($opts = array())
	{
		if (isset($this->query))
		{
			return $this->query;
		}

		$db = FabrikWorker::getDbo();
		$input = $this->app->input;
		$form = $this->getForm();

		if (!$form->record_in_database)
		{
			return;
		}

		$listModel = $this->getListModel();
		$item = $listModel->getTable();
		$sql = $listModel->buildQuerySelect('form');
		$sql .= $listModel->buildQueryJoin();
		$emptyRowId = $this->rowId === '' ? true : false;
		$random = $input->get('random');
        $opts = array(
            'formid' => $this->getId()
        );
		$useKey = FabrikWorker::getMenuOrRequestVar('usekey', '', $this->isMambot, 'var', $opts);

		if ($useKey != '')
		{
			$useKey = explode('|', $useKey);

			foreach ($useKey as &$tmpK)
			{
				$tmpK = !strstr($tmpK, '.') ? $item->db_table_name . '.' . $tmpK : $tmpK;
				$tmpK = FabrikString::safeColName($tmpK);
			}

			if (!is_array($this->rowId))
			{
				$aRowIds = explode('|', $this->rowId);
			}
		}

		$comparison = $input->get('usekey_comparison', '=');
		$viewPk = $input->get('view_primary_key');

		// $$$ hugh - changed this to !==, as in rowid=-1/usekey situations, we can have a rowid of 0
		// I don't THINK this will have any untoward side effects, but ...
		if ((!$random && !$emptyRowId) || !empty($useKey))
		{
			$sql .= ' WHERE ';

			if (!empty($useKey))
			{
				$sql .= "(";
				$parts = array();

				for ($k = 0; $k < count($useKey); $k++)
				{
					/**
					 *
					 * For gory reasons, we have to assume that an empty string cannot be a valid rowid
					 * when using usekey, so just create a 1=-1 if it is.
					 */
					if ($aRowIds[$k] === '')
					{
						$parts[] = ' 1=-1';
						continue;
					}
					// Ensure that the key value is not quoted as we Quote() afterwards
					if ($comparison == '=')
					{
						$parts[] = ' ' . $useKey[$k] . ' = ' . $db->q($aRowIds[$k]);
					}
					else
					{
						$parts[] = ' ' . $useKey[$k] . ' LIKE ' . $db->q('%' . $aRowIds[$k] . '%');
					}
				}

				$sql .= implode(' AND ', $parts);
				$sql .= ')';
			}
			else
			{
				$sql .= ' ' . $item->db_primary_key . ' = ' . $db->q($this->rowId);
			}
		}
		else
		{
			if ($viewPk != '')
			{
				$sql .= ' WHERE ' . $viewPk . ' ';
			}
			elseif ($random)
			{
				// $$$ rob Should this not go after prefilters have been applied ?
				$sql .= ' ORDER BY RAND() LIMIT 1 ';
			}
		}
		// Get pre-filter conditions from table and apply them to the record
		// the false, ignores any filters set by the table
		$where = $listModel->buildQueryWhere(false);

		if (strstr($sql, 'WHERE'))
		{
			// Do it this way as queries may contain sub-queries which we want to keep the where
			$firstWord = JString::substr($where, 0, 5);

			if ($firstWord == 'WHERE')
			{
				$where = JString::substr_replace($where, 'AND', 0, 5);
			}
		}
		// Set rowId to -2 to indicate random record
		if ($random)
		{
			$this->setRowId(-2);
		}

		// $$$ rob ensure that all prefilters are wrapped in brackets so that
		// only one record is loaded by the query - might need to set $word = and?
		if (trim($where) != '')
		{
			$where = explode(' ', $where);
			$word = array_shift($where);
			$sql .= $word . ' (' . implode(' ', $where) . ')';
		}

		if (!$random && FArrayHelper::getValue($opts, 'ignoreOrder', false) === false)
		{
			// $$$ rob if showing joined repeat groups we want to be able to order them as defined in the table
			$sql .= $listModel->buildQueryOrder();
		}

		$this->query = $sql;

		return $sql;
	}

	/**
	 * Attempts to determine if the form contains the element
	 *
	 * @param   string  $searchName  Element name to search for
	 * @param   bool    $checkInt    Check search name against element id
	 * @param   bool    $checkShort  Check short element name
	 *
	 * @return  bool  true if found, false if not found
	 */
	public function hasElement($searchName, $checkInt = false, $checkShort = true)
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
						$this->currentElement = $elementModel;

						return true;
					}
				}

				if ($searchName == $element->name && $checkShort)
				{
					$this->currentElement = $elementModel;

					return true;
				}

				if ($searchName == $elementModel->getFullName(true, false))
				{
					$this->currentElement = $elementModel;

					return true;
				}

				if ($searchName == $elementModel->getFullName(false, false))
				{
					$this->currentElement = $elementModel;

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get an element
	 *
	 * @param   string  $searchName  Name to search for
	 * @param   bool    $checkInt    Check search name against element id
	 * @param   bool    $checkShort  Check short element name
	 *
	 * @return  PlgFabrik_Element  ok: element model not ok: false
	 */
	public function getElement($searchName, $checkInt = false, $checkShort = true)
	{
		return $this->hasElement($searchName, $checkInt, $checkShort) ? $this->currentElement : false;
	}

	/**
	 * Set the list model
	 *
	 * @param   object  &$listModel  List model
	 *
	 * @return  void
	 */
	public function setListModel(&$listModel)
	{
		$this->listModel = $listModel;
	}

	/**
	 * Is the page a multi-page form?
	 *
	 * @return  bool
	 */
	public function isMultiPage()
	{
		$groups = $this->getGroupsHiarachy();
        $view =   $this->app->input->get('view', 'form');

		foreach ($groups as $groupModel)
		{
			$params = $groupModel->getParams();

			switch ($params->get('split_page'))
            {
                default:
                case '0':
                    break;
                case '1':
                    return true;
                    break;
                case '2':
                    if ($view === 'form')
                    {
                        return true;
                    }
                    break;
                case '3':
                    if ($view == 'details')
                    {
                        return true;
                    }
                    break;
            }
		}

		return false;
	}

	/**
	 * Get an object of pages, keyed on page counter and containing an array of the page's group ids
	 *
	 * @return  object
	 */
	public function getPages()
	{
		if (!is_null($this->pages))
		{
			return $this->pages;
		}

		$this->pages = new stdClass;
		$pageCounter = 0;
		$groups = $this->getGroupsHiarachy();
		$c = 0;

		foreach ($groups as $groupModel)
		{
			$params = $groupModel->getParams();

			if ($groupModel->isSplitPage() && $c != 0 && $groupModel->canView())
			{
				$pageCounter++;
			}

			if ($groupModel->canView())
			{
				if (!isset($this->pages->$pageCounter))
				{
					$this->pages->$pageCounter = array();
				}

				array_push($this->pages->$pageCounter, $groupModel->getId());
			}

			$c++;
		}

		return $this->pages;
	}

	/**
	 * Should the form submit via ajax or not?
	 *
	 * @return  bool
	 */

	public function isAjax()
	{
		if (is_null($this->ajax))
		{
			$this->ajax = $this->app->input->getBool('ajax', false);

			// $$$ rob - no element requires AJAX submission!

			/* $groups = $this->getGroupsHiarachy();
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
			} */
		}

		return (bool) $this->ajax;
	}

	/**
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
	 * @since   2.0rc1
	 *
	 * @return  void
	 */
	protected function _reduceDataForXRepeatedJoins()
	{
		$groups = $this->getGroupsHiarachy();
		$listModel = $this->getListModel();
		$pkField = '';

		foreach ($groups as $groupModel)
		{
			/**
			 * $$$ hugh - we need to do this for non-repeat joins as well
			 */
			if ($groupModel->isJoin())
			{
				$joinModel = $groupModel->getJoinModel();
				$tblJoin = $joinModel->getJoin();

				// $$$ hugh - slightly modified these lines so we don't create $this->data['join'] if there is no
				// join data, because that then messes up code subsequent code that checks for empty($this->data)
				if (!isset($this->data['join']))
				{
					// $this->data['join'] = array();
					return;
				}

				if (!array_key_exists($tblJoin->id, $this->data['join']))
				{
					continue;
				}

				if ($tblJoin->table_join == '')
				{
					continue;
				}

				$jData = &$this->data['join'][$tblJoin->id];
				$db = $listModel->getDb();
				$fields = $db->getTableColumns($tblJoin->table_join, false);
				$keyCount = 0;
				unset($pkField);

				foreach ($fields as $f)
				{
					if ($f->Key == 'PRI')
					{
						if (!isset($pkField))
						{
							$pkField = $tblJoin->table_join . '___' . $f->Field;
						}

						$keyCount ++;
					}
				}

				if (!isset($pkField))
				{
					$pkField = '';
				}
				/*
				 * Corner case if you link to #__user_profile - its primary key is made of 2 elements, so
				 * simply checking on the user_id (the first col) will find duplicate results and incorrectly
				 * merge down.
				 */
				if ($keyCount > 1)
				{
					return;
				}

				$usedKeys = array();

				if (!empty($jData) && array_key_exists($pkField, $jData))
				{
					foreach ($jData[$pkField] as $key => $value)
					{
						/*
						 * $$$rob
						 * added : || ($value === '' && !empty($this->errors))
						 * this was incorrectly reducing empty data
						 * when re-viewing form after failed validation
						 * with a form with repeating groups (with empty data in the key fields
						 *
						 */
						if (!in_array($value, $usedKeys) || ($value === '' && !empty($this->errors)))
						{
							$usedKeys[$key] = $value;
						}
					}
				}

				$keysToKeep = array_keys($usedKeys);

				// Remove unneeded data from array
				foreach ($jData as $key => $value)
				{
					foreach ($value as $key2 => $v)
					{
						if (!in_array($key2, $keysToKeep))
						{
							unset($jData[$key][$key2]);
						}
					}
				}
				// Reduce the keys so that we don't have keys of 0, 2
				foreach ($jData as $key => $array)
				{
					if ($groupModel->canRepeat())
					{
						$jData[$key] = array_values($array);
					}
					else
					{
						// $$$ hugh - if it's a one-to-one, it should be a single value
						$aVals = array_values($array);
						$jData[$key] = FArrayHelper::getValue($aVals, 0, '');
					}
				}
			}
		}
	}

	/**
	 * Query all active form plugins to see if they inject custom html into the top
	 * or bottom of the form
	 *
	 * @return  array  plugin top html, plugin bottom html (inside <form>) plugin end (after form)
	 */

	public function getFormPluginHTML()
	{
		$pluginManager = FabrikWorker::getPluginManager();
		$pluginManager->getPlugInGroup('form');
		$form = $this->getForm();

		$pluginManager->runPlugins('getBottomContent', $this, 'form');
		$pluginBottom = implode("<br />", array_filter($pluginManager->data));

		$pluginManager->runPlugins('getTopContent', $this, 'form');
		$pluginTop = implode("<br />", array_filter($pluginManager->data));

		// Inserted after the form's closing </form> tag
		$pluginManager->runPlugins('getEndContent', $this, 'form');
		$pluginEnd = implode("<br />", array_filter($pluginManager->data));

		return array($pluginTop, $pluginBottom, $pluginEnd);
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
	public function getIntro()
	{
		$intro = $this->getForm()->intro;

		return $this->parseIntroOutroPlaceHolders($intro);
	}

	/**
	 * Parse into and outro text
	 *
	 * @param   string  $text  Text to parse
	 *
	 * @since   3.0.7
	 *
	 * @return  string
	 */
	public function parseIntroOutroPlaceHolders($text)
	{

		if (!$this->isEditable())
		{
			$remove = "/{new:\s*.*?}/is";
			$text = preg_replace($remove, '', $text);
			$remove = "/{edit:\s*.*?}/is";
			$text = preg_replace($remove, '', $text);
			$match = "/{details:\s*.*?}/is";
			$text = preg_replace_callback($match, array($this, '_getIntroOutro'), $text);
		}
		else
		{
			$match = $this->isNewRecord() ? 'new' : 'edit';
			$remove = $this->isNewRecord()  ? 'edit' : 'new';
			$match = "/{" . $match . ":\s*.*?}/is";
			$remove = "/{" . $remove . ":\s*.*?}/is";
			$text = preg_replace_callback($match, array($this, '_getIntroOutro'), $text);
			$text = preg_replace($remove, '', $text);
			$text = preg_replace("/{details:({(?>[^{}]+|(?1))*})}/is", '', $text);
			$text = preg_replace("/{details:\s*.*?}/is", '', $text);
		}

		$this->data['fabrik_view_url'] = $this->getListModel()->viewDetailsLink($this->data);
		$this->data['fabrik_edit_url'] = $this->getListModel()->editLink($this->data);

		$w    = new FabrikWorker;
		$text = $w->parseMessageForPlaceHolder($text, $this->data, true);

		// Jaanus: to remove content plugin code from intro and/or outro when plugins are not processed
		$params = $this->getParams();
		$jPlugins = (int) $params->get('process-jplugins', '2');

		if ($jPlugins === 0 || ($jPlugins === 2 && $this->isEditable()))
		{
			$text = preg_replace("/{\s*.*?}/i", '', $text);
		}

		$text = FabrikString::translate($text);

		return $text;
	}

	/**
	 * Used from getIntro as preg_replace_callback function to strip
	 * undesired text from form label intro
	 *
	 * @param   array  $match  Preg matched strings
	 *
	 * @return  string  intro text
	 */
	private function _getIntroOutro($match)
	{
		$m = explode(":", $match[0]);
		array_shift($m);
		$m = implode(":", $m);
		$m = FabrikString::rtrimword($m, "}");
		$m = preg_replace('/\[(\S+?)\]/', '{${1}}', $m);
		return $m;
	}

	/**
	 * Jaanus: see text above about intro
	 *
	 * @return  string  Outro
	 */
	public function getOutro()
	{
		$params = $this->getParams();
		$outro = $params->get('outro');

		return $this->parseIntroOutroPlaceHolders($outro);
	}

	/**
	 * Get the form's label
	 *
	 * @return  string  Label
	 */
	public function getLabel()
	{
		$label = $this->getForm()->label;

		if (!$this->isEditable())
		{
			return str_replace("{Add/Edit}", '', $label);
		}

		if (JString::stristr($label, "{Add/Edit}"))
		{
			$replace = $this->isNewRecord() ? FText::_('COM_FABRIK_ADD') : FText::_('COM_FABRIK_EDIT');
			$label = str_replace("{Add/Edit}", $replace, $label);
		}

		return FText::_($label);
	}

	/**
	 * Currently only called from listModel _createLinkedForm when copying existing table
	 *
	 * @TODO should move this to the admin model
	 *
	 * @return  object  Form table
	 */
	public function copy()
	{
		// Array key = old id value new id
		$this->groupidmap = array();
		$input = $this->app->input;
		$groupModels = $this->getGroups();
		$this->form = null;
		$form = $this->getTable();
		$form->id = false;

		// $$$ rob newFormLabel set in table copy
		if ($input->get('newFormLabel', '', 'string') !== '')
		{
			$form->label = $input->get('newFormLabel', '', 'string');
		}

		$res = $form->store();
		$newElements = array();

		foreach ($groupModels as $groupModel)
		{
			$oldId = $groupModel->getId();

			// $$$rob use + rather than array_merge to preserve keys
			$groupModel->_newFormid = $form->id;
			$newElements = $newElements + $groupModel->copy();
			$this->groupidmap[$oldId] = $groupModel->getGroup()->id;
		}
		// Need to do finalCopyCheck() on form elements
		$pluginManager = FabrikWorker::getPluginManager();

		// @TODO something not right here when copying a cascading dropdown element in a join group
		foreach ($newElements as $origId => $newId)
		{
			$plugin = $pluginManager->getElementPlugin($newId);
			$plugin->finalCopyCheck($newElements);
		}
		// Update the model's table to the copied one
		$this->form = $form;
		$this->setId($form->id);
		$this->newElements = $newElements;

		return $form;
	}

	/**
	 * Get the related lists (relations defined by db join foreign keys)
	 *
	 * @return  array  Links to view the related lists
	 */
	public function getRelatedTables()
	{
		$input = $this->app->input;
		$links = array();
		$params = $this->getParams();

		if (!$params->get('show-referring-table-releated-data', false))
		{
			return $links;
		}

		$listModel = $this->getListModel();
		$referringTable = JModelLegacy::getInstance('List', 'FabrikFEModel');

		// $$$ rob - not sure that referring_table is anything other than the form's table id
		// but for now just defaulting to that if no other variable found (e.g when links in sef urls)
		$tid = $input->getInt('referring_table', $input->getInt('listid', $listModel->getTable()->id));
		$referringTable->setId($tid);
		$tableParams = $referringTable->getParams();
		$table = $referringTable->getTable();
		$joinsToThisKey = $referringTable->getJoinsToThisKey();
		$linksToForms = $referringTable->getLinksToThisKey();
		$row = $this->getData();
		$facetedLinks = $tableParams->get('facetedlinks', null);

		if (is_null($facetedLinks))
		{
			return;
		}

		$linkedLists = $facetedLinks->linkedlist;
		$aExisitngLinkedForms = $facetedLinks->linkedform;
		$linkedform_linktype = $facetedLinks->linkedform_linktype;
		$linkedtable_linktype = $facetedLinks->linkedlist_linktype;
		$f = 0;
		$allPks = array();

		foreach ($joinsToThisKey as $joinKey => $element)
		{
			$key = $element->list_id . '-' . $element->form_id . '-' . $element->element_id;
			$qsKey = $referringTable->getTable()->db_table_name . '___' . $element->name;
			$val = $input->get($qsKey);

			if ($val == '')
			{
				// Default to row id if we are coming from a main link (and not a related data link)
				$val = $input->get($qsKey . '_raw', '', 'string');

				if (empty($val))
				{
					$thisKey = $this->getListModel()->getTable()->db_table_name . '___' . $element->join_key_column . '_raw';
					$val = FArrayHelper::getValue($this->data, $thisKey, $val);

					if (empty($val))
					{
						$val = $input->get('rowid');
					}
				}
			}

			/* $$$ tom 2012-09-14 - If we don't have a key value, get all.  If we have a key value,
			 * use it to restrict the count to just this entry.
			 */

			$pks = array();

			if (!empty($val))
			{
				$pks[] = $val;
			}

			$allPks[$key] = $pks;

			if (isset($linkedLists->$key) && $linkedLists->$key != 0)
			{
				$recordCounts = $referringTable->getRecordCounts($element, $pks);

				// Jaanus - 18.10.2013 - get correct element fullnames as link keys
				$linkKey = $recordCounts['linkKey'];

				/* $$$ hugh - changed to use _raw as key, see:
				 * http://fabrikar.com/forums/showthread.php?t=20020
				 */

				$linkKeyRaw = $linkKey . '_raw';
				$popUpLink = FArrayHelper::getValue($linkedtable_linktype->$key, $f, false);
				$count = is_array($recordCounts) && array_key_exists($val, $recordCounts) ? $recordCounts[$val]->total : 0;
				$label = $facetedLinks->linkedlistheader->$key == '' ? $element->listlabel : $facetedLinks->linkedlistheader->$key;
				$links[$element->list_id][] = $label . ': ' . $referringTable->viewDataLink($popUpLink, $element, null, $linkKey, $val, $count, $f);
			}

			$f++;
		}

		$f = 0;

		// Create columns containing links which point to forms associated with this table

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
						$linkKeyData = $referringTable->getRecordCounts($element, $allPks[$key]);
						$linkKey = $linkKeyData['linkKey'];
						$val = $input->get($linkKey, '', 'string');

						if ($val == '')
						{
							$qsKey = $referringTable->getTable()->db_table_name . '___' . $element->name;
							$val = $input->get($qsKey . '_raw', $input->get('rowid'));
						}

						// Jaanus: when no link to list and no form headers then people still know where they add data
						$fKey = $facetedLinks->linkedformheader->$key;
						$label = $fKey != '' ? ': ' . $fKey : (isset($linkedLists->$key) && $linkedLists->$key != 0 ? '' : ': ' . $element->listlabel);

						// Jaanus: label after add link if no list link helps to make difference between data view links and only add links.
						$links[$element->list_id][] = $referringTable->viewFormLink($popUpLink, $element, null, $linkKey, $val, false, $f) . $label;
					}
				}

				$f++;
			}
		}

		return $links;
	}

	/**
	 * Create the form's html class name.
	 * Based on column counts etc. as to whether form-horizontal applied
	 *
	 * @return  string
	 */
	public function getFormClass()
	{
		return 'fabrikForm';
	}

	/**
	 * Strip out any element names from url qs vars
	 *
	 * @param   string  $url  URL
	 *
	 * @return  string
	 */
	protected function stripElementsFromUrl($url)
	{
		$url = explode('?', $url);

		if (count($url) == 1)
		{
			return $url;
		}

		$filtered = array();
		$bits = explode('&', $url[1]);

		foreach ($bits as $bit)
		{
			$parts = explode('=', $bit);
			$key = $parts[0];
			$key = FabrikString::rtrimword($key, '_raw');

			if (!$this->hasElement($key))
			{
				$filtered[] = implode('=', $parts);
			}
		}

		$url = $url[0] . '?' . implode('&', $filtered);

		return $url;
	}

	/**
	 * Get the url to use as the form's action property
	 *
	 * @return	string	Url
	 */
	public function getAction()
	{
		$option = $this->app->input->get('option');
		$router = $this->app->getRouter();

		if ($this->app->isAdmin())
		{
			$action = filter_var(ArrayHelper::getValue($_SERVER, 'REQUEST_URI', 'index.php'), FILTER_SANITIZE_URL);
			$action = $this->stripElementsFromUrl($action);
			$action = str_replace("&", "&amp;", $action);

			return $action;
		}

		if ($option === 'com_' . $this->package)
		{
			$page = 'index.php?';

			// Get array of all querystring vars
			$uri = JURI::getInstance();

			/**
			 * Was $router->parse($uri);
			 * but if you had a module + form on a page using sef urls and
			 * Joomla's language switcher - calling parse() would re-parse the url and
			 * mung it well good and proper like.
			 *
			 */
			$queryVars = $router->getVars();

			if ($this->isAjax())
			{
				$queryVars['format'] = 'raw';
				unset($queryVars['view']);
				$queryVars['task'] = 'form.process';
			}

			$qs = array();

			foreach ($queryVars as $k => $v)
			{
				if ($k == 'rowid')
				{
					$v = $this->getRowId();
				}
				/* $$$ hugh - things get weird if we have been passed a urlencoded URL as a qs arg,
				 * which the $router->parse() above will have urldecoded, and it gets used as part of the URI path
				 * when we JRoute::_() below.  So we need to re-urlencode stuff and junk.
				 * Ooops, make sure it isn't an array, which we'll get if they have something like
				 * &table___foo[value]=bar
				 */
				if (!is_array($v))
				{
					$v = urlencode($v);
					$qs[] = $k . '=' . $v;
				}
				else
				{
					foreach ($v as $subV)
					{
						$qs[] = $k . '[]=' . urlencode($subV);
					}
				}
			}

			$action = $page . implode("&amp;", $qs);
			$action = JRoute::_($action);
		}
		else
		{
			// In plugin & SEF URLs
			if ((int) $router->getMode() === (int) JROUTER_MODE_SEF)
			{
				// $$$ rob if embedding a form in a form, then the embedded form's url will contain
				// the id of the main form - not sure if its an issue for now
				$action = filter_var(ArrayHelper::getValue($_SERVER, 'REQUEST_URI', 'index.php'), FILTER_SANITIZE_URL);
			}
			else
			{
				// In plugin and no sef (routing dealt with in form controller)
				$action = 'index.php';
			}
		}

		return $action;
	}

	/**
	 * If the group is a joined group we want to ensure that
	 * its id field is contained with in the group's elements
	 *
	 * @param   object  &$groupTable  Group table
	 *
	 * @return	string	HTML hidden field
	 */
	protected function _makeJoinIdElement(&$groupTable)
	{
		$listModel = $this->getListModel();
		$joinId = $this->aJoinGroupIds[$groupTable->id];
		$element = new stdClass;

		// Add in row id for join data
		$element->label = '';
		$element->labels = '';
		$element->error = '';
		$element->value = '';
		$element->id = '';
		$element->startRow = 0;
		$element->endRow = 0;
		$element->errorTag = '';
		$element->column = '';
		$element->className = '';
		$element->containerClass = '';

		foreach ($listModel->getJoins() as $oJoin)
		{
			if ($oJoin->id == $joinId)
			{
				$key = $oJoin->table_join . $this->joinTableElementStep . $oJoin->table_join_key;

				if (array_key_exists('join', $this->data))
				{
					// $$$ rob if join element is a db join the data $key contains label and not foreign key value
					if (@array_key_exists($key . '_raw', $this->data['join'][$joinId]))
					{
						$val = $this->data['join'][$joinId][$key . '_raw'];
					}
					else
					{
						$val = @$this->data['join'][$joinId][$key];
					}

					if (is_array($val))
					{
						$val = array_key_exists(0, $val) ? $val[0] : '';
					}
				}
				else
				{
					$val = '';
				}

				if ($val == '')
				{
					// Something's gone wrong - lets take the main table's key
					$k = $oJoin->join_from_table . $this->joinTableElementStep . $oJoin->table_key;
					$val = @$this->data[$k];
				}

				if (is_array($val))
				{
					$val = array_shift($val);
				}

				$element->value = $val;
				$element->element = '<input type="hidden" id="join.' . $joinId . '.rowid" name="join[' . $joinId . '][rowid]" value="' . $val
					. '" />';
				$element->hidden = true;
				$element->containerClass = 'fabrikElementContainer  fabrikHide';
			}
		}

		return $element;
	}

	/**
	 * Get an array of read only values
	 *
	 * @return  array
	 */
	public function getreadOnlyVals()
	{
		return $this->readOnlyVals;
	}

	/**
	 * Prepare the elements for rendering
	 *
	 * @param   string  $tmpl  Form template
	 *
	 * @since   3.0
	 *
	 * @return  array
	 */
	public function getGroupView($tmpl = '')
	{
		if (isset($this->groupView))
		{
			return $this->groupView;
		}

		$input = $this->app->input;

		// $$$rob - do regardless of whether form is editable as $data is required for hidden encrypted fields
		// and not used anywhere else (avoids a warning message)
		$data = array();
		/* $$$ rob - 3.0 for some reason just using $this->data was not right as join data was empty when editing existing record
		 * $$$ hugh - commented this out, as a) running getData() twice is expensive, and b) it blows away any changes onLoad plugins
		 * make to _data, like the juser plugin
		 * Ran this change for a couple of weeks before committing, seems to work without it.
		 *unset($this->data);
		 */
		$origData = $this->getData();

		foreach ($origData as $key => $val)
		{
			if (is_string($val))
			{
				$data[$key] = htmlspecialchars($val, ENT_QUOTES);
			}
			else
			{
				// Not sure what the htmlspecialchars is for above but if we don't assign here we loose join data
				$data[$key] = $val;
			}
		}

		$this->tmplData = $data;
		$this->groupView = array();
		$this->readOnlyVals = array();

		// $$$ hugh - temp foreach fix
		$groups = $this->getGroupsHiarachy();

		foreach ($groups as $gkey => $groupModel)
		{
			$groupTable = $groupModel->getGroup();
			$group = $groupModel->getGroupProperties($this);
			$groupParams = $groupModel->getParams();
			$aElements = array();

			// Check if group is actually a table join
			/*
			if (array_key_exists($groupTable->id, $this->aJoinGroupIds))
			{
				$aElements[] = $this->_makeJoinIdElement($groupTable);
			}
			*/

			$repeatGroup = 1;
			$foreignKey = null;
			$startHidden = false;
			$newGroup = false;

			if ($groupModel->canRepeat())
			{
				$joinTable = $groupModel->getJoinModel()->getJoin();
				$foreignKey = '';

				if (is_object($joinTable))
				{
					$repeatGroup = $groupModel->repeatCount();

					if ($repeatGroup === 0)
					{
						$newGroup = true;
						$repeatGroup = 1;
					}

					if (!$groupModel->fkPublished())
					{
						$startHidden = false;
					}
				}
			}
			// Test failed validated forms, repeat group counts are in request
			$repeatGroups = $input->get('fabrik_repeat_group', array(), 'array');

			if (!empty($repeatGroups))
			{
				$repeatGroup = FArrayHelper::getValue($repeatGroups, $gkey, $repeatGroup);

				if ($repeatGroup == 0)
				{
					$repeatGroup = 1;
					$startHidden = true;
				}

				$newGroup = false;
			}

			$groupModel->repeatTotal = $startHidden ? 0 : $repeatGroup;
			$aSubGroups = array();

			for ($c = 0; $c < $repeatGroup; $c++)
			{
				$aSubGroupElements = array();
				$elCount = 0;
				$elementModels = $groupModel->getPublishedElements();

				foreach ($elementModels as $elementModel)
				{
					/* $$$ rob ensure that the element is associated with the correct form (could occur if n plugins rendering form
					 * and detailed views of the same form.
					 */
					$elementModel->setFormModel($this);
					$elementModel->tmpl = $tmpl;
					$elementModel->newGroup = $newGroup;

					/* $$$rob test don't include the element in the form is we can't use and edit it
					 * test for captcha element when user logged in
					 */
					if (!$this->isEditable())
					{
						$elementModel->inDetailedView = true;
					}

					if (!$this->isEditable() && !$elementModel->canView())
					{
						continue;
					}

					$elementModel->_foreignKey = $foreignKey;
					$elementModel->_repeatGroupTotal = $repeatGroup - 1;
					$element = $elementModel->preRender($c, $elCount, $tmpl);

					// $$$ hugh - experimenting with adding non-viewable, non-editable to encrypted vars
					// if (!$element || ($elementModel->canView() && !$elementModel->canUse()))
					if (!$element || !$elementModel->canUse())
					{
						/* $$$ hugh - $this->data doesn't seem to always have what we need in it, but $data does.
						 * can't remember exact details, was chasing a nasty issue with encrypted 'user' elements.
						 */

						// $$$ rob HTMLName seems not to work for joined data in confirmation plugin
						$elementModel->getValuesToEncrypt($this->readOnlyVals, $data, $c);
						/**
						 * $$$ hugh - need to decode it if it's a string, 'cos we encoded $data up there ^^ somewhere, which
						 * then causes read only data to get changed to htmlencoded after submission.  See this thread for gory details:
						 * http://fabrikar.com/forums/index.php?threads/how-to-avoid-changes-to-an-element-with-a-read-only-link.37656/#post-192437
						 */
						$elName = $elementModel->getFullName(true, false);

						if (!is_array($this->readOnlyVals[$elName]['data']))
						{
							$this->readOnlyVals[$elName]['data'] = htmlspecialchars_decode($this->readOnlyVals[$elName]['data']);
						}

						$this->readOnlyVals[$elName]['repeatgroup'] = $groupModel->canRepeat();
						$this->readOnlyVals[$elName]['join'] = $groupModel->isJoin();
					}

					if ($element)
					{
						$elementModel->stockResults($element, $aElements, $this->data, $aSubGroupElements);
					}

					if ($element && !$element->hidden)
					{
						$elCount++;
					}
				}
				// If its a repeatable group put in subgroup
				if ($groupModel->canRepeat())
				{
					// Style attribute for group columns (need to occur after randomisation of the elements otherwise clears are not ordered correctly)
					$rowix = -1;

					foreach ($aSubGroupElements as $elKey => $element)
					{
						$rowix = $groupModel->setColumnCss($element, $rowix);
					}

					$aSubGroups[] = $aSubGroupElements;
				}
			}

			$groupModel->randomiseElements($aElements);

			// Style attribute for group columns (need to occur after randomisation of the elements otherwise clears are not ordered correctly)
			$rowix = -1;

			// Don't double setColumnCss otherwise weirdness ensues
			if (!$groupModel->canRepeat())
			{
				foreach ($aElements as $elKey => $element)
				{
					$rowix = $groupModel->setColumnCss($element, $rowix);
				}
			}

			$group->elements = $aElements;
			$group->subgroups = $aSubGroups;
			$group->startHidden = $startHidden;
			$group->repeatIntro = $groupParams->get('repeat_intro', '');

			$group->classArray[] = 'fabrikGroup';

			if ((int) $groupParams->get('group_columns', 1) == 1)
			{
				if (($this->isEditable() && $groupModel->labelPosition('form') !== 1)
					|| (!$this->isEditable() && $groupModel->labelPosition('details') !== 1))
				{
					$group->classArray[] = 'form-horizontal';
				}
			}

			$group->class = implode(' ', $group->classArray);
			$group->newGroup = $newGroup;

			// Only create the group if there are some element inside it
			if (count($aElements) != 0 && $groupModel->canView() !== false)
			{
				// 28/01/2011 $$$rob and if it is published
				$showGroup = (int) $groupParams->get('repeat_group_show_first');

				if ($showGroup !== 0)
				{
					// $$$ - hugh - testing new 'hide if no usable elements' option (4)
					// Jaanus: if not form view with "details only" option and not details view with "form only" option
					if (!($showGroup == 2 && $this->isEditable()) && !($showGroup == 3 && $input->get('view', 'form') == 'details')
						&& !($showGroup == 4 && !$groupModel->canView()))
					{
						$this->groupView[$group->name] = $group;
					}
				}
			}
		}

		return $this->groupView;
	}

	/**
	 * Get any fabrik tables that link to the join table
	 *
	 * @param   string  $table  Table name
	 *
	 * @return  array
	 */
	public function getLinkedFabrikLists($table)
	{
		if (!isset($this->linkedFabrikLists))
		{
			$this->linkedFabrikLists = array();
		}

		if (!array_key_exists($table, $this->linkedFabrikLists))
		{
			$db = FabrikWorker::getDbo(true);

			if (trim($table == ''))
			{
				return array();
			}
			else
			{
				$query = $db->getQuery(true);
				$query->select('*')->from('#__{package}_lists')->where('db_table_name = ' . $db->q($table));
				$db->setQuery($query);
			}

			$this->linkedFabrikLists[$table] = $db->loadColumn();
		}

		return $this->linkedFabrikLists[$table];
	}

	/**
	 * Used to see if something legitimate in the submission process, like a form plugin,
	 * has modified an RO element value and wants to override the RO/origdata.
	 *
	 * If $value is set, then this method additionally adds the modified value to the updated array.
	 *
	 * @param   string  $fullname  Full element name
	 * @param   mixed   $value     Optional value, states that a plugin update the readonly value of $fullname
	 *
	 * @return bool
	 */
	public function updatedByPlugin($fullname = '', $value = null)
	{
		if (isset($value))
		{
			$this->pluginUpdatedElements[$fullname] = $value;
		}

		return array_key_exists($fullname, $this->pluginUpdatedElements);
	}

	/**
	 * Populate the Model state
	 *
	 * @return  void
	 */
	protected function populateState()
	{
		$input = $this->app->input;

		if (!$this->app->isAdmin())
		{
			// Load the menu item / component parameters.
			$params = $this->app->getParams();
			$this->setState('params', $params);

			// Load state from the request.
			$pk = $input->getInt('formid', $params->get('formid'));
		}
		else
		{
			$pk = $input->getInt('formid');
		}

		$this->setState('form.id', $pk);
	}

	/**
	 * Is the form editable
	 *
	 * @return  bool
	 */
	public function isEditable()
	{
		return $this->editable;
	}

	/**
	 * Set editable state
	 *
	 * @param   bool  $editable  Editable state
	 *
	 * @since 3.0.7
	 *
	 * @return  void
	 */
	public function setEditable($editable)
	{
		$this->editable = $editable;
	}

	/**
	 * Helper method to get the session redirect key. Redirect plugin stores this
	 * other form plugins such as twitter or Paypal may need to query the session to perform the final redirect
	 * once the user has returned from those sites.
	 *
	 * @return  string  Session key to store redirect information (note: ends in '.')
	 */
	public function getRedirectContext()
	{
		return 'com_' . $this->package . '.form.' . $this->getId() . '.redirect.';
	}

	/**
	 * Resets cached form data.
	 *
	 * @param   bool  $unset_groups  Also reset group and element model cached data
	 *
	 * @return  void
	 */
	public function unsetData($unset_groups = false)
	{
		unset($this->data);
		unset($this->query);

		if ($unset_groups)
		{
			/* $$$ hugh - unset group published elements list, and clear each
			 * element's default data.  Needed from content plugin, otherwise if
			 * we render the same form more than once with different rowids, we end up
			 * rendering the first copy's element data X times.
			 * Not sure if we need to actually unset the group published elements list,
			 * but for the moment I'm just using a Big Hammer to get the content plugin working!
			 */
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

			unset($this->groups);
			$pluginManager = FabrikWorker::getPluginManager();
			$pluginManager->clearFormPlugins($this);
		}
	}

	/**
	 * Reset form's cached data, i.e. from content plugin, where we may be rendering the same
	 * form twice, with different row data.
	 *
	 * @return  void
	 */
	public function reset()
	{
		$this->unsetData(true);
	}

	/**
	 * Get redirect URL
	 *
	 * @param   bool  $incSession  Set url in session?
	 * @param   bool  $isMambot    Is Mambot
	 *
	 * @return   array  url: string  Redirect url, baseRedirect (True: default redirect, False: plugin redirect)
	 *
	 * @since 3.0.6 (was in form controller)
	 */
	public function getRedirectURL($incSession = true, $isMambot = false)
	{
		$input = $this->app->input;

		if ($this->app->isAdmin())
		{
			// Admin always uses option com_fabrik
			if (array_key_exists('apply', $this->formData))
			{
				$url = 'index.php?option=com_fabrik&task=form.view&formid=' . $input->getInt('formid') . '&rowid=' . $input->getString('rowid', '', 'string');
			}
			else
			{
				$url = 'index.php?option=com_fabrik&task=list.view&listid=' . $this->getListModel()->getId();
			}
		}
		else
		{
			if (array_key_exists('apply', $this->formData))
			{
				$url = 'index.php?option=com_' . $this->package . '&view=form&formid=' . $input->getInt('formid') . '&rowid=' . $input->getString('rowid', '', 'string')
					. '&listid=' . $input->getInt('listid');
			}
			else
			{
				if ($isMambot)
				{
					// Return to the same page
					$url = filter_var(ArrayHelper::getValue($_SERVER, 'HTTP_REFERER', 'index.php'), FILTER_SANITIZE_URL);
				}
				else
				{
					// Return to the page that called the form
					$url = urldecode($input->post->get('fabrik_referrer', 'index.php', 'string'));
				}

				$itemId = (int) FabrikWorker::itemId();

				if ($url == '')
				{
					if ($itemId !== 0)
					{
						$url = 'index.php?' . http_build_query($this->app->getMenu('site')->getActive()->query) . '&Itemid=' . $itemId;
					}
					else
					{
						// No menu link so redirect back to list view
						$url = 'index.php?option=com_' . $this->package . '&view=list&listid=' . $input->getInt('listid');
					}
				}
			}

			if ($this->config->get('sef'))
			{
				$url = JRoute::_($url);
			}
		}
		// 3.0 need to distinguish between the default redirect and redirect plugin
		$baseRedirect = true;

		if (!$incSession)
		{
			return array('url' => $url, 'baseRedirect' => $baseRedirect);
		}

		$formdata = $this->session->get('com_' . $this->package . '.form.data');
		$context = $this->getRedirectContext();

		// If the redirect plug-in has set a url use that in preference to the default url
		$sUrl = $this->session->get($context . 'url', array());

		if (!empty($sUrl))
		{
			$baseRedirect = false;
		}

		if (!is_array($sUrl))
		{
			$sUrl = array($sUrl);
		}

		if (empty($sUrl))
		{
			$sUrl[] = $url;
		}

		$url = array_shift($sUrl);
		$this->session->set($context . 'url', $sUrl);

		// Redirect URL which set prefilters of < were converted to &lt; which then gave mySQL error
		$url = htmlspecialchars_decode($url);

		return array('url' => $url, 'baseRedirect' => $baseRedirect);
	}

	/**
	 * Should we show success messages
	 *
	 * @since  3.0.7
	 *
	 * @return boolean
	 */
	public function showSuccessMsg()
	{
		$mode = $this->getParams()->get('suppress_msgs', '0');

		return ($mode == 0 || $mode == 2);
	}

	/**
	 * Get the success message
	 *
	 * @return  string
	 */
	public function getSuccessMsg()
	{
		$registry = $this->session->get('registry');

		// $$$ rob 30/03/2011 if using as a search form don't show record added message
		if ($registry && $registry->get('com_' . $this->package . '.searchform.fromForm') != $this->get('id'))
		{
			if (!$this->showSuccessMsg())
			{
				return '';
			}

			$params = $this->getParams();

			return FText::_($params->get('submit-success-msg', 'COM_FABRIK_RECORD_ADDED_UPDATED'));
		}
		else
		{
			return '';
		}
	}

	/**
	 * Should we show ACL messages
	 *
	 * @since  3.0.7
	 *
	 * @return boolean
	 */
	public function showACLMsg()
	{
		$mode = $this->getParams()->get('suppress_msgs', '0');

		return $mode == 0 || $mode == 1;
	}

	/**
	 * If trying to add/edit a record when the user doesn't have rights to do so,
	 * what message, if any should we show.
	 *
	 * @param  bool  $force  if true don't check if messages suppressed
	 *
	 * @since  3.0.7
	 *
	 * @return string
	 */
	public function aclMessage($force = false)
	{
		if (!$force && !$this->showACLMsg())
		{
			return '';
		}

		$input = $this->app->input;
		$msg = $input->get('rowid', '', 'string') == 0 ? 'COM_FABRIK_NOTICE_CANT_ADD_RECORDS' : 'COM_FABRIK_NOTICE_CANT_EDIT_RECORDS';

		return FText::_($msg);
	}

	/**
	 * Say a form is embedded in an article, and is set to redirect on same/new page (so not in popup)
	 * Then we need to grab and re-apply the redirect/thanks message
	 *
	 * @return  void
	 */
	public function applyMsgOnce()
	{
		if (!$this->app->input->get('isMambot'))
		{
			// Don't apply if not isMambot
			return;
		}

		// Proceed, isMambot set in PlgFabrik_FormRedirect::buildJumpPage()
		$context = $this->getRedirectContext();
		$msg = $this->session->get($context . 'msg', array());

		if (!empty($msg))
		{
			$msg = FArrayHelper::getValue($msg, 0);
			$this->app->enqueueMessage($msg);
		}
		// Ensure its only shown once even if page is refreshed with isMambot in querystring
		$this->session->clear($context . 'msg');
	}

	/**
	 * Get redirect message
	 *
	 * @return  string  Redirect message
	 *
	 * @since   3.0.6 (was in form controller)
	 */
	public function getRedirectMessage()
	{
		if (!$this->showSuccessMsg())
		{
			return '';
		}

		$msg = $this->getSuccessMsg();
		$context = $this->getRedirectContext();
		$sMsg = $this->session->get($context . 'msg', array($msg));

		if (!is_array($sMsg))
		{
			$sMsg = array($sMsg);
		}

		if (empty($sMsg))
		{
			$sMsg[] = $msg;
		}

		/**
		 * $$$ rob Was using array_shift to set $msg, not to really remove it from $sMsg
		 * without the array_shift the custom message is never attached to the redirect page.
		 * Use-case: redirect plugin with jump page pointing to a J page and thanks message selected.
		 */
		$customMsg = array_keys($sMsg);
		$customMsg = array_shift($customMsg);
		$customMsg = FArrayHelper::getValue($sMsg, $customMsg);

		if ($customMsg != '')
		{
			$msg = $customMsg;
		}

		$q = $this->app->getMessageQueue();
		$found = false;

		foreach ($q as $m)
		{
			// Custom message already queued - unset default msg
			if ($m['type'] == 'message' && trim($m['message']) !== '')
			{
				$found = true;
				break;
			}
		}

		if ($found)
		{
			$msg = null;
		}

		$showMsg = null;
		$this->session->set($context . 'msg', $sMsg);
		$showMsg = (array) $this->session->get($context . 'showsystemmsg', array(true));

		if (is_array($showMsg))
		{
			$showMsg = array_shift($showMsg);
		}

		$msg = $showMsg == 1 ? $msg : '';

		// $$$ hugh - testing allowing placeholders in success msg
		$w = new FabrikWorker;
		$msg = $w->parseMessageForPlaceHolder($msg, $this->data);

		return $msg;
	}

	/**
	 * Build the JS key that the model uses in the view. This key is assigned to Fabrik.blocks
	 *
	 * @since   3.1rc1
	 *
	 * @return  string
	 */
	public function jsKey()
	{
		$key = $this->isEditable() ? 'form_' . $this->getId() : 'details_' . $this->getId();

		if ($this->getRowId() != '')
		{
			$key .= '_' . $this->getRowId();
		}

		return $key;
	}

	/**
	 * Ask all elements to add their js Fabrik.jLayouts to the framework
	 * This has to be done before we call FabrikHelperHTML::framework();
	 *
	 * @return void;
	 */
	public function elementJsJLayouts()
	{
		$groups = $this->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$elementModel->jsJLayouts();
			}
		}
	}

	/**
	 * Get a subset of the model's data with non accessible values removed
	 *
	 * @param   string  $view  View
	 *
	 * @return  array data
	 */
	public function accessibleData($view = 'form')
	{
		$accessibleData = $this->data;

		$groups = $this->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				switch ($view)
				{
					default:
					case 'form':
						$accessible = $elementModel->canUse($view);
						break;
					case 'details':
						$accessible = $elementModel->canView('form');
						break;
					case 'list':
						$accessible = $elementModel->canView('list');
						break;
				}

				if (!$accessible)
				{
					$name = $elementModel->getFullName(true, false);
					unset($accessibleData[$name]);
					unset($accessibleData[$name . '_raw']);
				}
			}
		}

		return $accessibleData;
	}


	/**
	 * Get a form JLayout file
	 *
	 * @param   string  $name     layout name
	 * @param   array   $paths    Optional paths to add as includes
	 * @param   array   $options  Options
	 *
	 * @return LayoutFile
	 */
	public function getLayout($name, $paths = array(), $options = array())
	{
		$view = $this->isEditable() ? 'form' : 'details';
		$paths[] = COM_FABRIK_FRONTEND . '/views/'. $view . '/tmpl/' . $this->getTmpl() . '/layouts';
		$layout  = FabrikHelperHTML::getLayout($name, $paths, $options);

		return $layout;
	}

	public function recordInDatabase()
	{
		$form = $this->getForm();

		return $form->record_in_database === '1';
	}
}
