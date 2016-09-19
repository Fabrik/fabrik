<?php
/**
 * Fabrik List Template: Admin Buttons
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
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
	if ($this->showClearFilters) {?>
		<li>
		<?php echo $this->clearFliterLink;?>
		</li>
	<?php }
	if ($this->showFilters && $this->toggleFilters) {?>
		<li>
			<a href="#" class="toggleFilters">
				<?php echo $this->buttons->filter;?>
				<span><?php echo FText::_('COM_FABRIK_FILTER');?></span>
			</a>
		</li>
	<?php }
	if ($this->advancedSearch !== '') {
		echo '<li>' . $this->advancedSearch . '</li>';
	}
	if ($this->canGroupBy) {?>
		<li>
			<a href="#" class="groupBy">
				<?php echo $this->buttons->groupby;?>
				<span><?php echo FText::_('COM_FABRIK_GROUP_BY');?></span>
			</a>
			<ul>
				<?php foreach ($this->groupByHeadings as $url => $obj) {?>
					<li><a data-groupby="<?php echo $obj->group_by?>" href="<?php echo $url?>"><?php echo $obj->label?></a></li>
				<?php
				}?>
			</ul>
		</li>
	<?php }
	 if ($this->showCSV) {?>
		<li class="csvExportButton">
			<a href="#">
				<?php echo $this->buttons->csvexport;?>
				<span><?php echo FText::_('COM_FABRIK_EXPORT_TO_CSV');?></span>
			</a>
		</li>
	<?php }
	if ($this->showCSVImport) {?>
	<li class="csvImportButton">
			<a href="<?php echo $this->csvImportLink;?>">
				<?php echo $this->buttons->csvimport;?>
				<span><?php echo FText::_('COM_FABRIK_IMPORT_FROM_CSV');?></span>
			</a>
		</li>
	<?php }
	if ($this->showRSS) {?>
	<li class="feedButton">
			<a href="<?php echo $this->rssLink;?>">
				<?php echo $this->buttons->feed;?>
				<span><?php echo FText::_('COM_FABRIK_SUBSCRIBE_RSS');?></span>
			</a>
		</li>
	<?php }
	if ($this->showPDF) {?>
		<li class="pdfButton">
			<a href="<?php echo $this->pdfLink;?>">
				<?php echo $this->buttons->pdf;?>
				<span><?php echo FText::_('COM_FABRIK_PDF');?></span>
			</a>
		</li>
	<?php }
	if ($this->emptyLink) {?>
		<li>
		<a href="<?php echo $this->emptyLink?>" class="doempty">
		<?php echo $this->buttons->empty;?>
		<span><?php echo FText::_('COM_FABRIK_EMPTY')?></span>
		</a>
		</li>
	<?php }
	foreach ($this->pluginTopButtons as $b) {?>
	<li>
		<?php echo $b;?>
	</li>
	<?php }?>
</ul>
<?php }?>
</div>