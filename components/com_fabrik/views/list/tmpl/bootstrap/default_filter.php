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
	<div class="span6">
<table class="filtertable table table-striped">
	<thead>
		<tr class="fabrik___heading">
			<th><?php echo JText::_('COM_FABRIK_SEARCH');?>:</th>
			<th style="text-align:right">
			<?php if ($this->showClearFilters) :?>
				<a class="clearFilters" href="#">
					<i class="icon-refresh"></i>
					<?php echo JText::_('COM_FABRIK_CLEAR')?>
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
	foreach ($this->filters as $key => $filter) :
			if ($key !== 'all') :
				$required = $filter->required == 1 ? ' notempty' : '';?>
				<tr class="fabrik_row oddRow<?php echo ($c % 2) . $required;?>">
				<td><?php echo $filter->label;?></td>
				<td style="text-align:right;"><?php echo $filter->element;?></td>
			</tr>

	<?php
	endif;
	$c ++;
	endforeach;
	?>
	<tr><td colspan="2">
	<input type="button" class="pull-right  btn-info btn fabrik_filter_submit button" value="<?php echo JText::_('COM_FABRIK_GO');?>" name="filter" >
	</td></tr>
</table>
<?php
endif;
?>
<?php
if (!($this->filterMode === 3 || $this->filterMode === 4)) :
?>
</div>
</div>
<?php endif; ?>
</div>