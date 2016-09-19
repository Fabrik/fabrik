<?php
/**
 * Fabrik List Filter Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

/**
 * List filter model
 *
 * @package  Fabrik
 * @since    3.0
 */
class FabrikFEModelListfilter extends FabModel
{
	/**
	 * Request
	 *
	 * @var array
	 */
	protected $request = null;

	/**
	 * List model
	 *
	 * @var FabrikFEModelList
	 */
	protected $listModel;

	/**
	 * Set the list model
	 *
	 * @param   FabrikFEModelList  $model  list model
	 *
	 * @return  void
	 */
	public function setListModel($model)
	{
		$this->listModel = $model;
	}

	/**
	 * get the table from the listModel
	 *
	 * @param   string  $name     table name
	 * @param   string  $prefix   prefix name
	 * @param   array   $options  config
	 *
	 * @return void
	 */
	public function getTable($name = '', $prefix = 'Table', $options = array())
	{
		return $this->listModel->getTable();
	}

	/**
	 * $$$ rob activelistid set in content plugin only clear filters on active list (otherwise with n tables in article all qs filters are removed)
	 *
	 * @return  bool - is the list currently being rendered the list that initially triggered the filter
	 */
	protected function activeTable()
	{
		$input = $this->app->input;

		return $input->getInt('id') == $input->getInt('activelistid') || $input->get('activelistid') == '';
	}

	/**
	 * unset request
	 *
	 * @return  void
	 */
	public function destroyRequest()
	{
		unset($this->request);
	}

	/**
	 * This merges session data for the fromForm with any request data
	 * allowing us to filter data results from both search forms and filters
	 *
	 * @return array
	 */
	public function getFilters()
	{
		$input = $this->app->input;

		// Form or detailed views should not apply filters? what about querystrings to set up the default values?
		if ($input->get('view') == 'details' || $input->get('view') == 'form')
		{
			$this->request = array();

			return $this->request;
		}

		if (isset($this->request))
		{
			return $this->request;
		}

		$profiler = JProfiler::getInstance('Application');
		$filters = array();

		// $$$ rob clears all list filters, and does NOT apply any
		// other filters to the table, even if in querystring
		if ($input->getInt('clearfilters') === 1 && $this->activeTable())
		{
			$this->clearFilters();
			$this->request = array();

			return $this->request;
		}

		if ($input->get('replacefilters') == 1)
		{
			$this->clearFilters();
		}

		/**
		 * $$$ fehers The filter is cleared and applied at once without having to clear it first and then apply it (would have to be two clicks).
		 * useful in querystring filters if you want to clear old filters and apply new filters
		 */

		// $$$ rob 20/03/2011 - request resetfilters should overwrite menu option - otherwise filter then nav will remove filter.
		if (
			!$input->get('incfilters', 0)
			&& ($input->get('filterclear') == 1 || FabrikWorker::getMenuOrRequestVar('resetfilters', 0, false, 'request') == 1)
			&& $this->activeTable())
		{
			$this->clearFilters();
		}

		JDEBUG ? $profiler->mark('listfilter:cleared') : null;

		// Overwrite filters with querystring filter
		$this->getQuerystringFilters($filters);
		JDEBUG ? $profiler->mark('listfilter:querystring filters got') : null;
		FabrikHelperHTML::debug($filters, 'filter array: after querystring filters');
		$request = $this->getPostFilterArray();
		JDEBUG ? $profiler->mark('listfilter:request got') : null;
		$this->counter = count(FArrayHelper::getValue($request, 'key', array()));

		// Overwrite filters with session filters (fabrik_incsessionfilters set to false in listModel::getRecordCounts / for faceted data counts
		if ($input->get('fabrik_incsessionfilters', true))
		{
			$this->getSessionFilters($filters);
		}

		FabrikHelperHTML::debug($filters, 'filter array: after session filters');
		JDEBUG ? $profiler->mark('listfilter:session filters got') : null;

		// The search form search all has lower priority than the filter search all and search form filters
		$this->getSearchFormSearchAllFilters($filters);

		// Overwrite session filters with search form filters
		$this->getSearchFormFilters($filters);
		FabrikHelperHTML::debug($filters, 'filter array: search form');

		// Overwrite filters with 'search all' filter
		$this->getSearchAllFilters($filters);
		JDEBUG ? $profiler->mark('listfilter:search all done') : null;

		// Finally overwrite filters with post filters
		$this->getPostFilters($filters);
		JDEBUG ? $profiler->mark('listfilter:post filters got') : null;
		FabrikHelperHTML::debug($filters, 'filter array: after getpostfilters');
		$this->request = $filters;
		FabrikHelperHTML::debug($this->request, 'filter array');
		$this->checkAccess($filters);
		$this->normalizeKeys($filters);

		return $filters;
	}

	/**
	 * With prefilter and search all - 2nd time you use the search all the array keys
	 * seem incorrect - resulting in an incorrect query.
	 * Use this to force each $filter['property'] array to start at 0 and increment
	 *
	 * @param   array  &$filters  list filters
	 *
	 * @since   3.0.6
	 *
	 * @return  void
	 */
	private function normalizeKeys(&$filters)
	{
		$properties = array_keys($filters);

		foreach ($properties as $property)
		{
			if (is_array($filters[$property]))
			{
				$filters[$property] = array_values($filters[$property]);
			}
			else
			{
				$filters[$property] = array();
			}
		}
	}

	/**
	 * $$$ rob if the filter should not be applied due to its acl level then set its condition so that it
	 * will always return true. Do this rather than unsetting the filter - as this removes the selected option
	 * from the filter forms field. Can be used in conjunction with a list filter plugin to override a normal filters option with the
	 * plugins option, e.g. load all university's courses OR [plugin option] load remote courses run by selected university
	 * e.g http://www.epics-ve.eu/index.php?option=com_fabrik&view=list&listid=5
	 *
	 * @param   array  &$filters  list filters
	 *
	 * @return  void
	 */
	public function checkAccess(&$filters)
	{
		$access = FArrayHelper::getValue($filters, 'access', array());
		$viewLevels = $this->user->getAuthorisedViewLevels();

		foreach ($access as $key => $selAccess)
		{
			// $$$ hugh - fix for where certain elements got created with 0 as the
			// the default for filter_access, which isn't a legal value, should be 1
			$selAccess = $selAccess == '0' ? '1' : $selAccess;
			$i = $filters['key'][$key];

			if (!in_array($selAccess, $viewLevels))
			{
				$filters['sqlCond'][$key] = '1=1';
			}
		}

		FabrikHelperHTML::debug($filters, 'filter array: after access taken into account');
	}

