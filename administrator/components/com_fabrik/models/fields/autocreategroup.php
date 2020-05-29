<?php
/**
 * Determine whether automatically to create a group when a form or list is created
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

/**
 * Determine whether automatically to create a group when a form or list is created
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       3.0
 */

class JFormFieldAutoCreateGroup extends JFormFieldRadio
{
	/**
	 * Element name
	 * @var		string
	 */
	protected $name = 'AutoCreateGroup';

	/**
	 * Method to get the radio button field input markup.
	 *
	 * @return  string  The field input markup.
	 */

	protected function getInput()
	{
		$this->value = $this->form->getValue('id') == 0 ? 1 : 0;

		return parent::getInput();
	}
}
