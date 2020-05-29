<?php
/**
 * Download list plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.download
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Image;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Download list plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.download
 * @since       3.0
 */
class PlgFabrik_ListDownload extends PlgFabrik_List
{
	/**
	 * Button prefix
	 *
	 * @var string
	 */
	protected $buttonPrefix = 'download';

	/**
	 * Message
	 *
	 * @var string
	 */
	protected $msg = null;

	/**
	 * Prep the button if needed
	 *
	 * @param   array &$args Arguments
	 *
	 * @return  bool;
	 */
	public function button(&$args)
	{
		parent::button($args);

		return true;
	}

	/**
	 * Get button image
	 *
	 * @since   3.1b
	 *
	 * @return   string  image
	 */
	protected function getImageName()
	{
		return 'download';
	}

	/**
	 * Get the button label
	 *
	 * @return  string
	 */
	protected function buttonLabel()
	{
		return FText::_($this->getParams()->get('download_button_label', parent::buttonLabel()));
	}

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 */
	protected function getAclParam()
	{
		return 'download_access';
	}

	/**
	 * Can the plug-in select list rows
	 *
	 * @return  bool
	 */
	public function canSelectRows()
	{
		return $this->canUse();
	}

	/**
	 * Can the plug-in use AJAX
	 *
	 * @return  bool
	 */
	public function canAJAX()
	{
		return false;
	}

	/**
	 * Do the plug-in action
	 *
	 * @param   array $opts Custom options
	 *
	 * @return  bool
	 */
	public function process($opts = array())
	{
		$params          = $this->getParams();
		$input           = $this->app->input;
		$model           = $this->getModel();
		$ids             = $input->get('ids', array(), 'array');
		$downloadPdf     = $params->get('download_pdfs', '0') === '1';
		$downloadTable   = $params->get('download_table');
		$downloadFk      = $params->get('download_fk');
		$downloadFile    = $params->get('download_file');
		$download_width  = $params->get('download_width');
		$download_height = $params->get('download_height');
		$downloadResize  = ($download_width || $download_height) ? true : false;
		$fileList        = array();
		$zipErr          = '';

		if ($downloadPdf)
		{
			$fileList = $this->getPDFs('ids');
		}
		elseif (empty($downloadFk) && empty($downloadFile) && empty($downloadTable))
		{
			return;
		}
		elseif (empty($downloadFk) && empty($downloadTable) && !empty($downloadFile))
		{
			$downloadFiles = explode(',', $downloadFile);

			foreach ($ids AS $id)
			{
				$row = $model->getRow($id);

				if (!$model->canView($row))
				{
					continue;
				}

				foreach ($downloadFiles as $dl)
				{
					$dl = trim($dl);

					if (isset($row->$dl) && !empty($row->$dl))
					{
						$tmpFiles = explode(GROUPSPLITTER, $row->$dl);

						foreach ($tmpFiles as $tmpFile)
						{
							$thisFile = JPATH_SITE . '/' . $tmpFile;

							if (JFile::exists($thisFile))
							{
								$fileList[] = $thisFile;
							}
						}
					}
				}
			}
		}
		else
		{
			$db = FabrikWorker::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->qn($downloadFile))
				->from($db->qn($downloadTable))
				->where($db->qn($downloadFk) . ' IN (' . implode(',', $db->q($ids)) . ')');
			$db->setQuery($query);
			$results = $db->loadObjectList();

			foreach ($results AS $result)
			{
				$thisFile = JPATH_SITE . '/' . $result->$downloadFile;

				if (is_file($thisFile))
				{
					$fileList[] = $thisFile;
				}
			}
		}

