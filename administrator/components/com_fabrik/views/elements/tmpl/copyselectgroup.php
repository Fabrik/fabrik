<?php
/**
 * Admin Elements Copy Element to Group Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.framework', true);

?>

<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
	<table class="adminlist">
	<tbody>
		<?php for ($i = 0; $i < count($this->items); $i++) :
		$element = $this->items[$i];?>
  		<tr>
  		<td>
  			<input type="text" value="<?php echo $element->name?>" name="name[<?php echo $element->id?>]">
  		</td>
  		<td>
	  		<select id="copy-<?php echo $element->id?>" name="cid[<?php echo $element->id;?>]">
 						<?php foreach ($this->groups as $group) :
						?>
 							<option value="<?php echo $group->id?>"><?php echo $group->name?></option>
 						<?php endforeach;
						?>
 					</select>
 					</td>
  		</tr>
		<?php endfor;
		?>
	</tbody>
	<thead>
	<tr>
		<th><?php echo FText::_('COM_FABRIK_NAME')?></th>
		<th><?php echo FText::_('COM_FABRIK_COPY_TO_GROUP')?></th>
	</tr>
	</thead>
	</table>
	<input type="hidden" name="task" value="" />
  	<?php echo JHTML::_('form.token');
	echo JHTML::_('behavior.keepalive'); ?>
</form>