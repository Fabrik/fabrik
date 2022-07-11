<?php
/**
 * List advanced search button layout
 */

defined('JPATH_BASE') or die;

$d = $displayData;
$title = '<span>' . FText::_('COM_FABRIK_ADVANCED_SEARCH') . '</span>';
$opts = array('alt' => FText::_('COM_FABRIK_ADVANCED_SEARCH'), 'class' => 'fabrikTip', 'opts' => '{"notice":true}', 'title' => $title);

$img = FabrikHelperHTML::image('find', 'list', $d->tmpl, $opts);

?>
<a href="<?php echo $d->url; ?>" class="advanced-search-link"><?php echo $img;?></a>