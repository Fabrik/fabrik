<?php
/**
 * Admin List Linked Elements Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$form = $this->formTable;
?>
<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">

<h3><?php echo FText::_('COM_FABRIK_FORM'); ?></h3>
<a href="index.php?option=com_fabrik&amp;task=form.edit&amp;id=<?php echo $form->id; ?>">
	<?php echo $form->label?>
</a>
<br />
<h3><?php echo FText::_('COM_FABRIK_ELEMENTS'); ?></h3>
<table style="margin-bottom:50px;" class="adminlist table table-striped" width="100%">
	<thead>
		<tr>
			<th class='title'><?php echo FText::_('COM_FABRIK_ELEMENT'); ?></th>
			<th class='title'><?php echo FText::_('COM_FABRIK_LABEL'); ?></th>
			<th class='title'><?php echo FText::_('COM_FABRIK_GROUP'); ?></th>
		</tr>
	</thead>
	<tbody>
<?php
$k = 1;
foreach ($this->formGroupEls as $el) :
$cid = $el->id;?>
		<tr class="sectiontableentry<?php echo $k . ' row' . $k?>">
 			<td>
 				<a href="index.php?option=com_fabrik&amp;task=element.edit&amp;id=<?php echo $cid; ?>">
 					<?php echo $el->name; ?>
 				</a>
 			</td>
    		<td><?php echo $el->label; ?></td>
    		<td>
    			<a href="index.php?option=com_fabrik&amp;task=group.edit&amp;id=<?php echo $el->group_id; ?>">
    				<?php echo $el->group_name; ?>
    			</a>
    		</td>
    	</tr>
<?php
$k = 1 - $k;
endforeach;?>
	</tbody>
</table>

 	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>
