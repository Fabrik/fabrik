<?php
/**
 * Form Auto-Fill
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.autofill
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

 // No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * other records in the table to auto fill in the rest of the form with that records data
 *
 * Does not alter the record you search for but creates a new record
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @author Rob Clayburn
 * @copyright Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Allows you to observe an element, and when it its blurred asks if you want to lookup related data to fill
 * into additional fields
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.autofill
 * @since       3.0
 */

class PlgFabrik_FormAutofill extends PlgFabrik_Form
{

	/**
	 * Need to do this rather than on onLoad as otherwise in chrome form.js addevents is fired
	 * before autocomplete class ini'd so then the autocomplete class never sets itself up
	 *
	 * @param   object  &$params     Plugin params
	 * @param   object  &$formModel  Form model
	 *
	 * @return  void
	 */

	public function onAfterJSLoad(&$params, &$formModel)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$rowid = $input->get('rowid', '', 'string');
		$opts = new stdClass;
		$opts->observe = str_replace('.', '___', $params->get('autofill_field_name'));
		$opts->trigger = str_replace('.', '___', $params->get('autofill_trigger'));
		$opts->formid = $formModel->getId();
		$opts->map = $params->get('autofill_map');
		$opts->cnn = $params->get('autofill_cnn');
		$opts->table = $params->get('autofill_table', '');

		$opts->editOrig = $params->get('autofill_edit_orig', 0) == 0 ? false : true;
		$opts->confirm = (bool) $params->get('autofill_confirm', true);
		$opts->autofill_lookup_field = $params->get('autofill_lookup_field');
		switch ($params->get('autofill_onload', '0'))
		{
			case '0':
			default:
				$opts->fillOnLoad = false;
				break;
			case '1':
				$opts->fillOnLoad = ($rowid === '');
				break;
			case '2':
				$opts->fillOnLoad = ($rowid !== '');
				break;
			case '3':
				$opts->fillOnLoad = true;
				break;
		}
		$opts = json_encode($opts);
		JText::script('PLG_FORM_AUTOFILL_DO_UPDATE');
		JText::script('PLG_FORM_AUTOFILL_SEARCHING');
		JText::script('PLG_FORM_AUTOFILL_NORECORDS_FOUND');
		if (!isset($formModel->formPluginJS))
		{
			$formModel->formPluginJS = '';
		}
		$this->formJavascriptClass($params, $formModel);
		$formModel->formPluginJS .= "\n" . 'var autofill = new Autofill(' . $opts . ');';
	}

	/**
	 * Called via ajax to get the first match record
	 *
	 * @return	string	json object of record data
	 */

	public function onajax_getAutoFill()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = JModelLegacy::getInstance('form', 'FabrikFEModel');
		$cnn = (int) $input->getInt('cnn');
		$element = $input->get('observe');
		$value = $input->get('v', '', 'string');
		$input->set('resetfilters', 1);
		$input->set('usekey', '');
		if ($cnn === 0 || $cnn == -1)
		{
			// No connection selected so query current forms' table data
			$formid = $input->getInt('formid');
			$input->set($element, $value, 'get');
			$model = JModelLegacy::getInstance('form', 'FabrikFEModel');
			$model->setId($formid);
			$listModel = $model->getlistModel();
		}
		else
		{
			$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
			$listModel->setId($input->getInt('table'));
		}
		if ($value !== '')
		{
			// Don't get the row if its empty
			if ($input->get('autofill_lookup_field', '') !== '')
			{
				// Load on a fk
				$fkid = $input->get('autofill_lookup_field', '');
				$db = $listModel->getDb();
				$fk = $listModel->getFormModel()->getElement($fkid, true);
				$elname = $fk->getElement()->name;
				$table = $listModel->getTable();
				$query = $db->getQuery(true);
				$query->select($table->db_primary_key)->from($table->db_table_name)->where($elname . ' = ' . $db->quote($value));
				$db->setQuery($query);
				$value = $db->loadResult();
			}
			$data = $listModel->getRow($value, true, true);
			if (is_array($data))
			{
				$data = array_shift($data);
			}
		}
		if (empty($data))
		{
			echo "{}";
		}
		else
		{
			$map = stripslashes($input->get('map', '', 'string'));
			$map = json_decode($map);
			if (!empty($map))
			{
				$newdata = new stdClass;
				foreach ($map as $from => $to)
				{
					$toraw = $to . '_raw';
					$fromraw = $from . '_raw';
					if (is_array($to))
					{
						foreach ($to as $to2)
						{
							$to2_raw = $to2 . '_raw';
							if (!array_key_exists($from, $data))
							{
								throw new RuntimeException('autofill map json not correctly set', 500);
							}
							$newdata->$to2 = isset($data->$from) ? $data->$from : '';
							if (!array_key_exists($fromraw, $data))
							{
								throw new RuntimeException('autofill toraw map json not correctly set?', 500);
							}
							$newdata->$to2_raw = isset($data->$fromraw) ? $data->$fromraw : '';
						}
					}
					else
					{
						// $$$ hugh - key may exist, but be null
						if (!array_key_exists($from, $data))
						{
							throw new RuntimeException('Couln\'t find from value in record data, is the element published?', 500);
						}
						$newdata->$to = isset($data->$from) ? $data->$from : '';
						if (!array_key_exists($fromraw, $data))
						{
							throw new RuntimeException('autofill toraw map json not correctly set?', 500);
						}
						$newdata->$toraw = isset($data->$fromraw) ? $data->$fromraw : '';
					}
				}
			}
			else
			{
				$newdata = $data;
			}
			echo json_encode($newdata);
		}
	}

}
