<?php
/**
 * Email list plugin default template
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.email
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<form method="post" enctype="multipart/form-data" action="<?php echo JURI::base();?>index.php" name="emailtable" id="emailtable">
	<div class="alert alert-info">
		<i class="icon-envelope"></i> <?php echo JText::plural('PLG_LIST_EMAIL_N_RECORDS', $this->recordcount) ?>
	</div>
	<?php
	if ($this->showToField)
	{
	?>
	<label>
		<?php echo FText::_('PLG_LIST_EMAIL_TO') ?><br />
	</label>
	<?php echo $this->fieldList ?>
	<?php
	}
	?>
	<?php
	if ($this->showSubject)
	{
	?>
	<label>
		<?php echo FText::_('PLG_LIST_EMAIL_SUBJECT') ?><br />
		<input class="inputbox fabrikinput span12" type="text" name="subject" id="subject" value="<?php echo $this->subject?>" size="50" />
	</label>
	<?php
	}
	?>
	<label>
		<?php echo FText::_('PLG_LIST_EMAIL_MESSAGE') ?><br />
	</label>
	<?php
	echo $this->editor;
	?>
<?php if ($this->allowAttachment)
{?>
	<div class="attachment">
		<label>
			<?php echo FText::_('PLG_LIST_EMAIL_ATTACHMENTS') ?><br />
			<input class="inputbox fabrikinput" name="attachment[]" type="file" id="attachment" />
		</label>
		<a href="#" class="addattachment">
		<?php echo FabrikHelperHTML::image('plus.png', 'form', @$this->tmpl, FText::_('COM_FABRIK_ADD'));?>
		</a>
		<a href="#" class="delattachment">
			<?php echo FabrikHelperHTML::image('minus-sign.png', 'form', @$this->tmpl, FText::_('COM_FABRIK_DELETE'));?>
		</a>
	</div>
	<?php
}
		?>
	<div class="form-actions">
		<input type="submit" id="submit" value="<?php echo FText::_('PLG_LIST_EMAIL_SEND') ?>" class="button btn btn-primary" />
	</div>
	<input type="hidden" name="option" value="com_fabrik" />
	<input type="hidden" name="controller" value=list.email />
	<input type="hidden" name="task" value="doemail" />
	<input type="hidden" name="tmpl" value="component" />
	<input type="hidden" name="renderOrder" value="<?php echo $this->renderOrder?>" />
	<input type="hidden" name="id" value="<?php echo $this->listid ?>" />
	<input type="hidden" name="recordids" value="<?php echo $this->recordids ?>" />
	<?php
	if (!$this->showToField)
	{
		echo $this->fieldList;
	}
	if (!$this->showSubject)
	{
	?>
		<input type="hidden" name="subject" id="subject" value="<?php echo $this->subject?>" />
	<?php
	}
	?>
</form>
<?php if ($this->allowAttachment) {?>
<script type="text/javascript"><!--

function watchAttachements() {
	document.getElements('.addattachment').removeEvents();
	document.getElements('.delattachment').removeEvents();
	document.getElements('.addattachment').addEvent('click', function (e) {
		e.stop();
		var li = e.target.getParent('.attachment');
		li.clone().inject(li, 'after');
		watchAttachements();
	});

	document.getElements('.delattachment').addEvent('click', function (e) {
		e.stop();
		if(document.getElements('.addattachment').length > 1) {
			e.target.getParent('.attachment').dispose();
		}
		watchAttachements();
	});

}

window.addEvent('load', function() {
	watchAttachements();
});
--></script>
<?php }?>
<script type="text/javascript">
window.addEvent('load', function() {
	console.log(list_email_to)
	if (typeOf(document.id('email_add')) !== 'null') {
		document.id('email_add').addEvent('click', function (e) {
			e.stop();
			document.id('email_to_selectfrom').getSelected().each(function (el) {
				el.inject(document.id('list_email_to'));
			});
		});
		document.id('email_remove').addEvent('click', function (e) {
			e.stop();
			document.id('list_email_to').getSelected().each(function (el) {
				el.inject(document.id('email_to_selectfrom'));
			});
		});
	}
});
</script>
