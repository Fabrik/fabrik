<?php
/**
 * Email list plugin default template
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.email
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
$params = $this->params;

?>
<form method="post" enctype="multipart/form-data" action="<?php echo JUri::base(); ?>index.php" name="emailtable" id="emailtable">
	<div class="alert alert-info">
		<?php echo FabrikHelperHTML::icon('icon-envelope'); ?><?php echo JText::plural('PLG_LIST_EMAIL_N_RECORDS', $this->recordcount) ?>
	</div>
	<div class="row-fluid">

		<?php
		if ($this->showToField)
		{
			?>
			<div class="span12">
				<label>
					<?php echo FText::_('PLG_LIST_EMAIL_TO') ?>
				</label>
			</div>
			<?php
			if ($this->toType == 'field')
			{
				$to = $this->emailTo;
				?>
				<div class="span12">
					<?php
					switch ($params->get('emailtable_email_to_field_how', 'readonly'))
					{
						case 'editable':
							echo '<input type="text" name="list_email_to" id="list_email_to" value="' . $to . '" />';
							break;
						case 'hidden':
							echo '<input name="list_email_to" id="list_email_to" value="' . $to . '" type="hidden" />';
							break;
						case 'readonly':
						default:
							echo '<input type="text" name="list_email_to" id="list_email_to" value="' . $to . '" readonly="readonly" />';
							break;
					}
					?>
				</div>
				<?php
			}
			elseif ($this->toType == 'list')
			{
				echo $this->listEmailTo;
			}
			elseif ($this->toType == 'table' || $this->toType == 'table_picklist')
			{
				if (empty($this->addressBook))
				{
					return FText::_('PLG_LIST_EMAIL_TO_TABLE_NO_DATA');
				}

				$attribs = 'class="fabrikinput inputbox input-medium" multiple="multiple" size="5"';
				$empty   = new stdClass;

				if ($this->toType == 'table_picklist')
				{ ?>
					<div class="span12">
						<div class="span6">
							<?php echo JHtml::_('select.genericlist', $this->addressBook, 'email_to_selectfrom[]', $attribs, 'email', 'name', '', 'email_to_selectfrom'); ?>

							<br /><a href="#" class="btn btn-small" id="email_add"><?php echo FabrikHelperHTML::icon('icon-plus'); ?>
								<?php echo FText::_('COM_FABRIK_ADD'); ?> &gt;&gt;
							</a>
						</div>
						<div class="span6">
							<?php echo JHtml::_('select.genericlist', $empty, 'list_email_to[]', $attribs, 'email', 'name', '', 'list_email_to'); ?>
							<br /><a href="#" class="btn btn-small" id="email_remove">&lt;&lt;
								<?php echo FText::_('COM_FABRIK_DELETE'); ?> <?php echo FabrikHelperHTML::icon('icon-delete'); ?></a>
						</div>
					</div>
					<?php
				}
				else
				{
					echo JHtml::_('select.genericlist', $results, 'list_email_to[]', 'class="fabrikinput inputbox input-large" multiple="multiple" size="5"', 'email', 'name', '', 'list_email_to');
				}
			}
		}
		if ($this->showSubject) :
			?>
			<label>
				<?php echo FText::_('PLG_LIST_EMAIL_SUBJECT') ?><br />
			</label>
			<input class="inputbox fabrikinput span12" type="text" name="subject" id="subject" value="<?php echo $this->subject ?>" size="50" />
			<?php
		endif;
		?>
		<label>
			<?php echo FText::_('PLG_LIST_EMAIL_MESSAGE') ?><br />
		</label>
		<?php
		echo $this->editor;
		?>
		<?php if ($this->allowAttachment) :
			?>
			<div class="attachment">
				<label>
					<?php echo FText::_('PLG_LIST_EMAIL_ATTACHMENTS') ?><br />
					<input class="inputbox fabrikinput" name="attachment[]" type="file" id="attachment" />
				</label>
				<a href="#" class="addattachment">
					<?php echo FabrikHelperHTML::image('plus.png', 'form', @$this->tmpl, FText::_('COM_FABRIK_ADD')); ?>
				</a>
				<a href="#" class="delattachment">
					<?php echo FabrikHelperHTML::image('minus-sign.png', 'form', @$this->tmpl, FText::_('COM_FABRIK_DELETE')); ?>
				</a>
			</div>
			<?php
		endif;
		?>
		<div class="form-actions">
			<input type="submit" id="submit" value="<?php echo FText::_('PLG_LIST_EMAIL_SEND') ?>" class="button btn btn-primary" />
		</div>
		<input type="hidden" name="option" value="com_fabrik" />
		<input type="hidden" name="controller" value=list.email />
		<input type="hidden" name="task" value="doemail" />
		<input type="hidden" name="tmpl" value="component" />
		<input type="hidden" name="renderOrder" value="<?php echo $this->renderOrder ?>" />
		<input type="hidden" name="id" value="<?php echo $this->listid ?>" />
		<input type="hidden" name="recordids" value="<?php echo $this->recordids ?>" />
		<?php
		if (!$this->showToField) :
			echo $this->fieldList;
		endif;
		if (!$this->showSubject) :
			?>
			<input type="hidden" name="subject" id="subject" value="<?php echo $this->subject ?>" />
			<?php
		endif;
		?>
	</div>
</form>
