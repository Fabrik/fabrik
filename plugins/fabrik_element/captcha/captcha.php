<?php
/**
 * Plugin element to captcha
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.captcha
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\String\String;

/**
 * Plugin element to captcha
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.captcha
 * @since       3.0
 */
class PlgFabrik_ElementCaptcha extends PlgFabrik_Element
{
	protected $font = 'monofont.ttf';

	/**
	 * Generate captcha text
	 *
	 * @param   int $characters number of characters to generate
	 *
	 * @return  string captcha text
	 */
	protected function _generateCode($characters)
	{
		// List all possible characters, similar looking characters and vowels have been removed
		$possible = '23456789bcdfghjkmnpqrstvwxyz';
		$code     = '';
		$i        = 0;

		while ($i < $characters)
		{
			$code .= String::substr($possible, mt_rand(0, String::strlen($possible) - 1), 1);
			$i++;
		}

		return $code;
	}

	/**
	 * Get the element's HTML label
	 *
	 * @param   int    $repeatCounter group repeat counter
	 * @param   string $tmpl          form template
	 *
	 * @return  string  label
	 */
	public function getLabel($repeatCounter, $tmpl = '')
	{
		$params = $this->getParams();

		if ($this->user->id != 0)
		{
			if ($params->get('captcha-showloggedin', 0) == 0)
			{
				return '';
			}
		}

		return parent::getLabel($repeatCounter, $tmpl);
	}

	/**
	 * Is the element hidden or not - if not set then return false
	 *
	 * @return  bool
	 */
	public function isHidden()
	{
		$params = $this->getParams();

		if ($this->user->get('id') != 0)
		{
			if ($params->get('captcha-showloggedin', 0) == 0)
			{
				return true;
			}
		}

		return parent::isHidden();
	}

	/**
	 * Check user can view the read only element OR view in list view
	 * $$$ rob 14/03/2012 always returns false now - cant see a need to show it in the details / list view
	 *
	 * @param   string $view View list/form @since 3.0.7
	 *
	 * @return  bool  can view or not
	 */
	public function canView($view = 'form')
	{
		return false;
	}

	/**
	 * Check if the user can use the active element
	 *
	 * @param   string $location To trigger plugin on
	 * @param   string $event    To trigger plugin on
	 *
	 * @return  bool can use or not
	 */
	public function canUse($location = null, $event = null)
	{
		$params = $this->getParams();

		if ($this->user->get('id') != 0)
		{
			if ($params->get('captcha-showloggedin', 0) == 0)
			{
				return false;
			}
		}

		return parent::canUse();
	}

