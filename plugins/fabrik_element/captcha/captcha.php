<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.captcha
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once JPATH_SITE . '/plugins/fabrik_element/captcha/recaptcha1.10/recaptchalib.php';

/**
 * Plugin element to captcha
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.captcha
 */

class plgFabrik_ElementCaptcha extends plgFabrik_Element
{

	var $_font = 'monofont.ttf';

	/**
	 * can be overwritten in plugin class
	 * determines if the element can contain data used in sending receipts, e.g. field returns true
	 */

	function isReceiptElement()
	{
		return true;
	}

	protected function _generateCode($characters)
	{
		/* list all possible characters, similar looking characters and vowels have been removed */
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

	function getLabel($repeatCounter, $tmpl = '')
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

	function isHidden()
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
	 * check user can view the read only element & view in list view
	 * If user logged in return false
	 * $$$ rob 14/03/2012 always returns false now - cant see a need to show it in the details / list view
	 * @return bol can view or not
	 */

	function canView()
	{
		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see plgFabrik_Element::canUse()
	 */

	public function canUse(&$model = null, $location = null, $event = null)
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
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$session = JFactory::getSession();
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$element = $this->getElement();
		$params = $this->getParams();
		$user = JFactory::getUser();
		$value = $this->getValue($data, $repeatCounter);
		if (!$this->_editable)
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

			//$$$tom added lang & theme options
			$theme = $params->get('recaptcha_theme', 'red');
			$lang = JString::strtolower($params->get('recaptcha_lang', 'en'));
			$error = null;
			if ($user->id != 0 && $params->get('captcha-showloggedin', 0) == false)
			{
				return '<input class="inputbox text" type="hidden" name="' . $name . '" id="' . $id . '" value="" />';
			}
			else
			{
				return recaptcha_get_html($id, $publickey, $theme, $lang, $error);
			}
		}
		else
		{
			$str = array();
			$size = $element->width;
			//$height = $params->get('captcha-height', 40);
			//$width = $params->get('captcha-width', 40);
			$fontsize = $params->get('captcha-font-size', 22);
			$angle = $params->get('captcha-angle', 0);
			$padding = $params->get('captcha-padding', 10);
			$characters = $params->get('captcha-chars', 6);
			$code = $this->_generateCode($characters);

			// $$$ hugh - code that generates image now in image.php
			$session->set('com_fabrik.element.captach.security_code', $code);

			// ***** e-kinst
			//	additional plugin params with validation
			$noise_color = $params->get('captcha-noise-color', '0000FF');
			// '0000FF' again if we have param value but it's invalid
			$noise_color = $this->_getRGBcolor($noise_color, '0000FF');
			$text_color = $params->get('captcha-text-color', '0000FF');
			$text_color = $this->_getRGBcolor($text_color, '0000FF');
			$bg_color = $params->get('captcha-bg', 'FFFFFF');
			$bg_color = $this->_getRGBcolor($bg_color, 'FFFFFF');

			//	let's keep all params in relatively safe place not only captcha value
			// Felixkat - Add
			$session->set('com_fabrik.element.captach.fontsize', $fontsize);
			$session->set('com_fabrik.element.captach.angle', $angle);
			$session->set('com_fabrik.element.captach.padding', $padding);
			
			// Felixkat - Remove
			//$session->set('com_fabrik.element.captach.height', $height);
			//$session->set('com_fabrik.element.captach.width', $width);
			// Felixkat - End
			$session->set('com_fabrik.element.captach.noise_color', $noise_color);
			$session->set('com_fabrik.element.captach.text_color', $text_color);
			$session->set('com_fabrik.element.captach.bg_color', $bg_color);
			$session->set('com_fabrik.element.captach.font', $this->_font);
			// * /e-kinst

			// $$$ hugh - changed from static image path to using simple image.php script, to get round IE caching images

			//***** e-kinst
			//	It seems too dangerous to set all parameters here,
			//	because everybody can enlarge image size and set noise color to
			//	background color to OCR captcha values without problems
			$str[] = '<img src="' . COM_FABRIK_LIVESITE . 'plugins/fabrik_element/captcha/image.php?foo=' . rand() . '" alt="'
				. JText::_('security image') . '" />';
				// *  /e-kinst
			$str[] = '<br />';

			$type = ($params->get('password') == "1") ? "password" : "text";
			if (isset($this->_elementError) && $this->_elementError != '')
			{
				$type .= " elementErrorHighlight";
			}
			if ($element->hidden == '1')
			{
				$type = "hidden";
			}
			$sizeInfo = ' size="' . $size . '"';
			$str[] = '<input class="inputbox ' . $type . '" type="' . $type . '" name="' . $name . '" id="' . $id . '" ' . $sizeInfo . ' value="" />';
			return implode("\n", $str);
		}
	}

	/**
	 * can be overwritten in adddon class
	 *
	 * checks the posted form data against elements INTERNAL validataion rule - e.g. file upload size / type
	 * @param string elements data
	 * @param int repeat group counter
	 * @return bol true if passes / false if falise validation
	 */

	function validate($data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		if (!$this->canUse())
		{
			return true;
		}
		if ($params->get('captcha-method') == 'recaptcha')
		{
			$privatekey = $params->get('recaptcha_privatekey');
			if (JRequest::getVar('recaptcha_response_field'))
			{
				$resp = recaptcha_check_answer($privatekey, $_SERVER["REMOTE_ADDR"], JRequest::getVar('recaptcha_challenge_field'),
					JRequest::getVar('recaptcha_response_field'));
				return ($resp->is_valid) ? true : false;
			}

			return false;
		}
		else
		{

			$this->getParams();
			$elName = $this->getFullName(true, true, false);
			$session = JFactory::getSession();
			if ($session->get('com_fabrik.element.captach.security_code', null) != $data)
			{
				return false;
			}
			return true;
		}
	}

	/**
	 * @return string error message raised from failed validation
	 */

	function getValidationErr()
	{
		return JText::_('PLG_ELEMENT_CAPTCHA_FAILED');
	}

	function mustValidate()
	{
		$params = $this->getParams();
		if (!$this->canUse() && !$this->canView())
		{
			return false;
		}
		return parent::mustValidate();
	}

	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @param object element
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$user = JFactory::getUser();
		if ($user->id == 0)
		{
			$id = $this->getHTMLId($repeatCounter);
			$opts = $this->getElementJSOptions($repeatCounter);
			$opts = json_encode($opts);
			return "new FbCaptcha('$id', $opts)";
		}
	}

	/**
	 * used to format the data when shown in the form's email
	 * @param mixed element's data
	 * @param array form records data
	 * @param int repeat group counter
	 * @return string formatted value
	 */

	function getEmailValue($value, $data, $c)
	{
		return "";
	}

	/** $$$ e-kinst
	/* @param	string	3- or 6-digits hex color with optional leading '#'
	/* @param	string	default hex color if first param invalid
	/* @return	string 	as 'R+G+B' where R,G,B are decimal
	 */
	private function _getRGBcolor($hexColor, $default = 'FF0000')
	{
		$regex = '/^#?(([\da-f])([\da-f])([\da-f])|([\da-f]{2})([\da-f]{2})([\da-f]{2}))$/i';
		$rgb = array();
		if (!preg_match($regex, $hexColor, $rgb))
		{
			if (!preg_match($regex, $default, $rgb))
			{
				// in case where $default invalid also (call error)
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
?>
