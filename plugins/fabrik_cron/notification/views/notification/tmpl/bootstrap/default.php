<?php
/**
 * Cron notification view to manage user notifications
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

if (count($this->rows) == 0) :
	echo FText::_('YOU_ARE_NOT_SUBSCRIBED_TO_ANY_NOTIFICATIONS');
else:
?>
<form action="index.php" method="post" name="adminForm">
	<table class="table table-stripped">
		<thead>
			<tr>
				<th width="1%"> <input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(<?php echo count($this->rows);?>);" /> </th>
				<th width="49%">
					<?php echo FText::_('PLG_CRON_NOTIFICATION_ENTRY')?>
				</th>
				<th width ="50%">
					<?php echo FText::_('PLG_CRON_NOTIFICATION_REASON')?>
				</th>
			</tr>
		</thead>
		<tbody>
<?php
$k = 0;
for ($i = 0, $n = count($this->rows); $i < $n; $i ++) :
	$row = $this->rows[$i];
	?>
			<tr>
				<td>
					<?php echo JHTML::_('grid.checkedout', $row, $i);?>
				</td>
				<td><a href="<?php echo $row->url?>"><?php echo $row->title?></a></td>
				<td><?php echo $row->reason?></td>
			</tr>
	<?php
	$k = 1 - $k;
endfor;
?>
		</tbody>
	</table>
	<input type="submit" value="<?php echo FText::_('COM_FABRIK_DELETE')?>" class="button btn btn-danger"/>
	<input type="hidden" name="option" value="com_fabrik" />
	<input type="hidden" name="id" value="<?php echo $this->id; ?>" />
	<input type="hidden" name="task" value="cron.delete" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHTML::_('form.token'); ?>
</form>
<?php endif;
