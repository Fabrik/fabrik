<?php
defined('JPATH_BASE') or die;

$d = $displayData;

if (!empty($d->control)) :
    echo $d->control . "<br />\n";
endif;

if (!empty($d->frontEndSelect)) :
    echo $d->frontEndSelect . "<br />\n";
endif;

if (!empty($d->description)) :
    echo $d->description . "<br />\n";
endif;

?>

