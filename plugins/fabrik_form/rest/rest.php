<?php
/**
 * Submit or update data to a REST service
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.rest
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';
require_once JPATH_ROOT . '/plugins/fabrik_form/rest/fabrikOAuth.php';

/**
 * Submit or update data to a REST service
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.rest
 * @since       3.0
 */
class PlgFabrik_FormRest extends PlgFabrik_Form
{
	/**
	 * @var FabrikOauth
	 */
	private $client;

	/**
	 * Constructor
	 *
	 * @param   object &$subject The object to observe
	 * @param   array  $config   An array that holds the plugin configuration
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		$this->oAuth = new FabrikOauth();
	}

	/**
	 * Are we using POST to create new records
	 * or PUT to update existing records
	 *
	 * @return  string
	 */
	protected function requestMethod()
	{
		/** @var FabrikFEModelForm $formModel */
		$formModel = $this->getModel();
		$method    = $formModel->isNewRecord() ? 'POST' : 'PUT';
		$fkData    = $this->fkData();

		// If existing record but no Fk value stored presume its a POST
		if (empty($fkData))
		{
			$method = 'POST';
		}

		return $method;
	}

	/**
	 * Get the foreign keys value.
	 *
	 * @return  mixed string|int
	 */
	protected function fkData()
	{
		if (!isset($this->fkData))
		{
			/** @var FabrikFEModelForm $formModel */
			$formModel    = $this->getModel();
			$params       = $this->getParams();
			$this->fkData = array();

			// Get the foreign key element
			$fkElement = $this->fkElement();

			if ($fkElement)
			{
				$fkElementKey = $fkElement->getFullName();
				$this->fkData = json_decode(FArrayHelper::getValue($formModel->formData, $fkElementKey));

				if (is_object($this->fkData))
				{
					$this->fkData = ArrayHelper::fromObject($this->fkData);
				}

				$fkEval = $params->get('foreign_key_eval', '');

				if ($fkEval !== '')
				{
					$fkData = $this->fkData;
					$eval   = eval($fkEval);

					if ($eval !== false)
					{
						$this->fkData = $eval;
					}
				}
			}
		}

		return $this->fkData;
	}

	/**
	 * Get the foreign key element
	 *
	 * @return  object  Fabrik element
	 */
	protected function fkElement()
	{
		$params    = $this->getParams();
		$formModel = $this->getModel();

		return $formModel->getElement($params->get('foreign_key'), true);
	}

	/**
	 * Run right before the form is processed
	 * form needs to be set to record in database for this to hook to be called
	 * If we need to update the records fk then we should run process(). However means we don't have access to the
	 * row's id.
	 *
	 * @return    bool
	 */
	public function onBeforeStore()
	{
		if ($this->shouldUpdateFk())
		{
			$this->process();
		}
	}

	/**
	 * Run after the form is processed
	 * form needs to be set to record in database for this to hook to be called
	 * If we don't need to update the records fk then we should run process() as we now have access to the row's id.
	 *
	 * @return    bool
	 */
	public function onAfterProcess()
	{
		if (!$this->shouldUpdateFk())
		{
			$this->process();
		}
	}

