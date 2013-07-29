<?php
/**
 * Default Form: Repeat group rendered as a table, <tr> template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       3.0
 */

$group = $this->group;
 ?>
<tr class="fabrikSubGroupElements fabrikSubGroup oddRow<?php echo $this->i % 2;?>">
<?php foreach ($this->elements as $element) :
	?>
	<td class="<?php echo $element->containerClass; ?>">
	<?php
	if ($this->tipLocation == 'above') :
	?>
		<div><?php echo $element->tipAbove; ?></div>
	<?php
	endif;
	echo $element->errorTag; ?>
	<div class="fabrikElement">
		<?php echo $element->element; ?>
	</div>

	<?php if ($this->tipLocation == 'side') :
		echo $element->tipSide;
	endif;
	if ($this->tipLocation == 'below') : ?>
		<div>
			<?php echo $element->tipBelow; ?>
		</div>
	<?php endif;
	?>
	</td>
	<?php
	endforeach;

	if ($group->editable) : ?>
		<td class="fabrikGroupRepeater">
			<?php if ($group->canAddRepeat) : ?>
			<a class="addGroup" href="#">
				<?php echo FabrikHelperHTML::image('add.png', 'form', $this->tmpl, array('class' => 'fabrikTip', 'title' => JText::_('COM_FABRIK_ADD_GROUP')));?>
			</a>
			<?php
			endif;
			if ($group->canDeleteRepeat) : ?>
			<a class="deleteGroup" href="#">
				<?php echo FabrikHelperHTML::image('del.png', 'form', $this->tmpl, array('class' => 'fabrikTip', 'title' => JText::_('COM_FABRIK_DELETE_GROUP')));?>
			</a>
			<?php
			endif;
			?>
		</td>
	<?php
	endif;
	?>
</tr>
