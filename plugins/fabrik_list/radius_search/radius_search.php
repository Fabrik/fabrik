<?php
/**
 * Add a radius search option to the list filters
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.radiussearch
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
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
class PlgFabrik_ListRadius_search extends PlgFabrik_List
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
		$default_search = $type[0];

		if ($params->get('myloc', 1) == 1)
		{
			$options[] = JHtml::_('select.option', 'mylocation', FText::_('PLG_VIEW_RADIUS_MY_LOCATION'));
		}
		else if ($default_search == 'mylocation')
		{
			$default_search = 'place';
		}

		if ($params->get('place', 1) == 1)
		{
			$placeElement = $this->getPlaceElement()->getElement();
			$options[] = JHtml::_('select.option', 'place', strip_tags($placeElement->label));
		}
		else if ($default_search == 'place')
		{
			$default_search = 'coords';
		}

		if ($params->get('coords', 1) == 1)
		{
			$options[] = JHtml::_('select.option', 'latlon', FText::_('PLG_VIEW_RADIUS_COORDINATES'));
		}
		else if ($default_search == 'latlon')
		{
			$default_search = 'geocode';
		}

		if ($params->get('geocode', 1) == 1)
		{
			$options[] = JHtml::_('select.option', 'geocode', FText::_('PLG_VIEW_RADIUS_GEOCODE'));
		}

		$selectName = 'radius_search_type' . $this->renderOrder . '[]';
		$select = JHtml::_('select.genericlist', $options, $selectName, '', 'value', 'text', $default_search);

		return $select;
	}

	/**
	 * Build the select list for distance options in simple mode
	 *
	 * @param   array  $default  Selected distance
	 *
	 * @return mixed
	 */
	protected function distanceSelectList($default)
	{
		$options = array();
		$params = $this->getParams();
		$distances = $params->get('radius_simple_distances', '1,10,100');
		$distances = explode(',', str_replace(' ', '', $distances));

		foreach ($distances as $distance)
		{
			$options[] = JHtml::_('select.option', $distance, $distance);
		}

		$selectName = 'radius_search_distance' . $this->renderOrder . '[]';
		$select = JHtml::_('select.genericlist', $options, $selectName, 'class="input-small"', 'value', 'text', $default);

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

		$model = $this->getModel();
		$params = $this->getParams();
		$f = new stdClass;
		$f->label = FText::_($params->get('radius_label', 'Radius search'));
		FabrikHelperHTML::stylesheet('plugins/fabrik_list/radius_search/radius_search.css');

		$layoutData = new stdClass;
		$layoutData->renderOrder = $this->renderOrder;
		$layoutData->baseContext = $this->getSessionContext();
		$layoutData->defaultSearch = $params->get('default_search', 'mylocation');
		$layoutData->geocodeDefault = $params->get('geocode_default', '');
		$layoutData->unit = $this->getParams()->get('radius_unit', 'km');
		$layoutData->distance = $this->getValue();
		$layoutData->startActive = $params->get('start_active', 0);
		$typeKey = $layoutData->baseContext . 'radius_search_type' . $this->renderOrder;
		$type = $this->app->getUserStateFromRequest($typeKey, 'radius_search_type' . $this->renderOrder, array($layoutData->defaultSearch));
		$layoutData->select = $this->searchSelectList($type);
		$layoutData->type = $type[0];
		list($layoutData->searchLatitude, $layoutData->searchLongitude) = $this->getSearchLatLon();
		$layoutData->geocodeAsYouType = $params->get('geocode_as_type', 1);
		$layoutData->hasGeocode = $params->get('geocode', 1) == 1;
		$layoutData->usePopup = $params->get('radius_use_popup', '1') === '1';
		$layoutData->simpleDistances = $this->distanceSelectList($this->getValue());
		$active    = $this->app->getUserStateFromRequest($layoutData->baseContext . 'radius_search_active', 'radius_search_active' . $this->renderOrder, array($layoutData->startActive));

		if ($layoutData->usePopup)
		{
			$layoutData->address = $this->app->getUserStateFromRequest($layoutData->baseContext . 'geocode' . $this->renderOrder, 'radius_search_geocode_field' . $this->renderOrder);
			$lat   = $this->app->getUserStateFromRequest($layoutData->baseContext . 'lat' . $this->renderOrder, 'radius_search_lat' . $this->renderOrder);
			$lon   = $this->app->getUserStateFromRequest($layoutData->baseContext . 'lon' . $this->renderOrder, 'radius_search_lon' . $this->renderOrder);

		}
		else
		{
			// don't use session for simple mode
			$active = $this->app->input->get('radius_search_active' . $this->renderOrder, array(0), 'array');
			if ($active[0] == 0)
			{
				$layoutData->address = '';
				$lat = $layoutData->searchLatitude = '';
				$lon = $layoutData->searchLongitude = '';
				$layoutData->emptyMsgClass = 'fabrikHide';
			}
			else
			{
				$layoutData->address = $this->app->getUserStateFromRequest($layoutData->baseContext . 'geocomplete' . $this->renderOrder, 'radius_search_geocomplete_field' . $this->renderOrder);
				$lat                 = $this->app->getUserStateFromRequest($layoutData->baseContext . 'lat' . $this->renderOrder, 'radius_search_geocomplete_lat' . $this->renderOrder);
				$lon                 = $this->app->getUserStateFromRequest($layoutData->baseContext . 'lon' . $this->renderOrder, 'radius_search_geocomplete_lon' . $this->renderOrder);
				$rowCount            = (int) $model->getTotalRecords();
				if ($rowCount === 0 && !empty($layoutData->address))
				{
					$layoutData->emptyMsgClass = '';
				}
				else
				{
					$layoutData->emptyMsgClass = 'fabrikHide';
				}
			}
		}

		$o = FabrikString::mapStrToCoords($layoutData->geocodeDefault);
		$layoutData->defaultLat  = $lat ? $lat : (float) $o->lat;
		$layoutData->defaultLon  = $lon ? $lon : (float) $o->long;
		$layoutData->defaultZoom = (int) $o->zoom === 0 ? 7 : (int) $o->zoom;
		$layoutData->lat = $lat;
		$layoutData->lon = $lon;
		$layoutData->active = $active[0];

		$layout = $this->getLayout('filters');
		$str = $layout->render($layoutData);

		$f->element = $str;
		$f->required = '';

		if ($this->app->input->get('format') !== 'raw')
		{
			FabrikHelperHTML::addStyleDeclaration("table.radius_table{border-collapse:collapse;border:0;}
			table.radius_table td{border:0;}");
		}

		JText::script('PLG_VIEW_RADIUS_NO_GEOLOCATION_AVAILABLE');
		JText::script('COM_FABRIK_SEARCH');
		JText::script('PLG_LIST_RADIUS_SEARCH');
		JText::script('PLG_LIST_RADIUS_SEARCH_BUTTON');

		$mapElement = $this->getMapElement();
		$mapName = $mapElement->getFullName(true, false);
		$model->viewfilters[$mapName] = $f;
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
		if (isset($this->placeCoordinates))
		{
			return $this->placeCoordinates;
		}

		$this->session->set('fabrik.list.radius_search.filtersGot.ignore', true);
		$input = $this->app->input;

		/** @var  $model FabrikFEModelList */
		$model = $this->getModel();
		$mapElement = $this->getMapElement();
		$mapName = $mapElement->getFullName(true, false);
		$placeElement = $this->getPlaceElement()->getElement();
		$useKey = $input->get('usekey');
		$input->set('usekey', $placeElement->name);
		$row = $model->getRow($place);
		$input->set('usekey', $useKey);

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
		$input = $this->app->input;
		list($latitude, $longitude) = $this->getSearchLatLon();

		if (trim($latitude) === '' && trim($longitude) === '')
		{
			$input->set('radius_search_active' . $this->renderOrder, array(0));

			return '';
		}
		// Need to unset for multiple radius searches to work
		unset($this->mapElement);
		$el = $this->getMapElement();
		$el = FabrikString::safeColName($el->getFullName(false, false));

		// Crazy sql to get the lat/lon from google map element
		$latField = "SUBSTRING_INDEX(TRIM(LEADING '(' FROM $el), ',', 1)";
		$lonField = "SUBSTRING_INDEX(SUBSTRING_INDEX($el, ',', -1), ')', 1)";
		$v = $this->getValue();

		if ($params->get('radius_unit', 'km') == 'km')
		{
			$query = "(((acos(sin((" . $latitude . "*pi()/180)) * sin(($latField *pi()/180))+cos((" . $latitude
				. "*pi()/180)) * cos(($latField *pi()/180)) * cos(((" . $longitude . "- $lonField)*pi()/180))))*180/pi())*60*1.1515*1.609344) <= "
				. $v;
		}
		else
		{
			$query = "(((acos(sin((" . $latitude . "*pi()/180)) * sin(($latField *pi()/180))+cos((" . $latitude
				. "*pi()/180)) * cos(($latField *pi()/180)) * cos(((" . $longitude . "- $lonField)*pi()/180))))*180/pi())*60*1.1515) <= " . $v;
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
		$baseContext = $this->getSessionContext();
		$type = $this->app->input->get('radius_search_type' . $this->renderOrder, array(), 'array');
		$type = FArrayHelper::getValue($type, 0);

		switch ($type)
		{
			case 'place':
				$place = $this->app->getUserStateFromRequest($baseContext . 'radius_search_place' . $this->renderOrder, 'radius_search_place' . $this->renderOrder);
				list($latitude, $longitude) = $this->placeCoordinates($place);
				break;
			default:
				$latitude = $this->app->getUserStateFromRequest($baseContext . 'lat' . $this->renderOrder, 'radius_search_lat' . $this->renderOrder);
				$longitude = $this->app->getUserStateFromRequest($baseContext . 'lon' . $this->renderOrder, 'radius_search_lon' . $this->renderOrder);
				break;
			case 'geocode':
				$latitude = $this->app->getUserStateFromRequest($baseContext . 'lat' . $this->renderOrder, 'radius_search_geocode_lat' . $this->renderOrder);
				$longitude = $this->app->getUserStateFromRequest($baseContext . 'lon' . $this->renderOrder, 'radius_search_geocode_lon' . $this->renderOrder);
				break;
			case 'geocomplete':
				$latitude = $this->app->getUserStateFromRequest($baseContext . 'lat' . $this->renderOrder, 'radius_search_geocomplete_lat' . $this->renderOrder);
				$longitude = $this->app->getUserStateFromRequest($baseContext . 'lon' . $this->renderOrder, 'radius_search_geocomplete_lon' . $this->renderOrder);
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
		if ($this->session->get('fabrik.list.radius_search.filtersGot.ignore'))
		{
			$this->session->clear('fabrik.list.radius_search.filtersGot.ignore');
			return true;
		}

		$params = $this->getParams();

		/** @var  $model FabrikFEModelList */
		$model = $this->getModel();
		$active = $this->app->input->get('radius_search_active' . $this->renderOrder, array(0), 'array');

		if ($active[0] == 0)
		{
			return true;
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
		$params = $this->getParams();

		$baseContext = $this->getSessionContext();
		$v           = $this->app->getUserStateFromRequest($baseContext . 'radius_search_distance' . $this->renderOrder, 'radius_search_distance' . $this->renderOrder, '');

		if (is_array($v))
		{
			$v = array_pop($v);
		}

		return (int) $v;
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
		$this->mapElement = FArrayHelper::getValue($elements, $params->get('radius_mapelement'), false);

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
		$mapElement = $this->getMapElement();

		if (!is_object($mapElement))
		{
			throw new RuntimeException('Radius search plug-in active but map element unpublished');

			return;
		}

		$opts = array();
		$opts['container'] = 'radius_search_place_container';

		// Increase z-index with advanced class
		$opts['menuclass'] = 'auto-complete-container advanced';
		$formId = $model->getFormModel()->get('id');

		if ($params->get('place', 1) == 1)
		{
			$el = $this->getPlaceElement();
			FabrikHelperHTML::autoComplete("radius_search_place{$this->renderOrder}", $el->getElement()->id, $formId, $el->getElement()->plugin, $opts);
		}

		if ($params->get('myloc', 1) == 1)
		{
			$ext = FabrikHelperHTML::isDebug() ? '.js' : '-min.js';
			FabrikHelperHTML::script('media/com_fabrik/js/lib/geo-location/geo' . $ext);
		}

		if ($params->get('radius_use_popup', '1') === '0')
		{
			$ext = FabrikHelperHTML::isDebug() ? '.js' : '-min.js';
			FabrikHelperHTML::script('components/com_fabrik/libs/googlemaps/geocomplete/jquery.geocomplete' . $ext);
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
		list($latitude, $longitude) = $this->getSearchLatLon();
		$opts = $this->getElementJSOptions();
		$containerOverride = FArrayHelper::getValue($args, 0, '');

		if (strstr($containerOverride, 'visualization'))
		{
			$opts->ref = str_replace('visualization_', '', $containerOverride);
		}

		$opts->steps = (int) $params->get('radius_max', 100);
		$opts->unit = $params->get('radius_unit', 'km');
		$opts->value = $this->getValue();
		$opts->lat = $latitude;
		$opts->lon = $longitude;
		$preFilterDistance = $params->get('prefilter_distance', '');
		$opts->prefilter = $preFilterDistance === '' ? false : true;
		$opts->prefilterDone = (bool) $this->app->input->getBool('radius_prefilter', false);
		$opts->prefilterDistance = $preFilterDistance;
		$opts->myloc = $params->get('myloc', 1) == 1 ? true : false;
		$o = FabrikString::mapStrToCoords($params->get('geocode_default', ''));
		$opts->geocode_default_lat = $o->lat;
		$opts->geocode_default_long = $o->long;
		$opts->geocode_default_zoom = (int) $o->zoom;
		$opts->geoCodeAsType = $params->get('geocode_as_type', 1);
		$opts->renderOrder = $this->renderOrder;
		$opts->offset_y = (int)$params->get('window_offset_y', '0');
		$config = JComponentHelper::getParams('com_fabrik');
		$apiKey = trim($config->get('google_api_key', ''));
		$opts->key = empty($apiKey) ? false : $apiKey;
		$opts->language = trim(strtolower($config->get('google_api_language', '')));

		$opts->usePopup = $params->get('radius_use_popup', '1') === '1';
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListRadius_search($opts)";

		JText::script('PLG_LIST_RADIUS_SEARCH_CLEAR_CONFIRM');
		JText::script('PLG_LIST_RADIUS_SEARCH_GEOCODE_ERROR');

		return true;
	}

	/**
	 * Load the AMD module class name
	 *
	 * @return string
	 */
	public function loadJavascriptClassName_result()
	{
		return 'FbListRadiusSearch';
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
