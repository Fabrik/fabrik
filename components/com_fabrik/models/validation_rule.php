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

	var $_pluginName = null;

	var $_counter = null;

	var $pluginParams = null;

	var $_rule = null;

	/**
	 * If true uses icon of same name as validation, otherwise uses png icon specified by $icon
	 *
	 *  @var bool
	 */
	protected $icon = true;

	/**
	 * Validate the elements data against the rule
	 *
	 * @param   string  $data           to check
	 * @param   object  &$elementModel  element Model
	 * @param   int     $pluginc        plugin sequence ref
	 * @param   int     $repeatCounter  repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */

	public function validate($data, &$elementModel, $pluginc, $repeatCounter)
	{
		return true;
	}

	/**
	 * Checks if the validation should replace the submitted element data
	 * if so then the replaced data is returned otherwise original data returned
	 *
	 * @param   string  $data           original data
	 * @param   model   &$elementModel  element model
	 * @param   int     $pluginc        validation plugin counter
	 * @param   int     $repeatCounter  repeat group counter
	 *
	 * @return  string	original or replaced data
	 */

	function replace($data, &$elementModel, $pluginc, $repeatCounter)
	{
		return $data;
	}

	/**
	 * Looks at the validation condition & evaulates it
	 * if evaulation is true then the validation rule is applied
	 *
	 * @param   string  $data  elements data
	 * @param   int     $c     repeat group counter
	 *
	 * @return  bool	apply validation
	 */

	function shouldValidate($data, $c)
	{
		$params = $this->getParams();
		$post = JRequest::get('post');
		$v = (array) $params->get($this->_pluginName . '-validation_condition');
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

	function getParams()
	{
		return $this->elementModel->getParams();
	}

	function getPluginParams()
	{
		if (!isset($this->pluginParams))
		{
			$this->pluginParams = $this->_loadPluginParams();
		}
		return $this->pluginParams;
	}

	function _loadPluginParams()
	{
		return $this->elementModel->getParams();
	}

	function &getValidationRule()
	{
		if (!$this->_rule)
		{
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
			$row = FabTable::getInstance('Validationrule', 'FabrikTable');
			$row->load($this->_id);
			$this->_rule = $row;
		}
		return $this->_rule;
	}

	/**
	 * Get the warning message
	 *
	 * @param   int  $c  validation rule number.
	 *
	 * @return  string
	 */

	public function getMessage($c = 0)
	{
		$params = $this->getParams();
		$v = (array) $params->get($this->_pluginName . '-message');
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
	 * @param   object  $elementModel  element model
	 * @param   int     $c             repeat group counter
	 * @param   string  $tmpl          template folder name
	 *
	 * @deprecated @since 3.0.5
	 *
	 * @return  string
	 */

	public function getIcon($elementModel, $c = 0, $tmpl = '')
	{
		$name = $this->icon === true ? $this->_pluginName : $this->icon;
		if ($this->allowEmpty($elementModel, $c))
		{
			$name .= '_allowempty';
		}
		$label = '<span>' . $this->getLabel($elementModel, $c) . '</span>';
		$opts = array('class' => 'fabrikTip ' . $this->_pluginName, 'opts' => "{notice:true}", 'title' => $label);
		$str = FabrikHelperHTML::image($name . '.png', 'form', $tmpl, $opts);
		return $str;
	}

	/**
	 * Get hover text with icon
	 *
	 * @param   object  $elementModel  element model
	 * @param   int     $pluginc       validation render order
	 * @param   string  $tmpl          template folder name
	 *
	 * @return  string
	 */

	public function getHoverText($elementModel, $pluginc = 0, $tmpl = '')
	{
		$name = $this->icon === true ? $this->_pluginName : $this->icon;
		if ($this->allowEmpty($elementModel, $pluginc))
		{
			$name .= '_allowempty';
		}
		$i = FabrikHelperHTML::image($name . '.png', 'form', $tmpl, array('class' => $this->_pluginName));
		return $i . $this->getLabel($elementModel, $pluginc);
	}

	/**
	 * Gets the hover/alt text that appears over the validation rule icon in the form
	 *
	 * @param   object  $elementModel  element model
	 * @param   int     $pluginc       validation render order
	 *
	 * @return  string	label
	 */

	protected function getLabel($elementModel, $pluginc)
	{
		if ($this->allowEmpty($elementModel, $pluginc))
		{
			return JText::_('PLG_VALIDATIONRULE_' . JString::strtoupper($this->_pluginName) . '_ALLOWEMPTY_LABEL');
		}
		else
		{
			return JText::_('PLG_VALIDATIONRULE_' . JString::strtoupper($this->_pluginName) . '_LABEL');
		}
	}

	/**
	 * Does the validation allow empty value?
	 * Default is false, can be overrideen on per-validation basis (such as isnumeric)
	 *
	 * @param   object  $elementModel  element model
	 * @param   int     $pluginc       validation render order
	 *
	 * @return  bool
	 */

	protected function allowEmpty($elementModel, $pluginc)
	{
		return false;
	}
}
