<?php
/**
 * Plugin element to captcha
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.captcha
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use ReCaptcha\ReCaptcha;

require_once JPATH_ROOT . '/plugins/fabrik_element/captcha/vendor/autoload.php';
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
			$code .= JString::substr($possible, mt_rand(0, JString::strlen($possible) - 1), 1);
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
			$lang  = FabrikWorker::replaceWithLanguageTags(JString::strtolower($params->get('recaptcha_lang', 'en')));
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
		elseif ($params->get('captcha-method') == 'nocaptcha')
		{
			$layout                = $this->getLayout('nocaptcha');
			$displayData           = new stdClass;
			$displayData->id       = $id;
			$displayData->name     = $name;
			$displayData->site_key = $params->get('recaptcha_publickey');
			$displayData->lang     = FabrikWorker::replaceWithLanguageTags(JString::strtolower($params->get('recaptcha_lang', 'en')));

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
			$this->session->set('com_' . $this->package . '.element.captcha.security_code', $code);
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

			$formId    = $this->getFormModel()->getId();
			$rowId     = $this->app->input->get('rowid', '0');
			$elementId = $this->getId();

			$displayData->url  = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $this->package
				. '&task=plugin.pluginAjax&plugin=captcha&method=ajax_image&format=raw&element_id='
				. $elementId . '&formid=' . $formId . '&rowid=' . $rowId . '&repeatcount=' . $repeatCounter;

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

		// if this is the submit from the confirmation plugin, we already validated when rendering confirmation page
		if ($this->app->input->get('fabrik_confirmation', '') === '2')
		{
			return true;
		}

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
				$privateKey = $params->get('recaptcha_privatekey');
				$noCaptcha  = new ReCaptcha($privateKey, new \ReCaptcha\RequestMethod\SocketPost());
				$response   = $input->get('g-recaptcha-response');
				$server     = $input->server->get('REMOTE_ADDR');
				$resp       = $noCaptcha->verify($response, $server);

				if ($resp->isSuccess())
				{
					return true;
				}
				else
				{
					if (FabrikHelperHTML::isDebug())
					{
						$msg = "noCaptcha error: ";
						foreach ($resp->getErrorCodes() as $code) {
							$msg .= '<tt>' . $code . '</tt> ';
						}
						$this->app->enqueueMessage($msg);
					}
					return false;
				}
			}

			if (FabrikHelperHTML::isDebug())
			{
				$this->app->enqueueMessage("No g-recaptcha-response!");
			}

			return false;
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
		$params = $this->getParams();
		$method = $params->get('captcha-method', 'standard');
		return FText::_('PLG_ELEMENT_CAPTCHA_' . strtoupper($method) . '_FAILED');
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
			if (JString::strlen($rgb[$i]) == 1)
			{
				$rgb[$i] .= $rgb[$i];
			}

			$rgb[$i] = intval($rgb[$i], 16);
		}

		return $rgb;
	}

	public function onAjax_image() {
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$this->setId($this->app->input->getInt('element_id'));
		$this->loadMeForAjax();
		$this->getElement();
		$params = $this->getParams();

		$code = $this->session->get('com_' . $package . '.element.captcha.security_code', false);

		if (empty($code))
		{
			exit;
		}

		$fontSize   = $params->get('captcha-font-size', 22);
		$angle      = $params->get('captcha-angle', 0);
		$padding    = $params->get('captcha-padding', 10);
		$nc = $params->get('captcha-noise-color', '0000FF');
		$nc = $this->_getRGBcolor($nc, '0000FF');
		$tc  = $params->get('captcha-text-color', '0000FF');
		$tc  = $this->_getRGBcolor($tc, '0000FF');
		$bc    = $params->get('captcha-bg', 'FFFFFF');
		$bc    = $this->_getRGBcolor($bc, 'FFFFFF');

		// Create textbox and add text
		$fontPath = JPATH_SITE . '/plugins/fabrik_element/captcha/' . $this->font;

		if (function_exists('imagettfbbox'))
		{
			$the_box = $this->calculateTextBox($code, $fontPath, $fontSize, $angle);
		}
		else
		{
			$the_box = array('width' => 150, 'height' => 50, 'top' => 0, 'left' => 0);
		}


		$imgWidth = $the_box["width"] + $padding;
		$imgHeight = $the_box["height"] + $padding;

		$image = imagecreate($imgWidth, $imgHeight);

		$background_color = imagecolorallocate($image, $bc[0], $bc[1], $bc[2]);
		$text_color = imagecolorallocate($image, $tc[0], $tc[1], $tc[2]);
		$noise_color = imagecolorallocate($image, $nc[0], $nc[1], $nc[2]);

		// Generate random dots in background
		for ($i = 0; $i < ($imgWidth * $imgHeight) / 3; $i++)
		{
			imagefilledellipse($image, mt_rand(0, $imgWidth), mt_rand(0, $imgHeight), 1, 1, $noise_color);
		}

		// Generate random lines in background
		for ($i = 0; $i < ($imgWidth * $imgHeight) / 150; $i++)
		{
			imageline($image, mt_rand(0, $imgWidth), mt_rand(0, $imgHeight), mt_rand(0, $imgWidth), mt_rand(0, $imgHeight), $noise_color);
		}

		$left = $the_box["left"] + ($imgWidth / 2) - ($the_box["width"] / 2);
		$top = $the_box["top"] + ($imgHeight / 2) - ($the_box["height"] / 2);

		if (function_exists('imagettfbbox'))
		{
			imagettftext(
				$image,
				$fontSize,
				$angle,
				$left,
				$top,
				$text_color,
				$fontPath,
				$code
			);
		}
		else
		{
			imagestring($image, 6, $left, $top, $code, $text_color);
		}

		// ... set no-cache (and friends) headers ...
		// Some time in the past
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		header('Accept-Ranges: bytes');

		header('Content-Type: image/jpeg');

		ob_start();
		imagejpeg($image);
		$img = ob_get_contents();

		/**
		Felixkat - Clean has been replaced with flush due to a image truncating issue
		Haven't been able to pinpoint the exact issue yet, possibly PHP version related
		http://fabrikar.com/forums/showthread.php?p=147606#post147606
		 */
		// Not this: ob_end_clean();
		ob_end_flush();

		// For some weird reason if we do this in 5.2.x the image gets truncated
		// http://fabrikar.com/forums/showthread.php?t=26941&page=5
		if (version_compare(PHP_VERSION, '5.3.0') < 0)
		{
			header('Content-Length: ' . JString::strlen($img));
		}

		imagedestroy($image);
		echo $img;

		// ... and we're done.
		exit();

	}

	/**
	 *  Simple function that calculates the *exact* bounding box (single pixel precision).
	 *  The function returns an associative array with these keys:
	 *  left, top:  coordinates you will pass to imagettftext
	 *  width, height: dimension of the image you have to create
	 *
	 * @param   string  $code      Code
	 * @param   string  $fontPath  Font path
	 * @param   int     $fontsize  Font size
	 * @param   int     $angle     Text angle
	 *
	 * @return  array
	 */
	private function calculateTextBox($code, $fontPath, $fontsize, $angle)
	{
		$rect = imagettfbbox($fontsize, $angle, $fontPath, $code);
		$minX = min(array($rect[0], $rect[2], $rect[4], $rect[6]));
		$maxX = max(array($rect[0], $rect[2], $rect[4], $rect[6]));
		$minY = min(array($rect[1], $rect[3], $rect[5], $rect[7]));
		$maxY = max(array($rect[1], $rect[3], $rect[5], $rect[7]));

		return array
		(
			"left"   => abs($minX) - 1,
			"top"    => abs($minY) - 1,
			"width"  => $maxX - $minX,
			"height" => $maxY - $minY,
			"box"    => $rect
		);
	}
}
