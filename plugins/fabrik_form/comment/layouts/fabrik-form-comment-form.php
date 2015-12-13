<?php
defined('JPATH_BASE') or die;
$d = $displayData;
?>
<form action="index.php" <?php echo $d->formId; ?> class="replyform">
	<p>
<?php
if ($d->wysiwyg) :
	echo $d->editor;
else :
?>
		<textarea style="width:95%" rows="6" cols="3" placeholder="<?php echo FText::_('PLG_FORM_COMMENT_TYPE_A_COMMENT_HERE'); ?>"></textarea>
<?php
endif;
?>
	</p>
	<table class="adminForm" style="width:350px" summary="comments">
		<?php
		if (!$d->userLoggedIn) :
			?>
			<tr>
				<td>
					<label for="add-comment-name-<?php echo $d->replyTo; ?>">
						<?php echo FText::_('PLG_FORM_COMMENT_NAME'); ?>
					</label>
					<br />
					<input class="inputbox" type="text" size="20" id="add-comment-name-<?php echo $d->replyTo; ?>"
						name="name" value="<?php echo $d->name; ?>" />
				</td>
				<td>
					<label for="add-comment-email-<?php echo $d->replyTo; ?>">
						<?php echo FText::_('PLG_FORM_COMMENT_EMAIL');?>
					</label>
					<br />
					<input class="inputbox" type="text" size="20" id="add-comment-email-<?php echo $d->replyTo; ?>"
						name="email" value="<?php echo $d->email; ?>" />
				</td>
			</tr>

		<?php
		endif;

		if ($d->notify) :
			?>
			<tr>
				<td colspan="2"><?php echo FText::_('PLG_FORM_COMMENT_NOTIFY_ME'); ?></td>
			</tr>
			<tr>
				<td>

					<label><input type="radio" name="notify[]" checked="checked" class="inputbox" value="0">
						<?php echo FText::_('JNO'); ?>
					</label>
				</td>
				<td>
					<label>
						<input type="radio" name="notify[]" class="inputbox" value="1">
						<?php echo FText::_('JYES'); ?>
					</label>
				</td>
			</tr>
		<?php
		endif;

		if ($d->rating || $d->anonymous) :
			?>
			<tr>
				<td>

					<?php if ($d->rating) :
						?>
						<label for="add-comment-rating-<?php echo $d->replyTo; ?>">
							<?php echo FText::_('PLG_FORM_COMMENT_RATING');?>
						</label>
						<br />
						<select id="add-comment-rating-<?php echo $d->replyTo; ?>" class="inputbox" name="rating">
							<option value="0"><?php echo FText::_('PLG_FORM_COMMENT_NONE'); ?></option>
							<option value="1"><?php echo FText::_('PLG_FORM_COMMENT_ONE'); ?></option>
							<option value="2"><?php echo FText::_('PLG_FORM_COMMENT_TWO'); ?></option>
							<option value="3"><?php echo FText::_('PLG_FORM_COMMENT_THREE'); ?></option>
							<option value="4"><?php echo FText::_('PLG_FORM_COMMENT_FOUR'); ?></option>
							<option value="5"><?php echo FText::_('PLG_FORM_COMMENT_FIVE'); ?></option>
						</select>
					<?php
					endif;
					?>
				</td>
				<td>
					<?php
					if ($d->anonymous) :
						?>
						<?php echo FText::_('Anonymous'); ?><br />
						<label for="add-comment-anonymous-no-<?php echo $d->replyTo; ?>">
							<?php echo FText::_('JNO'); ?>
						</label>
						<input type="radio" id="add-comment-anonymous-no<?php echo $d->replyTo; ?>" name="anonymous[]" checked="checked" class="inputbox" value="0" />
						<label for="add-comment-anonymous-yes-<?php echo $d->replyTo; ?>">
							<?php echo FText::_('JYES'); ?>
						</label>
						<input type="radio" id="add-comment-anonymous-yes-<?php echo $d->replyTo; ?>" name="anonymous[]" class="inputbox" value="1" />
					<?php
					endif;
					?>
				</td>
			</tr>
		<?php
		endif;
		?>

		<tr>
			<td colspan="2">
				<button class="button btn btn-success submit" style="margin-left:0">
					<?php echo FabrikHelperHTML::icon('icon-comments-2'); ?>
					<?php echo FText::_('PLG_FORM_COMMENT_POST_COMMENT'); ?>
				</button>
				<input type="hidden" name="reply_to" value="<?php echo $d->replyTo; ?>" />
				<input type="hidden" name="renderOrder" value="<?php echo $d->renderOrder; ?>" />
			</td>
		</tr>
	</table>
</form>