<?php
/**
 * Fabrik Coverflow Viz - default filter tmpl
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.coverflow
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

if ($this->showFilters) :
?>
<form method="post" action="" name="filter">
<?php
	foreach ($this->filters as $table => $filters) :
		if (!empty($filters)) :
		?>
	<table class="filtertable fabrikList">
		<tbody>
			<tr>
				<th style="text-align:left">
					<?php echo FText::_('SEARCH'); ?>:
				</th>
				<th style="text-align:right">
					<a href="#" class="clearFilters"><?php echo FText::_('CLEAR'); ?></a>
				</th>
			</tr>
			<?php
			$c = 0;
			foreach ($filters as $filter) :
			?>
			<tr class="fabrik_row oddRow<?php echo ($c % 2); ?>">
		    	<td>
		    		<?php echo $filter->label ?>
		    	</td>
		    	<td style="text-align:right;">
		    		<?php echo $filter->element ?>
		    	</td>
		 	 <?php
				$c++;
			endforeach;
			?>
		</tbody>
		<thead>
			<tr>
		  		<th colspan="2">
		  			<?php echo FText::_($table) ?>
		  		</th>
		  	</tr>
		</thead>
		<tfoot>
			<tr>
		  		<th colspan="2" style="text-align:right;">

					<?php // Needed when rendered as a J content plugin - otherwise it defaults to 1 each time ?>
					<input type="hidden" name="clearfilters" value="0" />
					<input type="hidden" name="resetfilters" value="0" />
					<input type="submit" class="button" value="<?php echo FText::_('GO') ?>" />
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
