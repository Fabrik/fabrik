<?php
/**
 * Fabrik List Template: Default Group Headings
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// No direct access
defined('_JEXEC') or die;

echo $this->showGroup ? '<tbody>' : '<tbody style="display:none">';
?>
	<tr class="fabrik_groupheading">
		<td colspan="<?php echo $this->colCount;?>">
			<a href="#" class="toggle">
				<?php echo FabrikHelperHTML::image('orderasc.png', 'list', $this->tmpl, JText::_('COM_FABRIK_TOGGLE'));?>
				<span class="groupTitle">
					<?php echo $this->groupHeading; ?>
				</span>
			</a>
		</td>
	</tr>
</tbody>