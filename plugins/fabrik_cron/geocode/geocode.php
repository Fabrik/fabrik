<?php
/**
 * A cron task to email records to a give set of users
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.geocode
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-cron.php';

require_once JPATH_SITE . '/plugins/fabrik_cron/geocode/libs/gmaps2.php';

/**
 * The cron notification plugin model.
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.geocode
 * @since       3.0
 */

class PlgFabrik_CronGeocode extends PlgFabrik_Cron
{
	/**
	 * Check if the user can use the active element
	 *
	 * @param   string  $location  To trigger plugin on
	 * @param   string  $event     To trigger plugin on
	 *
	 * @return  bool can use or not
	 */

	public function canUse($location = null, $event = null)
	{
		return true;
	}

	/**
	 * Whether cron should automagically load table data
	 *
	 * @return  bool
	 */

	public function requiresTableData()
	{
		return true;
	}

	/**
	 * Do the plugin action
	 *
	 * @param   array   &$data       array data to process
	 * @param   object  &$listModel  plugin's list model
	 *
	 * @return  int  number of records run
	 */

	public function process(&$data, &$listModel)
	{
		$params = $this->getParams();

		$db = $listModel->getDb();
		$query = $db->getQuery(true);

		// Grab the table model and find table name and PK
		$table = $listModel->getTable();
		$table_name = $table->db_table_name;
		$primary_key = $table->db_primary_key;
		$primary_key_element = FabrikString::shortColName($table->db_primary_key);
		$primary_key_element_long = $table_name . '___' . $primary_key_element . '_raw';

		$config = JComponentHelper::getParams('com_fabrik');
		$apiKey = $config->get('google_api_key', '');

		$connection = (int) $params->get('connection');

		/*
		 * For now, we have to read the table ourselves.  We can't rely on the $data passed to us
		 * because it can be arbitrarily filtered according to who happened to hit the page when cron
		 * needed to run.
		 */
		/*
		$mydata = array();
		$db = FabrikWorker::getDbo(false, $connection);
		$query = $db->getQuery(true);
		$query->select('*')->from($table_name);
		$db->setQuery($query);
		$mydata[0] = $db->loadObjectList();
		*/
		// Grab all the params, like GMaps key, field names to use, etc.

		$geocode_batch_limit = (int) $params->get('geocode_batch_limit', '0');
		$geocode_delay = (int) $params->get('geocode_delay', '0');
		$geocode_is_empty = $params->get('geocode_is_empty');
		$geocode_zoom_level = $params->get('geocode_zoom_level', '4');
		$geocode_map_element_long = $params->get('geocode_map_element');
		$geocode_map_element = FabrikString::shortColName($geocode_map_element_long);
		$geocode_addr1_element_long = $params->get('geocode_addr1_element');
		$geocode_addr1_element = $geocode_addr1_element_long ? FabrikString::shortColName($geocode_addr1_element_long) : '';
		$geocode_addr2_element_long = $params->get('geocode_addr2_element');
		$geocode_addr2_element = $geocode_addr2_element_long ? FabrikString::shortColName($geocode_addr2_element_long) : '';
		$geocode_city_element_long = $params->get('geocode_city_element');
		$geocode_city_element = $geocode_city_element_long ? FabrikString::shortColName($geocode_city_element_long) : '';
		$geocode_state_element_long = $params->get('geocode_state_element');
		$geocode_state_element = $geocode_state_element_long ? FabrikString::shortColName($geocode_state_element_long) : '';
		$geocode_zip_element_long = $params->get('geocode_zip_element');
		$geocode_zip_element = $geocode_zip_element_long ? FabrikString::shortColName($geocode_zip_element_long) : '';
		$geocode_country_element_long = $params->get('geocode_country_element');
		$geocode_country_element = $geocode_country_element_long ? FabrikString::shortColName($geocode_country_element_long) : '';
		$geocode_when = $params->get('geocode_when', '1');

		$gmap = new GeoCode;

		// Run through our table data
		$total_encoded = 0;
		$total_attempts = 0;

		foreach ($data as $gkey => $group)
		{
			if (is_array($group))
			{
				foreach ($group as $rkey => $row)
				{
					if ($geocode_batch_limit > 0 && $total_attempts >= $geocode_batch_limit)
					{
						FabrikWorker::log('plg.cron.geocode.information', 'reached batch limit');
						break 2;
					}
					/*
					 * See if the map element is considered empty
					 * Values of $geocode_when are:
					 * 1: default or empty
					 * 2: empty
					 * 3: always
					 */
					$do_geocode = true;

					if ($geocode_when == '1')
					{
						$do_geocode = empty($row->$geocode_map_element_long) || $row->$geocode_map_element_long == $geocode_is_empty;
					}
					elseif ($geocode_when == '2')
					{
						$do_geocode = empty($row->$geocode_map_element_long);
					}

					if ($do_geocode)
					{
						/*
						 * It's empty, so lets try and geocode.
						 * first, construct the address
						 * we'll build an array of address components, which we'll explode into a string later
						 */
						$a_full_addr = array();
						/*
						 * For each address component element, see if one is specific in the params,
						 * if so, see if it has a value in this row
						 * if so, add it to the address array.
						 */
						if ($geocode_addr1_element_long)
						{
							if ($row->$geocode_addr1_element_long)
							{
								$a_full_addr[] = $row->$geocode_addr1_element_long;
							}
						}

						if ($geocode_addr2_element_long)
						{
							if ($row->$geocode_addr2_element_long)
							{
								$a_full_addr[] = $row->$geocode_addr2_element_long;
							}
						}

						if ($geocode_city_element_long)
						{
							if ($row->$geocode_city_element_long)
							{
								$a_full_addr[] = $row->$geocode_city_element_long;
							}
						}

						if ($geocode_state_element_long)
						{
							if ($row->$geocode_state_element_long)
							{
								$a_full_addr[] = $row->$geocode_state_element_long;
							}
						}

						if ($geocode_zip_element_long)
						{
							if ($row->$geocode_zip_element_long)
							{
								$a_full_addr[] = $row->$geocode_zip_element_long;
							}
						}

						if ($geocode_country_element_long)
						{
							if ($row->$geocode_country_element_long)
							{
								$a_full_addr[] = $row->$geocode_country_element_long;
							}
						}
						// Now explode the address into a string
						$full_addr = implode(',', $a_full_addr);

						// Did we actually get an address?
						if (!empty($full_addr))
						{
							// OK!  Lets try and geocode it ...
							$total_attempts++;
							$full_addr = urlencode($full_addr);
							$res = $gmap->getLatLng($full_addr, 'array', $apiKey);

							if ($res['status'] == 'OK')
							{
								$lat = $res['lat'];
								$long = $res['lng'];

								if (!empty($lat) && !empty($long))
								{
									$map_value = "($lat,$long):$geocode_zoom_level";
									/*
									$query->clear();
									$query->update($table_name)->set($geocode_map_element . ' = ' . $db->quote($map_value))
									->where($primary_key . ' = ' . $db->quote($row->$primary_key_element_long));
									$db->setQuery($query);
									$db->execute();
									*/
									$listModel->storeCell($row->$primary_key_element_long, $geocode_map_element, $map_value);
									$total_encoded++;
								}
							}
							else
							{
								$logMsg = sprintf('Error (%s), id %s , no geocode result for: %s', $res['status'], $row->$primary_key_element_long, $full_addr);
								FabrikWorker::log('plg.cron.geocode.information', $logMsg);
							}

							if ($geocode_delay > 0)
							{
								usleep($geocode_delay);
							}
						}
						else
						{
							FabrikWorker::log('plg.cron.geocode.information', 'empty address, id = '.$row->$primary_key_element_long);
						}
					}
				}
			}
		}
		FabrikWorker::log('plg.cron.geocode.information', 'Total encoded: '.$total_encoded);
		return $total_encoded;
	}
}
