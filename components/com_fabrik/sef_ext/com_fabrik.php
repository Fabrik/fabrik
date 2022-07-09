<?php
/**
 * sh404SEF support for com_fabrik component.
 * Author : Jean-François Questiaux - based on peamak's work (tom@spierckel.net)
 * contact : webmaster@betterweb.fr
 *
 * Joomla! 3.8.x
 * sh404SEF version : 4.13.3.3797 - April 2018
 * Fabrik 3.8.1
 *
 * This is a sh404SEF native plugin file for Fabrik component (http://fabrikar.com)
 * Plugin version 3.1 - April 2018
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

if (!function_exists('shFetchFormName'))
{
	/**
	 * Fetch the form's name
	 *
	 * @param   number  $formId  Form id
	 *
	 * @return NULL|string
	 */
	function shFetchFormName($formId)
	{
		if (empty($formId))
		{
			return null;
		}

		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('label')
			->from($query->qn('#__fabrik_forms'))
			->where('id = ' . $db->q($formId));
		$db->setQuery($query); 
		$formName = $db->loadResult();

		return isset($formName) ? $formName : '';
	}
}

if (!function_exists('shFetchSlug'))
{
	/**
	 * Fetch slug
	 *
	 * @param   string  $rowId   Row id
	 * @param   number  $formId  Form id
	 *
	 * @return NULL|String
	 */
	function shFetchSlug($rowid, $formid)
	{
		if (empty($rowid) || $rowid == '-1')
		{
			return null;
		}
		else
		{
			$slug = shFetchRecordName($rowid, $formid);
			
			return isset($slug) ? $slug : '';
		}
	}
}

if (!function_exists('shFetchListName'))
{
	/**
	 * Fetch the list's name
	 *
	 * @param   int  $listid  List id
	 *
	 * @return string
	 */
	function shFetchListName($listid)
	{
		if (empty($listid))
		{
			return null;
		}

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('label')
		      ->from($query->qn('#__fabrik_lists'))
		      ->where('id = ' . $query->q($listid));
		$db->setQuery($query);
		$listName = $db->loadResult();

		return isset($listName) ? $listName : '';
	}
}

