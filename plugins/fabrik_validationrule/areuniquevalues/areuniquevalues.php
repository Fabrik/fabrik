<?php
/**
 * Are Unique values Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.areuniquevalues
 * @copyright   Copyright (C) 2005-2013 fabrikar.com & Lieven Gryp - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/validation_rule.php';

/**
 * Are Unique values Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.areuniquevalues
 * @since       3.0
 */
class PlgFabrik_ValidationruleAreUniqueValues extends PlgFabrik_Validationrule
{
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'areuniquevalues';

	/**
	 * Validate the elements data against the rule
	 *
	 * @param   string $data          To check
	 * @param   int    $repeatCounter Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */
	public function validate($data, $repeatCounter)
	{
		$input        = $this->app->input;
		$elementModel = $this->elementModel;

		// Could be a dropdown with multivalues
		if (is_array($data))
		{
			$data = implode('', $data);
		}

		$params     = $this->getParams();
		$listModel  = $elementModel->getlistModel();
		$table      = $listModel->getTable();

		$otherField = $params->get('areuniquevalues-otherfield', '');

		if ((int) $otherField !== 0)
		{
			$otherElementModel = $this->getOtherElement();
			$otherFullName     = $otherElementModel->getFullName(true, false);
			$otherField        = $otherElementModel->getFullName(false, false);
		}
		else
		{
			// Old fabrik 2.x params stored element name as a string
			$otherFullName = $table->db_table_name . '___' . $otherField;
		}

		$otherField2 = $params->get('areuniquevalues-otherfield2', '');

		if ((int) $otherField2 !== 0)
		{
			$otherElementModel2 = $this->getOtherElement('2');
			$otherFullName2     = $otherElementModel2->getFullName(true, false);
			$otherField2        = $otherElementModel2->getFullName(false, false);
		}
		else
		{
			// Old fabrik 2.x params stored element name as a string
			$otherFullName2 = $table->db_table_name . '___' . $otherField;
		}

		$db          = $listModel->getDb();
		$lookupTable = $db->qn($table->db_table_name);
		$data        = $db->q($data);
		$query       = $db->getQuery(true);
		$query->select('COUNT(*)')->from($lookupTable)->where($db->qn($elementModel->getFullName(false, false)) . ' = ' . $data);
		$listModel->buildQueryJoin($query);

		if (!empty($otherField))
		{
			// $$$ the array thing needs fixing, for now just grab 0
			$formData = $elementModel->getForm()->formData;
			$v        = FArrayHelper::getValue($formData, $otherFullName . '_raw', FArrayHelper::getValue($formData, $otherFullName, ''));

			if (is_array($v))
			{
				$v = FArrayHelper::getValue($v, 0, '');
			}

			$query->where($db->qn($otherField) . ' = ' . $db->quote($v));
		}

		if (!empty($otherField2))
		{
			// $$$ the array thing needs fixing, for now just grab 0
			$formData = $elementModel->getForm()->formData;
			$v        = FArrayHelper::getValue($formData, $otherFullName2 . '_raw', FArrayHelper::getValue($formData, $otherFullName2, ''));

			if (is_array($v))
			{
				$v = FArrayHelper::getValue($v, 0, '');
			}

			$query->where($db->qn($otherField2) . ' = ' . $db->quote($v));
		}

		/* $$$ hugh - need to check to see if we're editing a record, otherwise
		 * will fail 'cos it finds the original record (assuming this element hasn't changed)
		 * @TODO - is there a better way getting the rowid?  What if this is form a joined table?
		 */
		$rowId = $input->get('rowid');

		if (!empty($rowId))
		{
			$query->where($table->db_primary_key . ' != ' . $db->q($rowId));
		}

		$db->setQuery($query);
		$c = $db->loadResult();

		return ($c == 0) ? true : false;
	}

	/**
	 * Gets the other element model to compare this plugins element data against
	 *
	 * @return    object element model
	 */
	private function getOtherElement($suffix = '')
	{
		$params     = $this->getParams();
		$otherField = $params->get('areuniquevalues-otherfield' . $suffix);

		return FabrikWorker::getPluginManager()->getElementPlugin($otherField);
	}

	/**
	 * Gets the hover/alt text that appears over the validation rule icon in the form
	 *
	 * @return    string    label
	 */
	protected function getLabel()
	{
		$params            = $this->getParams();
		$otherField        = $params->get('areuniquevalues-otherfield');
		$otherField2        = $params->get('areuniquevalues-otherfield2');
		$tipText           = $params->get('tip_text', '');

		if (empty($tipText) && ((int) $otherField !== 0 || (int) $otherField2 !== 0))
		{
			$labels = array();

			if ((int)$otherField !== 0)
			{
				$otherElementModel = $this->getOtherElement();
				$labels[] = $otherElementModel->getElement()->label;
			}

			if ((int)$otherField2 !== 0)
			{
				$otherElementModel = $this->getOtherElement('2');
				$labels[] = $otherElementModel->getElement()->label;
			}

			$labels = implode(' ' . strtolower(JText::_('COM_FABRIK_AND')) . ' ', $labels);
			return JText::sprintf('PLG_VALIDATIONRULE_AREUNIQUEVALUES_ADDITIONAL_LABEL', $labels);
		}
		else
		{
			return parent::getLabel();
		}
	}
}
