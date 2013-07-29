<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.googlemap
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';
require_once JPATH_SITE . '/components/com_fabrik/helpers/googlemap.php';

/**
 * Plugin element to render a Google map
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.googlemap
 * @since       3.0
 */

class PlgFabrik_ElementGooglemap extends PlgFabrik_Element
{

	protected static $geoJs = null;

	protected static $radiusJs = null;

	protected static $usestatic = null;

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
		$listModel = $this->getListModel();
		$params = $this->getParams();
		$w = (int) $params->get('fb_gm_table_mapwidth');
		$h = (int) $params->get('fb_gm_table_mapheight');
		$z = (int) $params->get('fb_gm_table_zoomlevel');
		$data = FabrikWorker::JSONtoData($data, true);
		foreach ($data as $i => &$d)
		{
			if ($params->get('fb_gm_staticmap_tableview'))
			{
				$d = $this->_staticMap($d, $w, $h, $z, $i, true, JArrayHelper::fromObject($thisRow));
			}
			if ($params->get('icon_folder') == '1')
			{
				// $$$ rob was returning here but that stoped us being able to use links and icons together
				$d = $this->replaceWithIcons($d, 'list', $listModel->getTmpl());
			}
			else
			{
				if (!$params->get('fb_gm_staticmap_tableview'))
				{
					$d = $params->get('fb_gm_staticmap_tableview_type_coords', 'num') == 'dms' ? $this->_dmsformat($d) : $this->_microformat($d);
				}
			}
			$d = $this->rollover($d, $thisRow, 'list');
			$d = $listModel->_addLink($d, $this, $thisRow, $i);
		}
		return $this->renderListDataFinal($data);
	}

	/**
	 * Render RSS feed format
	 *
	 * @param   string  $data      Elements data
	 * @param   object  &$thisRow  All the data in the lists current row
	 *
	 * @return  string	formatted value
	 */

	public function renderListData_feed($data, &$thisRow)
	{
		$str = '';
		$data = FabrikWorker::JSONtoData($data, true);
		foreach ($data as $d)
		{
			$str .= $this->_georss($d);
		}
		return $str;
	}

	/**
	 * Format the data as a georss
	 *
	 * @param   string  $data  Data
	 *
	 * @return string html microformat markup
	 */

	protected function _georss($data)
	{
		if (strstr($data, '<georss:point>'))
		{
			return $data;
		}
		$o = $this->_strToCoords($data, 0);
		if ($data != '')
		{
			$lon = trim($o->coords[1]);
			$lat = trim($o->coords[0]);
			$data = "<georss:point>{$lat},{$lon}</georss:point>";
		}
		return $data;
	}

	/**
	 * Format the data as a microformat
	 *
	 * @param   string  $data  Data
	 *
	 * @return string html microformat markup
	 */

	protected function _microformat($data)
	{
		$o = $this->_strToCoords($data, 0);
		$str = array();
		if ($data != '')
		{
			$str[] = '<div class="geo">';
			$str[] = '<span class="latitude">' . $o->coords[0] . '</span>';
			$str[] = '<span class="longitude">' . $o->coords[1] . '</span>';
			$str[] = '</div>';
		}
		return implode("\n", $str);
	}

	/**
	 * Format the data as DMS
	 * [N,S,E,O] Degrees, Minutes, Seconds
	 *
	 * @param   string  $data  Data
	 *
	 * @return  string  html DMS markup
	 */

	protected function _dmsformat($data)
	{
		$dms = $this->_strToDMS($data);
		$str = array();
		if ($data != '')
		{
			$str[] = '<div class="geo">';
			$str[] = '<span class="latitude">' . $dms->coords[0] . '</span>';
			$str[] = '<span class="longitude">' . $dms->coords[1] . '</span>';
			$str[] = '</div>';
		}
		return implode("\n", $str);
	}

	/**
	 * As different map instances may or may not load geo.js we shouldnt put it in
	 * formJavascriptClass() but call this code from elementJavascript() instead.
	 * The files are still only loaded when needed and only once
	 *
	 * @return  void
	 */

	protected function geoJs()
	{
		if (!isset(self::$geoJs))
		{
			$document = JFactory::getDocument();
			$params = $this->getParams();
			if ($params->get('fb_gm_defaultloc'))
			{
				$uri = JURI::getInstance();
				FabrikHelperHTML::script('components/com_fabrik/libs/geo-location/geo.js');
				self::$geoJs = true;
			}
		}
	}

	/**
	 * As different map instances may or may not load radius widget JS we shouldnt put it in
	 * formJavascriptClass() but call this code from elementJavascript() instead.
	 * The files are still only loaded when needed and only once
	 *
	 * @return  void
	 */

	protected function radiusJs()
	{
		if (!isset(self::$radiusJs))
		{
			$document = JFactory::getDocument();
			$params = $this->getParams();
			if ((int) $params->get('fb_gm_radius', '0'))
			{
				FabrikHelperHTML::script('components/com_fabrik/libs/googlemaps/distancewidget.js');
				self::$radiusJs = true;
			}
		}
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
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$element = $this->getElement();
		$formModel = $this->getFormModel();
		$data = $formModel->_data;
		$v = $this->getValue($data, $repeatCounter);
		$zoomlevel = (int) $params->get('fb_gm_zoomlevel');
		$o = $this->_strToCoords($v, $zoomlevel);
		$dms = $this->_strToDMS($v);
		$opts = $this->getElementJSOptions($repeatCounter);
		$this->geoJs();

		$mapShown = $this->isEditable() || (!$this->isEditable() && $v != '');

		$opts->lat = (float) $o->coords[0];
		$opts->lon = (float) $o->coords[1];
		$opts->lat_dms = (float) $dms->coords[0];
		$opts->rowid = (int) JArrayHelper::getValue($data, 'rowid');
		$opts->lon_dms = (float) $dms->coords[1];
		$opts->zoomlevel = (int) $o->zoomlevel;
		$opts->control = $params->get('fb_gm_mapcontrol');
		$opts->scalecontrol = (bool) $params->get('fb_gm_scalecontrol');
		$opts->maptypecontrol = (bool) $params->get('fb_gm_maptypecontrol');
		$opts->overviewcontrol = (bool) $params->get('fb_gm_overviewcontrol');
		$opts->drag = (bool) $formModel->isEditable();
		$opts->staticmap = $this->_useStaticMap() ? true : false;
		$opts->maptype = $params->get('fb_gm_maptype');
		$opts->scrollwheel = (bool) $params->get('fb_gm_scroll_wheel');
		$opts->streetView = (bool) $params->get('fb_gm_street_view');
		$opts->latlng = $this->isEditable() ? (bool) $params->get('fb_gm_latlng', false) : false;
		$opts->sensor = (bool) $params->get('fb_gm_sensor', false);
		$opts->latlng_dms = $this->isEditable() ? (bool) $params->get('fb_gm_latlng_dms', false) : false;
		$opts->geocode = $params->get('fb_gm_geocode', '0');
		$opts->geocode_event = $params->get('fb_gm_geocode_event', 'button');
		$opts->geocode_fields = array();
		$opts->auto_center = (bool) $params->get('fb_gm_auto_center', false);
		$opts->styles = FabGoogleMapHelper::styleJs($params);
		if ($opts->geocode == '2')
		{
			foreach (array('addr1', 'addr2', 'city', 'state', 'zip', 'country') as $which_field)
			{
				$field_id = '';
				if ($field_id = $this->_getGeocodeFieldId($which_field, $repeatCounter))
				{
					$opts->geocode_fields[] = $field_id;
				}
			}
		}
		$opts->reverse_geocode = $params->get('fb_gm_reverse_geocode', '0') == '0' ? false : true;
		if ($opts->reverse_geocode)
		{
			foreach (array('route' => 'addr1', 'neighborhood' => 'addr2', 'locality' => 'city', 'administrative_area_level_1' => 'state',
				'postal_code' => 'zip', 'country' => 'country') as $google_field => $which_field)
			{
				$field_id = '';
				if ($field_id = $this->_getGeocodeFieldId($which_field, $repeatCounter))
				{
					$opts->reverse_geocode_fields[$google_field] = $field_id;
				}
			}
		}
		$opts->center = (int) $params->get('fb_gm_defaultloc', 0);

		$opts->use_radius = $params->get('fb_gm_radius', '0') == '0' || !$mapShown ? false : true;
		$opts->radius_fitmap = $params->get('fb_gm_radius_fitmap', '0') == '0' ? false : true;
		$opts->radius_write_element = $opts->use_radius ? $this->_getFieldId('fb_gm_radius_write_element', $repeatCounter) : false;
		$opts->radius_read_element = $opts->use_radius ? $this->_getFieldId('fb_gm_radius_read_element', $repeatCounter) : false;
		$opts->radius_ro_value = $opts->use_radius ? $this->_getFieldValue('fb_gm_radius_read_element', $data, $repeatCounter) : false;
		$opts->radius_default = $params->get('fb_gm_radius_default', '50');
		if ($opts->radius_ro_value === false)
		{
			$opts->radius_ro_value = $opts->radius_default;
		}
		$opts->radius_unit = $params->get('fb_gm_radius_unit', 'm');
		$opts->radius_resize_icon = COM_FABRIK_LIVESITE . 'media/com_fabrik/images/radius_resize.png';
		$opts->radius_resize_off_icon = COM_FABRIK_LIVESITE . 'media/com_fabrik/images/radius_resize.png';

		return array('FbGoogleMap', $id, $opts);
	}

	/**
	 * Get a fields value
	 *
	 * @param   string  $which_field    Parameter name of field
	 * @param   array   $data           Row data to get value from
	 * @param   int     $repeatCounter  Group repeat counter
	 *
	 * @return  mixed false or field value
	 */

	protected function _getFieldValue($which_field, $data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		$field = $params->get($which_field, false);
		if ($field)
		{
			$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($field);
			if (!$this->getFormModel()->isEditable())
			{
				$elementModel->_inDetailedView = true;
			}
			return $elementModel->getValue($data, $repeatCounter);
		}
		return false;
	}

	/**
	 * Get a fields HTML id
	 *
	 * @param   string  $which_field    Parameter name of field
	 * @param   int     $repeatCounter  Group repeat counter
	 *
	 * @return mixed false or element HTML id
	 */

	protected function _getFieldId($which_field, $repeatCounter = 0)
	{
		$listModel = $this->getlistModel();
		$params = $this->getParams();
		$field = $params->get($which_field, false);
		if ($field)
		{
			$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($field);
			if (!$this->getFormModel()->isEditable())
			{
				$elementModel->_inDetailedView = true;
			}
			return $elementModel->getHTMLId($repeatCounter);
		}
		return false;
	}

	/**
	 * Get the geocode field's ID
	 *
	 * @param   string  $which_field    Parameter name of field
	 * @param   int     $repeatCounter  Group repeat counter
	 *
	 * @return mixed false or element HTML id
	 */

	protected function _getGeocodeFieldId($which_field, $repeatCounter = 0)
	{
		return $this->_getFieldId('fb_gm_geocode_' . $which_field, $repeatCounter);
	}

	/**
	 * Determine if we use a google static map
	 * Option has to be turned on and element un-editable
	 *
	 * @return  bool
	 */

	protected function _useStaticMap()
	{
		if (!isset(self::$usestatic))
		{
			$params = $this->getParams();

			// Requires you to have installed the pda plugin
			// http://joomup.com/blog/2007/10/20/pdaplugin-joomla-15/
			if (array_key_exists('ispda', $GLOBALS) && $GLOBALS['ispda'] == 1)
			{
				self::$usestatic = true;
			}
			else
			{
				self::$usestatic = ($params->get('fb_gm_staticmap') == '1' && !$this->isEditable());
			}
		}
		return self::$usestatic;
	}

	/**
	 * Util function to turn the saved string into coordinate array
	 *
	 * @param   string  $v          coordinates
	 * @param   int     $zoomlevel  default zoom level
	 *
	 * @return  object  coords array and zoomlevel int
	 */

	protected function _strToCoords($v, $zoomlevel = 0)
	{
		$o = new stdClass;
		$o->coords = array('', '');
		$o->zoomlevel = (int) $zoomlevel;
		if (strstr($v, ","))
		{
			$ar = explode(":", $v);
			$o->zoomlevel = count($ar) == 2 ? array_pop($ar) : $o->zoomlevel;
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
	 * Util function to turn the saved string into DMS coordinate array
	 *
	 * @param   string  $v  coordinates
	 *
	 * @return  object  coords array and zoomlevel int
	 */

	protected function _strToDMS($v)
	{
		$dms = new stdClass;
		$dms->coords = array('', '');
		if (strstr($v, ","))
		{
			$ar = explode(":", $v);
			$v = FabrikString::ltrimword($ar[0], "(");
			$v = rtrim($v, ")");
			$dms->coords = explode(",", $v);

			// Latitude
			if (strstr($dms->coords[0], '-'))
			{
				$dms_lat_dir = 'S';
			}
			else
			{
				$dms_lat_dir = 'N';
			}
			$dms_lat_deg = abs((int) $dms->coords[0]);
			$dms_lat_min_float = 60 * (abs($dms->coords[0]) - $dms_lat_deg);
			$dms_lat_min = (int) $dms_lat_min_float;
			$dms_lat_sec_float = 60 * ($dms_lat_min_float - $dms_lat_min);

			// Round the secs
			$dms_lat_sec = round($dms_lat_sec_float, 0);
			if ($dms_lat_sec == 60)
			{
				$dms_lat_min ++;
				$dms_lat_sec = 0;
			}
			if ($dms_lat_min == 60)
			{
				$dms_lat_deg ++;
				$dms_lat_min = 0;
			}

			$dms->coords[0] = $dms_lat_dir . $dms_lat_deg . '&deg;' . $dms_lat_min . '&rsquo;' . $dms_lat_sec . '&quot;';

			// Longitude
			if (strstr($dms->coords[1], '-'))
			{
				$dms_long_dir = 'W';
			}
			else
			{
				$dms_long_dir = 'E';
			}
			$dms_long_deg = abs((int) $dms->coords[1]);
			$dms_long_min_float = 60 * (abs($dms->coords[1]) - $dms_long_deg);
			$dms_long_min = (int) $dms_long_min_float;
			$dms_long_sec_float = 60 * ($dms_long_min_float - $dms_long_min);

			// Round the secs
			$dms_long_sec = round($dms_long_sec_float, 0);
			if ($dms_long_sec == 60)
			{
				$dms_long_min ++;
				$dms_long_sec = 0;
			}
			if ($dms_long_min == 60)
			{
				$dms_long_deg ++;
				$dms_long_min = 0;
			}

			$dms->coords[1] = $dms_long_dir . $dms_long_deg . '&deg;' . $dms_long_min . '&rsquo;' . $dms_long_sec . '&quot;';

		}
		else
		{
			$dms->coords = array(0, 0);
		}
		return $dms;
	}

	/**
	 * Get a static map
	 *
	 * @param   string  $v              Coordinates
	 * @param   int     $w              Width
	 * @param   int     $h              Height
	 * @param   int     $z              Zoom level
	 * @param   int     $repeatCounter  Repeat group counter
	 * @param   bool    $tableView      Is the static map in the table view
	 * @param   array   $data           Row / form data, needed for optional radius value
	 *
	 * @return  string  static map html
	 */

	protected function _staticMap($v, $w = null, $h = null, $z = null, $repeatCounter = 0, $tableView = false, $data = array())
	{
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		if (is_null($w))
		{
			$w = $params->get('fb_gm_mapwidth');
		}
		if (is_null($h))
		{
			$h = $params->get('fb_gm_mapheight');
		}
		if (is_null($z))
		{
			$z = $params->get('fb_gm_zoomlevel');
		}
		$icon = urlencode($params->get('fb_gm_staticmap_icon'));
		$o = $this->_strToCoords($v, $z);
		$lat = trim($o->coords[0]);
		$lon = trim($o->coords[1]);

		switch ($params->get('fb_gm_maptype'))
		{
			case "G_SATELLITE_MAP":
				$type = 'satellite';
				break;
			case "G_HYBRID_MAP":
				$type = 'hybrid';
				break;
			case "TERRAIN":
				$type = 'terrain';
				break;
			case "G_NORMAL_MAP":
			default:
				$type = 'roadmap';
				break;
		}

		// New api3 url:
		$markers = '';
		if ($icon !== '')
		{
			$markers .= "icon:$icon|";
		}
		$markers .= "$lat,$lon";
		$uri = JURI::getInstance();
		$src = $uri->getScheme()
			. "://maps.google.com/maps/api/staticmap?center=$lat,$lon&amp;zoom={$z}&amp;size={$w}x{$h}&amp;maptype=$type&amp;mobile=true&amp;markers=$markers&amp;sensor=false";

		/**
		 * if radius widget is being used, build an encoded polyline representing a circle
		 */
		if ((int) $params->get('fb_gm_radius', '0') == 1)
		{
			require_once COM_FABRIK_FRONTEND . '/libs/googlemaps/polyline_encoder/class.polylineEncoder.php';
			$polyEnc = new PolylineEncoder;
			$radius = $this->_getFieldValue('fb_gm_radius_read_element', $data, $repeatCounter);
			if ($radius === false || !isset($radius))
			{
				$radius = $params->get('fb_gm_radius_default', '50');
			}
			$enc_str = $polyEnc->GMapCircle($lat, $lon, $radius);
			$src .= "&amp;path=weight:2%7Ccolor:black%7Cfillcolor:0x5599bb%7Cenc:" . $enc_str;
		}

		$id = $tableView ? '' : "id=\"{$id}\"";
		$str = "<div $id class=\"gmStaticMap\"><img src=\"$src\" alt=\"static map\" />";
		$str .= "</div>";
		return $str;
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           To preopulate element with
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string  elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		require_once COM_FABRIK_FRONTEND . '/libs/mobileuseragent/mobileuseragent.php';
		require_once COM_FABRIK_FRONTEND . '/helpers/string.php';
		$ua = new MobileUserAgent;
		$id = $this->getHTMLId($repeatCounter);
		$name = $this->getHTMLName($repeatCounter);
		$groupModel = $this->getGroupModel();
		$element = $this->getElement();
		$val = $this->getValue($data, $repeatCounter);
		$params = $this->getParams();
		$w = $params->get('fb_gm_mapwidth');
		$h = $params->get('fb_gm_mapheight');
		if ($this->_useStaticMap())
		{
			return $this->_staticMap($val, null, null, null, $repeatCounter, false, $data);
		}
		else
		{
			$val = JArrayHelper::getValue($data, $name, $val);
			if ($element->hidden == '1')
			{
				return $this->getHiddenField($name, $data[$name], $id);
			}
			$str = '<div class="fabrikSubElementContainer" id="' . $id . '">';

			// If its not editable and theres no val don't show the map
			$geoCodeEvent = $params->get('fb_gm_geocode_event', 'button');
			if ((!$this->isEditable() && $val != '') || $this->isEditable())
			{
				if ($this->isEditable() && $params->get('fb_gm_geocode') != '0')
				{
					$append = $geoCodeEvent === 'button' ? '' : 'input-append';
					$str .= '<div style="margin-bottom:5px" class="control-group ' . $append . '">';
				}
				if ($this->isEditable() && $params->get('fb_gm_geocode') == 1)
				{
					$str .= '<input class="geocode_input inputbox" style="margin-right:5px"/>';
				}

				if ($params->get('fb_gm_geocode') != '0' && $params->get('fb_gm_geocode_event', 'button') == 'button' && $this->isEditable())
				{
					$str .= '<input class="button geocode" type="button" value="' . JText::_('PLG_ELEMENT_GOOGLE_MAP_GEOCODE') . '" />';
				}
				if ($this->isEditable() && $params->get('fb_gm_geocode') != '0')
				{
					$str .= '</div>';
				}
				// Allow for 100% width
				if ($w !== '')
				{
					$w = 'width:' . $w . 'px;';
				}
				$str .= '<div class="map" style="' . $w . 'height:' . $h . 'px"></div>';
				$str .= '<input type="hidden" class="fabrikinput" name="' . $name . '" value="' . htmlspecialchars($val, ENT_QUOTES) . '" />';
				if (($this->isEditable() || $params->get('fb_gm_staticmap') == '2') && $params->get('fb_gm_latlng') == '1')
				{
					$arrloc = explode(',', $val);
					$arrloc[0] = str_replace("(", "", $arrloc[0]);
					$arrloc[1] = array_key_exists(1, $arrloc) ? str_replace(")", "", array_shift(explode(":", $arrloc[1]))) : '';
					$edit = $this->isEditable() ? '' : 'disabled="true"';
					$str .= '<div class="coord" style="margin-top:5px;">
					<input ' . $edit . ' size="23" value="' . $arrloc[0] . ' ° N" style="margin-right:5px" class="inputbox lat"/>
					<input ' . $edit . ' size="23" value="' . $arrloc[1] . ' ° E"  class="inputbox lng"/></div>';
				}
				if (($this->isEditable() || $params->get('fb_gm_staticmap') == '2') && $params->get('fb_gm_latlng_dms') == '1')
				{
					$dms = $this->_strToDMS($val);
					$edit = $this->isEditable() ? '' : 'disabled="true"';
					$str .= '<div class="coord" style="margin-top:5px;">
					<input ' . $edit . ' size=\"23\" value="' . $dms->coords[0] . '" style="margin-right:5px" class="latdms"/>
					<input ' . $edit . ' size=\"23\" value="' . $dms->coords[1] . '"  class="lngdms"/></div>';
				}
				$str .= '</div>';
			}
			else
			{
				$str .= JText::_('PLG_ELEMENT_GOOGLEMAP_NO_LOCATION_SELECTED');
			}
			$str .= $this->_microformat($val);
			return $str;
		}
	}

	/**
	 * Create the SQL select 'name AS alias' segment for list/form queries
	 *
	 * @param   array  &$aFields    Element names
	 * @param   array  &$aAsFields  'Name AS alias' fields
	 * @param   array  $opts        Options
	 *
	 * @return  void
	 */

	public function getAsField_html(&$aFields, &$aAsFields, $opts = array())
	{
		$dbtable = $this->actualTableName();
		$db = FabrikWorker::getDbo();
		$listModel = $this->getlistModel();
		$table = $listModel->getTable();
		$fullElName = JArrayHelper::getValue($opts, 'alias', $dbtable . '___' . $this->_element->name);
		$dbtable = $db->quoteName($dbtable);
		$str = $dbtable . '.' . $db->quoteName($this->_element->name) . ' AS ' . $db->quoteName($fullElName);
		if ($table->db_primary_key == $fullElName)
		{
			array_unshift($aFields, $fullElName);
			array_unshift($aAsFields, $fullElName);
		}
		else
		{
			$aFields[] = $str;
			$aAsFields[] = $db->quoteName($fullElName);
			$rawName = $fullElName . '_raw';
			$aFields[] = $dbtable . '.' . $db->quoteName($this->_element->name) . ' AS ' . $db->quoteName($rawName);
			$aAsFields[] = $db->quoteName($rawName);
		}
	}

	/**
	 * This really does get just the default value (as defined in the element's settings)
	 *
	 * @param   array  $data  Form data
	 *
	 * @return mixed
	 */

	public function getDefaultValue($data = array())
	{
		if (!isset($this->_default))
		{
			$params = $this->getParams();

			// $$$ hugh - added parens around lat,long for consistancy!
			$this->_default = '(' . $params->get('fb_gm_lat') . ',' . $params->get('fb_gm_long') . ')' . ':' . $params->get('fb_gm_zoomlevel');
		}
		return $this->_default;
	}

	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  When repeating joinded groups we need to know what part of the array to access
	 * @param   array  $opts           Options
	 *
	 * @return  string	value
	 */

	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		if (is_null($this->defaults))
		{
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults))
		{
			$groupModel = $this->getGroup();
			$formModel = $this->getForm();
			$element = $this->getElement();
			$listModel = $this->getlistModel();
			$params = $this->getParams();

			$value = $this->getDefaultOnACL($data, $opts);
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
					$value = FabrikWorker::JSONtoData($value, true);
					if (array_key_exists($repeatCounter, $value))
					{
						$value = $value[$repeatCounter];
						if (is_array($value))
						{
							$value = implode(',', $value);
						}
						return $value;
					}
				}
			}
			if ($groupModel->isJoin())
			{
				$fullName = $this->getFullName(false, true, false);
				$joinid = $groupModel->getGroup()->join_id;
				if (isset($data['join'][$joinid][$fullName]))
				{
					$value = $data['join'][$joinid][$fullName];
					if (is_array($value) && array_key_exists($repeatCounter, $value))
					{
						$value = $value[$repeatCounter];
					}
				}
				else
				{
					// $$$ rob - prob not used but im leaving in just in case
					if (isset($data[$fullName]))
					{
						$value = $data[$fullName];
						if (is_array($value) && array_key_exists($repeatCounter, $value))
						{
							$value = $value[$repeatCounter];
						}
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
			if ($value === '')
			{
				// Query string for joined data
				$value = JArrayHelper::getValue($data, $fullName);
			}

			// Stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1)
			{
				FabrikWorker::getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
			}
			/** ensure that the data is a string **/
			if (is_array($value))
			{
				$value = implode(',', $value);
			}
			$this->defaults[$repeatCounter] = $value;
		}
		return $this->defaults[$repeatCounter];
	}
}
