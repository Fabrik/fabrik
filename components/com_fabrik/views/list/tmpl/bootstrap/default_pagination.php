<?php
/**
 * Fabrik List Template: Admin Bootstrap pagination
 *
 * @package		Joomla.Administrator
 * @subpackage	Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
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
/*
function fabrik_pagination_list_footer($list)
{
	// Initialise variables.
	$lang = JFactory::getLanguage();
	$html = "<div class=\"container\"><div class=\"pagination\">\n";

	$html .= "\n<div class=\"limit\">" . FText::_('JGLOBAL_DISPLAY_NUM') . $list['limitfield'] . "</div>";
	$html .= $list['pageslinks'];
	$html .= "\n<div class=\"limit\">" . $list['pagescounter'] . "</div>";

	$html .= "\n<input type=\"hidden\" name=\"" . $list['prefix'] . "limitstart\" value=\"" . $list['limitstart'] . "\" />";
	$html .= "\n</div></div>";

	return $html;
}
 */

/* Commented out because /components/com_fabrik/helpers/pagination.php does not call this routine
function fabrik_pagination_list_footer($list, $paginator)
{
	// Initialize variables
	$html = array();
	$html[] = '<div class="list-footer">';
	$limitLabel = $paginator->showDisplayNum ? FText::_('COM_FABRIK_DISPLAY_NUM') : '';
	$html[] = '<div class="limit input-prepend"><button class="add-on">' . $limitLabel  . '</button>' . $list['limitfield'] . '</div>';
	$html[] = $list['pageslinks'];
	$html[] = '<div class="counter">' . $list['pagescounter'] . '</div>';
	$html[] = '<input type="hidden" name="limitstart' . $paginator->_id . '" id="limitstart' . $paginator->_id . '" value="' . $list['limitstart'] . '" />';
	$html[] = '</div>';
	return implode("\n", $html);
}
*/

if (!function_exists('fabrik_pagination_item_active'))
{
	function fabrik_pagination_item_active(&$item, $listid)
	{
		switch ($item->key)
		{
			case 'previous':
				$rel = 'rel="prev" ';
				break;
			case 'next':
				$rel = 'rel="next" ';
				break;
			default:
				$rel = '';
		}
		return '<a ' . $rel . 'title="' . $item->text . '" href="' . $item->link . '">' . $item->text . '</a>';
	}
}

if (!function_exists('fabrik_pagination_item_inactive'))
{
	function fabrik_pagination_item_inactive(&$item)
	{
		return '<a href="#">' . $item->text . '</a>';
	}
}
