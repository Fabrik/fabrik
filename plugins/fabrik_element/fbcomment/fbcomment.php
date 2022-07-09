<?php
/**
 * Plugin element to render facebook open graph comment widget
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.facebookcomment
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to render facebook open graph comment widget
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.facebookcomment
 * @since       3.0
 */

class PlgFabrik_ElementFbcomment extends PlgFabrik_Element
{
	/**
	 * Does the element have a label
	 *
	 * @var bool
	 */
	protected $hasLabel = false;

	/**
	 * Db table field type
	 *
	 * @var  string
	 */
	protected $fieldDesc = 'INT(%s)';

	/**
	 * Db table field size
	 *
	 * @var  string
	 */
	protected $fieldLength = '1';

	/**
	 * Draws the form element
	 *
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string  returns element html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		$displayData = new stdClass;
		$displayData->num = $params->get('fbcomment_number_of_comments', 10);
		$displayData->width = $params->get('fbcomment_width', 300);
		$displayData->colour = $params->get('fb_comment_scheme') == '' ? '' : ' colorscheme="dark" ';
		$displayData->href = $params->get('fbcomment_href', '');

		if (!isset($data->href) || empty($data->href))
		{
			$rowId = $this->app->input->getString('rowid', '', 'string');

			if ($rowId != '')
			{
				$formModel = $this->getFormModel();
				$formId = $formModel->getId();
				$href = 'index.php?option=com_fabrik&view=form&formid=' . $formId . '&rowid=' . $rowId;
				$href = JRoute::_($href);
				$displayData->href = COM_FABRIK_LIVESITE_ROOT . $href;
			}
		}

		if (!empty($displayData->href))
		{
			$w = new FabrikWorker;
			$displayData->href = $w->parseMessageForPlaceHolder($displayData->href, $data);
			$locale = $params->get('fbcomment_locale', 'en_US');

			if (empty($locale))
			{
				$locale = 'en_US';
			}

			$displayData->graphApi = FabrikHelperHTML::facebookGraphAPI($params->get('opengraph_applicationid'), $locale);
		}

		$layout = $this->getLayout('form');

		return $layout->render($displayData);
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
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);

		return array('FbComment', $id, $opts);
	}
}
