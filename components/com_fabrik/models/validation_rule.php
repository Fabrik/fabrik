<?php
/**
 * Fabrik Validation Rule Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/plugin.php';

/**
 * Fabrik Validation Rule Model
 *
 * @package  Fabrik
 * @since    3.0
 */

class PlgFabrik_Validationrule extends FabrikPlugin
{

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = null;

	/**
	 * Validation rule's element model
	 *
	 * @var JModel
	 */
	public $elementModel = null;

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
		return true;
	}

	/**
	 * Checks if the validation should replace the submitted element data
	 * if so then the replaced data is returned otherwise original data returned
	 *
	 * @param   string  $data           Original data
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  string	original or replaced data
	 */

	public function replace($data, $repeatCounter)
	{
		return $data;
	}

	/**
	 * Looks at the validation condition & evaulates it
	 * if evaulation is true then the validation rule is applied
	 *
	 * @param   string  $data  Elements data
	 *
	 * @return  bool	apply validation
	 */

	public function shouldValidate($data)
	{
		$params = $this->getParams();
		$condition = $params->get($this->pluginName . '-validation_condition');
		if ($condition == '')
		{
			return true;
		}
		$w = new FabrikWorker;
		$condition = trim($w->parseMessageForPlaceHolder($condition));
		$formModel = $this->elementModel->getFormModel();
		$res = @eval($condition);
		if (is_null($res))
		{
			return true;
		}
		return $res;
	}

	/**
	 * Get the warning message
	 *
	 * @return  string
	 */

	public function getMessage()
	{
		$params = $this->getParams();
		$v = $params->get($this->pluginName . '-message');
		if ($v === '')
		{
			$v = 'COM_FABRIK_FAILED_VALIDATION';
		}
		return JText::_($v);
	}

	/**
	 * Now show only on validation icon next to the element name and put icons and text inside hover text
	 * gets the validation rule icon
	 *
	 * @param   int     $c             Repeat group counter
	 * @param   string  $tmpl          Template folder name
	 *
	 * @deprecated @since 3.0.5
	 *
	 * @return  string
	 */

	public function getIcon($c = 0, $tmpl = '')
	{
		$name = $this->elementModel->validator->getIcon();
		$i = FabrikHelperHTML::image($name, 'form', $tmpl, array('class' => $this->pluginName));
	}

	/**
	 * Get the base icon image as defined by the J Plugin options
	 *
	 * @since   3.1b2
	 *
	 * @return  string
	 */

	public function iconImage()
	{
		$plugin = JPluginHelper::getPlugin('fabrik_validationrule', $this->pluginName);
		$params = new JRegistry($plugin->params);
		return $params->get('icon', 'star');
	}

	/**
	 * Get hover text with icon
	 *
	 * @param   string  $tmpl          Template folder name
	 *
	 * @return  string
	 */

	public function getHoverText($tmpl = '')
	{
		$name = $this->elementModel->validator->getIcon();
		$i = FabrikHelperHTML::image($name, 'form', $tmpl, array('class' => $this->pluginName));
		return $i . ' ' . $this->getLabel();
	}

	/**
	 * Gets the hover/alt text that appears over the validation rule icon in the form
	 *
	 * @return  string	label
	 */

	protected function getLabel()
	{
		$params = $this->getParams();
		$tipText = $params->get('tip_text', '');
		if ($tipText !== '')
		{
			return JText::_($tipText);
		}
		if ($this->allowEmpty())
		{
			return JText::_('PLG_VALIDATIONRULE_' . JString::strtoupper($this->pluginName) . '_ALLOWEMPTY_LABEL');
		}
		else
		{
			return JText::_('PLG_VALIDATIONRULE_' . JString::strtoupper($this->pluginName) . '_LABEL');
		}
	}

	/**
	 * Does the validation allow empty value?
	 * Default is false, can be overrideen on per-validation basis (such as isnumeric)
	 *
	 * @return  bool
	 */

	protected function allowEmpty()
	{
		return false;
	}
}
