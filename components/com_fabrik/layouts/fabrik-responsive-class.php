<?php
/**
 * Responsive class mapping layout, based on Bootstrap v2 names,
 * hidden-phone, hidden-tablet, hidden-desktop
 * ... so in default, just echo those.  In other frameworks, map to equivalent
 */

defined('JPATH_BASE') or die;

$d = $displayData;

echo $d->responsiveClass;
