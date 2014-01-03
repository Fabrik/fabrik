<?php
/**
 * Old package? export code
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

/**
 * Old package? export code
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 * @deprecated  Not used
 */

class FabrikFEModelExport
{
	/**
	 * Label
	 *
	 * @var string
	 */
	public $label = '';

	public $tableIds = array();

	public $format = 'xml';

	public $includeData = false;

	public $fabrikData = false;

	public $incTableStructure = false;

	public $packageModel = null;

	protected $tables = array();

	protected $files = array();

	/**
	 * Load a package for export
	 *
	 * @param   int  $id  Package Id
	 *
	 * @return  void
	 */
	public function load($id)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$this->packageModel = JModelLegacy::getInstance('Package', 'FabrikFEModel');
		$this->packageModel->setId($id);
		$this->packageModel->getPackage();
		$this->packageModel->loadTables();
		$this->format = $input->get('format', 'xml');
		$this->includeData = $input->get('tabledata', false);
		$this->fabrikData = $input->get('fabrikfields', false);
		$this->label = $input->get('label', '');
		$this->incTableStructure = $input->get('tablestructure', false);
		$this->setBufferFile();
	}

	/**
	 * Export table data
	 *
	 * @return void
	 */

	public function export()
	{
		$db = FabrikWorker::getDbo();

		switch ($this->format)
		{
			case 'csv':
				$this->_csvExport();
				break;
			case 'xml':
			default:
				$xml = $this->_buildXML();
				$this->_xmlExport();
				break;
		}
	}

	/**
	 * Collates the template files
	 *
	 * @return  string  xml string
	 */

	public function getTemplateFiles()
	{
		$templatePath = JPATH_SITE . '/components/com_fabrik/tmpl/form/';
		$aFiles = array();

		foreach ($this->tables as $listModel)
		{
			$table = $listModel->getTable();
			$formModel = $listModel->getForm();
			$form = $formModel->getForm();

			if (!in_array('table/' . $table->template, $aFiles))
			{
				$aFiles[] = 'table/' . $table->template;
			}

			if ($form->form_template != '')
			{
				if (is_dir($templatePath . $form->form_template))
				{
					if (!in_array('form/' . $form->form_template . '/elements.html', $aFiles))
					{
						$aFiles[] = 'form/' . $form->form_template . '/elements.html';
					}

					if (!in_array('form/' . $form->form_template . '/form.html', $aFiles))
					{
						$aFiles[] = 'form/' . $form->form_template . '/form.html';
					}
				}
				else
				{
					if (!in_array('form/' . $form->form_template, $aFiles))
					{
						$aFiles[] = 'form/' . $form->form_template;
					}
				}
			}

			if ($form->view_only_template != '')
			{
				if (is_dir($templatePath . $form->view_only_template))
				{
					if (!in_array('viewonly/' . $form->view_only_template . '/elements.html', $aFiles))
					{
						$aFiles[] = 'viewonly/' . $form->view_only_template . '/elements.html';
					}

					if (!in_array('viewonly/' . $form->view_only_template . '/form.html', $aFiles))
					{
						$aFiles[] = 'viewonly/' . $form->view_only_template . '/form.html';
					}
				}
				else
				{
					if (!in_array('viewonly/' . $form->view_only_template, $aFiles))
					{
						$aFiles[] = 'viewonly/' . $form->view_only_template;
					}
				}
			}
		}

		$xml = "<files>\n";

		foreach ($aFiles as $file)
		{
			$xml .= "\t<file>tmpl/$file</file>\n";
		}

		$this->files = $aFiles;
		$xml .= "</files>\n";

		return $xml;
	}

	/**
	 * Builds the xml installer file for a given table
	 *
	 * @return  string  xml file
	 */

	protected function _buildXML()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$db = FabrikWorker::getDbo();
		$this->clearExportBuffer();
		$strXML = "<?xml version=\"1.0\" ?>\n";
		$strXML .= "<install type=\"fabrik\" version=\"2.0\">\n";
		$strXML .= "<creationDate>" . $input->get('creationDate', '') . "</creationDate>\n";
		$strXML .= "<author>" . $input->get('author') . "</author>\n";
		$strXML .= "<copyright>" . $input->get('copyright') . "</copyright>\n";
		$strXML .= "<authorEmail>" . $input->get('authoremail') . "</authorEmail>\n";
		$strXML .= "<authorUrl>" . $input->get('authorurl') . "</authorUrl>\n";
		$strXML .= "<version>" . $input->get('version') . "</version>\n";
		$strXML .= "<licence>" . $input->get('license') . "</licence>\n";
		$strXML .= "<description>" . $input->get('description') . "</description>\n";
		$aTableObjs = array();
		$tables = $this->packageModel->_tables;
		$forms = $this->packageModel->_forms;

		if ($this->fabrikData)
		{
			$strXML .= "<tables>\n";

			if (is_array($this->tableIds))
			{
				foreach ($tables as $table)
				{
					$vars = get_object_vars($table);
					$strXML .= "\t<table>\n";

					foreach ($vars as $key => $val)
					{
						if (substr($key, 0, 1) != '_')
						{
							$strXML .= "\t\t<$key><![CDATA[$val]]></$key>\n";
						}
					}
				}
			}

			$strXML .= "</tables>\n\n";
			$strXML .= "<forms>\n";

			foreach ($forms as $form)
			{
				$vars = get_object_vars($form);
				$strXML .= "\t<form>\n";

				foreach ($vars as $key => $val)
				{
					if (substr($key, 0, 1) != '_')
					{
						$strXML .= "\t\t<$key><![CDATA[$val]]></$key>\n";
					}
				}

				$strXML .= "\t</form>\n";
			}

			$strXML .= "</forms>\n\n";
			$strElementXML = "<elements>\n";
			$strXML .= "<groups>\n";
			$strValidationXML = "<validations>\n";

			foreach ($this->tables as $listModel)
			{
				$groups = $listModel->_oForm->getGroupsHiarachy();
				$i = 0;

				foreach ($groups as $groupModel)
				{
					$group = $groupModel->getGroup();
					$vars = get_object_vars($group);
					$strXML .= "\t<group form_id=\"" . $listModel->getFormModel()->getId() . "\" ordering=\"" . $i . "\">\n";

					foreach ($vars as $key => $val)
					{
						// Don't insert join_id as this isn't in the group table
						if ($key != "join_id")
						{
							if (substr($key, 0, 1) != '_')
							{
								$strXML .= "\t\t<$key><![CDATA[$val]]></$key>\n";
							}
						}
					}

					$strXML .= "\t</group>\n";
					$elementModels = $groupModel->getPublishedElements();

					foreach ($elementModels as $elementModel)
					{
						$element = $elementModel->getElement();
						$vars = get_object_vars($element);
						$strElementXML .= "\t<element>\n";

						foreach ($vars as $key => $val)
						{
							if (substr($key, 0, 1) != '_')
							{
								$strElementXML .= "\t\t<$key><![CDATA[$val]]></$key>\n";
							}
						}

						$strElementXML .= "\t</element>\n\n";

						foreach ($elementModel->_aValidations as $oValidation)
						{
							$vars = get_object_vars($oValidation);
							$strValidationXML .= "\t<validation>\n";

							foreach ($vars as $key => $val)
							{
								if (substr($key, 0, 1) != '_')
								{
									$strValidationXML .= "\t\t<$key><![CDATA[$val]]></$key>\n";
								}
							}

							$strValidationXML .= "\t</validation>\n\n";
						}
					}

					$i++;
				}
			}

			$strXML .= "</groups>\n";
			$strElementXML .= "</elements>\n\n";
			$strValidationXML .= "</validations>\n\n";
			$strXML .= $strElementXML . $strValidationXML;
		}

		$this->writeExportBuffer($strXML);

		if ($this->incTableStructure)
		{
			$strXML = $this->_createTablesXML($strXML);
		}

		$strXML .= $this->getTemplateFiles();
		$strXML .= "</install>";
		$this->writeExportBuffer($strXML);
	}

	/**
	 * Create table xml file
	 *
	 * @return  void
	 */

	protected function _createTablesXML()
	{
		$strXML = "<queries>\n";

		for ($i = 0; $i < count($this->tables); $i++)
		{
			$tmpTable = $this->tables[$i];
			$this->writeExportBuffer("\t<query>" . $tmpTable->getDropTableSQL() . "</query>\n");
			$this->writeExportBuffer("\t<query>" . $tmpTable->getCreateTableSQL() . "</query>\n");

			if ($this->includeData)
			{
				$tmpTable->getInsertRowsSQL($this);
			}
		}

		$this->writeExportBuffer("</queries>\n");
	}

	/**
	 * Clear the export buffer
	 *
	 * @return  void
	 */

	public function clearExportBuffer()
	{
		if (JFile::exists($this->_bufferFile))
		{
			unlink($this->_bufferFile);
		}
	}

	/**
	 * Set the buffer file
	 *
	 * @return  void
	 */

	public function setBufferFile()
	{
		// Doesn't work in windowz
		// $this->_bufferFile = '/tmp/fabrik_package-' . $this->label . '.xml';
		$this->_bufferFile = JPATH_SITE . "/administrator/components/com_fabrik/fabrik_package-" . $this->label . '.xml';
	}

	/**
	 * Write string to export buffer
	 *
	 * @param   string  $str  Buffer
	 *
	 * @deprecated
	 *
	 * @return Ambiguous <object, mixed, reference>
	 */

	protected function writeExportBuffer($str)
	{
		$filename = $this->_bufferFile;

		// Let's make sure the file exists and is writeable first.
		if (JFile::exists($filename))
		{
			if (!is_writable($filename))
			{
				throw new RuntimeException(JText::sprintf("FILE NOT WRITEABLE", $filename), 500);
			}
		}

		if (!$handle = fopen($filename, 'a'))
		{
			throw new RuntimeException(JText::sprintf("CANT OPEN FILES", $filename), 500);
		}

		// Write $somecontent to our opened file.
		if (fwrite($handle, $str) === false)
		{
			throw new RuntimeException(JText::sprintf("CANT WRITE TO FILES", $filename), 500);
		}

		fclose($handle);
	}

	/**
	 * Do xml export
	 *
	 * @return  void
	 */

	protected function _xmlExport()
	{
		$archiveName = 'fabrik_package-' . $this->label;
		require_once JPATH_SITE . '/includes/Archive/Tar.php';
		$archivePath = JPATH_SITE . '/components/com_fabrik/' . $archiveName . '.tgz';

		if (JFile::exists($archivePath))
		{
			@unlink($archivePath);
		}

		$zip = new Archive_Tar($archivePath);
		$fileName = $archiveName . '.xml';
		$fileName = $this->_bufferFile;
		$fileName = str_replace(JPATH_SITE, '', $this->_bufferFile);
		$fileName = FabrikString::ltrimword($fileName, "/administrator/");
		$ok = $zip->addModify($fileName, '', "components/com_fabrik");

		for ($i = 0; $i < count($this->files); $i++)
		{
			$this->files[$i] = JPATH_SITE . '/components/com_fabrik/tmpl/' . $this->files[$i];
		}

		$zip->addModify($this->files, '', JPATH_SITE . '/components/com_fabrik');
		$this->_output_file($archivePath, $archiveName . '.tgz');
	}

	/**
	 * Do csv export
	 *
	 * @return  void
	 */
	protected function _csvExport()
	{
		$db = FabrikWorker::getDbo();
		initGzip();
		$listModel = JModelLegacy::getInstance('List', 'FabrikFEModel');
		$id = $this->tableIds[0];
		$listModel->setId($id);
		$listModel->setOutPutFormat('csv');
		$table = $listModel->getTable();
		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename="' . $table->label . '-export.csv"');
		$aTable = JArrayHelper::fromObject($table);
		$fabrikDb = &$listModel->getDb();
		$table = $table->db_table_name;
		$sql = "SELECT * FROM $table";
		$fabrikDb->setQuery($sql);
		$elementData = $fabrikDb->loadObjectList();
		$aFilter = array();
		$listModel->getForm();
		$listModel->getFormGroupElementData();

		$listModel->getParams();
		$limitLength = $listModel->getTotalRecords();
		$pageNav = $listModel->_getPagination(count($elementData), 0, $limitLength);
		$formdata = $listModel->getData();

		if (is_array($formdata))
		{
			$firstrow = JArrayHelper::fromObject($formdata[0][0]);

			if (is_array($firstrow))
			{
				echo implode(",", array_keys($firstrow));
			}

			foreach ($formdata as $group)
			{
				foreach ($group as $row)
				{
					echo "\n";
					echo implode(",", array_map(array($this, "_quote"), array_values(JArrayHelper::fromObject($row))));
				}
			}
		}

		doGzip();
	}

	/**
	 * Quote
	 *
	 * @param   string  $n  String to quote
	 *
	 * @return string
	 */
	public function _quote($n)
	{
		return '"' . str_replace('"', '""', $n) . '"';
	}

	/**
	 * Output the file
	 *
	 * @param   string  $file  File
	 * @param   string  $name  Name
	 *
	 * @return  void
	 */
	protected function _output_file($file, $name)
	{
		/*do something on download abort/finish
		register_shutdown_function('function_name' );*/
		if (!JFile::exists($file))
		{
			die('file not exist!');
		}

		$size = filesize($file);
		$name = rawurldecode($name);

		if (preg_match('/Opera(/| )([0-9].[0-9]{1,2})/', $_SERVER['HTTP_USER_AGENT']))
		{
			$UserBrowser = "Opera";
		}
		elseif (preg_match('/MSIE ([0-9].[0-9]{1,2})/', $_SERVER['HTTP_USER_AGENT']))
		{
			$UserBrowser = "IE";
		}
		else
		{
			$UserBrowser = '';
		}

		// Important for download in most browsers
		$mime_type = ($UserBrowser == 'IE' || $UserBrowser == 'Opera') ? 'application/octetstream' : 'application/octet-stream';
		@ob_end_clean();

		// Decrease cpu usage extreme
		header('Content-Type: ' . $mime_type);
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header('Accept-Ranges: bytes');
		header("Cache-control: private");
		header('Pragma: private');

		// Multipart-download and resume-download
		if (isset($_SERVER['HTTP_RANGE']))
		{
			list($a, $range) = explode("=", $_SERVER['HTTP_RANGE']);
			str_replace($range, "-", $range);
			$size2 = $size - 1;
			$new_length = $size - $range;
			header("HTTP/1.1 206 Partial Content");
			header("Content-Length: $new_length");
			header("Content-Range: bytes $range$size2/$size");
		}
		else
		{
			$size2 = $size - 1;
			header("Content-Length: " . $size);
		}

		$chunksize = 1 * (1024 * 1024);
		$this->bytes_send = 0;

		if ($file = fopen($file, 'r'))
		{
			if (isset($_SERVER['HTTP_RANGE']))
			{
				fseek($file, $range);
			}

			while (!feof($file) and (connection_status() == 0))
			{
				$buffer = fread($file, $chunksize);
				print($buffer);
				flush();
				$this->bytes_send += JString::strlen($buffer);
			}

			fclose($file);
		}
		else
		{
			die('error can not open file');
		}

		if (isset($new_length))
		{
			$size = $new_length;
		}
	}
}
