<?php
/**
 * Bootstrap List Template - Buttons
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<div class="btn-group">
	<?php if ($this->canGroupBy) :?>
		<div class="btn-group">
			<a href="#" class="btn dropdown-toggle groupBy" data-toggle="dropdown">
				<i class="icon-list-view"></i>
				<?php echo FText::_('COM_FABRIK_GROUP_BY');?>
			</a>
			<ul class="dropdown-menu">
				<?php foreach ($this->groupByHeadings as $url => $obj) {?>
					<li><a data-groupby="<?php echo $obj->group_by?>" href="<?php echo $url?>"><?php echo $obj->label?></a></li>
				<?php
				}?>
			</ul>
		</div>
	<?php endif ?>

	<?php if ($this->showAdd) {?>
		<a class="addbutton btn addRecord" href="<?php echo $this->addRecordLink;?>">
			<i class="icon-plus"></i>
			<?php echo $this->addLabel?>
		</a>
	<?php }?>

	<?php if ($this->showClearFilters) :?>
		<a class="clearFilters btn" href="#">
			<i class="icon-refresh"></i>
			<?php echo FText::_('COM_FABRIK_CLEAR')?>
		</a>
	<?php endif ?>

	<?php if ($this->showCSV) {?>
		<a href="#" class="btn csvExportButton">
			<i class="icon-upload"></i>
			<?php echo FText::_('COM_FABRIK_EXPORT_TO_CSV');?>
		</a>
	<?php }?>

	<?php if ($this->advancedSearch !== '') : ?>
		<a href="<?php echo $this->advancedSearchURL?>" class="btn advanced-search-link">
			<i class="icon-search"></i>
			<?php echo FText::_('COM_FABRIK_ADVANCED_SEARCH');?>
		</a>
	<?php endif?>

	<?php if ($this->showCSVImport) {?>
		<a href="<?php echo $this->csvImportLink;?>" class="btn csvImportButton">
			<i class="icon-download"></i>
			<?php echo FText::_('COM_FABRIK_IMPORT_FROM_CSV');?>
		</a>
	<?php }?>

	<?php if ($this->showRSS) {?>
		<a href="<?php echo $this->rssLink;?>" class="btn feedButton">
			<?php echo FabrikHelperHTML::image('feed.png', 'list', $this->tmpl);?>
			<?php echo FText::_('COM_FABRIK_SUBSCRIBE_RSS');?>
		</a>
	<?php }
	if ($this->showPDF) {?>
			<a href="<?php echo $this->pdfLink;?>" class="btn pdfButton">
				<i class="icon-file"></i>
				<?php echo FText::_('COM_FABRIK_PDF');?>
			</a>
	<?php }?>


</div>
<p></p>
