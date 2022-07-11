<?php
defined('JPATH_BASE') or die;

$d      = $displayData;
$height = empty($d->height) ? '' : ' height="' . $d->height . 'px" ';
$img    = '<img class="fabrikLightBoxImage" ' . $height . 'src="' . $d->file . '" alt="' . $d->title . '" />';
$nolinkImg    = '<img class="fabrikLightBoxImage" ' . $height . 'src="' . $d->file . '" alt="' . $d->title . '" title="' . $d->title . '" />';

if ($d->showImage == 0 && !$d->inListView) :
	?>
	<a href="<?php echo $d->fullSize; ?>"><?php echo basename($d->file);?></a>
<?php
else :
	if ($d->isSlideShow) :
		// We're building a Bootstrap slideshow, just a simple img tag
		?>
		<img height="100%" width="100%" object-fit="contain" src="<?php echo $d->fullSize; ?>" alt="<?php echo $d->title; ?>" style="margin:auto" />
	<?php
	else :
		if ($d->isJoin) :
			?>
			<div class="fabrikGalleryImage"
			style="width:<?php echo $d->width;?>px;height:<?php echo $d->height;?>px; vertical-align: middle;text-align: center;">
		<?php
		endif;

		if ($d->makeLink) :
			?>
			<a href="<?php echo $d->fullSize; ?>" <?php echo $d->lightboxAttrs;?> title="<?php echo $d->title; ?>">
				<?php echo $img;?>
			</a>
		<?php
		else :
			echo $nolinkImg;
		endif;

		if ($d->isJoin) : ?>
			</div>
		<?php
		endif;
	endif;
endif;




