<?php
/**
 * Connection Fabrik Table
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
 * Connection Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikTableConnection extends JTable
{
	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var int
	 */
	public $default = 0;

	/**
	 * @var string
	 */
	public $password = '';
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  database object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__fabrik_connections', 'id', $db);
	}
}
