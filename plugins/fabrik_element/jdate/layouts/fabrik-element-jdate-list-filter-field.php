<?php
defined('JPATH_BASE') or die;

use Joomla\Utilities\ArrayHelper;

$d    = $displayData;
$from = $d->from;

$calOpts = ArrayHelper::toString($d->calOpts);

if ($d->j3) :
	$from->img = '<button id ="' . $from->id . '_cal_img" class="btn calendarbutton">' . $from->img . '</button>';
endif;

$prepend = $d->j3 ? '<div class="input-append">' : '';
$append  = $d->j3 ? '</div>' : '';
//$str[] = $this->calendar($gmt, $name, $id . '_cal', $format, $calOpts, $repeatCounter);

?>
<div class="fabrik_filter_container">
	<?php echo $prepend; ?>
    <?php echo $d->jCal; ?>
	<?php echo $append; ?>
	<br />
</div>

