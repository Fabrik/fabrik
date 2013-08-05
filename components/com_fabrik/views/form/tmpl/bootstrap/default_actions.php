<?php
/**
 * Bootstrap Form Template - Actions
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

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
				<?php echo $form->prevButton . ' ' . $form->nextButton; ?>
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