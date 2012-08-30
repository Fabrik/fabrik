<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');
require_once JPATH_SITE . '/components/com_fabrik/views/form/view.base.php';

/**
 * HTML Form view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0.6
 */

class fabrikViewForm extends FabrikViewFormBase
{

	/**
	 * Main setup routine for displaying the form/detail view
	 *
	 * @param   string  $tpl  template
	 *
	 * @return  void
	 */

	public function display($tpl = null)
	{
		if (parent::display($tpl) !== false)
		{
			$this->output();
		}
	}
}
