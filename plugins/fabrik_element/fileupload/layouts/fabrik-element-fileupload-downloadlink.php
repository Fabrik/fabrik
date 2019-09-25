<?php
defined('JPATH_BASE') or die;

$d = $displayData;

$class = $d->downloadImg !== '' ? '' : 'class="btn btn-primary button"';

?>

<?php if (!$d->canDownload) :
    $noImg = ($d->noAccessImage === '' || !JFile::exists(JPATH_ROOT . '/media/com_fabrik/images/' . $d->noAccessImage));
	$aClass = $noImg ? 'class="btn button"' : '';

    if (!empty($d->noAccessURL)) :
        ?>
        <a href="<?php echo $d->noAccessURL; ?>" <?php echo $aClass; ?>>
    <?php
    endif;

    if ($noImg) :
        ?>
		<i class="icon-play-circle"></i><?php echo FText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD_NO_PERMISSION'); ?>
    <?php
    else :
    ?>
        <img src="<?php echo $d->noAccessImage;?>"
            alt="<?php echo FText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD_NO_PERMISSION'); ?>" />
		<?php
	endif;

	if (!empty($d->noAccessURL)) :
        ?>
        </a>
    <?php
    endif;
	else :?>
<a href="<?php echo $d->href;?>" <?php echo $class; ?>>
	<?php if ($d->downloadImg !== '') : ?>
		<img src="<?php echo $d->downloadImg;?>" alt="<?php echo $d->title;?>" />
	<?php else :?>
		<?php echo FabrikHelperHTML::icon('icon-download icon-white'); ?>
        <span><?php echo FText::_('PLG_ELEMENT_FILEUPLOAD_DOWNLOAD'); ?></span>
	<?php endif; ?>
</a>
<?php endif; ?>