<?php
/**
 * View to edit a form.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * View to edit a form.
 *
 * @package		Joomla.Administrator
 * @subpackage	Fabrik
 * @since		3.0
*/

class FabrikViewForm extends JViewLegacy
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
	 * Display the view
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 */

	public function display($tpl = null)
	{
		// Initialiase variables.
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		$this->state = $this->get('State');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new RuntimeException(implode("\n", $errors), 500);
			return false;
		}
		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Alias for display()
	 *
	 * @param   string  $tpl  Template
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
	 * @reutrn  void
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

		JToolBarHelper::title($isNew ? JText::_('COM_FABRIK_MANAGER_FORM_NEW') : JText::_('COM_FABRIK_MANAGER_FORM_EDIT'), 'form.png');
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
			if ($canDo->get('core.create'))
			{
				JToolBarHelper::custom('form.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}
			JToolBarHelper::cancel('form.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_FORMS_EDIT');
	}

	/**
	 * Not used?
	 *
	 * @return  void
	 */
	public function plugin()
	{
		echo "plugin";
	}

}
