<?php
/**
 * Fabrik List Template: Div Filter
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
<!--- no 'under headings' filter in div template, always 'above'-->
<ul class="filtertable fabrikList">

	<li class="fabrik___heading">
			<span ><?php echo FText::_('COM_FABRIK_SEARCH');?>:</span>
	</li>

	<?php
	$c = 0;
	foreach ($this->filters as $key => $filter) {
		$required = $filter->required == 1 ? ' notempty' : '';?>
		<li data-filter-row="<?php echo $key;?>" class="fabrik_row oddRow<?php echo ($c % 2) . $required;?>">
			<span class="divfilterLabel"><?php echo $filter->label;?></span>
			<span class="divfilterElement""><?php echo $filter->element;?></span>
		</li>
	<?php $c ++;
	} ?>
	<?php if ($this->filter_action != 'onchange') {?>
	<li class="fabrik_row oddRow<?php echo $c % 2;?>">
		<input type="button" class="fabrik_filter_submit button" value="<?php echo FText::_('COM_FABRIK_GO');?>"
			name="filter" />
	</li>
	<?php }?>
</ul>
</div>