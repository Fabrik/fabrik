<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.log
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\Worker;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Log form changes
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.log
 * @since       3.0
 */
class PlgFabrik_FormLog extends PlgFabrik_Form
{
	private $origData;

	private $newRecord;

	private $deleteRows;

	/**
	 * Render the element admin settings
	 *
	 * @param   array   $data           admin data
	 * @param   int     $repeatCounter  repeat plugin counter
	 * @param   string  $mode           how the fieldsets should be rendered currently support 'nav-tabs' (@since 3.1)
	 *
	 * @return  string	admin html
	 *
	 * @since
	 */
	public function onRenderAdminSettings($data = array(), $repeatCounter = null, $mode = null)
	{
		$this->install();

		return parent::onRenderAdminSettings($data, $repeatCounter, $mode);
	}

	/**
	 * Install the plugin db table
	 *
	 * @return  void
	 *
	 * @since
	 */
	public function install()
	{
		try
		{
			$db    = FabrikWorker::getDbo();
			$query = <<<EOT
CREATE TABLE IF NOT EXISTS `#__{package}_change_log_fields` (
	`id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`parent_id` INT( 11 ) NOT NULL,
	`user_id` INT( 11 ) NOT NULL ,
	`time_date` DATETIME NOT NULL ,
	`form_id` INT( 11 ) NOT NULL,
    `list_id` INT( 11 ) NOT NULL,
    `element_id` INT( 11 ) NOT NULL,
	`row_id` INT( 11 ) NOT NULL,
	`join_id` INT( 11 ),
	`pk_id` INT( 11 ) NOT NULL,
	`table_name` VARCHAR( 256 ) NOT NULL,
	`field_name` VARCHAR( 256 ) NOT NULL,
	`log_type_id` INT( 11 ) NOT NULL,
	`orig_value` TEXT,
	`new_value` TEXT
);
EOT;
			$db->setQuery($query);
			$db->execute();

			$query = <<<EOT
CREATE TABLE IF NOT EXISTS `#__{package}_change_log` (
     `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
     `user_id` INT( 11 ) NOT NULL ,
     `ip_address` CHAR( 14 ) NOT NULL ,
     `referrer` TEXT,
     `time_date` DATETIME NOT NULL ,
     `form_id` INT( 11 ) NOT NULL,
     `list_id` INT( 11 ) NOT NULL,
     `row_id` INT( 11 ) NOT NULL,
     `join_id` INT( 11 ),
     `log_type_id` INT( 11 ) NOT NULL,
     `parent_id` INT( 11 ) NOT NULL,
);
EOT;

			$db->setQuery($query);
			$db->execute();

			$query = <<<EOT
CREATE TABLE IF NOT EXISTS `#__{package}_change_log_types` (
     `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `type` VARCHAR( 32 ) NOT NULL
);
EOT;

			$db->setQuery($query);
			$db->execute();

			$query = <<<EOT
INSERT IGNORE INTO `#__{package}_change_log_types` (id, type)
VALUES
       (1, 'Add Row'),
       (2, 'Edit Row'),
       (3, 'Delete Row'),
       (4, 'Submit Form'),
       (5, 'Load Form'),
       (6, 'Delete Row'),
       (7, 'Add Joined Row'),
       (8, 'Delete Joined Row'),
       (9, 'Field Value Change'),
       (10, 'Edit Joined Row'),
       (11, 'Load Details')
EOT;

			$db->setQuery($query);
			$db->execute();
		}
		catch (Exception $e)
		{
			Worker::log('fabrik.form.log.err', "Error creating log plugin tables: " . $e->getMessage());
			$this->app->enqueueMessage('Error creating log plugin tables: ' . $e->getMessage());
		}
	}


	/**
	 * Run when the form loads
	 *
	 * @return  bool
	 *
	 * @since
	 */
	public function onLoad()
	{
		$params    = $this->getParams();
		/** @var FabrikFEModelForm $formModel */
		$formModel = $this->getModel();
		$rowId = $formModel->getRowId();

		if ($formModel->isEditable())
		{
			if ($params->get('log_form_load', '0') !== '0')
			{
				$this->logChange(5, $rowId);
			}
		}
		else
		{
			if ($params->get('log_form_load', '0') === '2')
			{
				$this->logChange(11, $rowId);
			}
		}

		return true;
	}

	/**
	 * @param array $groups
	 *
	 * @return bool|void
	 */
	public function onDeleteRowsFormTest(&$groups)
	{
		return true;
	}

	/**
	 * @param array $groups
	 *
	 * @return bool|void
	 */
	//public function onAfterDeleteRowsForm(&$data)
	public function onDeleteRowsForm(&$data)
	{
		$params = $this->getParams();
		$logDelete = $params->get('log_delete', '1');

		if ($logDelete === '0')
		{
			return;
		}

		/** @var FabrikFEModelForm $formModel */
		$formModel = $this->getModel();
		$listModel = $formModel->getListModel();
		$listParams = $listModel->getParams();
		$deleteJoinedRows = $listParams->get('delete-joined-rows', '0') === '1';
		$mergeJoinedData = $listModel->mergeJoinedData();
		$fields = $this->getFields();
		$mode = $params->get('log_mode', '1');
		$groupModels = $formModel->getGroupsHiarachy();
		$date = new \JDate();
		$pks = [];
		$joinsSeen = [];
		$changes = [];

		foreach ($data as $group)
		{
			foreach ($group as $rows)
			{
				foreach ($rows as $row)
				{
					if (!array_key_exists($row->__pk_val, $pks))
					{
						$pks[$row->__pk_val] = [
							'row_changes' => [],
							'joins' => []
						];
					}

					foreach ($groupModels as $groupModel)
					{
						if (!$groupModel->isJoin())
						{
							if ($logDelete === '2')
							{
								$elementModels = $groupModel->getPublishedElements();

								foreach ($elementModels as $elementModel)
								{
									$fullKey = $elementModel->getFullName(true, false);

									if ($mode === 'exclude' && in_array($fullKey, $fields))
									{
										continue;
									}
									else if ($mode === 'include' && !in_array($fullKey, $fields))
									{
										continue;
									}

									$fullKeyRaw = $fullKey . '_raw';

									if (isset($row->$fullKeyRaw) && !$this->dataEmpty($row->$fullKeyRaw))
									{
										if (!array_key_exists($fullKeyRaw, $pks[$row->__pk_val]['row_changes']))
										{
											$pks[$row->__pk_val]['row_changes'][$fullKeyRaw] = array(
												'time_date'   => $date->format('Y-m-d H:i:s'),
												'form_id'     => $formModel->getId(),
												'list_id'     => $formModel->getListModel()->getId(),
												'element_id'  => $elementModel->getId(),
												'row_id'      => $row->__pk_val,
												'pk_id'       => $row->__pk_val,
												'table_name'  => $listModel->getTable()->db_table_name,
												'orig_value'  => $row->$fullKeyRaw,
												'new_value'   => '',
												'field_name'  => $elementModel->element->name,
												'log_type_id' => 3
											);
										}
									}
								}
							}
						}
						else
						{
							if ($deleteJoinedRows)
							{
								/** @var FabrikFEModelJoin $join */
								$join = $groupModel->getJoinModel();
								$joinId = $join->getId();

								$pk = $join->getForeignID();
								$pkRaw = $pk . '_raw';
								$thisPks = isset($row->$pkRaw) ? $row->$pkRaw : '';
								$thisPks = Worker::JSONtoData($thisPks, true);

								if (!\Fabrik\Helpers\ArrayHelper::emptyIsh($thisPks))
								{
									if (!array_key_exists($joinId, $pks[$row->__pk_val]['joins']))
									{
										$pks[$row->__pk_val]['joins'][$joinId] = [
											'join' => $join,
											'row_changes' => []
										];
									}

									$elementModels = $groupModel->getPublishedElements();

									foreach ($thisPks as $k => $thisPk)
									{
										if (!array_key_exists($thisPk, $pks[$row->__pk_val]['joins'][$joinId]['row_changes']))
										{
											$pks[$row->__pk_val]['joins'][$joinId]['row_changes'][$thisPk] = [];
										}

										foreach ($elementModels as $elementModel)
										{
											$fullKey = $elementModel->getFullName(true, false);

											if ($mode === 'exclude' && in_array($fullKey, $fields))
											{
												continue;
											}
											else if ($mode === 'include' && !in_array($fullKey, $fields))
											{
												continue;
											}

											$fullKeyRaw = $fullKey . '_raw';
											$values     = isset($row->$fullKeyRaw) ? $row->$fullKeyRaw : '';
											$values     = Worker::JSONtoData($values, true);
											$value      = \Fabrik\Helpers\ArrayHelper::getValue($values, $k, '');

											if (!$this->dataEmpty($value))
											{
												if (!array_key_exists($fullKeyRaw, $pks[$row->__pk_val]['joins'][$joinId]['row_changes'][$thisPk]))
												{
													$pks[$row->__pk_val]['joins'][$joinId]['row_changes'][$thisPk][$fullKeyRaw] = array(
														'time_date'   => $date->format('Y-m-d H:i:s'),
														'form_id'     => $formModel->getId(),
														'list_id'     => $formModel->getListModel()->getId(),
														'element_id'  => $elementModel->getId(),
														'row_id'      => $row->__pk_val,
														'join_id'     => $joinId,
														'pk_id'       => $thisPk,
														'table_name'  => $join->getJoin()->table_join,
														'orig_value'  => $value,
														'new_value'   => '',
														'field_name'  => $elementModel->element->name,
														'log_type_id' => 8
													);
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		foreach ($pks as $pkVal => $seen)
		{
			$logId = $this->logDeleteChanges($pkVal, $seen['row_changes'], null);

			foreach ($seen['joins'] as $joinId => $joinSeen)
			{
				foreach ($joinSeen['row_changes'] as $rowId => $changes)
				{
					$this->logDeleteChanges($rowId, $changes, $joinId, $logId);
				}
			}
		}

		return true;
	}

	private function logDeleteChanges($rowId, $changes, $joinId = null, $parentId = null)
	{
		$params = $this->getParams();
		$logDelete = $params->get('log_delete', '1');
		$logId = null;

		if ($logDelete !== '0')
		{
			$logTypeId = isset($joinId) ? 8 : 3;
			/** @var FabrikFEModelForm $formModel */
			$logId     = $this->logChange($logTypeId, $rowId, $joinId, $parentId);

			if ($logDelete === '2' && !empty($logId))
			{
				$db    = JFactory::getDbo();
				$query = $db->getQuery($db);

				foreach ($changes as $change)
				{
					$query->clear()
						->insert('#__fabrik_change_log_fields')
						->set('parent_id = ' . (int) $logId);

					foreach ($change as $key => $value)
					{
						$query->set($db->qn($key) . ' = ' . $db->quote($value));
					}

					$db->setQuery($query);

					try
					{
						$db->execute();
					}
					catch (Exception $e)
					{
						Worker::log('fabrik.form.log.err', "Error writing database for log: " . $e->getMessage());
					}
				}
			}
		}

		return $logId;
	}

	/**
	 * Make sure we get our own copy of origData, the "raw" table data, prior to any updates
	 *
	 * @return    bool
	 */
	public function onBeforeProcess()
	{
		/** @var FabrikFEModelForm $formModel */
		$formModel = $this->getModel();
		$this->newRecord = empty($formModel->origRowId);
		$this->origData = $formModel->getOrigData();

		return true;
	}

	/**
	 * Test to see if empty or null, so we don't log a change of null to empty
	 *
	 * @param $data
	 *
	 * @return bool
	 *
	 * @since version
	 */
	private function dataEmpty($data)
	{
		return $data === null || $data === '';
	}

	/**
	 * Do the comparison between origData and new data
	 *
	 * @return    bool
	 */
	public function onAfterProcess()
	{
		$params = $this->getParams();
		/** @var FabrikFEModelForm $formModel */
		$formModel = $this->getModel();
		$type      = empty($formModel->origRowId) ? 'form.submit.add' : 'form.submit.edit';
		$formModel->setJoinData($this->origData);
		$rowId = $formModel->getRowId();
		$rowId = empty($rowId) ? $this->app->input->get('rowid', '') : $rowId;
		$data = $this->getNewData($rowId);
		$fields = $this->getFields();
		$mode = $params->get('log_mode', '1');
		$groups = $formModel->getGroupsHiarachy();
		$changes = array();
		$date = new \JDate();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			$origPks = [];
			$thisPks = [];
			$deletedPks = [];
			$groupIndexMap = [];
			$pk = '';
			$join = false;

			if ($groupModel->canRepeat())
			{
				/** @var FabrikFEModelJoin $join */
				$join = $groupModel->getJoinModel();
				$pk = $join->getForeignID();
				$origPks = ArrayHelper::getValue($this->origData, $pk, []);
				$thisPks = ArrayHelper::getValue($data, $pk, []);

				foreach ($origPks as $k => $origPk)
				{
					if (!in_array($origPk, $thisPks))
					{
						$deletedPks[$k] = $origPk;
					}
					else
					{
						$groupIndexMap[array_search($origPk, $thisPks)] = $k;
					}
				}

				foreach ($thisPks as $k => $thisPk)
				{
					if (!in_array($thisPk, $origPks))
					{
						$newPks[$k] = $thisPk;
					}
				}
			}

			foreach ($elementModels as $elementModel)
			{
				$fullKey = $elementModel->getFullName(true, false);

				if ($mode === 'exclude' && in_array($fullKey, $fields))
				{
					continue;
				}
				else if ($mode === 'include' && !in_array($fullKey, $fields))
				{
					continue;
				}

				if ($groupModel->canRepeat())
				{
					foreach ($deletedPks as $k => $pkVal)
					{
						$orig      = ArrayHelper::getValue($this->origData, $fullKey);
						$origValue = ArrayHelper::getValue($orig, $groupIndexMap[$k]);

						$changes[] = array(
							'time_date' => $date->format('Y-m-d H:i:s'),
							'form_id' => $formModel->getId(),
							'list_id' => $formModel->getListModel()->getId(),
							'element_id' => $elementModel->getId(),
							'row_id' => $rowId,
							'join_id' => $join->getId(),
							'pk_id' => $pkVal,
							'table_name' => $join->getJoin()->table_join,
							'orig_value' => $origValue,
							'new_value' => '',
							'field_name' => $elementModel->element->name,
							'log_type_id' => 8
						);
					}

					foreach ($data[$fullKey] as $k => $newValue)
					{
						$force = false;

						if (!$this->newRecord && array_key_exists($k, $groupIndexMap))
						{
							$orig      = ArrayHelper::getValue($this->origData, $fullKey);
							$origValue = ArrayHelper::getValue($orig, $groupIndexMap[$k]);
							$pkVal = ArrayHelper::getValue($this->origData[$pk], $groupIndexMap[$k], '');
							$changeTypeId = 10;
						}
						else
						{
							$changeTypeId = 7;
							$origValue = '';
							$pkVal = '';
							$force = true;
						}

						if ($force || $newValue !== $origValue)
						{
							if (!$force && $this->dataEmpty($newValue) && $this->dataEmpty($origValue))
							{
								continue;
							}

							$changes[] = array(
								'time_date' => $date->format('Y-m-d H:i:s'),
								'form_id' => $formModel->getId(),
								'list_id' => $formModel->getListModel()->getId(),
								'element_id' => $elementModel->getId(),
								'row_id' => $rowId,
								'pk_id' => $pkVal,
								'table_name' => $join->getJoin()->table_join,
								'orig_value' => $origValue,
								'new_value' => $newValue,
								'field_name' => $elementModel->element->name,
								'log_type_id' => $changeTypeId
							);
						}
					}
				}
				else
				{
					$force = false;

					if ($this->newRecord)
					{
						$force = true;
						$origValue = '';
						$changeTypeId = 1;
					}
					else
					{
						$origValue = $this->origData[$fullKey];
						$changeTypeId = 2;
					}

					if ($force || $data[$fullKey] !== $origValue)
					{
						if (!$force && $this->dataEmpty($data[$fullKey]) && $this->dataEmpty($origValue))
						{
							continue;
						}

						$changes[] = array(
							'time_date' => $date->format('Y-m-d H:i:s'),
							'form_id' => $formModel->getId(),
							'list_id' => $formModel->getListModel()->getId(),
							'element_id' => $elementModel->getId(),
							'row_id' => $rowId,
							'pk_id' => $rowId,
							'table_name' => $elementModel->getFormModel()->getListModel()->getTable()->db_table_name,
							'orig_value' => $origValue,
							'new_value' => $data[$fullKey],
							'field_name' => $elementModel->element->name,
							'log_type_id' => $changeTypeId
						);
					}
				}
			}
		}

		$this->logSubmitChanges($changes);

		return true;
	}

	/**
	 * @param $logTypeId
	 *
	 * @return bool|mixed
	 */
	protected function logChange($logTypeId, $rowId, $joinId = null, $parentId = null)
	{
		/** @var FabrikFEModelForm $formModel */
		$formModel = $this->getModel();
		$date = new \JDate();
		$db = JFactory::getDbo();
		$query = $db->getQuery($db);

		$query->insert('#__fabrik_change_log')
			->set('time_date = ' . $db->quote($date->format('Y-m-d H:i:s')))
			->set('user_id = ' . (int)$this->user->get('id'))
			->set('ip_address = ' . $db->quote($this->app->input->server->getRaw('REMOTE_ADDR', '')))
			->set('referrer = ' . $db->quote($this->app->input->server->getRaw('HTTP_REFERER' . '')))
			->set('form_id = ' . (int)$formModel->getId())
			->set('list_id = ' . (int)$formModel->getListModel()->getId())
			->set('row_id = ' . (int)$rowId)
			->set('log_type_id = ' . (int)$logTypeId);

		if (isset($joinId))
		{
			$query->set('join_id = ' . (int)$joinId);
		}

		if (isset($parentId))
		{
			$query->set('parent_id = ' . (int)$parentId);
		}

		$db->setQuery($query);

		try {
			$db->execute();
			$logId = $db->insertid();
		}
		catch (Exception $e)
		{
			Worker::log('fabrik.form.log.err', "Error writing database for log: " . $e->getMessage());
			$logId = false;
		}

		return $logId;
	}

	protected function logSubmitChanges($changes)
	{
		$params = $this->getParams();
		$formModel = $this->getModel();
		$rowId = $formModel->getRowId();
		$rowId = empty($rowId) ? $this->app->input->get('rowid', '') : $rowId;

		if ($params->get('log_submit', '0') === '1')
		{
			// log the form submission
			$logId = self::logChange(4, $rowId);
		}

		// if changes, log the changes
		if (!empty($changes))
		{
			/** @var FabrikFEModelForm $formModel */
			$logId = $this->logChange(
				$this->newRecord ? 1 : 2,
				$rowId
			);

			if (!empty($logId))
			{
				$db    = JFactory::getDbo();
				$query = $db->getQuery($db);

				foreach ($changes as $change)
				{
					$query->clear()
						->insert('#__fabrik_change_log_fields')
						->set('parent_id = ' . (int) $logId);

					foreach ($change as $key => $value)
					{
						$query->set($db->qn($key) . ' = ' . $db->quote($value));
					}

					$db->setQuery($query);

					try {
						$db->execute();
					}
					catch (Exception $e)
					{
						Worker::log('fabrik.form.log.err', "Error writing database for log: " . $e->getMessage());
					}
				}
			}
		}
	}

	protected function getFields()
	{
		$params = $this->getParams();
		$fields = $params->get('log_fields', '');
		$fields = explode(',', $fields);

		foreach ($fields as &$field)
		{
			$field = trim($field);
			$field = str_replace('.', '___', $field);
		}

		return $fields;
	}

	protected function getIncludes()
	{
		$params = $this->getParams();
		$includes = $params->get('log_include_fields', '');
		$includes = explode(',', $includes);

		foreach ($includes as &$include)
		{
			$include = trim($include);
			$include = str_replace('.', '___', $include);
		}

		return $includes;
	}

	/**
	 * Get new data
	 *
	 * @param $rowId int
	 *
	 * @return  array
	 */
	protected function getNewData($rowId)
	{
		/** @var FabrikFEModelForm $formModel */
		$formModel = $this->getModel();
		$new = false;

		if ($this->newRecord)
		{
			$new = true;
			$formModel->rowId = $rowId;
		}

		$listModel = $formModel->getListModel();
		$fabrikDb  = $listModel->getDb();
		$sql       = $formModel->buildQuery();
		$fabrikDb->setQuery($sql);
		$data = $fabrikDb->loadObjectList();
		$formModel->setJoinData($data);

		if ($new)
		{
			$formModel->rowId = null;
		}

		return $data;
	}

	/**
	 * Make a standard log message
	 *
	 * @param   string $result_compare Not sure?!
	 *
	 * @return  string  json encoded objects
	 */
	protected function makeStandardMessage($result_compare)
	{
		$params = $this->getParams();
		$input  = $this->app->input;
		$msg    = new stdClass;

		if ($params->get('log_record_ip') == 1)
		{
			$msg->ip = FabrikString::filteredIp();
		}

		if ($params->get('log_record_useragent') == 1)
		{
			$msg->userAgent = $input->server->getString('HTTP_USER_AGENT');
		}

		if ($params->get('compare_data') == 1)
		{
			$result_compare  = preg_replace('/<br\/>/', '- ', $result_compare);
			$msg->comparison = preg_replace('/\\n/', '- ', $result_compare);
		}

		return json_encode($msg);
	}
}
