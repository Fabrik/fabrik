<?php
/**
 * Fabrik Import View
 *
 * @package Joomla
 * @subpackage Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

class FabrikViewImport
{

	function setcsvChooseElementTypesToolbar()
	{
		JToolBarHelper::title(JText::_('Assign element types'));
		JToolBarHelper::custom('makeTableFromCSV', 'forward.png', 'forward.png', 'Continue', false);
		JToolBarHelper::cancel();
	}

	/**
	 * The imported data doesn't match the tables
	 * Ask what the user wants to do
	 *
	 */
	function csvChooseElementTypes($elementTypes)
	{

		FabrikViewImport::setcsvChooseElementTypesToolbar();
?>
	<form action="index.php" method="post" name="adminForm">
	<?php if (!empty($this->newHeadings))
		{
			echo "<H3>" . JText::_('NEW HEADINGS FOUND') . "</h3>";
			echo str_replace('{table}', $this->table->label, JText::_('NEWHEADINGSFOUNDDESC'));
	?>

		<table class="adminlist">
			<thead>
			<tr>
				<th class="title"><?php echo JText::_('CREATE ELEMENT'); ?></th>
				<th class="title"><?php echo JText::_('LABEL'); ?></th>
				<th class="title"><?php echo JText::_('ELEMENT TYPE'); ?></th>
				<?php if ($this->table->db_primary_key == '')
			{ ?>
					<th class="title"><?php echo JText::_('PRIMARY KEY'); ?></th>
				<?php } ?>
				<th><?php echo JText::_('SAMPLE DATA'); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			for ($i = 0; $i < count($this->newHeadings); $i++)
			{
				$heading = trim($this->newHeadings[$i]);
				foreach ($this->headings as $sKey => $sVal)
				{
					if (JString::strtolower($heading) == JString::strtolower($sVal))
					{
						$sample = $this->data[0][$sKey];
					}
				}
			?>
			<tr>
				<td>
				<label>
					<input type="radio" name="createElements[<?php echo $heading; ?>]" value="0" checked="checked">
					<?php echo JText::_('NO'); ?>
				</label>
				<label>
					<input type="radio" name="createElements[<?php echo $heading; ?>]" value="1">
					<?php echo JText::_('YES'); ?>
				</label>
			</td>
			<td><?php echo $heading; ?></td>
			<td><?php echo $elementTypes; ?></td>
			<?php if ($this->table->db_primary_key == '')
				{ ?>
				<td><input type="checkbox" name="key[<?php echo $heading; ?>]" value="1" /></td>
			<?php } ?>
			<td><?php echo $sample; ?></td>
		</tr>
<?php } ?>
</tbody>
</table>
<?php
		}
?> <?php echo "<H3>" . JText::_('EXISTING HEADINGS FOUND') . "</h3>"; ?>
<table class="adminlist">
	<thead>
	<tr>
		<th class="title"><?php echo JText::_('LABEL'); ?></th>
		<?php if ($this->table->db_primary_key == '')
		{ ?>
			<th class="title"><?php echo JText::_('PRIMARY KEY'); ?></th>
		<?php } ?>
		<th><?php echo JText::_('SAMPLE DATA'); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php

		foreach ($this->matchedHeadings as $heading)
		{

			foreach ($this->headings as $sKey => $sVal)
			{
				if (JString::strtolower($heading) == JString::strtolower($sVal))
				{
					$sample = $this->data[0][$sKey];
				}
			}
	?>
	<tr>
		<td><?php echo $heading; ?></td>
		<?php if ($this->table->db_primary_key == '')
			{ ?>
			<td>
			<input type="checkbox" name="key[<?php echo $heading; ?>]" value="1" />
			</td>
		<?php } ?>
		<td><?php echo $sample; ?></td>
	</tr>
	<?php } ?>
	</tbody>
</table>
	<input type="hidden" name="option" value="com_fabrik" />
	<?php
	$app = JFactory::getApplication();
	$input = $app->input;

	// @TODO not sure about this value: ?>
	<input type="hidden" name="table" value="<?php echo $this->table->db_table_name; ?>" />
	<input type="hidden" name="fabrik_list" value="<?php echo $this->table->id; ?>" />
	<input type="hidden" name="task" value="makeTableFromCSV" />
	<input type="hidden" name="c" value="import" />
	<input type="hidden" name="boxchecked" value="" />
	<input type="hidden" name="drop_data" value="<?php echo $input->get('drop_data') ?>" />
	<input type="hidden" name="overwrite" value="<?php echo $input->get('overwrite') ?>" />
	<?php echo JHTML::_('form.token'); ?>
</form>
<?php

	}

	/**
	 * select a file and options to start the
	 * import process
	 *
	 */
	function import()
	{
?>
	<script language="javascript" type="text/javascript">
		function submitbutton3(pressbutton) {
			form.submit();
		}
		</script>

<form enctype="multipart/form-data" action="index.php" method="post" name="csv">

<table class="adminform">
	<tr>
		<th colspan="2"><?php echo JText::_('IMPORT CSV FILE') . ": " . $this->table->label; ?></th>
	</tr>
	<tr>
		<td align="left"><label for="userfile"><?php echo JText::_('CSV FILE'); ?></label>
		</td>
		<td><input class="text_area" name="userfile" id="userfile" type="file" size="40" /></td>
	</tr>

	<tr>
		<td align="left"><label for="drop_data"><?php echo JText::_('DROP EXISTING DATA'); ?></label>
		</td>
		<td><input type="checkbox" name="drop_data" id="drop_data" value="1" />
		</td>
	</tr>
	<tr>
		<td align="left"><label for="overwrite"><?php echo JText::_('OVERWRITE MATCHING RECORDS'); ?></label>
		</td>
		<td><input type="checkbox" name="overwrite" id="overwrite" value="1" />
		</td>
	</tr>

	<tr>
		<td align="left"><label for="field_delimiter"><?php echo JText::_('FIELD DELIMITER'); ?></label>
		</td>
		<td>
		<input size="2" class="input" id="field_delimiter" name="field_delimiter" value="," />
		</td>
	</tr>
	<tr>
		<td align="left"><label for="text_delimiter"><?php echo JText::_('TEXT DELIMITER'); ?></label>
		</td>
		<td>
		<input size="2" class="input" name="text_delimiter" id="text_delimiter" value='&quot;' />
		</td>
	</tr>
	<tr>
		<td colspan="2" align="left"><input class="button" type="submit"
			value="<?php echo JText::_('IMPORT CSV'); ?>" /></td>
	</tr>
</table>
<input type="hidden" name="option" value="com_fabrik" />
<input type="hidden" name="c" value="import" />
<input type="hidden" name="task" value="doimport" />
<input type="hidden" name="listid" value="<?php echo $this->listid; ?>" />
</form>
<?php
	}
}
