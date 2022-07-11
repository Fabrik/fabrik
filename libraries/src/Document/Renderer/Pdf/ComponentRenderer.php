<?php
/**
 * PDF Document class
 *
 * @package     Joomla
 * @subpackage  Fabrik.Documents
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\CMS\Document\Renderer\Html;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Document\DocumentRenderer;

/**
 * HTML document renderer for the component output
 *
 * @since  3.5
 */
class ComponentRenderer extends DocumentRenderer
{
	/**
	 * Renders a component script and returns the results as a string
	 *
	 * @param   string  $component  The name of the component to render
	 * @param   array   $params     Associative array of values
	 * @param   string  $content    Content script
	 *
	 * @return  string  The output of the script
	 *
	 * @since   3.5
	 */
	public function render($component = null, $params = array(), $content = null)
	{
		return $content;
	}
}
