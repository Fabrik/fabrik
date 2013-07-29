<?php
/**
 * Fabrik Front End Element View
 *
 * @package     Joomla
 * @subpackage  Fabik
 * @copyright   Copyright (C) 2005 - 2008 Pollen 8 Design Ltd. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

/**
 * Fabrik Front End Element View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikViewElement extends JView
{

	/**
	 * Element id (not used?)
	 *
	 * @var int
	 */
	protected $id = null;

	/**
	 * Set id
	 *
	 * @param   int  $id  Element id
	 *
	 * @deprecated ?
	 *
	 * @return  void
	 */

	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Display the template
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return void
	 */

	public function display($tpl = null)
	{
	}

}
