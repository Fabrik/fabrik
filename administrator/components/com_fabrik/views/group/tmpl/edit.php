<?php
/**
 * Admin Group Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');

$fbConfig = JComponentHelper::getParams('com_fabrik');
$srcs = FabrikHelperHTML::framework();
FabrikHelperHTML::script($srcs);
?>

<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
	<div class="width-60 fltlft">
		<fieldset class="adminform">
			<legend><?php echo JText::_('COM_FABRIK_DETAILS');?></legend>
			<ul class="adminformlist">

				<?php foreach ($this->form->getFieldset('details') as $field) :?>
				<li>
					<?php echo $field->label; ?><?php echo $field->input; ?>
				</li>
				<?php endforeach; ?>

				<?php foreach ($this->form->getFieldset('details2') as $field) :?>
				<li>
					<?php echo $field->label; ?><?php echo $field->input; ?>
				</li>
				<?php endforeach; ?>

				<?php foreach ($this->form->getFieldset('details3') as $field) :?>
				<li>
					<?php echo $field->label; ?><?php echo $field->input; ?>
				</li>
				<?php endforeach; ?>
			</ul>
			<div class="clr"> </div>

		</fieldset>
	</div>

	<div class="width-40 fltlft">

		<fieldset class="adminform">
			<legend><?php echo JText::_('COM_FABRIK_REPEAT');?></legend>
			<ul class="adminformlist">
				<?php foreach ($this->form->getFieldset('repeat') as $field) :?>
				<li>
					<?php echo $field->label; ?><?php echo $field->input; ?>
				</li>
				<?php endforeach; ?>
			</ul>
		</fieldset>

		<fieldset class="adminform">
			<legend><?php echo JText::_('COM_FABRIK_LAYOUT');?></legend>
			<ul class="adminformlist">
				<?php foreach ($this->form->getFieldset('layout') as $field) :?>
				<li>
					<?php echo $field->label; ?><?php echo $field->input; ?>
				</li>
				<?php endforeach; ?>
			</ul>
		</fieldset>

		<fieldset class="adminform">
			<legend><?php echo JText::_('COM_FABRIK_GROUP_MULTIPAGE');?></legend>
			<ul class="adminformlist">
				<?php foreach ($this->form->getFieldset('pagination') as $field) :?>
				<li>
					<?php echo $field->label; ?><?php echo $field->input; ?>
				</li>
				<?php endforeach; ?>
			</ul>
		</fieldset>


	</div>
	<div class="clr"></div>

	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>
