<?php
/**
 * Raw Element controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

/**
 * Raw Element controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikControllerElement extends JControllerForm
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_ELEMENT';

	/**
	 * Default view
	 *
	 * @var string
	 */
	protected $default_view = 'element';

	/**
	 * Called via ajax to load in a given plugin's HTML settings
	 *
	 * @return  void
	 */

	public function getPluginHTML()
	{
		$plugin = JRequest::getCmd('plugin');
		$model = $this->getModel();
		$model->setState('element.id', JRequest::getInt('id'));
		$model->getForm();
		echo $model->getPluginHTML($plugin);
	}

	/**
	 * Method to save a record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 */

	public function save($key = null, $urlVar = null)
	{
		$listModel = $this->getModel('list', 'FabrikFEModel');
		$listModel->setId(JRequest::getInt('listid'));
		$rowId = JRequest::getVar('rowid');
		$key = JRequest::getVar('element');
		$key = array_pop(explode('___', $key));
		$value = JRequest::getVar('value');
		$listModel->storeCell($rowId, $key, $value);
		$this->mode = 'readonly';
		$this->display();
	}

}
