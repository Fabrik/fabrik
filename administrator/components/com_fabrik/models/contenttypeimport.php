<?php
/**
 * Fabrik Admin Content Type Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.3.5
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once 'fabmodeladmin.php';

// Tmp fix until https://issues.joomla.org/tracker/joomla-cms/7378 is available (should be Joomla 3.5.0)
require JPATH_COMPONENT_ADMINISTRATOR . '/models/databaseimporter.php';
require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/contenttype.php';

use Joomla\Utilities\ArrayHelper;
use \Joomla\Registry\Registry;

/**
 * Fabrik Admin Content Type Import Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.4
 */
class FabrikAdminModelContentTypeImport extends FabModelAdmin
{
	/**
	 * Include paths for searching for Content type XML files
	 *
	 * @var    array
	 */
	private static $_contentTypeIncludePaths = array();

	/**
	 * Content type DOM document
	 *
	 * @var DOMDocument
	 */
	private $doc;

	/**
	 * Admin List model
	 *
	 * @var FabrikAdminModelList
	 */
	private $listModel;

	/**
	 * This site's view levels
	 *
	 * @var array
	 */
	private $viewLevels;

	/**
	 * This site's user groups
	 *
	 * @var array
	 */
	private $groups;

	/**
	 * Array of created join ids
	 *
	 * @var array
	 */
	private $joinIds = array();

	/**
	 * Array of created group ids
	 *
	 * @var array
	 */
	private $groupMap = array();

	/**
	 * Array of created element ids
	 *
	 * @var array
	 */
	private $elementIds = array();

	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @throws UnexpectedValueException
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$listModel = ArrayHelper::getValue($config, 'listModel', JModelLegacy::getInstance('List', 'FabrikAdminModel'));

		if (!is_a($listModel, 'FabrikAdminModelList'))
		{
			throw new UnexpectedValueException('Content Type Constructor requires an Admin List Model');
		}

