<?php
/**
 * Plugin element to render facebook likebox widget
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.facebooklikebox
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to render facebook likebox widget
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.facebooklikebox
 * @since       3.0
 */

class PlgFabrik_ElementFblikebox extends PlgFabrik_Element
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
	 * Draws the html form element
	 *
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		$str = FabrikHelperHTML::facebookGraphAPI($params->get('opengraph_applicationid'));
		$pageid = $params->get('fblikebox_pageid', 0);
		$stream = $params->get('fblikebox_stream', 1) == 1 ? 'true' : 'false';
		$width = $params->get('fblikebox_width', 300);
		$height = $params->get('fblikebox_height', 300);
		$header = $params->get('fblikebox_header', 1) == 1 ? 'true' : 'false';
		$connections = $params->get('fblikebox_connections', 10);

		// $str .= "<fb:like-box id=\"$pageid\" width=\"$width\" height=\"$height\" connections=\"$connections\" stream=\"$stream\" header=\"$header\" />";
		$str .= '<fb:like-box id="185550966885" width="292" height="440" connections="4" stream="true" header="true" />';

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

		return array('FbLikebox', $id, $opts);
	}
}
