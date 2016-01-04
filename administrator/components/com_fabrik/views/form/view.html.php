<?php
/**
 * View to edit a form.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * View to edit a form.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikAdminViewForm extends JViewLegacy
{
	/**
	 * Form
	 *
	 * @var JForm
	 */
	protected $form;

	/**
	 * Form item
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
	 * Js code for controlling plugins
	 *
	 * @var string
	 */
	protected $js;

	/**
	 * Display the view
	 *
	 * @param   string $tpl template
	 *
	 * @return  void
	 */

	public function display($tpl = null)
	{
		// Initialise variables.
		$this->form  = $this->get('Form');
		$this->item  = $this->get('Item');
		$this->state = $this->get('State');
		$this->js    = $this->get('Js');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new RuntimeException(implode("\n", $errors), 500);
		}

		$this->addToolbar();
		FabrikAdminHelper::setViewLayout($this);

		// Set up the script shim
		$shim                        = array();
		$dep                         = new stdClass;
		$dep->deps                   = array('fab/fabrik');
		$shim['admin/pluginmanager'] = $dep;
		FabrikHelperHTML::iniRequireJS($shim);

		$srcs   = FabrikHelperHTML::framework();
		$srcs[] = 'administrator/components/com_fabrik/views/namespace.js';
		$srcs[] = 'administrator/components/com_fabrik/views/pluginmanager.js';

		FabrikHelperHTML::script($srcs, $this->js);
		parent::display($tpl);
	}

	/**
	 * Alias to display
	 *
	 * @param   string $tpl Template
	 *
	 * @return  void
	 */

	public function form($tpl = null)
	{
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 */

	protected function addToolbar()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		$user       = JFactory::getUser();
		$userId     = $user->get('id');
		$isNew      = ($this->item->id == 0);
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		$canDo      = FabrikAdminHelper::getActions($this->state->get('filter.category_id'));
		$title      = $isNew ? FText::_('COM_FABRIK_MANAGER_FORM_NEW') : FText::_('COM_FABRIK_MANAGER_FORM_EDIT') . ' "' . $this->item->label . '"';
		JToolBarHelper::title($title, 'file-2');

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				JToolBarHelper::apply('form.apply', 'JTOOLBAR_APPLY');
				JToolBarHelper::save('form.save', 'JTOOLBAR_SAVE');
				JToolBarHelper::addNew('form.save2new', 'JTOOLBAR_SAVE_AND_NEW');
			}

			JToolBarHelper::cancel('form.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					JToolBarHelper::apply('form.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('form.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						JToolBarHelper::addNew('form.save2new', 'JTOOLBAR_SAVE_AND_NEW');
					}
				}
			}
			// $$$ No 'save as copy' as this gets complicated due to renaming lists, groups etc. Users should copy from list view.
			JToolBarHelper::cancel('form.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_FORMS_EDIT', false, FText::_('JHELP_COMPONENTS_FABRIK_FORMS_EDIT'));
	}

	/**
	 * Once a form is saved - we need to display the select content type form.
	 *
	 * @param null $tpl
	 *
	 * @return void
	 */
	public function selectContentType($tpl = null)
	{
		$model      = $this->getModel();
		$this->form = $model->getContentTypeForm();
		$input      = JFactory::getApplication()->input;
		$this->data = $input->post->get('jform', array(), 'array');
		$this->addSelectSaveToolBar();
		FabrikHelperHTML::framework();
		FabrikHelperHTML::iniRequireJS();

		parent::display($tpl);
	}

	/**
	 * Add select content type tool bar
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	protected function addSelectSaveToolBar()
	{
		$app         = JFactory::getApplication();
		$this->state = $this->get('State');
		$input       = $app->input;
		$input->set('hidemainmenu', true);
		$canDo = FabrikAdminHelper::getActions($this->state->get('filter.category_id'));
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_SELECT_CONTENT_TYPE'), 'puzzle');

		// For new records, check the create permission.
		if ($canDo->get('core.create'))
		{
			JToolBarHelper::apply('form.doSave', 'JTOOLBAR_SAVE');
			JToolBarHelper::cancel('form.cancel', 'JTOOLBAR_CANCEL');
		}
	}
}
