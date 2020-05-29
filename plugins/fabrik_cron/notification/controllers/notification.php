<?php
/**
 * Cron Notification Fabrik Plug-in Controller
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
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @since       3.0
 */
class FabrikControllerCroncronnotification extends JControllerLegacy
{
	/**
	 * Display the view
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached - NOTE not actually used to control caching!!!
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  A JController object to support chaining.
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$document = JFactory::getDocument();
		$viewName = 'cronnotification';
		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view
		if ($model = $this->getModel($viewName))
		{
			$view->setModel($model, true);
		}

		// Display the view
		$view->error = $this->getError();
		$view->display();

		return $this;
	}

	/**
	 * Delete a notification
	 *
	 * @return  void
	 */
	public function delete()
	{
		$model = $this->getModel('cronnotification');
		$model->delete();
		$this->setRedirect('index.php?option=com_fabrikn&task=cron.cronnotification', FText::_('NOTIFICATIONS_REMOVED'));
	}
}
