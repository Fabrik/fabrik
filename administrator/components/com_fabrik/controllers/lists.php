<?php
/**
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
 * Lists list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikControllerLists extends FabControllerAdmin
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_LISTS';

	/**
	 * View item name
	 *
	 * @var string
	 */
	protected $view_item = 'lists';

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
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since   12.2
	 */

	public function &getModel($name = 'List', $prefix = 'FabrikModel')
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));
		return $model;
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
		$data = array('publish' => 1, 'unpublish' => 0, 'archive' => 2, 'trash' => -2, 'report' => -3);
		$task = $this->getTask();
		$value = JArrayHelper::getValue($data, $task, 0, 'int');
		if (empty($cid))
		{
			JError::raiseWarning(500, JText::_($this->text_prefix . '_NO_ITEM_SELECTED'));
		}
		else
		{
			// Make sure the item ids are integers
			JArrayHelper::toInteger($cid);
			$model = $this->getModel('Form');
			$formids = $model->swapListToFormIds($cid);

			// Publish the items.
			$formKeys = array();
			if (!$model->publish($formids, $value))
			{
				JError::raiseWarning(500, $model->getError());
			}
			else
			{
				// Publish the groups
				$groupModel = $this->getModel('Group');
				if (is_object($groupModel))
				{
					$groupids = $groupModel->swapFormToGroupIds($formids);
					if (!empty($groupids))
					{
						if ($groupModel->publish($groupids, $value) === false)
						{
							JError::raiseWarning(500, $groupModel->getError());
						}
						else
						{
							// Publish the elements
							$elementModel = $this->getModel('Element');
							$elementIds = $elementModel->swapGroupToElementIds($groupids);
							if (!$elementModel->publish($elementIds, $value))
							{
								JError::raiseWarning(500, $elementModel->getError());
							}
						}
					}
				}
				// Finally publish the list
				parent::publish();
			}
		}
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	/**
	 * Set up page asking about what to delete
	 *
	 * @return  null
	 */

	public function delete()
	{
		$model = $this->getModel();
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

}
