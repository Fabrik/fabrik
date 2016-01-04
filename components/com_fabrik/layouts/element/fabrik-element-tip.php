<?php
defined('JPATH_BASE') or die;

$d = $displayData;

if ($d->tipTitle !== '') {
?>
	<span class="fabrikTip" title="<?php echo $d->tipTitle; ?>" opts='<?php echo json_encode($d->tipOpts);?>'><?php echo $d->tipText; ?></span>
<?php
}
else {
	echo $d->tipText;
}