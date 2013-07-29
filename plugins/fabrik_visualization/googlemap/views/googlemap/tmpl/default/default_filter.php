<?php
/**
 * Default Google Map Viz Filter Template
 *
* @package     Joomla.Plugin
* @subpackage  Fabrik.visualization.googlemap
* @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
* @license     GNU General Public License version 2 or later; see LICENSE.txt
*/

defined('_JEXEC') or die('Restricted access');
?>

<?php if ($this->showFilters) :
?>
<form method="post" name="filter" action="<?php echo $this->filterFormURL; ?>">
<?php
	foreach ($this->filters as $table => $filters) :
		if (!empty($filters)) :
		?>
	  <table class="filtertable fabrikTable fabrikList">

	   <thead>
	  	<tr>
	  		<th><?php echo $table ?></th>
	  		<th style="text-align:right"><a href="#" class="clearFilters"><?php echo JText::_('PLG_VISUALIZATION_GOOGLEMAP_CLEAR'); ?></a></th>
	  	</tr>
	  </thead>

	  <tfoot>
	  	<tr>
	  		<th colspan="2" style="text-align:right;">
	  			<input type="submit" class="fabrik_filter_submit button" value="<?php echo JText::_('PLG_VISUALIZATION_GOOGLEMAP_GO') ?>" />
	  		</th>
	  	</tr>
	  </tfoot>

	  <tbody>
	  <?php
			$c = 0;
			foreach ($filters as $filter) :
				$required = $filter->required == 1 ? ' class="notempty"' : '';
			?>
	    <tr class="fabrik_row oddRow<?php echo ($c % 2); ?>">
	    	<td<?php echo $required ?>><?php echo $filter->label ?> </td>
	    	<td><?php echo $filter->element ?></td>
	    </tr>
	  <?php
				$c++;
			endforeach;
			?>
	  </tbody>

	  </table>
	  <?php
		endif;
	endforeach;
	?>

</form>
<?php
endif;
