<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_ROOT.DS.'plugins'.DS.'fabrik_element'.DS.'fileupload'.DS.'adaptor.php');

class filesystemstorage extends storageAdaptor{

	/**
	 * does a file exist
	 * @param $filepath
	 * @return unknown_type
	 */
	function exists($filepath)
	{
		return JFile::exists($filepath);
	}

	/**
	 * does a folder exist
	 * @param $folder
	 * @return unknown_type
	 */
	function folderExists($path)
	{
		return JFolder::exists($path);
	}

	/**
	 * create a folder
	 * @param $path
	 * @return unknown_type
	 */
	function createFolder($path)
	{
		return JFolder::create($path);
	}

	function clean($path)
	{
		return JPath::clean($path);
	}

	function cleanName($filename, $repeatCounter)
	{
		// replace any non-alnum chars (except _ and - and .) with _
		$filename_o = preg_replace( '#[^a-zA-Z0-9_\-\.]#', '_', $filename);
		// $$$peamak: add random filename
		$params = $this->getParams();
		if ($params->get('random_filename') == 1) {
			$length = $params->get('length_random_filename');
			$key = "";
			$possible = "0123456789bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRTVWXYZ";
			$i = 0;
			while ($i < $length) {
				$char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
				$key .= $char;
				$i++;
			}
			$file_e = JFile::getExt($filename_o);
			$file_f = preg_replace('/.'.$file_e.'$/', '', $filename_o);
			$filename = $file_f.'_'.$key.'.'.$file_e;
		} else {
			$filename = $filename_o;
		}
		return $filename;
	}

	function delete($filepath)
	{
		JFile::delete($filepath);
	}

	/**
	 * Moves an uploaded file to a destination folder
	 *
	 * @param string $src The name of the php (temporary) uploaded file
	 * @param string $dest The path (including filename) to move the uploaded file to
	 * @return boolean True on success
	 * @since 1.5
	 */

	function upload($tmpFile, $filepath)
	{
		$this->uploadedFilePath = $filepath;
		return JFile::upload($tmpFile, $filepath);
	}

	function setPermissions($filepath)
	{
		return JPath::setPermissions($filepath);
	}

	function write($file, $buffer)
	{
		JFile::write($file, $buffer);
	}

	function read($filepath)
	{
		return JFile::read($filepath);
	}

	function getFileUrl($file)
	{
		$livesite = COM_FABRIK_LIVESITE;
		$livesite = rtrim($livesite, '/\\');
		$file = ltrim($file,'/\\');
		return str_replace("\\", "/", $livesite  . '/' . $file);
	}

	/**
	 * get the thumbnail URL for the file given
	 *
	 * @param string $file url
	 * @return string thumbnail url
	 */

	function _getThumb($file)
	{
		return $this->_getSmallerFile($file, 'thumb');
	}

	/**
	 *
	 * get the path (relative to site root?) to the smaller file
	 * @param string large file path
	 * @param string type (thumb or crop)
	 */

