<?php
/**
 * Content Type controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.4.5
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controllerform');

require_once 'fabcontrollerform.php';

/**
 * Content Type controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 */
class FabrikAdminControllerContentType extends FabControllerForm
{
	/**
	 * Previews the content type's groups and elements
	 *
	 * @throws Exception
	 */
	public function preview()
	{
		$contentType = $this->input->getString('contentType');
		$listModel = $this->getModel('list');
		$model = $this->getModel('contenttypeImport', '', array('listModel' => $listModel));
		$viewType = JFactory::getDocument()->getType();
		$this->name = 'Fabrik';
		$this->setPath('view', COM_FABRIK_FRONTEND . '/views');
		$viewLayout = $this->input->get('layout', 'default');

		$view = $this->getView('Form', $viewType, '');
		$view->setLayout($viewLayout);

		/** @var FabrikFEModelForm  $formModel */
		$formModel = JModelLegacy::getInstance('Form', 'FabrikFEModel');
		$formModel->groups = $model->loadContentType($contentType)->preview();
		$view->setModel($formModel, true);

		$formModel->getGroupView('bootstrap');

		$view->preview();
		$res = new stdClass;
		ob_start();
		$view->output();
		$res->preview = ob_get_contents();
		ob_end_clean();
		$res->aclMap = $model->aclCheckUI();
		echo json_encode($res);
		exit;
	}
}
