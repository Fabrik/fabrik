<?php
/**
 * Fabrik List Template: Default Group Headings
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

echo $this->showGroup ? '<tbody>' : '<tbody style="display:none">';
?>
	<tr class="fabrik_groupheading">
		<td colspan="<?php echo $this->colCount;?>">
			<a href="#" class="toggle">
				<?php echo FabrikHelperHTML::image('orderasc.png', 'list', $this->tmpl, FText::_('COM_FABRIK_TOGGLE'));?>
				<span class="groupTitle">
					<?php echo $this->groupHeading; ?>
				</span>
			</a>
		</td>
	</tr>
</tbody>