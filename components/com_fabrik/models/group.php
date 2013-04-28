<?php
/**
 * Fabrik Group Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

/**
 * Fabrik Group Model
 *
 * @package  Fabrik
 * @since    3.0
 */

class FabrikFEModelGroup extends FabModel
{

	/**
	 * Parameters
	 *
	 * @var JRegistry
	 */
	protected $_params = null;

	/**
	 * Id of group to load
	 *
	 * @var int
	 */
	var $_id = null;

	/**
	 * Group table
	 *
	 * @var JTable
	 */
	var $_group = null;

	/**
	 * Form model
	 *
	 * @var FabrikFEModelForm
	 */
	protected $_form = null;

	/**
	 * List model
	 *
	 * @var FabrikFEModelList
	 */
	var $_table = null;

	/**
	 * Join model
	 *
	 * @var FabrikFEModelJoin
	 */
	var $_joinModel = null;

	/**
	 * Element plugins
	 *
	 * @var array
	 */
	public $elements = null;

	/**
	 * Published element plugins
	 *
	 * @var array
	 */
	public $publishedElements = null;

	/**
	 * Published element plugins shown in the list
	 *
	 * @var array
	 */
	protected $publishedListElements = null;

	/**
	 * How many times the group's data is repeated
	 *
	 *  @var int
	 */
	public $repeatTotal = null;

	/**
	 * Form ids that the group is in (maximum of one value)
	 *
	 * @var array
	 */
	protected $formsIamIn = null;

	/**
	 * Can the group be viewed (set to false if no elements are visible in the group
	 *
	 * @var bool
	 */
	protected $canView = null;

	/**
	* Can the group be edited (if false, will override element ACL's and make all elements read only)
	*
	* @var bool
	*/
	protected $canEdit = null;

	/**
	 * Constructor
	 *
	 * @param   array  $config  An array of configuration options (name, state, dbo, table_path, ignore_request).
	 */

	public function __construct($config = array())
	{
		parent::__construct($config);
	}

	/**
	 * Method to set the group id
	 *
	 * @param   int  $id  group ID number
	 *
	 * @return  void
	 */

	public function setId($id)
	{
		// Set new group ID
		$this->_id = $id;
		$this->id = $id;
	}

	/**
	 * Get group id
	 *
	 * @return int
	 */

	public function getId()
	{
		return $this->get('id');
	}

	/**
	 * Get group table
	 *
	 * @return  FabrikTableGroup
	 */

	public function &getGroup()
	{
		if (is_null($this->_group))
		{
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
			$this->_group = FabTable::getInstance('Group', 'FabrikTable');
			$this->_group->load($this->getId());
		}
		return $this->_group;
	}

	/**
	 * Set the group row
	 *
	 * @param   FabTableGroup  $group  Fabrik table
	 *
	 * @since   3.0.5
	 *
	 * @return  void
	 */

	public function setGroup($group)
	{
		$this->_group = $group;
	}

	/**
	* Can the user edit the group
	*
	* @return   bool
	*/

	public function canEdit()
	{
		/**
		 * First cut at this code, need to add actual ACL setting for edit
		 *
		 * Mostly needed so people can run plugins on this hook, to set groups to read only
		 */
		if (!is_null($this->canEdit))
		{
			return $this->canEdit;
		}
		$params = $this->getParams();
		$this->canEdit = true;
		// If group show is type 5, then read only.
		if ($params->get('repeat_group_show_first', '1') == '5')
		{
			$this->canEdit = false;
		}

		$formModel = $this->getFormModel();
		//$row = $formModel()->getData();
		$pluginCanEdit = FabrikWorker::getPluginManager()->runPlugins('onCanEditGroup', $formModel, 'form', $this);
		if (empty($pluginCanEdit))
		{
			$pluginCanEdit = true;
		}
		else
		{
			$pluginCanEdit = !in_array(false, $pluginCanEdit);
		}
		$this->canEdit = $pluginCanEdit;

		return $this->canEdit;
	}

