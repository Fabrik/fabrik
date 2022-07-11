<?php
/**
 * View to edit a package.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.view');

/**
 * View to edit a package.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       1.5
 */

class FabrikAdminViewPackage extends JViewLegacy
{
	/**
	 * Form
	 *
	 * @var JForm
	 */
	protected $form;

	/**
	 * Package item
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
	 * List forms in a modal?
	 *
	 * @return  void
	 */

	public function listform()
	{
		$srcs = FabrikHelperHTML::framework();
		FabrikHelperHTML::script($srcs);
		$this->listform	= $this->get('PackageListForm');
		JHtml::_('behavior.modal', 'a.modal');
		parent::display('list');
	}

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 */

	public function display($tpl = null)
	{
		// Initialise variables.
		JHtml::_('behavior.modal', 'a.modal');
		$model = $this->getModel();
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		$this->state = $this->get('State');
		$this->listform	= $this->get('PackageListForm');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new RuntimeException(implode("\n", $errors), 500);
		}

		$this->addToolbar();
		$canvas = FArrayHelper::getValue($this->item->params, 'canvas', array());
		$blocks = new stdClass;
		$b = FArrayHelper::getValue($canvas, 'blocks', array());
		$blocks->form = FArrayHelper::getValue($b, 'form', array());
		$blocks->list = FArrayHelper::getValue($b, 'list', array());
		$blocks->visualization = FArrayHelper::getValue($b, 'visualization', array());

		$opts = ArrayHelper::getvalue($canvas, 'options', array());
		$d = new stdClass;
		$layout = FArrayHelper::getValue($canvas, 'layout', $d);
		$document = JFactory::getDocument();

		$opts = new stdClass;

		$opts->blocks = $blocks;
		$opts->layout = $layout;
		$opts = json_encode($opts);
		$this->js = "PackageCanvas = new AdminPackage($opts);";
		$srcs[] = 'administrator/components/com_fabrik/views/package/adminpackage.js';

		FabrikHelperHTML::iniRequireJS();
		FabrikHelperHTML::script($srcs, $this->js);

		// Simple layout
		$this->listOpts = $model->getListOpts();
		$this->formOpts = $model->getFormOpts();
		$this->selFormOpts = $model->getSelFormOpts();
		$this->selListOpts = $model->getSelListOpts();

		FabrikAdminHelper::setViewLayout($this);
		parent::display($tpl);
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
		$app->input->set('hidemainmenu', true);
		$user = JFactory::getUser();
		$userId = $user->get('id');
		$isNew = ($this->item->id == 0);
		$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		$canDo = FabrikAdminHelper::getActions($this->state->get('filter.category_id'));
		JToolBarHelper::title($isNew ? FText::_('COM_FABRIK_MANAGER_PACKAGE_NEW') : FText::_('COM_FABRIK_MANAGER_PACKAGE_EDIT') . ' "' . $this->item->label . '"', 'box-add');

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				JToolBarHelper::apply('package.apply', 'JTOOLBAR_APPLY');
				JToolBarHelper::save('package.save', 'JTOOLBAR_SAVE');
				JToolBarHelper::addNew('package.save2new', 'JTOOLBAR_SAVE_AND_NEW');
			}

			JToolBarHelper::cancel('package.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					JToolBarHelper::apply('package.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('package.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						JToolBarHelper::addNew('package.save2new', 'JTOOLBAR_SAVE_AND_NEW');
					}
				}
			}

			if ($canDo->get('core.create'))
			{
				JToolBarHelper::custom('package.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}

			JToolBarHelper::cancel('package.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_PACKAGE_EDIT', false, FText::_('JHELP_COMPONENTS_FABRIK_PACKAGE_EDIT'));
	}
}
