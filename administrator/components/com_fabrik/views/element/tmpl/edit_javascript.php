<?php
/**
 * Admin Element Edit:javascript Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

echo JHtml::_('tabs.panel', FText::_('COM_FABRIK_JAVASCRIPT'), 'settings');
?>
<div id="javascriptActions" class="accordion"></div>
<a class="addButton" href="#" id="addJavascript"><?php echo FText::_('COM_FABRIK_ADD'); ?></a>
