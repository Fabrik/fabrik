<?php
/**
 * Connection controller class
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controllerform');

require_once 'fabcontrollerform.php';

/**
 * Connection controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       1.6
 */
class FabrikAdminControllerConnection extends FabControllerForm
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_CONNECTION';

	/**
	 * Tries to connection to the database
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
			$model = JModelLegacy::getInstance('Connection', 'FabrikFEModel');
			$model->setId($id);

			if ($model->testConnection() == false)
			{
				JError::raiseWarning(500, FText::_('COM_FABRIK_UNABLE_TO_CONNECT'));
				$this->setRedirect($link);

				return;
			}
		}

		$this->setRedirect($link, FText::_('COM_FABRIK_CONNECTION_SUCESSFUL'));
	}
}
