<?php
/**
 * Admin List Edit:related data Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die;
?>
<fieldset>
	<legend>
		<?php echo JHTML::_('tooltip', JText::_('COM_FABRIK_RELATED_DATA_DESC', false), JText::_('COM_FABRIK_RELATED_DATA'), 'tooltip.png', JText::_('COM_FABRIK_RELATED_DATA'));?>
	</legend>
	<ul class="adminformlist">
	<?php foreach ($this->form->getFieldset('factedlinks2') as $field):
		?>
		<li><?php echo $field->label; ?>
		<?php echo $field->input; ?>
		</li>
	<?php
	endforeach;
	?>
	</ul>
	<div style="clear:both"></div>
	<?php foreach ($this->form->getFieldset('factedlinks') as $field): ?>
		<?php echo $field->input; ?>
	<?php endforeach; ?>

</fieldset>