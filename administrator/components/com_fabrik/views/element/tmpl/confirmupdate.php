<?php
/**
 * Admin element tmpl: Confirm Update
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\String\StringHelper;

FabrikHelperHTML::formvalidation();
$db = FabrikWorker::getDbo(true);
?>

<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
	<ul style="list-style:none;font-weight:bold;color:#0055BB;background:#C3D2E5;padding:10px;margin-bottom:10px;border-top:3px solid #84A7DB;border-bottom:3px solid #84A7DB">
	<?php if ($db->qn($this->item->name) !== $this->oldName) : ?>
		<li style="padding-left:30px">
			<?php echo JText::sprintf('COM_FABRIK_UPDATE_ELEMENT_NAME', $this->oldName, $db->qn($this->item->name)); ?>
		</li>
	<?php endif; ?>
	<?php if (StringHelper::strtolower($this->origDesc) !== StringHelper::strtolower($this->newDesc)) :?>
  		<li style="padding-left:30px">
  			<?php echo JText::sprintf('COM_FABRIK_UPDATE_ELEMENT_STRUCTURE', $this->oldName, $this->origDesc, $this->newDesc); ?>
  		</li>
 	<?php endif;?>
	</ul>
	<?php echo FText::_('COM_FABRIK_UPDATE_FIELD_STRUCTURE_DESC')?>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="<?php echo $this->item->id?>" />
	<input type="hidden" name="origtask" value="<?php echo $this->origtask?>" />
  	<input type="hidden" name="oldname" value="<?php echo $this->oldName?>" />
	<input type="hidden" name="origplugin" value="<?php echo $this->origPlugin?>" />
  	<?php
	echo JHTML::_('form.token');
	echo JHTML::_('behavior.keepalive');
	?>
</form>
