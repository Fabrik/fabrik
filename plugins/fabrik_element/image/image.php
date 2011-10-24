<?php
/**
 * Plugin element to render image
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class plgFabrik_ElementImage extends plgFabrik_Element
{

	var $ignoreFolders = array('cache', 'lib', 'install', 'modules', 'themes', 'upgrade', 'locks', 'smarty', 'tmp');

	protected $fieldDesc = 'TEXT';

	/**
	 * this really does get just the default value (as defined in the element's settings)
	 * @return unknown_type
	 */

	function getDefaultValue($data = array())
	{
		if (!isset($this->_default)) {
			$params 		=& $this->getParams();
			$element = $this->getElement();
			$w = new FabrikWorker();
			$this->_default	 	= $params->get('imagefile');
			// $$$ hugh - this gets us the default image, with the root folder prepended.
			// But ... if the root folder option is set, we need to strip it.
			$rootFolder = $params->get('selectImage_root_folder', '/');
			$rootFolder = ltrim($rootFolder,'/');
			$this->_default = preg_replace("#^$rootFolder#",'',$this->_default);
			$this->_default = $w->parseMessageForPlaceHolder($this->_default, $data);
			if ($element->eval == "1") {
				$this->_default = @eval(stripslashes($this->_default));
				FabrikWorker::logEval($this->_default, 'Caught exception on eval in '.$element->name.'::getDefaultValue() : %s');
			}
		}
		return $this->_default;
	}

	/**
	 * get default value
	 *
	 * @param array $data
	 * @param int $repeatCounter
	 * @param array options
	 * @return string
	 */

	function getValue($data, $repeatCounter = 0, $opts = array())
	{
		if (is_null($this->defaults)) {
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults)) {
			$groupModel = $this->_group;
			$group			=& $groupModel->getGroup();
			$joinid			= $group->join_id;
			$formModel 	=& $this->getForm();
			$element		= $this->getElement();
			$params 		=& $this->getParams();
			// $$$rob - if no search form data submitted for the search element then the default
			// selection was being applied instead
			if (array_key_exists('use_default', $opts) && $opts['use_default'] == false) {
				$default = '';
			} else {
				$default   = $this->getDefaultValue($data);
			}

			$name = $this->getFullName(false, true, false);

			if ($groupModel->isJoin()) {
				if ($groupModel->canRepeat()) {
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) &&  array_key_exists($name, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name])) {
						$default = $data['join'][$joinid][$name][$repeatCounter];
					}
				} else {
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists($name, $data['join'][$joinid])) {
						$default = $data['join'][$joinid][$name];
					}
				}
			} else {
				if ($groupModel->canRepeat()) {
					//repeat group NO join
					if (array_key_exists($name, $data)) {
						if (is_array($data[$name])) {
							//occurs on form submission for fields at least
							$a = $data[$name];
						} else {
							//occurs when getting from the db
							//$a = 	explode(GROUPSPLITTER, $data[$name]);
							$a = json_decode($data[$name], true);
						}
						$default = JArrayHelper::getValue($a, $repeatCounter, $default);
					}

				} else {
					$default = JArrayHelper::getValue($data, $name, $default);
				}
			}
			if ($default === '') { //query string for joined data
				$default = JArrayHelper::getValue($data, $name);
			}
			$element->default = $default;
			//stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1) {
				FabrikWorker::getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
			}
			if (is_array($element->default)) {
				$element->default = implode(',', $element->default);
			}
			$this->defaults[$repeatCounter] = $element->default;

		}
		return $this->defaults[$repeatCounter];
	}

	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderListData($data, $oAllRowsData)
	{
		$data = json_decode($data, true);
		$params = $this->getParams();
		if ($data === '' || empty($data)) {
			//no data so default to image.
			$data = $params->get('imagepath');
			if (!strstr($data, '/')) {
				//single file specified so find it in tmpl folder
				$data = FabrikHelperHTML::image($data, 'list', @$this->tmpl, array(), false);
			}
		}
		$selectImage_root_folder = $params->get('selectImage_root_folder', '');
		// $$$ hugh - tidy up a bit so we don't have so many ///'s in the URL's
		$selectImage_root_folder = ltrim($selectImage_root_folder, '/');
		$selectImage_root_folder = rtrim($selectImage_root_folder, '/');
		$showImage = $params->get('show_image_in_table', 0);
		$linkURL = $params->get('link_url', '');
		if ($data[0] == '') {
			$data[] = $params->get('imagefile');
		}
		for ($i=0; $i <count($data); $i++) {
			if ($showImage) {
				// $$$ rob 30/06/2011 - say if we import via csv a url to the image check that and use that rather than the relative path
				$src = substr($data[$i], 0, 4) == 'http' ? $data[$i] : COM_FABRIK_LIVESITE .'images/' . $selectImage_root_folder . '/' . $data[$i];
				$data[$i] = "<img src=\"$src\" alt=\"$data[$i]\" />";
			}
			if ($linkURL) {
				$data[$i] = "<a href=\"$linkURL\" target=\"_blank\">" . $data[$i] . "</a>";
			}
		}
		//$data = implode(GROUPSPLITTER, $data);
		$data = json_encode($data);
		return parent::renderListData($data, $oAllRowsData);
	}

	/**
	 * formats the posted data for insertion into the database
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat($val, $data)
	{
		$groupModel = $this->getGroup();
		$params = $this->getParams();
		$selectImage_root_folder = $params->get('selectImage_root_folder', '');

		$key = $this->getFullName(false, true, false);
		if (!array_key_exists($key, $data)) {
			$element = $this->getElement();
			$key = $element->name;
		}

		if ($groupModel->canRepeat() && !$groupModel->isJoin()) {
			if ($groupModel->isJoin()) {
				// @TODO - not tested with join group data
			}
			if (!array_key_exists($key.'_folder', $data)) {
				//$retval = implode(GROUPSPLITTER , $data[$key]);
				$retval = json_encode( $data[$key]);
			} else {
				$retvals = array();
				foreach ($data[$key] as $k => $v) {
					$retvals[] = preg_replace("#^$selectImage_root_folder#", '', $data[$key . '_folder'][$k]) . $data[$key . '_image'][$k];
				}
				//$retval = implode(GROUPSPLITTER , $retvals);
				$retval = json_encode($retvals);
			}
		}
		else {

			// $$$ hugh - if we're using default image, no user selection,
			// the _folder and _image won't exist,
			// we'll just have the relative path in the element $key
			if (!array_key_exists($key.'_image', $data)) {
				$retval = $data[$key];
			}
			else {
				//$retval = preg_replace("#^$selectImage_root_folder#", '', $data[$key]) . $data[$key . '_image'];
				$retval = preg_replace("#^$selectImage_root_folder#", '', $data[$key]);
			}
		}
		return $retval;
	}

	/**
	 * shows the data formatted for RSS export
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderListData_rss($data, $oAllRowsData)
	{
		$params = $this->getParams();
		$selectImage_root_folder = $params->get('selectImage_root_folder', '');
		return "<img src='" . COM_FABRIK_LIVESITE  . 'images/' . $selectImage_root_folder . '/'. $data . "' />";
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$params 			=& $this->getParams();
		$name 				= $this->getHTMLName($repeatCounter);
		$value 				= $this->getValue($data, $repeatCounter);
		$id 					= $this->getHTMLId($repeatCounter);
		$rootFolder 	= $params->get('selectImage_root_folder');
		// $$$ hugh - tidy up a bit so we don't have so many ///'s in the URL's
		$rootFolder = ltrim($rootFolder, '/');
		$rootFolder = rtrim($rootFolder, '/');
		// $$$ rob - 30/062011 allow for full urls in the image. (e.g from csv import)
		$defaultImage = substr($value, 0, 4) == 'http' ? $value : COM_FABRIK_LIVESITE . 'images/'.$rootFolder.'/'.$value;
		// $$$ rob - 30/06/2011 can only select an image if its not a remote image
		$canSelect 		= ($params->get('image_front_end_select', '0') && substr($value, 0, 4) !== 'http');
		$float        = $params->get('image_float');
		$float = $float != '' ? "style='float:$float;'" : '';
		$str = "<div class='fabrikSubElementContainer' id='$id'>";

		$rootFolder = str_replace('/', DS, $rootFolder);
		if ($canSelect && $this->_editable) {
			$str .= '<img src="' . $defaultImage . '" alt="'. $value .'" '.$float.' class="imagedisplayor"/>'."\n";
			if (array_key_exists($name, $data)) {
				if (trim($value) == '') {
					$path = "/";
				} else {
					$bits = explode("/", $value);
					if (count($bits) > 1) {
						$path = DS . array_shift($bits) . DS;
						$path = $rootFolder . $path;
						$val = array_shift($bits);
					} else {
						$path = $rootFolder;
					}
				}
			} else {
				$path = $rootFolder;
			}

			$fullpath = JPATH_SITE.DS.'images';

			$images = array();
			$imagenames = (array)JFolder::files($fullpath.DS.$path);
			foreach ($imagenames as $n) {
				$images[] = JHTML::_('select.option', $n, $n);
			}
			// $$$rob not sure about his name since we are adding $repeatCounter to getHTMLName();
			$imageName = $this->_group->canRepeat() ? FabrikString::rtrimWord( $name, "][$repeatCounter]") . "_image][$repeatCounter]" : $id . '_image';
			$image = array_pop( explode('/', $value));
			// $$$ hugh - append $rootFolder to $fullpath, otherwise we're showing folders
			// they aren't supposed to be able to see.
			$folders = JFolder::folders($fullpath.DS.$rootFolder);
			// @TODO - if $folders is empty, hide the button/widget?  All they can do is select
			// from the initial image dropdown list, so no point having the widget for changing folder?
			$str  .=	"<br/>" .	JHTML::_('select.genericlist', $images, $imageName, 'class="inputbox imageselector" ', 'value', 'text', $image);
			$str .= FabrikHelperHTML::folderAjaxSelect($folders);
			$str 	.= "<input type=\"hidden\" name=\"$name\" value=\"$value\" class=\"fabrikinput hiddenimagepath folderpath\" />";
		} else {
			$linkURL = $params->get('link_url', '');
			$imgstr = '<img src="'.$defaultImage.'" alt="'.$value.'" '.$float.' class="imagedisplayor"/>'."\n";
			if ($linkURL) {
				$imgstr = "<a href=\"$linkURL\" target=\"_blank\">$imgstr</a>";
			}
			$str .= $imgstr;
			$str .= "<input type=\"hidden\" name=\"$name\" value=\"$value\" class=\"fabrikinput hiddenimagepath folderpath\" />";
		}
		$str .= "</div>";
		return $str;
	}

	function onAjax_files()
	{
		$folder = JRequest::getVar('folder');
		$pathA = JPath::clean(JPATH_SITE.DS.$folder);
		$folder = array();
		$files = array();
		$images = array();
		FabrikWorker::readImages( $pathA, "/", $folders, $images, $this->ignoreFolders);
		if (!array_key_exists('/',$images)) {
			$images['/'] = array();
		}
		echo json_encode($images['/']);
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance.
	 */

	function elementJavascript($repeatCounter)
	{
		$app = JFactory::getApplication();
		$params = $this->getParams();
		$element = $this->getElement();
		$id = $this->getHTMLId($repeatCounter);
		$selRoot = COM_FABRIK_LIVESITE.'images/'.$params->get('selectImage_root_folder', '');
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->rootPath = 'images/'.$params->get('selectImage_root_folder', '');
		$opts->canSelect = $params->get('image_front_end_select', false);
		$opts->id = $element->id;
		$opts->ds = DS;
		$opts->dir = JPATH_SITE.DS.str_replace('/', DS, $opts->rootPath);
		$opts = json_encode($opts);
		return "new FbImage('$id', $opts)";
	}

	/**
	 *
	 */

	function getAdminLists(&$lists)
	{

		/**
		 * IMPORTANT NOTE FOR HACKERS!
		 * 	if your images folder contains massive sub directories which you dont want fabrik
		 * accessing (and hance slowing down to a crawl the loading of this page)
		 * then put the folders in the $ignoreFolders array
		 */
		$params = $this->getParams();
		$images 	= array();
		$folders 	= array();
		$path 		= $params->get('imagepath', '/');
		$file 		= $params->get('imagefile');
		$fullpath = JPATH_SITE . '/images';
		$folders[] = JHTML::_('select.option', '/', '/');
		FabrikWorker::readImages($fullpath, "/", $folders, $images, $this->ignoreFolders);
		$lists['folders'] =	JHTML::_('select.genericlist',  $folders, 'params[imagepath]', 'class="inputbox" size="1" ', 'value', 'text', $path);
		$javascript	= "onchange=\"previewImage()\" onfocus=\"previewImage()\"";
		$is = JArrayHelper::getValue($images, $path, array());
		$lists['imagefiles'] = JHTML::_('select.genericlist', $is, 'params[imagefile]', 'class="inputbox" size="10" multiple="multiple" '. $javascript , 'value', 'text', $file);
		$defRootFolder = $params->get('selectImage_root_folder', '');
		$lists['selectImage_root_folder'] = JHTML::_('select.genericlist',  $folders, 'params[selectImage_root_folder]', "class=\"inputbox\"  size=\"1\" ", 'value', 'text', $defRootFolder);
	}

	/**
	 *
	 */

	function renderAdminSettings(&$lists)
	{
		return;
		$params = $this->getParams();
		$pluginParams = $this->getPluginParams();
		$this->getAdminLists( $lists);
		?>
<script language="javascript" type="text/javascript">
			/* <![CDATA[ */
			function setImageName() {
				var image = document.adminForm.imagefiles;
				var linkurl = document.getElementsByName('params[image_path]')[0];
				linkurl.value =  (image).get('value');
			}

			function previewImage() {
				var root = '<?php echo COM_FABRIK_LIVESITE;?>';
				var file = $('paramsimagefile').get('value');
				var folder = $('paramsimagepath').get('value');
				$('view_imagefiles').src = root + "images/"  + file;
			}

			head.ready(function() {
			$('paramsimagepath').addEvent('change', function(e) {
				var event = new Event(e);
				event.stop;
				var folder = '<?php echo 'images' . $params->get('selectImage_root_folder', ''); ?>' + $(event.target).getValue();
				var url = '<?php echo COM_FABRIK_LIVESITE;?>index.php?option=com_fabrik&format=raw&view=plugin&task=pluginAjax&g=element&plugin=image&method=ajax_files';
				var myAjax = new Request({url:url, method:'post',
			'data':{'folder':folder},
			onComplete: function(r) {
				var opts = eval(r);
				var folder = '<?php echo $params->get('selectImage_root_folder', ''); ?>' + $(event.target).getValue();
				$('paramsimagefile').empty()
				opts.each( function(opt) {
					$('paramsimagefile').adopt(
						new Element('option', {'value':folder + opt.value}).appendText(opt.text)
					);
				}.bind(this));
				previewImage();
			}.bind(this)
		}).request();

			});
			 previewImage();
			});
			/* ]]> */
		</script>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none">
<table class="admintable">
	<tr>
		<td class="paramlist_key"><?php echo JText::_('Default image'); ?></td>
		<td><?php echo $lists['folders'];echo "<br />\n" . $lists['imagefiles']; ?>
		<img name="view_imagefiles" id="view_imagefiles" src="<?php echo COM_FABRIK_LIVESITE . 'images/'. $params->get('image_path');?>" width="100" alt="view imagefiles"/> <br />
		</td>
	</tr>
	<tr>
		<td class="paramlist_key"><?php echo JText::_('Root folder');?>:</td>
		<td><?php echo $lists['selectImage_root_folder'];?></td>
	</tr>
</table>
		<?php echo $pluginParams->render();?></div>
		<?php
	}

	/**
	 * used to format the data when shown in the form's email
	 * @param string
	 * @param array form records data
	 * @param int repeat group counter
	 * @return string formatted value
	 */

	function getEmailValue($value, $data, $c)
	{
		return $this->render($data);
	}
}
?>