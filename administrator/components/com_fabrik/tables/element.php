<?php
/**
 * Element Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/tables/fabtable.php';

/**
 * Element Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikTableElement extends FabTable
{
	/**
	 * @var string
	 */
	public $label = '';

	/**
	 * @var bool
	 */
	public $published = true;

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * Construct
	 *
	 * @param   JDatabaseDriver  &$db  database object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__{package}_elements', 'id', $db);
	}

	/**
	 * Method to compute the default name of the asset.
	 * The default name is in the form table_name.id
	 * where id is the value of the primary key of the table.
	 *
	 * @return  string
	 */
	protected function _getAssetName()
	{
		$k = $this->_tbl_key;

		return 'com_fabrik.element.' . (int) $this->$k;
	}

	/**
	 * Method to return the title to use for the asset table.
	 *
	 * @return  string
	 */
	protected function _getAssetTitle()
	{
		return $this->label;
	}

	/**
	 * Overloaded bind function
	 *
	 * @param   array  $array   hash named array
	 * @param   array  $ignore  An optional array or space separated list of properties to ignore while binding.
	 *
	 * @return  null|string	 null is operation was satisfactory, otherwise returns an error
	 */
	public function bind($array, $ignore = array())
	{
		// Bind the rules.
		if (isset($array['rules']) && is_array($array['rules']))
		{
			$rules = new JAccessRules($array['rules']);
			$this->setRules($rules);
		}

		if (isset($array['params']) && is_array($array['params']))
		{
			$registry = new Registry;
			$registry->loadArray($array['params']);
			$array['params'] = (string) $registry;
		}

		return parent::bind($array, $ignore);
	}

	/**
	 * Method to set the show_in_list_view state for a row or list of rows in the database
	 * table.  The method respects checked out rows by other users and will attempt
	 * to check-in rows that it can after adjustments are made.
	 *
	 * @param   mixed    $pks     An optional array of primary key values to update.
	 * If not set the instance property value is used.
	 * @param   integer  $state   The publishing state. e.g. [0 = unpublished, 1 = published]
	 * @param   integer  $userId  The user id of the user performing the operation.
	 *
	 * @throws Exception
	 *
	 * @return  boolean	True on success.
	 */
	public function addToListView($pks = null, $state = 1, $userId = 0)
	{
		// Initialise variables.
		$k = $this->getKeyName();

		// Sanitize input.
		$pks = ArrayHelper::toInteger($pks);
		$userId = (int) $userId;
		$state = (int) $state;

		// If there are no primary keys set check to see if the instance key is set.
		if (empty($pks))
		{
			if ($this->$k)
			{
				$pks = array($this->$k);
			}
			// Nothing to set publishing state on, return false.
			else
			{
				throw new Exception(FText::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));
			}
		}

		// Update the publishing state for rows with the given primary keys.
		$query = $this->_db->getQuery(true);
		$query->update($this->_tbl);
		$query->set('show_in_list_summary = ' . (int) $state);

		// Determine if there is checkin support for the table.
		if (property_exists($this, 'checked_out') || property_exists($this, 'checked_out_time'))
		{
			$query->where('(checked_out = 0 OR checked_out = ' . (int) $userId . ')');
			$checkIn = true;
		}
		else
		{
			$checkIn = false;
		}

		// Build the WHERE clause for the primary keys.
		$query->where($k . ' = ' . implode(' OR ' . $k . ' = ', $pks));

		$this->_db->setQuery($query);

		// Check for a database error.
		if (!$this->_db->execute())
		{
			throw new Exception(JText::sprintf('JLIB_DATABASE_ERROR_PUBLISH_FAILED', get_class($this)));
		}

		// If check-in is supported and all rows were adjusted, check them in.
		if ($checkIn && (count($pks) == $this->_db->getAffectedRows()))
		{
			// Check-in the rows.
			foreach ($pks as $pk)
			{
				$this->checkin($pk);
			}
		}

		// If the JTable instance value is in the list of primary keys that were set, set the instance.
		if (in_array($this->$k, $pks))
		{
			$this->published = $state;
		}

		return true;
	}
}
