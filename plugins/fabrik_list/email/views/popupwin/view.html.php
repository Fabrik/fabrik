<?php
/**
 * Email list plugin view
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.email
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
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
	 * @param   string  $tmpl  Template
	 *
	 * @return  JView  this
	 */

	public function display($tmpl = 'default')
	{
		$app = JFactory::getApplication();
		$model = $this->getModel();
		$input = $app->input;
		$renderOrder = $input->getInt('renderOrder');
		$usersConfig = JComponentHelper::getParams('com_fabrik');

		$tmplpath = JPATH_ROOT . '/plugins/fabrik_list/email/views/popupwin/tmpl/' . $tmpl;
		$this->_setPath('template', $tmplpath);

		$this->showToField = $model->getShowToField();
		$this->fieldList = $model->getToField();
		$records = $model->getRecords();

		if (count($records) == 0)
		{
			$app->enqueueMessage('None of the selected records can be emailed', 'notice');

			return;
		}

		$this->recordcount = count($records);
		$this->renderOrder = $renderOrder;
		$this->recordids = implode(',', $records);
		$this->listid = $this->get('id', 'list');
		$this->showSubject = $model->getShowSubject();
		$this->subject = $model->getSubject();
		$this->message = $model->getMessage();
		$this->allowAttachment = $model->getAllowAttachment();
		$this->editor = $model->getEditor();

		$srcs = FabrikHelperHTML::framework();
		FabrikHelperHTML::script($srcs);

		return parent::display();
	}
}
