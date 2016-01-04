<?php
/**
 * Fabrik Admin Content Type Export Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.4
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once 'fabmodeladmin.php';

// Tmp fix until https://issues.joomla.org/tracker/joomla-cms/7378 is merged
require JPATH_COMPONENT_ADMINISTRATOR . '/models/databaseexporter.php';
require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/contenttype.php';

use Joomla\Utilities\ArrayHelper;
use \Joomla\Registry\Registry;

/**
 * Fabrik Admin Content Type Export Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.4
 */
class FabrikAdminModelContentTypeExport extends FabModelAdmin
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
	 * Plugin names that we can not use in a content type
	 *
	 * @var array
	 */
	private $invalidPlugins = array('cascadingdropdown');

	/**
	 * Plugin names that require an import/export of a database table.
	 *
	 * @var array
	 */
	private $pluginsWithTables = array('databasejoin');

	/**
	 * This site's view levels
	 *
	 * @var array
	 */
	private $viewLevels;

	/**
	 * This site's groups
	 *
	 * @var array
	 */
	private $groups;

	/**
	 * Exported tables
	 *
	 * @var array
	 */
	private static $exportedTables = array();

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

		$this->listModel               = $listModel;
		$this->doc                     = new DOMDocument();
		$this->doc->preserveWhiteSpace = false;
		$this->doc->formatOutput       = true;
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
	 * Create the content type
	 * Save it to /administrator/components/com_fabrik/models/content_types
	 * Update form model with content type path
	 *
	 * @param   FabrikFEModelForm $formModel
	 *
	 * @return  bool
	 */
	public function create($formModel)
	{
		// We don't want to export the main table, as a new one is created when importing the content type
		$this->listModel = $formModel->getListModel();
		$mainTable       = $this->listModel->getTable()->get('db_table_name');
		$contentType     = $this->doc->createElement('contenttype');
		$tables          = FabrikContentTypHelper::iniTableXML($this->doc, $mainTable);

		$label = JFile::makeSafe($formModel->getForm()->get('label'));
		$name  = $this->doc->createElement('name', $label);
		$contentType->appendChild($name);
		$contentType->appendChild($this->version());
		$groups = $formModel->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$groupData     = $groupModel->getGroup()->getProperties();
			$elements      = array();
			$elementModels = $groupModel->getMyElements();

			foreach ($elementModels as $elementModel)
			{
				$elements[] = $elementModel->getElement()->getProperties();
			}

			$contentType->appendChild($this->createFabrikGroupXML($groupData, $elements, $tables, $mainTable));
		}

		$contentType->appendChild($tables);
		$contentType->appendChild($this->createViewLevelXML());
		$contentType->appendChild($this->createGroupXML());
		$this->doc->appendChild($contentType);
		$xml  = $this->doc->saveXML();
		$path = JPATH_COMPONENT_ADMINISTRATOR . '/models/content_types/' . $label . '.xml';

		if (JFile::write($path, $xml))
		{
			$form   = $formModel->getForm();
			$params = $formModel->getParams();
			$params->set('content_type_path', $path);
			$form->params = $params->toString();

			return $form->save($form->getProperties());
		}

		return false;
	}

	/**
	 * Get the current Fabrik version in a DOMElement
	 *
	 * @return DOMElement
	 */
	private function version()
	{
		$xml     = simplexml_load_file(JPATH_COMPONENT_ADMINISTRATOR . '/fabrik.xml');
		$version = $this->doc->createElement('fabrikversion', (string) $xml->version);

		return $version;
	}

	/**
	 * Create group XML
	 *
	 * @param array      $data     Group data
	 * @param array      $elements Element data
	 * @param DomElement $tables
	 * @param string     $mainTable
	 *
	 * @return DOMElement
	 */
	private function createFabrikGroupXML($data, $elements, $tables, $mainTable = '')
	{
		$tableParams = array('table_join', 'join_from_table');

		$group = FabrikContentTypHelper::buildExportNode($this->doc, 'group', $data);

		if ($data['is_join'] === '1')
		{
			$join = FabTable::getInstance('Join', 'FabrikTable');
			$join->load($data['join_id']);

			foreach ($tableParams as $tableParam)
			{
				if ($join->get($tableParam) !== $mainTable)
				{
					$this->createTableXML($tables, $join->get($tableParam));
				}
			}

			$groupJoin = FabrikContentTypHelper::buildExportNode($this->doc, 'join', $join->getProperties(), array('id'));
			$group->appendChild($groupJoin);
		}

		foreach ($elements as $element)
		{
			$group->appendChild($this->createFabrikElementXML($element, $tables, $mainTable));
		}

		return $group;
	}

	/**
	 * Create element XML
	 *
	 * @param   array      $data Element data
	 * @param   DomElement $tables
	 * @param   string     $mainTable
	 *
	 * @return DOMElement
	 */
	private function createFabrikElementXML($data, $tables, $mainTable)
	{
		if (in_array($data['plugin'], $this->invalidPlugins))
		{
			throw new UnexpectedValueException('Sorry we can not create content types with ' .
				$data['plugin'] . ' element plugins');
		}

		if (in_array($data['plugin'], $this->pluginsWithTables))
		{
			$params = new Registry($data['params']);

			if ($params->get('join_db_name') !== $mainTable)
			{
				$this->createTableXML($tables, $params->get('join_db_name'));
			}
		};

		$element       = FabrikContentTypHelper::buildExportNode($this->doc, 'element', $data);
		$pluginManager = FabrikWorker::getPluginManager();
		$elementModel  = clone($pluginManager->getPlugIn($data['plugin'], 'element'));

		if (is_a($elementModel, 'PlgFabrik_ElementDatabasejoin'))
		{
			$join = FabTable::getInstance('Join', 'FabrikTable');
			$join->load(array('element_id' => $data['id']));
			$elementJoin = FabrikContentTypHelper::buildExportNode($this->doc, 'join', $join->getProperties(), array('id'));
			$element->appendChild($elementJoin);
		}

		return $element;
	}

	/**
	 * Create XML for table export
	 *
	 * @param   DOMElement $tables    Parent node to attach xml to
	 * @param   string     $tableName Table name to export
	 *
	 * @throws Exception
	 */
	private function createTableXML(&$tables, $tableName)
	{
		if (in_array($tableName, self::$exportedTables))
		{
			return;
		}

		self::$exportedTables[] = $tableName;
		//$exporter    = $this->db->getExporter();
		$exporter = new JDatabaseExporterMysqli2;
		$exporter->setDbo($this->db);
		$exporter->from($tableName);
		$tableDoc = new DOMDocument();
		$tableDoc->loadXML((string) $exporter);
		$structures = $tableDoc->getElementsByTagName('table_structure');

		foreach ($structures as $table)
		{
			$table = $this->doc->importNode($table, true);
			$tables->appendChild($table);
		}
	}

	/**
	 * Create the view levels ACL info
	 *
	 * @return DOMElement
	 */
	private function createViewLevelXML()
	{
		$rows       = $this->getViewLevels();
		$viewLevels = $this->doc->createElement('viewlevels');

		foreach ($rows as $row)
		{
			$viewLevel = FabrikContentTypHelper::buildExportNode($this->doc, 'viewlevel', $row);
			$viewLevels->appendChild($viewLevel);
		}

		return $viewLevels;
	}

	/**
	 * Create the group ACL info
	 *
	 * @return DOMElement
	 */
	private function createGroupXML()
	{
		$rows   = $this->getGroups();
		$groups = $this->doc->createElement('groups');

		foreach ($rows as $row)
		{
			$group = $this->doc->createElement('group');

			foreach ($row as $key => $value)
			{
				$group->setAttribute($key, $value);
			}
			$groups->appendChild($group);
		}

		return $groups;
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
	 * Download the content type
	 *
	 * @param   FabrikFEModelForm $formModel
	 *
	 * @throws Exception
	 */
	public function download($formModel)
	{
		$params  = $formModel->getParams();
		$file    = $params->get('content_type_path');
		$label   = 'content-type-' . $formModel->getForm()->get('label');
		$label   = JFile::makeSafe($label);
		$zip     = new ZipArchive;
		$zipFile = $this->config->get('tmp_path') . '/' . $label . '.zip';
		$zipRes  = $zip->open($zipFile, ZipArchive::CREATE);

		if (!$zipRes)
		{
			throw new Exception('unable to create ZIP');
		}

		if (!JFile::exists($file))
		{
			throw new Exception('Content type file not found');
		}

		if (!$zip->addFile($file, basename($file)))
		{
			throw new Exception('unable to add file ' . $file . ' to zip');
		}

		$zip->close();
		header('Content-Type: application/zip');
		header('Content-Length: ' . filesize($zipFile));
		header('Content-Disposition: attachment; filename="' . basename($zipFile) . '"');
		echo file_get_contents($zipFile);

		// Must exit to produce valid Zip download
		exit;
	}

	/**
	 * Create content type XML from an array of group/element data
	 * Used in CSV import
	 *
	 * @param   array $groupData
	 * @param   array $elements
	 *
	 * @return string
	 */
	public function createXMLFromArray($groupData, $elements)
	{
		$contentType = $this->doc->createElement('contenttype');
		$mainTable   = $this->listModel->getTable()->get('db_table_name');
		$tables      = FabrikContentTypHelper::iniTableXML($this->doc, $mainTable);
		$name        = $this->doc->createElement('name', 'tmp');
		$contentType->appendChild($name);
		$contentType->appendChild($this->createFabrikGroupXML($groupData, $elements, $tables));
		$contentType->appendChild($tables);
		$contentType->appendChild($this->createViewLevelXML());
		$contentType->appendChild($this->createGroupXML());
		$this->doc->appendChild($contentType);

		return $this->doc->saveXML();
	}

}
