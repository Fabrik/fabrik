<?php
/**
 * Connections controller class
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once 'fabcontrolleradmin.php';

/**
 * Connections list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       1.6
 */
class FabrikAdminControllerConnections extends FabControllerAdmin
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_CONNECTIONS';

	/**
	 * View item name
	 *
	 * @var string
	 */
	protected $view_item = 'connections';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see		JController
	 *
	 * @since	1.6
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerTask('unsetDefault', 'setDefault');
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    model name
	 * @param   string  $prefix  model prefix
	 *
	 * @since	1.6
	 *
	 * @return  J model
	 */
	public function &getModel($name = 'Connection', $prefix = 'FabrikAdminModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * Method to set the home property for a list of items
	 *
	 * @since	1.6
	 *
	 * @return null
	 */
	public function setDefault()
	{
		// Check for request forgeries
		JSession::checkToken() or die(FText::_('JINVALID_TOKEN'));
		$app = JFactory::getApplication();
		$input = $app->input;

		// Get items to publish from the request.
		$cid = $input->get('cid', array(), 'array');
		$data = array('setDefault' => 1, 'unsetDefault' => 0);
		$task = $this->getTask();
		$value = FArrayHelper::getValue($data, $task, 0, 'int');

		if ($value == 0)
		{
			$this->setMessage(FText::_('COM_FABRIK_CONNECTION_CANT_UNSET_DEFAULT'));
		}

		if (empty($cid))
		{
			JError::raiseWarning(500, FText::_($this->text_prefix . '_NO_ITEM_SELECTED'));
		}
		else
		{
			if ($value != 0)
			{
				$cid = $cid[0];

				// Get the model.
				$model = $this->getModel();

				// Publish the items.
				if (!$model->setDefault($cid, $value))
				{
					JError::raiseWarning(500, $model->getError());
				}
				else
				{
					$this->setMessage(FText::_('COM_FABRIK_CONNECTION_SET_DEFAULT'));
				}
			}
		}

		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}
}
