<?php
/**
 * Bootstrap List Template - Filter
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$style = $this->toggleFilters ? 'style="display:none"' : ''; ?>
<div class="fabrikFilterContainer" <?php echo $style?>>
<?php
if ($this->filterMode === 3 || $this->filterMode === 4) :
?>
<?php
else:
?>
<div class="row-fluid">
<?php
	if ($this->filterCols === 1) :
?>
	<div class="span6">
<?php
	endif;
?>
<table class="filtertable table table-striped">
	<thead>
		<tr class="fabrik___heading">
			<th><?php echo FText::_('COM_FABRIK_SEARCH');?>:</th>
			<th style="text-align:right">
			<?php if ($this->showClearFilters) :?>
				<a class="clearFilters" href="#">
					<i class="icon-refresh"></i>
					<?php echo FText::_('COM_FABRIK_CLEAR')?>
					</a>
			<?php endif ?>
			</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="2"></td>
		</tr>
	</tfoot>
	<?php
	$c = 0;
	// $$$ hugh - filterCols stuff isn't operation yet, WiP, just needed to get it committed
	if ($this->filterCols > 1) :
		?>
 	<tr>
 		<td colspan="2">
 			<table class="filtertable_horiz">
 		<?php
	endif;
	$filter_count = array_key_exists('all', $this->filters) ? count($this->filters) - 1 : count($this->filters);
	$colHeight = ceil($filter_count / $this->filterCols);
	foreach ($this->filters as $key => $filter) :
			if ($this->filterCols > 1 && $c >= $colHeight && $c % $colHeight === 0) :
				?>
			</table>
			<table class="filtertable_horiz">
				<?php
			endif;
			if ($key !== 'all') :
				$c ++;
				$required = $filter->required == 1 ? ' notempty' : '';?>
				<tr data-filter-row="<?php echo $key;?>" class="fabrik_row oddRow<?php echo ($c % 2) . $required;?>">
				<td><?php echo $filter->label;?></td>
				<td style="text-align:right;"><?php echo $filter->element;?></td>
			</tr>
	<?php
	endif;
	endforeach;
	if ($this->filterCols > 1) :
		?>
 			</table>
 		</td>
 	</tr>
 		<?php
	endif;
	if ($this->filter_action != 'onchange') :
	?>
	<tr>
		<td colspan="2">
			<input type="button" class="pull-right  btn-info btn fabrik_filter_submit button" value="<?php echo FText::_('COM_FABRIK_GO');?>" name="filter" >
		</td>
	</tr>
	<?php
	endif;
	?>
</table>
<?php
endif;
?>
<?php
if (!($this->filterMode === 3 || $this->filterMode === 4)) :
?>
<?php
	if ($this->filterCols === 1) :
?>
	</div>
<?php
	endif;
?>
</div>
<?php endif; ?>
</div>