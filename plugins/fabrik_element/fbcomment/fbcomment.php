<?php
/**
 * Plugin element to render facebook open graph comment widget
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.facebookcomment
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
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
		$str = '';

		// $id = $params->get('fbcomment_uniqueid');
		$href = $params->get('fbcomment_href');
		$width = $params->get('fbcomment_width', 300);
		$num = $params->get('fbcomment_number_of_comments', 10);
		$colour = $params->get('fb_comment_scheme') == '' ? '' : ' colorscheme="dark" ';

		if (empty($href))
		{
			$app = JFactory::getApplication();
			$rowid = $app->input->getString('rowid', '', 'string');

			if ($rowid != '')
			{
				$formModel = $this->getForm();
				$formid = $formModel->getId();
				$href = 'index.php?option=com_fabrik&view=form&formid=' . $formid . '&rowid=' . $rowid;
				$href = JRoute::_($href);
				$href = COM_FABRIK_LIVESITE_ROOT . $href;
			}
		}

		if (!empty($href))
		{
			$w = new FabrikWorker;
			$href = $w->parseMessageForPlaceHolder($href, $data);
			$locale = $params->get('fbcomment_locale', 'en_US');

			if (empty($locale))
			{
				$locale = "en_US";
			}

			$str .= FabrikHelperHTML::facebookGraphAPI($params->get('opengraph_applicationid'), $locale);
			$str .= '<div id="fb-root"><fb:comments href="' . $href . '" nmigrated="1" um_posts="' . $num
			. '" width="' . $width . '"' . $colour . '></fb:comments>';
		}
		else
		{
			$str .= FText::_('PLG_ELEMENT_FBCOMMENT_AVAILABLE_WHEN_SAVED');
		}

		return $str;
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
