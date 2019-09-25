<?php
defined('JPATH_BASE') or die;

use Fabrik\Helpers\Html;

$d = $displayData;

?>

<div class="row-fluid">
    <?php
    if ($d->headers > 0 && $d->showUser) :
        ?>
    <div class="<?php echo Html::getGridSpan(12 / $d->headers)?>">
        <?php echo $d->user; ?>
    </div>
    <?php
    endif;
    ?>
	<?php
	if ($d->headers > 0 && $d->showDate) :
        ?>
        <div class="<?php echo Html::getGridSpan(12 / $d->headers)?>">
			<?php echo $d->date; ?>
        </div>
	<?php
	endif;
	?>
    <div class="<?php echo Html::getGridSpan(12); ?>">
        <?php echo $d->note->text; ?>
    </div>
</div>