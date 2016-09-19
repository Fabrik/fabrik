<?php
/**
 * Abstract Storage adaptor for Fabrik file upload element
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Abstract Storage adaptor for Fabrik file upload element
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
abstract class FabrikStorageAdaptor
{
	/**
	 * Path or url to uploaded file
	 *
	 * @var string
	 */
	protected $uploadedFilePath = null;

	/**
	 * Constructor
	 *
	 * @param   Registry  &$params  Options
	 */
	public function __construct(&$params)
	{
		$this->params = $params;
	}

	/**
	 * Get params
	 *
	 * @return  Registry
	 */
	public function &getParams()
	{
		return $this->params;
	}

	/**
	 * Get the uploaded file path
	 *
	 * @return  string
	 */
	public function getUploadedFilePath()
	{
		return $this->uploadedFilePath;
	}

	/**
	 * Does a file exist
	 *
	 * @param   string  $filepath  File path to test
	 * @param   bool    $prependRoot  also test with root prepended
	 *
	 * @return bool
	 */
	public abstract function exists($filepath, $prependRoot = true);

	/**
	 * Does a folder exist
	 *
	 * @param   string  $path  Folder path to test
	 *
	 * @return bool
	 */
	public abstract function folderExists($path);

	/**
	 * Create a folder
	 *
	 * @param   string   $path  Folder path
	 * @param   bitmask  $mode  Permissions
	 *
	 * @return bool
	 */
	public abstract function createFolder($path, $mode = 0755);

	/**
	 * Write a file
	 *
	 * @param   string  $file    File name
	 * @param   string  $buffer  The buffer to write
	 *
	 * @return  void
	 */
	public abstract function write($file, $buffer);

	/**
	 * Read a file
	 *
	 * @param   string  $filepath  File path
	 *
	 * @return  mixed  Returns file contents or boolean False if failed
	 */
	public abstract function read($filepath);

	/**
	 * Clean the file path
	 *
	 * @param   string  $path  Path to clean
	 *
	 * @return  string  cleaned path
	 */
	public abstract function clean($path);

	/**
	 * Clean a file name
	 *
	 * @param   string  $filename       File name to clean
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  string  cleaned name
	 */
	public abstract function cleanName($filename, $repeatCounter);

	/**
	 * Delete a file
	 *
	 * @param   string  $filepath  File to delete
	 *
	 * @return  void
	 */
	public abstract function delete($filepath);

	/**
	 * Moves an uploaded file to a destination folder
	 *
	 * @param   string  $tmpFile   The name of the php (temporary) uploaded file
	 * @param   string  $filepath  The path (including filename) to move the uploaded file to
	 *
	 * @return  boolean True on success
	 */
	public abstract function upload($tmpFile, $filepath);

	/**
	 * Set a file's permissions
	 *
	 * @param   string  $filepath  File to set permissions for
	 *
	 * @return  string
	 */
	public abstract function setPermissions($filepath);

	/**
	 * Convert a full url into a full server path
	 *
	 * @param   string  $url  URL
	 *
	 * @return string  path
	 */
	public function urlToPath($url)
	{
		return $url;
	}

	/**
	 * Do a final transform on the path name
	 *
	 * @param   string  &$filepath  Path to parse
	 *
	 * @return  void
	 */
	public function finalFilePathParse(&$filepath)
	{
	}

	/**
	 * Convert a full server path into a full url
	 *
	 * @param   string  $path  Server path
	 *
	 * @return  string  url
	 */
	public function pathToURL($path)
	{
		$path = str_replace(COM_FABRIK_BASE, '', $path);
		$path = FabrikString::ltrimiword($path, '/');
		$path = COM_FABRIK_LIVESITE . $path;
		$path = str_replace('\\', '/', $path);

		// Some servers do not like double slashes in the URL.
		$path = str_replace('\/\/', '/', $path);

		return $path;
	}

	/**
	 * Make recursive folders
	 *
	 * @param   string   $folderPath  Path to folder - e.g. /images/stories
	 * @param   bitmask  $mode        Permissions
	 *
	 * @return  mixed JError|void
	 */
	public function makeRecursiveFolders($folderPath, $mode = 0755)
	{
		if (!JFolder::exists($folderPath))
		{
			if (!JFolder::create($folderPath, $mode))
			{
				throw new RuntimeException("Could not make dir $folderPath ", 21);
			}
		}
	}

	/**
	 * Get the complete folder path, including the server root
	 *
	 * @param   string  $filepath  The file path
	 *
	 * @return  string
	 */
	public abstract function getFullPath($filepath);

	/**
	 * Allows storage model to modify pathname just before it is rendered.  For instance,
	 * if using Amazon S3 with 'Authenticated URL' option.
	 *
	 * @param   string  $filepath  Path to file
	 *
	 * @return  string
	 */
	public function preRenderPath($filepath)
	{
		return $filepath;
	}

	/**
	 * When creating file paths, do we need to append them with JPATH_SITE
	 *
	 * @since  3.0.6.2
	 *
	 * @return  bool
	 */
	public function appendServerPath()
	{
		return true;
	}

	/**
	 * Randomize file name
	 *
	 * @param   string  &$filename  File name
	 *
	 * @since 3.0.8
	 *
	 * @return void
	 */
	protected function randomizeName(&$filename)
	{
		$params = $this->getParams();

		if ($params->get('random_filename') == 1)
		{
			$length = (int) $params->get('length_random_filename');

			if ($length < 6)
			{
				$length = 6;
			}

			$key = "";
			$possible = "0123456789bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRTVWXYZ";
			$i = 0;

			while ($i < $length)
			{
				$char = JString::substr($possible, mt_rand(0, JString::strlen($possible) - 1), 1);
				$key .= $char;
				$i++;
			}

			$ext = JFile::getExt($filename);
			$filename = $key . '.' . $ext;
		}
	}
}
