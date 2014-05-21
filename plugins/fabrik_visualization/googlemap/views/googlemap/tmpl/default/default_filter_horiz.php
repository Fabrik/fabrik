<?php
/**
 * Default Google Map Viz Filter Template
 *
 * @package      Joomla.Plugin
 * @subpackage   Fabrik.visualization.googlemap
 * @copyright    Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license      GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

if ($this->showFilters) :
?>
<form method="post" name="filter" action="<?php echo $this->filterFormURL; ?>">
<?php
	foreach ($this->filters as $table => $filters) :
		if (!empty($filters)) :
		?>
		<table class="filtertable fabrikTable fabrikList">
		<thead>
		<tr>
	  		<?php
			foreach ($filters as $filter) :
				$required = $filter->required == 1 ? ' class="notempty"' : '';
				?>
				<td<?php echo $required; ?>>
					<?php echo $filter->label; ?>
				</td>
				<?php
			endforeach;
			?>
	  	</tr>
	  </thead>

	  <tfoot>
	  	<tr class="fabrik_row1">
	  		<th colspan="<?php echo count($filters) - 1; ?>" style="text-align:right">
	  			<a href="#" class="clearFilters">
	  				<?php echo FText::_('CLEAR'); ?>
	  			</a>
	  		</th>
	  		<th style="text-align:right;">
	  			<input type="submit" class="button" value="<?php echo FText::_('GO') ?>" />
	  		</th>
	  	</tr>
	  </tfoot>

	  <tbody>
	  	<tr class="fabrik_row0">
			<?php
			$c = 0;
			foreach ($filters as $filter) :
			?>
	    	<td>
	    		<?php echo $filter->element ?>
	    	</td>
			<?php
			endforeach;
			?>
		</tr>
	  </tbody>

	</table>
	<?php
	endif;
endforeach;
?>
</form>
<?php
endif;
