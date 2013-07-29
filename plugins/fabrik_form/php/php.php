<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.php
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Run some php when the form is submitted
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.php
 * @since       3.0
 */

class PlgFabrik_FormPHP extends PlgFabrik_Form
{

	/**
	* canEditGroup, called when canEdit called in group model
	*
	* @param   object  $params      Plugin parameters
	* @param   object  &$formModel  Form model
	* @param   array   $groupModel  Group model
	*
	* @return  void
	*/

	public function onCanEditGroup($params, &$formModel, $groupModel)
	{
		if ($params->get('only_process_curl') == 'onCanEditGroup')
		{
			if (is_array($groupModel))
			{
				$groupModel = $groupModel[0];
			}
			if ($this->_runPHP($params, $formModel, $groupModel) === false)
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Sets up HTML to be injected into the form's bottom
	 *
	 * @param   object  $params     params
	 * @param   object  $formModel  form model
	 *
	 * @return void
	 */

	public function getBottomContent($params, $formModel)
	{
		$this->html = '';
		if ($params->get('only_process_curl') == 'getBottomContent')
		{
			$this->html = $this->_runPHP($params, $formModel);
			if ($this->html === false)
			{
				return JError::raiseWarning(E_WARNING, 'php form plugin failed');
			}
		}
		return true;
	}

	/**
	* Get any html that needs to be written after the form close tag
	*
	* @return	string	html
	*/

	public function getTopContent_result()
	{
		return $this->html;
	}

	/**
	 * Store the html to insert at the top of the form
	 *
	 * @param   object  $params     params
	 * @param   object  $formModel  form model
	 *
	 * @return  bool
	 */

	public function getTopContent($params, $formModel)
	{
		$this->html = '';
		if ($params->get('only_process_curl') == 'getTopContent')
		{
			$this->html = $this->_runPHP($params, $formModel);
			if ($this->html === false)
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Get any html that needs to be written after the form close tag
	 *
	 * @return	string	html
	 */

	public function getEndContent_result()
	{
		return $this->html;
	}

	/**
	 * Sets up any end html (after form close tag)
	 *
	 * @param   object  $params     plugin params
	 * @param   object  $formModel  form model
	 *
	 * @return  void
	 */

	public function getEndContent($params, $formModel)
	{
		$this->html = '';
		if ($params->get('only_process_curl') == 'getEndContent')
		{
			$this->html = $this->_runPHP($params, $formModel);
			if ($this->html === false)
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Run right at the beginning of the form processing
	 *
	 * @param   object  $params      plpugin params
	 * @param   object  &$formModel  form model
	 *
	 * @return	bool
	 */

	public function onBeforeProcess($params, &$formModel)
	{
		if ($params->get('only_process_curl') == 'onBeforeProcess')
		{
			if ($this->_runPHP($params, $formModel) === false)
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Before the record is stored, this plugin will see if it should process
	 * and if so store the form data in the session.
	 *
	 * @param   object  $params      params
	 * @param   object  &$formModel  form model
	 *
	 * @return  bool  should the form model continue to save
	 */

	public function onBeforeStore($params, &$formModel)
	{
		if ($params->get('only_process_curl') == 'onBeforeStore')
		{
			if ($this->_runPHP($params, $formModel) === false)
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Run before table calculations are applied
	 *
	 * @param   object  $params      plpugin params
	 * @param   object  &$formModel  form model
	 *
	 * @return	bool
	 */

	public function onBeforeCalculations($params, &$formModel)
	{
		if ($params->get('only_process_curl') == 'onBeforeCalculations')
		{
			if ($this->_runPHP($params, $formModel) === false)
			{
				return JError::raiseWarning(E_WARNING, 'php form plugin failed');
			}
		}
		return true;
	}

	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @param   object  $params      plugin params
	 * @param   object  &$formModel  form model
	 *
	 * @return	bool
	 */

	public function onAfterProcess($params, &$formModel)
	{
		if ($params->get('only_process_curl') == 'onAfterProcess')
		{
			if ($this->_runPHP($params, $formModel) === false)
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Run when the form is loaded - after its data has been created
	 * data found in $formModel->_data
	 *
	 * @param   object  $params      plugin params
	 * @param   object  &$formModel  form model
	 *
	 * @return	bool
	 */

	public function onLoad($params, &$formModel)
	{
		if ($params->get('only_process_curl') == 'onLoad')
		{
			return $this->_runPHP($params, $formModel);
		}
		return true;
	}

	/**
	 * Run when the form is loaded - before its data has been created
	 * data found in $formModel->_data
	 *
	 * @param   object  $params      plugin params
	 * @param   object  &$formModel  form model
	 *
	 * @return	bool
	 */

	public function onBeforeLoad($params, &$formModel)
	{
		if ($params->get('only_process_curl') == 'onBeforeLoad')
		{
			return $this->_runPHP($params, $formModel);
		}
		return true;
	}

	/**
	 * Process the plugin, called when form is submitted
	 *
	 * @param   object  $params      plugin params
	 * @param   object  &$formModel  form model
	 *
	 * @return  bool
	 */

	public function onError($params, &$formModel)
	{
		if ($params->get('only_process_curl') == 'onError')
		{
			$this->_runPHP($params, $formModel);
		}
		return true;
	}

	/**
	 * Run plugins php code/script
	 *
	 * @param   object  $params       Plugin params
	 * @param   object  &$formModel   Form model
	 * @param   object  &$groupModel  Group model
	 *
	 * @return bool false if error running php code
	 */

	private function _runPHP($params, &$formModel, &$groupModel = null)
	{
		/**
		 * if you want to modify the submitted form data
		 * $formModel->updateFormData('tablename___elementname', $newvalue);
		 */
		$method = $params->get('only_process_curl');
		/*
		 *  $$$ rob this is poor when submitting the form the data is stored in _formData, when editing its stored in _data -
		 *  as this method can run on render or on submit we have to do a little check to see which one we should use.
		 *  really we should use the same form property to store the data regardless of form state
		 */

		// $$$ hugh - erm, why are we putting data in $this->html?  It's only used by onGetFooContent plugins, for, well, html!
		$this->html = array();
		if (!empty($formModel->_formData))
		{
			$this->html = $formModel->_formData;
		}
		elseif (!empty($formModel->_data))
		{
			$this->html = $formModel->_data;
		}
		$w = new FabrikWorker;
		if ($params->get('form_php_file') == -1)
		{
			$code = $w->parseMessageForPlaceHolder($params->get('curl_code', ''), $this->html, true, true);

			if ($method == 'getBottomContent' || $method == 'getTopContent' || $method == 'getEndContent')
			{
				/* For these types of scripts any out put you want to inject into the form should be echo'd out
				 * $$$ hugh - the tooltip on the PHP plugin says specifically NOT to echo, but to return the content.
				 * Rather than break any existing code by changing this code to do what the tooltip says, here's a
				 * Horrible Hack so either way should work.
				 */
				ob_start();
				$php_result = eval($code);
				$output = ob_get_contents();
				ob_end_clean();
				if (!empty($output))
				{
					return $output;
				}
				else
				{
					if (is_string($php_result))
					{
						return $php_result;
					}
				}
				// Didn't get a viable response from either OB or result, so just return empty string
				return '';
			}
			else
			{
				$php_result = eval($code);

				// Bail out if code specifically returns false
				if ($php_result === false)
				{
					return false;
				}
			}
		}
		else
		{
			// Added require_once param, for (kinda) corner case of having a file that defines functions, which gets used
			// nore than once on the same page.
			$require_once = $params->get('form_php_require_once', '0') == '1';

			/* $$$ hugh - give them some way of getting at form data
			 * (I'm never sure if $_REQUEST is 'safe', i.e. if it has post-validation data)
			 * $$$ hugh - pretty sure we can dump this, but left it in for possible backward compat issues
			 */
			global $fabrikFormData, $fabrikFormDataWithTableName;

			// For some reason, = wasn't working??
			$fabrikFormData = $this->html;

			// $$$ hugh - doesn't exist for tableless forms
			if (isset($formModel->_formDataWithtableName))
			{
				$fabrikFormDataWithTableName = $formModel->_formDataWithtableName;
			}

			$php_file = JFilterInput::getInstance()->clean($params->get('form_php_file'), 'CMD');
			$php_file = JPATH_ROOT . '/plugins/fabrik_form/php/scripts/' . $php_file;

			if (!JFile::exists($php_file))
			{
				JError::raiseNotice(500, 'Mssing PHP form plugin file');
				return false;
			}

			// If it's a form load method, needs to be handled thisaway
			if ($method == 'getBottomContent' || $method == 'getTopContent' || $method == 'getEndContent')
			{
				/*
				 * For these types of scripts any out put you want to inject into the form should be echo'd out
				 * @TODO - shouldn't we apply this logic above as well (direct eval)?
				 * $$$ hugh - AAAAGH.  Tbe tooltip on the form plugin itself specifically says NOT to echo, but to
				 * return the content.  Rather than just changing this code to do what the comment says, which would
				 * break any existing code folk have, I'll do a Horrible Hack so it works either way.
				 */
				ob_start();
				$php_result = $require_once ? require_once $php_file : require $php_file;
				$output = ob_get_contents();
				ob_end_clean();
				if (!empty($output))
				{
					return $output;
				}
				else
				{
					if (is_string($php_result))
					{
						return $php_result;
					}
				}
				// Didn't get a viable response from either OB or result, so just return empty string
				return '';
			}

			// OK, it's a form submit method, so handle it thisaway
			$php_result = $require_once ? require_once $php_file : require $php_file;

			// Bail out if code specifically returns false
			if ($php_result === false)
			{
				return false;
			}

			/*
			 * $$$ hugh - added this to make it more convenient for defining functions to call in form PHP.
			 * So you can have a 'script file' that defines function(s), AND a direct eval that calls them,
			 * without having to stick a require() in the eval code.
			 * @TODO add an option to specify which way round to execute (file first or eval first)
			 * as per Skype convo with Rob.
			 */
			$code = $w->parseMessageForPlaceHolder($params->get('curl_code', ''), $this->html, true, true);
			if (!empty($code))
			{
				$php_result = eval($code);

				// Bail out if code specifically returns false
				if ($php_result === false)
				{
					return false;
				}
			}
		}

		// Well, we seemed to have got here without blowing up, so return true
		return true;
	}

}
