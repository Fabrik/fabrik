<?php
/**
 * Fabrik Plugin From Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.model');

/**
 * Fabrik Plugin From Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class PlgFabrik_Form extends FabrikPlugin
{
	/**
	 * Formatted email data
	 *
	 * @var array
	 */
	protected $emailData = null;

	/**
	 * HTML to return from plugin rendering
	 *
	 * @var string
	 */
	protected $html = '';

	/**
	 * Uses session during processing
	 *
	 * @var bool
	 */
	protected $usesSession = false;

	/**
	 * Data
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Run from form model when deleting record
	 *
	 * @param   array &$groups Form data for deletion
	 *
	 * @return  bool
	 */
	public function onDeleteRowsForm(&$groups)
	{
		return true;
	}

	/**
	 * Run from list model when deleting rows
	 *
	 * @param   array &$groups List data for deletion
	 *
	 * @return  bool
	 */
	public function onAfterDeleteRowsForm(&$groups)
	{
		return true;
	}

	/**
	 * Run right at the beginning of the form processing
	 *
	 * @return    bool
	 */
	public function onBeforeProcess()
	{
		return true;
	}

	/**
	 * Run if form validation fails
	 *
	 * @return    bool
	 */
	public function onError()
	{
	}

	/**
	 * Run before table calculations are applied
	 *
	 * @return    bool
	 */
	public function onBeforeCalculations()
	{
		return true;
	}

	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @return    bool
	 */
	public function onAfterProcess()
	{
		return true;
	}

	/**
	 * Alter the returned plugin manager's result
	 *
	 * @param   string $method Method
	 *
	 * @return bool
	 */
	public function customProcessResult($method)
	{
		return true;
	}

	/**
	 * Sets up HTML to be injected into the form's bottom
	 *
	 * @return void
	 */
	public function getBottomContent()
	{
		$this->html = '';
	}

	/**
	 * Inject custom html into the bottom of the form
	 *
	 * @param   int $c plugin counter
	 *
	 * @return  string  html
	 */
	public function getBottomContent_result($c)
	{
		return $this->html;
	}

	/**
	 * Store the html to insert at the top of the form
	 *
	 * @return  bool
	 */
	public function getTopContent()
	{
		$this->html = '';
	}

	/**
	 * Get any html that needs to be written at the top of the form
	 *
	 * @return  string  html
	 */
	public function getTopContent_result()
	{
		return $this->html;
	}

	/**
	 * Sets up any end html (after form close tag)
	 *
	 * @return  void
	 */
	public function getEndContent()
	{
		$this->html = '';
	}

	/**
	 * Get any html that needs to be written after the form close tag
	 *
	 * @return    string    html
	 */
	public function getEndContent_result()
	{
		return $this->html;
	}

	/**
	 * Helper method used in plugin onProcess() methods. Gets the form's data merged
	 * with the email data. So raw values are those of the submitted form and labels are
	 * those of the element model's getEmailValue() method (if found)
	 *
	 * @since   3.1rc1
	 *
	 * @return  array
	 */
	public function getProcessData()
	{
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark("getProcessData: start") : null;

		$model = $this->getModel();

		// See comments in getEmailData() about caching in $this vs $model
		unset($this->emailData);
		unset($model->emailData);
		$d          = isset($model->formDataWithTableName) ? $model->formDataWithTableName : array();
		$this->data = array_merge($d, $this->getEmailData());
		JDEBUG ? $profiler->mark("getProcessData: end") : null;

		return $this->data;
	}

	/**
	 * Convert the posted form data to the data to be shown in the email
	 * e.g. radio buttons swap their values for the value's label
	 *
	 * @return array email data
	 */
	public function getEmailData()
	{
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark("getEmailData: start") : null;

		/**
		 * NOTE - $$$ hugh - 9/17/2014  - we were originally caching in $this->emailData, but that provides no caching help at all,
		 * as "this" is a plugin model, and the cache needs to be on the form model.  So changed it to use
		 * the $model->emailData.  But for backward compatibility, we will continue to store a copy in $this.  This change has
		 * yielded huge speed gains on form submission for larger forms (in my testing, more than cutting it in half),
		 * as until this change we were re-buiding the $emailData from scratch for every element on the form, which didn't
		 * become apparent till we added the fabrikdebug=2 to allows profiling of submissions, and added the extra profiling
		 * marks for the submission processing
		 *
		 * ... which is great, but ...
		 *
		 * I have a sneaky suspicion it may have some unforeseen side effects for things like calcs, in certain corner
		 * cases where this function gets called early in submission processing.  So watch out for that.  If calcs start
		 * showing up with incorrect values in emails, this is probably why.
		 */

		$model = $this->getModel();

		if (isset($model->emailData))
		{
			JDEBUG ? $profiler->mark("getEmailData: cached") : null;

			return $model->emailData;
		}

		/** @var FabrikFEModelForm $model */
		$model = $this->getModel();

		if (is_null($model->formDataWithTableName))
		{
			return array();
		}

		$model->isAjax();
		/* $$$rob don't render the form - there's no need and it gives a warning about an unfound rowid
		 * $$$ rob also it sets the fromModels rowid to an + int even if we are submitting a new form
		 * which means that form plug-ins set to run on new only don't get triggered if they appear after
		 * fabrikemail/fabrikreceipt
		 * Now instead the pk value is taken from the tableModel->lastInsertId and inserted at the end of this method
		 *$model->render();
		 *
		 * $$$ hugh - hmmmm problem with that is, there's quite a few things that need the rowid, if we're running
		 * 'onAfterProcess' ... I think we need to have a separate $model->isNewRow, or some such, which gets set at
		 * the start of processing, and anything which needs to know if we're new vs edit uses that, rather than looking
		 * for rowid / __pk_val, or whatever.
		 */

		$listModel = $model->getListModel();
		$editable  = $model->isEditable();
		$model->setEditable(false);

		if (is_object($listModel))
		{
			$joins = $listModel->getJoins();
			$model->getJoinGroupIds($joins);
		}

		$this->emailData  = array();
		$model->emailData = array();

		// $$$ hugh - temp foreach fix
		$groups = $model->getGroupsHiarachy();

		foreach ($groups as $gKey => $groupModel)
		{
			$groupParams = $groupModel->getParams();

			// Check if group is actually a table join
			$repeatGroup = 1;

			if ($groupModel->canRepeat())
			{
				if ($groupModel->isJoin())
				{
					$joinModel = $groupModel->getJoinModel();
					$joinTable = $joinModel->getJoin();

					if (is_object($joinTable))
					{
						if (!$groupParams->get('repeat_group_show_first'))
						{
							continue;
						}

						// Need to duplicate this perhaps per the number of times
						// that a repeat group occurs in the default data?

						$elementModels = $groupModel->getPublishedElements();
						reset($elementModels);

						if (!empty($elementModels))
						{
							$tmpElement        = current($elementModels);
							$smallerElHTMLName = $tmpElement->getFullName(true, false);
							$tmpEl             = FArrayHelper::getValue($model->formDataWithTableName, $smallerElHTMLName, array(), 'array');
							$repeatGroup       = count($tmpEl);
						}
					}
				}
			}

			$groupModel->repeatTotal = $repeatGroup;

			for ($c = 0; $c < $repeatGroup; $c++)
			{
				$elementModels = $groupModel->getPublishedElements();

				foreach ($elementModels as $elementModel)
				{
					// Force reload?
					$elementModel->defaults          = null;
					$elementModel->_repeatGroupTotal = $repeatGroup - 1;

					$k   = $elementModel->getFullName(true, false);
					$key = $elementModel->getFullName(true, false);

					// Used for working out if the element should behave as if it was
					// in a new form (joined grouped) even when editing a record
					$elementModel->inRepeatGroup = $groupModel->canRepeat();
					$elementModel->_inJoin       = $groupModel->isJoin();
					$elementModel->setEditable(false);

					if ($groupModel->isJoin())
					{
						if ($groupModel->canRepeat())
						{
							$raw                              = FArrayHelper::getValue($model->formDataWithTableName[$k], $c, '');
							$this->emailData[$k . '_raw'][$c] = $raw;
							$this->emailData[$k][$c]          = $elementModel->getEmailValue($raw, $model->formDataWithTableName, $c);
							continue;
						}
						else
						{
							// E.g. ajax file upload - repeat data in none-repeat group
							if (array_key_exists($k, $model->formDataWithTableName) && is_array($model->formDataWithTableName[$k]))
							{
								foreach ($model->formDataWithTableName[$k] as $multiKey => $multiData)
								{
									$this->emailData[$k . '_raw'][$multiKey] = $multiData;
									$this->emailData[$k][$multiKey]          = $elementModel->getEmailValue($multiData, $model->formDataWithTableName, $multiData);
								}
								continue;
							}
						}
					}
					elseif (array_key_exists($key, $model->formDataWithTableName))
					{
						$rawValue = FArrayHelper::getValue($model->formDataWithTableName, $k . '_raw', '');

						if ($rawValue == '')
						{
							$this->emailData[$k . '_raw'] = $model->formDataWithTableName[$key];
						}
						else
						{
							/* Things like the user element only have their raw value filled in at this point
							 * so don't overwrite that with the blank none-raw value
							 * the none-raw value is add in getEmailValue()
							 */
							$this->emailData[$k . '_raw'] = $rawValue;
						}
					}

					$emailValue = '';

					if (array_key_exists($k . '_raw', $this->emailData))
					{
						$emailValue = $this->emailData[$k . '_raw'];
					}
					elseif (array_key_exists($k, $this->emailData))
					{
						$emailValue = $this->emailData[$k];
					}

					/**
					 * $$$ hugh - no idea why we wouldn't call getEmailValue() for multiselect joins, happened in this commit:
					 * https://github.com/Fabrik/fabrik/commit/06a03dbb430281951f00b9b3b691ea015a52ac7b
					 * ... but afaict, it's bogus, as otherwise multiselect joins never get processed in to labels, and stay as raw values.
					 */
					//if (!$elementModel->isJoin())
					//{
					$this->emailData[$k] = $elementModel->getEmailValue($emailValue, $model->formDataWithTableName, $c);
					//}
				}
			}
		}

		$pk = $listModel->getPrimaryKey(true);

		// If form contained joins then this was altering the exiting pk data to be the joined table's id - not good!
		if (is_object($listModel) && empty($this->emailData[$pk]))
		{
			$this->emailData[$pk]          = $listModel->lastInsertId;
			$this->emailData[$pk . '_raw'] = $listModel->lastInsertId;
		}

		$model->setEditable($editable);
		$model->emailData = $this->emailData;

		JDEBUG ? $profiler->mark("getEmailData: end") : null;

		return $this->emailData;
	}

	/**
	 * Set redirect URL for process plugins that navigate to a 3rd party site
	 * and then return to the site E.g Paypal.
	 *
	 * @param string $url
	 */
	protected function setDelayedRedirect($url)
	{
		$formModel = $this->model;
		$context = $formModel->getRedirectContext();

		/* $$$ hugh - fixing issue with new redirect, which now needs to be an array.
		 * Not sure if we need to preserve existing session data, or just create a new surl array,
		 * to force ONLY redirect to PayPal?
		 */
		$urls = (array) $this->session->get($context . 'url', array());
		$urls[$this->renderOrder] = $url;
		$this->session->set($context . 'url', $urls);
		$this->session->set($context . 'redirect_content_how', 'samepage');
	}

	/**
	 * Get the class to manage the plugin
	 * to ensure that the file is loaded only once
	 *
	 * @since   3.1b
	 *
	 * @return void
	 */
	public function formJavascriptClass()
	{
		$formModel = $this->getModel();
		$ext       = FabrikHelperHTML::isDebug() ? '.js' : '-min.js';
		$name      = $this->get('_name');
		static $jsClasses;

		if (!isset($jsClasses))
		{
			$jsClasses = array();
		}

		// Load up the default script

		$script = 'plugins/fabrik_form/' . $name . '/' . $name . $ext;

		if (empty($jsClasses[$script]))
		{
			$formModel->formPluginShim[ucfirst($name)] = $script;
			$jsClasses[$script]                        = 1;
		}
	}

	/**
	 * Get a list of admins which should receive emails
	 *
	 * @return  array  admin user objects
	 */
	protected function getAdminInfo()
	{
		$query = $this->_db->getQuery(true);
		$query->select('id, name, email, sendEmail')->from('#__users')->where('sendEmail = 1');
		$this->_db->setQuery($query);
		$rows = $this->_db->loadObjectList();

		return $rows;
	}

	/**
	 * Does the plugin use session.on
	 *
	 * @since  3.0.8
	 *
	 * @return  void
	 */
	public function usesSession()
	{
		$this->usesSession = false;
	}

	/**
	 * Does the plugin use session.on - returned results
	 *
	 * @since  3.0.8
	 *
	 * @return    bool  session.on
	 */

	public function usesSession_result()
	{
		return $this->usesSession;
	}

	/**
	 * Get the element's JLayout file
	 * Its actually an instance of FabrikLayoutFile which inverses the ordering added include paths.
	 * In FabrikLayoutFile the addedPath takes precedence over the default paths, which makes more sense!
	 *
	 * @param   string $type form/details/list
	 *
	 * @return FabrikLayoutFile
	 */
	public function getLayout($type)
	{
		$name     = get_class($this);
		$name     = strtolower(JString::str_ireplace('PlgFabrik_Form', '', $name));
		$basePath = COM_FABRIK_BASE . '/plugins/fabrik_form/' . $name . '/layouts';
		$layout   = new FabrikLayoutFile('fabrik-form-' . $name . '-' . $type, $basePath, array('debug' => false, 'component' => 'com_fabrik', 'client' => 'site'));
		$layout->addIncludePaths(JPATH_THEMES . '/' . $this->app->getTemplate() . '/html/layouts');
		$layout->addIncludePaths(JPATH_THEMES . '/' . $this->app->getTemplate() . '/html/layouts/com_fabrik');

		return $layout;
	}

	/**
	 * Get the fields value regardless of whether its in joined data or no
	 *
	 * @param   string $pName   Params property name to get the value for
	 * @param   array  $data    Posted form data
	 * @param   mixed  $default Default value
	 *
	 * @return  mixed  value
	 */
	public function getFieldValue($pName, $data, $default = '')
	{
		$params = $this->getParams();

		if ($params->get($pName, '') === '')
		{
			return $default;
		}

		$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($params->get($pName));
		$name         = $elementModel->getFullName(true, false);

		return ArrayHelper::getValue($data, $name, $default);
	}

	/**
	 * Replace a plugin parameter value with data parsed via parseMessageForPlaceholder
	 *
	 * @param string $pName Parameter name
	 *
	 * @return string
	 */
	public function placeholder($pName)
	{
		$params = $this->getParams();
		$w      = new FabrikWorker;

		return $w->parseMessageForPlaceHolder($params->get($pName), $this->data);
	}
}
