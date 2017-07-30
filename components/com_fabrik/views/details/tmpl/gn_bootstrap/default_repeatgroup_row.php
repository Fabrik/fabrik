<?php
/**
 * Bootstrap Details: Repeat group rendered as a table, <tr> template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$group = $this->group;
?>
<tr class="fabrikSubGroupElements fabrikSubGroup">
<?php foreach ($this->elements as $element) :
	$style = $element->hidden ? 'style="display:none"' : '';
	?>
	<td class="<?php echo $element->containerClass; ?>" <?php echo $style; ?>>
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
		<td class="fabrikGroupRepeater pull-right">
			<?php if ($group->canAddRepeat) :?>
			<a class="addGroup" href="#">
				<?php echo FabrikHelperHTML::image('plus', 'form', $this->tmpl, array('class' => 'fabrikTip tip-small', 'title' => FText::_('COM_FABRIK_ADD_GROUP')));?>
			</a>
			<?php
			endif;
			if ($group->canDeleteRepeat) :?>
			<a class="deleteGroup" href="#">
				<?php echo FabrikHelperHTML::image('minus', 'form', $this->tmpl, array('class' => 'fabrikTip tip-small', 'title' => FText::_('COM_FABRIK_DELETE_GROUP')));?>
			</a>
			<?php endif;?>
		</td>
	<?php endif; ?>
</tr>
