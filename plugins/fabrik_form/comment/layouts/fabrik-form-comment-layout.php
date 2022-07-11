<?php
defined('JPATH_BASE') or die;
$d = $displayData;
?>
<div id="fabrik-comments">
	<h3>
		<a href="#" name="comments">
			<?php
			if ($d->commentCount === 0) :
				echo FText::_('PLG_FORM_COMMENT_NO_COMMENTS');
			else:
				if ($d->showCountInTitle) :
					$data[] = $d->commentCount . ' ';
				endif;

				echo FText::_('PLG_FORM_COMMENT_COMMENTS');
			endif;
			?>
		</a>
	</h3>
<?php
echo $d->commnents;

if (!$d->commentsLocked) :
	if (!$d->userLoggedIn && $d->allowGuest == 0) :
		?>
		<h3><?php echo FText::_('PLG_FORM_COMMENT_PLEASE_SIGN_IN_TO_LEAVE_A_COMMENT'); ?></h3>
	<?php
	else :
		?>
		<h3><?php echo FText::_('PLG_FORM_COMMENT_ADD_COMMENT') ;?></h3>
	<?php
	endif;
	echo $d->form;

endif;
?>
</div>