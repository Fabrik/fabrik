<?php

defined('JPATH_BASE') or die;

$d = $displayData;

$pw1Attributes = array();
$pw2Attributes = array();

foreach ($d->pw1Attributes as $key => $val)
{
	$pw1Attributes[] = $key . '="' . $val . '" ';
}

$pw1Attributes = implode("\n", $pw1Attributes);


foreach ($d->pw2Attributes as $key => $val)
{
	$pw2Attributes[] = $key . '="' . $val . '" ';
}

$pw2Attributes = implode("\n", $pw2Attributes);


?>
<input <?php echo $pw1Attributes; ?>  />

<?php
if ($d->showStrengthMeter) :
	if ($d->j3) :
		?>
		<div class="strength progress progress-striped <?php echo $d->bootstrapClass; ?>" style="margin-left:6px; <?php echo $d->extraStyle; ?>"></div>
	<?php
	else :
		?>
		<span class="strength"></span>
	<?php
	endif;
endif;
?>

<input <?php echo $pw2Attributes; ?>"  />
