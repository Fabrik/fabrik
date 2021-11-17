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

if ($this->showFilters)
{ ?>
<form method="post" name="filter">
<?php
	foreach ($this->filters as $table => $filters)
	{
		if (!empty($filters))
		{
		?>
	  <table class="filtertable fabrikList"><tbody>
	  <?php
			$c = 0;
			foreach ($filters as $filter)
			{
			?>
	    <tr class="fabrik_row oddRow<?php echo ($c % 2); ?>">
	    	<td><?php echo $filter->label ?> </td>
	    	<td><?php echo $filter->element ?></td>
	  <?php
				$c++;
			}
	?>
	  </tbody>
	  <thead><tr><th colspan='2'><?php echo FText::_($table) ?></th></tr></thead>
	  <tfoot><tr><th colspan='2' style="text-align:right;">
	  <input type="submit" class="button" value="<?php echo FText::_('GO') ?>" />
	  </th></tr></tfoot></table>
	  <?php
		}
	}
?>
</form>
<?php
}