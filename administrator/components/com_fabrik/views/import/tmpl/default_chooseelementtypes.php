<?php
/**
 * Admin Import Choose Element types Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\String\StringHelper;

$app = JFactory::getApplication();
$input = $app->input;
$jform = $input->get('jform', array(), 'array');
?>
<form action="index.php" method="post" name="adminForm" id="adminForm">
	<?php if (!empty($this->newHeadings)) :
		if ((int) $this->table->id !== 0) :
		echo "<H3>" . FText::_('COM_FABRIK_IMPORT_NEW_HEADINGS_FOUND') . "</h3>";
		echo JText::sprintf('COM_FABRIK_IMPORT_NEW_HEADINGS_FOUND_DESC', $this->table->label, $this->table->label);
	endif;?>

		<table class="adminlist table table-striped">
			<thead>
			<tr>
				<th class="title"><?php echo FText::_('COM_FABRIK_IMPORT_CREATE_ELEMENT');?></th>
				<th class="title"><?php echo FText::_('COM_FABRIK_IMPORT_LABEL');?></th>
				<th class="title"><?php echo FText::_('COM_FABRIK_IMPORT_ELEMENT_TYPE');?></th>
				<?php if ($this->selectPKField) :?>
					<th class="title"><?php echo FText::_('COM_FABRIK_PRIMARY_KEY');?></th>
				<?php endif; ?>
				<th><?php echo FText::_('COM_FABRIK_IMPORT_SAMPLE_DATA');?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			$chx = (int) $this->table->id === 0 ? '' : 'checked="checked"';
			$chx2 = (int) $this->table->id === 0 ? 'checked="checked"' : '';
			for ($i = 0; $i < count($this->newHeadings); $i ++) :
				$heading = trim($this->newHeadings[$i]);
				$sample = '';
				foreach ($this->headings as $sKey => $sVal) :
					if(StringHelper::strtolower($heading) == StringHelper::strtolower($sVal)) :
						$sample = $this->sample[$sKey];
					endif;
				endforeach;
				?>
			<tr>
				<td>
				<?php if ($i == 0 && !$this->selectPKField) :?>
					<input type="hidden" name="createElements[<?php echo $heading;?>]" value="1" /><?php echo FText::_('JYES');?>
				<?php else : ?>
					<label>
						<input type="radio" name="createElements[<?php echo $heading;?>]" value="0" <?php echo $chx?>>
						<?php echo FText::_('JNO');?>
					</label>
					<label>
						<input type="radio" name="createElements[<?php echo $heading;?>]" value="1" <?php echo $chx2?>>
						<?php echo FText::_('JYES');?>
					</label>
				<?php endif; ?>
			</td>
			<td><?php echo $heading;?></td>
			<td>
				<?php if ($i == 0 && !$this->selectPKField) : ?>
					<input type="hidden" name="plugin[]" value="internalid" />ID
				<?php else : ?>
					<?php echo $this->elementTypes;?>
				<?php endif; ?>
			</td>
			<?php if ($this->selectPKField) :?>
				<td><input type="checkbox" name="key[<?php echo $heading;?>]" value="1" /></td>
			<?php endif; ?>
			<td><?php echo $sample;?></td>
		</tr>

<?php endfor;?>
</tbody>
</table>

<?php
endif;

?>

<?php if (!$this->selectPKField) : ?>
<input type="hidden" name="key[<?php echo $this->newHeadings[0]; ?>]" value="1" />
<?php endif; ?>
	<input type="hidden" name="option" value="com_fabrik" />
	<input type="hidden" name="listid" value="<?php echo $this->table->id;?>" />
	<input type="hidden" name="task" value="import.makeTableFromCSV" />
	<input type="hidden" name="boxchecked" value="" />
	<input type="hidden" name="jform[drop_data]" value="<?php echo $this->drop_data ?>" />
	<input type="hidden" name="jform[overwrite]" value="<?php echo $this->overwrite ?>" />
	<input type="hidden" name="connection_id" value="<?php echo FArrayHelper::getValue($jform, 'connection_id')?>" />
	<input type="hidden" name="jform[addkey]" value="<?php echo FArrayHelper::getValue($jform, 'addkey');?>" />
	<input type="hidden" name="label" value="<?php echo FArrayHelper::getValue($jform, 'label')?>" />
	<input type="hidden" name="db_table_name" value="<?php echo FArrayHelper::getValue($jform, 'db_table_name')?>" />
	<?php echo JHTML::_('form.token'); ?>
</form>