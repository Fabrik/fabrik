<?php
/**
 * Plugin element to render digital signature pad
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.digsig
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to render digital signature pad
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.digsig
 * @since       3.0
 */

class PlgFabrik_ElementDigsig extends PlgFabrik_Element
{
	/**
	 * If the element 'Include in search all' option is set to 'default' then this states if the
	 * element should be ignored from search all.
	 * @var bool  True, ignore in extended search all.
	 */
	protected $ignoreSearchAllDefault = true;

	/**
	 * Db table field type
	 *
	 * @var string
	 */
	protected $fieldDesc = 'TEXT';

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           To pre-populate element with
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$sig_id = $id . '_sig';
		$params = $this->getParams();
		$digsig_width = $params->get('digsig_form_width', '400');
		$digsig_height = $params->get('digsig_form_height', '150');
		$element = $this->getElement();
		$val = $this->getValue($data, $repeatCounter);
		$str = array();

		if (!$this->isEditable())
		{
			$str[] = '
		<div id="' . $sig_id . '" class="oc_sigPad signed">
		  <div class="sigWrapper" style="height:150px;" >
		    <canvas class="pad" width="' . $digsig_width . '" height="' . $digsig_height . '" ></canvas>
		  </div>
		</div>
			';
		}
		else
		{
			$str[] = '
	<div id="' . $id . '" class="fabrikSubElementContainer">
			';
			$str[] = '
		<div class="ccms_form_element cfdiv_custom spad_container_div" id="' . $id . '_oc_spad" >
			<ul class="sigNav">
				<li class="drawIt">
					<a href="#draw-it">Draw It</a>
				</li>
				<li class="clearButton">
					<a href="#clear">Clear</a>
				</li>
			</ul>
			<div class="sig sigWrapper">
				<div class="typed"></div>
				<canvas class="pad" id="' . $id . '_oc_pad" width="' . $digsig_width . '" height="' . $digsig_height . '" ></canvas>
			</div>
		</div>
			';
			$str[] = '
		<input type="hidden" class="fabrikinput" id="' . $sig_id . '" name="' . $name . '" value="' . $val . '" />
			';

			$str[] = '
	</div>
			';
		}

