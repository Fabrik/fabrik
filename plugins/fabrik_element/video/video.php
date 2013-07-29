<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.video
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

require_once COM_FABRIK_FRONTEND . '/helpers/image.php';

jimport('joomla.application.component.model');

/**
 * Plugin element to render video
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.video
 * @since       3.0
 */

class PlgFabrik_ElementVideo extends PlgFabrik_Element
{

	/** @var array allowed file extensions*/
	var $_aDefaultFileTypes = array('.mov', '.qtif', '.mp4');

	protected $_is_upload = true;

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string  $data      elements data
	 * @param   object  &$thisRow  all the data in the lists current row
	 *
	 * @return  string	formatted value
	 */

	public function renderListData($data, &$thisRow)
	{
		$str = '';
		$data = FabrikWorker::JSONtoData($data, true);
		foreach ($data as $d)
		{
			$str .= $this->_renderListData($d, $thisRow);
		}
		return $str;
	}

	/**
	 * shows the data formatted for the table view
	 * @param   string	data
	 * @param   object	all the data in the tables current row
	 * @return  string	formatted value
	 */

	function _renderListData($data, $thisRow)
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$document = JFactory::getDocument();
		$params = $this->getParams();
		$str = $data;
		if ($params->get('fbVideoShowVideoInTable') == true)
		{
			if ($data != '')
			{
				$data = COM_FABRIK_LIVESITE . str_replace("\\", "/", $data);
				$url = COM_FABRIK_LIVESITE . "index.php?option=com_" . $package . "&tmpl=component&view=plugin&task=pluginAjax&plugin=" . $this->_name
					. "&method=renderPopup&element_id=" . $this->_id . "&data=$data";
				//@TODO replace with Fabrik.Window()
				//FabrikHelperHTML::modal('a.popupwin');
				$w = $params->get('fbVideoWidth', 300) + 20;
				$h = $params->get('fbVideoHeight', '300') + 50;
				$src = COM_FABRIK_LIVESITE . 'plugins/fabrik_element/' . $this->_name . '/icon.gif';
				$data = '<a rel="{\'moveable\':true,useOverlay:false,handler: \'iframe\', size: {x: ' . $w . ', y: ' . $h . '}}" href="' . $url
					. '" class="popupwin"><img src="' . $src . '"alt="' . JText::_('View') . '" /></a>';
			}
		}
		return $data;
	}

	function renderPopup()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$document = JFactory::getDocument();
		$format = $input->get('format', '');

		// When loaded via ajax adding scripts into the doc head wont load them
		echo "<script type='text/javascript'>";
		require COM_FABRIK_FRONTEND . '/media/com_fabrik/js/element.js';
		echo "</script>";
		echo "<script type='text/javascript'>";
		require JPATH_ROOT . '/plugins/fabrik_element/video/video.js';
		echo "</script>";

		$params = $this->getParams();
		$value = $input->get('data', '', 'string');
		$loop = ($params->get('fbVideoLoop', 0) == 1) ? 'true' : 'false';
		$autoplay = ($params->get('fbVideoAutoPlay', 0) == 1) ? 'true' : 'false';
		$controller = ($params->get('fbVideoController', 0) == 1) ? 'true' : 'false';
		$enablejs = ($params->get('fbVideoEnableJS', 0) == 1) ? 'true' : 'false';
		$playallframes = ($params->get('fbVideoPlayEveryFrame', 0) == 1) ? 'true' : 'false';
		$f = str_replace("\\", "/", $element->default);
		$str = "head.ready(function() {\n";
		$str .= "var el = new fabrikvideo('video', " . "{'file':'$value'
		, 'width':" . $params->get('fbVideoWidth', 300) . ", 'height':" . $params->get('fbVideoHeight', '300')
			. "
		, 'enablejs':true
		, 'controller':" . $controller . "
		, 'autoplay':" . $autoplay . "
		, 'loop':" . $loop . "
		, 'livesite':'" . COM_FABRIK_LIVESITE . "'
		, 'ENABLEJAVASCRIPT':" . $enablejs . "
		, 'PLAYEVERYFRAME':" . $playallframes . "

		}" . ");\n";
		$str .= "el.insertMovie();\n";
		$str .= "})";
		echo "<script type='text/javascript'>$str</script>";
