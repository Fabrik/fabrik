<?php
/**
 * Layout: Yes/No field list view
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.2
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$d = $displayData;
$data = $d->value;
$tmpl = $d->tmpl;
$format = $d->format;

$j3 = FabrikWorker::j3();

$opts = array();
$yes_label = (empty($d->yes_label)) ? FText::_('JYES') : FText::_($d->yes_label) ;
$no_label = (empty($d->no_label)) ? FText::_('JNO') : FText::_($d->no_label) ;
$show_label = (empty($d->show_label)) ? '0' : $d->show_label;
$yes_image = (empty($d->yes_image)) ? 'checkmark' : $d->yes_image ;
$no_image = (empty($d->no_image)) ? 'remove' : $d->no_image ;

// if period . in image name assume it is a file
if ($d->format == 'pdf' || strpos($yes_image,'.')>0 ) :
	$opts['forceImage'] = true;
	FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/yesno/images/', 'image', 'list', false);
endif;

if(!empty($d->image_height)) $properties['style'] = FText::_('height:'.$d->image_height.'px!important');
 
/* (Bauer notes: Added because FabrikHelperHTML::image will unset properties['alt'] (why I don't know) 
 *  which makes it difficult to identify the value of the element in list or detail view.
 *  Not sure which of these, or both should be used - I vote for added class.)
 */
if($j3) {
    $properties['class'] = 'yn_val'.$data;
	$properties['title'] = ($data == '0') ? FText::_('JNO') : FText::_('JYES');
}

if ($data == '1') :
	$icon = $j3 && $format != 'pdf' ? $yes_image : '1.png';
	$properties['alt'] = $yes_label;
else :
	$icon = $j3 && $format != 'pdf' ? $no_image : '0.png';
    $properties['alt'] = $no_label;
endif;
//error_log('fabrik-element-yesno-details.php - '.FabrikHelperHTML::image($icon, 'details', $tmpl, $properties, false, $opts));
if($show_label) :
    echo ((int)$data == 0) ? $no_label : $yes_label ;
else :
    echo FabrikHelperHTML::image($icon, 'details', $tmpl, $properties, false, $opts);
endif; 
