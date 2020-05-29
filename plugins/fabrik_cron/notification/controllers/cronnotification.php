<?php
/**
 * Cron Notification Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

/**
 * Cron Notification Fabrik Plug-in Controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       1.5
 */
class FabrikControllerCroncronnotification extends JControllerLegacy
{
	/**
	 * Display the view
	 *
	 * @return  void
	 */
	public function display()
	{
		$document = JFactory::getDocument();
		$viewName = 'cronnotification';
		$viewType	= $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view

		if ($model	= &$this->getModel($viewName))
		{
			$view->setModel($model, true);
		}

		// Display the view
		$view->error = $this->getError();

		return $view->display();
	}

	/**
	 * Delete a notification
	 *
	 * @return false
	 */
	public function delete()
	{
		$model = $this->getModel('cronnotification');
		$model->delete();
		$this->setRedirect('index.php?option=com_fabrik&view=cron&controller=cron.cronnotification', FText::_('NOTIFICATIONS_REMOVED'));
	}
}
