<?php
/**
 * Forms list controller class.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once 'fabcontrolleradmin.php';

/**
 * Forms list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikAdminControllerForms extends FabControllerAdmin
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_FORMS';

	/**
	 * View item name
	 *
	 * @var string
	 */
	protected $view_item = 'forms';

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    Model name
	 * @param   string  $prefix  Model prefix
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @since	1.6
	 *
	 * @return  JModel
	 */
	public function &getModel($name = 'Form', $prefix = 'FabrikAdminModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * Attempt to alter the db structure to match the form's current status
	 *
	 * @return  null
	 */
	public function updateDatabase()
	{
		// Check for request forgeries
		JSession::checkToken() or die('Invalid Token');
		$this->setRedirect('index.php?option=com_fabrik&view=forms');
		$this->getModel()->updateDatabase();
		$this->setMessage(FText::_('COM_FABRIK_DATABASE_UPDATED'));
	}

	/**
	 * View the list data
	 *
	 * @return  null
	 */
	public function listview()
	{
		$input = $this->input;
		$cid = $input->get('cid', array(0), 'array');
		$cid = $cid[0];
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id')->from('#__fabrik_lists')->where('form_id = ' . (int) $cid);
		$db->setQuery($query);
		$listId = $db->loadResult();
		$this->setRedirect('index.php?option=com_fabrik&task=list.view&listid=' . $listId);
	}
}
