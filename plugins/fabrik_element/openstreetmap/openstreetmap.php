<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.openstreetmap
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Plugin element to render openstreet map
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.openstreetmap
 */

class plgFabrik_ElementOpenstreetmap extends plgFabrik_Element
{

	/**
	 * (non-PHPdoc)
	 * @see plgFabrik_Element::renderListData()
	 */

	public function renderListData($data, &$thisRow)
	{
		$str = '';
		$params = $this->getParams();
		$w = $params->get('fb_gm_table_mapwidth');
		$h = $params->get('fb_gm_table_mapheight');
		$z = $params->get('fb_gm_table_zoomlevel');
		$data = FabrikWorker::JSONtoData($data, true);
		foreach ($data as $d)
		{
			$str .= $this->_staticMap($d, $w, $h, $z);
		}
		return $str;
	}

	/**
	 * format the data as a microformat
	 * @param	string	$data
	 * @return	string	micro formatted data
	 */

	function _microformat($data)
	{
		$o = $this->_strToCoords($data, 0);
		if ($data != '')
		{
			$data = "<div class=\"geo\">
			<span class=\"latitude\">{$o->coords[0]}</span>,
			<span class=\"longitude\">{$o->coords[1]}</span>
			</div>
			";
		}
		return $data;
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass(&$srcs, $script = '')
	{
		static $jsloaded;
		if (!isset($jsloaded))
		{
			$document = JFactory::getDocument();
			$params = $this->getParams();

			$document->addScript("http://www.openlayers.org/api/OpenLayers.js");
			parent::formJavascriptClass($srcs);
			FabrikHelperHTML::script('components/com_fabrik/libs/openlayers/openlayers_ext.js');

			if ($params->get('fb_osm_virtualearthlayers'))
			{
				$document->addScript('http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1');
			}

			if ($params->get('fb_osm_gmlayers'))
			{
				$src = "http://maps.google.com/maps?file=api&amp;v=2&amp;key=" . $params->get('fb_osm_gm_key');
				$document->addScript($src);
			}

			if ($params->get('fb_osm_yahoolayers'))
			{
				$yahooid = $params->get('fb_yahoo_key');
				$document->addScript('http://api.maps.yahoo.com/ajaxymap?v=3.8&appid=' . $yahooid);
			}
			$document->addScript('http://www.openstreetmap.org/openlayers/OpenStreetMap.js');
			$jsloaded = true;
		}
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
		$element = $this->getElement();
		$data = $this->_form->_data;
		$v = $this->getValue($data, $repeatCounter);
		$zoomlevel = $params->get('fb_gm_zoomlevel');
		$o = $this->_strToCoords($v, $zoomlevel);

		$layers = new stdClass;
		$layers->virtualEarth = $params->get('fb_osm_virtualearthlayers');
		$layers->yahoo = $params->get('fb_osm_yahoolayers');
		$layers->google = $params->get('fb_osm_gmlayers');

		$opts = $this->getElementJSOptions($repeatCounter);

		$opts->lon = $o->coords[0];
		$opts->lat = $o->coords[1];
		$opts->zoomlevel = $o->zoomlevel;

		$opts->layers = $layers;

		$opts->control = $params->get('fb_osm_mapcontrol');
		$opts->scalecontrol = $params->get('fb_osm_scalecontrol');
		$opts->maptypecontrol = $params->get('fb_osm_maptypecontrol');
		$opts->overviewcontrol = $params->get('fb_osm_overviewcontrol');
		$opts->drag = ($this->getFormModel()->editable) ? true : false;
		$opts->staticmap = $this->_useStaticMap() ? true : false;
		$opts->maptype = $params->get('fb_osm_maptype');
		$opts->key = $params->get('fb_osm_key');
		$opts->defaultLayer = $params->get('fb_osm_defaultlayer');
		$opts = json_encode($opts);
		return "new FbOpenStreetMap('$id', $opts)";
	}

	/**
	 * determine if we use a google static ma
	 * Option has to be turned on and element un-editable
	 *
	 * @return bool
	 */

	function _useStaticMap()
	{
		static $usestatic;
		if (!isset($usestatic))
		{
			$params = $this->getParams();
			//requires you to have installed the pda plugin
			//http://joomup.com/blog/2007/10/20/pdaplugin-joomla-15/
			if (array_key_exists('ispda', $GLOBALS) && $GLOBALS['ispda'] == 1)
			{
				$usestatic = true;
			}
			else
			{
				$usestatic = ($params->get('fb_osm_staticmap') && !$this->_editable);
			}
		}
		return $usestatic;
	}

	/**
	 * util function to turn the saved string into coordinate array
	 *@param string coordinates
	 * @param int default zoom level
	 * @return object coords array and zoomlevel int
	 */

	function _strToCoords($v, $zoomlevel = 0)
	{
		$o = new stdClass;
		$o->coords = array('', '');
		$o->zoomlevel = (int) $zoomlevel;
		if (strstr($v, ","))
		{
			$ar = explode(":", $v);
			$o->zoomlevel = count($ar) == 2 ? array_pop($ar) : 4;
			$v = FabrikString::ltrimword($ar[0], "(");
			$v = rtrim($v, ")");
			$o->coords = explode(",", $v);
		}
		else
		{
			$o->coords = array(0, 0);
		}
		return $o;
	}

	/**
	 * @access private
	 * get a static map
	 *
	 * @param string coordinates
	 * @param int width
	 * @param int height
	 * @param int zoom level
	 * @param int $repeatCounter
	 * @return string static map html
	 */

	function _staticMap($v, $w = null, $h = null, $z = null, $repeatCounter = 0)
	{
		static $eljsloaded;
		if (!isset($eljsloaded))
		{
			$eljsloaded = true;
			FabrikHelperHTML::script('media/com_fabrik/js/element.js');
		}
		$this->formJavascriptClass();
		$id = $this->getHTMLId($repeatCounter) . uniqid();
		$params = $this->getParams();
		if (is_null($w))
		{
			$w = $params->get('fb_osm_table_mapwidth');
		}
		if (is_null($h))
		{
			$h = $params->get('fb_osm_table_mapheight');
		}
		if (is_null($z))
		{
			$z = $params->get('fb_osm_table_zoomlevel');
		}
		$k = $params->get('fb_osm_key');

		$o = $this->_strToCoords($v, $z);
		$str = "<div id=\"{$id}\" style=\"width:{$w}px;height:{$h}px\" class=\"gmStaticMap\">";
		$str .= "<div id=\"{$id}_map\" style=\"width:{$w}px;height:{$h}px\"></div></div>";

		$layers = new stdClass;
		$layers->virtualEarth = $params->get('fb_osm_virtualearthlayers');
		$layers->yahoo = $params->get('fb_osm_yahoolayers');
		$layers->google = $params->get('fb_osm_gmlayers');

		$opts = $this->getElementJSOptions($repeatCounter);

		$opts->lon = $o->coords[0];
		$opts->lat = $o->coords[1];
		$opts->zoomlevel = $z;

		$opts->layers = $layers;

		$opts->control = $params->get('fb_osm_mapcontrol');
		$opts->scalecontrol = $params->get('fb_osm_scalecontrol');
		$opts->maptypecontrol = $params->get('fb_osm_maptypecontrol');
		$opts->overviewcontrol = $params->get('fb_osm_overviewcontrol');
		$opts->drag = ($this->_form->_editable) ? true : false;
		$opts->staticmap = $this->_useStaticMap() ? true : false;
		$opts->maptype = $params->get('fb_osm_maptype');
		$opts->key = $params->get('fb_osm_key');
		$opts->defaultLayer = $params->get('fb_osm_defaultlayer');
		$opts = json_encode($opts);
		FabrikHelperHTML::addScriptDeclaration("head.ready(function() {new FbOpenStreetMap('$id', $opts);});");
		return $str;
	}
	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		require_once(COM_FABRIK_FRONTEND . DS . 'libs' . DS . 'mobileuseragent' . DS . 'mobileuseragent.php');
		require_once(COM_FABRIK_FRONTEND . DS . 'helpers' . DS . 'string.php');
		$ua = new MobileUserAgent();
		$id = $this->getHTMLId($repeatCounter);
		$name = $this->getHTMLName($repeatCounter);
		$groupModel = $this->_group;
		$element = $this->getElement();
		$val = $element->default;

		$params = &$this->getParams();
		$w = $params->get('fb_osm_mapwidth');
		$h = $params->get('fb_osm_mapheight');
		if ($this->_useStaticMap())
		{
			return $this->_staticMap($val, null, null, null, $repeatCounter);
		}
		else
		{
			$val = (array_key_exists($name, $data)) ? $data[$name] : $val;
			if ($element->hidden == '1')
			{
				return $this->getHiddenField($name, $data[$name], $id);
			}
			$str = '';
			//if its not editable and theres no val don't show the map
			if ((!$this->_editable && $val != '') || $this->_editable)
			{
				$str = "<div id=\"" . $id . "_map\" style=\"width:{$w}px; height:{$h}px\"></div>";
				$str .= "<input type='hidden' name='$name' id='" . $id . "' value='$val'/>";
			}
			else
			{
				$str .= JText::_('No location selected');
			}
			if (!$this->_editable)
			{
				$str .= $this->_microformat($val);
			}
			return $str;
		}
	}

