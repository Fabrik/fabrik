<?php
/**
 * Fabrik List Template: DB Join Select
 *
 * @deprecated since 3.0.8/3.1 as we now load list defult tmpl, and us js else where to observe row clicks.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// No direct access
defined('_JEXEC') or die;
?>
<?php if ($this->getModel()->getParams()->get('show-title', 1)) {?>
	<h1><?php echo $this->table->label;?></h1>
<?php }?>

<?php echo $this->table->intro;?>
<form class="fabrikForm" action="<?php echo $this->table->action;?>" method="post" id="<?php echo $this->formid;?>" name="fabrikList">

<?php

//for some really ODD reason loading the headings template inside the group
//template causes an error as $this->_path['template'] doesnt cotain the correct
// path to this template - go figure!
$this->headingstmpl =  $this->loadTemplate('headings');
if ($this->showFilters) {
	echo $this->loadTemplate('filter');
}?>
<br style="clear:right" />
<div class="tablespacer"></div>
<div class="emptyDataMessage" style="<?php echo $this->emptyStyle?>"><?php echo $this->emptyDataMessage; ?></div>
<div class="fabrikDataContainer" style="<?php echo $this->tableStyle?>">
<?php foreach ($this->pluginBeforeList as $c) {
			echo $c;
			}?>
<?php
	foreach ($this->rows as $groupedby => $group) {
		if ($this->isGrouped) {
			echo $this->grouptemplates[$groupedby];
		}
		?>
	<table class="fabrikList" id="list_<?php echo $this->table->renderid;?>" >
		<thead>
			<?php echo $this->headingstmpl; ?>
		</thead>
		<tfoot>
			<tr class="fabrik_calculations">
			<?php
			foreach ($this->calculations as $cal) {
				echo "<td>";
				echo array_key_exists($groupedby, $cal->grouped ) ? $cal->grouped[$groupedby] : $cal->calc;
				echo  "</td>";
			}
			?>
			</tr>
		</tfoot>
		<tbody>
		<?php
			foreach ($group as $this->_row) {
				echo $this->loadTemplate('row');
		 	}
		 	?>
		 	</tbody>
	</table>
<?php }
		?>
	<?php
	echo (trim($this->nav) == '') ? "<ul class='pagination'></ul>" : $this->nav;
	//end not empty

print_r($this->hiddenFields);
?>
</div>
</form>
<?php echo $this->table->outro;?>

<?php
FabrikHelperHTML::script('components/com_fabrik/views/list/tmpl/dbjoinselect/javascript.js');
$script = "head.ready(function() {
var trs = new TableRowSelect('".JRequest::getVar('triggerElement') . "', ".(int) $this->form->id.");
});
";
FabrikHelperHTML::addScriptDeclaration($script);

?>