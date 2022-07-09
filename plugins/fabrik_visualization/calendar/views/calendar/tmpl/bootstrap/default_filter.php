<?php
/**
 * Calendar Viz: Bootstrap Default Filter
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

if ($this->showFilters) :?>
<form method="post" name="filter" action="">
<?php
	foreach ($this->filters as $table => $filters) :
		if (!empty($filters)) :
		?>
	  <table class="filtertable table table-striped"><tbody>
	  <tr>
		<th style="text-align:left"><?php echo FText::_('PLG_VISUALIZATION_CALENDAR_SEARCH'); ?>:</th>
		<th style="text-align:right"><a href="#" class="clearFilters"><?php echo FabrikHelperHTML::icon('icon-refresh'); ?> <?php echo FText::_('PLG_VISUALIZATION_CALENDAR_CLEAR'); ?></a></th>
	</tr>
	  <?php
			$c = 0;
			foreach ($filters as $filter) :
		 	?>
	    <tr class="fabrik_row oddRow<?php echo ($c % 2); ?>">
	    	<td><?php echo $filter->label ?> </td>
	    	<td><?php echo $filter->element ?></td>
	  <?php
				$c++;
			endforeach;
		?>
	  </tbody>
	  <thead><tr><th colspan='2'><?php echo FText::_($table) ?></th></tr></thead>
	  <tfoot><tr><th colspan='2' style="text-align:right;">
	  <button type="submit" class="btn btn-info">
		  <?php echo FabrikHelperHTML::icon('icon-filter'); ?> <?php echo FText::_('PLG_VISUALIZATION_CALENDAR_GO') ?>
	  </button>
	  </th></tr></tfoot></table>
	  <?php
		endif;
	endforeach;
?>
<input type="hidden" name="resetfilters" value="0" />
</form>
<?php
endif;
