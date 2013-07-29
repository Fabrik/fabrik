<?php
/**
 * Connection controller class
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

/**
 * Connection controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       1.6
*/
class FabrikControllerConnection extends JControllerForm
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_CONNECTION';

	/**
	 * Trys to connection to the database
	 *
	 * @return string connection message
	 */

	public function test()
	{
		JSession::checkToken() or die('Invalid Token');
		$app = JFactory::getApplication();
		$input = $app->input;
		$cid = $input->get('cid', array(), 'array');
		$cid = array((int) $cid[0]);
		$link = 'index.php?option=com_fabrik&view=connections';
		foreach ($cid as $id)
		{
			$model = JModel::getInstance('Connection', 'FabrikFEModel');
			$model->setId($id);
			if ($model->testConnection() == false)
			{
				JError::raiseWarning(500, JText::_('COM_FABRIK_UNABLE_TO_CONNECT'));
				$this->setRedirect($link);
				return;
			}
		}
		$this->setRedirect($link, JText::_('COM_FABRIK_CONNECTION_SUCESSFUL'));
	}

}