if (!function_exists('shFetchRecordName'))
{
	/**
	 * Fetch the record's name
	 *
	 * @param   string  $rowId   Row id
	 * @param   number  $formId  Form id
	 *
	 * @return string
	 */
	function shFetchRecordName($rowid, $formid)
	{
		if (empty($rowid) || empty($formid))
		{
			return null;
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Get database table's name and slug first
		$query->select('db_table_name, params')
		      ->from($query->qn('#__fabrik_lists'))
		      ->where('form_id = ' . $query->q($formid));
		$db->setQuery($query);
		$result = $db->loadObject();

		$listName = $result->db_table_name;
		$json = $result->params;
		$slug = json_decode($json)->{'sef-slug'};

		// Get record's name
		$query = $db->getQuery(true);
		$query->select($query->qn($slug))
		      ->from($query->qn($listName))
		      ->where('id = ' . $query->q($rowid));
		$db->setQuery($query);
		$recordName = $db->loadResult();

		return isset($recordName) ? $recordName : '';
	}
}

if (!function_exists('shFetchVizName'))
{
	/**
	 * Fetch the visualization's name
	 *
	 * @param   int  $id  Id
	 *
	 * @return string
	 */
	function shFetchVizName($id)
	{
		if (empty($id))
		{
			return null;
		}

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('label')
			->from($query->qn('#__fabrik_visualizations'))
			->where('id = ' . $query->q($id));
		$db->setQuery($query);
		$vizName = $db->loadResult();

		return isset($vizName) ? $vizName : '';
	}
}

// ------------------  standard plugin initialize function - don't change ---------------------------
global $sh_LANG;
$sefConfig = & Sh404sefFactory::getConfig();
$shLangName = '';
$shLangIso = '';
$title = array();
$shItemidString = '';
$dosef = shInitializePlugin( $lang, $shLangName, $shLangIso, $option);
if ($dosef == false) return;
// ------------------  standard plugin initialize function - don't change ---------------------------

// ------------------  load language file - adjust as needed ----------------------------------------
//$shLangIso = shLoadPluginLanguage( 'com_XXXXX', $shLangIso, '_SEF_SAMPLE_TEXT_STRING');
// ------------------  load language file - adjust as needed ----------------------------------------

// remove common URL from GET vars list, so that they don't show up as query string in the URL
shRemoveFromGETVarsList('option');
shRemoveFromGETVarsList('calculations');
shRemoveFromGETVarsList('formid');
shRemoveFromGETVarsList('listid');
shRemoveFromGETVarsList('cid');
shRemoveFromGETVarsList('Itemid');
shRemoveFromGETVarsList('lang');
shRemoveFromGETVarsList('calculations');
shRemoveFromGETVarsList('random');
shRemoveFromGETVarsList('rowid');
shRemoveFromGETVarsList('id');
	
$Itemid = isset($Itemid) ? $Itemid : null;
$listid = isset($listid) ? $listid : null;
$id     = isset($id) ? $id : null;
$view   = isset($view) ? $view : null;
$formid = isset($formid) ? $formid : null;
$rowid  = isset($rowid) ? $rowid : null;

// remove extra data from rowid to keep the actual ID
$rowid = (int)$rowid;

// Get fabrik SEF configuration - used to include/exclude list's names in SEF urls
$config = JComponentHelper::getParams('com_fabrik');

switch ($view)
{
	case 'form':
		// If we edit the form, there is no need to create a SEF URL
		if($rowid != '')
		{
			$dosef = false;
		}
		else
		{
			// Insert table name if set so in Fabrik's options
			if ($config->get('fabrik_sef_tablename_on_forms') == 1)
			{
				if (isset($formid))
				{
					$title[] = FText::_(shFetchListName($formid));
				}
				else
				{
					$title[] = '';
				}
			}
			
			$config->get('fabrik_sef_customtxt_new') == '' ? $new = 'new' : $new = $config->get('fabrik_sef_customtxt_new');
			$title[] = FText::_(shFetchFormName($formid)) . '-' . FText::_($new);			
		}
		
		break;

	case 'details':
		// start by inserting the menu element title (can be set in Fabrik options)
		if ($config->get('fabrik_sef_prepend_menu_title_details') == 1 && $Itemid != '')
		{
			$task = isset($task) ? $task : null;
			$shSampleName = shGetComponentPrefix($option);
			$shSampleName = empty($shSampleName) ? getMenuTitle($option, $task, $Itemid, null, $shLangName) : $shSampleName;
			$shSampleName = (empty($shSampleName) || $shSampleName == '/') ? 'Fabrik':$shSampleName;
			
			$title[] = $shSampleName;
		}
		
		if ($rowid != '')
		{
			switch ($config->get('fabrik_sef_format_records'))
			{
				case 'param_id':
					$title[] = 'id=' . $rowid;
					break;
				case 'id_only':
					$title[] = $rowid;
					break;
				case 'id_slug':
					$title[] = $rowid . '-' . shFetchSlug($rowid, $formid);
					break;
				case 'slug_id':
					$title[] = shFetchSlug($rowid, $formid) . '-' . $rowid;

					break;
				case 'slug_only':
					$title[] = shFetchSlug($rowid, $formid);
					break;
			}
			shMustCreatePageId('set', true);
		}
		else
		{
			$dosef = false;
		}
		break;

	case 'list':
		// start by inserting the menu element title (can be set in Fabrik options)
		if ($config->get('fabrik_sef_prepend_menu_title_lists') == 1 && $Itemid != '')
		{
			$task = isset($task) ? $task : null;
			$shSampleName = shGetComponentPrefix($option);
			$shSampleName = empty($shSampleName) ? getMenuTitle($option, $task, $Itemid, null, $shLangName) : $shSampleName;
			$shSampleName = (empty($shSampleName) || $shSampleName == '/') ? 'Fabrik':$shSampleName;
			
			$title[] = $shSampleName;
		}

		if (isset($listid))
		{
			$title[] = FText::_(shFetchListName($listid));
			shMustCreatePageId('set', true);
		}
		
		break;

	case 'visualization':
		// start by inserting the menu element title (can be set in Fabrik options)
		if ($config->get('fabrik_sef_prepend_menu_title_viz') == 1 && $Itemid != '')
		{
			$task = isset($task) ? $task : null;
			$shSampleName = shGetComponentPrefix($option);
			$shSampleName = empty($shSampleName) ? getMenuTitle($option, $task, $Itemid, null, $shLangName) : $shSampleName;
			$shSampleName = (empty($shSampleName) || $shSampleName == '/') ? 'Fabrik':$shSampleName;
			
			$title[] = $shSampleName;
		}
		
		if (isset($id))
		{
			switch ($config->get('fabrik_sef_format_viz'))
			{
				case 'param_id':
					$title[] = 'id=' . $rowid;
					break;
				case 'viz-id':
					$title[] = $rowid;
					break;
				case 'id-viz':
					$title[] = $rowid . '-viz';
					break;
				case 'label-id':
					$title[] = FText::_(shFetchVizName($id)) . '-' . $id;
					break;
				case 'id-label':
					$title[] = $id . '-' . FText::_(shFetchVizName($id));
					break;
				case 'label_only':
					$title[] = FText::_(shFetchVizName($id));
					break;
			}
			shMustCreatePageId('set', true);
		}
		break;

		default:
			$dosef = false;
}
   
shRemoveFromGETVarsList('view');

// ------------------  standard plugin finalize function - don't change ---------------------------
if ($dosef){
  $string = shFinalizePlugin( $string, $title, $shAppendString, $shItemidString,
      (isset($limit) ? @$limit : null), (isset($limitstart) ? @$limitstart : null),
      (isset($shLangName) ? @$shLangName : null));
}
// ------------------  standard plugin finalize function - don't change ---------------------------
