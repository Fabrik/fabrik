<?php
/**
 * Approval Raw View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.approvals
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * Approval Raw View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.slideshow
 * @since       3.0.6
 */

class FabrikViewApprovals extends JViewLegacy
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

		if (!$model->canView())
		{
			echo FText::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		$this->plugin = $this->get('Plugin');
		$task = $input->get('plugintask');
		$model->$task();
	}
}
