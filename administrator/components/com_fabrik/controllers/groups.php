<?php
/**
 * Groups list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       1.6
 */

// No direct access.
defined('_JEXEC') or die;

require_once 'fabcontrolleradmin.php';

/**
 * Groups list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikControllerGroups extends FabControllerAdmin
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_GROUPS';

	/**
	 * View item name
	 *
	 * @var string
	 */
	protected $view_item = 'groups';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see		JController
	 * @since	1.6
	 */

	public function __construct($config = array())
	{
		parent::__construct($config);
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    model name
	 * @param   string  $prefix  model prefix
	 *
	 * @return  J model
	 */

	public function &getModel($name = 'Group', $prefix = 'FabrikModel')
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));
		return $model;
	}

}
