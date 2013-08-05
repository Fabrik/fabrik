<?php

// No direct access
defined('_JEXEC') or die('Restricted access');

class bitly {

	/**
	 * A PHP class to utilize the bitly API
	 * through the Bitly API. Handles:
	 * - Shorten
	 *
	 * Requires PHP5, CURL, JSON and a Bitly account http://bit.ly/
	 *
	 * Bitly API documentation: http://code.google.com/p/bitly-api/wiki/ApiDocumentation
	 *
	 * History:
	 * 10/20/2010: Updated to API v3 by <hugh.messenger@gmail.com>
	 *
	 */

	var $status_code = '200';
	var $status_txt = 'OK';

	protected $appkey;
	protected $login;
	protected $version;

	/**
	* Contruct the Bitly Class
	*
* @param string, your Bitly account key
* @param string, your Bitly account login
	*/
	public function __construct($login, $appkey, $version='v3') {
		$this->login = $login;
		$this->appkey = $appkey;
		$this->version = $version;
	}

	/**
	 * Was there an error on the last shorten attempt, return code or 0 for no error
	 *
	 * @return integer, status code
	 */
	public function getError() {
		if ($this->status_code == '200') {
			return 0;
		}
		else {
			return (int) $this->status_code;
		}
	}

	/**
	 * Return error status msg from last shorten attempt
	 *
	 * @return string, status code + txt
	 */
	public function getErrorMsg() {
		return $this->status_code . ': ' . $this->status_txt;
	}

	/**
	* Retrieve a shortened URL
	*
* @param string, url to shorten
	*/
	public function shorten($url) {
		//create the URL
		$api_url = 'http://api.bit.ly/'.$this->version.'/shorten?longUrl='.urlencode($url).'&format=xml&login='.$this->login.'&apiKey='.$this->appkey;
		//call the API
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $api_url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($curl);
		curl_close($curl);

		//parse the XML response and return the url
		$xml_object = new SimpleXMLElement($response);
		if ($xml_object->status_code == '200') {
			// success, set OK status and return the shortened URL
			$this->status_code = '200';
			$this->status_txt = 'OK';
			return $xml_object->data->url;
		}
		else {
			// ooops, set status to returned values from bit.ly, and return the original URL
			$this->status_code = $xml_object->status_code;
			$this->status_txt = $xml_object->status_txt;
			return $url;
		}
	}

}
?>