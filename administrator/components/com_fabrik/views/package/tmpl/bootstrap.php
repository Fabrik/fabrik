<?php
/**
 * Admin Package Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
FabrikHelperHTML::formvalidation();
JHtml::_('behavior.keepalive');
JHTML::stylesheet('media/com_fabrik/css/package.css');
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
	if (task == 'package.cancel' || document.formvalidator.isValid(document.id('adminForm'))) {
		submitform(task);
	}
	else {
		alert('<?php echo $this->escape(FText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
	}
}

submitform = function(task){
	var json = JSON.encode(PackageCanvas.prepareSave());
	document.id('jform_params_canvas').value = json;
	Joomla.submitform(task, $('adminForm'));
}
</script>
<div id="icons-container"></div>

<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
		<?php foreach ($this->form->getFieldset('json') as $field) :
			echo $field->input;
		endforeach; ?>
		<fieldset class="form-horizontal">
			<legend><?php echo FText::_('COM_FABRIK_DETAILS');?></legend>
				<?php foreach ($this->form->getFieldset('details') as $field): ?>
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

				<?php foreach ($this->form->getFieldset('publishing') as $field) :?>
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

			<div class="clr"> </div>

		</fieldset>

	<div class="clr"></div>
<!--<a id="undo" href="#">Undo</a> |
<a id="redo" href="#">Redo</a> <br />
-->
	<fieldset class="form-horizontal">
	<legend><?php echo FText::_('COM_FABRIK_LISTS')?></legend>
		<div class="control-group">
		<?php if (!$field->hidden) :?>
			<div class="control-label">
				<?php echo FText::_('COM_FABRIK_ADD')?>
			</div>
		<?php endif; ?>
			<div class="controls">
				<?php echo JHtml::_('select.genericlist', $this->listOpts, 'list-pick[]', 'multiple="true" size="10"');?>
				<button class="btn" id="add-list"><?php echo FText::_('COM_FABRIK_ADD')?> &gt;</button>
				<button class="btn" id="remove-list"><?php echo FText::_('COM_FABRIK_REMOVE')?> &lt;</button>
				<?php echo JHtml::_('select.genericlist', $this->selListOpts, 'blocks[list][]', 'multiple="true" size="10"');?>
			</div>
		</div>
	</fieldset>

	<div class="clr"></div>

	<fieldset class="form-horizontal">
		<legend><?php echo FText::_('COM_FABRIK_FORMS')?></legend>

		<div class="control-group">
		<?php if (!$field->hidden) :?>
			<div class="control-label">
				<?php echo FText::_('COM_FABRIK_ADD')?>
			</div>
		<?php endif; ?>
			<div class="controls">
				<?php echo JHtml::_('select.genericlist', $this->formOpts, 'form-pick', 'multiple="true" size="10"')?>
				<button class="btn" id="add-form"><?php echo FText::_('COM_FABRIK_ADD')?> &gt;</button>
				<button class="btn" id="remove-form"><?php echo FText::_('COM_FABRIK_REMOVE')?> &lt;</button>
				<?php echo JHtml::_('select.genericlist', $this->selFormOpts, 'blocks[form][]', 'multiple="true" size="10"')?>
			</div>
		</div>

	</fieldset>

<!--  <div class="adminform" style="margin:10px;background-color:#999;">
<ul id="packagemenu">

</ul>
<div id="packagepages" style="margin:10px;">

</div>
</div> -->
	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>