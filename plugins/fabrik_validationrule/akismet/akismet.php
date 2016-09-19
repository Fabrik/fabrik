<?php
/**
 * Akismet Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.akismet
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/validation_rule.php';

/**
 * Akismet Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.akismet
 * @since       3.0
 */
class PlgFabrik_ValidationruleAkismet extends PlgFabrik_Validationrule
{
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'akismet';

	/**
	 * Validate the elements data against the rule
	 *
	 * @param   string  $data           To check
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */
	public function validate($data, $repeatCounter)
	{
		$params = $this->getParams();

		if ($params->get('akismet-key') != '')
		{
			$username = $this->user->get('username') != '' ? $this->user->get('username') : $this->_randomSring();
			require_once JPATH_COMPONENT . '/plugins/validationrule/akismet/libs/akismet.class.php';
			$akismet_comment = array('author' => $username, 'email' => $this->user->get('email'), 'website' => JURI::base(), 'body' => $data);
			$akismet = new Akismet(JURI::base(), $params->get('akismet-key'), $akismet_comment);

			if ($akismet->errorsExist())
			{
				throw new RuntimeException("Couldn't connected to Akismet server!");
			}
			else
			{
				if ($akismet->isSpam())
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Create a random string
	 *
	 * @return string
	 */
	protected function _randomSring()
	{
		return preg_replace('/([ ])/e', 'chr(rand(97,122))', '     ');
	}
}
