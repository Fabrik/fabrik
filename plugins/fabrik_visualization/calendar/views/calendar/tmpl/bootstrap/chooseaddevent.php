<?php
/**
 * Calendar Viz: Default Choose Add Event Tmpl
 *
 * @package			Joomla.Plugin
 * @subpackage	Fabrik.visualization.calendar
 * @copyright		Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license			GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/

// No direct access
defined('_JEXEC') or die('Restricted access');

echo $this->plugin->_eventTypeDd;


?>
<iframe style="border:0;width:100%;height:100%" id="fabrik_event_form"></iframe>