	/**
	 * Process rest call
	 *
	 * @return    bool
	 */
	protected function process()
	{
		$formModel = $this->getModel();
		$params    = $this->getParams();
		$fkElement = $this->fkElement();
		$w         = new FabrikWorker;

		// POST new records, PUT existing records
		$method = $this->requestMethod();
		$fkData = $this->fkData();
		// Set where we should post the REST request to
		$endpoint = $method === 'PUT' ? $params->get('put') : $params->get('endpoint');
		$endpoint = $w->parseMessageForPlaceholder($endpoint);
		$endpoint = str_replace('{fk}', $fkData, $endpoint);
		$endpoint = $w->parseMessageForPlaceHolder($endpoint, $fkData);

		// What is the root node for the xml data we are sending
		$xmlParent = $params->get('xml_parent', '');
		$xmlParent = $w->parseMessageForPlaceholder($xmlParent);
		$dataMap   = $params->get('put_include_list', '');
		$include  = $w->parseMessageForPlaceholder($dataMap, $formModel->formData, true);
		$data   = $this->buildOutput($include, $xmlParent, $headers);

		if ($params->get('oauth_consumer_key', '') === '')
		{
			$output = $this->processCurl($endpoint, $data);
		}
		else
		{
			$output = $this->processOAuth($endpoint, $data);
		}

		$jsonOutPut = FabrikWorker::isJSON($output) ? true : false;

		// Set FK value in Fabrik form data
		if ($this->shouldUpdateFk())
		{
			if ($jsonOutPut)
			{
				$fkVal = json_encode($output);
			}
			else
			{
				$fkVal = $output;
			}

			$fkElementKey = $fkElement->getFullName();
			$formModel->updateFormData($fkElementKey, $fkVal, true, true);
		}

		$url = $this->app->input->get('fabrik_referrer', '', 'string');
		$context = $formModel->getRedirectContext();
		$url = $this->session->get($context . 'url', array($url));
		$url = array_shift($url);
		$this->app->redirect($url);
	}

	/**
	 * Process OAuth request
	 * @param   string  $endpoint  URL end point
	 * @param   string  $data      Querystring variables
	 *
	 * @return  JHttpResponse
	 */
	private function processOAuth($endpoint, $data)
	{
		parse_str($data, $d);
		$this->getOAuthStore();
		//$this->oAuth->setOption('sendheaders', false);
		$method = $this->requestMethod();

		//$key  = JFactory::getSession()->get('key', null, 'oauth_token');
		$token = $this->oAuth->authenticate();

		$parameters = array(
			'oauth_token' => $token['key']
		);

		return $this->oAuth->oauthRequest($endpoint, $method, $parameters, $d);
	}

	/**
	 * Process rest call via CURL
	 * @param $endpoint
	 *
	 * @return bool|void
	 */
	private function processCurl($endpoint, $data)
	{
		if (!function_exists('curl_init'))
		{
			throw new RuntimeException('CURL NOT INSTALLED', 500);

			return;
		}

		// Set up CURL object
		$chandle   = curl_init();

		// Request headers
		$headers = array();
		$method    = $this->requestMethod();

		$curlOpts = $this->buildCurlOpts($method, $headers, $endpoint, $data);

		foreach ($curlOpts as $key => $value)
		{
			curl_setopt($chandle, $key, $value);
		}

		$output = curl_exec($chandle);

		if (!$this->handleError($output, $chandle))
		{
			curl_close($chandle);

			// Return true otherwise form processing interrupted
			return false;
		}
		curl_close($chandle);
	}

	/**
	 * Should the REST call update the fabrik row's fk value after it has posted to the service.
	 *
	 * @return boolean
	 */
	protected function shouldUpdateFk()
	{
		$method    = $this->requestMethod();
		$fkElement = $this->fkElement();

		return $method === 'POST' && $fkElement;
	}

