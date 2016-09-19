<?php
/**
 * Fabrik List Template: F3
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

FabrikHelperHTML::script('media/com_fabrik/js/lib/art.js');
FabrikHelperHTML::script('media/com_fabrik/js/icons.js');
FabrikHelperHTML::script('media/com_fabrik/js/icongen.js');

$pageClass = $this->params->get('pageclass_sfx', '');
$filter = JFilterInput::getInstance(array('p'), array(), 1);
$opts = new stdClass;
$opts->listref = 'listform_' . $this->listref;
$opts = json_encode($opts);
$script = "
window.addEvent('fabrik.loaded', function() {
	new FabrikGrid($opts);
});";
FabrikHelperHTML::addScriptDeclaration($script)
?>

<div class="emptyDataMessage" style="<?php echo $this->emptyStyle?>"><?php echo $this->emptyDataMessage; ?></div>
<?php
if ($pageClass !== '') :
	echo '<div class="' . $pageClass . '">';
endif;
?>
<div id="list_<?php echo $this->table->renderid;?>">
<?php
if ($this->params->get('show_page_heading', 1)) { ?>
		<div class="componentheading<?php echo $this->params->get('pageclass_sfx')?>"><?php echo $this->escape($this->params->get('page_heading')); ?></div>
	<?php } ?>
	<?php if ($this->tablePicker != '') { ?>
		<div style="text-align:right"><?php echo FText::_('COM_FABRIK_LIST') ?>: <?php echo $this->tablePicker; ?></div>
	<?php } ?>
	<?php if ($this->getModel()->getParams()->get('show-title', 1)) {?>
		<h1><?php echo $this->table->label;?></h1>
	<?php }?>
	<?php echo $this->table->intro;?>
	<form class="fabrikForm" action="<?php echo $this->table->action;?>" method="post" id="<?php echo $this->formid;?>" name="fabrikList">
	<?php echo $this->loadTemplate('header')?>
	<?php
	//for some really ODD reason loading the headings template inside the group
	//template causes an error as $this->_path['template'] doesnt cotain the correct
	// path to this template - go figure!
	$this->headingstmpl = $this->loadTemplate('headings');
	?>
	<div class="fabrikDataContainer" style="<?php echo $this->tableStyle?>">

	<div class="f3main">
		<?php
		//only for non grouped records - load alt template for grouped records!
		$group = array_shift($this->rows);?>
			<?php foreach ($this->pluginBeforeList as $c) {
			echo $c;
			}?>
		<div class="scroll-x">

		<?php
		 echo $this->headingstmpl;?>

			<div class="scroll-y">

				<ul class="fabrikList list" id="list_<?php echo $this->table->renderid;?>" >
					<?php
					foreach ($group as $this->_row) {
						echo $this->loadTemplate('row');
				 	}
				 	?>
				</ul>
			</div>
		</div>
	</div>
	<?php	echo $this->loadTemplate('footer');?>
</div>
</form>
<?php
echo $this->table->outro;
if ($pageClass !== '') :
	echo '</div>';
endif;
?>
</div>