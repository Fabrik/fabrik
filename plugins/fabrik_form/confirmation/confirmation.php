<?php
/**
 * Form Confirmation
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.confirmation
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
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
	 * @param   string  $method  Current plug-in call method e.g. onBeforeStore
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
		$this->session->clear('com_' . $this->package . '.form.' . $id . '.session.on');
		$this->session->clear('com_' . $this->package . '.form.' . $id . '.session.hash');
	}

	/**
	 * Before the record is stored, this plugin will see if it should process
	 * and if so store the form data in the session.
	 *
	 * @return  bool  should the form model continue to save
	 */
	public function onBeforeStore()
	{
		/** @var FabrikFEModelForm $formModel */
		$formModel = $this->getModel();
		$params = $this->getParams();
		$input = $this->app->input;

		if ($input->getInt('fabrik_ignorevalidation') === 1 || $input->getInt('fabrik_ajax') === 1)
		{
			// Saving via inline edit - don't want to confirm
			return true;
		}

		$this->runAway = false;
		$this->data = $formModel->formData;

		if (!$this->shouldProcess('confirmation_condition', null, $params))
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

		// Save the posted form data to the form session, for retrieval later
		$sessionModel = JModelLegacy::getInstance('Formsession', 'FabrikFEModel');
		$sessionModel->setFormId($formModel->getId());
		$rowId = $input->get('rowid', 0);
		$sessionModel->setRowId($rowId);
		$sessionModel->savePage($formModel);

		// Tell the form model that it's data is loaded from the session
		$session = $this->session;
		$session->set('com_' . $this->package . '.form.' . $formModel->getId() . '.session.on', true);
		$session->set('com_' . $this->package . '.form.' . $formModel->getId() . '.session.hash', $sessionModel->getHash());

		// Set an error so we can reshow the same form for confirmation purposes
		$formModel->errors['confirmation_required'] = array(FText::_('PLG_FORM_CONFIRMATION_PLEASE_CONFIRM_YOUR_DETAILS'));
		$form->error = FText::_('PLG_FORM_CONFIRMATION_PLEASE_CONFIRM_YOUR_DETAILS');
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
				$elementModel->setEditable(false);
			}
		}

		return false;
	}

	/**
	 * Run for each element's canUse.  Return false to make an element read only
	 *
	 * @param  array  $args  array containing element model being tested
	 *
	 * @return  bool
	 */
	public function onElementCanUse($args)
	{
		if ($this->app->input->get('fabrik_confirmation', '0') === '1')
		{
			return false;
		}

		return true;
	}


	/**
	 * Sets up HTML to be injected into the form's bottom (fnar fnar)
	 *
	 * @return void
	 */
	public function getBottomContent()
	{
		$formModel = $this->getModel();
		$input = $this->app->input;

		// If we have already processed the form
		$this->html = '';

		if ($input->getInt('fabrik_confirmation') === 1)
		{
			$formModel->setEditable(false);

			// Unset this flag
			$input->set('fabrik_confirmation', 2);

			$safeHtmlFilter = JFilterInput::getInstance(null, null, 1, 1);
			$post = $safeHtmlFilter->clean($_POST, 'array');

			/**
			 * load in the posted values as hidden fields so that if we
			 * return to the form to edit it it will populate with our data
			 */
			// $$$ 24/10/2011 testing removing this as data is retrieved via the session not through posted data
			foreach ($post as $key => $val)
			{
				$noneRaw = JString::substr($key, 0, JString::strlen($key) - 4);

				if ($key == 'fabrik_vars')
				{
					continue;
				}

				if ($formModel->hasElement($key) || $formModel->hasElement($noneRaw))
				{
					// Return;
				}

				if ($formModel->hasElement($noneRaw))
				{
					$key = $formModel->getElement($noneRaw)->getHTMLName(0);

					// $$$ rob include both raw and non-raw keys (non raw for radios etc., _raw for db joins)
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
			$fields[] = "<input type=\"button\" id=\"fabrik_redoconfirmation\" class=\"button btn\" value=\"" . FText::_('PLG_FORM_CONFIRMATION_RE_EDIT')
				. "\" />";

			// Unset the task otherwise we will submit the form to be processed.
			FabrikHelperHTML::addScriptDeclaration("
				window.addEvent('fabrik.loaded', function() {
						jQuery('#fabrik_redoconfirmation').on('click', function(e) {
							var form = jQuery(e.target).closest('form');
							form.find('input[name=task]').val('');
							Fabrik.getBlock(form[0].id).mockSubmit();
						});
				});
			");
		}
		else
		{
			$fields[] = '<input type="hidden" name="fabrik_confirmation" value="0" />';
		}

		$this->html = implode("\n", $fields);
	}

	/**
	 * Inject custom html into the bottom of the form
	 *
	 * @param   int  $c  Plugin counter
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
	 * @return  void
	 */
	public function usesSession()
	{
		$this->usesSession = true;
	}
}
