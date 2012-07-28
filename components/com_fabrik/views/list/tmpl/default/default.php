<?php if ($this->tablePicker != '') { ?>
	<div style="text-align:right"><?php echo JText::_('COM_FABRIK_LIST') ?>: <?php echo $this->tablePicker; ?></div>
<?php } ?>
<?php if ($this->params->get('show-title', 1)) {?>
	<h1><?php echo $this->table->label;?></h1>
<?php }?>

<?php echo $this->table->intro;?>
<form class="fabrikForm" action="<?php echo $this->table->action;?>" method="post" id="<?php echo $this->formid;?>" name="fabrikList">

<?php echo $this->loadTemplate('buttons');
if ($this->showFilters) {
	echo $this->loadTemplate('filter');
}
//for some really ODD reason loading the headings template inside the group
//template causes an error as $this->_path['template'] doesnt cotain the correct
// path to this template - go figure!
$this->headingstmpl =  $this->loadTemplate('headings');
?>

<div class="fabrikDataContainer">

<?php foreach ($this->pluginBeforeList as $c) {
	echo $c;
}?>
	<div class="boxflex">
		<table class="fabrikList" id="list_<?php echo $this->table->renderid;?>" >
		 <tfoot>
			<tr class="fabrik___heading">
				<td colspan="<?php echo count($this->headings);?>">
					<?php echo $this->nav;?>
				</td>
			</tr>
		 </tfoot>
		 <!--
		 <thead style="<?php echo $this->emptyStyle?>">
		 	<tr>
		 		<td colspan="<?php echo $this->colCount;?>">
		 			<div class="emptyDataMessage">
						<?php echo $this->emptyDataMessage; ?>
					</div>
				</td>
		 	</tr>
		 </thead>
		 -->
			<?php
			echo '<thead>'.$this->headingstmpl.'</thead>';
			if ($this->isGrouped && empty($this->rows)) {
				?>
				<tbody style="<?php echo $this->emptyStyle?>">
				<tr>
				<td class="groupdataMsg" colspan="<?php echo count($this->headings)?>">
				<div class="emptyDataMessage" style="<?php echo $this->emptyStyle?>">
				<?php echo $this->emptyDataMessage; ?>
									</div>
								</td>
							</tr>
						</tbody>
				<?php
			}
			$gCounter = 0;
			foreach ($this->rows as $groupedby => $group) {
			/*
			if ($gCounter == 0) {
				echo '<thead>'.$this->headingstmpl.'</thead>';
			}
			*/
			if ($this->isGrouped) {
			?>
			<tbody>
			<tr class="fabrik_groupheading">
				<td colspan="<?php echo $this->colCount;?>">
					<a href="#" class="toggle">
						<?php echo FabrikHelperHTML::image('orderasc.png', 'list', $this->tmpl, JText::_('COM_FABRIK_TOGGLE'));?>
						<?php echo $this->grouptemplates[$groupedby]; ?> ( <?php echo count($group)?> )
					</a>
				</td>
			</tr>
			</tbody>
			<?php }?>
			<tbody class="fabrik_groupdata">
			<tr>
				<td class="groupdataMsg" colspan="<?php echo count($this->headings)?>">
					<div class="emptyDataMessage" style="<?php echo $this->emptyStyle?>">
						<?php echo $this->emptyDataMessage; ?>
					</div>
				</td>
			</tr>
<?php
			foreach ($group as $this->_row) {
				echo $this->loadTemplate('row');
		 	}
		 	?>
		<?php if ($this->hasCalculations) { ?>
				<tr class="fabrik_calculations">
				<?php
				foreach ($this->calculations as $cal) {
					echo "<td>";
					echo array_key_exists($groupedby, $cal->grouped) ? $cal->grouped[$groupedby] : $cal->calc;
					echo  "</td>";
				}
				?>
				</tr>

			<?php }?>
			</tbody>
			<?php $gCounter++;
			}?>
		</table>
		<?php print_r($this->hiddenFields);?>
	</div>
</div>
</form>
<?php echo $this->table->outro;?>