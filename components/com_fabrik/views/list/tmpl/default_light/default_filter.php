<?php
/**
 * Fabrik List Template: Default Filter
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// No direct access
defined('_JEXEC') or die;
?>
<div class="fabrikFilterContainer">
<?php if ($this->filterMode === 3 || $this->filterMode === 4) {?>
<div class="searchall">
	<ul class="fabrik_action">

	<?php if (array_key_exists('all', $this->filters)) {
		echo '<li>' . $this->filters['all']->element . '</li>';
	}?>
		<?php if ($this->filter_action != 'onchange') {?>
	<li>
		<input type="button" class="fabrik_filter_submit button" value="<?php echo JText::_('COM_FABRIK_GO');?>" name="filter" >

	</li>
	<?php } ?>
	</ul>
	</div>
<?php
} else {?>

<table class="filtertable fabrikList">
	<thead>
	<tr class="fabrik___heading">
			<th style="text-align:left"><?php echo JText::_('COM_FABRIK_SEARCH');?>:</th>
			<th style="text-align:right"></th>
		</tr>
	</thead>
	<?php
	$c = 0;
	foreach ($this->filters as $filter) {
			$required = $filter->required == 1 ? ' notempty' : '';?>
			<tr class="fabrik_row oddRow<?php echo ($c % 2) . $required;?>">
			<td><?php echo $filter->label;?></td>
			<td style="text-align:right;"><?php echo $filter->element;?></td>
		</tr>
	<?php $c ++;
	} ?>
	<?php if ($this->filter_action != 'onchange') {?>
	<tr class="fabrik_row oddRow<?php echo $c % 2;?>">
		<td colspan="2" style="text-align:right;">
		<input type="button" class="fabrik_filter_submit button" value="<?php echo JText::_('COM_FABRIK_GO');?>"
			name="filter" />
		</td>
	</tr>
	<?php }?>
</table>

<?php }?>
</div>