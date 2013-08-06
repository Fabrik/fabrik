<?php
/**
 * Plugin element to render pdf files
 *
 * @package fabrikar
 * @author Hugh Messenger
 * @copyright (C) hugh Messenger
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/plugins/fabrik_element/fileupload/models/pdf.php';
$render = new pdfRender;
