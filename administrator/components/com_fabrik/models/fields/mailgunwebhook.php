<?php
/**
 * Renders a Fabrik Help link
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0.9
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Renders a Fabrik Help link
 *
 * @package  Fabrik
 * @since    3.0.9
 */

class JFormFieldMailgunwebhook extends JFormField
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $name = 'Mailgunwebhook';

	/**
	 * Get the input - a read only link
	 *
	 * @return string
	 */

	public function getInput()
	{
		$formId = $this->form->model->getId();

		if (empty($formId))
		{
			$url = FText::_('Available once form saved');
		}
		else
		{
			$plugin = (string) $this->getAttribute('plugin', 'mailgun');
			$url = COM_FABRIK_LIVESITE . 'index.php?option=com_fabrik&c=plugin&task=plugin.pluginAjax';
			$url .= '&formid=' . $formId;
			$url .= '&g=form&plugin=' . $plugin;
			$url .= '&method=webhook';
			$url .= '&renderOrder=' . $this->form->repeatCounter;
		}

		return '<div>' . $url . '</div>';
	}
}
