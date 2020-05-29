<?php
/**
 * Div List Template - Buttons
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<div class="row-fluid">
<ul class="nav nav-pills  pull-left">

<?php if ($this->showAdd) :?>

	<li><a class="addbutton addRecord" href="<?php echo $this->addRecordLink;?>">
			<?php echo FabrikHelperHTML::icon('icon-plus'); ?>
		<?php echo $this->addLabel?>
	</a></li>
<?php
endif;

if ($this->showToggleCols) :
	echo $this->loadTemplate('togglecols');
endif;

if ($this->canGroupBy) :?>

	<li class="dropdown">
		<a href="#" class="dropdown-toggle groupBy" data-toggle="dropdown">
			<?php echo FabrikHelperHTML::icon('icon-list-view'); ?>
			<?php echo FText::_('COM_FABRIK_GROUP_BY');?>
			<b class="caret"></b>
		</a>
		<ul class="dropdown-menu">
			<?php foreach ($this->groupByHeadings as $url => $obj) :?>
				<li><a data-groupby="<?php echo $obj->group_by?>" href="<?php echo $url?>"><?php echo $obj->label?></a></li>
			<?php
			endforeach;?>
		</ul>
	</li>

<?php endif;
if (($this->showClearFilters && (($this->filterMode === 3 || $this->filterMode === 4))  || $this->bootShowFilters == false)):
	$clearFiltersClass = $this->gotOptionalFilters ? "clearFilters hasFilters" : "clearFilters";
?>
	<li>
		<a class="<?php echo $clearFiltersClass; ?>" href="#">
			<?php echo FabrikHelperHTML::icon('icon-refresh'); ?>
			<?php echo FText::_('COM_FABRIK_CLEAR')?>
		</a>
	</li>
<?php endif;
if ($this->showFilters && $this->toggleFilters) :?>
	<li>
		<a href="#" class="toggleFilters">
			<?php echo $this->buttons->filter;?>
			<span><?php echo FText::_('COM_FABRIK_FILTER');?></span>
		</a>
	</li>
<?php endif;
if ($this->advancedSearch !== '') : ?>
	<li>
		<a href="<?php echo $this->advancedSearchURL?>" class="advanced-search-link">
			<?php echo FabrikHelperHTML::icon('icon-search'); ?>
			<?php echo FText::_('COM_FABRIK_ADVANCED_SEARCH');?>
		</a>
	</li>
<?php endif;
if ($this->showCSVImport || $this->showCSV) :?>
	<li class="dropdown">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown">
			<?php echo FabrikHelperHTML::icon('icon-upload'); ?>
			<?php echo FText::_('COM_FABRIK_CSV');?>
			<b class="caret"></b>
		</a>
		<ul class="dropdown-menu">
			<?php if ($this->showCSVImport) :?>
			<li><a href="<?php echo $this->csvImportLink;?>" class="csvImportButton">
					<?php echo FabrikHelperHTML::icon('icon-download'); ?>
				<?php echo FText::_('COM_FABRIK_IMPORT_FROM_CSV');?>
			</a></li>
			<?php endif?>

			<?php if ($this->showCSV) :?>
			<li><a href="#" class="csvExportButton">
					<?php echo FabrikHelperHTML::icon('icon-upload'); ?>
				<?php echo FText::_('COM_FABRIK_EXPORT_TO_CSV');?>
			</a></li>
			<?php endif?>
		</ul>
	</li>
<?php endif;
if ($this->showRSS) :?>
	<li>
		<a href="<?php echo $this->rssLink;?>" class="feedButton">
		<?php echo FabrikHelperHTML::image('feed.png', 'list', $this->tmpl);?>
		<?php echo FText::_('COM_FABRIK_SUBSCRIBE_RSS');?>
		</a>
	</li>
<?php
endif;
if ($this->showPDF) :?>
			<li><a href="<?php echo $this->pdfLink;?>" class="pdfButton">
					<?php echo FabrikHelperHTML::icon('icon-file'); ?>
				<?php echo FText::_('COM_FABRIK_PDF');?>
			</a></li>
<?php endif;
if ($this->emptyLink) :?>
		<li>
			<a href="<?php echo $this->emptyLink?>" class="doempty">
			<?php echo $this->buttons->empty;?>
			<?php echo FText::_('COM_FABRIK_EMPTY')?>
			</a>
		</li>
<?php
endif;
?>
</ul>
<?php if (array_key_exists('all', $this->filters) || $this->filter_action != 'onchange') {
?>
<ul class="nav pull-right">
	<li>
	<div <?php echo $this->filter_action != 'onchange' ? 'class="input-append"' : ''; ?>>
	<?php if (array_key_exists('all', $this->filters)) {
		echo $this->filters['all']->element;

	if ($this->filter_action != 'onchange') {?>

		<input type="button" class="btn fabrik_filter_submit button" value="<?php echo FText::_('COM_FABRIK_GO');?>" name="filter" >

	<?php
	};?>

	<?php };
	?>
	</div>
	</li>
</ul>
<?php
}
?>
</div>