<?php

/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * if using file extensions sef and htaccess :
 * you need to edit yout .htaccess file to:
 *
 * RewriteCond %{REQUEST_URI} (/|\.csv|\.php|\.html|\.htm|\.feed|\.pdf|\.raw|/[^.]*)$  [NC]
 *
 * otherwise the csv exporter will give you a 404 error
 *
 */

function fabrikBuildRoute(&$query)
{
	$segments = array();
	$app = JFactory::getApplication();
	$menu = $app->getMenu();
	$menuItem = $menu->getItem(@$query['Itemid']);
	if (isset($query['c']))
	{
		//$segments[] = $query['c'];//remove from sef url
		unset($query['c']);
	}

	if (isset($query['task']))
	{
		$segments[] = $query['task'];
		unset($query['task']);
	}

	if (isset($query['view']))
	{
		$view = $query['view'];
		$segments[] = $view;
		unset($query['view']);
	}
	else
	{
		$view = '';
	}

	if (isset($query['id']))
	{
		$segments[] = $query['id'];
		unset($query['id']);
	}

	if (isset($query['layout']))
	{
		$segments[] = $query['layout'];
		unset($query['layout']);
	}

	if (isset($query['formid']))
	{
		$segments[] = $query['formid'];
		unset($query['formid']);
	}

	// $$$ hugh - looks like we still have some links using 'fabrik' instead of 'formid'
	if (isset($query['fabrik']))
	{
		$segments[] = $query['fabrik'];
		unset($query['fabrik']);
	}

	if (isset($query['listid']))
	{
		if ($view != 'form' && $view != 'details')
		{
			$segments[] = $query['listid'];
		}
		unset($query['listid']);
	}


	if (isset($query['rowid']))
	{
		$segments[] = $query['rowid'];
		unset($query['rowid']);
	}

	if (isset($query['calculations']))
	{
		$segments[] = $query['calculations'];
		unset($query['calculations']);
	}
	;

	if (isset($query['filetype']))
	{
		$segments[] = $query['filetype'];
		unset($query['filetype']);
	}
	if (isset($query['format']))
	{
		$segments[] = $query['format'];
		//don't unset as with sef urls and extensions on - if we unset it
		//the url's prefix is set to .html
		//unset($query['format']);
	}

	if (isset($query['type']))
	{
		$segments[] = $query['type'];
		unset($query['type']);
	}

	//test
	if (isset($query['fabriklayout']))
	{
		$segments[] = $query['fabriklayout'];
		unset($query['fabriklayout']);
	}
	return $segments;
}

function fabrikParseRoute($segments)
{
	//vars are what Joomla then uses for its $_REQUEST array
	$vars = array();
	//Get the active menu item
	$app = JFactory::getApplication();
	$menu = $app->getMenu();
	$item = $menu->getActive();
	$view = $segments[0];
	if (strstr($view, '.'))
	{
		$view = array_shift(explode('.', $view));
	}

	// View (controller not passed into segments)
	switch ($view)
	{
		case 'form':
		case 'details':
			$vars['view'] = $segments[0];
			$vars['formid'] = JArrayHelper::getValue($segments, 1, 0);
			$vars['rowid'] = JArrayHelper::getValue($segments, 2, 0);
			break;
		case 'table':
		case 'list':
			$vars['view'] = JArrayHelper::getValue($segments, 0, '');
			$vars['listid'] = JArrayHelper::getValue($segments, 1, 0);
			break;
		case 'import':
			$vars['view'] = 'import';
			$vars['listid'] = JArrayHelper::getValue($segments, 1, 0);
			$vars['filetype'] = JArrayHelper::getValue($segments, 2, 0);
			break;
		case 'visualization':
			$vars['id'] = JArrayHelper::getValue($segments, 1, 0);
			$vars['format'] = JArrayHelper::getValue($segments, 2, 'html');
			break;
		default:
			// Router: ahk no view! for $view
	}
	return $vars;
}
