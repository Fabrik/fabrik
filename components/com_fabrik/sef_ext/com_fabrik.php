<?php
/**
 * sh404SEF support for com_fabrik component.
 * Author : Jean-François Questiaux - based on peamak's work (tom@spierckel.net)
 * contact : info@betterliving.be
 *
 * Joomla! 3.2.x
 * sh404SEF version : 4.2.1.1586 - November 2013
 * Fabrik 3.1 RC2
 *
 * This is a sh404SEF native plugin file for Fabrik component (http://fabrikar.com)
 * Plugin version 2.2 - December 2013
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

if (!function_exists('shFetchFormName'))
{
	/**
	 * Fetch the form's name
	 *
	 * @param   number  $formid  Form id
	 *
	 * @return NULL|Ambiguous <string, unknown>
	 */
	function shFetchFormName($formid)
	{
		if (empty($formid))
		{
			return null;
		}

		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('label')
			->from($query->quoteName('#__fabrik_forms'))
			->where('id = ' . $db->quote($formid));
		$db->setQuery($query);
		$formName = $db->loadResult();

		return isset($formName) ? FText::_($formName) : '';
	}
}

if (!function_exists('shFetchListName'))
{
	/**
	 * Fetch the list's name from the form ID
	 *
	 * @param   int  $formid  Form id
	 *
	 * @return NULL|Ambiguous <string, unknown>
	 */
	function shFetchListName($formid)
	{
		if (empty($formid))
		{
			return null;
		}

		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('label')
			->from($query->quoteName('#__fabrik_lists'))
			->where('form_id = ' . $query->quote($formid));
		$db->setQuery($query);
		$listName = $db->loadResult();

		return isset($listName) ? FText::_($listName) : '';
	}
}

if (!function_exists('shFetchSlug'))
{
	/**
	 * Fetch slug
	 *
	 * @param   string  $rowid   Row id
	 * @param   number  $formid  Form id
	 *
	 * @return NULL|Ambiguous <string, NULL, Ambiguous, unknown>
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

if (!function_exists('shFetchTableName'))
{
	/**
	 * Fetch the table's name
	 *
	 * @param   int  $listid  List id
	 *
	 * @return NULL|Ambiguous <string, unknown>
	 */
	function shFetchTableName($listid)
	{
		if (empty($listid))
		{
			return null;
		}

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('label')
			->from($query->quoteName('#__fabrik_lists'))
			->where('id = ' . $query->quote($listid));
		$db->setQuery($query);
		$tableName = $db->loadResult();

		return isset($tableName) ? $tableName : '';
	}
}

if (!function_exists('shFetchRecordName'))
{
	/**
	 * Fetch the record's name
	 *
	 * @param   string  $rowid   Rowid
	 * @param   number  $formid  Form id
	 *
	 * @return NULL|Ambiguous <string, unknown>
	 */
	function shFetchRecordName($rowid, $formid)
	{
		if (empty($rowid) || empty($formid))
		{
			return null;
		}

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Get database table's name and slug first
		$query->select('db_table_name, params')
			->from($query->quoteName('#__fabrik_lists'))
			->where('form_id = ' . $query->quote($formid));
		$db->setQuery($query);
		$result = $db->loadObject();

		$listName = $result->db_table_name;
		$json = $result->params;
		$slug = json_decode($json)->{'sef-slug'};

		// Get record's name
		$query = $db->getQuery(true);
		$query->select($query->quoteName($slug))
			->from($query->quoteName($listName))
			->where('id = ' . $query->quote($rowid));
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
	 * @return NULL|Ambiguous <string, unknown>
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
			->from($query->quoteName('#__fabrik_visualizations'))
			->where('id = ' . $query->quote($id));
		$db->setQuery($query);
		$vizName = $db->loadResult();

		return isset($vizName) ? FText::_($vizName) : '';
	}
}

// ------------------  standard plugin initialize function - don't change ---------------------------
global $sh_LANG, $sefConfig;
$shLangName     = '';
$shLangIso      = '';
$title          = array();
$shItemidString = '';
$dosef          = shInitializePlugin($lang, $shLangName, $shLangIso, $option);

if ($dosef == false)
{
	return;
}

// ------------------  standard plugin initialize function - don't change ---------------------------

// ------------------  load language file - adjust as needed ----------------------------------------

// ------------------  load language file - adjust as needed ----------------------------------------

// $task   = isset($task) ? @$task : null;
// $Itemid = isset($Itemid) ? @$Itemid : null;
$listid = isset($listid) ? @$listid : null;
$id     = isset($id) ? @$id : null;
$view   = isset($view) ? @$view : null;
$formid = isset($formid) ? @$formid : null;
$rowid  = isset($rowid) ? @$rowid : null;

// Get fabrik SEF configuration - used to include/exclude list's names in SEF urls
$config = JComponentHelper::getParams('com_fabrik');

