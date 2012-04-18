<div class="fabrik_buttons">
	<?php if ($this->hasButtons) {?>

	<ul class="fabrik_action"><?php if ($this->showAdd) {?>
		<li class="addbutton">
			<a class="addRecord" href="<?php echo $this->addRecordLink;?>">
				<?php echo $this->buttons->add;?>
				<span><?php echo $this->addLabel?></span>
			</a>
		</li>
	<?php }

	if ($this->showFilters && $this->params->get('show-table-filters') == 2) {?>
		<li>
			<a href="#" class="toggleFilters">
				<?php echo $this->buttons->filter;?>
				<span><?php echo JText::_('COM_FABRIK_FILTER');?></span>
			</a>
		</li>
	<?php }
	if ($this->canGroupBy) {?>
		<li>
			<a href="#" class="groupBy">
				<?php echo $this->buttons->filter;?>
				<span><?php echo JText::_('COM_FABRIK_GROUP_BY');?></span>
			</a>
			<ul class="floating-tip">
				<?php foreach ($this->groupByHeadings as $url => $label) {?>
					<li><a href="<?php echo $url?>"><?php echo $label?></a></li>
				<?php
				}?>
			</ul>
		</li>
	<?php }
	 if ($this->showCSV) {?>
		<li class="csvExportButton">
			<a href="#">
				<?php echo $this->buttons->csvexport;?>
				<span><?php echo JText::_('COM_FABRIK_EXPORT_TO_CSV');?></span>
			</a>
		</li>
	<?php }
	if ($this->showCSVImport) {?>
	<li class="csvImportButton">
			<a href="<?php echo $this->csvImportLink;?>">
				<?php echo $this->buttons->csvimport;?>
				<span><?php echo JText::_('COM_FABRIK_IMPORT_FROM_CSV');?></span>
			</a>
		</li>
	<?php }
	if ($this->showRSS) {?>
	<li class="feedButton">
			<a href="<?php echo $this->rssLink;?>">
				<?php echo $this->buttons->feed;?>
				<span><?php echo JText::_('COM_FABRIK_SUBSCRIBE_RSS');?></span>
			</a>
		</li>
	<?php }
	if ($this->showPDF) {
		echo '<li>'.$this->pdfLink.'<li>';
	}
	if ($this->emptyLink) {?>
		<li>
		<a href="<?php echo $this->emptyLink?>" class="button doempty">
		<?php echo $this->buttons->empty;?>
		<span><?php echo JText::_('COM_FABRIK_EMPTY')?></span>
		</a>
		</li>
<?php }?>
</ul>
<?php }?>
</div>