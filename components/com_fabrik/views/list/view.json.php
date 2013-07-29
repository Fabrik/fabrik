<?php
/**
* @package     Joomla
* @subpackage  Fabrik
* @copyright   Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once JPATH_SITE . '/components/com_fabrik/views/list/view.base.php';

class FabrikViewlist extends FabrikViewListBase
{

	/**
	 * Display a json object representing the table data.
	 * Not used for updating fabrik list, use raw view for that, here in case you want to export the data to another application
	 */

	function display($tpl = null)
	{
		$model = $this->getModel();
		$app = JFactory::getApplication();
		$model->setId($app->input->getInt('listid'));
		if (!parent::access($model))
		{
			exit;
		}
		$data = $model->getData();
		echo json_encode($data);
	}

}
