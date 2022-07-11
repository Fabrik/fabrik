<?php
/**
 * CSV Export Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\Html;

jimport('joomla.application.component.model');

/**
 * CSV Export Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikFEModelCSVExport extends FabModel
{
	/**
	 * Number of records to output at a time
	 *
	 * @var int
	 */
	public $step = 100;

	/**
	 * Out put format
	 *
	 * @var string
	 */
	public $outPutFormat = 'csv';

	/**
	 * Cell delimiter
	 *
	 * @var string
	 */
	protected $delimiter = ';';

	/**
	 * @var FabrikFEModelList
	 */
	public $model;

	/**
	 * Get csv export step from params or URL
	 *
	 * @return  integer  Export step
	 */
	public function getStep()
	{
		$input = $this->app->input;
		$step_param = $this->model->getParams()->get('csv_export_step', $this->step);
		$step_url = $input->get('csv_export_step', $step_param);
		return (int) $step_url;
	}

	/**
	 * Write the current batch section of the CSV file
	 *
	 * @param   int  $total       Total # of records
	 * @param   bool $canDownload Can we also download the file (at end of export)
	 *
	 * @return  null
	 */
	public function writeFile($total, $canDownload = false)
	{
		$params = $this->model->getParams();
		$input = $this->app->input;

		// F3 turn off error reporting as this is an ajax call
		error_reporting(0);
		jimport('joomla.filesystem.file');
		$start    = $input->getInt('start', 0);
		$filePath = $this->getFilePath();
		$str      = '';

		if (JFile::exists($filePath))
		{
			if ($start === 0)
			{
				JFile::delete($filePath);
			}
			else
			{
				$str = file_get_contents($filePath);
			}
		}
		else
		{
			// Fabrik3 odd cant pass 2nd param by reference if we try to write '' so assign it to $tmp first
			$tmp = '';
			$ok  = JFile::write($filePath, $tmp);

			if (!$ok)
			{
				$this->reportWriteError($filePath);
				exit;
			}

			// with UTF8 Excel needs BOM
			$str = ( $input->get('excel') == 1 && $this->getEncoding() == 'UTF-8' ) ? "\xEF\xBB\xBF" : '';
		}

		$table = $this->model->getTable();
		$this->model->render();
		$this->removePkVal();
		$this->outPutFormat = $input->get('excel') == 1 ? 'excel' : 'csv';
		$config             = JComponentHelper::getParams('com_fabrik');
		$this->delimiter    = $this->outPutFormat == 'excel' ? COM_FABRIK_EXCEL_CSV_DELIMITER : COM_FABRIK_CSV_DELIMITER;
		$this->delimiter    = $config->get('csv_delimiter', $this->delimiter);
		$local_delimiter    = $this->model->getParams()->get('csv_local_delimiter');
		if ($local_delimiter != '') {
			$this->delimiter = $local_delimiter;
		}
		if ($this->delimiter === '\t') {
			$this->delimiter = "\t";
		}
		$end_of_line		= $this->model->getParams()->get('csv_end_of_line');
		if ($end_of_line == 'r') {
			$end_of_line = "\r";
		}
		else {
			$end_of_line = "\n";
		}
		if ($start === 0)
		{
			$headings = $this->getHeadings();

			if (empty($headings))
			{
				$url = $input->server->get('HTTP_REFERER', '');
				$this->app->enqueueMessage(FText::_('No data to export'));
				$this->app->redirect($url);

				return;
			}

			$str .= implode($this->delimiter, $headings) . $end_of_line;
		}

		$incRaw       = $input->get('incraw', true);
		$incData      = $input->get('inctabledata', true);
		$data         = $this->model->getData();
		$exportFormat = $this->model->getParams()->get('csvfullname');
		$shortKey     = FabrikString::shortColName($table->db_primary_key);
		$a            = array();

		foreach ($data as $group)
		{
			foreach ($group as $row)
			{
				$a = ArrayHelper::fromObject($row);

				if ($exportFormat == 1)
				{
					unset($a[$shortKey]);
				}

				if (!$incRaw)
				{
					foreach ($a as $key => $val)
					{
						if (substr($key, JString::strlen($key) - 4, JString::strlen($key)) == '_raw')
						{
							unset($a[$key]);
						}
					}
				}

				if (!$incData)
				{
					foreach ($a as $key => $val)
					{
						if (substr($key, JString::strlen($key) - 4, JString::strlen($key)) != '_raw')
						{
							unset($a[$key]);
						}
					}
				}

				if ($incData && $incRaw)
				{
					foreach ($a as $key => $val)
					{
						// Remove Un-needed repeat join element values.
						if (array_key_exists($key . '___params', $a))
						{
							unset($a[$key . '___params']);
						}

						if (array_key_exists($key . '_id', $a))
						{
							unset($a[$key . '_id']);
						}
					}
				}

				if ($input->get('inccalcs') == 1)
				{
					array_unshift($a, ' ');
				}

				$this->carriageReturnFix($a);

				if ($params->get('csv_format_json', '1') === '1')
				{
					array_walk($a, array($this, 'implodeJSON'), $end_of_line);
				}

				$this->model->csvExportRow = $a;
				$pluginResults = FabrikWorker::getPluginManager()->runPlugins('onExportCSVRow', $this->model, 'list', $a);
				if (in_array(false, $pluginResults))
				{
					continue;
				}
				else
				{
					$a = $this->model->csvExportRow;
				}

				$str .= implode($this->delimiter, array_map(array($this, 'quote'), array_values($a)));
				$str .= $end_of_line;
			}
		}

		$res              = new stdClass;
		$res->total       = $total;
		$res->count       = $start + $this->getStep();
		$res->file        = basename($filePath);
		$res->limitStart  = $start;
		$res->limitLength = $this->getStep();

		if ($res->count >= $res->total)
		{
			$this->addCalculations($a, $str);
		}

		error_reporting(0);
		$ok = JFile::write($filePath, $str);

		if (!$ok)
		{
			$this->reportWriteError($filePath);
			exit;
		}
		else
		{
			if (!$canDownload)
			{
				echo json_encode($res);
			}
		}
	}

	/**
	 * Format JSON data
	 */
	protected function implodeJSON(&$v, $k, $sepchar)
	{
		if (!FabrikString::isRawName($k) && FabrikWorker::isJSON($v))
		{
			$v = FabrikWorker::JSONtoData($v, true);
			$v = implode($sepchar, $v);
		}
	}

	/**
	 * Report a error writing the file
	 *
	 * @param   string $filePath file path we were trying to write to
	 *
	 * @return  null
	 */
	protected function reportWriteError($filePath)
	{
		$o      = new stdClass;
		$o->err = 'cant write file ' . $filePath;
		echo json_encode($o);
	}

	/**
	 * Fix carriage returns
	 *
	 * @param   object|array &$row Csv line of data to fix
	 *
	 * @return  null
	 */
	private function carriageReturnFix(&$row)
	{
		$newline = $this->model->getParams()->get('newline_csv_export', 'nl');

		switch ($newline)
		{
			default:
			case 'nl2br':
				if (is_array($row))
				{
					foreach ($row as &$val)
					{
						$val = nl2br($val);
						$val = str_replace(array("\n", "\r", "\n\r", "\r\n"), '', $val);
					}
				}
				else
				{
					$row = nl2br($row);
					$row = str_replace(array("\n", "\r", "\n\r", "\r\n"), '', $row);
				}

				break;
			case 'nl':
				break;
			case 'remove':
				if (is_array($row))
				{
					foreach ($row as &$val)
					{
						$val = str_replace(array("\n", "\r", "\n\r", "\r\n"), '', $val);
					}
				}
				else
				{
					$row = str_replace(array("\n", "\r", "\n\r", "\r\n"), '', $row);
				}

				break;
		}
	}

	/**
	 * Get the file name to save the csv data to
	 *
	 * @return  string  filename
	 */
	private function getFileName()
	{
		$this->model->setId($this->app->input->getInt('listid'));

		$filename = Html::getLayout('fabrik-csv-filename')
			->render((object) array(
				'model' => $this->model,
				'filename' => $this->model->getParams()->get('csv_filename')
			));
		return $filename;
	}

	/**
	 * Get the tmp folder to store the csv file in
	 *
	 * @return  string  path
	 */
	private function getFilePath()
	{
		return $this->config->get('tmp_path') . '/' . $this->getFileName();
	}

	/**
	 * Write the final csv file
	 */
	public function writeCSVFile()
	{
		$filePath = $this->getFilePath();
		$str      = $this->getCSVContent();
		JFile::delete($filePath);
		echo $str;
		exit;
	}

	/**
	 * Get the final CSV content
	 *
	 * @return bool
	 */
	protected function getCSVContent()
	{
		$filePath = $this->getFilePath();

		if (JFile::exists($filePath))
		{
			$str = file_get_contents($filePath);
		}
		else
		{
			// If we cant find the file then don't try to auto download it
			return false;
		}

		return $str;
	}

	/**
	 * Start the download of the completed csv file
	 *
	 * @return null
	 */
	public function downloadFile()
	{
		// To prevent long file from getting cut off from     //max_execution_time
		//error_reporting(0);
		@set_time_limit(0);
		jimport('joomla.filesystem.file');
		$filename = $this->getFileName();
		$filePath = $this->getFilePath();
		// Do additional processing if post-processing php file exists
		$listid = $this->app->input->getInt('listid');
		// Allows for custom csv file processing. Included php file should kill php processing
		// with die; or exit; to prevent continuation of this script (normal download). See Wiki.
		if(file_exists(JPATH_PLUGINS.'/fabrik_list/listcsv/scripts/list_'.$listid.'_csv_export.php')){	
   			require(JPATH_PLUGINS.'/fabrik_list/listcsv/scripts/list_'.$listid.'_csv_export.php');
		}
		$document = JFactory::getDocument();
		//$document->setMimeEncoding('application/zip');
		$document->setMimeEncoding('text/csv');
		$str = $this->getCSVContent();
		$this->app->clearHeaders();
		$encoding = $this->getEncoding();

		// Set the response to indicate a file download
		//$this->app->setHeader('Content-Type', 'application/zip');
		$this->app->setHeader('Content-Type', 'text/csv');
		$this->app->setHeader('Content-Disposition', "attachment;filename=\"" . $filename . "\"");

		// Xls formatting for accents
		if ($this->outPutFormat == 'excel')
		{
			$this->app->setHeader('Content-Type', 'application/vnd.ms-excel');
		}

		$this->app->setHeader('charset', $encoding);
		$this->app->setBody($str);
		echo $this->app->toString(false);
		JFile::delete($filePath);
		// $$$ rob 21/02/2012 - need to exit otherwise Chrome give 349 download error
		exit;
	}

	/**
	 * Add calculations
	 *
	 * @param   array  $a    of field elements $a
	 * @param   string &$str to out put as csv file $str
	 *
	 * @return  null
	 */
	protected function addCalculations($a, &$str)
	{
		$input = $this->app->input;

		if ($input->get('inccalcs') == 1)
		{
			$incRaw  = $input->get('incraw', true);
			$calKeys = array('sums', 'avgs', 'medians', 'count');

			foreach ($calKeys as $calKey)
			{
				$calculations[$calKey]    = FArrayHelper::array_fill(0, count($a) + 1, ' ');
				$calculations[$calKey][0] = $calKey;
				$calcs                    = $this->model->getCalculations();

				foreach ($calcs[$calKey] as $key => $cal)
				{
					$x     = 0;
					$found = false;

					// $$$rob if grouped data and calc split then get the formatted string as $cal['calc] wont exist below
					foreach ($a as $aKey => $aVal)
					{
						if (trim($aKey) == trim($key) && $x != 0)
						{
							$json = $calcs[$calKey][$aKey . '_obj'];
							unset($json['']);

							if (count($json) == 1)
							{
								$default = $defaultRaw = $json['Total']->value;
							}
							else
							{
								$default = strip_tags($calcs[$calKey][$aKey]);
								$defaultRaw = json_encode($json);
							}
						}

						$x++;
					}

					$x = 0;

					foreach ($a as $aKey => $aVal)
					{
						if ($aKey == JString::substr($key, 0, JString::strlen($key) - 4) && $x != 0)
						{
							$found = true;
							break;
						}

						$x++;
					}

					if ($found)
					{
						if (array_key_exists('calc', $cal))
						{
							$calculations[$calKey][$x] = $cal['calc']->value;

							if ($incRaw)
							{
								$calculations[$calKey][$x + 1] = $cal['calc']->value;
							}
						}
						else
						{
							$calculations[$calKey][$x] = $default;

							if ($incRaw)
							{
								$calculations[$calKey][$x + 1] = $defaultRaw;
							}
						}
					}
				}

				$str .= implode($this->delimiter, array_map(array($this, 'quote'), $calculations[$calKey]));
				$str .= "\n";
			}
		}
	}

	/**
	 * Quote a string
	 *
	 * @param   string $n string to quote
	 *
	 * @return  string
	 */
	protected function quote($n)
	{
		$cleanhtml = $this->model->getParams()->get('csv_clean_html', 'leave');
		
		switch ($cleanhtml)
		{
			default:
			case 'leave':
				break;
			
			case 'remove':
				$n = strip_tags($n);
				$n =  html_entity_decode($n);
				break;
				
			case 'replaceli':
				$n = str_replace ('<li>', '', $n);
				$n = str_replace ('</li>', "\n", $n);
				$n = strip_tags($n);
				$n =  html_entity_decode($n);
				break;
		}
		
		$doubleQuote  = $this->model->getParams()->get('csv_double_quote', '1') === '1';
		if ($doubleQuote == true)
		{
			$n = '"' . str_replace('"', '""', $n) . '"';
		}

		$csvEncoding = $this->getEncoding();

		// $$$ hugh - func won't exist if PHP wasn't built with MB string
		if (!function_exists('mb_convert_encoding') || $csvEncoding === 'UTF-8')
		{
			return $n;
		}

		if (function_exists('iconv'))
		{
			$res = iconv('UTF-8', $csvEncoding, $n);
			//$$$ trob: if iconv returns false try mb_convert instead of returning an empty field
			if ($res !== false) return $res;
		}
		return mb_convert_encoding($n, $csvEncoding, 'UTF-8');
	}

	/**
	 * Get the encoding e.g. UFT-8 for which to encode the text and set the document charset
	 * header on download
	 *
	 * @return string
	 */
	protected function getEncoding()
	{
		$params          = $this->model->getParams();
		$defaultEncoding = $this->outPutFormat == 'excel' ? 'UTF-16LE' : 'UTF-8';
		$csvEncoding     = $params->get('csv_encoding', '');

		if ($csvEncoding === '')
		{
			$csvEncoding = $defaultEncoding;
		}

		return $csvEncoding;
	}

	/**
	 * Get the headings for the csv file
	 *
	 * @return  array    heading labels
	 */
	public function getHeadings()
	{
		$input         = $this->app->input;
		$w             = new FabrikWorker;
		$table         = $this->model->getTable();
		$params        = $this->model->getParams();
		$headingFormat = $params->get('csvfullname');
		$data          = $this->model->getData();
		$g             = current($data);

		if (empty($g))
		{
			return $g;
		}

		$r         = current($g);
		$formModel = $this->model->getFormModel();
		$groups    = $formModel->getGroupsHiarachy();
		$h         = array();

		if (!is_object($r))
		{
			return new stdClass;
		}

		$incRaw  = $input->get('incraw', true);
		$incData = $input->get('inctabledata', true);

		$shortKey = FabrikString::shortColName($table->db_primary_key);

		foreach ($r as $heading => $value)
		{
			$found = false;

			foreach ($groups as $groupModel)
			{
				$elementModels = $groupModel->getPublishedElements();

				foreach ($elementModels as $elementModel)
				{
					$element  = $elementModel->getElement();
					$fullName = $elementModel->getFullName(true, false);

					if ($fullName == $heading || $fullName . '_raw' == $heading)
					{
						$found = true;

						switch ($headingFormat)
						{
							default:
							case '0':
								$n = $element->name;
								break;
							case '1':
								$n = $elementModel->getFullName(false, false);
								break;
							case '2':
								$n = $elementModel->getListHeading();
								break;
						}

						/**
						 * $$$ hugh - added next line as special case for a client, do not remove!
						 * (used in conjunction with "Custom QS" option, to allow variable header labels
						 */
						$n = $w->parseMessageForPlaceHolder($n, array());

						if ($fullName . '_raw' == $heading)
						{
							$n .= '_raw';
						}

						if ($incData && JString::substr($n, JString::strlen($n) - 4, JString::strlen($n)) !== '_raw')
						{
							if (!in_array($n, $h))
							{
								// Only add heading once
								$h[] = $n;
							}
							else
							{
								$h[] = $this->uniqueHeading($n, $h);
							}
						}

						if ($incRaw && JString::substr($n, JString::strlen($n) - 4, strlen($n)) == '_raw')
						{
							if (!in_array($n, $h))
							{
								// Only add heading once
								$h[] = $n;
							}
							else
							{
								$h[] = $this->uniqueHeading($n, $h);
							}
						}
					}
				}
			}

			if (!$found)
			{
				if (!(JString::substr($heading, JString::strlen($heading) - 4, JString::strlen($heading)) == '_raw' && !$incRaw))
				{
					// Stop id getting added to tables when exported with full element name key
					if ($headingFormat != 1 && $heading != $shortKey)
					{
						$h[] = $heading;
					}
				}
			}
		}

		if ($input->get('inccalcs') == 1)
		{
			array_unshift($h, FText::_('Calculation'));
		}

		$this->model->csvExportHeadings = $h;
		$pluginResults = FabrikWorker::getPluginManager()->runPlugins('onExportCSVHeadings', $this->model, 'list', $h);
		if (in_array(false, $pluginResults))
		{
			return false;
		}
		else
		{
			$h = $this->model->csvExportHeadings;
		}

		$h = array_map(array($this, "quote"), $h);

		return $h;
	}

	/**
	 * Get unique heading
	 *
	 * @param   string $n Key
	 * @param   array  $h Search
	 *
	 * @return  string
	 */
	protected function uniqueHeading($n, $h)
	{
		$c    = 1;
		$newN = $n . '_' . $c;

		while (in_array($newN, $h))
		{
			$c++;
			$newN = $n . '_' . $c;
		}

		return $newN;
	}

	/**
	 * Remove the __pk_val from data
	 *
	 * @return  null
	 */
	protected function removePkVal()
	{
		$data = $this->model->getData();

		foreach ($data as $group)
		{
			foreach ($group as $row)
			{
				unset($row->__pk_val);
			}
		}
	}
}
