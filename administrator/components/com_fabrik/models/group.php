<?php
/**
 * Fabrik Admin Group Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once 'fabmodeladmin.php';

/**
 * Fabrik Admin Group Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikAdminModelGroup extends FabModelAdmin
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 */
	protected $text_prefix = 'COM_FABRIK_GROUP';

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable	A database object
	 */

	public function getTable($type = 'Group', $prefix = 'FabrikTable', $config = array())
	{
		$config['dbo'] = FabrikWorker::getDbo(true);

		return FabTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array  $data      Data for the form.
	 * @param   bool   $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed	A JForm object on success, false on failure
	 */

	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_fabrik.group', 'group', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed	The data for the form.
	 */

	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_fabrik.edit.group.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Take an array of forms ids and return the corresponding group ids
	 * used in list publish code
	 *
	 * @param   array  $ids  form ids
	 *
	 * @return  string
	 */

	public function swapFormToGroupIds($ids = array())
	{
		if (empty($ids))
		{
			return array();
		}

		JArrayHelper::toInteger($ids);
		$db = FabrikWorker::getDbo();
		$query = $db->getQuery(true);
		$query->select('group_id')->from('#__{package}_formgroup')->where('form_id IN (' . implode(',', $ids) . ')');
		$db->setQuery($query);
		$res = $db->loadColumn();

		return $res;
	}

	/**
	 * Does the group have a primary key element
	 *
	 * @param   array  $data  jform posted data
	 *
	 * @return  bool
	 */

	protected function checkRepeatAndPK($data)
	{
		$groupModel = JModelLegacy::getInstance('Group', 'FabrikFEModel');
		$groupModel->setId($data['id']);
		$listModel = $groupModel->getListModel();
		$pk = FabrikString::safeColName($listModel->getTable()->db_primary_key);
		$elementModels = $groupModel->getMyElements();

		foreach ($elementModels as $elementModel)
		{
			if (FabrikString::safeColName($elementModel->getFullName(false, false)) == $pk)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 */

	public function save($data)
	{
		if ($data['id'] == 0)
		{
			$user = JFactory::getUser();
			$data['created_by'] = $user->get('id');
			$data['created_by_alias'] = $user->get('username');
			$data['created'] = JFactory::getDate()->toSql();
		}

		$makeJoin = false;
		$unMakeJoin = false;

		if ($this->checkRepeatAndPK($data))
		{
			$makeJoin = ($data['params']['repeat_group_button'] == 1);

			if ($makeJoin)
			{
				$data['is_join'] = 1;
			}
			elseif ($data['is_join'] == 1)
			{
				/*
				 * $$$ rob - this was destroying legitimate joins on saving the group
				 * see http://fabrikar.com/forums/showthread.php?t=29385
				 * commenting out for now until Hugh can take another look at what ever he was trying to solve
				 * in commit #ee697dd
				 *
				$unMakeJoin = true;
				$data['is_join'] = 0;
				*/
			}
		}
		else
		{
			if (($data['params']['repeat_group_button'] == 1))
			{
				$data['params']['repeat_group_button'] = 0;
				JFactory::getApplication()->enqueueMessage('You can not set the group containing the list primary key to be repeatable', 'notice');
			}
		}

		$data['params'] = json_encode($data['params']);
		$return = parent::save($data);
		$data['id'] = $this->getState($this->getName() . '.id');

		if ($return)
		{
			$this->makeFormGroup($data);

			if ($makeJoin)
			{
				/**
				 * $$$ rob added this check as otherwise toggling group from repeat
				 * to norepeat back to repeat incorrectly created a 2nd join
				 */
				if (!$this->joinedGroupExists($data['id']))
				{
					$return = $this->makeJoinedGroup($data);
				}
				// Update for the is_join change
				if ($return)
				{
					$return = parent::save($data);
				}
			}
			else
			{
				// $data['is_join'] =  0; // NO! none repeat joined groups were getting unset here - not right!
				if ($unMakeJoin)
				{
					$this->unMakeJoinedGroup($data);
				}

				$return = parent::save($data);
			}
		}

		parent::cleanCache('com_fabrik');

		return $return;
	}

	/**
	 * Check if a group id has an associated join already created
	 *
	 * @param   int  $id  group id
	 *
	 * @return  boolean
	 */

	protected function joinedGroupExists($id)
	{
		$item = FabTable::getInstance('Group', 'FabrikTable');
		$item->load($id);

		return $item->join_id == '' ? false : true;
	}

	/**
	 * Clears old form group entries if found and adds new ones
	 *
	 * @param   array  $data  jform data
	 *
	 * @return void
	 */

	protected function makeFormGroup($data)
	{
		if ($data['form'] == '')
		{
			return;
		}

		$formid = (int) $data['form'];
		$id = (int) $data['id'];
		$item = FabTable::getInstance('FormGroup', 'FabrikTable');
		$item->load(array('form_id' => $formid, 'group_id' => $id));

		if ($item->id == '')
		{
			// Get max group order
			$db = FabrikWorker::getDbo(true);
			$query = $db->getQuery(true);
			$query->select('MAX(ordering)')->from('#__{package}_formgroup')->where('form_id = ' . $formid);
			$db->setQuery($query);
			$next = (int) $db->loadResult() + 1;
			$item->ordering = $next;
			$item->form_id = $formid;
			$item->group_id = $id;
			$item->store();
		}
	}

	/**
	 * A group has been set to be repeatable but is not part of a join
	 * so we want to:
	 * Create a new db table for the groups elements ( + check if its not already there)
	 *
	 * @param   array  &$data  jform data
	 *
	 * @return  bool
	 */

	public function makeJoinedGroup(&$data)
	{
		$groupModel = JModelLegacy::getInstance('Group', 'FabrikFEModel');
		$groupModel->setId($data['id']);
		$listModel = $groupModel->getListModel();
		$pluginManager = FabrikWorker::getPluginManager();
		$db = $listModel->getDb();
		$list = $listModel->getTable();
		$elements = (array) $groupModel->getMyElements();
		$names = array();
		$fields = $listModel->getDBFields(null, 'Field');
		$names['id'] = "id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
		$names['parent_id'] = "parent_id INT(11)";

		foreach ($elements as $element)
		{
			$fname = $element->getElement()->name;
			/**
			 * if we are making a repeat group from the primary group then we don't want to
			 * overwrite the repeat group tables id definition with that of the main tables
			 */
			if (!array_key_exists($fname, $names))
			{
				$str = FabrikString::safeColName($fname);
				$field = JArrayHelper::getValue($fields, $fname);

				if (is_object($field))
				{
					$str .= " " . $field->Type . " ";

					if ($field->Null == 'NO')
					{
						$str .= "NOT NULL ";
					}

					$names[$fname] = $str;
				}
				else
				{
					$names[$fname] = $db->quoteName($fname) . ' ' . $element->getFieldDescription();
				}
			}
		}

		$db->setQuery("show tables");
		$newTableName = $list->db_table_name . '_' . $data['id'] . '_repeat';
		$existingTables = $db->loadColumn();

		if (!in_array($newTableName, $existingTables))
		{
			// No existing repeat group table found so lets create it
			$query = "CREATE TABLE IF NOT EXISTS " . $db->quoteName($newTableName) . " (" . implode(",", $names) . ")";
			$db->setQuery($query);
			$db->execute();

			// Create id and parent_id elements
			$listModel->makeIdElement($data['id']);
			$listModel->makeFkElement($data['id']);
		}
		else
		{
			if (trim($list->db_table_name) == '')
			{
				// New group not attached to a form
				$this->setError(FText::_('COM_FABRIK_GROUP_CANT_MAKE_JOIN_NO_DB_TABLE'));

				return false;
			}
			// Repeat table already created - lets check its structure matches the group elements
			$db->setQuery("DESCRIBE " . $db->quoteName($newTableName));
			$existingFields = $db->loadObjectList('Field');
			$newFields = array_diff(array_keys($names), array_keys($existingFields));

			if (!empty($newFields))
			{
				$lastfield = array_pop($existingFields);
				$lastfield = $lastfield->Field;

				foreach ($newFields as $newField)
				{
					$info = $names[$newField];
					$db->setQuery("ALTER TABLE " . $db->quoteName($newTableName) . " ADD COLUMN $info AFTER $lastfield");
					$db->execute();
				}
			}
		}
		// Create the join as well

		$jdata = array('list_id' => $list->id, 'element_id' => 0, 'join_from_table' => $list->db_table_name, 'table_join' => $newTableName,
			'table_key' => FabrikString::shortColName($list->db_primary_key), 'table_join_key' => 'parent_id', 'join_type' => 'left',
			'group_id' => $data['id']);

		// Load the matching join if found.
		$join = $this->getTable('join');
		$join->load($jdata);
		$opts = new stdClass;
		$opts->type = 'group';
		$jdata['params'] = json_encode($opts);
		$join->bind($jdata);

		// Update or save a new join
		$join->store();
		$data['is_join'] = 1;

		return true;
	}

	/**
	 * Repeat has been turned off for a group, so we need to remove the join.
	 * For now, leave the repeat table intact, just remove the join
	 * and the 'id' and 'parent_id' elements.
	 *
	 * @param   array  &$data  jform data
	 *
	 * @return boolean
	 */
	public function unMakeJoinedGroup(&$data)
	{
		if (empty($data['id']))
		{
			return false;
		}

		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->delete('#__{package}_joins')->where('group_id = ' . $data['id']);
		$db->setQuery($query);
		$return = $db->execute();

		$query = $db->getQuery(true);
		$query->select('id')->from('#__{package}_elements')->where('group_id  = ' . $data['id'] . ' AND name IN ("id", "parent_id")');
		$db->setQuery($query);
		$elids = $db->loadColumn();
		$elementModel = JModelLegacy::getInstance('Element', 'FabrikModel');
		$return = $elementModel->delete($elids);

		// Kinda meaningless return, but ...
		return $return;
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param   array  &$pks            An array of record primary keys.
	 * @param   bool   $deleteElements  delete elements?
	 *
	 * @return  bool  True if successful, false if an error occurs.
	 */

	public function delete(&$pks, $deleteElements = false)
	{
		if (empty($pks))
		{
			return true;
		}

		if (parent::delete($pks))
		{
			if ($this->deleteFormGroups($pks))
			{
				if ($deleteElements)
				{
					return $this->deleteElements($pks);
				}
				else
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Delete group elements
	 *
	 * @param   array  $pks  group ids to delete elements from
	 *
	 * @return  bool
	 */

	public function deleteElements($pks)
	{
		$db = FabrikWorker::getDbo(true);
		JArrayHelper::toInteger($pks);
		$query = $db->getQuery(true);
		$query->select('id')->from('#__{package}_elements')->where('group_id IN (' . implode(',', $pks) . ')');
		$db->setQuery($query);
		$elids = $db->loadColumn();
		$elementModel = JModelLegacy::getInstance('Element', 'FabrikAdminModel');

		return $elementModel->delete($elids);
	}

	/**
	 * Delete formgroups
	 *
	 * @param   array  $pks  group ids
	 *
	 * @return  bool
	 */

	public function deleteFormGroups($pks)
	{
		$db = FabrikWorker::getDbo(true);
		JArrayHelper::toInteger($pks);
		$query = $db->getQuery(true);
		$query->delete('#__{package}_formgroup')->where('group_id IN (' . implode(',', $pks) . ')');
		$db->setQuery($query);

		return $db->execute();
	}
}
