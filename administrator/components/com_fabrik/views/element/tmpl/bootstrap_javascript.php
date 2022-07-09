<?php
/**
 * Admin Element Edit - Javascript Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<div class="tab-pane" id="tab-javascript">
	<fieldset>
		<legend><?php echo FText::_('COM_FABRIK_JAVASCRIPT'); ?></legend>
		<div id="javascriptActions" class="accordion"></div>
		<a class="btn btn-success" href="#" id="addJavascript">
			<i class="icon-plus"></i>
			<?php echo FText::_('COM_FABRIK_ADD'); ?>
		</a>
	</fieldset>
</div>