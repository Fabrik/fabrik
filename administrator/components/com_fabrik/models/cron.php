<?php
/**
 * Cron Admin Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

require_once 'fabmodeladmin.php';

/**
 * Cron Admin Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikAdminModelCron extends FabModelAdmin
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 */
	protected $text_prefix = 'COM_FABRIK_CRON';

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string $type   The table type to instantiate
	 * @param   string $prefix A prefix for the table class name. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  JTable  A database object
	 */
	public function getTable($type = 'Cron', $prefix = 'FabrikTable', $config = array())
	{
		$config['dbo'] = FabrikWorker::getDbo(true);

		return FabTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array $data     Data for the form.
	 * @param   bool  $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_fabrik.cron', 'cron', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = $this->app->getUserState('com_fabrik.edit.cron.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Get html form fields for a plugin (filled with
	 * current element's plugin data
	 *
	 * @param   string $plugin plugin name
	 *
	 * @return  string    html form fields
	 */
	public function getPluginHTML($plugin = null)
	{
		$item = $this->getItem();

		if (is_null($plugin))
		{
			$plugin = $item->plugin;
		}

		JPluginHelper::importPlugin('fabrik_cron');

		// Trim old f2 cron prefix.
		$plugin = FabrikString::ltrimiword($plugin, 'cron');

		if ($plugin == '')
		{
			$str = '<div class="alert">' . FText::_('COM_FABRIK_SELECT_A_PLUGIN') . '</div>';
		}
		else
		{
			$plugin = $this->pluginManager->getPlugIn($plugin, 'Cron');
			$mode   = FabrikWorker::j3() ? 'nav-tabs' : '';
			$str    = $plugin->onRenderAdminSettings(ArrayHelper::fromObject($item), null, $mode);
		}

		return $str;
	}

	/**
	 * Save the cron job - merging plugin parameters
	 *
	 * @param   array $data The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 */
	public function save($data)
	{
		if (FArrayHelper::getValue($data, 'lastrun') == '')
		{
			$date            = JFactory::getDate();
			$data['lastrun'] = $date->toSql();
		}

		$data['params'] = json_encode($data['params']);

		return parent::save($data);
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   JForm  $form  The form to validate against.
	 * @param   array  $data  The data to validate.
	 * @param   string $group The name of the field group to validate.
	 *
	 * @see     JFormRule
	 * @see     JFilterInput
	 *
	 * @return  mixed  Array of filtered data if valid, false otherwise.
	 */
	public function validate($form, $data, $group = null)
	{
		$params = $data['params'];
		$ok     = parent::validate($form, $data);

		// Standard jform validation failed so we shouldn't test further as we can't be sure of the data

		if (!$ok)
		{
			return false;
		}
		// Hack - must be able to add the plugin xml fields file to $form to include in validation but cant see how at the moment
		$data['params'] = $params;

		return $data;
	}
}
