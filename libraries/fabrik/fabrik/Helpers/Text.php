<?php
/**
 * String helpers
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Helpers;

// No direct access
defined('_JEXEC') or die('Restricted access');
/**
 *
 * $$$ hugh JText::_() does funky stuff to strings with commas in them, like
 * truncating everything after the first comma, if what follows the first comma
 * is all "upper case".  But it tests for that using non MB safe code, so any non
 * ASCII strings (like Greek text) with a comma in them get truncated at the comma.
 * Corner case or what!  But we need to work round this behavior.
 *
 * So ... here's a wrapper for JText::_().
 */
class Text extends \JText
{
	/**
	 * Translates a string into the current language.
	 *
	 * Examples:
	 * <script>alert(Joomla.JText._('<?php echo Text::_("JDEFAULT", array("script"=>true));?>'));</script>
	 * will generate an alert message containing 'Default'
	 * <?php echo Text::_("JDEFAULT");?> it will generate a 'Default' string
	 *
	 * @param   string   $string                The string to translate.
	 * @param   mixed    $jsSafe                Boolean: Make the result javascript safe.
	 * @param   boolean  $interpretBackSlashes  To interpret backslashes (\\=\, \n=carriage return, \t=tabulation)
	 * @param   boolean  $script                To indicate that the string will be push in the javascript language store
	 *
	 * @return  string  The translated string or the key is $script is true
	 *
	 * @since   11.1
	 */
	public static function _($string, $jsSafe = false, $interpretBackSlashes = true, $script = false)
	{
		/**
		 * This function is now kind of redundant, as it uses to guard against some behavior of JText_() which no
		 * longer happens (as of 3.7).  But we'll keep it around as a wrapper in case we ever need to Do Fabrikm Stuff
		 * to translatable strings.
		 */
		return parent::_($string, $jsSafe, $interpretBackSlashes, $script);
	}
}
