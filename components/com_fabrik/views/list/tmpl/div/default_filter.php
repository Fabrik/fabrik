<div class="fabrikFilterContainer">
<!--- no 'under headings' filter in div template, always 'above'-->
<ul class="filtertable fabrikList">
	
	<li class="fabrik___heading">
			<span ><?php echo JText::_('COM_FABRIK_SEARCH');?>:</span>	
	</li>

	<?php
	$c = 0;
	foreach ($this->filters as $filter) {
		$required = $filter->required == 1 ? ' notempty' : '';?>
		<li class="fabrik_row oddRow<?php echo ($c % 2) . $required;?>">
			<span class="divfilterLabel"><?php echo $filter->label;?></span>
			<span class="divfilterElement""><?php echo $filter->element;?></span>
		</li>
	<?php $c ++;
	} ?>
	<?php if ($this->filter_action != 'onchange') {?>
	<li class="fabrik_row oddRow<?php echo $c % 2;?>">
		<input type="button" class="fabrik_filter_submit button" value="<?php echo JText::_('COM_FABRIK_GO');?>"
			name="filter" />
	</li>
	<?php }?>
</ul>
</div>