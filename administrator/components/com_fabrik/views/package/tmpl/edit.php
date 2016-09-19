<?php
/**
 * Admin Package Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
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
	// Ensure that the multiselect lists options are selected
	var multis = ['blockslist', 'blocksform'];
	for (var i = 0; i < multis.length; i++) {
		document.id(multis[i]).getElements('option').each(function (e) {
			e.selected = true;
		});
	}
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
	<div class="width-100 fltlft">
		<?php foreach ($this->form->getFieldset('json') as $field) :
			echo $field->input;
		endforeach; ?>
		<fieldset class="adminform">
			<legend><?php echo FText::_('COM_FABRIK_DETAILS');?></legend>
			<ul class="adminformlist twocols">
				<?php foreach ($this->form->getFieldset('details') as $field): ?>
				<li>
					<?php echo $field->label . $field->input; ?>
				</li>
				<?php endforeach; ?>

				<?php foreach ($this->form->getFieldset('publishing') as $field) :?>
				<li>
					<?php echo $field->label; ?>
					<?php echo $field->input; ?>
				</li>
				<?php endforeach; ?>

				<?php foreach ($this->form->getFieldset('more') as $field): ?>
				<li>
					<?php echo $field->label . $field->input; ?>
				</li>
				<?php endforeach; ?>
			</ul>
			<div class="clr"> </div>

		</fieldset>

	</div>
	<div class="clr"></div>
<!--<a id="undo" href="#">Undo</a> |
<a id="redo" href="#">Redo</a> <br />
-->
	<fieldset class="adminform">
	<legend><?php echo FText::_('COM_FABRIK_LISTS')?></legend>
		<ul class="adminformlist">
			<li>
				<?php echo JHtml::_('select.genericlist', $this->listOpts, 'list-pick[]', 'multiple="true" size="10"');?>
			</li>
			<li>
				<button id="add-list"><?php echo FText::_('COM_FABRIK_ADD')?> &gt;</button>
				<button id="remove-list"><?php echo FText::_('COM_FABRIK_REMOVE')?> &lt;</button>
			</li>
			<li>
				<?php echo JHtml::_('select.genericlist', $this->selListOpts, 'blocks[list][]', 'multiple="true" size="10"');?>
			</li>
		</ul>
		<div class="clr"></div>
	</fieldset>

	<div class="clr"></div>

	<fieldset class="adminform">
		<legend><?php echo FText::_('COM_FABRIK_FORMS')?></legend>
		<ul class="adminformlist">
			<li>
				<?php echo JHtml::_('select.genericlist', $this->formOpts, 'form-pick', 'multiple="true" size="10"')?>
			</li>
			<li>
				<button id="add-form"><?php echo FText::_('COM_FABRIK_ADD')?> &gt;</button>
				<button id="remove-form"><?php echo FText::_('COM_FABRIK_REMOVE')?> &lt;</button>
			</li>
			<li>
				<?php echo JHtml::_('select.genericlist', $this->selFormOpts, 'blocks[form][]', 'multiple="true" size="10"')?>
			</li>
		</ul>
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