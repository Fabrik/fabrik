<?php
/**
 * Bootstrap Form Template - buttons
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

if ($this->showEmail || $this->showPDF || $this->showPrint): ?>
	<div class="pull-right">
	<?php
	if ($this->showPrint):
		echo $this->printLink;
	endif;

	if ($this->showEmail):
		echo $this->emailLink;
	endif;

	if ($this->showPDF):
		echo $this->pdfLink;
	endif;
	?>
	</div>
<?php
endif;