		$this->listModel = $listModel;
		$this->doc       = new DOMDocument();
	}

	/**
	 * Method to get the select content type form.
	 *
	 * @param   array $data     Data for the form.
	 * @param   bool  $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 *
	 * @since    3.3.5
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_fabrik.content-type', 'content-type', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Load in a content type
	 *
	 * @param   string $name File name
	 *
	 * @throws UnexpectedValueException
	 *
	 * @return FabrikAdminModelContentType  Allows for chaining
	 */
	public function loadContentType($name)
	{
		if ((string) $name === '')
		{
			throw new UnexpectedValueException('no content type supplied');
		}

		$paths = self::addContentTypeIncludePath();
		$path  = JPath::find($paths, $name);

		if (!$path)
		{
			throw new UnexpectedValueException('Content type not found in paths');
		}

		$xml = file_get_contents($path);
		$this->doc->loadXML($xml);

		return $this;
	}

	/**
	 * Create Fabrik groups & elements from loaded content type
	 *
	 * @param string $dbTableName New main table name
	 *
	 * @return array  Created Group Ids
	 */
	public function createGroupsFromContentType($dbTableName)
	{
		if (!$this->doc)
		{
			throw new UnexpectedValueException('A content type must be loaded before groups can be created');
		}

		$groupIds   = array();
		$fields     = array();
		$xpath      = new DOMXpath($this->doc);
		$groups     = $xpath->query('/contenttype/group');
		$i          = 1;
		$elementMap = array();
		$w          = new FabrikWorker;
		$jForm      = $this->app->input->get('jform', array(), 'array');

		foreach ($groups as $group)
		{
			$groupData           = array();
			$groupData           = FabrikContentTypHelper::domNodeAttributesToArray($group, $groupData);
			$groupData           = $w->parseMessageForPlaceHolder($groupData, $jForm);
			$groupData['params'] = FabrikContentTypHelper::nodeParams($group);
			$this->mapGroupACL($groupData);

			$isJoin   = ArrayHelper::getValue($groupData, 'is_join', false);
			$isRepeat = isset($groupData['params']->repeat_group_button) ? $groupData['params']->repeat_group_button : false;

			$groupId                          = $this->listModel->createLinkedGroup($groupData, $isJoin, $isRepeat);
			$this->groupMap[$groupData['id']] = $groupId;
			$elements                         = $xpath->query('/contenttype/group[' . $i . ']/element');

			foreach ($elements as $element)
			{
				$elementData = FabrikContentTypHelper::domNodeAttributesToArray($element);

				if (array_key_exists('id', $elementData))
				{
					$oldId = $elementData['id'];
					unset($elementData['id']);
				}

				$elementData['params']   = json_encode(FabrikContentTypHelper::nodeParams($element));
				$elementData['group_id'] = $groupId;
				$this->mapElementACL($elementData);
				$name          = (string) $element->getAttribute('name');
				$fields[$name] = $this->listModel->makeElement($name, $elementData);

				if (!empty($oldId))
				{
					$elementMap[$oldId] = $fields[$name]->element->id;
				}

				$this->elementIds[] = $fields[$name]->element->id;
			}

			$groupIds[] = $groupId;
			$i++;
		}

		$this->mapElementIdParams($elementMap);
		$this->importJoins($elementMap, $dbTableName);
		$this->importTables($dbTableName);

		return $fields;
	}

	/**
	 * Map any changes in element ACL parameters
	 *
	 * @param   &$data  Element Data
	 */
	private function mapElementACL(&$data)
	{
		$map            = $this->app->input->get('aclMap', array(), 'array');
		$params         = array('edit_access', 'view_access', 'list_view_access', 'filter_access', 'sum_access', 'avg_access',
			'median_access', 'count_access', 'custom_calc_access');
		$data['access'] = ArrayHelper::getValue($map, $data['access'], $data['access']);
		$origParams     = json_decode($data['params']);

		foreach ($params as $param)
		{
			if (isset($origParams->$param))
			{
				if (array_key_exists($origParams->$param, $map))
				{
					$origParams->$param = $map[$origParams->$param];
				}
			}
			else
			{
				// default them to main access level
				$origParams->$param = $data['access'];
			}
		}

		$data['params'] = json_encode($origParams);
	}

	/**
	 * Map any ACL changes in group params
	 *
	 * @param   &$data
	 */
	private function mapGroupACL(&$data)
	{
		$map        = $this->app->input->get('aclMap', array(), 'array');
		$params     = array('access', 'repeat_add_access', 'repeat_delete_access');
		$origParams = $data['params'];

		foreach ($params as $param)
		{
			if (isset($origParams->$param) && array_key_exists($origParams->$param, $map))
			{
				$origParams->$param = $map[$origParams->$param];
			}
		}

	}

	/**
	 * Element's can have parameters which point to a specific element ID. We need to update those parameters
	 * to use the cloned element's ID
	 *
	 * @param   array $elementMap
	 *
	 * @return bool  True if all elements successfully saved
	 */
	private function mapElementIdParams($elementMap)
	{
		$return        = true;
		$formModel     = $this->listModel->getFormModel();
		$pluginManager = FabrikWorker::getPluginManager();

		foreach ($elementMap as $origId => $newId)
		{
			// The XML Dom object describing the element's plugin properties
			$pluginManifest = $pluginManager->getPluginFromId($newId)->getPluginForm()->getXml();

			// Get all listfield parameters where the value format property is no 'tableelement'
			$listFields = $pluginManifest->xpath('//field[@type=\'listfields\'][(@valueformat=\'tableelement\') != true()]');
			$paramNames = array();

			foreach ($listFields as $listField)
			{
				if ((string) $listField->attributes()->valueformat !== '')
				{
					$paramNames[] = (string) $listField->attributes()->name;
				}
			}

			if (!empty($paramNames))
			{
				$elementModel  = $formModel->getElement($newId, true);
				$element       = $elementModel->getElement();
				$elementParams = new Registry($element->params);

				foreach ($paramNames as $paramName)
				{
					$orig = $elementParams->get($paramName, null);

					if (!is_null($orig))
					{
						$elementParams->set($paramName, $elementMap[$orig]);
					}
				}

				$element->set('params', (string) $elementParams);
				$return = $return && $element->store();
			}
		}

		return $return;
	}

	/**
	 * Import any database table's defined in the XML's files '/contenttype/database/table_structure section
	 * These are table's needed for database join element's to work.
	 *
	 * @param string $dbTableName New db table name
	 *
	 * @return void
	 */
	private function importTables($dbTableName)
	{
		$xpath  = new DOMXpath($this->doc);
		$tables = $xpath->query('/contenttype/database/table_structure');
		// $importer = $this->db->getImporter();

		// Tmp fix until https://issues.joomla.org/tracker/joomla-cms/7378 is merged
		$importer = new JDatabaseImporterMysqli2;
		$importer->setDbo($this->db);

		foreach ($tables as $table)
		{
			// Internally generated repeat groups tables should have their name updated to contain the new group id
			if (preg_match('/(.*)_([0-9]*)_repeat/', $table->getAttribute('name'), $matches))
			{
				if ($matches[1] === $this->getSourceTableName())
				{
					$matches[1] = $dbTableName;
				}

				$oldGroupId = ArrayHelper::getValue($matches, 2);
				$new        = $this->groupMap[$oldGroupId];
				$newName    = preg_replace('/(.*)_([0-9]*)_repeat/', $matches[1] . '_' . $new . '_repeat', $table->getAttribute('name'));
				$table->setAttribute('name', $newName);

			}

			$xmlDoc     = new DOMDocument;
			$database   = $xmlDoc->createElement('database');
			$root       = $xmlDoc->createElement('root');
			$tableClone = $xmlDoc->importNode($table, true);
			$database->appendChild($tableClone);
			$root->appendChild($database);
			$xml = simplexml_import_dom($root);

			try
			{
				$importer->from($xml)->mergeStructure();
			} catch (Exception $e)
			{
				echo "error: " . $e->getMessage();
			}
		}
	}

	/**
	 * Import any group join entries from in /contenttypes/group/join,
	 * and element join entries from /contenttypes/element/join
	 * For group joins, the list id is not available. The join is thus finalised in
	 * finaliseImport()
	 *
	 * @param   array $elementMap array(oldElementId => newElementId)
	 * @param string  $dbTableName
	 *
	 * @return  void
	 */
	private function importJoins($elementMap, $dbTableName)
	{
		$xpath    = new DOMXpath($this->doc);
		$joins    = $xpath->query('/contenttype/group[join]/join');
		$elements = $xpath->query('/contenttype/group/element[join]/join');

		foreach ($joins as $join)
		{
			$newGroupId = $this->groupMap[(string) $join->getAttribute('group_id')];
			$join->setAttribute('group_id', $newGroupId);
			$joinData           = FabrikContentTypHelper::domNodeAttributesToArray($join);
			$joinData['params'] = json_encode(FabrikContentTypHelper::nodeParams($join));
			unset($joinData['list_id']);

			// Internally generated repeat groups should have their join table name updated
			if (preg_match('/(.*)_([0-9]*)_repeat/', $joinData['table_join'], $matches))
			{
				if ($matches[1] === $this->getSourceTableName())
				{
					$matches[1] = $dbTableName;
				}

				$oldGroupId             = ArrayHelper::getValue($matches, 2);
				$new                    = $this->groupMap[$oldGroupId];
				$joinData['table_join'] = preg_replace('/(.*)_([0-9]*)_repeat/', $matches[1] . '_' . $new . '_repeat', $joinData['table_join']);
			}

			$joinTable = FabTable::getInstance('Join', 'FabrikTable');
			$joinTable->save($joinData);
			$this->joinIds[] = $joinTable->get('id');
		}

		foreach ($elements as $join)
		{
			$oldElementId = (string) $join->getAttribute('element_id');
			$newId        = $elementMap[$oldElementId];
			$newGroupId   = $this->groupMap[(string) $join->getAttribute('group_id')];
			$join->setAttribute('group_id', $newGroupId);
			$join->setAttribute('element_id', $newId);
			$joinData           = FabrikContentTypHelper::domNodeAttributesToArray($join);
			$joinData['params'] = json_encode(FabrikContentTypHelper::nodeParams($join));
			$joinTable          = FabTable::getInstance('Join', 'FabrikTable');
			$joinTable->save($joinData);
			$this->joinIds[] = $joinTable->get('id');
		}
	}

	/**
	 * Get the source table name, defined in XML file.
	 *
	 * @return string
	 */
	private function getSourceTableName()
	{
		$xpath  = new DOMXpath($this->doc);
		$source = $xpath->query('/contenttype/database/source');
		$source = iterator_to_array($source);

		return (string) $source[0]->nodeValue;
	}

	/**
	 * Called at the end of a list save.
	 * Update the created joins with the created list's id and db_table_name
	 *
	 * @param   FabrikTableList $row List data
	 *
	 * @return  void
	 */
	public function finalise($row)
	{
		$source      = $this->getSourceTableName();
		$targetTable = $row->get('db_table_name');

		foreach ($this->joinIds as $joinId)
		{
			$joinTable = FabTable::getInstance('Join', 'FabrikTable');
			$joinTable->load($joinId);

			if ((int) $joinTable->get('element_id') === 0)
			{
				// Group join
				$joinTable->set('list_id', $row->get('id'));
				$joinTable->set('join_from_table', $targetTable);
			}
			else
			{
				// Element join
				$tableLookUps = array('join_from_table', 'table_join', 'table_join_alias');

				foreach ($tableLookUps as $tableLookup)
				{
					if ($joinTable->get($tableLookup) === $source)
					{
						$joinTable->set($tableLookup, $targetTable);
					}
				}
			}

			$joinTable->store();
		}

		// Update element params with source => target table name conversion
		foreach ($this->elementIds as $elementId)
		{
			/** @var FabrikTableElement $element */
			$element = FabTable::getInstance('Element', 'FabrikTable');
			$element->load($elementId);
			$elementParams = new Registry($element->params);

			if ($elementParams->get('join_db_name') === $source)
			{
				$elementParams->set('join_db_name', $targetTable);
				$element->set('params', $elementParams->toString());
				$element->store();
			}
		}
	}

	/**
	 * Add a filesystem path where content type XML files should be searched for.
	 * You may either pass a string or an array of paths.
	 *
	 * @param   mixed $path A filesystem path or array of filesystem paths to add.
	 *
	 * @return  array  An array of filesystem paths to find Content type XML files.
	 */
	public static function addContentTypeIncludePath($path = null)
	{
		// If the internal paths have not been initialised, do so with the base table path.
		if (empty(self::$_contentTypeIncludePaths))
		{
			self::$_contentTypeIncludePaths = JPATH_COMPONENT_ADMINISTRATOR . '/models/content_types';
		}

		// Convert the passed path(s) to add to an array.
		settype($path, 'array');

		// If we have new paths to add, do so.
		if (!empty($path))
		{
			// Check and add each individual new path.
			foreach ($path as $dir)
			{
				// Sanitize path.
				$dir = trim($dir);

				// Add to the front of the list so that custom paths are searched first.
				if (!in_array($dir, self::$_contentTypeIncludePaths))
				{
					array_unshift(self::$_contentTypeIncludePaths, $dir);
				}
			}
		}

		return self::$_contentTypeIncludePaths;
	}

	/**
	 * Prepare the group and element models for form view preview
	 *
	 * @return array
	 */
	public function preview()
	{
		$pluginManager = FabrikWorker::getPluginManager();
		$xpath         = new DOMXpath($this->doc);
		$groups        = $xpath->query('/contenttype/group');
		$return        = array();
		$i             = 1;

		foreach ($groups as $group)
		{
			$groupData           = array();
			$groupData           = FabrikContentTypHelper::domNodeAttributesToArray($group, $groupData);
			$groupData['params'] = FabrikContentTypHelper::nodeParams($group);
			$groupModel          = JModelLegacy::getInstance('Group', 'FabrikFEModel');
			$groupTable          = FabTable::getInstance('Group', 'FabrikTable');
			$groupTable->bind($groupData);
			$groupModel->setGroup($groupTable);

			$elements      = $xpath->query('/contenttype/group[' . $i . ']/element');
			$elementModels = array();

			foreach ($elements as $element)
			{
				$elementData                  = FabrikContentTypHelper::domNodeAttributesToArray($element);
				$elementData['params']        = FabrikContentTypHelper::nodeParams($element);
				$elementModel                 = clone($pluginManager->loadPlugIn($elementData['plugin'], 'element'));
				$elementModel->element        = $elementModel->getDefaultProperties($elementData);
				$elementModel->element->name  = $elementData['name'];
				$elementModel->element->label = $elementData['label'];

				if ($elementModel->element->hidden)
				{
					$elementModel->element->hidden = false;
					$elementModel->getparams()->set('containerclass', 'faux-shown');
				}

				$elementModel->editable = true;
				$elementModels[]        = $elementModel;
			}

			$groupModel->elements = $elementModels;
			$return[]             = $groupModel;
			$i++;
		}

		return $return;
	}

	/**
	 * Get default insert fields - either from content type or defaultfields input value
	 *
	 * @param string|null $contentType
	 * @param string      $dbTableName
	 * @param array       $groupData Group info
	 *
	 * @return array
	 */
	public function import($contentType = null, $dbTableName, $groupData = array())
	{
		$input = $this->app->input;

		if (!empty($contentType))
		{
			$fields = $this->loadContentType($contentType)
				->createGroupsFromContentType($dbTableName);
		}
		else
		{
			// Could be importing from a CSV in which case default fields are set.
			// TODO refactor this $input get into class constructor
			$fields     = $input->get('defaultfields', array('id' => 'internalid', 'date_time' => 'date'), 'array');
			$primaryKey = array_keys($input->get('key', array(), 'array'));
			$primaryKey = array_pop($primaryKey);
			$elements   = array();

			foreach ($fields as $name => $plugin)
			{
				$pk         = $name === $primaryKey ? 1 : 0;
				$elements[] = array(
					'plugin' => $plugin,
					'label' => $name,
					'name' => $name,
					'primary_key' => $pk,
					'access' => '1'
				);
			}

			/** @var FabrikAdminModelContentTypeExport $exporter */
			$exporter = JModelLegacy::getInstance('ContentTypeExport', 'FabrikAdminModel',
				array('listModel' => $this->listModel));
			$xml      = $exporter->createXMLFromArray($groupData, $elements);
			$this->doc->loadXML($xml);
			$fields = $this->createGroupsFromContentType($dbTableName);
		}

		return $fields;
	}

	/**
	 * Pre-installation check
	 *
	 * Ensure that before creating a list/form from a content type, that all
	 * elements are installed and published
	 *
	 * @param   string $contentType
	 *
	 * @throws UnexpectedValueException
	 *
	 * @return bool
	 */
	public function check($contentType)
	{
		$this->loadContentType($contentType);
		$xpath    = new DOMXpath($this->doc);
		$elements = $xpath->query('/contenttype/group/element');
		$db       = $this->db;
		$query    = $db->getQuery(true);
		$query->select('element')->from('#__extensions')
			->where('folder =' . $db->q('fabrik_element'))
			->where('enabled = 1');
		$db->setQuery($query);
		$allowed = $db->loadColumn();

		foreach ($elements as $element)
		{
			$pluginName = $element->getAttribute('plugin');

			if (!in_array($pluginName, $allowed))
			{
				throw new UnexpectedValueException($pluginName . ' not installed or published');
			}
		}

		return true;
	}

	/**
	 * Get the site's view levels
	 *
	 * @return array|mixed
	 */
	private function getViewLevels()
	{
		if (isset($this->viewLevels))
		{
			return $this->viewLevels;
		}

		$query = $this->db->getQuery(true);
		$query->select('*')->from('#__viewlevels');
		$this->viewLevels = $this->db->setQuery($query)->loadAssocList();

		return $this->viewLevels;
	}

	/**
	 * Get ACL Groups
	 *
	 * @return array
	 */
	private function getGroups()
	{
		if (isset($this->groups))
		{
			return $this->groups;
		}

		$query = $this->db->getQuery(true);
		$query->select('*')->from('#__usergroups');
		$this->groups = $this->db->setQuery($query)->loadAssocList('id');

		return $this->groups;
	}

	/**
	 * Ensure that the content type's view level XML matches the site's view levels
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	private function validateViewLevelXML()
	{
		$rows       = $this->getViewLevels();
		$xpath      = new DOMXpath($this->doc);
		$viewLevels = $xpath->query('/contenttype/viewlevels/viewlevel');

		if (count($rows) !== $viewLevels->length)
		{
			throw new Exception('Content type: View levels mismatch');
		}

		$i = 0;

		foreach ($viewLevels as $viewLevel)
		{
			$id = (int) $viewLevel->getAttribute('id');

			if (!($id === (int) $rows[$i]['id'] &&
				(string) $viewLevel->getAttribute('rules') === $rows[$i]['rules'])
			)
			{
				throw new Exception('Content type: view level data for ' . $id . ' not the same as server info');
			}

			$i++;
		}

		return false;
	}

	/**
	 * Attempt to compare exported ACL setting with the site's existing ACL setting
	 *
	 * @return string
	 */
	public function aclCheckUI()
	{
		$xpath            = new DOMXpath($this->doc);
		$parent           = $xpath->query('/contenttype');
		$importViewLevels = $xpath->query('/contenttype/viewlevels/viewlevel');
		$importGroups     = $xpath->query('/contenttype/groups/group');

		$contentTypeViewLevels = array();
		$contentTypeGroups     = array();
		$alteredGroups         = array();

		foreach ($importGroups as $importGroup)
		{
			$group                           = FabrikContentTypHelper::domNodeAttributesToArray($importGroup);
			$contentTypeGroups[$group['id']] = $group;
		}

		foreach ($importViewLevels as $importViewLevel)
		{
			$viewLevel = FabrikContentTypHelper::domNodeAttributesToArray($importViewLevel);
			$rules     = json_decode($viewLevel['rules']);

			foreach ($rules as &$rule)
			{
				$rule = array_key_exists($rule, $contentTypeGroups) ? $contentTypeGroups[$rule]['title'] : $rule;
			}

			$viewLevel['rules_labels'] = implode(', ', $rules);
			$contentTypeViewLevels[]   = $viewLevel;
		}

		$viewLevels = $this->getViewLevels();
		$groups     = $this->getGroups();

		foreach ($viewLevels as &$viewLevel)
		{
			$rules = json_decode($viewLevel['rules']);

			foreach ($rules as &$rule)
			{
				$rule = array_key_exists($rule, $groups) ? $groups[$rule]['title'] : $rule;
			}

			$viewLevel['rules_labels'] = implode(', ', $rules);
		}

		foreach ($groups as $group)
		{
			if (array_key_exists($group['id'], $contentTypeGroups))
			{
				$importGroup = $contentTypeGroups[$group['id']];

				if ($group['lft'] !== $importGroup['lft'] || $group['rgt'] !== $importGroup['rgt'] || $group['parent_id'] !== $importGroup['parent_id'])
				{
					$alteredGroups[] = $group;
				}
			}
		}

		$layoutData = (object) array(
			'viewLevels' => $viewLevels,
			'contentTypeViewLevels' => $contentTypeViewLevels,
			'match' => true,
			'groups' => $groups,
			'importGroups' => $contentTypeGroups,
			'alteredGroups' => $alteredGroups
		);
		try
		{
			$this->validateViewLevelXML();
		} catch (Exception $e)
		{
			$layoutData->match = false;
		}

		foreach ($parent as $p)
		{
			if ($p->getAttribute('ignoreacl') === 'true')
			{
				$layoutData->match = true;
			}
		}

		$this->checkVersion($xpath, $layoutData);
		$layout = FabrikHelperHTML::getLayout('fabrik-content-type-compare');

		return $layout->render($layoutData);
	}

	/**
	 * Check the Fabrik version against the content type version
	 *
	 * @param   DOMXpath $xpath
	 * @param   object   $layoutData
	 *
	 * @return void
	 */
	private function checkVersion($xpath, &$layoutData)
	{
		$xml                     = simplexml_load_file(JPATH_COMPONENT_ADMINISTRATOR . '/fabrik.xml');
		$layoutData->siteVersion = (string) $xml->version;

		$contentTypeVersion             = $xpath->query('/contenttype/fabrikversion');
		$contentTypeVersion             = iterator_to_array($contentTypeVersion);
		$layoutData->contentTypeVersion = empty($contentTypeVersion) ? 0 : (string) $contentTypeVersion[0]->nodeValue;

		$layoutData->versionMismatch = $layoutData->siteVersion !== $layoutData->contentTypeVersion;
	}
}
