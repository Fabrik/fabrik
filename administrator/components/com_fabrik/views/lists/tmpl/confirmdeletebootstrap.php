<?php
/**
 * Admin Lists Confirm Delete Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$app = JFactory::getApplication();
?>
<script type="text/javascript">
Joomla.submitform = function(task, form) {
	if (typeof(form) === 'undefined') {
		form = document.getElementById('adminForm');
	}
	if (typeof(task) !== 'undefined') {
	form.task.value = task;
	}
	// Submit the form.
	if (typeof form.onsubmit == 'function') {
		form.onsubmit();
	}
	if (typeof form.fireEvent == "function") {
		form.fireEvent('submit');
	}
	form.submit();
};
</script>
<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">

	<?php
	$cid = $app->input->get('cid', array(), 'array');
	foreach ($cid as $id) : ?>
		<input type="hidden" name="cid[]" value="<?php echo $id ;?>" />
	<?php endforeach; ?>

	<fieldset class="form-horizontal">
		<legend><?php echo FText::_('COM_FABRIK_DELETE_FROM');?></legend>
		<ul class="adminformlist">
		<?php for ($i = 0; $i < count($this->items); $i++) :?>
  			<li>
  				<?php echo $this->items[$i]?>
  			</li>
		<?php endfor; ?>
		</ul>

		<?php foreach ($this->form->getFieldset('details') as $field) :?>
			<div class="control-group">
			<?php if (!$field->hidden) :?>
				<div class="control-label">
					<?php echo $field->label; ?>
				</div>
			<?php endif; ?>
				<div class="controls">
					<?php echo $field->input; ?>
				</div>
			</div>
			<?php endforeach; ?>
	</fieldset>
	<input type="hidden" name="task" value="" />
  	<?php echo JHTML::_('form.token');
	echo JHTML::_('behavior.keepalive'); ?>
</form>