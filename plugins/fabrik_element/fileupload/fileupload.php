<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once COM_FABRIK_FRONTEND . '/helpers/image.php';

define("FU_DOWNLOAD_SCRIPT_NONE", '0');
define("FU_DOWNLOAD_SCRIPT_TABLE", '1');
define("FU_DOWNLOAD_SCRIPT_DETAIL", '2');
define("FU_DOWNLOAD_SCRIPT_BOTH", '3');

/**
 * Plug-in to render fileupload element
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 */

class plgFabrik_ElementFileupload extends plgFabrik_Element
{

	/**@var object storage method adaptor object (filesystem/amazon s3) */
	/* needs to be public as models have to see it */
	public $storage = null;

	protected $_is_upload = true;

	/**
	 * does the element store its data in a join table (1:n)
	 * @return bool
	 */

	public function isJoin()
	{
		$params = $this->getParams();
		if ($params->get('ajax_upload') && (int) $params->get('ajax_max', 4) > 1)
		{
			return true;
		}
		else
		{
			return parent::isJoin();
		}
	}

	/**
	 * decide whether to ingore data when updating a record
	 *
	 * @param	string	$val
	 * @return	bool	true if you shouldnt update the data
	 */

	function ignoreOnUpdate($val)
	{
		//check if its a CSV import if it is allow the val to be inserted
		if (JRequest::getCmd('task') === 'makeTableFromCSV' || $this->getListModel()->importingCSV)
		{
			return false;
		}
		$fullName = $this->getFullName(true, true, false);
		$params = $this->getParams();
		$groupModel = $this->_group;
		$return = false;
		if ($groupModel->canRepeat())
		{
			//$$$rob could be the case that we aren't uploading an element by have removed
			//a repeat group (no join) with a file upload element, in this case processUpload has the correct
			//file path settings.
			return false;
		}
		else
		{
			if ($groupModel->isJoin())
			{
				$name = $this->getFullName(false, true, false);
				$joinid = $groupModel->getGroup()->join_id;
				$fileJoinData = JArrayHelper::getValue($_FILES['join']['name'], $joinid, array());
				$fdata = JArrayHelper::getValue($fileJoinData, $name);
				//$fdata = $_FILES['join']['name'][$joinid][$name];
			}
			else
			{
				$fdata = @$_FILES[$fullName]['name'];
			}
			if ($fdata == '')
			{
				if ($params->get('fileupload_crop') == false)
				{
					return true;
				}
				else
				{
					//if we can crop we need to store the cropped coordinated in the field data
					// @see onStoreRow();
					// above depreciated - not sure what to return here for the moment
					return false;
				}
			}
			else
			{
				return false;
			}
		}
		return $return;
	}

	/**
	 * @param array of scripts previously loaded (load order is important as we are loading via head.js
	 * and in ie these load async. So if you this class extends another you need to insert its location in $srcs above the
	 * current file
	 *
	 * get the class to manage the form element
	 * if a plugin class requires to load another elements class (eg user for dbjoin then it should
	 * call FabrikModelElement::formJavascriptClass('plugins/fabrik_element/databasejoin/databasejoin.js', true);
	 * to ensure that the file is loaded only once
	 */

