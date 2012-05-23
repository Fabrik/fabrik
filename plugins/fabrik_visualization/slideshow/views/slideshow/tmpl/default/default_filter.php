<?php if ($this->showFilters) {?>
<form method="post">
<?php
foreach ($this->filters as $table => $filters) {
  if (!empty( $filters)) {?>
	  <table class="filtertable fabrikList"><tbody>
	  <?php
	  $c = 0;
	   foreach($filters as $filter) { ?>
	    <tr class="fabrik_row oddRow<?php echo ($c % 2);?>">
	    	<td><?php echo $filter->label?> </td>
	    	<td><?php echo $filter->element?></td>
	  <?php
	     $c ++;
	   }
	  ?>
	  </tbody>
	  <thead><tr><th colspan='2'><?php echo $table?></th></tr></thead>
	  <tfoot><tr><th colspan='2' style="text-align:right;">
	  <input type="submit" class="button" value="<?php echo JText::_('GO')?>" />
	  </th></tr></tfoot></table>
	  <?php
  }
}
?>

</form>
<?php } ?>