	/**
	 * Can the user view the group
	 *
	 * @return   bool
	 */

	public function canView()
	{
		if (!is_null($this->canView))
		{
			return $this->canView;
		}
		$params = $this->getParams();
		$elementModels = $this->getPublishedElements();
		$this->canView = false;
		foreach ($elementModels as $elementModel)
		{
			// $$$ hugh - added canUse() check, corner case, see:
			// http://fabrikar.com/forums/showthread.php?p=111746#post111746
			if (!$elementModel->canView() && !$elementModel->canUse())
			{
				continue;
			}
			$this->canView = true;
			break;
		}

		// Get the group access level
		$user = JFactory::getUser();
		$groups = $user->getAuthorisedViewLevels();
		$groupAccess = $params->get('access', '');
		if ($groupAccess !== '')
		{
			$this->canView = in_array($groupAccess, $groups);

			// If the user can't access the group return that and ingore repeat_group_show_first option
			if (!$this->canView)
			{
				return $this->canView;
			}
		}

		/*
		 * Sigh - seems that the repeat group 'repeat_group_show_first' property has been bastardized to be a setting
		 * that is applicable to a group even when not in a repeat group, and has basically become a standard group setting.
		 * My bad for labelling it poorly to start with.
		 * So, now if this is set to 'no' the group is not shown but canView was returning true - doh! Caused issues in
		 * multi page forms where we were trying to set/check errors in groups which were not attached to the form.
		 */
		$formModel = $this->getFormModel();
		$showGroup = $params->get('repeat_group_show_first', '1');
		if ($showGroup == 0)
		{
			$this->canView = false;
		}

		// If editable but only show group in details view:
		if ($formModel->isEditable() && $showGroup == 2)
		{
			$this->canView = false;
		}

		// If form not editable and show group in form view:
		if (!$formModel->isEditable() && $showGroup == 3)
		{
			$this->canView = false;
		}

		return $this->canView;
	}

	/**
	 * Set the context in which the element occurs
	 *
	 * @param   object  $formModel  Form model
	 * @param   object  $listModel  List model
	 *
	 * @return void
	 */

	public function setContext($formModel, $listModel)
	{
		$this->_form = $formModel;
		$this->_table = $listModel;
	}

	/**
	 * Get an array of forms that the group is in
	 * NOTE: now a group can only belong to one form
	 *
	 * @return  array  form ids
	 */

	public function getFormsIamIn()
	{
		if (!isset($this->formsIamIn))
		{
			$db = FabrikWorker::getDbo(true);
			$query = $db->getQuery(true);
			$query->select('form_id')->from('#__{package}_formgroup')->where('group_id = ' . (int) $this->getId());
			$db->setQuery($query);
			$this->formsIamIn = $db->loadColumn();
			if (!$db->execute())
			{
				return JError::raiseError(500, $db->getErrorMsg());
			}
		}
		return $this->formsIamIn;
	}

	/**
	 * Returns array of elements in the group
	 *
	 * NOTE: pretty sure that ->elements will already be loaded
	 * within $formModel->getGroupsHiarachy()
	 *
	 * @return  array	element objects (bound to element plugin)
	 */

	public function getMyElements()
	{
		// Elements should generally have already been loaded via the pluginmanager getFormPlugins() method
		if (!isset($this->elements))
		{
			$group = $this->getGroup();
			$this->elements = array();
			$pluginManager = FabrikWorker::getPluginManager();
			$formModel = $this->getFormModel();
			$allGroups = $pluginManager->getFormPlugins($formModel);
			if (empty($this->elements))
			{
				// Horrible hack for when saving group

				/*
				 * $$$ rob Using @ for now as in inline edit in podion you get multiple notices when
				 * saving the status element
				 */
				$this->elements = @$allGroups[$this->getId()]->elements;
			}
		}
		return $this->elements;
	}

	/**
	 * Randomise the element list (note the array is the pre-rendered elements)
	 *
	 * @param   array  &$elements  form views processed/formatted list of elements that the form template uses
	 *
	 * @return  void
	 */

