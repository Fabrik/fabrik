<?php
/**
 * Renders a radio list which will toggle visibility of a specified group
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('radio');

/**
 * Renders a radio list which will toggle visibility of a specified group
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */
class JFormFieldToggleoptionsradio extends JFormFieldRadio
{
	/**
	 * Element name
	 *
	 * @var		string
	 */
	protected $name = 'ToggleOptionsRadio';

	/**
	 * Method to get the field input markup.
	 *
	 * Options:
	 *  - target: the group id to toggler
	 *  - Show: the radio list's value to show target
	 *  - Hide: the radio list's value to hide the target
	 *  - alt: another group id, which is shown when target is hidden and hidden when target shown.
	 *
	 * @return	string	The field input markup.
	 */

	protected function getInput()
	{
		$fs = str_replace("'", '"', $this->element['fieldsets']);
		$script = array();

		if ($fs !== '') {

			/**
			 * New way:
			 *
			 * <field name="slack_attachment"
			type="toggleoptionsradio"
			class="btn-group"
			default="0"
			description="PLG_FORM_SLACK_ATTACHMENTS_DESC"
			label="PLG_FORM_SLACK_ATTACHMENTS_LABEL"
			fieldsets="{'0':'plg-form-slack-simple','1':'plg-form-slack-attachments'}">
			<option value="0">JNO</option>
			<option value="1">JYES</option>
			</field>
			 *
			 */
			$script[] = "jQuery('document').ready(function($) {
				var fs = $fs;
				var v = $('#" . $this->id . "').find('input:checked').val();
				$.each(fs, function (value, fieldset) {
					v !== value ? $('#' + fieldset).hide() : $('#' + fieldset).show();
					v !== value ? $('a[href*=' + fieldset + ']').hide() : $('a[href*=' + fieldset + ']').show();
				});

				 $('#" . $this->id . "').find('input').on('click', function () {
				    var v = $(this).val();
				    $.each(fs, function (value, fieldset) {
						v !== value ? $('#' + fieldset).hide() : $('#' + fieldset).show();
						v !== value ? $('a[href*=' + fieldset + ']').hide() : $('a[href*=' + fieldset + ']').show();
					});
				 });
			});";


		} else {
			$alt = $this->element['alt'];

			$script[] = "window.addEvent('domready', function() {
		var s = document.id('" . $this->id . "').getElements('input').filter(function (e) {
		return (e.checked);
		});
		if (s[0].get('value') == '" . $this->element['hide'] . "') {
			document.id('" . $this->element['toggle'] . "').hide();
		}";

			if ($alt)
			{
				$script[] = "if (s[0].get('value') == '" . $this->element['show'] . "') {
			document.id('" . $alt . "').hide();
		}";
			}

			$script[] = "document.id('" . $this->id
				. "').getElements('input').addEvent('change', function (e) {
				if (e.target.checked == true) {
					var v = e.target.get('value');
					if (v == '" . $this->element['show'] . "') {
						document.id('" . $this->element['toggle'] . "').show();
					} else {
						if (v == '" . $this->element['hide'] . "') {
							document.id('" . $this->element['toggle'] . "').hide();
						}
					}";

			if ($alt)
			{
				$script[] = "if (v == '" . $this->element['show'] . "') {
						document.id('" . $alt . "').hide();
					} else {
						if (v == '" . $this->element['hide'] . "') {
							document.id('" . $alt . "').show();
						}
					}";
			}

			$script[] = "
				}
			});
		})";
		}


		FabrikHelperHTML::addScriptDeclaration(implode("\n", $script));

		return parent::getInput();
	}
}
