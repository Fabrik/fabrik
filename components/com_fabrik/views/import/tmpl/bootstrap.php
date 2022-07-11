<?php
/**
 * Admin Import Tmpl
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$url = JRoute::_('index.php');
JHtml::_('behavior.tooltip');
FabrikHelperHTML::formvalidation();
$action = JRoute::_('index.php?option=com_fabrik');
$app = JFactory::getApplication();
$listId = $app->input->getInt('listid');
$itemId   = FabrikWorker::itemId();

?>
<form enctype="multipart/form-data" action="<?php echo $action ?>" method="post" name="adminForm" id="fabrik-form" class="form-validate">
	<input type="hidden" name="listid" value="<?php echo $listId; ?>" />
    <?php
    if (!empty($itemId)) :
    ?>
        <input type="hidden" name="Itemid" value="<?php echo $itemId; ?>" />
    <?php
    endif;
    ?>
	<h2><?php echo JText::sprintf('COM_FABRIK_CSV_IMPORT_HEADING', $this->listName); ?></h2>
	<?php foreach ($this->fieldsets as $fieldset) :
	?>
	<fieldset>
		<?php foreach ($this->form->getFieldSet($fieldset) as $field) :
		?>

		<div class="control-group">
			<div class="control-label">
				<?php echo $field->label; ?>
			</div>
			<div class="controls">
				<?php echo $field->input; ?>
			</div>
		</div>
		<?php endforeach; ?>
	</fieldset>
	<?php endforeach;?>

	<input type="hidden" name="task" value="import.doimport" />
  	<?php echo JHTML::_('form.token');
	echo JHTML::_('behavior.keepalive'); ?>
	<input type="submit" class="btn btn-primary" value="<?php echo FText::_('COM_FABRIK_IMPORT_CSV')?>" />
</form>

<script type="text/javascript">
	/* Hate this here but cheap hack for radio button js code - why o why is this not part of JUX? */
window.addEvent('domready', function () {
	document.id('fabrik-form').getElements(".btn-group input").each(function (input) {
		var label = document.getElement('label[for=' + input.id + ']');
		label.addClass('btn');
		if (input.checked) {
			v = input.get('value');
			if (v === '') {
				label.addClass('active btn-primary');
			} else if (v === '0') {
				label.addClass('active btn-danger');
			} else {
				label.addClass('active btn-success');
			}
		}
	});

	document.id('fabrik-form').addEvent('mouseup:relay(.btn-group label)', function (e, label) {
		var id, input;
		id = label.get('for');
		if (id !== '') {
			input = document.id(id);
		}
		if (typeOf(input) === 'null') {
			input = label.getElement('input');
		}
		this.setButtonGroupCSS(input);
	}.bind(this));

});

function setButtonGroupCSS(input) {
	var label;
	if (input.id !== '') {
		label = document.getElement('label[for=' + input.id + ']');
	}
	if (typeOf(label) === 'null') {
		label = input.getParent('label.btn');
	}
	var v = input.get('value');
	if (!input.get('checked')) {
		label.getParent('.btn-group').getElements('label').removeClass('active').removeClass('btn-success').removeClass('btn-danger').removeClass('btn-primary');
		if (v === '') {
			label.addClass('active btn-primary');
		} else if (v.toInt() === 0) {
			label.addClass('active btn-danger');
		} else {
			label.addClass('active btn-success');
		}
		input.set('checked', true);
	}
}
</script>
