<?php
/**
 * View to edit a cron.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * View to edit a cron.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       1.5
 */
class FabrikAdminViewCron extends JViewLegacy
{
	/**
	 * Form
	 *
	 * @var JForm
	 */
	protected $form;

	/**
	 * Cron item
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
		$this->pluginFields = $this->get('PluginHTML');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new RuntimeException(implode("\n", $errors), 500);
		}

		$this->addToolbar();
		FabrikAdminHelper::setViewLayout($this);

		$srcs = FabrikHelperHTML::framework();
		$srcs['Fabrik'] = FabrikHelperHTML::mediaFile('fabrik.js');
		$srcs['Namespace'] = 'administrator/components/com_fabrik/views/namespace.js';
		$srcs['PluginManager'] = 'administrator/components/com_fabrik/views/pluginmanager.js';
		$srcs['CronAdmin'] = 'administrator/components/com_fabrik/views/cron/admincron.js';

		$shim = array();
		$dep = new stdClass;
		$dep->deps = array('admin/pluginmanager');
		$shim['admin/cron/admincron'] = $dep;

		$opts = new stdClass;
		$opts->plugin = $this->item->plugin;

		$js = array();
		$js[] = "\twindow.addEvent('domready', function () {";
		$js[] = "\t\tFabrik.controller = new CronAdmin(" . json_encode($opts) . ");";
		$js[] = "\t})";
		FabrikHelperHTML::iniRequireJS($shim);
		FabrikHelperHTML::script($srcs, implode("\n", $js));

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
		$title = $isNew ? FText::_('COM_FABRIK_MANAGER_CRON_NEW') : FText::_('COM_FABRIK_MANAGER_CRON_EDIT') . ' "' . $this->item->label . '"';
		JToolBarHelper::title($title, 'clock');

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				JToolBarHelper::apply('cron.apply', 'JTOOLBAR_APPLY');
				JToolBarHelper::save('cron.save', 'JTOOLBAR_SAVE');
				JToolBarHelper::addNew('cron.save2new', 'JTOOLBAR_SAVE_AND_NEW');
			}

			JToolBarHelper::cancel('cron.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					JToolBarHelper::apply('cron.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('cron.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						JToolBarHelper::addNew('cron.save2new', 'JTOOLBAR_SAVE_AND_NEW');
					}
				}
			}

			if ($canDo->get('core.create'))
			{
				JToolBarHelper::custom('cron.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}

			JToolBarHelper::cancel('cron.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_CRONS_EDIT', false, FText::_('JHELP_COMPONENTS_FABRIK_CRONS_EDIT'));
	}
}
