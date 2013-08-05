<?php
/**
 * Abstract Fabrik Admin model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modeladmin');

/**
 * Abstract Fabrik Admin model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

abstract class FabModelAdmin extends JModelAdmin
{

	/**
	 * Component name
	 *
	 * @var  string
	 */
	protected $option = 'com_fabrik';

	/**
	 * Get the list's active/selected plug-ins
	 *
	 * @return array
	 */

	public function getPlugins()
	{
		$item = $this->getItem();

		// Load up the active plug-ins
		$plugins = JArrayHelper::getValue($item->params, 'plugins', array());
		return $plugins;
	}

}
