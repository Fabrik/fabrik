<?php

/**
 * @package		Joomla
 * @subpackage	Fabik
 * @copyright	Copyright (C) 2005 - 2008 Pollen 8 Design Ltd. All rights reserved.
 * @license		GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once JPATH_SITE . '/components/com_fabrik/views/list/view.base.php';

class FabrikViewList extends FabrikViewListBase
{

	/**
	 * Display the template
	 *
	 * @param   sting  $tpl  template
	 *
	 * @return void
	 */

	public function display($tpl = null)
	{
		$document = JFactory::getDocument();
		$model = $this->getModel();
		$usersConfig = JComponentHelper::getParams('com_fabrik');

		$model->setId(JRequest::getVar('listid', $usersConfig->get('listid')));
		$model->render();
		$table = $model->getTable();

		$document->title = $table->label;
		$document->description = $table->introduction;
		$document->copyright = '';
		$document->listid = $table->id;

		$document->items = $model->getData();

	}
}


