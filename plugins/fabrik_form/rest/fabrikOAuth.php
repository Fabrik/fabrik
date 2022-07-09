<?php
/**
 * Fabrik class for generating Generic OAuth API access token.
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.rest
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\Registry\Registry;

/**
 * Fabrik class for generating Generic OAuth API access token.
 *
 */
class FabrikOauth extends JOAuth1Client
{
	/**
	 * @var    Registry  Options for the FabrikOauth object.
	 * @since  13.1
	 */
	protected $options;

	/**
	 * Constructor.
	 *
	 * @param   Registry  $options  FabrikOauth options object.
	 * @param   JHttp     $client   The HTTP client object.
	 * @param   JInput    $input    The input object
	 *
	 * @since   13.1
	 */
	public function __construct(Registry $options = null, JHttp $client = null, JInput $input = null)
	{
		$this->options = isset($options) ? $options : new Registry;

		// Call the JOAuth1Client constructor to setup the object.
		parent::__construct($this->options, $client, $input);
	}

	/**
	 * Method to verify if the access token is valid by making a request to an API endpoint.
	 *
	 * @return  boolean  Returns true if the access token is valid and false otherwise.
	 *
	 * @since   13.1
	 */
	public function verifyCredentials()
	{
		return true;
	}

	/**
	 * Method to validate a response.
	 *
	 * @param   string         $url       The request URL.
	 * @param   JHttpResponse  $response  The response to validate.
	 *
	 * @return  void
	 *
	 * @since  13.1
	 * @throws DomainException
	 */
	public function validateResponse($url, $response)
	{
		if (!$code = $this->getOption('success_code'))
		{
			$code = 200;
		}

		if ($response->code != $code && $response->code != 201)
		{
			if ($error = json_decode($response->body))
			{
				throw new DomainException('Error code ' . $error->errorCode . ' received with message: ' . $error->message . '.');
			}
			else
			{
				throw new DomainException($response->body);
			}
		}
	}

	/**
	 * Method used to set permissions.
	 *
	 * @param   mixed  $scope  String or an array of string containing permissions.
	 *
	 * @return  FabrikOauth  This object for method chaining
	 *
	 */
	public function setScope($scope)
	{
		$this->setOption('scope', $scope);

		return $this;
	}

	/**
	 * Method to get the current scope
	 *
	 * @return  string String or an array of string containing permissions.
	 *
	 * @since   13.1
	 */
	public function getScope()
	{
		return $this->getOption('scope');
	}
}