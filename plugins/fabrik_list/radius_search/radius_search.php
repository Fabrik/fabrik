<?php
/**
 * Add a radius search option to the list filters
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.radiussearch
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Add a radius search option to the list filters
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.radiussearch
 * @since       3.0
 */

class PlgFabrik_ListRadius_Search extends PlgFabrik_List
{
	/**
	 * Place coordinates
	 *
	 * @var array
	 */
	protected $placeCoordinates = null;

	/**
	 * Build the select list which enables users to determine how the search is performed
	 *
	 * @param   array  $type  Selected search type
	 *
	 * @return mixed
	 */
	protected function searchSelectList($type)
	{
		$options = array();
		$params = $this->getParams();

		if ($params->get('myloc', 1) == 1)
		{
			$options[] = JHtml::_('select.option', 'mylocation', JText::_('PLG_VIEW_RADIUS_MY_LOCATION'));
		}

		if ($params->get('place', 1) == 1)
		{
			$placeElement = $this->getPlaceElement()->getElement();
			$options[] = JHtml::_('select.option', 'place', strip_tags($placeElement->label));
		}

		if ($params->get('coords', 1) == 1)
		{
			$options[] = JHtml::_('select.option', 'latlon', JText::_('PLG_VIEW_RADIUS_COORDINATES'));
		}

		if ($params->get('geocode', 1) == 1)
		{
			$options[] = JHtml::_('select.option', 'geocode', JText::_('PLG_VIEW_RADIUS_GEOCODE'));
		}

		$selectName = 'radius_search_type' . $this->renderOrder . '[]';
		$select = JHtml::_('select.genericlist', $options, $selectName, '', 'value', 'text', $type[0]);

		return $select;
	}

	/**
	 * Build the radius search HTML
	 *
	 * @param   array  &$args  Plugin args
	 *
	 * @return  void
	 */

