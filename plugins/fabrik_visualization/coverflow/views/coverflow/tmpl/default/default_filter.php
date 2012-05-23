<?php if ($this->showFilters) {?>
<form method="post" action="" name="filter">
<?php
foreach ($this->filters as $table => $filters) {
  if (!empty( $filters)) {?>
	  <table class="filtertable fabrikList"><tbody>
	  <tr>
		<th style="text-align:left"><?php echo JText::_('SEARCH');?>:</th>
		<th style="text-align:right"><a href="#" class="clearFilters"><?php echo JText::_('CLEAR'); ?></a></th>
	</tr>
	  <?php
	  $c = 0;
	   foreach($filters as $filter) { ?>
	    <tr class="fabrik_row oddRow<?php echo ($c % 2);?>">
	    	<td><?php echo $filter->label?> </td>
	    	<td style="text-align:right;"><?php echo $filter->element?></td>
	  <?php
	     $c ++;
	   }
	  ?>
	  </tbody>
	  <thead><tr><th colspan='2'><?php echo $table?></th></tr></thead>
	  <tfoot><tr><th colspan='2' style="text-align:right;">
	  <?php # needed when rendered as a J content plugin - otherwise it defaults to 1 each time?>
	  <input type="hidden" name="clearfilters" value="0" />
	  <input type="hidden" name="resetfilters" value="0" />
	  <input type="submit" class="button" value="<?php echo JText::_('GO')?>" />
	  </th></tr></tfoot></table>
	  <?php
  }
}
?>
</form>
<?php }?>