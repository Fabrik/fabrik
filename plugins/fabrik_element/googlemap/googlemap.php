<?php
/**
 * Fabrik GoogleMap Element
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.googlemap
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\Googlemap;
use Fabrik\Helpers\Image\Image;

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';


/**
 * Plugin element to render a Google map
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.googlemap
 * @since       3.0
 */
class PlgFabrik_ElementGooglemap extends PlgFabrik_Element
{
	/**
	 * Has the geoJS been loaded
	 *
	 * @var bool
	 */
	protected static $geoJs = null;

	/**
	 * Has the radius js been loaded
	 *
	 * @var bool
	 */
	protected static $radiusJs = null;

	/**
	 * Has the OSRef js been loaded
	 *
	 * @var bool
	 */
	protected static $OSRefJs = null;

	/**
	 * Determine if we use a google static map
	 *
	 * @var bool
	 */
	protected static $usestatic = null;

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string    $data      Elements data
	 * @param   stdClass  &$thisRow  All the data in the lists current row
	 * @param   array     $opts      Rendering options
	 *
	 * @return  string	formatted value
	 */
	public function renderListData($data, stdClass &$thisRow, $opts = array())
	{
        $profiler = JProfiler::getInstance('Application');
        JDEBUG ? $profiler->mark("renderListData: {$this->element->plugin}: start: {$this->element->name}") : null;

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
				$d = $this->_staticMap($d, $w, $h, $z, $i, true, ArrayHelper::fromObject($thisRow));
			}

			if ($params->get('icon_folder') == '1' && ArrayHelper::getValue($opts, 'icon', 1))
			{
				// $$$ rob was returning here but that stopped us being able to use links and icons together
				$d = $this->replaceWithIcons($d, 'list', $listModel->getTmpl());
			}
			else
			{
				if (!$params->get('fb_gm_staticmap_tableview'))
				{
					$d = $params->get('fb_gm_staticmap_tableview_type_coords', 'num') == 'dms' ? $this->_dmsformat($d) : $this->_microformat($d);
				}
			}
			if (ArrayHelper::getValue($opts, 'rollover', 1))
			{
				$d = $this->rollover($d, $thisRow, 'list');
			}

