<?php
/**
 * Fabrik List Template: Div Row
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<div id="<?php echo $this->_row->id;?>" class="fabrik_row row-striped <?php echo $this->_row->class;?>" >
	<?php foreach ($this->headings as $heading => $label) :
		$d = @$this->_row->data->$heading;
		if ($this->showEmpty === false  && trim(strip_tags($d == ''))) :
			continue;
		endif;?>
		<div class="row-fluid <?php echo $this->cellClass[$heading]['class']?>">
			<?php if ($this->showLabels) :
			echo '<span class="muted">' . $label . ': </span>';
			endif;?>

			<?php echo $d?>
		</div>
	<?php
	endforeach;
	?>
</div>