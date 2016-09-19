<?php
/**
 * Makes the list navigation html to traverse the list data
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Makes the list navigation html to traverse the list data
 *
 * @param   int the total number of records in the table
 * @param   int number of records to show per page
 * @param   int which record number to start at
 */

jimport('joomla.html.pagination');

/**
 * Extension to the normal page-nav functions
 * $total, $limitstart, $limit
 *
 * @package  Fabrik
 * @since    3.0
 */
class FPagination extends JPagination
{
	/**
	 * Action url
	 *
	 * @var  string
	 */
	public $url = '';

	/**
	 * Pagination ID
	 *
	 * @var  string
	 */
	protected $id = '';

	/**
	 * Show the total number of records found
	 *
	 * @var  bool
	 */
	public $showTotal = false;

	/**
	 * Add an 'all' option to the display # dropdown
	 *
	 * @var  bool
	 */
	public $showAllOption = false;

	/**
	 * The lists unique reference
	 *
	 * @var  string
	 */
	protected $listRef = null;

	/**
	 * Show 'x of y pages'
	 *
	 * @var  bool
	 */
	public $showDisplayNum = true;

	/**
	 * Add a 'show all' option to display # select list
	 *
	 * @var bool
	 */
	public $viewAll = false;

	/**
	 * Set the pagination ID
	 *
	 * @param   int $id id
	 *
	 * @return  void
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Return the pagination footer
	 *
	 * @param   int    $listRef List reference
	 * @param   string $tmpl    List template
	 *
	 * @return    string    Pagination footer
	 */
	public function getListFooter($listRef = '', $tmpl = 'default')
	{
		$app                  = JFactory::getApplication();
		$this->listRef        = $listRef;
		$list                 = array();
		$list['limit']        = $this->limit;
		$list['limitstart']   = $this->limitstart;
		$list['total']        = $this->total;
		$list['limitfield']   = $this->showDisplayNum ? $this->getLimitBox() : '';
		$list['pagescounter'] = $this->getPagesCounter();

		if ($this->showTotal)
		{
			$list['pagescounter'] .= ' ' . FText::_('COM_FABRIK_TOTAL') . ': ' . $list['total'];
		}

		$list['pageslinks'] = $this->getPagesLinks($listRef, $tmpl);
		$chromePath         = JPATH_THEMES . '/' . $app->getTemplate() . '/html/pagination.php';

		if (file_exists($chromePath))
		{
			require_once $chromePath;

			if (function_exists('pagination_list_footer'))
			{
				// Cant allow for it to be overridden
			}
		}

		return $this->_list_footer($list);
	}

	/**
	 * Creates a dropdown box for selecting how many records to show per page
	 *
	 * @return    string    The html for the limit # input box
	 */
	public function getLimitBox()
	{
		// Initialize variables
		$limits = array();
		$values = array();

		for ($i = 5; $i <= 30; $i += 5)
		{
			$values[] = $i;
		}

		$values[] = 50;
		$values[] = 100;

		if (!in_array($this->startLimit, $values))
		{
			$values[] = $this->startLimit;
		}

		asort($values);

		foreach ($values as $v)
		{
			$limits[] = JHTML::_('select.option', $v);
		}

		if ($this->showAllOption == true)
		{
			$limits[] = JHTML::_('select.option', '-1', FText::_('COM_FABRIK_ALL'));
		}

		$selected   = $this->viewAll ? '-1' : $this->limit;
		$js         = '';
		$attributes = 'class="inputbox input-mini" size="1" onchange="' . $js . '"';
		$html       = JHTML::_('select.genericlist', $limits, 'limit' . $this->id, $attributes, 'value', 'text', $selected);

		return $html;
	}

