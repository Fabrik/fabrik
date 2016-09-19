<?php
/**
 * Admin Form Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<div class="tab-pane" id="tab-process">

    <fieldset class="form-horizontal">
		<div class="control-group">
			<div class="control-label">
				<?php echo $this->form->getLabel('record_in_database'); ?>
			</div>
			<div class="controls">
				<?php echo $this->form->getInput('record_in_database'); ?>
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
				<?php echo $this->form->getLabel('db_table_name'); ?>
			</div>
			<div class="controls">
				<?php if ($this->item->record_in_database != '1') {?>
			<?php  echo $this->form->getInput('db_table_name'); ?>
		<?php } else { ?>
			<input class="readonly" readonly="readonly" id="database_name" name="_database_name" value="<?php echo $this->item->db_table_name;?>"  />
			<input type="hidden" id="_connection_id" name="_connection_id" value="<?php echo $this->item->connection_id;?>"  />
		<?php }?>
			</div>
		</div>


		<?php foreach ($this->form->getFieldset('processing') as $this->field) :
			echo $this->loadTemplate('control_group');
		endforeach;
		?>
	</fieldset>

    <fieldset class="form-horizontal">
		<legend><?php echo FText::_('COM_FABRIK_NOTES');?></legend>
		<?php foreach ($this->form->getFieldset('notes') as $this->field) :
			echo $this->loadTemplate('control_group');
		endforeach;
		?>
	</fieldset>
</div>
