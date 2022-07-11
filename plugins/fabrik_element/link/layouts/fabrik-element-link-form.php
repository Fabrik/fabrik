<?php
defined('JPATH_BASE') or die;

$d          = $displayData;

$linkAttributes = array();
$labelAttributes = array();

foreach ($d->linkAttributes as $key => $val)
{
	$linkAttributes[] = $key . '="' . $val . '" ';
}

$linkAttributes = implode("\n", $linkAttributes);


foreach ($d->labelAttributes as $key => $val)
{
	$labelAttributes[] = $key . '="' . $val . '" ';
}

$labelAttributes = implode("\n", $labelAttributes);

?>

<div class="fabrikSubElementContainer" id="<?php echo $d->id;?>">
	<input type="text" <?php echo $linkAttributes;?> />
	<input type="text" <?php echo $labelAttributes;?> />

</div>
