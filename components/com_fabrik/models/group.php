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

/**
 * Fabrik Group Model
 *
 * @package  Fabrik
 * @since    3.0
 */

class FabrikFEModelGroup extends FabModel
{

	/** @var object parameters */
	protected $_params = null;

	/** @var int id of group to load */
	var $_id = null;

	/** @var object group table */
	var $_group = null;

	/** @var object form model */
	protected $_form = null;

	/** @var object list model */
	var $_table = null;

	var $_joinModel = null;

	/** @var array of element plugins */
	public $elements = null;

	/** @var array of published element plugins */
	public $publishedElements = null;

	/** @var array of published element plugins shown in the list */
	protected $publishedListElements = null;

	/** @var int how many times the group's data is repeated */
	public $repeatTotal = null;

	/** @var array of form ids that the group is in (maximum of one value)*/
	protected $formsIamIn = null;

	/** @var bool can the group be viewed (set to false if no elements are visible in the group**/
	protected $canView = null;

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
	 * @param   FabTableGroup  $group  fabrik table
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
		}
		return $this->canView;
	}

	/**
	 * set the context in which the element occurs
	 *
	 * @param   object  $formModel  form model
	 * @param   object  $listModel  list model
	 *
	 * @return void
	 */

	public function setContext($formModel, $listModel)
	{
		$this->_form = $formModel;
		$this->_table = $listModel;
	}

	/**
	 * get an array of forms that the group is in
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
			if (!$db->query())
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
			$form = $this->getFormModel();
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
	 * randomise the element list (note the array is the pre-rendered elements)
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
	 * set the element column css allows for group colum settings to be applied
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
				$element->startRow = true;
				$element->column .= "clear:both;";
			}
			if (($elCount % $colcount === $colcount - 1) || $element->hidden)
			{
				$element->endRow = true;
			}
			$element->column .= '" ';
		}
		else
		{
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
	 * get the groups list model
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
		$ids = (array) JRequest::getVar('elementid');
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
	 * get a list of all elements which are set to show in list or are set to include in list query
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
		// $$$ rob fabrik_show_in_list set in admin module params (will also be set in menu items and content plugins later on)
		// its an array of element ids that should be show. Overrides default element 'show_in_list' setting.
		$showInList = (array) JRequest::getVar('fabrik_show_in_list', array());
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
					if (JRequest::getVar('view') == 'list' && !$elementModel->canView())
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
		// $$$ rob fabrik_show_in_list set in admin module params (will also be set in menu items and content plugins later on)
		// its an array of element ids that should be show. Overrides default element 'show_in_list' setting.
		$showInList = (array) JRequest::getVar('fabrik_show_in_list', array());
		$sig = empty($showInList) ? 0 : implode('.', $showInList);
		if (!array_key_exists($sig, $this->publishedListElements))
		{
			$this->publishedListElements[$sig] = array();
			$elements = $this->getMyElements();
			foreach ($elements as $elementModel)
			{
				$element = $elementModel->getElement();
				if ($element->published == 1 && $elementModel->canView())
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
			$groups = $user->authorisedLevels();
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
				$groups = $user->authorisedLevels();
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
	 * is the group a join?
	 *
	 * @return  bool
	 */

	public function isJoin()
	{
		return $this->getGroup()->is_join;
	}

	/**
	 * get the group's associated join model
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
	 * @return object params
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
		if (array_key_exists($startpage, $pages) && is_array($pages[$startpage]) && !in_array($groupTable->id, $pages[$startpage]) || $showGroup == 0)
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
		$group->displaystate = ($group->canRepeat == 1 && $formModel->_editable) ? 1 : 0;
		$group->maxRepeat = (int) $params->get('repeat_max');
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
		$elements = $this->getMyElements();
		$group = $this->getGroup();

		// NewGroupNames set in table copy
		$newNames = JRequest::getVar('newGroupNames', array());
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
