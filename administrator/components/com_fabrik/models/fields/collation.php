<?php
/**
 * Renders a list of database default collations
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

/**
 * Renders a list of installed image libraries
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       3.0.7
 */

class JFormFieldCollation extends JFormFieldList
{
	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   object  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
	 * @param   mixed   $value    The form field value to validate.
	 * @param   string  $group    The field name group control value. This acts as as an array container for the field.
	 *                            For example if the field has name="foo" and the group value is set to "bar" then the
	 *                            full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   11.1
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);
		if ($this->value == '' && $return)
		{
			$db = JFactory::getDbo();
			$this->value = $db->getCollation();
		}
		return $return;
	}
	/**
	 * Get element options
	 *
	 * @return  array
	 */
	protected function getOptions()
	{
		$db = JFactory::getDbo();
		$db->setQuery('SHOW COLLATION WHERE ' . $db->quoteName('Compiled') . ' = ' . $db->quote('Yes'));
		$rows = $db->loadObjectList();
		sort($rows);
		require_once COM_FABRIK_FRONTEND . '/helpers/image.php';
		$opts = array();
		foreach ($rows as $row)
		{
			$opts[] = JHTML::_('select.option', $row->Collation);
		}
		return $opts;
	}
}
