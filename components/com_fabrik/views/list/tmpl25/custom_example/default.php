<?php
/**
 * Fabrik List Template: Custom Example
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
<div class="emptyDataMessage" style="<?php echo $this->emptyStyle?>"><?php echo $this->emptyDataMessage; ?></div>
<form class="fabrikForm" action="<?php echo $this->table->action;?>" method="post" id="<?php echo $this->formid;?>" name="fabrikTable">
<?php if ($this->params->get('show-title', 1)) :?>
	<div class="page-header">
	<h1 class="fabrikTableHeading"><?php echo $this->table->label;?></h1>
	</div>
<?php endif;?>
<?php echo $this->table->intro;?>

<div class="row-fluid">
	<div class="span3">

		<?php echo $this->loadTemplate('buttons');
		if ($this->showFilters) {
			echo $this->loadTemplate('filter');
		}?>

	</div>

	<div class="span9">
	<?php
		foreach ($this->rows as $groupedby => $group) :
			if ($this->isGrouped) :
				echo $this->grouptemplates[$groupedby];
			endif;
			?>
		<div class="fabrikTable" id="table_<?php echo $this->table->id;?>" >
			<?php
				foreach ($group as $this->_row) :
					echo $this->loadTemplate('row');
			 	endforeach;
			 	?>
		</div>
	<?php endforeach; ?>
<?php
print_r($this->hiddenFields);
?>
	</div>
</div>




<div class="form-actions">
		<?php echo $this->nav; ?>
			<div class="fabrikButtons">
				<?php
				if ($this->canDelete) {
				 echo $this->deleteButton;
				}
				?>
				<?php
	if ($this->emptyLink) {?>
		<li>
		<a href="<?php echo $this->emptyLink?>" class="doempty">
		<?php echo $this->buttons->empty;?>
		<span><?php echo FText::_('COM_FABRIK_EMPTY')?></span>
		</a>
		</li>
	<?php }?>
			</div>
		</div>



</form>

<?php
echo $this->table->outro;
if ($pageClass !== '') :
	echo '</div>';
endif;
?>