			if (ArrayHelper::getValue($opts, 'link', 1))
			{
				$d = $listModel->_addLink($d, $this, $thisRow, $i);
			}
		}

		return $this->renderListDataFinal($data, $opts);
	}

	/**
	 * Render RSS feed format
	 *
	 * @param   string  $data      Elements data
	 * @param   object  &$thisRow  All the data in the lists current row
	 *
	 * @return  string	Formatted value
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
	 * @return string Html microformat markup
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
	 * @return string Html microformat markup
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
	 * @return  string  Html DMS markup
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
	 * As different map instances may or may not load geo.js we shouldn't put it in
	 * formJavascriptClass() but call this code from elementJavascript() instead.
	 * The files are still only loaded when needed and only once
	 *
	 * @return  void
	 */

	protected function geoJs()
	{
		if (!isset(self::$geoJs))
		{
			$params = $this->getParams();

			if ($params->get('fb_gm_defaultloc'))
			{
				$ext = FabrikHelperHTML::isDebug() ? '.js' : '-min.js';
				FabrikHelperHTML::script('media/com_fabrik/js/lib/geo-location/geo' . $ext);
				self::$geoJs = true;
			}
		}
	}

	/**
	 * JS lib for OSRef
	 * As different map instances may or may not load this we shouldn't put it in
	 * formJavascriptClass() but call this code from elementJavascript() instead.
	 * The files are still only loaded when needed and only once
	 *
	 * @return  void
	 */

	protected function OSRefJs()
	{
		if (!isset(self::$OSRefJs))
		{
			$params = $this->getParams();

			if ($params->get('fb_gm_latlng_osref'))
			{
				$ext = FabrikHelperHTML::isDebug() ? '.js' : '-min.js';
				FabrikHelperHTML::script('media/com_fabrik/js/lib/jscoord-1.1.1/jscoord-1.1.1' . $ext);
				self::$OSRefJs = true;
			}
		}
	}

	/**
	 * As different map instances may or may not load radius widget JS we shouldn't put it in
	 * formJavascriptClass() but call this code from elementJavascript() instead.
	 * The files are still only loaded when needed and only once
	 *
	 * @return  void
	 */

	protected function radiusJs()
	{
		if (!isset(self::$radiusJs))
		{
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
		$data = $formModel->data;
		$v = $this->getValue($data, $repeatCounter);
		$zoomlevel = (int) $params->get('fb_gm_zoomlevel');
		$o = $this->_strToCoords($v, $zoomlevel);
		$dms = $this->_strToDMS($v);
		$opts = $this->getElementJSOptions($repeatCounter);

		$this->geoJs();
		$this->OSRefJs();

		$mapShown = $this->isEditable() || (!$this->isEditable() && $v != '');
		$opts->mapShown = $mapShown;
		$opts->lat = (float) $o->coords[0];
		$opts->lon = (float) $o->coords[1];
		$opts->lat_dms = (float) $dms->coords[0];
		$opts->rowid = (int) FArrayHelper::getValue($data, 'rowid');
		$opts->lon_dms = (float) $dms->coords[1];
		$opts->zoomlevel = (int) $o->zoomlevel;
		$opts->control = $params->get('fb_gm_mapcontrol');
		$opts->scalecontrol = (bool) $params->get('fb_gm_scalecontrol');
		$opts->maptypecontrol = (bool) $params->get('fb_gm_maptypecontrol');
		$opts->maptypeids = $params->get('fb_gm_maptypecontroloptions');
		$opts->overviewcontrol = (bool) $params->get('fb_gm_overviewcontrol');
		$opts->traffic = (bool) $params->get('fb_gm_trafficlayer', '0');
		$opts->drag = (bool) $formModel->isEditable() && (bool) $params->get('fb_gm_draggable', '1');
		$opts->staticmap = $this->_useStaticMap() ? true : false;
		$opts->maptype = $params->get('fb_gm_maptype');
		$opts->scrollwheel = (bool) $params->get('fb_gm_scroll_wheel');
		$opts->streetView = (bool) $params->get('fb_gm_street_view');
		$opts->latlng = $this->isEditable() ? (bool) $params->get('fb_gm_latlng', false) : false;
		$opts->sensor = (bool) $params->get('fb_gm_sensor', false);
		$opts->latlng_dms = $this->isEditable() ? (bool) $params->get('fb_gm_latlng_dms', false) : false;
		$opts->latlng_osref = $this->isEditable() ? (bool) $params->get('fb_gm_latlng_osref', false) : false;
		$opts->geocode = $params->get('fb_gm_geocode', '0');
		$opts->geocode_event = $params->get('fb_gm_geocode_event', 'button');
		$opts->geocode_fields = array();
		// geocode_on_load, 0 = no, 1 = new, 2 = edit, 3 = always
		$geocode_on_load = $params->get('fb_gm_geocode_on_load', '0');
		$opts->geocode_on_load = $this->isEditable() &&
				(
					($geocode_on_load == 1 && $formModel->isNewRecord())
					|| ($geocode_on_load == 2 && !$formModel->isNewRecord())
					|| $geocode_on_load == 3
				);
		$opts->auto_center = (bool) $params->get('fb_gm_auto_center', false) && (bool) $params->get('fb_gm_draggable', '1');
		$opts->styles = Googlemap::styleJs($params);
		$opts->lat_element = $this->_getFieldId('fb_gm_lat_element', $repeatCounter);
		$opts->lon_element = $this->_getFieldId('fb_gm_lon_element', $repeatCounter);
		$opts->latlng_elements = !empty($opts->lat_element) && !empty($opts->lon_element);

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

			// remove any duplicates in case they have misunderstood and selected the same element for all fields
			$opts->geocode_fields = array_values(array_unique($opts->geocode_fields));
		}

		$opts->reverse_geocode = $params->get('fb_gm_reverse_geocode', '0') == '0' ? false : true;

		if ($opts->reverse_geocode)
		{
			foreach (array(
				         'street_number'               => 'street_number',
				         'route'                       => 'addr1',
				         'neighborhood'                => 'addr2',
				         'locality'                    => 'city',
				         'administrative_area_level_1' => 'state',
				         'postal_code'                 => 'zip',
				         'country'                     => 'country',
				         'formatted_address'           => 'formatted_address'
			         ) as $google_field => $which_field)
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

		if ($this->app->input->get('view', '') === 'details')
		{
			$opts->directionsFromElement = $this->_getFieldId('fb_gm_directions_from', $repeatCounter);
			if ($opts->directionsFromElement !== false)
			{
				$directionsFromCoords    = $this->_getFieldValue('fb_gm_directions_from', $data, $repeatCounter);
				$o                       = $this->_strToCoords($directionsFromCoords, 14);
				$opts->directionsFromLat = trim($o->coords[0]);
				$opts->directionsFromLon = trim($o->coords[1]);
				$opts->directionsFrom    = true;
			}
		}
		else
		{
			$opts->directionsFrom = false;
		}

		$opts->use_overlays = (int) $params->get('fb_gm_use_overlays', '0');

		if ($opts->use_overlays)
		{
			$overlays = $this->getOverlayArray();
			$opts->overlay_urls = $overlays['urls'];
			$opts->overlay_labels = $overlays['labels'];
			$opts->overlay_preserveviewports = $overlays['preserveViewports'];
			$opts->overlay_suppressinfowindows = $overlays['suppressInfoWindows'];
		}
		else
		{
			$opts->overlay_urls                = array();
			$opts->overlay_labels              = array();
			$opts->overlay_preserveviewports   = array();
			$opts->overlay_suppressinfowindows = array();
		}

		$opts->use_overlays_sidebar = $opts->use_overlays && (int) $params->get('fb_gm_use_overlays_sidebar', '0');
		$opts->use_overlays_select = $params->get('fb_gm_use_overlays_select', 'checkbox');
		$opts->use_overlays_checked = $params->get('fb_gm_use_overlays_checked', '');

		$config = JComponentHelper::getParams('com_fabrik');
		$apiKey = trim($config->get('google_api_key', ''));
		$opts->key = empty($apiKey) ? false : $apiKey;
		$opts->language             = trim(strtolower($config->get('google_api_language', '')));

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
				$elementModel->inDetailedView = true;
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
				$elementModel->inDetailedView = true;
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
			$static = $params->get('fb_gm_staticmap');

			// Requires you to have installed the pda plugin
			// http://joomup.com/blog/2007/10/20/pdaplugin-joomla-15/
			if (array_key_exists('ispda', $GLOBALS) && $GLOBALS['ispda'] == 1)
			{
				self::$usestatic = true;
			}
			else
			{
				self::$usestatic = ($static == '1' || $static == '3') && !$this->isEditable();
			}
		}

		return self::$usestatic;
	}

	/**
	 * Util function to turn the saved string into coordinate array
	 *
	 * @param   string  $v          Coordinates
	 * @param   int     $zoomlevel  Default zoom level
	 *
	 * @return  object  Coords array and zoomlevel int
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
	 * @param   string  $v  Coordinates
	 *
	 * @return  object  Coords array and zoomlevel int
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

		/**
		 * Width and height MUST be specified or static map call will fail.  But as we allow for
		 * leaving these params blank to get a 100% size full map, we have to set a default when
		 * building a static map.  Only real solution is to add YAFOs for "Static map width" and height.
		 * But for now, just default to 200x150.
		 */

		if (is_null($w))
		{
			$w = $params->get('fb_gm_mapwidth', '200');
			$w = empty($w) || strstr($w, '%') ? '200' : $w;
		}

		if (is_null($h))
		{
			$h = $params->get('fb_gm_mapheight', '150');
			$h = empty($h) ? '150' : $h;
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
			$markers .= 'icon:' . $icon . '|';
		}

		$markers .= $lat . ',' . $lon;
		$uri = JURI::getInstance();
		$src = $uri->getScheme() . '://maps.google.com/maps/api/staticmap?';
		$attribs = array();
		$attribs[] = 'center=' . $lat . ',' . $lon;
		$attribs[] = 'zoom=' . $z;
		$attribs[] = 'size=' . $w . 'x' . $h;
		$attribs[] = 'maptype=' . $type;
		$attribs[] = 'mobile=true';
		$attribs[] = 'markers=' . $markers;
		$attribs[] = 'sensor=false';

		$config = JComponentHelper::getParams('com_fabrik');
		$apiKey = trim($config->get('google_api_key', ''));
		$client = $config->get('google_buisness_client_id', '');
		$signature = $config->get('google_buisness_signature', '');

		if ($client !== '')
		{
			if ($signature === '')
			{
				throw new Exception('You have entered a Google Maps Business Client id, but have not supplied a signature value');
			}

			$attribs[] = 'client=' . $client;
			$attribs[] = 'signature=' . $signature;
		}
		elseif ($apiKey !== '')
		{
			$attribs[] = 'key=' . $apiKey;
		}

		if ($params->get('visual_refresh', false))
		{
			$attribs[] = 'visual_refresh=true';
		}

		// If radius widget is being used, build an encoded polyline representing a circle
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
			$attribs[] = 'path=weight:2%7Ccolor:black%7Cfillcolor:0x5599bb%7Cenc:' . $enc_str;
		}

		// Serve cached file from remote url
		$src .= implode('&', $attribs);
		$folder = 'cache/com_fabrik/staticmaps/';
		$file = implode('.', $attribs) . '.png';

		// If its not editable and there's no val don't show the map
		$layout = $this->getLayout('static');
		$displayData = new stdClass;

		if ((!$tableView && $params->get('fb_gm_staticmap') == '1') || ($tableView && $params->get('fb_gm_staticmap_tableview', '0') === '1'))
		{
			$displayData->src = Image::cacheRemote($src, $folder, $file);

			// if cacheImage returned false, probably an issue with permissions on the cache folder, so punt to direct URL
			if ($displayData->src === false)
			{
				$displayData->src = $src;
			}
		}
		else
		{
			$displayData->src = $src;
		}

		$displayData->id = $id;
		$displayData->view = $tableView ? 'list' : 'details';

		return $layout->render($displayData);
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           To pre-populate element with
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string  elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$id = $this->getHTMLId($repeatCounter);
		$name = $this->getHTMLName($repeatCounter);
		$element = $this->getElement();
		$val = $this->getValue($data, $repeatCounter);
		$params = $this->getParams();
		$w = $params->get('fb_gm_mapwidth');
		$h = $params->get('fb_gm_mapheight');
		$str = '';

		if ($this->_useStaticMap())
		{
			return $this->_staticMap($val, null, null, null, $repeatCounter, false, $data);
		}
		else
		{
			if ($element->hidden == '1')
			{
				return $this->getHiddenField($name, $data[$name], $id);
			}

			if ((!$this->isEditable() && $val != '') || $this->isEditable())
			{
				$layout = $this->getLayout('form');
				$layoutData = new stdClass;
				$layoutData->id = $id;

				$coords = FabrikString::mapStrToCoords($val);
				$layoutData->coords = $coords->coords;
				$layoutData->geoCodeEvent = $params->get('fb_gm_geocode_event', 'button');
				$layoutData->geocode = $params->get('fb_gm_geocode');
				$layoutData->editable = $this->isEditable();
				$layoutData->width = $w;
				$layoutData->height = $h;
				$layoutData->name = $name;
				$layoutData->label = $element->label;
				$layoutData->value = htmlspecialchars($val, ENT_QUOTES);
				$layoutData->dms = $this->_strToDMS($val);
				$layoutData->osref = "";
				$layoutData->staticmap = $params->get('fb_gm_staticmap');
				$layoutData->showdms = $params->get('fb_gm_latlng_dms');
				$layoutData->showlatlng = $params->get('fb_gm_latlng');
				$layoutData->showosref = $params->get('fb_gm_latlng_osref');
				$layoutData->sidebarPosition = $params->get('fb_gm_use_overlays_sidebar');
				$layoutData->showOverLays    = (bool) $params->get('fb_gm_use_overlays');

				if ($this->getShowSideBar())
				{
					$overlays = $this->getOverlayArray();
					$layoutData->showSidebar   = 1;
					$layoutData->overlaySelect = $params->get('fb_gm_use_overlays_select', 'checkbox');
					$layoutData->overlayUrls   = $overlays['urls'];
					$layoutData->overlayLabels = $overlays['labels'];
					$layoutData->overlaysChecked = $params->get('fb_gm_use_overlays_checked', '');
				}
				else
				{
					$layoutData->showSidebar = 0;
				}

				return $layout->render($layoutData);
			}
			else
			{
				$str .= FText::_('PLG_ELEMENT_GOOGLEMAP_NO_LOCATION_SELECTED');
			}

			/*
			 * $$$ hugh - not sure why we still do this.  If they want to show lat/lng details, they can use the
			 * gm_latlng option.  Problem with showing this is we never change it, so it's misleading.
			 */

			// $str .= $this->_microformat($val);

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
		$fullElName = FArrayHelper::getValue($opts, 'alias', $dbtable . '___' . $this->element->name);
		$dbtable = $db->qn($dbtable);
		$str = $dbtable . '.' . $db->qn($this->element->name) . ' AS ' . $db->qn($fullElName);

		if ($table->db_primary_key == $fullElName)
		{
			array_unshift($aFields, $fullElName);
			array_unshift($aAsFields, $fullElName);
		}
		else
		{
			$aFields[] = $str;
			$aAsFields[] = $db->qn($fullElName);
			$rawName = $fullElName . '_raw';
			$aFields[] = $dbtable . '.' . $db->qn($this->element->name) . ' AS ' . $db->qn($rawName);
			$aAsFields[] = $db->qn($rawName);
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
		if (!isset($this->default))
		{
			$params = $this->getParams();

			// $$$ hugh - added parens around lat,long for consistency!
			$this->default = '(' . $params->get('fb_gm_lat') . ',' . $params->get('fb_gm_long') . ')' . ':' . $params->get('fb_gm_zoomlevel');
		}

		return $this->default;
	}


	/**
	 * Used to format the data when shown in the form's email
	 *
	 * @param   mixed $value         element's data
	 * @param   array $data          form records data
	 * @param   int   $repeatCounter repeat group counter
	 *
	 * @return  string    formatted value
	 */
	public function getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		if ($this->inRepeatGroup && is_array($value))
		{
			$val = array();

			foreach ($value as $v2)
			{
				$val[] = $this->getIndEmailValue($v2, $data, $repeatCounter);
			}
		}
		else
		{
			$val = $this->getIndEmailValue($value, $data, $repeatCounter);
		}

		return $val;
	}

	/**
	 * Turn form value into email formatted value
	 *
	 * @param   mixed $value         Element value
	 * @param   array $data          Form data
	 * @param   int   $repeatCounter Group repeat counter
	 *
	 * @return  string  email formatted value
	 */
	protected function getIndEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		return $this->_staticMap($value, null, null, null, $repeatCounter, false, $data);
	}

	/**
	 * Get whether the map side bar should be shown
	 *
	 * @return  bool
	 */
	private function getShowSideBar()
	{
		$params = $this->getParams();

		// KML layers side bar?
		if ((int) $params->get('fb_gm_use_overlays', 0) === 1 && (int) $params->get('fb_gm_use_overlays_sidebar', 0) > 0)
		{
			return true;
		}

		return false;
	}

	private function useOverlays()
	{
		$params = $this->getParams();

		return (int) $params->get('fb_gm_use_overlays', 0) === 1;
	}

	private function getOverlayArray()
	{
		static $overlays = null;

		if (!isset($overlays))
		{
			if ($this->useOverlays())
			{
				$params                      = $this->getParams();
				$overlay_urls                = (array) $params->get('fb_gm_overlay_urls');
				$overlay_labels              = (array) $params->get('fb_gm_overlay_labels');
				$overlay_preserveviewports   = (array) $params->get('fb_gm_overlay_preserveviewport');
				$overlay_suppressinfowindows = (array) $params->get('fb_gm_overlay_suppressinfowindows');

				$overlayCode = trim($params->get('fb_gm_overlay_code', ''));

				if (!empty($overlayCode))
				{
					// make available for eval'ed code
					$formModel = $this->getFormModel();
					$overlayArray = eval($overlayCode);

					if (is_array($overlayArray))
					{
						if (array_key_exists('urls', $overlayArray) && is_array($overlayArray['urls']))
						{
							$overlay_urls = array_merge($overlay_urls, $overlayArray['urls']);
						}

						if (array_key_exists('labels', $overlayArray))
						{
							$overlay_labels = array_merge($overlay_labels, $overlayArray['labels']);
						}

						if (array_key_exists('preserveViewports', $overlayArray))
						{
							$overlay_preserveviewports = array_merge($overlay_preserveviewports, $overlayArray['preserveViewports']);
						}

						if (array_key_exists('suppressInfoWindows', $overlayArray))
						{
							$overlay_suppressinfowindows = array_merge($overlay_suppressinfowindows, $overlayArray['suppressInfoWindows']);
						}
					}
				}

				foreach ($overlay_urls as $k => $overlayUrl)
				{
					if (empty($overlayUrl))
					{
						unset($overlay_urls[$k]);
						unset($overlay_labels[$k]);
						unset($overlay_preserveviewports[$k]);
						unset($overlay_suppressinfowindows[$k]);
					}
				}

				$overlay_urls = array_values($overlay_urls);
				$overlay_labels = array_values($overlay_labels);
				$overlay_suppressinfowindows = array_values($overlay_suppressinfowindows);
				$overlay_preserveviewports = array_values($overlay_preserveviewports);

				$overlays = array(
					'urls'                => $overlay_urls,
					'labels'              => $overlay_labels,
					'preserveViewports'   => $overlay_preserveviewports,
					'suppressInfoWindows' => $overlay_suppressinfowindows
				);
			}
			else
			{
				$overlays = array(
					'urls'                => array(),
					'labels'              => array(),
					'preserveViewports'   => array(),
					'suppressInfoWindows' => array()
				);
			}

			$this->overlayData = $overlays;
		}

		return $overlays;
	}
}
