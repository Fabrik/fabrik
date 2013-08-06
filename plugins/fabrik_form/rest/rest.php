<?php
/**
 * Submit or update data to a REST service
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.rest
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

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
	 * Are we using POST to create new records
	 * or PUT to update existing records
	 *
	 * @return  string
	 */
	protected function requestMethod()
	{
		$method = $this->formModel->_formData['rowid'] == 0 ? 'POST' : 'PUT';
		$fkData = $this->fkData();

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
			$this->fkData = array();

			// Get the foreign key element
			$fkElement = $this->fkElement();
			if ($fkElement)
			{
				$fkElementKey = $fkElement->getFullName();
				$this->fkData = json_decode(JArrayHelper::getValue($this->formModel->_formData, $fkElementKey));
				$this->fkData = JArrayHelper::fromObject($this->fkData);

				$fkEval = $this->params->get('foreign_key_eval', '');
				if ($fkEval !== '')
				{
					$fkData = $this->fkData;
					$eval = eval($fkEval);
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
		return $this->formModel->getElement($this->params->get('foreign_key'), true);
	}

	/**
	 * Run right before the form is processed
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @param   object  $params      plugin params
	 * @param   object  &$formModel  form model
	 *
	 * @return	bool
	 */

	public function onBeforeStore($params, &$formModel)
	{
		$this->formModel = $formModel;
		$this->params = $params;

		$w = new FabrikWorker;
		if (!function_exists('curl_init'))
		{
			throw new RuntimeException('CURL NOT INSTALLED', 500);
			return;
		}
		// The username/password
		$config_userpass = $params->get('username') . ':' . $params->get('password');

		// POST new records, PUT exisiting records
		$method = $this->requestMethod();

		$fkData = $this->fkData();
		$fkElement = $this->fkElement();

		// Set where we should post the REST request to
		$endpoint = $method === 'PUT' ? $params->get('put') : $params->get('endpoint');
		$endpoint = $w->parseMessageForPlaceholder($endpoint);
		$endpoint = str_replace('{fk}', $fkData, $endpoint);

		// What is the root node for the xml data we are sending
		$xmlParent = $params->get('xml_parent', '');
		$xmlParent = $w->parseMessageForPlaceholder($xmlParent);

		// Request headers
		$headers = array();

		// Set up CURL object
		$chandle = curl_init();

		$dataMap = $params->get('put_include_list', '');

		$include = $w->parseMessageForPlaceholder($dataMap, $formModel->_formData, true);
		$endpoint = $w->parseMessageForPlaceHolder($endpoint, $fkData);

		$output = $this->buildOutput($formModel, $include, $xmlParent, $headers);

		$curlOpts = $this->buildCurlOpts($method, $headers, $endpoint, $params, $output);

		foreach ($curlOpts as $key => $value)
		{
			curl_setopt($chandle, $key, $value);
		}
		$output = curl_exec($chandle);
		$jsonOutPut = FabrikWorker::isJSON($output) ? true : false;

		if (!$this->handleError($output, $formModel, $chandle))
		{
			curl_close($chandle);
			return false;
		}

		curl_close($chandle);

		// Set FK value in Fabrik form data
		if ($method === 'POST' && $fkElement)
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

	}

	/**
	 * Create the data structure containing the data to send
	 *
	 * @param   object  $formModel  Form Model
	 * @param   string  $include    list of fields to include
	 * @param   xml     $xmlParent  Parent node if rendering as xml (ignored if include is json and prob something i want to deprecate)
	 * @param   array   &$headers   Headeres
	 *
	 * @return mixed
	 */

	private function buildOutput($formModel, $include, $xmlParent, &$headers)
	{
		$postData = array();
		$w = new FabrikWorker;
		$fkElement = $this->fkElement();
		$fkData = $this->fkData();

		if (FabrikWorker::isJSON($include))
		{
			$include = json_decode($include);

			$keys = $include->put_key;
			$values = $include->put_value;
			$format = $include->put_type;
			$i = 0;
			$fkName = $fkElement ? $fkElement->getFullName(false, true) : '';
			foreach ($values as &$v)
			{
				if ($v === $fkName)
				{
					$v = $fkData;
				}
				else
				{
					$v = FabrikString::safeColNameToArrayKey($v);

					$v = $w->parseMessageForPlaceHolder('{' . $v . '}', $formModel->_formData, true);
				}
				if ($format[$i] == 'number')

				{
					$v = floatval(preg_replace('#^([-]*[0-9\.,\' ]+?)((\.|,){1}([0-9-]{1,2}))*$#e', "str_replace(array('.', ',', \"'\", ' '), '', '\\1') . '.\\4'", $v));
				}
				$i ++;
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
					$postData[$key] = $values[$i];
				}
				$i ++;
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
			$xml = new SimpleXMLElement('<' . $xmlParent . '></' . $xmlParent . '>');
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
	 * @param   string  $method    POST/PUT
	 * @param   array   &$headers  Headers
	 * @param   string  $endpoint  URL to post/put to
	 * @param   object  $params    Plugin params
	 * @param   string  $output    URL Encoded querystring
	 *
	 * @return  array
	 */

	private function buildCurlOpts($method, &$headers, $endpoint, $params, $output)
	{
		// The username/password
		$config_userpass = $params->get('username') . ':' . $params->get('password');
		$curlOpts = array();
		if ($method === 'POST')
		{
			$curlOpts[CURLOPT_POST] = 1;
		}
		else
		{
			$curlOpts[CURLOPT_CUSTOMREQUEST] = "PUT";
		}

		$curlOpts[CURLOPT_URL] = $endpoint;
		$curlOpts[CURLOPT_SSL_VERIFYPEER] = 0;
		$curlOpts[CURLOPT_POSTFIELDS] = $output;
		$curlOpts[CURLOPT_HTTPHEADER] = $headers;
		$curlOpts[CURLOPT_RETURNTRANSFER] = 1;
		$curlOpts[CURLOPT_HTTPAUTH] = CURLAUTH_ANY;
		$curlOpts[CURLOPT_USERPWD] = $config_userpass;
		$curlOpts[CURLOPT_VERBOSE] = true;
		return $curlOpts;
	}

	/**
	 * Handle any error generated
	 *
	 * @param   mixed              &$output    CURL request result - may be a json string
	 * @param   FabrikFEModelForm  $formModel  Form Model
	 * @param   object             $chandle    CURL object
	 *
	 * @return boolean
	 */

	private function handleError(&$output, $formModel, $chandle)
	{
		if (FabrikWorker::isJSON($output))
		{
			$output = json_decode($output);

			// @TODO make this more generic - currently only for apparty
			if (isset($output->errors))
			{
				// Have to set something in the errors array otherwise form validates
				$formModel->_arErrors['dummy___elementname'][] = 'woops!';
				$formModel->getForm()->error = implode(', ', $output->errors);
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
			throw new RuntimeException(curl_error($chandle));
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

		//  Init the OAuthStore
		$options = array(
				'consumer_key' => $params->get('oauth_consumer_key'),
				'consumer_secret' => $params->get('oauth_consumer_secret'),
				'server_uri' => $params->get('server_uri'),
				'request_token_uri' => $params->get('request_token_uri'),
				'authorize_uri' => $params->get('authorize_uri'),
				'access_token_uri' => $params->get('access_token_uri')
		);

		// Note: do not use "Session" storage in production. Prefer a database
		// storage, such as MySQL.
		OAuthStore::instance("Session", $options);

		/* $options = array('server' => $config->get('host'), 'username' => $config->get('user'),
		 'password' => $config->get('password'),  'database' => $config->get('db'));
		$store   = OAuthStore::instance('MySQL', $options); */
	}

	/**
	 * Run once the form's data has been loaded
	 *
	 * @param   object  $params      Plugin parameters
	 * @param   object  &$formModel  Form model
	 *
	 * @return	bool
	 */
	public function onLoad($params, &$formModel)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		if ($params->get('oauth_consumer_key', '') === '')
		{
			return;
		}
		$this->formModel = $formModel;
		require COM_FABRIK_BASE . '/components/com_fabrik/libs/xing/xing.php';
		//require_once COM_FABRIK_BASE . '/components/com_fabrik/libs/oauth-php/OAuthStore.php';
		//require_once COM_FABRIK_BASE . '/components/com_fabrik/libs/oauth-php/OAuthRequester.php';

		$config = JFactory::getConfig();


		define("OAUTH_CALLBACK_URL", JUri::getInstance());
		define('OAUTH_TMP_DIR', $config->get('tmp_path'));
		define("OAUTH_AUTHORIZE_URL", $params->get('authorize_uri'));

		$this->getOAuthStore();

		$session = JFactory::getSession();
		$user = JFactory::getUser();
		$userid = $user->get('id');
		define(OATH_SESSION_KEY, 'fabrik.rest.xing' . $userid);
		$sessionResponseKey = 'fabrik.rest.xing' . $userid . '.response';

		if ($input->get('reset') == 1 && $input->get('oauth_token', '') === '')
		{
			echo "rset<br>";
			$session->destroy($sessionResponseKey);
			return;
		}
		if ($session->has($sessionResponseKey))
		{

			$responseBody = $session->get($sessionResponseKey);
		}
		else
		{
			//  STEP 1:  If we do not have an OAuth token yet, go get one
			$token = $input->get('oauth_token');
			if (empty($token))
			{
				$this->getOAuthRequestToken();
			}
			else
			{
				$result = $this->getAccessToken();

				if ($result === false)
				{
					return;
				}
				echo "<pre>";print_r($result);echo "</pre>";
				parse_str($result["body"], $responseBody);


				if (!isset($responseBody["user_id"]))
				{
					throw new Exception("user_id not found.");
					return;
				}

				// Save access token for subsequent requests (without asking the user for permission again)
				$session->set($sessionResponseKey, $responseBody);
			}
		}
		$url = $params->get('get');
		$w = new FabrikWorker;
		$url = $w->parseMessageForPlaceHolder($url, $responseBody);
		$result = $this->doGet($url);
		if ($result !== false)
		{
			$data = json_decode($result['body']);
			$this->updateFormModelData($params, $responseBody, $data);
		}
	}

	/**
	 * Step 1:
	 * Get a request token then redirect to the authorization page, they will redirect back
	 *
	 * @return void
	 */
	protected function getOAuthRequestToken()
	{
		$params = $this->getParams();
		$consumerKey = $params->get('oauth_consumer_key');
		$app = JFactory::getApplication();
		$tokenParams = array(
			'oauth_callback' => OAUTH_CALLBACK_URL,
			'oauth_consumer_key' => $consumerKey
		);

		$curlOpts = $this->getOAuthCurlOpts();

		$tokenResult = XingOAuthRequester::requestRequestToken($consumerKey, 0, $tokenParams, 'POST', array(), $curlOpts);
		$requestOpts = array('http_error_codes' => array(200, 201));
		//$tokenResult = OAuthRequester::requestRequestToken($consumerKey, 0, $tokenParams, 'POST', $requestOpts, $curlOpts);
		$uri = OAUTH_AUTHORIZE_URL . "?btmpl=mobile&oauth_token=" . $tokenResult['token'];
		$app->redirect($uri);
	}

	/**
	 * Get the curl options required for the REST request
	 *
	 * @return  array
	 */
	protected function getOAuthCurlOpts()
	{
		return array(CURLOPT_SSL_VERIFYPEER => false);
	}

	/**
	 * Step 2: Get the acces token
	 *
	 * @return boolean|array
	 */
	protected function getAccessToken()
	{
		$app = JFactory::getApplication();
		$params = $this->getParams();
		$consumerKey = $params->get('oauth_consumer_key');
		$session = JFactory::getSession();
		$oauthToken = $app->input->get('oauth_token', '', 'string');
		try
		{
			//$_GET['http_error_codes'] = array(200, 201, 301, 302);

			$curlOpts = $this->getOAuthCurlOpts();
			$result = XingOAuthRequester::requestAccessToken($consumerKey, $oauthToken, 0, 'POST', $_GET, $curlOpts);
			//$result = OAuthRequester::requestAccessToken($consumerKey, $oauthToken, 0, 'POST', $_GET, $curlOpts);
		}
		catch (OAuthException2 $e)
		{
			echo "<h1>get access token error</h1>";
			echo "<pre>restult...";print_r($result);echo "</pre>";
			print_r($e->getMessage());
			return false;
		}
		return $result;
	}

	/**
	 * Perform a curl request to GET from teh web service
	 *
	 * @param   string  $url  GET endpoint
	 *
	 * @throws  RuntimeException
	 *
	 * @return  array|boolean
	 */
	protected function doGet($url)
	{
		// Make the docs requestrequest.
		$curlOpts = array(CURLOPT_SSL_VERIFYPEER => false);
		$request = new XingOAuthRequester($url, 'GET', array());
		//$request = new OAuthRequester($url, 'GET', array());

		$result = $request->doRequest(0, $curlOpts);
		if (in_array((int) $result['code'], array(200, 201)))
		{
			return $result;
		}
		else
		{
			echo "<pre>fabrik rest parse error =";print_r($result);echo "</pre>";//exit;
			//throw new RuntimeException('Fabrik REST form: error parsing result', $result['code']);
			return false;
		}
	}

	/**
	 * Update the form models data with data from CURL request
	 *
	 * @param   JRegistry  $params        Parameters
	 * @param   array      $responseBody  Response body
	 * @param   array      $data          Data returned from CURL request
	 *
	 * @return  void
	 */

	protected function updateFormModelData($params, $responseBody, $data)
	{
		$w = new FabrikWorker;
		$dataMap = $params->get('put_include_list', '');
		$include = $w->parseMessageForPlaceholder($dataMap, $responseBody, true);
		if (FabrikWorker::isJSON($include))
		{
			$include = json_decode($include);

			$keys = $include->put_key;
			$values = $include->put_value;
			$defaults = $include->put_value;
			for ($i = 0; $i < count($keys); $i++)
			{
				$key = $keys[$i];
				$default = $defaults[$i];
				$localKey = FabrikString::safeColNameToArrayKey($values[$i]);
				$remoteData = FArrayHelper::getNestedValue($data, $key, $default, true);
				if (!is_null($remoteData))
				{
					$this->formModel->_data[$localKey] = $remoteData;
				}
			}
		}

	}
}
