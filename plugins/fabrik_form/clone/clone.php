<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.clone
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Copy a series of form records
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.clone
 * @since       3.0
 */

class PlgFabrik_FormClone extends PlgFabrik_Form
{

	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @param   object  $params      plugin params
	 * @param   object  &$formModel  form model
	 *
	 * @return	bool
	 */

	public function onAfterProcess($params, &$formModel)
	{
		return $this->_process($params, $formModel);
	}

	/**
	 * Clone the record
	 *
	 * @param   object  $params      plugin params
	 * @param   object  &$formModel  form model
	 *
	 * @return  bool
	 */

	private function _process($params, &$formModel)
	{
		$clone_times_field_id = $params->get('clone_times_field', '');
		$clone_batchid_field_id = $params->get('clone_batchid_field', '');
		if ($clone_times_field_id != '')
		{
			$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($clone_times_field_id);
			$element = $elementModel->getElement(true);
			if ($clone_batchid_field_id != '')
			{
				$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($clone_batchid_field_id);
				$id_element = $id_elementModel->getElement(true);
				$formModel->_formData[$id_element->name] = $formModel->_fullFormData['rowid'];
				$formModel->_formData[$id_element->name . '_raw'] = $formModel->_fullFormData['rowid'];
				$listModel = $formModel->getlistModel();
				$listModel->setFormModel($formModel);
				$primaryKey = FabrikString::shortColName($listModel->getTable()->db_primary_key);
				$formModel->_formData[$primaryKey] = $formModel->_fullFormData['rowid'];
				$formModel->_formData[$primaryKey . '_raw'] = $formModel->_fullFormData['rowid'];
				$listModel->storeRow($formModel->_formData, $formModel->_fullFormData['rowid']);
			}

			// $clone_times_field = $elementModel->getFullName(false, true, false);
			$clone_times = $formModel->_formData[$element->name];
			if (is_numeric($clone_times))
			{
				$clone_times = (int) $clone_times;
				$formModel->_formData['Copy'] = 1;
				for ($x = 1; $x < $clone_times; $x++)
				{
					$formModel->processToDB();
				}
				return true;
			}
		}
		JError::raiseNotice(JText::_('CLONEERR'), "Couldn't find a valid number of times to clone!");
		return true;
	}

}
