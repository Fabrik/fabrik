<?php
/**
 * A cron task to email records to a give set of users
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.importcsv
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-cron.php';
require_once COM_FABRIK_FRONTEND . '/models/importcsv.php';

/**
 * Cron Import CSV class
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.email
 * @since       3.0
 */

class PlgFabrik_Cronimportcsv extends PlgFabrik_Cron
{
	/**
	 * Check if the user can use the active element
	 *
	 * @param   string  $location  To trigger plugin on
	 * @param   string  $event     To trigger plugin on
	 *
	 * @return  bool can use or not
	 */

	public function canUse($location = null, $event = null)
	{
		return true;
	}

	/**
	 * Whether cron should automagically load table data
	 *
	 * @return  bool
	 */

	public function requiresTableData()
	{
		/* We don't need cron to load $data for us */
		return false;
	}

	/**
	 * Get the list id from the filename
	 *
	 * @param   string  $tableName  The name of the file to be loaded.  Should only be file name--not a path.
	 *
	 * @author Kyle
	 *
	 * @return  int  listid  The id frabrik gives the list that hold information about files named $tablename
	 * returns an empty() type if no table exists with the same name as $tablename.
	 */

	protected function getListIdFromFileName($tableName)
	{
		// Get site's database
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('id')->from('#__{package}_lists')->where('db_table_name = ' . $db->quote($tableName));
		$db->setQuery($query);
		$id = $db->loadResult();

		return $id;
	}

	/**
	 * Do the plugin action
	 *
	 * @param   array   &$data       array data to process
	 * @param   object  &$listModel  plugin's list model
	 *
	 * @return  int  number of records run
	 */

	public function process(&$data, &$listModel)
	{
		$input = $this->app->input;
		$params = $this->getParams();

		// Get plugin settings and save state of request array vars we might change
		$maxFiles = (int) $params->get('cron_importcsv_maxfiles', 1);
		$deleteFile = $params->get('cron_importcsv_deletefile', true);
		$cronDir = $params->get('cron_importcsv_directory');
		$useTableName = (int) $params->get('cron_importcsv_usetablename', false);

		$dropData = $params->get('cron_importcsv_dropdata', '0');
		$origDropData = $input->get('dropdata', -1);

		$overwrite = $params->get('cron_importcsv_overwrite', '0');
		$origOverwrite = $input->get('overwrite', -1);

		$fieldDelimiter = $params->get('cron_importcsv_field_delimiter', ',');
		$origFieldDelimiter = $input->get('field_delimiter', -1);

		$textDelimiter = $params->get('cron_importcsv_text_delimiter', '0');
		$orig_text_delimiter = $input->get('text_delimiter', -1);

		$jForm = array();
		$jForm['drop_data'] = $dropData;
		$jForm['overwrite'] = $overwrite;
		$jForm['field_delimiter'] = $fieldDelimiter;

		if ($fieldDelimiter == '\t')
		{
			$jForm['tabdelimited'] = '1';
		}

		$jForm['text_delimiter'] = $textDelimiter;

		$input->set('jform', $jForm);
		$origListId = $input->getInt('listid', -1);

		// Fabrik use this as the base directory, so we need a new directory under 'media'
		define("FABRIK_CSV_IMPORT_ROOT", str_replace('\\', '/', JPATH_ROOT . '/media'));
		$d = FABRIK_CSV_IMPORT_ROOT . '/' . $cronDir;

		// TODO: Need to also have a FILTER for CSV files ONLY.
		$filter = "\.CSV$|\.csv$";
		$exclude = array('done', '.svn', 'CVS');
		$files = JFolder::files($d, $filter, true, true, $exclude);

		// The csv import class needs to know we are doing a cron import
		$input->set('cron_csvimport', true);
		$xfiles = 0;

		foreach ($files as $fullCsvFile)
		{
			$fullCsvFile = str_replace('\\', '/', $fullCsvFile);
			if (++$xfiles > $maxFiles)
			{
				break;
			}

			FabrikWorker::log('plg.cron.cronimportcsv.information', "Starting import: $fullCsvFile:  ");
			$clsImportCSV = JModelLegacy::getInstance('Importcsv', 'FabrikFEModel');

			if ($useTableName)
			{
				$listId = $this->getListIdFromFileName(basename($fullCsvFile));
			}
			else
			{
				$table = $listModel->getTable();
				$listId = $table->id;
			}

			if (empty($listId))
			{
				FabrikWorker::log('plg.cron.cronimportcsv.warning', "List for $fullCsvFile does not exist");
				continue;
			}

			$input->set('listid', $listId);

			// Grab the CSV file, need to strip import root off path first
			$csvFile = str_replace(FABRIK_CSV_IMPORT_ROOT, '', $fullCsvFile);
			$clsImportCSV->setBaseDir(FABRIK_CSV_IMPORT_ROOT);
			$clsImportCSV->readCSV($csvFile);

			// Get this->matchedHeading
			$clsImportCSV->findExistingElements();
			$msg = $clsImportCSV->makeTableFromCSV();

			if ($this->app->isAdmin())
			{
				$this->app->enqueueMessage($msg);
			}

			if ($deleteFile == '1')
			{
				JFile::delete($fullCsvFile);
			}
			elseif ($deleteFile == '2')
			{
				$new_csvfile = $fullCsvFile . '.' . time();
				JFile::move($fullCsvFile, $new_csvfile);
			}
			elseif ($deleteFile == '3')
			{
				$done_folder = dirname($fullCsvFile) . '/done';

				if (JFolder::exists($done_folder))
				{
					$new_csvfile = $done_folder . '/' . basename($fullCsvFile);
					JFile::move($fullCsvFile, $new_csvfile);
				}
				else
				{
					if ($this->app->isAdmin())
					{
						$this->app->enqueueMessage("Move file requested, but can't find 'done' folder: $done_folder");
					}
				}
			}

			FabrikWorker::log('plg.cron.cronimportcsv.information', $msg);
		}

		// Leave the request array how we found it
		if (!empty($origListId))
		{
			$input->set('listid', $origListId);
		}

		if ($origDropData != -1)
		{
			$input->set('drop_data', $origDropData);
		}

		if ($origOverwrite != -1)
		{
			$input->set('overwite', $origOverwrite);
		}

		if ($origFieldDelimiter != -1)
		{
			$input->set('field_delimiter', $origFieldDelimiter);
		}

		if ($orig_text_delimiter != -1)
		{
			$input->set('text_delimiter', $orig_text_delimiter);
		}

		if ($xfiles > 0)
		{
			$updates = $clsImportCSV->addedCount + $clsImportCSV->updatedCount;
		}
		else
		{
			$updates = 0;
		}

		return $updates;
	}
}