	/**
	 * get the search all posted (or session) value
	 *
	 * @param   string  $mode  html (performs htmlspecialchars on value) OR 'query' (adds slashes and url decodes)
	 *
	 * @return  string
	 */
	public function getSearchAllValue($mode = 'html')
	{
		$identifier = $this->listModel->getRenderContext();

		// Test new option to have one field to search them all
		$key = 'com_' . $this->package . '.list' . $identifier . '.filter.searchall';

		// Seems like post keys 'name.1' get turned into 'name_1'
		$requestKey = $this->getSearchAllRequestKey();
		$v = $this->app->getUserStateFromRequest($key, $requestKey);

		if (trim($v) == '')
		{
			$fromFormId = $this->app->getUserState('com_' . $this->package . '.searchform.fromForm');

			if ($fromFormId != $this->listModel->getFormModel()->getForm()->id)
			{
				$v = $this->app->getUserState('com_' . $this->package . '.searchform.form' . $fromFormId . '.searchall');
			}
		}

		$v = $mode == 'html' ? htmlspecialchars($v, ENT_QUOTES) : addslashes(urldecode($v));

		return $v;
	}

	/**
	 * small method just to return the inout name for the lists search all field
	 *
	 * @return string
	 */

	public function getSearchAllRequestKey()
	{
		$identifier = $this->listModel->getRenderContext();

		return 'fabrik_list_filter_all_' . $identifier;
	}

	/**
	 * Check if the search all field (name=fabrik_list_filter_all) has submitted data
	 *
	 * If it has then go through all elements, and add in a filter
	 * for each element whose data type matches the search type
	 * (e.g. if searching a string then ignore int() fields)
	 *
	 * If another filter has posted some data then don't add in a 'search all' record for that filter
	 *
	 * @param   array  &$filters  filter array
	 *
	 * @return  void
	 */
	private function getSearchAllFilters(&$filters)
	{
		$input = $this->app->input;
		$requestKey = $this->getSearchAllRequestKey();
		$search = $this->getSearchAllValue('query');

		if ($search == '')
		{
			if (array_key_exists($requestKey, $_POST))
			{
				// Empty search string sent unset any searchall filters
				$ks = array_keys($filters);
				$filterKeys = array_keys(FArrayHelper::getValue($filters, 'search_type', array()));

				foreach ($filterKeys as $filterKey)
				{
					if (FArrayHelper::getValue($filters['search_type'], $filterKey, '') == 'searchall')
					{
						foreach ($ks as $k)
						{
							/**
							 * $$$ rob 10/04/2012  simply unsetting the array leaves the array pointer, but somewhere we recreate
							 * $filters['search_type'] so its index becomes out of sync. see http://fabrikar.com/forums/showthread.php?t=25698
							 * unset($filters[$k][$filterKey]);
							 */
							$filters[$k] = array();
						}
					}
				}
			}
		}

		if ($search == '')
		{
			// Clear full text search all
			if (array_key_exists($requestKey, $_POST))
			{
				$this->clearAFilter($filters, 9999);
			}

			return;
		}

		$listId = $input->getInt('listid', -1);

		// Check that we actually have the correct list id (or -1 if filter from viz)
		if ($this->listModel->getTable()->id == $listId || $listId == -1)
		{
			if ($this->listModel->getParams()->get('search-mode-advanced'))
			{
				$this->doBooleanSearch($filters, $search);
			}
			else
			{
				$this->insertSearchAllIntoFilters($filters, $search);
			}
		}
	}

	/**
	 * clear specific filter data all from filters
	 *
	 * @param   array  &$filters  array filters
	 * @param   int    $id        index
	 *
	 * @return  void
	 */
	public function clearAFilter(&$filters, $id)
	{
		$keys = array_keys($filters);

		foreach ($keys as $key)
		{
			/**
			 * $$$ hugh - couple of folk have reported getting PHP error "Cannot unset string offsets"
			 * which means sometimes $filters->foo is a string.  Putting a band-aid on it for now,
			 * but really should try and find out why sometimes we have strings rather than arrays.
			 */
			if (is_array($filters[$key]))
			{
				unset($filters[$key][$id]);
			}
		}
	}

	/**
	 * For extended search all test if the search string is long enough
	 *
	 * @param   string  $s  search string
	 *
	 * @since 3.0.6
	 *
	 * @throws UnexpectedValueException
	 *
	 * @return  bool	search string long enough?
	 */
	protected function testBooleanSearchLength($s)
	{
		$this->_db->setQuery('SHOW VARIABLES LIKE \'ft_min_word_len\'');
		$res = $this->_db->loadObject();

		if (!JString::strlen($s) >= $res->Value)
		{
			throw new UnexpectedValueException(FText::_('COM_FABRIK_NOTICE_SEARCH_STRING_TOO_SHORT'));
		}

		return true;
	}

	/**
	 * Do a boolean search
	 *
	 * @param   array   &$filters  filter array
	 * @param   string  $search    term
	 *
	 * @return  void
	 */
	private function doBooleanSearch(&$filters, $search)
	{
		$input = $this->app->input;
		$mode = $input->get('search-mode-advanced', 'and');

		if (trim($search) == '')
		{
			return;
		}

		$this->testBooleanSearchLength($search);
		$search = explode(' ', $search);

		switch ($mode)
		{
			case 'all':
				$operator = '+';
				break;
			case 'none':
				$operator = '-';
				break;
			default:
			case 'exact':
			case 'any':
				$operator = '';
				break;
		}

		foreach ($search as &$s)
		{
			$s = $operator . $s . '*';
		}

		$search = implode(' ', $search);

		if ($mode == 'exact')
		{
			$search = '"' . $search . '"';
		}

		if ($mode == 'none')
		{
			/**
			 * Have to do it like this as the -operator removes records matched from
			 * previous +operators (so if you just have -operator)
			 * no records are returned
			 */
			$search = '+(a* b* c* d* e* f* g* h* i* j* k* l* m* n* o* p* q* r* s* t* u* v* w* x* y* z*) ' . $search;
		}

		$input->set('override_join_val_column_concat', 1);
		$names = $this->listModel->getSearchAllFields();

		if (empty($names))
		{
			return;
		}

		$input->set('override_join_val_column_concat', 0);
		$names = implode(", ", $names);
		$filters['value'][9999] = $search;
		$filters['condition'][9999] = 'AGAINST';
		$filters['join'][9999] = 'AND';
		$filters['no-filter-setup'][9999] = 0;
		$filters['hidden'][9999] = 0;
		$filters['key'][9999] = "MATCH(" . $names . ")";
		$filters['key2'][9999] = "MATCH(" . $names . ")";
		$filters['search_type'][9999] = 'searchall';
		$filters['match'][9999] = 1;
		$filters['full_words_only'][9999] = 0;
		$filters['eval'][9999] = 0;
		$filters['required'][9999] = 0;
		$filters['access'][9999] = 0;
		$filters['grouped_to_previous'][9999] = 1;
		$filters['label'][9999] = '';
		$filters['elementid'][9999] = -1;
		$filters['raw'][9999] = false;
	}

