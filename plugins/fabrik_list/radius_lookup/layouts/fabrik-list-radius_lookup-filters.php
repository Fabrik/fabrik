<?php
/**
 * Layout: Yes/No field list view
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.2
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$data = $displayData;
$renderOrder = $data['renderOrder'];
$vals = $data['vals'];
$lat = $data['lat'];
$lon = $data['lon'];
$distanceField = $data['distanceField'];
$nameField = $data['nameField'];
$listData = $data['data'];
?>
<div id="radius_lookup<?php echo $renderOrder; ?>">
		<input type="hidden" name="radius_search_lat<?php echo $renderOrder; ?>" value="<?php echo $lat; ?>" />
		<input type="hidden" name="radius_search_lon<?php echo $renderOrder; ?>" value="<?php echo $lon; ?>" />
		<table class="table">
		<tr>
		<th><?php echo JText::_('PLG_LIST_RADIUS_LOOKUP_NAME'); ?></th>
		<th><?php echo JText::_('PLG_LIST_RADIUS_DISTANCE'); ?></th>
		<th><?php echo JText::_('PLG_LIST_RADIUS_LOOKUP_VISIBLE'); ?></th>
		</tr>

	<?php
 		foreach ($listData as $group)
		{
			foreach ($group as $row)
			{
				$val = FArrayHelper::getValue($vals, $row->__pk_val);
				$noneSel = (string) $val === '' ? 'selected' : '';
				$noSel = (string) $val === '0' ? 'selected' : '';
				$yesSel = (string) $val === '1' ? 'selected' : '';
				$distance = $row->$distanceField;
				$drop = '<select name="radius_lookup' . $renderOrder . '[' . $row->__pk_val . ']">
				<option ' . $noneSel . '>' . JText::_('COM_FABRIK_PLEASE_SELECT') . '</option>
				<option ' . $noSel. ' value="0">'
						 . JText::_('JNO') . '</option><option ' . $yesSel. ' value="1">' . JText::_('JYES') . '</option></select>';?>
				<tr>
				<td><?php echo $row->$nameField?></td>
				<td><?php echo $distance ?></td>
				<td><?php echo $drop?></td>
				</tr>
			<?php }
		}
?>
		</table>
		</div>