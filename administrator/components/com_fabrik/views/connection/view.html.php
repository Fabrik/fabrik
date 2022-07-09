<?php
/**
 * View to edit a connection.
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
 * View to edit a connection.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikAdminViewConnection extends JViewLegacy
{
	/**
	 * Form
	 *
	 * @var JForm
	 */
	protected $form;

	/**
	 * Connection item
	 *
	 * @var JTable
	 */
	protected $item;

	/**
	 * A state object
	 *
	 * @var    object
	 */
	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  template
	 *
	 * @return  void
	 */

	public function display($tpl = null)
	{
		// Initialiase variables.
		$model = $this->getModel();
		$this->item = $this->get('Item');
		$model->checkDefault($this->item);
		$this->form = $this->get('Form');
		$this->form->bind($this->item);
		$this->state = $this->get('State');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new RuntimeException(implode("\n", $errors), 500);
		}

		$this->addToolbar();
		FabrikAdminHelper::setViewLayout($this);

		$srcs = FabrikHelperHTML::framework();
		$srcs['Fabrik'] = 'media/com_fabrik/js/fabrik.js';

		FabrikHelperHTML::iniRequireJS();
		FabrikHelperHTML::script($srcs);
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
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
		$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		$canDo = FabrikAdminHelper::getActions($this->state->get('filter.category_id'));
		$title = $isNew ? FText::_('COM_FABRIK_MANAGER_CONNECTION_NEW') : FText::_('COM_FABRIK_MANAGER_CONNECTION_EDIT') . ' "' . $this->item->description . '"';
		JToolBarHelper::title($title, 'tree-2');

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				JToolBarHelper::apply('connection.apply', 'JTOOLBAR_APPLY');
				JToolBarHelper::save('connection.save', 'JTOOLBAR_SAVE');
				JToolBarHelper::addNew('connection.save2new', 'JTOOLBAR_SAVE_AND_NEW');
			}

			JToolBarHelper::cancel('connection.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					JToolBarHelper::apply('connection.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('connection.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						JToolBarHelper::addNew('connection.save2new', 'JTOOLBAR_SAVE_AND_NEW');
					}
				}
			}

			if ($canDo->get('core.create'))
			{
				JToolBarHelper::custom('connection.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}

			JToolBarHelper::cancel('connection.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_CONNECTIONS_EDIT', false, FText::_('JHELP_COMPONENTS_FABRIK_CONNECTIONS_EDIT'));
	}
}
