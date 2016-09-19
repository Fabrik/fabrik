<?php
/**
 * Is Unique Value Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isuniquevalue
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/validation_rule.php';

/**
 * Is Unique Value Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isuniquevalue
 * @since       3.0
 */
class PlgFabrik_ValidationruleIsUniqueValue extends PlgFabrik_Validationrule
{
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'isuniquevalue';

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

		// Could be a drop-down with multi-values
		if (is_array($data))
		{
			$data = implode('', $data);
		}

		$params      = $this->getParams();
		$element     = $elementModel->getElement();
		$listModel   = $elementModel->getlistModel();
		$table       = $listModel->getTable();
		$db          = $listModel->getDb();
		$lookupTable = $db->qn($table->db_table_name);
		$data        = $db->q($data);
		$query       = $db->getQuery(true);
		$cond        = $params->get('isuniquevalue-caseinsensitive') == 1 ? 'LIKE' : '=';
		$secret      = $this->config->get('secret');

		if ($elementModel->encryptMe())
		{
			$k = 'AES_DECRYPT(' . $element->name . ', ' . $db->q($secret) . ')';
		}
		else
		{
			$k = $db->qn($element->name);
		}

		$query->select('COUNT(*)')->from($lookupTable)->where($k . ' ' . $cond . ' ' . $data);

		/* $$$ hugh - need to check to see if we're editing a record, otherwise
		 * will fail 'cos it finds the original record (assuming this element hasn't changed)
		 * @TODO - is there a better way getting the rowid?  What if this is form a joined table?
		 * $rowId = $input->get('rowid');
		 * Have to do it by grabbing PK from request, 'cos rowid isn't set on AJAX validation
		 *
		 * Paul - if pk is an input field, then input pk may not be original so should use rowid
		 * to match the record in the DB that matches THIS record, rather than the user changed pk.
		 * Hugh rightly points out that this does not handle joined tables correctly, but this is
		 * true if we use:
		 * $rowId = $input->get('rowid','');    or
		 * $rowId = $input->get($pk,'');
			$pk = FabrikString::safeColNameToArrayKey($table->db_primary_key);
		 */
		$rowId = $input->get('rowid', '');

		if (!empty($rowId))
		{
			$query->where($table->db_primary_key . ' != ' . $db->q($rowId));
		}

		$db->setQuery($query);
		$c = $db->loadResult();

		return ($c === '0') ? true : false;
	}
}
