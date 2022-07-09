<?php
/**
 * Is Unique Value Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isuniquevalue
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

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
		$params = $this->getParams();
		$input        = $this->app->input;
		$elementModel = $this->elementModel;

		// Could be a drop-down with multi-values
		if (is_array($data))
		{
			$data = implode('', $data);
		}

		if ($params->get('isuniquevalue-allow_empty', '0') === '1' && $data === '')
		{
			return true;
		}

		$element     = $elementModel->getElement();
		$listModel   = $elementModel->getlistModel();
		$table       = $listModel->getTable();
		$db          = $listModel->getDb();
		$query       = $db->getQuery(true);
		$cond        = $params->get('isuniquevalue-caseinsensitive') == 1 ? 'LIKE' : '=';
		$secret      = $this->config->get('secret');

		$groupModel = $elementModel->getGroup();

		// if it's a join, get the joined table name
		if ($groupModel->isJoin())
		{
			if ($groupModel->canRepeat() && $params->get('isuniquevalue-within-group', '0') === '1')
			{
				/*
				 * If "within group" option selected, we don't care about the state of the database, we only care
				 * whether the submitted data for this repeat group has a dupe
				 */
				return $this->checkThisGroup($data, $repeatCounter);
			}

			$lookupTable = $groupModel->getJoinModel()->getJoin()->table_join;
		}
		else
		{
			$lookupTable = $table->db_table_name;
		}

		if ($elementModel->encryptMe())
		{
			$k = 'AES_DECRYPT(' . $element->name . ', ' . $db->q($secret) . ')';
		}
		else
		{
			$k = $db->qn($element->name);
		}

		$query->select('COUNT(*)')->from($db->qn($lookupTable))->where($k . ' ' . $cond . ' ' . $db->q($data));

		/*
		 * Check to see if we're editing a record, so we can exclude this record, and handle joined / repeated
		 * data accordingly.
		 */

		if (!$groupModel->isJoin())
		{
			// not a join, just use rowid and normal pk

			// if copying row, empty row id
			if ($elementModel->getFormModel()->copyingRow())
			{
				$rowId = '';
			}
			else
			{
				$rowId = $input->get('rowid', '');
			}

			$pk = $table->db_primary_key;
		}
		else
		{
			// join, so get the join's PK and value as rowid
			$joinModel = $groupModel->getJoinModel();
			$pk = $joinModel->getForeignID();

			if ($groupModel->canRepeat())
			{
				/**
				 * Check if there's a dupe in this repeat, which might not show in a database lookup, if they've
				 * added repeats, or changed existing repeats.  if so, don't even bother doing the lookup, just bail.
				 */
				if (!$this->checkThisGroup($data, $repeatCounter))
				{
					return false;
				}

				$rowids = Arrayhelper::getValue($this->formModel->formData, $pk, array());
				$rowId = ArrayHelper::getValue($rowids, $repeatCounter, '');
			}
			else
			{
				$rowId = Arrayhelper::getValue($this->formModel->formData, $pk, '');
			}

			$pk = $joinModel->getForeignID('.');
		}

		if (!empty($rowId))
		{
			$query->where(FabrikString::safeQuoteName($pk) . ' != ' . $db->q($rowId));
		}

		$db->setQuery($query);
		$c = $db->loadResult();

		return ($c === '0') ? true : false;
	}

	/**
	 * Check for duplicate values with the submitted data for this repeat group
	 *
	 * @param   string $data          To check
	 * @param   int    $repeatCounter Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */
	private function checkThisGroup($data, $repeatCounter)
	{
		$params = $this->getParams();
		$elementModel = $this->elementModel;
		$groupModel = $elementModel->getGroup();

		if ($groupModel->canRepeat())
		{
			$fullName = $elementModel->getFullName(true, false);
			$elementData = ArrayHelper::getValue($this->formModel->formData, $fullName, array());

			foreach ($elementData as $k => $v)
			{
				if ($k === (int)$repeatCounter)
				{
					continue;
				}

				if (is_array($v))
				{
					$v = implode('', $v);
				}

				if ($v === $data)
				{
					return false;
				}
			}
		}

		return true;
	}
}
