<?php
defined('JPATH_BASE') or die;
/* DEPRECATED
$d = $displayData;
if ($d->height <= 1) :
?>
<span class="fabrikinput fabrikElementReadOnly" style="display:inline-block;" name="<?php echo $d->name;?>" id="<?php echo $d->id;?>"><?php echo $d->value;?></span>
<?php
else : ?>
<textarea class="fabrikinput" disabled="disabled" name="<?php echo $d->name;?>"
	id="<?php echo $d->id;?>" cols="<?php echo $d->cols; ?>"
	rows="<?php echo $d->rows; ?>"><?php echo $d->value;?></textarea>
<?php endif; ?>
*/

/* 06Feb2021 by Bruce Decker - rewrite to translate calc width to bootstrap class*/
$d = $displayData;
if($d->cols <= 15){
	$bootWidth = 'input-small';
} else {
	if($d->cols <= 40){
		$bootWidth = 'input-medium';
	} else{
		if ($d->cols <=60){
			$bootWidth = 'input-large';
		} else {
			$bootWidth = 'input-xlarge';
		}
	} 
}
if ($d->height <= 1){
	$htmlSnip = <<<HTMLSNIP
	<span class="fabrikinput fabrikElementReadOnly" style="display:inline-block;" name="$d->name" id="$d->id" $d->value</span>
	HTMLSNIP;
} else {
	$htmlSnip = <<<HTMLSNIP
	<textarea class="fabrikinput $bootWidth" disabled="disabled" name="$d->name"
	id="$d->id" cols="$d->cols" rows="$d->rows">$d->value</textarea>
	HTMLSNIP;	
}
echo $htmlSnip;
?>