		return implode("\n", $str);
	}

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string    $data      Elements data
	 * @param   stdClass  &$thisRow  All the data in the lists current row
	 *
	 * @return  string	Formatted value
	 */

	public function renderListData($data, stdClass &$thisRow)
	{
		if ($data === '' || empty($data))
		{
			return '';
		}

		$params = $this->getParams();
		$digsig_width = $params->get('digsig_list_width', '200');
		$digsig_height = $params->get('digsig_list_height', '75');
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$formModel = $this->getForm();
		$formid = $formModel->getId();
		$rowid = $thisRow->__pk_val;
		$elementid = $this->getId();

		$link = COM_FABRIK_LIVESITE
		. 'index.php?option=com_' . $package . '&amp;task=plugin.pluginAjax&amp;plugin=digsig&amp;method=ajax_signature_to_image&amp;'
			. 'format=raw&amp;element_id=' . $elementid . '&amp;formid=' . $formid . '&amp;rowid=' . $rowid . '&amp;repeatcount=0';

		$data = '<img src="' . $link . '" width="' . $digsig_width . '" height="' . $digsig_height . '"/>';

		return parent::renderListData($data, $thisRow);
	}

	/**
	 * Save the signature to an image
	 *
	 * @return  void
	 */
	public function onAjax_signature_to_image()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$this->setId($input->getInt('element_id'));
		$this->loadMeForAjax();
		$this->getElement();
		$params = $this->getParams();
		$params = $this->getParams();
		$digsig_width = (int) $params->get('digsig_list_width', '200');
		$digsig_height = (int) $params->get('digsig_list_height', '75');
		$lang = JFactory::getLanguage();
		$lang->load('com_fabrik.plg.element.fabrikdigsig', JPATH_ADMINISTRATOR);

		if (!$this->canView())
		{
			$app->enqueueMessage(FText::_('PLG_ELEMENT_DIGSIG_NO_PERMISSION'));
			$app->redirect($url);
			exit;
		}

		$rowid = $input->get('rowid', '', 'string');

		if (empty($rowid))
		{
			$app->enqueueMessage(FText::_('PLG_ELEMENT_FDIGSIG_NO_SUCH_FILE'));
			$app->redirect($url);
			exit;
		}

		$repeatcount = $input->getInt('repeatcount', 0);
		$listModel = $this->getListModel();
		$row = $listModel->getRow($rowid, false);

		if (empty($row))
		{
			$app->enqueueMessage(FText::_('PLG_ELEMENT_DIGSIG_NO_SUCH_FILE'));
			$app->redirect($url);
			exit;
		}

		$elName = $this->getFullName(true, false);
		$json_sig = $row->$elName;
		require JPATH_SITE . '/plugins/fabrik_element/digsig/libs/signature-to-image/signature-to-image.php';
		$opts = array(
			'imageSize' => array($digsig_width, $digsig_height)
		);
		$filecontent = sigJsonToImage($json_sig, $opts);

		if (!empty($filecontent))
		{
			$width = imagesx($filecontent);
			$height = imagesy($filecontent);
			ob_start();
			imagepng($filecontent);
			$img = ob_get_contents();
			ob_end_clean();

			// Some time in the past
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
			header('Accept-Ranges: bytes');
			header('Content-Length: ' . strlen($img));
			header('Content-Type: ' . 'image/png');

			// Serve up the file
			echo $img;

			// And we're done.
			exit();
		}
		else
		{
			$app->enqueueMessage(FText::_('PLG_ELEMENT_DIGSIG_NO_SUCH_FILE'));
			$app->redirect($url);
			exit;
		}
	}

	/**
	 * Manipulates posted form data for insertion into database
	 *
	 * @param   mixed  $val   This elements posted form data
	 * @param   array  $data  Posted form data
	 *
	 * @return  mixed
	 */

	public function storeDatabaseFormat($val, $data)
	{
		if ($val == '')
		{
			$val = null;
		}

		return $val;
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */

	public function elementJavascript($repeatCounter)
	{
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$sig_id = $id . '_sig';
		$opts = $this->getElementJSOptions($repeatCounter);
		$data = $this->getFormModel()->data;
		$opts->value = $this->getValue($data, $repeatCounter);
		$opts->sig_id = $sig_id;

		return array('FbDigsig', $id, $opts);
	}

	/**
	 * Get the class to manage the form element
	 * to ensure that the file is loaded only once
	 *
	 * @param   array   &$srcs   Scripts previously loaded
	 * @param   string  $script  Script to load once class has loaded
	 * @param   array   &$shim   Dependant class names to load before loading the class - put in requirejs.config shim
	 *
	 * @return void
	 */

	public function formJavascriptClass(&$srcs, $script = '', &$shim = array())
	{
		$s = new stdClass;
		$s->deps = array('fab/element');

		$params = $this->getParams();

		$folder = 'element/digsig/libs/signature-pad/';
		$digsigShim = new stdClass;
		$digsigShim->deps = array($folder . 'jquery.signaturepad');
		$s->deps[] = $folder . 'jquery.signaturepad';

		$s->deps[] = $folder . 'flashcanvas';
		$shim[$folder . 'flashcanvas'] = $digsigShim;

		$s->deps[] = $folder . 'json2';
		$shim[$folder . 'json2'] = $digsigShim;

		$shim['element/digsig/digsig'] = $s;

		FabrikHelperHTML::stylesheet(COM_FABRIK_LIVESITE . 'plugins/fabrik_element/digsig/libs/signature-pad/jquery.signaturepad.css');

		parent::formJavascriptClass($srcs, $script, $shim);

		// $$$ hugh - added this, and some logic in the view, so we will get called on a per-element basis
		return false;
	}
}
