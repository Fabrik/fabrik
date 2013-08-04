<?php
/**
 * Fabrik Package MySQL driver
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * MySQL database driver
 *
 * @package		Joomla.Framework
 * @subpackage	Database
 * @since		3.0.7
 */
class JDatabaseDriverMySQLi_Fab extends JDatabaseDriverMySQLi
{
	/**
	 * The database driver name
	 *
	 * @var string
	 */
	public $name = 'mysqli_fab';

	/**
	 * This function replaces a string identifier <var>$prefix</var> with the
	 * string held is the <var>_table_prefix</var> class variable.
	 *
	 * @param   string	The SQL query
	 * @param   string	The common table prefix
	 */
	public function replacePrefix($sql, $prefix = '#__')
	{
		$app = JFactory::getApplication();
		$package = $app->getUserStateFromRequest('com_fabrik.package', 'package', 'fabrik');
		$sql = str_replace('{package}', $package, $sql);
		return parent::replacePrefix($sql, $prefix);
	}

}
