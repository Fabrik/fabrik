<?php
/**
 * Bootstrap Details Template: Repeat group rendered as standard form
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       3.0
 */

$group = $this->group;
foreach ($group->subgroups as $subgroup) :
	?>
	<div class="fabrikSubGroup">
	<?php
		// Add the add/remove repeat group buttons
		if ($group->editable) : ?>
			<div class="fabrikGroupRepeater pull-right">
				<?php if ($group->canAddRepeat) :?>
				<a class="addGroup" href="#">
					<?php echo FabrikHelperHTML::image('plus.png', 'form', $this->tmpl, array('class' => 'fabrikTip tip-small', 'opts' => '{trigger: "hover"}', 'title' => JText::_('COM_FABRIK_ADD_GROUP')));?>
				</a>
				<?php
				endif;
				if ($group->canDeleteRepeat) :?>
				<a class="deleteGroup" href="#">
					<?php echo FabrikHelperHTML::image('minus.png', 'form', $this->tmpl, array('class' => 'fabrikTip tip-small', 'opts' => '{trigger: "hover"}', 'title' => JText::_('COM_FABRIK_DELETE_GROUP')));?>
				</a>
				<?php endif;?>
			</div>
		<?php
		endif;
		?>
		<div class="fabrikSubGroupElements">
			<?php

			// Load each group in a <ul>
			$this->elements = $subgroup;
			echo $this->loadTemplate('group');
			?>
		</div><!-- end fabrikSubGroupElements -->
	</div><!-- end fabrikSubGroup -->
	<?php
endforeach;
