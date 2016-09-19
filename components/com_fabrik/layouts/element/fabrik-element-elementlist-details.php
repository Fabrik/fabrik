<?php
defined('JPATH_BASE') or die;

$d = $displayData;

$condensed = array();

if ($d->condense) :
	foreach ($d->uls as $ul) :
		$condensed[] = $ul[0];
	endforeach;

	echo $d->addHtml ? '<ul class="fabrikRepeatData"><li>' . implode('</li><li>', $condensed) . '</li></ul>' : implode(' ', $condensed);
else:
	if ($d->addHtml) : ?>
		<ul class="fabrikRepeatData"><li>
	<?php endif;?>

	<?php foreach ($d->uls as $ul) :
	if ($d->addHtml) :?>
		<ul class="fabrikRepeatData"><li>
		<?php echo implode('</li><li>', $ul);
		echo '</li></ul>';
	else:
		echo implode($d->sepChar, $ul);
	endif;

	endforeach;
	if ($d->addHtml) :?>
	 </li></ul>
	<?php endif;
endif;