	/**
	 * removes any search or filters from the list
	 *
	 * @return  void
	 */
	public function clearFilters()
	{
		$registry = $this->session->get('registry');
		$id = $this->app->input->get('listref', $this->listModel->getRenderContext());
		$tid = 'list' . $id;
		$listContext = 'com_' . $this->package . '.list' . $id . '.';
		$context = $listContext . 'filter';
		$this->app->setUserState($listContext . 'limitstart', 0);

		if (!is_object($registry))
		{
			return;
		}

		$reg = $registry->get($context, new stdClass);
		/**
		 * $$$ rob jpluginfilters search_types are those which have been set inside the
		 * Joomla content plugin e.g. {fabrik view=list id=1 tablename___elementname=foo}
		 * these should not be removed when the list filters are cleared
		 * see:
		 * http://fabrikar.com
		 * /forums/index.php?threads/many-to-many-relationship-show-all-related-items-as-list-on-the-joined-list-details.36697/
		 * #post-184335
		 */
		$reg = ArrayHelper::fromObject($reg);
		$searchTypes = FArrayHelper::getValue($reg, 'search_type', array());

		for ($i = 0; $i < count($searchTypes); $i++)
		{
			if ($this->canClear($searchTypes[$i]))
			{
				$this->clearAFilter($reg, $i);
			}
		}

		$reg['searchall'] = '';
		$reg = ArrayHelper::toObject($reg);
		$registry->set($context, $reg);
		$reg = $registry->get($context, new stdClass);

		// Reset plugin filter
		if (isset($registry->_registry['com_' . $this->package]['data']->$tid->plugins))
		{
			unset($registry->_registry['com_' . $this->package]['data']->$tid->plugins);
		}

		$key = 'com_' . $this->package . '.' . $tid . '.searchall';
		$this->app->setUserState($key, '');
		$fromFormId = $this->app->getUserState('com_' . $this->package . '.searchform.fromForm');

		if ($fromFormId != $this->listModel->getFormModel()->get('id'))
		{
			$this->app->setUserState('com_' . $this->package . '.searchform.form' . $fromFormId . '.searchall', '');
		}
	}

	/**
	 * Can we clear a filter.
	 * Filters set by the content plugin ($searchType == jpluginfilters) can only be unset if you are not viewing the content plugin but instead
	 * a menu item, which points at the same list AND when that menu item has its resetfilters option set to yes.
	 *
	 * @param   string  $searchType  Search type string
	 *
	 * @return boolean
	 */
	protected function canClear($searchType)
	{
		if (!$this->app->isAdmin() && $this->activeTable())
		{
			$menus = $this->app->getMenu();
			$menu = $menus->getActive();

			if (is_object($menu))
			{
				if ($menu->params->get('resetfilters') == 1)
				{
					return true;
				}
			}
		}

		return $searchType === 'jpluginfilters' ? false : true;
	}

	/**
	 * Get users default access level
	 *
	 * @return  int  access level
	 */
	protected function defaultAccessLevel()
	{
		$accessLevels = $this->user->getAuthorisedViewLevels();

		return FArrayHelper::getValue($accessLevels, 0, 1);
	}

	/**
	 * Insert search all string into filters
	 *
	 * @param   array   &$filters  list filters
	 * @param   string  $search    search string
	 *
	 * @return null
	 */
	private function insertSearchAllIntoFilters(&$filters, $search)
	{
		$elements = $this->listModel->getElements('id', false);
		$keys = array_keys($elements);
		$i = 0;
		$condition = 'REGEXP';
		$orig_search = $search;
		$searchable = false;

		foreach ($keys as $elid)
		{
			// $$$ hugh - need to reset $search each time round, in case getFilterValue has escaped something,
			// like foo.exe to foo\\\.exe ... otherwise each time round we double the number of \s's
			$search = $orig_search;
			$elementModel = $elements[$elid];

			if (!$elementModel->includeInSearchAll(false, $search))
			{
				continue;
			}

			$searchable = true;
			$k = $elementModel->getFullName(false, false);
			$k = FabrikString::safeColName($k);

			// Lower case for search on accented characters e.g. Ö
			$k = 'LOWER(' . $k . ')';

			$key = array_key_exists('key', $filters) ? array_search($k, $filters['key']) : false;

			/**
			 * $$$ rob 28/06/2011 see http://fabrikar.com/forums/showthread.php?t=26006
			 * This line was setting eval to 1 as array_search returns the key, think we want the value
			 */
			// $eval = array_key_exists('eval', $filters) ? array_search($k, $filters['eval']) : FABRIKFILTER_TEXT;
			$eval = array_key_exists('eval', $filters) ? FArrayHelper::getValue($filters['eval'], $key, FABRIKFILTER_TEXT) : FABRIKFILTER_TEXT;

			if (!is_a($elementModel, 'PlgFabrik_ElementDatabasejoin'))
			{
				$fieldDesc = $elementModel->getFieldDescription();

				if (JString::stristr($fieldDesc, 'INT'))
				{
					if (is_numeric($search) && $condition == '=')
					{
						$eval = FABRKFILTER_NOQUOTES;
					}
				}

				$k2 = null;
			}
			else
			{
				if ($elementModel->isJoin())
				{
					$k2 = $elementModel->buildQueryElementConcat('', false);
				}
				else
				{
					$k2 = $elementModel->getJoinLabelColumn();
				}

				$k = $k2 = 'LOWER(' . $k2 . ')';
			}

			// Retest as $k has been modified and may now exist.
			if (!$key)
			{
				$key = array_key_exists('key', $filters) ? array_search($k, $filters['key']) : false;
			}

			$element = $elementModel->getElement();
			//$elParams = $elementModel->getParams();
			$access = $this->defaultAccessLevel();

			// $$$ rob so search all on checkboxes/radio buttons etc. will take the search value of 'one' and return '1'
			$newSearch = $elementModel->getFilterValue($search, $condition, $eval);
			$newCondition = $newSearch[1];
			$newSearch = $newSearch[0];


			if ($key !== false)
			{
				$filters['orig_condition'][$key] = $condition;
				$filters['value'][$key] = $newSearch;
				$filters['condition'][$key] = $newCondition;
				$filters['join'][$key] = 'OR';
				$filters['no-filter-setup'][$key] = ($element->filter_type == '') ? 1 : 0;
				$filters['hidden'][$key] = ($element->filter_type == '') ? 1 : 0;
				$filters['key'][$key] = $k;
				$filters['key2'][$key] = $k2;
				$filters['search_type'][$key] = 'searchall';
				$filters['match'][$key] = 1;
				$filters['full_words_only'][$key] = 0;
				$filters['eval'][$key] = $eval;
				$filters['required'][$key] = 0;
				$filters['access'][$key] = $access;
				/**
				 * $$$ rob 16/06/2011 - changed this. If search all and search on post then change post filter.
				 * The grouped_to_previous was being set from 1 to 0 - giving
				 * incorrect query. AFAICT grouped_to_previous should always be 1 for search_all.
				 * And testing if the element name = 0 seems v wrong :)
				 */
				// $filters['grouped_to_previous'][$key] = $k == 0 ? 0 : 1;
				$filters['grouped_to_previous'][$key] = 1;
				$filters['label'][$key] = $elementModel->getListHeading();
				$filters['raw'][$key] = false;
			}
			else
			{
				$filters['orig_condition'][] = $condition;
				$filters['value'][] = $newSearch;
				$filters['condition'][] = $condition;
				$filters['join'][] = 'OR';
				$filters['no-filter-setup'][] = ($element->filter_type == '') ? 1 : 0;
				$filters['hidden'][] = ($element->filter_type == '') ? 1 : 0;
				$filters['key'][] = $k;
				$filters['key2'][] = $k2;
				$filters['search_type'][] = 'searchall';
				$filters['match'][] = 1;
				$filters['full_words_only'][] = 0;
				$filters['eval'][] = $eval;
				$filters['required'][] = 0;
				$filters['access'][] = $access;
				/**
				 * $$$ rob having grouped_to_previous as 1 was barfing this list view for beate, when doing a search all:
				 * http://test.xx-factory.de/index.php?option=com_fabrik&view=list&listid=31&calculations=0&Itemid=16&resetfilters=0
				 */
				// $filters['grouped_to_previous'][] = 0;//1;

				/**
				 * $$$ rob 16/06/2011 - Yeah but no! - if you have search all AND a post filter -
				 * the post filter should filter a subset of the search
				 * all data, so setting grouped_to_previous to 1 gives you a query of:
				 * where (el = 'searchall' OR el = 'searchall') AND el = 'post value'
				 */
				$filters['grouped_to_previous'][] = 1;
				$filters['label'][] = $elementModel->getListHeading();
				$filters['elementid'][] = $element->id;
				$filters['raw'][] = false;
			}

			$i++;
		}

		if (!$searchable)
		{
			$this->app->enqueueMessage(FText::_('COM_FABRIK_NOTICE_SEARCH_ALL_BUT_NO_ELEMENTS'));
		}
	}

