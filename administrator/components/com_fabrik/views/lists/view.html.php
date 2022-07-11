<?php
/**
 *  View class for a list of lists.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * View class for a list of lists.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       1.6
 */
class FabrikAdminViewLists extends JViewLegacy
{
	/**
	 * List items
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
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 */

	public function display($tpl = null)
	{
		switch ($this->getLayout())
		{
			case 'confirmdelete':
				$this->confirmdelete();

				return;
				break;
			case 'import':
				$this->import($tpl);

				return;
				break;
		}
		// Initialise variables.
		$app = JFactory::getApplication();
		$input = $app->input;
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->packageOptions = $this->get('PackageOptions');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new RuntimeException(implode("\n", $errors), 500);

			return false;
		}

		$this->table_groups = $this->get('TableGroups');
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
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_LISTS'), 'list');

		if ($canDo->get('core.create'))
		{
			JToolBarHelper::addNew('list.add', 'JTOOLBAR_NEW');
		}

		if ($canDo->get('core.edit'))
		{
			JToolBarHelper::editList('list.edit', 'JTOOLBAR_EDIT');
		}

		JToolBarHelper::custom('list.copy', 'copy.png', 'copy_f2.png', 'COM_FABRIK_COPY');

		if ($canDo->get('core.edit.state'))
		{
			if ($this->state->get('filter.state') != 2)
			{
				JToolBarHelper::divider();
				JToolBarHelper::custom('lists.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				JToolBarHelper::custom('lists.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
		}

		JToolBarHelper::divider();

		if ($canDo->get('core.create'))
		{
			JToolBarHelper::custom('import.display', 'upload.png', 'upload_f2.png', 'COM_FABRIK_IMPORT', false);
		}

		JToolBarHelper::divider();

		if (JFactory::getUser()->authorise('core.manage', 'com_checkin'))
		{
			JToolBarHelper::custom('lists.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
		}

		JToolBarHelper::divider();

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			JToolBarHelper::deleteList('', 'lists.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolBarHelper::trash('lists.trash', 'JTOOLBAR_TRASH');
		}

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::divider();
			JToolBarHelper::preferences('com_fabrik');
		}

		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_LISTS', false, FText::_('JHELP_COMPONENTS_FABRIK_LISTS'));

		if (FabrikWorker::j3())
		{
			JHtmlSidebar::setAction('index.php?option=com_fabrik&view=lists');

			$publishOpts = JHtml::_('jgrid.publishedOptions', array('archived' => false));
			JHtmlSidebar::addFilter(
				FText::_('JOPTION_SELECT_PUBLISHED'), 'filter_published',
				JHtml::_('select.options', $publishOpts, 'value', 'text', $this->state->get('filter.published'), true)
			);

			if (!empty($this->packageOptions))
			{
				array_unshift($this->packageOptions, JHtml::_('select.option', 'fabrik', FText::_('COM_FABRIK_SELECT_PACKAGE')));
				JHtmlSidebar::addFilter(
					FText::_('JOPTION_SELECT_PUBLISHED'), 'package',
					JHtml::_('select.options', $this->packageOptions, 'value', 'text', $this->state->get('com_fabrik.package'), true)
				);
			}
		}
	}

	/**
	 * Add the page title and toolbar for confirming list deletion
	 *
	 * @return  void
	 */

	protected function addConfirmDeleteToolbar()
	{
		$app = JFactory::getApplication();
		$app->input->set('hidemainmenu', true);
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_LIST_CONFIRM_DELETE'), 'list');
		JToolBarHelper::save('lists.dodelete', 'JTOOLBAR_APPLY');
		JToolBarHelper::cancel('list.cancel', 'JTOOLBAR_CANCEL');
		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_LISTS_EDIT', true, 'http://fabrikar.com/wiki/index.php/List_delete_confirmation');
	}

	/**
	 * Add the page title and toolbar for List import
	 *
	 * @return  void
	 */

	protected function addImportToolBar()
	{
		$app = JFactory::getApplication();
		$app->input->set('hidemainmenu', true);
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_LIST_IMPORT'), 'list');
		JToolBarHelper::save('lists.doimport', 'JTOOLBAR_APPLY');
		JToolBarHelper::cancel('list.cancel', 'JTOOLBAR_CANCEL');
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
		$this->form = $this->get('ConfirmDeleteForm', 'list');
		$model = $this->getModel('lists');
		$this->items = $model->getDbTableNames();
		$this->addConfirmDeleteToolbar();
		$v = new JVersion;

		if ($v->RELEASE > 2.5)
		{
			$this->setLayout('confirmdeletebootstrap');
		}

		parent::display($tpl);
	}

	/**
	 * Show a screen allowing the user to import a csv file to create a fabrik table.
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 */

	protected function import($tpl = null)
	{
		$this->form = $this->get('ImportForm');
		$this->addImportToolBar();
		parent::display($tpl);
	}
}
