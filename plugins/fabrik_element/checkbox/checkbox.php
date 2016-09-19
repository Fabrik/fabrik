<?php
/**
 * Plugin element to render series of checkboxes
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.checkbox
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Plugin element to render series of checkboxes
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.checkbox
 * @since       3.0
 */
class PlgFabrik_ElementCheckbox extends PlgFabrik_ElementList
{
	protected $inputType = 'checkbox';

	/**
	 * Set the element id
	 * and maps parameter names for common ElementList options
	 *
	 * @param   int $id element id
	 *
	 * @return  void
	 */
	public function setId($id)
	{
		parent::setId($id);
		$params = $this->getParams();

		// Set elementlist params from checkbox params
		$params->set('options_per_row', $params->get('ck_options_per_row'));
		$params->set('allow_frontend_addto', (bool) $params->get('allow_frontend_addtocheckbox', false));
		$params->set('allowadd-onlylabel', (bool) $params->get('chk-allowadd-onlylabel', true));
		$params->set('savenewadditions', (bool) $params->get('chk-savenewadditions', false));
	}

	/**
	 * Shows the RAW list data - can be overwritten in plugin class
	 *
	 * @param   string $data    element data
	 * @param   object $thisRow all the data in the tables current row
	 *
	 * @return  string    formatted value
	 */
	public function renderRawListData($data, $thisRow)
	{
		return json_encode($data);
	}

	/**
	 * Will the element allow for multiple selections
	 *
	 * @since    3.0.6
	 *
	 * @return  bool
	 */
	protected function isMultiple()
	{
		return true;
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int $repeatCounter Repeat group counter
	 *
	 * @return  array
	 */
	public function elementJavascript($repeatCounter)
	{
		$params           = $this->getParams();
		$id               = $this->getHTMLId($repeatCounter);
		$values           = (array) $this->getSubOptionValues();
		$labels           = (array) $this->getSubOptionLabels();
		$data             = $this->getFormModel()->data;
		$opts             = $this->getElementJSOptions($repeatCounter);
		$opts->value      = $this->getValue($data, $repeatCounter);
		$opts->defaultVal = $this->getDefaultValue($data);
		$opts->data       = (empty($values) && empty($labels)) ? array() : array_combine($values, $labels);
		$opts->allowadd   = (bool) $params->get('allow_frontend_addtocheckbox', false);
		JText::script('PLG_ELEMENT_CHECKBOX_ENTER_VALUE_LABEL');

		return array('FbCheckBox', $id, $opts);
	}

	/**
	 * If your element risks not to post anything in the form (e.g. check boxes with none checked)
	 * the this function will insert a default value into the database
	 *
	 * @param   array &$data form data
	 *
	 * @return  array  form data
	 */
	public function getEmptyDataValue(&$data)
	{
		$params  = $this->getParams();
		$element = $this->getElement();

		$value = FArrayHelper::getValue($data, $element->name, '');

		if ($value === '')
		{
			$data[$element->name]          = $params->get('sub_default_value');
			$data[$element->name . '_raw'] = array($params->get('sub_default_value'));
		}
	}

	/**
	 * If the search value isn't what is stored in the database, but rather what the user
	 * sees then switch from the search string to the db value here
	 * overwritten in things like checkbox and radio plugins
	 *
	 * @param   string $value filterVal
	 *
	 * @return  string
	 */
	protected function prepareFilterVal($value)
	{
		$values = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();

		for ($i = 0; $i < count($labels); $i++)
		{
			if (JString::strtolower($labels[$i]) == JString::strtolower($value))
			{
				return $values[$i];
			}
		}

		return $value;
	}

	/**
	 * If no filter condition supplied (either via querystring or in posted filter data
	 * return the most appropriate filter option for the element.
	 *
	 * @return  string    default filter condition ('=', 'REGEXP' etc.)
	 */
	public function getDefaultFilterCondition()
	{
		return '=';
	}

	/**
	 * Manipulates posted form data for insertion into database
	 *
	 * @param   mixed $val  this elements posted form data
	 * @param   array $data posted form data
	 *
	 * @return  mixed
	 */
	public function storeDatabaseFormat($val, $data)
	{
		if (is_array($val))
		{
			// Ensure that array is incremental numeric key -otherwise json_encode turns it into an object
			$val = array_values($val);
		}

		if (is_array($val) || is_object($val))
		{
			return json_encode($val);
		}
		else
		{
			/*
			 * $$$ hugh - nastyish hack to try and make sure we consistently save as JSON,
			 * for instance in CSV import, if data is just a single option value like 2,
			 * instead of ["2"], we have been saving it as just that value, rather than a single
			 * item JSON array.
			 */
			if (isset($val))
			{
				// We know it's not an array or an object, so lets see if it's a string
				// which doesn't contain ", [ or ]
				if (!preg_match('#["\[\]]#', $val))
				{
					// No ", [ or ], so lets see if wrapping it up in JSON array format
					// produces valid JSON
					$json_val = '["' . $val . '"]';

					if (FabrikWorker::isJSON($json_val))
					{
						// Looks like we we have a valid JSON array, so return that
						return $json_val;
					}
					else
					{
						// Give up and just store whatever it was we got!
						return $val;
					}
				}
				else
				{
					// Contains ", [ or ], so wtf, hope it's json
					return $val;
				}
			}
			else
			{
				return '';
			}
		}
	}
}
