<?php
/**
 * Fabrik Admin QuickIcon
 *
 * @package     Joomla.Administrator
 * @subpackage  mod_fabrik_quickicon
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Fabrik quick icons
 *
 * @package     Joomla.Administrator
 * @subpackage  mod_fabrik_quickicon
 * @since       3.0.8
 */
abstract class ModFabrik_QuickIconHelper
{
	/**
	 * Stack to hold buttons
	 *
	 * @since	1.6
	 */
	protected static $buttons = array();

	/**
	 * Get selected lists to add to dashboard
	 * @return mixed
	 */
	public static function listIcons()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id, label, params')->from('#__fabrik_lists')
			->where('params LIKE \'%"dashboard":"1"%\'');
		$db->setQuery($query);
		$lists = $db->loadObjectList();

		foreach ($lists as $list)
		{
			$params = new Joomla\Registry\Registry($list->params);
			$list->icon = $params->get('dashboard_icon', 'icon-list');
		}

		return $lists;
	}

	/**
	 * Helper method to return button list.
	 *
	 * This method returns the array by reference so it can be
	 * used to add custom buttons or remove default ones.
	 *
	 * @param   JRegistry  $params  The module parameters.
	 *
	 * @return	array	An array of buttons
	 */
	public static function &getButtons($params)
	{
		$key = (string) $params;

		if (!isset(self::$buttons[$key]))
		{
			$context = $params->get('context', 'mod_fabrik_quickicon');

			if ($context == 'mod_fabrik_quickicon')
			{
				// Load mod_quickicon language file in case this method is called before rendering the module
			JFactory::getLanguage()->load('mod_fabrik_quickicon');

				self::$buttons[$key] = array(

					array(
						'link' => JRoute::_('index.php?option=com_fabrik&view=lists'),
						'image' => '/components/com_fabrik/images/header/fabrik-list.png',
						'text' => JText::_('MOD_FABRIK_QUICKICON_LISTS'),
						'access' => array('core.manage', 'com_fabrik')
					),
					array(
						'link' => JRoute::_('index.php?option=com_fabrik&view=forms'),
						'image' => '/components/com_fabrik/images/header/fabrik-form.png',
						'text' => JText::_('MOD_FABRIK_QUICKICON_FORMS'),
						'access' => array('core.manage', 'com_fabrik')
					),
					array(
							'link' => JRoute::_('index.php?option=com_fabrik&view=groups'),
							'image' => '/components/com_fabrik/images/header/fabrik-group.png',
							'text' => JText::_('MOD_FABRIK_QUICKICON_GROUPS'),
							'access' => array('core.manage', 'com_fabrik')
					),
					array(
							'link' => JRoute::_('index.php?option=com_fabrik&view=elements'),
							'image' => '/components/com_fabrik/images/header/fabrik-element.png',
							'text' => JText::_('MOD_FABRIK_QUICKICON_ELEMENTS'),
							'access' => array('core.manage', 'com_fabrik')
					),
					array(
							'link' => JRoute::_('index.php?option=com_fabrik&view=visualizations'),
							'image' => '/components/com_fabrik/images/header/fabrik-visualization.png',
							'text' => JText::_('MOD_FABRIK_QUICKICON_VISUALIZATIONS'),
							'access' => array('core.manage', 'com_fabrik')
					),
					array(
							'link' => JRoute::_('index.php?option=com_fabrik&view=packages'),
							'image' => '/components/com_fabrik/images/header/fabrik-package.png',
							'text' => JText::_('MOD_FABRIK_QUICKICON_PACKAGES'),
							'access' => array('core.manage', 'com_fabrik')
					),
					array(
							'link' => JRoute::_('index.php?option=com_fabrik&view=connections'),
							'image' => '/components/com_fabrik/images/header/fabrik-connection.png',
							'text' => JText::_('MOD_FABRIK_QUICKICON_CONNECTIONS'),
							'access' => array('core.manage', 'com_fabrik')
					),
					array(
							'link' => JRoute::_('index.php?option=com_fabrik&view=crons'),
							'image' => '/components/com_fabrik/images/header/fabrik-schedule.png',
							'text' => JText::_('MOD_FABRIK_QUICKICON_SCHEDULED_TASKS'),
							'access' => array('core.manage', 'com_fabrik')
					)
				);
			}
			else
			{
				self::$buttons[$key] = array();
			}
		}

		$html = array();

		foreach (self::$buttons[$key] as &$button)
		{
			$btn = self::button($button);

			if ($btn !== false)
			{
				$html[] = $btn;
			}
		}

		return self::$buttons[$key];
	}

	/**
	 * Make buttons html
	 *
	 * @param   array  $button  Buttons
	 *
	 * @return string
	 */
	public static function button($button)
	{
		$user = JFactory::getUser();

		if (!empty($button['access']))
		{
			if (is_bool($button['access']))
			{
				if ($button['access'] == false)
				{
					return false;
				}
			}
			else
			{
				// Take each pair of permission, context values.
				for ($i = 0, $n = count($button['access']); $i < $n; $i += 2)
				{
					if (!$user->authorise($button['access'][$i], $button['access'][$i + 1]))
					{
						return false;
					}
				}
			}
		}

		return $button;
	}

	/**
	 * Get the alternate title for the module
	 *
	 * @param   JRegistry  $params  The module parameters.
	 * @param   object     $module  The module.
	 *
	 * @return	string	The alternate title for the module.
	 */
	public static function getTitle($params, $module)
	{
		$key = $params->get('context', 'mod_fabrik_quickicon') . '_title';

		if (JFactory::getLanguage()->hasKey($key))
		{
			return JText::_($key);
		}
		else
		{
			return $module->title;
		}
	}
}
