<?php
/**
 * sh404SEF support for com_fabrik component.
 * Author : Jean-François Questiaux - based on peamak's work (tom@spierckel.net)
 * contact : info@betterliving.be
 *
 * sh404SEF version : 3.4.6.1269 - February 2012
 *
 * This is a sh404SEF native plugin file for Fabrik component (http://fabrikar.com)
 * Plugin version 1.3 - April 2012
 * 
 */

defined('_JEXEC' ) or die('Direct Access to this location is not allowed.');

//Fetch the form's name
if (!function_exists('shFetchFormName')) {
	function shFetchFormName($formid) {
		if (empty($formid)) return null;
		$database = FabrikWorker::getDbo();
		$sqlform = 'SELECT label, id FROM #__{package}_forms WHERE id = \''.$formid.'\'';
		$database->setQuery($sqlform);
		$formName = $database->loadResult('label', false);
		return isset($formName) ? $formName : '';
	}
}

//Fetch the list's name from the form ID
if (!function_exists('shFetchListName')) {
	function shFetchListName($formid) {
		if (empty($formid)) return null;
		$database = FabrikWorker::getDbo();
		$sqltable = 'SELECT label, id FROM #__{package}_lists WHERE form_id = \''.$formid.'\'';
		$database->setQuery($sqltable);
		$listName = $database->loadResult('label', false);
		return isset($listName) ? $listName : '';
	}
}

// Fetch slug
if (!function_exists('shFetchSlug')) {
	function shFetchSlug($rowid) {
		if (empty($rowid) || $rowid == '-1') {
		       return null;
		} else {
		       $pos = strpos( $rowid, '-' );
		       $slug = substr( $rowid, $pos+1 );
		       if (!empty($slug)) {
			       shRemoveFromGETVarsList('rowid');
		       }
		       return isset($slug) ? $slug : '';
		}
	}
}

//Fetch the table's name
if (!function_exists('shFetchTableName')) {
	function shFetchTableName($listid) {
		if (empty($listid)) return null;
		$database = FabrikWorker::getDbo();
		$sqltable = 'SELECT label, id FROM #__{package}_lists WHERE id = \''.$listid.'\'';
		$database->setQuery($sqltable);
		$tableName = $database->loadResult('label', false);
		return isset($tableName) ? $tableName : '';
	}
}

//Fetch the record's name
if (!function_exists('shFetchRecordName')) {
	function shFetchRecordName( $rowid, $formid ) {
		if (empty($rowid) || empty( $formid )) return null;
		$database = FabrikWorker::getDbo();
		// Get database table's name and slug first
		$sqltable = 'SELECT db_table_name, params FROM #__{package}_lists WHERE form_id = \''.$formid.'\'';
		$database->setQuery($sqltable);
		$result = $database->loadObject();
		
		$listName = $result->db_table_name;
		$json = $result->params;
		$slug= json_decode( $json )->{'sef-slug'};
		
		// Get record's name
		$sqltable = 'SELECT ' . $slug . ' FROM ' . $listName . ' WHERE id = \''.$rowid.'\'';
		$database->setQuery($sqltable);
		$recordName = $database->loadResult($slug, false); 		
		return isset($recordName) ? $recordName : '';
	}
}

//Fetch the visualization's name
if (!function_exists('shFetchVizName')) {
	function shFetchVizName($id) {
		if (empty($id)) return null;
		$database = FabrikWorker::getDbo();
		$sqlviz = 'SELECT label, id FROM #__{package}_visualizations WHERE id = \''.$id.'\'';
		$database->setQuery($sqlviz);
		$vizName = $database->loadResult('label', false);
		return isset($vizName) ? $vizName : '';
	}
}

// ------------------  standard plugin initialize function - don't change ---------------------------
global $sh_LANG, $sefConfig;
$shLangName = '';
$shLangIso = '';
$title = array();
$shItemidString = '';
$dosef = shInitializePlugin($lang, $shLangName, $shLangIso, $option);
if ($dosef == false) return;

// ------------------  standard plugin initialize function - don't change ---------------------------

// ------------------  load language file - adjust as needed ----------------------------------------

// ------------------  load language file - adjust as needed ----------------------------------------

