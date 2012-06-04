<?php
/**
 * Plugin element to render two fields to capture a link (url/label)
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class plgFabrik_ElementLink extends plgFabrik_Element
{

	var $hasSubElements = true;

	protected $fieldDesc = 'TEXT';

	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderListData($data, $oAllRowsData)
	{
		$listModel = $this->getlistModel();
		$params = $this->getParams();
		$target = $params->get('link_target', '');
		$smart_link = $params->get('link_smart_link', false);
		if ($listModel->getOutPutFormat() != 'rss' && ($smart_link || $target == 'mediabox')) {
			FabrikHelperHTML::slimbox();
		}
		$data = FabrikWorker::JSONtoData($data, true);

		if (!empty($data)) {
			if (array_key_exists('label', $data)) {
				$data = (array)$this->_renderListData($data, $oAllRowsData);
			} else {
				for ($i = 0; $i < count($data); $i++) {
					$data[$i] = JArrayHelper::fromObject($data[$i]);
					$data[$i] = $this->_renderListData($data[$i], $oAllRowsData);
				}
			}
		}
		$data = json_encode($data);
		return parent::renderListData($data, $oAllRowsData);
	}

	/**
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	protected function _renderListData($data, $oAllRowsData)
	{
		if (is_string($data)) {
			$data = FabrikWorker::JSONtoData($data, true);
		}
		$listModel = $this->getlistModel();
		$params = $this->getParams();
		if (is_array($data)) {
			if (count($data) == 1) {
				$data['label'] = JArrayHelper::getValue($data, 'link');
			}
			$_lnk = trim($data['link']);
			$_lbl = trim($data['label']);
			if (strtolower($_lnk) == 'http://' || strtolower($_lnk) == 'https://') {
				$_lnk = ''; //threat some default values as empty
			}
			$target = $params->get('link_target', '');
			if ($listModel->getOutPutFormat() != 'rss') {
				$link = '';
				if (empty($_lbl))
					$_lbl = $_lnk; //if label is empty, set as a copy of the link
				if ((!empty($_lbl)) && (!empty($_lnk))) {
					$smart_link = $params->get('link_smart_link', false);
					if ($smart_link || $target == 'mediabox') {
						$smarts = $this->_getSmartLinkType($_lnk);
						$link = '<a href="' . $_lnk . '" rel="lightbox[' . $smarts['type'] . ' ' . $smarts['width'] . ' ' . $smarts['height'] . ']">' . $_lbl . '</a>';
					}
					else {
						$link = '<a href="' . $_lnk . '" target="' . $target . '">' . $_lbl . '</a>';
					}
				}
			} else {
				$link = $_lnk;
			}
			$w = new FabrikWorker();
			$link = $listModel->parseMessageForRowHolder($link, JArrayHelper::fromObject($oAllRowsData));
			return $link;
		}
		return $data;
	}

	/**
	 * draws the form element
	 * @param array form data
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		$bits = $this->inputProperties($repeatCounter);
		$value = $this->getValue($data, $repeatCounter);
		if ($value == "")
		{
			$value = array('label'=>'', 'link'=>'');
		}
		else
		{
			if (!is_array($value))
			{
				$value = FabrikWorker::JSONtoData($value, true);
				if (array_key_exists(0, $value))
				{
					$value['label'] = $value[0];
				}
			}
		}

		if (count($value) == 0)
		{
			$value = array('label' => '', 'link' => '');
		}

		//if (JRequest::getVar('rowid') == 0)
		if (FabrikWorker::getMenuOrRequestVar('rowid') == 0)
		{
			$value['link'] = $params->get('link_default_url');
		}
		if (!$this->_editable)
		{
			$_lbl = trim($value['label']);
			$_lnk = trim($value['link']);
			$w = new FabrikWorker();
			$_lnk = is_array($data) ? $w->parseMessageForPlaceHolder($_lnk, $data) : $w->parseMessageForPlaceHolder($_lnk);
			if (empty($_lnk) || strtolower($_lnk) == 'http://' || strtolower($_lnk) == 'https://')
				return ''; //don't return empty links
			$target = $params->get('link_target', '');
			$smart_link = $params->get('link_smart_link', false);
			if (empty($_lbl))
				$_lbl = $_lnk; //if label is empty, set as a copy of the link
			if ($smart_link || $target == 'mediabox')
			{
				$smarts = $this->_getSmartLinkType($_lnk);
				return '<a href="' . $_lnk . '" rel="lightbox['.$smarts['type'] . ' ' . $smarts['width'] . ' ' . $smarts['height'] . ']">' . $_lbl . '</a>';
			}
			return '<a href="' . $_lnk . '" target="' . $target . '">' . $_lbl . '</a>';
		}

		$labelname = FabrikString::rtrimword( $name, "[]").'[label]';
		$linkname = FabrikString::rtrimword( $name, "[]").'[link]';

		$html = array();
		$bits['name'] = $labelname;
		$bits['placeholder'] = JText::_('PLG_ELEMENT_LINK_LABEL');
		$bits['value'] = $value['label'];
		$bits['class'] .= ' fabrikSubElement';
		unset($bits['id']);

		$html[] = '<div class="fabrikSubElementContainer" id="'.$id.'">';
		$html[] = $this->buildInput('input', $bits);
		$bits['placeholder'] = JText::_('PLG_ELEMENT_LINK_URL');
		$bits['name'] = $linkname;
		$bits['value'] = JArrayHelper::getValue($value, 'link');
		$html[] = $this->buildInput('input', $bits);
		$html[] = '</div>';
		return implode("\n", $html);
	}

	/**
	 * used to format the data when shown in the form's email
	 * @param mixed element's data
	 * @return string formatted value
	 */

	protected function _getEmailValue($value)
	{
		if (is_string($value)) {
			$value = FabrikWorker::JSONtoData($value, true);
			$value['label'] = JArrayHelper::getValue($value, 0);
			$value['link'] = JArrayHelper::getValue($value, 1);
		}
		if (is_array($value)) {
			$w = new FabrikWorker();
			$link 	= $w->parseMessageForPlaceHolder($value['link']);
			$value = '<a href="'.$link.'" >'.$value['label'].'</a>';
		}
		return $value;
	}

	/**
	 * manupulates posted form data for insertion into database
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat($val, $data)
	{
		// $$$ hugh - added 'normalization' of links, to add http:// if no :// in the link.
		// not sure if we really want to do it here, or only when rendering?
		// $$$ hugh - quit normalizing links.
		$return = '';
		$params = $this->getParams();
		if (is_array($val)) {
			if ($params->get('use_bitly')) {
				require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'libs'.DS.'bitly'.DS.'bitly.php');
				$login = $params->get('bitly_login');
				$key = $params->get('bitly_apikey');
				$bitly = new bitly($login, $key);
			}
			foreach ($val as $key => &$v) {
				if (is_array($v)) {

					if ($params->get('use_bitly')) {
						// bitly will return an error if you try and shorten a shortened link,
						// and the class file we are using doesn't check for this
						if (!strstr($v['link'],'bit.ly/') && $v['link'] !== '') {
							$v['link'] = $bitly->shorten($v['link']);
						}
					}
					/*$return .= implode(GROUPSPLITTER2, $v);
					$return .= GROUPSPLITTER;*/

				} else {
					if ($key == 'link') {
						$v = FabrikString::encodeurl($v);
					}
					// not in repeat group
					if($key == 'link' && $params->get('use_bitly')) {
						if (!strstr($v,'bit.ly/') && $v !== '') {
							$v = $bitly->shorten($v);
						}
					}
				}
			}
		} else {
			if (json_decode($val))
			{
				return $val;
			}
			$return = $val;
		}
		$return = json_encode($val);
		return $return;
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$listModel = $this->getlistModel();
		$params = $this->getParams();
		$target = $params->get('link_target', '');
		$smart_link = $params->get('link_smart_link', false);
		if ($listModel->getOutPutFormat() != 'rss' && ($smart_link || $target == 'mediabox')) {
			FabrikHelperHTML::slimbox();
		}
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts = json_encode($opts);
		return "new FbLink('$id', $opts)";
	}

	/**
	 *
	 * @param array $value, previously encrypted values
	 * @param array data
	 * @param int repeat group counter
	 * @return null
	 */

	function getValuesToEncrypt(&$values, $data, $c)
	{
		$data = (array)json_decode($this->getValue($data, $c, true));
		$name = $this->getFullName(false, true, false);
		$group = $this->getGroup();
		if ($group->canRepeat()) {
			// $$$ rob - I've not actually tested this bit!
			if (!array_key_exists($name, $values)) {
				$values[$name]['data']['label'] = array();
				$values[$name]['data']['link'] = array();
			}
			$values[$name]['data']['label'][$c] = $data[0];
			$values[$name]['data']['link'][$c] = $data[1];
		} else {
			$values[$name]['data']['label'] = $data[0];
			$values[$name]['data']['link'] = $data[1];
		}
	}

	/**
	 * this really does get just the default value (as defined in the element's settings)
	 * @param array data to use as parsemessage for placeholder
	 * @return unknown}_type
	 */

	function getDefaultValue($data = array())
	{
		if (!isset($this->_default)) {
			$w = new FabrikWorker();
			$params = $this->getParams();
			$link = $params->get('link_default_url');
			// $$$ hugh - no idea what this was here for, but it was causing some BIZARRE bugs!
			//$formdata = $this->getForm()->getData();
			// $$$ rob only parse for place holder if we can use the element
			// otherwise for encrypted values store raw, and they are parsed when the
			// form in processsed in form::addEncrytedVarsToArray();
			if ($this->canUse()) {
				$link = $w->parseMessageForPlaceHolder($link, $data);
			}
			$element = $this->getElement();
			$default = $w->parseMessageForPlaceHolder($element->default, $data);
			if ($element->eval == "1") {
				$default = @eval(stripslashes($default));
			}
			$this->_default = array('label'=>$default, 'link'=>$link);
		}
		return $this->_default;
	}

	/**
	 * can be overwritten by plugin class
	 * determines the value for the element in the form view
	 * @param	array	data
	 * @param	int		when repeating joinded groups we need to know what part of the array to access
	 * @param	array	options
	 * @return	string	default value
	 */

	function getValue($data, $repeatCounter = 0, $opts = array())
	{
		if (!isset($this->defaults)) {
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults)) {
			$groupModel = $this->getGroup();
			$group = $groupModel->getGroup();
			$joinid = $group->join_id;
			$formModel = $this->getFormModel();
			$element = $this->getElement();
			// $$$rob - if no search form data submitted for the search element then the default
			// selection was being applied instead
			if (array_key_exists('use_default', $opts) && $opts['use_default'] == false) {
				$default = '';
			} else {
				$default = $this->getDefaultValue($data);
			}

			$name = $this->getFullName(false, true, false);

			if ($groupModel->isJoin()) {
				if ($groupModel->canRepeat()) {
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists($name, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name])) {
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
							$a = json_decode($data[$name]);
						}
						$default = JArrayHelper::getValue($a, $repeatCounter, $default);
					}

				} else {
					if (array_key_exists($name, $data)) {
						$default = JArrayHelper::getValue($data, $name);
					}
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
				//$element->default = implode(GROUPSPLITTER2, $element->default);
				$element->default = json_encode($element->default);
			}
			$this->defaults[$repeatCounter] = $element->default;

		}
		return $this->defaults[$repeatCounter];
	}

	/**
	 * @param string url
	 * @return string url
	 */

	protected function _getSmartLinkType($link) {
		/* $$$ hugh - not really sure how much of this is necessary, like setting different widths
		 * and heights for different social video sites. I copied the numbers from the examples page
		 * for mediabox: http://iaian7.com/webcode/mediaboxAdvanced
		 */
		$ret = array (
			'width' => '800',
			'height' => '600',
			'type' => 'mediabox'
			);
			if (preg_match('#^http://([\w\.]+)/#',$link,$matches)) {
				$site = $matches[1];
				// @TODO should probably make this a little more intelligent, like optional www,
				// and check for site specific spoor in the URL (like '/videoplay' for google,
				// '/photos' for flicker, etc).
				switch ($site) {
					case 'www.flickr.com':
						$ret['width'] = '400';
						$ret['height'] = '300';
						$ret['type'] = 'social';
						break;
					case 'video.google.com':
						$ret['width'] = '640';
						$ret['height'] = '400';
						$ret['type'] = 'social';
						break;
					case 'www.metacafe.com':
						$ret['width'] = '400';
						$ret['height'] = '350';
						$ret['type'] = 'social';
						break;
					case 'vids.myspace.com':
						$ret['width'] = '430';
						$ret['height'] = '346';
						$ret['type'] = 'social';
						break;
					case 'myspacetv.com':
						$ret['width'] = '430';
						$ret['height'] = '346';
						$ret['type'] = 'social';
						break;
					case 'www.revver.com':
						$ret['width'] = '480';
						$ret['height'] = '392';
						$ret['type'] = 'social';
						break;
					case 'www.seesmic.com':
						$ret['width'] = '425';
						$ret['height'] = '353';
						$ret['type'] = 'social';
						break;
					case 'www.youtube.com':
						$ret['width'] = '480';
						$ret['height'] = '380';
						$ret['type'] = 'social';
						break;
					case 'www.veoh.com':
						$ret['width'] = '540';
						$ret['height'] = '438';
						$ret['type'] = 'social';
						break;
					case 'www.viddler.com':
						$ret['width'] = '437';
						$ret['height'] = '370';
						$ret['type'] = 'social';
						break;
					case 'vimeo.com':
						$ret['width'] = '400';
						$ret['height'] = '302';
						$ret['type'] = 'social';
						break;
					case '12seconds.tv':
						$ret['width'] = '431';
						$ret['height'] = '359';
						$ret['type'] = 'social';
						break;
				}
				if ($ret['type'] == 'mediabox') {
					$ext = strtolower(JFile::getExt($link));
					switch ($ext) {
						case 'swf':
						case 'flv':
						case 'mp4':
							$ret['width'] = '640';
							$ret['height'] = '360';
							$ret['type'] = 'flash';
							break;
						case 'mp3':
							$ret['width'] = '400';
							$ret['height'] = '20';
							$ret['type'] = 'audio';
							break;
					}
				}
			}
			return $ret;
	}

	/**
	 * Examples of where this would be overwritten include drop downs whos "please select" value might be "-1"
	 * @param string data posted from form to check
	 * @param int repeat group counter
	 * @return bol if data is considered empty then returns true
	 */

	function dataConsideredEmpty($data, $repeatCounter)
	{
		$data = strip_tags($data);
		if (trim($data) == '' || $data == '<a target="_self" href=""></a>') {
			return true;
		}
		return false;
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
		//whilst link isnt really an element list we can use its js AddNewEvent method
		$elementList = 'media/com_fabrik/js/elementlist.js';
		if (!in_array($elementList, $srcs)) {
			$srcs[] = $elementList;
		}
		parent::formJavascriptClass($srcs, $script);
	}

}
?>