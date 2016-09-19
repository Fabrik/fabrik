<?php
/**
 * Package
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use \Joomla\Registry\Registry;

jimport('joomla.application.component.modelitem');

/**
 * Package
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikFEModelPackage extends FabModel
{
	/**
	 * table objects
	 *
	 * @var array
	 */
	protected $tables = array();

	/**
	 * Package items
	 *
	 * @var JTable
	 */
	private $package = null;

	/**
	 * ID
	 *
	 * @var int id
	 */
	public $id = null;

	/**
	 * Method to set the  id
	 *
	 * @param   int  $id  ID number
	 *
	 * @return  void
	 */
	public function setId($id)
	{
		// Set new package ID
		$this->id = $id;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since	1.6
	 *
	 * @return  void
	 */
	protected function populateState()
	{
		$app = JFactory::getApplication('site');

		// Load the parameters.
		$params = $app->getParams();

		// Load state from the request.
		$pk = $app->input->getInt('id', $params->get('id'));
		$this->setState('package.id', $pk);

		$this->setState('params', $params);

		// TODO: Tune these values based on other permissions.
		$user = JFactory::getUser();

		if ((!$user->authorise('core.edit.state', 'com_fabrik')) && (!$user->authorise('core.edit', 'com_fabrik')))
		{
			$this->setState('filter.published', 1);
			$this->setState('filter.archived', 2);
		}
	}

	/**
	 * Method to get package data.
	 * Packages are all stored in jos_fabrik_packages - so don't use {package} in the query to load them
	 *
	 * @param   int  $pk  The id of the package.
	 *
	 * @return  mixed	Menu item data object on success, false on failure.
	 */
	public function &getItem($pk = null)
	{
		// Initialise variables.
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('package.id');

		if (!isset($this->_item))
		{
			$this->_item = array();
		}

		if (!isset($this->_item[$pk]))
		{
			try
			{
				$db = FabrikWorker::getDbo();
				$query = $db->getQuery(true);

				$query->select('label, params, published, component_name');
				$query->from('#__fabrik_packages');

				$query->where('id = ' . (int) $pk);

				// Filter by published state.
				$published = $this->getState('filter.published');
				$archived = $this->getState('filter.archived');

				if (is_numeric($published))
				{
					$query->where('(published = ' . (int) $published . ' OR published =' . (int) $archived . ')');
				}

				$db->setQuery($query);
				$data = $db->loadObject();

				if ($error = $db->getErrorMsg())
				{
					throw new Exception($error);
				}

				if (empty($data))
				{
					throw new JException(FText::_('COM_FABRIK_ERROR_PACKAGE_NOT_FOUND'), 404);
				}

				// Check for published state if filter set.
				if (((is_numeric($published)) || (is_numeric($archived))) && (($data->published != $published) && ($data->published != $archived)))
				{
					throw new JException(FText::_('COM_FABRIK_ERROR_PACKAGE_NOT_FOUND'), 404);
				}

				// Convert parameter fields to objects.
				$registry = new Registry;
				$registry->loadJSON($data->params);
				$data->params = clone $this->getState('params');
				$data->params->merge($registry);

				$this->_item[$pk] = $data;
			}
			catch (JException $e)
			{
				$this->setError($e);
				$this->_item[$pk] = false;
			}
		}

		return $this->_item[$pk];
	}

	/**
	 * get a package table object
	 *
	 * @return  object connection tables
	 */
	public function &getPackage()
	{
		if (!isset($this->package))
		{
			$this->package = FabTable::getInstance('Package', 'FabrikTable');
			$this->package->load($this->id);

			// Forms can currently only be set from form module
			$this->package->forms = '';
		}

		return $this->package;
	}

	/**
	 * Render the package in the front end
	 *
	 * @return  void
	 */
	public function render()
	{
		// Test stuff needs to be assigned in admin
		$this->_blocks = array();

		return;
	}

	/**
	 * Load in the tables associated with the package
	 *
	 * @return  array
	 */
	protected function loadTables()
	{
		if ($this->package->tables != '')
		{
			$aIds = explode(',', $this->package->tables);

			foreach ($aIds as $id)
			{
				$viewModel = JModelLegacy::getInstance('view', 'FabrikFEModel');
				$viewModel->setId($id);
				$this->tables[] = $viewModel->getTable();
				$formModel = $viewModel->getFormModel();
				$this->forms[] = $formModel->getForm();
			}
		}

		return $this->tables;
	}

	/**
	 * (un)publish the package & all its tables
	 *
	 * @param   int  $state  State
	 *
	 * @return  void
	 */
	public function publish($state)
	{
		foreach ($this->tables as $oTable)
		{
			$oTable->publish($oTable->id, $state);
		}

		parent::publish($this->id, $state);
	}
}

/**
 * Package Menu
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikPackageMenu extends JModelLegacy
{
	/**
	 * Method to set the  id
	 *
	 * @param   int  $id  ID number
	 *
	 * @return  void
	 */
	public function setId($id)
	{
		// Set new form ID
		$this->id = $id;
	}

	/**
	 * Render
	 *
	 * @return string
	 */
	public function render()
	{
		return "menu items to go here";
	}
}
