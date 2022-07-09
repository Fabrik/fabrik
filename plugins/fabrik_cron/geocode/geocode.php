<?php
/**
 * A cron task to email records to a give set of users
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.geocode
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
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
		$apiKey = trim($config->get('google_api_key', ''));

		//$connection = (int) $params->get('connection');

		$geocode_batch_limit = (int) $params->get('geocode_batch_limit', '0');
		$geocode_delay = (int) $params->get('geocode_delay', '0');
		$geocode_is_empty = $params->get('geocode_is_empty');
		$geocode_zoom_level = $params->get('geocode_zoom_level', '4');
		$geocode_map_element_long = $params->get('geocode_map_element');
		$geocode_map_element_long_raw = $geocode_map_element_long . '_raw';
		$geocode_map_element = FabrikString::shortColName($geocode_map_element_long);
		$geocode_lat_element_long = $params->get('geocode_lat_element');
		$geocode_lat_element_long_raw = $geocode_lat_element_long . '_raw';
		$geocode_lat_element = FabrikString::shortColName($geocode_lat_element_long);
		$geocode_lon_element_long = $params->get('geocode_lon_element');
		$geocode_lon_element_long_raw = $geocode_lon_element_long . '_raw';
		$geocode_lon_element = FabrikString::shortColName($geocode_lon_element_long);
		$geocode_addr1_element_long = $params->get('geocode_addr1_element');
		$geocode_addr1_element = $geocode_addr1_element_long ? FabrikString::shortColName($geocode_addr1_element_long) : '';
		$geocode_addr2_element_long = $params->get('geocode_addr2_element');
		$geocode_addr2_element = $geocode_addr2_element_long ? FabrikString::shortColName($geocode_addr2_element_long) : '';
		$geocode_city_element_long = $params->get('geocode_city_element');
		$geocode_city_element = $geocode_city_element_long ? FabrikString::shortColName($geocode_city_element_long) : '';
		$geocode_county_element_long = $params->get('geocode_county_element');
		$geocode_county_element = $geocode_county_element_long ? FabrikString::shortColName($geocode_county_element_long) : '';
		$geocode_municipality_element_long = $params->get('geocode_municipality_element');
		$geocode_municipality_element = $geocode_municipality_element_long ? FabrikString::shortColName($geocode_municipality_element_long) : '';
		$geocode_state_element_long = $params->get('geocode_state_element');
		$geocode_state_element = $geocode_state_element_long ? FabrikString::shortColName($geocode_state_element_long) : '';
		$geocode_zip_element_long = $params->get('geocode_zip_element');
		$geocode_zip_element = $geocode_zip_element_long ? FabrikString::shortColName($geocode_zip_element_long) : '';
		$geocode_country_element_long = $params->get('geocode_country_element');
		$geocode_country_element = $geocode_country_element_long ? FabrikString::shortColName($geocode_country_element_long) : '';
		$geocode_normalize_street_element_long = $params->get('geocode_normalize_street_element');
		$geocode_normalize_street_element = $geocode_normalize_street_element_long ? FabrikString::shortColName($geocode_normalize_street_element_long) : '';
		$geocode_when = $params->get('geocode_when', '1');
		$geocode_from = $params->get('geocode_from', '1');
		$geocode_normalize_format = $params->get('geocode_normalize_format', 'long');

		$config = JComponentHelper::getParams('com_fabrik');
		$verifyPeer = (bool) $config->get('verify_peer', '1');

		$gmap = new GeoCode($verifyPeer);

		// Run through our table data
		$total_encoded = 0;
		$total_attempts = 0;

		foreach ($data as $gkey => $group)
		{
			if (is_array($group))
			{
				foreach ($group as $rkey => $row)
				{
					$lat = '';
					$long = '';
					$fields = array();

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
						$do_geocode = empty($row->$geocode_map_element_long_raw) || $row->$geocode_map_element_long_raw == $geocode_is_empty;
					}
					elseif ($geocode_when == '2')
					{
						$do_geocode = empty($row->$geocode_map_element_long_raw);
					}

					// 1 - geocode from address components
					if ($geocode_from === '1')
					{
						if ($geocode_batch_limit > 0 && $total_attempts >= $geocode_batch_limit)
						{
							FabrikWorker::log('plg.cron.geocode.information', 'reached batch limit');
							break 2;
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
								$full_addr = urlencode(html_entity_decode($full_addr, ENT_QUOTES));
								$res       = $gmap->getLatLng($full_addr, 'array', $apiKey);

								if ($res['status'] == 'OK')
								{
									if (!empty($geocode_normalize_street_element))
									{
										$types = array();

										// pivot the address components by type
										foreach ($res['components'] as $component)
										{
											foreach ($component->types as $type)
											{
												$types[$type]['short_name'] = $component->short_name;
												$types[$type]['long_name']  = $component->long_name;
											}
										}

										if (array_key_exists('street_number', $types))
										{
											$fields[$geocode_normalize_street_element] = $types['street_number'][$geocode_normalize_format] . ' ' . $types['route'][$geocode_normalize_format];
										}
										else
										{
											$fields[$geocode_normalize_street_element] = $types['route'][$geocode_normalize_format];
										}
									}

									if (!empty($geocode_lat_element))
									{
										$fields[$geocode_lat_element] = $res['lat'];
									}

									if (!empty($geocode_lon_element))
									{
										$fields[$geocode_lon_element] = $res['lng'];
									}

									if (!empty($geocode_map_element))
									{
										$fields[$geocode_map_element] = "(" . $res['lat'] . "," . $res['lng'] . "):$geocode_zoom_level";
									}

									$total_encoded++;
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
								FabrikWorker::log('plg.cron.geocode.information', 'empty address, id = ' . $row->$primary_key_element_long);
							}
						}
					}
					// 2 - convert separate lat/lon elements to a map element, no geocoding
					else if ($geocode_from === '2')
					{
						$total_attempts++;

						if (!empty($geocode_map_element))
						{
							$lat = $row->$geocode_lat_element_long_raw;
							$long = $row->$geocode_lon_element_long_raw;
							$fields[$geocode_map_element] = "($lat,$long):$geocode_zoom_level";
						}

						$total_encoded++;
					}
					// 3 - bust map element out to separate lat/lon fields, no geocoding
					else if ($geocode_from === '3')
					{
						$total_attempts++;

						$coords = FabrikString::mapStrToCoords($row->$geocode_map_element_long_raw);

						if (!empty($geocode_lat_element))
						{
							$fields[$geocode_lat_element] = $coords->lat;
						}

						if (!empty($geocode_lon_element))
						{
							$fields[$geocode_lon_element] = $coords->long;
						}

						$total_encoded++;
					}
					// 4/5 - reverse geocode from map ot lat/lon
					else if ($geocode_from === '4' || $geocode_from === '5')
					{
						if ($geocode_batch_limit > 0 && $total_attempts >= $geocode_batch_limit)
						{
							FabrikWorker::log('plg.cron.geocode.information', 'reached batch limit');
							break 2;
						}

						if ($do_geocode)
						{
							switch ($geocode_from)
							{
								case '4':
									$lat  = $row->$geocode_lat_element_long_raw;
									$long = $row->$geocode_lon_element_long_raw;
									break;
								case '5':
									$coords = FabrikString::mapStrToCoords($row->$geocode_map_element_long_raw);
									$lat = $coords->lat;
									$long = $coords->long;
									break;
							}

							if (!empty($lat) && !empty($long))
							{
								// OK!  Lets try and geocode it ...
								$total_attempts++;
								$res       = $gmap->getAddress($lat, $long, 'array', $apiKey);

								if ($res['status'] == 'OK')
								{
									$types = array();

									// pivot the address components by type
									foreach($res['components'] as $component)
									{
										foreach ($component->types as $type)
										{
											$types[$type]['short_name'] = $component->short_name;
											$types[$type]['long_name'] = $component->long_name;
										}
									}

									/**
									 * Assign selected address components to their fields
									 *
									 * @todo - add UI to choose short / long form for each address component
									 */

									if (!empty($geocode_addr1_element))
									{
										if (array_key_exists('street_number', $types))
										{
											$fields[$geocode_addr1_element] = $types['street_number']['long_name'] . ' ' . $types['route']['long_name'];
										}
										else
										{
											$fields[$geocode_addr1_element] = $types['route']['long_name'];
										}
									}

									if (!empty($geocode_city_element))
									{
										$fields[$geocode_city_element] = $types['locality']['long_name'];
									}

									if (!empty($geocode_county_element))
									{
										$fields[$geocode_city_element] = $types['administrative_area_level_2']['long_name'];
									}

									if (!empty($geocode_municipality_element))
									{
										$fields[$geocode_municipality_element] = $types['administrative_area_level_3']['long_name'];
									}

									if (!empty($geocode_state_element))
									{
										$fields[$geocode_state_element] = $types['administrative_area_level_1']['short_name'];
									}

									if (!empty($geocode_zip_element))
									{
										$fields[$geocode_zip_element] = $types['postal_code']['long_name'];
									}

									if (!empty($geocode_country_element))
									{
										$fields[$geocode_country_element] = $types['country']['short_name'];
									}

									switch ($geocode_from)
									{
										case '4':
											if (!empty($geocode_map_element))
											{
												$fields[$geocode_map_element] = "($lat,$long):$geocode_zoom_level";
											}
											break;
										case '5':
											if (!empty($geocode_lat_element))
											{
												$fields[$geocode_lat_element] = $lat;
											}

											if (!empty($geocode_lon_element))
											{
												$fields[$geocode_lon_element] = $long;
											}

											break;
									}

									$total_encoded++;
								}
								else
								{
									$logMsg = sprintf('Error (%s), id %s , no geocode result for: %s', $res['status'], $row->$primary_key_element_long, $lat.",".$long);
									FabrikWorker::log('plg.cron.geocode.information', $logMsg);
								}

								if ($geocode_delay > 0)
								{
									usleep($geocode_delay);
								}
							}
							else
							{
								FabrikWorker::log('plg.cron.geocode.information', 'empty lat/lng, id = ' . $row->$primary_key_element_long);
							}
						}
					}

					// if we've got fields to update, make it so ...
					if (!empty($fields))
					{
						$query->clear();
						$query->update($db->quoteName($table_name));

						foreach ($fields as $fieldName => $value)
						{
							$query->set($db->quoteName($fieldName) . ' = ' . $db->quote($value));
						}

						$query->where($primary_key_element . ' = ' . $db->quote($row->$primary_key_element_long));
						$db->setQuery($query);

						try
						{
							$db->execute();
						}
						catch (Exception $e)
						{
							FabrikWorker::log('plg.cron.geocode.error', 'update query error: ' . $e->getMessage());
						}
					}
				}
			}
		}

		FabrikWorker::log('plg.cron.geocode.information', 'Total encoded: '.$total_encoded);

		return $total_encoded;
	}
}