	/**
	 * Insert search form's search all filters
	 *
	 * @param   array  &$filters  list filters
	 *
	 * @return  void
	 */
	private function getSearchFormSearchAllFilters(&$filters)
	{
		// See if there was a search all created from a search form
		$formModel = $this->listModel->getFormModel();
		$key = 'com_' . $this->package . '.searchform.fromForm';
		$fromFormId = $this->app->getUserState($key);

		if ($fromFormId != $formModel->getId())
		{
			$search = $this->app->getUserState('com_' . $this->package . '.searchform.form' . $fromFormId . '.searchall');

			if (trim($search) == '')
			{
				return;
			}

			$this->insertSearchAllIntoFilters($filters, $search);
		}
	}

	/**
	 * Get search form id
	 *
	 * @return  int  search form id
	 */
	private function getSearchFormId()
	{
		$key = 'com_' . $this->package . '.searchform.fromForm';

		return $this->app->getUserState($key);
	}

	/**
	 * Set search form id
	 *
	 * @param   int  $id  form id
	 *
	 * @return  void
	 */
	private function setSearchFormId($id = null)
	{
		$key = 'com_' . $this->package . '.searchform.fromForm';
		$this->app->setUserState($key, $id);
	}

	/**
	 * Get search form filters
	 *
	 * @param   array  &$filters  list filters
	 *
	 * @return  void
	 */
	private function getSearchFormFilters(&$filters)
	{
		$fromFormId = $this->getSearchFormId();

		if (!empty($fromFormId))
		{
			$formModel = $this->listModel->getFormModel();
			$db = FabrikWorker::getDbo();
			$lookupKeys = FArrayHelper::getValue($filters, 'key', array());

			if ($fromFormId != $formModel->get('id'))
			{
				$fromForm = JModelLegacy::getInstance('Form', 'FabrikFEModel');
				$fromForm->setId($fromFormId);
				//$fromFormParams = $fromForm->getParams();
				/**
				 * $$$ hugh Added $filter_elements from 'filter_name'
				 * which we'll need in the case of $elid not being in $elements for search forms
				 */
				$elements = $this->listModel->getElements('id');
				$filter_elements = $this->listModel->getElements('filtername');
				$tableName = $db->qn($this->listModel->getTable()->db_table_name);
				$searchFilters = $this->app->getUserState('com_' . $this->package . '.searchform.form' . $fromFormId . '.filters');

				// check if key set, might not be if (say) checkbox filters on search form with no selection
				if (array_key_exists('key', $searchFilters))
				{
					for ($i = 0; $i < count($searchFilters['key']); $i++)
					{
						$eval  = FABRIKFILTER_TEXT;
						$found = false;
						$key   = $searchFilters['key'][$i];
						$elid  = $searchFilters['elementid'][$i];

						if (array_key_exists($elid, $elements))
						{
							$found        = true;
							$elementModel = $elements[$elid];
						}
						else
						{
							// If sent from a search form - the table name will be blank
							$key = explode('.', $key);
							$key = $tableName . '.' . array_pop($key);

							if (array_key_exists($key, $filter_elements))
							{
								$found        = true;
								$elementModel = $filter_elements["$key"];
							}
							else
							{
								// $$$ rob - I've not actually tested this code
								$joins = $this->listModel->getJoins();

								foreach ($joins as $join)
								{
									$key = $db->qn($join->table_join) . '.' . array_pop(explode('.', $key));

									if (array_key_exists($key, $filter_elements))
									{
										$found        = true;
										$elementModel = $filter_elements[$key];
										break;
									}
								}
							}
						}

						if (!isset($elementModel) || !is_a($elementModel, 'plgFabrik_Element') || $found === false)
						{
							// Could be looking for an element which exists in a join
							continue;
						}

						$index    = array_key_exists('key', $filters) ? array_search($key, $lookupKeys) : false;
						$element  = $elementModel->getElement();
						$elParams = $elementModel->getParams();
						$grouped  = array_key_exists($i, $searchFilters['grouped_to_previous']) ? $searchFilters['grouped_to_previous'][$i] : 0;

						$join = $searchFilters['join'][$i];

						if ($index === false)
						{
							$filters['value'][]               = $searchFilters['value'][$i];
							$filters['condition'][]           = $elementModel->getDefaultFilterCondition();
							$filters['join'][]                = $join;
							$filters['no-filter-setup'][]     = ($element->filter_type == '') ? 1 : 0;
							$filters['hidden'][]              = ($element->filter_type == '') ? 1 : 0;
							$filters['key'][]                 = $key;
							$filters['search_type'][]         = 'search';
							$filters['match'][]               = $element->filter_exact_match;
							$filters['full_words_only'][]     = $elParams->get('full_words_only');
							$filters['eval'][]                = $eval;
							$filters['required'][]            = $elParams->get('filter_required');
							$filters['access'][]              = $elParams->get('filter_access');
							$filters['grouped_to_previous'][] = $grouped;
							$filters['label'][]               = $elementModel->getListHeading();
							$filters['raw'][]                 = false;
						}
						else
						{
							unset($lookupKeys[$index]);
							$filters['value'][$index]               = $searchFilters['value'][$i];
							$filters['condition'][$index]           = $elementModel->getDefaultFilterCondition();
							$filters['join'][$index]                = $join;
							$filters['no-filter-setup'][$index]     = ($element->filter_type == '') ? 1 : 0;
							$filters['hidden'][$index]              = ($element->filter_type == '') ? 1 : 0;
							$filters['key'][$index]                 = $key;
							$filters['search_type'][$index]         = 'search';
							$filters['match'][$index]               = $element->filter_exact_match;
							$filters['full_words_only'][$index]     = $elParams->get('full_words_only');
							$filters['eval'][$index]                = $eval;
							$filters['required'][$index]            = $elParams->get('filter_required');
							$filters['access'][$index]              = $elParams->get('filter_access');
							$filters['grouped_to_previous'][$index] = $grouped;
							$filters['label'][$index]               = $elementModel->getListHeading();
							$filters['raw'][$index]                 = false;
						}

						$filters['elementid'][] = $element->id;
					}
				}
			}

			/**
			 * unset the search form id so we wont reuse the search data
			 * until a new search is performed
			 */
			$this->setSearchFormId(null);
		}
	}

