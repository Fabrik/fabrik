<?php
/**
 * Admin Elements Confirm Delete Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="fabrik-form" class="form-validate">
	<table class="adminlist">
		<thead>
			<tr>
				<th style="width:2%"><input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" /></th>
				<th><?php echo JText::_('COM_FABRIK_ELEMENTS'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php for ($i = 0; $i < count($this->items); $i++) :
				$element = $this->items[$i];?>
			<tr>
				<td>
					<?php echo JHtml::_('grid.id', $i, $element->id, false, 'elementIds'); ?>
					<input type="hidden" name="cid[]" value="<?php echo $element->id?>" />
				</td>
				<td><?php echo $element->label; ?></td>
			</tr>
			<?php endfor?>
		</tbody>
	</table>
	<input type="hidden" name="task" value="" />
  	<?php echo JHTML::_('form.token');
	echo JHTML::_('behavior.keepalive'); ?>
</form>