	public function onMakeFilters(&$args)
	{
		if (!is_object($this->getMapElement()))
		{
			return;
		}

		$params = $this->getParams();
		$model = $this->getModel();
		$app = JFactory::getApplication();
		$baseContext = $this->getSessionContext();
		$f = new stdClass;
		$f->label = $params->get('radius_label', 'Radius search');
		$class = "class=\"inputbox fabrik_filter autocomplete-trigger\"";
		$typeKey = $baseContext . 'radius_search_type' . $this->renderOrder;
		$type = $app->getUserStateFromRequest($typeKey, 'radius_search_type' . $this->renderOrder, array('mylocation'));
		$style = $type[0] == 'place' ? 'display:block' : 'display:none';

		$context = $baseContext . 'radius_search_place-auto-complete';
		$name = "radius_search_place{$this->renderOrder}-auto-complete";
		$place = $app->getUserStateFromRequest($context, $name);

		$strPlace = array();
		$strPlace[] = '<div class="radius_search_place_container" style="' . $style . ';position:relative;">';
		$strPlace[] = '<input type="text" name="' . $name . '" id="' . $name . '" ' . $class . 'value="' . $place . '"/>';

		$context = $baseContext . 'radius_search_place';
		$name = 'radius_search_place' . $this->renderOrder;
		$placeValue = $app->getUserStateFromRequest($context, $name);
		$strPlace[] = '<input type="hidden" name="' . $name . '" id="' . $name . '" ' . $class . ' value="' . $placeValue . '"/>';
		$strPlace[] = '</div>';
		$strPlace = implode("\n", $strPlace);

		$style = $type[0] == 'latlon' ? 'display:block' : 'display:none';
		$lat = $app->getUserStateFromRequest($baseContext . 'lat' . $this->renderOrder, 'radius_search_lat' . $this->renderOrder);
		$lon = $app->getUserStateFromRequest($baseContext . 'lon' . $this->renderOrder, 'radius_search_lon' . $this->renderOrder);

		$strLatLon = "<div class=\"radius_search_coords_container\" style=\"$style\">
		<table style=\"width:100%\"><tr><td><label for=\"radius_search_lat_" . $this->renderOrder . "\">" . JText::_('PLG_VIEW_RADIUS_LATITUDE')
			. "</label></td><td><input type=\"text\" name=\"radius_search_lat\" value=\"$lat\" id=\"radius_search_lat_"
				. $this->renderOrder . "\" $class size=\"6\"/></td></tr>
		<label><tr><td><label for=\"radius_search_lon_" . $this->renderOrder . "\">" . JText::_('PLG_VIEW_RADIUS_LONGITUDE')
			. "</label></td><td><input type=\"text\" name=\"radius_search_lon\" value=\"$lon\" id=\"radius_search_lon_"
				. $this->renderOrder . "\" $class size=\"6\"/></td></tr></table></div>";

		$o = FabrikString::mapStrToCoords($params->get('geocode_default', ''));

		$defaultLat = $lat ? $lat : (float) $o->lat;
		$defaultLon = $lon ? $lon : (float) $o->long;
		$defaultZoom = (int) $o->zoom === 0 ? 7 : (int) $o->zoom;
		$strSlider = $this->slider();

		$str = '';
		$geocodeSelected = $params->get('geocode', 1);
		$totalOpts = $params->get('myloc', 1) + $params->get('place', 1) + $params->get('coords', 1) + $geocodeSelected;
		$activeDef = array($params->get('start_active', 0));
		$active = $app->getUserStateFromRequest($baseContext . 'radius_search_active', 'radius_search_active' . $this->renderOrder, $activeDef);

		$str .= '<div class="radius_search" id="radius_search' . $this->renderOrder . '" style="left:-100000px;position:absolute;">';
		$str .= '<input type="hidden" name="radius_search_active' . $this->renderOrder . '[]" value="' . $active[0] . '" />';

		$str .= '<div class="radius_search_options">';

		/*
		 * $$$ hugh - JS expects these, in geoCode(), so for now just leave
		 * 'em, should really sort out the JS so it doesn't look for them if geocode turned off
		 */
			$str .= '<input type="hidden" name="geo_code_def_zoom" value="' . $defaultZoom . '" />'
				. '<input type="hidden" name="geo_code_def_lat" value="' . $defaultLat . '" />'
				. '<input type="hidden" name="geo_code_def_lon" value="' . $defaultLon . '" />';
		$str .= '
		<table class="radius_table fabrikList table" style="width:100%">
			<tbody>
			<tr>
				<td>' . JText::_('PLG_VIEW_RADIUS_DISTANCE') . '</td>
				<td>' . $strSlider . '</td>
			</tr>';

		$strGeocode = $this->geoCodeWidget($type);

		$select = $this->searchSelectList($type);

		$str .= '<tr><td>' . JText::_('PLG_VIEW_RADIUS_FROM') . '</td><td>' . $select . '</td></tr>';
		$str .= '<tr><td colspan="2">' . $strPlace . $strLatLon . $strGeocode . '</td></tr>';

		$str .=	'</tbody>
		</table>';
		$str .= '<div style="padding-top:5px;float:right">';
		$str .= '<input type="button" class="btn btn-link cancel" value="' . JText::_('COM_FABRIK_CANCEL') . '" /> ';
		$str .= '<input type="button" name="filter" value="Go" class="fabrik_filter_submit button btn btn-primary"></div>';
		$str .= '</div>';
		$str .= '<input type="hidden" name="radius_prefilter" value="1" />';

		$str .= "</div>";
		$f->element = $str;
		$f->required = '';

		if (JFactory::getApplication()->input->get('format') !== 'raw')
		{
			FabrikHelperHTML::addStyleDeclaration("table.radius_table{border-collapse:collapse;border:0;}
			table.radius_table td{border:0;}");
		}

		JText::script('PLG_VIEW_RADIUS_NO_GEOLOCATION_AVAILABLE');
		JText::script('COM_FABRIK_SEARCH');
		JText::script('PLG_LIST_RADIUS_SEARCH');

		$mapElement = $this->getMapElement();
		$mapName = $mapElement->getFullName(true, false);
		$model->viewfilters[$mapName] = $f;
	}

	/**
	 * Create the geocode widget to determine search centre.
	 *
	 * @param   array  $type  Search type
	 *
	 * @since   3.0.8
	 *
	 * @return  string
	 */

	private function geoCodeWidget($type)
	{
		$app = JFactory::getApplication();
		$params = $this->getParams();
		$baseContext = $this->getSessionContext();
		$style = $params->get('geocode', 1) == 1 && $type[0] == 'geocode' ? '' : 'position:absolute;left:-10000000px;';

		$address = $app->getUserStateFromRequest($baseContext . 'geocode' . $this->renderOrder, 'radius_search_geocode_field' . $this->renderOrder);
		list($latitude, $longitude) = $this->getSearchLatLon();
		$str[] = '<div class="radius_search_geocode input-append" style="' . $style . '">';
		$str[] = '<input type="text" class="radius_search_geocode_field"
			name="radius_search_geocode_field' . $this->renderOrder . '" value="' . $address . '" />';

		if (!$params->get('geocode_as_type', 1))
		{
			$str[] = '<button class="btn button">' . JText::_('COM_FABRIK_SEARCH') . '</button>';
		}

		$str[] = '<div class="radius_search_geocode_map" id="radius_search_geocode_map'
			. $this->renderOrder . '" style="width:400px;height:270px;margin-top:15px;"></div>';
		$str[] = '<input type="hidden" name="radius_search_geocode_lat' . $this->renderOrder . '" value="' . $latitude . '" />';
		$str[] = '<input type="hidden" name="radius_search_geocode_lon' . $this->renderOrder . '" value="' . $longitude . '" />';
		$str[] = '</div>';

		return implode("\n", $str);
	}

	/**
	 * Get the coordinates for a place
	 *
	 * @param   string  $place  value selected in widget
	 *
	 * @return  array
	 */

	private function placeCoordinates($place)
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		if (isset($this->placeCoordinates))
		{
			return $this->placeCoordinates;
		}

		$app = JFactory::getApplication();
		$input = $app->input;
		$model = $this->getModel();
		$mapElement = $this->getMapElement();
		$mapName = $mapElement->getFullName(true, false);
		$placeElement = $this->getPlaceElement()->getElement();
		$db = $model->getDb();
		$usekey = $input->get('usekey');
		$input->set('usekey', $placeElement->name);
		$row = $model->getRow($place);
		$input->set('usekey', $usekey);

		if (is_object($row))
		{
			$coords = explode(':', str_replace(array('(', ')'), '', $row->$mapName));
			$this->placeCoordinates = explode(',', $coords[0]);
		}
		else
		{
			// No exact match lets unset the query and try to find a partial match
			// (perhaps the user didn't select anything from the dropdown?)
			unset($model->getForm()->query);
			$row = $model->findRow($placeElement->name, $place);

			if (is_object($row))
			{
				$coords = explode(':', str_replace(array('(', ')'), '', $row->$mapName));
				$this->placeCoordinates = explode(',', $coords[0]);
			}
			else
			{
				$this->placeCoordinates = array('', '');
			}
		}

		return $this->placeCoordinates;
	}

