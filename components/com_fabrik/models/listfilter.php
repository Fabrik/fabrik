<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * List filter model
 *
 * @package  Fabrik
 * @since    3.0
 */

class FabrikFEModelListfilter extends FabModel
{

	protected $_request = null;

	/**
	 * Set the list model
	 *
	 * @param   object  $model  list model
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
		return JRequest::getInt('id') == JRequest::getInt('activelistid') || JRequest::getVar('activelistid') == '';
	}

	/**
	 * unset request
	 *
	 * @return  void
	 */

	public function destroyRequest()
	{
		unset($this->_request);
	}
	/**
	 * this merges session data for the fromForm with any request data
	 * allowing us to filter data results from both search forms and filters
	 *
	 * @return array
	 */

	public function getFilters()
	{
		// Form or detailed views should not apply filters? what about querystrings to set up the default values?
		if (JRequest::getCmd('view') == 'details' || JRequest::getCmd('view') == 'form')
		{
			$this->_request = array();
			return $this->_request;
		}
		if (isset($this->_request))
		{
			return $this->_request;
		}
		$profiler = JProfiler::getInstance('Application');
		$filters = array();

		// $$$ rob clears all list filters, and does NOT apply any
		// other filters to the table, even if in querystring
		if (JRequest::getInt('clearfilters') === 1 && $this->activeTable())
		{
			$this->clearFilters();
			$this->_request = array();
			return $this->_request;
		}

		if (JRequest::getVar('replacefilters') == 1)
		{
			$this->clearFilters();
		}

		/**
		 * $$$ fehers The filter is cleared and applied at once without having to clear it first and then apply it (would have to be two clicks).
		 * useful in querystring filters if you want to clear old filters and apply new filters
		 */

		// $$$ rob 20/03/2011 - request resetfilters should overwrite menu option - otherwise filter then nav will remove filter.
		if ((JRequest::getVar('filterclear') == 1 || FabrikWorker::getMenuOrRequestVar('resetfilters', 0, false, 'request') == 1)
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
		$this->counter = count(JArrayHelper::getValue($request, 'key', array()));

		// Overwrite filters with session filters (fabrik_incsessionfilters set to false in listModel::getRecordCounts / for facted data counts
		if (JRequest::getVar('fabrik_incsessionfilters', true))
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
		$this->_request = $filters;
		FabrikHelperHTML::debug($this->_request, 'filter array');
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
			$filters[$property] = array_values($filters[$property]);
		}
	}

	/**
	 * $$$ rob if the filter should not be applied due to its acl level then set its condition so that it
	 * will always return true. Do this rather than unsetting the filter - as this removes the selected option
	 * from the filter forms field. Can be used in conjunction with a list filter plugin to override a normal fiters option with the
	 * plugins option, e.g. load all univertisties courses OR [plugin option] load remote courses run by selected university
	 * e.g http://www.epics-ve.eu/index.php?option=com_fabrik&view=list&listid=5
	 *
	 * @param   array  &$filters  list filters
	 *
	 * @return  void
	 */

