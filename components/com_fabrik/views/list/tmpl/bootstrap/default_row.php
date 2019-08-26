<?php
/**
 * Fabrik List Template: Admin Row
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$rowintro = $this->rowintro;

if ($rowintro != '' && $this->_row->data->$rowintro != '')
{ ?>
	<tr id="<?php echo $this->_row->id . '_intro';?>" class="<?php echo $this->_row->class;?>">
	<td colspan="<?php echo count($this->headings);?>"><?php echo $this->_row->data->$rowintro;?></td>
	</tr>
<?php } ?>

<tr id="<?php echo $this->_row->id;?>" class="<?php echo $this->_row->class;?>">
	<?php foreach ($this->headings as $heading => $label) {
		$style = empty($this->cellClass[$heading]['style']) ? '' : 'style="'.$this->cellClass[$heading]['style'].'"';
		?>
		<td class="<?php echo $this->cellClass[$heading]['class']?>" <?php echo $style?>>
			<?php echo isset($this->_row->data) ? $this->_row->data->$heading : '';?>
		</td>
	<?php }?>
</tr>
