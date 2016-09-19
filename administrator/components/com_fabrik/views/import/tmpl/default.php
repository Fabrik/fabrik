<?php
/**
 * Admin Import Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

JHtml::_('behavior.tooltip');
FabrikHelperHTML::formvalidation();
$app = JFactory::getApplication();
$input = $app->input;
$input->set('hidemainmenu', true);
?>

<form enctype="multipart/form-data" action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
<div class="width-100 fltlft">
	<?php
$id = $input->getInt('listid', 0); // from list data view in admin
$cid = $input->get('cid', array(0), 'array');// from list of lists checkbox selection
$cid = ArrayHelper::toInteger($cid);

	if ($id === 0)
{
	$id = $cid[0];
}
if (($id !== 0))
{
	$db = FabrikWorker::getDbo(true);
	$query = $db->getQuery(true);
	$query->select('label')->from('#__{package}_lists')->where('id = ' . $id);
	$db->setQuery($query);
	$list = $db->loadResult();
}
$fieldsets = array('details');
$fieldsets[] = $id === 0 ? 'creation' : 'append';
$fieldsets[] = 'format';
	?>
		<input type="hidden" name="listid" value="<?php echo $id; ?>" />
<?php foreach ($fieldsets as $fieldset)
{ ?>
	<fieldset class="adminform">
		<ul>
		<?php foreach ($this->form->getFieldset($fieldset) as $field) : ?>
			<li>
				<?php echo $field->label; ?><?php echo $field->input; ?>
			</li>
			<?php endforeach; ?>
		</ul>
	</fieldset>
	<?php } ?>

	<input type="hidden" name="task" value="" />
  	<?php echo JHTML::_('form.token');
echo JHTML::_('behavior.keepalive');
	  ?>
	</div>
</form>