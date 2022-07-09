<?php
defined('JPATH_BASE') or die;

$d = $displayData;
?>

<div class="fb-like"
     data-href="<?php echo $d->url; ?>"
     data-layout="<?php echo $d->layout; ?>"
     data-action="<?php echo $d->action; ?>"
     data-show-faces="<?php echo $d->showfaces;?>"
     data-share="<?php echo $d->includeShare;?>"
     data-width="<?php echo $d->width;?>"
     data-colorscheme="<?php echo $d->colorscheme;?>"
>
</div>