	/**
	 * Not used I think
	 *
	 * @param   array  &$args  Filters created from listfilter::getPostFilters();
	 *
	 * @return  void
	 */

	public function onGetPostFilter(&$args)
	{
		// Returning here as was creating odd results with empty filters for other elements - seems to work without this anyway???
		return;
	}

	/**
	 * Build the sql query to filter the data
	 *
	 * @param   object  $params  plugin params
	 *
	 * @return  string  query's where statement
	 */

	protected function getQuery($params)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$values = JArrayHelper::getValue($this->filters, 'value', array());
		list($latitude, $longitude) = $this->getSearchLatLon();

		if (trim($latitude) === '' && trim($longitude) === '')
		{
			$input->set('radius_search_active' . $this->renderOrder, array(0));

			return;
		}
		// Need to unset for multiple radius searches to work
		unset($this->mapElement);
		$el = $this->getMapElement();
		$el = FabrikString::safeColName($el->getFullName(false, false));

		// Crazy sql to get the lat/lon from google map element
		$latfield = "SUBSTRING_INDEX(TRIM(LEADING '(' FROM $el), ',', 1)";
		$lonfield = "SUBSTRING_INDEX(SUBSTRING_INDEX($el, ',', -1), ')', 1)";
		$v = $this->getValue();

		if ($params->get('radius_unit', 'km') == 'km')
		{
			$query = "(((acos(sin((" . $latitude . "*pi()/180)) * sin(($latfield *pi()/180))+cos((" . $latitude
				. "*pi()/180)) * cos(($latfield *pi()/180)) * cos(((" . $longitude . "- $lonfield)*pi()/180))))*180/pi())*60*1.1515*1.609344) <= "
				. $v;
		}
		else
		{
			$query = "(((acos(sin((" . $latitude . "*pi()/180)) * sin(($latfield *pi()/180))+cos((" . $latitude
				. "*pi()/180)) * cos(($latfield *pi()/180)) * cos(((" . $longitude . "- $lonfield)*pi()/180))))*180/pi())*60*1.1515) <= " . $v;
		}

