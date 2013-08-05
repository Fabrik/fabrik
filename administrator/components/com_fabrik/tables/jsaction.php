<?php
/**
 * JS Action Fabrik table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/tables/fabtable.php';

/**
 * JS Action Fabrik table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 */
class FabrikTableJsaction extends FabTable
{

	/**
	 * Construct
	 *
	 * @param   object  &$db  database object
	 */

	function __construct(&$_db)
	{
		parent::__construct('#__{package}_jsactions', 'id', $_db);
	}

}
