<?php
/**
 * Validation Rule Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/tables/fabtable.php';

/**
 * Validation Rule Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 * @deprecated  not used?
 */
class FabrikTableValidationrule extends FabTable
{
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  database object
	 */

	public function __construct(&$db)
	{
		parent::__construct('#__{package}_validation_rules', 'id', $db);
	}
}
