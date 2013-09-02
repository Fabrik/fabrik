<?php
/**
 * Form Field class for the Joomla Platform.
 * An ace.js code editor field
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');
JFormHelper::loadFieldClass('textarea');

/**
 * Form Field class for the Joomla Platform.
 * An ace.js code editor field
 *
 * @package     Joomla.Libraries
 * @subpackage  Form
 * @see         JEditor
 * @since       1.6
 */
class JFormFieldFabrikeditor extends JFormFieldTextArea
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.6
	 */
	public $type = 'Fabrikeditor';

	/**
	 * Method to get the field input markup for the editor area
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.6
	 */

	protected function getInput()
	{
		// Initialize some field attributes.
		$class = $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';
		$disabled = ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
		$columns = $this->element['cols'] ? ' cols="' . (int) $this->element['cols'] . '"' : '';
		$rows = $this->element['rows'] ? ' rows="' . (int) $this->element['rows'] . '"' : '';
		$required = $this->required ? ' required="required" aria-required="true"' : '';

		// JS events are saved as encoded html - so we don't want to double encode them
		$encoded = JArrayHelper::getValue($this->element, 'encoded', false);
		if (!$encoded)
		{
			$this->value = htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8');
		}

		$onchange = $this->element['onchange'] ? ' onchange="' . (string) $this->element['onchange'] . '"' : '';
		$editor = '<textarea name="' . $this->name . '" id="' . $this->id . '"'
			. $columns . $rows . $class . $disabled . $onchange . $required . '>'
			. $this->value . '</textarea>';

		$version = new JVersion;
		if ($version->RELEASE == 2.5)
		{
			return $editor;
		}

		// Joomla 3 version
		$mode = $this->element['mode'] ? (string) $this->element['mode'] : 'html';
		$theme = $this->element['theme'] ? (string) $this->element['theme'] : 'github';
		$height = $this->element['height'] ? (string) $this->element['height'] : '200px';
		$maxHeight = $this->element['max-height'] ? (string) $this->element['max-height'] : str_ireplace('px', '', $height) * 2 . 'px';
		$width = $this->element['width'] ? (string) $this->element['width'] : '300px';
		FabrikHelperHTML::framework();
		FabrikHelperHTML::iniRequireJS();

		if ($mode === 'php')
		{
			$aceMode = '{path:"ace/mode/php", inline:true}';
		}
		else
		{
			$aceMode = '"ace/mode/' . $mode . '"';
		}

		$minHeight = str_ireplace('px', '' , $height);
		$maxHeight = str_ireplace('px', '' , $maxHeight);
		// In code below, the +/- 2 is to account for the top/bottom border of 1px each.
		$script = '
window.addEvent(\'domready\', function () {
	var FbEditor = ace.edit("' . $this->id . '-ace");
	FbEditor.setTheme("ace/theme/' . $theme . '");
	FbEditor.getSession().setMode(' . $aceMode . ');
	FbEditor.setValue(document.id("' . $this->id . '").value);
	FbEditor.setAnimatedScroll(true);
	FbEditor.setBehavioursEnabled(true);
	FbEditor.setDisplayIndentGuides(true);
	FbEditor.setHighlightGutterLine(true);
	FbEditor.setHighlightSelectedWord(true);
	FbEditor.setShowFoldWidgets(true);
	FbEditor.setWrapBehavioursEnabled(true);
	FbEditor.getSession().setUseWrapMode(true);
	FbEditor.getSession().setTabSize(2);
	FbEditor.on("blur", function () {
		document.id("' . $this->id . '").value = FbEditor.getValue();
	});
	var maxlines = Math.floor((' . $maxHeight . ' - 2) / FbEditor.renderer.lineHeight);
	var updateHeight = function () {
		var s = FbEditor.getSession();
		var r = FbEditor.renderer;
		var l = s.getScreenLength();
		var h = (l > maxlines ? maxlines : l)
		      * r.lineHeight
		      + (r.$horizScroll ? r.scrollBar.getWidth() : 0)
		      + 2;
		h = h < ' . $minHeight . ' ? ' . $minHeight . ' : h;
		c = document.id("' . $this->id . '-container").getStyle("height").toInt();
		if (c !== h) {
			document.id("' . $this->id . '-container").setStyle("height", h.toString() + "px");
			FbEditor.resize();
		}
	}
	updateHeight();
	FbEditor.getSession().on("change", updateHeight);
});
		';

		$src = array('media/com_fabrik/js/lib/ace/src-min-noconflict/ace.js');
		FabrikHelperHTML::script($src, $script);

		echo '<style type="text/css" media="screen">
	#' . $this->id . '-ace {
		position: absolute;
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
		border: 1px solid #c0c0c0;
		border-radius: 3px;
	}

	#' . $this->id . '-container {
		position: relative;
		width: ' . $width . ';
		height: ' . $height . ';
	}
</style>';
		$this->element['cols'] = 1;
		$this->element['rows'] = 1;

		// For element js event code.
		return '<div id="' . $this->id . '-container"><div id="' . $this->id . '-ace"></div>' . $editor . '</div>';
	}

}
