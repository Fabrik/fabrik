<?php
/**
 * Renders a radio list which will toggle visibility of a specified group
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
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
		$alt = $this->element['alt'];
		$script = array();
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
		FabrikHelperHTML::addScriptDeclaration(implode("\n", $script));
		return parent::getInput();
	}

}
