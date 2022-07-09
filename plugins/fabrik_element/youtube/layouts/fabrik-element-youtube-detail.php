<?php

defined('JPATH_BASE') or die;

$d      = $displayData;
$uri    = JUri::getInstance();
$scheme = $uri->getScheme();

if ($d->type === 'youtube') :
?>

<iframe
	type="text/html"
	width="<?php echo $d->width;?>"
	height="<?php echo $d->height; ?>"
    	src="<?php echo $d->url; ?><?php echo $d->vid; ?>?autoplay=<?php echo $d->autoplay; ?>&fs=<?php echo $d->fs; ?>"
    	frameborder="0"
	<?php if ($d->fs) {echo 'allowfullscreen';} ?>
></iframe>
<?php
elseif ($d->type === 'twitchclip' || $d->type === 'twitchvideo') :
?>
	<iframe
		src="<?php echo $d->url; ?><?php echo $d->vid; ?>"
		width="<?php echo $d->width;?>"
		height="<?php echo $d->height; ?>"
		frameborder="0"
		scrolling="no"
		allowfullscreen="true">
	</iframe>
<?php
elseif ($d->type === 'streamable') :
	?>
	<div style="width:<?php echo $d->width;?>;height:0px;position:relative;padding-bottom:56.250%;">
		<iframe
			src="<?php echo $d->url; ?><?php echo $d->vid; ?>"
			frameborder="0"
			width="100%"
			height="100%"
			allowfullscreen
			style="width:100%;height:100%;position:absolute;left:0px;top:0px;overflow:hidden;">
		</iframe>
	</div>
<?php
endif;
?>
