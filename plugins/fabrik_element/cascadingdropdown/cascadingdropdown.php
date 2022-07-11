<?php
/**
 * Plugin element to render cascading dropdown
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.cascadingdropdown
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

require_once JPATH_SITE . '/plugins/fabrik_element/databasejoin/databasejoin.php';

/**
 * Plugin element to render cascading drop-down
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.cascadingdropdown
 * @since       3.0
 */
class PlgFabrik_ElementCascadingdropdown extends PlgFabrik_ElementDatabasejoin
{
	/**
	 * J Parameter name for the field containing the label value
	 *
	 * @var string
	 */
	protected $labelParam = 'cascadingdropdown_label';

	/**
	 * J Parameter name for the field containing the concat label
	 *
	 * @var string
	 */
	protected $concatLabelParam = 'cascadingdropdown_label_concat';

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */
	public function elementJavascript($repeatCounter)
	{
		$input = $this->app->input;
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();

		if ($this->getDisplayType() === 'auto-complete')
		{
			$autoOpts = array();
			$autoOpts['observerid'] = $this->getWatchId($repeatCounter);
			$autoOpts['formRef'] = $this->getFormModel()->jsKey();
			$autoOpts['storeMatchedResultsOnly'] = true;
			FabrikHelperHTML::autoComplete($id, $this->getElement()->id, $this->getFormModel()->getId(), 'cascadingdropdown', $autoOpts);
		}

		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->showPleaseSelect = $this->showPleaseSelect();
		$opts->watch = $this->getWatchId($repeatCounter);
		$watchElementModel = $this->getWatchElement();
		$opts->watchChangeEvent = $watchElementModel->getChangeEvent();
		$opts->displayType = $params->get('cdd_display_type', 'dropdown');
		$opts->id = $this->getId();
		$opts->listName = $this->getListModel()->getTable()->db_table_name;
		$opts->lang           = FabrikWorker::getMultiLangURLCode();

		// This bizarre chunk of code handles the case of setting a CDD value on the QS on a new form
		$rowId = $this->getFormModel()->getRowId();
		$fullName = $this->getFullName();
		$watchName = $this->getWatchFullName();

		// If returning from failed posted validation data can be in an array
		$qsValue = $input->get($fullName, array(), 'array');
		$qsValue = FArrayHelper::getValue($qsValue, 0, null);
		$qsWatchValue = $input->get($watchName, array(), 'array');
		$qsWatchValue = FArrayHelper::getValue($qsWatchValue, 0, null);
		$useQsValue = $this->getFormModel()->hasErrors() && $this->isEditable() && $rowId === '' && !empty($qsValue) && !empty($qsWatchValue);
		$opts->def = $useQsValue ? $qsValue : $this->getValue(array(), $repeatCounter);

		// $$$ hugh - for reasons utterly beyond me, after failed validation, getValue() is returning an array.
		if (is_array($opts->def) && !empty($opts->def))
		{
			$opts->def = $opts->def[0];
		}

		$watchGroup = $this->getWatchElement()->getGroup()->getGroup();
		$group = $this->getGroup()->getGroup();
		$opts->watchInSameGroup = $watchGroup->id === $group->id;
		$opts->editing = ($this->isEditable() && $rowId !== '');
		$opts->showDesc = $params->get('cdd_desc_column', '') === '' ? false : true;
		$opts->advanced = $this->getAdvancedSelectClass() != '';
		$formId = $this->getFormModel()->getId();
		$opts->autoCompleteOpts = $opts->displayType == 'auto-complete'
				? FabrikHelperHTML::autoCompleteOptions($opts->id, $this->getElement()->id, $formId, 'cascadingdropdown') : null;
		$this->elementJavascriptJoinOpts($opts);

		$data = $this->getFormModel()->data;

		// Was otherwise using the none-raw value.
		$opts->value = $this->getValue($data, $repeatCounter, array('raw' => true));
		$opts->optsPerRow = (int) $params->get('dbjoin_options_per_row', 1);

		if (is_array($opts->value) && count($opts->value) > 0)
		{
			$opts->value = ArrayHelper::getValue($opts->value, 0);
		}

		return array('FbCascadingdropdown', $id, $opts);
	}

	/**
	 * Get the field name to use as the column that contains the join's label data
	 *
	 * @param   bool  $useStep  use step in element name
	 *
	 * @return	string join label column either returns concat statement or quotes `tablename`.`elementname`
	 */
	public function getJoinLabelColumn($useStep = false)
	{
		$params = $this->getParams();
		$join = $this->getJoin();
		$db = $this->getDb();

		if (($params->get('cascadingdropdown_label_concat') != '') && $this->app->input->get('override_join_val_column_concat', '0') !== '1')
		{
			$val = $params->get('cascadingdropdown_label_concat');

			if ($join)
			{
				$val = $this->parseThisTable($val, $join);
			}

			$w = new FabrikWorker;
			$val = $w->parseMessageForPlaceHolder($val, array(), false, false, null, false);

			return 'CONCAT_WS(\'\', ' . $val . ')';
		}

		$label = FabrikString::shortColName($join->params->get('join-label'));

		if ($label == '')
		{
			// This is being raised with checkbox rendering and using drop-down filter, everything seems to be working with using the element name though!
			// JError::raiseWarning(500, 'Could not find the join label for ' . $this->getElement()->name . ' try unlinking and saving it');
			$label = $this->getElement()->name;
		}

		if ($this->isJoin())
		{
			$joinTableName = $this->getDbName();
			$label = $this->getLabelParamVal();
		}
		else
		{
			$joinTableName = $join->table_join_alias;
		}

		return $useStep ? $joinTableName . '___' . $label : $db->qn($joinTableName . '.' . $label);
	}