	public function randomiseElements(&$elements)
	{
		if ($this->getParams()->get('random', false) == true)
		{
			$keys = array_keys($elements);
			shuffle($keys);
			foreach ($keys as $key)
			{
				$new[$key] = $elements[$key];
			}
			$elements = $new;
		}
	}

	/**
	 * Set the element column css allows for group colum settings to be applied
	 *
	 * @param   object  &$element  prerender element properties
	 * @param   int     $elCount   current key when looping over elements.
	 *
	 * @since 	Fabrik 3.0.5.2
	 *
	 * @return  int  the next column count
	 */

	public function setColumnCss(&$element, $elCount)
	{
		$params = $this->getParams();
		$element->column = '';
		$colcount = (int) $params->get('group_columns');

		// Bootstrap grid formatting
		$spans = $this->columnSpans();
		if ($colcount === 0)
		{
			$colcount = 1;
		}
		$spanKey = ($elCount -1) % $colcount;

		$element->span = $colcount == 0 ? 'span12' : JArrayHelper::getValue($spans, $spanKey, 'span' . floor(12 / $colcount));
		$element->span = ' ' . $element->span;
		$element->offset = $params->get('group_offset', 0);

		$element->startRow = false;
		$element->endRow = false;
		if ($colcount > 1)
		{
			$widths = $params->get('group_column_widths', '');
			$w = floor((100 - ($colcount * 6)) / $colcount) . '%';
			if ($widths !== '')
			{
				$widths = explode(',', $widths);
				$w = JArrayHelper::getValue($widths, ($elCount - 1) % $colcount, $w);
			}
			$element->column = ' style="float:left;width:' . $w . ';';
			if ($elCount !== 0 && (($elCount - 1) % $colcount == 0) || $element->hidden)
			{
				$element->startRow = 1;
				$element->column .= "clear:both;";
			}

			if ($element->hidden)
			{
				$element->endRow = 1;
			}
			else
			{
				if ((($elCount -1) % $colcount === $colcount - 1))
				{
					// echo $element->label_raw . ($elCount - 1) . "  % $colcount = " . $elCount % $colcount . " = " . ($colcount - 1) . " (yes)<br>";
					$element->endRow = 1;
				}
				else
				{
					// echo $element->label_raw . ($elCount - 1) . " $elCount % $colcount = " . $elCount % $colcount . " = " . ($colcount - 1) . "(no)<br>";

				}
			}
			$element->column .= '" ';
		}
		else
		{
			$element->startRow = 0;
			$element->endRow = 0;
			$element->span = '';
			$element->column .= ' style="clear:both;width:100%;"';
		}
		// $$$ rob only advance in the column count if the element is not hidden
		if (!$element->hidden)
		{
			$elCount++;
		}
		return $elCount;
	}

	/**
	 * Work out the bootstrap column spans for the group
	 * Assigned to each element in setColumnCss()
	 * Looks at the property group_column_widths which accepts either % or 1-12 as values
	 *
	 * @since 3.0b
	 *
	 * @return  array
	 */

	public function columnSpans()
	{
		$params = $this->getParams();
		$widths = $params->get('group_column_widths', '');
		$widths = explode(',', $widths);
		if (FabrikWorker::j3())
		{
			foreach ($widths as &$w)
			{
				if ($w == '')
				{
					$w = 6;
				}
				if (strstr($w, '%'))
				{
					$w = (int) str_replace('%', '', $w);
					$w = floor(($w / 100) * 12);
				}
				$w = ' span' . $w;
			}
		}
		return $widths;
	}

	/**
	 * Alias to getFormModel()
	 *
	 * @deprecated
	 *
	 * @return object form model
	 */

	public function getForm()
	{
		return $this->getFormModel();
	}

	/**
	* Get the groups form model
	*
	* @return object form model
	*/