	/**
	 * Get any querystring filters that can be applied to the list
	 * you can simple do tablename___elementname=value
	 * or if you want more control you can do
	 *
	 * tablename___elementname[value]=value&tablename_elementname[condition]=OR etc.
	 *
	 * @param   array  &$filters  list filters
	 *
	 * @return  void
	 */
	private function getQuerystringFilters(&$filters)
	{
		//$item = $this->listModel->getTable();
		$filter = JFilterInput::getInstance();
		$request = $filter->clean($_GET, 'array');
		$formModel = $this->listModel->getFormModel();
		$filterKeys = array_keys($filters);

		foreach ($request as $key => $val)
		{
			$oldKey = $key;
			$key = FabrikString::safeColName($key);
			$index = array_key_exists('key', $filters) ? array_search($key, $filters['key']) : false;

			if ($index !== false)
			{
				foreach ($filterKeys as $fKey)
				{
					if (is_array($filters[$fKey]) && array_key_exists($index, $filters[$fKey]))
					{
						unset($filters[$fKey][$index]);

						// Reindex array
						$filters[$fKey] = array_values($filters[$fKey]);
					}
				}
			}

			$raw = 0;

			if (substr($oldKey, -4, 4) == '_raw')
			{
				$raw = 1;

				// Without this line related data links 'listname___elementname_raw=X' where not having their filter applied
				$key = FabrikString::safeColName(FabrikString::rtrimword($oldKey, '_raw'));
			}

			$elementModel = $formModel->getElement(FabrikString::rtrimword($oldKey, '_raw'), false, false);

			if (!is_a($elementModel, 'PlgFabrik_Element'))
			{
				continue;
			}

			$eval = is_array($val) ? FArrayHelper::getValue($val, 'eval', FABRIKFILTER_TEXT) : FABRIKFILTER_TEXT;
			$condition = is_array($val) ? FArrayHelper::getValue($val, 'condition', $elementModel->getDefaultFilterCondition())
				: $elementModel->getDefaultFilterCondition();

			{
				$fieldDesc = $elementModel->getFieldDescription();

				if (JString::stristr($fieldDesc, 'INT'))
				{
					if (is_numeric($val) && $condition == '=')
					{
						$eval = FABRKFILTER_NOQUOTES;
					}
				}
			}

			// Add request filter to end of filter array
			if (is_array($val))
			{
				$value = FArrayHelper::getValue($val, 'value', '');
				$join = FArrayHelper::getValue($val, 'join', 'AND');
				$grouped = FArrayHelper::getValue($val, 'grouped_to_previous', 0);

				/**
				 * do a ranged querystring search with this syntax
				 * ?element_test___time_date[value][]=2009-08-07&element_test___time_date[value][]=2009-08-10&element_test___time_date[condition]=BETWEEN
				 */
				if (is_array($value) && strtoupper($condition) !== 'BETWEEN' && strtoupper($condition) !== 'IN')
				{
					// If we aren't doing a ranged search
					foreach ($value as $vk => $avalue)
					{
						// If � entered in qs then that is converted to %E9 which urldecode will convert back
						$value = addslashes(urldecode($avalue));
						$acondition = (is_array($condition) && array_key_exists($vk, $condition)) ? $condition[$vk] : $condition;
						$ajoin = (is_array($join) && array_key_exists($vk, $join)) ? $join[$vk] : $join;
						$agrouped = (is_array($grouped) && array_key_exists($vk, $grouped)) ? $grouped[$vk] : $grouped;
						$this->indQueryString($elementModel, $filters, $avalue, $acondition, $ajoin, $agrouped, $eval, $key, $raw);
					}
				}
				else
				{
					if (is_string($value))
					{
						$value = addslashes(urldecode($value));
					}

					$this->indQueryString($elementModel, $filters, $value, $condition, $join, $grouped, $eval, $key, $raw);
				}
			}
			else
			{
				// If � entered in qs then that is converted to %E9 which urldecode will convert back
				$value = addslashes(urldecode($val));
				$join = 'AND';
				$grouped = 0;
				$this->indQueryString($elementModel, $filters, $value, $condition, $join, $grouped, $eval, $key, $raw);
			}
		}
	}