	public function checkAccess(&$filters)
	{
		$access = JArrayHelper::getValue($filters, 'access', array());
		foreach ($access as $key => $selAccess)
		{
			// $$$ hugh - fix for where certain elements got created with 0 as the
			// the default for filter_access, which isn't a legal value, should be 1
			$selAccess = $selAccess == '0' ? '1' : $selAccess;
			$i = $filters['key'][$key];
			if (!in_array($selAccess, JFactory::getUser()->authorisedLevels()))
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
		$app = JFactory::getApplication();
		$identifier = $this->listModel->getRenderContext();

		// Test new option to have one field to search them all
		$key = 'com_fabrik.list' . $identifier . '.filter.searchall';

		// Seems like post keys 'name.1' get turned into 'name_1'
		$requestKey = $this->getSearchAllRequestKey();
		$v = $app->getUserStateFromRequest($key, $requestKey);
		if (trim($v) == '')
		{
			$fromFormId = $app->getUserState('com_fabrik.searchform.fromForm');
			if ($fromFormId != $this->listModel->getFormModel()->getForm()->id)
			{
				$v = $app->getUserState('com_fabrik.searchform.form' . $fromFormId . '.searchall');
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
		$requestKey = $this->getSearchAllRequestKey();
		$search = $this->getSearchAllValue('query');
		if ($search == '')
		{
			if (array_key_exists($requestKey, $_POST))
			{
				// Empty search string sent unset any searchall filters
				$ks = array_keys($filters);
				$filterkeys = array_keys(JArrayHelper::getValue($filters, 'search_type', array()));
				foreach ($filterkeys as $filterkey)
				{
					if (JArrayHelper::getValue($filters['search_type'], $filterkey, '') == 'searchall')
					{
						foreach ($ks as $k)
						{
							/**
							 * $$$ rob 10/04/2012  simply unsetting the array leaves the array pointer, but somewhere we recreate
							 * $filters['search_type'] so its index becomes out of sync. see http://fabrikar.com/forums/showthread.php?t=25698
							 * unset($filters[$k][$filterkey]);
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
		$listid = JRequest::getInt('listid', -1);

		// Check that we actually have the correct list id (or -1 if filter from viz)
		if ($this->listModel->getTable()->id == $listid || $listid == -1)
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
			 * which means sometimes $filters->foo is a string.  Putting a bandaid on it for now,
			 * but really should try and find out why sometimes we have strings rather than arrays.
			 */
			if (is_array($filters[$key]))
			{
				unset($filters[$key][$id]);
			}
		}
	}

	/**
	 * for advanced search all test if the search string is long enough
	 *
	 * @param   string  $s  search string
	 *
	 * @since 3.0.6
	 *
	 * @return  bool	search string long enough?
	 */

	protected function testBooleanSearchLength($s)
	{
		$db = JFactory::getDbo();
		$db->setQuery('SHOW VARIABLES LIKE \'ft_min_word_len\'');
		$res = $db->loadObject();
		return JString::strlen($s) >= $res->Value;
	}

	/**
	 * do a boolean search
	 *
	 * @param   array   &$filters  filter array
	 * @param   string  $search    term
	 *
	 * @return  void
	 */

	private function doBooleanSearch(&$filters, $search)
	{
		$mode = JRequest::getVar('search-mode-advanced', 'and');
		if (trim($search) == '')
		{
			return;
		}
		if (!$this->testBooleanSearchLength($search))
		{
			JError::raiseNotice(500, JText::_('COM_FABRIK_NOTICE_SEARCH_STRING_TOO_SHORT'));
			return;
		}
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
			 * previous +operators (so if you just have -operatos)
			 * no records are returned
			 */
			$search = '+(a* b* c* d* e* f* g* h* i* j* k* l* m* n* o* p* q* r* s* t* u* v* w* x* y* z*) ' . $search;
		}

		JRequest::setVar('overide_join_val_column_concat', 1);
		$names = $this->listModel->getSearchAllFields();

		if (empty($names))
		{
			return;
		}
		JRequest::setVar('overide_join_val_column_concat', 0);
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
		$app = JFactory::getApplication();
		$session = JFactory::getSession();
		$registry = $session->get('registry');
		$id = JRequest::getVar('listref', $this->listModel->getRenderContext());
		$tid = 'list' . $id;
		$listContext = 'com_fabrik.list' . $id . '.';
		$context = $listContext . 'filter';
		$app->setUserState($listContext . 'limitstart', 0);
		if (!is_object($registry))
		{
			return;
		}
		$reg = $registry->get($context, new stdClass);
		/**
		 * $$$ rob jpluginfilters search_types are those which have been set inside the
		 * Joomla content plugin e.g. {fabrik view=list id=1 tablename___elementname=foo}
		 * these should not be removed when the list filters are cleared
		 */
		$reg = JArrayHelper::fromObject($reg);
		$serachTypes = JArrayHelper::getValue($reg, 'search_type', array());
		for ($i = 0; $i < count($serachTypes); $i++)
		{
			if ($serachTypes[$i] !== 'jpluginfilters')
			{
				$this->clearAFilter($reg, $i);
			}
		}
		$reg['searchall'] = '';
		$reg = JArrayHelper::toObject($reg);

		$registry->set($context, $reg);
		$reg = $registry->get($context, new stdClass);

		// Reset plugin filter
		if (isset($registry->_registry['com_fabrik']['data']->$tid->plugins))
		{
			unset($registry->_registry['com_fabrik']['data']->$tid->plugins);
		}
		$key = 'com_fabrik.' . $tid . '.searchall';
		$v = $app->setUserState($key, '');

		$fromFormId = $app->getUserState('com_fabrik.searchform.fromForm');
		if ($fromFormId != $this->listModel->getFormModel()->get('id'))
		{
			$app->setUserState('com_fabrik.searchform.form' . $fromFormId . '.searchall', '');
		}
	}

	/**
	 * get users default access level
	 *
	 * @return  int  access level
	 */

	protected function defaultAccessLevel()
	{
		$accessLevels = JFactory::getUser()->authorisedLevels();
		return JArrayHelper::getValue($accessLevels, 0, 1);
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
			// $$$ hugh - need to reset $search each time round, in case getFilterValue has esacped something,
			// like foo.exe to foo\\\.exe ... otherwise each time round we double the number of \s's
			$search = $orig_search;
			$elementModel = $elements[$elid];
			if (!$elementModel->includeInSearchAll())
			{
				continue;
			}
			$searchable = true;
			$k = $elementModel->getFullName(false, false, false);
			$k = FabrikString::safeColName($k);

			$key = array_key_exists('key', $filters) ? array_search($k, $filters['key']) : false;

			/**
			 * $$$ rob 28/06/2011 see http://fabrikar.com/forums/showthread.php?t=26006
			 * This line was setting eval to 1 as array_search returns the key, think we want the value
			 */
			// $eval = array_key_exists('eval', $filters) ? array_search($k, $filters['eval']) : FABRIKFILTER_TEXT;
			$eval = array_key_exists('eval', $filters) ? JArrayHelper::getValue($filters['eval'], $key, FABRIKFILTER_TEXT) : FABRIKFILTER_TEXT;

			if (!is_a($elementModel, 'plgFabrik_ElementDatabasejoin'))
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
			}
			$element = $elementModel->getElement();
			$elparams = $elementModel->getParams();

			$access = $this->defaultAccessLevel();

			// $$$ rob so search all on checkboxes/radio buttons etc will take the search value of 'one' and return '1'
			$newsearch = $elementModel->getFilterValue($search, $condition, $eval);
			$search = $newsearch[0];
			if ($key !== false)
			{
				$filters['value'][$key] = $search;
				$filters['condition'][$key] = $condition;
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
				 * incorrect query. ASAICT grouped_to_previous should always be 1 for search_all.
				 * And testing if the element name = 0 seems v wrong :)
				 */
				// $filters['grouped_to_previous'][$key] = $k == 0 ? 0 : 1;
				$filters['grouped_to_previous'][$key] = 1;
				$filters['label'][$key] = $elparams->get('alt_list_heading') == '' ? $element->label : $elparams->get('alt_list_heading');
				$filters['raw'][$key] = false;
			}
			else
			{
				$filters['value'][] = $search;
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
				 * $$$ rob having grouped_to_previous as 1 was barfing this list view for bea, when doing a search all:
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
				$filters['label'][] = $elparams->get('alt_list_heading') == '' ? $element->label : $elparams->get('alt_list_heading');
				$filters['elementid'][] = $element->id;
				$filters['raw'][] = false;
			}
			$i++;
		}
		if (!$searchable)
		{
			JError::raiseNotice(500, JText::_('COM_FABRIK_NOTICE_SEARCH_ALL_BUT_NO_ELEMENTS'));
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
		$app = JFactory::getApplication();
		$formModel = $this->listModel->getFormModel();
		$key = 'com_fabrik.searchform.fromForm';
		$fromFormId = $app->getUserState($key);
		if ($fromFormId != $formModel->getId())
		{
			$search = $app->getUserState('com_fabrik.searchform.form' . $fromFormId . '.searchall');
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
		$app = JFactory::getApplication();
		$key = 'com_fabrik.searchform.fromForm';
		return $app->getUserState($key);
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
		$app = JFactory::getApplication();
		$key = 'com_fabrik.searchform.fromForm';
		$app->setUserState($key, $id);
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
		$app = JFactory::getApplication();
		$fromFormId = $this->getSearchFormId();
		$formModel = $this->listModel->getFormModel();
		$db = FabrikWorker::getDbo();
		$lookupkeys = JArrayHelper::getValue($filters, 'key', array());
		if ($fromFormId != $formModel->get('id'))
		{
			$fromForm = JModel::getInstance('Form', 'FabrikFEModel');
			$fromForm->setId($fromFormId);
			$fromFormParams = $fromForm->getParams();
			/**
			 * $$$ hugh Added $filter_elements from 'filter_name'
			 * which we'll need in the case of $elid not being in $elements for search forms
			 */
			$elements = $this->listModel->getElements('id');
			$filter_elements = $this->listModel->getElements('filtername');
			$tablename = $db->quoteName($this->listModel->getTable()->db_table_name);
			$searchfilters = $app->getUserState('com_fabrik.searchform.form' . $fromFormId . '.filters');
			for ($i = 0; $i < count($searchfilters['key']); $i++)
			{
				$eval = FABRIKFILTER_TEXT;
				$found = false;
				$key = $searchfilters['key'][$i];
				$elid = $searchfilters['elementid'][$i];
				if (array_key_exists($elid, $elements))
				{
					$found = true;
					$elementModel = $elements[$elid];
				}
				else
				{
					// If sent from a search form - the table name will be blank
					$key = $tablename . '.' . array_pop(explode('.', $key));
					if (array_key_exists($key, $filter_elements))
					{
						$found = true;
						$elementModel = $filter_elements["$key"];
					}
					else
					{
						// $$$ rob - I've not actually tested this code
						$joins = $this->listModel->getJoins();
						foreach ($joins as $join)
						{
							$key = $db->quoteName($join->table_join) . '.' . array_pop(explode('.', $key));
							if (array_key_exists($key, $filter_elements))
							{
								$found = true;
								$elementModel = $filter_elements[$key];
								break;
							}
						}
					}
				}
				if (!is_a($elementModel, 'plgFabrik_Element') || $found === false)
				{
					// Could be looking for an element which exists in a join
					continue;
				}
				$index = array_key_exists('key', $filters) ? array_search($key, $lookupkeys) : false;
				$element = $elementModel->getElement();
				$elparams = $elementModel->getParams();
				$grouped = array_key_exists($i, $searchfilters['grouped_to_previous']) ? $searchfilters['grouped_to_previous'][$i] : 0;

				$join = $searchfilters['join'][$i];
				if ($index === false)
				{
					$filters['value'][] = $searchfilters['value'][$i];
					$filters['condition'][] = $elementModel->getDefaultFilterCondition();
					$filters['join'][] = $join;
					$filters['no-filter-setup'][] = ($element->filter_type == '') ? 1 : 0;
					$filters['hidden'][] = ($element->filter_type == '') ? 1 : 0;
					$filters['key'][] = $key;
					$filters['search_type'][] = 'search';
					$filters['match'][] = $element->filter_exact_match;
					$filters['full_words_only'][] = $elparams->get('full_words_only');
					$filters['eval'][] = $eval;
					$filters['required'][] = $elparams->get('filter_required');
					$filters['access'][] = $elparams->get('filter_access');
					$filters['grouped_to_previous'][] = $grouped;
					$filters['label'][] = $elparams->get('alt_list_heading') == '' ? $element->label : $elparams->get('alt_list_heading');
					$filters['raw'][] = false;
				}
				else
				{
					unset($lookupkeys[$index]);
					$filters['value'][$index] = $searchfilters['value'][$i];
					$filters['condition'][$index] = $elementModel->getDefaultFilterCondition();
					$filters['join'][$index] = $join;
					$filters['no-filter-setup'][$index] = ($element->filter_type == '') ? 1 : 0;
					$filters['hidden'][$index] = ($element->filter_type == '') ? 1 : 0;
					$filters['key'][$index] = $key;
					$filters['search_type'][$index] = 'search';
					$filters['match'][$index] = $element->filter_exact_match;
					$filters['full_words_only'][$index] = $elparams->get('full_words_only');
					$filters['eval'][$index] = $eval;
					$filters['required'][$index] = $elparams->get('filter_required');
					$filters['access'][$index] = $elparams->get('filter_access');
					$filters['grouped_to_previous'][$index] = $grouped;
					$filters['label'][$index] = $elparams->get('alt_list_heading') == '' ? $element->label : $elparams->get('alt_list_heading');
					$filters['raw'][$index] = false;
				}
				$filters['elementid'][] = $element->id;
			}
		}
		/**
		 * unset the search form id so we wont reuse the search data
		 * untill a new search is performed
		 */
		$this->setSearchFormId(null);
	}

	/**
	 * get any querystring filters that can be applied to the list
	 * you can simple do tablename___elementname=value
	 * or if you want more control you can do
	 *
	 * tablename___elementname[value]=value&tablename_elementname[condition]=OR etc
	 *
	 * @param   array  &$filters  list filters
	 *
	 * @return  void
	 */

	private function getQuerystringFilters(&$filters)
	{
		$item = $this->listModel->getTable();
		$request = JRequest::get('get');
		$formModel = $this->listModel->getFormModel();
		$filterkeys = array_keys($filters);
		foreach ($request as $key => $val)
		{
			$oldkey = $key;
			$key = FabrikString::safeColName($key);
			$index = array_key_exists('key', $filters) ? array_search($key, $filters['key']) : false;
			if ($index !== false)
			{
				foreach ($filterkeys as $fkey)
				{
					if (is_array($filters[$fkey]) && array_key_exists($index, $filters[$fkey]))
					{
						unset($filters[$fkey][$index]);

						// Reindex array
						$filters[$fkey] = array_values($filters[$fkey]);
					}
				}
			}
			$raw = 0;
			if (substr($oldkey, -4, 4) == '_raw')
			{
				$raw = 1;

				// Withouth this line releated data links 'listname___elementname_raw=X' where not having their filter applied
				$key = FabrikString::safeColName(FabrikString::rtrimword($oldkey, '_raw'));
			}
			$elementModel = $formModel->getElement(FabrikString::rtrimword($oldkey, '_raw'), false, false);
			if (!is_a($elementModel, 'plgFabrik_Element'))
			{
				continue;
			}

			$eval = is_array($val) ? JArrayHelper::getValue($val, 'eval', FABRIKFILTER_TEXT) : FABRIKFILTER_TEXT;
			$condition = is_array($val) ? JArrayHelper::getValue($val, 'condition', $elementModel->getDefaultFilterCondition())
				: $elementModel->getDefaultFilterCondition();

			if (!is_a($elementModel, 'plgFabrik_ElementDatabasejoin'))
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
				$value = JArrayHelper::getValue($val, 'value', '');
				$join = JArrayHelper::getValue($val, 'join', 'AND');
				$grouped = JArrayHelper::getValue($val, 'grouped_to_previous', 0);

				/**
				 * do a ranged querystring search with this syntax
				 * ?element_test___time_date[value][]=2009-08-07&element_test___time_date[value][]=2009-08-10&element_test___time_date[condition]=BETWEEN
				 */
				if (is_array($value) && $condition != 'BETWEEN')
				{
					// If we aren't doing a ranged search
					foreach ($value as $vk => $avalue)
					{
						// If � entered in qs then that is coverted to %E9 which urldecode will convert back
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
				// If � entered in qs then that is coverted to %E9 which urldecode will convert back
				$value = addslashes(urldecode($val));
				$join = 'AND';
				$grouped = 0;
				$this->indQueryString($elementModel, $filters, $value, $condition, $join, $grouped, $eval, $key, $raw);
			}
		}
	}

	/**
	 * insert individual querystring filter into filter array
	 *
	 * @param   object  $elementModel  element model
	 * @param   array   &$filters      filter array
	 * @param   mixed   $value         value
	 * @param   string  $condition     condition
	 * @param   string  $join          join
	 * @param   bool    $grouped       is grouped
	 * @param   bool    $eval          is eval
	 * @param   string  $key           element key
	 * @param   string  $raw           is the filter a raw filter (tablename___elementname_raw=foo)
	 *
	 * @return  void
	 */

	private function indQueryString($elementModel, &$filters, $value, $condition, $join, $grouped, $eval, $key, $raw = false)
	{
		$element = $elementModel->getElement();
		$elparams = $elementModel->getParams();
		if (is_string($value))
		{
			$value = trim($value);
		}
		$k2 = FabrikString::safeColNameToArrayKey($key);
		/**
		 * $$$ rob fabrik_sticky_filters set in J content plugin
		 * Treat these as prefilters so we dont unset them
		 * when we clear the filters
		 */
		$filterType = in_array($k2 . '_raw', JRequest::getVar('fabrik_sticky_filters', array()))
			|| in_array($k2, JRequest::getVar('fabrik_sticky_filters', array())) ? 'jpluginfilters' : 'querystring';
		$filters['value'][] = $value;
		$filters['condition'][] = urldecode($condition);
		$filters['join'][] = $join;
		$filters['no-filter-setup'][] = ($element->filter_type == '') ? 1 : 0;
		$filter['hidden'][] = ($element->filter_type == '') ? 1 : 0;
		$filters['key'][] = $key;
		$filters['key2'][] = '';
		$filters['search_type'][] = $filterType;
		$filters['match'][] = $element->filter_exact_match;
		$filters['full_words_only'][] = $elparams->get('full_words_only');
		$filters['eval'][] = $eval;
		$filters['required'][] = $elparams->get('filter_required');
		$filters['access'][] = $elparams->get('filter_access');
		$filters['grouped_to_previous'][] = $grouped;
		$filters['label'][] = $elparams->get('alt_list_heading') == '' ? $element->label : $elparams->get('alt_list_heading');
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
			$item = $this->listModel->getTable();
			$request = JRequest::get('post');
			/**
			 * Use request ONLY if you want to test an ajax post with params in url
			 * $request	= JRequest::get('request');
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
	 * Overwrite session and serach all filters with posted data
	 *
	 * @param   array  &$filters  filter array
	 *
	 * @return  void
	 */

	private function getPostFilters(&$filters)
	{
		$item = $this->listModel->getTable();
		$request = $this->getPostFilterArray();
		$elements = $this->listModel->getElements('id');
		$filterkeys = array_keys($filters);
		$values = JArrayHelper::getValue($request, 'value', array());
		$searchTypes = JArrayHelper::getValue($filters, 'search_type', array());
		$usedMerges = array();
		if (!empty($request) && array_key_exists('key', $request))
		{
			$keyints = array_keys($request['key']);
			$ajaxPost = JString::strtolower(JRequest::getVar('HTTP_X_REQUESTED_WITH', '', 'server'));
			$this->listModel->ajaxPost = $ajaxPost;
			$this->listModel->postValues = $values;
			foreach ($keyints as $i)
			{
				$value = JArrayHelper::getValue($values, $i, '');

				// $$$ rob 28/10/2011 - running an ajax filter (autocomplete) from horz-search tmpl, looking for term 'test + test'
				// the '+' is converted into a space so search fails

				/* if ($ajaxPost == 'xmlhttprequest') {
				    if (is_array($value)) {
				foreach ($value as $k => $v) {
				$value[$k] = urldecode($v);
				}
				} else {
				$value = urldecode($value);
				}
				} */
				$key = JArrayHelper::getValue($request['key'], $i);
				$elid = JArrayHelper::getValue($request['elementid'], $i);
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
				$emptyValue = ((is_string($value) && trim($value) == '') || (is_array($value) && trim(implode('', $value)) == ''));

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
					// $$ $rob - if the filter has been added from search all then don't remove it
					if (JArrayHelper::getValue($searchTypes, $index) != 'searchall')
					{
						$this->clearAFilter($filters, $index);
					}
					// $$$ rob - regardless of whether the filter was added by search all or not - don't overwrite it with post filter
					continue;
				}
				$elementModel = $elements[$elid];
				if (!is_a($elementModel, 'plgFabrik_Element'))
				{
					continue;
				}

				// If the request key is already in the filter array - unset it
				if ($index !== false)
				{
					foreach ($filterkeys as $fkey)
					{
						if (is_array($filters[$fkey]) && array_key_exists($index, $filters[$fkey]))
						{
							// Don't unset search all filters when the value is empty and continue so we dont add in a new filter
							if (JArrayHelper::getValue($searchTypes, $index) == 'searchall' && $value == '')
							{
								continue 2;
							}

							// $$$rob we DO need to unset
							unset($filters[$fkey][$index]);
						}
					}
				}

				// Empty ranged data test

				// $$$ hugh - was getting single value array when testing AJAX nav, so 'undefined index 1' warning.
				if (is_array($value) && $value[0] == '' && (!isset($value[1]) || $value[1] == ''))
				{
					continue;
				}
				$eval = is_array($value) ? JArrayHelper::getValue($value, 'eval', FABRIKFILTER_TEXT) : FABRIKFILTER_TEXT;
				if (!is_a($elementModel, 'plgFabrik_ElementDatabasejoin'))
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
						$joinMode = array_pop(JArrayHelper::getValue($filters, 'join', array('AND')));

						// $$$ rob - If search all made, then the post filters should filter further the results
						$lastSearchType = array_pop(JArrayHelper::getValue($filters, 'search_type', array('normal')));
						if ($lastSearchType == 'searchall')
						{
							$joinMode = 'AND';
						}
					}
				}

				// Add request filter to end of filter array
				$element = $elementModel->getElement();
				$elparams = $elementModel->getParams();
				$filters['value'][] = $value;
				$filters['condition'][] = urldecode($request['condition'][$i]);
				$filters['join'][] = $joinMode;
				$filters['no-filter-setup'][] = ($element->filter_type == '') ? 1 : 0;
				$filters['hidden'][] = ($element->filter_type == '') ? 1 : 0;
				$filters['key'][] = urldecode($key);
				$filters['search_type'][] = JArrayHelper::getValue($request['search_type'], $i, 'normal');
				$filters['match'][] = $element->filter_exact_match;
				$filters['full_words_only'][] = $elparams->get('full_words_only');
				$filters['eval'][] = $eval;
				$filters['required'][] = $elparams->get('filter_required');
				$filters['access'][] = $elparams->get('filter_access');
				$filters['grouped_to_previous'][] = JArrayHelper::getValue($request['grouped_to_previous'], $i, '0');
				$filters['label'][] = $elparams->get('alt_list_heading') == '' ? $element->label : $elparams->get('alt_list_heading');
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
		$app = JFactory::getApplication();
		$elements = $this->listModel->getElements('id');
		$item = $this->listModel->getTable();
		$identifier = JRequest::getVar('listref', $this->listModel->getRenderContext());
		$identifier = $this->listModel->getRenderContext();
		$key = 'com_fabrik.list' . $identifier . '.filter';
		$sessionfilters = JArrayHelper::fromObject($app->getUserState($key));
		$filterkeys = array_keys($filters);
		if (!is_array($sessionfilters) || !array_key_exists('key', $sessionfilters))
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
		$key = 'com_fabrik.list' . $identifier . '.filter.searchall';
		$requestKey = $this->getSearchAllRequestKey();
		JDEBUG ? $profiler->mark('listfilter:session filters getSearchAllRequestKey') : null;
		$pluginKeys = $this->getPluginFilterKeys();
		JDEBUG ? $profiler->mark('listfilter:session filters getPluginFilterKeys') : null;
		$search = $app->getUserStateFromRequest($key, $requestKey);

		$postkeys = JArrayHelper::getValue($request, 'key', array());
		for ($i = 0; $i < count($sessionfilters['key']); $i++)
		{
			$elid = $sessionfilters['elementid'][$i];
			$key = JArrayHelper::getValue($sessionfilters['key'], $i, null);
			$index = JArrayHelper::getValue($filters['elementid'], $key, false);

			// Used by radius search plugin
			$sqlConds = JArrayHelper::getValue($sessionfilters, 'sqlCond', array());

			if ($index !== false)
			{
				foreach ($filterkeys as $fkey)
				{
					if (is_array($filters[$fkey]) && array_key_exists($index, $filters[$fkey]))
					{
						/**
						 * $$$rob test1
						 * with the line below uncomment, the unset caused only first filter from query string to work, e..g
						 * &element_test___user[value][0]=aaassss&element_test___user[value][1]=X Administrator&element_test___user[join][1]=OR
						 * converted to:
						 * WHERE `#__users`.`name` REGEXP 'aaassss' OR `#___users`.`name` REGEXP ' X Administrator'
						 *
						 * unset($filters[$fkey][$index]);
						 */

						$filters[$fkey] = array_values($filters[$fkey]);
					}
				}
			}
			$value = $sessionfilters['value'][$i];
			$key2 = array_key_exists('key2', $sessionfilters) ? JArrayHelper::getValue($sessionfilters['key2'], $i, '') : '';
			if ($elid == -1)
			{
				// Search all boolean mode
				$eval = 0;
				$condition = 'AGAINST';
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
				 * $$$ rob force the counter to always be the same for advanced search all
				 * stops issue of multiple search alls being applied
				 */
				$counter = 9999;
				$raw = 0;
				$sqlCond = null;
			}
			else
			{
				$elementModel = $elements[$elid];
				if (!is_a($elementModel, 'plgFabrik_Element') && !in_array($elid, $pluginKeys))
				{
					continue;
				}
				// Check list plugins
				if (in_array($elid, $pluginKeys))
				{
					$condition = $sessionfilters['condition'][$i];
					$eval = $sessionfilters['eval'][$i];
					$search_type = $sessionfilters['search_type'][$i];
					$join = $sessionfilters['join'][$i];
					$grouped = $sessionfilters['grouped_to_previous'][$i];
					$noFiltersSetup = $sessionfilters['no-filter-setup'][$i];
					$hidden = $sessionfilters['hidden'][$i];
					$match = $sessionfilters['match'][$i];
					$fullWordsOnly = $sessionfilters['full_words_only'][$i];
					$required = $sessionfilters['required'][$i];
					$access = $sessionfilters['access'][$i];
					$label = $sessionfilters['label'][$i];
					$sqlCond = JArrayHelper::getValue($sqlConds, $i);
					$raw = $sessionfilters['raw'][$i];
					$counter = $elid;
				}
				else
				{
					$sqlCond = null;
					$condition = array_key_exists($i, $sessionfilters['condition']) ? $sessionfilters['condition'][$i]
						: $elementModel->getDefaultFilterCondition();
					$raw = array_key_exists($i, $sessionfilters['raw']) ? $sessionfilters['raw'][$i] : 0;
					$eval = array_key_exists($i, $sessionfilters['eval']) ? $sessionfilters['eval'][$i] : FABRIKFILTER_TEXT;
					if (!is_a($elementModel, 'plgFabrik_ElementDatabasejoin'))
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
					$search_type = array_key_exists($i, $sessionfilters['search_type']) ? $sessionfilters['search_type'][$i]
						: $elementModel->getDefaultFilterCondition();

					$join = $sessionfilters['join'][$i];
					$grouped = array_key_exists($i, $sessionfilters['grouped_to_previous']) ? $sessionfilters['grouped_to_previous'][$i] : 0;

					$element = $elementModel->getElement();
					$elparams = $elementModel->getParams();
					$noFiltersSetup = ($element->filter_type == '') ? 1 : 0;
					$hidden = ($element->filter_type == '') ? 1 : 0;
					$match = $element->filter_exact_match;
					$fullWordsOnly = $elparams->get('full_words_only');
					$required = $elparams->get('filter_required');
					$access = $elparams->get('filter_access');
					$label = $elparams->get('alt_list_heading') == '' ? $element->label : $elparams->get('alt_list_heading');

					/**
					 * $$$ rob if the session filter is also in the request data then set it to use the same key as the post data
					 * when the post data is processed it should then overwrite these values
					 */
					$counter = array_search($key, $postkeys) !== false ? array_search($key, $postkeys) : $this->counter;

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
			if (array_search($key, $postkeys) === false)
			{
				$this->counter++;
			}
		}
	}

	/**
	 * get an array of the lists's plugin filter keys
	 *
	 * @return  array  key names
	 */

	public function getPluginFilterKeys()
	{
		$pluginManager = FabrikWorker::getPluginManager();
		$pluginManager->runPlugins('onGetFilterKey', $this->listModel, 'list');
		return $pluginManager->_data;
	}

}
