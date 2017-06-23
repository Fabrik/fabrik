<?php

if (is_array($html)) {
	reset($html);
}

$d = $textslugData;

$html[] = '<div '
        . 'id="' . $d->id . '" '
        . 'class="fabrikinput" '
        . '>';
$html[] = $d->value;
$html[] = '</div>';

echo implode("\n", $html) . "\n";
unset($html);
?>