	/**
	 * Insert individual querystring filter into filter array
	 *
	 * @param   object  $elementModel  element model
	 * @param   array   &$filters      filter array
	 * @param   mixed   $value         value
	 * @param   string  $condition     condition
	 * @param   string  $join          join
	 * @param   bool    $grouped       is grouped
	 * @param   bool    $eval          is eval
	 * @param   string  $key           element key
	 * @param   bool    $raw           is the filter a raw filter (tablename___elementname_raw=foo)
	 *
	 * @return  void
	 */
	private function indQueryString($elementModel, &$filters, $value, $condition, $join, $grouped, $eval, $key, $raw = false)
	{
		$input = $this->app->input;
		$element = $elementModel->getElement();
		$elParams = $elementModel->getParams();

		if (is_string($value))
		{
			$value = trim($value);
		}

		$k2 = FabrikString::safeColNameToArrayKey($key);
		/**
		 * $$$ rob fabrik_sticky_filters set in J content plugin
		 * Treat these as prefilters so we don't unset them
		 * when we clear the filters
		 */
		$stickyFilters = $input->get('fabrik_sticky_filters', array(), 'array');
		$filterType = in_array($k2 . '_raw', $stickyFilters)
			|| in_array($k2, $stickyFilters) ? 'jpluginfilters' : 'querystring';
		$filters['value'][] = $value;
		$filters['condition'][] = urldecode($condition);
		$filters['join'][] = $join;
		$filters['no-filter-setup'][] = ($element->filter_type == '') ? 1 : 0;
		$filters['hidden'][] = ($element->filter_type == '') ? 1 : 0;
		$filters['key'][] = $key;
		$filters['key2'][] = '';
		$filters['search_type'][] = $filterType;
		$filters['match'][] = $element->filter_exact_match;
		$filters['full_words_only'][] = $elParams->get('full_words_only');
		$filters['eval'][] = $eval;
		$filters['required'][] = $elParams->get('filter_required');
		$filters['access'][] = $elParams->get('filter_access');
		$filters['grouped_to_previous'][] = $grouped;
		$filters['label'][] = $elementModel->getListHeading();
		$filters['elementid'][] = $element->id;
		$filters['raw'][] = $raw;
	}

	/**
	 * Get post filters
	 *
	 * @return  array
	 */
	private function getPostFilterArray()
	{
		if (!isset($this->request))
		{
			//$item = $this->listModel->getTable();
			$filter = JFilterInput::getInstance();
			$request = $filter->clean($_POST, 'array');
			/**
			 * Use request ONLY if you want to test an ajax post with params in url
			 * $request	= $filter->clean($_REQUEST, 'array');
			 */
			$k = 'list_' . $this->listModel->getRenderContext();

			if (array_key_exists('fabrik___filter', $request) && array_key_exists($k, $request['fabrik___filter']))
			{
				$this->request = $request['fabrik___filter'][$k];
			}
			else
			{
				$this->request = array();
			}
		}

		return $this->request;
	}