	/**
	 * Create the SQL select 'name AS alias' segment for list/form queries
	 *
	 * @param   array  &$aFields    array of element names
	 * @param   array  &$aAsFields  array of 'name AS alias' fields
	 * @param   array  $opts        options
	 *
	 * @return  void
	 */

	public function getAsField_html(&$aFields, &$aAsFields, $opts = array())
	{
		$db = FabrikWorker::getDbo();
		$dbtable = $this->actualTableName();
		$listModel = $this->getlistModel();
		$table = &$listModel->getTable();
		$fullElName = JArrayHelper::getValue($opts, 'alias', "$dbtable" . "___" . $this->_element->name);
		$str = FabrikString::safeColName($fullElName) . " AS " . $db->quoteName($fullElName);
		if ($table->db_primary_key == $fullElName)
		{
			array_unshift($aFields, $fullElName);
			array_unshift($aAsFields, $fullElName);
		}
		else
		{
			$aFields[] = $str;
			$aAsFields[] = $fullElName;
			$aFields[] = $db->quoteName($dbtable) . '.' . $db->quoteName($this->_element->name) . ' AS ' . $db->quoteName($fullElName . '_raw');
			$aAsFields[] = $db->quoteName($fullElName . '_raw');
		}
	}

	/**
	 * this really does get just the default value (as defined in the element's settings)
	 * @return unknown_type
	 */

