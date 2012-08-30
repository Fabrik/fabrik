<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class storageAdaptor{

	/**@var string path or url to uploaded file */
	var $uploadedFilePath = null;

	function __construct(&$params)
	{
		$this->params = $params;
	}

	function &getParams()
	{
		return $this->params;
	}

	function getUploadedFilePath()
	{
		return $this->uploadedFilePath;
	}

	/**
	 * does a file exist
	 * @param $filepath
	 * @return unknown_type
	 */

	function exists($filepath)
	{
		return JError::raiseWarning(500, 'method not implemeneted');
	}

	/**
	 * does a folder exist
	 * @param $folder
	 * @return unknown_type
	 */

	function folderExists($path)
	{
		return JError::raiseWarning(500, 'method not implemeneted');
	}

	/**
	 * create a folder
	 * @param $path
	 * @return unknown_type
	 */

	function createFolder($path)
	{
		return JError::raiseWarning(500, 'method not implemeneted');
	}

	function write($file, $buffer)
	{
		return JError::raiseWarning(500, 'method not implemeneted');
	}

	function read( $path)
	{
		return JError::raiseWarning(500, 'method not implemeneted');
	}

	function clean($path)
	{
		return JError::raiseWarning(500, 'method not implemeneted');
	}

	function cleanName($filename, $repeatGroupCounter)
	{
		return JError::raiseWarning(500, 'method not implemeneted');
	}

	function delete($filepath)
	{
		return JError::raiseWarning(500, 'method not implemeneted');
	}

	function upload($tmpFile, $filepath)
	{
		return JError::raiseWarning(500, 'method not implemeneted');
	}

	function setPermissions($filepath)
	{
		return JError::raiseWarning(500, 'method not implemeneted');
	}

	function urlToPath($url)
	{
		return $url;
	}

	/**
	 * @abstract
	 * do a final transform on the path name
	 * @param $path
	 */
	function finalFilePathParse(&$path)
	{

	}

	/**
	 * convert a full server path into a full url
	 */
	function pathToURL($path)
	{
		//return str_replace(COM_FABRIK_LIVESITE, COM_FABRIK_BASE, $url);
		$path = COM_FABRIK_LIVESITE . str_replace(COM_FABRIK_BASE, '', $path);
		$path = str_replace('\\', '/', $path);
		return $path;
	}

	/**
	 * @access public
	 * @param string path to folder - eg /images/stories
	 */

	function makeRecursiveFolders( $folderPath, $mode = 0755)
	{
		if (!JFolder::exists($folderPath)) {
			if (!JFolder::create($folderPath, $mode)) {
				return JError::raiseError(21, "Could not make dir $folderPath ");
			}
		}
	}

	function getFullPath($filepath) {
		return JError::raiseWarning(500, 'method not implemeneted');
	}

	/**
	 * Allows storage model to modify pathname just before it is rendered.  For instance,
	 * if using Amazon S3 with 'Authenticated URL' option.
	 *
	 * @access public
	 * @param string path to file
	 * @return string
	 */

	function preRenderPath($filepath) {
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
}