	/**
	 * Overwrite session and search all filters with posted data
	 *
	 * @param   array  &$filters  filter array
	 *
	 * @since   3.5.1
	 *
	 * @throws  UnexpectedValueException
	 *
	 * @return  void
	 */
	private function getPostFilters(&$filters)
	{
		$input = $this->app->input;
		//$item = $this->listModel->getTable();
		$request = $this->getPostFilterArray();
		$elements = $this->listModel->getElements('id');
		$filterKeys = array_keys($filters);
		$values = FArrayHelper::getValue($request, 'value', array());
		$searchTypes = FArrayHelper::getValue($filters, 'search_type', array());
		$conditions = FArrayHelper::getValue($request, 'condition', array());

		$usedMerges = array();

		FabrikHelperHTML::debug($filters, 'filter array: start getPostFilters');

		if (!empty($request) && array_key_exists('key', $request))
		{
			$keyInts = array_keys($request['key']);
			$ajaxPost = JString::strtolower($input->server->get('HTTP_X_REQUESTED_WITH'));
			$this->listModel->ajaxPost = $ajaxPost;
			$this->listModel->postValues = $values;

			foreach ($keyInts as $i)
			{
				$value = FArrayHelper::getValue($values, $i, '');
				$key = FArrayHelper::getValue($request['key'], $i);
				$elid = FArrayHelper::getValue($request['elementid'], $i);
				$condition = FArrayHelper::getValue($conditions, $i);

				if ($key == '')
				{
					continue;
				}

				// Index is the filter index for a previous filter that uses the same element id
				if (!in_array($elid, $usedMerges))
				{
					$index = array_key_exists('elementid', $filters) ? array_search($elid, (array) $filters['elementid']) : false;
				}
				else
				{
					$index = false;
				}

				if ($index !== false)
				{
					$usedMerges[] = $elid;
				}

				/**
				 * $$$rob empty post filters SHOULD overwrite previous filters, as the user has submitted
				 * this filter with nothing selected
				 */
				/*if (is_string($value) && trim($value) == '') {
				    continue;
				}
				 */

				// $$$ rob set a var for empty value - regardless of whether its an array or string
				$emptyValue =
					(
						(is_string($value) && trim($value) == '')
						|| (is_array($value) && trim(implode('', $value)) == '')
					)
					&& ($condition !== 'EMPTY' && $condition !== 'NOTEMPTY');

				/**
				 * $$rob ok the above meant that require filters stopped working as soon as you submitted
				 * an empty search!
				 * So now  add in the empty search IF there is NOT a previous filter in the search data
				 */
				if ($emptyValue && $index === false)
				{
					continue;
				}

				/**
				 * $$$ rob if we are posting an empty value then we really have to clear the filter out from the
				 * session. Otherwise the filter is run as "where field = ''"
				 */
				if ($emptyValue && $index !== false)
				{
					/*
					 * Testing clearing only if normal filter, previous test on searchType != 'searchall'
					 * meant advanced search filters were removed on page nav
					 */
					if (FArrayHelper::getValue($searchTypes, $index) == 'normal')
					{
						$this->clearAFilter($filters, $index);
					}
					// $$$ rob - regardless of whether the filter was added by search all or not - don't overwrite it with post filter
					continue;
				}

				$origCondition = $condition;
				$filters['orig_condition'][] = $condition;

				if ($condition === 'EMPTY')
				{
					$condition = '=';
					$value = '';
				}

				if ($condition === 'NOTEMPTY')
				{
					$condition = '<>';
					$value = '';
				}


				$elementModel = $elements[$elid];

				if (!is_a($elementModel, 'PlgFabrik_Element'))
				{
					continue;
				}

				// Date element's have specific empty values
				if ($origCondition === 'EMPTY' || $origCondition === 'NOTEMPTY')
				{
					$value = $elementModel->emptyFilterValue();
				}

				// If the request key is already in the filter array - unset it
				if ($index !== false)
				{
					foreach ($filterKeys as $fKey)
					{
						if (is_array($filters[$fKey]) && array_key_exists($index, $filters[$fKey]))
						{
							// Don't unset search all filters when the value is empty and continue so we don't add in a new filter
							if (FArrayHelper::getValue($searchTypes, $index) == 'searchall' && $value == '')
							{
								continue 2;
							}

							// $$$rob we DO need to unset
							unset($filters[$fKey][$index]);
						}
					}
				}

				if (is_array($value))
				{
					// Ensure the array is indexed starting at 0.
					$value = array_values($value);

					// Empty ranged data test
					if (FArrayHelper::getValue($value, 0) == '' && FArrayHelper::getValue($value, 1) == '')
					{
						continue;
					}
				}

				$eval = is_array($value) ? FArrayHelper::getValue($value, 'eval', FABRIKFILTER_TEXT) : FABRIKFILTER_TEXT;

				if (!is_a($elementModel, 'PlgFabrik_ElementDatabasejoin'))
				{
					$fieldDesc = $elementModel->getFieldDescription();

					if (JString::stristr($fieldDesc, 'INT'))
					{
						if (is_numeric($value) && $request['condition'][$i] == '=')
						{
							$eval = FABRKFILTER_NOQUOTES;
						}
					}
				}

				/**
				 * $$$ rob - search all and dropdown filter: Search first on searchall = usa, then select dropdown to usa.
				 * post filter query overwrites search all query, but uses add so = where id REGEX 'USA' AND country LIKE '%USA'
				 * this code swaps the first
				 */
				$joinMode = JString::strtolower($request['join'][$i]) != 'where' ? $request['join'][$i] : 'AND';

				if (!empty($filters))
				{
					if ($i == 0)
					{
						$joinModes = FArrayHelper::getValue($filters, 'join', array('AND'));
						$joinMode = array_pop($joinModes);

						// $$$ rob - If search all made, then the post filters should filter further the results
						$tmpSearchTypes = FArrayHelper::getValue($filters, 'search_type', array('normal'));
						$lastSearchType = array_pop($tmpSearchTypes);

						if ($lastSearchType == 'searchall')
						{
							$joinMode = 'AND';
						}
					}
				}

				// Add request filter to end of filter array
				$element = $elementModel->getElement();
				$elParams = $elementModel->getParams();
				$filters['value'][] = $value;
				$filters['condition'][] = urldecode($condition);
				$filters['join'][] = $joinMode;
				$filters['no-filter-setup'][] = ($element->filter_type == '') ? 1 : 0;
				$filters['hidden'][] = ($element->filter_type == '') ? 1 : 0;
				/*
				 * $$$ hugh - need to check for magic quotes, otherwise filter keys for
				 * CONCAT's get munged into things like CONCAT(last_name,\', \',first_name)
				 * which then blows up the WHERE query.
				 */

				$elKey = htmlspecialchars_decode($elementModel->getFilterFullName(), ENT_QUOTES);
				if (get_magic_quotes_gpc())
				{
					$decodedKey = stripslashes(urldecode($key));
				}
				else
				{
					$decodedKey = urldecode($key);
				}

				if ($decodedKey !== $elKey)
				{
					throw new UnexpectedValueException(FText::_('Unexpected key for list filter'));
				}

				$filters['key'][] = $decodedKey;

				$filters['search_type'][] = FArrayHelper::getValue($request['search_type'], $i, 'normal');
				$filters['match'][] = $element->filter_exact_match;
				$filters['full_words_only'][] = $elParams->get('full_words_only');
				$filters['eval'][] = $eval;
				$filters['required'][] = $elParams->get('filter_required');
				$filters['access'][] = $elParams->get('filter_access');
				$filters['grouped_to_previous'][] = FArrayHelper::getValue($request['grouped_to_previous'], $i, '0');
				$filters['label'][] = $elementModel->getListHeading();
				$filters['elementid'][] = $elid;
				$filters['raw'][] = false;
			}
		}

		$this->listModel->tmpFilters = $filters;
		FabrikHelperHTML::debug($filters, 'filter array: before onGetPostFilter');
		FabrikWorker::getPluginManager()->runPlugins('onGetPostFilter', $this->listModel, 'list', $filters);
		FabrikHelperHTML::debug($filters, 'filter array: after onGetPostFilter');
		$filters = $this->listModel->tmpFilters;
	}