	/**
	 * Gets the challenge HTML (AJAX version).
	 * This is called from the browser, and the resulting reCAPTCHA HTML widget
	 * is embedded within the HTML form it was called from.
	 *
	 * @param string  $id      the HTML id for the div
	 * @param string  $pubkey  A public key for reCAPTCHA
	 * @param string  $theme   Theme to use, default red
	 * @param string  $lang    Language to use, default en
	 * @param string  $error   The error given by reCAPTCHA (optional, default is null)
	 * @param boolean $use_ssl Should the request be made over ssl? (optional, default is false)
	 *
	 * @return string - The HTML to be embedded in the user's form.
	 */
	function fabrik_recaptcha_get_html($id, $pubkey, $theme = "red", $lang = "en", $error = null, $use_ssl = false)
	{
		if ($pubkey == null || $pubkey == '')
		{
			die ("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>");
		}

		if ($use_ssl)
		{
			$server = RECAPTCHA_API_SECURE_SERVER;
		}
		else
		{
			$server = RECAPTCHA_API_SERVER;
		}

		//$str = '<script type="text/javascript" src="' . $server . '/js/recaptcha_ajax.js"></script> ';
		$str      = '  <div id="' . $id . '"></div> ';
		$document = JFactory::getDocument();
		$document->addScript($server . '/js/recaptcha_ajax.js');
		FabrikHelperHTML::addScriptDeclaration(
			'window.addEvent("fabrik.loaded", function() {
			Recaptcha.create(
				"' . $pubkey . '",
	    		"' . $id . '",
	    		{
	    			theme: "' . $theme . '",
					lang : "' . $lang . '"
				}
			);
		});'
		);

		return $str;
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array $data          to pre-populate element with
	 * @param   int   $repeatCounter repeat group counter
	 *
	 * @return  string    elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$name    = $this->getHTMLName($repeatCounter);
		$id      = $this->getHTMLId($repeatCounter);
		$element = $this->getElement();
		$params  = $this->getParams();
		$value   = $this->getValue($data, $repeatCounter);

		if (!$this->isEditable())
		{
			if ($element->hidden == '1')
			{
				return '<!-- ' . stripslashes($value) . ' -->';
			}
			else
			{
				return stripslashes($value);
			}
		}

		if ($params->get('captcha-method') == 'recaptcha')
		{
			if (!function_exists('_recaptcha_qsencode'))
			{
				require_once JPATH_SITE . '/plugins/fabrik_element/captcha/libs/recaptcha-php-1.11/recaptchalib.php';
			}

			$publickey = $params->get('recaptcha_publickey');

			// $$$tom added lang & theme options
			$theme = $params->get('recaptcha_theme', 'red');
			$lang  = String::strtolower($params->get('recaptcha_lang', 'en'));
			$error = null;

			if ($this->user->get('id') != 0 && $params->get('captcha-showloggedin', 0) == false)
			{
				return '<input class="inputbox text" type="hidden" name="' . $name . '" id="' . $id . '" value="" />';
			}
			else
			{
				$browser = JBrowser::getInstance();
				$ssl     = $browser->isSSLConnection();

				return $this->fabrik_recaptcha_get_html($id, $publickey, $theme, $lang, $error, $ssl);
			}
		}
		elseif ($params->get('captcha-method') == 'playthru')
		{
			if ($this->user->get('id') != 0 && $params->get('captcha-showloggedin', 0) == false)
			{
				return '<input class="inputbox text" type="hidden" name="' . $name . '" id="' . $id . '" value="" />';
			}

			if (!defined('AYAH_PUBLISHER_KEY'))
			{
				define('AYAH_PUBLISHER_KEY', $params->get('playthru_publisher_key', ''));
				define('AYAH_SCORING_KEY', $params->get('playthru_scoring_key', ''));
			}

			require_once JPATH_SITE . '/plugins/fabrik_element/captcha/libs/ayah_php_bundle_1.1.7/ayah.php';
			$ayah = new AYAH;

			return $ayah->getPublisherHTML();
		}
		elseif ($params->get('captcha-method') == 'nocaptcha')
		{
			$layout                = $this->getLayout('nocaptcha');
			$displayData           = new stdClass;
			$displayData->id       = $id;
			$displayData->name     = $name;
			$displayData->site_key = $params->get('recaptcha_publickey');

			return $layout->render($displayData);
		}
		else
		{
			if (!function_exists('imagettfbbox'))
			{
				throw new RuntimeException(FText::_('PLG_FABRIK_ELEMENT_CAPTCHA_STANDARD_TTF_ERROR'));
			}

			$size       = $element->width;
			$fontSize   = $params->get('captcha-font-size', 22);
			$angle      = $params->get('captcha-angle', 0);
			$padding    = $params->get('captcha-padding', 10);
			$characters = $params->get('captcha-chars', 6);
			$code       = $this->_generateCode($characters);

			// $$$ hugh - code that generates image now in image.php
			$this->session->set('com_' . $this->package . '.element.captcha.security_code', $code);

			// Additional plugin params with validation
			$noiseColor = $params->get('captcha-noise-color', '0000FF');

			// '0000FF' again if we have param value but it's invalid
			$noiseColor = $this->_getRGBcolor($noiseColor, '0000FF');
			$textColor  = $params->get('captcha-text-color', '0000FF');
			$textColor  = $this->_getRGBcolor($textColor, '0000FF');
			$bgColor    = $params->get('captcha-bg', 'FFFFFF');
			$bgColor    = $this->_getRGBcolor($bgColor, 'FFFFFF');

			// Let's keep all params in relatively safe place not only captcha value
			// Felixkat - Add
			$this->session->set('com_' . $this->package . '.element.captcha.fontsize', $fontSize);
			$this->session->set('com_' . $this->package . '.element.captcha.angle', $angle);
			$this->session->set('com_' . $this->package . '.element.captcha.padding', $padding);
			$this->session->set('com_' . $this->package . '.element.captcha.noise_color', $noiseColor);
			$this->session->set('com_' . $this->package . '.element.captcha.text_color', $textColor);
			$this->session->set('com_' . $this->package . '.element.captcha.bg_color', $bgColor);
			$this->session->set('com_' . $this->package . '.element.captcha.font', $this->font);

			$type = $params->get('password') == '1' ? 'password' : 'text';

			if ($this->elementError != '')
			{
				$type .= ' elementErrorHighlight';
			}

			if ($element->hidden == '1')
			{
				$type = 'hidden';
			}

			$layout            = $this->getLayout('form');
			$displayData       = new stdClass;
			$displayData->id   = $id;
			$displayData->name = $name;

			// $$$ hugh - changed from static image path to using simple image.php script, to get round IE caching images
			//$displayData->url = COM_FABRIK_LIVESITE . 'plugins/fabrik_element/captcha/image.php?foo=' . rand();

			// Changed to relative path as some sites were on site.com and loading from www.site.com (thus sessions different)
			$displayData->url  = JRoute::_('plugins/fabrik_element/captcha/image.php?foo=' . rand());
			$displayData->type = $type;
			$displayData->size = $size;

			return $layout->render($displayData);
		}
	}

	/**
	 * Internal element validation
	 *
	 * @param   array $data          form data
	 * @param   int   $repeatCounter repeat group counter
	 *
	 * @return bool
	 */
	public function validate($data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		$input  = $this->app->input;

		if (!$this->canUse())
		{
			return true;
		}

		if ($params->get('captcha-method') == 'recaptcha')
		{
			if (!function_exists('_recaptcha_qsencode'))
			{
				require_once JPATH_SITE . '/plugins/fabrik_element/captcha/libs/recaptcha-php-1.11/recaptchalib.php';
			}

			$privateKey = $params->get('recaptcha_privatekey');

			if ($input->get('recaptcha_response_field'))
			{
				$challenge = $input->get('recaptcha_challenge_field');
				$response  = $input->get('recaptcha_response_field');
				$resp      = recaptcha_check_answer($privateKey, FabrikString::filteredIp(), $challenge, $response);

				return ($resp->is_valid) ? true : false;
			}

			return false;
		}
		elseif ($params->get('captcha-method') == 'nocaptcha')
		{
			if ($input->get('g-recaptcha-response'))
			{
				require_once JPATH_SITE . '/plugins/fabrik_element/captcha/libs/ReCaptcha/ReCaptcha.php';
				require_once JPATH_SITE . '/plugins/fabrik_element/captcha/libs/ReCaptcha/RequestMethod.php';
				require_once JPATH_SITE . '/plugins/fabrik_element/captcha/libs/ReCaptcha/RequestMethod/Post.php';
				require_once JPATH_SITE . '/plugins/fabrik_element/captcha/libs/ReCaptcha/RequestParameters.php';
				require_once JPATH_SITE . '/plugins/fabrik_element/captcha/libs/ReCaptcha/Response.php';

				$privateKey = $params->get('recaptcha_privatekey');
				$noCaptcha  = new \ReCaptcha\ReCaptcha($privateKey);
				$response   = $input->get('g-recaptcha-response');
				$server     = $input->server->get('REMOTE_ADDR');
				$resp       = $noCaptcha->verify($response, $server);

				return $resp->isSuccess();
			}

			return false;
		}
		elseif ($params->get('captcha-method') == 'playthru')
		{
			if (!defined('AYAH_PUBLISHER_KEY'))
			{
				define('AYAH_PUBLISHER_KEY', $params->get('playthru_publisher_key', ''));
				define('AYAH_SCORING_KEY', $params->get('playthru_scoring_key', ''));
			}

			require_once JPATH_SITE . '/plugins/fabrik_element/captcha/libs/ayah_php_bundle_1.1.7/ayah.php';
			$ayah = new AYAH;

			return $ayah->scoreResult();
		}
		else
		{
			$this->getParams();

			if ($this->session->get('com_' . $this->package . '.element.captcha.security_code', null) != $data)
			{
				return false;
			}

			return true;
		}
	}

	/**
	 * Get validation error - run through JText
	 *
	 * @return  string
	 */
	public function getValidationErr()
	{
		return FText::_('PLG_ELEMENT_CAPTCHA_FAILED');
	}

	/**
	 * Determine if the element should run its validation plugins on form submission
	 *
	 * @return  bool    default true
	 */

	public function mustValidate()
	{
		if (!$this->canUse() && !$this->canView())
		{
			return false;
		}

		return parent::mustValidate();
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int $repeatCounter Repeat group counter
	 *
	 * @return  array
	 */
	public function elementJavascript($repeatCounter)
	{
		if ($this->user->get('id') == 0)
		{
			$id   = $this->getHTMLId($repeatCounter);
			$opts = $this->getElementJSOptions($repeatCounter);

			return array('FbCaptcha', $id, $opts);
		}

		return array();
	}

	/**
	 * Used to format the data when shown in the form's email
	 *
	 * @param   mixed $value         element's data
	 * @param   array $data          form records data
	 * @param   int   $repeatCounter repeat group counter
	 *
	 * @return  string    formatted value
	 */
	public function getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		return "";
	}

	/**
	 * $$$ e-kinst Convert a hex colour to RGB
	 *
	 * @param   string $hexColor 3- or 6-digits hex color with optional leading '#'
	 * @param   string $default  default hex color if first param invalid
	 *
	 * @return  string    as 'R+G+B' where R,G,B are decimal
	 */
	private function _getRGBcolor($hexColor, $default = 'FF0000')
	{
		$regex = '/^#?(([\da-f])([\da-f])([\da-f])|([\da-f]{2})([\da-f]{2})([\da-f]{2}))$/i';
		$rgb   = array();

		if (!preg_match($regex, $hexColor, $rgb))
		{
			if (!preg_match($regex, $default, $rgb))
			{
				// In case where $default invalid also (call error)
				$rgb = array('FF0000', 'FF0000', 'FF', '00', '00');
			}
		}

		array_shift($rgb);
		array_shift($rgb);

		if (count($rgb) > 3)
		{
			$rgb = array_slice($rgb, 3, 3);
		}

		for ($i = 0; $i < 3; $i++)
		{
			if (String::strlen($rgb[$i]) == 1)
			{
				$rgb[$i] .= $rgb[$i];
			}

			$rgb[$i] = intval($rgb[$i], 16);
		}

		return implode('+', $rgb);
	}
}
