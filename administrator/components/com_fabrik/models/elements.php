<?php
/**
 * Fabrik Admin Elements Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

require_once 'fabmodellist.php';

/**
 * Fabrik Admin Elements Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikAdminModelElements extends FabModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @see        JController
	 *
	 * @since      1.6
	 */

	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array('e.id', 'e.name', 'e.label', 'e.show_in_list_summary', 'e.published', 'e.ordering', 'g.label',
				'e.plugin', 'g.name');
		}

		parent::__construct($config);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		// Initialise variables.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'e.*, e.ordering AS ordering'));
		$query->from('#__{package}_elements AS e');

		$query->select('(SELECT COUNT(*) FROM #__fabrik_jsactions AS js WHERE js.element_id = e.id) AS numJs');

		// Filter by published state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where('e.published = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(e.published IN (0, 1))');
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $db->quote('%' . $db->escape($search, true) . '%');
			$query->where('(e.name LIKE ' . $search . ' OR e.label LIKE ' . $search . ')');
		}

		$group = $this->getState('filter.group');

		if (trim($group) !== '')
		{
			$query->where('g.id = ' . (int) $group);
		}

		$showInList = $this->getState('filter.showinlist');

		if (trim($showInList) !== '')
		{
			$query->where('e.show_in_list_summary = ' . (int) $showInList);
		}

		$plugin = $this->getState('filter.plugin');

		if (trim($plugin) !== '')
		{
			$query->where('e.plugin = ' . $db->quote($plugin));
		}

		// For drop fields view
		$cids = (array) $this->getState('filter.cid');

		if (!empty($cids))
		{
			$query->where('e.id IN (' . implode(',', $cids) . ')');
		}

		$this->filterByFormQuery($query, 'fg');

		// Join over the users for the checked out user.

		$query->select('e.id');

		$query->join('LEFT', '#__users AS u ON checked_out = u.id');
		$query->join('LEFT', '#__{package}_groups AS g ON e.group_id = g.id ');

		// Was inner join but if el assigned to group which was not part of a form then the element was not shown in the list
		$query->join('LEFT', '#__{package}_formgroup AS fg ON fg.group_id = e.group_id');
		$query->join('LEFT', '#__{package}_lists AS l ON l.form_id = fg.form_id');

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol == 'ordering' || $orderCol == 'category_title')
		{
			$orderCol = 'ordering';
		}

		if (trim($orderCol) !== '')
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		// Work out the element ids so we can limit the fullname subquery
		$db->setQuery($query, $start = $this->getState('list.start'), $this->getState('list.limit'));
		$elementIds = $db->loadColumn();

		/**
		 * $$$ hugh - altered this query as ...
		 * WHERE (jj.list_id != 0 AND jj.element_id = 0)
		 * ...instead of ...
		 * WHERE jj.list_id != 0
		 * ... otherwise we pick up repeat elements, as they have both table and element set
		 * and he query fails with "returns multiple values" for the fullname select
		 */

		if (count($elementIds) > 0)
		{
			$fullname = "(SELECT DISTINCT(
			IF( ISNULL(jj.table_join), CONCAT(ll.db_table_name, '___', ee.name), CONCAT(jj.table_join, '___', ee.name))
			)
			FROM #__fabrik_elements AS ee
			LEFT JOIN #__{package}_joins AS jj ON jj.group_id = ee.group_id
			LEFT JOIN #__{package}_formgroup as fg ON fg.group_id = ee.group_id
			LEFT JOIN #__{package}_lists AS ll ON ll.form_id = fg.form_id
			WHERE (jj.list_id != 0 AND jj.element_id = 0)
			AND ee.id = e.id AND ee.group_id <> 0 AND ee.id IN (" . implode(',', $elementIds) . ") LIMIT 1)  AS full_element_name";

			$query->select('u.name AS editor, ' . $fullname . ', g.name AS group_name, l.db_table_name');
			$query->select("(SELECT GROUP_CONCAT(ec.id SEPARATOR ',') FROM #__{package}_elements AS ec WHERE ec.parent_id = e.id) AS child_ids");
		}

		//$sql = (string)$query;

		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 */

	public function getItems()
	{
		$items = parent::getItems();
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('id, title')->from('#__viewlevels');
		$db->setQuery($query);
		$viewLevels = $db->loadObjectList('id');

		// Get the join element name of those elements not in a joined group
		foreach ($items as &$item)
		{
			if ($item->full_element_name == '')
			{
				$item->full_element_name = $item->db_table_name . '___' . $item->name;
			}

			// Add a tip containing the access level information
			$params = new Registry($item->params);

			$addAccessTitle = FArrayHelper::getValue($viewLevels, $item->access);
			$addAccessTitle = is_object($addAccessTitle) ? $addAccessTitle->title : 'n/a';

			$editAccessTitle = FArrayHelper::getValue($viewLevels, $params->get('edit_access', 1));
			$editAccessTitle = is_object($editAccessTitle) ? $editAccessTitle->title : 'n/a';

			$viewAccessTitle = FArrayHelper::getValue($viewLevels, $params->get('view_access', 1));
			$viewAccessTitle = is_object($viewAccessTitle) ? $viewAccessTitle->title : 'n/a';

			$item->tip = FText::_('COM_FABRIK_ACCESS_EDITABLE_ELEMENT') . ': ' . $addAccessTitle
				. '<br />' . FText::_('COM_FABRIK_ELEMENT_EDIT_ACCESS_LABEL') . ': ' . $editAccessTitle
				. '<br />' . FText::_('COM_FABRIK_ACCESS_VIEWABLE_ELEMENT') . ': ' . $viewAccessTitle;

			$validations = $params->get('validations');
			$v           = array();

			// $$$ hugh - make sure the element has validations, if not it could return null or 0 length array
			if (is_object($validations))
			{
				for ($i = 0; $i < count($validations->plugin); $i++)
				{
					$pname = $validations->plugin[$i];
					/*
					 * $$$ hugh - it's possible to save an element with a validation that hasn't
					 * actually had a plugin type selected yet.
					 */
					if (empty($pname))
					{
						$v[] = '&nbsp;&nbsp;<strong>' . FText::_('COM_FABRIK_ELEMENTS_NO_VALIDATION_SELECTED') . '</strong>';
						continue;
					}

					$msgs = $params->get($pname . '-message');
					/*
					 * $$$ hugh - elements which haven't been saved since Published param was added won't have
					 * plugin_published, and just default to Published
					 */
					if (!isset($validations->plugin_published))
					{
						$published = FText::_('JPUBLISHED');
					}
					else
					{
						$published = $validations->plugin_published[$i] ? FText::_('JPUBLISHED') : FText::_('JUNPUBLISHED');
					}

					$v[] = '&nbsp;&nbsp;<strong>' . $pname . ': <em>' . $published . '</em></strong>'
						. '<br />&nbsp;&nbsp;&nbsp;&nbsp;' . FText::_('COM_FABRIK_FIELD_ERROR_MSG_LABEL') . ': <em>' . htmlspecialchars(FArrayHelper::getValue($msgs, $i, 'n/a')) . '</em>';
				}
			}

			$item->numValidations = count($v);
			$item->validationTip  = $v;
		}

		return $items;
	}

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string $type   The table type to instantiate
	 * @param   string $prefix A prefix for the table class name. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  JTable    A database object
	 *
	 * @since   1.6
	 */

	public function getTable($type = 'Element', $prefix = 'FabrikTable', $config = array())
	{
		$config['dbo'] = FabrikWorker::getDbo();

		return FabTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string $ordering  An optional ordering field.
	 * @param   string $direction An optional direction (asc|desc).
	 *
	 * @since    1.6
	 *
	 * @return  null
	 */

	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication('administrator');

		// Load the parameters.
		$params = JComponentHelper::getParams('com_fabrik');
		$this->setState('params', $params);

		$published = $app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// Load the form state
		$form = $app->getUserStateFromRequest($this->context . '.filter.form', 'filter_form', '');
		$this->setState('filter.form', $form);

		// Load the group state
		$group = $app->getUserStateFromRequest($this->context . '.filter.group', 'filter_group', '');
		$this->setState('filter.group', $group);

		// Load the show in list state
		$showinlist = $app->getUserStateFromRequest($this->context . '.filter.showinlist', 'filter_showinlist', '');
		$this->setState('filter.showinlist', $showinlist);

		// Load the plug-in state
		$plugin = $app->getUserStateFromRequest($this->context . '.filter.plugin', 'filter_plugin', '');
		$this->setState('filter.plugin', $plugin);

		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Get show in list options
	 *
	 * @return  array  of Jhtml select.options
	 */

	public function getShowInListOptions()
	{
		return array(JHtml::_('select.option', 0, FText::_('JNO')), JHtml::_('select.option', 1, FText::_('JYES')));
	}

	/**
	 * Get a list of plugin types to filter on
	 *
	 * @return  array  of select.options
	 */

	public function getPluginOptions()
	{
		$db     = FabrikWorker::getDbo(true);
		$user   = JFactory::getUser();
		$levels = implode(',', $user->getAuthorisedViewLevels());
		$query  = $db->getQuery(true);
		$query->select('element AS value, element AS text')->from('#__extensions')->where('enabled >= 1')->where('type =' . $db->quote('plugin'))
			->where('state >= 0')->where('access IN (' . $levels . ')')->where('folder = ' . $db->quote('fabrik_element'))->order('text');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		return $rows;
	}

	/**
	 * Batch process element properties
	 *
	 * @param   array $ids   element ids
	 * @param   array $batch element properties to set to
	 *
	 * @since   3.0.7
	 *
	 * @return  bool
	 */

	public function batch($ids, $batch)
	{
		$ids = ArrayHelper::toInteger($ids);

		foreach ($ids as $id)
		{
			$item = $this->getTable('Element');
			$item->load($id);
			$item->batch($batch);
		}
	}

	/**
	 * Stops internal id from being unpublished
	 *
	 * @param   array $ids Ids wanting to be unpublished
	 *
	 * @return  array  allowed ids
	 */
	public function canUnpublish($ids)
	{
		$ids = ArrayHelper::toInteger($ids);
		$blocked = array();

		foreach ($ids as $id)
		{
			$item = $this->getTable('Element');
			$item->load($id);

			if ($item->get('plugin') == 'internalid')
			{
				$blocked[] = $id;
			}
		}

		if (!empty($blocked))
		{
			$app = JFactory::getApplication();
			$app->enqueueMessage(FText::_('COM_FABRIK_CANT_UNPUBLISHED_PK_ELEMENT'), 'warning');
		}

		return array_diff($ids, $blocked);
	}
}
