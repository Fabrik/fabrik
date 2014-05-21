<?php
/**
 * Download list plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.download
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

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
	 * @param   array  &$args  Arguments
	 *
	 * @return  bool;
	 */

	public function button(&$args)
	{
		parent::button($args);

		return true;
	}

	/**
	 * Get the button label
	 *
	 * @return  string
	 */

	protected function buttonLabel()
	{
		return $this->getParams()->get('download_button_label', parent::buttonLabel());
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
	 * Do the plug-in action
	 *
	 * @param   array  $opts  Custom options
	 *
	 * @return  bool
	 */

	public function process($opts = array())
	{
		$params = $this->getParams();
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = $this->getModel();
		$ids = $input->get('ids', array(), 'array');
		$download_table = $params->get('download_table');
		$download_fk = $params->get('download_fk');
		$download_file = $params->get('download_file');
		$download_width = $params->get('download_width');
		$download_height = $params->get('download_height');
		$download_resize = ($download_width || $download_height) ? true : false;
		$table = $model->getTable();
		$filelist = array();
		$zip_err = '';

		// Check ajax upload file names
		$formModel = $model->getFormModel();
		$downloadElement = $formModel->getElement($download_file);

		if ($downloadElement)
		{
			$download_file = $downloadElement->getFullName(true, true);
		}

		if (empty($download_fk) && empty($download_file) && empty($download_table))
		{
			return;
		}
		elseif (empty($download_fk) && empty($download_table) && !empty($download_file))
		{
			foreach ($ids AS $id)
			{
				$row = $model->getRow($id);

				if (isset($row->$download_file))
				{
					$tmpFiles = explode(GROUPSPLITTER, $row->$download_file);

					foreach ($tmpFiles as $tmpFile)
					{
						$this_file = JPATH_SITE . '/' . $tmpFile;

						if (is_file($this_file))
						{
							$filelist[] = $this_file;
						}
					}
				}
			}
		}
		else
		{
			$db = FabrikWorker::getDbo();
			JArrayHelper::toInteger($ids);
			$query = $db->getQuery(true);
			$query->select($db->quoteName($download_file))
			->from($db->quoteName($download_table))
			->where($db->quoteName($download_fk) . ' IN (' . implode(',', $ids) . ')');
			$db->setQuery($query);
			$results = $db->loadObjectList();

			foreach ($results AS $result)
			{
				$this_file = JPATH_SITE . '/' . $result->$download_file;

				if (is_file($this_file))
				{
					$filelist[] = $this_file;
				}
			}
		}

		if (!empty($filelist))
		{
			if ($download_resize)
			{
				ini_set('max_execution_time', 300);
				require_once COM_FABRIK_FRONTEND . '/helpers/image.php';
				$storage = $this->getStorage();
				$download_image_library = $params->get('download_image_library');
				$oImage = FabimageHelper::loadLib($download_image_library);
				$oImage->setStorage($storage);
			}

			$zipfile = tempnam(sys_get_temp_dir(), "zip");
			$zipfile_basename = basename($zipfile);
			$zip = new ZipArchive;
			$zipres = $zip->open($zipfile, ZipArchive::OVERWRITE);

			if ($zipres === true)
			{
				$ziptot = 0;
				$tmp_files = array();

				foreach ($filelist AS $this_file)
				{
					$this_basename = basename($this_file);

					if ($download_resize && $oImage->getImgType($this_file))
					{
						$tmp_file = '/tmp/' . $this_basename;
						$oImage->resize($download_width, $download_height, $this_file, $tmp_file);
						$this_file = $tmp_file;
						$tmp_files[] = $tmp_file;
					}

					$zipadd = $zip->addFile($this_file, $this_basename);

					if ($zipadd === true)
					{
						$ziptot++;
					}
					else
					{
						$zip_err .= FText::_('ZipArchive add error: ' . $zipadd);
					}
				}

				if (!$zip->close())
				{
					$zip_err = FText::_('ZipArchive close error') . ($zip->status);
				}

				if ($download_resize)
				{
					foreach ($tmp_files as $tmp_file)
					{
						$storage->delete($tmp_file);
					}
				}

				if ($ziptot > 0)
				{
					// Stream the file to the client
					$filesize = filesize($zipfile);

					if ($filesize > 0)
					{
						header("Content-Type: application/zip");
						header("Content-Length: " . filesize($zipfile));
						header("Content-Disposition: attachment; filename=\"$zipfile_basename.zip\"");
						echo file_get_contents($zipfile);
						JFile::delete($zipfile);
						exit;
					}
					else
					{
						$zip_err .= FText::_('ZIP is empty');
					}
				}
			}
			else
			{
				$zip_err = FText::_('ZipArchive open error: ' . $zipres);
			}
		}
		else
		{
			$zip_err = "No files to ZIP!";
		}

		if (empty($zip_err))
		{
			return true;
		}
		else
		{
			$this->msg = $zip_err;

			return false;
		}
	}

	/**
	 * Get the message generated in process()
	 *
	 * @param   int  $c  Plugin render order
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
	 * @param   array  $args  Array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 */

	public function onLoadJavascriptInstance($args)
	{
		parent::onLoadJavascriptInstance($args);
		$opts = $this->getElementJSOptions();
		$opts = json_encode($opts);
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
			$params = $this->getParams();
			$storageType = 'filesystemstorage';
			require_once JPATH_ROOT . '/plugins/fabrik_element/fileupload/adaptors/' . $storageType . '.php';
			$this->storage = new $storageType($params);
		}

		return $this->storage;
	}
}
