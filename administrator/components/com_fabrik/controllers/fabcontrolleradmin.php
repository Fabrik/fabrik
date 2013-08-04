<?php
/**
 * Extends JControllerAdmin allowing for confirmation of removal of
 * items, along with call to model to perform additional
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controlleradmin');

/**
 * List controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabControllerAdmin extends JControllerAdmin
{
	/**
	 * Component name
	 *
	 * @var string
	 */

	var $option = 'com_fabrik';

	/**
	 * Actally delete the requested items forms etc
	 *
	 * @return null
	 */

	public function dodelete()
	{
		parent::delete();
	}
}
