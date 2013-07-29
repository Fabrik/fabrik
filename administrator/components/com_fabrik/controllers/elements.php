<?php
/**
 * Elements list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       1.6
 */

// No direct access.
defined('_JEXEC') or die;

require_once 'fabcontrolleradmin.php';

/**
 * Elements list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikControllerElements extends FabControllerAdmin
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_ELEMENTS';

	/**
	 * View item name
	 *
	 * @var string
	 */
	protected $view_item = 'elements';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see		JController
	 * @since	1.6
	 */

	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerTask('showInListView', 'toggleInList');
		$this->registerTask('hideFromListView', 'toggleInList');
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    model name
	 * @param   string  $prefix  model prefix
	 *
	 * @return  J model
	 */

	public function &getModel($name = 'Element', $prefix = 'FabrikModel')
	{
		$config = array();
		$config['ignore_request'] = true;
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}

	/**
	 * Set selected elements to be shown/not shown in list
	 *
	 * @return null
	 */

	public function toggleInList()
	{
		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

		// Get items to publish from the request.
		$app = JFactory::getApplication();
		$input = $app->input;
		$cid = $input->get('cid', array(), 'array');
		$data = array('showInListView' => 1, 'hideFromListView' => 0);
		$task = $this->getTask();
		$value = JArrayHelper::getValue($data, $task, 0, 'int');
		if (empty($cid))
		{
			JError::raiseWarning(500, JText::_($this->text_prefix . '_NO_ITEM_SELECTED'));
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			JArrayHelper::toInteger($cid);

			// Publish the items.
			if (!$model->addToListView($cid, $value))
			{
				JError::raiseWarning(500, $model->getError());
			}
			else
			{
				if ($value == 1)
				{
					$ntext = $this->text_prefix . '_N_ITEMS_ADDED_TO_LIST_VIEW';
				}
				else
				{
					$ntext = $this->text_prefix . '_N_ITEMS_REMOVED_FROM_LIST_VIEW';
				}
				$this->setMessage(JText::plural($ntext, count($cid)));
			}
		}
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	/**
	 * Set up page asking about what to delete
	 *
	 * @since	1.6
	 *
	 * @return null
	 */

	public function delete()
	{
		$model = $this->getModel('Elements');
		$viewType = JFactory::getDocument()->getType();
		$view = $this->getView($this->view_item, $viewType);
		$view->setLayout('confirmdelete');
		if (!JError::isError($model))
		{
			$view->setModel($model, true);
		}

		// Used to load in the confirm form fields
		$view->setModel($this->getModel('list'));
		$view->display();
	}

	/**
	 * Cancel delete element
	 *
	 * @return  null
	 */

	public function cancel()
	{
		$this->setRedirect('index.php?option=com_fabrik&view=elements');
	}

	/**
	 * Set up the page to ask for which group to copy the element to
	 *
	 * @return  null
	 */

	public function copySelectGroup()
	{
		JSession::checkToken() or die('Invalid Token');
		$model = $this->getModel('Elements');
		$viewType = JFactory::getDocument()->getType();
		$view = $this->getView($this->view_item, $viewType);
		$view->setLayout('copyselectgroup');
		if (!JError::isError($model))
		{
			$view->setModel($model, true);
		}

		// Used to load in the confirm form fields
		$view->setModel($this->getModel('list'));
		$view->display();
	}

	/**
	 * Batch process elements, setting acl levels
	 *
	 * @since   3.0.7
	 *
	 * @return  void
	 */

	public function batch()
	{
		JSession::checkToken() or die('Invalid Token');
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = $this->getModel('Elements');
		$cid = $input->get('cid', array(), 'array');
		$opts = $input->get('batch', array(), 'array');
		$model->batch($cid, $opts);
		$this->setRedirect('index.php?option=com_fabrik&view=elements', JText::_('COM_FABRIK_MSG_BATCH_DONE'));
	}

}
