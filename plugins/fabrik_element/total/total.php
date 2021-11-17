<?php
/**
 * Plugin element to store the user's IP address
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.ip
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

/**
 * Plugin element to store the user's IP address
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.ip
 * @since       3.0
 */
class PlgFabrik_ElementTotal extends PlgFabrik_Element
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
		$value = $this->getValue($data, $repeatCounter);

		$layoutData = new stdClass;
		$layoutData->id = $id;
		$layoutData->name = $name;
		$layoutData->value = $value;

		if ($this->canView())
		{
			if (!$this->isEditable())
			{
				return $value;
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
		$element = $this->getElement();

		if (!$element->published)
		{
			return false;
		}

		if ($this->encryptMe())
		{
			$shortName = $element->name;
			$listModel = $this->getListModel();
			$listModel->encrypt[] = $shortName;
		}

		$formModel = $this->getFormModel();
		$data[$element->name] = $this->getTotal($formModel->formDataWithTableName, $repeatCounter);

		return true;
	}

	private function getTotal($data = array(), $repeatCounter = 0)
	{
		$params = $this->getParams();
		$method = $params->get('total_method', 'load');
		$fixed = (int)$params->get('total_fixed', '2');
		$watchNames = $this->getWatchNames();
		$watchOperands = $this->getWatchOperands();
		$total  = (float)$params->get('total_start_value', '0');

		switch ($method)
		{
			case 'sum_repeat':
				foreach ($watchNames as $key => $watchName)
				{
					$operand = ArrayHelper::getValue($watchOperands, $key, 'add');
					$values  = (array) ArrayHelper::getValue($data, $watchName . '_raw', array());

					foreach ($values as $value)
					{
						if (is_numeric($value))
						{
							$value = (float) $value;

							switch ($operand)
							{
								case 'add':
									$total += $value;
									break;
								case 'subtract':
									$total -= $value;
									break;
								case 'multiply':
									$total *= $value;
									break;
								case 'divide':
									if (!empty($value))
									{
										$total = $total / $value;
									}
									break;
							}
						}
					}
				}
				break;

			case 'sum_multiple':
				foreach ($watchNames as $key => $watchName)
				{
					$operand = ArrayHelper::getValue($watchOperands, $key, 'add');
					$value   = ArrayHelper::getValue($data, $watchName . '_raw');

					if ($this->getGroup()->canRepeat())
					{
						$value = ArrayHelper::getValue($value, $repeatCounter);
					}

					if (is_numeric($value))
					{
						$value = (float) $value;

						switch ($operand)
						{
							case 'add':
								$total += $value;
								break;
							case 'subtract':
								$total -= $value;
								break;
							case 'multiply':
								$total *= $value;
								break;
							case 'divide':
								if (!empty($value))
								{
									$total = $total / $value;
								}
								break;
						}
					}
				}
				break;
		}

		$total = round($total, $fixed);

		return $total;
	}


	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */
	public function elementJavascript($repeatCounter)
	{
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->observe = $this->getWatchNames();
		$opts->operands = $this->getWatchOperands();
		$opts->fixed = $params->get('total_fixed', '2');
		$opts->method = $params->get('total_method', 'sum_repeat');
		$opts->startValue = $params->get('total_start_value', '0');
		$opts->totalOnLoad = (bool) $params->get('total_on_load', true);

		return array('FbTotal', $id, $opts);
	}

	/**
	 * Get full element names of watched elements
	 *
	 * @return  array
	 */
	protected function getWatchNames()
	{
		$params = $this->getParams();
		$mode = $params->get('total_method', 'sum_repeat');
		$observe = array();

		switch ($mode)
		{
			case 'sum_repeat':
				//$observe[] = $params->get('total_repeat_element', '');
				//break;
			case 'sum_multiple':
				$multiples = $params->get('total_multiple_elements');
				$multiples = is_string($multiples) ? json_decode($multiples) : $multiples;
				$observe = $multiples->total_multiple_element;
				break;
		}

		return $observe;
	}

	private function getWatchOperands()
	{
		$params = $this->getParams();
		$mode = $params->get('total_method', 'sum_repeat');
		$operands = array();

		switch ($mode)
		{
			case 'sum_repeat':
				$operands[] = 'add';
				break;
			case 'sum_multiple':
				$multiples = $params->get('total_multiple_elements');
				$multiples = is_string($multiples) ? json_decode($multiples) : $multiples;
				$operands = $multiples->total_multiple_operand;
				break;
		}

		return $operands;
	}
}
