<?php
/**
 * Add a radius lookup option to the list filters
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.radiussearch
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Add a radius search option to the list filters
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.radiuslookup
 * @since       3.2
 */

class PlgFabrik_ListRadius_lookup extends PlgFabrik_List
{
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
		$baseContext = $this->getSessionContext();
		$listModel = new FabrikFEModelList();
		$listModel->setId($params->get('radius_lookup_list'));

		$layoutData = array('renderOrder' => $this->renderOrder);
		$layoutData['vals'] = $this->app->input->get('radius_lookup' . $this->renderOrder, array(), 'array');
		$layoutData['lat'] = $this->app->getUserStateFromRequest($baseContext . 'lat' . $this->renderOrder, 'radius_search_lat' . $this->renderOrder);
		$layoutData['lon'] = $this->app->getUserStateFromRequest($baseContext . 'lon' . $this->renderOrder, 'radius_search_lon' . $this->renderOrder);
		$layoutData['data'] = $listModel->getData();
		$layoutData['distanceField'] = $params->get('distance_field');
		$layoutData['nameField'] = $params->get('name_field');

		$layout = $this->getLayout('filters');

		$str = $layout->render($layoutData);

		$f = new stdClass;
		$f->label = $params->get('radius_label', 'Radius search');
		$f->element = $str;
		$f->required = '';

		$mapElement = $this->getMapElement();
		$mapName = $mapElement->getFullName(true, false);
		$model->viewfilters[$mapName] = $f;
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
		$lookUps = $input->get('radius_lookup' . $this->renderOrder, array(), 'array');
		$lookUps = array_filter($lookUps, function ($v) { return (string) $v === '1'; });
		$ids = array_keys($lookUps);
		$ids = ArrayHelper::toInteger($ids);

		$listModel = new FabrikFEModelList();
		$listModel->setId($params->get('radius_lookup_list'));
		$listModel->setLimits(0, -1);
		$key = $listModel->getPrimaryKey();
		$listModel->setPluginQueryWhere('list.radius_lookup', $key . ' IN (' . implode(',', $ids) . ')');
		$data = $listModel->getData();
		$distanceField = $params->get('distance_field') . '_raw';
		$data = $listModel->getData();

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
		$latField = "SUBSTRING_INDEX(TRIM(LEADING '(' FROM $el), ',', 1)";
		$lonField = "SUBSTRING_INDEX(SUBSTRING_INDEX($el, ',', -1), ')', 1)";
		$query = array();
		$unit = $params->get('radius_lookup_unit', 'km');

		foreach ($data as $group)
		{
			foreach ($group as $row)
			{
				$v = $row->$distanceField;

				if ($unit == 'km')
				{
					$query[] = "((((acos(sin((" . $latitude . "*pi()/180)) * sin(($latField *pi()/180))+cos((" . $latitude
					. "*pi()/180)) * cos(($latField *pi()/180)) * cos(((" . $longitude . "- $lonField)*pi()/180))))*180/pi())*60*1.1515*1.609344) <= "
					. $v . ')';
				}
				else
					{
					$query[] = "((((acos(sin((" . $latitude . "*pi()/180)) * sin(($latField *pi()/180))+cos((" . $latitude
					. "*pi()/180)) * cos(($latField *pi()/180)) * cos(((" . $longitude . "- $lonField)*pi()/180))))*180/pi())*60*1.1515) <= " . $v . ')';
					}
			}
		}

		$query = '(' . implode(' OR ', $query) . ')';

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
		$latitude = $app->getUserStateFromRequest($baseContext . 'lat' . $this->renderOrder, 'radius_search_lat' . $this->renderOrder);
		$longitude = $app->getUserStateFromRequest($baseContext . 'lon' . $this->renderOrder, 'radius_search_lon' . $this->renderOrder);

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

		/** @var FabrikFEModelList $model */
		$model = $this->getModel();
		$key = $this->onGetFilterKey();
		$app = JFactory::getApplication();
		$lookUps = $app->input->get('radius_lookup' . $this->renderOrder, array(), 'array');
		$active = in_array('1', $lookUps);

		if (!$active)
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
		return $this->getSearchLatLon();
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
		$this->mapElement = FArrayHelper::getValue($elements, $params->get('radius_lookup_mapelement'), false);

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
		$opts = array();
		$opts['container'] = 'radius_search_place_container';

		// Increase z-index with advanced class
		$opts['menuclass'] = 'auto-complete-container advanced';

		if ($params->get('myloc', 1) == 1)
		{
			$ext = FabrikHelperHTML::isDebug() ? '.js' : '-min.js';
			FabrikHelperHTML::script('media/com_fabrik/js/lib/geo-location/geo' . $ext);
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

		$opts->value = $this->getValue();
		$opts->lat = $latitude;
		$opts->lon = $longitude;
		$opts->myloc = $params->get('myloc', 1) == 1 ? true : false;
		$opts->renderOrder = $this->renderOrder;
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListRadius_lookup($opts)";
		return true;
	}

	/**
	 * Load the AMD module class name
	 *
	 * @return string
	 */
	public function loadJavascriptClassName_result()
	{
		return 'FbListRadius_lookup';
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
