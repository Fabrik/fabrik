<?php
/**
 * Bootstrap Details Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       3.1
 */

$form = $this->form;
if ($this->hasActions) : ?>
<div class="fabrikActions form-actions">
	<div class="row-fluid">
		<div class="span12">
			<div class="btn-group">
				<?php echo $form->nextButton . ' ' . $form->prevButton;
				echo $form->gobackButton  . ' ' . $this->message;
				?>
			</div>
		</div>
	</div>
</div>
<?php
endif;