<?php
/**
 * Fabrik List CSV plugin import user class
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.listcsv
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
+
/**
 * A simple example script which was used for testing the listcsv plugin during development.
 * It grabs the $formModel from the $listModel, and modifies the data being imported
 * , which will then be used by the main CSV import code for writing the table data.
 */
defined('_JEXEC') or die();

$listModel = $this->getModel();
$formModel = $listModel->getFormModel();

if (strstr($formModel->formData['us_streets___date_time'],'1899'))
{
   $formModel->updateFormData('us_streets___date_time', str_replace('1899','1999', $formModel->formData['us_streets___date_time']));
}

$formModel->updateFormData('us_streets___street_desc', 'testing 1 2 3 testing');
$formModel->updateFormData('us_streets___street_pic', 'images/stories/fabrik/streets/foo.jpg');