	/**
	 * Create the data structure containing the data to send
	 *
	 * @param   string $include   list of fields to include
	 * @param   xml    $xmlParent Parent node if rendering as xml (ignored if include is json and prob something i want
	 *                            to deprecate)
	 * @param   array  &$headers  Headers
	 *
	 * @return mixed
	 */
	private function buildOutput($include, $xmlParent, &$headers)
	{
		$postData = array();

		/** @var FabrikFEModelForm $formModel */
		$formModel = $this->getModel();
		$w         = new FabrikWorker;
		$fkElement = $this->fkElement();
		$fkData    = $this->fkData();

		if (FabrikWorker::isJSON($include))
		{
			$include = json_decode($include);
			$keys    = $include->put_key;
			$values  = $include->put_value;
			$format  = $include->put_type;
			$i       = 0;
			$fkName  = $fkElement ? $fkElement->getFullName(true, false, true) : '';

			foreach ($values as &$v)
			{
				if ($v === $fkName)
				{
					$v = $fkData;
				}
				else
				{
					$v = FabrikString::safeColNameToArrayKey($v);
					$v = $w->parseMessageForPlaceHolder('{' . $v . '}', $formModel->formData, true);
				}

				if ($format[$i] == 'number')
				{
					$regex = '#^([-]*[0-9\.,\' ]+?)((\.|,){1}([0-9-]{1,2}))*$#e';
					$v     = floatval(preg_replace($regex, "str_replace(array('.', ',', \"'\", ' '), '', '\\1') . '.\\4'", $v));
				}

				$i++;
			}

			$i = 0;

			foreach ($keys as $key)
			{
				// Can be in format "foo[bar]" in which case we want to map into nested array
				preg_match('/\[(.*)\]/', $key, $matches);

				if (count($matches) >= 2)
				{
					$bits = explode('[', $key);

					if (!array_key_exists($bits[0], $postData))
					{
						$postData[$bits[0]] = array();
					}

					$postData[$bits[0]][$matches[1]] = $values[$i];
				}
				else
				{
					// specific request from a user to concatenate values if key specified more than once
					if (array_key_exists($key, $postData))
					{
						$postData[$key] .= $values[$i];
					}
					else
					{
						$postData[$key] = $values[$i];
					}
				}

				$i++;
			}
		}
		else
		{
			$include = explode(',', $include);

			foreach ($include as $i)
			{
				if (array_key_exists($i, $formModel->formData))
				{
					$postData[$i] = $formModel->formData[$i];
				}
				elseif (array_key_exists($i, $formModel->fullFormData, $i))
				{
					$postData[$i] = $formModel->fullFormData[$i];
				}
			}
		}

		$postAsXML = false;

		if ($postAsXML)
		{
			$xml     = new SimpleXMLElement('<' . $xmlParent . '></' . $xmlParent . '>');
			$headers = array('Content-Type: application/xml', 'Accept: application/xml');

			foreach ($postData as $k => $v)
			{
				$xml->addChild($k, $v);
			}

			$output = $xml->asXML();
		}
		else
		{
			$output = http_build_query($postData);
		}

		return $output;
	}

	/**
	 * Create the CURL options when sending
	 *
	 * @param   string $method   POST/PUT
	 * @param   array  &$headers Headers
	 * @param   string $endpoint URL to post/put to
	 * @param   string $output   URL Encoded querystring
	 *
	 * @return  array
	 */
	private function buildCurlOpts($method, &$headers, $endpoint, $output)
	{
		$params = $this->getParams();

		// The username/password
		if (!($params->get('username', '') === '' && $params->get('password') === ''))
		{
			$curlOpts[CURLOPT_USERPWD] = $params->get('username') . ':' . $params->get('password');
		}

		$curlOpts = array();

		if ($method === 'POST')
		{
			$curlOpts[CURLOPT_POST] = 1;
		}
		else
		{
			$curlOpts[CURLOPT_CUSTOMREQUEST] = "PUT";
		}

		$curlOpts[CURLOPT_URL]            = $endpoint;
		$curlOpts[CURLOPT_SSL_VERIFYPEER] = 0;
		$curlOpts[CURLOPT_POSTFIELDS]     = $output;
		$curlOpts[CURLOPT_HTTPHEADER]     = $headers;
		$curlOpts[CURLOPT_RETURNTRANSFER] = 1;
		$curlOpts[CURLOPT_HTTPAUTH]       = CURLAUTH_ANY;
		$curlOpts[CURLOPT_VERBOSE]        = true;

		return $curlOpts;
	}

