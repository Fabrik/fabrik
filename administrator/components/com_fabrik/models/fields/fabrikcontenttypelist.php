<?php
/**
 * Renders a list of Fabrik content types
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Renders a list of Fabrik content types
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */
class JFormFieldFabrikContentTypeList extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 */
	protected $name = 'FabrikContentTypeList';

	/**
	 * Method to get the field options.
	 *
	 * @return  string    The field input markup.
	 */
	protected function getOptions()
	{
		$base    = JPATH_COMPONENT_ADMINISTRATOR . '/models/content_types';
		$files   = JFolder::files($base, '.xml');
		$options = array();

		foreach ($files as $file)
		{
			$xml = file_get_contents($base . '/' . $file);
			$doc = new DOMDocument();
			$doc->loadXML($xml);
			$xpath = new DOMXpath($doc);
			$name  = iterator_to_array($xpath->query('/contenttype/name'));

			if (!is_null($name) && count($name) > 0)
			{
				$options[] = JHTML::_('select.option', $file, $name[0]->nodeValue);
			}
		}

		return $options;
	}

	/**
	 * Method to get the field input markup for a generic list.
	 * Use the multiple attribute to enable multi-select.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		$str = '<div class="row-fluid">
		<div class="span5">' . parent::getInput() . '<div id="contentTypeListAclUi"></div></div><div class="span7">';
		$str .= '<legend>' . JText::_('COM_FABRIK_PREVIEW') . ': </legend>';
		$str .= '<div class="well" id="contentTypeListPreview"></div>';

		$str .= '</div>';
		$script = 'new FabrikContentTypeList(\'' . $this->id . '\');';
		$src    = array(
			'Fabrik' => 'media/com_fabrik/js/fabrik.js',
			'ContentTypeList' => 'administrator/components/com_fabrik/models/fields/fabrikcontenttypelist.js'
		);
		FabrikHelperHTML::script($src, $script);

		return $str;
	}
}
