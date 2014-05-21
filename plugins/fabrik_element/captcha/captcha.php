<?php
/**
 * Plugin element to captcha
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.captcha
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/plugins/fabrik_element/captcha/libs/recaptcha-php-1.11/recaptchalib.php';

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
	 * Determines if the element can contain data used in sending receipts,
	 * e.g. fabrikfield returns true
	 *
	 * @deprecated - not used
	 *
	 * @return  bool
	 */

	public function isReceiptElement()
	{
		return true;
	}

	/**
	 * Generate captcha text
	 *
	 * @param   int  $characters  number of characters to generate
	 *
	 * @return  string captcha text
	 */

	protected function _generateCode($characters)
	{
		// List all possible characters, similar looking characters and vowels have been removed
		$possible = '23456789bcdfghjkmnpqrstvwxyz';
		$code = '';
		$i = 0;

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
	 * @param   int     $repeatCounter  group repeat counter
	 * @param   string  $tmpl           form template
	 *
	 * @return  string  label
	 */

	public function getLabel($repeatCounter, $tmpl = '')
	{
		$user = JFactory::getUser();
		$params = $this->getParams();

		if ($user->id != 0)
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
		$user = JFactory::getUser();
		$params = $this->getParams();

		if ($user->id != 0)
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
	 * @param   string  $view  View list/form @since 3.0.7
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
	 * @param   string  $location  To trigger plugin on
	 * @param   string  $event     To trigger plugin on
	 *
	 * @return  bool can use or not
	 */

	public function canUse($location = null, $event = null)
	{
		$user = JFactory::getUser();
		$params = $this->getParams();

		if ($user->id != 0)
		{
			if ($params->get('captcha-showloggedin', 0) == 0)
			{
				return false;
			}
		}

		return parent::canUse();
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$session = JFactory::getSession();
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$element = $this->getElement();
		$params = $this->getParams();
		$user = JFactory::getUser();
		$value = $this->getValue($data, $repeatCounter);

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
			$publickey = $params->get('recaptcha_publickey');

			// $$$tom added lang & theme options
			$theme = $params->get('recaptcha_theme', 'red');
			$lang = JString::strtolower($params->get('recaptcha_lang', 'en'));
			$error = null;

			if ($user->id != 0 && $params->get('captcha-showloggedin', 0) == false)
			{
				return '<input class="inputbox text" type="hidden" name="' . $name . '" id="' . $id . '" value="" />';
			}
			else
			{
				$browser = JBrowser::getInstance();
				$ssl = $browser->isSSLConnection();

				return fabrik_recaptcha_get_html($id, $publickey, $theme, $lang, $error, $ssl);
			}
		}
		elseif ($params->get('captcha-method') == 'playthru')
		{
			if ($user->id != 0 && $params->get('captcha-showloggedin', 0) == false)
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
		else
		{
			if (!function_exists('imagettfbbox'))
			{
				throw new RuntimeException(FText::_('PLG_FABRIK_ELEMENT_CAPTCHA_STANDARD_TTF_ERROR'));
			}

			$str = array();
			$size = $element->width;
			$fontsize = $params->get('captcha-font-size', 22);
			$angle = $params->get('captcha-angle', 0);
			$padding = $params->get('captcha-padding', 10);
			$characters = $params->get('captcha-chars', 6);
			$code = $this->_generateCode($characters);

			// $$$ hugh - code that generates image now in image.php
			$session->set('com_' . $package . '.element.captcha.security_code', $code);

			// ***** e-kinst

			// Additional plugin params with validation
			$noise_color = $params->get('captcha-noise-color', '0000FF');

			// '0000FF' again if we have param value but it's invalid
			$noise_color = $this->_getRGBcolor($noise_color, '0000FF');
			$text_color = $params->get('captcha-text-color', '0000FF');
			$text_color = $this->_getRGBcolor($text_color, '0000FF');
			$bg_color = $params->get('captcha-bg', 'FFFFFF');
			$bg_color = $this->_getRGBcolor($bg_color, 'FFFFFF');

			// Let's keep all params in relatively safe place not only captcha value
			// Felixkat - Add
			$session->set('com_' . $package . '.element.captcha.fontsize', $fontsize);
			$session->set('com_' . $package . '.element.captcha.angle', $angle);
			$session->set('com_' . $package . '.element.captcha.padding', $padding);
			$session->set('com_' . $package . '.element.captcha.noise_color', $noise_color);
			$session->set('com_' . $package . '.element.captcha.text_color', $text_color);
			$session->set('com_' . $package . '.element.captcha.bg_color', $bg_color);
			$session->set('com_' . $package . '.element.captcha.font', $this->font);

			// $$$ hugh - changed from static image path to using simple image.php script, to get round IE caching images

			/* e-kinst
			 *	It seems too dangerous to set all parameters here,
			 *	because everybody can enlarge image size and set noise color to
			 *	background color to OCR captcha values without problems
			*/
			$str[] = '<img src="' . COM_FABRIK_LIVESITE . 'plugins/fabrik_element/captcha/image.php?foo=' . rand() . '" alt="'
			. FText::_('security image') . '" />';

			$str[] = '<div class="captcha_input">';

			$type = $params->get('password') == '1' ? "password" : "text";

			if ($this->elementError != '')
			{
				$type .= ' elementErrorHighlight';
			}

			if ($element->hidden == '1')
			{
				$type = 'hidden';
			}

			$sizeInfo = ' size="' . $size . '"';
			$str[] = '<input class="inputbox ' . $type . '" type="' . $type . '" name="' . $name . '" id="' . $id . '" ' . $sizeInfo . ' value="" />';
			$str[] = '</div>';

			return implode("\n", $str);
		}
	}

	/**
	 * Internal element validation
	 *
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return bool
	 */

	public function validate($data, $repeatCounter = 0)
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$params = $this->getParams();
		$app = JFactory::getApplication();
		$input = $app->input;

		if (!$this->canUse())
		{
			return true;
		}

		if ($params->get('captcha-method') == 'recaptcha')
		{
			$privatekey = $params->get('recaptcha_privatekey');

			if ($input->get('recaptcha_response_field'))
			{
				$challenge = $input->get('recaptcha_challenge_field');
				$response = $input->get('recaptcha_response_field');
				$resp = recaptcha_check_answer($privatekey, $_SERVER["REMOTE_ADDR"], $challenge, $response);

				return ($resp->is_valid) ? true : false;
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
			$elName = $this->getFullName(true, false);
			$session = JFactory::getSession();

			if ($session->get('com_' . $package . '.element.captcha.security_code', null) != $data)
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
	 * @return  bool	default true
	 */

	public function mustValidate()
	{
		$params = $this->getParams();

		if (!$this->canUse() && !$this->canView())
		{
			return false;
		}

		return parent::mustValidate();
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */

	public function elementJavascript($repeatCounter)
	{
		$user = JFactory::getUser();

		if ($user->id == 0)
		{
			$id = $this->getHTMLId($repeatCounter);
			$opts = $this->getElementJSOptions($repeatCounter);

			return array('FbCaptcha', $id, $opts);
		}

		return array();
	}

	/**
	 * Used to format the data when shown in the form's email
	 *
	 * @param   mixed  $value          element's data
	 * @param   array  $data           form records data
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	formatted value
	 */

	public function getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		return "";
	}

	/**
	 * $$$ e-kinst Convert a hex colour to RGB
	 *
	 * @param   string  $hexColor  3- or 6-digits hex color with optional leading '#'
	 * @param   string  $default   default hex color if first param invalid
	 *
	 * @return  string 	as 'R+G+B' where R,G,B are decimal
	 */

	private function _getRGBcolor($hexColor, $default = 'FF0000')
	{
		$regex = '/^#?(([\da-f])([\da-f])([\da-f])|([\da-f]{2})([\da-f]{2})([\da-f]{2}))$/i';
		$rgb = array();

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

		return implode('+', $rgb);
	}
}
