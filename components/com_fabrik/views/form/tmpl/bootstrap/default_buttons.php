<?php
/**
 * Bootstrap Form Template - buttons
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

if ($this->showEmail || $this->showPDF || $this->showPrint): ?>
	<div class="pull-right">
	<?php
	if ($this->showPrint):?>
		<a class="btn" data-fabrik-print href="<?php echo $this->printURL?>">
			<i class="icon-print"></i>
			<?php echo FText::_('JGLOBAL_PRINT')?>
		</a>
	<?php endif;

	if ($this->showEmail): ?>
		<a class="btn fabrikWin" rel='{"title":"<?php echo FText::_('JGLOBAL_EMAIL'); ?>", "loadMethod":"iframe", "height":"300px"}' href="<?php echo $this->emailURL?>">
			<i class="icon-envelope"></i>
			<?php echo FText::_('JGLOBAL_EMAIL'); ?>
		</a>
	<?php endif;

	if ($this->showPDF):?>
		<a class="btn" href="<?php echo $this->pdfURL?>">
			<i class="icon-file"></i>
			<?php echo FText::_('COM_FABRIK_PDF')?>
		</a>
	<?php endif;
	?>
	</div>
<?php
endif;
