<?php
/**
 * User Exists Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.userexists
 * @copyright   Copyright (C) 2005 Pollen 8 Design Ltd. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/validation_rule.php';

/**
 * User Exists Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.userexists
 * @since       3.0
 */

class PlgFabrik_ValidationruleUserExists extends PlgFabrik_Validationrule
{

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'userexists';

	/**
	 * Validate the elements data against the rule
	 *
	 * @param   string  $data           To check
	 * @param   object  &$elementModel  Element Model
	 * @param   int     $pluginc        Plugin sequence ref
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */

	public function validate($data, &$elementModel, $pluginc, $repeatCounter, $allData)
	{
		$params = $this->getParams();
		$pluginc = trim((string) $pluginc);

		// As ornot is a radio button it gets json encoded/decoded as an object
		$ornot = (object) $params->get('userexists_or_not');
		$ornot = isset($ornot->$pluginc) ? $ornot->$pluginc : 'fail_if_exists';
		$user = JFactory::getUser();
		jimport('joomla.user.helper');
		$result = JUserHelper::getUserId($data);
		if ($user->get('guest'))
		{
			if (!$result)
			{
				if ($ornot == 'fail_if_exists')
				{
					return true;
				}
			}
			else
			{
				if ($ornot == 'fail_if_not_exists')
				{
					return true;
				}
			}
			return false;
		}
		else
		{
			if (!$result)
			{
				if ($ornot == 'fail_if_exists')
				{
					return true;
				}
			}
			else
			{
				$user_field = (array) $params->get('userexists_user_field', array());
				$user_field = $user_field[$pluginc];
				$user_id = 0;
				if ((int) $user_field !== 0)
				{
					$user_elementModel = FabrikWorker::getPluginManager()->getElementPlugin($user_field);
					$user_fullName = $user_elementModel->getFullName(true, false);
					$user_field = $user_elementModel->getFullName(false, false);
				}
				if (!empty($user_field))
				{
					// $$$ the array thing needs fixing, for now just grab 0
					$formdata = $elementModel->getForm()->formData;
					$user_id = JArrayHelper::getValue($formdata, $user_fullName . '_raw', JArrayHelper::getValue($formdata, $user_fullName, ''));
					if (is_array($user_id))
					{
						$user_id = JArrayHelper::getValue($user_id, 0, '');
					}
				}
				if ($user_id != 0)
				{
					if ($result == $user_id)
					{
						return ($ornot == 'fail_if_exists') ? true : false;
					}
					return false;
				}
				else
				{
					// The connected user is editing his own data
					if ($result == $user->get('id'))
					{
						return ($ornot == 'fail_if_exists') ? true : false;
					}
					return false;
				}
			}
			return false;
		}
	}
}
