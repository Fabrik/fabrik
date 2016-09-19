<?php
/**
 * Fabrik List Template: Bluesky Filter
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<div class="fabrikFilterContainer">
<?php if ($this->filterMode === 3 || $this->filterMode === 4) {
	?><div class="searchall">
	<ul class="fabrik_action">

	<?php if (array_key_exists('all', $this->filters)) {
		echo '<li>'.$this->filters['all']->element.'</li>';
	}?>
		<?php if ($this->filter_action != 'onchange') {?>
	<li>
	<button class="fabrik_filter_submit button" value="<?php echo FText::_('COM_FABRIK_GO');?>"
				name="filter" >
	<?php echo FabrikHelperHTML::image('search.png', 'list', $this->tmpl);?>
	</button>
<!-- 	<input type="button" class="fabrik_filter_submit button" value="<?php echo FText::_('COM_FABRIK_GO');?>"
				name="filter" /> -->
	</li>
	<?php } ?>
	</ul>
	</div>
<?php
} else {?>

<table class="filtertable fabrikList">
	<thead>
	<tr class="fabrik___heading">
			<th style="text-align:left"><?php echo FText::_('COM_FABRIK_SEARCH');?>:</th>
			<th style="text-align:right"></th>
		</tr>
	</thead>
	<?php
	$c = 0;
	foreach ($this->filters as $key =>$filter) {
			$required = $filter->required == 1 ? ' notempty' : '';?>
			<tr data-filter-row="<?php echo $key;?>" class="fabrik_row oddRow<?php echo ($c % 2). $required;?>">
			<td><?php echo $filter->label;?></td>
			<td style="text-align:right;"><?php echo $filter->element;?></td>
		</tr>
	<?php $c ++;
	} ?>
	<?php if ($this->filter_action != 'onchange') {?>
	<tr class="fabrik_row oddRow<?php echo $c % 2;?>">
		<td colspan="2" style="text-align:right;">
		<input type="button" class="fabrik_filter_submit button" value="<?php echo FText::_('COM_FABRIK_GO');?>"
			name="filter" />
		</td>
	</tr>
	<?php }?>
</table>

<?php }?>
</div>