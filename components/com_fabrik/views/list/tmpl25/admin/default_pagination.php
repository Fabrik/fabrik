<?php
/**
 * Fabrik List Template: Admin Pagination
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * This is a file to add template specific chrome to pagination rendering.
 *
 * pagination_list_footer
 *	Input variable $list is an array with offsets:
 *		$list[prefix]		: string
 *		$list[limit]		: int
 *		$list[limitstart]	: int
 *		$list[total]		: int
 *		$list[limitfield]	: string
 *		$list[pagescounter]	: string
 *		$list[pageslinks]	: string
 *
 * pagination_list_render
 *	Input variable $list is an array with offsets:
 *		$list[all]
 *			[data]		: string
 *			[active]	: boolean
 *		$list[start]
 *			[data]		: string
 *			[active]	: boolean
 *		$list[previous]
 *			[data]		: string
 *			[active]	: boolean
 *		$list[next]
 *			[data]		: string
 *			[active]	: boolean
 *		$list[end]
 *			[data]		: string
 *			[active]	: boolean
 *		$list[pages]
 *			[{PAGE}][data]		: string
 *			[{PAGE}][active]	: boolean
 *
 * pagination_item_active
 *	Input variable $item is an object with fields:
 *		$item->base	: integer
 *		$item->prefix	: string
 *		$item->link	: string
 *		$item->text	: string
 *
 * pagination_item_inactive
 *	Input variable $item is an object with fields:
 *		$item->base	: integer
 *		$item->prefix	: string
 *		$item->link	: string
 *		$item->text	: string
 *
 * This gives template designers ultimate control over how pagination is rendered.
 *
 * NOTE: If you override pagination_item_active OR pagination_item_inactive you MUST override them both
 */

function fabrik_pagination_list_render($list, $context)
{
	// Initialise variables.
	$lang = JFactory::getLanguage();
	$html = array();
	$html[] = '<div class="pagination">';
	if ($list['start']['active'])
	{
		$html[] = '<div class="button2-right"><div class="start">' . $list['start']['data'] . '</div></div>';
	}
	else
	{
		$html[] = '<div class="button2-right off"><div class="start">' . $list['start']['data'] . '</div></div>';
	}
	if ($list['previous']['active'])
	{
		$html[] = '<div class="button2-right"><div class="prev">' . $list['previous']['data'] . '</div></div>';
	}
	else
	{
		$html[] = '<div class="button2-right off"><div class="prev">' . $list['previous']['data'] . '</div></div>';
	}

	$html[] = '<div class="button2-left"><div class="page">';
	foreach($list['pages'] as $page)
	{
		$html[] = $page['data'];
	}
	$html[] = '</div></div>';

	if ($list['next']['active'])
	{
		$html[] = '<div class="button2-left"><div class="next">' . $list['next']['data'] . '</div></div>';
	}
	else
	{
		$html[] = '<div class="button2-left off"><div class="next">' . $list['next']['data'] . '</div></div>';
	}
	if ($list['end']['active'])
	{
		$html[] = '<div class="button2-left"><div class="end">' . $list['end']['data'] . '</div></div>';
	}
	else
	{
		$html[] = '<div class="button2-left off"><div class="end">' . $list['end']['data'] . '</div></div>';
	}
	$html[] =  '</div>';
	return implode("\n", $html);
}