	/**
	 * Method to create an active pagination link to the item
	 *
	 * @param   JPaginationObject $item The object with which to make an active link.
	 *
	 * @return   string  HTML link
	 */
	protected function _item_active(JPaginationObject $item)
	{
		$displayData       = new stdClass;
		$displayData->item = $item;
		$paths[]           = JPATH_THEMES . '/' . JFactory::getApplication()->getTemplate() . '/html/layouts/com_fabrik/list_' . $this->id;

		$layout = FabrikHelperHTML::getLayout('pagination.fabrik-pagination-item-active', $paths);

		return $layout->render($displayData);
	}

	/**
	 * Method to create an inactive pagination string
	 *
	 * @param   JPaginationObject $item The item to be processed
	 *
	 * @return  string
	 *
	 * @since   1.5
	 */
	protected function _item_inactive(JPaginationObject $item)
	{
		$displayData       = new stdClass;
		$displayData->item = $item;
		$paths[]           = JPATH_THEMES . '/' . JFactory::getApplication()->getTemplate() . '/html/layouts/com_fabrik/list_' . $this->id;

		$layout = FabrikHelperHTML::getLayout('pagination.fabrik-pagination-item-inactive', $paths);

		return $layout->render($displayData);
	}

	/**
	 * Create and return the pagination page list string, i.e. Previous, Next, 1 2 3 ... x.
	 *
	 * @param   int    $listRef Unique list reference
	 * @param   string $tmpl    List template name
	 *
	 * @return  string  Pagination page list string.
	 *
	 * @since   11.1
	 */
	public function getPagesLinks($listRef = 0, $tmpl = 'default')
	{
		// Build the page navigation list
		$data = $this->_buildDataObject();

		$list = array();

		$itemOverride = false;
		$listOverride = false;
		$chromePath   = COM_FABRIK_FRONTEND . '/views/list/tmpl/' . $tmpl . '/default_pagination.php';

		if (JFile::exists($chromePath))
		{
			require_once $chromePath;

			if (function_exists('fabrik_pagination_item_active') && function_exists('fabrik_pagination_item_inactive'))
			{
				// Can't allow this as the js code we use for the items is different
				$itemOverride = true;
			}

			if (function_exists('fabrik_pagination_list_render'))
			{
				$listOverride = true;
			}
		}

		// Build the select list
		if ($data->all->base !== null)
		{
			$list['all']['active'] = true;
			$list['all']['data']   = $itemOverride ? fabrik_pagination_item_active($data->all, $this->listRef) : $this->_item_active($data->all);
		}
		else
		{
			$list['all']['active'] = false;
			$list['all']['data']   = $itemOverride ? fabrik_pagination_item_inactive($data->all) : $this->_item_inactive($data->all);
		}

		if ($data->start->base !== null)
		{
			$list['start']['active'] = true;
			$list['start']['data']   = $itemOverride ? fabrik_pagination_item_active($data->start, $this->listRef) : $this->_item_active($data->start);
		}
		else
		{
			$list['start']['active'] = false;
			$list['start']['data']   = $itemOverride ? fabrik_pagination_item_inactive($data->start) : $this->_item_inactive($data->start);
		}

		if ($data->previous->base !== null)
		{
			$list['previous']['active'] = true;
			$list['previous']['data']   = $itemOverride ? fabrik_pagination_item_active($data->previous, $this->listRef)
				: $this->_item_active($data->previous);
		}
		else
		{
			$list['previous']['active'] = false;
			$list['previous']['data']   = $itemOverride ? fabrik_pagination_item_inactive($data->previous) : $this->_item_inactive($data->previous);
		}

		// Make sure it exists
		$list['pages'] = array();

		foreach ($data->pages as $i => $page)
		{
			if ($page->base !== null)
			{
				$list['pages'][$i]['active'] = true;
				$list['pages'][$i]['data']   = $itemOverride ? fabrik_pagination_item_active($page, $this->listRef) : $this->_item_active($page);
			}
			else
			{
				$list['pages'][$i]['active'] = false;
				$list['pages'][$i]['data']   = $itemOverride ? fabrik_pagination_item_inactive($page) : $this->_item_inactive($page);
			}
		}

		if ($data->next->base !== null)
		{
			$list['next']['active'] = true;
			$list['next']['data']   = $itemOverride ? fabrik_pagination_item_active($data->next, $this->listRef) : $this->_item_active($data->next);
		}
		else
		{
			$list['next']['active'] = false;
			$list['next']['data']   = $itemOverride ? fabrik_pagination_item_inactive($data->next) : $this->_item_inactive($data->next);
		}

		if ($data->end->base !== null)
		{
			$list['end']['active'] = true;
			$list['end']['data']   = $itemOverride ? fabrik_pagination_item_active($data->end, $this->listRef) : $this->_item_active($data->end);
		}
		else
		{
			$list['end']['active'] = false;
			$list['end']['data']   = $itemOverride ? fabrik_pagination_item_inactive($data->end) : $this->_item_inactive($data->end);
		}

		if ($this->total > $this->limit)
		{
			return ($listOverride) ? fabrik_pagination_list_render($list, $this->listRef) : $this->_list_render($list);
		}
		else
		{
			return '';
		}
	}

