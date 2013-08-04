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
<div id="<?php echo $this->_row->id;?>" class="fabrik_row span6">
	<?php foreach ($this->headings as $heading => $label) : ?>
		<div class="row-fluid">
			<?php echo @$this->_row->data->$heading;?>
		</div>
	<?php
	endforeach;
	?>
</div>