		if (!empty($fileList))
		{
			if ($downloadResize)
			{
				ini_set('max_execution_time', 300);
				$storage              = $this->getStorage();
				$downloadImageLibrary = $params->get('download_image_library');
				$oImage               = Image::loadLib($downloadImageLibrary);
				$oImage->setStorage($storage);
			}

			/**
			 * $$$ hugh - system tmp dir is sometimes not readable, i.e. on restrictive open_base_dir setups,
			 * so use J! tmp folder instead.
			 * $zipFile = tempname(sys_get_temp_dir(), "zip");
			 */
			$zipFile         = tempnam($this->config->get('tmp_path'), "zip");
			$zipFileBasename = basename($zipFile);
			$zip             = new ZipArchive;
			$zipRes          = $zip->open($zipFile, ZipArchive::CREATE);

			if ($zipRes === true)
			{
				$zipTotal = 0;
				$tmpFiles = array();

				foreach ($fileList AS $thisFile)
				{
					$thisBaseName = basename($thisFile);

					if ($downloadResize && $oImage->getImgType($thisFile))
					{
						$tmpFile = '/tmp/' . $thisBaseName;
						$oImage->resize($download_width, $download_height, $thisFile, $tmpFile);
						$thisFile  = $tmpFile;
						$tmpFiles[] = $tmpFile;
					}

					$zipAdd = $zip->addFile($thisFile, $thisBaseName);

					if ($zipAdd === true)
					{
						$zipTotal++;
					}
					else
					{
						$zipErr .= FText::_('ZipArchive add error: ' . $zipAdd);
					}
				}

				if (!$zip->close())
				{
					$zipErr = FText::_('ZipArchive close error') . ($zip->status);
				}

				if ($downloadResize)
				{
					foreach ($tmpFiles as $tmpFile)
					{
						$storage->delete($tmpFile);
					}
				}

				if ($downloadPdf)
				{
					foreach ($fileList as $tmpFile)
					{
						JFile::delete($tmpFile);
					}
				}

				if ($zipTotal > 0)
				{
					// Stream the file to the client
					$fileSize = filesize($zipFile);

					if ($fileSize > 0)
					{
						header('Content-Type: application/zip');
						header('Content-Length: ' . filesize($zipFile));
						header('Content-Disposition: attachment; filename="' . $zipFileBasename . '.zip"');
						echo file_get_contents($zipFile);
						JFile::delete($zipFile);
						exit;
					}
					else
					{
						$zipErr .= FText::_('PLG_FABRIK_LIST_DOWNLOAD_ZIP_EMPTY');
					}
				}
			}
			else
			{
				$zipErr = FText::_('ZipArchive open error, cannot create file : ' . $zipFile . ' : ' . $zipRes);
			}
		}
		else
		{
			$zipErr = FText::_("PLG_FABRIK_LIST_DOWNLOAD_ZIP_NO_FILES");
		}

		if (empty($zipErr))
		{
			return true;
		}
		else
		{
			$this->msg = $zipErr;

			return false;
		}
	}

	/**
	 * Get the message generated in process()
	 *
	 * @param   int $c Plugin render order
	 *
	 * @return  string
	 */
	public function process_result($c)
	{
		return $this->msg;
	}

	/**
	 * Return the javascript to create an instance of the class defined in formJavascriptClass
	 *
	 * @param   array $args Array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 */
	public function onLoadJavascriptInstance($args)
	{
		parent::onLoadJavascriptInstance($args);
		$opts             = $this->getElementJSOptions();
		$opts             = json_encode($opts);
		$this->jsInstance = "new FbListDownload($opts)";

		return true;
	}

	/**
	 * Get filesystem storage class
	 *
	 * @return  object  Filesystem storage
	 */
	protected function getStorage()
	{
		if (!isset($this->storage))
		{
			$params      = $this->getParams();
			$storageType = 'filesystemstorage';
			require_once JPATH_ROOT . '/plugins/fabrik_element/fileupload/adaptors/' . $storageType . '.php';
			$this->storage = new $storageType($params);
		}

		return $this->storage;
	}

	/**
	 * Get the selected records
	 *
	 * @param   string $key key
	 *
	 * @return    array    pdf file paths
	 */
	public function getPDFs($key = 'ids')
	{
		$pdfFiles = array();
		$input    = $this->app->input;

		/** @var FabrikFEModelList $model */
		$model     = $this->getModel();
		$formModel = $model->getFormModel();
		$formId    = $formModel->getId();

		$ids = (array) $input->get($key, array(), 'array');

		foreach ($ids as $rowId)
		{
			$row = $model->getRow($rowId);

			if (!$model->canView($row))
			{
				continue;
			}

			$p = tempnam($this->config->get('tmp_path'), 'download_');

			if (empty($p))
			{
				return false;
			}

			JFile::delete($p);
			$p .= '.pdf';

			$url        = COM_FABRIK_LIVESITE . 'index.php?option=com_fabrik&view=details&formid=' . $formId . '&rowid=' . $rowId . '&format=pdf';

			if (FabrikHelperHTML::isDebug())
			{
				$url .= '&XDEBUG_SESSION_START=PHPSTORM';
			}

			$pdfContent = file_get_contents($url);

			JFile::write($p, $pdfContent);

			$pdfFiles[] = $p;
		}

		return $pdfFiles;
	}

	/**
	 * Load the AMD module class name
	 *
	 * @return string
	 */
	public function loadJavascriptClassName_result()
	{
		return 'FbListDownload';
	}
}
