<?php

/**
* Determines if a row is editable
* @package Joomla
* @subpackage Fabrik
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

use Joomla\Utilities\ArrayHelper;

//require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

class PlgFabrik_ListLockrow extends PlgFabrik_List
{
	protected $result = null;

	public function canSelectRows()
	{
		return false;
	}

	public function onCanEdit($row)
	{
		$params = $this->getParams();
		$model = $this->getModel();

		// If $row is null, we were called from the table's canEdit() in a per-table rather than per-row context,
		// and we don't have an opinion on per-table edit permissions, so just return true.
		if (is_null($row) || is_null($row[0]))
		{
			$this->result = true;
			return true;
		}

		$data = array();
		if (!is_array($row[0]))
		{
			$data = ArrayHelper::fromObject($row[0]);
		}
		else
		{
			$data = $row[0];
		}

		// Sometimes we might have been given a $row with a single empty object (like getEmailData on elements).
		if (empty($data))
		{
			$this->result = true;
			return true;
		}

		$groupModels = $model->getFormGroupElementData();
		static $lockElementModel = null;
		static $lockElementName = null;
		static $hasLock = null;

		if ($hasLock === null) {
			foreach ($groupModels as $groupModel) {
				// not going to mess with having lockrow elements in joins for now
				if ($groupModel->isJoin())
				{
					continue;
				}

				$elementModels = $groupModel->getPublishedElements();
				foreach ($elementModels as $elementModel) {
					if (is_a($elementModel, 'PlgFabrik_ElementLockrow'))
					{
						// found one, only support one per table, so stash it and bail
						$lockElementModel = $elementModel;
						$lockElementName = $elementModel->getFullName(true, false);
						$hasLock = true;
						break 2;
					}
				}
			}

			// set the static cache to false if we didn't find anything
			if ($hasLock !== true)
			{
				$hasLock = false;
			}
		}


		/**
		 * If there's an active lock, set access to false, otherwise set to null, which means "no opinion",
		 * so we don't override the standard ACLs (in other words, don't return true when no lock)
		 */

		if ($hasLock)
		{

			$value = ArrayHelper::getValue($data, $lockElementName . '_raw', '0');

			if (\Fabrik\Helpers\Worker::inFormProcess())
			{
				$this->result = $lockElementModel->isSubmitLocked($value) === true ? false : null;
			}
			else
			{
				$this->result = $lockElementModel->isLocked($value) === true ? false : null;
			}
		}
		else
		{
			$this->result = null;
		}

		return $this->result;
	}

	/**
	 * Custom process plugin result
	 *
	 * @param   string $method Method
	 *
	 * @return boolean
	 */
	public function customProcessResult($method)
	{
		/*
		 * If we didn't return false from onCanEdit(), the plugin manager will get the final result from this method,
		 * so we need to return whatever onCanEdit() set the result to.
		 */
		if ($method === 'onCanEdit')
		{
			return $this->result;
		}

		return true;
	}
}