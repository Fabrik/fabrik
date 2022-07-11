<?php
/**
 * JS Action Fabrik table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
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
 * @since       3.0
 */
class FabrikTableJsaction extends FabTable
{
	/**
	 * Construct
	 *
	 * @param   JDatabaseDriver  &$db  database object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__{package}_jsactions', 'id', $db);
	}
}