	/**
	 * Create the html for a list footer
	 *
	 * @param   array $list Pagination list data structure.
	 *
	 * @return  string  HTML for a list start, previous, next,end
	 */
	protected function _list_render($list)
	{
		$displayData       = new stdClass;
		$displayData->list = $list;
		$paths[]           = JPATH_THEMES . '/' . JFactory::getApplication()->getTemplate() . '/html/layouts/com_fabrik/list_' . $this->id;

		$layout = FabrikHelperHTML::getLayout('pagination.fabrik-pagination-links', $paths);

		return $layout->render($displayData);
	}

	/**
	 * THIS SEEMS GOOFY TO HAVE TO OVERRIDE DEFAULT FUNCTION - BUT!
	 * THE ORIGINAL SETS THE PAGE TO EMPTY IF ITS 0 - APPARENTLY TO DO WITH
	 * ROUTING - THIS HAS BEEN REMOVED HERE
	 *
	 * PERHAPS THE FABRIK ROUTING ISN'T RIGHT?
	 *
	 * OCCURS EVEN WITHOUT SEF URLS ON THOUGH? :s
	 *
	 * Create and return the pagination data object
	 *
	 * @return    object    Pagination data object
	 */
	protected function _buildDataObject()
	{
		// Initialize variables
		$data      = new stdClass;
		$this->url = preg_replace("/limitstart{$this->id}=(.*)?(&|)/", '', $this->url);
		$this->url = FabrikString::rtrimword($this->url, "&");

		// $$$ hugh - need to work out if we need & or ?
		$sepchar        = strstr($this->url, '?') ? '&amp;' : '?';
		$data->all      = new JPaginationObject(FText::_('COM_FABRIK_VIEW_ALL'));
		$data->all->key = 'all';

		if (!$this->viewAll)
		{
			$data->all->base = '0';
			$data->all->link = JRoute::_("{$sepchar}limitstart=");
		}

		// Set the start and previous data objects
		$data->start         = new JPaginationObject(FText::_('COM_FABRIK_START'));
		$data->start->key    = 'start';
		$data->previous      = new JPaginationObject(FText::_('COM_FABRIK_PREV'));
		$data->previous->key = 'previous';

		if ($this->get('pages.current') > 1)
		{
			$page                 = ($this->get('pages.current') - 2) * $this->limit;
			$data->start->base    = '0';
			$data->start->link    = JRoute::_($this->url . "{$sepchar}limitstart{$this->id}=0");
			$data->previous->base = $page;
			$data->previous->link = JRoute::_($this->url . "{$sepchar}limitstart{$this->id}=" . $page);
			$data->start->link    = str_replace('resetfilters=1', '', $data->start->link);
			$data->previous->link = str_replace('resetfilters=1', '', $data->previous->link);
			$data->start->link    = str_replace('clearordering=1', '', $data->start->link);
			$data->previous->link = str_replace('clearordering=1', '', $data->previous->link);
		}

		// Set the next and end data objects
		$data->next      = new JPaginationObject(FText::_('COM_FABRIK_NEXT'));
		$data->next->key = 'next';
		$data->end       = new JPaginationObject(FText::_('COM_FABRIK_END'));
		$data->end->key  = 'end';

		if ($this->get('pages.current') < $this->get('pages.total'))
		{
			$next             = $this->get('pages.current') * $this->limit;
			$end              = ($this->get('pages.total') - 1) * $this->limit;
			$data->next->base = $next;
			$data->next->link = JRoute::_($this->url . "{$sepchar}limitstart{$this->id}=" . $next);
			$data->end->base  = $end;
			$data->end->link  = JRoute::_($this->url . "{$sepchar}limitstart{$this->id}=" . $end);
			$data->next->link = str_replace('resetfilters=1', '', $data->next->link);
			$data->end->link  = str_replace('resetfilters=1', '', $data->end->link);
			$data->next->link = str_replace('clearordering=1', '', $data->next->link);
			$data->end->link  = str_replace('clearordering=1', '', $data->end->link);
		}

		$data->pages = array();
		$stop        = $this->get('pages.stop');

		for ($i = $this->get('pages.start'); $i <= $stop; $i++)
		{
			$offset               = ($i - 1) * $this->limit;
			$data->pages[$i]      = new JPaginationObject($i);
			$data->pages[$i]->key = $i;

			if ($i != $this->get('pages.current') || $this->viewAll)
			{
				$data->pages[$i]->base = $offset;
				$data->pages[$i]->link = JRoute::_($this->url . "{$sepchar}limitstart{$this->id}=" . $offset);
				$data->pages[$i]->link = str_replace('resetfilters=1', '', $data->pages[$i]->link);
				$data->pages[$i]->link = str_replace('clearordering=1', '', $data->pages[$i]->link);
			}
		}

		return $data;
	}

