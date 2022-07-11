<?php
/**
 * Fabrik Lists List Controller class
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

require_once 'fabcontrolleradmin.php';

/**
 * Lists list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikAdminControllerLists extends FabControllerAdmin
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
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JModelLegacy  The model.
	 *
	 * @since   12.2
	 */
	public function getModel($name = 'List', $prefix = 'FabrikAdminModel', $config = array())
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
		$input = $this->input;
		$cid = $input->get('cid', array(), 'array');
		$data = array('publish' => 1, 'unpublish' => 0, 'archive' => 2, 'trash' => -2, 'report' => -3);
		$task = $this->getTask();
		$value = FArrayHelper::getValue($data, $task, 0, 'int');

		if (empty($cid))
		{
			$this->setMessage(FText::_($this->text_prefix . '_NO_ITEM_SELECTED'), 'error');
		}
		else
		{
			// Make sure the item ids are integers
			$cid = ArrayHelper::toInteger($cid);
			$model = $this->getModel('Form', 'FabrikAdminModel');
			$formIds = $model->swapListToFormIds($cid);

			// Publish the items.

			if (!$model->publish($formIds, $value))
			{
				$this->setMessage($model->getError(), 'error');
			}
			else
			{
				// Publish the groups
				$groupModel = $this->getModel('Group');

				if (is_object($groupModel))
				{
					$groupIds = $groupModel->swapFormToGroupIds($formIds);

					if (!empty($groupIds))
					{
						if ($groupModel->publish($groupIds, $value) === false)
						{
							$this->setMessage($groupModel->getError(), 'error');
						}
						else
						{
							// Publish the elements
							$elementModel = $this->getModel('Element');
							$elementIds = $elementModel->swapGroupToElementIds($groupIds);

							if (!$elementModel->publish($elementIds, $value))
							{
								$this->setMessage($elementModel->getError(), 'error');
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
		$listsModel = $this->getModel('lists');
		$viewType = JFactory::getDocument()->getType();
		$view = $this->getView($this->view_item, $viewType);
		$view->setLayout('confirmdelete');

		if ($model = $this->getModel())
		{
			$view->setModel($model, true);
			$view->setModel($listsModel);
		}
		// Used to load in the confirm form fields
		$view->setModel($this->getModel('list'));
		$view->display();
	}
}
