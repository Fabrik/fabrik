<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/components/com_fabrik/views/list/view.base.php';

class FabrikViewlist extends FabrikViewListBase
{

	/**
	 * Display a json object representing the table data.
	 * Not used for updating fabrik list, use raw view for that, here in case you want to export the data to another application
	 *
	 * @return  void
	 */

	function display($tpl = null)
	{
		$app = JFactory::getApplication();
		$model = $this->getModel();
		$model->setId($app->input->getInt('listid'));
		if (!parent::access($model))
		{
			exit;
		}
		$data = $model->getData();
		echo json_encode($data);
	}

}
