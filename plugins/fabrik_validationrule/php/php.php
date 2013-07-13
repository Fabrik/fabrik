<?php
/**
 * PHP Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.php
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/validation_rule.php';

/**
 * PHP Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.php
 * @since       3.0
 */

class PlgFabrik_ValidationrulePhp extends PlgFabrik_Validationrule
{

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'php';

	/**
	 * Validate the elements data against the rule
	 *
	 * @param   string  $data           To check
	 * @param   object  &$elementModel  Element Model
	 * @param   int     $pluginc        Plugin sequence ref
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */

	public function validate($data, &$elementModel, $pluginc, $repeatCounter)
	{
		// For multiselect elements
		if (is_array($data))
		{
			$data = implode('', $data);
		}
		$params = $this->getParams();
		$domatch = $params->get('php-match');
		$domatch = $domatch[$pluginc];
		if ($domatch)
		{
			$formModel = $elementModel->getFormModel();
			$formData = $formModel->formData;
			$w = new FabrikWorker;
			$phpCode = $params->get('php-code');
			$php_code = $w->parseMessageForPlaceHolder($phpCode[$pluginc], $formData, true, true);
			$retval = @eval($php_code);
			FabrikWorker::logEval($retval, 'Caught exception on php validation of ' . $elementModel->getFullName(false,false) . '::_getV(): %s');			$retval = eval($php_code[$pluginc]);
			return $retval;
		}
		return true;
	}

	/**
	 * Checks if the validation should replace the submitted element data
	 * if so then the replaced data is returned otherwise original data returned
	 *
	 * @param   string  $data           Original data
	 * @param   model   &$elementModel  Element model
	 * @param   int     $pluginc        Validation plugin counter
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  string	original or replaced data
	 */

	public function replace($data, &$elementModel, $pluginc, $repeatCounter)
	{
		$params = $this->getParams();
		$domatch = $params->get('php-match');
		$domatch = $domatch[$pluginc];
		if (!$domatch)
		{
			$formModel = $elementModel->getFormModel();
			$formData = $formModel->formData;
			$w = new FabrikWorker;
			$phpCode = $params->get('php-code');
			$php_code = $w->parseMessageForPlaceHolder($phpCode[$pluginc], $formData, true, true);
			$retval = @eval($php_code);
			FabrikWorker::logEval($retval, 'Caught exception on php validation of ' . $elementModel->getFullName(false,false) . '::_getV(): %s');			$retval = eval($php_code[$pluginc]);
			return $retval;
		}
		return $data;
	}
}
