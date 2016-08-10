<?php
/**
 * Fabrik List Template: Div Row
 * Note the div cell container is now generated in the default template
 * in FabrikHelperHTML::bootstrapGrid();
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<?php foreach ($this->headings as $heading => $label) :
	$d = @$this->_row->data->$heading;
	if (isset($this->showEmpty) && $this->showEmpty === false && trim(strip_tags($d)) == '') :
		continue;
	endif; ?>
	<div class="row-fluid <?php echo $this->cellClass[$heading]['class'] ?>">
		<?php if (isset($this->showLabels) && $this->showLabels) :
			echo '<span class="muted">' . $label . ': </span>';
		endif; ?>

		<?php echo $d ?>
	</div>
	<?php
endforeach;
?>
