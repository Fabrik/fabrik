<?php
/**
 * Post content to twitter
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.twitter
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Abraham\TwitterOAuth\TwitterOAuth;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

if (!class_exists('TwitterOAuth'))
{
//	require_once COM_FABRIK_FRONTEND . '/libs/abraham-twitteroauth/twitteroauth/twitteroauth.php';
	require_once COM_FABRIK_FRONTEND . '/libs/twitteroauth/src/Config.php';
	require_once COM_FABRIK_FRONTEND . '/libs/twitteroauth/src/TwitterOAuthException.php';
	require_once COM_FABRIK_FRONTEND . '/libs/twitteroauth/src/Util/JsonDecoder.php';
	require_once COM_FABRIK_FRONTEND . '/libs/twitteroauth/src/Token.php';
	require_once COM_FABRIK_FRONTEND . '/libs/twitteroauth/src/Util.php';
	require_once COM_FABRIK_FRONTEND . '/libs/twitteroauth/src/Request.php';
	require_once COM_FABRIK_FRONTEND . '/libs/twitteroauth/src/Consumer.php';
	require_once COM_FABRIK_FRONTEND . '/libs/twitteroauth/src/Response.php';
	require_once COM_FABRIK_FRONTEND . '/libs/twitteroauth/src/SignatureMethod.php';
	require_once COM_FABRIK_FRONTEND . '/libs/twitteroauth/src/HmacSha1.php';
	require_once COM_FABRIK_FRONTEND . '/libs/twitteroauth/src/TwitterOAuth.php';
}

//JLoader::registerNamespace('Abraham\TwitterOAuth\TwitterOAuth', COM_FABRIK_FRONTEND . '/libs/twitteroauth/src');
//JLoader::discover('TwitterOAuth', COM_FABRIK_FRONTEND . '/libs/twitteroauth/src');

/**
 * Post content to twitter
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.twitter
 * @since       3.0
 */

class PlgFabrik_FormTwitter extends PlgFabrik_Form
{
	/**
	 * Max length of message
	 *
	 * @var int
	 */
	protected $max_msg_length = 140;

	/**
	 * Somewhere to put bitly object so bitlyCallback function can get at it
	 *
	 * @var mixed
	 */
	protected $bitly = false;

	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @return	bool
	 */

	public function onAfterProcess()
	{
		$this->_process();

		// Stop default redirect from occurring
		return false;
	}

	/**
	 * Create the form model
	 *
	 * @param   int  $id  form id
	 *
	 * @return  FabrikFEModelForm
	 */
	protected function buildModel($id)
	{
		$this->model = JModelLegacy::getInstance('form', 'FabrikFEModel');
		$this->model->setId($id);
		$form = $this->model->getForm();
		$row = $this->getRow();
		$row->params = $form->params;

		return $this->model;
	}

	/**
	 * Now that the oauth request tokens have been set via user validation
	 * we want to create the access tokens for said request tokens
	 *
	 * @return  void
	 */

	public function onTweet()
	{
		global $_SESSION;
		$input = $this->app->input;
		$formModel = $this->buildModel($input->get('formid'));
		$params = $formModel->getParams();
		$renderOrder = $input->getInt('renderOrder');

		$consumerKey = FArrayHelper::fromObject($params->get('twitter_consumer_key'));
		$consumerKey = $consumerKey[$renderOrder];

		$consumerSecret = FArrayHelper::fromObject($params->get('twitter_consumer_secret'));
		$consumerSecret = $consumerSecret[$renderOrder];


		$connection = new TwitterOAuth($consumerKey, $consumerSecret, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

		/* Request access tokens from twitter */
		//$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);
		$access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $_REQUEST['oauth_verifier']));

		$connection = new TwitterOAuth($consumerKey, $consumerSecret, $access_token['oauth_token'], $access_token['oauth_token_secret']);

		/* Save the access tokens. Normally these would be saved in a database for future use. */
		$_SESSION['access_token'] = $access_token;

		// Remove no longer needed request tokens
		unset($_SESSION['oauth_token']);
		unset($_SESSION['oauth_token_secret']);

