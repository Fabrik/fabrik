<?php
/**
 * Fabrik modifier view
 * @package Joomla
 * @subpackage Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * static class to provide access to output modifier functions
 */
class fabrikModifier {

	function truncate( $text, $opts = array() ) {
		return fabrikString::truncate( $data, $opts);
	}

}
?>