	function getDefaultValue($data = array())
	{
		if (!isset($this->_default))
		{
			$params = &$this->getParams();
			$this->_default = $params->get('fb_osm_lat') . ',' . $params->get('fb_osm_long') . ':' . $params->get('fb_osm_zoomlevel');
		}
		return $this->_default;
	}

	/**
	 * determines the value for the element in the form view
	 * @param array data
	 * @param int when repeating joinded groups we need to know what part of the array to access
	 * @param array options
	 * @return string value
	 */

	function getValue($data, $repeatCounter = 0, $opts = array())
	{
		if (is_null($this->defaults))
		{
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults))
		{
			$name = $this->getHTMLName($repeatCounter);
			$groupModel = $this->_group;
			$formModel = $this->_form;
			$element = $this->getElement();
			$listModel = $this->getlistModel();
			$params = $this->getParams();

			// $$$rob - if no search form data submitted for the search element then the default
			// selection was being applied instead
			if (array_key_exists('use_default', $opts) && $opts['use_default'] == false)
			{
				$value = '';
			}
			else
			{
				$value = $this->getDefaultValue($data);
			}

			$table = $listModel->getTable();
			if ($groupModel->canRepeat() == '1')
			{
				$fullName = $table->db_table_name . $formModel->joinTableElementStep . $element->name;
				if (isset($data[$fullName]))
				{
					if (is_array($data[$fullName]))
					{
						$value = $data[$fullName][0];
					}
					else
					{
						$value = $data[$fullName];
					}
					//$value = explode(GROUPSPLITTER, $value);
					$value = FabrikWorker::JSONtoData($value, true);
					if (is_array($value) && array_key_exists($repeatCounter, $value))
					{
						$value = $value[$repeatCounter];
						if (is_array($value))
						{
							$value = implode(',', $value);
						}
						if ($value === '')
						{ //query string for joined data
							$value = JArrayHelper::getValue($data, $name);
						}
						return $value;
					}
				}
			}
			if ($groupModel->isJoin())
			{
				$fullName = $this->getFullName(false, true, false);
				if (isset($data[$fullName]))
				{
					$value = $data[$fullName];
					if (is_array($value) && array_key_exists($repeatCounter, $value))
					{
						$value = $value[$repeatCounter];
					}
				}
			}
			else
			{
				$fullName = $table->db_table_name . $formModel->joinTableElementStep . $element->name;
				if (isset($data[$fullName]))
				{
					/* drop down  */
					if (is_array($data[$fullName]))
					{

						if (isset($data[$fullName][0]))
						{
							/* if not its a file upload el */
							$value = $data[$fullName][0];
						}
					}
					else
					{
						$value = $data[$fullName];
					}
				}
			}

			/** ensure that the data is a string **/
			//stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1)
			{
				FabrikWorker::getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
			}
			if (is_array($value))
			{
				$value = implode(',', $value);
			}
			if ($value === '')
			{ //query string for joined data
				$value = JArrayHelper::getValue($data, $name);
			}
			$this->defaults[$repeatCounter] = $value;
		}
		return $this->defaults[$repeatCounter];
	}
}
?>