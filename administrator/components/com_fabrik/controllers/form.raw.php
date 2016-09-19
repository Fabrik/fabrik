<?php
/**
 * Raw Form controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controllerform');

/**
 * Raw Form controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikAdminControllerForm extends JControllerForm
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_FORM';

	/**
	 * Is in J content plugin
	 *
	 * @var bool
	 */
	public $isMambot = false;

	/**
	 * Set up inline edit view
	 *
	 * @return  void
	 */
	public function inlineedit()
	{
		$model = JModelLegacy::getInstance('FormInlineEdit', 'FabrikFEModel');
		$model->render();
	}

	/**
	 * Save a form's page to the session table
	 *
	 * @return  null
	 */
	public function savepage()
	{
		$input     = $this->input;
		$model     = $this->getModel('Formsession', 'FabrikFEModel');
		$formModel = $this->getModel('Form', 'FabrikFEModel');
		$formModel->setId($input->getInt('formid'));
		$model->savePage($formModel);
	}

	/**
	 * Handle saving posted form data from the admin pages
	 *
	 * @return  void
	 */
	public function process()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$viewName = $input->get('view', 'form');

		// For now lets route this to the html view.
		$view = $this->getView($viewName, 'html');

		if ($model = JModelLegacy::getInstance('Form', 'FabrikFEModel'))
		{
			$view->setModel($model, true);
		}

		$model->setId($input->get('formid', 0));
		$this->isMambot = $input->get('_isMambot', 0);
		$model->getForm();
		$model->rowId = $input->get('rowid', '', 'string');

		// Check for request forgeries
		if ($model->spoofCheck())
		{
			JSession::checkToken() or die('Invalid Token');
		}

		$validated = $model->validate();

		if (!$validated)
		{
			// If its in a module with ajax or in a package or inline edit
			if ($input->get('fabrik_ajax'))
			{
				if ($input->getInt('elid') !== 0)
				{
					// Inline edit
					$eMsgs = array();
					$errs = $model->getErrors();

					// Only raise errors for fields that are present in the inline edit plugin
					$toValidate = array_keys($input->get('toValidate', array(), 'array'));

					foreach ($errs as $errorKey => $e)
					{
						if (in_array($errorKey, $toValidate) && count($e[0]) > 0)
						{
							array_walk_recursive($e, array('FabrikString', 'forHtml'));
							$eMsgs[] = count($e[0]) === 1 ? '<li>' . $e[0][0] . '</li>' : '<ul><li>' . implode('</li><li>', $e[0]) . '</ul>';
						}
					}

					if (!empty($eMsgs))
					{
						$eMsgs = '<ul>' . implode('</li><li>', $eMsgs) . '</ul>';
						header('HTTP/1.1 500 ' . FText::_('COM_FABRIK_FAILED_VALIDATION') . $eMsgs);
						jexit();
					}
					else
					{
						$validated = true;
					}
				}
				else
				{
					echo $model->getJsonErrors();
				}

				if (!$validated)
				{
					return;
				}
			}

			if (!$validated)
			{
				if ($this->isMambot)
				{
					$referrer = filter_var(FArrayHelper::getValue($_SERVER, 'HTTP_REFERER', ''), FILTER_SANITIZE_URL);
					$input->post->set('fabrik_referrer', $referrer);

					/**
					 * $$$ hugh - testing way of preserving form values after validation fails with form plugin
					 * might as well use the 'savepage' mechanism, as it's already there!
					 */
					$this->savepage();
					$this->makeRedirect($model, '');
				}
				else
				{
					$view->display();
				}

				return;
			}
		}

		// Reset errors as validate() now returns ok validations as empty arrays
		$model->errors = array();
		$model->process();

		// Check if any plugin has created a new validation error
		if ($model->hasErrors())
		{
			FabrikWorker::getPluginManager()->runPlugins('onError', $model);
			$view->display();

			return;
		}

		$listModel = $model->getListModel();
		$tid = $listModel->getTable()->id;

		$res = $model->getRedirectURL(true, $this->isMambot);
		$this->baseRedirect = $res['baseRedirect'];
		$url = $res['url'];

		$msg = $model->getRedirectMessage($model);

		if ($input->getInt('elid', 0) !== 0)
		{
			// Inline edit show the edited element
			$inlineModel = $this->getModel('forminlineedit', 'FabrikFEModel');
			$inlineModel->setFormModel($model);
			echo $inlineModel->showResults();

			return;
		}

		if ($input->getInt('packageId', 0) !== 0)
		{
			$rowId = $input->getString('rowid', '', 'string');
			echo json_encode(array('msg' => $msg, 'rowid' => $rowId));

			return;
		}

		// @todo -should get handed off to the json view to do this
		if ($input->getInt('fabrik_ajax') == 1)
		{
			// $$$ hugh - adding some options for what to do with redirect when in content plugin
			// Should probably do this elsewhere, but for now ...
			$redirect_opts = array(
					'msg' => $msg,
					'url' => $url,
					'baseRedirect' => $this->baseRedirect,
					'rowid' => $input->get('rowid', '', 'string'),
					'suppressMsg' => !$model->showSuccessMsg()
			);

			if (!$this->baseRedirect && $this->isMambot)
			{
				$session = JFactory::getSession();
				$context = $model->getRedirectContext();
				$redirect_opts['redirect_how'] = $session->get($context . 'redirect_content_how', 'popup');
				$redirect_opts['width'] = (int) $session->get($context . 'redirect_content_popup_width', '300');
				$redirect_opts['height'] = (int) $session->get($context . 'redirect_content_popup_height', '300');
				$redirect_opts['x_offset'] = (int) $session->get($context . 'redirect_content_popup_x_offset', '0');
				$redirect_opts['y_offset'] = (int) $session->get($context . 'redirect_content_popup_y_offset', '0');
				$redirect_opts['title'] = $session->get($context . 'redirect_content_popup_title', '');
				$redirect_opts['reset_form'] = $session->get($context . 'redirect_content_reset_form', '1') == '1';
			}
			elseif (!$this->baseRedirect && !$this->isMambot)
			{
				/**
				 * $$$ hugh - I think this case only happens when we're a popup form from a list
				 * in which case I don't think "popup" is realy a valid option.  Anyway, need to set something,
				 * so for now just do the same as we do for isMambot, but default redirect_how to 'samepage'
				 */
				$session = JFactory::getSession();
				$context = $model->getRedirectContext();
				$redirect_opts['redirect_how'] = $session->get($context . 'redirect_content_how', 'samepage');
				$redirect_opts['width'] = (int) $session->get($context . 'redirect_content_popup_width', '300');
				$redirect_opts['height'] = (int) $session->get($context . 'redirect_content_popup_height', '300');
				$redirect_opts['x_offset'] = (int) $session->get($context . 'redirect_content_popup_x_offset', '0');
				$redirect_opts['y_offset'] = (int) $session->get($context . 'redirect_content_popup_y_offset', '0');
				$redirect_opts['title'] = $session->get($context . 'redirect_content_popup_title', '');
				$redirect_opts['reset_form'] = $session->get($context . 'redirect_content_reset_form', '1') == '1';

			}
			elseif ($this->isMambot)
			{
				// $$$ hugh - special case to allow custom code to specify that
				// the form should not be cleared after a failed AJAX submit
				$session = JFactory::getSession();
				$context = 'com_fabrik.form.' . $model->get('id') . '.redirect.';
				$redirect_opts['reset_form'] = $session->get($context . 'redirect_content_reset_form', '1') == '1';
			}
			// Let form.js handle the redirect logic
			echo json_encode($redirect_opts);

			// Stop require.js being added to output
			exit;
		}

		if ($input->get('format') == 'raw')
		{
			$url = 'index.php?option=com_fabrik&task=list.view&format=raw&listid=' . $tid;
			$this->setRedirect($url, $msg);
		}
		else
		{
			$this->setRedirect($url, $msg);
		}
	}

	/**
	 * Generic function to redirect
	 *
	 * @param   object  $model  Form model
	 * @param   string  $msg    Optional redirect message
	 *
	 * @deprecated - since 3.0.6 not used
	 * @return  null
	 */
	protected function makeRedirect($model, $msg = null)
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		if (is_null($msg))
		{
			$msg = FText::_('COM_FABRIK_RECORD_ADDED_UPDATED');
		}

		if (array_key_exists('apply', $model->formData))
		{
			$page = 'index.php?option=com_fabrik&task=form.view&formid=' . $input->getInt('formid') . '&listid=' . $input->getInt('listid')
				. '&rowid=' . $input->getString('rowid', '', 'string');
		}
		else
		{
			$page = 'index.php?option=com_fabrik&task=list.view&cid[]=' . $model->getlistModel()->getTable()->id;
		}

		$this->setRedirect($page, $msg);
	}

	/**
	 * Validate via ajax
	 *
	 * @return  null
	 */
	public function ajax_validate()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = $this->getModel('form', 'FabrikFEModel');
		$model->setId($input->getInt('formid', 0));
		$model->getForm();
		$model->setRowId($input->get('rowid', '', 'string'));
		$model->validate();
		$data = array('modified' => $model->modifiedValidationData);

		// Validating entire group when navigating form pages
		$data['errors'] = $model->errors;
		echo json_encode($data);
	}
}