	/**
	 * Create the HTML for a list footer
	 *
	 * @param   array $list Pagination list data structure.
	 *
	 * @return  string  HTML for a list footer
	 */
	protected function _list_footer($list)
	{
		$limitLabel = $this->showDisplayNum ? FText::_('COM_FABRIK_DISPLAY_NUM') : '';

		// Initialize variables
		$paths                     = array();
		$displayData               = new stdClass;
		$displayData->id           = $this->id;
		$displayData->label        = $limitLabel;
		$displayData->value        = $list['limitstart'];
		$displayData->list         = $list['limitfield'];
		$displayData->pagesCounter = $list['pagescounter'];
		$displayData->listName     = 'limit' . $this->id;
		$displayData->links        = $list['pageslinks'];
		$displayData->showNav      = $this->showNav;
		$displayData->showTotal    = $this->showTotal;

		$paths[] = JPATH_THEMES . '/' . JFactory::getApplication()->getTemplate() . '/html/layouts/com_fabrik/list_' . $this->id;

		$layout = FabrikHelperHTML::getLayout('pagination.fabrik-pagination-footer', $paths);

		return $layout->render($displayData);
	}

	/**
	 * Returns a property of the object or the default value if the property is not set.
	 * Avoids deprecated notices in 3.1 whilst maintaining backwards compat
	 *
	 * @param   string $property The name of the property.
	 * @param   mixed  $default  The default value.
	 *
	 * @return  mixed    The value of the property.
	 *
	 * @since       12.2
	 * @deprecated  13.3  Access the properties directly.
	 */
	public function get($property, $default = null)
	{
		$version = new JVersion;

		if ($version->RELEASE > 2.5)
		{
			if (strpos($property, '.'))
			{
				$prop     = explode('.', $property);
				$prop[1]  = ucfirst($prop[1]);
				$property = implode($prop);
			}

			if (isset($this->$property))
			{
				return $this->$property;
			}

			return $default;
		}
		elseif (isset($this->$property))
		{
			return $this->$property;
		}
		else
		{
			return $default;
		}
	}
}
