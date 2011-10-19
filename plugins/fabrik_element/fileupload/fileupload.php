<?php
/**
 * Plug-in to render fileupload element
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'image.php');

define("FU_DOWNLOAD_SCRIPT_NONE", '0');
define("FU_DOWNLOAD_SCRIPT_TABLE", '1');
define("FU_DOWNLOAD_SCRIPT_DETAIL", '2');
define("FU_DOWNLOAD_SCRIPT_BOTH", '3');

class plgFabrik_ElementFileupload extends plgFabrik_Element
{

	/**@var object storage method adaptor object (filesystem/amazon s3) */
	protected $storage = null;

	protected $_is_upload = true;

	public function isJoin()
	{
		$params = $this->getParams();
		if ($params->get('ajax_upload') && (int)$params->get('ajax_max', 4) > 1) {
			return true;
		} else {
			return parent::isJoin();
		}
	}

	/**
	 * decide whether to ingore data when updating a record
	 *
	 * @param string $val
	 * @return bol true if you shouldnt update the data
	 */

	function ignoreOnUpdate($val)
	{
		//check if its a CSV import if it is allow the val to be inserted
		if (JRequest::getCmd('task') === 'makeTableFromCSV' || $this->getListModel()->_importingCSV) {
			return false;
		}
		$fullName = $this->getFullName(true, true, false);
		$params = $this->getParams();
		$groupModel = $this->_group;
		$return = false;
		if ($groupModel->canRepeat()) {
			//$$$rob could be the case that we aren't uploading an element by have removed
			//a repeat group (no join) with a file upload element, in this case processUpload has the correct
			//file path settings.
			return false;
		} else {
			if ($groupModel->isJoin()) {
				$name = $this->getFullName(false, true, false);
				$joinid = $groupModel->getGroup()->join_id;
				$fdata = $_FILES['join']['name'][$joinid][$name];
			} else {
				$fdata = @$_FILES[$fullName]['name'];
			}
			if ($fdata == '') {
				if ($params->get('fileupload_crop') == false) {
					return true;
				} else {
					//if we can crop we need to store the cropped coordinated in the field data
					// @see onStoreRow();
					// above depreciated - not sure what to return here for the moment
					return false;
				}
			} else {
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
		$params = $this->getParams();
		if ($params->get('ajax_upload')) {

			$prefix = JDEBUG ? '' : '.min';
			$runtimes = $params->get('ajax_runtime', 'html5');
			parent::formJavascriptClass($srcs, 'plugins/fabrik_element/fileupload/lib/plupload/js/plupload'.$prefix.'.js');

			if(strstr($runtimes, 'html5')) {
				parent::formJavascriptClass($srcs, 'plugins/fabrik_element/fileupload/lib/plupload/js/plupload.html5'.$prefix.'.js');
			}
			if(strstr($runtimes, 'html4')) {
				parent::formJavascriptClass($srcs, 'plugins/fabrik_element/fileupload/lib/plupload/js/plupload.html4'.$prefix.'.js');
			}
			if(strstr($runtimes, 'gears')) {
				parent::formJavascriptClass($srcs, 'plugins/fabrik_element/fileupload/lib/plupload/js/gears_init.js');
				parent::formJavascriptClass($srcs, 'plugins/fabrik_element/fileupload/lib/plupload/js/plupload.gears'.$prefix.'.js');
			}

			if(strstr($runtimes, 'flash')) {
				parent::formJavascriptClass($srcs, 'plugins/fabrik_element/fileupload/lib/plupload/js/plupload.flash'.$prefix.'.js');
			}
			if(strstr($runtimes, 'silverlight')) {
				parent::formJavascriptClass($srcs, 'plugins/fabrik_element/fileupload/lib/plupload/js/plupload.silverlight'.$prefix.'.js');
			}
			if(strstr($runtimes, 'browserplus')) {
				parent::formJavascriptClass($srcs, 'plugins/fabrik_element/fileupload/lib/plupload/js/plupload.browserplus'.$prefix.'.js');
			}
		}
		parent::formJavascriptClass($srcs, $script);
		static $elementclasses;

		if (!isset($elementclasses)) {
			$elementclasses = array();
		}
		//load up the default scipt
		if ($script == '') {
			$script = 'plugins/fabrik_element/'.$this->getElement()->plugin.'/'.$this->getElement()->plugin.'.js';
		}
		if (empty($elementclasses[$script])) {
			$srcs[] = $script;
			$elementclasses[$script] = 1;
		}
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		//if ((int)$params->get('fileupload_crop', 0) == 1) {
			//FabrikHelperHTML::script('media/com_fabrik/js/mcl-min.js');

			$src = array('media/com_fabrik/js/lib/mcl/CANVAS.js', 'media/com_fabrik/js/lib/mcl/CanvasItem.js',
			'media/com_fabrik/js/lib/mcl/Cmorph.js', 'media/com_fabrik/js/lib/mcl/Layer.js',
			'media/com_fabrik/js/lib/mcl/LayerHash.js', 'media/com_fabrik/js/lib/mcl/Thread.js',
			'media/com_fabrik/js/lib/canvas-extra.js'
			);
			FabrikHelperHTML::script($src);
		//}

		$element = $this->getElement();
		$paramsKey = $this->getFullName(false, true, false);
		$paramsKey = Fabrikstring::rtrimword($paramsKey, $this->getElement()->name);
		$paramsKey .= 'params';
		$formData = $this->getForm()->_data;
		$imgParams = JArrayHelper::getValue($formData, $paramsKey);

		$value = $this->getValue(array(), $repeatCounter);

		//$value = is_array($value) ? $value : explode(GROUPSPLITTER, $value);
		$value = is_array($value) ? $value : FabrikWorker::JSONtoData($value, true);
		$value = $this->checkForSingleCropValue($value);

		//repeat_image_repeat_image___params
		$rawvalues = count($value) == 0 ? array() : array_fill(0, count($value), 0);
		$fdata = $this->getForm()->_data;
		$rawkey = $this->getFullName(false, true, false).'_raw';
		$rawvalues = JArrayHelper::getValue($fdata, $rawkey, $rawvalues);
		if (!is_array($rawvalues)) {
			$rawvalues = explode(GROUPSPLITTER, $rawvalues);
		}
		if (!is_array($imgParams)) {
			$imgParams = explode(GROUPSPLITTER, $imgParams);
		}
		$oFiles = new stdClass();
		$iCounter = 0;
		for ($x = 0; $x < count($value); $x++) {
			if (array_key_exists($x, $value) && $value[$x] !== '') {
				if (is_array($value[$x])) {
					//from failed validation
					foreach ($value[$x]['id'] as $tkey => $parts) {
						$o = new stdClass();
						$o->id = 'alreadyuploaded_'.$element->id.'_'.$iCounter;//$rawvalues[$x];
						$o->name = array_pop(explode(DS, $tkey));
						$o->path = $tkey;
						$o->url = $this->getStorage()->pathToURL($tkey);
						$o->recordid = $rawvalues[$x];
						$o->params = json_decode($value[$x]['crop'][$tkey]);
						$oFiles->$iCounter = $o;
						$iCounter++;
					}
				} else {
					if (is_object($value[$x])) { //single crop image (not sure about the 0 settings in here)
						$parts = explode(DS, $value[$x]->file);
						$o = new stdClass();
						$o->id = 'alreadyuploaded_'.$element->id.'_0';
						$o->name = array_pop($parts);
						$o->path = $value[$x]->file;
						$o->url = $this->getStorage()->pathToURL($value[$x]->file);
						$o->recordid = 0;
						$o->params = json_decode($value[$x]->params);
						$oFiles->$iCounter = $o;
						$iCounter++;
					} else {
							$parts = explode(DS, $value[$x]);
							$o = new stdClass();
							$o->id = 'alreadyuploaded_'.$element->id.'_'.$rawvalues[$x];
							$o->name = array_pop($parts);
							$o->path = $value[$x];
							$o->url = $this->getStorage()->pathToURL($value[$x]);
							$o->recordid = $rawvalues[$x];
							$o->params = json_decode(JArrayHelper::getValue($imgParams, $x, '{}'));
							$oFiles->$iCounter = $o;
							$iCounter++;
					}
				}
			}

		}

		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->id = $this->_id;
		$opts->elid = $element->id;
		$opts->defaultImage = $params->get('default_image');
		$opts->folderSelect = $params->get('upload_allow_folderselect', 0);
		$opts->dir = JPATH_SITE.DS.$params->get('ul_directory');
		$opts->ajax_upload = (bool)$params->get('ajax_upload', false);
		$opts->ajax_runtime = $params->get('ajax_runtime', 'html5');
		$opts->max_file_size = $params->get('ul_max_file_size');
		$opts->ajax_chunk_size = (int)$params->get('ajax_chunk_size', 0);
		$opts->crop = (int)$params->get('fileupload_crop', 0);
		$opts->elementName = $this->getFullName(true, true, true);
		$opts->cropwidth = (int)$params->get('fileupload_crop_width');
		$opts->cropheight = (int)$params->get('fileupload_crop_height');
		$opts->ajax_max = (int)$params->get('ajax_max', 4);
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
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderListData($data, $oAllRowsData)
	{
		$data = FabrikWorker::JSONtoData($data, true);
		$params = $this->getParams();
		for ($i=0; $i <count($data); $i++) {
			$data[$i] = $this->_renderListData($data[$i], $oAllRowsData, $i);
		}
		$data = json_encode($data);
		return parent::renderListData($data, $oAllRowsData);
	}

	/**
	 * shows the data formatted for the CSV export view
	 * @param string data
	 * @param string element name
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderListData_csv($data, $rows)
	{
		$params = $this->getParams();
		$format = $params->get('ul_export_encode_csv', 'base64');
		return $this->encodeFile($data, $format);
	}

	/**
	 * shows the data formatted for the JSON export view
	 * @param string data
	 * @param string element name
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderListData_json($data, $rows)
	{
		$params = $this->getParams();
		$format = $params->get('ul_export_encode_json', 'base64');
		return $this->encodeFile($data, $format);
	}

	/**
	 * encodes the file
	 * @param string $file relative file path
	 * @param mixed format to encode the file full|url|base64|raw|relative
	 * @return string encoded file for export
	 */

	protected function encodeFile($file, $format = 'relative')
	{
		$path = JPATH_SITE.DS.$file;
		if (!JFile::exists($path)) {
			return $file;
		}

		switch ($format) {
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
	 * @param string file
	 */

	function loadElement($file)
	{
		$ext = strtolower(JFile::getExt($file));
		if (JFile::exists(JPATH_ROOT.DS.'plugins'.DS.'fabrik_element'.DS.'fileupload'.DS.'element'.DS.'custom'.DS.$ext.'.php')) {
			require(JPATH_ROOT.DS.'plugins'.DS.'fabrik_element'.DS.'fileupload'.DS.'element'.DS.'custom'.DS.$ext.'.php');
		}
		else if (JFile::exists(JPATH_ROOT.DS.'plugins'.DS.'fabrik_element'.DS.'fileupload'.DS.'element'.DS.$ext.'.php')) {
			require(JPATH_ROOT.DS.'plugins'.DS.'fabrik_element'.DS.'fileupload'.DS.'element'.DS.$ext.'.php');
		} else {
			//default down to allvideos content plugin
			if (in_array($ext, array('flv', '3gp', 'divx'))) {
				require(JPATH_ROOT.DS.'plugins'.DS.'fabrik_element'.DS.'fileupload'.DS.'element'.DS.'allvideos.php');
			} else {
				require(JPATH_ROOT.DS.'plugins'.DS.'fabrik_element'.DS.'fileupload'.DS.'element'.DS.'default.php');
			}
		}
		return $render;
	}

	/**
	 * Display the file in the table
	 *
	 * @param string $data
	 * @param array $oAllRowsData
	 * @param int repeat group count
	 * @return string
	 */

	function _renderListData($data, $oAllRowsData, $i = 0)
	{
		$this->_repeatGroupCounter = $i;
		$element = $this->getElement();
		$params = $this->getParams();
		// not sure but after update from 2.1 to 3 for podion data was an object
		if (is_object($data)) {
			$data  = $data->file;
		}
		$data = FabrikWorker::JSONtoData($data);
		if (is_array($data) && !empty($data)) {
			//crop stuff needs to be removed from data to get correct file path
			$data = $data[0];
		}
		$storage = $this->getStorage();

		$use_download_script = $params->get('fu_use_download_script', '0');
		if ($use_download_script == FU_DOWNLOAD_SCRIPT_TABLE || $use_download_script == FU_DOWNLOAD_SCRIPT_BOTH) {
			if (empty($data) || !$storage->exists(COM_FABRIK_BASE.$data)) {
				return "";
			}
			$formModel = $this->getForm();
			$formid = $formModel->getId();
			$rowid = $oAllRowsData->__pk_val;
			$elementid = $this->_id;
			$title = basename($data);
			if ($params->get('fu_title_element') == '') {
				$title_name = $this->getFullName(true, true, false ) . '__title';
			} else {
				$title_name = str_replace('.', '___', $params->get('fu_title_element'));
			}
			//$title_name .= '_raw'; //Jaanus: WHY _raw? Do we really want to see the numbers instead of normal words? See also http://fabrikar.com/forums/showthread.php?p=123267 about f2
			if (array_key_exists($title_name, $oAllRowsData)) {
				if (!empty($oAllRowsData->$title_name)) {
					$title = $oAllRowsData->$title_name;
					$title = FabrikWorker::JSONtoData($title, true);
					$title = $title[$i];
				}
			}
			$link = COM_FABRIK_LIVESITE."index.php?option=com_fabrik&amp;task=plugin.pluginAjax&amp;plugin=fileupload&amp;method=ajax_download&amp;element_id=$elementid&amp;formid=$formid&amp;rowid=$rowid&amp;repeatcount=$i";
			$url = "<a href=\"$link\">$title</a>";
			return $url;
		}

		if ($params->get('fu_show_image_in_table')  == '0') {
			$render = $this->loadElement('default');
		} else {
			$render = $this->loadElement($data);
		}

		if (empty($data) || !$storage->exists(COM_FABRIK_BASE.$data)) { //@TODO use adaptor
			$render->output = '';
		} else {
			$render->renderListData($this, $params, $data, $oAllRowsData);
		}
		if ($render->output == '' && $params->get('default_image') != '') {
			$render->output = "<img src=\"{$params->get('default_image')}\" alt=\"image\" />";
		}
		return $render->output;
	}

	/**
	 * do we need to include the lighbox js code
	 *
	 * @return bol
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
	 * formats the posted data for insertion into the database
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat($val, $data)
	{
		//val already contains group splitter from processUpload() code
		return $val;
	}

	/**
	 * checks the posted form data against elements INTERNAL validataion rule - e.g. file upload size / type
	 * @param string elements data
	 * @param int repeat group counter
	 * @return bol true if passes / false if falise validation
	 */

	function validate($data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		$groupModel = $this->_group;
		$group = $groupModel->getGroup();
		$this->_validationErr = '';
		$errors = array();
		$elName = $this->getFullName();
		$elName = str_replace('[]', '', $elName); //remove any repeat group labels
		if ($groupModel->isJoin()) {
			// $$$ hugh - kinda screws things up if 'join' is used in the element name!
			/*
			 $elTempName = str_replace('join', '', $elName);
			 $elTempName = str_replace('[', '', $elTempName);
			 $joinArray = explode(']', $elTempName);
			 */
			$joinArray = array();
			if (!preg_match('#join\[(\d+)\]\[(\S+)\]#', $elName, $joinArray)) {
				return true;
			}
			if (!array_key_exists('join', $_FILES)) {
				return true;
			}
			$aFile 	=  $_FILES['join'];
			$myFileName = $aFile['name'][$joinArray[1]][$joinArray[2]];
			$myFileSize = $aFile['size'][$joinArray[1]][$joinArray[2]];
			if (is_array($myFileSize)) {
				$myFileSize = $myFileSize[$repeatCounter];
			}
			if (is_array($myFileName)) {
				$myFileName = $myFileName[$repeatCounter];
			}
		} else {
			if (!array_key_exists($elName, $_FILES)) {
				return true;
			}
			$aFile 	=  $_FILES[$elName];
			if ($groupModel->canRepeat()) {
				$myFileName = $aFile['name'][$repeatCounter];
				$myFileSize = $aFile['size'][$repeatCounter];
			} else {
				$myFileName = $aFile['name'];
				$myFileSize = $aFile['size'];
			}
		}
		$ok = true;

		if (!$this->_fileUploadFileTypeOK($myFileName)) {
			$errors[] = JText::_('PLG_ELEMENT_FILEUPLOAD_FILE_TYPE_NOT_ALLOWED');
			$ok = false;
		}
		if (!$this->_fileUploadSizeOK($myFileSize)) {
			$ok = false;
			$mySize = $myFileSize / 1000;
			$errors[] = JText::sprintf('PLG_ELEMENT_FILEUPLOAD_FILE_TOO_LARGE', $params->get('ul_max_file_size'), $mySize);
		}
		$filepath = $this->_getFilePath( $repeatCounter);
		jimport('joomla.filesystem.file');
		if (JFile::exists($filepath)) {
			if ($params->get('ul_file_increment', 0) == 0) {
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
		if ($allowedFiles != '') {
			// $$$ hugh - strip spaces, as folk often do ".foo, .bar"
			preg_replace('#\s+#', '', $allowedFiles);
			$aFileTypes = explode(",", $allowedFiles);
		} else {
			$mediaparams = JComponentHelper::getParams('com_media');
			$aFileTypes = explode(',', $mediaparams->get('upload_extensions'));
		}
		return $aFileTypes;
	}

	/**
	 * This checks the uploaded file type against the csv specified in the upload
	 * element
	 * @access PRIVATE
	 * @param string filename
	 * @return bol true if upload file type ok
	 */

	function _fileUploadFileTypeOK($myFileName)
	{
		$aFileTypes = $this->_getAllowedExtension();
		if ($myFileName == '') {
			return true;
		}
		$curr_f_ext = strtolower(JFile::getExt($myFileName));
		array_walk($aFileTypes, create_function('&$v', '$v = strtolower($v);'));
		if (in_array($curr_f_ext, $aFileTypes) || in_array(".".$curr_f_ext, $aFileTypes)) {
			return true;
		}

		return false;
	}

	/**
	 * This checks that thte fileupload size is not greater than that specified in
	 * the upload element
	 * @access PRIVATE
	 * @param string file size
	 * @return bol true if upload file type ok
	 */

	function _fileUploadSizeOK($myFileSize)
	{
		$params = $this->getParams();
		$max_size = $params->get('ul_max_file_size') * 1000;
		if ($myFileSize <= $max_size) {
			return true;
		}
		return false;
	}

	/**
	 * if we are using plupload but not with crop
	 * @param string element $name
	 * @return bool if processed or not
	 */

	function processAjaxUploads($name)
	{
		$params = $this->getParams();
		if ($params->get('fileupload_crop') == false && JRequest::getCmd('task') !== 'pluginAjax' && $params->get('ajax_upload') == true) {
			$post = JRequest::get('post');
			$raw = $this->getValue($post);
			if ($raw == '') {
				return true;
			}
			if (empty($raw)){
				return;
			}
			$crop = (array)JArrayHelper::getValue($raw[0], 'crop');
			$ids = (array)JArrayHelper::getValue($raw[0], 'id');
			$ids = array_values($ids);

			$saveParams = array();
			$files = array_keys($crop);
			$groupModel = $this->getGroup();
			$isjoin = ($groupModel->isJoin() || $this->isJoin());

			if ($isjoin) {
				if (!$groupModel->canRepeat() && !$this->isJoin()) {
					$files = $files[0];
				}
				$joinid = $groupModel->getGroup()->join_id;
				if ($this->isJoin()) {
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

			} else {
				$strfiles = json_encode($files);
				$this->_form->updateFormData($name . "_raw", $strfiles);
				$this->_form->updateFormData($name, $strfiles);
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 *
	 * If an image has been uploaded with ajax upload then we may need to crop it
	 * @param string element $name
	 * @return bool if processed or not
	 */

	function crop($name)
	{
		$params = $this->getParams();

		if ($params->get('fileupload_crop') == true && JRequest::getCmd('task') !== 'pluginAjax') {
			$post = JRequest::get('post');
			$raw = JArrayHelper::getValue($post, $name."_raw", array());

			if ($this->getValue($post) != 'Array,Array') {
				$raw = $this->getValue($post);
				$crop =(array)JArrayHelper::getValue($raw[0], 'crop');
				$ids = (array)JArrayHelper::getValue($raw[0], 'id');
			} else {
				//single image
				$crop = (array)JArrayHelper::getValue($raw, 'crop');
				$ids = (array)JArrayHelper::getValue($raw, 'id');
			}
			if ($raw == '') {
				return true;
			}

			$ids = array_values($ids);

			$saveParams = array();
			$files = array_keys($crop);
			$storage = $this->getStorage();
			$oImage = imageHelper::loadLib($params->get('image_library'));
			$oImage->setStorage($storage);
			$fileCounter = 0;

			foreach ($crop as $filepath => $json) {
				$coords = json_decode(urldecode($json));
				$saveParams[] = $json;

				//@todo allow uploading into front end designated folders?
				$myFileDir = '';

				$cropPath = $storage->clean(JPATH_SITE.DS.$params->get('fileupload_crop_dir').DS.$myFileDir.DS, false);
				$w = new FabrikWorker();
				$cropPath = $w->parseMessageForPlaceHolder($cropPath);
				$cropWidth = $params->get('fileupload_crop_width', 125);
				$cropHeight = $params->get('fileupload_crop_height', 125);

				if ($cropPath != '') {
					if (!$storage->folderExists($cropPath)) {
						if (!$storage->createFolder($cropPath)) {
							$this->setError(21, "Could not make dir $cropPath ");
							continue;
						}
					}
				}
				$filepath = $storage->clean(JPATH_SITE.DS.$filepath);

				$fileURL = $storage->getFileUrl(str_replace(COM_FABRIK_BASE, '', $filepath));
				$destCropFile = $storage->_getCropped($fileURL);
				$destCropFile = $storage->urlToPath($destCropFile);
				$destCropFile = $storage->clean($destCropFile);
				$srcX = $coords->cropdim->x;
				$srcY = $coords->cropdim->y;


				$imagedim = $coords->imagedim;

				if (!JFile::exists($filepath)) {
					unset($files[$fileCounter]);
					$fileCounter ++;
					continue;
				}
				$fileCounter ++;

				/*if ((int)$coords->scale > 100) {
					echo "largte";
					$this->cropForLarger($oImage, $filepath, $destCropFile, $coords);
					} else {*/
				$this->cropForSmaller($oImage, $filepath, $destCropFile, $coords);
				//}
				$storage->setPermissions($destCropFile);
			}

			$groupModel = $this->getGroup();
			$isjoin = ($groupModel->isJoin() || $this->isJoin());

			if ($isjoin) {
				if (!$groupModel->canRepeat() && !$this->isJoin()) {
					$files = $files[0];
				}
				$joinid = $groupModel->getGroup()->join_id;
				if ($this->isJoin()) {
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

			} else {
				//only one file
				$store = array();
				for ($i=0; $i < count($files); $i++) {
					$o = new stdClass();
					$o->file = $files[$i];
					$o->params = $saveParams[$i];
					$store[] = $o;
				}
				$store = json_encode($store);
				$strfiles = implode(GROUPSPLITTER, $store);
				$this->_form->updateFormData($name . "_raw", $store);
				$this->_form->updateFormData($name, $store);

			}
			return true;
		} else {
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
		$scale = (int)$coords->scale;
		//get the orignal file
		list($origImage, $header) = $oImage->imageFromFile($filepath);

		//get original File dims
		list($origWidth, $origHeight) = getimagesize($filepath);
		if ($scale !== 100) {
			//make a scaled verios of the original image
			$destWidth= (int)$origWidth * ($scale/100);
			$destHeight = (int)$origHeight* ($scale/100);
			$scaledImage = imagecreatetruecolor($destWidth, $destHeight);
			//copy the man image into the scaled image
			imagecopyresampled($scaledImage, $origImage, 0, 0, 0, 0, $destWidth, $destHeight, $origWidth, $origHeight);
			$origImage = $scaledImage;
		}
		//$oImage->imageToFile($destCropFile, $origImage);exit;
		$imagedim = $coords->imagedim;
		//has the image itself been dragged?
		$deltaX = 400/2 - $imagedim->x;
		$deltaY = 400/2 - $imagedim->y;

		//make an image the size of the crop interface
		$canvas = imagecreatetruecolor(400, 400);
		$destX = (int)(400 - ($origWidth * ($scale/100))) /2; //x position to start placing the original image on the canvas
		$destX = $destX - $deltaX;

		$destY = (int)(400 - ($origHeight * ($scale/100))) /2; //y position to start placing the original image on the canvas
		$destY = $destY - $deltaY;

		$srcX = 0; // x point on source image to copy from
		$srcY = 0; //y point on source image to copy from
		$srcW = (int)$origWidth * ($scale/100);
		$srcH = (int)$origHeight * ($scale/100);
		$destWidth = (int)$imagedim->w;
		$setHeight = (int)$imagedim->h;
		imagecopyresampled($canvas, $origImage, $destX, $destY, $srcX, $srcY, $destWidth, $setHeight, $srcW, $srcH);

		$oImage->imageToFile($destCropFile, $canvas);

		if ($coords->rotation != 0) {
			//works great here for images with scale < 100
			//rotate image
			list($rotatedImgObject, $rotateWidth, $rotateHeight) = $oImage->rotate($destCropFile, $destCropFile, $coords->rotation * -1);
			//scale it back to crop dims
			$xx = $rotateWidth/2 - 400/2;
			$yy = $rotateHeight/2 - 400/2;
			$oImage->crop($destCropFile, $destCropFile, $xx , $yy , 400, 400);
		}

		//Crop it from the crop coordinates
		$srcX = ($coords->cropdim->x - ($coords->cropdim->w/2));
		$srcY = $coords->cropdim->y - ($coords->cropdim->h/2);
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
		$params =& $this->getParams();
		$bg = $params->get('fileupload_crop_bg', '#FFFFFF');
		$log = array();
		$log['coords'] = $coords;

		$imagedim = $coords->imagedim;
		$srcX = $coords->cropdim->x;
		$srcY = $coords->cropdim->y;
		$cropWidth = $coords->cropdim->w;
		$cropHeight = $coords->cropdim->h;
		$scale = (int)$coords->scale;
		//deprecaited (again lol)
		//from here replaces commented code below
		list($width, $height) = getimagesize($filepath);
		$log['rotate'] = array('path' => $filepath, 'dest' => $destCropFile, 'rotation' => $coords->rotation * -1);
		list($rotatedImgObject, $rotateWidth, $rotateHeight) = $oImage->rotate($filepath, $destCropFile, $coords->rotation * -1);

		$xx = $rotateWidth/2 - $width/2;
		$yy = $rotateHeight/2 - $height/2;
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
		$destwidth = $width * ((float)$scale/100);
		$destheight = $height * ((float)$scale/100);
		//create a file which resembles the crop interfaces image
		$log['scale'] = array('dest'=>$destCropFile, 'destX' => $destX, 'destY' => $destY, 'destWidth' => $destwidth, 'destHeight' => $destheight, 'sourceWidth'=>$width, 'sourceHeight'=>$height);
		imagecopyresampled($image_p, $image, $destX, $destY, 0, 0, $destwidth, $destheight, $width, $height);
		$oImage->imageToFile($destCropFile, $image_p);


		//finally take the cropper coordinates and crop the image
		$offsetX = ($imagedim->w < 400) ? (400 - $imagedim->w)/2 : 0;
		$offsetY = ($imagedim->h < 400) ? (400 - $imagedim->h)/2 : 0;

		$srcX = ($coords->cropdim->x - ($coords->cropdim->w/2)) - $offsetX ;
		$srcY = $coords->cropdim->y - ($coords->cropdim->h/2) - $offsetY;

		$log['crop2'] = array('dest'=>$destCropFile, 'startx' => $srcX, 'starty' => $srcY, 'crop width' => $cropWidth, 'cropHeight' => $cropHeight, 'cropx'=>0, 'cropy'=>0, 'bg'=>$bg);
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

		if ($isjoin) {
			$name = $this->getFullName(false, true, false);
			$joinid = $groupModel->getGroup()->join_id;
		} else {
			$name = $this->getFullName(true, true, false);
		}

		if ($this->processAjaxUploads($name)) {
			//stops form data being updated with blank data.
			return;
		}
		if ($this->crop($name))
		{
			//stops form data being updated with blank data.
			return;
		}
		$files = array();
		$deletedImages = JRequest::getVar('fabrik_fileupload_deletedfile', array(), 'request', 'array');
		$gid = $groupModel->getGroup()->id;

		$deletedImages = JArrayHelper::getValue($deletedImages, $gid, array());
		$imagesToKeep = array();

		for ($j = 0; $j < count($origData); $j++) {
			foreach ($origData[$j] as $key => $val) {
				if ($key == $name) {
					if (in_array($val, $deletedImages)) {
						unset($origData[$j]->$key);
					} else {
						$imagesToKeep[] = $origData[$j]->$key;
					}
				}
			}
		}

		if ($groupModel->canRepeat()) {
			if ($isjoin) {
				$fdata = $_FILES['join']['name'][$joinid][$name];
			} else {
				$fdata = $_FILES[$name]['name'];
			}

			foreach ($fdata as $i => $f) {
				if ($isjoin) {
					$myFileDir = (is_array($request['join'][$joinid][$name]) && array_key_exists($i, $request['join'][$joinid][$name])) ? $request['join'][$joinid][$name][$i] : '';
				} else {
					$myFileDir = (is_array($request[$name]) && array_key_exists($i, $request[$name])) ? $request[$name][$i] : '';
				}

				$file = array(
					'name' 			=> $isjoin ? $_FILES['join']['name'][$joinid][$name][$i] : $_FILES[$name]['name'][$i],
					'type' 			=> $isjoin ? $_FILES['join']['type'][$joinid][$name][$i] : $_FILES[$name]['type'][$i],
					'tmp_name' 	=> $isjoin ? $_FILES['join']['tmp_name'][$joinid][$name][$i] : $_FILES[$name]['tmp_name'][$i],
					'error' 		=> $isjoin ? $_FILES['join']['error'][$joinid][$name][$i] : $_FILES[$name]['error'][$i],
					'size' 			=> $isjoin ? $_FILES['join']['size'][$joinid][$name][$i] : $_FILES[$name]['size'][$i]
				);
				if ($file['name'] != '') {
					$files[$i] = $this->_processIndUpload($file, $myFileDir, $i);
				} else {
					$files[$i] = $imagesToKeep[$i];//$origData[$i]->$name;
				}
			}
			foreach($imagesToKeep as $k => $v) {
				if (!array_key_exists($k, $files)) {
					$files[$k] = $v;
				}
			}
		} else {
			if ($isjoin) {
				$myFileDir = $request['join'][$joinid][$name];
			} else {
				$myFileDir = JArrayHelper::getValue($request, $name);
			}

			$file = array(
					'name' 			=> $isjoin ? $_FILES['join']['name'][$joinid][$name] : $_FILES[$name]['name'],
					'type' 			=> $isjoin ? $_FILES['join']['type'][$joinid][$name] : $_FILES[$name]['type'],
					'tmp_name' 	=> $isjoin ? $_FILES['join']['tmp_name'][$joinid][$name] : $_FILES[$name]['tmp_name'],
					'error' 		=> $isjoin ? $_FILES['join']['error'][$joinid][$name] : $_FILES[$name]['error'],
					'size' 			=> $isjoin ? $_FILES['join']['size'][$joinid][$name] : $_FILES[$name]['size']
			);

			if ($file['name'] != '') {
				$files[] = $this->_processIndUpload($file, $myFileDir);
			} else {
				// $$$ hugh - fixing nasty bug where existing upload was getting wiped when editing an existing row and not uploading anything.
				// I think this should work.  if we're not in a repeat group, then it doesn't matter how many rows were in origData, and hence
				// how many rows are in $imagesToKeep ... if $imagesToKeep isn't empty, then we can assume a) it occurs at least once, and
				// b) there was at least one row in $origData
				if (!empty($imagesToKeep)) {
					$files[] = $origData[0]->$name;
				}
				/*
				// @todo not tested but commented code below produced warning as $i not set
				if (array_key_exists($name, $imagesToKeep)) {
					$files[$i] = $origData[$i]->$name;
				}
				*/
				/*if (array_key_exists($i, $imagesToKeep)) {
					$files[] = $imagesToKeep[$i];
					}*/
			}
		}
		$files = array_flip(array_flip($files));
		if ($params->get('upload_delete_image')) {
			foreach ($deletedImages as $filename) {
				$this->deleteFile($filename);
			}
		}
		// $$$ rob dont alter the request array as we should be inserting into the form models
		// ->_formData array using updateFormData();

		if ($isjoin) {
			if (!$groupModel->canRepeat()) {
				$files = $files[0];
			}
			$this->_form->updateFormData("join.{$joinid}.{$name}", $files);
			$this->_form->updateFormData("join.{$joinid}.{$name}_raw", $files);
		} else {
			$strfiles = json_encode($files);
			$this->_form->updateFormData($name . "_raw", $strfiles);
			$this->_form->updateFormData($name, $strfiles);
		}

	}

	/**
	 * delete the file
	 * @param $filename string file name (not including JPATH)
	 */

	function deleteFile($filename)
	{
		$storage = $this->getStorage();
		$file = $storage->clean(JPATH_SITE.DS.$filename);
		$thumb = $storage->clean($storage->_getThumb($filename));
		$cropped = $storage->clean($storage->_getCropped($filename));
		if ($storage->exists($file)) {
			$storage->delete($file);
		}
		if ($storage->exists($thumb)) {
			$storage->delete($thumb);
		} else {
			if ($storage->exists(JPATH_SITE.DS.$thumb)) {
				$storage->delete(JPATH_SITE.DS.$thumb);
			}
		}
		if ($storage->exists($cropped)) {
			$storage->delete($cropped);
		} else {
			if ($storage->exists(JPATH_SITE.DS.$cropped)) {
				$storage->delete(JPATH_SITE.DS.$cropped);
			}
		}
	}

	/**
	 * used in notempty validation rule
	 */

	function dataConsideredEmpty($data, $repeatCounter)
	{
		if ((int)JRequest::getVar('rowid', 0) !== 0) {
			if (JRequest::getVar('task') == '') {
				return parent::dataConsideredEmpty($data, $repeatCounter);
			}
			$olddaata = $this->getFormModel()->_origData[$repeatCounter];
			$name = $this->getFullName(false, true, false);
			return JArrayHelper::getValue(JArrayHelper::fromObject($olddaata), $name) === '' ? true : false;
		}
		$groupModel = $this->getGroup();
		if ($groupModel->isJoin()) {
			$name = $this->getFullName(false, true, false);
			$joinid = $groupModel->getGroup()->join_id;
			$joindata = JRequest::getVar('join', '', 'files', 'array', array());
			if (!array_key_exists('name', $joindata)) {
				return true;
			}
			$file = (array)$joindata['name'][$joinid][$name];
			return JArrayHelper::getValue($file, $repeatCounter, '') == '' ? true : false;
		} else {

			if ($this->isJoin()) {
				$join = $this->getJoinModel()->getJoin();
				$joinid = $join->id;
				$joindata = JRequest::getVar('join', '', 'post', 'array', array());
				$joindata = JArrayHelper::getValue($joindata, $joinid, array());
				$name = $this->getFullName(false, true, false);
				$joindata = JArrayHelper::getValue($joindata, $name, array());
				$joinids = JArrayHelper::getValue($joindata, 'id', array());
				return empty($joinids) ? true : false;
			} else {

			$name = $this->getFullName(true, true, false);
			$file = JRequest::getVar($name, '', 'files', 'array', array());
				if ($groupModel->canRepeat()) {
					return JArrayHelper::getValue($file['name'], $repeatCounter, '') == '' ? true : false;
				}
			}

		}
		if (!array_key_exists('name', $file)) {
			$file = JRequest::getVar($name);
			return $file == '' ? true : false;//ajax test - nothing in files
		}
		// no files selected?
		return $file['name'] == '' ? true : false;
	}

	/**
	 * process the upload (can be called via ajax from pluploader
	 * @access private
	 *
	 * @param array $file info
	 * @param string user selected upload folder
	 * @param int repeat group counter
	 * @return string location of uploaded file
	 */

	protected function _processIndUpload(&$file, $myFileDir = '', $repeatGroupCounter = 0)
	{
		$params = $this->getParams();
		$storage = $this->getStorage();
		// $$$ hugh - check if we need to blow away the cached filepath, set in validation
		$myFileName = $storage->cleanName($file['name'], $repeatGroupCounter);
		if ($myFileName != $file['name']) {
			$file['name'] = $myFileName;
			unset($this->_filePaths[$repeatGroupCounter]);
		}
		$tmpFile = $file['tmp_name'];
		$uploader = $this->getFormModel()->getUploader();

		if ($params->get('ul_file_types') == '') {
			$params->set('ul_file_types', implode(',', $this->_getAllowedExtension()));
		}
		$err = null;
		// Set FTP credentials, if given
		jimport('joomla.client.helper');
		JClientHelper::setCredentialsFromRequest('ftp');
		if ($myFileName == '') {
			return;
		}
		$filepath = $this->_getFilePath($repeatGroupCounter);
		if (!uploader::canUpload($file, $err, $params)) {
			$this->setError(100, $file['name'] .': '. JText::_($err));
		}

		if ($storage->exists($filepath)) {
			switch ($params->get('ul_file_increment', 0)) {
				case 0:
					break;
				case 1:
					$filepath = uploader::incrementFileName($filepath, $filepath, 1);
					break;
				case 2:
					$storage->delete($filepath);
					break;
			}
		}
		if (!$storage->upload($tmpFile, $filepath)) {
			$uploader->moveError = true;
			$this->setError(100, JText::sprintf('PLG_ELEMENT_FILEUPLOAD_UPLOAD_ERR', $tmpFile, $filepath));
			return;
		}
		$filepath = $storage->getUploadedFilePath();
		jimport('joomla.filesystem.path');
		$storage->setPermissions($filepath);

		// $$$ hugh @TODO - shouldn't we check to see if it's actually an image before we do any of this stuff???

		//resize main image
		$oImage = imageHelper::loadLib($params->get('image_library'));
		$oImage->setStorage($storage);
		// $$$ hugh - removing default of 200, otherwise we ALWAYS resize, whereas
		// tooltip on these options say 'leave blank for no resizing'
		$mainWidth 		= $params->get('fu_main_max_width', '');
		$mainHeight 	= $params->get('fu_main_max_height', '');

		if ($mainWidth != '' || $mainHeight != '') {
			// $$$ rob ensure that both values are integers otherwise resize fails
			if ($mainHeight == '') {
				$mainHeight = (int)$mainWidth;
			}
			if ($mainWidth == '') {
				$mainWidth = (int)$mainHeight;
			}
			$oImage->resize($mainWidth, $mainHeight, $filepath, $filepath);
		}
		// $$$ hugh - if it's a PDF, make sure option is set to attempt PDF thumb
		$make_thumbnail = $params->get('make_thumbnail') == '1' ? true : false;
		if (JFile::getExt($filepath) == 'pdf' && $params->get('fu_make_pdf_thumb', '0') == '0') {
			$make_thumbnail = false;
		}
		if ($make_thumbnail) {
			$thumbPath = $storage->clean(JPATH_SITE.DS.$params->get('thumb_dir').DS.$myFileDir.DS, false);
			$w = new FabrikWorker();
			$thumbPath = $w->parseMessageForPlaceHolder($thumbPath);
			$thumbPrefix 	= $params->get('thumb_prefix');
			$maxWidth 		= $params->get('thumb_max_width', 125);
			$maxHeight 		= $params->get('thumb_max_height', 125);

			if ($thumbPath != '') {
				if (!$storage->folderExists($thumbPath)) {
					if (!$storage->createFolder($thumbPath)) {
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
		if (!isset($this->storage)) {
			$params = $this->getParams();
			$storageType = JFilterInput::clean($params->get('fileupload_storage_type', 'filesystemstorage'), 'CMD');
			require_once(JPATH_ROOT.DS.'plugins'.DS.'fabrik_element'.DS.'fileupload'.DS.'adaptors'.DS.$storageType.'.php');
			$this->storage = new $storageType($params);
		}
		return $this->storage;
	}

	/**
	 * get the full server file path for the upload, including the file name i
	 * @param int repeat group counter
	 * @return string path
	 */

	function _getFilePath($repeatCounter = 0)
	{
		if (!isset($this->_filePaths)) {
			$this->_filePaths = array();
		}
		if (array_key_exists($repeatCounter, $this->_filePaths)) {
			return $this->_filePaths[$repeatCounter];
		}
		$aData = JRequest::get('post');
		$elName = $this->getFullName(true, true, false);
		$elNameRaw = $elName.'_raw';
		$params   = $this->getParams();
		//@TODO test with fileuploads in join groups

		$groupModel = $this->getGroup();

		if ($groupModel->isJoin()) {
			$joinid = $groupModel->getGroup()->join_id;

			$elNameNoJoinstr = $this->getFullName(false, true, false);
			if ($groupModel->canRepeat()) {
				//$myFileName = $_FILES['join']['name'][$joinid][$elNameNoJoinstr][$repeatCounter];
				$myFileName = array_key_exists('join', $_FILES) ? @$_FILES['join']['name'][$joinid][$elNameNoJoinstr][$repeatCounter] : @$_FILES['file']['name'];
				$myFileDir = JArrayHelper::getValue($aData['join'][$joinid][$elNameNoJoinstr], 'ul_end_dir', array());
				$myFileDir = JArrayHelper::getValue($myFileDir, $repeatCounter, '');
			} else {
				//$myFileName = $_FILES['join']['name'][$joinid][$elNameNoJoinstr];
				//@TODO test this:
				$myFileName = array_key_exists('join', $_FILES) ? @$_FILES['join']['name'][$joinid][$elNameNoJoinstr] : @$_FILES['file']['name'];
				$myFileDir = JArrayHelper::getValue($aData['join'][$joinid][$elNameNoJoinstr], 'ul_end_dir', '');
			}
		} else {
			if ($groupModel->canRepeat()) {
				//$myFileName   = @$_FILES[$elName]['name'][$repeatCounter];
				//@TODO test this:
				$myFileName   = array_key_exists($elName, $_FILES) ? @$_FILES[$elName]['name'][$repeatCounter] : @$_FILES['file']['name'];
				$myFileDir    = array_key_exists($elNameRaw, $aData) && is_array($aData[$elNameRaw] ) ? @$aData[$elNameRaw]['ul_end_dir'][$repeatCounter] : '';
			} else {
				$myFileName   = array_key_exists($elName, $_FILES) ? @$_FILES[$elName]['name'] : @$_FILES['file']['name'];
				$myFileDir    = array_key_exists($elNameRaw, $aData) && is_array($aData[$elNameRaw] ) ? @$aData[$elNameRaw]['ul_end_dir'] : '';

			}
		}

		$storage = $this->getStorage();
		// $$$ hugh - check if we need to blow away the cached filepath, set in validation
		$myFileName = $storage->cleanName($myFileName, $repeatCounter);

		$folder = $params->get('ul_directory');
		$folder  = $folder.DS.$myFileDir;
		$folder = JPath::clean(JPATH_SITE.DS.$folder);
		$w = new FabrikWorker();
		$folder = $w->parseMessageForPlaceHolder($folder);

		JPath::check($folder);
		$storage->makeRecursiveFolders($folder);
		$p = $folder . DS . $myFileName;
		$this->_filePaths[$repeatCounter] = JPath::clean($p);
		return $this->_filePaths[$repeatCounter];
	}

	/**
	 * draws the form element
	 * @param array data
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$this->_repeatGroupCounter = $repeatCounter;
		$id = $this->getHTMLId($repeatCounter);
		$name = $this->getHTMLName($repeatCounter);
		$groupModel = $this->getGroup();
		$element = $this->getElement();
		$params = $this->getParams();
		if ($element->hidden == '1') {
			return $this->getHiddenField($name, $data[$name], $id);
		}

		$str = '';
		$value = $this->getValue($data, $repeatCounter);
		$value = is_array($value) ? $value : FabrikWorker::JSONtoData($value, true);

		$value = $this->checkForSingleCropValue($value);
		if ($params->get('ajax_upload')) {
			$value = isset($value->file) ? $value->file : '';
		}
		$imagedata = array();
	/* 	if (strstr($value, GROUPSPLITTER)) {
			//crop stuff needs to be removed from data to get correct file path
			$imagedata = explode(GROUPSPLITTER, $value);
			// $$$ rob commented out as this was barfing in detailed view (not tested in edit view yet)
			//$value = $imagedata[0];

		} */
		$ulDir = $params->get('ul_directory');
		$storage = $this->getStorage();

		$formModel = $this->getFormModel();
		$formid = $formModel->get('id');

		$use_download_script = $params->get('fu_use_download_script', '0');
		if (!$this->_editable && ($use_download_script == FU_DOWNLOAD_SCRIPT_DETAIL || $use_download_script == FU_DOWNLOAD_SCRIPT_BOTH)) {
			return $this->downloadLink();
		}
		// $$$ rob - explode as it may be grouped data (if element is a repeating upload)
		$values = is_array($value) ? $value : FabrikWorker::JSONtoData($value, true);
		$render = new stdClass();
		$render->output = '';
		if (($params->get('fu_show_image') !== '0' && !$params->get('ajax_upload')) || !$this->_editable) {
			foreach ($values as $value) {

				$render = $this->loadElement($value);

				if ($value != '' && ($storage->exists(COM_FABRIK_BASE.$value) || substr($value, 0, 4) == 'http')) {
					$render->render($this, $params, $value);
				}
				if ($render->output != '') {
					$str .= $render->output;
				}
			}
		}
		if (!$this->_editable) {
			if ($render->output == '' && $params->get('default_image') != '') {
				$render->output = '<img src="'.$params->get('default_image').'" alt="image" />';
			}
		}
		if (!$this->_editable) {
			$str 	= '<div class="fabrikSubElementContainer">'.$str;
			$str .= '</div>';
			return $str;
		} else {
			// $$$ rob dont wrap readonly in subElementContainer as it stops dataConsideredEmpty() working when testing ro values

		}
		$str .= '<input class="fabrikinput" name="'.$name.'" type="file" id="'.$id.'" />'."\n";
		if ($params->get('upload_allow_folderselect') == '1') {
			$rDir	= JPATH_SITE.DS.$params->get('ul_directory');
			$folders = JFolder::folders($rDir);
			$str .= FabrikHelperHTML::folderAjaxSelect($folders);
			if ($groupModel->canRepeat()) {
				$ulname = FabrikString::rtrimword( $name, "[$repeatCounter]") . "[ul_end_dir][$repeatCounter]";
			} else {
				$ulname = $name.'[ul_end_dir]';
			}
			$str .= '<input name="'.$ulname.'" type="hidden" class="folderpath"/>';
		}

		if ($params->get('ajax_upload')) {
			$str = $render->output.$this->plupload($str, $repeatCounter, $values);
		}
		$str 	= '<div class="fabrikSubElementContainer">'.$str;
		$str .= '</div>';
		return $str;
	}

	protected function checkForSingleCropValue($value)
	{
		$params = $this->getParams();
		//if its a single upload crop element
		if ($params->get('ajax_upload') && $params->get('ajax_max', 4) == 1) {
			$singleCropImg = $value;
			if (empty($singleCropImg)) {
				$value = '';
			} else {
				$singleCropImg = $singleCropImg[0];
			}
		}
		return $value;
	}

	protected function downloadLink($value)
	{
		$params = $this->getParams();
		$storage = $this->getStorage();
		$formModel = $this->getFormModel();
		if (empty($value) || !$storage->exists(COM_FABRIK_BASE.$value)) {
			return "";
		}
		$aclEl = str_replace('.', '___', $params->get('fu_download_acl', ''));
		if (!empty($aclEl)) {
			$canDownload = in_array($data[$aclEl], JFactory::getUser()->authorisedLevels());
			if (!$canDownload) {
				$img = $params->get('fu_download_noaccess_image');
				return $img == '' ? '' : "<img src=\"images/stories/$img\" alt=\"".JText::_('DOWNLOAD NO PERMISSION')."\" />";
			}
		}

		$rowid = JRequest::getVar('rowid', '0');
		$elementid = $this->_id;
		$title = basename($value);
		if ($params->get('fu_title_element') == '') {
			$title_name = $this->getFullName(true, true, false ) . '__title';
		} else {
			$title_name = str_replace('.', '___', $params->get('fu_title_element'));
		}
		//$title_name .= '_raw'; //Jaanus: see 406
		if (is_array($formModel->_data)) {
			if (array_key_exists($title_name, $formModel->_data)) {
				if (!empty($formModel->_data[$title_name])) {
					$title = $formModel->_data[$title_name];
					$titles = FabrikWorker::JSONtoData($title, true);
					$title = JArrayHelper::getValue($titles, $repeatCounter, $title);
				}
			}
		}
		if ($params->get('fu_download_access_image') !== '') {
			$title = "<img src=\"images/stories/".$params->get('fu_download_access_image')."\" alt=\"$title\" />";
		}
		$link = COM_FABRIK_LIVESITE . "index.php?option=com_fabrik&task=plugin.pluginAjax&plugin=fileupload&method=ajax_download&element_id=$elementid&formid=$formid&rowid=$rowid&repeatcount=$repeatCounter";
		$url = "<a href=\"$link\">$title</a>";
		return $url;
	}

	/**
	 * @depreciated
	 * load the required plupload runtime engines
	 * @param string $runtimes
	 */

	protected function pluploadLRuntimes($runtimes)
	{
		return ;
		FabrikHelperHTML::script('plugins/fabrik_element/fileupload/lib/plupload/js/plupload.min.js');
		FabrikHelperHTML::script('plugins/fabrik_element/fileupload/lib/plupload/js/plupload.js');

		if(strstr($runtimes, 'html5')) {
			FabrikHelperHTML::script('plugins/fabrik_element/fileupload/lib/plupload/js/plupload.html5.min.js');
		}
		if(strstr($runtimes, 'html4')) {
			FabrikHelperHTML::script('components/com_fabrik/plugins/element/fabrikfileupload/lib/plupload/js/plupload.html4.min.js');
		}
		if(strstr($runtimes, 'gears')) {
			FabrikHelperHTML::script('plugins/fabrik_element/fileupload/lib/plupload/js/gears_init.js');
			FabrikHelperHTML::script('plugins/fabrik_element/fileupload/lib/plupload/js/plupload.gears.min.js');
		}
		if(strstr($runtimes, 'flash')) {
			FabrikHelperHTML::script('plugins/element/fileupload/lib/plupload/js/plupload.flash.min.js');
		}
		if(strstr($runtimes, 'silverlight')) {
			FabrikHelperHTML::script('plugins/fabrik_element/fileupload/lib/plupload/js/plupload.silverlight.min.js');
		}
		if(strstr($runtimes, 'browserplus')) {
			FabrikHelperHTML::script('plugins/fabrik_element/fileupload/lib/plupload/js/plupload.browserplus.min.js');
		}
	}

	/**
	 * Create the html plupload widget plus css
	 * @param string current html output
	 * @param int repeat group counter
	 * @param array existing files
	 * @return modified fileupload html
	 */

	protected function plupload($str, $repeatCounter, $values)
	{
		FabrikHelperHTML::stylesheet(COM_FABRIK_LIVESITE.'media/com_fabrik/css/slider.css');
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		$runtimes = $params->get('ajax_runtime', 'html5');
		//$this->pluploadLRuntimes($runtimes);

		$pstr = '<!-- UPLOAD CONTAINER -->
		<div id="'.$id.'-widgetcontainer">';


		
			$pstr .= '
		<canvas id="'.$id.'-widget" width="400" height="400"></canvas>';
			if ($params->get('fileupload_crop', 0)) {
			$pstr .= '
<div class="zoom" style="float:left;margin-top:10px;padding-right:10x;width:200px">
zoom:
	<div class="fabrikslider-line" style="width: 100px;float:left;">
		<div class="knob"></div>
	</div>
	<input name="zoom-val" value="" size="3" />
</div>
<div class="rotate" style="float:left;margin-top:10px;width:200px">'.JText::_('PLG_ELEMENT_FILEUPLOAD_ROTATE').':
	<div class="fabrikslider-line" style="width: 100px;float:left;">
		<div class="knob"></div>
	</div>
	<input name="rotate-val" value="" size="3" />

</div>';
			}
			$pstr .= '
<div  style="text-align: right;float:right;margin-top:10px; width: 205px">
	<input type="button" class="button" name="close-crop" value="'.JText::_('CLOSE').'" />
	</div>
</div>';
	
		$pstr .= '

		<div class="plupload_container fabrikHide" id="'.$id.'_container">
			<div class="plupload">
				<div class="plupload_header">
					<div class="plupload_header_content">
						<div class="plupload_header_title">'.JText::_('PLG_ELEMENT_FILEUPLOAD_PLUP_HEADING').'</div>
						<div class="plupload_header_text">'.JText::_('PLG_ELEMENT_FILEUPLOAD_PLUP_SUB_HEADING').'</div>
					</div>
				</div>
				<div class="plupload_content">
					<div class="plupload_filelist_header">
						<div class="plupload_file_name">'.JText::_('PLG_ELEMENT_FILEUPLOAD_FILENAME').'</div>
					<div class="plupload_file_action">&nbsp;</div>
					<div class="plupload_file_status"><span>'.JText::_('PLG_ELEMENT_FILEUPLOAD_STATUS').'</span></div>
					<div class="plupload_file_size">'.JText::_('PLG_ELEMENT_FILEUPLOAD_SIZE').'</div>
					<div class="plupload_clearer">&nbsp;</div>
				</div>
				<ul class="plupload_filelist" id="'.$id.'_dropList">

				</ul>
				<div class="plupload_filelist_footer">
					<div class="plupload_file_name">
						<div class="plupload_buttons">
							<a id="'.$id.'_browseButton" class="plupload_button plupload_add" href="#">'.JText::_('PLG_ELEMENT_FILEUPLOAD_ADD_FILES').'</a>
							<a class="plupload_button plupload_start plupload_disabled" href="#">'.JText::_('PLG_ELEMENT_FILEUPLOAD_START_UPLOAD').'</a>
						</div>
						<span class="plupload_upload_status"></span>
					</div>
					<div class="plupload_file_action"></div>
					<div class="plupload_file_status">
						<span class="plupload_total_status">0%</span>
					</div>
					<div class="plupload_file_size">
						<span class="plupload_total_file_size">0 b</span>
					</div>
					<div class="plupload_progress">
						<div class="plupload_progress_container">
							<div class="plupload_progress_bar"></div>
						</div>
					</div>
					<div class="plupload_clearer">&nbsp;</div>
				</div>
			</div>
		</div>
		</div>

<!-- FALLBACK; SHOULD LOADING OF PLUPLOAD FAIL -->
<div class="plupload_fallback">'.JText::_('PLG_ELEMENT_FILEUPLOAD_FALLBACK_MESSAGE').'
   <br />
   '. $str.'
</div>';

		$w = (int)$params->get('ajax_dropbox_width', 300);
		$h = (int)$params->get('ajax_dropbox_hight', 200);
		FabrikHelperHTML::stylesheet(COM_FABRIK_LIVESITE.'plugins/fabrik_element/fileupload/lib/plupload/css/plupload.queue.css');
		FabrikHelperHTML::addStyleDeclaration(".dropList{background:#aaa; width:".$w."px; height:".$h."px;}");
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
		error_reporting(0);
		error_reporting(E_ERROR | E_PARSE);
		$this->_id = JRequest::getInt('element_id');
		$groupModel = $this->getGroup();
		$isjoin = $groupModel->isJoin();
		if ($isjoin) {
			$name = $this->getFullName(false, true, false);
			$joinid = $groupModel->getGroup()->join_id;
		} else {
			$name = $this->getFullName(true, true, false);
		}

		// Get parameters
		$chunk = JRequest::getInt('chunk', 0);
		$chunks = JRequest::getInt('chunks', 0);
		$fileName = JRequest::getVar('name', '');

		if ($chunk + 1 < $chunks){
			return;
		}

		require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'uploader.php');
		//@TODO test in join
		$o = new stdClass();
		if (array_key_exists('file', $_FILES) || array_keys_exists('join', $_FILES)) {
			$file = array(
						'name' 			=> $isjoin ? $_FILES['join']['name'][$joinid] : $_FILES['file']['name'],
						'type' 			=> $isjoin ? $_FILES['join']['type'][$joinid] : $_FILES['file']['type'],
						'tmp_name' 	=> $isjoin ? $_FILES['join']['tmp_name'][$joinid] : $_FILES['file']['tmp_name'],
						'error' 		=> $isjoin ? $_FILES['join']['error'][$joinid] : $_FILES['file']['error'],
						'size' 			=> $isjoin ? $_FILES['join']['size'][$joinid] : $_FILES['file']['size']
			);

			$filepath = $this->_processIndUpload($file, '', 0);
			$uri = $this->getStorage()->pathToURL($filepath);

			$o->filepath = $filepath;
			$o->uri = $uri;
		} else {
			$o->filepath = null;
			$o->uri = null;
		}
		echo json_encode($o);
		return;
	}

	/**
	 * (non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelElement::getFieldDescription()
	 */

	function getFieldDescription()
	{
		$p = $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		return "TEXT";
	}

	/**
	 * attach documents to the email
	 * @param string data
	 * @return string formatted value
	 */

	function addEmailAttachement($data)
	{
		/// @TODO: check what happens here with open base_dir in effect //
		$params = $this->getParams();

		if ($params->get('ul_email_file')) {
			$config	=& JFactory::getConfig();
			if (empty($data)) {
				$data = $params->get('default_image');
			}
			if (strstr($data, JPATH_SITE)) {
				$p = str_replace(COM_FABRIK_LIVESITE , JPATH_SITE, $data);
			} else {
				$p = JPATH_SITE . DS . $data;
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
	 * @param string $val
	 * @param string view form or table
	 * @return string modified val
	 * @TODO: base the returned string completely on the params specified for the element
	 * e.g. thumbnail, show image, link etc
	 */

	function modifyJoinQuery($val, $view='form')
	{
		$params = $this->getParams();
		if (!$params->get('fu_show_image', 0) && $view == 'form') {
			return $val;
		}
		if ($params->get('make_thumbnail')) {
			$ulDir = JPath::clean($params->get('ul_directory')) . DS;
			$ulDir = str_replace("\\", "\\\\", $ulDir);
			$thumbDir = $params->get('thumb_dir');
			$thumbDir = JPath::clean($params->get('thumb_dir')) . DS;
			$w = new FabrikWorker();
			$thumbDir = $w->parseMessageForPlaceHolder($thumbDir);
			$thumbDir = str_replace("\\", "\\\\", $thumbDir);

			$w = new FabrikWorker();
			$thumbDir = $w->parseMessageForPlaceHolder($thumbDir);

			$thumbDir .= $params->get('thumb_prefix');

			$str = "CONCAT('<img src=\"".COM_FABRIK_LIVESITE."',".
			"REPLACE(".
 			"REPLACE($val, '$ulDir', '".$thumbDir."')".	//replace the main image dir with thumb dir
			", '\\\', '/')".														//replace the backslashes with forward slashes
			", '\" alt=\"database join image\" />')";

		} else {
			$str = " REPLACE(CONCAT('<img src=\"".COM_FABRIK_LIVESITE. "' , $val, '\" alt=\"database join image\"/>'), '\\\', '/') ";
		}
		return $str;
	}

	/**
	 * trigger called when a row is deleted
	 * @param array grouped data of rows to delete
	 */

	function onDeleteRows($groups)
	{
		//cant delete files from unpublished elements
		if (!$this->canUse()) {
			return;
		}
		$db = $this->getListModel()->getDb();
		$storage = $this->getStorage();
		require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'uploader.php');
		$params = $this->getParams();
		if ($params->get('upload_delete_image')) {
			jimport('joomla.filesystem.file');
			$elName = $this->getFullName(false, true, false);
			$name = $this->getElement()->name;
			foreach ($groups as $rows) {
				foreach ($rows as $row) {
					if (array_key_exists($elName."_raw", $row)) {
						if ($this->isJoin()) {
							$join = $this->getJoinModel()->getJoin();
							$db->setQuery("SELECT * FROM ".$db->nameQuote($join->table_join)." WHERE ".$db->nameQuote('parent_id')." = ".$db->Quote($row->__pk_val));
							$imageRows = $db->loadObjectList('id');
							if (!empty($imageRows)) {
								foreach ($imageRows as $imageRow) {
									$this->deleteFile($imageRow->$name);
								}
								$db->setQuery("DELETE FROM ".$db->nameQuote($join->table_join)." WHERE ".$db->nameQuote('id')." IN (".implode(", ", array_keys($imageRows)).")");
								$db->query();
							}
						} else {
							$files = explode(GROUPSPLITTER, $row->{$elName."_raw"});
							foreach ($files as $filename) {
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
		$last = strtolower(substr($val, -1));
		if ($last == 'g')
		$val = $val*1024*1024*1024;
		if ($last == 'm')
		$val = $val*1024*1024;
		if ($last == 'k')
		$val = $val*1024;
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
	 * used to format the data when shown in the form's email
	 * @param string
	 * @return string formatted value
	 */

	protected function _getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		$params = $this->getParams();
		if ($params->get('fu_show_image_in_email', false)) {
			$storage = $this->getStorage();
			$render = $this->loadElement($value);
			if ($params->get('fu_show_image')  != '0') {
				if ($value != '' && $storage->exists(COM_FABRIK_BASE.$value)) {
					$render->render($this, $params, $value);
				}
			}
			if ($render->output == '' && $params->get('default_image') != '') {
				$render->output = "<img src=\"{$params->get('default_image')}\" alt=\"image\" />";
			}
			return $render->output;
		} else {
			return $storage->preRenderPath($value);
		}
	}

	function getROValue($data, $repeatCounter = 0)
	{
		$v = $this->getValue($data, $repeatCounter);
		$storage = $this->getStorage();
		return $storage->pathToURL($v);
	}

	/* not really an AJAX call, we just use the pluginAjax method so we can run this
	 * method for handling scripted downloads.
	 */
	function onAjax_download()
	{
		$app = JFactory::getApplication();
		$url = JRequest::getVar('HTTP_REFERER', '', 'server');
		$lang = &JFactory::getLanguage();
		$lang->load('com_fabrik.plg.element.fabrikfileupload', JPATH_ADMINISTRATOR);
		if (!$this->canView()) {
			$app->enqueueMessage(JText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD_NO_PERMISSION'));
			$app->redirect($url);
			exit;
		}
		$rowid = JRequest::getInt('rowid', 0);
		if (empty($rowid)) {
			$app->enqueueMessage(JText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD_NO_SUCH_FILE'));
			$app->redirect($url);
			exit;
		}
		$repeatcount = JRequest::getInt('repeatcount', 0);
		$listModel = $this->getListModel();
		$row = $listModel->getRow($rowid, false);
		if (empty($row)) {
			$app->enqueueMessage(JText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD_NO_SUCH_FILE'));
			$app->redirect($url);
			exit;
		}
		//$element = $this->getElement();
		$storage = $this->getStorage();
		$elName = $this->getFullName(false, true, false);
		//$elName .= '_raw';
		$filepath = $row->$elName;
		//$filepath = explode(GROUPSPLITTER,$filepath);
		$filepath = FabrikWorker::JSONtoData($filepath, true);
		$filepath = JArrayHelper::getValue($filepath, $repeatcount);
		$filepath = $storage->getFullPath($filepath);
		$filecontent = $storage->read($filepath);
		if ($filecontent !== false) {
			/*
			 // $$$ hugh - turn of E_DEPRECATED to avoid warnings about eregi() in getid3
			 $current_level = error_reporting();
			 error_reporting($current_level & ~E_DEPRECATED);

			 require_once(COM_FABRIK_FRONTEND.DS.'libs'.DS.'getid3'.DS.'getid3'.DS.'getid3.php');
			 require_once(COM_FABRIK_FRONTEND.DS.'libs'.DS.'getid3'.DS.'getid3'.DS.'getid3.lib.php');

			 getid3_lib::IncludeDependency(COM_FABRIK_FRONTEND.DS.'libs'.DS.'getid3'.DS.'getid3'.DS.'extension.cache.mysql.php', __FILE__, true);
			 $config =& JFactory::getConfig();
			 $host =  $config->getValue('host');
			 $database = $config->getValue('db');
			 $username = $config->getValue('user');
			 $password = $config->getValue('password');
			 $getID3 = new getID3_cached_mysql($host, $database, $username, $password);
			 // Analyze file and store returned data in $ThisFileInfo
			 $thisFileInfo = $getID3->analyze($filepath);
			 */
			$thisFileInfo = $storage->getFileInfo($filepath);
			if ($thisFileInfo === false) {
				$app->enqueueMessage( JText::_('DOWNLOAD NO SUCH FILE'));
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
			header('Content-Disposition: attachment; filename=' . $thisFileInfo['filename']);

			// ... serve up the image ...
			echo $filecontent;

			$this->downloadEmail($row, $filepath);
			$this->downloadHit($rowid, $repeatcount);
			$this->downloadLog($row, $filepath);
			// ... and we're done.
			exit();
		}
		else {
			$app->enqueueMessage(JText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD_NO_SUCH_FILE'));
			$app->redirect($url);
			exit;
		}
	}

	function downloadHit($rowid, $repeatCount = 0)
	{
		// $$$ hugh @TODO - make this work for repeats and/or joins!
		$params = $this->getParams();
		if ($hit_counter = $params->get('fu_download_hit_counter','')) {
			JError::setErrorHandling(E_ALL, 'ignore');
			$listModel = $this->getListModel();
			$pk = $listModel->getTable()->db_primary_key;
			$fabrikDb = $listModel->getDb();
			list($table_name,$element_name) = explode('.', $hit_counter);
			$sql = "UPDATE $table_name SET $element_name = COALESCE($element_name,0) + 1 WHERE $pk = ".$fabrikDb->Quote($rowid);
			$fabrikDb->setQuery($sql);
			$fabrikDb->query();
		}
	}

	function downloadEmail( &$row, $filepath)
	{
		$params = $this->getParams();
		$email_to = $params->get('fu_download_email', '');
		if (!empty($email_to)) {
			JError::setErrorHandling(E_ALL, 'ignore');
			jimport('joomla.mail.helper');
			$w = new FabrikWorker();
			$email_to = $w->parseMessageForPlaceholder($email_to, JArrayHelper::fromObject($row), false);
			$config = JFactory::getConfig();
			$from = $config->getValue('mailfrom');
			$fromname = $config->getValue('fromname');
			$msg = JText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD_EMAIL_MSG') . "<br />\n";
			$msg .= JText::_('PLG_ELEMENT_FILEUPLOAD_FILENAME') . ': ' . $filepath . "<br />\n";
			$user = JFactory::getUser();
			$msg .= JText::_('PLG_ELEMENT_FILEUPLOAD_BY') . ': ' . ($user->get('id') == 0 ? 'guest' : $user->get('username')) . "<br />\n";
			$msg .= JText::_('PLG_ELEMENT_FILEUPLOAD_FROM') . ': ' . JRequest::getVar('REMOTE_ADDR','','server') . "<br />\n";
			$msg .= JText::_('PLG_ELEMENT_FILEUPLOAD_ON') . ': ' . date(DATE_RFC822) . "<br />\n";
			$subject = JText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD_EMAIL_SUBJECT') . ' :: ' . $config->getValue('sitename');
			foreach (explode(',', $email_to) as $to) {
				$res = JUtility::sendMail( $from, $fromname, $to, $subject, $msg, true);
			}
		}
	}

	/**
	 * log the download
	 * @since 2.0.5
	 * @param string $filepath
	 */

	function downloadLog(&$row, $filepath)
	{
		$params =& $this->getParams();
		if ((int)$params->get('fu_download_log', 0)) {
			JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables');
			$log =& JTable::getInstance('log', 'Table');
			$log->message_type = 'fabrik.fileupload.download';
			$user = JFactory::getUser();
			$msg = new stdClass();
			$msg->file = $filepath;
			$msg->userid = $user->get('id');
			$msg->username = $user->get('username');
			$msg->email = $user->get('email');
			$log->referring_url = JRequest::getVar('REMOTE_ADDR','','server');
			$log->message = json_encode($msg);
			$log->store();
		}
	}

	/**
	* called when save as copy form button clicked
	* @param mixed value to copy into new record
	* @return mixed value to copy into new record
	*/

	public function onSaveAsCopy($val)
	{
		if (empty($val)) {
			$isjoin = $groupModel->isJoin();
			$origData = $this->_form->getOrigData();
			$groupModel =& $this->getGroup();
			if ($isjoin) {
				$name = $this->getFullName(false, true, false);
				$joinid = $groupModel->getGroup()->join_id;
			} else {
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
		$this->deleteFile($filename);
		$db = $this->getListModel()->getDb();
		$sql = "DELETE FROM ".$db->nameQuote($join->table_join)." WHERE ".$db->nameQuote('id')." =".JRequest::getInt('recordid');
		$db->setQuery($sql);
		$db->query();
	}
}
?>