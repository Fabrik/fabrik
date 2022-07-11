<?php
/**
 * List clear filters button layout
 */

defined('JPATH_BASE') or die;

$d = $displayData;
$title = '<span>' . FText::_('COM_FABRIK_CLEAR') . '</span>';
$opts = array('alt' => FText::_('COM_FABRIK_CLEAR'), 'class' => 'fabrikTip', 'opts' => '{"notice":true}', 'title' => $title);
$img = FabrikHelperHTML::image('filter_delete.png', 'list', $d->tmpl, $opts);

?>
<a href="#" class="clearFilters"><?php echo $img; ?></a>