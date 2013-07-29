<?php
/**
 * View class for a list of elements.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * View class for a list of elements.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
*/

class FabrikViewElements extends JView
{
	/**
	 * Elements
	 *
	 * @var  array
	 */
	protected $items;

	/**
	 * Pagination
	 *
	 * @var  JPagination
	 */
	protected $pagination;

	/**
	 * View state
	 *
	 * @var object
	 */
	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 */

	public function display($tpl = null)
	{
		if ($this->getLayout() == 'confirmdelete')
		{
			$this->confirmdelete();
			return;
		}
		if ($this->getLayout() == 'copyselectgroup')
		{
			$this->copySelectGroup();
			return;
		}
		// Initialise variables.
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->formOptions = $this->get('FormOptions');
		$this->showInListOptions = $this->get('ShowInListOptions');
		$this->groupOptions = $this->get('GroupOptions');
		$this->pluginOptions = $this->get('PluginOptions');
		$this->packageOptions = $this->get('PackageOptions');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 */

	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/fabrik.php';
		$canDo = FabrikAdminHelper::getActions($this->state->get('filter.category_id'));

		JToolBarHelper::title(JText::_('COM_FABRIK_MANAGER_ELEMENTS'), 'elements.png');
		if ($canDo->get('core.create'))
		{
			JToolBarHelper::addNew('element.add', 'JTOOLBAR_NEW');
		}
		if ($canDo->get('core.edit'))
		{
			JToolBarHelper::editList('element.edit', 'JTOOLBAR_EDIT');
		}
		JToolBarHelper::custom('elements.copySelectGroup', 'copy.png', 'copy_f2.png', 'COM_FABRIK_COPY');
		if ($canDo->get('core.edit.state'))
		{
			if ($this->state->get('filter.state') != 2)
			{
				JToolBarHelper::divider();
				JToolBarHelper::custom('elements.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				JToolBarHelper::custom('elements.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
			JToolBarHelper::divider();
			JToolBarHelper::custom('elements.showInListView', 'publish.png', 'publish_f2.png', 'COM_FABRIK_SHOW_IN_LIST_VIEW', true);
			JToolBarHelper::custom('elements.hideFromListView', 'unpublish.png', 'unpublish_f2.png', 'COM_FABRIK_REMOVE_FROM_LIST_VIEW', true);

		}
		if (JFactory::getUser()->authorise('core.manage', 'com_checkin'))
		{
			JToolBarHelper::custom('elements.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
		}
		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			JToolBarHelper::deleteList('', 'elements.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolBarHelper::trash('elements.trash', 'JTOOLBAR_TRASH');
		}
		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::divider();
			JToolBarHelper::preferences('com_fabrik');
		}
		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_ELEMENTS', false, JText::_('JHELP_COMPONENTS_FABRIK_ELEMENTS'));
	}

	/**
	 * Show a screen asking if the user wants to delete the lists forms/groups/elements
	 * and if they want to drop the underlying database table
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 */

	protected function confirmdelete($tpl = null)
	{
		$model = $this->getModel();
		$app = JFactory::getApplication();
		$input = $app->input;
		$model->setState('filter.cid', $input->get('cid', array(), 'array'));
		$this->items = $this->get('Items');
		$this->addConfirmDeleteToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar for confirming list deletion
	 *
	 * @return  void
	 */

	protected function addConfirmDeleteToolbar()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		JToolBarHelper::title(JText::_('COM_FABRIK_MANAGER_ELEMENT_CONFIRM_DELETE'), 'element.png');
		JToolBarHelper::save('elements.dodelete', 'JTOOLBAR_APPLY');
		JToolBarHelper::cancel('elements.cancel', 'JTOOLBAR_CANCEL');
		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_ELEMENTS_EDIT', true, 'http://fabrikar.com/wiki/index.php/Element_delete_confirmation');
	}

	/**
	 * Show a view for selecting which group the element should be copied to
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 */

	public function copySelectGroup($tpl = null)
	{
		JSession::checkToken() or die('Invalid Token');
		$model = $this->getModel();
		$app = JFactory::getApplication();
		$input = $app->input;
		$model->setState('filter.cid', $input->get('cid', array(), 'array'));
		$this->items = $this->get('Items');
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id, name')->from('#__fabrik_groups')->order('name');
		$db->setQuery($query);
		$this->groups = $db->loadObjectList();
		$this->addConfirmCopyToolbar();
		parent::display($tpl);
	}

	/**
	 * Add confirm copy elements toolbar
	 *
	 * @return  void
	 */

	protected function addConfirmCopyToolbar()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		JToolBarHelper::title(JText::_('COM_FABRIK_MANAGER_ELEMENT_COPY_TO_WHICH_GROUP'), 'element.png');
		JToolBarHelper::save('element.copy', 'JTOOLBAR_APPLY');
		JToolBarHelper::cancel('elements.cancel', 'JTOOLBAR_CANCEL');
		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_ELEMENTS_EDIT', true, 'http://fabrikar.com/wiki/index.php/Element_copy_confirmation');
	}
}
