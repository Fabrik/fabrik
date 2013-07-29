<?php
/**
 * Fabrik List Template: Div
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// No direct access
defined('_JEXEC') or die;
?>
<?php if ($this->tablePicker != '') { ?>
	<div style="text-align:right"><?php echo JText::_('COM_FABRIK_LIST') ?>: <?php echo $this->tablePicker; ?></div>
<?php } ?>
<?php if ($this->getModel()->getParams()->get('show-title', 1)) {?>
	<h1><?php echo $this->table->label;?></h1>
<?php }?>

<?php echo $this->table->intro;?>
<form class="fabrikForm" action="<?php echo $this->table->action;?>" method="post" id="<?php echo $this->formid;?>" name="fabrikList">

<?php echo $this->loadTemplate('buttons');


if ($this->showFilters) {
	echo $this->loadTemplate('filter');
}?>

<div class="fabrikDataContainer">

<?php foreach ($this->pluginBeforeList as $c) {
	echo $c;
}?>
	<div class="boxflex">
		<div class="fabrikList" id="list_<?php echo $this->table->renderid;?>" >

			<?php
			$gCounter = 0;
			foreach ($this->rows as $groupedby => $group) {?>
			<?php
			if ($this->isGrouped) :
			?>
			<div class="fabrik_groupheading">
				<a href="#" class="toggle">
					<?php echo FabrikHelperHTML::image('orderasc.png', 'list', $this->tmpl, JText::_('COM_FABRIK_TOGGLE'));?>
					<span class="groupTitle">
						<?php echo $this->grouptemplates[$groupedby]; ?> ( <?php echo count($group)?> )
					</span>
				</a>
			</div>
			<?php
			endif;
			?>
			<div class="fabrik_groupdata">
				<div class="groupdataMsg">
					<div class="emptyDataMessage" style="<?php echo $this->emptyStyle?>">
						<?php echo $this->emptyDataMessage; ?>
					</div>
				</div>

			<?php
			foreach ($group as $this->_row) :
				echo $this->loadTemplate('row');
		 	endforeach;
			if ($this->hasCalculations) : ?>
					<ul class="fabrik_calculations">
					<?php
					foreach ($this->calculations as $cal) :
						echo "<li>";
						echo array_key_exists($groupedby, $cal->grouped ) ? $cal->grouped[$groupedby] : $cal->calc;
						echo  "</li>";
					endforeach;
					?>
					</ul>

			<?php
			endif;
			?>
			<?php
			$gCounter++;?>

			</div>
			<?php
			}?>

		</div>
		<?php
		echo $this->nav;
		print_r($this->hiddenFields);?>
	</div>
</div>

</form>
<?php echo $this->table->outro;?>
