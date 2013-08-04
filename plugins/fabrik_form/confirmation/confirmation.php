<?php
/**
 * Form Confirmation
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.confirmation
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * After submission, shows a page where the user can confirm the data they are posting
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.confirmation
 * @since       3.0
 */

class PlgFabrik_FormConfirmation extends PlgFabrik_Form
{

	protected $runAway = false;

	/**
	 * If true then the plugin is stating that any subsequent plugin in the same group
	 * should not be run.
	 *
	 * @param   string  $method  current plug-in call method e.g. onBeforeStore
	 *
	 * @return  bool
	 */

	public function runAway($method)
	{
		if ($method == 'onBeforeStore')
		{
			return $this->runAway;
		}
		return false;
	}

	/**
	 * Remove session flags which state that the form should be loaded
	 * from the session
	 *
	 * @param   int  $id  form id
	 *
	 * @since   2.0.4
	 *
	 * @return  void
	 */

	protected function clearSession($id)
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$session = JFactory::getSession();
		$session->clear('com_' . $package . '.form.' . $id . '.session.on');
		$session->clear('com_' . $package . '.form.' . $id . '.session.hash');
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
		$app = JFactory::getApplication();
		$input = $app->input;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		if ($input->getInt('fabrik_ignorevalidation') === 1 || $input->getInt('fabrik_ajax') === 1)
		{
			// Saving via inline edit - dont want to confirm
			return true;
		}
		$this->runAway = false;
		$this->data = $formModel->formData;
		if (!$this->shouldProcess('confirmation_condition'))
		{
			$this->clearSession($formModel->getId());
			return true;
		}
		if ($input->get('fabrik_confirmation') == 2)
		{
			/**
			 * If we were already on the confirmation page
			 * return and set to 2 to ignore?
			 * $$$ hugh - I don't think it really matters,
			 * 'cos getBottomContent isn't going to be called again
			 */
			$input->set('fabrik_confirmation', 1);
			return true;
		}

		// $$$ set flag to stop subsequent onBeforeStore plug-ins from running
		$this->runAway = true;

		// Initialize some variables
		$form = $formModel->getForm();

		// Save the posted form data to the form session, for retrival later
		$sessionModel = JModelLegacy::getInstance('Formsession', 'FabrikFEModel');
		$sessionModel->setFormId($formModel->getId());
		$rowid = $input->get('rowid', 0);
		$sessionModel->setRowId($rowid);
		$sessionModel->savePage($formModel);

		// Tell the form model that it's data is loaded from the session
		$session = JFactory::getSession();
		$session->set('com_' . $package . '.form.' . $formModel->getId() . '.session.on', true);
		$session->set('com_' . $package . '.form.' . $formModel->getId() . '.session.hash', $sessionModel->getHash());

		// Set an error so we can reshow the same form for confirmation purposes
		$formModel->errors['confirmation_required'] = array(JText::_('PLG_FORM_CONFIRMATION_PLEASE_CONFIRM_YOUR_DETAILS'));
		$form->error = JText::_('PLG_FORM_CONFIRMATION_PLEASE_CONFIRM_YOUR_DETAILS');
		$formModel->setEditable(false);

		// Clear out unwanted buttons
		$formParams = $formModel->getParams();
		$formParams->set('reset_button', 0);
		$formParams->set('goback_button', 0);

		/**
		 * The user has posted the form we need to make a note of this
		 * for our getBottomContent() function
		 */
		$input->set('fabrik_confirmation', 1);

		// Set the element access to read only??
		$groups = $formModel->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				// $$$ rob 20/04/2012 unset the element access otherwise previously cached acl is used.
				$elementModel->clearAccess();
				$elementModel->getElement()->access = -1;
			}
		}
		return false;
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
		$app = JFactory::getApplication();
		$input = $app->input;

		// If we have already processed the form
		$this->html = '';
		if ($input->getInt('fabrik_confirmation') === 1)
		{
			$session = JFactory::getSession();

			// Unset this flag
			$input->set('fabrik_confirmation', 2);

			$safeHtmlFilter = JFilterInput::getInstance(null, null, 1, 1);
			$post = $safeHtmlFilter->clean($_POST, 'array');

			/**
			 * load in the posted values as hidden fields so that if we
			 * return to the form to edit it it will populate with our data
			 */
			// $$$ 24/10/2011 testing removing this as data is retrieved via the session not thorugh posted data
			foreach ($post as $key => $val)
			{
				$noneraw = JString::substr($key, 0, JString::strlen($key) - 4);
				if ($key == 'fabrik_vars')
				{
					continue;
				}
				if ($formModel->hasElement($key) || $formModel->hasElement($noneraw))
				{
					// Return;
				}
				if ($formModel->hasElement($noneraw))
				{
					$key = $formModel->getElement($noneraw)->getHTMLName(0);

					// $$$ rob include both raw and non-raw keys (non raw for radios etc, _raw for db joins)
					if (is_array($val))
					{
						foreach ($val as $val2)
						{
							if (!FabrikWorker::isReserved($key))
							{
								if (!strstr($key, '[]'))
								{
									$key .= '[]';
								}
								// $fields[] = '<input type="hidden" name="'.str_replace('_raw','',$key).'[]" value="'.urlencode($val2).'" />';
								// $fields[] = '<input type="hidden" name="'.$key.'" value="'.urlencode($val2).'" />';
								$fields[] = '<input type="hidden" name="' . $key . '" value="' . ($val2) . '" />';
							}
						}
					}
					else
					{
						if (!FabrikWorker::isReserved($key))
						{
							// $fields[] = '<input type="hidden" name="'.str_replace('_raw','',$key).'" value="'.urlencode($val).'" />';
							// $fields[] = '<input type="hidden" name="'.$key.'" value="'.urlencode($val).'" />';
							$fields[] = '<input type="hidden" name="' . $key . '" value="' . ($val) . '" />';
						}
					}
				}
			}
			// Add in a view field as the form doesn't normally contain one
			$fields[] = '<input type="hidden" name="view" value="form" />';
			$fields[] = '<input type="hidden" name="fabrik_confirmation" value="2" />';

			// Add in a button to allow you to go back to the form and edit your data
			$fields[] = "<input type=\"button\" id=\"fabrik_redoconfirmation\" class=\"button btn\" value=\"" . JText::_('PLG_FORM_CONFIRMATION_RE_EDIT')
				. "\" />";

			// Unset the task otherwise we will submit the form to be processed.
			FabrikHelperHTML::addScriptDeclaration(
				"window.addEvent('fabrik.loaded', function() {" . "$('fabrik_redoconfirmation').addEvent('click', function(e) {;\n" . "  this.form.task.value = '';\n"
					. "  this.form.submit.click();\n" . "	});\n" . "});");
			$this->html = implode("\n", $fields);
		}
	}

	/**
	 * Inject custom html into the bottom of the form
	 *
	 * @param   int  $c  plugin counter
	 *
	 * @return  string  html
	 */

	public function getBottomContent_result($c)
	{
		return $this->html;
	}

	/**
	 * Does the plugin use session.on
	 *
	 * @param   object  $params     Plugin params
	 * @param   object  $formModel  Form model
	 *
	 * @return  void
	 */

	public function usesSession($params, $formModel)
	{
		$this->usesSession = true;
	}

}