switch ($view)
{
	case 'form':
		if (isset($formid) && $rowid != '')
		{
			$config->get('fabrik_sef_customtxt_edit') == '' ? $edit = 'edit' : $edit = $config->get('fabrik_sef_customtxt_edit');
			$title[] = shFetchFormName($formid) . '-' . $rowid . '-' . FText::_($edit);
		}
		else
		{
			$config->get('fabrik_sef_customtxt_new') == '' ? $new = 'new' : $new = $config->get('fabrik_sef_customtxt_new');
			$title[] = shFetchFormName($formid) . '-' . FText::_($new);
		}
		break;

	case 'details':
		// Insert menu name if set so in Fabrik's options
		if ($config->get('fabrik_sef_prepend_menu_title') == 1)
		{
			$app     = JFactory::getApplication();
			$menus   = $app->getMenu();
			$menusId = $menus->getMenu();
			$itemId  = $app->input->getInt('Itemid');

			$title[] = $menusId[$itemId]->title;
		}
		// Insert table name if set so in Fabrik's options
		if ($config->get('fabrik_sef_tablename_on_forms') == 1)
		{
			if (isset($formid))
			{
				$title[] = shFetchListName($formid);
			}
			else
			{
				$title[] = '';
			}
		}

		if (isset($rowid))
		{
			switch ($config->get('fabrik_sef_format_records'))
			{
				case 'param_id':
					$title[] = '';
					break;
				case 'id_only':
					$title[] = $rowid;
					shRemoveFromGETVarsList('rowid');
					break;
				case 'id_slug':
					$title[] = $rowid . '-' . shFetchSlug($rowid, $formid);
					shRemoveFromGETVarsList('rowid');
					break;
				case 'slug_id':
					$title[] = shFetchSlug($rowid, $formid) . '-' . $rowid;
					shRemoveFromGETVarsList('rowid');
					break;
				case 'slug_only':
					$title[] = shFetchSlug($rowid, $formid);
					shRemoveFromGETVarsList('rowid');
					break;
			}

			shMustCreatePageId('set', true);
		}
		else
		{
			// Case of link to details from menu item
			// First get the Itemid from the menu link URL
			$pos    = strpos($string, 'Itemid=');
			$itemId = substr($string, $pos + 7);
			$pos    = strpos($itemId, '&');
			$itemId = substr($itemId, 0, $pos);

			$app     = JFactory::getApplication();
			$menus   = $app->getMenu();
			$menusId = $menus->getMenu();

			// Get the rowid and formid from the menu object
			$menu_params = new JParameter($menusId[$itemId]->params);
			$rowid 	     = $menu_params->get('rowid');
			$formid      = $menusId[$itemId]->query['formid'];

			if ($formid)
			{
				$title[] = shFetchRecordName($rowid, $formid);
				shMustCreatePageId('set', true);
			}
		}
		break;

	case 'list':
		if ($config->get('fabrik_sef_prepend_menu_title') == 1)
		{
			// When different views are requested to the same list from a menu item
			// First get the Itemid from the menu link URL
			$pos    = strpos($string, 'Itemid=');
			$itemId = substr($string, $pos + 7);
			$pos    = strpos($itemId, '&');
			$itemId = substr($itemId, 0, $pos);

			$app     = JFactory::getApplication();
			$menus   = $app->getMenu();
			$menusId = $menus->getMenu();

			$title[] = $menusId[$itemId]->title;
			shMustCreatePageId('set', true);
		}
		else
		{
			if (isset($listid))
			{
				$title[] = shFetchTableName($listid);
				shMustCreatePageId('set', true);
			}
		}
		break;

	case 'visualization':
		if ($config->get('fabrik_sef_prepend_menu_title') == 1)
		{
			// When different views are requested to the same list from a menu item
			// First get the Itemid from the menu link URL
			$pos    = strpos($string, 'Itemid=');
			$itemId = substr($string, $pos + 7);
			$pos    = strpos($itemId, '&');
			$itemId = substr($itemId, 0, $pos);

			$app     = JFactory::getApplication();
			$menus   = $app->getMenu();
			$menusId = $menus->getMenu();

			$title[] = $menusId[$itemId]->title;
			shRemoveFromGETVarsList('id');
			shMustCreatePageId('set', true);
		}
		else
		{
			if (isset($id))
			{
				$title[] = shFetchVizName($id);
				shRemoveFromGETVarsList('id');
				shMustCreatePageId('set', true);
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
shRemoveFromGETVarsList('calculations');
shRemoveFromGETVarsList('random');

// ------------------  standard plugin finalize function - don't change ---------------------------
if ($dosef)
{
	$string = shFinalizePlugin(
		$string, $title, $shAppendString, $shItemidString, (isset($limit) ? @$limit : null),
		(
			isset($limitstart) ? @$limitstart : null), (isset($shLangName) ? @$shLangName : null)
	);
}

// ------------------  standard plugin finalize function - don't change ---------------------------
