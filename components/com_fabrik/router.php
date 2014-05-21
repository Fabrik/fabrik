<?php
/**
 * build route
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * if using file extensions sef and htaccess :
 * you need to edit yout .htaccess file to:
 *
 * RewriteCond %{REQUEST_URI} (/|\.csv|\.php|\.html|\.htm|\.feed|\.pdf|\.raw|/[^.]*)$  [NC]
 *
 * otherwise the csv exporter will give you a 404 error
 *
 */

/**
 * build route
 *
 * @param   object  &$query  uri?
 *
 * @return  array url
 */

function fabrikBuildRoute(&$query)
{
	$segments = array();
	$app = JFactory::getApplication();
	$menu = $app->getMenu();

	if (empty($query['Itemid']))
	{
		$menuItem = $menu->getActive();
		$menuItemGiven = false;
	}
	else
	{
		$menuItem = $menu->getItem($query['Itemid']);
		$menuItemGiven = true;
	}

	// Are we dealing with a view that is attached to a menu item https://github.com/Fabrik/fabrik/issues/498?
	$hasMenu = ($menuItem instanceof stdClass) && isset($query['view'])
	&& array_key_exists('view', $menuItem->query) && $menuItem->query['view'] == $query['view']
		&& isset($query['id'])  && isset($menuItem->query['id']) && $menuItem->query['id'] == intval($query['id']);

	if ($hasMenu)
	{
		unset($query['view']);

		if (isset($query['catid']))
		{
			unset($query['catid']);
		}

		if (isset($query['layout']))
		{
			unset($query['layout']);
		}

		unset($query['id']);

		return $segments;
	}

	if (isset($query['c']))
	{
		// $segments[] = $query['c'];//remove from sef url
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

	if (isset($query['filetype']))
	{
		$segments[] = $query['filetype'];
		unset($query['filetype']);
	}

	if (isset($query['format']))
	{
		// Was causing error when sef on, url rewrite on and suffix add to url on.
		// $segments[] = $query['format'];

		/**
		 * Don't unset as with sef urls and extensions on - if we unset it
		 * the url's prefix is set to .html
		 *
		 *  unset($query['format']);
		 */
	}

	if (isset($query['type']))
	{
		$segments[] = $query['type'];
		unset($query['type']);
	}

	// Test
	if (isset($query['fabriklayout']))
	{
		$segments[] = $query['fabriklayout'];
		unset($query['fabriklayout']);
	}

	return $segments;
}

/**
 * parse route
 *
 * @param   array  $segments  url
 *
 * @return  array vars
 */

function fabrikParseRoute($segments)
{
	// $vars are what Joomla then uses for its $_REQUEST array
	$vars = array();

	// Get the active menu item
	$app = JFactory::getApplication();
	$menu = $app->getMenu();
	$item = $menu->getActive();
	$view = $segments[0];

	if (strstr($view, '.'))
	{
		$view = explode('.', $view);
		$view = array_shift($view);
	}

	// View (controller not passed into segments)
	switch ($view)
	{
		case 'form':
		case 'details':
		case 'emailform':
			$vars['view'] = $segments[0];
			$vars['formid'] = JArrayHelper::getValue($segments, 1, 0);
			$vars['rowid'] = JArrayHelper::getValue($segments, 2, '');
			$vars['format'] = JArrayHelper::getValue($segments, 3, 'html');
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
			break;
	}

	return $vars;
}
