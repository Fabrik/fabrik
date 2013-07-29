<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.gcalsync
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-cron.php';

/**
 * Fabrik Cron Job:
 * Syncs events from a Google Calendar into a Fabrik List
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.gcalsync
 * @since       3.0
 */

class PlgFabrik_CronGcalsync extends PlgFabrik_Cron
{

	/**
	 * Check if the user can use the plugin
	 *
	 * @param   object  &$model    calling the plugin list/form
	 * @param   string  $location  to trigger plugin on
	 * @param   string  $event     to trigger plugin on
	 *
	 * @return  bool can use or not
	 */

	public function canUse(&$model = null, $location = null, $event = null)
	{
		return true;
	}

	/**
	 * Get a GCal short id from a long id
	 *
	 * @param   string  $long_id  long id
	 *
	 * @return  string
	 */

	protected function _getGcalShortId($long_id)
	{
		$matches = array();
		if (preg_match('#/(\w+)$#', $long_id, $matches))
		{
			return $matches[1];
		}
		else
		{
			return $long_id;
		}
	}

	/**
	 * Do the plugin action
	 *
	 * @param   array   &$data       selected data
	 * @param   object  &$listModel  list model
	 *
	 * @return number of records updated
	 */

	public function process(&$data, &$listModel)
	{
		$params = $this->getParams();
		$gcal_url = $params->get('gcal_sync_gcal_url');
		$matches = array();

		/* This matches a standard GCal URL, found under the Google "Calender Details" tab, using the XML button.
		 * It should match any form, for public or private ...
		 * http://www.google.com/calendar/feeds/hugh.messenger%40gmail.com/public/basic
		 * http://www.google.com/calendar/feeds/hugh.messenger%40gmail.com/private-3081eca2b0asdfasdf8f106ea6f63343056/basic
		 */
		if (preg_match('#feeds/(.*?)/(\w+-\w+|\w+)/(\w+)#', $gcal_url, $matches))
		{
			// Grab the bits of the URL we need for the Zend framework call
			$gcal_user = $matches[1];
			$gcal_visibility = $matches[2];
			$gcal_projection = $matches[3];
			$gcal_email = urldecode($gcal_user);

			// Grab the table model and find table name and PK
			$table = $listModel->getTable();
			$table_name = $table->db_table_name;

			/* For now, we have to read the table ourselves.  We can't rely on the $data passed to us
			 * because it can be filtered, and we need to see all records to know if the GCal events
			 * already exist in the table
			 */

			$mydata = array();
			$db = FabrikWorker::getDbo();
			$query = $db->getQuery(true);
			$query->select('*')->from($table_name);
			$db->setQuery($query);
			$mydata[0] = $db->loadObjectList();

			// Grab all the field names to use
			$gcal_label_element_long = $params->get('gcal_sync_label_element');
			$gcal_label_element = FabrikString::shortColName($gcal_label_element_long);
			$gcal_desc_element_long = $params->get('gcal_sync_desc_element');
			$gcal_desc_element = FabrikString::shortColName($gcal_desc_element_long);
			$gcal_start_date_element_long = $params->get('gcal_sync_startdate_element');
			$gcal_start_date_element = FabrikString::shortColName($gcal_start_date_element_long);
			$gcal_end_date_element_long = $params->get('gcal_sync_enddate_element');
			$gcal_end_date_element = FabrikString::shortColName($gcal_end_date_element_long);
			$gcal_id_element_long = $params->get('gcal_sync_id_element');
			$gcal_id_element = FabrikString::shortColName($gcal_id_element_long);
			$gcal_userid_element_long = $params->get('gcal_sync_userid_element');
			$gcal_userid_element = FabrikString::shortColName($gcal_userid_element_long);

			// Sanity check, make sure required elements have been specified
			if (empty($gcal_label_element_long) || empty($gcal_start_date_element_long) || empty($gcal_end_date_element_long)
				|| empty($gcal_id_element_long))
			{
				return;
			}

			// If they selected a User ID element to use, see if we can find a J! user with matching email to this feed's owner
			$our_userid = 0;
			if ($gcal_userid_element_long)
			{
				$query = $db->getQuery(true);
				$query->select('id')->from('#__users')->whre('email = ' . $db->quote('$gcal_email'));
				$db->setQuery($query);
				$our_userid = $db->loadResult();

				// Better make sure it's not NULL, in case underlying column is NOT NULL
				if (empty($our_userid))
				{
					$our_userid = 0;
				}
			}

			// Include the Zend stuff
			$path = JPATH_SITE . '/libraries';
			set_include_path(get_include_path() . PATH_SEPARATOR . $path);
			$path = get_include_path();
			require_once 'Zend/Loader.php';
			Zend_Loader::loadClass('Zend_Gdata');

			/* Won't need these loaded until we add sync'ing events back to Google
			 * Zend_Loader::loadClass('Zend_Gdata_AuthSub');
			 * Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
			 */
			Zend_Loader::loadClass('Zend_Gdata_Calendar');

			// See if they want to sync to gcal, and provided a login
			$gcal_sync_upload = $params->get('gcal_sync_upload_events', 'from');
			if ($gcal_sync_upload == 'both' || $gcal_sync_upload == 'to')
			{
				Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
				$email = $params->get('gcal_sync_login', '');
				$passwd = $params->get('gcal_sync_passwd', '');
				try
				{
					$client = Zend_Gdata_ClientLogin::getHttpClient($email, $passwd, 'cl');
				}
				catch (Zend_Gdata_App_CaptchaRequiredException $cre)
				{
					echo 'URL of CAPTCHA image: ' . $cre->getCaptchaUrl() . "\n";
					echo 'Token ID: ' . $cre->getCaptchaToken() . "\n";
					return;
				}
				catch (Zend_Gdata_App_AuthException $ae)
				{
					echo 'Problem authenticating: ' . $ae->exception() . "\n";
					return;
				}
				$gdataCal = new Zend_Gdata_Calendar($client);
			}
			else
			{
				$gdataCal = new Zend_Gdata_Calendar;
			}

			// Set up and execute the call to grab the feed from google
			$query = $gdataCal->newEventQuery();
			$query->setUser($gcal_user);
			$query->setVisibility($gcal_visibility);
			$query->setProjection($gcal_projection);
			$eventFeed = $gdataCal->getCalendarEventFeed($query);

			// Build an array of the events from the feed, indexed by the Google ID
			$event_ids = array();
			foreach ($eventFeed as $key => $event)
			{
				$short_id = $this->_getGcalShortId($event->id->text);
				$gcal_event_ids[$short_id] = $eventFeed[$key];
			}

			/* Run through our table data, and build an array of our events indexed by the Google ID
			 * (of course not all events may have a Google ID)
			 */
			$our_event_ids = array();
			$our_upload_ids = array();
			foreach ($mydata as $gkey => $group)
			{
				if (is_array($group))
				{
					foreach ($group as $rkey => $row)
					{
						if ($row->$gcal_id_element)
						{
							$our_event_ids[$row->$gcal_id_element] = $mydata[$gkey][$rkey];
						}
						else
						{
							$our_upload_ids[] = $mydata[$gkey][$rkey];
						}
					}
				}
			}

			// Now go through the google events id's, and process the ones which aren't in our table.
			$our_event_adds = array();
			foreach ($gcal_event_ids as $id => $event)
			{
				if (!array_key_exists($id, $our_event_ids))
				{
					// We don't have the ID, so add the event to our table
					$row = array();
					$row[$gcal_start_date_element] = strftime('%Y-%m-%d %H:%M:%S', strtotime($event->when[0]->startTime));
					$row[$gcal_end_date_element] = strftime('%Y-%m-%d %H:%M:%S', strtotime($event->when[0]->endTime));
					$row[$gcal_label_element] = $event->title->text;
					$row[$gcal_desc_element] = $event->content->text;
					$row[$gcal_id_element] = $id;
					if ($gcal_userid_element_long)
					{
						$row[$gcal_userid_element] = $our_userid;
					}
					$listModel->storeRow($row, 0);
				}
			}

			// If upload syncing (from us to gcal) is enabled ...
			if ($gcal_sync_upload == 'both' || $gcal_sync_upload == 'to')
			{
				// Grab the tzOffset.  Note that gcal want +/-XX (like -06)

				// But J! gives us +/-X (like -6) so we sprintf it to the right format
				$config = JFactory::getConfig();
				$tzOffset = (int) $config->get('offset');
				$tzOffset = sprintf('%+03d', $tzOffset);

				// Loop thru the array we built earlier of events we have that aren't in gcal
				foreach ($our_upload_ids as $id => $event)
				{
					// Skip if a userid element is specified, and doesn't match the owner of this gcal
					if ($gcal_userid_element_long)
					{
						if ($event->$gcal_userid_element != $our_userid)
						{
							continue;
						}
					}
					// Now start building the gcal event structure
					$newEvent = $gdataCal->newEventEntry();
					$newEvent->title = $gdataCal->newTitle($event->$gcal_label_element);
					if ($gcal_desc_element_long)
					{
						$newEvent->content = $gdataCal->newContent($event->$gcal_desc_element);
					}
					else
					{
						$newEvent->content = $gdataCal->newContent($event->$gcal_label_element);
					}
					$when = $gdataCal->newWhen();

					// Grab the start date, apply the tx offset, and format it for gcal
					$start_date = JFactory::getDate($event->$gcal_start_date_element);
					$start_date->setOffset($tzOffset);
					$start_fdate = $start_date->toSql();
					$date_array = explode(' ', $start_fdate);
					$when->startTime = "{$date_array[0]}T{$date_array[1]}.000{$tzOffset}:00";

					/* We have to provide an end date for gcal, so if we don't have one,
					 * default it to start date + 1 hour
					 */
					if ($event->$gcal_end_date_element == '0000-00-00 00:00:00')
					{
						$startstamp = strtotime($event->$gcal_start_date_element);
						$endstamp = $startstamp + (60 * 60);
						$event->$gcal_end_date_element = strftime('%Y-%m-%d %H:%M:%S', $endstamp);
					}

					// Grab the end date, apply the tx offset, and format it for gcal
					$end_date = JFactory::getDate($event->$gcal_end_date_element);
					$end_date->setOffset($tzOffset);
					$end_fdate = $end_date->toSql();
					$date_array = explode(' ', $end_fdate);
					$when->endTime = "{$date_array[0]}T{$date_array[1]}.000{$tzOffset}:00";
					$newEvent->when = array($when);

					// Fire off the insertEvent to gcal, catch any errors
					try
					{
						$retEvent = $gdataCal->insertEvent($newEvent);
					}
					catch (Zend_Gdata_App_HttpException $he)
					{
						$errStr = 'Problem adding event: ' . $he->getRawResponseBody() . "\n";
						continue;
					}

					/* So, insertEvent worked, grab the gcal ID from the returned event data,
					 * and update our event record with the short version of the ID
					 */
					$gcal_id = $this->_getGcalShortId($retEvent->id->text);
					$our_id = $event->id;
					$query = $db->getQuery(true);
					$query->update($table_name)->set($gcal_id_element . ' = ' . $db->quote($gcal_id))->where('id = ' . $db->quote($our_id));
					$db->setQuery($query);
					$db->execute();
				}
			}
		}
	}

}