$task = isset($task) ? @$task : null; // var_dump( $task );
$Itemid = isset($Itemid) ? @$Itemid : null;  //var_dump( $Itemid );
$listid = isset($listid) ? @$listid : null; // var_dump( $listid );
$id = isset($id) ? @$id : null;  // var_dump( $id );
$view = isset($view) ? @$view : null;  // var_dump( $view ); 
$formid = isset($formid) ? @$formid : null;  // var_dump( $formid );
$rowid = isset($rowid) ? @$rowid : null; // var_dump( $rowid );

// Get fabrik SEF configuration - used to include/exclude list's names in SEF urls
$config	= JComponentHelper::getParams('com_fabrik');

switch ($view) {
	case 'form':
		return false;
		break;

	case 'details':			
		// Insert table name if set so in Fabrik's options
		if( $config->get('fabrik_sef_tablename_on_forms') == 1 ) {
			if (isset($formid)) {
				$title[] = shFetchListName($formid);
			}
		}
		if (isset($rowid)) {
			$title[] = shFetchSlug($rowid);
			shRemoveFromGETVarsList( 'rowid' );
			shMustCreatePageId( 'set', true);
		} else {
			// Case of link to details from menu item
			// First get the Itemid from the menu link URL
			$pos = strpos( $string, 'Itemid=' );
			$itemId = substr( $string, $pos+7 );
			$pos = strpos( $itemId, '&' );
			$itemId = substr( $itemId, 0, $pos );
			
			$menus = JSite::getMenu();     
			$menusId = $menus->getMenu();
			
			// Get the rowid and formid from the menu object
			$menu_params = new JParameter($menusId[$itemId]->params);
			$rowid = $menu_params->get('rowid'); 
			$formid =  $menusId[$itemId]->query['formid'];
			if ( $formid ) {
				$title[] = shFetchRecordName( $rowid, $formid ); 
				shMustCreatePageId( 'set', true );
			}			 
		}
		break;
	
	case 'list' :
		if( $config->get( 'fabrik_sef_prepend_menu_title' ) == 1 ) {//When different views are requested to the same list from a menu item
			// First get the Itemid from the menu link URL
			$pos = strpos( $string, 'Itemid=' );
			$itemId = substr( $string, $pos+7 );
			$pos = strpos( $itemId, '&' );
			$itemId = substr( $itemId, 0, $pos );
			
			$menus = JSite::getMenu();     
			$menusId = $menus->getMenu(); 
			     
			$title[] = $menusId[$itemId]->title;
			shMustCreatePageId( 'set', true );
		} else {
			if (isset($listid)) {
				$title[] = shFetchTableName($listid);
				shMustCreatePageId( 'set', true );
			}
		}
		break;
	
	case 'visualization' :
		if( $config->get( 'fabrik_sef_prepend_menu_title' ) == 1 ) {//When different views are requested to the same list from a menu item
			// First get the Itemid from the menu link URL
			$pos = strpos( $string, 'Itemid=' );
			$itemId = substr( $string, $pos+7 );
			$pos = strpos( $itemId, '&' );
			$itemId = substr( $itemId, 0, $pos );
			
			$menus = JSite::getMenu();     
			$menusId = $menus->getMenu(); 
			     
			$title[] = $menusId[$itemId]->title;
			shRemoveFromGETVarsList( 'id' );
			shMustCreatePageId( 'set', true );
		} else {
			if( isset( $id )) {
				$title[] = shFetchVizName($id);
				shRemoveFromGETVarsList( 'id' );
				shMustCreatePageId( 'set', true );
			}
		}
		break;		
}

shRemoveFromGETVarsList('option');
shRemoveFromGETVarsList('calculations');
shRemoveFromGETVarsList('formid');
shRemoveFromGETVarsList('listid');
shRemoveFromGETVarsList('cid');
shRemoveFromGETVarsList('view');
shRemoveFromGETVarsList('Itemid');
shRemoveFromGETVarsList('lang');
shRemoveFromGETVarsList('resetfilters');
shRemoveFromGETVarsList('calculations');
shRemoveFromGETVarsList('random');

// ------------------  standard plugin finalize function - don't change ---------------------------
if ($dosef) {
	$string = shFinalizePlugin($string, $title, $shAppendString, $shItemidString,
	(isset($limit) ? @$limit : null), (isset($limitstart) ? @$limitstart : null),
	(isset($shLangName) ? @$shLangName : null)); 
}
// ------------------  standard plugin finalize function - don't change ---------------------------

?>
