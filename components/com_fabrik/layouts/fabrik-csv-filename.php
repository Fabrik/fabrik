<?php
/**
 * PDF document title for form view
 *
 */

defined('JPATH_BASE') or die;

use Fabrik\Helpers\Worker;

$d = $displayData;

// Set the download file name based on the document title and rowid

$fileName = $d->filename;

if ($fileName == '')
{
	$table    = $d->model->getTable();
	$fileName = $table->db_table_name . '-export.csv';
}
else
{
	$w = new Worker;
	$fileName = $w->parseMessageForPlaceholder($fileName);
	$w->replaceRequest($fileName);
	$fileName = sprintf($fileName, date('Y-m-d'));
}

echo $fileName;
