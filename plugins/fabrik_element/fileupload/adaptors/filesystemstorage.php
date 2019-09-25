<?php
/**
 * Server File System Storage adaptor for Fabrik file upload element
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ROOT . '/plugins/fabrik_element/fileupload/adaptor.php';

/**
 * Server File System Storage adaptor for Fabrik file upload element
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class Filesystemstorage extends FabrikStorageAdaptor
{
	/**
	 * Does a file exist
	 *
	 * @param   string  $filepath     File path to test
	 * @param   bool    $prependRoot  also test with root prepended
	 *
	 * @return bool
	 */

	public function exists($filepath, $prependRoot = true)
	{
		if (empty($filepath) || $filepath == '\\')
		{
			return false;
		}

		if (JFile::exists($filepath))
		{
		    return true;
		}

		if ($prependRoot)
		{
			$filepath = COM_FABRIK_BASE . '/' . FabrikString::ltrimword($filepath, COM_FABRIK_BASE . '/');

			return JFile::exists($filepath);
		}

		return false;
	}

	/**
	 * Does a folder exist
	 *
	 * @param   string  $path  folder path to test
	 *
	 * @return bool
	 */

	public function folderExists($path)
	{
		return JFolder::exists($path);
	}

	/**
	 * Create empty index.html for security
	 *
	 * @param   string  $path  path to folder
	 *
	 * @return bool success
	 */

	public function createIndexFile($path)
	{
		// Don't write a index.html in root
		if ($path === '')
		{
			return true;
		}

		$index_file = $path . '/index.html';

		if (!$this->exists($index_file))
		{
			$content = FText::_('PLG_ELEMENT_FILEUPLOAD_INDEX_FILE_CONTENT');

			return JFile::write($index_file, $content);
		}

		return true;
	}

	/**
	 * Create a folder
	 *
	 * @param   string   $path  Folder path
	 * @param   bitmask  $mode  Permissions
	 *
	 * @return bool
	 */

	public function createFolder($path, $mode = 0755)
	{
		if (JFolder::create($path, $mode))
		{
			return $this->createIndexFile($path);
		}

		return false;
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
		static $nested = 0;

		// Check if parent dir exists
		$parent = dirname($folderPath);

		if (!$this->folderExists($parent))
		{
			// Prevent infinite loops!
			$nested++;

			if (($nested > 20) || ($parent == $folderPath))
			{
				$nested--;

				return false;
			}

			if ($this->makeRecursiveFolders($parent, $mode) !== true)
			{
				// JFolder::create throws an error
				$nested--;

				return false;
			}

			// OK, parent directory has been created
			$nested--;
		}

		if (JFolder::exists($folderPath))
		{
			return true;
		}

		return $this->createFolder($folderPath, $mode);
	}

	/**
	 * Clean the file path
	 *
	 * @param   string  $path  path to clean
	 *
	 * @return  string  cleaned path
	 */

	public function clean($path)
	{
		return JPath::clean($path);
	}

	/**
	 * Clean a file name
	 *
	 * @param   string  $filename       file name to clean
	 * @param   int     $repeatCounter  repeat group counter
	 *
	 * @return  string  cleaned name
	 */

	public function cleanName($filename, $repeatCounter)
	{
		$params = $this->getParams();

		if ($params->get('fu_clean_filename', '1') === '1')
		{
			// Replace any non-alphanumeric chars (except _ and - and .) with _
			$filename = preg_replace('#[^a-zA-Z0-9_\-\.]#', '_', $filename);
		}

		$this->randomizeName($filename);

		return $filename;
	}

	/**
	 * Delete a file
	 *
	 * @param   string  $filepath  file to delete
	 * @param   bool    $prependRoot  also test with root prepended
	 *
	 * @return  void
	 */

	public function delete($filepath, $prependRoot = true)
	{
		if (JFile::exists($filepath))
		{
			return JFile::delete($filepath);
		}
		else
		{
			if ($prependRoot)
			{
				$filepath = COM_FABRIK_BASE . '/' . FabrikString::ltrimword($filepath, COM_FABRIK_BASE . '/');

				return JFile::delete($filepath);
			}

			return false;
		}
	}

	/**
	 * Moves an uploaded file to a destination folder
	 *
	 * @param   string  $tmpFile   The name of the php (temporary) uploaded file
	 * @param   string  $filepath  The path (including filename) to move the uploaded file to
	 *
	 * @return  boolean True on success
	 */

	public function upload($tmpFile, $filepath)
	{
		$this->uploadedFilePath = $filepath;

		$params = $this->getParams();
		$allowUnsafe = $params->get('allow_unsafe', '0') === '1';

		if (JFile::upload($tmpFile, $filepath, false, $allowUnsafe))
		{
			return $this->createIndexFile(dirname($filepath));
		}

		return false;
	}

	/**
	 * Set a file's permissions
	 *
	 * @param   string  $filepath  file to set permissions for
	 *
	 * @return  string
	 */

	public function setPermissions($filepath)
	{
		return JPath::setPermissions($filepath);
	}

	/**
	 * Write a file
	 *
	 * @param   string  $file    file name
	 * @param   string  $buffer  the buffer to write
	 *
	 * @return  bool
	 */

	public function write($file, $buffer)
	{
		return JFile::write($file, $buffer);
	}

	/**
	 * Read a file
	 *
	 * @param   string  $filepath  file path
	 *
	 * @return  mixed  Returns file contents or boolean False if failed
	 */

	public function read($filepath)
	{
		return file_get_contents($filepath);
	}

	/**
	 * Stream a file
	 *
	 * @param   string  $filepath  file path
	 * @param   int     $chunkSize  chunk size
	 *
	 * @return  bool
	 */

	public function stream($filepath, $chunkSize = 1048576)
	{
		$buffer = '';
		$handle = fopen($filepath, 'rb');

		if ($handle === false)
		{
			return false;
		}

		while (!feof($handle))
		{
			$buffer = fread($handle, $chunkSize);
			echo $buffer;
			ob_flush();
			flush();
		}

		fclose($handle);

		return true;
	}

	/**
	 * Get the file's URL
	 *
	 * @param   string  $file  file path
	 *
	 * @return  string  URL
	 */

	public function getFileUrl($file)
	{
		$livesite = COM_FABRIK_LIVESITE;
		$livesite = rtrim($livesite, '/\\');
		$file = JString::ltrim($file, '/\\');

		return str_replace("\\", "/", $livesite . '/' . $file);
	}

	/**
	 * Get the thumbnail URL for the file given
	 *
	 * @param   string  $file  url
	 *
	 * @return string thumbnail url
	 */

	public function _getThumb($file)
	{
		return $this->_getSmallerFile($file, 'thumb');
	}

	/**
	 * Get the path (relative to site root?) to the smaller file
	 *
	 * @param   string  $file  large file path
	 * @param   string  $type  type (thumb or crop)
	 *
	 * @return  string
	 */

	protected function _getSmallerFile($file, $type)
	{
		$params = $this->getParams();
		$w = new FabrikWorker;

		// $$$ rob wasn't working when getting thumb path on upload
		$ulDir = JPath::clean($params->get('ul_directory'));
		$ulDir = str_replace("\\", "/", $ulDir);

		// If we're deleting a file, See http://fabrikar.com/forums/showthread.php?t=31715
		$file = str_replace("\\", "/", $file);

		// Replace things like $my->id may barf on other stuff
		$afile = str_replace(JURI::root(), '', $file);
		$afile = JString::ltrim($afile, "/");
		$ulDir = JString::ltrim($ulDir, "/");
		$ulDir = JString::rtrim($ulDir, "/");
		$ulDirbits = explode('/', $ulDir);
		$filebits = explode('/', $afile);

		$match = array();
		$replace = array();

		for ($i = 0; $i < count($filebits); $i++)
		{
			if (array_key_exists($i, $ulDirbits) && $filebits[$i] != $ulDirbits[$i])
			{
				$match[] = $ulDirbits[$i];
				$replace[] = $filebits[$i];
			}
		}

		$ulDir = str_replace($match, $replace, $ulDir);

		// $$$ rob wasn't working when getting thumb path on upload
		$typeDir = $type == 'thumb' ? $params->get('thumb_dir') : $params->get('fileupload_crop_dir');
		$thumbdir = str_replace($match, $replace, $typeDir);
		$ulDir = $w->parseMessageForPlaceHolder($ulDir);
		$thumbdir = $w->parseMessageForPlaceHolder($thumbdir);
		$file = $w->parseMessageForPlaceHolder($file);
		$f = basename($file);
		$dir = dirname($file);
		$dir = str_replace($ulDir, $thumbdir, $dir);
		$ext = JFile::getExt($f);

		// Remove extension
		$fclean = str_replace('.' . $ext, '', $f);

		if ($type == 'thumb')
		{
			// $f replaced by $fclean, $ext
			$file = $dir . '/' . $params->get('thumb_prefix') . $fclean . $params->get('thumb_suffix') . '.' . $ext;
		}
		else
		{
			$file = $dir . '/' . $f;
		}

		return $file;
	}

	/**
	 * Get the cropped file name
	 *
	 * @param   string  $file  large file name
	 *
	 * @return  string  cropped file name
	 */

	public function _getCropped($file)
	{
		return $this->_getSmallerFile($file, 'crop');
	}

	/**
	 * Convert a full url into a full server path
	 *
	 * @param   string  $url  URL
	 *
	 * @see /plugins/fabrik_element/fileupload/storageAdaptor#urlToPath($url)
	 *
	 * @return string  path
	 */

	public function urlToPath($url)
	{
		return str_replace(COM_FABRIK_LIVESITE, COM_FABRIK_BASE, $url);
	}

	/**
	 * Do a final transform on the path name
	 *
	 * @param   string  &$filepath  path to parse
	 *
	 * @return  void
	 */

	public function finalFilePathParse(&$filepath)
	{
		/* $$$ hugh - oops!  Double Plus Ungood if JPATH_SITE is just /,
		 * which happens on some shared hosts which are chrooted (jailed)
		 * 'cos then we just strip out all the /'s in the path!
		 *$filepath = str_replace(JPATH_SITE, '', $filepath);
		 */
		$filepath = preg_replace('#^' . preg_quote(JPATH_SITE, '#') . '#', '', $filepath);
	}

	/**
	 * Get file info using getid3
	 *
	 * @param   string  $filepath  file path
	 *
	 * @return mixed array|false
	 */

	public function getFileInfo($filepath)
	{
	    $filepath = $this->getFullPath($filepath);

		if ($this->exists($filepath))
		{
			// $$$ hugh - turn of E_DEPRECATED to avoid warnings about eregi() in getid3
			// LOL!  E_DEPRECATED only available in 5.3.0+, pitches Notice in anything earlier.  :)
			if (version_compare(PHP_VERSION, '5.3.0') >= 0)
			{
				$current_level = error_reporting();
				error_reporting($current_level & ~E_DEPRECATED);
			}

			require_once COM_FABRIK_FRONTEND . '/libs/phpmimetypeclass/class.mime.php';
			$mime = new MIMETypes;
			$thisFileInfo['filesize'] = filesize($filepath);
			$thisFileInfo['filename'] = basename($filepath);
			$thisFileInfo['mime_type'] = $mime->getMimeType($filepath);

			return $thisFileInfo;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get the complete folder path, including the server root
	 *
	 * @param   string  $filepath  The file path
	 *
	 * @return  string   The cleaned full file path
	 */

	public function getFullPath($filepath)
	{
		if (!(preg_match('#^' . preg_quote(COM_FABRIK_BASE, '#') . '#', $filepath)))
		{
			$filepath = COM_FABRIK_BASE . '/' . $filepath;
		}

		$filepath = JPath::clean($filepath);

		return $filepath;
	}

	/**
	 * Check for snooping
	 *
	 * @param   string   $folder   The file path
	 *
	 * @return  void
	 */
	public function checkPath($folder)
	{
		if ($this->appendServerPath())
		{
			JPath::check($folder);
		}
	}

	/**
	 * Return the directory separator - can't use DIRECTORY_SEPARATOR by default, as s3 uses /
	 *
	 * @return string
	 *
	 * @since 3.8
	 */
	public function getDS()
	{
		return DIRECTORY_SEPARATOR;
	}
}
