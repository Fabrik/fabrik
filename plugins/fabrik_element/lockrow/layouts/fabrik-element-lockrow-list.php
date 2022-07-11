<?php

defined('JPATH_BASE') or die;

$d = $displayData;
?>
<div class="<?php echo $d->class; ?>">
	<?php
		echo FabrikHelperHTML::image($d->icon, 'list', $d->tmpl, array('title' => $d->alt, 'style' => 'font-size:18px'));
	?>
</div>
