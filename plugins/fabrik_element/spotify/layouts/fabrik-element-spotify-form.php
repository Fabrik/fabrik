<?php

defined('JPATH_BASE') or die;

$d = $displayData;
?>

<input <?php
	foreach ($d->attributes as $key => $val) : ?>
	<?php echo $key . '="' . $val . '" ';?>
<?php
endforeach; ?>
/>
