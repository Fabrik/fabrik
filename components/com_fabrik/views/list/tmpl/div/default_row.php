<?php
/**
 * Fabrik List Template: Div Row
 * Note the div cell container is now generated in the default template
 * in FabrikHelperHTML::bootstrapGrid();
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<?php foreach ($this->headings as $heading => $label) :
	$d = @$this->_row->data->$heading;
	if (isset($this->showEmpty) && $this->showEmpty === false && trim(strip_tags($d)) == '') :
		continue;
	endif;
	$h = $this->headingClass[$heading];
	$c = $this->cellClass[$heading];
	$hStyle = empty($h['style']) ? '' : 'style="' . $h['style'] . '"';
	$cStyle = empty($c['style']) ? '' : 'style="'. $c['style'].'"';
	?>
    <div class="row-fluid">
		<?php if (isset($this->showLabels) && $this->showLabels) :
			echo '<span class="muted ' . $h['class'] . '" ' . $hStyle . '>' . $label . ': </span>';
		endif; ?>

		<?php echo '<span class="' . $c['class'] . '" ' . $cStyle . '>' . $d . '</span>'; ?>
	</div>
	<?php
endforeach;
?>