?>
<div id="video_placeholder">vide</div>
		<?php
	}

	/**
	 * Determines if the element can contain data used in sending receipts,
	 * e.g. fabrikfield returns true
	 *
	 * @deprecated - not used
	 *
	 * @return  bool
	 */

	public function isReceiptElement()
	{
		return false;
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to preopulate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$element = $this->getElement();
		$value = $element->default;
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();

		$maxlength = $params->get('maxlength');
		if ((int) $maxlength === 0)
		{
			$maxlength = $element->width;
		}

		$type = ($params->get('password') == "1") ? "password" : "text";

		if (isset($this->_elementError) && $this->_elementError != '')
		{
			$type .= " elementErrorHighlight";
		}
		if ($element->hidden == '1')
		{
			$type = "hidden";
		}
		$sizeInfo = " size=\"$element->width\" maxlength=\"$maxlength\"";
		if (!$this->isEditable())
		{
			$format = $params->get('text_format_string');
			if ($format != '')
			{
				$value = eval(sprintf($format, $value));
			}
			if ($element->hidden == '1')
			{
				return "<!--" . $value . "-->";
			}
			else
			{
				return "<div id='" . $this->getHTMLId($repeatCounter) . "_placeholder'>$value</div>";
			}
		}

		$str = '<input class="fabrikinput" name="' . $name . '" type="file" id="' . $id . '" />' . "\n";
		$str .= "<div id='" . $id . "_placeholder'>$value</div>";
		return $str;
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */

	public function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$element = $this->getElement();
		$params = $this->getParams();
		$f = str_replace("\\", "/", $element->default);
		$value = ($element->default != '') ? COM_FABRIK_LIVESITE . $f : '';

		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->file = $value;
		$opts->width = $params->get('fbVideoWidth', 300);
		$opts->height = $params->get('fbVideoHeight', 300);
		$opts->enablejs = true;
		$opts->controller = ($params->get('fbVideoController', 0) == 1) ? true : false;
		$opts->autoplay = ($params->get('fbVideoAutoPlay', 0) == 1) ? true : false;
		$opts->loop = ($params->get('fbVideoLoop', 0) == 1) ? true : false;
		$opts->livesite = COM_FABRIK_LIVESITE;
		$opts->ENABLEJAVASCRIPT = ($params->get('fbVideoEnableJS', 0) == 1) ? true : false;
		$opts->PLAYEVERYFRAME = ($params->get('fbVideoPlayEveryFrame', 0) == 1) ? true : false;
		return array('FbVideo', $id, $opts);
	}

	/**
	 * Processes uploaded data
	 *
	 * @return  void
	 */

	public function processUpload()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$filter = JFilterInput::getInstance();
		$aData = $filter->clean($_POST, 'array');
		$elName = $this->getFullName(true, false);
		if (strstr($elName, 'join'))
		{
			$elTempName = str_replace('join', '', $elName);
			$elTempName = str_replace('[', '', $elTempName);
			$joinArray = explode(']', $elTempName);
			$elName = $joinArray[1];
			$aFile = $_FILES['join'];
			$aFile = $input->files->get('join', array(), 'array');
			$myFileName = $aFile[$joinArray[0]][$joinArray[1]]['name'];
			$myTempFileName = $aFile[$joinArray[0]][$joinArray[1]]['tmp_name'];
			$aData['join'][$joinArray[0]][$joinArray[1]] = '';
		}
		else
		{
			$aFile = $input->files->get($elName, array(), 'array');
			$myFileName = $aFile['name'];
			$myTempFileName = $aFile['tmp_name'];
		}
		$_POST[$elName] = '';
		$oUploader = new FabrikUploader($this);
		$files = array();
		if (is_array($myFileName))
		{
			for ($i = 0; $i < count($myFileName); $i++)
			{
				$fileName = $myFileName[$i];
				$tmpFile = $myTempFileName[$i];
				if (is_array($joinArray))
				{
					$myFileDir = $_POST['join'][$joinArray[0]][$joinArray[1]][$i + 1]['ul_end_dir'];
					$file = $this->_processIndUpload($oUploader, $fileName, $tmpFile, $i, $myFileDir, $aFile);
					$aData['join'][$joinArray[0]][$joinArray[1]][$i][$this->name] = $file;
				}
				else
				{
					$myFileDir = $aData[$elName]['ul_end_dir'];
					$files[] = $this->_processIndUpload($oUploader, $fileName, $tmpFile, $i, $myFileDir, $aFile);
				}
			}
		}
		else
		{
			$tmpFile = $myTempFileName;
			$myFileDir = $aData[$elName]['ul_end_dir'];
			$files[] = $this->_processIndUpload($oUploader, $myFileName, $tmpFile, '', $myFileDir, $aFile);
		}
		$group = $this->getGroup()->getGroup();
		if (!$group->is_join)
		{
			$aData[$elName] = implode("|", $files);
		}
		else
		{
			$aData['join'][$group->join_id][$elName] = implode("|", $files);
		}
		return $aData;
	}

	/**
	 *
	 */

	function _processIndUpload(&$oUploader, $myFileName, $tmpFile, $arrayInc, $myFileDir = '', $file)
	{
		$params = $this->getParams();
		if ($params->get('ul_file_types') == '')
		{
			$params->set('ul_file_types', implode(',', $this->_aDefaultFileTypes));
		}
		$folder = $params->get('ul_directory');
		if ($myFileDir != '')
		{
			$folder .= JPath::clean(JPATH_SITE . '/' . $myFileDir);
		}
		$oUploader->_makeRecursiveFolders($folder);

		$folder = JPath::clean(JPATH_SITE . '/' . $folder);

		$err = null;

		// Set FTP credentials, if given
		jimport('joomla.client.helper');
		JClientHelper::setCredentialsFromRequest('ftp');

		if ($myFileName != '')
		{
			$filepath = JPath::clean($folder . '/' . JString::strtolower($myFileName));

			if (!uploader::canUpload($file, $err, $params))
			{
				return JError::raiseNotice(100, JText::_($err));
			}
			if (JFile::exists($filepath))
			{
				if ($params->get('ul_file_increment', 0))
				{
					$filepath = uploader::incrementFileName($filepath, $filepath, 1);
				}
				else
				{
					return JError::raiseNotice(100, JText::_('A file of that name already exists'));
				}
			}
			if (!JFile::upload($tmpFile, $filepath))
			{
				$oUploader->moveError = true;
				JError::raiseWarning(100, JText::_("Error. Unable to upload file (from $tmpFile to $destFile)"));
			}
			else
			{
				jimport('joomla.filesystem.path');
				JPath::setPermissions($destFile);

				// Resize main image
				$oImage = FabimageHelper::loadLib($params->get('image_library'));
				$mainWidth = $params->get('fu_main_max_width');
				$mainHeight = $params->get('fu_main_max_height');
				if ($params->get('make_thumbnail') == '1')
				{
					$thumbPath = JPath::clean($params->get('thumb_dir') . '/' . $myFileDir . '/');
					$thumbPrefix = $params->get('thumb_prefix');
					$maxWidth = $params->get('thumb_max_width');
					$maxHeight = $params->get('thumb_max_height');
					if ($thumbPath != '')
					{
						$oUploader->_makeRecursiveFolders($thumbPath);
					}
					$destThumbFile = JPath::clean((JPATH_SITE) . '/' . $thumbPath . '/' . $thumbPrefix . basename($filepath));
					$msg = $oImage->resize($maxWidth, $maxHeight, $filepath, $destThumbFile);
				}

				if ($mainWidth != '' || $mainHeight != '')
				{
					$msg = $oImage->resize($mainWidth, $mainHeight, $filepath, $filepath);
				}

				$res = str_replace(JPATH_SITE, '', $filepath);
				return $res;
			}
		}

	}
}
