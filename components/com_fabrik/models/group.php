<?php
/**
 * Fabrik Group Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

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
	protected $params = null;

	/**
	 * Id of group to load
	 *
	 * @var int
	 */
	protected $id = null;

	/**
	 * Group table
	 *
	 * @var JTable
	 */
	protected $group = null;

	/**
	 * Form model
	 *
	 * @var FabrikFEModelForm
	 */
	protected $form = null;

	/**
	 * List model
	 *
	 * @var FabrikFEModelList
	 */
	protected $table = null;

	/**
	 * Join model
	 *
	 * @var FabrikFEModelJoin
	 */
	protected $joinModel = null;

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
	 * @var int
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
	 * Method to set the group id
	 *
	 * @param   int  $id  group ID number
	 *
	 * @return  void
	 */

	public function setId($id)
	{
		// Set new group ID
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
		if (is_null($this->group))
		{
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
			$this->group = FabTable::getInstance('Group', 'FabrikTable');
			$this->group->load($this->getId());
		}

		return $this->group;
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
		$this->group = $group;
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

		// If group show is type 5, then always read only.
		if (in_array($params->get('repeat_group_show_first', '1'), array('2','5')))
		{
			$this->canEdit = false;

			return $this->canEdit;
		}

		$formModel = $this->getFormModel();
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
	 * @param   string  $mode  View mode list|form
	 *
	 * @return   bool
	 */

	public function canView($mode = 'form')
	{
		// No ACL option for list view.
		if ($mode === 'list')
		{
			return true;
		}

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

			// If the user can't access the group return that and ignore repeat_group_show_first option
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
		if (!($formModel->isEditable() && $showGroup == 2))
		{
			$this->canView = true;
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
		$this->form = $formModel;
		$this->table = $listModel;
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
			$db->execute();
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
	 * Set the element column css allows for group column settings to be applied
	 *
	 * @param   object  &$element  Pre-render element properties
	 * @param   int     $rowIx     Current key when looping over elements.
	 *
	 * @since 	Fabrik 3.0.5.2
	 *
	 * @return  int  the next column count
	 */

	public function setColumnCss(&$element, $rowIx)
	{
		$params = $this->getParams();
		$colcount = (int) $params->get('group_columns');

		if ($colcount === 0)
		{
			$colcount = 1;
		}

		$element->offset = $params->get('group_offset', 0);

		// Bootstrap grid formatting
		if ($colcount === 1) // Single column
		{
			$element->startRow = true;
			$element->endRow = 1;
			$element->span = ' span12';
			$element->column = ' style="clear:both;width:100%;"';
			$rowIx = -1;

			return $rowIx;
		}

		// Multi-column
		$widths = $params->get('group_column_widths', '');
		$w = floor((100 - ($colcount * 6)) / $colcount) . '%';

		if ($widths !== '')
		{
			$widths = explode(',', $widths);
			$w = JArrayHelper::getValue($widths, ($rowIx) % $colcount, $w);
		}

		$element->column = ' style="float:left;width:' . $w . ';';
		$element->startRow = 0;
		$element->endRow = 0;

		/**
		 * Hidden fields at start of row will be grouped on a separate row to avoid
		 * issues with css selector :first-child.
		 * $rowIx == -1 indicates a new row to distinguish it from
		 * $rowIx = 0 which indicates hidden fields already processed at start of row.
		 **/
		if ($rowIx === 0 && !$element->hidden)
		{
			// Previous element must have been hidden - so set end of row on that and new row on this
			$this->setColumnCssLastElement->endRow = 1;
			$rowIx = -1;
		}

		if ($rowIx < 0)
		{
			$rowIx = 0;
			$element->startRow = 1;
			$element->column .= "clear:both;";
		}

		$element->column .= '" ';
		$spans = $this->columnSpans();
		$spanKey = $rowIx % $colcount;
		$element->span = $element->hidden ? '' : JArrayHelper::getValue($spans, $spanKey, 'span' . floor(12 / $colcount));

		if (!$element->hidden)
		{
			$rowIx++;
		}

		if ($rowIx !== 0 && ($rowIx % $colcount === 0))
		{
			$element->endRow = 1;

			// Reset rowIx to indicate a new row.
			$rowIx = -1;
		}

		// Save this so we can set endRow on previous element if it was hidden and this element isn't.
		$this->setColumnCssLastElement = $element;

		return $rowIx;
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

		if (trim($widths) === '')
		{
			return;
		}

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
		if (!isset($this->form))
		{
			$formids = $this->getFormsIamIn();
			$formid = empty($formids) ? 0 : $formids[0];
			$this->form = JModelLegacy::getInstance('Form', 'FabrikFEModel');
			$this->form->setId($formid);
			$this->form->getForm();
			$this->form->getlistModel();
		}

		return $this->form;
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
			$joins = $this->getListModel()->getJoins();
			/**
			* $$$ Paul - it is possible that the user has set Include in List Query
			* to No for table primary key or join foreign key. If List is then set
			* to Merge and Reduce, this causes a problem because the pk/fk
			* placeholder is not set. We therefore include the table PK and join FK
			* regardless of Include in List Query settings if any elements in the
			* group have Include in List Query = Yes.
			* In order to avoid iterating over the elements twice, we save the
			* PK / FK elementModel and include it as soon as it is needed.
			* If the access level does not allow for these to be used, then we should
			* display some sort of warning - though this is not included in this fix.
			**/
			$repeating = $this->canRepeat();
			$join = $this->getJoinModel();

			if (is_null($join->getJoin()->params))
			{
				$join_id = "";
				$join_fk = "";
			}
			else
			{
				$join_id = $join->getForeignID();
				$join_fk = $join->getForeignKey();
			}

			$element_included = false;
			$table_pk_included = $join_fk_included = false;
			$table_pk_element = $join_fk_element = null;

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
					$full_name = $elementModel->getFullName(true, false);

					/**
					 * As this function seems to be used to build both the list view and the form view, we should NOT
					 * include elements in the list query if the user can not view them, as their data is sent to the json object
					 * and thus visible in the page source
					 */

					if ($input->get('view') == 'list' && !$this->getListModel()->isUserDoElement($full_name) && !$elementModel->canView('list'))
					{
						continue;
					}

					$showThisInList = $element->primary_key || $params->get('include_in_list_query', 1) == 1
					|| (empty($showInList) && $element->show_in_list_summary) || in_array($element->id, $showInList);

					if ($showThisInList)
					{
						if ($element->primary_key || $full_name == $join_id)
						{
							$table_pk_included = true;
						}
						elseif (!$table_pk_included && !is_null($table_pk_element))
						{
							// Add primary key before other element
							$this->listQueryElements[$sig][] = $table_pk_element;
							$table_pk_included = true;
						}

						if ($full_name == $join_fk)
						{
							$join_fk_included = true;
						}
						elseif (!$join_fk_included && !is_null($join_fk_element))
						{
							// Add foreign key before other element
							$this->listQueryElements[$sig][] = $join_fk_element;
							$join_fk_included = true;
						}

						$this->listQueryElements[$sig][] = $elementModel;
						$element_included = true;
					}
					elseif ($element->primary_key || $full_name == $join_id)
					{
						if ($element_included)
						{
							// Add primary key after other element
							$this->listQueryElements[$sig][] = $elementModel;
							$table_pk_included = true;
						}
						else
						{
							// Save primary key for future use
							$table_pk_element = $elementModel;
						}
					}
					elseif ($elementModel->getFullName(true, false) == $join_fk)
					{
						if ($element_included)
						{
							// Add foreign key after other element
							$this->listQueryElements[$sig][] = $elementModel;
							$join_fk_included = true;
						}
						else
						{
							// Save foreign key for future use
							$join_fk_element = $elementModel;
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

		if (is_null($this->joinModel))
		{
			$this->joinModel = JModelLegacy::getInstance('Join', 'FabrikFEModel');
			$this->joinModel->setId($group->join_id);
			$js = $this->getListModel()->getJoins();

			// $$$ rob set join models data from preloaded table joins - reduced load time
			for ($x = 0; $x < count($js); $x++)
			{
				if ($js[$x]->id == $group->join_id && $js[$x]->element_id == 0)
				{
					$this->joinModel->setData($js[$x]);
					break;
				}
			}

			$this->joinModel->getJoin();
		}

		return $this->joinModel;
	}

	/**
	 * Get group params
	 *
	 * @return  object	params
	 */

	public function &getParams()
	{
		if (!$this->params)
		{
			$this->params = new JRegistry($this->getGroup()->params);
		}

		return $this->params;
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
		$input = JFactory::getApplication()->input;
		$group = new stdClass;
		$groupTable = $this->getGroup();
		$params = $this->getParams();

		if (!isset($this->editable))
		{
			$this->editable = $formModel->isEditable();
		}

		if ($this->editable)
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
				$this->editable = false;
			}
		}

		$group->editable = $this->editable;
		$group->canRepeat = $params->get('repeat_group_button', '0');
		$showGroup = $params->def('repeat_group_show_first', '1');
		$pages = $formModel->getPages();
		$startpage = isset($formModel->sessionModel->last_page) ? $formModel->sessionModel->last_page : 0;
		/**
		 * $$$ hugh - added array_key_exists for (I think!) corner case where group properties have been
		 * changed to remove (or change) paging, but user still has session state set.  So it was throwing
		 * a PHP 'undefined index' notice.
		 */

		if (array_key_exists($startpage, $pages) && is_array($pages[$startpage])
			&& !in_array($groupTable->id, $pages[$startpage]) || $showGroup == -1 || $showGroup == 0)
		{
			$groupTable->css .= ";display:none;";
		}

		$group->css = trim(str_replace(array("<br />", "<br>"), "", $groupTable->css));
		$group->id = $groupTable->id;

		$label = $input->getString('group' . $group->id . '_label', $groupTable->label);

		if (JString::stristr($label, "{Add/Edit}"))
		{
			$replace = $formModel->isNewRecord() ? FText::_('COM_FABRIK_ADD') : FText::_('COM_FABRIK_EDIT');
			$label = str_replace("{Add/Edit}", $replace, $label);
		}

		$groupTable->label = $label;
		$group->title = $w->parseMessageForPlaceHolder($groupTable->label, $formModel->data, false);
		$group->title = FText::_($group->title);
		$group->name = $groupTable->name;
		$group->displaystate = ($group->canRepeat == 1 && $formModel->isEditable()) ? 1 : 0;
		$group->maxRepeat = (int) $params->get('repeat_max');
		$group->minRepeat = $params->get('repeat_min', '') === '' ? 1 : (int) $params->get('repeat_min', '');
		$group->showMaxRepeats = $params->get('show_repeat_max', '0') == '1';
		$group->canAddRepeat = $this->canAddRepeat();
		$group->canDeleteRepeat = $this->canDeleteRepeat();
		$group->intro = $text = FabrikString::translate($params->get('intro'));
		$group->outro = FText::_($params->get('outro'));
		$group->columns = $params->get('group_columns', 1);
		$group->splitPage = $params->get('split_page', 0);
		$group->showLegend = $this->showLegend($group);
		$group->labels = $params->get('labels_above', -1);
		$group->dlabels = $params->get('labels_above_details', -1);

		if ($this->canRepeat())
		{
			$group->tmpl = $params->get('repeat_template', 'repeatgroup');
		}
		else
		{
			$group->tmpl = 'group';
		}

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
		$formGroup->store();
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

	/**
	 * Get the records master Insert Id - need better description...
	 *
	 * @return  string
	 */

	protected function masterInsertId()
	{
		$formModel = $this->getFormModel();
		$joinModel = $this->getJoinModel();
		$formData =& $formModel->formDataWithTableName;
		$joinToPk = $joinModel->getJoinedToTablePk();

		return $formData[$joinToPk];
	}

	/**
	 * Part of process()
	 * Set foreign key's value to the main records insert id
	 *
	 * @return  void
	 */

	protected function setForeignKey()
	{
		$formModel = $this->getFormModel();
		$formData =& $formModel->formDataWithTableName;
		$joinModel = $this->getJoinModel();
		$masterInsertId = $this->masterInsertId();
		$fk = $joinModel->getForeignKey();
		$fks = array($fk, $fk . '_raw');

		foreach ($fks as $fk)
		{
			if ($this->canRepeat() && array_key_exists($fk, $formData))
			{
				if (array_key_exists($fk, $formData))
				{
					if (is_array($formData[$fk]))
					{
						foreach ($formData[$fk] as $k => $v)
						{
							$formData[$fk][$k] = $masterInsertId;
						}
					}
					else
					{
						$formData[$fk] = $masterInsertId;
					}
				}
			}
			else
			{
				$formData[$fk] = $masterInsertId;
			}
		}
	}

	/**
	 * Get the number of times the group was repeated when the user fills
	 * in the form
	 *
	 * @todo whats the difference between this and @link(repeatCount)
	 *
	 * @return  int
	 */

	protected function repeatTotals()
	{
		$input = JFactory::getApplication()->input;
		$repeatTotals = $input->get('fabrik_repeat_group', array(0), 'post', 'array');

		return (int) JArrayHelper::getValue($repeatTotals, $this->getGroup()->id, 0);
	}

	/**
	 * Group specific form submission code - deals with saving joined data.
	 *
	 * @return  void
	 */

	public function process()
	{
		if (!$this->isJoin())
		{
			return;
		}

		$canRepeat = $this->canRepeat();
		$repeats = $this->repeatTotals();
		$joinModel = $this->getJoinModel();
		$pkField = $joinModel->getForeignID();
		$listModel = $this->getListModel();
		$item = $this->getGroup();
		$formModel = $this->getFormModel();
		$formData =& $formModel->formDataWithTableName;

		$this->setForeignKey();
		$elementModels = $this->getMyElements();
		$list = $listModel->getTable();
		$tblName = $list->db_table_name;

		// Set the list's table name to the join table, needed for storeRow()
		$join = $joinModel->getJoin();
		$list->db_table_name = $join->table_join;
		$usedKeys = array();

		// For each repeat group
		for ($i = 0; $i < $repeats; $i ++)
		{
			$data = array();

			foreach ($elementModels as $elementModel)
			{
				$elementModel->onStoreRow($data, $i);
			}

			$pk = $canRepeat ? JArrayHelper::getValue($formData[$pkField], $i, '') : $formData[$pkField];

			// Say for some reason the pk was set as a dbjoin!
			if (is_array($pk))
			{
				$pk = array_shift($pk);
			}

			$insertId = $listModel->storeRow($data, $pk, true, $item);

			// Update key
			if ($canRepeat)
			{
				$formData[$pkField][$i] = $insertId;
			}
			else
			{
				$formData[$pkField] = $insertId;
			}

			$usedKeys[] = $insertId;
		}

		// Delete any removed groups
		$db = $listModel->getDb();
		$query = $db->getQuery(true);
		$masterInsertId = $this->masterInsertId();
		$query->delete($list->db_table_name);

		if (is_array($masterInsertId))
		{
			foreach ($masterInsertId as &$mid)
			{
				if (is_array($mid))
				{
					$mid = array_unshift($mid);
				}

				$mid = $db->quote($mid);
			}

			$query->where($join->table_join_key . ' IN (' . implode(', ', $masterInsertId) . ')');
		}
		else
		{
			$query->where($join->table_join_key . ' = ' . $db->quote($masterInsertId));
		}

		if (!empty($usedKeys))
		{
			$pk = $join->params->get('pk');
			$query->where('!(' . $pk . 'IN (' . implode(',', $usedKeys) . ')) ');
		}

		$db->setQuery($query);
		$db->execute();

		// Reset the list's table name
		$list->db_table_name = $tblName;
	}

	/**
	 * Test if the group can repeat and if the fk element is published
	 *
	 * @since   3.1rc1
	 *
	 * @return boolean
	 */

	public function fkPublished()
	{
		if ($this->canRepeat())
		{
			return true;
		}

		$joinTable = $this->getJoinModel()->getJoin();
		$fullFk = $joinTable->table_join . '___' . $joinTable->table_join_key;
		$elementModels = $this->getPublishedElements();

		foreach ($elementModels as $elementModel)
		{
			if ($elementModel->getFullName(true, false) === $fullFk)
			{
				return true;
			}
		}

		JError::raiseWarning(E_ERROR, JText::sprintf('COM_FABRIK_JOINED_DATA_BUT_FK_NOT_PUBLISHED', $fullFk));

		return false;
	}

	/**
	 * Get the number of times the group was repeated based on the form's current data
	 *
	 * @since   3.1rc1
	 *
	 * @return number
	 */

	public function repeatCount()
	{
		$data = $this->getFormModel()->getData();
		$elementModels = $this->getPublishedElements();
		reset($elementModels);
		$tmpElement = current($elementModels);

		if (!empty($elementModels))
		{
			$smallerElHTMLName = $tmpElement->getFullName(true, false);
			$d = JArrayHelper::getValue($data, $smallerElHTMLName, 1);

			if (is_object($d))
			{
				$d = JArrayHelper::fromObject($d);
			}

			$repeatGroup = count($d);
		}
		else
		{
			// No published elements - not sure if setting repeatGroup to 0 is right though
			$repeatGroup = 0;
		}

		return $repeatGroup;
	}

	/**
	 * Should the group legend be shown
	 *
	 * @param   object  $group  Group properties
	 *
	 * @return boolean
	 */
	private function showLegend($group)
	{
		$allHidden = true;

		foreach ($this->elements as $elementModel)
		{
			$allHidden &= $elementModel->isHidden();
		}
		if ((!$allHidden || !empty($group->intro)) && trim($group->title) !== '') {
			return true;
		}

		return false;
	}
}
