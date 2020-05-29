<?php
/**
 * Partial Document class
 *
 * @package     Joomla
 * @subpackage  Fabrik.Documents
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('JPATH_PLATFORM') or die;

JLog::add('JDocumentRendererMessage is deprecated, use JDocumentRendererHtmlMessage instead.', JLog::WARNING, 'deprecated');

/**
 * JDocument system message renderer
 *
 * @since       11.1
 * @deprecated  4.0  Use JDocumentRendererHtmlMessage instead
 */
class JDocumentRendererMessage extends JDocumentRendererHtmlMessage
{
}
