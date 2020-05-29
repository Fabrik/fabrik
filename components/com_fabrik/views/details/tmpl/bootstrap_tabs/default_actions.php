<?php
/**
 * Bootstrap Tabs Form Template - actions
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$form = $this->form;
if ($this->hasActions) : ?>
<div class="fabrikActions form-actions">
	<div class="row-fluid">
		<div class="span4 btn-group">
			<?php
			echo $form->submitButton. ' ';
			echo $form->applyButton . ' ';
			echo $form->copyButton;
			?>
		</div>
		<?php if ($form->gobackButton . $form->resetButton . $form->deleteButton !== '') : ?>
		<div class="span4"><!-- No Page buttons --></div>
		<div class="span4">
			<div class="pull-right btn-group">
				<?php
				echo $form->gobackButton . ' ' . $this->message;
				echo $form->resetButton . ' ';
				echo $form->deleteButton;
				?>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>
<?php endif;