	/**
	 * Reset cached data, needed when rendering table if CDD
	 * is in repeat group, so we can build optionVals
	 *
	 * @deprecated - not used
	 *
	 * @return  void
	 */
	protected function _resetCache()
	{
		unset($this->optionVals);
		unset($this->sql);
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$db = $this->getDb();
		$params = $this->getParams();
		$name = $this->getHTMLName($repeatCounter);
		$opts = array('raw' => 1);
		$default = (array) $this->getValue($data, $repeatCounter, $opts);

		/* $$$ rob don't bother getting the options if editable as the js event is going to get them.
		 * However if in readonly mode the we do need to get the options
		 * $$$ hugh - need to rethink this approach, see ticket #725. When editing, we need
		 * to build options and selection on server side, otherwise daisy chained CDD's don't
		 * work due to timing issues in JS between onComplete and get_options calls.
		 * $tmp = 	$this->isEditable() ? array() : $this->_getOptions($data);
		 * So ... we want to get options if not editable, or if editing an existing row.
		 * See also small change to attachedToForm() in JS, and new 'editing' option in
		 * elementJavascript() above, so the JS won't get options on init when editing an existing row
		 */
		$tmp = array();
		$rowId = $this->app->input->string('rowid', '', 'string');
		$showPlease = $this->showPleaseSelect();

		// $$$ hugh testing to see if we need to load options after a validation failure, but I don't think we do, as JS will reload via AJAX
		if (!$this->isEditable() || ($this->isEditable() && $rowId !== ''))
		{
			$tmp = $this->_getOptions($data, $repeatCounter);
		}
		else
		{
			if ($showPlease)
			{
				$tmp[] = $this->selectOption();
			}
		}

		$imageOpts = array('alt' => FText::_('PLG_ELEMENT_CALC_LOADING'), 'style' => 'display:none;padding-left:10px;', 'class' => 'loader');
		$this->loadingImg = FabrikHelperHTML::image('ajax-loader.gif', 'form', @$this->tmpl, $imageOpts);

		// Get the default label for the drop down (use in read only templates)
		$defaultLabel = '';
		$defaultValue = '';

		foreach ($tmp as $obj)
		{
			if (in_array($obj->value, $default))
			{
				$defaultValue = $obj->value;
				$defaultLabel = $obj->text;
				break;
			}
		}

		$id = $this->getHTMLId($repeatCounter);
		$class = 'fabrikinput inputbox ' . $params->get('bootstrap_class', '');
		$disabled = '';

		if (count($tmp) == 1)
		{
			$class .= ' readonly';

			// Selects don't have readonly properties !
		}

		$w = new FabrikWorker;
		$default = $w->parseMessageForPlaceHolder($default);
		$displayType = $params->get('cdd_display_type', 'dropdown');
		$html = array();

		if ($this->canUse())
		{
			// $$$ rob display type not set up in parameters as not had time to test fully yet
			switch ($displayType)
			{
				case 'checkbox':
					$this->renderCheckBoxList($data, $repeatCounter, $html, $tmp, $default);
					$defaultLabel = implode("\n", $html);
					break;
				case 'radio':
					$this->renderRadioList($data, $repeatCounter, $html, $tmp, $default);
					$defaultLabel = implode("\n", $html);
					break;
				case 'multilist':
					$this->renderMultiSelectList($data, $repeatCounter, $html, $tmp, $default);
					$defaultLabel = implode("\n", $html);
					break;
				case 'auto-complete':
					$this->renderAutoComplete($data, $repeatCounter, $html, $default);
					break;
				default:
				case 'dropdown':
					// Jaanus: $maxWidth to avoid drop-downs become too large (when choosing options they would still be of their full length
					$maxWidth = $params->get('max-width', '') === '' ? '' : ' style="max-width:' . $params->get('max-width') . ';"';
					$advancedClass = $this->getAdvancedSelectClass();
					$attributes = 'class="' . $class . ' ' . $advancedClass . '" ' . $disabled . ' size="1"' . $maxWidth;
					$html[] = JHTML::_('select.genericlist', $tmp, $name, $attributes, 'value', 'text', $default, $id);
					break;
			}

			$html[] = $this->loadingImg;
		}

		if (!$this->isEditable())
		{
			if ($params->get('cascadingdropdown_readonly_link') == 1)
			{
				$listId = (int) $params->get('cascadingdropdown_table');

				if ($listId !== 0)
				{
					$query = $db->getQuery(true);
					$query->select('form_id')->from('#__{package}_lists')->where('id = ' . $listId);
					$db->setQuery($query);
					$popupFormId = $db->loadResult();
					$url = 'index.php?option=com_fabrik&view=details&formid=' . $popupFormId . '&listid=' . $listId . '&rowid=' . $defaultValue;
					$defaultLabel = '<a href="' . JRoute::_($url) . '">' . $defaultLabel . '</a>';
				}
			}

			return $defaultLabel;
		}

		$html[] = $this->renderDescription($tmp, $default);

		return implode("\n", $html);
	}

