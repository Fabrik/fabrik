<?php
/**
 * Fabrik List Template: Div Row
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<div id="<?php echo $this->_row->id;?>" class="<?php echo $this->_row->class;?>">
	<ul>
	<?php foreach ($this->headings as $heading => $label) :
		$style = empty($this->cellClass[$heading]['style']) ? '' : 'style="'.$this->cellClass[$heading]['style'].'"';?>
		<li class="<?php echo $this->cellClass[$heading]['class']?>" <?php echo $style?>>
			<div class="divlabel"><?php echo $label;?>:</div>
			<div class="divelement"><?php echo @$this->_row->data->$heading;?></div>
		</li>
	<?php
	endforeach;
	?>
	</ul>
</div>