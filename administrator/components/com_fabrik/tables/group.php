<?php
/**
 * Group Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/tables/fabtable.php';

/**
 * Group Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikTableGroup extends FabTable
{
	/**
	 * Join ID - not sure its used?
	 * @var int
	 */
	public $join_id = null;

	public $is_join = false;

	public $params = '';

	public $id = null;

	public $name = '';

	public $label = '';

	public $css = '';
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  database object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__{package}_groups', 'id', $db);
	}

	/**
	 * Overloaded check function
	 *
	 * @return  bool
	 */
	public function check()
	{
		if (trim($this->name) == '')
		{
			$this->_error = FText::_("YOUR GROUP MUST CONTAIN A NAME");

			return false;
		}

		return true;
	}

	/**
	 * Method to load a row from the database by primary key and bind the fields
	 * to the JTable instance properties.
	 *
	 * @param   mixed    $keys   An optional primary key value to load the row by, or an array of fields to match.  If not
	 * set the instance property value is used.
	 * @param   boolean  $reset  True to reset the default values before loading the new row.
	 *
	 * @return  boolean  True if successful. False if row not found or on error (internal error state set in that case).
	 */
	public function load($keys = null, $reset = true)
	{
		if (empty($keys))
		{
			// If empty, use the value of the current key
			$keyName = $this->_tbl_key;
			$keyValue = $this->$keyName;

			// If empty primary key there's is no need to load anything
			if (empty($keyValue))
			{
				return true;
			}

			$keys = array($keyName => $keyValue);
		}
		elseif (!is_array($keys))
		{
			// Load by primary key.
			$keys = array($this->_tbl_key => $keys);
		}

		if ($reset)
		{
			$this->reset();
		}

		$db = $this->getDBO();
		$query = $db->getQuery(true);
		$query->select('#__{package}_groups.*, #__{package}_joins.id AS join_id')->from($this->_tbl)
			->join('LEFT', '#__{package}_joins ON #__{package}_groups.id = #__{package}_joins.group_id');

		foreach ($keys as $field => $value)
		{
			$query->where($db->qn('#__{package}_groups') . '.' . $db->qn($field) . ' = ' . $db->q($value));
		}

		$query->where(" (( element_id = 0 OR is_join = 0) OR element_id IS NULL)");
		$db->setQuery($query);
		$row = $db->loadAssoc();

		// Check that we have a result.
		if (empty($row))
		{
			return false;
		}

		// Bind the object with the row and return.
		return $this->bind($row);
	}

	/**
	 * Method to store a row in the database from the JTable instance properties.
	 * If a primary key value is set the row with that primary key value will be
	 * updated with the instance property values.  If no primary key value is set
	 * a new row will be inserted into the database with the properties from the
	 * JTable instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 */
	public function store($updateNulls = false)
	{
		unset($this->join_id);

		return parent::store($updateNulls);
	}
}
