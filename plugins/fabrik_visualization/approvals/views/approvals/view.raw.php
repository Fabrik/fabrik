<?php
/**
 * Approval Raw View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.approvals
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

/**
 * Approval Raw View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.slideshow
 * @since       3.0.6
 */

class FabrikViewApprovals extends JView
{

	/**
	 * Display view
	 *
	 * @param   string  $tmpl  Template
	 *
	 * @return  void
	 */
	public function display($tmpl = 'default')
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = $this->getModel();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$id = $input->get('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0)));
		$model->setId($id);

		$this->plugin = $this->get('Plugin');
		$model->runPluginTask();
	}

}
