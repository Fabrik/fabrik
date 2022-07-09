<?php
/**
 * Form Clone
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.clone
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

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
	 * @return	bool
	 */
	public function onAfterProcess()
	{
		return $this->_process();
	}

	/**
	 * Clone the record
	 *
	 * @return  bool
	 */
	private function _process()
	{
		$params = $this->getParams();

		/** @var FabrikFEModelForm $formModel */
		$formModel = $this->getModel();
		$clone_times_field_id = $params->get('clone_times_field', '');
		$clone_batchid_field_id = $params->get('clone_batchid_field', '');

		if ($clone_times_field_id != '')
		{
			$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($clone_times_field_id);
			$element = $elementModel->getElement(true);

			if ($clone_batchid_field_id != '')
			{
				$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($clone_batchid_field_id);
				$id_element = $elementModel->getElement(true);
				$formModel->formData[$id_element->name] = $formModel->fullFormData['rowid'];
				$formModel->formData[$id_element->name . '_raw'] = $formModel->fullFormData['rowid'];
				$listModel = $formModel->getlistModel();
				$listModel->setFormModel($formModel);
				$primaryKey = FabrikString::shortColName($listModel->getPrimaryKey());
				$formModel->formData[$primaryKey] = $formModel->fullFormData['rowid'];
				$formModel->formData[$primaryKey . '_raw'] = $formModel->fullFormData['rowid'];
				$listModel->storeRow($formModel->formData, $formModel->fullFormData['rowid']);
			}

			$clone_times = $formModel->formData[$element->name];

			if (is_numeric($clone_times))
			{
				$clone_times = (int) $clone_times;
				$formModel->formData['Copy'] = 1;

				for ($x = 1; $x < $clone_times; $x++)
				{
					$formModel->processToDB();
				}

				return true;
			}
		}

		throw new RuntimeException("Couldn't find a valid number of times to clone!");
	}
}
