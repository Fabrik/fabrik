<?php
/**
 * Admin List Edit:related data Tmpl
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