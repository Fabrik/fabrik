<?php
/**
 * Bootstrap Details Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<div class="row-striped">
<?php
foreach ($this->elements as $element) :
	$this->element = $element;
	$element->fullWidth = $element->span == 'span12' || $element->span == '';
	$style = $element->hidden ? 'style="display:none"' : '';
	if ($element->startRow) : ?>
			<div class="row-fluid" <?php echo $style?>><!-- start element row -->
		<?php
		endif;
		if ($this->params->get('labels_above', 0) == 1) :
			echo $this->loadTemplate('group_labels_above');
		else :

			if ($element->fullWidth || $this->params->get('labels_above', 0) == 0) :
				echo $this->loadTemplate('group_labels_side');
			else :

				// Multi columns - best to use simplified layout with labels above field
				echo $this->loadTemplate('group_labels_above');
			endif;
		endif;
		if ($element->endRow) :?>
		</div><!-- end row-fluid -->
	<?php endif;
endforeach;

// If the last element was not closing the row add an additional div (only if elements are in columns
if (!$element->endRow && !$element->fullWidth) :?>
</div><!-- end row-fluid for open row -->
<?php endif;?>
</div>

