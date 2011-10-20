<?php

/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class uploader extends JObject
{

	protected $_form = null;
	public $moveError = false;

	function uploader(&$oForm)
	{
		$this->_form = $oForm;
	}

	/**
	 * perform upload of files
	 * @return bol true if error occured
	 */

	function upload()
	{
		$groups = $this->_form->getGroupsHiarachy();
		foreach ($groups as $groupModel) {
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel) {
				if ($elementModel->isUpload()) {
					$elementModel->processUpload();
				}
			}
		}
	}

	/**
	 * moves  a file from one location to another
	 * @param string file to move
	 * @param string location to move file to
	 * @param bol do we overwrite any existing files found at pathTo?
	 */

	function move($pathFrom , $pathTo, $overwrite = true)
	{
		if (file_exists($pathTo)) {
			if ($overwrite) {
				unlink($pathTo);
				$ok = rename($pathFrom, $pathTo);
			} else {
				$ok = false;
			}
		} else {
			$ok = rename($pathFrom, $pathTo);
		}
		return $ok;
	}

	/**
	 * @access private
	 * @param string path to folder - eg /images
	 */

	function _makeRecursiveFolders($folderPath, $mode = 0755)
	{
		if (!JFolder::exists($folderPath)) {
			if (!JFolder::create($folderPath, $mode)) {
				return JError::raiseError(21, "Could not make dir $folderPath ");
			}
		}
	}

	/**
	 * iterates through $_FILE data to see if any files have been uploaded
	 * @return bol true if files uploaded
	 */

	function check()
	{
		if (isset($_FILES) and !empty($_FILES)) {
			foreach ($_FILES as $f) {
				if ($f['name'] != '') {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Checks if the file can be uploaded
	 *
	 * @param array File information
	 * @param string An error message to be returned
	 * @return boolean
	 */

	function canUpload($file, &$err, &$params)
	{
		if (empty($file['name'])) {
			$err = 'Please input a file for upload';
			return false;
		}

		if (!is_uploaded_file($file['tmp_name'])) {
	    //handle potential malicous attack
	    $err =  JText::_('File has not been uploaded');
			return false;;
		}

		jimport('joomla.filesystem.file');
		$format = strtolower(JFile::getExt($file['name']));

		$allowable = explode(',', strtolower($params->get('ul_file_types')));

		$format = FabrikString::ltrimword($format, '.');
		$format2 = ".$format";
		if (!in_array($format, $allowable) && !in_array($format2, $allowable))
		{
			$err = 'WARNFILETYPE';
			return false;
		}

		$maxSize = (int)$params->get('upload_maxsize', 0);
		if ($maxSize > 0 && (int)$file['size'] > $maxSize)
		{
			$err = 'WARNFILETOOLARGE';
			return false;
		}
		$ignored = array();
		$user = JFactory::getUser();
		$imginfo = null;
		if ($params->get('restrict_uploads',1)) {
			$images = explode(',', $params->get('image_extensions'));
			if (in_array($format, $images)) { // if its an image run it through getimagesize
				if (($imginfo = getimagesize($file['tmp_name'])) === FALSE) {
					$err = 'WARNINVALIDIMG';
					return false;
				}
			} else if (!in_array($format, $ignored)) {
				// if its not an image...and we're not ignoring it
				/*$allowed_mime = explode(',', $upload_mime);
				$illegal_mime = explode(',', $upload_mime_illegal);
				if (function_exists('finfo_open') && $params->get('check_mime',1)) {
					// We have fileinfo
					$finfo = finfo_open(FILEINFO_MIME);
					$type = finfo_file($finfo, $file['tmp_name']);
					if (strlen($type) && !in_array($type, $allowed_mime) && in_array($type, $illegal_mime)) {
						$err = 'WARNINVALIDMIME';
						return false;
					}
					finfo_close($finfo);
				} else if (function_exists('mime_content_type') && $params->get('check_mime',1)) {
					// we have mime magic
					$type = mime_content_type($file['tmp_name']);
					if (strlen($type) && !in_array($type, $allowed_mime) && in_array($type, $illegal_mime)) {
						$err = 'WARNINVALIDMIME';
						return false;
					}
				}*/
			}
		}

		$xss_check = JFile::read($file['tmp_name'], false, 256);
		$html_tags = array('abbr','acronym','address','applet','area','audioscope','base','basefont','bdo','bgsound','big','blackface','blink','blockquote','body','bq','br','button','caption','center','cite','code','col','colgroup','comment','custom','dd','del','dfn','dir','div','dl','dt','em','embed','fieldset','fn','font','form','frame','frameset','h1','h2','h3','h4','h5','h6','head','hr','html','iframe','ilayer','img','input','ins','isindex','keygen','kbd','label','layer','legend','li','limittext','link','listing','map','marquee','menu','meta','multicol','nobr','noembed','noframes','noscript','nosmartquotes','object','ol','optgroup','option','param','plaintext','pre','rt','ruby','s','samp','script','select','server','shadow','sidebar','small','spacer','span','strike','strong','style','sub','sup','table','tbody','td','textarea','tfoot','th','thead','title','tr','tt','ul','var','wbr','xml','xmp','!DOCTYPE', '!--');
		foreach ($html_tags as $tag) {
			// A tag is '<tagname ', so we need to add < and a space or '<tagname>'
			if (JString::stristr($xss_check, '<'.$tag.' ') || JString::stristr($xss_check, '<'.$tag.'>')) {
				$err = 'WARNIEXSS';
				return false;
			}
		}
		return true;
	}

	/**
	 * recursive file name incrementation untill no file with exsiting name
	 * exists
	 * @param string intial file name
	 * @param string this recursions file name
	 * @param int file version
	 * @return string new file name
	 */

	function incrementFileName($origFileName, $newFileName, $version)
	{
		if (JFile::exists($newFileName)) {
			$bits = explode('.', $newFileName);
			$ext = array_pop($bits);
			$f = implode('.', $bits);
			$f = rtrim($f, $version - 1);
			$newFileName = $f . $version . "." . $ext;
			$version ++;
			$newFileName = uploader::incrementFileName($origFileName, $newFileName, $version);
		}
		return $newFileName;
	}

}
?>