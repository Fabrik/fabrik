<?php
/**
 * Details controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controllerform');

/**
 * Details controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikAdminControllerDetails extends JControllerForm
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	 string
	 */
	protected $text_prefix = 'COM_FABRIK_FORM';

	/**
	 * Show the form in the admin
	 *
	 * @return  void
	 */
	public function view()
	{
		$document = JFactory::getDocument();
		$model = JModelLegacy::getInstance('Form', 'FabrikFEModel');
		$app = JFactory::getApplication();
		$input = $app->input;
		$input->set('tmpl', 'component');
		$input->set('view', 'details');
		$viewType = $document->getType();
		$this->setPath('view', COM_FABRIK_FRONTEND . '/views');
		$viewLayout	= $input->get('layout', 'default');
		$this->name = 'Fabrik';
		$view = $this->getView('Form', $viewType, '');
		$view->setModel($model, true);

		// Set the layout
		$view->setLayout($viewLayout);

		// @TODO check for cached version
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_FORMS'), 'file-2');

		$view->display();
		FabrikAdminHelper::addSubmenu($input->getWord('view', 'lists'));
	}
}
