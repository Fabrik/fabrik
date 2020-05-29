<?php
/**
 * Partial Document class
 *
 * @package     Joomla
 * @subpackage  Fabrik.Documents
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
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
