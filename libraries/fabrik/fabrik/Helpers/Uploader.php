<?php
/**
 * Fabrik upload helper
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Helpers;

// No direct access
defined('_JEXEC') or die('Restricted access');

use JFactory;
use JFile;
use JFolder;
use Joomla\Registry\Registry;
use RuntimeException;

/**
 * Fabrik upload helper
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class Uploader extends \JObject
{
	/**
	 * Form model
	 *
	 * @var  object
	 */
	protected $form = null;

	/**
	 * Move uploaded file error
	 *
	 * @var  bool
	 */
	public $moveError = false;

	/**
	 * Upload
	 *
	 * @param   object  $formModel  form model
	 */

	public function __construct($formModel)
	{
		$this->form = $formModel;
	}

	/**
	 * Perform upload of files
	 *
	 * @return  bool true if error occurred
	 */

	public function upload()
	{
		$groups = $this->form->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				if ($elementModel->isUpload())
				{
					$elementModel->processUpload();
				}
			}
		}
	}

	/**
	 * Moves a file from one location to another
	 *
	 * @param   string  $pathFrom   File to move
	 * @param   string  $pathTo     Location to move file to
	 * @param   bool    $overwrite  Should we overwrite existing files
	 *
	 * @deprecated (don't think its used)
	 *
	 * @return  bool  do we overwrite any existing files found at pathTo?
	 */

	public function move($pathFrom, $pathTo, $overwrite = true)
	{
		if (file_exists($pathTo))
		{
			if ($overwrite)
			{
				unlink($pathTo);
				$ok = rename($pathFrom, $pathTo);
			}
			else
			{
				$ok = false;
			}
		}
		else
		{
			$ok = rename($pathFrom, $pathTo);
		}

		return $ok;
	}

	/**
	 * Make a recursive folder structure
	 *
	 * @param   string  $folderPath  Path to folder - e.g. /images/stories
	 * @param   hex     $mode        Folder permissions
	 *
	 * @return  void
	 */

	public function _makeRecursiveFolders($folderPath, $mode = 0755)
	{
		if (!JFolder::exists($folderPath))
		{
			if (!JFolder::create($folderPath, $mode))
			{
				throw new RuntimeException("Could not make dir $folderPath ");
			}
		}
	}

	/**
	 * Iterates through $_FILE data to see if any files have been uploaded
	 *
	 * @deprecated (don't see it being used)
	 *
	 * @return  bool  true if files uploaded
	 */

	public function check()
	{
		if (isset($_FILES) and !empty($_FILES))
		{
			foreach ($_FILES as $f)
			{
				if ($f['name'] != '')
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Checks if the file can be uploaded
	 *
	 * @param   array     $file     File information
	 * @param   string    &$err     An error message to be returned
	 * @param   Registry  &$params  Params
	 *
	 * @return  bool
	 */
	public static function canUpload($file, &$err, &$params)
	{
		if (empty($file['name']))
		{
			$err = 'Please input a file for upload';

			return false;
		}

		if (!is_uploaded_file($file['tmp_name']))
		{
			// Handle potential malicious attack
			$err = Text::_('File has not been uploaded');

			return false;
		}

		jimport('joomla.filesystem.file');
		$format = StringHelper::strtolower(JFile::getExt($file['name']));
		$allowable = explode(',', StringHelper::strtolower($params->get('ul_file_types')));
		$format = StringHelper::ltrimword($format, '.');
		$format2 = ".$format";

		if (!in_array($format, $allowable) && !in_array($format2, $allowable))
		{
			$err = 'WARNFILETYPE';

			return false;
		}

		$maxSize = (int) $params->get('upload_maxsize', 0);

		if ($maxSize > 0 && (int) $file['size'] > $maxSize)
		{
			$err = 'WARNFILETOOLARGE';

			return false;
		}

		$ignored = array();
		$user = JFactory::getUser();
		$imginfo = null;

		if ($params->get('restrict_uploads', 1))
		{
			$images = explode(',', $params->get('image_extensions'));

			if (in_array($format, $images))
			{
				// If its an image run it through getimagesize
				if (($imginfo = getimagesize($file['tmp_name'])) === false)
				{
					$err = 'WARNINVALIDIMG';

					return false;
				}
			}
			elseif (!in_array($format, $ignored))
			{
				// If its not an image...and we're not ignoring it
			}
		}

		$xss_check = file_get_contents($file['tmp_name'], false, null, 0, 256);
		$html_tags = array('abbr', 'acronym', 'address', 'applet', 'area', 'audioscope', 'base', 'basefont', 'bdo', 'bgsound', 'big', 'blackface',
			'blink', 'blockquote', 'body', 'bq', 'br', 'button', 'caption', 'center', 'cite', 'code', 'col', 'colgroup', 'comment', 'custom', 'dd',
			'del', 'dfn', 'dir', 'div', 'dl', 'dt', 'em', 'embed', 'fieldset', 'fn', 'font', 'form', 'frame', 'frameset', 'h1', 'h2', 'h3', 'h4',
			'h5', 'h6', 'head', 'hr', 'html', 'iframe', 'ilayer', 'img', 'input', 'ins', 'isindex', 'keygen', 'kbd', 'label', 'layer', 'legend',
			'li', 'limittext', 'link', 'listing', 'map', 'marquee', 'menu', 'meta', 'multicol', 'nobr', 'noembed', 'noframes', 'noscript',
			'nosmartquotes', 'object', 'ol', 'optgroup', 'option', 'param', 'plaintext', 'pre', 'rt', 'ruby', 's', 'samp', 'script', 'select',
			'server', 'shadow', 'sidebar', 'small', 'spacer', 'span', 'strike', 'strong', 'style', 'sub', 'sup', 'table', 'tbody', 'td', 'textarea',
			'tfoot', 'th', 'thead', 'title', 'tr', 'tt', 'ul', 'var', 'wbr', 'xml', 'xmp', '!DOCTYPE', '!--');

		foreach ($html_tags as $tag)
		{
			// A tag is '<tagname ', so we need to add < and a space or '<tagname>'
			if (StringHelper::stristr($xss_check, '<' . $tag . ' ') || StringHelper::stristr($xss_check, '<' . $tag . '>'))
			{
				$err = 'WARNIEXSS';

				return false;
			}
		}

		return true;
	}

	/**
	 * Recursive file name incrementation until no file with existing name
	 * exists
	 *
	 * @param   string  $origFileName  Initial file name
	 * @param   string  $newFileName   This recursions file name
	 * @param   int     $version       File version
	 * @params  object  $storage       Storage adapter
	 *
	 * @return  string  New file name
	 */

	public static function incrementFileName($origFileName, $newFileName, $version, $storage)
	{
		if ($storage->exists($newFileName))
		{
			$bits = explode('.', $newFileName);
			$ext = array_pop($bits);
			$f = implode('.', $bits);
			$f = StringHelper::rtrim($f, $version - 1);
			$newFileName = $f . $version . "." . $ext;
			$version++;
			$newFileName = self::incrementFileName($origFileName, $newFileName, $version, $storage);
		}

		return $newFileName;
	}
}
