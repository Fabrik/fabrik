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
		<div class="<?php echo FabrikHelperHTML::getGridSpan(4); ?> btn-group">
			<?php
			echo $form->submitButton. ' ';
			echo $form->applyButton . ' ';
			echo $form->copyButton;
			?>
		</div>
		<?php if ($form->customButtons): ?>
			<div class="fabrikCustomButtons <?php echo FabrikHelperHTML::getGridSpan(2); ?>">
				<div class="btn-group">
				<?php echo $form->customButtons; ?>
				</div>
			</div>
		<?php endif; ?>
		<?php if ( $form->gobackButton || $form->resetButton || $form->deleteButton || $form->clearMultipageSessionButton): ?>
		<div class="<?php echo FabrikHelperHTML::getGridSpan(2); ?>"><!-- No Page buttons --></div>
		<div class="<?php echo FabrikHelperHTML::getGridSpan(4); ?>">
			<div class="pull-right btn-group">
				<?php
				echo $form->gobackButton;
				echo $form->resetButton;
				echo $form->deleteButton;
				echo $form->clearMultipageSessionButton;
				?>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>
<?php endif;
