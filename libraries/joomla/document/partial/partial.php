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

/**
 * Documentpartial class, used by Fabrik to load popups, avoiding re-including jQuery in head
 *
 * @package     Joomla.Framework
 * @subpackage  Document
 * @since       1.5
 */
class JDocumentpartial extends JDocumentHTML
{

	/**
	 * Class constructor
	 *
	 * @access protected
	 * @param   array	$options Associative array of options
	 */
	function __construct($options = array())
	{
		parent::__construct($options);

		//set document type
		$this->_type = 'partial';
	}
}