		return $query;
	}

	/**
	 * Get the searched for lat/lon
	 *
	 * @since   3.0.8
	 *
	 * @return  array
	 */

	protected function getSearchLatLon()
	{
		$app = JFactory::getApplication();
		$baseContext = $this->getSessionContext();
		$type = $app->input->get('radius_search_type' . $this->renderOrder, array(), 'array');
		$type = JArrayHelper::getValue($type, 0);

		switch ($type)
		{
			case 'place':
				$place = $app->getUserStateFromRequest($baseContext . 'radius_search_place' . $this->renderOrder, 'radius_search_place' . $this->renderOrder);
				list($latitude, $longitude) = $this->placeCoordinates($place);
				break;
			default:
				$latitude = $app->getUserStateFromRequest($baseContext . 'lat' . $this->renderOrder, 'radius_search_lat' . $this->renderOrder);
				$longitude = $app->getUserStateFromRequest($baseContext . 'lon' . $this->renderOrder, 'radius_search_lon' . $this->renderOrder);
				break;
			case 'geocode':
				$latitude = $app->getUserStateFromRequest($baseContext . 'lat' . $this->renderOrder, 'radius_search_geocode_lat' . $this->renderOrder);
				$longitude = $app->getUserStateFromRequest($baseContext . 'lon' . $this->renderOrder, 'radius_search_geocode_lon' . $this->renderOrder);
				break;
		}

		return array($latitude, $longitude);
	}

	/**
	 * onFiltersGot method - run after the list has created filters
	 *
	 * @return bool currently ignored
	 */

	public function onFiltersGot()
	{
		$params = $this->getParams();
		$model = $this->getModel();
		$key = $this->onGetFilterKey();
		$app = JFactory::getApplication();
		$active = $app->input->get('radius_search_active' . $this->renderOrder, array(0), 'array');

		if ($active[0] == 0)
		{
			return;
		}

		$v = $this->getValue();
		$query = $this->getQuery($params);
		$key = 'rs_test___map';
		$model->filters['elementid'][] = null;
		$model->filters['value'][] = $v;
		$model->filters['condition'][] = '=';
		$model->filters['join'][] = 'AND';
		$model->filters['no-filter-setup'][] = 0;
		$model->filters['hidden'][] = 0;
		$model->filters['key'][] = $key;
		$model->filters['search_type'][] = 'normal';
		$model->filters['match'][] = 0;
		$model->filters['full_words_only'][] = 0;
		$model->filters['eval'][] = 0;
		$model->filters['required'][] = 0;
		$model->filters['access'][] = 0;
		$model->filters['grouped_to_previous'][] = 0;
		$model->filters['label'][] = $params->get('radius_label', 'Radius search');
		$model->filters['sqlCond'][] = $query;
		$model->filters['origvalue'][] = $v;
		$model->filters['filter'][] = $v;
	}

	/**
	 * Get radius search distance value
	 *
	 * @return number
	 */

	protected function getValue()
	{
		$baseContext = $this->getSessionContext();
		$app = JFactory::getApplication();
		$v = $app->getUserStateFromRequest($baseContext . 'radius_search_distance' . $this->renderOrder, 'radius_search_distance' . $this->renderOrder, '');

		if ($v == '')
		{
			return;
		}

		$v = (int) $v;

		return $v;
	}

	/**
	 * Build the html for the distance slider
	 *
	 * @return  string
	 */

	private function slider()
	{
		$v = $this->getValue();
		FabrikHelperHTML::stylesheet('plugins/fabrik_list/radius_search/radius_search.css');
		$str = array();
		$str[] = '<div class="slider_cont" style="width:200px;">';
		$str[] = '<div class="fabrikslider-line" style="width:200px">';
		$str[] = '<div class="knob"></div>';
		$str[] = '</div>';
		$str[] = '<input type="hidden" class="radius_search_distance" name="radius_search_distance' . $this->renderOrder . '" value="' . $v . '"/>';
		$str[] = '<div class="slider_output">' . $v . $this->getParams()->get('radius_unit', 'km') . '</div>';
		$str[] = '</div>';

		return implode("\n", $str);
	}

	/**
	 * Can the plug-in select list rows
	 *
	 * @return  bool
	 */

	public function canSelectRows()
	{
		return false;
	}

	/**
	 * Get the place element model
	 * Don't get a cached version as 2 plugins may be set
	 *
	 * @return  object  Place element model
	 */

	private function getPlaceElement()
	{
		$model = $this->getModel();
		$elements = $model->getElements('id', false);
		$params = $this->getParams();

		if (!array_key_exists($params->get('radius_placeelement'), $elements))
		{
			throw new RuntimeException('No place element found for radius search plugin', 500);
		}
		else
		{
			$this->placeElement = $elements[$params->get('radius_placeelement')];

			return $this->placeElement;
		}
	}

	/**
	 * Get the map element model
	 * Don't get a cached version as 2 plugins may be set
	 *
	 * @return  object  Element model
	 */

	private function getMapElement()
	{
		$params = $this->getParams();
		$model = $this->getModel();
		$elements = $model->getElements('id');
		$this->mapElement = JArrayHelper::getValue($elements, $params->get('radius_mapelement'), false);

		if ($this->mapElement === false)
		{
			throw new RuntimeException('Radius Search Plugin: no map element selected', 500);
		}

		return $this->mapElement;
	}

	/**
	 * Load the javascript class that manages plugin interaction
	 * should only be called once
	 *
	 * @return  string  Javascript class file
	 */

	public function loadJavascriptClass()
	{
		$params = $this->getParams();
		$model = $this->getModel();
		$mapelement = $this->getMapElement();

		if (!is_object($mapelement))
		{
			throw new RuntimeException('Radius search plug-in active but map element unpublished');

			return;
		}

		$opts = array();
		$opts['container'] = 'radius_search_place_container';

		// Increase z-index with advanced class
		$opts['menuclass'] = 'auto-complete-container advanced';
		$listid = $model->get('id');
		$formid = $model->getFormModel()->get('id');

		if ($params->get('place', 1) == 1)
		{
			$el = $this->getPlaceElement();
			FabrikHelperHTML::autoComplete("radius_search_place{$this->renderOrder}", $el->getElement()->id, $formid, $el->getElement()->plugin, $opts);
		}

		if ($params->get('myloc', 1) == 1)
		{
			FabrikHelperHTML::script('components/com_fabrik/libs/geo-location/geo.js');
		}

		parent::loadJavascriptClass();
	}

	/**
	 * Return the javascript to create an instance of the class defined in formJavascriptClass
	 *
	 * @param   array  $args  Array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 */

	public function onLoadJavascriptInstance($args)
	{
		parent::onLoadJavascriptInstance($args);

		if (!is_object($this->getMapElement()))
		{
			return false;
		}

		$params = $this->getParams();
		$app = JFactory::getApplication();
		list($latitude, $longitude) = $this->getSearchLatLon();
		$opts = $this->getElementJSOptions();
		$containerOverride = JArrayHelper::getValue($args, 0, '');

		if (strstr($containerOverride, 'visualization'))
		{
			$opts->ref = str_replace('visualization_', '', $containerOverride);
		}

		$opts->steps = (int) $params->get('radius_max', 100);
		$opts->unit = $params->get('radius_unit', 'km');
		$opts->value = $this->getValue();
		$opts->lat = $latitude;
		$opts->lon = $longitude;
		$prefilterDistance = $params->get('prefilter_distance', '');
		$opts->prefilter = $prefilterDistance === '' ? false : true;
		$opts->prefilterDone = (bool) $app->input->getBool('radius_prefilter', false);
		$opts->prefilterDistance = $prefilterDistance;
		$opts->myloc = $params->get('myloc', 1) == 1 ? true : false;
		$o = FabrikString::mapStrToCoords($params->get('geocode_default', ''));
		$opts->geocode_default_lat = $o->lat;
		$opts->geocode_default_long = $o->long;
		$opts->geocode_default_zoom = (int) $o->zoom;
		$opts->geoCodeAsType = $params->get('geocode_as_type', 1);
		$opts->renderOrder = $this->renderOrder;
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListRadiusSearch($opts)";

		JText::script('PLG_LIST_RADIUS_SEARCH_CLEAR_CONFIRM');

		return true;
	}

	/**
	 * Overridden by plugins if necessary.
	 * If the plugin is a filter plugin, return true if it needs the 'form submit'
	 * method, i.e. the Go button.  Implemented specifically for radius search plugin.
	 *
	 * @return  bool
	 */

	public function requireFilterSubmit()
	{
	}

	/**
	 * Overridden by plugins if necessary.
	 * If the plugin is a filter plugin, return true if it needs the 'form submit'
	 * method, i.e. the Go button.  Implemented specifically for radius search plugin.
	 *
	 * @return  bool
	 */
	public function requireFilterSubmit_result()
	{
		return true;
	}
}
