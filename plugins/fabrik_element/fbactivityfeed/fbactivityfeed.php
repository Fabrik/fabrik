<?php
/**
 * Plugin element to render facebook open graph activity feed widget
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.facebookactivityfeed
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to render facebook open graph activity feed widget
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.facebookactivityfeed
 * @since       3.0
 */

class PlgFabrik_ElementFbactivityfeed extends PlgFabrik_Element
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
		$str = FabrikHelperHTML::facebookGraphAPI($params->get('opengraph_applicationid'));
		$domain = $params->get('fbactivityfeed_domain');
		$width = $params->get('fbactivityfeed_width', 300);
		$height = $params->get('fbactivityfeed_height', 300);
		$header = $params->get('fbactivityfeed_header', 1) ? 'true' : 'false';
		$border = $params->get('fbactivityfeed_border', '');
		$font = $params->get('fbactivityfeed_font', 'arial');
		$colorscheme = $params->get('fbactivityfeed_colorscheme', 'light');
		$str .= "<fb:activity site=\"$domain\" width=\"$width\" height=\"$height\" header=\"$header\" colorscheme=\"$colorscheme\" font=\"$font\" border_color=\"$border\" />";
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
		return array('FbActivityfeed', $id, $opts);
	}

}