	/**
	 * Handle any error generated
	 *
	 * @param   mixed  &$output CURL request result - may be a json string
	 * @param   object $chandle CURL object
	 *
	 * @return boolean
	 */
	private function handleError(&$output, $chandle)
	{
		$formModel = $this->getModel();

		if (FabrikWorker::isJSON($output))
		{
			$output = json_decode($output);

			// @TODO make this more generic - currently only for apparty
			if (isset($output->errors))
			{
				// Have to set something in the errors array otherwise form validates
				$formModel->_arErrors['dummy___elementname'][] = 'woops!';
				$formModel->getForm()->error                   = implode(', ', $output->errors);

				return false;
			}
		}

		$httpCode = curl_getinfo($chandle, CURLINFO_HTTP_CODE);

		switch ($httpCode)
		{
			case '400':
				echo "Bad Request";
				break;
			case '401':
				echo "Unauthorized";
				break;
			case '404':
				echo "Not found";
				break;
			case '405':
				echo "Method Not Allowed";
				break;
			case '406':
				echo "Not Acceptable";
				break;
			case '415':
				echo "Unsupported Media Type";
				break;
			case '500':
				echo "Internal Server Error";
				break;
		}

		if (curl_errno($chandle))
		{
			$this->app->enqueueMessage('Fabrik Rest form plugin: ' . curl_error($chandle), 'error');

			return false;
		}

		return true;
	}

	/**
	 * Get the OAuth store
	 *
	 * @return  void
	 */
	protected function getOAuthStore()
	{
		$params = $this->getParams();

		$this->oAuth->setOption('accessTokenURL', $params->get('access_token_uri'));
		$this->oAuth->setOption('authoriseURL', $params->get('authorize_uri'));
		$this->oAuth->setOption('requestTokenURL', $params->get('request_token_uri'));
		//$this->oAuth->setOption('callback', true);
		$this->oAuth->setOption('callback', (string) JUri::getInstance());
		$this->oAuth->setOption('authenticateURL', 'https://www.linkedin.com/uas/oauth/authenticate');
		$this->oAuth->setOption('consumer_key', $params->get('oauth_consumer_key'));
		$this->oAuth->setOption('consumer_secret', $params->get('oauth_consumer_secret'));
		$this->oAuth->setOption('sendheaders', true);

		//  Init the OAuthStore
		/*	$options = array(
					'server_uri' => $params->get('server_uri'),
			);*/
	}

	/**
	 * Run once the form's data has been loaded
	 *
	 * @return    bool
	 */
	public function onLoad()
	{
		$params = $this->getParams();
		$url   = $params->get('get');

		if ($params->get('oauth_consumer_key', '') === '' || is_null($url))
		{
			return;
		}

		$this->getOAuthStore();
		$this->oAuth->authenticate();
		$token = $this->oAuth->getToken();

		$parameters = array(
			'oauth_token' => $token['key']
		);

		$response = $this->oAuth->oauthRequest($url, 'GET', $parameters);

		if (in_array((int) $response->code, array(200, 201)))
		{
			$data = json_decode($response->body);
			$this->updateFormModelData($params, $response->body, $data);
		}
	}

	/**
	 * Update the form models data with data from CURL request
	 *
	 * @param   Joomla\Registry\Registry $params       Parameters
	 * @param   array                    $responseBody Response body
	 * @param   array                    $data         Data returned from CURL request
	 *
	 * @return  void
	 */
	protected function updateFormModelData($params, $responseBody, $data)
	{
		$w         = new FabrikWorker;
		$dataMap   = $params->get('put_include_list', '');
		$include   = $w->parseMessageForPlaceholder($dataMap, $responseBody, true);
		$formModel = $this->getModel();

		if (FabrikWorker::isJSON($include))
		{
			$include = json_decode($include);

			$keys     = $include->put_key;
			$values   = $include->put_value;
			$defaults = $include->put_value;

			for ($i = 0; $i < count($keys); $i++)
			{
				$key        = $keys[$i];
				$default    = $defaults[$i];
				$localKey   = FabrikString::safeColNameToArrayKey($values[$i]);
				$remoteData = FArrayHelper::getNestedValue($data, $key, $default, true);

				if (!is_null($remoteData))
				{
					$formModel->_data[$localKey] = $remoteData;
				}
			}
		}
	}
}