	function _getSmallerFile($file, $type)
	{

		$params = $this->getParams();
		$w = new FabrikWorker();

		//$$$ rob wasnt working when getting thumb path on upload
		$ulDir = JPath::clean($params->get('ul_directory'));
		$ulDir = str_replace("\\", "/", $ulDir);

		//replace things like $my->id may barf on other stuff
		$afile = str_replace(JURI::root(), '', $file);
		$afile = ltrim($afile, "/");
		$ulDir = ltrim($ulDir, "/");
		$ulDir = rtrim($ulDir, "/");
		$ulDirbits = explode('/', $ulDir);
		$filebits = explode('/', $afile);

		$match = array();
		$replace = array();
		for ($i=0; $i < count($filebits); $i++) {
			if (array_key_exists($i, $ulDirbits) && $filebits[$i] != $ulDirbits[$i]) {
				$match[] = $ulDirbits[$i];
				$replace[] = $filebits[$i];
			}
		}

		$ulDir = str_replace($match, $replace, $ulDir);

		//$$$ rob wasnt working when getting thumb path on upload
		$typeDir = $type == 'thumb' ? $params->get('thumb_dir') : $params->get('fileupload_crop_dir');
		$thumbdir = str_replace($match, $replace, $typeDir);
		$ulDir = $w->parseMessageForPlaceHolder($ulDir);
		$thumbdir = $w->parseMessageForPlaceHolder($thumbdir);
		$file = str_replace($ulDir, $thumbdir, $file);
		$file = $w->parseMessageForPlaceHolder($file);
		$f = basename($file);
		$dir = dirname($file);
		// Jaanus added: create also thumb suffix
		$ext = JFile::getExt($f);
		$fclean = str_replace('.'.$ext, '', $f); //remove extension
		if ($type == 'thumb') {
		//	$file = $dir . '/' . $params->get('thumb_prefix') .  $f;
			$file = $dir . '/' . $params->get('thumb_prefix') .  $fclean . $params->get('thumb_suffix') .'.'. $ext; //$f replaced by $fclean, $ext
		// Jaanus: end of changements
		} else {
			$file = $dir . '/' . $f;
		}
		return $file;
	}

	function _getCropped($file)
	{
		return $this->_getSmallerFile($file, 'crop');
	}

	/**
	 * convert a full url into a full server path
	 * @see /plugins/fabrik_element/fileupload/storageAdaptor#urlToPath($url)
	 */

	function urlToPath($url)
	{
		//$replace = substr(COM_FABRIK_BASE, -1) == DS ? COM_FABRIK_BASE : COM_FABRIK_BASE . DS;
		return str_replace(COM_FABRIK_LIVESITE, COM_FABRIK_BASE, $url);
	}

	/**
	 * do a final transform on the path name
	 * @param $path
	 */

	function finalFilePathParse(&$filepath)
	{
		$filepath = str_replace(JPATH_SITE, '', $filepath);
	}

	/**
	 * Get file info using getid3
	 * @param $filepath
	 * return array
	 */
	function getFileInfo($filepath) {
		if ($this->exists($filepath)) {
			// $$$ hugh - turn of E_DEPRECATED to avoid warnings about eregi() in getid3
			// LOL!  E_DEPRECATED only available in 5.3.0+, pitches Notice in anything earlier.  :)
			if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
				$current_level = error_reporting();
	    		error_reporting($current_level & ~E_DEPRECATED);
			}
	    	/*
			require_once(COM_FABRIK_FRONTEND.DS.'libs'.DS.'getid3'.DS.'getid3'.DS.'getid3.php');
			require_once(COM_FABRIK_FRONTEND.DS.'libs'.DS.'getid3'.DS.'getid3'.DS.'getid3.lib.php');

			getid3_lib::IncludeDependency(COM_FABRIK_FRONTEND.DS.'libs'.DS.'getid3'.DS.'getid3'.DS.'extension.cache.mysql.php', __FILE__, true);
			$config =& JFactory::getConfig();
			$host =  $config->getValue('host');
			$database = $config->getValue('db');
			$username = $config->getValue('user');
			$password = $config->getValue('password');
			$getID3 = new getID3_cached_mysql($host, $database, $username, $password);
			$getID3->encoding = 'UTF-8';
			// Analyze file and store returned data in $ThisFileInfo
			$thisFileInfo = $getID3->analyze($filepath);
			*/
			require_once(COM_FABRIK_FRONTEND.DS.'libs'.DS.'phpmimetypeclass'.DS.'class.mime.php');
			$mime = new MIMETypes();
			$thisFileInfo['filesize'] = filesize($filepath);
			$thisFileInfo['filename'] = basename($filepath);
			$thisFileInfo['mime_type'] = $mime->getMimeType($filepath);
			return $thisFileInfo;
		}
		else {
			return false;
		}
	}

	function getFullPath($filepath) {
		if (!(preg_match('#^' . COM_FABRIK_BASE . '#', $filepath))) {
			return COM_FABRIK_BASE.DS.$filepath;
		}
		return $filepath;
	}
}
?>