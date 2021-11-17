<?php
/**
 * View class for a list of elements.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * View class for a list of elements.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikAdminViewElements extends JViewLegacy
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
			if (FabrikWorker::j3())
			{
				$this->setLayout('bootstrap_confirmdelete');
			}

			$this->confirmdelete();

			return;
		}

		if ($this->getLayout() == 'copyselectgroup')
		{
			$this->copySelectGroup();

			return;
		}
		// Initialise variables.
		$app = JFactory::getApplication();
		$input = $app->input;
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
			throw new RuntimeException(implode("\n", $errors), 500);
		}

		FabrikAdminHelper::setViewLayout($this);
		$this->addToolbar();
		FabrikAdminHelper::addSubmenu($input->getWord('view', 'lists'));

		if (FabrikWorker::j3())
		{
			$this->sidebar = JHtmlSidebar::render();
		}

		FabrikHelperHTML::iniRequireJS();
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
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_ELEMENTS'), 'checkbox-unchecked');

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
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_ELEMENTS', false, FText::_('JHELP_COMPONENTS_FABRIK_ELEMENTS'));

		if (FabrikWorker::j3())
		{
			JHtmlSidebar::setAction('index.php?option=com_fabrik&view=elements');

			if (!empty($this->packageOptions))
			{
				array_unshift($this->packageOptions, JHtml::_('select.option', 'fabrik', FText::_('COM_FABRIK_SELECT_PACKAGE')));
				JHtmlSidebar::addFilter(
				FText::_('JOPTION_SELECT_PUBLISHED'),
				'package',
				JHtml::_('select.options', $this->packageOptions, 'value', 'text', $this->state->get('com_fabrik.package'), true)
				);
			}

			// don't translate plugin names, otherwise things like "jdate" become "Date"
			JHtmlSidebar::addFilter(
			FText::_('COM_FABRIK_SELECT_PLUGIN'),
			'filter_plugin',
			JHtml::_('select.options', $this->pluginOptions, 'value', 'text', $this->state->get('filter.plugin'), false)
			);

			JHtmlSidebar::addFilter(
			FText::_('COM_FABRIK_SELECT_FORM'),
			'filter_form',
			JHtml::_('select.options', $this->formOptions, 'value', 'text', $this->state->get('filter.form'), true)
			);

			JHtmlSidebar::addFilter(
			FText::_('COM_FABRIK_SELECT_GROUP'),
			'filter_group',
			JHtml::_('select.options', $this->groupOptions, 'value', 'text', $this->state->get('filter.group'), true)
			);

			$publishOpts = JHtml::_('jgrid.publishedOptions', array('archived' => false));
			JHtmlSidebar::addFilter(
			FText::_('JOPTION_SELECT_PUBLISHED'),
			'filter_published',
			JHtml::_('select.options', $publishOpts, 'value', 'text', $this->state->get('filter.published'), true)
			);

			JHtmlSidebar::addFilter(
			FText::_('COM_FABRIK_SELECT_SHOW_IN_LIST'),
			'filter_showinlist',
			JHtml::_('select.options', $this->showInListOptions, 'value', 'text', $this->state->get('filter.showinlist'), true)
			);
		}
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
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_ELEMENT_CONFIRM_DELETE'), 'checkbox-unchecked');
		JToolBarHelper::save('elements.dodelete', 'JTOOLBAR_APPLY');
		JToolBarHelper::cancel('elements.cancel', 'JTOOLBAR_CANCEL');
		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_ELEMENTS_EDIT', true, 'http://www.fabrikar.com/forums/index.php?wiki/element-delete-confirmation/');
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
		FabrikHelperHTML::iniRequireJS();
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
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_ELEMENT_COPY_TO_WHICH_GROUP'), 'checkbox-unchecked');
		JToolBarHelper::save('element.copy', 'JTOOLBAR_APPLY');
		JToolBarHelper::cancel('elements.cancel', 'JTOOLBAR_CANCEL');
		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_ELEMENTS_EDIT', true, 'http://fabrikar.com/wiki/index.php/Element_copy_confirmation');
	}
}