	public function getFormModel()
	{
		if (!isset($this->_form))
		{
			$formids = $this->getFormsIamIn();
			$formid = empty($formids) ? 0 : $formids[0];
			$this->_form = JModel::getInstance('Form', 'FabrikFEModel');
			$this->_form->setId($formid);
			$this->_form->getForm();
			$this->_form->getlistModel();
		}
		return $this->_form;
	}

	/**
	 * Get the groups list model
	 *
	 * @return  object	list model
	 */

	public function getlistModel()
	{
		return $this->getFormModel()->getlistModel();
	}

	/**
	 * Get an array of published elements
	 *
	 * @since 120/10/2011 - can override with elementid request data (used in inline edit to limit which elements are shown)
	 *
	 * @return  array	published element objects
	 */

	public function getPublishedElements()
	{
		if (!isset($this->publishedElements))
		{
			$this->publishedElements = array();
		}
		$app = JFactory::getApplication();
		$ids = (array) $app->input->get('elementid', array(), 'array');
		$sig = implode('.', $ids);
		if (!array_key_exists($sig, $this->publishedElements))
		{
			$this->publishedElements[$sig] = array();
			$elements = $this->getMyElements();
			foreach ($elements as $elementModel)
			{
				$element = $elementModel->getELement();
				if ($element->published == 1)
				{
					if (empty($ids) || in_array($element->id, $ids))
					{
						$this->publishedElements[$sig][] = $elementModel;
					}
				}
			}
		}
		return $this->publishedElements[$sig];
	}

	/**
	 * Get a list of all elements which are set to show in list or
	 * are set to include in list query
	 *
	 * @since   3.0.6
	 *
	 * @return  array  list of element models
	 */

	public function getListQueryElements()
	{
		if (!isset($this->listQueryElements))
		{
			$this->listQueryElements = array();
		}
		$app = JFactory::getApplication();
		$input = $app->input;

		// $$$ rob fabrik_show_in_list set in admin module params (will also be set in menu items and content plugins later on)
		// its an array of element ids that should be show. Overrides default element 'show_in_list' setting.
		$showInList = $input->get('fabrik_show_in_list', array(), 'array');
		$sig = empty($showInList) ? 0 : implode('.', $showInList);
		if (!array_key_exists($sig, $this->listQueryElements))
		{
			$this->listQueryElements[$sig] = array();
			$elements = $this->getMyElements();
			foreach ($elements as $elementModel)
			{
				$element = $elementModel->getElement();
				$params = $elementModel->getParams();
				/**
				 * $$$ hugh - experimenting adding non-viewable data to encrypted vars on forms,
				 * also we need them in addDefaultDataFromRO()
				 * if ($element->published == 1 && $elementModel->canView())
				 */
				if ($element->published == 1)
				{
					/**
					 * As this function seems to be used to build both the list view and the form view, we should NOT
					 * include elements in the list query if the user can not view them, as their data is sent to the json object
					 * and thus visible in the page source
					 */
					if ($input->get('view') == 'list' && !$elementModel->canView('list'))
					{
						continue;
					}

					if (empty($showInList))
					{
						if ($element->show_in_list_summary || $params->get('include_in_list_query', 1) == 1)
						{
							$this->listQueryElements[$sig][] = $elementModel;
						}
					}
					else
					{
						if (in_array($element->id, $showInList) || $params->get('include_in_list_query', 1) == 1)
						{
							$this->listQueryElements[$sig][] = $elementModel;
						}
					}
				}
			}
		}
		return $this->listQueryElements[$sig];
	}

	/**
	 * Get published elements to show in list
	 *
	 * @return  array
	 */