		// Now we're fully authenticated - lets tweet!
		$this->sendTweet($connection);
	}

	/**
	 * Actually send the tweet and deals with any redirection
	 * set in the session
	 *
	 * @param   TwitterOAuth  $connection  Twitter oauth connection
	 *
	 * @return  void
	 */

	protected function sendTweet($connection)
	{
		$formModel = $this->getModel();
		$input = $this->app->input;
		$formdata = $this->session->get('com_' . $this->package . '.form.data');

		/*
		 * @TODO incorrect for API1.1 should implement this
		 * https://dev.twitter.com/docs/api/1.1/get/application/rate_limit_status. For now just use error msg
		 * $content = $connection->get('account/rate_limit_status');

		if ($content->remaining_hits <= 0)
		{
			$app->enqueueMessage(FText::_('TWITTER_ACCOUNT_LIMIT_REACHED'));
		} */

		// Get logged in user to help with tests
		$user = $connection->get('account/verify_credentials');
		$msg = $_SESSION['msg'];

		$parameters = array('status' => $msg);
		$status = $connection->post('statuses/update', $parameters);
		$show_success = (int) $this->session->get('com_' . $this->package . '.form.twitter.showmessage', 0);

		switch ($connection->getLastHttpCode())
		{
			case 200:
			case 304:
				if ($show_success == 1)
				{
					$this->app->enqueueMessage(FText::_('PLG_FORM_TWITTER_SUCCESS'));
				}
				break;
			default:
				$this->app->enqueueMessage(FText::_('PLG_FORM_TWITTER_ERR') . ": " . $connection->getLastHttpCode() . " : " . $status->errors[0]->message);
		}

		$url = $input->get('fabrik_referrer', '', 'string');
		$context = $formModel->getRedirectContext();
		$url = $this->session->get($context . 'url', array($url));
		$url = array_shift($url);
		$this->app->redirect($url);
	}

	/**
	 * Process plugin
	 *
	 * @return void
	 */
	private function _process()
	{
		$params = $this->getParams();
		global $_SESSION;
		$input = $this->app->input;
		$formModel = $this->getModel();

		$this->session->set('com_' . $this->package . '.form.twitter.showmessage', $params->get('twitter-show-success-msg', 0));
		$_SESSION['msg'] = $this->getMessage();

		// If the admin has specified an account use that
		$consumerKey = $params->get('twitter_consumer_key');
		$consumerSecret = $params->get('twitter_consumer_secret');

		/*
		if ($params->get('twitter_oauth_token') == '')
		{
			throw new RuntimeException(FText::_('PLG_FORM_TWITTER_ERR_NO_OAUTH_TOKEN'), 500);
		}

		if ($params->get('twitter_oauth_token_secret') == '')
		{
			throw new RuntimeException(FText::_('PLG_FORM_TWITTER_ERR_NO_OAUTH_SECRET_TOKEN'), 500);
		}
		*/

		if (!empty($params->get('twitter_oauth_token_secret')))
		{
			$input->set('oauth_verifier', $params->get('twitter_oauth_verifier'));
			$token = $params->get('twitter_oauth_token');
			$secret = $params->get('twitter_oauth_token_secret');
			$connection = new TwitterOAuth($consumerKey, $consumerSecret, $token, $secret);
			$this->sendTweet($connection);

			return;
		}

		$context = $formModel->getRedirectContext();
		$surl = (array) $this->session->get($context . 'url', array());
		$surl[$this->renderOrder] = $input->getString('fabrik_referrer');
		$this->session->set($context . 'url', $surl);
		$this->session->set($context . 'redirect_content_how', 'samepage');

		// Otherwise get authorization url from user to use their own account

		// $this->row not set ?! so this callback url was giving notices
		$callback = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $this->package . '&task=plugin.pluginAjax&plugin=twitter&g=form&method=tweet&formid='
			. $formModel->getId();
		$callback .= '&renderOrder=' . $this->renderOrder;

		// Build TwitterOAuth object with client credentials
		if ($consumerKey == '')
		{
			throw new RuntimeException('Please enter your consumer key. You may need to save the form first before continuing');
		}

		if ($consumerSecret == '')
		{
			throw new RuntimeException('Please enter your consumer secret. You may need to save the form first before continuing');
		}

		$connection = new TwitterOAuth($consumerKey, $consumerSecret);

		// Get temporary credentials.
		try
		{
			$requestToken = $connection->oauth('oauth/request_token', array('oauth_callback' => $callback));

			// Save temporary credentials to session.
			$_SESSION['oauth_token']              = $token = $requestToken['oauth_token'];
			$_SESSION['oauth_token_secret']       = $requestToken['oauth_token_secret'];
			$_SESSION['oauth_callback_confirmed'] = $requestToken['oauth_callback_confirmed'];

			// Build authorize URL and redirect user to Twitter.
			// $url = $connection->getAuthorizeURL($token);
			$url = $connection->url('oauth/authorize', array('oauth_token' => $requestToken['oauth_token']));
			//header('Location: ' . $url);
			$this->app->redirect($url);
		}
		catch (TwitterOAuthException $e)
		{
			throw new RuntimeException('Could not connect to Twitter. Refresh the page or try again later.');
		}
	}

	/**
	 * Convert the posted form data to the data to be shown in the email
	 * e.g. radio buttons swap their values for the value's label
	 *
	 * @return array email data
	 */
	public function getEmailData()
	{
		$input = $this->app->input;
		$formModel = $this->getModel();
		$data = parent::getEmailData();
		$id = $input->get('rowid');
		$formId = $formModel->getId();
		$data['fabrik_editurl'] = COM_FABRIK_LIVESITE
			. JRoute::_("index.php?option=com_" . $this->package . "&amp;view=form&amp;formid=" . $formId . "&amp;rowid=" . $id);
		$data['fabrik_viewurl'] = COM_FABRIK_LIVESITE
			. JRoute::_("index.php?option=com_" . $this->package . "&amp;view=details&amp;formid=" . $formId . "&amp;rowid=" . $id);

		// $$$ rob fabrik_viewurl/fabrik_editurl described in help text as fabrik_edit_url/fabrik_view_url.
		// $$$ hugh - so let's add edit_link and view_link as well, just for consistency
		$data['fabrik_edit_url'] = $data['fabrik_editurl'];
		$data['fabrik_view_url'] = $data['fabrik_viewurl'];
		$data['fabrik_editlink'] = "<a href=\"{$data['fabrik_editurl']}\">" . FText::_('EDIT') . "</a>";
		$data['fabrik_viewlink'] = "<a href=\"{$data['fabrik_viewurl']}\">" . FText::_('VIEW') . "</a>";
		$data['fabrik_edit_link'] = "<a href=\"{$data['fabrik_editurl']}\">" . FText::_('EDIT') . "</a>";
		$data['fabrik_view_link'] = "<a href=\"{$data['fabrik_viewurl']}\">" . FText::_('VIEW') . "</a>";

		return $data;
	}

	/**
	 * Call back function used from within bitlifyMessage() to URL shorten each link
	 *
	 * @param   string  $url  full url to shorten
	 *
	 * @return  string  shortened url
	 */
	private function bitlifyCallback($url)
	{
		$return_url = $url[1];

		if ($this->bitly === false)
		{
			return $return_url;
		}

		if (!strstr($url[1], 'bit.ly/') && $url[1] !== '')
		{
			$return_url = (string) $this->bitly->shorten($url[1]);

			if ($this->bitly->getError() > 0)
			{
				throw new RuntimeException('Error with bit.ly: ' . $this->bitly->getErrorMsg());
			}
		}

		return $return_url;
	}

	/**
	 * URL Shorten any links in the message
	 *
	 * @param   string  $msg  message
	 *
	 * @return  string   message
	 */
	private function bitlifyMessage($msg)
	{
		static $bitly;

		if (!isset($bitly))
		{
			$params = $this->getParams();
			$bitly_login = $params->get('twitter_bitly_api_login', '');
			$bitly_key = $params->get('twitter_bitly_api_key', '');

			if (!empty($bitly_login) && !empty($bitly_key))
			{
				require_once JPATH_SITE . '/components/com_fabrik/libs/bitly/bitly.php';
				$this->bitly = $bitly = new bitly($bitly_login, $bitly_key);
			}
			else
			{
				$this->bitly = $bitly = false;

				return $msg;
			}
		}

		$re = "#(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)"
			. "(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?������]))#";
		$msg = preg_replace_callback($re, array(&$this, 'bitlifyCallback'), $msg);

		return $msg;
	}

	/**
	 * Get message to tweet
	 *
	 * @return  string  message
	 */
	protected function getMessage()
	{
		$params = $this->getParams();
		$data = $this->getProcessData();
		$twitter_msg_field_id = $params->get('twitter_msg_field', '');

		if ($twitter_msg_field_id != '')
		{
			$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($twitter_msg_field_id);
			$element = $elementModel->getElement(true);
			$twitter_msg_field = $elementModel->getFullName(true, false);
			$msg = $data[$twitter_msg_field];
		}
		else
		{
			$w = new FabrikWorker;
			$msg = $w->parseMessageForPlaceHolder($params->get('twitter_msg_tmpl'), $data);
		}

		$msg = $this->bitlifyMessage($msg);

		// $$$ hugh - I thought the twitter class chopped the msg to 140, but apparently it doesn't ..
		$msg = JString::substr($msg, 0, $this->max_msg_length);

		return $msg;
	}

	/**
	 * from admin, get the administrator to authenticate an account for the form
	 *
	 * @return  void
	 */
	public function onAuthenticateAdmin()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$formModel = $this->buildModel($input->getInt('formid'));
		$params = $formModel->getParams();
		$counter = $input->get('repeatCounter');
		$consumerKey = $params->get('twitter_consumer_key');
		if (is_object($consumerKey))
		{
				$consumerKey = FArrayHelper::fromObject($consumerKey);
		}
		$consumerKey = $consumerKey[$counter];

		$consumerSecret = $params->get('twitter_consumer_secret');
		if (is_object($consumerSecret))
		{
			$consumerSecret = FArrayHelper::fromObject($consumerSecret);
		}
		$consumerSecret = $consumerSecret[$counter];

		$callback = COM_FABRIK_LIVESITE
			. 'index.php?option=com_' . $package . '&task=plugin.pluginAjax&plugin=twitter&tmpl=component&g=form&method=updateAdmin&formid='
			. $formModel->getId();
		$callback .= "&repeatCounter=" . $input->getInt('repeatCounter');

		if (!function_exists('curl_init'))
		{
			throw new RuntimeException(FText::_('PLG_FORM_TWITTER_ERR_CURL'), 500);
		}

		// Build TwitterOAuth object with client credentials.
		$connection = new TwitterOAuth($consumerKey, $consumerSecret);

		// Get temporary credentials.
		try
		{
			$requestToken = $connection->oauth('oauth/request_token', array('oauth_callback' => $callback));

			// Save temporary credentials to session.
			$_SESSION['oauth_token']              = $token = $requestToken['oauth_token'];
			$_SESSION['oauth_token_secret']       = $requestToken['oauth_token_secret'];
			$_SESSION['oauth_callback_confirmed'] = $requestToken['oauth_callback_confirmed'];

			// Build authorize URL and redirect user to Twitter.
			// $url = $connection->getAuthorizeURL($token);
			$url = $connection->url('oauth/authorize', array('oauth_token' => $requestToken['oauth_token']));
			$app->redirect($url);
		}
		catch (TwitterOAuthException $e)
		{
			throw new RuntimeException('Could not connect to Twitter. Refresh the page or try again later.');
		}
	}

	/**
	 * From admin the user has authorize the plugin for this app.
	 * Lets store the data for it.
	 * As we are in a pop up window we need to set some js to update the parent window's
	 * parameters
	 *
	 * @return  void
	 */
	public function onUpdateAdmin()
	{
		$input = $this->app->input;
		$formModel = $this->buildModel($input->getInt('formid'));
		$params = $formModel->getParams();
		$renderOrder = $input->get('repeatCounter');

		$consumerKey = FArrayHelper::fromObject($params->get('twitter_consumer_key'));
		$consumerKey = $consumerKey[$renderOrder];
		$consumerSecret = FArrayHelper::fromObject($params->get('twitter_consumer_secret'));
		$consumerSecret = $consumerSecret[$renderOrder];
		$connection = new TwitterOAuth($consumerKey, $consumerSecret, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

		/* Request access tokens from twitter */
		$access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $_REQUEST['oauth_verifier']));

		// Save the access token to the element params
		$formModel = JModelLegacy::getInstance('Form', 'FabrikFEModel');
		$formModel->setId($input->getInt('formid'));
		$row = $formModel->getForm();

		$params = $formModel->getParams();
		$opts = $params->toArray();
		$counter = $input->get('repeatCounter');
		$pairs = array('twitter_oauth_token' => 'oauth_token', 'twitter_oauth_token_secret' => 'oauth_token_secret',
			'twitter_oauth_user' => 'screen_name');

		$js = array();

		$jsValues = array();

		foreach ($pairs as $paramname => $requestname)
		{
			$tokens = (array) FArrayHelper::getValue($opts, $paramname);
			$newtokens = array();

			for ($i = 0; $i <= $counter; $i++)
			{
				$newtokens[$i] = ($i == $counter) ? $access_token[$requestname] : '';
				$jsid = '#jform_params_' . $paramname . '-' . $i;
				//$js[] = "window.opener.document.getElement('$jsid').value = '$newtokens[$i]';";
				$jsValues[]= array($jsid, $newtokens[$i]);
			}

			$opts[$paramname] = $newtokens;
		}

		$json = json_encode($jsValues);


		$row->params = json_encode($opts);
		$row->store();

		$langFile = 'com_fabrik.plg.form.fabriktwitter';
		$this->lang->load($langFile, JPATH_ADMINISTRATOR, null, true);

		// If we had already authorized the app then we will still be in the admin page - so update the fields:
		echo FText::_('PLG_FORM_TWITTER_CREDENTIALS_SAVED');
		$document = JFactory::getDocument();
		//$script = implode("\n", $js) . "
		$script = <<<EOT
window.opener.postMessage($json, '*');
(function() {window.close()}).delay(4000);
EOT;
		$document->addScriptDeclaration($script);
	}
}
