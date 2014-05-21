<?php
/**
 * Renders a list of elements found in a fabrik list
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

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
	 * @access	protected
	 * @var		string
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
	 * @return  string	The field input markup.
	 */

	protected function getInput()
	{
		if (is_null($this->results))
		{
			$this->results = array();
		}

		$app = JFactory::getApplication();
		$input = $app->input;
		$controller = $input->get('view', $input->get('task'));
		$formModel = false;
		$aEls = array();
		$pluginFilters = trim($this->element['filter']) == '' ? array() : explode('|', $this->element['filter']);
		$c = (int) FabrikAdminElementHelper::getRepeatCounter($this);
		$connection = $this->element['connection'];
		/*
		 * 27/08/2011 - changed from default table-element to id - for juser form plugin - might cause havoc
		 * else where but loading elements by id as default seems more robust (and is the default behaviour in f2.1
		 */
		$valueformat = (string) JArrayHelper::getValue($this->element, 'valueformat', 'id');
		$onlylistfields = (int) JArrayHelper::getValue($this->element, 'onlylistfields', 0);
		$showRaw = (bool) JArrayHelper::getValue($this->element, 'raw', false);
		$labelMethod = (string) JArrayHelper::getValue($this->element, 'label_method');
		$nojoins = (bool) JArrayHelper::getValue($this->element, 'nojoins', false);
		$mode = (string) JArrayHelper::getValue($this->element, 'mode', false);
		$useStep = (bool) JArrayHelper::getValue($this->element, 'usestep', false);

		switch ($controller)
		{
			case 'validationrule':
				$id = $input->getInt('id');
				$pluginManager = FabrikWorker::getPluginManager();
				$elementModel = $pluginManager->getElementPlugin($id);
				$element = $elementModel->getElement();
				$res = $this->loadFromGroupId($element->group_id);
				break;
			case 'visualization':
			case 'element':
				$repeat = FabrikAdminElementHelper::getRepeat($this) || $this->element['repeat'];

				// @TODO this seems like we could re-factor it to use the formModel class as per the table and form switches below?
				// $connectionDd = ($c === false) ? $connection : $connection . '-' . $c;
				$connectionDd = $repeat ? $connection . '-' . $c : $connection;

				if ($connection == '')
				{
					$groupId = isset($this->form->rawData) ? JArrayHelper::getValue($this->form->rawData, 'group_id', 0)
						: $this->form->getValue('group_id');
					$res = $this->loadFromGroupId($groupId);
				}
				else
				{
					$this->js();
					$o = new stdClass;
					$o->table_name = '';
					$o->name = '';
					$o->value = '';
					$o->text = FText::_('COM_FABRIK_SELECT_A_TABLE_FIRST');
					$res[] = $o;
				}
				break;
			case 'listform':
			case 'list':
			case 'module':
			case 'item':
			// Menu item
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

				$listModel = $this->form->model;

				if ($id !== 0)
				{
					$formModel = $listModel->getFormModel();
					$valfield = $valueformat == 'tableelement' ? 'name' : 'id';
					$res = $formModel->getElementOptions($useStep, $valfield, $onlylistfields, $showRaw, $pluginFilters, $labelMethod, $nojoins);
				}
				else
				{
					$res = array();
				}

				break;
			case 'form':

				if (!isset($this->form->model))
				{
					throw new RuntimeException('Model not set in listfields field ' . $this->id);

					return;
				}

				$formModel = $this->form->model;
				$valfield = $valueformat == 'tableelement' ? 'name' : 'id';
				$res = $formModel->getElementOptions($useStep, $valfield, $onlylistfields, $showRaw, $pluginFilters, $labelMethod, $nojoins);

				$jsres = $formModel->getElementOptions($useStep, $valfield, $onlylistfields, $showRaw, $pluginFilters, $labelMethod, $nojoins);
				array_unshift($jsres, JHTML::_('select.option', '', FText::_('COM_FABRIK_PLEASE_SELECT')));
				$this->js($jsres);
				break;
			case 'group':
				$valfield = $valueformat == 'tableelement' ? 'name' : 'id';
				$id = $this->form->getValue('id');
				$groupModel = JModelLegacy::getInstance('Group', 'FabrikFEModel');
				$groupModel->setId($id);
				$formModel = $groupModel->getFormModel();
				$res = $formModel->getElementOptions($useStep, $valfield, $onlylistfields, $showRaw, $pluginFilters, $labelMethod, $nojoins);
				break;
			default:
				return FText::_('The ListFields element is only usable by lists and elements');
				break;
		}

		$return = '';

		if (is_array($res))
		{
			if ($controller == 'element')
			{
				foreach ($res as $o)
				{
					$s = new stdClass;

					// Element already contains correct key
					if ($controller != 'element')
					{
						$s->value = $valueformat == 'tableelement' ? $o->table_name . '.' . $o->text : $o->value;
					}
					else
					{
						$s->value = $o->value;
					}

					$s->text = FabrikString::getShortDdLabel($o->text);
					$aEls[] = $s;
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

			// For pk fields - we are no longer storing the key with '`' as that's mySQL specific
			$this->value = str_replace('`', '', $this->value);

			// Some elements were stored as names but subsequently changed to ids (need to check for old values an substitute with correct ones)
			if ($valueformat == 'id' && !is_numeric($this->value) && $this->value != '')
			{
				if ($formModel)
				{
					$elementModel = $formModel->getElement($this->value);
					$this->value = $elementModel ? $elementModel->getId() : $this->value;
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

	private function js($res = array())
	{
		$connection = $this->element['connection'];
		$repeat = FabrikAdminElementHelper::getRepeat($this) || $this->element['repeat'];
		$c = (int) FabrikAdminElementHelper::getRepeatCounter($this);
		$mode = (string) JArrayHelper::getValue($this->element, 'mode', false);
		$connectionDd = $repeat ? $connection . '-' . $c : $connection;
		$highlightpk = (bool) JArrayHelper::getValue($this->element, 'highlightpk', false);
		$tableDd = $this->element['table'];
		$opts = new stdClass;
		$opts->table = ($repeat) ? 'jform_' . $tableDd . '-' . $c : 'jform_' . $tableDd;
		$opts->conn = 'jform_' . $connectionDd;
		$opts->value = $this->value;
		$opts->repeat = $repeat;
		$opts->showAll = (int) JArrayHelper::getValue($this->element, 'showall', '1');
		$opts->highlightpk = (int) $highlightpk;
		$opts->mode = $mode;
		$opts->defaultOpts = $res;
		$opts->addBrackets = (bool) JArrayHelper::getValue($this->element, 'addbrackets', false);
		$opts = json_encode($opts);
		$script = array();
		$script[] = "if (typeOf(FabrikAdmin.model.fields.listfields) === 'null') {";
		$script[] = "FabrikAdmin.model.fields.listfields = {};";
		$script[] = "}";
		$script[] = "FabrikAdmin.model.fields.listfields['$this->id'] = new ListFieldsElement('$this->id', $opts);";
		$script = implode("\n", $script);

		$srcs = array();
		$srcs[] = 'media/com_fabrik/js/fabrik.js';
		$srcs[] = 'administrator/components/com_fabrik/models/fields/listfields.js';
		FabrikHelperHTML::script($srcs, $script);
	}

	/**
	 * Build GUI for adding in elements
	 *
	 * @return  string  Textarea GUI
	 */

	private function gui()
	{
		$str = array();
		$modeField = (string) JArrayHelper::getValue($this->element, 'modefield', 'textarea');

		if ($modeField === 'textarea')
		{
			$str[] = '<textarea cols="20" row="3" id="' . $this->id . '" name="' . $this->name . '">' . $this->value . '</textarea>';
		}
		else
		{
			$str[] = '<input class="input" id="' . $this->id . '" name="' . $this->name . '" value="' . $this->value . '" />';
		}

		$str[] = '<button class="button btn"><span class="icon-arrow-left"></span> ' . FText::_('COM_FABRIK_ADD') . '</button>';
		$str[] = '<select class="elements"></select>';

		return implode("\n", $str);
	}

	/**
	 * Load the element list from the group id
	 *
	 * @param   int  $groupId  Group id
	 *
	 * @since   3.0.6
	 *
	 * @return array
	 */

	protected function loadFromGroupId($groupId)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$controller = $input->get('view', $input->get('task'));
		$valueformat = (string) JArrayHelper::getValue($this->element, 'valueformat', 'id');
		$onlylistfields = (int) JArrayHelper::getValue($this->element, 'onlylistfields', 0);
		$pluginFilters = trim($this->element['filter']) == '' ? array() : explode('|', $this->element['filter']);
		$labelMethod = (string) JArrayHelper::getValue($this->element, 'label_method');
		$nojoins = (bool) JArrayHelper::getValue($this->element, 'nojoins', false);

		$bits = array();
		$showRaw = (bool) JArrayHelper::getValue($this->element, 'raw', false);
		$groupModel = JModelLegacy::getInstance('Group', 'FabrikFEModel');
		$groupModel->setId($groupId);
		$optskey = $valueformat == 'tableelement' ? 'name' : 'id';
		$useStep = (bool) JArrayHelper::getValue($this->element, 'usestep', false);
		$res = $groupModel->getForm()->getElementOptions($useStep, $optskey, $onlylistfields, $showRaw, $pluginFilters, $labelMethod, $nojoins);
		$hash = $controller . '.' . implode('.', $bits);

		if (array_key_exists($hash, $this->results))
		{
			$res = $this->results[$hash];
		}
		else
		{
			$this->results[$hash] = &$res;
		}

		return $res;
	}
}
