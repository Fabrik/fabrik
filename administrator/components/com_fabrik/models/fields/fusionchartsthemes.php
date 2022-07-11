<?php
/**
 * Renders a list of connections
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
JFormHelper::loadFieldClass('filelist');

/**
 * Renders a list of connections
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       3.0
 */
class JFormFieldFusionchartsthemes extends JFormFieldFileList
{
	/**
	 * Element name
	 *
	 * @var        string
	 */
	protected $name = 'Fusionchartsthemes';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions()
	{
		$params = $this->form->getData()->get('params');
		$opts = array();

		if ($params)
		{
			$fcLib = $params->fusionchart_library;

			if (!empty($fcLib))
			{

				$this->directory   = JPATH_ROOT . '/plugins/fabrik_visualization/fusionchart/libs/' . $fcLib . '/js/themes';
				$this->hideDefault = true;

				$opts = parent::getOptions();

				foreach ($opts as &$opt)
				{
					$matches = array();
					if (preg_match('/fusioncharts\.theme\.(\w+)\.js/', $opt->value, $matches))
					{
						$opt->value = $matches[1];
						$opt->text  = $matches[1];
					}
				}
			}
		}

		return $opts;
	}
}
