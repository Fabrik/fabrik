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
	 * Error message
	 *
	 * @var string
	 */
	protected $errorMsg = null;

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
	 * Looks at the validation condition & evaluates it
	 * if evaluation is true then the validation rule is applied
	 *
	 * @param   string  $data  Elements data
	 *
	 * @return  bool	apply validation
	 */

	public function shouldValidate($data)
	{
		if (!$this->shouldValidateIn())
		{
			return false;
		}

		if (!$this->shouldValidateOn())
		{
			return false;
		}

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
	 * Should the validation be run - based on whether in admin/front end
	 *
	 * @return boolean
	 */
	protected function shouldValidateIn()
	{
		$params = $this->getParams();
		$name = $this->elementModel->getFullName();

		$app = JFactory::getApplication();
		$in = $params->get('validate_in', 'both');

		$admin = $app->isAdmin();

		if ($in === 'both')
		{
			return true;
		}

		if ($admin && $in === 'back')
		{
			return true;
		}

		if (!$admin && $in === 'front')
		{
			return true;
		}

		return false;
	}

	/**
	 * Should the validation be run - based on whether new record or editing existing
	 *
	 * @return boolean
	 */
	protected function shouldValidateOn()
	{
		$params = $this->getParams();
		$app = JFactory::getApplication();
		$on = $params->get('validation_on', 'both');
		$rowid = $this->elementModel->getFormModel()->getRowId();

		if ($on === 'both')
		{
			return true;
		}

		if ($rowid === '' && $on === 'new')
		{
			return true;
		}

		if ($rowid !== '' && $on === 'edit')
		{
			return true;
		}

		return false;
	}

	/**
	 * Get the warning message
	 *
	 * @return  string
	 */

	public function getMessage()
	{
		if (isset($this->errorMsg))
		{
			return $this->errorMsg;
		}

		$params = $this->getParams();
		$v = $params->get($this->pluginName . '-message', '');

		if ($v === '')
		{
			$v = 'COM_FABRIK_FAILED_VALIDATION';
		}

		$this->errorMsg = FText::_($v);

		return $this->errorMsg;
	}

	/**
	 * Set the error message
	 *
	 * @param   string  $msg  New error message
	 *
	 * @since   3.0.9
	 *
	 * @return  void
	 */

	public function setMessage($msg)
	{
		$this->errorMsg = $msg;
	}

	/**
	 * Now show only on validation icon next to the element name and put icons and text inside hover text
	 * gets the validation rule icon
	 *
	 * @param   int     $c     Repeat group counter
	 * @param   string  $tmpl  Template folder name
	 *
	 * @deprecated @since 3.0.5
	 *
	 * @return  string
	 */

	public function getIcon($c = 0, $tmpl = '')
	{
		$name = $this->elementModel->validator->getIcon($c);
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

		/**
		 * $$$ hugh - this code doesn't belong here, but am working on an issue whereby if a validation rule plugin
		 * hasn't been saved yet on the backend, the 'icon' param won't be in the the extensions table yet, so we
		 * will have to get it from the manifest XML.
		 *
		 * NOTE - commenting this out, so I don't lose this chunk of code, and can come back and work on this later
		 */
		/*
		if ($plugin->params === '{}')
		{
			$plugin_form = $this->getJForm();
			JForm::addFormPath(JPATH_SITE . '/plugins/fabrik_validationrule/' . $this->get('pluginName'));
			$xmlFile = JPATH_SITE . '/plugins/fabrik_validationrule/' . $this->get('pluginName') . '/' . $this->get('pluginName') . '.xml';
			$xml = $this->jform->loadFile($xmlFile, false);
			$params_fieldset = $plugin_form->getFieldset('params');
		}
		*/

		$params = new JRegistry($plugin->params);

		return $params->get('icon', 'star');
	}

	/**
	 * Get hover text with icon
	 *
	 * @param   int     $c     Validation render order
	 * @param   string  $tmpl  Template folder name
	 *
	 * @return  string
	 */

	public function getHoverText($c = null, $tmpl = '')
	{
		$name = $this->elementModel->validator->getIcon($c);
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
			return FText::_($tipText);
		}

		if ($this->allowEmpty())
		{
			return FText::_('PLG_VALIDATIONRULE_' . JString::strtoupper($this->pluginName) . '_ALLOWEMPTY_LABEL');
		}
		else
		{
			return FText::_('PLG_VALIDATIONRULE_' . JString::strtoupper($this->pluginName) . '_LABEL');
		}
	}

	/**
	 * Does the validation allow empty value?
	 * Default is false, can be overridden on per-validation basis (such as isnumeric)
	 *
	 * @return  bool
	 */

	protected function allowEmpty()
	{
		return false;
	}
}
