<?php
/*
* Send sms's
*
* @package     Joomla
* @subpackage  Fabrik.helpers
* @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
* @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/

namespace Fabrik\Helpers;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

/**
 * Custom code
 *
 * To use, copy this file to Custom.php and rename the class from CustomSample to Custom.
 *
 * Add your functions as 'public static' methods.
 *
 * Call them from anywhere you can run PHP code in Fabrik as \Fabrik\Helpers\Custom::doMyThing(),
 * or FabrikCustom::doMyThing().  The latter is a class alias, which may be deprecated in future versions.
 *
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @since       3.8
 */
class CustomSample
{
	private static $init = null;

	private static $config = null;

	private static $user = null;

	private static $app = null;

	private static $lang = null;

	private static $date = null;

	private static $session = null;

	private static $formModel = null;

	public static function __initStatic($config = array())
	{
		if (!isset(self::$init))
		{
			self::$config  = ArrayHelper::getValue($config, 'config', \JFactory::getConfig());
			self::$user    = ArrayHelper::getValue($config, 'user', \JFactory::getUser());
			self::$app     = ArrayHelper::getValue($config, 'app', \JFactory::getApplication());
			self::$lang    = ArrayHelper::getValue($config, 'lang', \JFactory::getLanguage());
			self::$date    = ArrayHelper::getValue($config, 'date', \JFactory::getDate());
			self::$session = ArrayHelper::getValue($config, 'session', \JFactory::getSession());
			self::$formModel = ArrayHelper::getValue($config, 'formModel', null);
			self::$init    = true;
		}
	}

	public static function doMyThing()
	{
		return true;
	}
}
