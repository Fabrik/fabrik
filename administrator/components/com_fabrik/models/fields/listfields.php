<?php
/**
 * Renders a list of elements found in a fabrik list
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');
require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

/**
 * Renders a list of elements found in a fabrik list
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */
class JFormFieldListfields extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 */
	protected $name = 'Listfields';

	/**
	 * Objects resulting from this elements queries - keyed on identifying hash
	 *
	 * @var  array
	 */
	protected $results = null;

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string    The field input markup.
	 */

	protected function getInput()
	{
		if (is_null($this->results))
		{
			$this->results = array();
		}

		$app           = JFactory::getApplication();
		$input         = $app->input;
		$controller    = $input->get('view', $input->get('task'));
		$formModel     = false;
		$filter        = $this->getAttribute('filter');
		$pluginFilters = trim($filter) == '' ? array() : explode('|', $filter);
		$connection    = $this->getAttribute('connection');
		/*
		 * 27/08/2011 - changed from default table-element to id - for juser form plugin - might cause havoc
		 * else where but loading elements by id as default seems more robust (and is the default behaviour in f2.1
		 */
		$valueFormat    = (string) $this->getAttribute('valueformat', 'id');
		$onlyListFields = (int) $this->getAttribute('onlylistfields', 0);
		$showRaw        = FabrikWorker::toBoolean($this->getAttribute('raw', false), false);
		$labelMethod    = (string) $this->getAttribute('label_method');
		$noJoins        = FabrikWorker::toBoolean($this->getAttribute('nojoins', false), false);
		$mode           = (string) $this->getAttribute('mode', false);
		$useStep        = FabrikWorker::toBoolean($this->getAttribute('usestep', false), false);

		switch ($controller)
		{
			case 'validationrule':
				$res = $this->_validationOptions();
				break;
			case 'visualization':
			case 'element':
				$res = $this->_elementOptions($connection);
				break;
			case 'listform':
			case 'list':
			case 'module':
			case 'item':
				// Menu item
				$res = $this->_listOptions($controller, $valueFormat, $useStep, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins);
				break;
			case 'form':
				$res = $this->_formOptions($valueFormat, $useStep, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins);
				break;
			case 'group':
				$res = $this->_groupOptions($useStep, $valueFormat, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins);
				break;
			default:
				return FText::_('The ListFields element is only usable by lists and elements');
				break;
		}

		$return = '';

		if (is_array($res))
		{
			$aEls = $this->_formatOptions($res, $valueFormat);

			// For pk fields - we are no longer storing the key with '`' as that's mySQL specific
			$this->value = str_replace('`', '', $this->value);

			// Some elements were stored as names but subsequently changed to ids (need to check for old values an substitute with correct ones)
			if ($valueFormat == 'id' && !is_numeric($this->value) && $this->value != '')
			{
				if ($formModel)
				{
					$elementModel = $formModel->getElement($this->value);
					$this->value  = $elementModel ? $elementModel->getId() : $this->value;
				}
			}

			if ($mode === 'gui')
			{
				$this->js($aEls);
				$return = $this->gui();
			}
			else
			{
				$return = JHTML::_('select.genericlist', $aEls, $this->name, 'class="inputbox" size="1" ', 'value', 'text', $this->value, $this->id);
				$return .= '<img style="margin-left:10px;display:none" id="' . $this->id
					. '_loader" src="components/com_fabrik/images/ajax-loader.gif" alt="' . FText::_('LOADING') . '" />';
			}
		}

		FabrikHelperHTML::framework();
		FabrikHelperHTML::iniRequireJS();

		return $return;
	}

	/**
	 * Format options
	 *
	 * @param array $res
	 * @param       $valueFormat
	 *
	 * @return array
	 * @throws Exception
	 */
	private function _formatOptions(array $res, $valueFormat)
	{
		$aEls       = array();
		$input      = JFactory::getApplication()->input;
		$controller = $input->get('view', $input->get('task'));

		if ($controller == 'element')
		{
			foreach ($res as $o)
			{
				$s = new stdClass;

				// Element already contains correct key
				if ($controller != 'element')
				{
					$s->value = $valueFormat == 'tableelement' ? $o->table_name . '.' . $o->text : $o->value;
				}
				else
				{
					$s->value = $o->value;
				}

				$s->text = FabrikString::getShortDdLabel($o->text);
				$aEls[]  = $s;
			}
		}
		else
		{
			foreach ($res as &$o)
			{
				$o->text = FabrikString::getShortDdLabel($o->text);
			}

			$aEls = $res;
		}

		// Paul - Prepend rather than append "none" option.
		array_unshift($aEls, JHTML::_('select.option', '', '-'));

		return $aEls;
	}

	/**
	 * Get validation options
	 *
	 * @return array
	 * @throws Exception
	 */
	private function _validationOptions()
	{
		$input         = JFactory::getApplication()->input;
		$id            = $input->getInt('id');
		$pluginManager = FabrikWorker::getPluginManager();
		$elementModel  = $pluginManager->getElementPlugin($id);
		$element       = $elementModel->getElement();

		return $this->loadFromGroupId($element->group_id);
	}

	/**
	 * Get element options
	 *
	 * @param $connection
	 *
	 * @return array
	 */
	private function _elementOptions($connection)
	{
		if ($connection == '')
		{
			$groupId = isset($this->form->rawData) ? FArrayHelper::getValue($this->form->rawData, 'group_id', 0)
				: $this->form->getValue('group_id');
			$res     = $this->loadFromGroupId($groupId);
		}
		else
		{
			$this->js();
			$o             = new stdClass;
			$o->table_name = '';
			$o->name       = '';
			$o->value      = '';
			$o->text       = FText::_('COM_FABRIK_SELECT_A_TABLE_FIRST');
			$res[]         = $o;
		}

		return $res;
	}

	/**
	 * Get list options
	 *
	 * @param $controller
	 * @param $valueFormat
	 * @param $useStep
	 * @param $onlyListFields
	 * @param $showRaw
	 * @param $pluginFilters
	 * @param $labelMethod
	 * @param $noJoins
	 *
	 * @return array|void
	 * @throws Exception
	 */
	private function _listOptions($controller, $valueFormat, $useStep, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins)
	{
		$app = JFactory::getApplication();

		if ($controller === 'item')
		{
			$id = $this->form->getValue('request.listid');
		}
		else
		{
			$id = $this->form->getValue('id');
		}

		if (!isset($this->form->model))
		{
			if (!in_array($controller, array('item', 'module')))
			{
				// Seems to work anyway in the admin module page - so lets not raise notice
				$app->enqueueMessage('Model not set in listfields field ' . $this->id, 'notice');
			}

			return;
		}

		/** @var FabrikFEModelList $listModel */
		$listModel = $this->form->model;

		if ($id !== 0)
		{
			$formModel = $listModel->getFormModel();
			$valField  = $valueFormat == 'tableelement' ? 'name' : 'id';
			$res       = $formModel->getElementOptions($useStep, $valField, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins);
		}
		else
		{
			$res = array();
		}

		return $res;
	}

	/**
	 * Form options
	 *
	 * @param $valueFormat
	 * @param $useStep
	 * @param $onlyListFields
	 * @param $showRaw
	 * @param $pluginFilters
	 * @param $labelMethod
	 * @param $noJoins
	 *
	 * @return array
	 */
	private function _formOptions($valueFormat, $useStep, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins)
	{
		if (!isset($this->form->model))
		{
			throw new RuntimeException('Model not set in listfields field ' . $this->id);

			return;
		}

		/** @var FabrikFEModelForm $formModel */
		$formModel = $this->form->model;
		$valField  = $valueFormat == 'tableelement' ? 'name' : 'id';
		$res       = $formModel->getElementOptions($useStep, $valField, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins);

		$jsRes = $formModel->getElementOptions($useStep, $valField, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins);
		array_unshift($jsRes, JHTML::_('select.option', '', FText::_('COM_FABRIK_PLEASE_SELECT')));
		$this->js($jsRes);

		return $res;
	}

	/**
	 * Get group view options
	 *
	 * @param $useStep
	 * @param $valueFormat
	 * @param $onlyListFields
	 * @param $showRaw
	 * @param $pluginFilters
	 * @param $labelMethod
	 * @param $noJoins
	 *
	 * @return array
	 */
	private function _groupOptions($useStep, $valueFormat, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins)
	{
		$valField   = $valueFormat == 'tableelement' ? 'name' : 'id';
		$id         = $this->form->getValue('id');
		$groupModel = JModelLegacy::getInstance('Group', 'FabrikFEModel');
		$groupModel->setId($id);
		$formModel = $groupModel->getFormModel();

		return $formModel->getElementOptions($useStep, $valField, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins);
	}

	/**
	 * Get JS
	 *
	 * @param array $res
	 */
	private function js($res = array())
	{
		$at = (string) $this->getAttribute('at', 'false');

		if ($at === 'true')
		{
			FabrikHelperHTML::atWHo('textarea[data-at]', ArrayHelper::getColumn($res, 'value'));
		}

		$connection        = $this->getAttribute('connection');
		$repeat            = FabrikWorker::toBoolean($this->getAttribute('repeat', false), false);
		$repeat            = FabrikAdminElementHelper::getRepeat($this) || $repeat;
		$c                 = (int) FabrikAdminElementHelper::getRepeatCounter($this);
		$mode              = $this->getAttribute('mode');
		$connectionDd      = $repeat ? $connection . '-' . $c : $connection;
		$highlightPk       = FabrikWorker::toBoolean($this->getAttribute('highlightpk', false), false);
		$tableDd           = $this->getAttribute('table');
		$opts              = new stdClass;
		$opts->table       = ($repeat) ? 'jform_' . $tableDd . '-' . $c : 'jform_' . $tableDd;
		$opts->conn        = 'jform_' . $connectionDd;
		$opts->value       = $this->value;
		$opts->repeat      = $repeat;
		$opts->showAll     = (int) $this->getAttribute('showall', '1');
		$opts->showRaw     = FabrikWorker::toBoolean($this->getAttribute('raw', false), false);
		$opts->highlightpk = (int) $highlightPk;
		$opts->mode        = $mode;
		$opts->defaultOpts = $res;
		$opts->addBrackets = FabrikWorker::toBoolean($this->getAttribute('addbrackets', false), false);
		$opts              = json_encode($opts);
		$script            = array();
		$script[]          = "if (typeOf(FabrikAdmin.model.fields.listfields) === 'null') {";
		$script[]          = "FabrikAdmin.model.fields.listfields = {};";
		$script[]          = "}";
		$script[]          = "if (FabrikAdmin.model.fields.listfields['$this->id'] === undefined) {";
		$script[]          = "FabrikAdmin.model.fields.listfields['$this->id'] = new ListFieldsElement('$this->id', $opts);";
		$script[]          = "}";
		$script            = implode("\n", $script);

		$srcs = array(
			'Fabrik' => 'media/com_fabrik/js/fabrik.js',
			'ListFields' => 'administrator/components/com_fabrik/models/fields/listfields.js'
		);
		FabrikHelperHTML::script($srcs, $script);
	}

	/**
	 * Build GUI for adding in elements
	 *
	 * @return  string  Textarea GUI
	 */
	private function gui()
	{
		$str         = array();
		$modeField   = (string) $this->getAttribute('modefield', 'textarea');
		$class       = $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';
		$placeholder = $this->element['placeholder'] ? ' placeholder="' . (string) $this->element['placeholder'] . '"' : '';
		$at          = (string) $this->getAttribute('at', 'false');

		$rows = $this->element['rows'] ? $this->element['rows'] : 3;

		if ($modeField === 'textarea')
		{
			$str[] = '<textarea ' . $class . $placeholder . ' data-at cols="20" rows="' . $rows . '" id="' . $this->id . '" name="' . $this->name . '">' . $this->value . '</textarea>';
		}
		else
		{
			$str[] = '<input ' . $class . $placeholder . ' id="' . $this->id . '" name="' . $this->name . '" value="' . $this->value . '" />';
		}

		$str[] = $at === 'true' ? '<div style="display:none">' : '';
		$str[] = '<button class="button btn"><span class="icon-arrow-left"></span> ' . FText::_('COM_FABRIK_ADD') . '</button>';
		$str[] = '<select class="elements"></select>';
		$str[] = $at === 'true' ? '</div>' : '';

		return implode("\n", $str);
	}

	/**
	 * Load the element list from the group id
	 *
	 * @param   int $groupId Group id
	 *
	 * @since   3.0.6
	 *
	 * @return array
	 */
	protected function loadFromGroupId($groupId)
	{
		$app            = JFactory::getApplication();
		$input          = $app->input;
		$controller     = $input->get('view', $input->get('task'));
		$valueFormat    = (string) $this->getAttribute('valueformat', 'id');
		$onlyListFields = (int) $this->getAttribute('onlylistfields', 0);
		$filter         = (string) $this->getAttribute('filter', '');
		$pluginFilters  = trim($filter) == '' ? array() : explode('|', $filter);
		$labelMethod    = (string) $this->getAttribute('label_method');
		$noJoins        = FabrikWorker::toBoolean($this->getAttribute('nojoins', false), false);

		$bits    = array();
		$showRaw = FabrikWorker::toBoolean($this->getAttribute('raw', false), false);

		/** @var FabrikFEModelGroup $groupModel */
		$groupModel = JModelLegacy::getInstance('Group', 'FabrikFEModel');
		$groupModel->setId($groupId);
		$optsKey = $valueFormat == 'tableelement' ? 'name' : 'id';
		$useStep = FabrikWorker::toBoolean($this->getAttribute('usestep', false), false);
		$hash    = $controller . '.' . implode('.', $bits);

		if (array_key_exists($hash, $this->results))
		{
			$res = $this->results[$hash];
		}
		else
		{
			$res                  = $groupModel->getFormModel()->getElementOptions($useStep, $optsKey, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins);
			$this->results[$hash] = &$res;
		}

		return $res;
	}
}
