<?php
/**
 * PDF document title for form view
 *
 */

defined('JPATH_BASE') or die;

$d = $displayData;

/**
 * Set the download file name based on the document title and rowid
 *
 * For overriding, you can access form data in $d->model->data['yourtable____yourelement'];
 *
 */

echo $d->doc->getTitle() . '-' . $d->model->getRowId();
