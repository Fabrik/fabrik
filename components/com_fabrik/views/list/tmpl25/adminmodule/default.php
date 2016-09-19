<?php
/**
 * Fabrik List Template: AdminModule
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$pageClass = $this->params->get('pageclass_sfx', '');
if ($pageClass !== '') :
	echo '<div class="' . $pageClass . '">';
endif;
?>
<form class="fabrikForm" action="<?php echo $this->table->action;?>" method="post" id="<?php echo $this->formid;?>" name="<?php echo $this->formid;?>">

<?php
/*
 * For some really ODD reason loading the headings template inside the group
* template causes an error as $this->_path['template'] doesnt cotain the correct
* path to this template - go figure!
*/
$this->headingstmpl = $this->loadTemplate('headings');
?>

<div class="fabrikDataContainer" style="<?php echo $this->tableStyle?>">
<div class="emptyDataMessage" style="<?php echo $this->emptyStyle?>"><?php echo $this->emptyDataMessage; ?></div>
<?php foreach ($this->pluginBeforeList as $c) :
	echo $c;
endforeach;?>
	<div class="boxflex">
<?php
if ($this->showFilters) :
	echo $this->loadTemplate('filter');
endif;
?>
	<table class="fabrikList" id="list_<?php echo $this->table->renderid;?>" >

	<?php
	$gCounter = 0;
	foreach ($this->rows as $groupedby => $group) :
		if ($gCounter == 0) :
			echo '<thead>'.$this->headingstmpl.'</thead>';
		endif;
		if ($this->isGrouped) :
				$this->groupHeading = $this->grouptemplates[$groupedby] . ' ( ' . count($group) . ' )';
				echo $this->loadTemplate('group_heading');
			endif;?>
			<tbody class="fabrik_groupdata">
<?php
			foreach ($group as $this->_row) :
				echo $this->loadTemplate('row');
		 	endforeach;
		 	?>
		<?php if ($this->hasCalculations) : ?>
				<tr class="fabrik_calculations">
				<?php
				foreach ($this->calculations as $cal) {
					echo "<td>";
					echo array_key_exists($groupedby, $cal->grouped ) ? $cal->grouped[$groupedby] : $cal->calc;
					echo  "</td>";
				}
				?>
				</tr>

			<?php endif; ?>
			</tbody>
			<?php $gCounter++;
			endforeach;

			$this->showGroup = false;
			for ($x = $gCounter; $x < $this->limitLength; $x ++) :
				$this->groupHeading = 'hidden ' . $x;
				echo $this->loadTemplate('group_heading');
				echo '<tbody class="fabrik_groupdata" style="display:none"></tbody>';
			endfor;
			?>
		</table>
		<?php print_r($this->hiddenFields);?>
	</div>
</div>
<?php echo $this->loadTemplate('buttons');?>
</form>
<?php
echo $this->table->outro;
if ($pageClass !== '') :
	echo '</div>';
endif;
?>
