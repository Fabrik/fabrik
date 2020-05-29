<?php
/**
 * Bootstrap Details Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<div class="row-striped">
<?php
$rowStarted = false;
foreach ($this->elements as $element) :
	$this->element = $element;
	$this->element->single = $single = $element->startRow && $element->endRow;

	if ($single)
	{
		$this->element->containerClass = str_replace('fabrikElementContainer', '', $this->element->containerClass);
	}

	$element->fullWidth = $element->span == FabrikHelperHTML::getGridSpan(12) || $element->span == '';
	$style = $element->hidden ? 'style="display:none"' : '';

	if ($element->startRow) : ?>
			<div class="row-fluid <?php echo $single ? 'fabrikElementContainer ' : ''; echo $single && $element->dataEmpty ? 'fabrikDataEmpty ' : ''; ?>" <?php echo $style?>><!-- start element row -->
	<?php
		$rowStarted = true;
	endif;
	$style = $element->hidden ? 'style="display:none"' : '';
	$labels_above = $element->dlabels;

	if ($labels_above == 1)
	{
		echo $this->loadTemplate('group_labels_above');
	}
	elseif ($labels_above == 2)
	{
		echo $this->loadTemplate('group_labels_none');
	}
	elseif ($element->span == FabrikHelperHTML::getGridSpan('12') || $element->span == '' || $labels_above == 0)
	{
		echo $this->loadTemplate('group_labels_side');
	}
	else
	{
		// Multi columns - best to use simplified layout with labels above field
		echo $this->loadTemplate('group_labels_above');
	}
	if ($element->endRow) :?>
		</div><!-- end row-fluid -->
	<?php
		$rowStarted = false;
	endif;
endforeach;
// If the last element was not closing the row add an additional div
if ($rowStarted === true) :?>
</div><!-- end row-fluid for open row -->
<?php endif;?>
</div>
