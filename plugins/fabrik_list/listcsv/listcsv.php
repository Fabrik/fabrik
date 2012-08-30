<?php

/**
 * Allow processing of CSV import / export on a per row basis
 * @package Joomla
 * @subpackage Fabrik
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';
require_once(COM_FABRIK_FRONTEND . '/helpers/html.php');

class plgFabrik_ListListcsv extends plgFabrik_List {

	var $_counter = null;

	/**
	 * determine if the table plugin is a button and can be activated only when rows are selected
	 *
	 * @return bol
	 */

	public function canSelectRows()
	{
		return false;
	}

	/**
	 * Prep the button if needed
	 *
	 * @param   object  $params  plugin params
	 * @param   object  &$model  list model
	 * @param   array   &$args   arguements
	 *
	 * @return  bool;
	 */

	public function button($params, &$model, &$args)
	{
		parent::button($params, $model, $args);
		return false;
	}

	public function onImportCSVRow(&$params, &$listModel)
	{
		$file = JFilterInput::clean($params->get('listcsv_import_php_file'), 'CMD');
		if ($file == -1 || $file == '') {
			$code = @eval($params->get('listcsv_import_php_code'));
			FabrikWorker::logEval($code, 'Caught exception on eval in onImportCSVRow : %s');
		} else {
			@require(JPATH_ROOT . '/plugins/fabrik_list/listcsv/scripts/' . $file);
		}
		return true;
	}

}
?>
