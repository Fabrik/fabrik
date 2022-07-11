<?php
/**
 * Display a json loaded window with a repeatable set of sub fields
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

/**
 * Display a json loaded window with a repeatable set of sub fields
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */

class JFormFieldFabrikModalrepeat extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'FabrikModalrepeat';

	/**
	 * Method to get the field input markup.
	 *
	 * @since	1.6
	 *
	 * @return	string	The field input markup.
	 */

	protected function getInput()
	{
		// Initialize variables.
		$app = JFactory::getApplication();
		$document = JFactory::getDocument();
		JHTML::stylesheet('administrator/components/com_fabrik/views/fabrikadmin.css');
		$subForm = new JForm($this->name, array('control' => 'jform'));
		$xml = $this->element->children()->asXML();
		$subForm->load($xml);
		$j3 = FabrikWorker::j3();
		
		if (!isset($this->form->repeatCounter))
		{
			$this->form->repeatCounter = 0;
		}

		// Needed for repeating modals in gmaps viz
		$subForm->repeatCounter = (int) $this->form->repeatCounter;

		/**
		 * f3 hack
		 */

		$input = $app->input;
		$view = $input->get('view', 'list');

		switch ($view)
		{
			case 'item':
				$view = 'list';
				$id = (int) $this->form->getValue('request.listid');
				break;
			case 'module':
				$view = 'list';
				$id = (int) $this->form->getValue('params.list_id');
				break;
			default:
				$id = $input->getInt('id');
				break;
		}

		if ($view === 'element')
		{
			$pluginManager = FabrikWorker::getPluginManager();
			$feModel = $pluginManager->getPluginFromId($id);
		}
		else
		{
			$feModel = JModelLegacy::getInstance($view, 'FabrikFEModel');
			$feModel->setId($id);
		}

		$subForm->model = $feModel;

		if (isset($this->form->rawData))
		{
			$subForm->rawData = $this->form->rawData;
		}

		// Hack for order by elements which we now want to store as ids
		$v = json_decode($this->value);

		if (isset($v->order_by))
		{
			$formModel = $feModel->getFormModel();

			foreach ($v->order_by as &$orderBy)
			{
				$elementModel = $formModel->getElement($orderBy, true);
				$orderBy = $elementModel ? $elementModel->getId() : $orderBy;
			}
		}

		$this->value = json_encode($v);

		/*
		 * end
		 */
		$children = $this->element->children();

		// $$$ rob 19/07/2012 not sure y but this fires a strict standard warning deep in JForm, suppress error for now
		@$subForm->setFields($children);

		$str = array();
		$version = new JVersion;
		$j32 = version_compare($version->RELEASE, '3.2') >= 0 ? true : false;
		$j322 = ($j32 && $version->DEV_LEVEL >=3);
		$j33 = version_compare($version->RELEASE, '3.3') >= 0 ? true : false;

		$modalId = $j32 || $j33 ? 'attrib-' . $this->id . '_modal' : $this->id . '_modal';

		// As JForm will render child fieldsets we have to hide it via CSS
		$fieldSetId = str_replace('jform_params_', '', $modalId);
		$css = 'a[href="#' . $fieldSetId . '"] { display: none!important; }';
		$document->addStyleDeclaration($css);

		$path = 'templates/' . $app->getTemplate() . '/images/menu/';

		$str[] = '<div id="' . $modalId . '" style="display:none">';
		$str[] = '<table class="adminlist ' . $this->element['class'] . ' table table-striped">';
		$str[] = '<thead><tr class="row0">';
		$names = array();
		$attributes = $this->element->attributes();

		foreach ($subForm->getFieldset($attributes->name . '_modal') as $field)
		{
			$names[] = (string) $field->element->attributes()->name;
			$str[] = '<th>' . strip_tags($field->getLabel($field->name));
			$str[] = '<br /><small style="font-weight:normal">' . FText::_($field->description) . '</small>';
			$str[] = '</th>';
		}

		if ($j3)
		{
			$str[] = '<th><a href="#" class="add btn button btn-success"><i class="icon-plus"></i> </a></th>';
		}
		else
		{
			$str[] = '<th><a href="#" class="add"><img src="' . $path . '/icon-16-new.png" alt="' . FText::_('ADD') . '" /></a></th>';
		}

		$str[] = '</tr></thead>';

		$str[] = '<tbody><tr>';

		foreach ($subForm->getFieldset($attributes->name . '_modal') as $field)
		{
			$str[] = '<td>' . $field->getInput() . '</td>';
		}

		$str[] = '<td>';

		if ($j3)
		{
			$str[] = '<div class="btn-group"><a class="add btn button btn-success"><i class="icon-plus"></i> </a>';
			$str[] = '<a class="remove btn button btn-danger"><i class="icon-minus"></i> </a></div>';
		}
		else
		{
			$str[] = '<a href="#" class="add"><img src="' . $path . '/icon-16-new.png" alt="' . FText::_('ADD') . '" /></a>';
			$str[] = '<a href="#" class="remove"><img src="' . $path . '/icon-16-delete.png" alt="' . FText::_('REMOVE') . '" /></a>';
		}

		$str[] = '</td>';
		$str[] = '</tr></tbody>';
		$str[] = '</table>';
		$str[] = '</div>';
		$form = implode("\n", $str);
		static $modalRepeat;

		if (!isset($modalRepeat))
		{
			$modalRepeat = array();
		}

		if (!array_key_exists($modalId, $modalRepeat))
		{
			$modalRepeat[$modalId] = array();
		}

		if (!array_key_exists($this->form->repeatCounter, $modalRepeat[$modalId]))
		{
			// If loaded as js template then we don't want to repeat this again. (fabrik)
			$names = json_encode($names);
			$pane = str_replace('jform_params_', '', $modalId) . '-options';

			$modalRepeat[$modalId][$this->form->repeatCounter] = true;
			$opts = new stdClass;
			$opts->j3 = $j3;
			$opts = json_encode($opts);
			$script = str_replace('-', '', $modalId) . " = new FabrikModalRepeat('$modalId', $names, '$this->id', $opts);";
			$option = $input->get('option');

			if ($option === 'com_fabrik')
			{
				FabrikHelperHTML::script('administrator/components/com_fabrik/models/fields/fabrikmodalrepeat.js', $script);
			}
			else
			{
				if ($j3)
				{

					$context = strtoupper($option);

					if ($context === 'COM_ADVANCEDMODULES')
					{
						$context = 'COM_MODULES';
					}

					$j3pane = $context . '_' . str_replace('jform_params_', '', $modalId) . '_FIELDSET_LABEL';

					if ($j32)
					{
						$j3pane = strtoupper(str_replace('attrib-', '', $j3pane));
					}

					if ($j322 || $j33)
					{
						$script = "window.addEvent('domready', function() {
					" . $script . "
					});";
					}
					else
					{
						$script = "window.addEvent('domready', function() {
					var a = jQuery(\"a:contains('$j3pane')\");
						if (a.length > 0) {
							a = a[0];
							var href= a.get('href');
							jQuery(href)[0].destroy();

							var accord = a.getParent('.accordion-group');
							if (typeOf(accord) !== 'null') {
								accord.destroy();
							} else {
								a.destroy();
							}
							" . $script . "
						}
					});";
					}
				}
				else
				{
					$script = "window.addEvent('domready', function() {
			" . $script . "
			if (typeOf($('$pane')) !== 'null') {
			  //$('$pane').getParent().hide();
			}
			});";
				}

				// Wont work when rendering in admin module page
				// @TODO test this now that the list and form pages are loading plugins via ajax (18/08/2012)
				FabrikHelperHTML::script('administrator/components/com_fabrik/models/fields/fabrikmodalrepeat.js', $script);
			}
		}

		if (is_array($this->value))
		{
			$this->value = array_shift($this->value);
		}

		$value = htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8');

		if ($j3)
		{
			$icon = $this->element['icon'] ? '<i class="icon-' . $this->element['icon'] . '"></i> ' : '';
			$icon .= FText::_('JLIB_FORM_BUTTON_SELECT');
			$str[] = '<button class="btn" id="' . $modalId . '_button" data-modal="' . $modalId . '">' . $icon . '</button>';
			$str[] = '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '" value="' . $value . '" />';
		}
		else
		{
			$str[] = '<div class="button2-left">';
			$str[] = '	<div class="blank">';
			$str[] = '		<a id="' . $modalId . '_button" data-modal="' . $modalId . '">' . FText::_('JLIB_FORM_BUTTON_SELECT') . '</a>';
			$str[] = '		<input type="hidden" name="' . $this->name . '" id="' . $this->id . '" value="' . $value . '" />';
			$str[] = '	</div>';
			$str[] = '</div>';
		}

		FabrikHelperHTML::framework();
		FabrikHelperHTML::iniRequireJS();

		return implode("\n", $str);
	}
}
