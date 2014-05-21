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
	 * @param   string  $data           To check
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */

	public function validate($data, $repeatCounter)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$elementModel = $this->elementModel;

		// Could be a dropdown with multivalues
		if (is_array($data))
		{
			$data = implode('', $data);
		}

		$params = $this->getParams();
		$otherfield = $params->get('areuniquevalues-otherfield', '');
		$element = $elementModel->getElement();
		$listModel = $elementModel->getlistModel();
		$table = $listModel->getTable();

		if ((int) $otherfield !== 0)
		{
			$otherElementModel = $this->getOtherElement();
			$otherFullName = $otherElementModel->getFullName(true, false);
			$otherfield = $otherElementModel->getFullName(false, false);
		}
		else
		{
			// Old fabrik 2.x params stored element name as a string
			$otherFullName = $table->db_table_name . '___' . $otherfield;
		}

		$db = $listModel->getDb();
		$lookuptable = $db->quoteName($table->db_table_name);
		$data = $db->quote($data);
		$query = $db->getQuery(true);
		$query->select('COUNT(*)')->from($lookuptable)->where($db->quoteName($elementModel->getFullName(false, false)) . ' = ' . $data);
		$listModel->buildQueryJoin($query);

		if (!empty($otherfield))
		{
			// $$$ the array thing needs fixing, for now just grab 0
			$formdata = $elementModel->getForm()->formData;
			$v = JArrayHelper::getValue($formdata, $otherFullName . '_raw', JArrayHelper::getValue($formdata, $otherFullName, ''));

			if (is_array($v))
			{
				$v = JArrayHelper::getValue($v, 0, '');
			}

			$query->where($db->quoteName($otherfield) . ' = ' . $db->quote($v));
		}

		/* $$$ hugh - need to check to see if we're editing a record, otherwise
		 * will fail 'cos it finds the original record (assuming this element hasn't changed)
		 * @TODO - is there a better way getting the rowid?  What if this is form a joined table?
		 */
		$rowid = $input->get('rowid');

		if (!empty($rowid))
		{
			$query->where($table->db_primary_key . ' != ' . $db->quote($rowid));
		}

		$db->setQuery($query);
		$c = $db->loadResult();

		return ($c == 0) ? true : false;
	}

	/**
	 * Gets the other element model to compare this plugins element data against
	 *
	 * @return	object element model
	 */

	private function getOtherElement()
	{
		$params = $this->getParams();
		$otherfield = $params->get('areuniquevalues-otherfield');

		return FabrikWorker::getPluginManager()->getElementPlugin($otherfield);
	}

	/**
	 * Gets the hover/alt text that appears over the validation rule icon in the form
	 *
	 * @return	string	label
	 */

	protected function getLabel()
	{
		$otherElementModel = $this->getOtherElement();
		$params = $this->getParams();
		$otherfield = $params->get('areuniquevalues-otherfield');

		if ((int) $otherfield !== 0)
		{
			return JText::sprintf('PLG_VALIDATIONRULE_AREUNIQUEVALUES_ADDITIONAL_LABEL', $otherElementModel->getElement()->label);
		}
		else
		{
			return parent::getLabel();
		}
	}
}
