<?php
/**
 * Admin List Edit:data Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

echo JHtml::_('tabs.panel', FText::_('COM_FABRIK_GROUP_LABEL_DATA'), 'list-data-panel');
echo JHtml::_('sliders.start', 'table-sliders-data-'.(int) $this->item->id, array('useCookie'=>0));
echo JHtml::_('sliders.panel', FText::_('COM_FABRIK_DATA'), 'data-details'); ?>
<fieldset class="adminform">
	<legend><?php echo FText::_('COM_FABRIK_DATA'); ?></legend>
	<ul class="adminformlist">
		<li>
			<?php echo $this->form->getLabel('connection_id'). $this->form->getInput('connection_id')?>
		</li>
		<?php if ($this->item->id == 0) { ?>
			<li>
				<?php echo $this->form->getLabel('_database_name'). $this->form->getInput('_database_name')?>
			</li>
			<li><?php echo $this->form->getLabel('or');?></li>
		<?php }?>
		<li>
			<?php echo $this->form->getLabel('db_table_name'). $this->form->getInput('db_table_name')?>
		</li>
		<?php if ($this->item->id != 0) { ?>
			<li>
				<?php echo $this->form->getLabel('db_primary_key'). $this->form->getInput('db_primary_key')?>
			</li>
			<li>
				<?php echo $this->form->getLabel('auto_inc'). $this->form->getInput('auto_inc')?>
			</li>
		<?php }?>
		<li>
			<label for="order_by"><?php echo FText::_('COM_FABRIK_FIELD_ORDER_BY_LABEL'); ?></label>
			<div id="orderByTd" style="float:left;margin:4px 0 0 2px">
				<?php for ($o = 0; $o < count($this->order_by); $o++) { ?>
					<div class="orderby_container" style="margin-bottom:3px">
					<?php
						echo FArrayHelper::getValue($this->order_by, $o, $this->order_by[0]);
						if ((int) $this->item->id !== 0) {
							echo FArrayHelper::getValue($this->order_dir, $o)?>
						<a class="addOrder" href="#"><img src="components/com_fabrik/images/add.png" label="<?php echo FText::_('COM_FABRIK_ADD')?>" alt="<?php echo FText::_('COM_FABRIK_ADD')?>" /></a>
						<a class="deleteOrder" href="#"><img src="components/com_fabrik/images/remove.png" label="<?php echo FText::_('REMOVE')?>" alt="<?php echo FText::_('REMOVE')?>" /></a>
						<?php }?>
					</div>
				<?php }?>
			</div>
		</li>
	</ul>
</fieldset>

<fieldset class="adminform">
	<legend><?php echo FText::_('COM_FABRIK_GROUP_BY'); ?></legend>
	<ul class="adminformlist">
		<?php foreach ($this->form->getFieldset('grouping') as $field):
			if (!$field->hidden): ?>
				<li><?php echo $field->label; ?></li>
			<?php endif; ?>
			<li id="li_<?php echo str_replace(array('[', ']', 'jformparams'), '', $field->name) ?>"><?php echo $field->input; ?></li>
		<?php endforeach;
		foreach ($this->form->getFieldset('grouping2') as $field):
			if (!$field->hidden): ?>
				<li><?php echo $field->label; ?></li>
			<?php endif; ?>
			<li id="li_<?php echo str_replace(array('[', ']', 'jformparams'), '', $field->name) ?>"><?php echo $field->input; ?></li>
		<?php endforeach; ?>
	</ul>
</fieldset>

<?php echo JHtml::_('sliders.panel', FText::_('COM_FABRIK_PREFILTER'), 'data-prefilters'); ?>
<fieldset>
		<legend>
			<?php echo JHTML::_('tooltip', FText::_('COM_FABRIK_PREFILTER_DESC'), FText::_('COM_FABRIK_PREFILTER'), 'tooltip.png', FText::_('COM_FABRIK_PREFILTER')); ?>
		</legend>
		<a class="addButton" href="#" onclick="oAdminFilters.addFilterOption(); return false;">
			<?php echo FText::_('COM_FABRIK_ADD'); ?>
		</a>
		<ul class="adminformlist">
			<?php foreach ($this->form->getFieldset('prefilter') as $field): ?>
				<?php if (!$field->hidden): ?>
					<li><?php echo $field->label; ?></li>
				<?php endif; ?>
				<li><?php echo $field->input; ?></li>
			<?php endforeach; ?>
		</ul>
	<table class="adminform" width="100%">
		<tbody id="filterContainer">
		</tbody>
	</table>
</fieldset>

<?php echo JHtml::_('sliders.panel', FText::_('COM_FABRIK_JOINS'), 'joins-details'); ?>
<fieldset>
	<legend>
		<?php echo JHTML::_('tooltip', FText::_('COM_FABRIK_JOINS_DESC'), FText::_('COM_FABRIK_JOINS'), 'tooltip.png', FText::_('COM_FABRIK_JOINS'));?>
	</legend>
	<?php if ($this->item->id != 0) { ?>
		<a href="#" id="addAJoin" class="addButton">
			<?php echo FText::_('COM_FABRIK_ADD'); ?>
		</a>
		<div id="joindtd"></div>
		<ul class="adminformlist">
			<?php foreach ($this->form->getFieldset('joins') as $field): ?>
				<?php if (!$field->hidden): ?>
					<li><?php echo $field->label; ?></li>
				<?php endif; ?>
				<li><?php echo $field->input; ?></li>
			<?php endforeach; ?>
		</ul>
	<?php
	} else {
		echo FText::_('COM_FABRIK_AVAILABLE_ONCE_SAVED');
	} ?>
</fieldset>

<?php echo JHtml::_('sliders.panel', FText::_('COM_FABRIK_RELATED_DATA'), 'related-data-details'); ?>
<?php echo $this->loadTemplate('related_data');?>
<?php echo JHtml::_('sliders.end'); ?>
