<?php
/**
 * Email list plugin view
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.email
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * Email list plugin view
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.email
 * @since       3.0
 */
class FabrikViewPopupwin extends JViewLegacy
{
	/**
	 * Display the view
	 *
	 * @param   string $tmpl Template
	 *
	 * @return  JView  this
	 */
	public function display($tmpl = 'default')
	{
		$w           = new FabrikWorker;
		$params      = $this->getParams();
		$app         = JFactory::getApplication();
		$model       = $this->getModel();
		$input       = $app->input;
		$renderOrder = $input->getInt('renderOrder');

		$path = JPATH_ROOT . '/plugins/fabrik_list/email/views/popupwin/tmpl/' . $tmpl;
		$this->_setPath('template', $path);

		$this->showToField = $model->getShowToField();
		$records           = $model->getRecords();

		if (count($records) == 0)
		{
			$app->enqueueMessage(FText::_('PLG_LIST_EMAIL_ERR_NONE_MAILED'), 'notice');

			return;
		}

		$this->recordcount     = count($records);
		$this->renderOrder     = $renderOrder;
		$this->recordids       = implode(',', $records);
		$this->listid          = $this->get('id', 'list');
		$this->showSubject     = $model->getShowSubject();
		$this->subject         = $model->getSubject();
		$this->message         = $model->getMessage();
		$this->allowAttachment = $model->getAllowAttachment();
		$this->editor          = $model->getEditor();
		$this->toType          = $model->_toType();
		$this->emailTo        = $model->_emailTo();
		$this->params          = $model->getParams();
		$this->listEmailTo     = $model->formModel->getElementList('list_email_to');
		$this->addressBook     = $model->addressBook();
		$this->additionalQS    = $w->parseMessageForPlaceHolder($params->get('list_email_additional_qs', ''));


		$srcs = FabrikHelperHTML::framework();
		FabrikHelperHTML::iniRequireJs();
		FabrikHelperHTML::script($srcs);

		return parent::display();
	}
}