	function formJavascriptClass(&$srcs, $script = '')
	{
		// $$$ hugh - adding js.new folder to make it easier to test new plupload git releases
		// I just copy new stuff into js.new, and un-comment one of these as appropriate
		$js_dir = 'js';
		//$js_dir = 'js.new';

		$params = $this->getParams();
		if ($params->get('ajax_upload'))
		{
			$prefix = JDEBUG ? '' : '.min';
			$runtimes = $params->get('ajax_runtime', 'html5');
			parent::formJavascriptClass($srcs, 'plugins/fabrik_element/fileupload/lib/plupload/' . $js_dir . '/plupload' . $prefix . '.js');

			if (strstr($runtimes, 'html5'))
			{
				parent::formJavascriptClass($srcs, 'plugins/fabrik_element/fileupload/lib/plupload/' . $js_dir . '/plupload.html5' . $prefix . '.js');
			}
			if (strstr($runtimes, 'html4'))
			{
				parent::formJavascriptClass($srcs, 'plugins/fabrik_element/fileupload/lib/plupload/' . $js_dir . '/plupload.html4' . $prefix . '.js');
			}
			if (strstr($runtimes, 'gears'))
			{
				parent::formJavascriptClass($srcs, 'plugins/fabrik_element/fileupload/lib/plupload/' . $js_dir . '/gears_init.js');
				parent::formJavascriptClass($srcs, 'plugins/fabrik_element/fileupload/lib/plupload/' . $js_dir . '/plupload.gears' . $prefix . '.js');
			}

			if (strstr($runtimes, 'flash'))
			{
				parent::formJavascriptClass($srcs, 'plugins/fabrik_element/fileupload/lib/plupload/' . $js_dir . '/plupload.flash' . $prefix . '.js');
			}
			if (strstr($runtimes, 'silverlight'))
			{
				parent::formJavascriptClass($srcs,
					'plugins/fabrik_element/fileupload/lib/plupload/' . $js_dir . '/plupload.silverlight' . $prefix . '.js');
			}
			if (strstr($runtimes, 'browserplus'))
			{
				parent::formJavascriptClass($srcs,
					'plugins/fabrik_element/fileupload/lib/plupload/' . $js_dir . '/plupload.browserplus' . $prefix . '.js');
			}
		}
		parent::formJavascriptClass($srcs, $script);
		static $elementclasses;

		if (!isset($elementclasses))
		{
			$elementclasses = array();
		}
		//load up the default scipt
		if ($script == '')
		{
			$script = 'plugins/fabrik_element/' . $this->getElement()->plugin . '/' . $this->getElement()->plugin . '.js';
		}
		if (empty($elementclasses[$script]))
		{
			$srcs[] = $script;
			$elementclasses[$script] = 1;
		}
		// $$$ hugh - added this, and some logic in the view, so we will get called on a per-element basis
		return false;
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  string
	 */

	public function elementJavascript($repeatCounter)
	{
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		FabrikHelperHTML::mcl();

		$element = $this->getElement();
		$paramsKey = $this->getFullName(false, true, false);
		$paramsKey = Fabrikstring::rtrimword($paramsKey, $this->getElement()->name);
		$paramsKey .= 'params';
		$formData = $this->getForm()->_data;
		$imgParams = JArrayHelper::getValue($formData, $paramsKey);

		$value = $this->getValue(array(), $repeatCounter);

		$value = is_array($value) ? $value : FabrikWorker::JSONtoData($value, true);
		$value = $this->checkForSingleCropValue($value);

		//repeat_image_repeat_image___params
		$rawvalues = count($value) == 0 ? array() : array_fill(0, count($value), 0);
		$fdata = $this->getForm()->_data;
		$rawkey = $this->getFullName(false, true, false) . '_raw';
		$rawvalues = JArrayHelper::getValue($fdata, $rawkey, $rawvalues);
		if (!is_array($rawvalues))
		{
			$rawvalues = explode(GROUPSPLITTER, $rawvalues);
		}
		if (!is_array($imgParams))
		{
			$imgParams = explode(GROUPSPLITTER, $imgParams);
		}
		$oFiles = new stdClass;
		$iCounter = 0;
		for ($x = 0; $x < count($value); $x++)
		{
			if (is_array($value))
			{
				if (array_key_exists($x, $value) && $value[$x] !== '')
				{
					if (is_array($value[$x]))
					{
						//from failed validation
						foreach ($value[$x]['id'] as $tkey => $parts)
						{
							$o = new stdClass;
							$o->id = 'alreadyuploaded_' . $element->id . '_' . $iCounter;//$rawvalues[$x];
							$o->name = array_pop(explode(DS, $tkey));
							$o->path = $tkey;
							if ($fileinfo = $this->getStorage()->getFileInfo($o->path))
							{
								$o->size = $fileinfo['filesize'];
							}
							else
							{
								$o->size = 'unknown';
							}
							$o->url = $this->getStorage()->pathToURL($tkey);
							$o->recordid = $rawvalues[$x];
							$o->params = json_decode($value[$x]['crop'][$tkey]);
							$oFiles->$iCounter = $o;
							$iCounter++;
						}
					}
					else
					{
						if (is_object($value[$x]))
						{
							//single crop image (not sure about the 0 settings in here)
							$parts = explode(DS, $value[$x]->file);
							$o = new stdClass;
							$o->id = 'alreadyuploaded_' . $element->id . '_0';
							$o->name = array_pop($parts);
							$o->path = $value[$x]->file;
							if ($fileinfo = $this->getStorage()->getFileInfo($o->path))
							{
								$o->size = $fileinfo['filesize'];
							}
							else
							{
								$o->size = 'unknown';
							}
							$o->url = $this->getStorage()->pathToURL($value[$x]->file);
							$o->recordid = 0;
							$o->params = json_decode($value[$x]->params);
							$oFiles->$iCounter = $o;
							$iCounter++;
						}
						else
						{
							$parts = explode(DS, $value[$x]);
							$o = new stdClass;
							$o->id = 'alreadyuploaded_' . $element->id . '_' . $rawvalues[$x];
							$o->name = array_pop($parts);
							$o->path = $value[$x];
							if ($fileinfo = $this->getStorage()->getFileInfo($o->path))
							{
								$o->size = $fileinfo['filesize'];
							}
							else
							{
								$o->size = 'unknown';
							}
							$o->url = $this->getStorage()->pathToURL($value[$x]);
							$o->recordid = $rawvalues[$x];
							$o->params = json_decode(JArrayHelper::getValue($imgParams, $x, '{}'));
							$oFiles->$iCounter = $o;
							$iCounter++;
						}
					}
				}
			}

		}

		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->id = $this->_id;
		if ($this->isJoin())
		{
			$opts->isJoin = true;
			$opts->joinId = $this->getJoinModel()->getJoin()->id;
		}
		$opts->elid = $element->id;
		$opts->defaultImage = $params->get('default_image');
		$opts->folderSelect = $params->get('upload_allow_folderselect', 0);
		$opts->dir = JPATH_SITE . '/' . $params->get('ul_directory');
		$opts->ajax_upload = (bool) $params->get('ajax_upload', false);
		$opts->ajax_runtime = $params->get('ajax_runtime', 'html5');
		$opts->max_file_size = $params->get('ul_max_file_size');
		$opts->ajax_chunk_size = (int) $params->get('ajax_chunk_size', 0);
		$opts->crop = (int) $params->get('fileupload_crop', 0);
		$opts->elementName = $this->getFullName(true, true, true);
		$opts->cropwidth = (int) $params->get('fileupload_crop_width');
		$opts->cropheight = (int) $params->get('fileupload_crop_height');
		$opts->ajax_max = (int) $params->get('ajax_max', 4);
		$opts->dragdrop = true;
		$opts->previewButton = FabrikHelperHTML::image('image.png', 'form', @$this->tmpl, array('alt' => JText::_('PLG_ELEMENT_FILEUPLOAD_VIEW')));
		$opts->resizeButton = FabrikHelperHTML::image('resize.png', 'form', @$this->tmpl, array('alt' => JText::_('PLG_ELEMENT_FILEUPLOAD_RESIZE')));
		$opts->files = $oFiles;
		$opts = json_encode($opts);
		JText::script('PLG_ELEMENT_FILEUPLOAD_MAX_UPLOAD_REACHED');
		JText::script('PLG_ELEMENT_FILEUPLOAD_DRAG_FILES_HERE');
		JText::script('PLG_ELEMENT_FILEUPLOAD_UPLOAD_ALL_FILES');
		JText::script('PLG_ELEMENT_FILEUPLOAD_RESIZE');
		JText::script('PLG_ELEMENT_FILEUPLOAD_CROP_AND_SCALE');
		JText::script('PLG_ELEMENT_FILEUPLOAD_PREVIEW');
		return "new FbFileUpload('$id', $opts)";
	}

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string  $data      data to show
	 * @param	object  &$thisRow  all the data in the tables current row
	 *
	 * @return	string	formatted value
	 */

	public function renderListData($data, &$thisRow)
	{
		$data = FabrikWorker::JSONtoData($data, true);
		$params = $this->getParams();

		// $$$ hugh - have to run thru rendering even if data is empty, iin case default image is being used.
		if (empty($data))
		{
			$data[0] = $this->_renderListData('', $thisRow, 0);
		}
		else
		{
			for ($i = 0; $i < count($data); $i++)
			{
				$data[$i] = $this->_renderListData($data[$i], $thisRow, $i);
			}
		}
		$data = json_encode($data);
		return parent::renderListData($data, $thisRow);
	}

	/**
	 * Shows the data formatted for the CSV export view
	 * @param	string	data
	 * @param	string	element name
	 * @param	object	all the data in the tables current row
	 * @return	string	formatted value
	 */

	public function renderListData_csv($data, &$thisRow)
	{
		$data = explode(GROUPSPLITTER, $data);
		$params = $this->getParams();
		$format = $params->get('ul_export_encode_csv', 'base64');
		foreach ($data as &$d)
		{
			$d = $this->encodeFile($d, $format);
		}
		return implode(GROUPSPLITTER, $data);
	}

	/**
	 * shows the data formatted for the JSON export view
	 * @param	string	data
	 * @param	string	element name
	 * @param	object	all the data in the tables current row
	 * @return	string	formatted value
	 */

	function renderListData_json($data, $rows)
	{
		$data = explode(GROUPSPLITTER, $data);
		$params = $this->getParams();
		$format = $params->get('ul_export_encode_json', 'base64');
		foreach ($data as &$d)
		{
			$d = $this->encodeFile($d, $format);
		}
		return implode(GROUPSPLITTER, $data);
	}

	/**
	 * encodes the file
	 * @param	string	$file relative file path
	 * @param	mixed	format to encode the file full|url|base64|raw|relative
	 * @return	string	encoded file for export
	 */

	protected function encodeFile($file, $format = 'relative')
	{
		$path = JPATH_SITE . '/' . $file;
		if (!JFile::exists($path))
		{
			return $file;
		}
		switch ($format)
		{
			case 'full':
				return $path;
				break;
			case 'url':
				return COM_FABRIK_LIVESITE . str_replace('\\', '/', $file);
				break;
			case 'base64':
				return base64_encode(JFile::read($path));
				break;
			case 'raw':
				return JFile::read($path);
				break;
			case 'relative':
				return $file;
				break;
		}
	}

	/**
	 * examine the file being displayed and load in the corresponding
	 * class that deals with its display
	 * @param	string	file
	 */

	function loadElement($file)
	{
		$ext = JString::strtolower(JFile::getExt($file));
		if (JFile::exists(JPATH_ROOT . '/plugins/fabrik_element/fileupload/element/custom/' . $ext . '.php'))
		{
			require(JPATH_ROOT . '/plugins/fabrik_element/fileupload/element/custom/' . $ext . '.php');
		}
		elseif (JFile::exists(JPATH_ROOT . '/plugins/fabrik_element/fileupload/element/' . $ext . '.php'))
		{
			require(JPATH_ROOT . '/plugins/fabrik_element/fileupload/element/' . $ext . '.php');
		}
		else
		{
			//default down to allvideos content plugin
			if (in_array($ext, array('flv', '3gp', 'divx')))
			{
				require(JPATH_ROOT . '/plugins/fabrik_element/fileupload/element/allvideos.php');
			}
			else
			{
				require(JPATH_ROOT . '/plugins/fabrik_element/fileupload/element/default.php');
			}
		}
		return $render;
	}

	/**
	 * Display the file in the table
	 *
	 * @param   string  $data      current cell data
	 * @param   array   &$thisRow  current row data
	 * @param   int    $i          repeat group count
	 *
	 * @return	string
	 */

	function _renderListData($data, $thisRow, $i = 0)
	{
		$this->_repeatGroupCounter = $i;
		$element = $this->getElement();
		$params = $this->getParams();

		// $$$ hugh - added 'skip_check' param, as the exists() check in s3
		// storage adaptor can add a second or two per file, per row to table render time.
		$skip_exists_check = (int) $params->get('fileupload_skip_check', '0');
		if ($params->get('ajax_upload') && $params->get('ajax_max', 4) == 1)
		{
			// Not sure but after update from 2.1 to 3 for podion data was an object
			if (is_object($data))
			{
				$data = $data->file;
			}
			else
			{
				if ($data !== '')
				{
					$singleCropImg = json_decode($data);
					if (empty($singleCropImg))
					{
						$data = '';
					}
					else
					{
						$singleCropImg = $singleCropImg[0];
						$data = $singleCropImg->file;
					}
				}
			}
		}

		$data = FabrikWorker::JSONtoData($data);
		if (is_array($data) && !empty($data))
		{
			// Crop stuff needs to be removed from data to get correct file path
			$data = $data[0];
		}
		$storage = $this->getStorage();
		$use_download_script = $params->get('fu_use_download_script', '0');
		if ($use_download_script == FU_DOWNLOAD_SCRIPT_TABLE || $use_download_script == FU_DOWNLOAD_SCRIPT_BOTH)
		{
			if (empty($data) || !$storage->exists(COM_FABRIK_BASE . $data))
			{
				return '';
			}
			$aclEl = $this->getFormModel()->getElement($params->get('fu_download_acl', ''), true);
			if (!empty($aclEl))
			{
				$aclEl = $aclEl->getFullName();
				$aclElraw = $aclEl . '_raw';
				$user = JFactory::getUser();
				$groups = $user->getAuthorisedViewLevels();
				$canDownload = in_array($thisRow->$aclElraw, $groups);
				if (!$canDownload)
				{
					$img = $params->get('fu_download_noaccess_image');
					$noImg = ($img == '' || ! JFile::exists(COM_FABRIK_LIVESITE . 'media/com_fabrik/images/' . $img));
					$aClass = $noImg ? 'class="btn button"' : '';
					$a = $params->get('fu_download_noaccess_url') == '' ? '' : '<a href="' . $params->get('fu_download_noaccess_url') . '" ' . $aClass . '>';
					$a2 = $params->get('fu_download_noaccess_url') == '' ? '' : '</a>';

					if ($noImg)
					{
						$img = JText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD_NO_PERMISSION');
					}
					else
					{
						$img = '<img src="' . COM_FABRIK_LIVESITE . 'media/com_fabrik/images/' . $img . '" alt="'
							. JText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD_NO_PERMISSION') . '" />';
					}
					return $a . $img . $a2;
				}
			}
			$formModel = $this->getForm();
			$formid = $formModel->getId();
			$rowid = $thisRow->__pk_val;
			$elementid = $this->_id;
			if ($params->get('fu_title_element') == '')
			{
				$title_name = $this->getFullName(true, true, false) . '__title';
			}
			else
			{
				$title_name = str_replace('.', '___', $params->get('fu_title_element'));
			}
			//$title_name .= '_raw'; //Jaanus: WHY _raw? Do we really want to see the numbers instead of normal words? See also http://fabrikar.com/forums/showthread.php?p=123267 about f2
			if (array_key_exists($title_name, $thisRow))
			{
				if (!empty($thisRow->$title_name))
				{
					$title = $thisRow->$title_name;
					$title = FabrikWorker::JSONtoData($title, true);
					$title = $title[$i];
				}
			}
			$downloadImg = $params->get('fu_download_access_image');
			if ($downloadImg !== '' && JFile::exists('media/com_fabrik/images/' . $downloadImg))
			{
				$aClass = '';
				$title = '<img src="' . COM_FABRIK_LIVESITE . 'media/com_fabrik/images/' . $downloadImg . '" alt="'
					. $title . '" />';
			}
			else
			{
				$aClass = 'class="btn btn-primary button"';
				$title = '<i class="icon-download icon-white"></i>' . JText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD');
			}
			$link = COM_FABRIK_LIVESITE
				. 'index.php?option=com_fabrik&amp;task=plugin.pluginAjax&amp;plugin=fileupload&amp;method=ajax_download&amp;element_id='
				. $elementid . '&amp;formid=' . $formid . '&amp;rowid=' . $rowid . '&amp;repeatcount=' . $i;
			$url = '<a href="' . $link . '"' . $aClass . '>' . $title . '</a>';
			return $url;
		}

		if ($params->get('fu_show_image_in_table') == '0')
		{
			$render = $this->loadElement('default');
		}
		else
		{
			$render = $this->loadElement($data);
		}

		if (empty($data) || (!$skip_exists_check && !$storage->exists(COM_FABRIK_BASE . '/' . $data)))
		{
			$render->output = '';
		}
		else
		{
			$render->renderListData($this, $params, $data, $thisRow);
		}
		if ($render->output == '' && $params->get('default_image') != '')
		{
			$defaultURL = $storage->getFileUrl(str_replace(COM_FABRIK_BASE, '', $params->get('default_image')));
			$render->output = '<img src="' . $defaultURL . '" alt="image" />';
		}
		return $render->output;
	}

	/**
	 * do we need to include the lighbox js code
	 *
	 * @return	bool
	 */

	function requiresLightBox()
	{
		$params = $this->getParams();
		//wont load it if in admin module with this condition. Testing returning true as some thing else is not right with it either.
		/*if (JRequest::getCmd('view') == 'list' && $params->get('fu_show_image_in_table')  == '0') {
		 return false;
		}*/
		return true;
	}

	/**
	 * Manupulates posted form data for insertion into database
	 *
	 * @param   mixed  $val   this elements posted form data
	 * @param   array  $data  posted form data
	 *
	 * @return  mixed
	 */

	public function storeDatabaseFormat($val, $data)
	{
		//val already contains group splitter from processUpload() code
		return $val;
	}

	/**
	 * checks the posted form data against elements INTERNAL validataion rule - e.g. file upload size / type
	 * @param	string	elements data
	 * @param	int		repeat group counter
	 * @return	bool	true if passes / false if falise validation
	 */

	function validate($data = array(), $repeatCounter = 0)
	{
		$params = $this->getParams();
		$groupModel = $this->getGroupModel();
		$group = $groupModel->getGroup();
		$this->_validationErr = '';
		$errors = array();
		$elName = $this->getFullName();
		$elName = str_replace('[]', '', $elName); //remove any repeat group labels
		if ($groupModel->isJoin())
		{
			// $$$ hugh - kinda screws things up if 'join' is used in the element name!
			/*
			 $elTempName = str_replace('join', '', $elName);
			$elTempName = str_replace('[', '', $elTempName);
			$joinArray = explode(']', $elTempName);
			 */
			$joinArray = array();
			if (!preg_match('#join\[(\d+)\]\[(\S+)\]#', $elName, $joinArray))
			{
				return true;
			}
			if (!array_key_exists('join', $_FILES))
			{
				return true;
			}
			$aFile = $_FILES['join'];
			$myFileName = $aFile['name'][$joinArray[1]][$joinArray[2]];
			$myFileSize = $aFile['size'][$joinArray[1]][$joinArray[2]];
			if (is_array($myFileSize))
			{
				$myFileSize = $myFileSize[$repeatCounter];
			}
			if (is_array($myFileName))
			{
				$myFileName = $myFileName[$repeatCounter];
			}
		}
		else
		{

			if (JRequest::getVar('method') === 'ajax_upload')
			{
				$aFile = $_FILES['file'];
			}
			else
			{
				if (!array_key_exists($elName, $_FILES))
				{
					return true;
				}
				$aFile = $_FILES[$elName];
			}

			if ($groupModel->canRepeat())
			{
				$myFileName = $aFile['name'][$repeatCounter];
				$myFileSize = $aFile['size'][$repeatCounter];
			}
			else
			{
				$myFileName = $aFile['name'];
				$myFileSize = $aFile['size'];
			}
		}
		$ok = true;

		if (!$this->_fileUploadFileTypeOK($myFileName))
		{
			$errors[] = JText::_('PLG_ELEMENT_FILEUPLOAD_FILE_TYPE_NOT_ALLOWED');
			$ok = false;
		}
		if (!$this->_fileUploadSizeOK($myFileSize))
		{
			$ok = false;
			$mySize = $myFileSize / 1000;
			$errors[] = JText::sprintf('PLG_ELEMENT_FILEUPLOAD_FILE_TOO_LARGE', $params->get('ul_max_file_size'), $mySize);
		}
		$filepath = $this->_getFilePath($repeatCounter);
		jimport('joomla.filesystem.file');
		if (JFile::exists($filepath))
		{
			if ($params->get('ul_file_increment', 0) == 0)
			{
				$errors[] = JText::_('PLG_ELEMENT_FILEUPLOAD_EXISTING_FILE_NAME');
				$ok = false;
			}
		}
		$this->_validationErr = implode('<br />', $errors);
		return $ok;
	}

	function _getAllowedExtension()
	{
		$params = $this->getParams();
		$allowedFiles = $params->get('ul_file_types');
		if ($allowedFiles != '')
		{
			// $$$ hugh - strip spaces, as folk often do ".foo, .bar"
			preg_replace('#\s+#', '', $allowedFiles);
			$aFileTypes = explode(",", $allowedFiles);
		}
		else
		{
			$mediaparams = JComponentHelper::getParams('com_media');
			$aFileTypes = explode(',', $mediaparams->get('upload_extensions'));
		}
		return $aFileTypes;
	}

	/**
	 * This checks the uploaded file type against the csv specified in the upload
	 * element
	 * @access PRIVATE
	 * @param	string	filename
	 * @return	bool	true if upload file type ok
	 */

	function _fileUploadFileTypeOK($myFileName)
	{
		$aFileTypes = $this->_getAllowedExtension();
		if ($myFileName == '')
		{
			return true;
		}
		$curr_f_ext = JString::strtolower(JFile::getExt($myFileName));
		array_walk($aFileTypes, create_function('&$v', '$v = JString::strtolower($v);'));
		if (in_array($curr_f_ext, $aFileTypes) || in_array("." . $curr_f_ext, $aFileTypes))
		{
			return true;
		}
		return false;
	}

	/**
	 * This checks that thte fileupload size is not greater than that specified in
	 * the upload element
	 * @access PRIVATE
	 * @param	string	file size
	 * @return	bool	true if upload file type ok
	 */

	function _fileUploadSizeOK($myFileSize)
	{
		$params = $this->getParams();
		$max_size = $params->get('ul_max_file_size') * 1000;
		if ($myFileSize <= $max_size)
		{
			return true;
		}
		return false;
	}

	/**
	 * if we are using plupload but not with crop
	 * @param	string	element $name
	 * @return	bool	if processed or not
	 */

	function processAjaxUploads($name)
	{
		$params = $this->getParams();
		if ($params->get('fileupload_crop') == false && JRequest::getCmd('task') !== 'pluginAjax' && $params->get('ajax_upload') == true)
		{
			$post = JRequest::get('post');
			$raw = $this->getValue($post);
			if ($raw == '')
			{
				return true;
			}
			if (empty($raw))
			{
				return true;
			}
			// $$$ hugh - for some reason, we're now getting $raw[] with a single, uninitialized entry back
			// from getvalue() when no files are uploaded
			if (count($raw) == 1 && empty($raw[0]))
			{
				return true;
			}
			// $$$ hugh - no longer seems to be in $raw[0] ?
			/*
			$crop = (array)JArrayHelper::getValue($raw[0], 'crop');
			$ids = (array)JArrayHelper::getValue($raw[0], 'id');
			 */
			$crop = (array) JArrayHelper::getValue($raw, 'crop');
			$ids = (array) JArrayHelper::getValue($raw, 'id');
			$ids = array_values($ids);

			$saveParams = array();
			$files = array_keys($crop);
			$groupModel = $this->getGroup();
			$isjoin = ($groupModel->isJoin() || $this->isJoin());
			if ($isjoin)
			{
				if (!$groupModel->canRepeat() && !$this->isJoin())
				{
					$files = $files[0];
				}
				$joinid = $groupModel->getGroup()->join_id;
				if ($this->isJoin())
				{
					$joinid = $this->getJoinModel()->getJoin()->id;
				}

				$j = $this->getJoinModel()->getJoin()->table_join;
				$joinsid = $j . '___id';
				$joinsparam = $j . '___params';

				$name = $this->getFullName(false, true, false);

				$this->_form->updateFormData("join.{$joinid}.{$name}", $files);
				$this->_form->updateFormData("join.{$joinid}.{$name}_raw", $files);

				$this->_form->updateFormData("join.{$joinid}.{$joinsid}", $ids);
				$this->_form->updateFormData("join.{$joinid}.{$joinsid}_raw", $ids);

				$this->_form->updateFormData("join.{$joinid}.{$joinsparam}", $saveParams);
				$this->_form->updateFormData("join.{$joinid}.{$joinsparam}_raw", $saveParams);
			}
			else
			{
				$strfiles = json_encode($files);
				$this->_form->updateFormData($name . '_raw', $strfiles);
				$this->_form->updateFormData($name, $strfiles);
			}
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 *
	 * If an image has been uploaded with ajax upload then we may need to crop it
	 * @param	string	element $name
	 * @return	bool	if processed or not
	 */

	function crop($name)
	{
		$params = $this->getParams();
		if ($params->get('fileupload_crop') == true && JRequest::getCmd('task') !== 'pluginAjax')
		{
			$post = JRequest::get('post');
			$raw = JArrayHelper::getValue($post, $name . '_raw', array());
			if ($this->getValue($post) != 'Array,Array')
			{
				$raw = $this->getValue($post);

				// $$$ rob 26/07/2012 inline edit producing a string value for $raw on save
				if ($raw == '' || empty($raw) || is_string($raw))
				{
					return true;
				}
				if (array_key_exists(0, $raw))
				{
					$crop = (array) JArrayHelper::getValue($raw[0], 'crop');
					$ids = (array) JArrayHelper::getValue($raw[0], 'id');
				}
				else
				{
					// Single uploaded image.
					$crop = (array) JArrayHelper::getValue($raw, 'crop');
					$ids = (array) JArrayHelper::getValue($raw, 'id');
				}
			}
			else
			{
				// Single image
				$crop = (array) JArrayHelper::getValue($raw, 'crop');
				$ids = (array) JArrayHelper::getValue($raw, 'id');
			}
			if ($raw == '')
			{
				return true;
			}

			$ids = array_values($ids);
			$saveParams = array();
			$files = array_keys($crop);
			$storage = $this->getStorage();
			$oImage = FabimageHelper::loadLib($params->get('image_library'));
			$oImage->setStorage($storage);
			$fileCounter = 0;
			foreach ($crop as $filepath => $json)
			{
				$coords = json_decode(urldecode($json));
				$saveParams[] = $json;

				// @todo allow uploading into front end designated folders?
				$myFileDir = '';
				$cropPath = $storage->clean(JPATH_SITE . '/' . $params->get('fileupload_crop_dir') . '/' . $myFileDir . '/', false);
				$w = new FabrikWorker;
				$cropPath = $w->parseMessageForPlaceHolder($cropPath);
				$cropWidth = $params->get('fileupload_crop_width', 125);
				$cropHeight = $params->get('fileupload_crop_height', 125);
				if ($cropPath != '')
				{
					if (!$storage->folderExists($cropPath))
					{
						if (!$storage->createFolder($cropPath))
						{
							$this->setError(21, "Could not make dir $cropPath ");
							continue;
						}
					}
				}
				$filepath = $storage->clean(JPATH_SITE . '/' . $filepath);
				$fileURL = $storage->getFileUrl(str_replace(COM_FABRIK_BASE, '', $filepath));
				$destCropFile = $storage->_getCropped($fileURL);
				$destCropFile = $storage->urlToPath($destCropFile);
				$destCropFile = $storage->clean($destCropFile);
				$srcX = $coords->cropdim->x;
				$srcY = $coords->cropdim->y;
				$imagedim = $coords->imagedim;
				if (!JFile::exists($filepath))
				{
					unset($files[$fileCounter]);
					$fileCounter++;
					continue;
				}
				$fileCounter++;
				$this->cropForSmaller($oImage, $filepath, $destCropFile, $coords);
				$storage->setPermissions($destCropFile);
			}
			$groupModel = $this->getGroup();
			$isjoin = ($groupModel->isJoin() || $this->isJoin());

			if ($isjoin)
			{
				if (!$groupModel->canRepeat() && !$this->isJoin())
				{
					$files = $files[0];
				}
				$joinid = $groupModel->getGroup()->join_id;
				if ($this->isJoin())
				{
					$joinid = $this->getJoinModel()->getJoin()->id;
				}
				$j = $this->getJoinModel()->getJoin()->table_join;
				$joinsid = $j . '___id';
				$joinsparam = $j . '___params';

				$name = $this->getFullName(false, true, false);

				$this->_form->updateFormData("join.{$joinid}.{$name}", $files);
				$this->_form->updateFormData("join.{$joinid}.{$name}_raw", $files);

				$this->_form->updateFormData("join.{$joinid}.{$joinsid}", $ids);
				$this->_form->updateFormData("join.{$joinid}.{$joinsid}_raw", $ids);

				$this->_form->updateFormData("join.{$joinid}.{$joinsparam}", $saveParams);
				$this->_form->updateFormData("join.{$joinid}.{$joinsparam}_raw", $saveParams);
			}
			else
			{
				// Only one file
				$store = array();
				for ($i = 0; $i < count($files); $i++)
				{
					$o = new stdClass;
					$o->file = $files[$i];
					$o->params = $saveParams[$i];
					$store[] = $o;
				}
				$store = json_encode($store);
				$this->_form->updateFormData($name . '_raw', $store);
				$this->_form->updateFormData($name, $store);

			}
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $oImage
	 * @param unknown_type $filepath
	 * @param unknown_type $destCropFile
	 * @param unknown_type $coords
	 */

	private function cropForSmaller($oImage, $filepath, $destCropFile, $coords)
	{
		$params = $this->getParams();
		$bg = $params->get('fileupload_crop_bg', '#FFFFFF');
		$log = array();
		$log['coords'] = $coords;
		$cropWidth = $coords->cropdim->w;
		$cropHeight = $coords->cropdim->h;
		$scale = (int) $coords->scale;
		// Get the orignal file
		list($origImage, $header) = $oImage->imageFromFile($filepath);

		// Get original File dims
		list($origWidth, $origHeight) = getimagesize($filepath);
		if ($scale !== 100)
		{
			//make a scaled verios of the original image
			$destWidth = (int) $origWidth * ($scale / 100);
			$destHeight = (int) $origHeight * ($scale / 100);

			$scaledImage = imagecreatetruecolor($destWidth, $destHeight);
			//copy the man image into the scaled image
			imagecopyresampled($scaledImage, $origImage, 0, 0, 0, 0, $destWidth, $destHeight, $origWidth, $origHeight);
			$origImage = $scaledImage;
		}
		$imagedim = $coords->imagedim;

		// Has the image itself been dragged?
		$deltaX = 400 / 2 - $imagedim->x;
		$deltaY = 400 / 2 - $imagedim->y;

		// Make an image the size of the crop interface
		$canvas = imagecreatetruecolor(400, 400);

		// X position to start placing the original image on the canvas
		$destX = (int) (400 - ($origWidth * ($scale / 100))) / 2;
		$destX = $destX - $deltaX;

		// Y position to start placing the original image on the canvas
		$destY = (int) (400 - ($origHeight * ($scale / 100))) / 2;
		$destY = $destY - $deltaY;

		// X point on source image to copy from
		$srcX = 0;

		// Y point on source image to copy from
		$srcY = 0;
		$srcW = (int) $origWidth * ($scale / 100);
		$srcH = (int) $origHeight * ($scale / 100);
		$destWidth = (int) $imagedim->w;
		$setHeight = (int) $imagedim->h;

		imagecopyresampled($canvas, $origImage, $destX, $destY, $srcX, $srcY, $destWidth, $setHeight, $srcW, $srcH);

		$oImage->imageToFile($destCropFile, $canvas);

		if ($coords->rotation != 0)
		{
			// Works great here for images with scale < 100

			// Rotate image
			list($rotatedImgObject, $rotateWidth, $rotateHeight) = $oImage->rotate($destCropFile, $destCropFile, $coords->rotation * -1);
			//scale it back to crop dims
			$xx = $rotateWidth / 2 - 400 / 2;
			$yy = $rotateHeight / 2 - 400 / 2;
			$oImage->crop($destCropFile, $destCropFile, $xx, $yy, 400, 400);
		}

		// Crop it from the crop coordinates
		$srcX = ($coords->cropdim->x - ($coords->cropdim->w / 2));
		$srcY = $coords->cropdim->y - ($coords->cropdim->h / 2);
		$oImage->crop($destCropFile, $destCropFile, $srcX, $srcY, $cropWidth, $cropHeight, 0, 0, $bg);
		FabrikWorker::log('fabrik.fileupload.crop', $log);
	}

	/**
	 * Enter description here ...
	 * @param unknown_type $oImage
	 * @param unknown_type $filepath
	 * @param unknown_type $destCropFile
	 * @param unknown_type $coords
	 */

	private function cropForLarger($oImage, $filepath, $destCropFile, $coords)
	{
		$params = $this->getParams();
		$bg = $params->get('fileupload_crop_bg', '#FFFFFF');
		$log = array();
		$log['coords'] = $coords;

		$imagedim = $coords->imagedim;
		$srcX = $coords->cropdim->x;
		$srcY = $coords->cropdim->y;
		$cropWidth = $coords->cropdim->w;
		$cropHeight = $coords->cropdim->h;
		$scale = (int) $coords->scale;
		//deprecaited (again lol)
		//from here replaces commented code below
		list($width, $height) = getimagesize($filepath);
		$log['rotate'] = array('path' => $filepath, 'dest' => $destCropFile, 'rotation' => $coords->rotation * -1);
		list($rotatedImgObject, $rotateWidth, $rotateHeight) = $oImage->rotate($filepath, $destCropFile, $coords->rotation * -1);

		$xx = $rotateWidth / 2 - $width / 2;
		$yy = $rotateHeight / 2 - $height / 2;
		//need to crop image down to initial crop interface dimensions as rotate changes image dimensions
		//$oImage->crop($destCropFile, $destCropFile, $xx , $yy , 400, 400);
		//check if image  size is smaller than canvas size first
		$destW = $imagedim->w < 400 ? $imagedim->w : 400;
		$destH = $imagedim->h < 400 ? $imagedim->h : 400;

		//@todo test for smaller image - set offset so that they dont appear at top
		$log['crop1'] = array($destCropFile, $destCropFile, $xx, $yy, $destW, $destH, 0, 0, $bg);
		$oImage->crop($destCropFile, $destCropFile, $xx, $yy, $destW, $destH, 0, 0, $bg);
		$destX = $imagedim->x - ($imagedim->w / 2);
		$destY = $imagedim->y - ($imagedim->h / 2);

		//make an image the size of the crop interface
		$image_p = imagecreatetruecolor($destW, $destH);

		list($image, $header) = $oImage->imageFromFile($destCropFile);
		//figure out what the destination w/h should be (scaling the image based on the submitted scale value)
		$destwidth = $width * ((float) $scale / 100);
		$destheight = $height * ((float) $scale / 100);
		//create a file which resembles the crop interfaces image
		$log['scale'] = array('dest' => $destCropFile, 'destX' => $destX, 'destY' => $destY, 'destWidth' => $destwidth, 'destHeight' => $destheight,
			'sourceWidth' => $width, 'sourceHeight' => $height);
		imagecopyresampled($image_p, $image, $destX, $destY, 0, 0, $destwidth, $destheight, $width, $height);
		$oImage->imageToFile($destCropFile, $image_p);

		//finally take the cropper coordinates and crop the image
		$offsetX = ($imagedim->w < 400) ? (400 - $imagedim->w) / 2 : 0;
		$offsetY = ($imagedim->h < 400) ? (400 - $imagedim->h) / 2 : 0;

		$srcX = ($coords->cropdim->x - ($coords->cropdim->w / 2)) - $offsetX;
		$srcY = $coords->cropdim->y - ($coords->cropdim->h / 2) - $offsetY;

		$log['crop2'] = array('dest' => $destCropFile, 'startx' => $srcX, 'starty' => $srcY, 'crop width' => $cropWidth, 'cropHeight' => $cropHeight,
			'cropx' => 0, 'cropy' => 0, 'bg' => $bg);
		$oImage->crop($destCropFile, $destCropFile, $srcX, $srcY, $cropWidth, $cropHeight, 0, 0, $bg);
		FabrikWorker::log('fabrik.fileupload.crop', $log);
	}

	/**
	 * OPTIONAL
	 */

	function processUpload()
	{
		//@TODO: test in joins
		$params = $this->getParams();
		$request = JRequest::get('request');
		$groupModel = $this->getGroup();
		$isjoin = $groupModel->isJoin();
		$origData = $this->_form->getOrigData();
		if ($isjoin)
		{
			$name = $this->getFullName(false, true, false);
			$joinid = $groupModel->getGroup()->join_id;
		}
		else
		{
			$name = $this->getFullName(true, true, false);
		}
		if ($this->processAjaxUploads($name))
		{
			// Stops form data being updated with blank data.
			return;
		}
		// if we've turnd on crop but not set ajax upload then the cropping wont work so we shouldnt return
		// otherwise no standard image processed
		if ($this->crop($name) && $params->get('ajax_upload'))
		{
			// Stops form data being updated with blank data.
			return;
		}
		$files = array();
		$deletedImages = JRequest::getVar('fabrik_fileupload_deletedfile', array(), 'request', 'array');
		$gid = $groupModel->getGroup()->id;

		$deletedImages = JArrayHelper::getValue($deletedImages, $gid, array());
		$imagesToKeep = array();

		for ($j = 0; $j < count($origData); $j++)
		{
			foreach ($origData[$j] as $key => $val)
			{
				if ($key == $name && !empty($val))
				{
					if (in_array($val, $deletedImages))
					{
						unset($origData[$j]->$key);
					}
					else
					{
						$imagesToKeep[] = $origData[$j]->$key;
					}
				}
			}
		}

		if ($groupModel->canRepeat())
		{
			if ($isjoin)
			{
				$fdata = $_FILES['join']['name'][$joinid][$name];
			}
			else
			{
				$fdata = $_FILES[$name]['name'];
			}
			foreach ($fdata as $i => $f)
			{
				if ($isjoin)
				{
					$myFileDir = (is_array($request['join'][$joinid][$name]) && array_key_exists($i, $request['join'][$joinid][$name]))
						? $request['join'][$joinid][$name][$i] : '';
				}
				else
				{
					$myFileDir = (is_array($request[$name]) && array_key_exists($i, $request[$name])) ? $request[$name][$i] : '';
				}

				$file = array('name' => $isjoin ? $_FILES['join']['name'][$joinid][$name][$i] : $_FILES[$name]['name'][$i],
					'type' => $isjoin ? $_FILES['join']['type'][$joinid][$name][$i] : $_FILES[$name]['type'][$i],
					'tmp_name' => $isjoin ? $_FILES['join']['tmp_name'][$joinid][$name][$i] : $_FILES[$name]['tmp_name'][$i],
					'error' => $isjoin ? $_FILES['join']['error'][$joinid][$name][$i] : $_FILES[$name]['error'][$i],
					'size' => $isjoin ? $_FILES['join']['size'][$joinid][$name][$i] : $_FILES[$name]['size'][$i]);
				if ($file['name'] != '')
				{
					$files[$i] = $this->_processIndUpload($file, $myFileDir, $i);
				}
				else
				{
					$files[$i] = $imagesToKeep[$i];//$origData[$i]->$name;
				}
			}
			foreach ($imagesToKeep as $k => $v)
			{
				if (!array_key_exists($k, $files))
				{
					$files[$k] = $v;
				}
			}
		}
		else
		{
			$file = array('name' => '');
			if ($isjoin)
			{
				$myFileDir = $request['join'][$joinid][$name];
				if (array_key_exists('join', $_FILES) && array_key_exists('name', $_FILES['join'])
					&& array_key_exists($joinid, $_FILES['join']['name']) && array_key_exists($name, $_FILES['join']['name'][$joinid]))
				{
					$file['name'] = $_FILES['join']['name'][$joinid][$name];
					$file['type'] = $_FILES['join']['type'][$joinid][$name];
					$file['tmp_name'] = $_FILES['join']['tmp_name'][$joinid][$name];
					$file['error'] = $_FILES['join']['error'][$joinid][$name];
					$file['size'] = $_FILES['join']['size'][$joinid][$name];
				}
			}
			else
			{
				$myFileDir = JArrayHelper::getValue($request, $name);
				if (array_key_exists($name, $_FILES))
				{
					$file['name'] = $_FILES[$name]['name'];
					$file['type'] = $_FILES[$name]['type'];
					$file['tmp_name'] = $_FILES[$name]['tmp_name'];
					$file['error'] = $_FILES[$name]['name'];
					$file['size'] = $_FILES[$name]['size'];
				}
			}
			/*
			$file = array(
			        'name' 			=> $isjoin ? $_FILES['join']['name'][$joinid][$name] : $_FILES[$name]['name'],
			        'type' 			=> $isjoin ? $_FILES['join']['type'][$joinid][$name] : $_FILES[$name]['type'],
			        'tmp_name' 	=> $isjoin ? $_FILES['join']['tmp_name'][$joinid][$name] : $_FILES[$name]['tmp_name'],
			        'error' 		=> $isjoin ? $_FILES['join']['error'][$joinid][$name] : $_FILES[$name]['error'],
			        'size' 			=> $isjoin ? $_FILES['join']['size'][$joinid][$name] : $_FILES[$name]['size']
			);
			 */

			if ($file['name'] != '')
			{
				$files[] = $this->_processIndUpload($file, $myFileDir);
			}
			else
			{
				// $$$ hugh - fixing nasty bug where existing upload was getting wiped when editing an existing row and not uploading anything.
				// I think this should work.  if we're not in a repeat group, then it doesn't matter how many rows were in origData, and hence
				// how many rows are in $imagesToKeep ... if $imagesToKeep isn't empty, then we can assume a) it occurs at least once, and
				// b) it'll at least be in [0]
				if (!empty($imagesToKeep))
				{
					$files[] = $origData[0]->$name;
				}
			}
		}
		$files = array_flip(array_flip($files));
		// $$$ hugh - if we have multiple repeat joined groups, the data won't have been merged / reduced,
		// so the double array_flip will have made 'holes' in the array, by removign duplicates.
		// So, we need to re-index, otherwise the _formData['join'] data
		// structure will end up havign holes in it in processToDb, and we drop data.
		$files = array_values($files);
		if ($params->get('upload_delete_image'))
		{
			foreach ($deletedImages as $filename)
			{
				$this->deleteFile($filename);
			}
		}
		// $$$ rob dont alter the request array as we should be inserting into the form models
		// ->_formData array using updateFormData();

		if ($isjoin)
		{
			if (!$groupModel->canRepeat())
			{
				$files = JArrayHelper::getValue($files, 0, '');
			}
			$this->_form->updateFormData("join.{$joinid}.{$name}", $files);
			$this->_form->updateFormData("join.{$joinid}.{$name}_raw", $files);
		}
		else
		{
			$strfiles = implode(GROUPSPLITTER, $files);
			$this->_form->updateFormData($name . '_raw', $strfiles);
			$this->_form->updateFormData($name, $strfiles);
		}
	}

	/**
	 * delete the file
	 * @param	$filename	string file name (not including JPATH)
	 */

	function deleteFile($filename)
	{
		$storage = $this->getStorage();
		$file = $storage->clean(JPATH_SITE . '/' . $filename);
		$thumb = $storage->clean($storage->_getThumb($filename));
		$cropped = $storage->clean($storage->_getCropped($filename));
		if ($storage->exists($file))
		{
			$storage->delete($file);
		}
		if ($storage->exists($thumb))
		{
			$storage->delete($thumb);
		}
		else
		{
			if ($storage->exists(JPATH_SITE . '/' . $thumb))
			{
				$storage->delete(JPATH_SITE . '/' . $thumb);
			}
		}
		if ($storage->exists($cropped))
		{
			$storage->delete($cropped);
		}
		else
		{
			if ($storage->exists(JPATH_SITE . '/' . $cropped))
			{
				$storage->delete(JPATH_SITE . '/' . $cropped);
			}
		}
	}

	/**
	 * used in notempty validation rule
	 */

	function dataConsideredEmpty($data, $repeatCounter)
	{
		if ((int) JRequest::getVar('rowid', 0) !== 0)
		{
			if (JRequest::getVar('task') == '')
			{
				return parent::dataConsideredEmpty($data, $repeatCounter);
			}
			$olddaata = JArrayHelper::getValue($this->getFormModel()->_origData, $repeatCounter);
			if (!is_null($olddaata))
			{
				$name = $this->getFullName(false, true, false);
				$r = JArrayHelper::getValue(JArrayHelper::fromObject($olddaata), $name, '') === '' ? true : false;
				if (!$r)
				{
					//if an original value is found then data not empty - if not found continue to check the $_FILES array to see if one
					// has been uploaded
					return false;
				}
			}
		}

		$groupModel = $this->getGroup();
		if ($groupModel->isJoin())
		{
			$name = $this->getFullName(false, true, false);
			$joinid = $groupModel->getGroup()->join_id;
			$joindata = JRequest::getVar('join', '', 'files', 'array', array());
			if (!array_key_exists('name', $joindata))
			{
				return true;
			}
			$file = (array) $joindata['name'][$joinid][$name];
			return JArrayHelper::getValue($file, $repeatCounter, '') == '' ? true : false;
		}
		else
		{
			if ($this->isJoin())
			{
				$join = $this->getJoinModel()->getJoin();
				$joinid = $join->id;
				$joindata = JRequest::getVar('join', '', 'post', 'array', array());
				$joindata = JArrayHelper::getValue($joindata, $joinid, array());
				$name = $this->getFullName(false, true, false);
				$joindata = JArrayHelper::getValue($joindata, $name, array());
				$joinids = JArrayHelper::getValue($joindata, 'id', array());
				return empty($joinids) ? true : false;
			}
			else
			{
				$name = $this->getFullName(true, true, false);
				$file = JRequest::getVar($name, '', 'files', 'array', array());
				if ($groupModel->canRepeat())
				{
					return JArrayHelper::getValue($file['name'], $repeatCounter, '') == '' ? true : false;
				}
			}

		}
		if (!array_key_exists('name', $file))
		{
			$file = JRequest::getVar($name);
			return $file == '' ? true : false;//ajax test - nothing in files
		}
		// no files selected?
		return $file['name'] == '' ? true : false;
	}

	/**
	 * Process the upload (can be called via ajax from pluploader
	 *
	 * @param	array	$file info
	 * @param	string	user selected upload folder
	 * @param	int		repeat group counter
	 * @return	string	location of uploaded file
	 */

	protected function _processIndUpload(&$file, $myFileDir = '', $repeatGroupCounter = 0)
	{
		$params = $this->getParams();
		$storage = $this->getStorage();

		// $$$ hugh - check if we need to blow away the cached filepath, set in validation
		$myFileName = $storage->cleanName($file['name'], $repeatGroupCounter);
		if ($myFileName != $file['name'])
		{
			$file['name'] = $myFileName;
			unset($this->_filePaths[$repeatGroupCounter]);
		}
		$tmpFile = $file['tmp_name'];
		$uploader = $this->getFormModel()->getUploader();
		if ($params->get('ul_file_types') == '')
		{
			$params->set('ul_file_types', implode(',', $this->_getAllowedExtension()));
		}
		$err = null;

		// Set FTP credentials, if given
		jimport('joomla.client.helper');
		JClientHelper::setCredentialsFromRequest('ftp');
		if ($myFileName == '')
		{
			return;
		}
		$filepath = $this->_getFilePath($repeatGroupCounter);
		if (!FabrikUploader::canUpload($file, $err, $params))
		{
			$this->setError(100, $file['name'] . ': ' . JText::_($err));
		}

		if ($storage->exists($filepath))
		{
			switch ($params->get('ul_file_increment', 0))
			{
				case 0:
					break;
				case 1:
					$filepath = FabrikUploader::incrementFileName($filepath, $filepath, 1);
					break;
				case 2:
					$storage->delete($filepath);
					break;
			}
		}
		if (!$storage->upload($tmpFile, $filepath))
		{
			$uploader->moveError = true;
			$this->setError(100, JText::sprintf('PLG_ELEMENT_FILEUPLOAD_UPLOAD_ERR', $tmpFile, $filepath));
			return;
		}
		$filepath = $storage->getUploadedFilePath();
		jimport('joomla.filesystem.path');
		$storage->setPermissions($filepath);

		// $$$ hugh @TODO - shouldn't we check to see if it's actually an image before we do any of this stuff???

		//resize main image
		$oImage = FabimageHelper::loadLib($params->get('image_library'));
		$oImage->setStorage($storage);
		// $$$ hugh - removing default of 200, otherwise we ALWAYS resize, whereas
		// tooltip on these options say 'leave blank for no resizing'
		$mainWidth = $params->get('fu_main_max_width', '');
		$mainHeight = $params->get('fu_main_max_height', '');

		if ($mainWidth != '' || $mainHeight != '')
		{
			// $$$ rob ensure that both values are integers otherwise resize fails
			if ($mainHeight == '')
			{
				$mainHeight = (int) $mainWidth;
			}
			if ($mainWidth == '')
			{
				$mainWidth = (int) $mainHeight;
			}
			$oImage->resize($mainWidth, $mainHeight, $filepath, $filepath);
		}
		// $$$ hugh - if it's a PDF, make sure option is set to attempt PDF thumb
		$make_thumbnail = $params->get('make_thumbnail') == '1' ? true : false;
		if (JFile::getExt($filepath) == 'pdf' && $params->get('fu_make_pdf_thumb', '0') == '0')
		{
			$make_thumbnail = false;
		}
		if ($make_thumbnail)
		{
			$thumbPath = $storage->clean(JPATH_SITE . '/' . $params->get('thumb_dir') . '/' . $myFileDir . '/', false);
			$w = new FabrikWorker;
			$thumbPath = $w->parseMessageForPlaceHolder($thumbPath);
			$thumbPrefix = $params->get('thumb_prefix');
			$maxWidth = $params->get('thumb_max_width', 125);
			$maxHeight = $params->get('thumb_max_height', 125);
			if ($thumbPath != '')
			{
				if (!$storage->folderExists($thumbPath))
				{
					if (!$storage->createFolder($thumbPath))
					{
						JError::raiseError(21, "Could not make dir $thumbPath ");
					}
				}
			}
			$fileURL = $storage->getFileUrl(str_replace(COM_FABRIK_BASE, '', $filepath));
			$destThumbFile = $storage->_getThumb($fileURL);
			$destThumbFile = $storage->urlToPath($destThumbFile);
			$oImage->resize($maxWidth, $maxHeight, $filepath, $destThumbFile);
			$storage->setPermissions($destThumbFile);
		}
		$storage->setPermissions($filepath);
		$storage->finalFilePathParse($filepath);
		return $filepath;
	}

	function getStorage()
	{
		if (!isset($this->storage))
		{
			$params = $this->getParams();
			$storageType = JFilterInput::getInstance()->clean($params->get('fileupload_storage_type', 'filesystemstorage'), 'CMD');
			require_once(JPATH_ROOT . '/plugins/fabrik_element/fileupload/adaptors/' . $storageType . '.php');
			$this->storage = new $storageType($params);
		}
		return $this->storage;
	}

	/**
	 * get the full server file path for the upload, including the file name i
	 * @param	int		repeat group counter
	 * @return	string	path
	 */

	function _getFilePath($repeatCounter = 0)
	{
		if (!isset($this->_filePaths))
		{
			$this->_filePaths = array();
		}
		if (array_key_exists($repeatCounter, $this->_filePaths))
		{
			return $this->_filePaths[$repeatCounter];
		}
		$aData = JRequest::get('post');
		$elName = $this->getFullName(true, true, false);
		$elNameRaw = $elName . '_raw';
		$params = $this->getParams();

		//@TODO test with fileuploads in join groups

		$groupModel = $this->getGroup();
		if ($groupModel->isJoin())
		{
			$joinid = $groupModel->getGroup()->join_id;
			$elNameNoJoinstr = $this->getFullName(false, true, false);
			if ($groupModel->canRepeat())
			{
				$myFileName = array_key_exists('join', $_FILES) ? @$_FILES['join']['name'][$joinid][$elNameNoJoinstr][$repeatCounter]
					: @$_FILES['file']['name'];
				$myFileDir = JArrayHelper::getValue($aData['join'][$joinid][$elNameNoJoinstr], 'ul_end_dir', array());
				$myFileDir = JArrayHelper::getValue($myFileDir, $repeatCounter, '');
			}
			else
			{
				//$myFileName = $_FILES['join']['name'][$joinid][$elNameNoJoinstr];
				//@TODO test this:
				$myFileName = array_key_exists('join', $_FILES) ? @$_FILES['join']['name'][$joinid][$elNameNoJoinstr] : @$_FILES['file']['name'];
				$myFileDir = JArrayHelper::getValue($aData['join'][$joinid][$elNameNoJoinstr], 'ul_end_dir', '');
			}
		}
		else
		{
			if ($groupModel->canRepeat())
			{
				//$myFileName   = @$_FILES[$elName]['name'][$repeatCounter];
				//@TODO test this:
				$myFileName = array_key_exists($elName, $_FILES) ? @$_FILES[$elName]['name'][$repeatCounter] : @$_FILES['file']['name'];
				$myFileDir = array_key_exists($elNameRaw, $aData) && is_array($aData[$elNameRaw]) ? @$aData[$elNameRaw]['ul_end_dir'][$repeatCounter]
					: '';
			}
			else
			{
				$myFileName = array_key_exists($elName, $_FILES) ? @$_FILES[$elName]['name'] : @$_FILES['file']['name'];
				$myFileDir = array_key_exists($elNameRaw, $aData) && is_array($aData[$elNameRaw]) ? @$aData[$elNameRaw]['ul_end_dir'] : '';

			}
		}

		$storage = $this->getStorage();

		// $$$ hugh - check if we need to blow away the cached filepath, set in validation
		$myFileName = $storage->cleanName($myFileName, $repeatCounter);

		$folder = $params->get('ul_directory');
		$folder = $folder . '/' . $myFileDir;
		if ($storage->appendServerPath())
		{
			$folder = JPATH_SITE . '/' . $folder;
		}
		$folder = JPath::clean($folder);
		$w = new FabrikWorker;
		$folder = $w->parseMessageForPlaceHolder($folder);

		if ($storage->appendServerPath())
		{
			JPath::check($folder);
		}
		$storage->makeRecursiveFolders($folder);
		$p = $folder . '/' . $myFileName;
		$this->_filePaths[$repeatCounter] = JPath::clean($p);
		return $this->_filePaths[$repeatCounter];
	}

	/**
	 * draws the form element
	 * @param	array	data
	 * @param	int		repeat group counter
	 * @return	string	returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$this->_repeatGroupCounter = $repeatCounter;
		$id = $this->getHTMLId($repeatCounter);
		$name = $this->getHTMLName($repeatCounter);
		$groupModel = $this->getGroup();
		$element = $this->getElement();
		$params = $this->getParams();
		if ($element->hidden == '1')
		{
			return $this->getHiddenField($name, $data[$name], $id);
		}
		$str = array();
		$value = $this->getValue($data, $repeatCounter);
		$value = is_array($value) ? $value : FabrikWorker::JSONtoData($value, true);
		$value = $this->checkForSingleCropValue($value);
		if ($params->get('ajax_upload'))
		{
			if (isset($value->file))
			{
				$value = $value->file;
			}
		}
		$imagedata = array();

		$ulDir = $params->get('ul_directory');
		$storage = $this->getStorage();

		$formModel = $this->getFormModel();
		$formid = $formModel->getId();

		$use_download_script = $params->get('fu_use_download_script', '0');
		// $$$ rob - explode as it may be grouped data (if element is a repeating upload)
		$values = is_array($value) ? $value : FabrikWorker::JSONtoData($value, true);

		if (!$this->_editable && ($use_download_script == FU_DOWNLOAD_SCRIPT_DETAIL || $use_download_script == FU_DOWNLOAD_SCRIPT_BOTH))
		{
			$links = array();
			foreach ($value as $v)
			{
				$links[] = $this->downloadLink($v, $data, $repeatCounter);
			}
			return implode("\n", $links);
		}

		$render = new stdClass;
		$render->output = '';
		$allRenders = '';
		if (($params->get('fu_show_image') !== '0' && !$params->get('ajax_upload')) || !$this->_editable)
		{
			//failed validations - format different!
			if (array_key_exists('id', $values))
			{
				$values = array_keys($values['id']);
			}
			//end failed validations

			foreach ($values as $value)
			{
				if (is_object($value))
				{
					$value = $value->file;
				}
				$render = $this->loadElement($value);

				if ($value != '' && ($storage->exists(COM_FABRIK_BASE . $value) || JString::substr($value, 0, 4) == 'http'))
				{
					$render->render($this, $params, $value);
				}
				if ($render->output != '')
				{
					$allRenders .= $render->output;
				}
			}
		}
		if (!$this->_editable)
		{
			if ($render->output == '' && $params->get('default_image') != '')
			{
				$render->output = '<img src="' . $params->get('default_image') . '" alt="image" />';
			}
			$str[] = '<div class="fabrikSubElementContainer">';
			$str[] = $allRenders;
			$str[] = '</div>';
			return implode("\n", $str);
		}
		$str[] = $allRenders . '<br/><input class="fabrikinput" name="' . $name . '" type="file" id="' . $id . '" />' . "\n";
		if ($params->get('upload_allow_folderselect') == '1')
		{
			$rDir = JPATH_SITE . '/' . $params->get('ul_directory');
			$folders = JFolder::folders($rDir);
			$str[] = FabrikHelperHTML::folderAjaxSelect($folders);
			if ($groupModel->canRepeat())
			{
				$ulname = FabrikString::rtrimword($name, "[$repeatCounter]") . "[ul_end_dir][$repeatCounter]";
			}
			else
			{
				$ulname = $name . '[ul_end_dir]';
			}
			$str[] = '<input name="' . $ulname . '" type="hidden" class="folderpath"/>';
		}

		if ($params->get('ajax_upload'))
		{
			$str = array();
			$str[] = $allRenders;
			$str = $this->plupload($str, $repeatCounter, $values);
		}

		array_unshift($str, '<div class="fabrikSubElementContainer">');
		$str[] = '</div>';
		return implode("\n", $str);
	}

	protected function checkForSingleCropValue($value)
	{
		$params = $this->getParams();
		//if its a single upload crop element
		if ($params->get('ajax_upload') && $params->get('ajax_max', 4) == 1)
		{
			$singleCropImg = $value;
			if (empty($singleCropImg))
			{
				$value = '';
			}
			else
			{
				$singleCropImg = $singleCropImg[0];
			}
		}
		return $value;
	}

	/**
	 * make download link
	 * @param	string	$value
	 * @param	array	row $data
	 * @param	int		repeat counter $repeatCounter
	 * @return	string	download link
	 */

	protected function downloadLink($value, $data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		$storage = $this->getStorage();
		$formModel = $this->getFormModel();
		if (empty($value) || !$storage->exists(COM_FABRIK_BASE . $value))
		{
			return '';
		}
		$aclEl = $this->getFormModel()->getElement($params->get('fu_download_acl', ''), true);
		if (!empty($aclEl))
		{
			$aclEl = $aclEl->getFullName();
			$canDownload = in_array($data[$aclEl], JFactory::getUser()->authorisedLevels());
			if (!$canDownload)
			{
				$img = $params->get('fu_download_noaccess_image');
				return $img == '' ? ''
					: '<img src="' . COM_FABRIK_LIVESITE . 'media/com_fabrik/images/' . $img . '" alt="' . JText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD_NO_PERMISSION')
						. '" />';
			}
		}

		$formid = $formModel->getId();
		$rowid = JRequest::getVar('rowid', '0');
		$elementid = $this->_id;
		$title = basename($value);
		if ($params->get('fu_title_element') == '')
		{
			$title_name = $this->getFullName(true, true, false) . '__title';
		}
		else
		{
			$title_name = str_replace('.', '___', $params->get('fu_title_element'));
		}
		if (is_array($formModel->_data))
		{
			if (array_key_exists($title_name, $formModel->_data))
			{
				if (!empty($formModel->_data[$title_name]))
				{
					$title = $formModel->_data[$title_name];
					$titles = FabrikWorker::JSONtoData($title, true);
					$title = JArrayHelper::getValue($titles, $repeatCounter, $title);
				}
			}
		}
		if ($params->get('fu_download_access_image') !== '')
		{
			$title = '<img src="' . COM_FABRIK_LIVESITE . 'media/com_fabrik/images/' . $params->get('fu_download_access_image') . '" alt="' . $title
				. '" />';
		}
		$link = COM_FABRIK_LIVESITE . 'index.php?option=com_fabrik&task=plugin.pluginAjax&plugin=fileupload&method=ajax_download&element_id='
			. $elementid . '&formid=' . $formid . '&rowid=' . $rowid . '&repeatcount=' . $repeatCounter;
		$url = '<a href="' . $link . '">' . $title . '</a>';
		return $url;
	}

	/**
	 * @depreciated
	 * load the required plupload runtime engines
	 * @param string $runtimes
	 */

	protected function pluploadLRuntimes($runtimes)
	{
		return;
	}

	/**
	 * Create the html plupload widget plus css
	 * @param	array	current html output
	 * @param	int		repeat group counter
	 * @param	array	existing files
	 * @return	array	modified fileupload html
	 */

	protected function plupload($str, $repeatCounter, $values)
	{
		FabrikHelperHTML::stylesheet(COM_FABRIK_LIVESITE . 'media/com_fabrik/css/slider.css');
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		$runtimes = $params->get('ajax_runtime', 'html5');
		$w = (int) $params->get('ajax_dropbox_width', 300);
		$h = (int) $params->get('ajax_dropbox_hight', 200);
		//add span with id so that element fxs work.

		$pstr = array();
		$pstr[] = '<span id="' . $id . '"></span>';
		$pstr[] = '<div id="' . $id . '-widgetcontainer">';

		$pstr[] = '<canvas id="' . $id . '-widget" width="400" height="400"></canvas>';
		if ($params->get('fileupload_crop', 0))
		{
			$pstr[] = '<div class="zoom" style="float:left;margin-top:10px;padding-right:10x;width:200px">';
			$pstr[] = '	zoom:';
			$pstr[] = '	<div class="fabrikslider-line" style="width: 100px;float:left;">';
			$pstr[] = '		<div class="knob"></div>';
			$pstr[] = '	</div>';
			$pstr[] = '	<input name="zoom-val" value="" size="3" />';
			$pstr[] = '</div>';
			$pstr[] = '<div class="rotate" style="float:left;margin-top:10px;width:200px">' . JText::_('PLG_ELEMENT_FILEUPLOAD_ROTATE') . ':';
			$pstr[] = '	<div class="fabrikslider-line" style="width: 100px;float:left;">';
			$pstr[] = '		<div class="knob"></div>';
			$pstr[] = '	</div>';
			$pstr[] = '	<input name="rotate-val" value="" size="3" />';
			$pstr[] = '</div>';
		}
		$pstr[] = '<div  style="text-align: right;float:right;margin-top:10px; width: 205px">';
		$pstr[] = '<input type="button" class="button" name="close-crop" value="' . JText::_('CLOSE') . '" />';
		$pstr[] = '</div>';
		$pstr[] = '</div>';

		$pstr[] = '<div class="plupload_container fabrikHide" id="' . $id . '_container" style="width:' . $w . 'px;height:' . $h . 'px">';
		$pstr[] = '<div class="plupload">';
		$pstr[] = '	<div class="plupload_header">';
		$pstr[] = '		<div class="plupload_header_content">';
		$pstr[] = '			<div class="plupload_header_title">' . JText::_('PLG_ELEMENT_FILEUPLOAD_PLUP_HEADING') . '</div>';
		$pstr[] = '			<div class="plupload_header_text">' . JText::_('PLG_ELEMENT_FILEUPLOAD_PLUP_SUB_HEADING') . '</div>';
		$pstr[] = '		</div>';
		$pstr[] = '	</div>';
		$pstr[] = '	<div class="plupload_content">';
		$pstr[] = '		<div class="plupload_filelist_header">';
		$pstr[] = '			<div class="plupload_file_name">' . JText::_('PLG_ELEMENT_FILEUPLOAD_FILENAME') . '</div>';
		$pstr[] = '			<div class="plupload_file_action">&nbsp;</div>';
		$pstr[] = '			<div class="plupload_file_status"><span>' . JText::_('PLG_ELEMENT_FILEUPLOAD_STATUS') . '</span></div>';
		$pstr[] = '			<div class="plupload_file_size">' . JText::_('PLG_ELEMENT_FILEUPLOAD_SIZE') . '</div>';
		$pstr[] = '			<div class="plupload_clearer">&nbsp;</div>';
		$pstr[] = '		</div>';
		$pstr[] = '		<ul class="plupload_filelist" id="' . $id . '_dropList">';
		$pstr[] = '		</ul>';
		$pstr[] = '		<div class="plupload_filelist_footer">';
		$pstr[] = '		<div class="plupload_file_name">';
		$pstr[] = '			<div class="plupload_buttons">';
		$pstr[] = '				<a id="' . $id . '_browseButton" class="plupload_button plupload_add" href="#">'
			. JText::_('PLG_ELEMENT_FILEUPLOAD_ADD_FILES') . '</a>';
		$pstr[] = '				<a class="plupload_button plupload_start plupload_disabled" href="#">'
			. JText::_('PLG_ELEMENT_FILEUPLOAD_START_UPLOAD') . '</a>';
		$pstr[] = '			</div>';
		$pstr[] = '			<span class="plupload_upload_status"></span>';
		$pstr[] = '		</div>';
		$pstr[] = '		<div class="plupload_file_action"></div>';
		$pstr[] = '			<div class="plupload_file_status">';
		$pstr[] = '				<span class="plupload_total_status"></span>';
		$pstr[] = '			</div>';
		$pstr[] = '		<div class="plupload_file_size">';
		$pstr[] = '			<span class="plupload_total_file_size"></span>';
		$pstr[] = '		</div>';
		$pstr[] = '		<div class="plupload_progress">';
		$pstr[] = '			<div class="plupload_progress_container">';
		$pstr[] = '			<div class="plupload_progress_bar"></div>';
		$pstr[] = '		</div>';
		$pstr[] = '	</div>';
		$pstr[] = '	<div class="plupload_clearer">&nbsp;</div>';
		$pstr[] = '	</div>';
		$pstr[] = '</div>';
		$pstr[] = '</div>';
		$pstr[] = '</div>';
		$pstr[] = '<!-- FALLBACK; SHOULD LOADING OF PLUPLOAD FAIL -->';
		$pstr[] = '<div class="plupload_fallback">' . JText::_('PLG_ELEMENT_FILEUPLOAD_FALLBACK_MESSAGE');
		$pstr[] = '<br />';

		array_merge($pstr, $str);
		$pstr[] = '</div>';

		FabrikHelperHTML::stylesheet(COM_FABRIK_LIVESITE . 'plugins/fabrik_element/fileupload/lib/plupload/css/plupload.queue.css');
		return $pstr;
	}

	/**
	 * Fabrik 3 - needs to be onAjax_upload not ajax_upload
	 * triggered by plupload widget
	 */

	public function onAjax_upload()
	{
		//got this warning on fabrikar.com - not sure why set testing with errors off
		/*
		 * <b>Warning</b>:  utf8_to_unicode: Illegal sequence identifier in UTF-8 at byte 0 in <b>/home/fabrikar/public_html/downloads/libraries/phputf8/utils/unicode.php</b> on line <b>110</b><br />
		 */
		/* error_reporting(0); */
		// $$$ hugh - reinstated this workaround, as I started getting those utf8 warnings as well.
		error_reporting(E_ERROR | E_PARSE);

		$o = new stdClass;
		$this->_id = JRequest::getInt('element_id');
		$groupModel = $this->getGroup();

		if (!$this->validate())
		{
			$o->error = $this->_validationErr;
			echo json_encode($o);
			return;
		}
		$isjoin = $groupModel->isJoin();
		if ($isjoin)
		{
			$name = $this->getFullName(false, true, false);
			$joinid = $groupModel->getGroup()->join_id;
		}
		else
		{
			$name = $this->getFullName(true, true, false);
		}

		// Get parameters
		$chunk = JRequest::getInt('chunk', 0);
		$chunks = JRequest::getInt('chunks', 0);
		$fileName = JRequest::getVar('name', '');

		if ($chunk + 1 < $chunks)
		{
			return;
		}
		require_once(COM_FABRIK_FRONTEND . '/helpers/uploader.php');
		//@TODO test in join
		if (array_key_exists('file', $_FILES) || array_keys_exists('join', $_FILES))
		{
			$file = array('name' => $isjoin ? $_FILES['join']['name'][$joinid] : $_FILES['file']['name'],
				'type' => $isjoin ? $_FILES['join']['type'][$joinid] : $_FILES['file']['type'],
				'tmp_name' => $isjoin ? $_FILES['join']['tmp_name'][$joinid] : $_FILES['file']['tmp_name'],
				'error' => $isjoin ? $_FILES['join']['error'][$joinid] : $_FILES['file']['error'],
				'size' => $isjoin ? $_FILES['join']['size'][$joinid] : $_FILES['file']['size']);

			$filepath = $this->_processIndUpload($file, '', 0);
			$uri = $this->getStorage()->pathToURL($filepath);
			$o->filepath = $filepath;
			$o->uri = $uri;
		}
		else
		{
			$o->filepath = null;
			$o->uri = null;
		}
		echo json_encode($o);
		return;
	}

	/**
	 * Get database field description
	 *
	 * @return  string  db field type
	 */

	public function getFieldDescription()
	{
		$p = $this->getParams();
		if ($this->encryptMe())
		{
			return 'BLOB';
		}
		return "TEXT";
	}

	/**
	 * attach documents to the email
	 * @param	string	data
	 * @return	string	formatted value
	 */

	function addEmailAttachement($data)
	{
		/// @TODO: check what happens here with open base_dir in effect //
		$params = $this->getParams();
		if ($params->get('ul_email_file'))
		{
			$config = JFactory::getConfig();
			if (empty($data))
			{
				$data = $params->get('default_image');
			}
			if (strstr($data, JPATH_SITE))
			{
				$p = str_replace(COM_FABRIK_LIVESITE, JPATH_SITE, $data);
			}
			else
			{
				$p = JPATH_SITE . '/' . $data;
			}
			return $p;
		}
		return false;
	}

	/**
	 * If a database join element's value field points to the same db field as this element
	 * then this element can, within modifyJoinQuery, update the query.
	 * E.g. if the database join element points to a file upload element then you can replace
	 * the file path that is the standard $val with the html to create the image
	 *
	 * @param   string  $val   value
	 * @param   string  $view  form or list
	 *
	 * @deprecated - doesn't seem to be used
	 *
	 * @return  string	modified val
	 */

	protected function modifyJoinQuery($val, $view = 'form')
	{
		$params = $this->getParams();
		if (!$params->get('fu_show_image', 0) && $view == 'form')
		{
			return $val;
		}
		if ($params->get('make_thumbnail'))
		{
			$ulDir = JPath::clean($params->get('ul_directory')) . '/';
			$ulDir = str_replace("\\", "\\\\", $ulDir);
			$thumbDir = $params->get('thumb_dir');
			$thumbDir = JPath::clean($params->get('thumb_dir')) . '/';
			$w = new FabrikWorker;
			$thumbDir = $w->parseMessageForPlaceHolder($thumbDir);
			$thumbDir = str_replace("\\", "\\\\", $thumbDir);

			$w = new FabrikWorker;
			$thumbDir = $w->parseMessageForPlaceHolder($thumbDir);
			$thumbDir .= $params->get('thumb_prefix');

			$str = "CONCAT('<img src=\"" . COM_FABRIK_LIVESITE . "'," . "REPLACE(" . "REPLACE($val, '$ulDir', '" . $thumbDir . "')" . //replace the main image dir with thumb dir
				", '\\\', '/')" . //replace the backslashes with forward slashes
				", '\" alt=\"database join image\" />')";

		}
		else
		{
			$str = " REPLACE(CONCAT('<img src=\"" . COM_FABRIK_LIVESITE . "' , $val, '\" alt=\"database join image\"/>'), '\\\', '/') ";
		}
		return $str;
	}

	/**
	 * trigger called when a row is deleted
	 * @param	array	grouped data of rows to delete
	 */

	function onDeleteRows($groups)
	{
		//cant delete files from unpublished elements
		if (!$this->canUse())
		{
			return;
		}
		$db = $this->getListModel()->getDb();
		$storage = $this->getStorage();
		require_once(COM_FABRIK_FRONTEND . '/helpers/uploader.php');
		$params = $this->getParams();
		if ($params->get('upload_delete_image'))
		{
			jimport('joomla.filesystem.file');
			$elName = $this->getFullName(false, true, false);
			$name = $this->getElement()->name;
			foreach ($groups as $rows)
			{
				foreach ($rows as $row)
				{
					if (array_key_exists($elName . '_raw', $row))
					{
						if ($this->isJoin())
						{
							$join = $this->getJoinModel()->getJoin();
							$query = $db->getQuery(true);
							$query->select('*')->from($db->quoteName($join->table_join))
								->where($db->quoteName('parent_id') . ' = ' . $db->quote($row->__pk_val));
							$db->setQuery($query);
							$imageRows = $db->loadObjectList('id');
							if (!empty($imageRows))
							{
								foreach ($imageRows as $imageRow)
								{
									$this->deleteFile($imageRow->$name);
								}
								$query->clear();
								$query->delete($db->quoteName($join->table_join))
									->where($db->quoteName('id') . ' IN (' . implode(', ', array_keys($imageRows)) . ')');
								$db->setQuery($query);
								$db->query();
							}
						}
						else
						{
							$files = explode(GROUPSPLITTER, $row->{$elName . '_raw'});
							foreach ($files as $filename)
							{
								$this->deleteFile(trim($filename));
							}
						}
					}
				}
			}
		}
	}

	function _return_bytes($val)
	{
		$val = trim($val);
		$last = JString::strtolower(substr($val, -1));
		if ($last == 'g')
			$val = $val * 1024 * 1024 * 1024;
		if ($last == 'm')
			$val = $val * 1024 * 1024;
		if ($last == 'k')
			$val = $val * 1024;
		return $val;
	}

	/**
	 * get the max upload size allowed by the server.
	 * @return int kilobyte upload size
	 */

	function maxUpload()
	{
		$post_value = $this->_return_bytes(ini_get('post_max_size'));
		$upload_value = $this->_return_bytes(ini_get('upload_max_filesize'));
		$value = min($post_value, $upload_value);
		$value = $value / 1024;
		return $value;
	}

	/**
	 * Turn form value into email formatted value
	 *
	 * @param   mixed  $value          element value
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  group repeat counter
	 *
	 * @return  string  email formatted value
	 */

	protected function _getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		$params = $this->getParams();
		$storage = $this->getStorage();
		if ($params->get('fu_show_image_in_email', false))
		{
			$render = $this->loadElement($value);
			if ($params->get('fu_show_image') != '0')
			{
				if ($value != '' && $storage->exists(COM_FABRIK_BASE . $value))
				{
					$render->render($this, $params, $value);
				}
			}
			if ($render->output == '' && $params->get('default_image') != '')
			{
				$render->output = '<img src="' . $params->get('default_image') . '" alt="image" />';
			}
			return $render->output;
		}
		else
		{
			return $storage->preRenderPath($value);
		}
	}

	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  when repeating joinded groups we need to know what part of the array to access
	 *
	 * @return  string	value
	 */

	public function getROValue($data, $repeatCounter = 0)
	{
		$v = $this->getValue($data, $repeatCounter);
		$storage = $this->getStorage();
		return $storage->pathToURL($v);
	}

	/**
	 * not really an AJAX call, we just use the pluginAjax method so we can run this
	 * method for handling scripted downloads.
	 */

	function onAjax_download()
	{
		$this->setId(JRequest::getInt('element_id'));
		$this->getElement();
		$params = $this->getParams();
		$app = JFactory::getApplication();
		$url = JRequest::getVar('HTTP_REFERER', '', 'server');
		$lang = JFactory::getLanguage();
		$lang->load('com_fabrik.plg.element.fabrikfileupload', JPATH_ADMINISTRATOR);
		if (!$this->canView())
		{
			$app->enqueueMessage(JText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD_NO_PERMISSION'));
			$app->redirect($url);
			exit;
		}
		$rowid = JRequest::getInt('rowid', 0);
		if (empty($rowid))
		{
			$app->enqueueMessage(JText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD_NO_SUCH_FILE'));
			$app->redirect($url);
			exit;
		}
		$repeatcount = JRequest::getInt('repeatcount', 0);
		$listModel = $this->getListModel();
		$row = $listModel->getRow($rowid, false);
		if (empty($row))
		{
			$app->enqueueMessage(JText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD_NO_SUCH_FILE'));
			$app->redirect($url);
			exit;
		}
		$aclEl = $this->getFormModel()->getElement($params->get('fu_download_acl', ''), true);
		if (!empty($aclEl))
		{
			$aclEl = $aclEl->getFullName();
			$aclElraw = $aclEl . '_raw';
			$user = JFactory::getUser();
			$groups = $user->getAuthorisedViewLevels();
			$canDownload = in_array($row->$aclElraw, $groups);
			if (!$canDownload)
			{
				$app->enqueueMessage(JText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD_NO_PERMISSION'));
				$app->redirect($url);
			}
		}
		$storage = $this->getStorage();
		$elName = $this->getFullName(false, true, false);
		$filepath = $row->$elName;
		$filepath = FabrikWorker::JSONtoData($filepath, true);
		$filepath = JArrayHelper::getValue($filepath, $repeatcount);
		$filepath = $storage->getFullPath($filepath);
		$filecontent = $storage->read($filepath);
		if ($filecontent !== false)
		{
			$thisFileInfo = $storage->getFileInfo($filepath);
			if ($thisFileInfo === false)
			{
				$app->enqueueMessage(JText::_('DOWNLOAD NO SUCH FILE'));
				$app->redirect($url);
				exit;
			}

			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Some time in the past
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
			header('Accept-Ranges: bytes');
			header('Content-Length: ' . $thisFileInfo['filesize']);
			header('Content-Type: ' . $thisFileInfo['mime_type']);
			header('Content-Disposition: attachment; filename="' . $thisFileInfo['filename'] . '"');
			// ... serve up the file ...
			echo $filecontent;
			//$this->downloadEmail($row, $filepath);
			$this->downloadHit($rowid, $repeatcount);
			$this->downloadLog($row, $filepath);
			// ... and we're done.
			exit();
		}
		else
		{
			$app->enqueueMessage(JText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD_NO_SUCH_FILE'));
			$app->redirect($url);
			exit;
		}
	}

	function downloadHit($rowid, $repeatCount = 0)
	{
		// $$$ hugh @TODO - make this work for repeats and/or joins!
		$params = $this->getParams();
		if ($hit_counter = $params->get('fu_download_hit_counter', ''))
		{
			JError::setErrorHandling(E_ALL, 'ignore');
			$listModel = $this->getListModel();
			$pk = $listModel->getTable()->db_primary_key;
			$fabrikDb = $listModel->getDb();
			list($table_name, $element_name) = explode('.', $hit_counter);
			$sql = "UPDATE $table_name SET $element_name = COALESCE($element_name,0) + 1 WHERE $pk = " . $fabrikDb->quote($rowid);
			$fabrikDb->setQuery($sql);
			$fabrikDb->query();
		}
	}

	/**
	 * log the download
	 * @since 2.0.5
	 * @param	object	row
	 * @param	string	$filepath
	 */

	function downloadLog($row, $filepath)
	{
		$params = $this->getParams();
		if ((int) $params->get('fu_download_log', 0))
		{
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
			$log = JTable::getInstance('log', 'Table');
			$log->message_type = 'fabrik.fileupload.download';
			$user = JFactory::getUser();
			$msg = new stdClass;
			$msg->file = $filepath;
			$msg->userid = $user->get('id');
			$msg->username = $user->get('username');
			$msg->email = $user->get('email');
			$log->referring_url = JRequest::getVar('REMOTE_ADDR', '', 'server');
			$log->message = json_encode($msg);
			$log->store();
		}
	}

	/**
	 * called when save as copy form button clicked
	 * @param	mixed	value to copy into new record
	 * @return	mixed	value to copy into new record
	 */

	public function onSaveAsCopy($val)
	{
		if (empty($val))
		{
			$isjoin = $groupModel->isJoin();
			$origData = $this->_form->getOrigData();
			$groupModel = $this->getGroup();
			if ($isjoin)
			{
				$name = $this->getFullName(false, true, false);
				$joinid = $groupModel->getGroup()->join_id;
			}
			else
			{
				$name = $this->getFullName(true, true, false);
			}
			$val = $origData[$name];
		}
		return $val;
	}

	/**
	 * is the element a repeating element
	 * @return bool
	 */

	public function isRepeatElement()
	{
		$params = $this->getParams();
		return $params->get('ajax_upload') && ($params->get('ajax_max', 4) > 1);
	}

	/**
	 * fabrik 3: needs to be onAjax_deleteFile
	 * delete a previously uploaded file via ajax
	 */

	public function onAjax_deleteFile()
	{
		$filename = JRequest::getVar('file');
		$join = FabTable::getInstance('join', 'FabrikTable');
		$join->load(array('element_id' => JRequest::getInt('element_id')));
		$this->setId(JRequest::getInt('element_id'));
		$this->getElement();
		$params = $this->getParams();
		$dir = $params->get('ul_directory', '');
		$filename = rtrim($dir, '/') . '/' . $filename;
		$this->deleteFile($filename);
		$db = $this->getListModel()->getDb();
		$query = $db->getQuery(true);
		$query->delete($db->quoteName($join->table_join))->where($db->quoteName('id') . ' = ' . JRequest::getInt('recordid'));
		$db->setQuery($query);
		$db->query();
	}

	/**
	 * can be overwritten by plugin class
	 * determines the value for the element in the form view
	 * @param	array	data
	 * @param	int		when repeating joinded groups we need to know what part of the array to access
	 * @param	array	options
	 * @return	string	value
	 */

	function getValue($data, $repeatCounter = 0, $opts = array())
	{
		//@TODO rename $this->defaults to $this->values
		if (!isset($this->defaults))
		{
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults))
		{
			$groupModel = $this->getGroup();
			$group = $groupModel->getGroup();
			$joinid = $this->isJoin() ? $this->getJoinModel()->getJoin()->id : $group->join_id;
			$formModel = $this->getFormModel();
			$element = $this->getElement();

			// $$$rob - if no search form data submitted for the search element then the default
			// selection was being applied instead
			//otherwise get the default value so if we don't find the element's value in $data we fall back on this value
			$value = JArrayHelper::getValue($opts, 'use_default', true) == false ? '' : $this->getDefaultValue($data);

			$name = $this->getFullName(false, true, false);
			$rawname = $name . '_raw';
			if ($groupModel->isJoin() || $this->isJoin())
			{
				// $$$ rob 22/02/2011 this test barfed on fileuploads which weren't repeating
				//if ($groupModel->canRepeat() || !$this->isJoin()) {
				if ($groupModel->canRepeat())
				{
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid])
						&& array_key_exists($name, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name]))
					{
						$value = $data['join'][$joinid][$name][$repeatCounter];
					}
					else
					{
						if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid])
							&& array_key_exists($name, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name]))
						{
							$value = $data['join'][$joinid][$name][$repeatCounter];
						}
					}
				}
				else
				{
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid])
						&& array_key_exists($name, $data['join'][$joinid]))
					{
						$value = $data['join'][$joinid][$name];
					}
					else
					{
						if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid])
							&& array_key_exists($rawname, $data['join'][$joinid]))
						{
							$value = $data['join'][$joinid][$rawname];
						}
					}
					// $$$ rob if you have 2 tbl joins, one repeating and one not
					// the none repeating one's values will be an array of duplicate values
					// but we only want the first value
					if (is_array($value) && !$this->isJoin())
					{
						$value = array_shift($value);
					}
				}
			}
			else
			{
				if ($groupModel->canRepeat())
				{
					//repeat group NO join
					$thisname = $name;
					if (!array_key_exists($name, $data))
					{
						$thisname = $rawname;
					}
					if (array_key_exists($thisname, $data))
					{
						if (is_array($data[$thisname]))
						{
							//occurs on form submission for fields at least
							$a = $data[$thisname];
						}
						else
						{
							//occurs when getting from the db
							$a = json_decode($data[$thisname]);
						}
						$value = JArrayHelper::getValue($a, $repeatCounter, $value);
					}

				}
				else
				{
					$value = !is_array($data) ? $data : JArrayHelper::getValue($data, $name, JArrayHelper::getValue($data, $rawname, $value));
				}
			}
			if (is_array($value) && !$this->isJoin())
			{
				if (!$this->getParams()->get('fileupload_crop'))
				{
					$value = implode(',', $value);
				}
			}
			// $$$ hugh - don't know what this is for, but was breaking empty fields in repeat
			// groups, by rendering the //..*..// seps.
			// if ($value === '') { //query string for joined data
			if ($value === '' && !$groupModel->canRepeat())
			{
				// Query string for joined data
				$value = JArrayHelper::getValue($data, $name);
			}
			if (is_array($value) && !$this->isJoin())
			{
				if (!$this->getParams()->get('fileupload_crop'))
				{

					$value = implode(',', $value);
				}
			}
			// @TODO perhaps we should change this to $element->value and store $element->default as the actual default value
			// stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1)
			{
				FabrikWorker::getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
			}
			$this->defaults[$repeatCounter] = $value;
		}
		return $this->defaults[$repeatCounter];
	}
}
