<?php
/**
 * Google Chart default filter tmpl
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.chart
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

if ($this->showFilters) : ?>
<form method="post" name="filter">
<?php
	foreach ($this->filters as $table => $filters) :
		if (!empty($filters)) :
		?>
	<table class="filtertable table table-striped">
		<tbody>
	  	<?php
			$c = 0;
			foreach ($filters as $filter) :
			?>
	    <tr>
	    	<td><?php echo $filter->label ?></td>
	    	<td><?php echo $filter->element ?></td>
	  <?php
			endforeach;
	?>
	</tbody>
	<thead>
		<tr>
			<th colspan="2"><?php echo FText::_($table) ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th colspan="2" style="text-align:right;">
				<?php echo FabrikHelperHTML::icon('icon-filter'); ?>
  				<button type="submit" class="btn btn-primary">
  					<?php echo FText::_('GO') ?>
  				</button>
			</th>
		</tr>
	</tfoot>
</table>
  <?php
		endif;
	endforeach;
?>
</form>
<?php
endif;