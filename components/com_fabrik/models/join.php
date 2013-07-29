<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

/**
 * Fabrik Join Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikFEModelJoin extends FabModel
{
	/**
	 * Join table
	 *
	 * @var object
	 */
	var $_join = null;

	/**
	 * Join id to load
	 *
	 * @var int
	 */
	var $_id = null;

	/**
	 * Data to bind to Join table
	 *
	 * @var array
	 */
	var $_data = null;

	/**
	 * Constructor
	 *
	 * @param   array  $config  An array of configuration options (name, state, dbo, table_path, ignore_request).
	 *
	 * @since       1.5
	 */

	public function __construct($config = array())
	{
		parent::__construct($config);
	}

	/**
	 * Set the join id
	 *
	 * @param   int  $id  join id
	 *
	 * @return  void
	 */

	public function setId($id)
	{
		$this->_id = $id;
	}

	/**
	 * Get the join id
	 *
	 * @return  int  join id
	 */

	public function getId()
	{
		return $this->_id;
	}

	/**
	 * Set data
	 *
	 * @param   array  $data  to set to
	 *
	 * @return  void
	 */

	public function setData($data)
	{
		$this->_data = $data;
	}

	/**
	 * Get Join
	 *
	 * @return  FabTable
	 */

	public function getJoin()
	{
		if (!isset($this->_join))
		{
			$this->_join = FabTable::getInstance('join', 'FabrikTable');
			if (isset($this->_data))
			{
				$this->_join->bind($this->_data);
			}
			else
			{
				$this->_join->load($this->_id);
			}
			if (is_string($this->_join->params))
			{
				$this->_join->params = trim($this->_join->params) == '' ? '{"type": ""}' : $this->_join->params;
				$this->_join->params = json_decode($this->_join->params);
			}
		}
		return $this->_join;
	}

	/**
	 * Clear the join
	 *
	 * @return  void
	 */

	public function clearJoin()
	{
		unset($this->_join);
	}

	/**
	 * Load the model from the element id
	 *
	 * @param   string  $key  db table key
	 * @param   int     $id   key value
	 *
	 * @return  FabTable  join
	 */

	public function getJoinFromKey($key, $id)
	{
		if (!isset($this->_join))
		{
			$db = FabrikWorker::getDbo(true);
			$this->_join = FabTable::getInstance('join', 'FabrikTable');
			$this->_join->load(array($key => $id));
		}
		return $this->_join;
	}

	/**
	 * Get join table's primary key
	 *
	 * @param   string  $glue  Between table and field name
	 *
	 * @return  string
	 */

	public function getPrimaryKey($glue = '___')
	{
		$join = $this->getJoin();
		$pk = $join->table_join . $glue . $join->table_join_key;
		return $pk;
	}

	/**
	 * Set the join element ID
	 *
	 * @param   int  $id  element id
	 *
	 * @return  void
	 */

	public function setElementId($id)
	{
		$this->join->element_id = $id;
	}

	/**
	 * deletes the loaded join and then
	 * removes all elements, groups & form group record
	 *
	 * @param   int  $groupId  the group id that the join is linked to
	 *
	 * @return void/JError
	 */

	public function deleteAll($groupId)
	{
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->delete(' #__{package}_elements')->where('group_id = ' . (int) $groupId);
		$db->setQuery($query);
		if (!$db->execute())
		{
			return JError::raiseError(500, $db->getErrorMsg());
		}
		$query->clear();
		$query->delete(' #__{package}_groups')->where('id = ' . (int) $groupId);
		$db->setQuery($query);
		if (!$db->execute())
		{
			return JError::raiseError(500, $db->getErrorMsg());
		}

		// Delete all form group records
		$query->clear();
		$query->delete(' #__{package}_formgroup')->where('group_id = ' . (int) $groupId);
		$db->setQuery($query);
		if (!$db->execute())
		{
			return JError::raiseError(500, $db->getErrorMsg());
		}
		$this->getJoin()->delete();
	}

	/**
	 * saves the table join data
	 *
	 * @param   array  $source  data to save
	 *
	 * @return  bool
	 */

	public function save($source)
	{
		if (!$this->bind($source))
		{
			return false;
		}
		if (!$this->check())
		{
			return false;
		}
		if (!$this->store())
		{
			return false;
		}
		$this->_error = '';
		return true;
	}

}
