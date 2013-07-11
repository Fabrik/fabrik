<?php
/**
 * Email Already Registered Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.emailexists
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/validation_rule.php';

/**
 * Email Already Registered Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.emailexists
 * @since       3.0
 */

class PlgFabrik_ValidationruleEmailExists extends PlgFabrik_Validationrule
{

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'emailexists';

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
		if (empty($data))
		{
			return false;
		}
		if (is_array($data))
		{
			$data = $data[0];
		}
		$params = $this->getParams();

		// As ornot is a radio button it gets json encoded/decoded as an object
		$ornot = (object) $params->get('emailexists_or_not');
		$ornot = isset($ornot->$pluginc) ? $ornot->$pluginc : 'fail_if_exists';

		$user_field = (array) $params->get('emailexists_user_field', array());
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

		jimport('joomla.user.helper');
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('id')->from('#__users')->where('email = ' . $db->quote($data));
		$db->setQuery($query);
		$result = $db->loadResult();
		$user = JFactory::getUser();
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
				return ($ornot == 'fail_if_exists') ? true : false;
			}
			else
			{
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
					if ($result == $user->get('id')) // The connected user is editing his own data
					{
						return ($ornot == 'fail_if_exists') ? true : false;
					}
					return false;
				}
			}
		}
		return false;
	}

	/**
	 * Gets the hover/alt text that appears over the validation rule icon in the form
	 *
	 * @param   object  $elementModel  Element model
	 * @param   int     $pluginc       Plugin render order
	 *
	 * @return	string	label
	 */

	protected function getLabel($elementModel, $pluginc)
	{
		$params = $this->getParams();

		// As ornot is a radio button it gets json encoded/decoded as an object
		$ornot = (object) $params->get('emailexists_or_not');
		$pluginc = (int) $pluginc;
		$cond = '';
		foreach ($ornot as $k => $v)
		{
			if ($k == $pluginc)
			{
				$cond = $v;
			}
		}
		if ($cond == 'fail_if_not_exists')
		{
			return JText::_('PLG_VALIDATIONRULE_EMAILEXISTS_LABEL_NOT');
		}
		else
		{
			return parent::getLabel($elementModel, $pluginc);
		}
	}
}