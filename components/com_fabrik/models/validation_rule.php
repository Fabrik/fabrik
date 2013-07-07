<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

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
	 * Validate the elements data against the rule
	 *
	 * @param   string  $data           To check
	 * @param   object  &$elementModel  Element Model
	 * @param   int     $pluginc        Plugin sequence ref
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */

	public function validate($data, &$elementModel, $pluginc, $repeatCounter, $allData)
	{
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

	public function replace($data, &$elementModel, $pluginc, $repeatCounter, $allData)
	{
		return $data;
	}

	/**
	 * Looks at the validation condition & evaulates it
	 * if evaulation is true then the validation rule is applied
	 *
	 * @param   string  $data  Elements data
	 * @param   int     $c     Repeat group counter
	 *
	 * @return  bool	apply validation
	 */

	public function shouldValidate($data, $c)
	{
		$params = $this->getParams();
		$filter = JFilterInput::getInstance();
		$post = $filter->clean($_POST, 'array');
		$v = (array) $params->get($this->pluginName . '-validation_condition');
		if (!array_key_exists($c, $v))
		{
			return true;
		}
		$condition = $v[$c];
		if ($condition == '')
		{
			return true;
		}
		$w = new FabrikWorker;

		// $$$ rob merge join data into main array so we can access them in parseMessageForPlaceHolder()
		$joindata = JArrayHelper::getValue($post, 'join', array());
		foreach ($joindata as $joinid => $joind)
		{
			foreach ($joind as $k => $v)
			{
				if ($k !== 'rowid')
				{
					$post[$k] = $v;
				}
			}
		}
		$condition = trim($w->parseMessageForPlaceHolder($condition, $post));
		$formModel = $this->elementModel->getFormModel();
		$res = @eval($condition);
		if (is_null($res))
		{
			return true;
		}
		return $res;
	}

	/**
	 * Get element model params
	 *
	 * @return  object  Params
	 */

	public function getParams()
	{
		return $this->elementModel->getParams();
	}

	/**
	 * Get the warning message
	 *
	 * @param   int  $c  Validation rule number.
	 *
	 * @return  string
	 */

	public function getMessage($c = 0)
	{
		$params = $this->getParams();
		$v = (array) $params->get($this->pluginName . '-message');
		$v = JArrayHelper::getValue($v, $c, '');
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
	 * @param   object  $elementModel  Element model
	 * @param   int     $c             Repeat group counter
	 * @param   string  $tmpl          Template folder name
	 *
	 * @deprecated @since 3.0.5
	 *
	 * @return  string
	 */

	public function getIcon($elementModel, $c = 0, $tmpl = '')
	{
		$name = $elementModel->validator->getIcon();
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
	 * @param   object  $elementModel  Element model
	 * @param   int     $pluginc       Validation render order
	 * @param   string  $tmpl          Template folder name
	 *
	 * @return  string
	 */

	public function getHoverText($elementModel, $pluginc = 0, $tmpl = '')
	{
		$name = $elementModel->validator->getIcon();
		$i = FabrikHelperHTML::image($name, 'form', $tmpl, array('class' => $this->pluginName));
		return $i . ' ' . $this->getLabel($elementModel, $pluginc);
	}

	/**
	 * Gets the hover/alt text that appears over the validation rule icon in the form
	 *
	 * @param   object  $elementModel  Element model
	 * @param   int     $pluginc       Validation render order
	 *
	 * @return  string	label
	 */

	protected function getLabel($elementModel, $pluginc)
	{
		$params = $elementModel->getParams();
		$tipText = $params->get('tip_text', '');
		$tipText = JArrayHelper::getValue($tipText, $pluginc, '');
		if ($tipText !== '')
		{
			return JText::_($tipText);
		}
		if ($this->allowEmpty($elementModel, $pluginc))
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
	 * @param   object  $elementModel  Element model
	 * @param   int     $pluginc       Validation render order
	 *
	 * @return  bool
	 */

	protected function allowEmpty($elementModel, $pluginc)
	{
		return false;
	}
}
