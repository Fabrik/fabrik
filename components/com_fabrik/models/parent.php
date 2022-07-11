<?php
/**
 * Extend JModel with Fabrik specific methods
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// ROB don't think this is used - use fabrik.php model instead

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

/**
 * Extend JModel with Fabrik specific methods
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikFEModel extends JModelLegacy
{
	/**
	 * required for compatibility with mambo 4.5.4
	 *
	 * @param   mixed  $value  value to set all properties to default null
	 *
	 * @return  null
	 */

	public function reset($value = null)
	{
		$keys = $this->getProperties();

		foreach ($keys as $k)
		{
			$this->$k = $value;
		}
	}
}