	/**
	 * Add the description to the element's form HTML
	 *
	 * @param   array  $options  Select options
	 * @param   array  $default  Default values
	 *
	 * @return  string
	 */
	protected function renderDescription($options = array(), $default = array())
	{
		$params = $this->getParams();

		if ($params->get('cdd_desc_column', '') !== '')
		{
			$layout = $this->getLayout('form-description');
			$displayData = new stdClass;
			$displayData->opts = $options;
			$displayData->default = FArrayHelper::getValue($default, 0);
			$displayData->showPleaseSelect = $this->showPleaseSelect();

			return $layout->render($displayData);
		}

		return '';
	}

	/**
	 * Get the display type (list,checkbox,multiselect etc.)
	 *
	 * @since  3.0.7
	 *
	 * @return  string
	 */
	protected function getDisplayType()
	{
		return $this->getParams()->get('cdd_display_type', 'dropdown');
	}

	/**
	 * Get a list of the HTML options used in the database join drop down / radio buttons
	 *
	 * @param   array  $data           From current record (when editing form?)
	 * @param   int    $repeatCounter  Repeat group counter
	 * @param   bool   $incWhere       Do we include custom where in query
	 * @param   array  $opts           Additional options passed into _getOptionVals()
	 *
	 * @return  array	option objects
	 */
	protected function _getOptions($data = array(), $repeatCounter = 0, $incWhere = true, $opts = array())
	{
		$tmp = $this->_getOptionVals($data, $repeatCounter);

		return $tmp;
	}

	/**
	 * Gets the options for the drop down - used in package when forms update
	 *
	 * @return  void
	 */
	public function onAjax_getOptions()
	{
		$input = $this->app->input;
		$filterView = $input->get('filterview', '');
		$this->loadMeForAjax();

		/**
		 * $$$ hugh -added test for $filterView, and only do filter stuff if we are being
		 * called in a filter context, not in a regular form display context.
		 */

		if (!empty($filterView) && $this->getFilterBuildMethod() == 1)
		{
			// Get distinct records which have already been selected: http://fabrikar.com/forums/showthread.php?t=30450
			$listModel = $this->getListModel();
			$db = $listModel->getDb();
			$obs = $this->getWatchElement();
			$obsName = $obs->getFullName(false, false);

			// From a filter...
			if ($input->get('fabrik_cascade_ajax_update') == 1)
			{
				$obsValue = $input->get('v', array(), 'array');
			}
			else
			{
				// Standard
				$obsValue = (array) $input->get($obs->getFullName(true, false) . '_raw');
			}

			foreach ($obsValue as &$v)
			{
				$v = $db->quote($v);
			}

			$where = $obsName . ' IN (' . implode(',', $obsValue) . ')';
			$opts = array('where' => $where);
			$input->set('fabrik_storesessionfilters', false);
			$input->set('incfilters', true);
			$ids = $listModel->getColumnData($this->getFullName(false, false), true, $opts);
			$key = $this->queryKey();

			if (is_array($ids))
			{
				array_walk($ids, function(&$val) {
					$db = JFactory::getDbo();
					$val = $db->quote($val);
				});
				$this->autocomplete_where = empty($ids) ? '1 = -1' : $key . ' IN (' . implode(',', $ids) . ')';
			}
		}

		$filter = JFilterInput::getInstance();
		$data = $filter->clean($_POST, 'array');
		$opts = $this->_getOptionVals($data);
		$this->_replaceAjaxOptsWithDbJoinOpts($opts);

		echo json_encode($opts);
	}

