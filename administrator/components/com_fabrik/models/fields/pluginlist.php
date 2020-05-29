<?php
/**
 * Plugin List Field class for Fabrik.
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Plugin List Field class for Fabrik.
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */
class JFormFieldPluginList extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.6
	 */
	protected $type = 'PluginList';

	/**
	 * Cache plugin list options
	 *
	 * @var array
	 */
	private static $cache = array();

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions()
	{
		$group    = (string) $this->element['plugin'];
		$key      = $this->element['key'];
		$key      = ($key == 'visualization.plugin') ? "CONCAT('visualization.',element) " : 'element';
		$cacheKey = $group . '.' . $key;

		if (array_key_exists($cacheKey, self::$cache))
		{
			return self::$cache[$cacheKey];
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select($key . ' AS value, element AS text');
		$query->from('#__extensions AS p');
		$query->where($db->qn('type') . ' = ' . $db->q('plugin'));
		$query->where($db->qn('enabled') . ' = 1 AND state != -1');
		$query->where($db->qn('folder') . ' = ' . $db->q($group));
		$query->order('text');

		// Get the options.
		$db->setQuery($query);
		$options = $db->loadObjectList();
		array_unshift($options, JHtml::_('select.option', '', FText::_('COM_FABRIK_PLEASE_SELECT')));
		self::$cache[$cacheKey] = $options;

		return $options;
	}
}
