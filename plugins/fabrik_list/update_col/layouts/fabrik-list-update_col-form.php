<?php
defined('JPATH_BASE') or die;
$d     = $displayData;
?>

<?php 
if (!empty($d->user_select_message))
{
?>
<div class="update_col_user_select_message">
<?php echo FText::_($d->user_select_message); ?>
</div>
<?php 
}
?>
<form id="update_col<?php echo $d->listRef; ?>_<?php echo $d->renderOrder; ?>">

	<table class="table table-striped" style="width:100%">
		<thead>
		<tr>
			<th><?php echo FText::_('COM_FABRIK_ELEMENT'); ?></th>
			<th><?php echo FText::_('COM_FABRIK_VALUE'); ?></th>
			<th>
				<a class="btn add button btn-primary" href="#">
					<?php echo $d->addImg; ?>
				</a>
			</th>
		<tr>
		</thead>
		<tbody>
		<tr>
			<td><?php echo $d->elements; ?></td>
			<td class="update_col_value">
			<td>
				<div class="btn-group">
					<a class="btn add button btn-primary" href="#">
						<?php echo $d->addImg; ?>
					</a>
					<a class="btn button delete" href="#">
						<?php echo $d->delImg; ?>
					</a>
				</div>
			</td>
		</tr>
		</tbody>
	</table>
	<input class="button btn button-primary" value="<?php echo FText::_('COM_FABRIK_APPLY'); ?>" type="button">
</form>