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

		<div class="span4">
			<div class="btn-group">
				<?php
				echo $form->gobackButton  . ' ' . $this->message;
				echo $form->resetButton . ' ';
				echo  $form->deleteButton;
				?>
			</div>
		</div>
	</div>
</div>
<?php
endif;