<?php
/**
 * Repeat group add button for table format
 */

defined('JPATH_BASE') or die;

$d = $displayData;
?>
<a class="addGroup" href="#">
	<?php echo  FabrikHelperHTML::icon('icon-plus fabrikTip tip-small', '', 'data-role="fabrik_duplicate_group" opts=\'{"trigger": "hover"}\' title="' . FText::_('COM_FABRIK_ADD_GROUP'). '"');?>
</a>