<?php
/**
 * Elements list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

require_once 'fabcontrolleradmin.php';

/**
 * Elements list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikAdminControllerElements extends FabControllerAdmin
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
	 * @param   string  $name    Model name
	 * @param   string  $prefix  Model prefix
	 * @param   array   $config  Model config
	 *
	 * @return  J model
	 */
	public function getModel($name = 'Element', $prefix = 'FabrikAdminModel', $config = array())
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
		JSession::checkToken() or die(FText::_('JINVALID_TOKEN'));

		// Get items to publish from the request.
		$app = JFactory::getApplication();
		$input = $app->input;
		$cid = $input->get('cid', array(), 'array');
		$data = array('showInListView' => 1, 'hideFromListView' => 0);
		$task = $this->getTask();
		$value = FArrayHelper::getValue($data, $task, 0, 'int');

		if (empty($cid))
		{
			JError::raiseWarning(500, FText::_($this->text_prefix . '_NO_ITEM_SELECTED'));
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			$cid = ArrayHelper::toInteger($cid);

			// Publish the items.
			if (!$model->addToListView($cid, $value))
			{
				JError::raiseWarning(500, $model->getError());
			}
			else
			{
				if ($value == 1)
				{
					$nText = $this->text_prefix . '_N_ITEMS_ADDED_TO_LIST_VIEW';
				}
				else
				{
					$nText = $this->text_prefix . '_N_ITEMS_REMOVED_FROM_LIST_VIEW';
				}

				$this->setMessage(JText::plural($nText, count($cid)));
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
		$viewType = JFactory::getDocument()->getType();
		$view = $this->getView($this->view_item, $viewType);
		$view->setLayout('confirmdelete');

		if ($model = $this->getModel('Elements'))
		{
			// Push the model into the view (as default)
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
		$viewType = JFactory::getDocument()->getType();
		$view = $this->getView($this->view_item, $viewType);
		$view->setLayout('copyselectgroup');

		if ($model = $this->getModel('Elements'))
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
		$this->setRedirect('index.php?option=com_fabrik&view=elements', FText::_('COM_FABRIK_MSG_BATCH_DONE'));
	}

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return	void
	 *
	 * @since   3.1rc1
	 */
	public function saveOrderAjax()
	{
		$pks = $this->input->post->get('cid', array(), 'array');
		$order = $this->input->post->get('order', array(), 'array');

		// Sanitize the input
		$pks = ArrayHelper::toInteger($pks);
		$order = ArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		JFactory::getApplication()->close();
	}

	/**
	 * Method to publish a list of items
	 *
	 * @return  null
	 */
	public function publish()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$cid = $input->get('cid', array(), 'array');
		$model = $this->getModel('Elements');
		$task = $this->getTask();

		if ($task === 'unpublish')
		{
			$cid = $model->canUnpublish($cid);
			$input->set('cid', $cid);
		}

		if (empty($cid))
		{
			$this->setRedirect('index.php?option=com_fabrik&view=elements');
		}
		else
		{
			parent::publish();
		}
	}
}
