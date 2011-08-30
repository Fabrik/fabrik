<?php
/**
* @version		$Id:sql.php 6961 2007-03-15 16:06:53Z tcp $
* @package		Joomla.Framework
* @subpackage	Parameter
* @copyright	Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'element.php');

//missing class lead to error: Class 'JFormFieldList' not found
JFormHelper::loadFieldClass('list');
//

/**
 * Renders a SQL element
 *
 * @package 	Joomla.Framework
 * @subpackage		Parameter
 * @since		1.5
 */
 


class JFormFieldSQL2 extends JFormFieldList
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'SQL';

	function getOptions()
	{
		$db	= FabrikWorker::getDbo();

		$check = $this->element['checkexists'] ? (bool)$this->element['checkexists'] : false;
		if ($check) {
			$q = explode(" ", $this->element['query']);
			$i = array_search('FROM', $q);
			if (!$i) {
				$i = array_search('from', $q);
			}
			$i++;
			$tbl = $db->replacePrefix($q[$i]);

			$db->setQuery("SHOW TABLES");
			$rows = $db->loadResultArray();
			$found = in_array($tbl, $rows) ? true : false;
			if (!$found) {
				return array(JHTML::_('select.option', $tbl.' not found', ''));
			}
		}
		$db->setQuery($this->element['query']);
		$key = $this->element['key_field'] ? $this->element['key_field'] : 'value';
		$val = $this->element['value_field'] ? $this->element['value_field'] : $this->name;
		if ($this->element['add_select']) {
		  $rows = array(JHTML::_('select.option', ''));
		  $rows = array_merge($rows, (array)$db->loadObjectList());
		} else {
		   $rows = $db->loadObjectList();
		}
		return $rows;
	}
}
