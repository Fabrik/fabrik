<?php
/**
 * Admin Element Edit:validations Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<?php echo JHtml::_('tabs.panel', FText::_('COM_FABRIK_VALIDATIONS'), 'settings');
?>
<fieldset class="adminform">
	<div id="plugins" class="pane-sliders"></div>
	<a href="#" id="addPlugin" class="addButton"><?php echo FText::_('COM_FABRIK_ADD'); ?></a>
</fieldset>