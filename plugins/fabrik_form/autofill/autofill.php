<?php
/**
 * Form Auto-Fill
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.autofill
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
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
 * @author      Rob Clayburn
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
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
	 * @return  void
	 */
	public function onAfterJSLoad()
	{
		$params        = $this->getParams();
		$formModel     = $this->getModel();
		$input         = $this->app->input;
		$rowId         = $input->get('rowid', '', 'string');
		$opts          = new stdClass;
		$opts->observe = str_replace('.', '___', $params->get('autofill_field_name'));
		$opts->trigger = str_replace('.', '___', $params->get('autofill_trigger'));
		$opts->formid  = $formModel->getId();
		$opts->map     = $params->get('autofill_map');
		$opts->cnn     = $params->get('autofill_cnn');
		$opts->table   = $params->get('autofill_table', '');

		$opts->editOrig              = $params->get('autofill_edit_orig', 0) == 0 ? false : true;
		$opts->confirm               = (bool) $params->get('autofill_confirm', true);
		$opts->autofill_lookup_field = $params->get('autofill_lookup_field');

		switch ($params->get('autofill_onload', '0'))
		{
			case '0':
			default:
				$opts->fillOnLoad = false;
				break;
			case '1':
				$opts->fillOnLoad = ($rowId === '');
				break;
			case '2':
				$opts->fillOnLoad = ($rowId !== '');
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
			$formModel->formPluginJS = array();
		}

		$this->formJavascriptClass($params, $formModel);
		$formModel->formPluginJS['Autofill' . $this->renderOrder] = 'var autofill = new Autofill(' . $opts . ');';
	}

	/**
	 * Called via ajax to get the first match record
	 *
	 * @return    string    json object of record data
	 */
	public function onajax_getAutoFill()
	{
		$input   = $this->app->input;
		$cnn     = (int) $input->getInt('cnn');
		$element = $input->get('observe');
		$value   = $input->get('v', '', 'string');
		$input->set('resetfilters', 1);
		$input->set('usekey', '');

		if ($cnn === 0 || $cnn == -1)
		{
			// No connection selected so query current forms' table data
			$formId = $input->getInt('formid');
			$input->set($element, $value, 'get');
			/** @var FabrikFEModelForm $model */
			$model = JModelLegacy::getInstance('form', 'FabrikFEModel');
			$model->setId($formId);
			$listModel = $model->getlistModel();
		}
		else
		{
			/** @var FabrikFEModelList $listModel */
			$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
			$listModel->setId($input->getInt('table'));
		}

		if ($value !== '')
		{
			// Don't get the row if its empty
			if ($input->get('autofill_lookup_field', '') !== '')
			{
				// Load on a fk
				$fkId   = $input->get('autofill_lookup_field', '');
				$db     = $listModel->getDb();
				$fk     = $listModel->getFormModel()->getElement($fkId, true);
				$elName = $fk->getElement()->name;
				$table  = $listModel->getTable();
				$query  = $db->getQuery(true);
				$query->select($table->db_primary_key)->from($table->db_table_name)->where($elName . ' = ' . $db->q($value));
				$db->setQuery($query);
				$value = $db->loadResult();
			}

			$data = $listModel->getRow($value, true, false);

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
				$newData = new stdClass;
				/*
				 * need __pk_val if 'edit original row'
				 */
				$newData->__pk_val = $data->__pk_val;

				foreach ($map as $from => $to)
				{
					if (is_array($to))
					{
						foreach ($to as $to2)
						{
							$this->fillField($data, $newData, $from, $to2);
						}
					}
					else
					{
						$this->fillField($data, $newData, $from, $to);
					}
				}
			}
			else
			{
				$newData = $data;
			}

			echo json_encode($newData);
		}
	}

	/**
	 * Fill the response with the lookup data
	 *
	 * @param   object $data     Lookup List - Row data
	 * @param   object &$newData Data to fill the form with
	 * @param   string $from     Key to search for in $data - may be either element full name, or placeholders
	 * @param   string $to       Form's field to insert data into
	 *
	 * @return  null
	 */
	protected function fillField($data, &$newData, $from, $to)
	{
		$matched = false;
		$toRaw   = $to . '_raw';
		$fromRaw = $from . '_raw';

		if (array_key_exists($from, $data))
		{
			$matched = true;
		}

		$newData->$to = isset($data->$from) ? $data->$from : '';

		if (array_key_exists($fromRaw, $data))
		{
			$matched = true;
		}

		if (!$matched)
		{
			$w               = new FabrikWorker;
			$newData->$toRaw = $newData->$to = $w->parseMessageForPlaceHolder($from, $data);
		}
		else
		{
			$newData->$toRaw = isset($data->$fromRaw) ? $data->$fromRaw : '';
		}
	}
}