	public function getPublishedListElements()
	{
		if (!isset($this->publishedListElements))
		{
			$this->publishedListElements = array();
		}
		$app = JFactory::getApplication();
		$input = $app->input;

		// $$$ rob fabrik_show_in_list set in admin module params (will also be set in menu items and content plugins later on)
		// its an array of element ids that should be show. Overrides default element 'show_in_list' setting.
		$showInList = (array) $input->get('fabrik_show_in_list', array(), 'array');
		$sig = empty($showInList) ? 0 : implode('.', $showInList);
		if (!array_key_exists($sig, $this->publishedListElements))
		{
			$this->publishedListElements[$sig] = array();
			$elements = $this->getMyElements();
			foreach ($elements as $elementModel)
			{
				$element = $elementModel->getElement();
				if ($element->published == 1 && $elementModel->canView('list'))
				{
					if (empty($showInList))
					{
						if ($element->show_in_list_summary)
						{
							$this->publishedListElements[$sig][] = $elementModel;
						}
					}
					else
					{
						if (in_array($element->id, $showInList))
						{
							$this->publishedListElements[$sig][] = $elementModel;
						}
					}
				}
			}
		}
		return $this->publishedListElements[$sig];
	}

	/**
	 * Is the group a repeat group
	 *
	 * @return  bool
	 */

	public function canRepeat()
	{
		$params = $this->getParams();
		return $params->get('repeat_group_button');
	}

	/**
	 * Can the user add a repeat group
	 *
	 * @since   3.0.1
	 *
	 * @return  bool
	 */

	public function canAddRepeat()
	{
		$params = $this->getParams();
		$ok = $this->canRepeat();
		if ($ok)
		{
			$user = JFactory::getUser();
			$groups = $user->getAuthorisedViewLevels();
			$ok = in_array($params->get('repeat_add_access', 1), $groups);
		}
		return $ok;

	}

	/**
	 * Can the user delete a repeat group
	 *
	 * @since   3.0.1
	 *
	 * @return  bool
	 */

	public function canDeleteRepeat()
	{
		$ok = false;
		if ($this->canRepeat())
		{
			$params = $this->getParams();
			$row = $this->getFormModel()->getData();
			$ok = FabrikWorker::canUserDo($params, $row, 'repeat_delete_access_user');
			if ($ok === -1)
			{
				$user = JFactory::getUser();
				$groups = $user->getAuthorisedViewLevels();
				$ok = in_array($params->get('repeat_delete_access', 1), $groups);
			}
		}
		return $ok;
	}

	/**
	* Is the group a repeat group
	*
	* @return  bool
	*/

	public function canCopyElementValues()
	{
		$params = $this->getParams();
		return $params->get('repeat_copy_element_values', '0') === '1';
	}

	/**
	 * Is the group a join?
	 *
	 * @return  bool
	 */

	public function isJoin()
	{
		return $this->getGroup()->is_join;
	}

	/**
	 *
	 * Get the group's join_id
	 *
	 * @return  mixed   join_id, or false if not a join
	 */
	public function getJoinId()
	{
		if (!$this->isJoin())
		{
			return false;
		}
		return $this->getGroup()->join_id;
	}

	/**
	 * Get the group's associated join model
	 *
	 * @return  object  join model
	 */

	public function getJoinModel()
	{
		$group = $this->getGroup();
		if (is_null($this->_joinModel))
		{
			$this->_joinModel = JModel::getInstance('Join', 'FabrikFEModel');
			$this->_joinModel->setId($group->join_id);
			$js = $this->getListModel()->getJoins();

			// $$$ rob set join models data from preloaded table joins - reduced load time
			for ($x = 0; $x < count($js); $x++)
			{
				if ($js[$x]->id == $group->join_id)
				{
					$this->_joinModel->setData($js[$x]);
					break;
				}
			}

			$this->_joinModel->getJoin();
		}
		return $this->_joinModel;
	}

	/**
	 * Get group params
	 *
	 * @return  object	params
	 */

	public function &getParams()
	{
		if (!$this->_params)
		{
			$this->_params = new JRegistry($this->getGroup()->params);
		}
		return $this->_params;
	}

	/**
	 * Make a group object to be used in the form view. Object contains
	 * group display properties
	 *
	 * @param   object  &$formModel  form model
	 *
	 * @return  object	group display properties
	 */

