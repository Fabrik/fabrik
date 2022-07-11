<?php
/**
 * Legacy Fabrik 3.5 Fabrik\Helpers\Html FabrikHelperHTML
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

class_alias('Fabrik\Helpers\Worker', 'FabrikWorker');
class_alias('Fabrik\Helpers\Pdf', 'FabrikPDFHelper');
class_alias('Fabrik\Helpers\ArrayHelper', 'FArrayHelper');
class_alias('Fabrik\Helpers\StringHelper', 'FabrikString');
class_alias('Fabrik\Helpers\Text', 'FText');
class_alias('Fabrik\Helpers\Element', 'FabrikHelperElement');
class_alias('Fabrik\Helpers\Html', 'FabrikHelperHTML');
//class_alias('Fabrik\Helpers\LayoutFile', 'FabrikLayoutFile');
class_alias('Fabrik\Helpers\Image', 'FabimageHelper');
//class_alias('Fabrik\Helpers\Pagination', 'FPagination');
class_alias('Fabrik\Helpers\Googlemap', 'FabGoogleMapHelper');

if (file_exists(JPATH_LIBRARIES . '/fabrik/fabrik/Helpers/Custom.php'))
{
	class_alias('Fabrik\Helpers\Custom', 'FabrikCustom');
}