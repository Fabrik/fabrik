<?php
/**
 * Plugin element to render fields
 *
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/plugins/fabrik_element/fileupload/models/audio.php';
$render = new audioRender;
