<?php
defined('JPATH_BASE') or die;

$d          = $displayData;
$attributes = array();

foreach ($d->attributes as $key => $val)
{
	$attributes[] = $key . '="' . $val . '" ';
}

$attributes = implode("\n", $attributes);
?>

<?php
if (!$d->isEditable) :
	if ($d->hidden) :
		echo '<!-- ' . $d->value . ' -->';
	else :
		echo $d->value;
	endif;
else :
	?>

	<input <?php echo $attributes; ?>
		value="<?php echo $d->value; ?>"
		/>

<?php
endif;
