<?php
/**
 * @package     Joomla.Framework
 * @subpackage  Document
 * @copyright   Copyright (C) 2005 - 2010 Open Source Matters. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

file_exists(JPATH_LIBRARIES . '/joomla/document/html/html.php') && require_once JPATH_LIBRARIES . '/joomla/document/html/html.php';
require_once JPATH_SITE . '/components/com_fabrik/helpers/pdf.php';

/**
 * DocumentPDF class, provides an easy interface to parse and display a pdf document
 *
 * @package     Joomla.Framework
 * @subpackage  Document
 * @since       1.5
 */
class JDocumentpartial extends JDocumentHTML
{
	/**
	 * Render the document.
	 *
	 * @param   boolean  $cache   If true, cache the output
	 * @param   array    $params  Associative array of attributes
	 *
	 * @return	string
	 */
	public function render($cache = false, $params = array())
	{
		$this->setType('partial');

		return parent::render();
	}
}
