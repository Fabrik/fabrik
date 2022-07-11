<?php
defined('JPATH_BASE') or die;

$d = $displayData;
?>

<?php
if ($d->href !== '') :
    echo $d->graphApi;
	?>
    <div class="fb-comments"
         data-href="<?php echo $d->href; ?>"
         data-numposts="<?php echo $d->num;?>"
         width="<?php echo $d->width;?>"
		 <?php echo $d->colour; ?>
    >
    </div>
<?php
else :
	?>
	<?php echo FText::_('PLG_ELEMENT_FBCOMMENT_AVAILABLE_WHEN_SAVED');?>
<?php
endif;