	public function getGroupProperties(&$formModel)
	{
		$w = new FabrikWorker;
		$group = new stdClass;
		$groupTable = $this->getGroup();
		$params = $this->getParams();
		if (!isset($this->_editable))
		{
			$this->_editable = $formModel->_editable;
		}
		if ($this->_editable)
		{
			// If all of the groups elements are not editable then set the group to uneditable
			$elements = $this->getPublishedElements();
			$editable = false;
			foreach ($elements as $element)
			{
				if ($element->canUse())
				{
					$editable = true;
				}
			}
			if (!$editable)
			{
				$this->_editable = false;
			}
		}
		$group->editable = $this->_editable;
		$group->canRepeat = $params->get('repeat_group_button', '0');
		$showGroup = $params->def('repeat_group_show_first', '1');

		$pages = $formModel->getPages();

		$startpage = isset($formModel->sessionModel->last_page) ? $formModel->sessionModel->last_page : 0;
		/**
		 * $$$ hugh - added array_key_exists for (I think!) corner case where group properties have been
		 * changed to remove (or change) paging, but user still has session state set.  So it was throwing
		 * a PHP 'undefined index' notice.
		 */
		if (array_key_exists($startpage, $pages) && is_array($pages[$startpage]) && !in_array($groupTable->id, $pages[$startpage]) || $showGroup == -1 || $showGroup == 0)
		{
			$groupTable->css .= ";display:none;";
		}
		$group->css = trim(str_replace(array("<br />", "<br>"), "", $groupTable->css));
		$group->id = $groupTable->id;

		if (JString::stristr($groupTable->label, "{Add/Edit}"))
		{
			$replace = ((int) $formModel->_rowId === 0) ? JText::_('COM_FABRIK_ADD') : JText::_('COM_FABRIK_EDIT');
			$groupTable->label = str_replace("{Add/Edit}", $replace, $groupTable->label);
		}
		$group->title = $w->parseMessageForPlaceHolder($groupTable->label, $formModel->_data, false);

		$group->name = $groupTable->name;
		$group->displaystate = ($group->canRepeat == 1 && $formModel->isEditable()) ? 1 : 0;
		$group->maxRepeat = (int) $params->get('repeat_max');
		$group->minRepeat = (int) $params->get('repeat_min');
		$group->showMaxRepeats = $params->get('show_repeat_max', '0') == '1';
		$group->canAddRepeat = $this->canAddRepeat();
		$group->canDeleteRepeat = $this->canDeleteRepeat();
		return $group;
	}

	/**
	 * Copies a group, form group and its elements
	 * (when copying a table (and hence a group) the groups join is copied in table->copyJoins)
	 *
	 * @return  array	an array of new element id's keyed on original elements that have been copied
	 */

	public function copy()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$elements = $this->getMyElements();
		$group = $this->getGroup();

		// NewGroupNames set in table copy
		$newNames = $input->get('newGroupNames', array(), 'array');
		if (array_key_exists($group->id, $newNames))
		{
			$group->name = $newNames[$group->id];
		}
		$group->id = null;
		$group->store();

		$newElements = array();
		foreach ($elements as $element)
		{
			$origElementId = $element->getElement()->id;
			$copy = $element->copyRow($origElementId, $element->getElement()->label, $group->id);
			$newElements[$origElementId] = $copy->id;
		}
		$this->elements = null;
		$elements = $this->getMyElements();

		// Create form group
		$formid = isset($this->_newFormid) ? $this->_newFormid : $this->getFormModel()->getId();
		$formGroup = FabTable::getInstance('FormGroup', 'FabrikTable');
		$formGroup->form_id = $formid;
		$formGroup->group_id = $group->id;
		$formGroup->ordering = 999999;
		if (!$formGroup->store())
		{
			JError::raiseError(500, $formGroup->getError());
		}
		$formGroup->reorder(" form_id = '$formid'");
		return $newElements;
	}

	/**
	 * Resets published element cache
	 *
	 * @return  void
	 */

	public function resetPublishedElements()
	{
		unset($this->publishedElements);
		unset($this->publishedListElements);
		unset($this->elements);
	}

}
