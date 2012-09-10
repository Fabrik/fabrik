<?php

/**
* A cron task to email records to a give set of users
* @package Joomla
* @subpackage Fabrik
* @author Hugh Messenger
* @copyright (C) Hugh Messenger
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once(COM_FABRIK_FRONTEND . '/models/plugin-cron.php');


require_once(JPATH_SITE . '/plugins/fabrik_cron/geocode/libs/gmaps2.php');

class plgFabrik_CronGeocode extends plgFabrik_Cron {

	public function canUse(&$model = null, $location = null, $event = null)
	{
		return true;
	}

	/**
	 * do the plugin action
	 *
	 */
	function process(&$data, &$listModel)
	{
		$params = $this->getParams();

		// grab the table model and find table name and PK
		$table = $listModel->getTable();
		$table_name 		= $table->db_table_name;
		$primary_key = $table->db_primary_key;
		$primary_key_element = FabrikString::shortColName($table->db_primary_key);

		// for now, we have to read the table ourselves.  We can't rely on the $data passed to us
		// because it can be arbitrarily filtered according to who happened to hit the page when cron
		// needed to run.

		$mydata = array();
		$db = FabrikWorker::getDbo();
		$db->setQuery("SELECT * FROM $table_name");
		$mydata[0] = $db->loadObjectList();

		// grab all the params, like GMaps key, field names to use, etc
		// $geocode_gmap_key = $params->get('geocode_gmap_key');
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
		$geocode_when = $params->get('geocode_zip_element', '1');

		// sanity check, make sure required elements have been specified
		/*
		if (empty($geocode_gmap_key)) {
			JError::raiseNotice(500, 'No google maps key specified');
			return;
		}
		$gmap = new GMaps($geocode_gmap_key);
		*/
		$gmap = new GeoCode();
		// run through our table data
		$total_encoded = 0;
		$total_attempts = 0;
		foreach ($mydata as $gkey => $group) {
			if (is_array($group)) {
				foreach ($group as $rkey => $row) {
					if ($geocode_batch_limit > 0 && $total_attempts >= $geocode_batch_limit) {
						FabrikWorker::log('plg.cron.geocode.information', 'reached batch limit');
						break 2;
					}
					// See if the map element is considered empty
					// Values of $geocode_when are:
					// 1: default or empty
					// 2: empty
					// 3: always
					$do_geocode = true;
					if ($geocode_when == '1') {
						$do_geocode = empty($row->$geocode_map_element) || $row->$geocode_map_element == $geocode_is_empty;
					}
					elseif ($geocode_when == '2') {
						$do_geocode = empty($row->$geocode_map_element);
					}
					if ($do_geocode) {
						// it's empty, so lets try and geocode.
						// first, construct the address
						// we'll build an array of address components, which we'll explode into a string later
						$a_full_addr = array();
						// for each address component element, see if one is specific in the params,
						// if so, see if it has a value in this row
						// if so, add it to the address array.
						if ($geocode_addr1_element) {
							if ($row->$geocode_addr1_element) {
								$a_full_addr[] = $row->$geocode_addr1_element;
							}
						}
						if ($geocode_addr2_element) {
							if ($row->$geocode_addr2_element) {
								$a_full_addr[] = $row->$geocode_addr2_element;
							}
						}
						if ($geocode_city_element) {
							if ($row->$geocode_city_element) {
								$a_full_addr[] = $row->$geocode_city_element;
							}
						}
						if ($geocode_state_element) {
							if ($row->$geocode_state_element) {
								$a_full_addr[] = $row->$geocode_state_element;
							}
						}
						if ($geocode_zip_element) {
							if ($row->$geocode_zip_element) {
								$a_full_addr[] = $row->$geocode_zip_element;
							}
						}
						if ($geocode_country_element) {
							if ($row->$geocode_country_element) {
								$a_full_addr[] = $row->$geocode_country_element;
							}
						}
						// now explode the address into a string
						$full_addr = implode(',', $a_full_addr);

						// Did we actually get an address?
						if (!empty($full_addr)) {
							// OK!  Lets try and geocode it ...
							$total_attempts++;
							$res = $gmap->getLatLng($full_addr);
							if ($res['status'] == 'OK') {
								//echo 'found ';
								$lat = $res['lat'];
								$long = $res['lng'];
								if (!empty($lat) && !empty($long)) {
									$map_value = "($lat,$long):$geocode_zoom_level";
									$db->setQuery("
										UPDATE $table_name
										SET $geocode_map_element = '$map_value'
										WHERE $primary_key = '{$row->$primary_key_element}'
									");
									$db->query();
									$total_encoded++;
								}
							}
							else {
								FabrikWorker::log('plg.cron.geocode.information', sprintf('Error (%s), no geocode result for: %s', $res['status'], $full_addr));
							}
							if ($geocode_delay > 0) {
								usleep($geocode_delay);
							}
						}
						else {
							FabrikWorker::log('plg.cron.geocode.information', 'empty address');
						}
					}
				}
			}
		}
		return $total_encoded;
	}

	/**
	 * show a new for entering the form actions options
	 */
/*
	function renderAdminSettings()
	{
		//JHTML::stylesheet('fabrikadmin.css', 'administrator/components/com_fabrik/views/');
		$this->getRow();
		$pluginParams = $this->getParams();

		$document = JFactory::getDocument();
		?>
		<div id="page-<?php echo $this->_name;?>" class="pluginSettings" style="display:none">
		<b>NOTES</b>
		<ul>
			<li>
				You can either run this as a scheduled task, or use it as a one-off import script for new data (by simply not selecting a scheduled run time, and using the Run button by hand)
			</li>
			<li>
				You don't need to specify all the geocoding elements (addr1, addr2, city, etc), but whatever you do select should build a valid address, when concatenated (in order) into one comma separated string.  The simplest case would be a single element which has the entire address already comma separated.  Or you can match some or all of the address components to you form elements.
			</li>
			<li>
				The 'Empty Value' can be used where (for example) you have Fabrik map elements which have been submitted without the marker being placed, so they will have the default lat/long and zoom level, like "53.2224,-4.2007:4".  Setting the "Empty Value" to this will cause this script to treat both empty map elements AND ones which have that default string as being in need of encoding.
			</li>
		</ul>
		<?php
			// @TODO - work out why the language diddly doesn't work here, so we can make the above notes translateable?
			//echo JText::_('GCALNOTES');
			echo $pluginParams->render('params');
			echo $pluginParams->render('params', 'fields');
			?>
		</div>
		<?php
		return ;
	}*/

}
?>