	/**
	 * Test for db join element - if so update option labels with related join labels
	 *
	 * @param   array  &$opts  standard options
	 *
	 * @return  void
	 */
	protected function _replaceAjaxOptsWithDbJoinOpts(&$opts)
	{
		$groups = $this->getFormModel()->getGroupsHiarachy();
		$watch = $this->getWatchFullName();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$fullName = $elementModel->getFullName();

				if ($fullName == $watch)
				{
					/**
					 * $$$ hugh - not sure what this is for, but changed class name to 3.x name,
					 * as it was still set to the old 2.1 naming.
					 */

					if (get_parent_class($elementModel) == 'plgFabrik_ElementDatabasejoin')
					{
						$data = array();
						/** @var plgFabrik_ElementDatabasejoin $elementModel */
						$joinOpts = $elementModel->_getOptions($data);
					}

					/**
					 * $$$ hugh - I assume we can break out of both foreach now, as there shouldn't
					 * be more than one match for the $watch element.
					 */
					break 2;
				}
			}
		}

		if (isset($joinOpts))
		{
			$matrix = array();

			foreach ($joinOpts as $j)
			{
				$matrix[$j->value] = $j->text;
			}

			foreach ($opts as &$opt)
			{
				if (array_key_exists($opt->text, $matrix))
				{
					$opt->text = $matrix[$opt->text];
				}
			}
		}
	}

	/**
	 * Get array of option values
	 *
	 * @param   array  $data           Data
	 * @param   int    $repeatCounter  Repeat group counter
	 * @param   bool   $incWhere       Do we add custom where statement into sql
	 * @param   array  $opts           Additional options passed into buildQuery()
	 *
	 * @return  array	option values
	 */
	protected function _getOptionVals($data = array(), $repeatCounter = 0, $incWhere = true, $opts = array())
	{
		$params = $this->getParams();

		if (!isset($this->optionVals))
		{
			$this->optionVals = array();
		}

		$db = $this->getDb();
		$opts = array();
		$opts['repeatCounter'] = $repeatCounter;
		$sql = $this->buildQuery($data, $incWhere, $opts);
		$sqlKey = (string) $sql;
		$sqlKey .= $this->isEditable() ? '0' : '1';

		$eval = $params->get('cdd_join_label_eval', '');

		if (trim($eval) === '' && array_key_exists($sqlKey, $this->optionVals))
		{
			return $this->optionVals[$sqlKey];
		}

		$db->setQuery($sql);
		FabrikHelperHTML::debug((string) $db->getQuery(), 'cascadingdropdown _getOptionVals');
		$this->optionVals[$sqlKey] = $db->loadObjectList();

		if (trim($eval) !== '')
		{
			foreach ($this->optionVals[$sqlKey] as $key => &$opt)
			{
				// Allow removing an option by returning false
				if (eval($eval) === false)
				{
					unset($this->optionVals[$sqlKey][$key]);
				}
			}
		}

		/*
		 * If it's a filter, need to use filterSelectLabel() regardless of showPleaseSelect()
		 * (should probably shift this logic into showPleaseSelect, and have that just do this
		 * test, and return the label to use.
		 */
		$filterView = $this->app->input->get('filterview', '');

		if ($filterView == 'table')
		{
			array_unshift($this->optionVals[$sqlKey], JHTML::_('select.option', $params->get('cascadingdropdown_noselectionvalue', ''), $this->filterSelectLabel()));
		}
		else
		{
			if ($this->showPleaseSelect())
			{
				array_unshift($this->optionVals[$sqlKey], $this->selectOption());
			}
		}

		// Remove tags from labels
		if ($this->canUse() && in_array($this->getDisplayType(), array('multilist', 'dropdown')))
		{
			foreach ($this->optionVals[$sqlKey] as $key => &$opt)
			{
				$opt->text = strip_tags($opt->text);
			}
		}

		return $this->optionVals[$sqlKey];
	}

	/**
	 * Create the select option for drop-down
	 *
	 *  @return  object
	 */
	private function selectOption()
	{
		$params = $this->getParams();
		return JHTML::_('select.option', $params->get('cascadingdropdown_noselectionvalue', ''), $this->_getSelectLabel());
	}

	/**
	 * Do you add a please select option to the cdd list
	 *
	 * @since 3.0b
	 *
	 * @return  bool
	 */
	protected function showPleaseSelect()
	{
		$params = $this->getParams();

		if (!$this->canUse())
		{
			return false;
		}

		if (!$this->isEditable())
		{
			return false;
		}

		if (in_array($this->getDisplayType(), array('checkbox', 'multilist', 'radio')))
		{
			return false;
		}

		return (bool) $params->get('cascadingdropdown_showpleaseselect', true);
	}

	/**
	 * Get the full name of the element to observe. When this element changes
	 * state, the cdd should perform an ajax lookup to update its options
	 *
	 * @param   bool  $repeat  include repeat []
	 * @return  string
	 */
	protected function getWatchFullName($repeat = true)
	{
		$elementModel = $this->getWatchElement();

		return $elementModel->getFullName(true, $repeat);
	}

	/**
	 * Get the HTML id for the watch element
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  string
	 */
	protected function getWatchId($repeatCounter = 0)
	{
		$elementModel = $this->getWatchElement();

		return $elementModel->getHTMLId($repeatCounter);
	}

	/**
	 * Get the element to watch. Changes to this element will trigger the cdd's lookup
	 *
	 * @return  plgFabrik_Element
	 */
	protected function getWatchElement()
	{
		if (!isset($this->watchElement))
		{
			$watch = $this->getParams()->get('cascadingdropdown_observe');

			if ($watch == '')
			{
				throw new RuntimeException('No watch element set up for cdd' . $this->getElement()->id, 500);
			}

			$this->watchElement = $this->getFormModel()->getElement($watch, true);

			if (!$this->watchElement)
			{
				// This element is a child element, so $watch is in the parent element (in another form)
				$pluginManager = FabrikWorker::getPluginManager();
				$parent = $pluginManager->getElementPlugin($watch);

				// These are the possible watch elements
				$children = $parent->getElementDescendents();

				// Match the possible element ids with the current form's element ids
				$elementIds = $this->getFormModel()->getElementIds();
				$matched = array_values(array_intersect($elementIds, $children));

				// Load the matched element
				$this->watchElement = $pluginManager->getElementPlugin($matched[0]);

				if (!$this->watchElement)
				{
					throw new RuntimeException('No watch element found for cdd: ' . $this->getElement()->id . ', trying to find children of: ' . $watch, 500);
				}
			}
		}

		return $this->watchElement;
	}

	/**
	 * Create the sql query used to get the possible selectable value/labels used to create
	 * the drop-down/checkboxes
	 *
	 * @param   array  $data      data
	 * @param   bool   $incWhere  include where
	 * @param   array  $opts      query options
	 *
	 * @return  mixed	JDatabaseQuery or false if query can't be built
	 */
	protected function buildQuery($data = array(), $incWhere = true, $opts = array())
	{
		$input = $this->app->input;
		$sig = isset($this->autocomplete_where) ? $this->autocomplete_where . '.' . $incWhere : $incWhere;
		$sig .= '.' . serialize($opts);
		$repeatCounter = FArrayHelper::getValue($opts, 'repeatCounter', 0);
		$db = FabrikWorker::getDbo();

		if (isset($this->sql[$sig]))
		{
			return $this->sql[$sig];
		}

		$params = $this->getParams();
		$watch = $this->getWatchFullName(false);
		$whereVal = null;
		$groups = $this->getFormModel()->getGroupsHiarachy();
		$formModel = $this->getFormModel();
		$watchElement = $this->getWatchElement();

		// Test for ajax update
		if ($input->get('fabrik_cascade_ajax_update') == 1)
		{
			// Allow for multiple values - e.g. when observing a db join rendered as a checkbox
			$whereVal = $input->get('v', array(), 'array');
		}
		else
		{
			if (isset($formModel->data) || isset($formModel->formData))
			{
				$watchOpts = array('raw' => 1);

				if (isset($formModel->data))
				{
					if ($watchElement->isJoin())
					{
						$id = $watchElement->getFullName(true, false) . '_id';
						$whereVal = FArrayHelper::getValue($formModel->data, $id);
					}
					else
					{
						$whereVal = $watchElement->getValue($formModel->data, $repeatCounter, $watchOpts);
					}
				}
				else
				{
					/*
					 * If we're running onAfterProcess, formData will have short names in it, which means getValue()
					 * won't find the watch element, as it's looking for full names.  So if it exists, use formDataWithTableName.
					 */
					if (is_array($formModel->formDataWithTableName) && array_key_exists($watch, $formModel->formDataWithTableName))
					{
						$whereVal = $watchElement->getValue($formModel->formDataWithTableName, $repeatCounter, $watchOpts);
					}
					else
					{
						$whereVal = $watchElement->getValue($formModel->formData, $repeatCounter, $watchOpts);
					}
				}

				// $$$ hugh - if not set, set to '' to avoid selecting entire table
				if (!isset($whereVal))
				{
					$whereVal = '';
				}
			}
			else
			{
				// $$$ hugh - probably rendering table view ...
				$watchRaw = $watch . '_raw';

				if (isset($data[$watchRaw]))
				{
					$whereVal = $data[$watchRaw];
				}
				else
				{
					// $$$ hugh ::sigh:: might be coming in via swapLabelsForvalues in pre_process phase
					// and join array in data will have been flattened.  So try regular element name for watch.
					$noJoinWatchRaw = $watchElement->getFullName(true, false) . '_raw';

					if (isset($data[$noJoinWatchRaw]))
					{
						$whereVal = $data[$noJoinWatchRaw];
					}
					else
					{
						// $$$ hugh - if watched element has no value, we have been selecting all rows from CDD table
						// but should probably select none.

						// Unless its a cdd autocomplete list filter - seems sensible to populate that with the values matching the search term
						if ($this->app->input->get('method') !== 'autocomplete_options')
						{
							$whereVal = '';
						}
					}
				}
			}
		}

		$where = '';
		$whereKey = $params->get('cascadingdropdown_key');

		if (!is_null($whereVal) && $whereKey != '')
		{
			$whereBits = strstr($whereKey, '___') ? explode('___', $whereKey) : explode('.', $whereKey);
			$whereKey = array_pop($whereBits);

			if (is_array($whereVal))
			{
				foreach ($whereVal as &$v)
				{

					// Jaanus: Solving bug: imploded arrays when chbx in repeated group

					if (is_array($v))
					{
						foreach ($v as &$vchild)
						{
							$vchild = FabrikString::safeQuote($vchild);
						}
						$v = implode(',', $v);
					}
					else
					{
						$v = FabrikString::safeQuote($v);
					}
				}

				// Jaanus: if count of where values is 0 or if there are no letters or numbers, only commas in imploded array

				$where .= count($whereVal) == 0 || !preg_match('/\w/', implode(',', $whereVal)) ? '4 = -4' : $whereKey . ' IN ' . '(' . str_replace(',,', ',\'\',', implode(',', $whereVal)) . ')';
			}
			else
			{
				$where .= $whereKey . ' = ' . $db->quote($whereVal);
			}
		}

		$filter = $params->get('cascadingdropdown_filter');

		if (!empty($this->autocomplete_where))
		{
			$where .= $where !== '' ? ' AND ' . $this->autocomplete_where : $this->autocomplete_where;
		}

		/* $$$ hugh - temporary hack to work around this issue:
		 * http://fabrikar.com/forums/showthread.php?p=71288#post71288
		 * ... which is basically that if they are using {placeholders} in their
		 * filter query, there's no point trying to apply that filter if we
		 * aren't in form view, for instance when building a search filter
		 * or in table view when the cdd is in a repeat group, 'cos there won't
		 * be any {placeholder} data to use.
		 * So ... for now, if the filter contains {...}, and view!=form ... skip it
		 * $$$ testing fix for the bandaid, ccd JS should not be submitting data from form
		 */
		if (trim($filter) != '')
		{
			$where .= ($where == '') ? ' ' : ' AND ';
			$where .= $filter;
		}

		$w = new FabrikWorker;

		// $$$ hugh - add some useful stuff to search data
		$placeholders = is_null($whereVal) ? array() : array('whereval' => $whereVal, 'wherekey' => $whereKey);
		$join = $this->getJoin();
		$where = $this->parseThisTable($where, $join);

		$data = array_merge($data, $placeholders);
		$where = $w->parseMessageForRepeats($where, $data, $this, $repeatCounter);
		$where = $w->parseMessageForPlaceHolder($where, $data);
		$table = $this->getDbName();
		$key = $this->queryKey();
		$orderBy = 'text';
		$tables = $this->getFormModel()->getLinkedFabrikLists($params->get('join_db_name'));
		$listModel = JModelLegacy::getInstance('List', 'FabrikFEModel');
		$val = $params->get('cascadingdropdown_label_concat');

		if (!empty($val) && $this->app->input->get('override_join_val_column_concat', '0') !== '1')
		{
			$val = $this->parseThisTable($val, $join);
			$val = $w->parseMessageForPlaceHolder($val, $data);
			$val = 'CONCAT_WS(\'\', ' . $val . ')';
			$orderBy = $val;
		}
		else
		{
			$val = FabrikString::safeColName($params->get($this->labelParam));
			$val = preg_replace("#^`($table)`\.#", $db->qn($join->table_join_alias) . '.', $val);

			foreach ($tables as $tid)
			{
				$listModel->setId($tid);
				$listModel->getTable();
				$formModel = $this->getFormModel();
				$formModel->getGroupsHiarachy();
				$orderBy = $val;

				// See if any of the tables elements match the db joins val/text
				foreach ($groups as $groupModel)
				{
					$elementModels = $groupModel->getPublishedElements();

					foreach ($elementModels as $elementModel)
					{
						$element = $elementModel->element;

						if ($element->name == $val)
						{
							$val = $elementModel->modifyJoinQuery($val);
						}
					}
				}
			}
		}

		$val = str_replace($db->qn($table), $db->qn($join->table_join_alias), $val);
		$query = $db->getQuery(true);
		$query->select('DISTINCT(' . $key . ') AS value, ' . $val . 'AS text');
		$desc = $params->get('cdd_desc_column', '');

		if ($desc !== '')
		{
			$query->select(FabrikString::safeColName($desc) . ' AS description');
		}

		$query->from($db->qn($table) . ' AS ' . $db->qn($join->table_join_alias));
		$query = $this->buildQueryJoin($query);
		$where = FabrikString::rtrimword($where);

		if ($where !== '')
		{
			$query->where($where);
		}

		if (!JString::stristr($where, 'order by'))
		{
			$query->order($orderBy . ' ASC');
		}

		$this->sql[$sig] = $query;
		FabrikHelperHTML::debug((string) $this->sql[$sig]);

		return $this->sql[$sig];
	}

	/**
	 * Get the the field name used for the foo AS value part of the query
	 *
	 * @since   3.0.8
	 *
	 * @return  string
	 */
	protected function queryKey()
	{
		$db = FabrikWorker::getDbo();
		$join = $this->getJoin();
		$table = $this->getDbName();
		$params = $this->getParams();
		$key = FabrikString::safeColName($params->get('cascadingdropdown_id'));
		$key = str_replace($db->qn($table), $db->qn($join->table_join_alias), $key);

		return $key;
	}

	/**
	 * Get the element name or concat statement used to build the dropdown labels or
	 * table data field
	 *
	 * @return  string
	 */
	protected function getLabelOrConcatVal()
	{
		$params = $this->getParams();
		$join = $this->getJoin();

		if ($params->get('cascadingdropdown_label_concat') == '' || $this->app->input->get('override_join_val_column_concat', '0') === '1')
		{
			return $this->getLabelParamVal();
		}
		else
		{
			$w = new FabrikWorker;
			$val = $this->parseThisTable($params->get('cascadingdropdown_label_concat'), $join);
			$val = $w->parseMessageForPlaceHolder($val, array());

			return 'CONCAT_WS(\'\', ' . $val . ')';
		}
	}

	/**
	 * Load connection object
	 *
	 * @return  object	connection table
	 */
	protected function loadConnection()
	{
		$params = $this->getParams();
		$id = $params->get('cascadingdropdown_connection');
		$cid = $this->getlistModel()->getConnection()->getConnection()->id;

		if ($cid == $id)
		{
			$this->cn = $this->getlistModel()->getConnection();
		}
		else
		{
			$this->cn = JModelLegacy::getInstance('Connection', 'FabrikFEModel');
			$this->cn->setId($id);
		}

		return $this->cn->getConnection();
	}

	/**
	 * Get the cdd's database name
	 *
	 * @throws RuntimeException
	 *
	 * @return  string|boolean  Db name or false if unable to get name
	 */
	protected function getDbName()
	{
		if (!isset($this->dbname) || $this->dbname == '')
		{
			$params = $this->getParams();
			$id = $params->get('cascadingdropdown_table');

			if ($id == '')
			{
				throw new RuntimeException('Unable to get table for cascading dropdown (ignore if creating a new element)');
			}

			$db = FabrikWorker::getDbo(true);
			$query = $db->getQuery(true);
			$query->select('db_table_name')->from('#__{package}_lists')->where('id = ' . (int) $id);
			$db->setQuery($query);
			$this->dbname = $db->loadResult();
		}

		return $this->dbname;
	}

	/**
	 * If used as a filter add in some JS code to watch observed filter element's changes
	 * when it changes update the contents of this elements dd filter's options
	 *
	 * @param   bool    $normal     is the filter a normal (true) or advanced filter
	 * @param   string  $container  container
	 *
	 * @return  void
	 */
	public function filterJS($normal, $container)
	{
		$element = $this->getElement();
		$observerId = $this->getWatchId();
		$observerId .= 'value';
		$formModel = $this->getFormModel();
		$formId = $formModel->get('id');

		// 3.1 Cdd filter set up elsewhere
		if ($element->filter_type == 'dropdown')
		{
			$params = $this->getParams();
			$default = $this->getDefaultFilterVal($normal);

			if ($default === '')
			{
				$default = $params->get('cascadingdropdown_noselectionvalue', '');
			}

			$filterId = $this->getHTMLId() . 'value';
			FabrikHelperHTML::script('plugins/fabrik_element/cascadingdropdown/filter.js');
			$opts = new stdClass;
			$opts->formid = $formId;
			$opts->filterid = $filterId;
			$opts->elid = $this->getId();
			$opts->def = $default;
			$opts->advanced = $this->getAdvancedSelectClass();
			$opts->noselectionvalue = $params->get('cascadingdropdown_noselectionvalue', '');
			$opts->filterobj = 'Fabrik.filter_' . $container;
			$opts->lang           = FabrikWorker::getMultiLangURLCode();
			$opts = json_encode($opts);

			return "Fabrik.filter_{$container}.addFilter('$element->plugin', new CascadeFilter('$observerId', $opts));\n";
		}
	}

	/**
	 * Get the field name for the joined tables' pk
	 *
	 * @since  3.0.7
	 *
	 * @return  string
	 */
	protected function getJoinValueFieldName()
	{
		$params = $this->getParams();
		$full = $params->get('cascadingdropdown_id');

		return FabrikString::shortColName($full);
	}

	/**
	 * Get the observed element's element model
	 *
	 * @return mixed element model or false
	 */
	protected function _getObserverElement()
	{
		$params = $this->getParams();
		$observer = $params->get('cascadingdropdown_observe');
		$formModel = $this->getFormModel();
		$groups = $formModel->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getMyElements();

			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();

				if ($observer == $element->name)
				{
					return $elementModel;
				}
			}
		}

		return false;
	}

	/**
	 * Run before the element is saved
	 *
	 * @param   object  &$row  that is going to be updated
	 *
	 * @return null
	 */
	public function beforeSave(&$row)
	{
		/*
		 * do nothing, just here to prevent join element method from running
		 * instead (which removed join table
		 * entry if not pluginname==databasejoin
		 */
		return true;
	}

	/**
	 * Get an array of element html ids and their corresponding
	 * js events which trigger a validation.
	 * Examples of where this would be overwritten include time date element with time field enabled
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  array  html ids to watch for validation
	 */
	public function getValidationWatchElements($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$ar = array('id' => $id, 'triggerEvent' => 'change');

		return array($ar);
	}

	/**
	 * When copying elements from an existing table
	 * once a copy of all elements has been made run them through this method
	 * to ensure that things like watched element id's are updated
	 *
	 * @param   array  $elementMap  copied element ids (keyed on original element id)
	 *
	 * @throws RuntimeException
	 *
	 * @return  mixed JError:void
	 */
	public function finalCopyCheck($elementMap)
	{
		$element = $this->getElement();
		unset($this->params);
		$params = $this->getParams();
		$oldObserverId = $params->get('cascadingdropdown_observe');

		if (!array_key_exists($oldObserverId, $elementMap))
		{
			throw new RuntimeException('cascade dropdown: no id ' . $oldObserverId . ' found in ' . implode(",", array_keys($elementMap)));
		}

		$newObserveId = $elementMap[$oldObserverId];
		$params->set('cascadingdropdown_observe', $newObserveId);

		// Save params
		$element->params = $params->toString();

		if (!$element->store())
		{
			return JError::raiseWarning(500, $element->getError());
		}
	}

	/**
	 * Return an array of parameter names which should not get updated if a linked element's parent is saved
	 * notably any parameter which references another element id should be returned in this array
	 * called from admin element model updateChildIds()
	 * see cascading-drop-down element for example
	 *
	 * @return  array	parameter names to not alter
	 */
	public function getFixedChildParameters()
	{
		return array('cascadingdropdown_observe');
	}

	/**
	 * Get select option label
	 *
	 * @param  bool  $filter  get alt label for filter, if present using :: splitter
	 *
	 * @return  string
	 */
	protected function _getSelectLabel($filter = false)
	{
		$params = $this->getParams();
		$label = $params->get('cascadingdropdown_noselectionlabel');

		if (strstr($label, '::'))
		{
			$labels = explode('::', $label);
			$label = $filter ? $labels[1] : $labels[0];
		}

		if (!$filter && $label == '')
		{
			$label = 'COM_FABRIK_PLEASE_SELECT';
		}

		return FText::_($label);
	}

	/**
	 * Should the 'label' field be quoted.  Overridden by databasejoin and extended classes,
	 * which may use a CONCAT'ed label which mustn't be quoted.
	 *
	 * @since	3.0.6
	 *
	 * @return boolean
	 */
	protected function quoteLabel()
	{
		$params = $this->getParams();

		return $params->get('cascadingdropdown_label_concat', '') == '';
	}

	/**
	 * If filterValueList_Exact incjoin value = false, then this method is called
	 * to ensure that the query produced in filterValueList_Exact contains at least the database join element's
	 * join
	 *
	 * @return  string  required join text to ensure exact filter list code produces a valid query.
	 */
	protected function buildFilterJoin()
	{
		$joinTable = FabrikString::safeColName($this->getDbName());
		$join = $this->getJoin();
		$joinTableName = FabrikString::safeColName($join->table_join_alias);
		$joinKey = $this->getJoinValueColumn();
		$elName = FabrikString::safeColName($this->getFullName(true, false));

		return 'INNER JOIN ' . $joinTable . ' AS ' . $joinTableName . ' ON ' . $joinKey . ' = ' . $elName;
	}

	/**
	 * Use in list model storeRow() to determine if data should be stored.
	 * Currently only supported for db join elements whose values are default values
	 * avoids casing '' into 0 for int fields
	 *
	 * Extended this from dbjoin element as an empty string should be possible in cdd, if no options selected.
	 * Otherwise previously selected values are kept
	 *
	 * @param   array  $data  Data being inserted
	 * @param   mixed  $val   Element value to insert into table
	 *
	 * @since   3.0.7
	 *
	 * @return boolean
	 */
	public function dataIsNull($data, $val)
	{
		return false;
	}

	/**
	 * Get drop-down filter select label
	 *
	 * @return  string
	 */
	protected function filterSelectLabel()
	{
		$params = $this->getParams();
		$label = $this->_getSelectLabel(true);

		if (empty($label))
		{
			$label = $params->get('filter_required') == 1 ? FText::_('COM_FABRIK_PLEASE_SELECT') : FText::_('COM_FABRIK_FILTER_PLEASE_SELECT');
		}

		return $label;
	}

	/**
	 * Create an array of label/values which will be used to populate the elements filter dropdown
	 * returns all possible options
	 *
	 * @param   bool    $normal     do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  table name to use - defaults to element's current table
	 * @param   string  $label      field to use, defaults to element name
	 * @param   string  $id         field to use, defaults to element name
	 * @param   bool    $incjoin    include join
	 *
	 * @return  array	filter value and labels
	 */
	protected function filterValueList_All($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		/**
		 * Don't build filter options on page build, it gets done via AJAX from the page.
		 */
		return array();
	}

	/**
	 * Create an array of label/values which will be used to populate the elements filter dropdown
	 * returns only data found in the table you are filtering on
	 *
	 * @param   bool    $normal     do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  table name to use - defaults to element's current table
	 * @param   string  $label      field to use, defaults to element name
	 * @param   string  $id         field to use, defaults to element name
	 * @param   bool    $incjoin    include join
	 *
	 * @return  array	filter value and labels
	 */
	protected function filterValueList_Exact($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		/**
		 * Don't bother building a filter list on page load, that'll get done via AJAX from the page
		 */
		return array();
	}


}
