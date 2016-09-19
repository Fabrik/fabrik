<?php
/**
 * YQL web service
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

/**
 * YQL web service
 *
 * @package  Fabrik
 * @since    3.0
 */

class FabrikWebServiceYql extends FabrikWebService
{
	/**
	 * Constructor
	 *
	 * @param   array  $options  ini state
	 */

	public function __construct($options)
	{
		$this->options = $options;
		ini_set("soap.wsdl_cache_enabled", 0);
	}

	/**
	 * get clients function
	 *
	 * @return  false
	 */

	public function getFunctions()
	{
		return false;
	}

	/**
	 * Query the web service to get the data
	 *
	 * @param   string  $method      to call at web service (soap only)
	 * @param   array   $options     key value filters to send to web service to filter the data
	 * @param   string  $startPoint  of actual data, if soap this is an xpath expression,
	 * otherwise its a key.key2.key3 string to traverse the returned data to arrive at the data to map to the fabrik list
	 * @param   string  $result      method name - soap only, if not set then "$method . 'Result' will be used
	 *
	 * @return  array	series of objects which can then be bound to the list using storeLocally()
	 */

	public function get($method, $options = array(), $startPoint = null, $result = null)
	{
		if (!strstr($method, 'where'))
		{
			$method .= ' where ';
		}

		foreach ($options as $k => $v)
		{
			$method .= $k . '=\'' . $v . '\'';
		}

		$url = $this->options['endpoint'] . '?q=' . urlencode($method) . '&format=json';

		// Make call with cURL
		$session = curl_init($url);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		$json = curl_exec($session);
		$phpObj = json_decode($json);

		if (!is_null($phpObj->query->results))
		{
			$res = $phpObj->query->results;
			$startPoints = explode('.', $startPoint);

			foreach ($startPoints as $p)
			{
				$res = &$res->$p;
			}

			return $res;
		}
		else
		{
			return array();
		}
	}
}
