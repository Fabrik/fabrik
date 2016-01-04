<?php
/**
 * Plugin element to store the user's IP address
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.ip
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Plugin element to store the user's IP address
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.ip
 * @since       3.0
 */
class PlgFabrik_ElementIp extends PlgFabrik_Element
{
	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();

		$rowId = $this->app->input->get('rowid', false);
		/**
		 * @TODO when editing a form with joined repeat group the rowid will be set but
		 * the record is in fact new
		 */

		if ($params->get('ip_update_on_edit') || !$rowId || ($this->inRepeatGroup && $this->_inJoin && $this->_repeatGroupTotal == $repeatCounter))
		{
			$ip = FabrikString::filteredIp();
		}
		else
		{
			if (empty($data) || empty($data[$name]))
			{
				// If $data is empty, we must (?) be a new row, so just grab the IP
				$ip = FabrikString::filteredIp();
			}
			else
			{
				$ip = $this->getValue($data, $repeatCounter);
			}
		}

		$layoutData = new stdClass;
		$layoutData->id = $id;
		$layoutData->name = $name;
		$layoutData->value = $ip;

		if ($this->canView())
		{
			if (!$this->isEditable())
			{
				return $ip;
			}
			else
			{
				$layoutData->type = 'text';
			}
		}
		else
		{
			// Make a hidden field instead
			$layoutData->type = 'hidden';
		}

		$layout = $this->getLayout('form');

		return $layout->render($layoutData);
	}

	/**
	 * Trigger called when a row is stored.
	 * If we are creating a new record, and the element was set to readonly
	 * then insert the users data into the record to be stored
	 *
	 * @param   array  &$data          Data to store
	 * @param   int    $repeatCounter  Repeat group index
	 *
	 * @return  bool  If false, data should not be added.
	 */
	public function onStoreRow(&$data, $repeatCounter = 0)
	{
		if (!parent::onStoreRow($data, $repeatCounter))
		{
			return false;
		}

		$element = $this->getElement();
		$formModel = $this->getFormModel();
		$formData = $formModel->formData;

		if (FArrayHelper::getValue($formData, 'rowid', 0) == 0 && !in_array($element->name, $data))
		{
			$data[$element->name] = $_SERVER['REMOTE_ADDR'];
		}
		else
		{
			$params = $this->getParams();

			if ($params->get('ip_update_on_edit', 0))
			{
				$data[$element->name] = FabrikString::filteredIp();
				$data[$element->name . '_raw'] = FabrikString::filteredIp();
			}
		}

		return true;
	}

	/**
	 * This really does get just the default value (as defined in the element's settings)
	 *
	 * @param   array  $data  form data
	 *
	 * @return mixed
	 */
	public function getDefaultValue($data = array())
	{
		if (!isset($this->default))
		{
			$this->default = FabrikString::filteredIp();
		}

		return $this->default;
	}

	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  when repeating joined groups we need to know what part of the array to access
	 * @param   array  $opts           options
	 *
	 * @return  string	value
	 */
	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		// Kludge for 2 scenarios
		if (array_key_exists('rowid', $data))
		{
			// When validating the data on form submission
			$key = 'rowid';
		}
		else
		{
			// When rendering the element to the form
			$key = '__pk_val';
		}

		if (empty($data) || !array_key_exists($key, $data) || (array_key_exists($key, $data) && empty($data[$key])))
		{
			$value = $this->getDefaultOnACL($data, $opts);

			return $value;
		}

		$res = parent::getValue($data, $repeatCounter, $opts);

		return $res;
	}
}
