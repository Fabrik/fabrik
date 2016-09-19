<?php
/**
 * View to edit an element.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * View to edit an element.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       1.5
 */

class FabrikAdminViewElement extends JViewLegacy
{
	/**
	 * Form
	 *
	 * @var JForm
	 */
	protected $form;

	/**
	 * Element item
	 *
	 * @var JTable
	 */
	protected $item;

	/**
	 * View state
	 *
	 * @var object
	 */
	protected $state;

	/**
	 * Plugin HTML
	 * @var string
	 */
	protected $pluginFields;

	/**
	 * JavaScript Events
	 * @var array
	 */
	protected $jsevents;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 */

	public function display($tpl = null)
	{
		if ($this->getLayout() == 'confirmupdate')
		{
			$this->confirmupdate();

			return;
		}

		// Initialiase variables.
		$model = $this->getModel();
		$this->form = $model->getForm();
		$this->item = $model->getItem();
		$this->state = $model->getState();
		$this->pluginFields = $model->getPluginHTML();

		$this->js = $model->getJs();

		// Check for errors.
		if (count($errors = $model->getErrors()))
		{
			throw new RuntimeException(implode("\n", $errors), 500);
		}

		$this->addToolbar();
		$this->parent = $model->getParent();
		FabrikAdminHelper::setViewLayout($this);
		JText::script('COM_FABRIK_ERR_ELEMENT_JS_ACTION_NOT_DEFINED');

		$srcs = FabrikHelperHTML::framework();
		$srcs['Fabrik'] = FabrikHelperHTML::mediaFile('fabrik.js');
		$srcs['NameSpace'] = 'administrator/components/com_fabrik/views/namespace.js';
		$srcs['fabrikAdminElement'] = 'administrator/components/com_fabrik/views/element/tmpl/adminelement.js';

		$shim = array();
		$dep = new stdClass;
		$dep->deps = array('admin/pluginmanager');
		$shim['admin/element/tmpl/adminelement'] = $dep;
		$shim['adminfields/tables'] = $dep;

		$plugManagerDeps = new stdClass;
		$plugManagerDeps->deps = array('admin/namespace');
		$shim['admin/pluginmanager'] = $plugManagerDeps;
		FabrikHelperHTML::iniRequireJS($shim);
		FabrikHelperHTML::script($srcs, $this->js);
		parent::display($tpl);
	}

	/**
	 * Ask the user if they really want to alter the element fields structure/name
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 */

	protected function confirmupdate($tpl = null)
	{
		$model = $this->getModel();
		$this->state = $model->getState();
		$app = JFactory::getApplication();
		$this->addConfirmToolbar();
		$this->item = $model->getItem();
		$this->oldName = $app->getUserState('com_fabrik.oldname');
		$this->origDesc = $app->getUserState('com_fabrik.origDesc');
		$this->newDesc = $app->getUserState('com_fabrik.newdesc');
		$this->origPlugin = $app->getUserState('com_fabrik.origplugin');
		$this->origtask = $app->getUserState('com_fabrik.origtask');
		$app->setUserState('com_fabrik.confirmUpdate', 0);
		parent::display($tpl);
	}

	/**
	 * Add the confirmation tool bar
	 *
	 * @return  void
	 */

	protected function addConfirmToolbar()
	{
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_ELEMENT_EDIT'), 'checkbox-unchecked');
		$app = JFactory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		JToolBarHelper::save('element.updatestructure', 'JTOOLBAR_SAVE');
		JToolBarHelper::cancel('element.cancelUpdatestructure', 'JTOOLBAR_CANCEL');
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since	1.6
	 *
	 * @return  void
	 */

	protected function addToolbar()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		$user = JFactory::getUser();
		$userId = $user->get('id');
		$isNew = ($this->item->id == 0);
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		$canDo = FabrikAdminHelper::getActions($this->state->get('filter.category_id'));
		$title = $isNew ? FText::_('COM_FABRIK_MANAGER_ELEMENT_NEW') : FText::_('COM_FABRIK_MANAGER_ELEMENT_EDIT') . ' "' . $this->item->name . '"';
		JToolBarHelper::title($title, 'checkbox-unchecked');

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				JToolBarHelper::apply('element.apply', 'JTOOLBAR_APPLY');
				JToolBarHelper::save('element.save', 'JTOOLBAR_SAVE');
				JToolBarHelper::addNew('element.save2new', 'JTOOLBAR_SAVE_AND_NEW');
			}

			JToolBarHelper::cancel('element.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					JToolBarHelper::apply('element.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('element.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						JToolBarHelper::addNew('element.save2new', 'JTOOLBAR_SAVE_AND_NEW');
					}
				}
			}

			if ($canDo->get('core.create'))
			{
				JToolBarHelper::custom('element.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}

			JToolBarHelper::cancel('element.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_ELEMENTS_EDIT', false, FText::_('JHELP_COMPONENTS_FABRIK_ELEMENTS_EDIT'));
	}
}
