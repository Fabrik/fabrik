<?php
$form = $this->form;
if ($this->hasActions) : ?>
<div class="fabrikActions form-actions">
	<div class="row-fluid">
		<div class="span4">
			<div class="btn-group">
			<?php
			echo $form->submitButton;
			echo $form->applyButton;
			echo $form->copyButton;
			?>
			</div>
		</div>
		<?php if ($form->gobackButton . $form->resetButton . $form->deleteButton !== '') : ?>
		<div class="span4">
			<div class="btn-group">
				<?php
				echo $form->gobackButton  . ' ' . $this->message;
				echo $form->resetButton . ' ';
				echo $form->deleteButton;
				?>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>