	/**
	 * load up filters stored in the session from previous searches
	 *
	 * @param   array  &$filters  list filters
	 *
	 * @return  void
	 */
	private function getSessionFilters(&$filters)
	{
		$profiler = JProfiler::getInstance('Application');
		$elements = $this->listModel->getElements('id');
		$identifier = $this->app->input->get('listref', $this->listModel->getRenderContext());
		$key = 'com_' . $this->package . '.list' . $identifier . '.filter';
		$sessionFilters = ArrayHelper::fromObject($this->app->getUserState($key));
		$filterKeys = array_keys($filters);

		if (!is_array($sessionFilters) || !array_key_exists('key', $sessionFilters))
		{
			return;
		}

		// If we are coming from a search form ignore session filters
		$fromFormId = $this->getSearchFormId();
		$formModel = $this->listModel->getFormModel();

		if (!is_null($fromFormId) && $fromFormId !== $formModel->getId())
		{
			return;
		}
		// End ignore
		$request = $this->getPostFilterArray();
		JDEBUG ? $profiler->mark('listfilter:session filters getPostFilterArray') : null;
		$key = 'com_' . $this->package . '.list' . $identifier . '.filter.searchall';
		$requestKey = $this->getSearchAllRequestKey();
		JDEBUG ? $profiler->mark('listfilter:session filters getSearchAllRequestKey') : null;
		$pluginKeys = $this->getPluginFilterKeys();
		JDEBUG ? $profiler->mark('listfilter:session filters getPluginFilterKeys') : null;
		$search = $this->app->getUserStateFromRequest($key, $requestKey);
		$postKeys = FArrayHelper::getValue($request, 'key', array());

		for ($i = 0; $i < count($sessionFilters['key']); $i++)
		{
			$elid = FArrayHelper::getValue($sessionFilters['elementid'], $i);
			$key = FArrayHelper::getValue($sessionFilters['key'], $i, null);
			$index = FArrayHelper::getValue($filters['elementid'], $key, false);
			$origCondition = FArrayHelper::getValue($filters['orig_condition'], $i, '');

			// Used by radius search plugin
			$sqlConds = FArrayHelper::getValue($sessionFilters, 'sqlCond', array());

			if ($index !== false)
			{
				foreach ($filterKeys as $fKey)
				{
					if (is_array($filters[$fKey]) && array_key_exists($index, $filters[$fKey]))
					{
						/**
						 * $$$rob test1
						 * with the line below uncomment, the unset caused only first filter from query string to work, e..g
						 * &element_test___user[value][0]=aaassss&element_test___user[value][1]=X Administrator&element_test___user[join][1]=OR
						 * converted to:
						 * WHERE `#__users`.`name` REGEXP 'aaassss' OR `#___users`.`name` REGEXP ' X Administrator'
						 *
						 * unset($filters[$fKey][$index]);
						 */

						$filters[$fKey] = array_values($filters[$fKey]);
					}
				}
			}

			$value = $sessionFilters['value'][$i];
			$key2 = array_key_exists('key2', $sessionFilters) ? FArrayHelper::getValue($sessionFilters['key2'], $i, '') : '';

			if ($elid == -1)
			{
				// Search all boolean mode
				$eval = 0;
				$condition = 'AGAINST';
				$origCondition = 'AGAINST';
				$join = 'AND';
				$noFiltersSetup = 0;
				$hidden = 0;
				$search_type = 'searchall';
				$match = 1;
				$fullWordsOnly = 0;
				$required = 0;
				$access = $this->defaultAccessLevel();
				$grouped = 1;
				$label = '';
				/**
				 * $$$ rob force the counter to always be the same for extended search all
				 * stops issue of multiple search alls being applied
				 */
				$counter = 9999;
				$raw = 0;
				$sqlCond = null;
			}
			else
			{
				$elementModel = FArrayHelper::getValue($elements, $elid);

				if (!is_a($elementModel, 'plgFabrik_Element') && !in_array($elid, $pluginKeys))
				{
					continue;
				}
				// Check list plugins
				if (in_array($elid, $pluginKeys))
				{
					$condition = $sessionFilters['condition'][$i];
					$origCondition = $sessionFilters['orig_condition'][$i];
					$eval = $sessionFilters['eval'][$i];
					$search_type = $sessionFilters['search_type'][$i];
					$join = $sessionFilters['join'][$i];
					$grouped = $sessionFilters['grouped_to_previous'][$i];
					$noFiltersSetup = $sessionFilters['no-filter-setup'][$i];
					$hidden = $sessionFilters['hidden'][$i];
					$match = $sessionFilters['match'][$i];
					$fullWordsOnly = $sessionFilters['full_words_only'][$i];
					$required = $sessionFilters['required'][$i];
					$access = $sessionFilters['access'][$i];
					$label = $sessionFilters['label'][$i];
					$sqlCond = FArrayHelper::getValue($sqlConds, $i);
					$raw = $sessionFilters['raw'][$i];
					$counter = $elid;
				}
				else
				{
					$sqlCond = null;
					$condition = array_key_exists($i, $sessionFilters['condition']) ? $sessionFilters['condition'][$i]
						: $elementModel->getDefaultFilterCondition();

					$origFound = array_key_exists('orig_condition', $sessionFilters) && array_key_exists($i, $sessionFilters['orig_condition']);
					$origCondition = $origFound ? $sessionFilters['orig_condition'][$i] : $elementModel->getDefaultFilterCondition();
					$raw = array_key_exists($i, $sessionFilters['raw']) ? $sessionFilters['raw'][$i] : 0;
					$eval = array_key_exists($i, $sessionFilters['eval']) ? $sessionFilters['eval'][$i] : FABRIKFILTER_TEXT;

					if (!is_a($elementModel, 'PlgFabrik_ElementDatabasejoin'))
					{
						$fieldDesc = $elementModel->getFieldDescription();

						if (JString::stristr($fieldDesc, 'INT'))
						{
							if (is_numeric($search) && $condition == '=')
							{
								$eval = FABRKFILTER_NOQUOTES;
							}
						}
					}

					/**
					 * add request filter to end of filter array
					 * with advanced search and then page nav this wasn't right
					 */
					$search_type = array_key_exists($i, $sessionFilters['search_type']) ? $sessionFilters['search_type'][$i]
						: $elementModel->getDefaultFilterCondition();

					$join = $sessionFilters['join'][$i];
					$grouped = array_key_exists($i, $sessionFilters['grouped_to_previous']) ? $sessionFilters['grouped_to_previous'][$i] : 0;

					$element = $elementModel->getElement();
					$elParams = $elementModel->getParams();
					$noFiltersSetup = ($element->filter_type == '') ? 1 : 0;
					$hidden = ($element->filter_type == '') ? 1 : 0;
					$match = $element->filter_exact_match;
					$fullWordsOnly = $elParams->get('full_words_only');
					$required = $elParams->get('filter_required');
					$access = $elParams->get('filter_access');
					$label = $elementModel->getListHeading();

					/**
					 * $$$ rob if the session filter is also in the request data then set it to use the same key as the post data
					 * when the post data is processed it should then overwrite these values
					 */
					$counter = array_search($key, $postKeys) !== false ? array_search($key, $postKeys) : $this->counter;
				}
			}

			/**
			 * $$$ hugh - attempting to stop plugin filters getting overwritten
			 * PLUGIN FILTER SAGA
			 * So ... if this $filter is a pluginfilter, lets NOT overwrite it
			 */
			if (array_key_exists('search_type', $filters) && array_key_exists($counter, $filters['search_type'])
				&& $filters['search_type'][$counter] == 'jpluginfilters')
			{
				continue;
			}

			$filters['value'][$counter] = $value;
			$filters['condition'][$counter] = $condition;
			$filters['join'][$counter] = $join;
			$filters['no-filter-setup'][$counter] = $noFiltersSetup;
			$filters['hidden'][$counter] = $hidden;
			$filters['key'][$counter] = $key;
			$filters['key2'][$counter] = $key2;
			$filters['search_type'][$counter] = $search_type;
			$filters['match'][$counter] = $match;
			$filters['full_words_only'][$counter] = $fullWordsOnly;
			$filters['eval'][$counter] = $eval;
			$filters['required'][$counter] = $required;
			$filters['access'][$counter] = $access;
			$filters['grouped_to_previous'][$counter] = $grouped;
			$filters['label'][$counter] = $label;
			$filters['elementid'][$counter] = $elid;
			$filters['sqlCond'][$counter] = $sqlCond;
			$filters['raw'][$counter] = $raw;
			$filters['orig_condition'][$counter] = $origCondition;

			if (array_search($key, $postKeys) === false)
			{
				$this->counter++;
			}
		}
	}

	/**
	 * Get an array of the lists's plugin filter keys
	 *
	 * @return  array  key names
	 */
	public function getPluginFilterKeys()
	{
		$pluginManager = FabrikWorker::getPluginManager();
		$pluginManager->runPlugins('onGetFilterKey', $this->listModel, 'list');

		return $pluginManager->data;
	}
}
