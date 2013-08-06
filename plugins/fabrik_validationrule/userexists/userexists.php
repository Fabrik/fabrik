<?php
/**
 * User Exists Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.userexists
 * @copyright   Copyright (C) 2005 Pollen 8 Design Ltd. All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

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
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */

	public function validate($data, $repeatCounter)
	{
		$params = $this->getParams();
		$elementModel = $this->elementModel;

		// As ornot is a radio button it gets json encoded/decoded as an object
		$ornot = $params->get('userexists_or_not', 'fail_if_exists');
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
				$user_field = $params->get('userexists_user_field');
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
