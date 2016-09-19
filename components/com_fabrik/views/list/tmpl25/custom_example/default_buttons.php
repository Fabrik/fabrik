<?php
/**
 * Fabrik List Template: Custom Example Buttons
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<?php if($this->showAdd) {?>
	<span class="addbutton" id="<?php echo $this->addRecordId;?>">
		<a href="<?php echo $this->addRecordLink;?>"><?php echo FText::_('ADD');?></a>
	</span>
<?php }?>

<?php if($this->showCSV) {?>
	<span class="csvExportButton" id="fabrikExportCSV">
		<a href="#"><?php echo FText::_('EXPORT TO CSV');?></a>
	</span>
<?php }?>

<?php if($this->showCSVImport) {?>
	<span class="csvImportButton" id="fabrikImportCSV">
		<a href="<?php echo $this->csvImportLink;?>"><?php echo FText::_('IMPORT FROM CSV');?></a>
	</span>
<?php }?>

<?php if($this->showRSS == 'sdfsd') {?>
	<span class="feedButton" id="fabrikShowRSS">
		<a href="<?php echo $this->rssLink;?>"><?php echo FText::_('SUBSCRIBE RSS');?></a>
	</span>
<?php }?>

<?php if($this->showPDF) {
echo $this->pdfLink;
}?>