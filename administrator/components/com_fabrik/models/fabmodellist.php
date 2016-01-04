<?php
/**
 * Fabrik Admin List Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modellist');

/**
 * Fabrik Admin List Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabModelList extends JModelList
{
	/**
	 * Constructor.
	 * Ensure that we use the fabrik db model for the dbo
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 */
	public function __construct($config = array())
	{
		$config['dbo'] = FabrikWorker::getDbo(true);
		parent::__construct($config);
	}

	/**
	 * Get an array of objects to populate the form filter dropdown
	 *
	 * @deprecated
	 *
	 * @return  array  option objects
	 */
	public function getFormOptions()
	{
		// Initialise variables.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('id AS value, label AS text');
		$query->from('#__{package}_forms')->where('published <> -2');
		$query->order('label ASC');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		return $rows;
	}

	/**
	 * Get an array of objects to populate the package/apps dropdown list
	 *
	 * @since 3.0.5
	 * @deprecated
	 *
	 * @return  array    value/text objects
	 */
	public function getPackageOptions()
	{
		// Initialise variables. Always use J db here no matter what package we are using
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('DISTINCT component_name AS value, label AS text');
		$query->from('#__fabrik_packages')->where('external_ref = 1');
		$query->order('label ASC');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		return $rows;
	}

	/**
	 * Doesnt seem to be used 3.0.6
	 *
	 * @deprecated
	 *
	 * @return  array
	 */
	public function getGroupOptions()
	{
		// Initialise variables.
		$db     = $this->getDbo();
		$query  = $db->getQuery(true);
		$formId = $this->getState('filter.form');

		// Select the required fields from the table.
		$query->select('g.id AS value, g.name AS text');
		$query->from('#__{package}_groups AS g');
		$query->where('published <> -2');

		if ($formId !== '')
		{
			$query->join('INNER', '#__{package}_formgroup AS fg ON fg.group_id = g.id');
			$query->where('fg.form_id = ' . (int) $formId);
		}

		$query->order('g.name ASC');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		return $rows;
	}

	/**
	 * Build the part of the list query that deals with filtering by form
	 *
	 * @param   JDatabaseQuery &$query partial query
	 * @param   string         $table  db table
	 *
	 * @return  void
	 */
	protected function filterByFormQuery(&$query, $table)
	{
		$form = $this->getState('filter.form');

		if (!empty($form))
		{
			$query->where($table . '.form_id = ' . (int) $form);
		}
	}

	/**
	 * Method to auto-populate the model state.
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string $ordering  An optional ordering field.
	 * @param   string $direction An optional direction (asc|desc).
	 *
	 * @since    1.6
	 *
	 * @return  void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication('administrator');

		// Load the package state
		$package = $app->getUserStateFromRequest('com_fabrik.package', 'package', '');
		$this->setState('com_fabrik.package', $package);

		// List state information.
		parent::populateState($ordering, $direction);
	}
}
