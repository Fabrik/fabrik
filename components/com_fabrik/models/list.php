<?php
/**
 * Fabrik List Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.modelform');

require_once COM_FABRIK_FRONTEND . '/helpers/pagination.php';
require_once COM_FABRIK_FRONTEND . '/helpers/string.php';
require_once COM_FABRIK_FRONTEND . '/helpers/list.php';

/**
 * Fabrik List Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikFEModelList extends JModelForm
{

	/**
	 * List id
	 *
	 * @var int
	 */
	public $id = null;

	/**
	 * package id
	 *
	 * @var int
	 */
	public $packageId = null;

	/**
	 * Lists connection object
	 *
	 * @var object
	 */
	protected $_oConn = null;

	/**
	 * List item
	 *
	 * @var JTable
	 */
	protected $_table = null;

	/**
	 * List's form model
	 * @var FabrikFEModelForm
	 */
	protected $_oForm = null;

	/**
	 * Joins
	 * @var array
	 */
	protected $_aJoins = null;

	/**
	 * Column calculations
	 * @var array
	 */
	protected $_aRunCalculations = array();

	/**
	 * List output format - set to rss to collect correct element data within function getData()
	 *
	 * @var string
	*/
	protected $outPutFormat = 'html';

	/**
	 * Is rendered as a J content plugin
	 *
	 * @var bool
	 */
	public $isMambot = false;

	/**
	 * Contain access rights
	 *
	 * @var object
	 */
	protected $_access = null;

	/**
	 * Id of the last inserted record (or if updated the last record updated)
	 *
	 * @var int
	 */
	public $lastInsertId = null;

	/**
	 * Store data to create joined records from
	 *
	 * @var array
	 */
	protected $_joinsToProcess = null;

	/**
	 * Database fields
	 *
	 * @var array
	 */
	protected $_dbFields = null;

	/**
	 * Force reload table calculations
	 *
	 * @var bool
	 */
	protected $_reloadCalculations = false;

	/**
	 * Data contains request data
	 *
	 * @var array
	 */
	protected $_aData = null;

	/**
	 * Used when making custom links to determine if we need to append the rowid to the url
	 *
	 * @var bool
	 */
	protected $rowIdentifierAdded = false;

	/**
	 * Is ajax used
	 *
	 * @var bool
	 */
	public $ajax = null;

	/**
	 * Plugin manager
	 *
	 * @var FabrikFEModelPluginmanager
	 */
	protected $_pluginManager = null;

	/**
	 * Join sql
	 *
	 * @var string
	 */
	protected $_joinsSQL = null;

	/**
	 * Order by column names
	 *
	 * @var array
	 */
	var $orderByFields = null;

	/**
	 * Is the object inside a package?
	 *
	 * @bar bool
	 */
	//var $_inPackage  = false;

	protected $_joinsToThisKey = null;

	/**
	 * Used to determine which filter action to use.
	 * If a filter is a range then override lists setting with onsubmit
	 *
	 * @var string
	 */
	protected $_real_filter_action = null;

	/**
	 * Merged request and session data used to potentially filter the list
	 *
	 * @var array
	 */
	protected $_request = null;

	/**
	 * Internally used when using parseMessageForRowHolder();
	 *
	 * @var array
	 */
	protected $_aRow = null;

	/**
	 * Rows to delete
	 *
	 * @var array
	 */
	protected $_rowsToDelete = null;

	/**
	 * Original list data BEFORE form saved - used to ensure uneditable data is stored
	 *
	 * @var array
	 */
	protected $_origData = null;

	/**
	 * Set to true to load records STARTING from a random id (used in the getPageNav func)
	 *
	 * @var bool
	 */
	public $randomRecords = false;

	/**
	 * List data
	 *
	 * @var array
	 */
	protected $_data = null;

	/**
	 * Template name
	 *
	 * @var string
	 */
	protected $tmpl = null;

	/**
	 * Pagination
	 *
	 * @var FPagination
	 */
	var $nav = null;

	/**
	 * List field names
	 *
	 * @var array
	 */
	var $fields = null;

	/**
	 * Prefilters
	 *
	 * @var array
	 */
	var $prefilters = null;

	/**
	 * Filters
	 *
	 * @var array
	 */
	var $filters = null;

	/**
	 * Db element joins whose fk's point to the list's primary key
	 *
	 * @var array
	 */
	var $aJoinsToThisKey = null;

	/**
	 * Can rows be selected
	 *
	 * @var bool
	 */
	var $canSelectRows = null;

	/**
	 * As fields - used in query to build list data
	 *
	 * @var array
	 */
	var $asfields = null;

	/**
	 * Has an element, which is the db key, already been added to the list of fields to select
	 *
	 * @var bool
	 */
	var $_temp_db_key_addded = false;

	/**
	 * Has the group by statement been added to the list query
	 *
	 * @var bool
	 */
	var $_group_by_added = false;

	/**
	 * List of where conditions added by list plugins
	 *
	 * @var array
	 */
	var $_pluginQueryWhere = array();

	/**
	 * List of group by statements added by list plugins
	 *
	 * @var array
	 */
	var $_pluginQueryGroupBy = array();

	/**
	 * Used in views for rendering
	 *
	 * @var array
	*/
	public $groupTemplates = array();

	/**
	 * Is the list a view
	 *
	 * @var bool
	 */
	protected $isView = null;

	/**
	 * Index objects
	 *
	 * @var array
	 */
	var $_indexes = null;

	/**
	 * Previously submitted advanced search data
	 *
	 * @var array
	 */
	var $advancedSearchRows = null;

	/**
	 * List action url
	 *
	 * @var string
	 */
	var $tableAction = null;

	/**
	 * Doing CSV import
	 *
	 * @var bool
	 */
	public $importingCSV = false;

	/**
	 * Element names to encrypt
	 *
	 * @var array
	 */
	public $encrypt = array();

	/**
	 * Which record number to start showing from
	 *
	 * @var int
	 */
	var $limitStart = null;

	/**
	 * Number of records per page
	 *
	 * @var int
	 */
	var $limitLength = null;

	/**
	 * List rows
	 *
	 * @var array
	 */
	protected $rows = null;

	/**
	 * Should a heading be added for action buttons (returns true if at least one row has buttons)
	 *
	 * @deprecated (since 3.0.7)
	 *
	 * @var bool
	 */
	protected $actionHeading = false;

	/**
	 * List of column data - used for filters
	 *
	 * @var array
	 */
	protected $columnData = array();

	/**
	 * Render context used for defining custom css variable for tmpl rendering e.g. module_1
	 *
	 * @var string
	 */
	protected $renderContext = '';

	/**
	 * Tthe max number of buttons that is shown in a row
	 *
	 * @var int
	 */
	protected $rowActionCount = 0;

	/**
	 * Do any of the elements have a required filter, only used through method of same name
	 *
	 * @var bool
	 */
	protected $hasRequiredElementFilters = null;

	/**
	 * Elements which have a required filter
	 *
	 * @var array
	 */
	protected $elementsWithRequiredFilters = array();

	/**
	 * Force formatData() to format all elements, uses formatAll() accessor method
	 *
	 * @var bool
	 */
	protected $_format_all = false;

	/**
	 * Array of order by elements
	 *
	 * @var array
	 */
	public $orderEls = array();

	/**
	 * Cached order by statement
	 *
	 * @since 3.0.7
	 *
	 * @var mixed - string or JQueryBuilder section
	 */
	public $orderBy = null;
	/**
	 * Load form
	 *
	 * @param   array  $data      form data
	 * @param   bool   $loadData  load in the data
	 *
	 * @since       1.5
	 *
	 * @return  mixed  false or form.
	*/

	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_fabrik.list', 'view', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}
		return $form;
	}

	/**
	 * Constructor
	 */

	public function __construct()
	{
		parent::__construct();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$id = JRequest::getInt('listid', $usersConfig->get('listid'));
		$this->packageId = (int) JRequest::getInt('packageId', $usersConfig->get('packageId'));
		$this->setId($id);
		$this->_access = new stdClass;
	}

	/**
	 * Process the lists plug-ins
	 *
	 * @return  array	of list plug-in result messages
	 */

	public function processPlugin()
	{
		$pluginManager = FabrikWorker::getPluginManager();
		$pluginManager->runPlugins('process', $this, 'list');
		return $pluginManager->_data;
	}

	/**
	 * Code to enable plugins to add a button to the top of the list
	 *
	 * @return  array	button html
	 */

	public function getPluginTopButtons()
	{
		$pluginManager = FabrikWorker::getPluginManager();
		$pluginManager->runPlugins('topButton', $this, 'list');
		$buttons = $pluginManager->_data;
		return $buttons;
	}

	/**
	 * Get the html that is outputted by list plug-in buttons
	 *
	 * @return  array  buttons
	 */

	public function getPluginButtons()
	{
		$pluginManager = FabrikWorker::getPluginManager();
		$pluginManager->getPlugInGroup('list');
		$pluginManager->runPlugins('button', $this, 'list');
		$buttons = $pluginManager->_data;
		return $buttons;
	}

	/**
	 * Get an array of plugin js classes to load
	 *
	 * @param   array  &$r  previously loaded classes
	 *
	 * @return  array
	 */

	public function getPluginJsClasses(&$r = array())
	{
		$pluginManager = FabrikWorker::getPluginManager();
		$pluginManager->getPlugInGroup('list');
		$src = array();
		$pluginManager->runPlugins('loadJavascriptClass', $this, 'list', $src);
		foreach ($pluginManager->_data as $f)
		{
			if (is_array($f))
			{
				$r = array_merge($r, $f);
			}
			else
			{
				$r[] = $f;
			}
		}
		return $r;
	}

	/**
	 * Get plugin js objects
	 *
	 * @param   string  $container  list container HTML id
	 *
	 * @return  mixed
	 */

	public function getPluginJsObjects($container = null)
	{
		if (is_null($container))
		{
			$container = 'listform_' . $this->getId();
		}
		$pluginManager = FabrikWorker::getPluginManager();
		$pluginManager->getPlugInGroup('list');
		$pluginManager->runPlugins('loadJavascriptInstance', $this, 'list', $container);
		return $pluginManager->_data;
	}

	/**
	 * Main query to build table
	 *
	 * @return  array  list data
	 */

	function render()
	{
		FabrikHelperHTML::debug($_POST, 'render:post');
		$profiler = JProfiler::getInstance('Application');
		$id = $this->getId();
		if (is_null($id) || $id == '0')
		{
			return JError::raiseError(500, JText::_('COM_FABRIK_INCORRECT_LIST_ID'));
		}
		$this->outPutFormat = JRequest::getVar('format', 'html');
		if ($this->outPutFormat == 'fabrikfeed')
		{
			$this->outPutFormat = 'feed';
		}

		$item = $this->getTable();
		if ($item->db_table_name == '')
		{
			return JError::raiseError(500, JText::_('COM_FABRIK_INCORRECT_LIST_ID'));
		}

		// Cant set time limit in safe mode so suppress warning
		@set_time_limit(60);
		//$this->getRequestData();
		JDEBUG ? $profiler->mark('About to get table filter') : null;
		$filters = $this->getFilterArray();
		JDEBUG ? $profiler->mark('Got filters') : null;
		$this->setLimits();
		$this->setElementTmpl();
		$data = $this->getData();
		JDEBUG ? $profiler->mark('got data') : null;

		// Think we really have to do these as the calc isnt updated when the list is filtered
		$this->doCalculations();
		JDEBUG ? $profiler->mark('done calcs') : null;
		$this->getCalculations();
		JDEBUG ? $profiler->mark('got cacls') : null;
		$item->hit();
		return $data;
	}

	/**
	 * Set the navigation limit and limitstart
	 *
	 * @param   int  $limitstart_override   Specific limitstart to use, if both start and length are specified
	 * @param   int  $limitlength_override  Specific limitlength to use, if both start and length are specified
	 *
	 * @return  void
	 */

	public function setLimits($limitstart_override = null, $limitlength_override = null)
	{

		// Plugins using setLimits - these limits would get overwritten by render() or getData() calls
		if (isset($this->limitLength) && isset($this->limitStart) && is_null($limitstart_override) && is_null($limitlength_override))
		{
			return;
		}
		/*
		 * $$$ hugh - added the overrides, so things like visualizations can just turn
		 * limits off, by passing 0's, without having to go round the houses setting
		 * the request array before calling this method.
		 */
		if (!is_null($limitstart_override) && !is_null($limitlength_override))
		{
			// Might want to set the request vars here?
			$limitStart = $limitstart_override;
			$limitLength = $limitlength_override;
		}
		else
		{
			$app = JFactory::getApplication();
			$package = $app->getUserState('com_fabrik.package', 'fabrik');
			$item = $this->getTable();
			$params = $this->getParams();
			$id = $this->getId();
			$this->randomRecords = JRequest::getVar('fabrik_random', $this->randomRecords);

			// $$$ rob dont make the key list.X as the registry doesnt seem to like keys with just '1' a
			$context = 'com_' . $package . '.list' . $this->getRenderContext() . '.';
			$limitStart = $this->randomRecords ? $this->getRandomLimitStart() : 0;

			// Deal with the fact that you can have more than one table on a page so limitstart has to be
			// specfic per table

			// Deal with the fact that you can have more than one table on a page so limitstart has to be  specfic per table

			// If table is rendered as a content plugin dont set the limits in the session
			if ($app->scope == 'com_content')
			{
				$limitLength = JRequest::getInt('limit' . $id, $item->rows_per_page);

				if (!$this->randomRecords)
				{
					$limitStart = JRequest::getInt('limitstart' . $id, $limitStart);
				}
			}
			else
			{
				$rowsPerPage = FabrikWorker::getMenuOrRequestVar('rows_per_page', $item->rows_per_page, $this->isMambot);
				$limitLength = $app->getUserStateFromRequest($context . 'limitlength', 'limit' . $id, $rowsPerPage);
				if (!$this->randomRecords)
				{
					$limitStart = $app->getUserStateFromRequest($context . 'limitstart', 'limitstart' . $id, $limitStart, 'int');
				}
			}
			if ($this->outPutFormat == 'feed')
			{
				$limitLength = JRequest::getVar('limit', $params->get('rsslimit', 150));
				$maxLimit = $params->get('rsslimitmax', 2500);
				if ($limitLength > $maxLimit)
				{
					$limitLength = $maxLimit;
				}
			}
			if ($limitStart < 0)
			{
				$limitStart = 0;
			}
		}
		$this->limitLength = $limitLength;
		$this->limitStart = $limitStart;
	}

	/**
	 * This merges session data for the fromForm with any request data
	 * allowing us to filter data results from both search forms and filters
	 *
	 * @return  array
	 */

	function getRequestData()
	{
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark('start get Request data') : null;
		$f = $this->getFilterModel()->getFilters();
		JDEBUG ? $profiler->mark('end get Request data') : null;
		return $f;
	}

	/**
	 * Get the table's filter model
	 *
	 * @return  model	filter model
	 */

	function &getFilterModel()
	{
		if (!isset($this->filterModel))
		{
			$this->filterModel = JModel::getInstance('Listfilter', 'FabrikFEModel');
			$this->filterModel->setListModel($this);
		}
		return $this->filterModel;
	}

	/**
	 * Once we have a few table joins, our select statements are
	 * getting big enough to hit default select length max in MySQL.  Added per-list
	 * setting to enable_big_selects.
	 *
	 * 03/10/2012 - Should preserve any old list settings, but this is now set in the global config
	 * We set it on the main J db in the system plugin setBigSelects() but should do here as well as we
	 * may not be dealing with the same db.
	 *
	 * 2012-10-19 - $$$ hugh - trouble with preserving old list settings is there is no way to change them, without
	 * directly poking around in the params in the database.  Commenting out the per-list checking.
	 *
	 * @deprecated   now handled in FabrikHelper::getDbo(), as it needs to apply to all queruies, including internal / default connection ones.
	 * @since   3/16/2010
	 *
	 * @return  void
	 */

	function setBigSelects()
	{
		$fbConfig = JComponentHelper::getParams('com_fabrik');
		$bigSelects = $fbConfig->get('enable_big_selects', 0);
		/*
		 $fabrikDb = $this->getDb();
		$params = $this->getParams();
		if ($params->get('enable_big_selects', $bigSelects))
		 */
		if ($bigSelects)
		{
			$fabrikDb = $this->getDb();

			// $$$ hugh - added bumping up GROUP_CONCAT_MAX_LEN here, rather than adding YAFO for it
			$fabrikDb->setQuery("SET OPTION SQL_BIG_SELECTS=1, GROUP_CONCAT_MAX_LEN=10240");
			$fabrikDb->query();

		}
	}

	/**
	 * Get the table's data
	 *
	 * @return  array	of objects (rows)
	 */

	public function getData()
	{
		$profiler = JProfiler::getInstance('Application');
		$pluginManager = FabrikWorker::getPluginManager();
		$fbConfig = JComponentHelper::getParams('com_fabrik');
		$pluginManager->runPlugins('onPreLoadData', $this, 'list');
		if (isset($this->_data) && !is_null($this->_data))
		{
			return $this->_data;
		}
		// Needs to be off for FOUND_ROWS() to work
		ini_set('mysql.trace_mode', 'off');
		$fabrikDb = $this->getDb();
		JDEBUG ? $profiler->mark('query build start') : null;

		// Ajax call needs to recall this - not sure why
		$this->setLimits();
		$query = $this->_buildQuery();
		JDEBUG ? $profiler->mark('query build end') : null;

		$cache = FabrikWorker::getCache($this);
		$results = $cache->call(array(get_class($this), 'finesseData'), $this->getId(), $query, $this->limitStart, $this->limitLength, $this->outPutFormat);
		$this->totalRecords = $results[0];
		$this->_data = $results[1];
		$this->groupTemplates = $results[2];
		$nav = $this->getPagination($this->totalRecords, $this->limitStart, $this->limitLength);
		$pluginManager->runPlugins('onLoadData', $this, 'list');
		return $this->_data;

	}

	/**
	 * Cached Method to run the getData select query and do our Fabrik magikin'
	 *
	 * @param   int     $listId        list id
	 * @param   string  $query         sql query
	 * @param   int     $start         start of limit
	 * @param   int     $length        limit length
	 * @param   string  $outPutFormat  output format csv/html/rss etc
	 *
	 * @return array (total records, data set)
	 */

	public static function finesseData($listId, $query, $start, $length, $outPutFormat)
	{
		$profiler = JProfiler::getInstance('Application');
		$traceModel = ini_get('mysql.trace_mode');
		$listModel = JModel::getInstance('List', 'FabrikFEModel');
		$listModel->setId($listId);
		$listModel->setOutPutFormat($outPutFormat);
		$fabrikDb = $listModel->getDb();
		$listModel->setBigSelects();

		// $$$ rob - if merging joined data then we don't want to limit
		// the query as we have already done so in _buildQuery()
		if ($listModel->mergeJoinedData())
		{
			$fabrikDb->setQuery($query);
		}
		else
		{
			$fabrikDb->setQuery($query, $start, $length);
		}
		FabrikHelperHTML::debug($fabrikDb->getQuery(), 'list GetData:' . $listModel->getTable()->label);
		JDEBUG ? $profiler->mark('before query run') : null;

		/* set 2nd param to false in attempt to stop joomfish db adaptor from translating the orignal query
		 * fabrik3 - 2nd param in j16 is now used - guessing that joomfish now uses the third param for the false switch?
		* $$$ rob 26/09/2011 note Joomfish not currently released for J1.7
		*/
		$listModel->_data = $fabrikDb->loadObjectList('', 'stdClass', false);
		if ($fabrikDb->getErrorNum() != 0)
		{
			jexit('getData:' . $fabrikDb->getErrorMsg());
		}
		// $$$ rob better way of getting total records
		if ($listModel->mergeJoinedData())
		{
			$listModel->totalRecords = $listModel->getTotalRecords();
		}
		else
		{
			$fabrikDb->setQuery("SELECT FOUND_ROWS()");
			$listModel->totalRecords = $fabrikDb->loadResult();
		}
		if ($listModel->randomRecords)
		{
			shuffle($listModel->_data);
		}
		ini_set('mysql.trace_mode', $traceModel);

		JDEBUG ? $profiler->mark('query run and data loaded') : null;
		$listModel->translateData($listModel->_data);
		if ($fabrikDb->getErrorNum() != 0)
		{
			JError::raiseNotice(500, 'getData: ' . $fabrikDb->getErrorMsg());
		}

		// Add labels before preformatting - otherwise calc elements on dropdown elements show raw data for {list___element}
		$listModel->addLabels($listModel->_data);

		// Run calculations
		$listModel->preFormatFormJoins($listModel->_data);

		JDEBUG ? $profiler->mark('start format for joins') : null;
		$listModel->formatForJoins($listModel->_data);

		JDEBUG ? $profiler->mark('start format data') : null;
		$listModel->formatData($listModel->_data);

		JDEBUG ? $profiler->mark('data formatted') : null;

		return array($listModel->totalRecords, $listModel->_data, $listModel->groupTemplates);
	}

	/**
	 * Part of list model finesseData() replace list___element values with labels for
	 * things like dropdowns, needed so that calc elements in preFormatFormJoins() have access to the element
	 * label
	 *
	 * @param   array  &$data  List data
	 *
	 * @return  void
	 */
	protected function addLabels(&$data)
	{
		$form = $this->getFormModel();
		$groups = $form->getGroupsHiarachy();
		$ec = count($data);
		foreach ($groups as $groupModel)
		{
			$elementModels = $this->activeContextElements($groupModel);
			foreach ($elementModels as $elementModel)
			{
				$elementModel->setContext($groupModel, $form, $this);
				$col = $elementModel->getFullName(false, true, false);

				if (!empty($data) && array_key_exists($col, $data[0]))
				{
					for ($i = 0; $i < $ec; $i++)
					{
						$thisRow = $data[$i];
						$coldata = $thisRow->$col;
						$data[$i]->$col = $elementModel->getLabelForValue($coldata);
					}
				}
			}
		}
	}

	/**
	 * $$$ rob pointless getting elemetsnnot shown in the table view?
	 * $$$ hugh - oops, they might be using elements in group-by template not shown in table
	 * http://fabrikar.com/forums/showthread.php?p=102600#post102600
	 * $$$ rob in that case lets test that rather than loading blindly
	 * $$$ rob 15/02/2011 or out put may be csv in which we want to format any fields not shown in the form
	 * $$$ hugh 06/05/2012 added formatAll() mechanism, so plugins can force formatting of all elements
	 *
	 * @param   JModel  $groupModel  Group model
	 *
	 * @return array element models
	 */
	private function activeContextElements($groupModel)
	{
		$tableParams = $this->getParams();
		if ($this->formatAll() || ($tableParams->get('group_by_template') !== '' && $this->getGroupBy() != '') || $this->outPutFormat == 'csv'
			|| $this->outPutFormat == 'feed')
		{
			$elementModels = $groupModel->getPublishedElements();
		}
		else
		{
			/* $$$ hugh - added 'always render' option to elements, and methods to grab those.
			 * Could probably do this in getPublishedListElements(), but for now just grab a list
			 * of elements with 'always render' set to Yes, and "show in list" set to No,
			 * then merge that with the getPublishedListElements.  This is to work around issues
			 * where things like plugin bubble templates use placeholders for elements not shown in the list.
			 */
			$alwaysRenderElements = $this->getAlwaysRenderElements(true);
			$elementModels = $groupModel->getPublishedListElements();
			$elementModels = array_merge($elementModels, $alwaysRenderElements);
		}
		return $elementModels;
	}

	/**
	 * Translate data
	 *
	 * @param   array  &$data  data
	 *
	 * @deprecated Joomfish not available in J1.7
	 *
	 * @return  void
	 */

	function translateData(&$data)
	{
		return;
		$params = $this->getParams();
		if (!JPluginHelper::isEnabled('system', 'jfdatabase'))
		{
			return;
		}
		if (defined('JOOMFISH_PATH') && $params->get('allow-data-translation'))
		{
			$table = $this->getTable();
			$db = FabrikWorker::getDbo();
			$jf = JoomFishManager::getInstance();
			$config = JFactory::getConfig();
			$tableName = str_replace($config->get('dbprefix'), '', $table->db_table_name);
			$contentElement = $jf->getContentElement($tableName);
			if (!is_object($contentElement))
			{
				return;
			}

			$title = Fabrikstring::shortColName($params->get('joomfish-title'));
			$activelangs = $jf->getActiveLanguages();
			$registry = JFactory::getConfig();
			$langid = $activelangs[$registry->get("jflang")]->id;
			$db->setQuery($contentElement->createContentSQL($langid));
			if ($title == '')
			{
				$contentTable = $contentElement->getTable();
				foreach ($contentTable->Fields as $tableField)
				{
					if ($tableField->Type == 'titletext')
					{
						$title = $tableField->Name;
					}
				}
			}
			$longKey = FabrikString::safeColNameToArrayKey($table->db_primary_key);
			$res = $db->loadObjectList(FabrikString::shortColName($table->db_primary_key));

			// $$$ hugh - if no JF results, bail out, otherwise we pitch warnings in the foreach loop.
			if (empty($res))
			{
				return;
			}
			foreach ($data as &$row)
			{
				// $$$ rob if the id isnt published fall back to __pk_val
				$translateRow = array_key_exists($longKey, $row) ? $res[$row->$longKey] : $res[$row->__pk_val];
				foreach ($row as $key => $val)
				{
					$shortkey = array_pop(explode('___', $key));
					if ($shortkey === $title)
					{
						$row->$key = $translateRow->titleTranslation;
						$key = $key . '_raw';
						$row->$key = $translateRow->titleTranslation;
					}
					else
					{
						if (array_key_exists($shortkey, $translateRow))
						{
							$row->$key = $translateRow->$shortkey;
							$key = $key . '_raw';
							if (array_key_exists($key, $row))
							{
								$row->$key = $translateRow->$shortkey;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Run the list data through element filters
	 *
	 * @param   array  &$data  list data
	 *
	 * @return  void
	 */

	function formatData(&$data)
	{
		$profiler = JProfiler::getInstance('Application');
		jimport('joomla.filesystem.file');
		$form = $this->getFormModel();
		$tableParams = $this->getParams();
		$table = $this->getTable();
		$pluginManager = FabrikWorker::getPluginManager();
		$method = 'renderListData_' . $this->outPutFormat;
		$this->_aLinkElements = array();

		// $$$ hugh - temp foreach fix
		$groups = $form->getGroupsHiarachy();
		$ec = count($data);
		foreach ($groups as $groupModel)
		{
			$elementModels = $this->activeContextElements($groupModel);
			foreach ($elementModels as $elementModel)
			{
				$e = $elementModel->getElement();
				$elementModel->setContext($groupModel, $form, $this);
				$params = $elementModel->getParams();
				$col = $elementModel->getFullName(false, true, false);

				// Check if there is  a custom out put handler for the tables format
				// Currently supports "renderListData_csv", "renderListData_rss", "renderListData_html", "renderListData_json"
				if (!empty($data) && array_key_exists($col, $data[0]))
				{
					if (method_exists($elementModel, $method))
					{
						for ($i = 0; $i < count($data); $i++)
						{
							$thisRow = $data[$i];
							$coldata = $thisRow->$col;
							$data[$i]->$col = $elementModel->$method($coldata, $thisRow);
						}
					}
					else
					{
						JDEBUG ? $profiler->mark('elements renderListData: ' . "($ec)" . " talbeid = $table->id " . $col) : null;
						for ($i = 0; $i < $ec; $i++)
						{
							$thisRow = $data[$i];
							$coldata = $thisRow->$col;

							$data[$i]->$col = $elementModel->renderListData($coldata, $thisRow);
							$rawCol = $col . '_raw';
							/* Not sure if this works, as far as I can tell _raw will always exist, even if
							 * the element model hasn't explicitly done anything with it (except mayeb unsetting it?)
							* For instance, the calc element needs to set _raw.  For now, I changed $thisRow above to
							* be a = reference to $data[$i], and in renderListData() the calc element modifies
							* the _raw entry in $thisRow.  I guess it could simply unset the _raw in $thisRow and
							* then implement a renderRawListData.  Anyway, just sayin'.
							*/
							if (!array_key_exists($rawCol, $thisRow))
							{
								$data[$i]->$rawCol = $elementModel->renderRawListData($coldata, $thisRow);
							}
						}
					}
				}
			}
		}
		JDEBUG ? $profiler->mark('elements rendered for table data') : null;
		$this->_aGroupInfo = array();
		$groupTitle = array();

		$this->groupTemplates = array();

		// Check if the data has a group by applied to it
		$groupBy = $this->getGroupBy();
		if ($groupBy != '' && $this->outPutFormat != 'csv')
		{
			$w = new FabrikWorker;

			// 3.0 if not group by template spec'd by group but assigned in qs then use that as the group by tmpl
			$requestGroupBy = JRequest::getCmd('group_by', '');
			if ($requestGroupBy == '')
			{

				$groupTemplate = $tableParams->get('group_by_template');
				if ($groupTemplate == '')
				{
					$groupTemplate = '{' . $groupBy . '}';
				}
			}
			else
			{
				$groupTemplate = '{' . $requestGroupBy . '}';
			}
			$groupedData = array();
			$thisGroupedData = array();
			$groupBy = FabrikString::safeColNameToArrayKey($groupBy);

			$groupTitle = null;
			$aGroupTitles = array();
			$groupId = 0;
			$gKey = 0;
			for ($i = 0; $i < count($data); $i++)
			{
				if (isset($data[$i]->$groupBy))
				{
					$sdata = $data[$i]->$groupBy;

					// Test if its just an <a>*</a> tag - if so allow HTML (enables use of icons)
					$xml = new SimpleXMLElement('<div>' . $sdata . '</div>');
					$children = $xml->children();

					// Not working in PHP5.2	if (!($xml->count() === 1 && $children[0]->getName() == 'a'))
					if (!(count($xml->children()) === 1 && $children[0]->getName() == 'a'))
					{
						$sdata = strip_tags($sdata);
					}

					if (!in_array($sdata, $aGroupTitles))
					{
						$aGroupTitles[] = $sdata;
						$grouptemplate = ($w->parseMessageForPlaceHolder($groupTemplate, JArrayHelper::fromObject($data[$i])));
						$this->groupTemplates[$sdata] = nl2br($grouptemplate);
						$groupedData[$sdata] = array();
					}
					$data[$i]->_groupId = $sdata;
					$gKey = $sdata;

					// If the group_by was added in in getAsFields remove it from the returned data set (to avoid mess in package view)
					if ($this->_group_by_added)
					{
						unset($data[$i]->$groupBy);
					}
					if ($this->_temp_db_key_addded)
					{
						$k = $table->db_primary_key;
					}
				}
				$groupedData[$gKey][] = $data[$i];
			}
			$data = $groupedData;
		}
		else
		{
			for ($i = 0; $i < count($data); $i++)
			{
				if ($this->_temp_db_key_addded)
				{
					$k = $table->db_primary_key;
				}
			}
			// Make sure that the none grouped data is in the same format
			$data = array($data);
		}
		JDEBUG ? $profiler->mark('table groupd by applied') : null;
		if ($this->outPutFormat != 'pdf' && $this->outPutFormat != 'csv' && $this->outPutFormat != 'feed')
		{
			$this->addSelectBoxAndLinks($data);
			FabrikHelperHTML::debug($data, 'table:data');
		}
		JDEBUG ? $profiler->mark('end format data') : null;
	}

	/**
	 * Add the select box and various links into the data array
	 *
	 * @param   array  &$data  list's row objects
	 *
	 * @return  void
	 */

	function addSelectBoxAndLinks(&$data)
	{
		$item = $this->getTable();
		$app = JFactory::getApplication();
		$db = FabrikWorker::getDbo(true);
		$params = $this->getParams();
		$nextview = $this->canEdit() ? 'form' : 'details';
		$tmpKey = '__pk_val';
		$factedlinks = $params->get('factedlinks');

		// Get a list of fabrik lists and ids for view list and form links
		$oldLinksToForms = $this->getLinksToThisKey();
		$linksToForms = array();
		foreach ($oldLinksToForms as $join)
		{
			if ($join !== false)
			{
				$k = $join->list_id . '-' . $join->form_id . '-' . $join->element_id;
				$linksToForms[$k] = $join;
			}
		}
		$action = $app->isAdmin() ? 'task' : 'view';
		$query = $db->getQuery(true);
		$query->select('id, label, db_table_name')->from('#__{package}_lists');
		$db->setQuery($query);
		$aTableNames = $db->loadObjectList('label');
		if ($db->getErrorNum())
		{
			JError::raiseError(500, $db->getErrorMsg());
		}
		$cx = count($data);
		$viewLinkAdded = false;

		// Get pk values
		$pks = array();
		foreach ($data as $groupKey => $group)
		{
			$cg = count($group);
			for ($i = 0; $i < $cg; $i++)
			{
				$pks[] = @$data[$groupKey][$i]->$tmpKey;
			}
		}

		$joins = $this->getJoins();
		foreach ($data as $groupKey => $group)
		{
			// $group = $data[$key]; //Messed up in php 5.1 group positioning in data became ambiguous
			$cg = count($group);
			for ($i = 0; $i < $cg; $i++)
			{
				$row = $data[$groupKey][$i];
				$viewLinkAdded = false;

				// Done each row as its result can change
				$canEdit = $this->canEdit($row);
				$canView = $this->canView($row);
				$canDelete = $this->canDelete($row);

				$nextview = $canEdit ? 'form' : 'details';
				$pKeyVal = array_key_exists($tmpKey, $row) ? $row->$tmpKey : '';
				$pkcheck = array();
				$pkcheck[] = '<div style="display:none">';
				foreach ($joins as $join)
				{
					if ($join->list_id !== '0')
					{
						// $$$ rob 22/02/2011 was not using _raw before which was intserting html into the value for image elements
						$fkey = $join->table_join_alias . '___' . $join->table_key . '_raw';
						if (isset($row->$fkey))
						{
							$fKeyVal = $row->$fkey;
							$pkcheck[] = '<input type="checkbox" class="fabrik_joinedkey" value="' . htmlspecialchars($fKeyVal, ENT_COMPAT, 'UTF-8')
							. '" name="' . $join->table_join_alias . '[' . $row->__pk_val . ']" />';
						}
					}
				}
				$pkcheck[] = '</div>';
				$pkcheck = implode("\n", $pkcheck);
				$row->fabrik_select = $this->canSelectRow($row)
				? '<input type="checkbox" id="id_' . $row->__pk_val . '" name="ids[' . $row->__pk_val . ']" value="'
						. htmlspecialchars($pKeyVal, ENT_COMPAT, 'UTF-8') . '" />' . $pkcheck : '';

				// Add in some default links if no element choosen to be a link
				$link = $this->viewDetailsLink($data[$groupKey][$i]);
				$edit_link = $this->editLink($data[$groupKey][$i]);
				$row->fabrik_view_url = $link;
				$row->fabrik_edit_url = $edit_link;

				$editLinkAttribs = $this->getCustomLink('attribs', 'edit');
				$detailsLinkAttribs = $this->getCustomLink('attribs', 'details');

				$row->fabrik_view = '';
				$row->fabrik_edit = '';

				$editLabel = $params->get('editlabel', JText::_('COM_FABRIK_EDIT'));
				$editLink = '<a class="fabrik__rowlink" ' . $editLinkAttribs . 'data-list="list_' . $this->getRenderContext() . '" href="'
						. $edit_link . '" title="' . $editLabel . '">' . FabrikHelperHTML::image('edit.png', 'list', '', array('alt' => $editLabel))
						. '<span>' . $editLabel . '</span></a>';

				$viewLabel = $params->get('detaillabel', JText::_('COM_FABRIK_VIEW'));
				$viewLink = '<a class="fabrik___rowlink" ' . $detailsLinkAttribs . 'data-list="list_' . $this->getRenderContext() . '" href="'
						. $link . '" title="' . $viewLabel . '">' . FabrikHelperHTML::image('view.png', 'list', '', array('alt' => $viewLabel))
						. '<span>' . $viewLabel . '</span></a>';

				// 3.0 actions now in list in one cell
				$row->fabrik_actions = array();
				$actionMethod = $this->actionMethod();
				if ($canView || $canEdit)
				{
					if ($canEdit == 1)
					{
						if ($params->get('editlink') || $actionMethod == 'floating')
						{
							$row->fabrik_edit = $editLink;
							$row->fabrik_actions['fabrik_edit'] = '<li class="fabrik_edit">' . $row->fabrik_edit . '</li>';
						}
						$row->fabrik_edit_url = $edit_link;
						if ($this->canViewDetails() && ($params->get('detaillink') == 1 || $actionMethod == 'floating'))
						{
							$row->fabrik_view = $viewLink;
							$row->fabrik_actions['fabrik_view'] = '<li class="fabrik_view">' . $row->fabrik_view . '</li>';
						}
					}
					else
					{
						if ($this->canViewDetails() && ($params->get('detaillink') == '1' || $actionMethod == 'floating'))
						{
							if (empty($this->_aLinkElements))
							{
								$viewLinkAdded = true;
								$row->fabrik_view = $viewLink;
								$row->fabrik_actions['fabrik_view'] = '<li class="fabrik_view">' . $row->fabrik_view . '</li>';
							}
						}
						else
						{
							$row->fabrik_edit = '';
						}
					}
				}
				if ($this->canViewDetails() && !$viewLinkAdded && ($params->get('detaillink') == '1' || $actionMethod == 'floating'))
				{
					$link = $this->viewDetailsLink($row, 'details');
					$row->fabrik_view_url = $link;
					$row->fabrik_view = $viewLink;
					$row->fabrik_actions['fabrik_view'] = '<li class="fabrik_view">' . $row->fabrik_view . '</li>';
				}
				if ($this->canDelete($row))
				{
					$row->fabrik_actions['fabrik_delete'] = $this->deleteButton();
				}
				// Create columns containing links which point to tables associated with this table
				$oldJoinsToThisKey = $this->getJoinsToThisKey();
				$joinsToThisKey = array();
				foreach ($oldJoinsToThisKey as $join)
				{
					$k = $join->list_id . '-' . $join->form_id . '-' . $join->element_id;
					$joinsToThisKey[$k] = $join;
				}
				foreach ($joinsToThisKey as $f => $join)
				{
					// $$$ hugh - for reasons I don't understand, $joinsToThisKey now contains entries
					// which aren't in $factedlinks->linkedlist, so added this sanity check.
					if (isset($factedlinks->linkedlist->$f))
					{
						$linkedTable = $factedlinks->linkedlist->$f;
						$popupLink = $factedlinks->linkedlist_linktype->$f;
						$linkedListText = $factedlinks->linkedlisttext->$f;
						if ($linkedTable != '0')
						{
							$recordKey = $join->element_id . '___' . $linkedTable;
							$key = $recordKey . "_list_heading";
							$val = $pKeyVal;
							$recordCounts = $this->getRecordCounts($join, $pks);
							$count = 0;
							$linkKey = $recordCounts['linkKey'];
							if (is_array($recordCounts))
							{
								if (array_key_exists($val, $recordCounts))
								{
									$count = $recordCounts[$val]->total;
									$linkKey = $recordCounts[$val]->linkKey;
								}
								else
								{
									if (array_key_exists((int) $val, $recordCounts) && (int) $val !== 0)
									{
										$count = $recordCounts[(int) $val]->total;
										$linkKey = $recordCounts[$val]->linkKey;
									}
								}
							}
							$join->list_id = array_key_exists($join->listlabel, $aTableNames) ? $aTableNames[$join->listlabel]->id : '';
							$group[$i]->$key = $this->viewDataLink($popupLink, $join, $row, $linkKey, $val, $count, $f);
						}
						// $$$ hugh - pretty sure we don't need to be doing this
						// $f++;
					}
				}

				// Create columns containing links which point to forms assosciated with this table
				foreach ($linksToForms as $f => $join)
				{
					$linkedForm = $factedlinks->linkedform->$f;
					$popupLink = $factedlinks->linkedform_linktype->$f;
					/* $$$ hugh @TODO - rob, can you check this, I added this line,
					 * but the logic applied for $val in the linked table code above seems to be needed?
					* http://fabrikar.com/forums/showthread.php?t=9535
					*/
					$val = $pKeyVal;
					if ($linkedForm !== '0')
					{
						if (is_object($join))
						{
							// $$$rob moved these two lines here as there were giving warnings since Hugh commented out the if ($element != '') {
							$linkKey = @$join->db_table_name . '___' . @$join->name;
							$gkey = $linkKey . '_form_heading';
							$row2 = JArrayHelper::fromObject($row);
							$linkLabel = $this->parseMessageForRowHolder($factedlinks->linkedformtext->$f, $row2);
							$group[$i]->$gkey = $this->viewFormLink($popupLink, $join, $row, $linkKey, $val, false, $f);
						}
					}
				}
			}
		}
		$args['data'] = &$data;
		$pluginButtons = $this->getPluginButtons();
		foreach ($data as $groupKey => $group)
		{
			$cg = count($group);
			for ($i = 0; $i < $cg; $i++)
			{
				$row = $data[$groupKey][$i];
				foreach ($pluginButtons as $b)
				{
					if (trim($b) !== '')
					{
						$row->fabrik_actions[] = '<li>' . $b . '</li>';
					}
				}
				if (!empty($row->fabrik_actions))
				{
					if (count($row->fabrik_actions) > $this->rowActionCount)
					{
						$this->rowActionCount = count($row->fabrik_actions);
					}
					$row->fabrik_actions = '<ul class="fabrik_action">' . implode("\n", $row->fabrik_actions) . '</ul>';
				}
				else
				{
					$row->fabrik_actions = '';
				}
			}
		}
	}

	/**
	 * Get the way row buttons are rendered floating/inline
	 * Can be set either by global config or list options
	 *
	 * @since   3.0.7
	 *
	 * @return  string
	 */

	public function actionMethod()
	{
		$params = $this->getParams();
		if ($params->get('actionMethod', 'default') == 'default')
		{
			// Use global
			$fbConfig = JComponentHelper::getParams('com_fabrik');
			return $fbConfig->get('actionMethod', 'floating');
		}
		else
		{
			return $params->get('actionMethod', 'floating');
		}
	}

	/**
	 * Get delete button
	 *
	 * @param   string  $tpl  Template
	 *
	 * @since 3.0
	 *
	 * @return	string	delete button wrapped in <li>
	 */

	protected function deleteButton($tpl = '')
	{
		$tpl = $this->getTmpl();
		return '<li class="fabrik_delete"><a href="#" class="delete" title="' . JText::_('COM_FABRIK_DELETE') . '">'
				. FabrikHelperHTML::image('delete.png', 'list', $tpl, array('alt' => JText::_('COM_FABRIK_DELETE'))) . '<span>'
						. JText::_('COM_FABRIK_DELETE') . '</span></a></li>';
	}

	/**
	 * Get a list of possible menus
	 * USED TO BUILD RELTED TABLE LNKS WITH CORRECT iTEMD AND TEMPLATE
	 *
	 * @since   2.0.4
	 *
	 * @return  array  linked table menu items
	 */

	protected function getTableLinks()
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		if (isset($this->tableLinks))
		{
			return $this->tableLinks;
		}
		$db = JFactory::getDBO();
		$joinsToThisKey = $this->getJoinsToThisKey();
		if (empty($joinsToThisKey))
		{
			$this->tableLinks = array();
		}
		else
		{
			$query = $db->getQuery(true);
			$query->select('*')->from('#__menu');
			foreach ($joinsToThisKey as $element)
			{
				$linkWhere[] = 'link LIKE "index.php?option=com_' . $package . '&view=list&listid=' . (int) $element->list_id . '%"';
			}
			$where = 'type = "component" AND (' . implode(' OR ', $linkWhere) . ')';
			$query->where($where);
			$db->setQuery($query);
			$this->tableLinks = $db->loadObjectList();
		}
		return $this->tableLinks;
	}

	/**
	 * For releated table links get the record count for each of the table's rows
	 *
	 * @param   object  &$element  element
	 * @param   array   $pks       primary keys to count on
	 *
	 * @return  array  counts key'd on element primary key
	 */

	public function getRecordCounts(&$element, $pks = array())
	{
		if (!isset($this->recordCounts))
		{
			$this->recordCounts = array();
		}
		$k = $element->element_id;
		if (array_key_exists($k, $this->recordCounts))
		{
			return $this->recordCounts[$k];
		}
		$listModel = JModel::getInstance('List', 'FabrikFEModel');
		$listModel->setId($element->list_id);
		$db = $listModel->getDb();
		$elementModel = $listModel->getFormModel()->getElement($element->element_id, true);
		$key = $elementModel->getFullName(false, false, false);
		$linkKey = FabrikString::safeColName($key);
		$fparams = $listModel->getParams();

		// Ensure that the facted list's "require filters" option is set to false
		$fparams->set('require-filter', false);

		// Ignore facted lists session filters
		$origIncSesssionFilters = JRequest::getVar('fabrik_incsessionfilters', true);
		JRequest::setVar('fabrik_incsessionfilters', false);
		$where = $listModel->_buildQueryWhere(JRequest::getVar('incfilters', 0));
		if (!empty($pks))
		{
			// Only load the current record sets record counts
			$where .= trim($where) == '' ? ' WHERE ' : ' AND ';
			$where .= "$linkKey IN (" . implode(',', $pks) . ")";
		}
		// Force reload of join sql
		$listModel->set('_joinsSQL', null);

		// Trigger load of joins without cdd elements - seems to mess up count otherwise
		$listModel->set('includeCddInJoin', false);

		// See http://fabrikar.com/forums/showthread.php?t=12860
		// $totalSql  = "SELECT $linkKey AS id, COUNT($linkKey) AS total FROM " . $element->db_table_name . " " . $tableModel->_buildQueryJoin();

		$k2 = $db->quote(FabrikString::safeColNameToArrayKey($key));

		// $totalSql  = "SELECT $k2 AS linkKey, $linkKey AS id, COUNT($linkKey) AS total FROM " . $listModel->getTable()->db_table_name . " " . $listModel->_buildQueryJoin();

		// $$$ Jannus - see http://fabrikar.com/forums/showthread.php?t=20751
		$distinct = $listModel->mergeJoinedData() ? 'DISTINCT ' : '';
		$totalSql = "SELECT $k2 AS linkKey, $linkKey AS id, COUNT($distinct " . $listModel->getTable()->db_primary_key . ") AS total FROM "
				. $listModel->getTable()->db_table_name . " " . $listModel->_buildQueryJoin();

		$totalSql .= " $where GROUP BY $linkKey";
		$listModel->set('includeCddInJoin', true);
		$db->setQuery($totalSql);
		$this->recordCounts[$k] = $db->loadObjectList('id');
		$this->recordCounts[$k]['linkKey'] = FabrikString::safeColNameToArrayKey($key);
		FabrikHelperHTML::debug($db->getQuery(), 'getRecordCounts query: ' . $linkKey);
		FabrikHelperHTML::debug($this->recordCounts[$k], 'getRecordCounts data: ' . $linkKey);
		JRequest::setVar('fabrik_incsessionfilters', $origIncSesssionFilters);
		return $this->recordCounts[$k];
	}

	/**
	 * Creates the html <a> link allowing you to edit other forms from the table
	 * E.g. Faceted browsing: those specified in the table's "Form's whose primary keys link to this table"
	 *
	 * @param   bool    $popUp    is popup link
	 * @param   object  $element  27/06/2011 - changed to passing in element
	 * @param   object  $row      current list row
	 * @param   string  $key      key
	 * @param   string  $val      value
	 * @param   bool    $usekey   use the key
	 * @param   int     $f        repeat value 27/11/2011
	 *
	 * @return  string	<a> html part
	 */

	public function viewFormLink($popUp = false, $element = null, $row = null, $key = '', $val = '', $usekey = false, $f = 0)
	{
		$elKey = $element->list_id . '-' . $element->form_id . '-' . $element->element_id;
		$params = $this->getParams();
		$listid = $element->list_id;
		$formid = $element->form_id;
		$linkedFormText = $params->get('linkedformtext');
		$factedlinks = $params->get('factedlinks');
		$linkedFormText = JArrayHelper::fromObject($factedlinks->linkedformtext);
		$msg = JArrayHelper::getValue($linkedFormText, $elKey);
		$row2 = JArrayHelper::fromObject($row);
		$label = $this->parseMessageForRowHolder($msg, $row2);
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		if (!$app->isAdmin())
		{
			$Itemid = (int) @$app->getMenu('site')->getActive()->id;
		}
		if (is_null($listid))
		{
			$list = $this->getTable();
			$listid = $list->id;
		}
		if (is_null($formid))
		{
			$form = $this->getFormModel()->getForm();
			$formid = $form->id;
		}
		$facetTable = $this->_facetedTable($listid);
		if (!$facetTable->canAdd())
		{
			return '<div style="text-align:center"><a title="' . JText::_('JERROR_ALERTNOAUTHOR')
			. '"><img src="media/com_fabrik/images/login.png" alt="' . JText::_('JERROR_ALERTNOAUTHOR') . '" /></a></div>';
		}
		if ($app->isAdmin())
		{
			$bits[] = 'task=form.view';
			$bits[] = 'cid=' . $formid;
		}
		else
		{
			$bits[] = 'view=form';
			$bits[] = 'Itemid=' . $Itemid;
		}
		$bits[] = 'formid=' . $formid;
		$bits[] = 'referring_table=' . $this->getTable()->id;

		// $$$ hugh - change in fabrikdatabasejoin getValue() means we have to append _raw to key name
		if ($key != '')
		{
			$bits[] = $key . '_raw=' . $val;
		}
		if ($popUp)
		{
			$bits[] = "tmpl=component";
			$bits[] = "ajax=1";
		}
		if ($usekey and $key != '' and !is_null($row))
		{
			$bits[] = 'usekey=' . FabrikString::shortColName($key);
			$bits[] = 'rowid=' . $row->slug;
		}
		else
		{
			$bits[] = 'rowid=0';
		}

		$url = 'index.php?option=com_' . $package . '&' . implode('&', $bits);
		$url = JRoute::_($url);
		if (is_null($label) || $label == '')
		{
			$label = JText::_('COM_FABRIK_LINKED_FORM_ADD');
		}
		if ($popUp)
		{
			FabrikHelperHTML::mocha('a.popupwin');
			$opts = new stdClass;
			$opts->maximizable = 1;
			$opts->title = JText::_('COM_FABRIK_ADD');
			$opts->evalScripts = 1;
			$opts = json_encode($opts);
			$link = "<a rel='$opts' href=\"$url\" class=\"popupwin\" title=\"$label\">" . $label . "</a>";
		}
		else
		{
			$link = '<a href="' . $url . '" title="' . $label . '">' . $label . '</a>';
		}
		$url = '<span class="addbutton">' . $link . '</span></a>';
		return $url;
	}

	/**
	 * Get one of the current tables facet tables
	 *(used in tables that link to this lists links)
	 *
	 * @param   int  $id  list id
	 *
	 * @return  object	table
	 */

	function _facetedTable($id)
	{
		if (!isset($this->facettables))
		{
			$this->facettables = array();
		}
		if (!array_key_exists($id, $this->facettables))
		{
			$this->facettables[$id] = JModel::getInstance('List', 'FabrikFEModel');
			$this->facettables[$id]->setId($id);
		}
		return $this->facettables[$id];
	}

	/**
	 * Build the link (<a href..>) for viewing list data
	 *
	 * @param   bool    $popUp    is the link to generated a popup to show
	 * @param   object  $element  27/06/2011
	 * @param   object  $row      current list row data
	 * @param   string  $key      28/06/2011 - do longer passed in with _raw appended (done in this method)
	 * @param   string  $val      value
	 * @param   int     $count    number of related records
	 * @param   int     $f        ref to related data admin info 27/16/2011
	 *
	 * @return  string
	 */

	public function viewDataLink($popUp = false, $element = null, $row = null, $key = '', $val = '', $count = 0, $f = null)
	{
		$elKey = $element->list_id . '-' . $element->form_id . '-' . $element->element_id;
		$listid = $element->list_id;
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$params = $this->getParams();
		$factedLinks = $params->get('factedlinks');

		/* $$$ hugh - we are getting element keys that aren't in the linkedlisttext.
		 * not sure why, so added this defensive code.  Should probably find out
		* why though!  I just needed to make this error go away NAO!
		*/
		$linkedListText = isset($factedLinks->linkedlisttext->$elKey) ? $factedLinks->linkedlisttext->$elKey : '';
		$row2 = JArrayHelper::fromObject($row);
		$label = $this->parseMessageForRowHolder($linkedListText, $row2);

		$Itemid = $app->isAdmin() ? 0 : @$app->getMenu('site')->getActive()->id;

		$action = $app->isAdmin() ? 'task' : 'view';
		$url = 'index.php?option=com_' . $package . '&';

		if (is_null($listid))
		{
			$list = $this->getTable();
			$listid = $list->id;
		}
		$facetTable = $this->_facetedTable($listid);
		if (!$facetTable->canView())
		{
			return '<div style="text-align:center"><a title="' . JText::_('COM_FABRIK_NO_ACCESS_PLEASE_LOGIN')
			. '"><img src="media/com_fabrik/images/login.png" alt="' . JText::_('COM_FABRIK_NO_ACCESS_PLEASE_LOGIN') . '" /></a></div>';
		}
		$tlabel = ($label === '') ? JText::_('COM_FABRIK_NO_RECORDS') : '(0) ' . $label;

		if ($count === 0)
		{
			$aExisitngLinkedForms = (array) $params->get('linkedform');
			$linkedForm = JArrayHelper::getValue($aExisitngLinkedForms, $f, false);
			$addLink = $linkedForm == '0' ? $this->viewFormLink($popUp, $element, $row, $key, $val, false, $f) : '';
			return '<div style="text-align:center" class="related_data_norecords">' . $tlabel . '</div>' . $addLink;
		}
		$key .= '_raw';
		if ($label === '')
		{
			$label = JText::_('COM_FABRIK_VIEW');
		}
		$label = '<span class="fabrik_related_data_count">(' . $count . ')</span> ' . $label;
		if ($app->isAdmin())
		{
			$bits[] = 'task=list.view';
			$bits[] = 'cid=' . $listid;
		}
		else
		{
			$bits[] = 'view=list';
			$bits[] = 'listid=' . $listid;
			$listLinks = $this->getTableLinks();

			// $$$ rob 01/03/2011 find at matching itemid in another menu item for the related data link
			foreach ($listLinks as $listLink)
			{
				if (strstr($listLink->link, 'index.php?option=com_' . $package . '&view=list&listid=' . $listid))
				{
					$bits[] = 'Itemid=' . $listLink->id;
					$Itemid = $listLink->id;
					break;
				}
			}
			$bits[] = 'Itemid=' . $Itemid;
		}
		if ($key != '')
		{
			$bits[] = $key . '=' . $val;
		}
		$bits[] = 'limitstart' . $listid . '=0';
		if ($popUp)
		{
			$bits[] = 'tmpl=component';
			$bits[] = 'ajax=1';
		}
		$bits[] = '&resetfilters=1';

		// Nope stops url filter form workin on related data :(
		// $bits[] = 'clearfilters=1';

		// Test for releated data, filter once, go backt o main list re-filter -
		$bits[] = '&fabrik_incsessionfilters=0';
		$url .= implode('&', $bits);
		$url = JRoute::_($url);
		if ($popUp)
		{
			FabrikHelperHTML::windows('a.popupwin');
			$opts = new stdClass;
			$opts->maximizable = 1;
			$opts->title = JText::_('COM_FABRIK_VIEW');
			$opts->evalScripts = 1;
			$opts = str_replace('"', "'", json_encode($opts));
			$url = '<a rel="' . $opts . '" href="' . $url . '" class="popupwin">' . $label . '</a>';
		}
		else
		{
			$url = '<a class="related_data" href="' . $url . '">' . $label . "</a>";
		}
		return $url;
	}

	/**
	 * Add a normal/custom link to the element data
	 *
	 * @param   string  $data           element data
	 * @param   object  &$elementModel  element model
	 * @param   object  $row            of all row data
	 * @param   int     $repeatCounter  repeat group counter
	 *
	 * @return  string	element data with link added if specified
	 */

	public function _addLink($data, &$elementModel, $row, $repeatCounter = 0)
	{
		$element = $elementModel->getElement();
		if ($this->outPutFormat == 'csv' || $element->link_to_detail == 0)
		{
			return $data;
		}
		$params = $elementModel->getParams();
		$customLink = $params->get('custom_link');

		// $$$ rob if its a custom link then we aren't linking to the details view so we should
		// ignore the view details access settings
		if (!($this->canViewDetails($row) || $this->canEdit()) && trim($customLink) == '')
		{
			return $data;
		}
		$list = $this->getTable();
		$primaryKeyVal = $this->getKeyIndetifier($row);
		$link = $this->linkHref($elementModel, $row, $repeatCounter);
		if ($link == '')
		{
			return $data;
		}
		// Try to remove any previously entered links
		$data = preg_replace('/<a(.*?)>|<\/a>/', '', $data);
		$class = '';
		if ($this->canViewDetails($row))
		{
			$class = 'fabrik_view';
		}
		if ($this->canEdit($row))
		{
			$class = 'fabrik_edit';
		}
		$data = '<a data-list="list_' . $this->getRenderContext() . '" class="fabrik___rowlink ' . $class . '" href="' . $link . '">' . $data
		. '</a>';
		return $data;
	}

	/**
	 * Get the href for the edit/details link
	 *
	 * @param   object  $elementModel   element model
	 * @param   array   $row            lists current row data
	 * @param   int     $repeatCounter  repeat group counter
	 *
	 * @since   2.0.4
	 *
	 * @return  string	link href
	 */

	public function linkHref($elementModel, $row, $repeatCounter = 0)
	{
		$element = $elementModel->getElement();
		$table = $this->getTable();
		$params = $elementModel->getParams();
		$customLink = $params->get('custom_link');
		$link = '';
		if ($customLink == '')
		{
			// $$$ rob only test canEdit and canView on stardard edit links - if custom we should always use them,
			// 3.0 get either edit or view link - as viewDetailslInk now always returns the view details link
			if ($this->canEdit($row))
			{
				$this->_aLinkElements[] = $element->name;
				$link = $this->editLink($row);
			}
			elseif ($this->canViewDetails($row))
			{
				$this->_aLinkElements[] = $element->name;
				$link = $this->viewDetailsLink($row);
			}
		}
		else
		{
			$array = JArrayHelper::fromObject($row);
			foreach ($array as $k => &$v)
			{
				/* $$$ hugh - not everything is JSON, some stuff is just plain strings.
				 * So we need to see if JSON encoding failed, and only use result if it didn't.
				* $v = json_decode($v, true);
				*/
				if (is_array($v))
				{
					$v = JArrayHelper::getValue($v, $repeatCounter);
				}
				else
				{
					$v2 = json_decode($v, true);
					if ($v2 !== null)
					{
						if (is_array($v2))
						{
							$v = JArrayHelper::getValue($v2, $repeatCounter);
						}
						else
						{
							$v = $v2;
						}
					}
				}
			}
			$array['rowid'] = $this->getSlug($row);
			$array['listid'] = $table->id;
			$link = JRoute::_($this->parseMessageForRowHolder($customLink, $array));
		}
		return $link;
	}

	/**
	 * get query to make records
	 *
	 * @return  string	sql
	 */

	function _buildQuery()
	{
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark('_buildQuery: start') : null;
		$query = array();
		$this->mergeQuery = '';
		$table = $this->getTable();
		if ($this->mergeJoinedData())
		{
			/* $$$ rob - get a list of the main table's ids limited on the navigation
			 * this will then be used to filter the main query,
			* by modifying the where part of the query
			*/
			$db = $this->getDb();
			$table = $this->getTable();

			/* $$$ rob 23/05/2012 if the search data is in the joined records we want to get the id's for the joined records and not the master record
			 see http://fabrikar.com/forums/showthread.php?t=26400. This is a partial hack as I can't see how we know which joined record is really last
			$$$ rob 25/05/2012 - slight change so that we work our way up the pk/fk list until we find some ids.
			$$$ hugh, later in the day 25/05/2012 - big OOOOPS, see comment below about table_key vs table_join_key!
			erm no not a mistake!?! reverted as no example of what was wrong with original code
			*/
			$joins = $this->getJoins();

			// Default to the primary key as before this fix
			$lookupC = 0;
			$tmpPks = array();

			foreach ($joins as $join)
			{
				// $$$ hugh - added repeatElement, as _makeJoinAliases() is going to set canUse to false for those,
				// so they won't get included in the query ... so will blow up if we reference them with __pk_calX selection
				if ($join->_params->get('type') !== 'element' && $join->_params->get('type') !== 'repeatElement')
				{
					// $$$ hugh - need to be $lookupC + 1, otherwise we end up with two 0's, 'cos we added main table above

					/**
					 * [non-merged data]
					 *
					 * country	towm
					 * ------------------------------
					 * france	la rochelle
					 * france	paris
					 * france	bordeaux
					 *
					 * [merged data]
					 *
					 * country	town
					 * -------------------------------
					 * france	la rochelle
					 * 			paris
					 * 			bordeaux
					 *
					 * [now search on town = 'la rochelle']
					 *
					 * If we dont use this new code then the search results show all three towns.
					 * By getting the lowest set of complete primary keys (in this example the town ids) we set our query to be:
					 *
					 * where town_id IN (1)
					 *
					 * which gives a search result of
					 *
					 * country	town
					 * -------------------------------
					 * france	la rochelle
					 *
					 */
					$pk = $join->_params->get('pk');
					if (!array_key_exists($pk, $tmpPks) || !is_array($tmpPks[$pk]))
					{
						$tmpPks[$pk] = array($pk);
					}
					else
					{
						if (count($tmpPks[$pk]) == 1)
						{
							$v = str_replace('`', '', $tmpPks[$pk][0]);
							$v = explode('.', $v);
							$v[0] = $v[0] . '_0';
							$tmpPks[$pk][0] = $db->quoteName($v[0] . '.' . $v[1]);
						}
						$v = str_replace('`', '', $pk);
						$v = explode('.', $v);
						$v[0] = $v[0] . '_' . count($tmpPks[$pk]);
						$tmpPks[$pk][] = $db->quoteName($v[0] . '.' . $v[1]);
					}
				}
			}
			// Check for duplicate pks if so we can presume that they are aliased with _X in from query
			$lookupC = 0;
			$lookUps = array('DISTINCT ' . $table->db_primary_key . ' AS __pk_val' . $lookupC);
			$lookUpNames = array($table->db_primary_key);

			foreach ($tmpPks as $pks)
			{
				foreach ($pks as $pk)
				{
					$lookUps[] = $pk . ' AS __pk_val' . ($lookupC + 1);
					$lookUpNames[] = $pk;
					$lookupC++;
				}
			}

			// $$$ rob if no ordering applied i had results where main record (e.g. UK) was shown in 2 lines not next to each other
			// causing them not to be merged and a 6 rows shown when limit set to 5. So below, if no order by set then order by main pk asc
			$by = trim($table->order_by) === '' ? array() : (array) json_decode($table->order_by);
			if (empty($by))
			{
				$dir = (array) json_decode($table->order_dir);
				array_unshift($dir, 'ASC');
				$table->order_dir = json_encode($dir);

				$by = (array) json_decode($table->order_by);
				array_unshift($by, $table->db_primary_key);
				$table->order_by = json_encode($by);
			}

			// $$$ rob build order first so that we know of any elemenets we need to include in the select statement
			$order = $this->_buildQueryOrder();
			$this->selectedOrderFields = (array) $this->selectedOrderFields;
			$this->selectedOrderFields = array_unique(array_merge($lookUps, $this->selectedOrderFields));
			$query['select'] = 'SELECT  ' . implode(', ', $this->selectedOrderFields) . ' FROM ' . $db->quoteName($table->db_table_name);

			$query['join'] = $this->_buildQueryJoin();
			$query['where'] = $this->_buildQueryWhere(JRequest::getVar('incfilters', 1));
			$query['groupby'] = $this->_buildQueryGroupBy();
			$query['order'] = $order;

			// Check that the order by fields are in the select statement
			$squery = implode(' ', $query);

			// Can't limit the query here as this gives incorrect _data array.
			// $db->setQuery($squery, $this->limitStart, $this->limitLength);
			$db->setQuery($squery);
			$this->mergeQuery = $db->getQuery();
			FabrikHelperHTML::debug($db->getQuery(), 'table:mergeJoinedData get ids');
			$ids = array();
			$idRows = $db->loadObjectList();

			// $$$ hugh - can't use simple !$idRows, as empty array is false!
			if (!is_array($idRows))
			{
				JError::raiseError(500, $db->getErrorMsg());
			}
			$maxPossibleIds = count($idRows);

			// An array of the lists pk values
			$mainKeys = array();
			foreach ($idRows as $r)
			{
				$mainKeys[] = $db->quote($r->__pk_val0);
			}
			// Chop up main keys for list limitstart, length to cull the data down to the correct length as defined by the page nav/ list settings
			$mainKeys = array_slice(array_unique($mainKeys), $this->limitStart, $this->limitLength);
			/**
			 * $$$ rob get an array containing the PRIMARY key values for each joined tables data.
			 * Stop as soon as we have a set of ids totaling the sum of records contained in $this->mergeQuery / $idRows
			*/

			while (count($ids) < $maxPossibleIds && $lookupC >= 0)
			{
				$ids = JArrayHelper::getColumn($idRows, '__pk_val' . $lookupC);
				for ($idx = count($ids) - 1; $idx >= 0; $idx--)
				{
					if ($ids[$idx] == '')
					{
						unset($ids[$idx]);
					}
					else
					{
						$ids[$idx] = $db->quote($ids[$idx]);
					}
				}
				if (count($ids) < $maxPossibleIds)
				{
					$lookupC--;
				}
			}
		}

		// Now lets actually construct the query that will get the required records:
		$query = array();
		$query['select'] = $this->_buildQuerySelect();
		JDEBUG ? $profiler->mark('queryselect: got') : null;
		$query['join'] = $this->_buildQueryJoin();
		JDEBUG ? $profiler->mark('queryjoin: got') : null;

		if ($this->mergeJoinedData())
		{
			/* $$$ rob We've already used buildQueryWhere to get our list of main pk ids.
			 * so lets use that list of ids to create the where statement. This will return 5/10/20 etc
			* records from our main table, as per our page nav, even if a main record has 3 rows of joined
			* data. If no ids found then do where 1 = -1 to return no records
			*/
			if (!empty($ids))
			{
				$query['where'] = ' WHERE ' . $lookUpNames[$lookupC] . ' IN (' . implode(array_unique($ids), ',') . ')';

				if (!empty($mainKeys))
				{
					// Limit to the current page
					$query['where'] .= ' AND ' . $table->db_primary_key . ' IN (' . implode($mainKeys, ',') . ')';
				}
			}
			else
			{
				$query['where'] = ' WHERE 1 = -1';
			}
		}
		else
		{
			// $$$ rob we aren't merging joined records so lets just add the standard where query
			// Incfilters set when exporting as CSV
			$query['where'] = $this->_buildQueryWhere(JRequest::getVar('incfilters', 1));
		}
		$query['groupby'] = $this->_buildQueryGroupBy();
		$query['order'] = $this->_buildQueryOrder();
		$query = $this->pluginQuery($query);
		$query = implode(' ', $query);
		$this->mainQuery = $query;
		return $query;
	}

	/**
	 * Pass an sql query through the table plug-ins
	 *
	 * @param   string  $query  sql query
	 *
	 * @return  string	altered query.
	 */

	public function pluginQuery($query)
	{
		// Pass the query as an object property so it can be updated via reference
		$args = new stdClass;
		$args->query = $query;
		FabrikWorker::getPluginManager()->runPlugins('onQueryBuilt', $this, 'list', $args);
		$query = $args->query;
		return $query;
	}

	/**
	 * Add the slug field to the select fields, called from buildQuerySelect()
	 *
	 * @param   array  &$fields  fields
	 *
	 * @since 3.0.6
	 *
	 * @return  void
	 */

	private function selectSlug(&$fields)
	{
		$formModel = $this->getFormModel();
		$item = $this->getTable();
		$pk = FabrikString::safeColName($item->db_primary_key);
		$params = $this->getParams();
		if (in_array($this->outPutFormat, array('raw', 'html', 'feed', 'pdf', 'phocapdf')))
		{
			$slug = $params->get('sef-slug');
			$raw = JString::substr($slug, JString::strlen($slug) - 4, 4) == '_raw' ? true : false;
			$slug = FabrikString::rtrimword($slug, '_raw');

			$slugElement = $formModel->getElement($slug);
			if ($slugElement)
			{
				$slug = $slugElement->getSlugName($raw);
			}

			// Test slug is not ``.``
			if (preg_match('/[A-Z|a-z][0-9]/', $slug))
			{
				$slug = FabrikString::safeColName($slug);
				$fields[] = "CONCAT_WS(':', $pk, $slug) AS slug";
			}
			else
			{
				if ($pk !== '``')
				{
					$fields[] = $pk . ' AS slug';
				}
			}
		}
	}

	/**
	 * Get the select part of the query
	 *
	 * @param   string  $mode  list/form - effects which elements are selected
	 *
	 * @return  string
	 */

	function _buildQuerySelect($mode = 'list')
	{
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark('queryselect: start') : null;
		$db = $this->getDb();
		$form = $this->getFormModel();
		$table = $this->getTable();
		$form->getGroupsHiarachy();
		JDEBUG ? $profiler->mark('queryselect: fields load start') : null;
		$fields = $this->getAsFields($mode);
		$pk = FabrikString::safeColName($table->db_primary_key);
		$params = $this->getParams();
		$this->selectSlug($fields);
		JDEBUG ? $profiler->mark('queryselect: fields loaded') : null;
		$sfields = (empty($fields)) ? '' : implode(", \n ", $fields) . "\n ";

		// $$$rob added raw as an option to fix issue in saving calendar data
		if (trim($table->db_primary_key) != '' && (in_array($this->outPutFormat, array('raw', 'html', 'feed', 'pdf', 'phocapdf', 'csv'))))
		{
			$sfields .= ', ';
			$strPKey = $pk . ' AS ' . $db->quoteName('__pk_val') . "\n";
			$query = 'SELECT SQL_CALC_FOUND_ROWS DISTINCT ' . $sfields . $strPKey;
		}
		else
		{
			$query = 'SELECT SQL_CALC_FOUND_ROWS DISTINCT ' . trim($sfields, ", \n") . "\n";
		}
		$query .= ' FROM ' . $db->quoteName($table->db_table_name) . " \n";
		return $query;
	}

	/**
	 * Get the part of the sql statement that orders the table data
	 * Since 3.0.7 caches the results as calling orderBy twice when using single ordering in admin module anules the user selected order by
	 *
	 * @param   mixed  $query  false or a query object
	 *
	 * @return  string	ordering part of sql statement
	 */

	public function _buildQueryOrder($query = false)
	{
		if (isset($this->orderBy))
		{
			return $this->orderBy;
		}
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$params = $this->getParams();
		$formModel = $this->getFormModel();
		$table = $this->getTable();
		$db = $this->getDb();
		$this->selectedOrderFields = array();
		if ($this->outPutFormat == 'fabrikfeed' || $this->outPutFormat == 'feed')
		{
			$dateColId = (int) $params->get('feed_date', 0);
			$dateColElement = $formModel->getElement($dateColId, true);
			$dateCol = $db->quoteName($dateColElement->getFullName(false, false, false));
			if ($dateColId !== 0)
			{
				$this->order_dir = 'DESC';
				$this->order_by = $dateCol;
				if (!$query)
				{
					return "\n" . ' ORDER BY ' . $dateCol . ' DESC';
				}
				else
				{
					$query->order($dateCol . ' DESC');
					return $query;
				}
			}
		}
		$session = JFactory::getSession();

		/**
		 * When list reordered the controller runs order() and
		 * stores the order settings in the session by calling setOrderByAndDir()
		 * it then redirects to the list view and here all we need to do it get
		 * those order settings from the session
		*/

		$elements = $this->getElements();

		// Build the order by statement from the session
		$strOrder = '';
		$clearOrdering = (bool) JRequest::getInt('clearordering', false) && JRequest::getCmd('task') !== 'order';
		$singleOrdering = $this->singleOrdering();

		$id = $this->getId();
		foreach ($elements as $element)
		{
			$context = 'com_' . $package . '.list' . $this->getRenderContext() . '.order.' . $element->getElement()->id;
			if ($clearOrdering)
			{
				$session->set($context, '');
			}
			else
			{
				// $$$tom Added single-ordering option
				if (!$singleOrdering || ($singleOrdering && $element->getElement()->id == JRequest::getInt('orderby', '')))
				{
					$dir = $session->get($context);
					if ($dir != '' && $dir != '-' && trim($dir) != 'Array')
					{
						$strOrder == '' ? $strOrder = "\n ORDER BY " : $strOrder .= ',';
						$strOrder .= $element->getOrderByName() . ' ' . $dir;
						$this->orderEls[] = $element->getOrderByName();
						$this->orderDirs[] = $dir;
						$element->getAsField_html($this->selectedOrderFields, $aAsFields);
					}
				}
				else
				{
					$session->set($context, '');
				}
			}
		}
		// If nothing found in session use default ordering (or that set by querystring)
		if ($strOrder == '')
		{
			$orderbys = explode(',', JRequest::getVar('order_by', ''));
			if ($orderbys[0] == '')
			{
				$orderbys = json_decode($table->order_by, true);
			}
			// $$$ not sure why, but sometimes $orderbys is NULL at this point.
			if (!isset($orderbys))
			{
				$orderbys = array();
			}
			// Covert ids to names (were stored as names but then stored as ids)
			foreach ($orderbys as &$orderby)
			{
				if (is_numeric($orderby))
				{
					$elementModel = $formModel->getElement($orderby, true);
					$orderby = $elementModel ? $elementModel->getOrderByName() : $orderby;
				}
			}
			$orderdirs = explode(',', JRequest::getVar('order_dir', ''));
			if ($orderdirs[0] == '')
			{
				$orderdirs = json_decode($table->order_dir, true);
			}
			$els = $this->getElements('filtername');
			if (!empty($orderbys))
			{
				$bits = array();
				$o = 0;
				foreach ($orderbys as $orderbyRaw)
				{
					$dir = JArrayHelper::getValue($orderdirs, $o, 'desc');
					if ($orderbyRaw !== '')
					{
						// $$$ hugh - getOrderByName can return a CONCAT, ie join element ...
						// $$$ hugh - OK, we need to test for this twice, because older elements
						// which get converted form names to ids above have already been run through
						// getOrderByName().  So first check here ...
						if (!JString::stristr($orderbyRaw, 'CONCAT('))
						{
							$orderbyRaw = FabrikString::safeColName($orderbyRaw);
							if (array_key_exists($orderbyRaw, $els))
							{
								$field = $els[$orderbyRaw]->getOrderByName();
								// $$$ hugh - ... second check for CONCAT, see comment above
								// $$$ @TODO why don't we just embed this logic in safeColName(), so
								// it recognizes a CONCAT and treats it accordingly?
								if (!JString::stristr($field, 'CONCAT('))
								{
									$field = FabrikString::safeColName($field);
								}
								$bits[] = " $field $dir";
								$this->orderEls[] = $field;
								$this->orderDirs[] = $dir;
							}
							else
							{
								if (strstr($orderbyRaw, '_raw`'))
								{
									$orderbyRaw = FabrikString::safeColNameToArrayKey($orderbyRaw);
								}
								$bits[] = " $orderbyRaw $dir";
								$this->orderEls[] = $orderbyRaw;
								$this->orderDirs[] = $dir;
							}
						}
						else
						{
							// If it was a CONCAT(), just add it with no other checks or processing
							$bits[] = " $orderbyRaw $dir";
							$this->orderEls[] = $orderbyRaw;
							$this->orderDirs[] = $dir;
						}
					}
					$o ++;
				}
				if (!empty($bits))
				{
					if (!$query)
					{
						$strOrder = "\n ORDER BY" . implode(',', $bits);
					}
					else
					{
						$query->order(implode(',', $bits));
					}
				}
			}
		}
		/* apply group ordering
		 * @TODO - explain something to hugh!  Why is this "group ordering"?  AFAICT, it's just a secondary
		* order by, isn't specific to the Group By feature in any way?  So why not just put this option in
		*/
		$groupOrderBy = $params->get('group_by_order');
		if ($groupOrderBy != '')
		{
			$groupOrderDir = $params->get('group_by_order_dir');
			$strOrder == '' ? $strOrder = "\n ORDER BY " : $strOrder .= ',';
			$orderby = strstr($groupOrderBy, '_raw`') ? FabrikString::safeColNameToArrayKey($groupOrderBy) : FabrikString::safeColName($groupOrderBy);
			if (!$query)
			{
				$strOrder .= $orderby . ' ' . $groupOrderDir;
			}
			else
			{
				$query->order($orderby . ' ' . $groupOrderDir);
			}
			$this->orderEls[] = $orderby;
			$this->orderDirs[] = $groupOrderDir;
		}
		$this->orderBy = $query === false ? $strOrder : $query;
		return $this->orderBy;
	}

	/**
	 * Should we order on multiple elements or one
	 *
	 * @since   3.0.7 (refractored from _buildQueryOrder())
	 *
	 * @return  bool
	 */

	public function singleOrdering()
	{
		$params = $this->getParams();
		if ($params->get('enable_single_sorting', 'default') == 'default')
		{
			// Use global
			$fbConfig = JComponentHelper::getParams('com_fabrik');
			$singleOrdering = $fbConfig->get('enable_single_sorting', false);
		}
		else
		{
			$singleOrdering = $params->get('enable_single_sorting', false);
		}
		return $singleOrdering;
	}

	/**
	 * Called when the table column order by is clicked
	 * store order options in session
	 *
	 * @return  void
	 */

	public function setOrderByAndDir()
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$session = JFactory::getSession();
		$postOrderBy = JRequest::getInt('orderby', '');
		$postOrderDir = JRequest::getVar('orderdir', '');
		$arOrderVals = array('asc', 'desc', '-');
		$id = $this->getRenderContext();
		if (in_array($postOrderDir, $arOrderVals))
		{
			$context = 'com_' . $package . '.list' . $id . '.order.' . $postOrderBy;
			$session->set($context, $postOrderDir);
		}
	}

	/**
	 * Get the part of the sql query that creates the joins
	 * used when building the table's data
	 *
	 * @param   mixed  $query  JQuery object or false
	 *
	 * @return  string	join sql
	 */

	public function _buildQueryJoin($query = false)
	{
		$db = FabrikWorker::getDbo();
		$ref = $query ? '1' : '0';
		if (isset($this->_joinsSQL[$ref]))
		{
			return $this->_joinsSQL[$ref];
		}
		$statements = array();
		$table = $this->getTable();
		$selectedTables[] = $table->db_table_name;
		$return = array();
		$joins = ($this->get('includeCddInJoin', true) === false) ? $this->getJoinsNoCdd() : $this->getJoins();
		$tableGroups = array();
		foreach ($joins as $join)
		{
			// Used to bypass user joins if the table connect isnt the Joomla connection
			if ((int) $join->canUse === 0)
			{
				continue;
			}
			if ($join->join_type == '')
			{
				$join->join_type = 'LEFT';
			}
			$sql = JString::strtoupper($join->join_type) . ' JOIN ' . $db->quoteName($join->table_join);
			$k = FabrikString::safeColName($join->keytable . '.' . $join->table_key);
			if ($join->table_join_alias == '')
			{
				$on = FabrikString::safeColName($join->table_join . '.' . $join->table_join_key);
				$sql .= ' ON ' . $on . ' = ' . $k;
			}
			else
			{
				$on = FabrikString::safeColName($join->table_join_alias . '.' . $join->table_join_key);
				$sql .= ' AS ' . FabrikString::safeColName($join->table_join_alias) . ' ON ' . $on . ' = ' . $k . " \n";
			}
			/* Try to order join statements to ensure that you are selecting from tables that have
			 * already been included (either via a previous join statement or the table select statement)
			*/
			if (in_array($join->keytable, $selectedTables))
			{
				$return[] = $sql;
				$selectedTables[] = $join->table_join;
			}
			else
			{
				// Didn't find anything so defer it till later

				/* $statements[$join->keytable] = $sql;
				 * $$$rob - sometimes the keytable is the same for 2 deferred joins
				* in this case the first join is incorrectly overwritten in the $statements array
				* keying on join->id should solve this
				*/
				$statements[$join->id] = array($join->keytable, $sql);
			}

			// Go through the deferred join statements and see if their table has now been selected
			foreach ($statements as $joinid => $ar)
			{
				$t = $ar[0];
				$s = $ar[1];
				if (in_array($t, $selectedTables))
				{
					if (!in_array($s, $return))
					{
						// $$$rob test to avoid duplicate join queries
						$return[] = $s;
						unset($statements[$t]);
					}
				}
			}
		}
		// $$$rob test for bug #376
		foreach ($statements as $joinid => $ar)
		{
			$s = $ar[1];
			if (!in_array($s, $return))
			{
				$return[] = $s;
			}
		}
		// 3.0 not really tested
		if ($query !== false)
		{
			foreach ($return as $r)
			{
				$words = explode(' ', trim($r));
				$type = array_shift($words);
				$statement = str_replace('JOIN', '', implode(' ', $words));
				$query->join($type, $statement);
			}
			return $query;
		}
		else
		{
			$return = implode(' ', $return);
			$this->_joinsSQL[$ref] = $return;
		}
		return $query == false ? $return : $query;
	}

	/**
	 * Build query prefilter where part
	 *
	 * @param   object  $element  model
	 *
	 * @return  string
	 */

	public function _buildQueryPrefilterWhere($element)
	{
		$elementName = FabrikString::safeColName($element->getFullName(false, false, false));
		$filters = $this->getFilterArray();
		$keys = array_keys($filters);
		$vkeys = array_keys(JArrayHelper::getValue($filters, 'value', array()));
		foreach ($vkeys as $i)
		{
			if ($filters['search_type'][$i] != 'prefilter' || $filters['key'][$i] != $elementName)
			{
				foreach ($keys as $key)
				{
					unset($filters[$key][$i]);
				}
			}
		}
		list($sqlNoFilter, $sql) = $this->_filtersToSQL($filters);
		$where = str_replace('WHERE', '', $sql);
		if ($where != '')
		{
			$where = ' AND ' . $where;
		}
		return $where;
	}

	/**
	 * Get the part of the main query that provides a group by statement
	 * only added by 'count' element plug-in at the moment
	 *
	 * @param   mixed  $query  false to return a mySQL string, JQuery object to append group statement to.
	 *
	 * @return  mixed  string if $query false, else JQuery object
	 */

	function _buildQueryGroupBy($query = false)
	{
		$groups = $this->getFormModel()->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$res = $elementModel->getGroupByQuery();
				if ($res != '')
				{
					$this->_pluginQueryGroupBy[] = $res;
				}
			}
		}
		if (!empty($this->_pluginQueryGroupBy))
		{
			return ' GROUP BY ' . implode(', ', $this->_pluginQueryGroupBy);
		}
		return '';
	}

	/**
	 * Get the part of the sql query that relates to the where statement
	 *
	 * @param   bool  $incFilters  if true the SQL contains
	 * any filters if false only contains prefilter sql
	 * @param   bool  $query       start the statement with 'where' (true is for j1.5 way of making queries, false for j1.6+)
	 *
	 * @return  mixed	string if $query false, else JQuery object
	 */

	public function _buildQueryWhere($incFilters = true, $query = false)
	{
		$sig = !$query ? 'string' : 'query';
		$db = FabrikWorker::getDbo();
		if (isset($this->_whereSQL[$sig]))
		{
			return $this->_whereSQL[$sig][$incFilters];
		}
		$filters = $this->getFilterArray();
		$params = $this->getParams();

		/* $$$ hugh - added option to 'require filtering', so if no filters specified
		 * we return an empty table.  Only do this where $inFilters is set, so we're only doing this
		* on the main row count and data fetch, and things like
		* filter dropdowns still get built.
		*/

		if ($incFilters && !$this->gotAllRequiredFilters())
		{
			// $this->emptyMsg = JText::_('COM_FABRIK_SELECT_AT_LEAST_ONE_FILTER');
			if (!$query)
			{
				return 'WHERE 1 = -1 ';
			}
			else
			{
				$query->where('1 = -1');
				return $query;
			}
		}
		$groups = $this->getFormModel()->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$elementModel->appendTableWhere($this->_pluginQueryWhere);
			}
		}
		if (empty($filters))
		{
			// $$$ hugh - testing hack for plugins to add WHERE clauses
			if (!empty($this->_pluginQueryWhere))
			{
				if (!$query)
				{
					return 'WHERE ' . implode(' AND ', $this->_pluginQueryWhere);
				}
				else
				{
					$query->where(implode(' AND ', $this->_pluginQueryWhere));
					return $query;
				}
			}
			else
			{
				return $query ? $query : '';
			}
		}
		$addWhere = $query == false ? true : false;
		list($sqlNoFilter, $sql) = $this->_filtersToSQL($filters, $addWhere);
		$this->_whereSQL[$sig] = array('0' => $sqlNoFilter, '1' => $sql);
		if (!$query)
		{
			return $this->_whereSQL[$sig][$incFilters];
		}
		else
		{
			if (!empty($this->_whereSQL[$sig][$incFilters]))
			{
				$query->where($this->_whereSQL[$sig][$incFilters]);
			}
			return $query;
		}
	}

	/**
	 * Used by _buildWhereQuery and buildQueryPrefilterWhere
	 * takes a filter array and returns the SQL
	 *
	 * @param   array  &$filters        filters
	 * @param   bool   $startWithWhere  start the statement with 'where' (true is for j1.5 way of making queries, false for j1.6+)
	 *
	 * @return  array	nofilter, filter sql
	 */

	private function _filtersToSQL(&$filters, $startWithWhere = true)
	{
		$prefilters = $this->_groupFilterSQL($filters, 'prefilter');
		$postfilers = $this->_groupFilterSQL($filters);
		if (!empty($prefilters) && !empty($postfilers))
		{
			array_unshift($postfilers, 'AND');
		}
		$sql = array_merge($prefilters, $postfilers);
		$pluginQueryWhere = trim(implode(' AND ', $this->_pluginQueryWhere));
		if ($pluginQueryWhere !== '')
		{
			$pluginQueryWhere = '(' . $pluginQueryWhere . ')';
			if (!empty($sql))
			{
				$sql[] = ' AND ';
			}
			if (!empty($prefilters))
			{
				$prefilters[] = ' AND ';
			}
			$sql[] = $pluginQueryWhere;
			$prefilters[] = $pluginQueryWhere;
		}
		// Add in the where to the query
		if (!empty($sql) && $startWithWhere)
		{
			array_unshift($sql, 'WHERE');
		}
		if (!empty($prefilters) && $startWithWhere)
		{
			array_unshift($prefilters, 'WHERE');
		}
		$sql = implode($sql, ' ');
		$prefilters = implode($prefilters, ' ');
		return array($prefilters, $sql);
	}

	/**
	 * Parse the filter array and return an array of words that will make up part of the filter query
	 *
	 * @param   array   &$filters  filters
	 * @param   string  $type      * = filters, 'prefilter' = get prefilter only
	 *
	 * @return  array	words making up sql query.
	 */

	private function _groupFilterSQL(&$filters, $type = '*')
	{
		$groupedCount = 0;
		$ingroup = false;
		$sql = array();

		// $$$ rob keys may no longer be in asc order as we may have filtered out some in buildQueryPrefilterWhere()
		$vkeys = array_keys(JArrayHelper::getValue($filters, 'key', array()));
		$last_i = false;
		while (list($vkey, $i) = each($vkeys))
		{
			// $$$rob - prefilter with element that is not published so ignore
			$condition = JString::strtoupper(JArrayHelper::getValue($filters['condition'], $i, ''));
			if (JArrayHelper::getValue($filters['sqlCond'], $i, '') == '' && ($condition != 'IS NULL' && $condition != 'IS NOT NULL'))
			{
				$last_i = $i;
				continue;
			}
			if ($filters['search_type'][$i] == 'prefilter' && $type == '*')
			{
				$last_i = $i;
				continue;
			}
			if ($filters['search_type'][$i] != 'prefilter' && $type == 'prefilter')
			{
				$last_i = $i;
				continue;
			}
			$n = current($vkeys);
			if ($n === false)
			{
				// End of array
				$n = -1;
			}
			$gstart = '';
			$gend = '';
			if ($condition == 'IS NULL' || $condition == 'IS NOT NULL')
			{
				$filters['origvalue'][$i] = 'this is ignoerd i hope';
			}
			// $$$ rob added $filters['sqlCond'][$i] test so that you can test for an empty string
			if ($filters['origvalue'][$i] != '' || $filters['sqlCond'][$i] != '')
			{
				if (array_key_exists($n, $filters['grouped_to_previous']))
				{
					if ($filters['grouped_to_previous'][$n] == 1)
					{
						if (!$ingroup)
						{
							// Search all filter after a prefilter - alter 'join' value to 'AND'
							$gstart = '(';
							$groupedCount++;
						}
						$ingroup = true;
					}
					else
					{
						if ($ingroup)
						{
							$gend = ')';
							$groupedCount--;
							$ingroup = false;
						}
					}
				}
				else
				{
					if ($ingroup)
					{
						$gend = ')';
						$groupedCount--;
						$ingroup = false;
					}
				}
				$glue = JArrayHelper::getValue($filters['join'], $i, 'AND');
				$sql[] = empty($sql) ? $gstart : $glue . ' ' . $gstart;
				$sql[] = $filters['sqlCond'][$i] . $gend;
			}
			$last_i = $i;
		}
		// $$$rob ensure opening and closing parathethis for prefilters are equal
		// Seems to occur if you have 3 prefilters with 2nd = grouped/AND and 3rd grouped/OR

		if ($groupedCount > 0)
		{
			$sql[] = str_pad('', (int) $groupedCount, ")");
		}
		// Wrap in brackets
		if (!empty($sql))
		{
			array_unshift($sql, '(');
			$sql[] = ')';
		}
		return $sql;
	}

	/**
	 * Get a list of the tables columns' order by field names
	 *
	 * @deprecated - dont think its used
	 *
	 * @return  array	order by names
	 */

	public function getOrderByFields()
	{
		if (is_null($this->orderByFields))
		{
			$this->orderByFields = array();
		}
		$form = $this->getFormModel();
		$groups = $form->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$this->orderByFields[] = $elementModel->getOrderByName();
			}
		}
		return $this->orderByFields;
	}

	/**
	 * Get the elements that are included in the search all query
	 *
	 * @return  array  search all fields
	 */

	public function getSearchAllFields()
	{
		$profiler = JProfiler::getInstance('Application');
		if (isset($this->searchAllAsFields))
		{
			return $this->searchAllAsFields;
		}
		$searchAllFields = array();
		$this->searchAllAsFields = array();

		$form = $this->getFormModel();
		$table = $this->getTable();
		$aJoinObjs = $this->getJoins();
		$groups = $form->getGroupsHiarachy();
		$gkeys = array_keys($groups);
		$opts = array('inc_raw' => false);
		$mode = $this->getParams()->get('search-mode-advanced');
		foreach ($gkeys as $x)
		{
			$groupModel = $groups[$x];
			$elementModels = $groupModel->getPublishedElements();
			for ($ek = 0; $ek < count($elementModels); $ek++)
			{
				$elementModel = $elementModels[$ek];
				if ($elementModel->includeInSearchAll($mode))
				{
					// Boolean search doesnt seem possible on encrypted fields.
					$p = $elementModel->getParams();
					$o = $p->get('encrypt');
					$p->set('encrypt', false);
					$elementModel->getAsField_html($this->searchAllAsFields, $searchAllFields, $opts);
					$p->set('encrypt', $o);
				}
			}
		}

		$db = FabrikWorker::getDbo();

		// If the group by element isnt in the fields (IE its not published) add it (otherwise group by wont work)
		$longGroupBy = $db->quoteName($this->getGroupBy());

		if (!in_array($longGroupBy, $searchAllFields) && trim($table->group_by) != '')
		{
			$this->searchAllAsFields[] = FabrikString::safeColName($this->getGroupBy()) . ' AS ' . $longGroupBy;
			$searchAllFields[] = $longGroupBy;
		}

		for ($x = 0; $x < count($this->searchAllAsFields); $x++)
		{
			$match = ' AS ' . $searchAllFields[$x];
			if (array_key_exists($x, $this->searchAllAsFields))
			{
				$this->searchAllAsFields[$x] = trim(str_replace($match, '', $this->searchAllAsFields[$x]));
			}
		}
		$this->searchAllAsFields = array_unique($this->searchAllAsFields);
		return $this->searchAllAsFields;
	}

	/**
	 * Get the part of the table sql statement that selects which fields to load
	 *
	 * @param   string  $mode  list/form - effects which elements are selected
	 *
	 * @return  array	field names to select in getelement data sql query
	 */

	function &getAsFields($mode = 'list')
	{
		$profiler = JProfiler::getInstance('Application');
		if (isset($this->asfields))
		{
			return $this->asfields;
		}
		$this->fields = array();
		$this->asfields = array();
		$db = FabrikWorker::getDbo(true);
		$form = $this->getFormModel();
		$table = $this->getTable();
		$aJoinObjs = $this->getJoins();
		$this->_temp_db_key_addded = false;
		$groups = $form->getGroupsHiarachy();
		$gkeys = array_keys($groups);
		foreach ($gkeys as $x)
		{
			$groupModel = $groups[$x];
			if ($groupModel->canView() !== false)
			{
				$elementModels = $mode === 'list' ? $groupModel->getListQueryElements() : $groupModel->getPublishedElements();
				foreach ($elementModels as $elementModel)
				{
					$method = 'getAsField_' . $this->outPutFormat;
					if (!method_exists($elementModel, $method))
					{
						$method = 'getAsField_html';
					}
					$elementModel->$method($this->asfields, $this->fields);
				}
			}
		}
		/*temporaraily add in the db key so that the edit links work, must remove it before final return
		 of getData();
		*/
		JDEBUG ? $profiler->mark('getAsFields: starting to test if a view') : null;
		if (!$this->isView())
		{
			if (!$this->_temp_db_key_addded && $table->db_primary_key != '')
			{
				$str = FabrikString::safeColName($table->db_primary_key) . ' AS ' . FabrikString::safeColNameToArrayKey($table->db_primary_key);
				$this->fields[] = $db->quoteName(FabrikString::safeColNameToArrayKey($table->db_primary_key));
			}
		}
		JDEBUG ? $profiler->mark('getAsFields: end of view test') : null;

		// For raw data in packages
		if ($this->outPutFormat == 'raw')
		{
			$str = FabrikString::safeColName($table->db_primary_key) . ' AS __pk_val';
			$this->fields[] = $str;
		}

		$this->_group_by_added = false;

		// If the group by element isnt in the fields (IE its not published) add it (otherwise group by wont work)
		$longGroupBy = $this->getGroupByName();
		if (!in_array($longGroupBy, $this->fields) && trim($longGroupBy) != '')
		{
			$this->asfields[] = FabrikString::safeColName($longGroupBy) . ' AS ' . $longGroupBy;
			$this->fields = $longGroupBy;
			$this->_group_by_added = true;
		}
		return $this->asfields;
	}

	/**
	 * Get the group by element regardless of wheter it was stored as id or string
	 *
	 * @since 3.0.7
	 *
	 * @return  plgFabrik_Element
	 */
	protected function getGroupByElement()
	{
		$app = JFactory::getApplication();
		$item = $this->getTable();
		$formModel = $this->getFormModel();
		$groupBy = $app->input->get('group_by', $item->group_by, 'string');
		return $formModel->getElement($groupBy, true);
	}

	/**
	 * Get group by field name
	 *
	 * @since 3.0.7
	 *
	 * @return mixed false or name
	 */

	protected function getGroupByName()
	{
		$db = $this->getDb();
		$elementModel = $this->getGroupByElement();
		if (!$elementModel)
		{
			return false;
		}
		$groupBy = $elementModel->getFullName(false, true, false);
		return $db->quoteName(FabrikString::safeColNameToArrayKey($groupBy));
	}

	/**
	 * Checks if the params object has been created and if not creates and returns it
	 *
	 * @return  object	params
	 */

	public function getParams()
	{
		$item = $this->getTable();
		if (!isset($this->_params))
		{
			$this->_params = new JRegistry($item->params);
		}
		return $this->_params;
	}

	/**
	 * Method to set the list id
	 *
	 * @param   int  $id  list ID
	 *
	 * @return  void
	 */

	public function setId($id)
	{
		$this->setState('list.id', $id);
		$this->renderContext = '';

		// $$$ rob not sure why but we need this getState() here when assinging id from admin view
		$this->setRenderContext($id);
		$this->getState();
	}

	/**
	 * Get the list id
	 *
	 * @return  int  list id
	 */

	public function getId()
	{
		return $this->getState('list.id');
	}

	/**
	 * Get the table object for the models _id
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return   object	table
	 */

	public function getTable($name = '', $prefix = 'Table', $options = array())
	{
		if ($name === true)
		{
			$this->clearTable();
		}
		if (!isset($this->_table) || !is_object($this->_table))
		{
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
			$this->_table = FabTable::getInstance('List', 'FabrikTable');
			$id = $this->getId();
			if ($id !== 0)
			{
				$this->_table->load($id);
			}
			if (trim($this->_table->db_primary_key) !== '')
			{
				$this->_table->db_primary_key = FabrikString::safeColName($this->_table->db_primary_key);
			}
		}
		return $this->_table;
	}

	/**
	 * Set the table object
	 *
	 * @param   object  $table  db row
	 *
	 * @return   void
	 */

	public function setTable($table)
	{
		$this->_table = $table;
	}

	/**
	 * unset the table object
	 *
	 * @return void
	 */

	public function clearTable()
	{
		unset($this->_table);
	}

	/**
	 * Load the database object associated with the list
	 *
	 * @return  object	database
	 */

	public function &getDb()
	{
		return FabrikWorker::getConnection($this->getTable())->getDb();
	}

	/**
	 * Get the lists connection object
	 * sets $this->connection to the lists connection
	 *
	 * @deprecated since 3.0b use FabrikWorker::getConnection() instead
	 *
	 * @return  object	connection
	 */

	public function &getConnection()
	{
		$this->_oConn = FabrikWorker::getConnection($this->getTable());
		return $this->_oConn;
	}

	/**
	 *Is the table published
	 * Dates are stored as UTC so we can compare them against a date with no offset applied
	 *
	 * @return  bool	published state
	 */

	public function canPublish()
	{
		$item = $this->getTable();
		$db = FabrikWorker::getDbo();
		$nullDate = $db->getNullDate();
		$publishup = JFactory::getDate($item->publish_up);
		$publishup = $publishup->toUnix();
		$publishdown = JFactory::getDate($item->publish_down);
		$publishdown = $publishdown->toUnix();
		$jnow = JFactory::getDate();
		$now = $jnow->toUnix();
		if ($item->published == '1')
		{
			if ($now >= $publishup || $item->publish_up == '' || $item->publish_up == $nullDate)
			{
				if ($now <= $publishdown || $item->publish_down == '' || $item->publish_down == $nullDate)
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Access control to determine if the current user has rights to drop data
	 * from the table
	 *
	 * @return  bool	yes/no
	 */

	public function canEmpty()
	{
		$params = $this->getParams();
		if (!is_object($this->_access) || !array_key_exists('allow_drop', $this->_access))
		{
			$groups = JFactory::getUser()->authorisedLevels();
			$this->_access->allow_drop = in_array($this->getParams()->get('allow_drop'), $groups);
		}
		return $this->_access->allow_drop;
	}

	/**
	 * Check if the user can view the detailed records
	 *
	 * @return  bool
	 */

	public function canViewDetails()
	{
		$params = $this->getParams();
		if (!is_object($this->_access) || !array_key_exists('viewdetails', $this->_access))
		{
			$groups = JFactory::getUser()->authorisedLevels();
			$this->_access->viewdetails = in_array($this->getParams()->get('allow_view_details'), $groups);
		}
		return $this->_access->viewdetails;
	}

	/**
	 * Checks user access for editing records
	 *
	 * @param   object  $row  of data currently active
	 *
	 * @return  bool	access allowed
	 */

	public function canEdit($row = null)
	{
		$params = $this->getParams();

		/**
		 * $$$ hugh - FIXME - we really need to split out a onCanEditRow method, rather than overloading
		 * onCanEdit for both table and per-row contexts.  At the moment, we calling per-row plugins with
		 * null $row when canEdit() is called in a table context.
		 */

		/**
		* Find out what any plugins have to say
		*/

		$pluginCanEdit = FabrikWorker::getPluginManager()->runPlugins('onCanEdit', $this, 'list', $row);
		$pluginCanEdit = !empty($pluginCanEdit) && !in_array(false, $pluginCanEdit);

		/*
		 * At least one plugin ran, and none of them said No, so that's it, let them override, don't even
		 * bother findin out what canUserDo and regular ACL's say.
		 */
		if ($pluginCanEdit)
		{
			return true;
		}

		$canUserDo = $this->canUserDo($row, 'allow_edit_details2');
		if ($canUserDo !== -1)
		{
			// canUserDo() expressed a boolean preference, so use that
			return $canUserDo;
		}
		if (!is_object($this->_access) || !array_key_exists('edit', $this->_access))
		{
			$user = JFactory::getUser();
			$groups = $user->authorisedLevels();
			$this->_access->edit = in_array($this->getParams()->get('allow_edit_details'), $groups);
		}
		// Plugins didn't override, canuserDo() didn't express a preference, so return standard ACL
		return $this->_access->edit;
	}

	/**
	 * Checks if any one row is editable = used to get the correct headings
	 *
	 * @return  bool
	 */

	protected function canEditARow()
	{
		$data = $this->getData();
		foreach ($data as $rows)
		{
			foreach ($rows as $row)
			{
				if ($this->canEdit($row))
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Access control function for determining if the user can perform
	 * a designated function on a specific row
	 *
	 * @param   object  $row  data
	 * @param   string  $col  access control setting to compare against
	 *
	 * @return  mixed	- if ACL setting defined here return bool, otherwise return -1 to contiune with default acl setting
	 */

	protected function canUserDo($row, $col)
	{
		$params = $this->getParams();
		return FabrikWorker::canUserDo($params, $row, $col);
	}

	/**
	 * Checks user access for deleting records.
	 *
	 * @param   object  $row  of data currently active
	 *
	 * @return  bool	access allowed
	 */

	public function canDelete($row = null)
	{
		/**
		 * Find out if any plugins deny delete.  We then allow a plugin to override with 'false' if
		 * if useDo or group ACL allows edit.  But we don't allow plugin to allow, if userDo or group ACL
		 * deny access.
		 */
		$pluginCanDelete = FabrikWorker::getPluginManager()->runPlugins('onCanDelete', $this, 'list', $row);
		$pluginCanDelete = !in_array(false, $pluginCanDelete);
		$canUserDo = $this->canUserDo($row, 'allow_delete2');
		if ($canUserDo !== -1)
		{
			// If userDo allows delete, let plugin override
			return $canUserDo ? $pluginCanDelete : $canUserDo;
		}
		if (!is_object($this->_access) || !array_key_exists('delete', $this->_access))
		{
			$groups = JFactory::getUser()->authorisedLevels();
			$this->_access->delete = in_array($this->getParams()->get('allow_delete'), $groups);
		}
		// If group access allows delete, then let plugin override
		return $this->_access->delete ? $pluginCanDelete : $this->_access->delete;
	}

	/**
	 * Determine if any record can be deleted - used to see if we include the
	 * delete button in the list view
	 *
	 * @return  bool
	 */

	public function deletePossible()
	{
		$data = $this->getData();
		foreach ($data as $rows)
		{
			foreach ($rows as $row)
			{
				if ($this->canDelete($row))
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Checks user access for importing csv
	 *
	 * @return  bool  access allowed
	 */

	public function canCSVImport()
	{
		if (!is_object($this->_access) || !array_key_exists('csvimport', $this->_access))
		{
			$groups = JFactory::getUser()->authorisedLevels();
			$this->_access->csvimport = in_array($this->getParams()->get('csv_import_frontend'), $groups);
		}
		return $this->_access->csvimport;
	}

	/**
	 * Checks user access for exporting csv
	 *
	 * @return  bool  access allowed
	 */

	public function canCSVExport()
	{
		if (!is_object($this->_access) || !array_key_exists('csvexport', $this->_access))
		{
			$groups = JFactory::getUser()->authorisedLevels();
			$this->_access->csvexport = in_array($this->getParams()->get('csv_export_frontend'), $groups);
		}
		return $this->_access->csvexport;
	}

	/**
	 * Checks user access for front end group by
	 *
	 * @return  bool  access allowed
	 */

	public function canGroupBy()
	{
		if (!is_object($this->_access) || !array_key_exists('groupby', $this->_access))
		{
			$groups = JFactory::getUser()->authorisedLevels();
			$this->_access->groupby = in_array($this->getParams()->get('group_by_access'), $groups);
		}
		return $this->_access->groupby;
	}

	/**
	 * Checks user access for adding records
	 *
	 * @return  bool  access allowed
	 */

	function canAdd()
	{
		$params = $this->getParams();
		if (!is_object($this->_access) || !array_key_exists('add', $this->_access))
		{
			$groups = JFactory::getUser()->authorisedLevels();
			$this->_access->add = in_array($this->getParams()->get('allow_add'), $groups);
		}
		return $this->_access->add;
	}

	/**
	 * Check use can view the list
	 *
	 * @return  bool  can view or not
	 */

	public function canView()
	{
		if (!is_object($this->_access) || !array_key_exists('view', $this->_access))
		{
			$groups = JFactory::getUser()->authorisedLevels();
			$this->_access->view = in_array($this->getTable()->access, $groups);
		}
		return $this->_access->view;
	}

	/**
	 * Load the table from the form_id value
	 *
	 * @param   int  $formId  (jos_fabrik_forms.id)
	 *
	 * @return  object	table row
	 */

	public function loadFromFormId($formId)
	{
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/table');
		$row = FabTable::getInstance('List', 'FabrikTable');
		$row->load(array('form_id' => $formId));
		$this->_table = $row;
		$this->setId($row->id);
		$this->setState('list.id', $row->id);
		return $row;
	}

	/**
	 * Like getJoins() but exclude cascading dropdown joins
	 * seems to be needed when calculating related table's record counts.
	 * This is called from within _buildQueryJoin()
	 * and fired if this is done:
	 * $listModel->set('includeCddInJoin', false);
	 * as in tableModel::getRecordCounts()
	 *
	 * @return  array  join objects (table rows - not table objects or models)
	 */

	protected function getJoinsNoCdd()
	{
		if (!isset($this->_joinsNoCdd))
		{
			$form = $this->getFormModel();
			$form->getGroupsHiarachy();
			$ignore = array('plgFabrik_ElementCascadingdropdown');
			$ids = $form->getElementIds($ignore);
			$db = FabrikWorker::getDbo(true);
			$id = (int) $this->getId();
			$query = $db->getQuery(true);
			$query->select('*')->from('#__{package}_joins')->where('list_id = ' . $id, 'OR');
			if (!empty($ids))
			{
				$query->where('element_id IN (' . implode(", ", $ids) . ')');
			}
			/* maybe we will have to order by element_id asc to ensure that table joins are loaded
			 * before element joins (if an element join is in a table join then its 'join_from_table' key needs to be updated
			 		*/
			$query->order('id');
			$db->setQuery($query);
			$this->_joinsNoCdd = $db->loadObjectList();
			if ($db->getErrorNum())
			{
				JError::raiseError(500, $db->stderr());
			}
			$this->_makeJoinAliases($this->_joinsNoCdd);
		}
		return $this->_joinsNoCdd;
	}

	/**
	 * Get joins
	 *
	 * @return array join objects (table rows - not table objects or models)
	 */

	public function &getJoins()
	{
		if (!isset($this->_aJoins))
		{
			$form = $this->getFormModel();
			$form->getGroupsHiarachy();
			$ids = $form->getElementIds(array(), array('includePublised' => false));
			$db = FabrikWorker::getDbo(true);
			$id = (int) $this->getId();
			$query = $db->getQuery(true);
			$query->select('*')->from('#__{package}_joins')->where('(element_id = 0 AND list_id = ' . $id . ')', 'OR');
			if (!empty($ids))
			{
				$query->where('element_id IN ( ' . implode(', ', $ids) . ')');
			}
			/* maybe we will have to order by element_id asc to ensure that table joins are loaded
			 * before element joins (if an element join is in a table join then its 'join_from_table' key needs to be updated
			 		*/
			$query->order('id');
			$db->setQuery($query);
			$this->_aJoins = $db->loadObjectList();
			if ($db->getErrorNum())
			{
				JError::raiseError(500, $db->stderr());
			}
			$this->_makeJoinAliases($this->_aJoins);
			foreach ($this->_aJoins as &$join)
			{
				if (!isset($join->_params))
				{
					$join->_params = new JRegistry($join->params);
					$this->setJoinPk($join);
				}
			}
		}
		return $this->_aJoins;
	}

	/**
	 * Merged data queries need to know the joined tables primary key value
	 *
	 * @param   object  &$join  join
	 *
	 * @since	3.0.6
	 *
	 * @return  void
	 */

	protected function setJoinPk(&$join)
	{
		$pk = $join->_params->get('pk');
		if (!isset($pk))
		{
			$fabrikDb = $this->getDb();
			$db = FabrikWorker::getDbo(true);
			$query = $db->getQuery(true);
			$pk = $this->getPrimaryKeyAndExtra($join->table_join);

			$pks = $join->table_join;
			$pks .= '.' . $pk[0]['colname'];
			$join->_params->set('pk', $fabrikDb->quoteName($pks));
			$query->update('#__{package}_joins')->set('params = ' . $db->quote((string) $join->_params))->where('id = ' . (int) $join->id);
			$db->setQuery($query);
			$db->query();
			$join->_params = new JRegistry($join->params);
		}
	}

	/**
	 * As you may be joining to multiple versions of the same db table we need
	 * to set the various database name alaises that our SQL query will use
	 *
	 * @param   array  &$joins  joins
	 *
	 * @return  void
	 */
	protected function _makeJoinAliases(&$joins)
	{
		$app = JFactory::getApplication();
		$prefix = $app->getCfg('dbprefix');
		$table = $this->getTable();
		$db = FabrikWorker::getDbo(true);
		$aliases = array($table->db_table_name);
		$tableGroups = array();

		// Build up the alias and $tableGroups array first
		foreach ($joins as &$join)
		{
			$join->canUse = true;
			if ($join->table_join == '#__users' || $join->table_join == $prefix . 'users')
			{
				$conf = JFactory::getConfig();
				$thisCn = $this->getConnection()->getConnection();
				if (!($thisCn->host == $conf->get('host') && $thisCn->database == $conf->get('db')))
				{
					/* $$$ hugh - changed this to pitch an error and bang out, otherwise if we just set canUse to false, our getData query
					 * is just going to blow up, with no useful warning msg.
					* This is basically a bandaid for corner case where user has (say) host name in J!'s config, and IP address in
					* our connection details, or vice versa, which is not uncommon for 'locahost' setups,
					* so at least I'll know what the problem is when they post in the forums!
					*/

					// The user element relies on canUse returning false, when used in a non-default connection so we can't raise an error so commenting out
					// JError::raiseError(500, JText::_('COM_FABRIK_ERR_JOIN_TO_OTHER_DB'));
					$join->canUse = false;
				}
			}
			// $$$ rob = check for repeat elements In table view we dont need to add the join
			// as the element data is concatenated into one row. see elementModel::getAsField_html()
			$opts = json_decode($join->params);
			if (isset($opts->type) && $opts->type == 'repeatElement')
			{
				// If ($join->list_id != 0 && $join->element_id != 0) {
				$join->canUse = false;
			}
			$tablejoin = str_replace('#__', $prefix, $join->table_join);
			if (in_array($tablejoin, $aliases))
			{
				$base = $tablejoin;
				$a = $base;
				$c = 0;
				while (in_array($a, $aliases))
				{
					$a = $base . '_' . $c;
					$c++;
				}
				$join->table_join_alias = $a;
			}
			else
			{
				$join->table_join_alias = $tablejoin;
			}
			$aliases[] = str_replace('#__', $prefix, $join->table_join_alias);
			if (!array_key_exists($join->group_id, $tableGroups))
			{
				if ($join->element_id == 0)
				{
					$tableGroups[$join->group_id] = $join->table_join_alias;
				}
			}
		}
		foreach ($joins as &$join)
		{
			// If they are element joins add in this tables name as the calling joining table.
			if ($join->join_from_table == '')
			{
				$join->join_from_table = $table->db_table_name;
			}

			/*
			 * Test case:
			* you have a table that joins to a 2nd table
			* in that 2nd table there is a database join element
			* that 2nd elements key needs to point to the 2nd tables name and not the first
			*
			* e.g. when you want to create a n-n relationship
			*
			* events -> (table join) events_artists -> (element join) artist
			*/

			$join->keytable = $join->join_from_table;
			if (!array_key_exists($join->group_id, $tableGroups))
			{

			}
			else
			{
				if ($join->element_id != 0)
				{
					$join->keytable = $tableGroups[$join->group_id];
					$join->join_from_table = $join->keytable;
				}
			}
		}
		FabrikHelperHTML::debug($joins, 'joins');
	}

	/**
	 * Gets the field names for the given table
	 * $$$ hugh - copies this to backend model, so remember to modify that as well, if
	 * you make changes to this one.  Better yet, make it a Helper func that requires
	 * the $tbl arg, as that's the only thing that makes it list model specific.
	 *
	 * @param   string  $tbl  table name
	 * @param   string  $key  field to key return array on
	 *
	 * @return  array	table fields
	 */

	public function getDBFields($tbl = null, $key = null)
	{
		if (is_null($tbl))
		{
			$table = $this->getTable();
			$tbl = $table->db_table_name;
		}
		if ($tbl == '')
		{
			return array();
		}
		$sig = $tbl . $key;
		$tbl = FabrikString::safeColName($tbl);
		if (!isset($this->_dbFields[$sig]))
		{
			$db = $this->getDb();
			$tbl = FabrikString::safeColName($tbl);
			$db->setQuery("DESCRIBE " . $tbl);
			$this->_dbFields[$sig] = $db->loadObjectList($key);
			if ($db->getErrorNum())
			{
				JError::raiseWarning(500, $db->getErrorMsg());
				$this->_dbFields[$sig] = array();
			}
			foreach ($this->_dbFields[$sig] as &$row)
			{
				/**
				 * Boil the type down to just the base type, so "INT(11) UNSIGNED" becomes just "INT"
				 * I'm sure there's other cases than just UNSIGNED I need to deal with, but for now that's
				 * what I most care about, as this stuff is being written handle being more specific about
				 * the elements the list PK can be selected from.
				 */
				$row->BaseType = strtoupper( preg_replace('#(\(\d+\))$#', '', $row->Type) );
				$row->BaseType = preg_replace('#(\s+SIGNED|\s+UNSIGNED)#', '', $row->BaseType);
			}
		}
		return $this->_dbFields[$sig];
	}

	/**
	 * Called at the end of saving an element
	 * if a new element it will run the sql to add to field,
	 * if existing element and name changed will create query to be used later
	 *
	 * @param   object  &$elementModel  element model
	 * @param   string  $origColName    original column name
	 *
	 * @return  array($update, $q, $oldName, $newdesc, $origDesc, $dropKey)
	 */

	public function shouldUpdateElement(&$elementModel, $origColName = null)
	{

		$db = FabrikWorker::getDbo();
		$return = array(false, '', '', '', '', false);
		$element = $elementModel->getElement();
		$pluginManager = FabrikWorker::getPluginManager();
		$basePlugIn = $pluginManager->getPlugIn($element->plugin, 'element');
		$fbConfig = JComponentHelper::getParams('com_fabrik');
		$fabrikDb = $this->getDb();
		$group = $elementModel->getGroup();
		$dropKey = false;
		/*$$$ rob - replaced this with getting the table from the group as if we moved the element
		 *from one group to another $this->getTable gives you the old group's table, where as we want
		* the new group's table
		*/
		$table = $group->getlistModel()->getTable();

		// $$$ hugh - if this is a table-less form ... not much point going any
		// further 'cos things will go BANG
		if (empty($table->id))
		{
			return $return;
		}
		if ($this->isView())
		{
			return $return;
		}
		if ($group->isJoin())
		{
			$tableName = $group->getJoinModel()->getJoin()->table_join;
			$keydata = $keydata[0];
			$primaryKey = $keydata['colname'];
		}
		else
		{
			$tableName = $table->db_table_name;
			$primaryKey = $table->db_primary_key;
		}
		$keydata = $this->getPrimaryKeyAndExtra($tableName);

		// $$$ rob base plugin needs to know group info for date fields in non-join repeat groups
		$basePlugIn->setGroupModel($elementModel->getGroupModel());

		// The element type AFTER saving
		$objtype = $elementModel->getFieldDescription();
		$newObjectType = strtolower($objtype);
		$dbdescriptions = $this->getDBFields($tableName, 'Field');

		if (!$this->canAlterFields() && !$this->canAddFields())
		{
			$objtype = $dbdescriptions[$origColName]->Type;
		}
		if (is_null($objtype))
		{
			return $return;
		}
		$existingfields = array_keys($dbdescriptions);
		$lastfield = $existingfields[count($existingfields) - 1];
		$tableName = FabrikString::safeColName($tableName);
		$lastfield = FabrikString::safeColName($lastfield);
		$altered = false;
		if (!array_key_exists($element->name, $dbdescriptions))
		{
			if ($origColName == '')
			{
				if ($this->canAddFields())
				{
					$fabrikDb
					->setQuery("ALTER TABLE $tableName ADD COLUMN " . FabrikString::safeColName($element->name) . " $objtype AFTER $lastfield");
					if (!$fabrikDb->query())
					{
						return JError::raiseError(500, 'alter structure: ' . $fabrikDb->getErrorMsg());
					}
					$altered = true;
				}
			}
			// Commented out as it stops the update when changing an element name
			// return $return;
		}
		$thisFieldDesc = JArrayHelper::getValue($dbdescriptions, $origColName, new stdClass);

		/* $$$ rob the Default property for timestamps when they are set to CURRENT_TIMESTAMP
		 * doesn't show up from getDBFields()  - so presuming a timestamp field will always default
		* to the current timestamp (update of the field's data controller in the Extra property (on update CURRENT_TIMESTAMP)
				*/
		$existingDef = '';
		if (isset($thisFieldDesc->Type))
		{
			$existingDef = $thisFieldDesc->Type;
			if ($thisFieldDesc->Type == 'timestamp')
			{
				$existingDef .= $thisFieldDesc->Null = 'YES' ? ' NULL' : ' NOT NULL';
				$existingDef .= ' DEFAULT CURRENT_TIMESTAMP';
				$existingDef .= ' ' . $thisFieldDesc->Extra;
			}
		}

		// If its the primary 3.0
		for ($k = 0; $k < count($keydata); $k++)
		{
			if ($keydata[$k]['colname'] == $origColName)
			{
				$existingDef .= ' ' . $keydata[$k]['extra'];
			}
		}
		/* $$$ hugh 2012/05/13 - tweaking things a little so we don't care about certain differences in type.
		 * Initally, just integer types and signed vs unsigned.  So if the existing column is TINYINT(3) UNSIGNED
		* and we think it's INT(3), i.e. that's what getFieldDescription() returns, let's treat those as functionally
		* the same, and not change anything.  Ideally we should turn this into some kind of element model method, so
		* we would do something like $base_existingDef = $elementModel->baseFieldDescription($existingDef), and (say) the
		* field element, if passed "TINYINT(3) UNSIGNED" would return "INT(3)".  But for now, just tweak it here.
		*/
		$lowerobjtype = JString::strtolower(trim($objtype));
		$lowerobjtype = str_replace(' not null', '', $lowerobjtype);
		$lowerobjtype = str_replace(' unsigned', '', $lowerobjtype);
		$base_existingDef = JString::strtolower(trim($existingDef));
		$base_existingDef = str_replace(' unsigned', '', $base_existingDef);
		$base_existingDef = str_replace(array('integer', 'tinyint', 'smallint', 'mediumint', 'bigint'), 'int', $base_existingDef);

		if ($element->name == $origColName && trim($base_existingDef) == $lowerobjtype)
		{
			// No chanages to the element name or field type
			// Give a notice if the user cant alter the field type but selections he has made would normally do so:
			if ($this->canAlterFields() === false && trim($base_existingDef) !== $newObjectType)
			{
				JError::raiseNotice(301, JText::_('COM_FABRIK_NOTICE_ELEMENT_SAVED_BUT_STRUCTUAL_CHANGES_NOT_APPLIED'));
			}

			return $return;
		}

		$return[4] = $existingDef;
		$existingfields = array_keys($dbdescriptions);

		$lastfield = $existingfields[count($existingfields) - 1];
		$tableName = FabrikString::safeColName($tableName);
		$lastfield = FabrikString::safeColName($lastfield);

		// $$$ rob this causes issues when renaming an element with the same name but different upper/lower case
		// if (empty($origColName) || !in_array(JString::strtolower($origColName), $existingfields)) {

		// $$$ rob and this meant that renaming an element created a new column rather than renaming exisiting
		// if (empty($element->name) || !in_array($element->name, $existingfields)) {
		if (empty($origColName) || !in_array($origColName, $existingfields))
		{
			if (!$altered)
			{
				$fabrikDb->setQuery("ALTER TABLE $tableName ADD COLUMN " . FabrikString::safeColName($element->name) . " $objtype AFTER $lastfield");
				if (!$fabrikDb->query())
				{
					/* $$$ rob ok this is hacky but I had a whole series of elements wiped from the db,
					 * but wanted to re-add them into the database.
					* as the db table already had the fields this error was stopping the save.
					*/
					if (!array_key_exists($element->name, $dbdescriptions))
					{
						return JError::raiseError(500, 'alter structure: ' . $fabrikDb->getErrorMsg());
					}

				}
			}
		}
		else
		{
			// $$$ rob don't alter it yet - lets defer this and give the user the choice if they
			// really want to do this
			if ($this->canAlterFields())
			{
				$origColName = $origColName == null ? $fabrikDb->quoteName($element->name) : $fabrikDb->quoteName($origColName);
				if (JString::strtolower($objtype) == 'blob')
				{
					$dropKey = true;
				}
				$q = 'ALTER TABLE ' . $tableName . ' CHANGE ' . $origColName . ' ' . FabrikString::safeColName($element->name) . ' ' . $objtype . ' ';
				$testColName = $tableName . '.' . FabrikString::safeColName($element->name);
				if (FabrikString::safeColName($primaryKey) == $tableName . '.' . FabrikString::safeColName($element->name) && $table->auto_inc)
				{
					if (!strstr($q, 'NOT NULL AUTO_INCREMENT'))
					{
						$q .= ' NOT NULL AUTO_INCREMENT ';
					}
				}
				$origColName = FabrikString::safeColName($origColName);
				$return[0] = true;
				$return[1] = $q;
				$return[2] = $origColName;
				$return[3] = $objtype;
				$return[5] = $dropKey;
				return $return;
			}
		}
		return $return;
	}

	/**
	 * Add or update a database column via sql
	 *
	 * @param   object  &$elementModel  element plugin
	 * @param   string  $origColName    origional field name
	 *
	 * @return  bool
	 */

	public function alterStructure(&$elementModel, $origColName = null)
	{
		$db = FabrikWorker::getDbo();
		$element = $elementModel->getElement();
		$pluginManager = FabrikWorker::getPluginManager();
		$basePlugIn = $pluginManager->getPlugIn($element->plugin, 'element');
		$fbConfig = JComponentHelper::getParams('com_fabrik');
		$fabrikDb = $this->getDb();
		$table = $this->getTable();
		$tableName = $table->db_table_name;

		// $$$ rob base plugin needs to know group info for date fields in non-join repeat groups
		$basePlugIn->setGroupModel($elementModel->getGroupModel());
		$objtype = $elementModel->getFieldDescription();
		$dbdescriptions = $this->getDBFields($tableName);
		if (!$this->canAlterFields())
		{
			foreach ($dbdescriptions as $f)
			{
				if ($f->Field == $origColName)
				{
					$objtype = $f->Type;
				}
			}
		}
		if (!is_null($objtype))
		{
			foreach ($dbdescriptions as $dbdescription)
			{
				$fieldname = JString::strtolower($dbdescription->Field);
				if (JString::strtolower($element->name) == $fieldname && JString::strtolower($dbdescription->Type) == JString::strtolower($objtype))
				{
					return;
				}
				$existingfields[] = $fieldname;
			}
			$lastfield = $fieldname;
			$element->name = FabrikString::safeColName($element->name);
			$tableName = FabrikString::safeColName($tableName);
			$lastfield = FabrikString::safeColName($lastfield);
			if (empty($origColName) || !in_array(JString::strtolower($origColName), $existingfields))
			{
				$fabrikDb->setQuery("ALTER TABLE $tableName ADD COLUMN $element->name $objtype AFTER $lastfield");
				if (!$fabrikDb->query())
				{
					return JError::raiseError(500, 'alter structure: ' . $fabrikDb->getErrorMsg());
				}
			}
			else
			{
				if ($this->canAlterFields())
				{
					if ($origColName == null)
					{
						$origColName = $element->name;
					}
					$origColName = FabrikString::safeColName($origColName);
					$fabrikDb->setQuery("ALTER TABLE $tableName CHANGE $origColName $element->name $objtype");
					if (!$fabrikDb->query())
					{
						return JError::raiseError(500, 'alter structure: ' . $fabrikDb->getErrorMsg());
					}
				}
			}
		}
		return true;
	}

	/**
	 * Can we alter this tables fields structure?
	 *
	 * @return  bool
	 */

	public function canAlterFields()
	{
		$listid = $this->getId();
		if (empty($listid))
		{
			return false;
		}
		$state = $this->alterExisting();
		return $state == 1;
	}

	/**
	 * Get the alter fields setting
	 *
	 * @since	3.0.6
	 *
	 * @return  string	alter fields setting
	 */

	private function alterExisting()
	{
		$params = $this->getParams();
		$fbConfig = JComponentHelper::getParams('com_fabrik');
		$alter = $params->get('alter_existing_db_cols', 'default');
		if ($alter === 'default')
		{
			$alter = $fbConfig->get('fbConf_alter_existing_db_cols', true);
		}
		return $alter;
	}

	/**
	 * Can we add fields to the list?
	 *
	 * @since	3.0.6
	 *
	 * @return  bool
	 */

	public function canAddFields()
	{
		$state = $this->alterExisting();
		return ($state == 1 || $state == 'addonly');
	}

	/**
	 * If not loaded this loads in the table's form model
	 * also binds a reference of the table to the form.
	 *
	 * @return  object	form model with form table loaded
	 */

	public function &getFormModel()
	{
		if (!isset($this->_oForm))
		{
			$this->_oForm = JModel::getInstance('Form', 'FabrikFEModel');
			$table = $this->getTable();
			$this->_oForm->setId($table->form_id);
			$this->_oForm->getForm();
			$this->_oForm->setListModel($this);
		}
		return $this->_oForm;
	}

	/**
	 * Set the form model
	 *
	 * @param   object  $model  form model
	 *
	 * @return  void
	 */

	public function setFormModel($model)
	{
		$this->_oForm = $model;
	}

	/**
	 * Tests if the table is in fact a view
	 *
	 * @return  bool	true if table is a view
	 */

	public function isView()
	{
		$params = $this->getParams();
		$isView = $params->get('isview', null);

		if (!is_null($isView) && (int) $isView >= 0)
		{
			return $isView;
		}
		/* $$$ hugh - because querying INFORMATION_SCHEMA can be very slow (like minutes!) on
		 * a shared host, I made a small change.  The edit table view now adds a hidden 'isview'
		* param, defaulting to -1 on new tables.  So the following code should only ever execute
		* one time, when a new table is saved.  Before this change, because 'isview' wasn't
		* included on the edit view (because it's not a "real" user settable param), so didn't
		* exist when we picked up the params from the submitted data, this code was running (twice!)
		* every time a table was saved.
		* http://fabrikar.com/forums/showthread.php?t=16622&page=6
		*/

		if (isset($this->isView))
		{
			return $this->isView;
		}
		$db = FabrikWorker::getDbo();
		$table = $this->getTable();
		$cn = $this->getConnection();
		$c = $cn->getConnection();
		$dbname = $c->database;
		if ($table->db_table_name == '')
		{
			return;
		}
		$sql = " SELECT table_name, table_type, engine FROM INFORMATION_SCHEMA.tables " . "WHERE table_name = " . $db->quote($table->db_table_name)
		. " AND table_type = 'view' AND table_schema = " . $db->quote($dbname);
		$db->setQuery($sql);
		$row = $db->loadObjectList();
		$this->isView = empty($row) ? 0 : 1;

		// Store and save param for following tests
		$params->set('isview', $this->isView);
		$table->params = (string) $params;
		$table->store();
		return $this->isView;

	}

	/**
	 * Store filters in the registry
	 *
	 * @param   array  $request  filters to store
	 *
	 * @return  void
	 */

	public function storeRequestData($request)
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$session = JFactory::getSession();
		$registry = $session->get('registry');
		$option = 'com_' . $package;
		$tid = 'list' . $this->getRenderContext();

		// Make sure that we only store data thats been entered from this page first test we aren't in a plugin
		if (JRequest::getCmd('option') == $option && is_object($registry))
		{
			// Don't do this when you are viewing a form or details page as it wipes out the table filters
			$reg = $registry->get('_registry');
			if (isset($reg[$option]) && !in_array(JRequest::getCmd('view'), array('form', 'details')))
			{
				unset($reg[$option]['data']->$tid->filter);
			}
		}

		$context = $option . '.' . $tid . '.filter';

		// @TODO test for _clear_ in values and if so delete session data
		foreach ($request as $key => $val)
		{
			if (is_array($val))
			{
				$key = $context . '.' . $key;
				$app->setUserState($key, array_values($val));
			}
		}
	}

	/**
	 * Creates filter array (return existing if exists)
	 *
	 * @return  array	filters
	 */

	public function &getFilterArray()
	{
		if (isset($this->filters))
		{
			return $this->filters;
		}
		$filterModel = $this->getFilterModel();
		$db = FabrikWorker::getDbo();
		$this->filters = array();
		$user = JFactory::getUser();
		$request = $this->getRequestData();
		$this->storeRequestData($request);
		FabrikHelperHTML::debug($request, 'filter:request');

		$params = $this->getParams();
		$elements = $this->getElements('id');

		/* $$$ rob prefilters loaded before anything to avoid issues where you filter on something and
		 * you have 2 prefilters with joined by an OR - this was incorrectly giving SQL of
		* WHERE normal filter = x OR ( prefilter1 = y OR prefilter2 = x)
		* this change changes the SQL to
		* WHERE ( prefilter1 = y OR prefilter2 = x) AND normal filter = x
		*/
		$this->getPrefilterArray($this->filters);

		// These are filters created from a search form or normal search
		$keys = array_keys($request);
		$indexStep = count(JArrayHelper::getValue($this->filters, 'key', array()));
		FabrikHelperHTML::debug($keys, 'filter:request keys');
		foreach ($keys as $key)
		{
			if (is_array($request[$key]))
			{
				foreach ($request[$key] as $kk => $v)
				{
					if (!array_key_exists($key, $this->filters) || !is_array($this->filters[$key]))
					{
						$this->filters[$key] = array();
					}
					$this->filters[$key][$kk + $indexStep] = $v;
				}
			}
		}

		FabrikHelperHTML::debug($this->filters, 'tablemodel::getFilterArray middle');
		$readOnlyValues = array();
		$w = new FabrikWorker;
		$noFiltersSetup = JArrayHelper::getValue($this->filters, 'no-filter-setup', array());
		if (count($this->filters) == 0)
		{
			FabrikWorker::getPluginManager()->runPlugins('onFiltersGot', $this, 'list');
			return $this->filters;
		}

		// Get a list of plugins
		$pluginKeys = $filterModel->getPluginFilterKeys();
		$elementids = JArrayHelper::getValue($this->filters, 'elementid', array());
		$sqlCond = JArrayHelper::getValue($this->filters, 'sqlCond', array());
		$raws = JArrayHelper::getValue($this->filters, 'raw', array());
		foreach ($this->filters['key'] as $i => $keyval)
		{
			$value = $this->filters['value'][$i];
			$condition = JString::strtolower($this->filters['condition'][$i]);
			$key = $this->filters['key'][$i];
			$filterEval = $this->filters['eval'][$i];
			$elid = JArrayHelper::getValue($elementids, $i);
			$key2 = array_key_exists('key2', $this->filters) ? JArrayHelper::getValue($this->filters['key2'], $i, '') : '';

			/* $$$ rob see if the key is a raw filter
			 * 20/12/2010 - think $key is never with _raw now as it is unset in tablefilter::getQuerystringFilters() although may  be set elsewhere
			* - if it is make a note and remove the _raw from the name
			*/
			$raw = JArrayHelper::getValue($raws, $i, false);
			if (substr($key, -5, 5) == '_raw`')
			{
				$key = JString::substr($key, 0, JString::strlen($key) - 5) . '`';
				$raw = true;
			}
			if ($elid == -1)
			{
				// Bool match
				$this->filters['origvalue'][$i] = $value;
				$this->filters['sqlCond'][$i] = $key . ' ' . $condition . ' (' . $db->quote($value) . ' IN BOOLEAN MODE)';
				continue;
			}

			// List plug-in filter found - it should have set its own sql in onGetPostFilter();
			if (in_array($elid, $pluginKeys))
			{
				$this->filters['origvalue'][$i] = $value;
				$this->filters['sqlCond'][$i] = $this->filters['sqlCond'][$i];
				continue;

			}
			$elementModel = JArrayHelper::getValue($elements, $elid);

			// $$$ rob key2 if set is in format  `countries_0`.`label` rather than  `countries`.`label`
			// used for search all filter on 2nd db join element pointing to the same table
			if (strval($key2) !== '')
			{
				$key = $key2;
			}
			$eval = $this->filters['eval'][$i];
			$fullWordsOnly = $this->filters['full_words_only'][$i];
			$exactMatch = $this->filters['match'][$i];

			if (!is_a($elementModel, 'plgFabrik_Element'))
			{
				continue;
			}
			$elementModel->_rawFilter = $raw;

			// $$ hugh - testing allowing {QS} replacements in pre-filter values
			$w->replaceRequest($value);
			$value = $this->_prefilterParse($value);
			$value = $w->parseMessageForPlaceHolder($value);
			if ($filterEval == '1')
			{
				// $$$ rob hehe if you set $i in the eval'd code all sorts of chaos ensues
				$origi = $i;
				$value = stripslashes(htmlspecialchars_decode($value, ENT_QUOTES));
				$value = @eval($value);
				FabrikWorker::logEval($value, 'Caught exception on eval of tableModel::getFilterArray() ' . $key . ': %s');
				$i = $origi;
			}
			if ($condition == 'regexp')
			{
				$condition = 'REGEXP';

				// $$$ 30/06/2011 rob dont escape the search as it may contain \\\ from preg_escape (e.g. search all on 'c+b)

				// $$$ 14/11/2012 - Lower case search value - as accented characters e.g. Ö are case sensetive in regex. Key already lower cased in filter model

				// $value = 'LOWER(' . $db->quote($value, false) . ')';
			}
			elseif ($condition == 'like')
			{
				$condition = 'LIKE';
				$value = $db->quote($value);
			}
			elseif ($condition == 'laterthisyear' || $condition == 'earlierthisyear')
			{
				$value = $db->quote($value);
			}
			if ($fullWordsOnly == '1')
			{
				$condition = 'REGEXP';
			}
			$originalValue = $this->filters['value'][$i];
			if ($value == '' && $eval == FABRIKFILTER_QUERY)
			{
				JError::raiseError(500, JText::_('COM_FABRIK_QUERY_PREFILTER_WITH_NO_VALUE'));
			}
			list($value, $condition) = $elementModel->getFilterValue($value, $condition, $eval);
			if ($fullWordsOnly == '1')
			{
				if (is_array($value))
				{
					foreach ($value as &$v)
					{
						$v = "\"[[:<:]]" . $v . "[[:>:]]\"";
					}
				}
				else
				{
					$value = "\"[[:<:]]" . $value . "[[:>:]]\"";
				}
			}
			if ($condition === 'REGEXP')
			{
				// $$$ 15/11/2012 - moved from before getFilterValue() to after as otherwise date filters in querystrings created wonky query
				$value = 'LOWER(' . $db->quote($value, false) . ')';
			}
			if (!array_key_exists($i, $sqlCond) || $sqlCond[$i] == '')
			{
				$query = $elementModel->getFilterQuery($key, $condition, $value, $originalValue, $this->filters['search_type'][$i]);
				$this->filters['sqlCond'][$i] = $query;
			}
			$this->filters['condition'][$i] = $condition;

			// Used when getting the selected dropdown filter value
			$this->filters['origvalue'][$i] = $originalValue;
			$this->filters['value'][$i] = $value;
			if (!array_key_exists($i, $noFiltersSetup))
			{
				$this->filters['no-filter-setup'][$i] = 0;
			}
			if ($this->filters['no-filter-setup'][$i] == 1)
			{
				$tmpName = $elementModel->getFullName(false, true, false);
				$tmpData = array($tmpName => $originalValue, $tmpName . '_raw' => $originalValue);

				// Set defaults to null to ensure we get correct value for 2nd dropdown search value (mutli dropdown from search form)
				$elementModel->defaults = null;
				if (array_key_exists($key, $readOnlyValues))
				{
					$readOnlyValues[$key][] = $elementModel->getFilterRO($tmpData);
				}
				else
				{
					$readOnlyValues[$key] = array($elementModel->getFilterRO($tmpData));
				}
				// Set it back to null again so that in form view we dont return this value.
				$elementModel->defaults = null;

				// Filter value assinged in readOnlyValues foreach loop towards end of this function
				$this->filters['filter'][$i] = '';
			}
			else
			{
				/*$$$rob not sure $value is the right var to put in here - or if its acutally used
				 * but without this line you get warnings about missing variable in the filter array
				*/
				$this->filters['filter'][$i] = $value;
			}
		}
		FabrikHelperHTML::debug($this->filters, 'end filters');
		foreach ($readOnlyValues as $key => $val)
		{
			foreach ($this->filters['key'] as $i => $fkey)
			{
				if ($fkey === $key)
				{
					$this->filters['filter'][$i] = implode("<br>", $val);
				}
			}
		}
		FabrikWorker::getPluginManager()->runPlugins('onFiltersGot', $this, 'list');
		FabrikHelperHTML::debug($this->filters, 'after plugins:onFiltersGot');
		return $this->filters;
	}

	/**
	 * Creates array of prefilters
	 *
	 * @param   array  &$filters  filters
	 *
	 * @return  array	prefilters combinde with filters
	 */

	protected function getPrefilterArray(&$filters)
	{
		if (!isset($this->prefilters))
		{
			$app = JFactory::getApplication();
			$package = $app->getUserState('com_fabrik.package', 'fabrik');
			$params = $this->getParams();
			$showInList = array();
			$listels = json_decode(FabrikWorker::getMenuOrRequestVar('list_elements', '', $this->isMambot));
			if (isset($listels->show_in_list))
			{
				$showInList = $listels->show_in_list;
			}
			$showInList = (array) JRequest::getVar('fabrik_show_in_list', $showInList);

			// Are we coming from a post request via a module?
			$moduleid = 0;
			$requestRef = JRequest::getVar('listref', '');
			if ($requestRef !== '' && !strstr($requestRef, 'com_' . $package))
			{
				// If so we need to load in the modules parameters
				$ref = explode('_', $requestRef);
				if (count($ref) > 1)
				{
					$moduleid = (int) array_pop($ref);
					$db = JFactory::getDbo();
					$query = $db->getQuery(true);
					if ($moduleid !== 0)
					{
						$this->setRenderContext($moduleid);
						$query->select('params')->from('#__modules')->where('id = ' . $moduleid);
						$db->setQuery($query);
						$obj = json_decode($db->loadResult());
						if (is_object($obj) && isset($obj->prefilters))
						{
							$properties = $obj->prefilters;
						}
					}
				}
			}

			// List prfilter properties
			$elements = $this->getElements('filtername');
			$afilterFields = (array) $params->get('filter-fields');
			$afilterConditions = (array) $params->get('filter-conditions');
			$afilterValues = (array) $params->get('filter-value');
			$afilterAccess = (array) $params->get('filter-access');
			$afilterEval = (array) $params->get('filter-eval');
			$afilterJoins = (array) $params->get('filter-join');
			$afilterGrouped = (array) $params->get('filter-grouped');

			/* If we are rendering as a module dont pick up the menu item options (parmas already set in list module)
			 * so first statement when rendenering a module, 2nd when posting to the component from a module.
			*/
			if (!strstr($this->getRenderContext(), 'mod_fabrik_list') && $moduleid === 0)
			{
				$properties = FabrikWorker::getMenuOrRequestVar('prefilters', '', $this->isMambot);
			}
			if (isset($properties))
			{
				$prefilters = JArrayHelper::fromObject(json_decode($properties));
				$conditions = (array) $prefilters['filter-conditions'];
				if (!empty($conditions))
				{
					$afilterFields = JArrayHelper::getValue($prefilters, 'filter-fields', array());
					$afilterConditions = JArrayHelper::getValue($prefilters, 'filter-conditions', array());
					$afilterValues = JArrayHelper::getValue($prefilters, 'filter-value', array());
					$afilterAccess = JArrayHelper::getValue($prefilters, 'filter-access', array());
					$afilterEval = JArrayHelper::getValue($prefilters, 'filter-eval', array());
					$afilterJoins = JArrayHelper::getValue($prefilters, 'filter-join', array());
				}
			}
			$join = 'WHERE';
			$w = new FabrikWorker;
			for ($i = 0; $i < count($afilterFields); $i++)
			{
				if (!array_key_exists(0, $afilterJoins) || $afilterJoins[0] == '')
				{
					$afilterJoins[0] = 'AND';
				}
				$join = JArrayHelper::getValue($afilterJoins, $i, 'AND');

				if (trim(JString::strtolower($join)) == 'where')
				{
					$join = 'AND';
				}
				$filter = $afilterFields[$i];
				$condition = $afilterConditions[$i];
				$selValue = JArrayHelper::getValue($afilterValues, $i, '');
				$filterEval = JArrayHelper::getValue($afilterEval, $i, false);
				$filterGrouped = JArrayHelper::getValue($afilterGrouped, $i, false);

				$selAccess = $afilterAccess[$i];
				if (!$this->mustApplyFilter($selAccess))
				{
					continue;
				}
				// $tmpfilter = strstr($filter, '_raw') ? FabrikString::rtrimword( $filter, '_raw') : $filter;
				$raw = preg_match("/_raw$/", $filter) > 0;
				$tmpfilter = $raw ? FabrikString::rtrimword($filter, '_raw') : $filter;
				$elementModel = JArrayHelper::getValue($elements, FabrikString::safeColName($tmpfilter), false);
				if ($elementModel === false)
				{
					// Include the JLog class.
					jimport('joomla.log.log');

					// Add the logger.
					JLog::addLogger(array('text_file' => 'fabrik.log.php'));

					// Start logging...
					JLog::add(
					'A prefilter has been set up on an unpublished element, and will not be applied:' . FabrikString::safeColName($tmpfilter),
					JLog::NOTICE, 'com_fabrik');
					continue;
				}
				$filters['join'][] = $join;
				$filters['search_type'][] = 'prefilter';
				$filters['key'][] = $tmpfilter;
				$filters['value'][] = $selValue;
				$filters['origvalue'][] = $selValue;
				$filters['sqlCond'][] = '';
				$filters['no-filter-setup'][] = null;
				$filters['condition'][] = $condition;
				$filters['grouped_to_previous'][] = $filterGrouped;
				$filters['eval'][] = $filterEval;
				$filters['match'][] = ($condition == 'equals') ? 1 : 0;
				$filters['full_words_only'][] = 0;
				$filters['label'][] = '';
				$filters['access'][] = '';
				$filters['key2'][] = '';
				$filters['required'][] = 0;
				$filters['hidden'][] = false;
				$filters['elementid'][] = $elementModel !== false ? $elementModel->getElement()->id : 0;
				$filters['raw'][] = $raw;
				$this->prefilters = true;
			}
		}
		FabrikHelperHTML::debug($filters, 'prefilters');
	}

	/**
	 * Get the total number of records in the table
	 *
	 * @return  int		total number of records
	 */

	public function getTotalRecords()
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');

		// $$$ rob ensure that the limits are set - otherwise can create monster query
		$this->setLimits();
		$session = JFactory::getSession();
		$context = 'com_' . $package . '.list' . $this->getRenderContext() . '.total';
		if (isset($this->totalRecords))
		{
			$session->set($context, $this->totalRecords);
			return $this->totalRecords;
		}
		// $$$ rob getData() should always be run first
		if (is_null($this->_data))
		{
			$this->getData();
			return $this->totalRecords;
		}
		if ($this->mergeJoinedData())
		{
			$this->totalRecords = $this->getJoinMergeTotalRecords();
			$session->set($context, $this->totalRecords);
			return $this->totalRecords;
		}
	}

	/**
	 * Modified version of getTotalRecords() for use when the table join data
	 * is to be merged on the main table's primary key
	 *
	 * @return int total records
	 */

	protected function getJoinMergeTotalRecords()
	{
		$db = $this->getDb();
		$table = $this->getTable();
		$count = "DISTINCT " . $table->db_primary_key;
		$totalSql = "SELECT COUNT(" . $count . ") AS t FROM " . $table->db_table_name . " " . $this->_buildQueryJoin();
		$totalSql .= " " . $this->_buildQueryWhere(JRequest::getVar('incfilters', 1));
		$totalSql .= " " . $this->_buildQueryGroupBy();
		$totalSql = $this->pluginQuery($totalSql);
		$db->setQuery($totalSql);
		FabrikHelperHTML::debug($db->getQuery(), 'table getJoinMergeTotalRecords');
		$total = $db->loadResult();
		return $total;
	}

	/**
	 * Load in the elements for the table's form
	 * If no form loaded for the list object then one is loaded
	 *
	 * @return  array	element objects
	 */

	public function getFormGroupElementData()
	{
		return $this->getFormModel()->getGroupsHiarachy();
	}

	/**
	 * Require the correct pagenav class based on template
	 *
	 * @param   int  $total       total
	 * @param   int  $limitstart  start
	 * @param   int  $limit       length of records to return
	 *
	 * @return  object	pageNav
	 */

	function &getPagination($total = 0, $limitstart = 0, $limit = 0)
	{
		$db = FabrikWorker::getDbo();
		if (!isset($this->nav))
		{
			if ($this->randomRecords)
			{
				$limitstart = $this->getRandomLimitStart();
			}
			$params = $this->getParams();
			$this->nav = new FPagination($total, $limitstart, $limit);

			// $$$ rob set the nav link urls to the table action to avoid messed up url links when  doing ranged filters via the querystring
			$this->nav->url = $this->getTableAction();
			$this->nav->showAllOption = $params->get('showall-records', false);
			$this->nav->setId($this->getId());
			$this->nav->showTotal = $params->get('show-total', false);
			$item = $this->getTable();
			$this->nav->startLimit = FabrikWorker::getMenuOrRequestVar('rows_per_page', $item->rows_per_page, $this->isMambot);
			$this->nav->showDisplayNum = $params->get('show_displaynum', true);
		}
		return $this->nav;
	}

	/**
	 * Get the random lmit start val
	 *
	 * @return  int	 limit start
	 */

	protected function getRandomLimitStart()
	{
		if (isset($this->randomLimitStart))
		{
			return $this->randomLimitStart;
		}
		$db = $this->getDb();
		$table = $this->getTable();
		/* $$$ rob @todo - do we need to add the join in here as well?
		 * added + 1 as with 4 records to show 3 4th was not shown
		*/
		$query = $db->getQuery(true);
		$query->select('FLOOR(RAND() * COUNT(*) + 1) AS ' . $db->quoteName('offset'))->from($db->quoteName($table->db_table_name));
		$query = $this->_buildQueryWhere($query);
		$db->setQuery($query);
		/* $db
		 ->setQuery(
		 		'SELECT FLOOR(RAND() * COUNT(*) + 1) AS ' . $db->quoteName('offset') . ' FROM ' . $db->quoteName($table->db_table_name) . ' '
		 		. $this->_buildQueryWhere()); */
		$limitstart = $db->loadResult();
		/*$$$ rob 11/01/2011 cant do this as we dont know what the total is yet
		 $$$ rob ensure that the limitstart + limit isn't greater than the total
		if ($limitstart + $limit > $total) {
		$limitstart = $total - $limit;
		}
		$$$ rob 25/02/2011 if you only have say 3 reocrds then above random will show 1 2 or 3 records
		so decrease the random start num by the table row dispaly num
		going to favour records at the beginning of the table though
		*/
		$limitstart -= $table->rows_per_page;
		if ($limitstart < 0)
		{
			$limitstart = 0;
		}
		$this->randomLimitStart = $limitstart;
		return $limitstart;
	}

	/**
	 * Used to determine which filter action to use.
	 * If a filter is a range then override lists setting with onsubmit
	 *
	 * @return  string
	 */

	public function getFilterAction()
	{
		if (!isset($this->_real_filter_action))
		{
			// first, grab the list's setting as the default
			$table = $this->getTable();
			$this->_real_filter_action = $table->filter_action;

			// Check to see if any list filter plugins require a Go button, like radius search
			$pluginManager = FabrikWorker::getPluginManager();
			$listPlugins = $pluginManager->getPlugInGroup('list');

			$pluginManager->runPlugins('requireFilterSubmit', $this, 'list');
			$res = $pluginManager->_data;
			if (!empty($res))
			{
				if (in_array(1, $res))
				{
					$this->_real_filter_action = 'submitform';
					return $this->_real_filter_action;
				}
			}

			// No list plugins expressed a preference, so check for range filters
			$form = $this->getFormModel();
			$groups = $form->getGroupsHiarachy();
			foreach ($groups as $groupModel)
			{
				$elementModels = $groupModel->getPublishedElements();
				foreach ($elementModels as $elementModel)
				{
					$element = $elementModel->getElement();
					if (isset($element->filter_type) && $element->filter_type <> '')
					{
						if ($elementModel->canView() && $elementModel->canUseFilter() && $element->show_in_list_summary == '1')
						{
							// $$$ rob does need to check auto-compelte otherwise submission occurs without the value selected.
							if ($element->filter_type == 'range' || $element->filter_type == 'auto-complete')
							{
								$this->_real_filter_action = 'submitform';
								return $this->_real_filter_action;
							}
						}
					}
				}
			}
		}
		return $this->_real_filter_action;
	}

	/**
	 * Gets the part of a url to describe the key that the link links to
	 * if a table this is rowid=x
	 * if a view this is view_primary_key={where statement}
	 *
	 * @param   object  $data  current list row
	 *
	 * @return  string
	 */

	protected function getKeyIndetifier($data)
	{
		return '&rowid=' . $this->getSlug($data);
	}

	/**
	 * Format the row id slug
	 *
	 * @param   object  $row  current list row data
	 *
	 * @return  string	formatted slug
	 */

	protected function getSlug($row)
	{
		if (!isset($row->slug))
		{
			return '';
		}
		$row->slug = str_replace(':', '-', $row->slug);
		$row->slug = JApplication::stringURLSafe($row->slug);
		return $row->slug;
	}

	/**
	 * Get other lists who have joins to the list db tables pk
	 *
	 * @return array of element objects that are database joins and that
	 * use this table's key as their foregin key
	 */

	public function getJoinsToThisKey()
	{
		if (is_null($this->_joinsToThisKey))
		{
			$this->_joinsToThisKey = array();
			$db = FabrikWorker::getDbo(true);
			$table = $this->getTable();
			if ($table->id == 0)
			{
				$this->_joinsToThisKey = array();
			}
			else
			{
				$usersConfig = JComponentHelper::getParams('com_fabrik');
				$query = $db->getQuery(true);

				// Select the required fields from the table.
				$query
				->select(
						"l.db_table_name,
						el.name, el.plugin, l.label AS listlabel, l.id as list_id, \n
						el.id AS element_id, el.label AS element_label, f.id AS form_id,
						el.params AS element_params");
				$query->from('#__{package}_elements AS el');
				$query->join('LEFT', '#__{package}_formgroup AS fg ON fg.group_id = el.group_id');
				$query->join('LEFT', '#__{package}_forms AS f ON f.id = fg.form_id');
				$query->join('LEFT', '#__{package}_lists AS l ON l.form_id = f.id');
				$query->join('LEFT', '#__{package}_groups AS g ON g.id = fg.group_id');
				$query->where('el.published = 1 AND g.published = 1');
				$query
				->where(
						"(plugin = 'databasejoin' AND el.params like '%\"join_db_name\":\"" . $table->db_table_name
						. "\"%'
						AND el.params like  '%\"join_conn_id\":\"" . $table->connection_id . "%') OR (plugin = 'cascadingdropdown' AND \n"
						. " el.params like '\"%cascadingdropdown_table\":\"" . $table->id . "\"%' \n"
						. "AND el.params like '\"%cascadingdropdown_connection\":\"" . $table->connection_id . "\"%') ", "OR");

				// Load in user element links as well
				// $$$rob - not convinced this is a good idea
				if ($usersConfig->get('user_elements_as_related_data', false) == true)
				{
					$query->where("(plugin = 'user' AND
							el.params like '%\"join_conn_id\":\"" . $table->connection_id . "%\"' )", "OR");
				}

				$db->setQuery($query);
				$this->_joinsToThisKey = $db->loadObjectList();
				if ($db->getErrorNum())
				{
					$this->_joinsToThisKey = array();
					JError::raiseWarning(500, 'getJoinsToThisKey: ' . $db->getErrorMsg());
				}
				foreach ($this->_joinsToThisKey as $join)
				{
					$element_params = json_decode($join->element_params);
					$join->join_key_column = $element_params->join_key_column;
				}
			}
		}
		return $this->_joinsToThisKey;
	}

	/**
	 * Get an array of elements that point to a form where their data will be filtered
	 *
	 * @return  array
	 */

	public function getLinksToThisKey()
	{
		if (!is_null($this->aJoinsToThisKey))
		{
			return $this->aJoinsToThisKey;
		}
		$params = $this->getParams();
		$this->aJoinsToThisKey = array();
		$facted = $params->get('factedlinks', new stdClass);
		if (!isset($facted->linkedform))
		{
			return $this->aJoinsToThisKey;
		}
		$linkedForms = $facted->linkedform;
		$aAllJoinsToThisKey = $this->getJoinsToThisKey();
		foreach ($aAllJoinsToThisKey as $join)
		{
			$key = "{$join->list_id}-{$join->form_id}-{$join->element_id}";
			if (isset($linkedForms->$key))
			{
				$this->aJoinsToThisKey[] = $join;
			}
			else
			{
				// $$$ rob required for releated form links. otherwise links for forms not listed first in the admin options wherent being rendered
				$this->aJoinsToThisKey[] = false;
			}
		}
		return $this->aJoinsToThisKey;
	}

	/**
	 * Get empty data message
	 *
	 * @return string
	 */

	public function getEmptyDataMsg()
	{
		if (isset($this->emptyMsg))
		{
			return $this->emptyMsg;
		}
		$params = $this->getParams();
		return $params->get('empty_data_msg', JText::_('COM_FABRIK_LIST_NO_DATA_MSG'));
	}

	/**
	 * Get the message telling the user that all required filters must be selected
	 *
	 * @return  string
	 */

	public function getRequiredMsg()
	{
		if (isset($this->emptyMsg))
		{
			return $this->emptyMsg;
		}
		return '';
	}

	/**
	 * Do we have all required filters, by both list level and element level settings.
	 *
	 * @return  bool
	 */

	public function gotAllRequiredFilters()
	{
		if ($this->listRequiresFiltering() && !$this->gotOptionalFilters())
		{
			$this->emptyMsg = JText::_('COM_FABRIK_SELECT_AT_LEAST_ONE_FILTER');
			return false;
		}
		if ($this->hasRequiredElementFilters() && !$this->getRequiredFiltersFound())
		{
			$this->emptyMsg = JText::_('COM_FABRIK_PLEASE_SELECT_ALL_REQUIRED_FILTERS');
			return false;
		}
		return true;
	}

	/**
	 * Does a filter have to be appled before we show any list data
	 *
	 * @return bool
	 */

	protected function listRequiresFiltering()
	{
		$app = JFactory::getApplication();
		$params = $this->getParams();
		/*
		 if (!$this->getRequiredFiltersFound()) {
		return true;
		}
		*/
		switch ($params->get('require-filter', 0))
		{
			case 0:
			default:
				return false;
				break;
			case 1:
				return true;
				break;
			case 2:
				return $app->isAdmin() ? false : true;
				break;
		}
	}

	/**
	 * Have all the required filters been met?
	 *
	 * @return  bool  true if they have if false we shouldnt show the table data
	 */

	function hasRequiredElementFilters()
	{
		if (isset($this->hasRequiredElementFilters))
		{
			return $this->hasRequiredElementFilters;
		}
		$filters = $this->getFilterArray();
		$elements = $this->getElements();
		$this->hasRequiredElementFilters = false;
		foreach ($elements as $kk => $val2)
		{
			// Don't do with = as this foobars up the last elementModel
			$elementModel = $elements[$kk];
			$element = $elementModel->getElement();
			if ($element->filter_type <> '' && $element->filter_type != 'null')
			{
				if ($elementModel->canView() && $elementModel->canUseFilter())
				{
					if ($elementModel->getParams()->get('filter_required') == 1)
					{
						$this->elementsWithRequiredFilters[] = $elementModel;
						$this->hasRequiredElementFilters = true;
					}
				}
			}
		}
		return $this->hasRequiredElementFilters;
	}

	/**
	 * Do we have any filters that aren't pre-filters
	 *
	 * @return  bool
	 */

	protected function gotOptionalFilters()
	{
		$filters = $this->getFilterArray();
		$ftypes = JArrayHelper::getValue($filters, 'search_type', array());
		foreach ($ftypes as $i => $ftype)
		{
			if ($ftype != 'prefilter')
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Have all the required filters been met?
	 *
	 * @return  bool  true if they have if false we shouldnt show the table data
	 */

	function getRequiredFiltersFound()
	{
		if (isset($this->requiredFilterFound))
		{
			return $this->requiredFilterFound;
		}
		$filters = $this->getFilterArray();
		$elements = $this->getElements();
		$required = array();
		/* if no required filters, then by definition we have them all */
		if (!$this->hasRequiredElementFilters())
		{
			return true;
		}
		/* if no filter keys, by definition we don't have required ones */
		if (!array_key_exists('key', $filters) || !is_array($filters['key']))
		{
			$this->emptyMsg = JText::_('COM_FABRIK_PLEASE_SELECT_ALL_REQUIRED_FILTERS');
			return false;
		}
		foreach ($this->elementsWithRequiredFilters as $elementModel)
		{
			if ($elementModel->getParams()->get('filter_required') == 1)
			{
				$name = FabrikString::safeColName($elementModel->getFullName(false, false, false));
				reset($filters['key']);
				$found = false;
				while (list($key, $val) = each($filters['key']))
				{
					if ($val == $name)
					{
						$found = true;
						break;
					}
				}
				if (!$found || $filters['origvalue'][$key] == '')
				{
					$this->emptyMsg = JText::_('COM_FABRIK_PLEASE_SELECT_ALL_REQUIRED_FILTERS');
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Get filters for display in html view
	 *
	 * @param   string  $container  List container
	 * @param   string  $type       Type
	 * @param   string  $id         Html id, only used if called from viz plugin
	 * @param   string  $ref        Js ref used when filters set for visualizations
	 *
	 * @return array filters
	 */

	public function getFilters($container = 'listform_1', $type = 'list', $id = '', $ref = '')
	{
		if (!isset($this->viewfilters))
		{
			$profiler = JProfiler::getInstance('Application');
			$params = $this->getParams();
			$this->viewfilters = array();
			JDEBUG ? $profiler->mark('fabrik makeFilters start') : null;
			$modelFilters = $this->makeFilters($container, $type, $id, $ref);
			JDEBUG ? $profiler->mark('fabrik makeFilters end') : null;
			foreach ($modelFilters as $name => $filter)
			{
				$f = new stdClass;
				$f->label = $filter->label;
				$f->element = $filter->filter;
				$f->required = array_key_exists('required', $filter) ? $filter->required : '';
				$this->viewfilters[$filter->name] = $f;
			}
			FabrikWorker::getPluginManager()->runPlugins('onMakeFilters', $this, 'list');
		}
		return $this->viewfilters;
	}

	/**
	 * Creates an array of HTML code for each filter
	 * Also adds in JS code to manage filters
	 *
	 * @param   string  $container  container
	 * @param   string  $type       type listviz
	 * @param   int     $id         html id, only used if called from viz plugin
	 * @param   string  $ref        js filter ref, used when rendering filters for visualizations
	 *
	 * @return  array	of html code for each filter
	 */

	protected function &makeFilters($container = 'listform_1', $type = 'list', $id = '', $ref = '')
	{
		$aFilters = array();
		$table = $this->getTable();
		$opts = new stdClass;
		$opts->container = $container;
		$opts->type = $type;
		$opts->id = $type === 'list' ? $this->getId() : $id;
		$opts->ref = $this->getRenderContext();
		$opts->advancedSearch = $this->getAdvancedSearchOpts();
		$opts->advancedSearch->controller = $type;
		$opts = json_encode($opts);
		$fscript = "
		Fabrik.filter_{$container} = new FbListFilter($opts);\n";

		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$filters = $this->getFilterArray();

		$params = $this->getParams();
		if ($params->get('search-mode', 'AND') == 'OR')
		{
			// One field to search them all (and in the darkness bind them)
			$requestKey = $this->getFilterModel()->getSearchAllRequestKey();
			$v = $this->getFilterModel()->getSearchAllValue('html');
			$o = new stdClass;
			$class = FabrikWorker::j3() ? 'fabrik_filter search-query input-medium' : 'fabrik_filter';
			$o->filter = '<input type="search" size="20" placeholder="' . JText::_('COM_FABRIK_SEARCH') . '" value="' . $v
			. '" class="' . $class . '" name="' . $requestKey . '" />';
			if ($params->get('search-mode-advanced') == 1)
			{
				$opts = array();
				$opts[] = JHTML::_('select.option', 'all', JText::_('COM_FABRIK_ALL_OF_THESE_TERMS'));
				$opts[] = JHTML::_('select.option', 'any', JText::_('COM_FABRIK_ANY_OF_THESE_TERMS'));
				$opts[] = JHTML::_('select.option', 'exact', JText::_('COM_FABRIK_EXACT_TERMS'));
				$opts[] = JHTML::_('select.option', 'none', JText::_('COM_FABRIK_NONE_OF_THESE_TERMS'));
				$mode = $app->getUserStateFromRequest('com_' . $package . '.list' . $this->getRenderContext() . '.searchallmode', 'search-mode-advanced');
				$o->filter .= '&nbsp;'
						. JHTML::_('select.genericList', $opts, 'search-mode-advanced', "class='fabrik_filter'", 'value', 'text', $mode);
			}
			$o->name = 'all';
			$o->label = $params->get('search-all-label', JText::_('COM_FABRIK_ALL'));
			$aFilters[] = $o;
		}
		$counter = 0;
		/* $$$ hugh - another one of those weird ones where if we use = the foreach loop
		 * will sometimes skip a group
		* $groups = $this->getFormGroupElementData();
		*/
		$groups = $this->getFormGroupElementData();
		foreach ($groups as $groupModel)
		{
			$g = $groupModel->getGroup();
			$elementModels = null;
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();

				/*$$ rob added as some filter_types were null, have to double check that this doesnt
				 * mess with showing the readonly values from search forms
				*/
				if (isset($element->filter_type) && $element->filter_type <> '' && $element->filter_type != 'null')
				{
					if ($elementModel->canView() && $elementModel->canUseFilter())
					{
						/* $$$ rob in facted browsing somehow (not sure how!) some elements from the facted table get inserted into elementModels
						 * with their form id set - so test if its been set and if its not the same as the current form id
						* if so then ignore
						*/
						if (isset($element->form_id) && (int) $element->form_id !== 0 && $element->form_id !== $this->getFormModel()->getId())
						{
							continue;
						}
						// Force the correct group model into the element model to ensure no wierdness in getting the element name
						$elementModel->setGroupModel($groupModel);
						$o = new stdClass;
						$o->name = $elementModel->getFullName(false, true, false);
						$o->filter = $elementModel->getFilter($counter, true);
						$fscript .= $elementModel->filterJS(true, $container);
						$o->required = $elementModel->getParams()->get('filter_required');
						$o->label = $elementModel->getParams()->get('alt_list_heading') == '' ? $element->label
						: $elementModel->getParams()->get('alt_list_heading');
						$aFilters[] = $o;
						$counter++;
					}
				}
			}
		}
		$fscript .= 'Fabrik.filter_' . $container . ".update();\n";
		$this->filterJs = $fscript;

		// Check for search form filters - if they exists create hidden elements for them
		$keys = JArrayHelper::getValue($filters, 'key', array());

		foreach ($keys as $i => $key)
		{
			if ($filters['no-filter-setup'][$i] == '1' && !in_array($filters['search_type'][$i], array('searchall', 'advanced', 'jpluginfilters')))
			{
				$o = new stdClass;
				/* $$$ rob - we are now setting read only filters 'filter' var to the elements read only
				 * label for the passed in filter value
				*$o->filter = $value;
				*/
				$elementModel = $this->getFormModel()->getElement(str_replace('`', '', $key));
				$o->filter = $filters['filter'][$i];
				if ($elementModel)
				{
					$elementModel->getElement()->filter_type = 'hidden';
					$o->filter .= $elementModel->getFilter(0, true);
				}
				$o->name = $filters['key'][$i];
				$o->label = $filters['label'][$i];
				$aFilters[] = $o;
			}
		}
		return $aFilters;
	}

	/**
	 * Build the advanced search link
	 *
	 * @return  string  <a href...> link
	 */

	public function getAdvancedSearchLink()
	{
		$params = $this->getParams();
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		if ($params->get('advanced-filter', '0'))
		{
			$table = $this->getTable();
			$tmpl = $this->getTmpl();
			$url = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $package . '&amp;view=list&amp;layout=_advancedsearch&amp;tmpl=component&amp;listid='
					. $table->id . '&amp;nextview=' . JRequest::getVar('view', 'list');

			$url .= '&amp;tkn=' . JSession::getFormToken();
			$title = '<span>' . JText::_('COM_FABRIK_ADVANCED_SEARCH') . '</span>';
			$opts = array('alt' => JText::_('COM_FABRIK_ADVANCED_SEARCH'), 'class' => 'fabrikTip', 'opts' => "{notice:true}", 'title' => $title);
			$img = FabrikHelperHTML::image('find.png', 'list', $tmpl, $opts);
			return '<a href="' . $url . '" class="advanced-search-link">' . $img . '</a>';
		}
		else
		{
			return '';
		}
	}

	/**
	 * Called from index.php?option=com_fabrik&view=list&layout=_advancedsearch&tmpl=component&listid=4
	 * advanced serach popup view
	 *
	 * @return  object	advanced search options
	 */

	public function getAdvancedSearchOpts()
	{
		$params = $this->getParams();
		$opts = new stdClass;

		// $$$ rob - 20/208/2012 if list advanced search off return nothing
		if ($params->get('advanced-filter') == 0)
		{
			return $opts;
		}
		$list = $this->getTable();
		$listRef = $this->getRenderContext();
		$opts->conditionList = FabrikHelperHTML::conditonList($listRef, '');
		list($fieldNames, $firstFilter) = $this->getAdvancedSearchElementList();
		$statements = $this->getStatementsOpts();
		$opts->elementList = JHTML::_('select.genericlist', $fieldNames, 'fabrik___filter[list_' . $listRef . '][key][]',
				'class="inputbox key" size="1" ', 'value', 'text');
		$opts->statementList = JHTML::_('select.genericlist', $statements, 'fabrik___filter[list_' . $listRef . '][condition][]',
				'class="inputbox" size="1" ', 'value', 'text');
		$opts->listid = $list->id;
		$opts->listref = $listRef;
		$opts->ajax = $this->isAjax();
		$opts->counter = count($this->getadvancedSearchRows()) - 1;
		$elements = $this->getElements();
		$arr = array();
		foreach ($elements as $e)
		{
			$key = $e->getFilterFullName();
			$arr[$key] = array('id' => $e->getId(), 'plugin' => $e->getElement()->plugin);
		}
		$opts->elementMap = $arr;
		return $opts;
	}

	/**
	 * Get a list of elements that are included in the advacned search dropdown list
	 *
	 * @return  array  list of fields names and which is the first filter
	 */

	private function getAdvancedSearchElementList()
	{
		$first = false;
		$firstFilter = false;
		$fieldNames[] = JHTML::_('select.option', '', JText::_('COM_FABRIK_PLEASE_SELECT'));
		$elementModels = $this->getElements();
		foreach ($elementModels as $elementModel)
		{
			$element = $elementModel->getElement();
			$elParams = $elementModel->getParams();
			if ($elParams->get('inc_in_adv_search', 1))
			{
				$elName = $elementModel->getFilterFullName();
				if (!$first)
				{
					$first = true;
					$firstFilter = $elementModel->getFilter(0, false);
				}
				$fieldNames[] = JHTML::_('select.option', $elName, strip_tags($element->label));
			}
		}
		return array($fieldNames, $firstFilter);
	}

	/**
	 * Get a list of advanced search options
	 *
	 * @return array of JHTML options
	 */

	private function getStatementsOpts()
	{
		$statements = array();
		$statements[] = JHTML::_('select.option', '=', JText::_('COM_FABRIK_EQUALS'));
		$statements[] = JHTML::_('select.option', '<>', JText::_('COM_FABRIK_NOT_EQUALS'));
		$statements[] = JHTML::_('select.option', 'BEGINS WITH', JText::_('COM_FABRIK_BEGINS_WITH'));
		$statements[] = JHTML::_('select.option', 'CONTAINS', JText::_('COM_FABRIK_CONTAINS'));
		$statements[] = JHTML::_('select.option', 'ENDS WITH', JText::_('COM_FABRIK_ENDS_WITH'));
		$statements[] = JHTML::_('select.option', '>', JText::_('COM_FABRIK_GREATER_THAN'));
		$statements[] = JHTML::_('select.option', '<', JText::_('COM_FABRIK_LESS_THAN'));
		return $statements;
	}

	/**
	 * Get a list of submitted advanced filters
	 *
	 * @return array advanced filter values
	 */

	private function getAdvancedFilterValues()
	{
		$filters = $this->getFilterArray();
		$advanced = array();
		$iKeys = array_keys(JArrayHelper::getValue($filters, 'key', array()));
		foreach ($iKeys as $i)
		{
			$searchType = JArrayHelper::getValue($filters['search_type'], $i);
			if (!is_null($searchType) && $searchType == 'advanced')
			{
				$tmp = array();
				foreach (array_keys($filters) as $k)
				{
					if (array_key_exists($k, $advanced))
					{
						$advanced[$k][] = JArrayHelper::getValue($filters[$k], $i, '');
					}
					else
					{
						$advanced[$k] = array_key_exists($i, $filters[$k]) ? array(($filters[$k][$i])) : '';
					}
				}
			}
		}
		return $advanced;
	}
	/**
	 * Build an array of html data that gets inserted into the advanced search popup view
	 *
	 * @return  array	html lists/fields
	 */

	public function getAdvancedSearchRows()
	{
		if (isset($this->advancedSearchRows))
		{
			return $this->advancedSearchRows;
		}
		$statements = $this->getStatementsOpts();
		$rows = array();
		$first = false;
		$elementModels = $this->getElements();
		list($fieldNames, $firstFilter) = $this->getAdvancedSearchElementList();
		$prefix = 'fabrik___filter[list_' . $this->getRenderContext() . '][';
		$type = '<input type="hidden" name="' . $prefix . 'search_type][]" value="advanced" />';
		$grouped = '<input type="hidden" name="' . $prefix . 'grouped_to_previous][]" value="0" />';

		$filters = $this->getAdvancedFilterValues();
		$counter = 0;
		if (array_key_exists('key', $filters))
		{
			foreach ($filters['key'] as $key)
			{
				foreach ($elementModels as $elementModel)
				{
					$testkey = FabrikString::safeColName($elementModel->getFullName(false, false, false));
					if ($testkey == $key)
					{
						break;
					}
				}
				$join = $filters['join'][$counter];

				$condition = $filters['condition'][$counter];
				$value = $filters['origvalue'][$counter];
				$v2 = $filters['value'][$counter];
				$jsSel = '=';
				switch ($condition)
				{
					case "<>":
						$jsSel = '<>';
						break;
					case "=":
						$jsSel = 'EQUALS';
						break;
					case "<":
						$jsSel = '<';
						break;
					case ">":
						$jsSel = '>';
						break;
					default:
						$firstChar = JString::substr($v2, 1, 1);
						$lastChar = JString::substr($v2, -2, 1);
						switch ($firstChar)
						{
							case "%":
								$jsSel = ($lastChar == "%") ? 'CONTAINS' : $jsSel = 'ENDS WITH';
								break;
							default:
								if ($lastChar == "%")
								{
									$jsSel = 'BEGINS WITH';
								}
								break;
						}
						break;
				}

				$value = trim(trim($value, '"'), "%");
				if ($counter == 0)
				{
					$join = JText::_('COM_FABRIK_WHERE') . '<input type="hidden" value="WHERE" name="' . $prefix . 'join][]" />';
				}
				else
				{
					$join = FabrikHelperHTML::conditonList($this->getRenderContext(), $join);
				}

				$lineElname = FabrikString::safeColName($elementModel->getFullName(false, true, false));
				$orig = JRequest::getVar($lineElname);
				JRequest::setVar($lineElname, array('value' => $value));
				$filter = $elementModel->getFilter($counter, false);
				JRequest::setVar($lineElname, $orig);
				$key = JHTML::_('select.genericlist', $fieldNames, $prefix . 'key][]', 'class="inputbox key" size="1" ', 'value', 'text', $key);
				$jsSel = JHTML::_('select.genericlist', $statements, $prefix . 'condition][]', 'class="inputbox" size="1" ', 'value', 'text', $jsSel);
				$rows[] = array('join' => $join, 'element' => $key, 'condition' => $jsSel, 'filter' => $filter, 'type' => $type,
						'grouped' => $grouped);
				$counter++;
			}
		}

		if ($counter == 0)
		{
			$join = JText::_('COM_FABRIK_WHERE') . '<input type="hidden" name="' . $prefix . 'join][]" value="WHERE" />';
			$key = JHTML::_('select.genericlist', $fieldNames, $prefix . 'key][]', 'class="inputbox key" size="1" ', 'value', 'text', '');
			$jsSel = JHTML::_('select.genericlist', $statements, $prefix . 'condition][]', 'class="inputbox" size="1" ', 'value', 'text', '');
			$rows[] = array('join' => $join, 'element' => $key, 'condition' => $jsSel, 'filter' => $firstFilter, 'type' => $type,
					'grouped' => $grouped);
		}
		$this->advancedSearchRows = $rows;
		return $rows;
	}

	/**
	 * Fet the headings that should be shown in the csv export file
	 *
	 * @param   array  $headings  to use (key is element name value must be 1 for it to be added)
	 *
	 * @return  void
	 */

	function setHeadingsForCSV($headings)
	{
		$asfields = $this->getAsFields();
		$newfields = array();
		$db = $this->getDb();
		$this->_temp_db_key_addded = false;
		/* $$$ rob if no fields specified presume we are requesting CSV file from URL and return
		 * all fields otherwise set the fields to be those selected in fabrik window
		* or defined in the lists csv export settings
		*/
		if (!empty($headings))
		{
			foreach ($headings as $name => $val)
			{
				if ($val != 1)
				{
					continue;
				}
				$elModel = $this->getFormModel()->getElement($name);
				if (is_object($elModel))
				{
					$name = $elModel->getFullName(false, true, false);
					$pName = $elModel->isJoin() ? $db->quoteName($elModel->getJoinModel()->getJoin()->table_join . '___params') : '';
					foreach ($asfields as $f)
					{
						if ((strstr($f, $db->quoteName($name)) || strstr($f, $db->quoteName($name . '_raw'))
							|| ($elModel->isJoin() && strstr($f, $pName))))
						{
							$newfields[] = $f;
						}
					}

				}
			}
			$this->asfields = $newfields;
		}
	}

	/**
	 * returns the table headings, seperated from writetable function as
	 * when group_by is selected mutliple tables are written
	 * 09/07/2011 moved headingClass into arry rather than string
	 *
	 * @return  array  (table headings, array columns, $aLinkElements)
	 */

	public function getHeadings()
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$item = $this->getTable();
		$item->order_dir = JString::strtolower($item->order_dir);
		$aTableHeadings = array();
		$headingClass = array();
		$cellClass = array();
		$params = $this->getParams();

		$w = new FabrikWorker;
		$session = JFactory::getSession();
		$formModel = $this->getFormModel();
		//$linksToForms = $this->getLinksToThisKey();
		$oldLinksToForms = $this->getLinksToThisKey();
		$linksToForms = array();
		foreach ($oldLinksToForms as $join)
		{
			// $$$ hugh - anoher issue with getLinksTothisKey() now returning false for some joins.
			if ($join)
			{
				$k = $join->list_id . '-' . $join->form_id . '-' . $join->element_id;
				$linksToForms[$k] = $join;
			}
		}
		$groups = $formModel->getGroupsHiarachy();
		$groupHeadings = array();

		$orderbys = json_decode($item->order_by, true);
		$listels = json_decode($params->get('list_elements'));

		$showInList = array();
		$listels = json_decode(FabrikWorker::getMenuOrRequestVar('list_elements', '', $this->isMambot));

		// $$$ rob check if empty or if a single empty value was set in the menu/module params
		if (isset($listels->show_in_list) && !(count($listels->show_in_list) === 1 && $listels->show_in_list[0] == ''))
		{
			$showInList = $listels->show_in_list;
		}
		$showInList = (array) JRequest::getVar('fabrik_show_in_list', $showInList);

		// Set it for use by groupModel->getPublishedListElements()
		JRequest::setVar('fabrik_show_in_list', $showInList);

		if (!in_array($this->outPutFormat, array('pdf', 'csv')))
		{
			if ($this->canSelectRows() && $params->get('checkboxLocation', 'end') !== 'end')
			{
				$this->addCheckBox($aTableHeadings, $headingClass, $cellClass);
			}
			if ($params->get('checkboxLocation', 'end') !== 'end')
			{
				$this->actionHeading($aTableHeadings, $headingClass, $cellClass);
			}
		}

		foreach ($groups as $groupModel)
		{
			$groupHeadingKey = $w->parseMessageForPlaceHolder($groupModel->getGroup()->label, array(), false);
			$groupHeadings[$groupHeadingKey] = 0;
			$elementModels = $groupModel->getPublishedListElements();

			if ($groupModel->canView() === false)
			{
				continue;
			}
			foreach ($elementModels as $key => $elementModel)
			{
				$element = $elementModel->getElement();

				// If we define the elements to show in the list - e.g in admin list module then only show those elements
				if (!empty($showInList) && !in_array($element->id, $showInList))
				{
					continue;
				}
				$viewLinkAdded = false;
				$groupHeadings[$groupHeadingKey]++;
				$key = $elementModel->getFullName(false, true, false);
				$compsitKey = !empty($showInList) ? array_search($element->id, $showInList) . ':' . $key : $key;
				$orderKey = $elementModel->getOrderbyFullName(false, false);
				$elementParams = $elementModel->getParams();
				$label = $elementParams->get('alt_list_heading');
				if ($label == '')
				{
					$label = $element->label;
				}
				$label = $w->parseMessageForPlaceHolder($label, array());
				if ($elementParams->get('can_order') == '1' && $this->outPutFormat != 'csv')
				{
					$context = 'com_' . $package . '.list' . $this->getRenderContext() . '.order.' . $element->id;
					$orderDir = $session->get($context);
					$class = "";
					$currentOrderDir = $orderDir;
					$tmpl = $this->getTmpl();
					switch ($orderDir)
					{
						case "desc":
							$orderDir = "-";
							$class = 'class="fabrikorder-desc"';
							$img = FabrikHelperHTML::image('orderdesc.png', 'list', $tmpl, array('alt' => JText::_('COM_FABRIK_ORDER')));
							break;
						case "asc":
							$orderDir = "desc";
							$class = 'class="fabrikorder-asc"';
							$img = FabrikHelperHTML::image('orderasc.png', 'list', $tmpl, array('alt' => JText::_('COM_FABRIK_ORDER')));
							break;
						case "":
						case "-":
							$orderDir = "asc";
							$class = 'class="fabrikorder"';
							$img = FabrikHelperHTML::image('ordernone.png', 'list', $tmpl, array('alt' => JText::_('COM_FABRIK_ORDER')));
							break;
					}

					if ($class === '')
					{
						if (in_array($key, $orderbys))
						{
							if ($item->order_dir === 'desc')
							{
								$class = 'class="fabrikorder-desc"';
								$img = FabrikHelperHTML::image('orderdesc.png', 'list', $tmpl, array('alt' => JText::_('COM_FABRIK_ORDER')));
							}
						}
					}

					$heading = '<a ' . $class . ' href="#">' . $img . $label . '</a>';
				}
				else
				{
					$heading = $label;
				}
				$aTableHeadings[$compsitKey] = $heading;

				$headingClass[$compsitKey] = array('class' => $elementModel->getHeadingClass(), 'style' => $elementParams->get('tablecss_header'));
				$cellClass[$compsitKey] = array('class' => $elementModel->getCellClass(), 'style' => $elementParams->get('tablecss_cell'));

			}
			if ($groupHeadings[$groupHeadingKey] == 0)
			{
				unset($groupHeadings[$groupHeadingKey]);
			}
		}
		if (!empty($showInList))
		{
			$aTableHeadings = $this->removeHeadingCompositKey($aTableHeadings);
			$headingClass = $this->removeHeadingCompositKey($headingClass);
			$cellClass = $this->removeHeadingCompositKey($cellClass);
		}
		if (!in_array($this->outPutFormat, array('pdf', 'csv')))
		{
			// @TODO check if any plugins need to use the selector as well!
			if ($this->canSelectRows() && $params->get('checkboxLocation', 'end') === 'end')
			{
				$this->addCheckBox($aTableHeadings, $headingClass, $cellClass);
			}
			$viewLinkAdded = false;

			// If no elements linking to the edit form add in a edit column (only if we have the right to edit/view of course!)
			if ($params->get('checkboxLocation', 'end') === 'end')
			{
				$this->actionHeading($aTableHeadings, $headingClass, $cellClass);
			}
			// Create columns containing links which point to lists associated with this list
			$factedlinks = $params->get('factedlinks');
			$joinsToThisKey = $this->getJoinsToThisKey();
			$f = 0;
			foreach ($joinsToThisKey as $join)
			{
				if ($join === false)
				{
					continue;
				}
				$key = $join->list_id . '-' . $join->form_id . '-' . $join->element_id;
				if (is_object($join) && isset($factedlinks->linkedlist->$key))
				{
					$linkedTable = $factedlinks->linkedlist->$key;
					$heading = $factedlinks->linkedlistheader->$key;
					if ($linkedTable != '0')
					{
						$prefix = $join->element_id . '___' . $linkedTable;
						$aTableHeadings[$prefix . "_list_heading"] = empty($heading) ? $join->listlabel . ' ' . JText::_('COM_FABRIK_LIST') : $heading;
						$headingClass[$prefix . "_list_heading"] = array('class' => 'fabrik_ordercell ' . $prefix . '_list_heading related',
								'style' => '');
						$cellClass[$prefix . "_list_heading"] = array('class' => $prefix . '_list_heading fabrik_element related');
					}
				}
				$f++;
			}

			$f = 0;
			foreach ($linksToForms as $key => $join)
			{
				if ($join === false)
				{
					continue;
				}
				$linkedForm = $factedlinks->linkedform->$key;
				if ($linkedForm != '0')
				{
					$heading = $factedlinks->linkedformheader->$key;
					$prefix = $join->db_table_name . '___' . $join->name;
					$aTableHeadings[$prefix . '_form_heading'] = empty($heading) ? $join->listlabel . ' ' . JText::_('COM_FABRIK_FORM') : $heading;
					$headingClass[$prefix . '_form_heading'] = array('class' => 'fabrik_ordercell ' . $prefix . '_form_heading related',
							'style' => '');
					$cellClass[$prefix . '_form_heading'] = array('class' => $prefix . '_form_heading fabrik_element related');
				}
				$f++;
			}
		}
		if ($this->canSelectRows())
		{
			$groupHeadings[''] = '';
		}

		$args['tableHeadings'] = $aTableHeadings;
		$args['groupHeadings'] = $groupHeadings;
		$args['headingClass'] = $headingClass;
		$args['cellClass'] = $cellClass;
		FabrikWorker::getPluginManager()->runPlugins('onGetPluginRowHeadings', $this, 'list', $args);
		return array($aTableHeadings, $groupHeadings, $headingClass, $cellClass);
	}

	/**
	 * Put the actions in the headings array - separated to here to enable it to be added at the end or beginning
	 *
	 * @param   array  &$aTableHeadings  Table headings
	 * @param   array  &$headingClass    Heading classes
	 * @param   array  &$cellClass       Cell classes
	 *
	 * @return  void
	 */

	protected function actionHeading(&$aTableHeadings, &$headingClass, &$cellClass)
	{
		$params = $this->getParams();

		// Check for conditions in https://github.com/Fabrik/fabrik/issues/621
		$details = $this->canViewDetails();
		if ($params->get('detaillink') == 0)
		{
			$details = false;
		}
		$edit = $this->canEdit();
		if ($params->get('editlink') == 0)
		{
			$edit = false;
		}
		if ($this->canSelectRows() || $details || $edit)
		{
			// 3.0 actions now go in one column
			$pluginManager = FabrikWorker::getPluginManager();
			$headingButtons = array();
			if ($this->deletePossible())
			{
				$headingButtons[] = $this->deleteButton();
			}
			$return = $pluginManager->runPlugins('button', $this, 'list');
			$res = $pluginManager->_data;
			foreach ($res as &$r)
			{
				$r = '<li>' . $r . '</li>';
			}

			$headingButtons = array_merge($headingButtons, $res);

			$aTableHeadings['fabrik_actions'] = empty($headingButtons) ? '' : '<ul class="fabrik_action">' . implode("\n", $headingButtons) . '</ul>';
			$headingClass['fabrik_actions'] = array('class' => 'fabrik_ordercell fabrik_actions', 'style' => '');

			// Needed for ajax filter/nav
			$cellClass['fabrik_actions'] = array('class' => 'fabrik_actions fabrik_element');
		}
	}

	/**
	 * Put the checkbox in the headings array - separated to here to enable it to be added at the end or beginning
	 *
	 * @param   array  &$aTableHeadings  table headings
	 * @param   array  &$headingClass    heading classes
	 * @param   array  &$cellClass       cell classes
	 *
	 * @return  void
	 */

	protected function addCheckBox(&$aTableHeadings, &$headingClass, &$cellClass)
	{
		$select = '<input type="checkbox" name="checkAll" class="list_' . $this->getId() . '_checkAll" />';
		$aTableHeadings['fabrik_select'] = $select;
		$headingClass['fabrik_select'] = array('class' => 'fabrik_ordercell fabrik_select', 'style' => '');

		// Needed for ajax filter/nav
		$cellClass['fabrik_select'] = array('class' => 'fabrik_select fabrik_element');
	}

	/**
	 * Enter description here ...
	 *
	 * @param   array  $arr  array
	 *
	 * @return  array
	 */

	protected function removeHeadingCompositKey($arr)
	{
		/* $$$ hugh - horrible hack, but if we just ksort as-is, once we have more than 9 elements,
		 * it'll start sort 0,1,10,11,2,3 etc.  There's no doubt a cleaner way to do this,
		* but for now ... rekey with a 0 padded prefix before we ksort
		*/
		foreach ($arr as $key => $val)
		{
			if (strstr($key, ':'))
			{
				list($part1, $part2) = explode(':', $key);
				$part1 = sprintf('%03d', $part1);
				$newkey = $part1 . ':' . $part2;
				$arr[$newkey] = $arr[$key];
				unset($arr[$key]);
			}
		}
		ksort($arr);
		foreach ($arr as $key => $val)
		{
			if (strstr($key, ':'))
			{
				$bits = explode(':', $key);
				$newkey = array_pop($bits);
				$arr[$newkey] = $arr[$key];
				unset($arr[$key]);
			}
		}
		return $arr;
	}

	/**
	 * Can the user select the specified row
	 *
	 * Needs to return true to insert a checkbox in the row.
	 *
	 * @param   object  $row  row of list data
	 *
	 * @return  bool
	 */

	public function canSelectRow($row)
	{
		$canSelect = FabrikWorker::getPluginManager()->runPlugins('onCanSelectRow', $this, 'list', $row);
		if (in_array(false, $canSelect))
		{
			return false;
		}
		if ($this->canDelete($row))
		{
			$this->canSelectRows = true;
			return true;
		}
		$params = $this->getParams();
		$actionMethod = $this->actionMethod();
		if ($actionMethod == 'floating' && ($this->canEdit($row) || $this->canViewDetails($row)))
		{
			return true;
		}
		$usedPlugins = (array) $params->get('plugins');
		if (empty($usedPlugins))
		{
			return false;
		}
		$pluginManager = FabrikWorker::getPluginManager();
		$listplugins = $pluginManager->getPlugInGroup('list');
		$v = in_array(true, $pluginManager->runPlugins('canSelectRows', $this, 'list'));
		if ($v)
		{
			$this->canSelectRows = true;
		}
		return $v;
	}

	/**
	 * Can the user select ANY row?
	 *
	 * Should the checkbox be shown in the list
	 * If you can delete then true returned, if not then check
	 * available list plugins to see if they allow for row selection
	 * if so a checkbox column appears in the table
	 *
	 * @return  bool
	 */

	public function canSelectRows()
	{
		if (!is_null($this->canSelectRows))
		{
			return $this->canSelectRows;
		}
		$actionMethod = $this->actionMethod();
		if ($this->canDelete() || ($this->canEditARow() && $actionMethod === 'floating') || $this->deletePossible())
		{
			$this->canSelectRows = true;
			return $this->canSelectRows;
		}
		$params = $this->getParams();
		if ($actionMethod == 'floating' && ($this->canEdit() || $this->canViewDetails()))
		{
			$this->canSelectRows = true;
			return true;
		}
		$usedPlugins = (array) $params->get('plugins');
		if (empty($usedPlugins))
		{
			$this->canSelectRows = false;
			return $this->canSelectRows;
		}
		$pluginManager = FabrikWorker::getPluginManager();
		$pluginManager->getPlugInGroup('list');
		$this->canSelectRows = in_array(true, $pluginManager->runPlugins('canSelectRows', $this, 'list'));
		return $this->canSelectRows;
	}

	/**
	 * Clear the calculations
	 *
	 * @return  void
	 */

	public function clearCalculations()
	{
		unset($this->_aRunCalculations);
	}
	/**
	 * return mathematical column calculations (run at doCalculations() on for submission)
	 *
	 * @return  array  calculations
	 */

	function getCalculations()
	{
		if (!empty($this->_aRunCalculations))
		{
			return $this->_aRunCalculations;
		}
		$user = JFactory::getUser();
		$aclGroups = $user->authorisedLevels();
		$aCalculations = array();
		$formModel = $this->getFormModel();
		$aAvgs = array();
		$aSums = array();
		$aMedians = array();
		$aCounts = array();
		$aCustoms = array();
		$groups = $formModel->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$params = $elementModel->getParams();
				$elName = $elementModel->getFullName(false, true, false);
				$sumOn = $params->get('sum_on', '0');
				$avgOn = $params->get('avg_on', '0');
				$medianOn = $params->get('median_on', '0');
				$countOn = $params->get('count_on', '0');
				$customOn = $params->get('custom_calc_on', '0');
				$sumAccess = $params->get('sum_access', 0);
				$avgAccess = $params->get('avg_access', 0);
				$medianAccess = $params->get('median_access', 0);
				$countAccess = $params->get('count_access', 0);
				$customAccess = $params->get('custom_calc_access', 0);
				if ($sumOn && in_array($sumAccess, $aclGroups) && $params->get('sum_value', '') != '')
				{
					$aSums[$elName] = $params->get('sum_value', '');
					$ser = $params->get('sum_value_serialized');
					if (is_string($ser))
					{
						// If group gone from repeat to none repeat could be array
						$aSums[$elName . '_obj'] = unserialize($ser);
					}
				}
				if ($avgOn && in_array($avgAccess, $aclGroups) && $params->get('avg_value', '') != '')
				{
					$aAvgs[$elName] = $params->get('avg_value', '');
					$ser = $params->get('avg_value_serialized');
					if (is_string($ser))
					{
						$aAvgs[$elName . '_obj'] = unserialize($ser);
					}
				}
				if ($medianOn && in_array($medianAccess, $aclGroups) && $params->get('median_value', '') != '')
				{
					$aMedians[$elName] = $params->get('median_value', '');
					$ser = $params->get('median_value_serialized', '');
					if (is_string($ser))
					{
						$aMedians[$elName . '_obj'] = unserialize($ser);
					}
				}
				if ($countOn && in_array($countAccess, $aclGroups) && $params->get('count_value', '') != '')
				{
					$aCounts[$elName] = $params->get('count_value', '');
					$ser = $params->get('count_value_serialized');
					if (is_string($ser))
					{
						$aCounts[$elName . '_obj'] = unserialize($ser);
					}
				}

				if ($customOn && in_array($customAccess, $aclGroups) && $params->get('custom_calc_value', '') != '')
				{
					$aCustoms[$elName] = $params->get('custom_calc_value', '');
					$ser = $params->get('custom_calc_value_serialized');
					if (is_string($ser))
					{
						$aCounts[$elName . '_obj'] = unserialize($ser);
					}
				}
			}
		}
		$aCalculations['sums'] = $aSums;
		$aCalculations['avgs'] = $aAvgs;
		$aCalculations['medians'] = $aMedians;
		$aCalculations['count'] = $aCounts;
		$aCalculations['custom_calc'] = $aCustoms;
		$this->_aRunCalculations = $aCalculations;
		return $aCalculations;
	}

	/**
	 * Get list headings to pass into list js oject
	 *
	 * @return  string	headings tablename___name
	 */

	public function _jsonHeadings()
	{
		$aHeadings = array();
		$table = $this->getTable();
		$formModel = $this->getFormModel();
		$groups = $formModel->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();
				if ($element->show_in_list_summary)
				{
					$aHeadings[] = $table->db_table_name . '___' . $element->name;
				}
			}
		}
		return "['" . implode("','", $aHeadings) . "']";
	}

	/**
	 * When form saved (and set to record in database)
	 * this is run to see if there is any table join data,
	 * if there is it stores it in $this->_joinsToProcess
	 *
	 * @return  array	[joinid] = array(join => $join, 'groups' => array, 'elements' => array element models)
	 */

	public function preProcessJoin()
	{
		if (!isset($this->_joinsToProcess))
		{
			$this->_joinsToProcess = array();
			$formModel = $this->getFormModel();
			$groups = $formModel->getGroupsHiarachy();
			foreach ($groups as $groupModel)
			{
				$group = $groupModel->getGroup();
				if ($groupModel->isJoin())
				{
					$joinModel = $groupModel->getJoinModel();
					$join = $joinModel->getJoin();
					if (!array_key_exists($join->id, $this->_joinsToProcess))
					{
						$this->_joinsToProcess[$join->id] = array('join' => $join, 'groups' => array($groupModel));
					}
					else
					{
						$this->_joinsToProcess[$join->id]['groups'][] = $groupModel;
					}
				}
				$elements = $groupModel->getPublishedElements();
				$c = count($elements);
				for ($x = 0; $x < $c; $x++)
				{
					$elementModel = $elements[$x];
					if ($elementModel->isJoin())
					{
						$joinModel = $elementModel->getJoinModel();
						$join = $joinModel->getJoin();
						if (!array_key_exists($join->id, $this->_joinsToProcess))
						{
							$this->_joinsToProcess[$join->element_id] = array('join' => $join, 'elements' => array($elementModel));
						}
						else
						{
							$this->_joinsToProcess[$join->element_id]['elements'][] = $elementModel;
						}
					}
				}
			}
		}
		return $this->_joinsToProcess;
	}

	/**
	 * Strip the table names from the front of the key
	 *
	 * @param   array   $data   data to strip
	 * @param   string  $split  string splitter ___ or .
	 *
	 * @return  array stripped data
	 */

	public function removeTableNameFromSaveData($data, $split = '___')
	{
		foreach ($data as $key => $val)
		{
			$akey = explode($split, $key);
			if (count($akey) > 1)
			{
				$newKey = $akey[1];
				unset($data[$key]);
			}
			else
			{
				$newKey = $akey[0];
			}
			$data[$newKey] = $val;
		}
		return $data;
	}

	/**
	 * Saves posted form data into a table
	 * data should be keyed on short name
	 *
	 * @param   array   $data            to save
	 * @param   int     $rowId           row id to edit/updated
	 * @param   bool    $isJoin          is the data being saved into a join table
	 * @param   object  $joinGroupTable  joined group table
	 *
	 * @return  bool	true if saved ok
	 */

	public function storeRow($data, $rowId, $isJoin = false, $joinGroupTable = null)
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$origRowId = $rowId;

		// Don't save a record if no data collected
		if ($isJoin && empty($data))
		{
			return;
		}
		$fabrikDb = $this->getDb();
		$table = $this->getTable();
		$formModel = $this->getFormModel();
		if ($isJoin)
		{
			$this->getFormGroupElementData();
		}
		$oRecord = new stdClass;
		$aBindData = array();
		$noRepeatFields = array();
		$c = 0;
		$groups = $formModel->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$group = $groupModel->getGroup();
			/*
			 * $$$rob this following if statement avoids this scenario from happening:
			* you have a form with joins to two other tables
			* each joined group has a field called 'password'
			* first group's password is set to password plugin, second to field
			* on update if no password entered for first field data should not be updated as recordInDatabase() return false
			* however, as we were iterating over all groups, the 2nd password field's data is used instead!
			* this if statement ensures we only look at the correct group
			*/
			if ($isJoin == false || $group->id == $joinGroupTable->id)
			{
				if (($isJoin && $groupModel->isJoin()) || (!$isJoin && !$groupModel->isJoin()))
				{
					$elementModels = $groupModel->getPublishedElements();
					foreach ($elementModels as $elementModel)
					{
						$element = $elementModel->getElement();
						$key = $element->name;
						$fullkey = $elementModel->getFullName(false, true, false);

						// For radio buttons and dropdowns otherwise nothing is stored for them??
						$postkey = array_key_exists($key . '_raw', $data) ? $key . '_raw' : $key;

						// @TODO similar check (but not quiet the same performed in formModel _removeIgnoredData() - should merge into one place
						if ($elementModel->recordInDatabase($data))
						{
							if (array_key_exists($key, $data) && !in_array($key, $noRepeatFields))
							{
								$noRepeatFields[] = $key;
								$lastKey = $key;
								$val = $elementModel->storeDatabaseFormat($data[$postkey], $data, $key);
								$elementModel->updateRowId($rowId);
								if (array_key_exists('fabrik_copy_from_table', $data))
								{
									$val = $elementModel->onCopyRow($val);
								}

								if (array_key_exists('Copy', $data))
								{
									$val = $elementModel->onSaveAsCopy($val);
								}

								// Test for backslashed quotes
								if (get_magic_quotes_gpc())
								{
									if (!$elementModel->isUpload())
									{
										$val = stripslashes($val);
									}
								}
								if (!$elementModel->dataIsNull($data, $val))
								{
									$oRecord->$key = $val;
									$aBindData[$key] = $val;
								}

								if ($elementModel->isJoin() && $isJoin && array_key_exists('params', $data))
								{
									// Add in params object set by element plugin - eg fileupload element rotation/scale
									$oRecord->params = JArrayHelper::getValue($data, 'params');
									$aBindData[$key] = $oRecord->params;
								}
								$c++;
							}
						}
					}
				}
			}
		}

		$primaryKey = FabrikString::shortColName($this->getTable()->db_primary_key);

		if ($rowId != '' && $c == 1 && $lastKey == $primaryKey)
		{
			return;
		}
		/*
		 * $$$ rob - correct rowid is now inserted into the form's rowid hidden field
		* even when useing usekey and -1, we just need to check if we are adding a new record and if so set rowid to 0
		*/
		if (JRequest::getVar('usekey_newrecord', false))
		{
			$rowId = 0;
			$origRowId = 0;
		}

		$primaryKey = str_replace("`", "", $primaryKey);

		// $$$ hugh - if we do this, CSV importing can't maintain existing keys
		if (!$this->importingCSV)
		{
			// If its a repeat group which is also the primary group $primaryKey was not set.
			if ($primaryKey)
			{
				if (isset($oRecord->$primaryKey) && is_numeric($oRecord->$primaryKey))
				{
					$oRecord->$primaryKey = $rowId;
				}
			}
		}
		if ($origRowId == '' || $origRowId == 0)
		{
			// $$$ rob added test for auto_inc as sugarid key is set from storeDatabaseFormat() and needs to be maintained
			// $$$ rob don't do this when importing via CSV as we want to maintain existing keys (hence check on task var
			if (($primaryKey !== '' && $this->getTable()->auto_inc == true) && JRequest::getCmd('task') !== 'doImport')
			{
				unset($oRecord->$primaryKey);
			}
			$ok = $this->insertObject($table->db_table_name, $oRecord, $primaryKey, false);
		}
		else
		{
			$ok = $this->updateObject($table->db_table_name, $oRecord, $primaryKey, true);
		}
		$this->_tmpSQL = $fabrikDb->getQuery();
		if (!$ok)
		{
			$q = JDEBUG ? $fabrikDb->getQuery() : '';
			return JError::raiseWarning(500, 'Store row failed: ' . $q . "<br>" . $fabrikDb->getErrorMsg());
		}
		else
		{
			// Clean the cache.
			JFactory::getCache('com_' . $package)->clean();

			// $$$ rob new as if you update a record the insertid() returns 0
			$this->lastInsertId = ($rowId == '' || $rowId == 0) ? $fabrikDb->insertid() : $rowId;
			return true;
		}
	}

	/**
	 * hack! copied from mysqli db driver to enable AES_ENCRYPT calls
	 *
	 * @param   string  $table        table name
	 * @param   object  &$object      update object
	 * @param   string  $keyName      name of pk field
	 * @param   bool    $updateNulls  update null values
	 *
	 * @return  mixed  query result
	 */

	function updateObject($table, &$object, $keyName, $updateNulls = true)
	{
		$db = $this->getDb();
		$secret = JFactory::getConfig()->get('secret');
		$fmtsql = 'UPDATE ' . $db->quoteName($table) . ' SET %s WHERE %s';
		$tmp = array();
		foreach (get_object_vars($object) as $k => $v)
		{
			if (is_array($v) or is_object($v) or $k[0] == '_')
			{
				// Internal or NA field
				continue;
			}
			if ($k == $keyName)
			{
				// PK not to be updated
				$where = $keyName . '=' . $db->quote($v);
				continue;
			}
			if ($v === null)
			{
				if ($updateNulls)
				{
					$val = 'NULL';
				}
				else
				{
					continue;
				}
			}
			else
			{
				$val = $db->isQuoted($k) ? $db->quote($v) : (int) $v;
			}
			if (in_array($k, $this->encrypt))
			{
				$val = "AES_ENCRYPT($val, '$secret')";
			}
			$tmp[] = $db->quoteName($k) . '=' . $val;
		}
		$db->setQuery(sprintf($fmtsql, implode(",", $tmp), $where));
		if (!$db->query())
		{
			throw new Exception($db->getErrorMsg());
			return false;
		}
		return true;
	}

	/**
	 * Hack! copied from mysqli db driver to enable AES_ENCRYPT calls
	 * Inserts a row into a table based on an objects properties
	 *
	 * @param   string  $table    The name of the table
	 * @param   object  &$object  An object whose properties match table fields
	 * @param   string  $keyName  The name of the primary key. If provided the object property is updated.
	 *
	 * @return  bool
	 */

	public function insertObject($table, &$object, $keyName = null)
	{
		$db = $this->getDb();
		$secret = JFactory::getConfig()->get('secret');
		$fmtsql = 'INSERT INTO ' . $db->quoteName($table) . ' ( %s ) VALUES ( %s ) ';
		$fields = array();
		$values = array();
		foreach (get_object_vars($object) as $k => $v)
		{
			if (is_array($v) or is_object($v) or $v === null)
			{
				continue;
			}
			if ($k[0] == '_')
			{
				// Internal field
				continue;
			}
			$fields[] = $db->quoteName($k);
			$val = $db->isQuoted($k) ? $db->quote($v) : (int) $v;
			if (in_array($k, $this->encrypt))
			{
				$val = "AES_ENCRYPT($val, '$secret')";
			}
			$values[] = $val;
		}
		$db->setQuery(sprintf($fmtsql, implode(",", $fields), implode(",", $values)));
		if (!$db->query())
		{
			throw new Exception($db->getErrorMsg());
			return false;
		}
		$id = $db->insertid();
		if ($keyName && $id)
		{
			$object->$keyName = $id;
		}
		return true;
	}

	/**
	 * If an element is set to readonly, and has a default value selected then insert this
	 * data into the array that is to be bound to the table record
	 *
	 * @param   array   &$data           list data
	 * @param   object  &$oRecord        to bind to table row
	 * @param   int     $isJoin          is record join record
	 * @param   int     $rowid           row id
	 * @param   object  $joinGroupTable  join group table
	 *
	 * @since	1.0.6
	 *
	 * @deprecated  since 3.0.7 - we should be using formmodel addEncrytedVarsToArray() only
	 *
	 * @return  void
	 */

	function _addDefaultDataFromRO(&$data, &$oRecord, $isJoin, $rowid, $joinGroupTable)
	{
		jimport('joomla.utilities.simplecrypt');

		// $$$ rob since 1.0.6 : 10 June 08
		// Get the current record - not that which was posted
		$formModel = $this->getFormModel();
		$table = $this->getTable();
		if (is_null($this->_origData))
		{
			/* $$$ hugh FIXME - doesn't work for rowid=-1 / usekey submissions,
			 * ends up querying "WHERE foo.userid = '<rowid>'" instead of <userid>
			* OK for now, as we should catch RO data from the encrypted vars check
			* later in this method.
			*/
			if (empty($rowid))
			{
				$this->_origData = $origdata = array();
			}
			else
			{
				$sql = $formModel->_buildQuery();
				$db = $this->getDb();
				$db->setQuery($sql);
				$origdata = $db->loadObject();
				$origdata = JArrayHelper::fromObject($origdata);
				$origdata = is_array($origdata) ? $origdata : array();
				$this->_origData = $origdata;
			}
		}
		else
		{
			$origdata = $this->_origData;
		}
		$form = $formModel->getForm();
		$groups = $formModel->getGroupsHiarachy();

		/* $$$ hugh - seems like there's no point in doing this chunk if there is no
		 $origdata to work with?  Not sure if there's ever a valid reason for doing so,
		but it certainly breaks things like onCopyRow(), where (for instance) user
		elements will get reset to 0 by this code.
		*/
		$repeatGroupCounts = JRequest::getVar('fabrik_repeat_group', array());
		if (!empty($origdata))
		{
			$gcounter = 0;
			foreach ($groups as $groupModel)
			{
				if (($isJoin && $groupModel->isJoin()) || (!$isJoin && !$groupModel->isJoin()))
				{
					$elementModels = $groupModel->getPublishedElements();
					foreach ($elementModels as $elementModel)
					{
						// $$$ rob 25/02/2011 unviewable elements are now also being encrypted
						// if (!$elementModel->canUse() && $elementModel->canView()) {
						if (!$elementModel->canUse())
						{
							$element = $elementModel->getElement();
							$fullkey = $elementModel->getFullName(false, true, false);

							// $$$ rob 24/01/2012 if a previous joined data set had a ro element then if we werent checkign that group is the
							// same as the join group then the insert failed as data from other joins added into the current join
							if ($isJoin && ($groupModel->getId() != $joinGroupTable->id))
							{
								continue;
							}
							$key = $element->name;

							// $$$ hugh - allow submission plugins to override RO data
							// TODO - test this for joined data
							if ($formModel->updatedByPlugin($fullkey))
							{
								continue;
							}
							// Force a reload of the default value with $origdata
							unset($elementModel->defaults);
							$default = array();
							$repeatGroupCount = JArrayHelper::getValue($repeatGroupCounts, $groupModel->getGroup()->id);
							for ($repeatCount = 0; $repeatCount < $repeatGroupCount; $repeatCount++)
							{
								$def = $elementModel->getValue($origdata, $repeatCount);
								if (is_array($def))
								{
									// Radio buttons getValue() returns an array already so don't array the array.
									$default = $def;
								}
								else
								{
									$default[] = $def;
								}
							}
							$default = count($default) == 1 ? $default[0] : json_encode($default);
							$data[$key] = $default;
							$oRecord->$key = $default;
						}
					}
				}
				$gcounter++;
			}
		}
		$copy = JRequest::getBool('Copy');

		// Check crypted querystring vars (encrypted in form/view.html.php ) _cryptQueryString
		if (array_key_exists('fabrik_vars', $_REQUEST) && array_key_exists('querystring', $_REQUEST['fabrik_vars']))
		{
			$crypt = new JSimpleCrypt;
			foreach ($_REQUEST['fabrik_vars']['querystring'] as $key => $encrypted)
			{

				// $$$ hugh - allow submission plugins to override RO data
				// TODO - test this for joined data
				if ($formModel->updatedByPlugin($key))
				{
					continue;
				}
				$key = FabrikString::shortColName($key);

				/* $$$ hugh - trying to fix issue where encrypted elements from a main group end up being added to
				 * a joined group's field list for the update/insert on the joined row(s).
				*/
				/*
				 * $$$ rob - commenting it out as this was stopping data that was not viewable or editable from being included
				* in $data. New test added inside foreach loop below
				**/
				/* if (!array_key_exists($key, $data))
				 {
				continue;
				} */
				foreach ($groups as $groupModel)
				{
					// New test to replace if (!array_key_exists($key, $data))
					// $$$ hugh - this stops elements from joined groups being added to main row, but see 'else'
					if ($isJoin)
					{
						if ($groupModel->getGroup()->id != $joinGroupTable->id)
						{
							continue;
						}
					}
					else
					{
						// $$$ hugh - need test here if not $isJoin, to stop keys from joined groups being added to main row!
						if ($groupModel->isJoin())
						{
							continue;
						}
					}
					$elementModels = $groupModel->getPublishedElements();
					foreach ($elementModels as $elementModel)
					{
						$element = $elementModel->getElement();
						// $$$ hugh - I have a feeling this test is a Bad Thing <tm> as it is using short keys, so if two joined groups share the same element name(s) ...
						if ($element->name == $key)
						{
							// Don't overwrite if something has been entered

							// $$$ rob 25/02/2011 unviewable elements are now also being encrypted
							// if (!$elementModel->canUse() && $elementModel->canView()) {
							if (!$elementModel->canUse())
							{
								// Repeat groups
								$default = array();
								$repeatGroupCount = JArrayHelper::getValue($repeatGroupCounts, $groupModel->getGroup()->id);
								for ($repeatCount = 0; $repeatCount < $repeatGroupCount; $repeatCount++)
								{
									$enc = JArrayHelper::getValue($encrypted, $repeatCount);

									if (is_array($enc))
									{
										$v = array();
										foreach ($enc as $e)
										{
											$e = urldecode($e);
											$v[] = empty($e) ? '' : $crypt->decrypt($e);
										}
										$v = json_encode($v);
									}
									else
									{
										$enc = urldecode($enc);
										$v = !empty($enc) ? $crypt->decrypt($enc) : '';
									}

								}

								/* $$$ hugh - also gets called in storeRow(), not sure if we really need to
								 * call it here?  And if we do, then we should probably be calling onStoreRow
								* as well, if $data['fabrik_copy_from_table'] is set?  Can't remember why,
								* but we differentiate between the two, with onCopyRow being when a row is copied
								* using the list plugin, and onSaveAsCopy when the form plugin is used.
								*/
								if ($copy)
								{
									$v = $elementModel->onSaveAsCopy($v);
								}
								$data[$key] = $v;
								$oRecord->$key = $v;
							}
							break 2;
						}
					}
				}
			}
		}
	}

	/**
	 * Called when the form is submitted to perform calculations
	 *
	 * @return  void
	 */

	public function doCalculations()
	{
		$cache = FabrikWorker::getCache($this);
		$cache->call(array(get_class($this), 'cacheDoCalculations'), $this->getId());
	}

	/**
	 * Cache do calculations
	 *
	 * @param   int  $listId  List id
	 *
	 * @return  void
	 */

	public static function cacheDoCalculations($listId)
	{
		$listModel = JModel::getInstance('List', 'FabrikFEModel');
		$listModel->setId($listId);
		$db = FabrikWorker::getDbo();
		$formModel = $listModel->getFormModel();
		$groups = $formModel->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();
				$params = $elementModel->getParams();
				$update = false;
				if ($params->get('sum_on', 0) == 1)
				{
					$aSumCals = $elementModel->sum($listModel);
					$params->set('sum_value_serialized', serialize($aSumCals[1]));
					$params->set('sum_value', $aSumCals[0]);
					$update = true;
				}
				if ($params->get('avg_on', 0) == 1)
				{
					$aAvgCals = $elementModel->avg($listModel);
					$params->set('avg_value_serialized', serialize($aAvgCals[1]));
					$params->set('avg_value', $aAvgCals[0]);
					$update = true;
				}
				if ($params->get('median_on', 0) == 1)
				{
					$medians = $elementModel->median($listModel);
					$params->set('median_value_serialized', serialize($medians[1]));
					$params->set('median_value', $medians[0]);
					$update = true;
				}
				if ($params->get('count_on', 0) == 1)
				{
					$aCountCals = $elementModel->count($listModel);
					$params->set('count_value_serialized', serialize($aCountCals[1]));
					$params->set('count_value', $aCountCals[0]);
					$update = true;
				}
				if ($params->get('custom_calc_on', 0) == 1)
				{
					$aCustomCalcCals = $elementModel->custom_calc($listModel);
					$params->set('custom_calc_value_serialized', serialize($aCustomCalcCals[1]));
					$params->set('custom_calc_value', $aCustomCalcCals[0]);
					$update = true;
				}
				if ($update)
				{
					$elementModel->storeAttribs();
				}
			}
		}
	}

	/**
	 * Check to see if prefilter should be applied
	 *
	 * @param   int  $gid  group id to check against
	 *
	 * @return  bool	must apply filter
	 */

	protected function mustApplyFilter($gid)
	{
		return in_array($gid, JFactory::getUser()->authorisedLevels());
	}

	/**
	 * Set the connection id - used when creating a new table
	 *
	 * @param   int  $id  connection id
	 *
	 * @return  void
	 */

	public function setConnectionId($id)
	{
		$this->getTable()->connection_id = $id;
	}

	/**
	 * Get group by (can be set via qs group_by var)
	 *
	 * @return  string
	 */

	public function getGroupBy()
	{
		$elementModel = $this->getGroupByElement();
		if (!$elementModel)
		{
			return '';
		}
		return $elementModel->getFullName(false, true, false);
	}

	/**
	 * Get the element ids for list odering
	 *
	 * @since  3.0.7
	 *
	 * @return  array  element ids
	 */

	public function getOrderBys()
	{
		$item = $this->getTable();
		$orderBys = FabrikWorker::JSONtoData($item->order_by, true);
		$formModel = $this->getFormModel();
		foreach ($orderBys as &$orderBy)
		{
			$elementModel = $formModel->getElement($orderBy, true);
			$orderBy = $elementModel ? $elementModel->getId() : '';
		}
		return $orderBys;
	}

	/**
	 * Test if the main J user can create mySQL tables
	 *
	 * @return  bool
	 */

	function canCreateDbTable()
	{
		return true;
	}

	/**
	 * Make id element
	 *
	 * @param   int  $groupId  element group id
	 *
	 * @since Fabrik 3.0
	 *
	 * @return  void
	 */

	public function makeIdElement($groupId)
	{
		$pluginMananger = FabrikWorker::getPluginManager();
		$element = $pluginMananger->getPlugIn('internalid', 'element');
		$item = $element->getDefaultProperties();
		$item->name = $item->label = 'id';
		$item->group_id = $groupId;
		if (!$item->store())
		{
			JError::raiseWarning(500, $item->getError());
			return false;
		}
		return true;
	}

	/**
	 * Make foreign key element
	 *
	 * @param   int  $groupId  element group id
	 *
	 * @since   Fabrik 3.0
	 *
	 * @return void
	 */

	public function makeFkElement($groupId)
	{
		$pluginMananger = FabrikWorker::getPluginManager();
		$element = $pluginMananger->getPlugIn('field', 'element');
		$item = $element->getDefaultProperties();
		$item->name = $item->label = 'parent_id';
		$item->hidden = 1;
		$item->group_id = $groupId;
		if (!$item->store())
		{
			JError::raiseWarning(500, $item->getError());
			return false;
		}
		return true;
	}

	/**
	 * Updates the table record to point to the newly created form
	 *
	 * @param   int  $formId  form id
	 *
	 * @deprecated - not used
	 *
	 * @return  mixed  null/error
	 */

	function _updateFormId($formId)
	{
		$item = $this->getTable();
		$item->form_id = $formId;
		if (!$item->store())
		{
			return JError::raiseWarning(500, $item->getError());
		}
	}

	/**
	 * Get the tables primary key and if the primary key is auto increment
	 *
	 * @param   string  $table  optional table name (used when getting pk to joined tables
	 *
	 * @return  mixed	if ok returns array(key, extra, type, name) otherwise
	 */

	public function getPrimaryKeyAndExtra($table = null)
	{
		$origColNames = $this->getDBFields($table);
		$keys = array();
		$origColNamesByName = array();
		if (is_array($origColNames))
		{
			foreach ($origColNames as $origColName)
			{
				$colName = $origColName->Field;
				$key = $origColName->Key;
				$extra = $origColName->Extra;
				$type = $origColName->Type;
				if ($key == "PRI")
				{
					$keys[] = array("key" => $key, "extra" => $extra, "type" => $type, "colname" => $colName);
				}
				else
				{
					// $$$ hugh - if we never find a PRI, it may be a view, and we'll need this info in the Hail Mary.
					$origColnamesByName[$colName] = $origColName;
				}
			}
		}
		if (empty($keys))
		{
			// $$$ hugh - might be a view, so Hail Mary attempt to find it in our lists
			// $$$ So ... see if we know about it, and if so, fake out the PK details
			$db = FabrikWorker::getDbo(true);
			$query = $db->getQuery(true);
			$query->select('db_primary_key')->from('#__{package}_lists')->where('db_table_name = ' . $db->quote($table));
			$db->setQuery($query);
			$join_pk = $db->loadResult();
			if (!empty($join_pk))
			{
				$shortColName = FabrikString::shortColName($join_pk);
				$key = $origColName->Key;
				$extra = $origColName->Extra;
				$type = $origColName->Type;
				$keys[] = array('colname' => $shortColName, 'type' => $type, 'extra' => $extra, 'key' => $key);
			}
		}
		return empty($keys) ? false : $keys;
	}

	/**
	 * Run the prefilter sql and replace any placeholders in the subsequent prefilter
	 *
	 * @param   mixed  $selValue  string/array prefilter value
	 *
	 * @return  mixed  string/array prefilter value
	 */

	protected function _prefilterParse($selValue)
	{
		$isstring = false;
		if (is_string($selValue))
		{
			$isstring = true;
			$selValue = array($selValue);
		}
		$preSQL = htmlspecialchars_decode($this->getParams()->get('prefilter_query'), ENT_QUOTES);
		if (trim($preSQL) != '')
		{
			$db = FabrikWorker::getDbo();
			$w = new FabrikWorker;
			$w->replaceRequest($preSQL);
			$preSQL = $w->parseMessageForPlaceHolder($preSQL);
			$db->setQuery($preSQL);
			$q = $db->loadObjectList();
			if (!$q)
			{
				// Try the table's connection db for the query
				$thisDb = $this->getDb();
				$thisDb->setQuery($preSQL);
				$q = $thisDb->loadObjectList();
			}
			if (!empty($q))
			{
				$q = $q[0];
			}
		}
		if (isset($q))
		{
			foreach ($q as $key => $val)
			{
				if (substr($key, 0, 1) != '_')
				{
					$found = false;
					for ($i = 0; $i < count($selValue); $i++)
					{
						if (strstr($selValue[$i], '{$q-&gt;' . $key))
						{
							$found = true;
							$pattern = '{$q-&gt;' . $key . "}";
						}
						if (strstr($selValue[$i], '{$q->' . $key))
						{
							$found = true;
							$pattern = '{$q->' . $key . "}";
						}
						if ($found)
						{
							$selValue[$i] = str_replace($pattern, $val, $selValue[$i]);
						}
					}
				}
			}
		}
		else
		{
			/* Parse for default values only
			 * $$$ hugh - this pattern is being greedy, so for example ...
			 * foo {$my->id} bar {$my->id} gaprly
			 * ... matches everyting from first to last brace, like ...
			 * {$my->id} bar {$my->id}
			 *$pattern = "/({[^}]+}).*}?/s";
			 */
			$pattern = "/({[^}]+})/";
			for ($i = 0; $i < count($selValue); $i++)
			{
				$ok = preg_match($pattern, $selValue[$i], $matches);
				foreach ($matches as $match)
				{
					$matchx = JString::substr($match, 1, JString::strlen($match) - 2);

					// A default option was set so lets use that
					if (strstr($matchx, '|'))
					{
						$bits = explode('|', $matchx);
						$selValue[$i] = str_replace($match, $bits[1], $selValue[$i]);
					}
				}
			}
		}
		return $isstring ? $selValue[0] : $selValue;
	}

	/**
	 * Get the lists db table's indexes
	 *
	 * @return array  list indexes
	 */

	protected function getIndexes()
	{
		if (!isset($this->_indexes))
		{
			$db = $this->getDb();
			$db->setQuery("SHOW INDEXES FROM " . $this->getTable()->db_table_name);
			$this->_indexes = $db->loadObjectList();
		}
		return $this->_indexes;
	}

	/**
	 * Add an index to the table
	 *
	 * @param   string  $field   field name
	 * @param   string  $prefix  index name prefix (allows you to differentiate between indexes created in
	 * different parts of fabrik)
	 * @param   string  $type    index type
	 * @param   int     $size    index length
	 *
	 * @return void
	 */

	public function addIndex($field, $prefix = '', $type = 'INDEX', $size = '')
	{
		$indexes = $this->getIndexes();
		if (is_numeric($field))
		{
			$el = $this->getFormModel()->getElement($field, true);
			$field = $el->getFullName(false, true, false);
		}
		/* $$$ hugh - @TODO $field is in 'table.element' format but $indexes
		 * has Column_name as just 'element' ... so we're always rebuilding indexes!
		* I'm in the middle of fixing something else, must come back and fix this!!
		* OK, moved these two lines from below to here
		*/
		$field = str_replace('_raw', '', $field);

		// $$$ rob 29/03/2011 ensure its in tablename___elementname format
		$field = str_replace('.', '___', $field);

		// $$$ rob 28/02/2011 if index in joined table we need to use that the make the key on
		$table = !strstr($field, '___') ? $this->getTable()->db_table_name : array_shift(explode('___', $field));
		$field = FabrikString::shortColName($field);
		FArrayHelper::filter($indexes, 'Column_name', $field);
		if (!empty($indexes))
		{
			// An index already exists on that column name no need to add
			return;
		}
		$db = $this->getDb();
		if ($field == '')
		{
			return;
		}
		if ($size != '')
		{
			$size = '( ' . $size . ' )';
		}
		$this->dropIndex($field, $prefix, $type, $table);
		$query = " ALTER TABLE " . $db->quoteName($table) . " ADD INDEX " . $db->quoteName("fb_{$prefix}_{$field}_{$type}") . " ("
				. $db->quoteName($field) . " $size)";
		$db->setQuery($query);
		$db->query();
	}

	/**
	 * Drop an index
	 *
	 * @param   string  $field   field name
	 * @param   stirng  $prefix  index name prefix (allows you to differentiate between indexes created in
	 * different parts of fabrik)
	 * @param   string  $type    table name @since 29/03/2011
	 * @param   string  $table   db table name
	 *
	 * @return  string  index type
	 */

	public function dropIndex($field, $prefix = '', $type = 'INDEX', $table = '')
	{
		$db = $this->getDb();
		$table = $table == '' ? $this->getTable()->db_table_name : $table;
		$field = FabrikString::shortColName($field);
		if ($field == '')
		{
			return;
		}
		$db->setQuery("SHOW INDEX FROM " . $db->quoteName($table));
		$dbIndexes = $db->loadObjectList();
		if (is_array($dbIndexes))
		{
			foreach ($dbIndexes as $index)
			{
				if ($index->Key_name == "fb_{$prefix}_{$field}_{$type}")
				{
					$db->setQuery(" ALTER TABLE " . $db->quoteName($table) . " DROP INDEX " . $db->quoteName("fb_{$prefix}_{$field}_{$type}"));
					$db->query();
					break;
				}
			}
		}
	}

	/**
	 * Drop all indexes for a give element name
	 * required when encrypting text fileds whcih have a key on them , as blobs cant have keys
	 *
	 * @param   string  $field  field name to drop
	 * @param   string  $table  table to drop from
	 *
	 * @return  void
	 */

	public function dropColumnNameIndex($field, $table = '')
	{
		$db = $this->getDb();
		$table = $table == '' ? $this->getTable()->db_table_name : $table;
		$field = FabrikString::shortColName($field);
		if ($field == '')
		{
			return;
		}
		$db->setQuery("SHOW INDEX FROM " . $db->quoteName($table) . ' WHERE Column_name = ' . $db->quote($field));
		$dbIndexes = $db->loadObjectList();
		foreach ($dbIndexes as $index)
		{
			$db->setQuery(" ALTER TABLE " . $db->quoteName($table) . " DROP INDEX " . $db->quoteName($index->Key_name));
			$db->query();
		}
	}

	/**
	 * Delete joined records when deleting the main row
	 *
	 * @param   string  $val  quoted primary key values from the main table's rows that are to be deleted
	 *
	 * @return  void
	 */

	protected function deleteJoinedRows($val)
	{
		$db = $this->getDb();
		$params = $this->getParams();
		if ($params->get('delete-joined-rows', false))
		{
			$joins = $this->getJoins();
			for ($i = 0; $i < count($joins); $i++)
			{
				$join = $joins[$i];
				if ((int) $join->list_id !== 0)
				{
					$sql = "DELETE FROM " . $db->quoteName($join->table_join) . " WHERE " . $db->quoteName($join->table_join_key) . " IN (" . $val
					. ")";
					$db->setQuery($sql);
					$db->query();
				}
			}
		}
	}

	/**
	 * Deletes records from a table
	 *
	 * @param   string  &$ids  key value to delete
	 * @param   string  $key   key to use (leave empty to default to the table's key)
	 *
	 * @return  string	error message
	 */

	public function deleteRows(&$ids, $key = '')
	{
		if (!is_array($ids))
		{
			$ids = array($ids);
		}
		$val = $ids;
		$app = JFactory::getApplication();
		$table = $this->getTable();
		$db = $this->getDb();
		$params = $this->getParams();
		if ($key == '')
		{
			$key = $table->db_primary_key;
			if ($key == '')
			{
				return JError::raiseWarning(JText::_("COM_FABRIK_NO_KEY_FOUND_FOR_THIS_TABLE"));
			}
		}

		$c = count($val);
		foreach ($val as &$v)
		{
			$v = $db->quote($v);
		}
		$val = implode(",", $val);

		// $$$ rob - if we are not deleting joined rows then onloy load in the first row
		// otherwise load in all rows so we can apply onDeleteRows() to all the data
		if ($this->getParams()->get('delete-joined-rows', false) == false)
		{
			$nav = $this->getPagination($c, 0, $c);
		}
		$this->_whereSQL['string'][true] = ' WHERE ' . $key . ' IN (' . $val . ')';
		/* $$$ hugh - need to clear cached data, 'cos we called getTotalRecords from the controller, which now
		 * calls getData(), and will have cached all rows on this page, not just the ones being deleted, which means
		* things like form and element onDelete plugins will get handed a whole page of rows, not just the ones
		* selected for delete!  Ooops.
		*/
		unset($this->_data);
		$rows = $this->getData();

		/* $$$ hugh - we need to check delete perms, see:
		 * http://fabrikar.com/forums/showthread.php?p=102670#post102670
		* Short version, if user has access for a table plugin, they get a checkbox on the row, but may not have
		* delete access on that row.
		*/
		$removed_id = false;
		foreach ($rows as &$group)
		{
			foreach ($group as $group_key => $row)
			{
				if (!$this->canDelete($row))
				{
					// Can't delete, so remove row data from $rows, and the id from $ids, and queue a message
					foreach ($ids as $id_key => $id)
					{
						if ($id == $row->__pk_val)
						{
							unset($ids[$id_key]);
							continue;
						}
					}
					unset($group[$group_key]);
					$app->enqueueMessage('NO PERMISSION TO DELETE ROW');
					$removed_id = true;
				}
			}
		}

		// See if we have any rows left to delete after checking perms
		if (empty($ids))
		{
			return;
		}
		// Redo $val list of ids in case we zapped any on canDelete check
		if ($removed_id)
		{
			$val = $ids;
			$c = count($val);
			foreach ($val as &$v)
			{
				$v = $db->quote($v);
			}
			$val = implode(",", $val);
		}

		$this->_rowsToDelete = $rows;
		$groupModels = $this->getFormGroupElementData();
		foreach ($groupModels as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$elementModel->onDeleteRows($rows);
			}
		}
		$pluginManager = FabrikWorker::getPluginManager();

		/* $$$ hugh - added onDeleteRowsForm plugin (needed it so fabrikjuser form plugin can delete users)
		 * NOTE - had to call it onDeleteRowsForm rather than onDeleteRows, otherwise runPlugins() automagically
		* runs the element onDeleteRows(), which we already do above.  And with the code as-is, that won't work
		* from runPlugins() 'cos it won't pass it the $rows it needs.  So i have to sidestep the issue by using
		* a different trigger name.  Added a default onDeleteRowsForm() to plugin-form.php, and implemented
		* (and tested) user deletion in fabrikjuser.php using this trigger.  All seems to work.  7/28/2009
		*/

		if (in_array(false, $pluginManager->runPlugins('onDeleteRowsForm', $this->getFormModel(), 'form', $rows)))
		{
			return;
		}

		$pluginManager->getPlugInGroup('list');
		if (in_array(false, $pluginManager->runPlugins('onDeleteRows', $this, 'list')))
		{
			return;
		}
		$query = $db->getQuery(true);
		$query->delete($table->db_table_name)->where($key . ' IN (' . $val . ')');
		$db->setQuery($query);
		if (!$db->query())
		{
			return JError::raiseWarning($db->getErrorMsg());
		}
		$this->deleteJoinedRows($val);

		// Clean the cache.
		$cache = JFactory::getCache(JRequest::getCmd('option'));
		$cache->clean();
		return true;
	}

	/**
	 * Remove all records from the table
	 *
	 * @return  mixed
	 */

	public function dropData()
	{
		$db = $this->getDb();
		$query = $db->getQuery(true);
		$table = $this->getTable();
		$query->delete($db->quoteName($table->db_table_name));
		$db->setQuery($query);
		if (!$db->query())
		{
			return JError::raiseWarning(JText::_($db->getErrorMsg()));
		}
		return true;
	}

	/**
	 * Drop the table containing the fabriktables data and drop any internal joins db tables.
	 *
	 * @return  mixed
	 */

	public function drop()
	{
		$db = $this->getDb();
		$item = $this->getTable();
		if ($item->db_table_name !== '')
		{
			$sql = "DROP TABLE IF EXISTS " . $db->quoteName($item->db_table_name);
			$db->setQuery($sql);
			if (!$db->query())
			{
				return JError::raiseError(500, 'drop:' . JText::_($db->getErrorMsg()));
			}
		}
		// Remove any groups that were set to be repeating and hence were storing in their own db table.
		$joinModels = $this->getInternalRepeatJoins();
		foreach ($joinModels as $joinModel)
		{
			if ($joinModel->getJoin()->table_join !== '')
			{
				$sql = "DROP TABLE IF EXISTS " . $db->quoteName($joinModel->getJoin()->table_join);
				$db->setQuery($sql);
				$db->query();
				if ($db->getErrorNum())
				{
					JError::raiseError(500, 'drop internal group tables: ' . $db->getErrorMsg());
				}
			}
		}
		return true;
	}

	/**
	 * Get an array of join models relating to the groups which were set to be repeating and thus thier data
	 * stored in a separate db table
	 *
	 * @return  array  join models.
	 */

	public function getInternalRepeatJoins()
	{
		$return = array();
		$groupModels = $this->getFormGroupElementData();

		// Remove any groups that were set to be repeating and hence were storing in their own db table.
		foreach ($groupModels as $groupModel)
		{
			if ($groupModel->isJoin())
			{
				$joinModel = $groupModel->getJoinModel();
				$join = $joinModel->getJoin();
				$joinParams = is_string($join->params) ? json_decode($join->params) : $join->params;
				if (isset($joinParams->type) && $joinParams->type === 'group')
				{
					$return[] = $joinModel;
				}
			}
		}
		return $return;
	}

	/**
	 * Truncate the main db table and any internal joined groups
	 *
	 * @return  void
	 */

	public function truncate()
	{
		$db = $this->getDb();
		$item = $this->getTable();

		// Remove any groups that were set to be repeating and hence were storing in their own db table.
		$joinModels = $this->getInternalRepeatJoins();
		foreach ($joinModels as $joinModel)
		{
			$db->setQuery("TRUNCATE " . $db->quoteName($joinModel->getJoin()->table_join));
			$db->query();
		}
		$db->setQuery("TRUNCATE " . $db->quoteName($item->db_table_name));
		$db->query();

		// 3.0 clear filters (resets limitstart so that subsequently added records are shown)
		$this->getFilterModel()->clearFilters();
	}

	/**
	 * Test if a field already exists in the database
	 *
	 * @param   string  $field   field to test
	 * @param   array   $ignore  id's to ignore
	 *
	 * @return  bool
	 */

	public function fieldExists($field, $ignore = array())
	{
		$field = JString::strtolower($field);
		$groupModels = $this->getFormGroupElementData();
		foreach ($groupModels as $groupModel)
		{
			if (!$groupModel->isJoin())
			{
				// Don't check groups that aren't in this table
				$elementModels = $groupModel->getMyElements();
				foreach ($elementModels as $elementModel)
				{
					$element = $elementModel->getElement();
					$n = JString::strtolower($element->name);
					if (JString::strtolower($element->name) == $field && !in_array($element->id, $ignore))
					{
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Build a dropdown list of fileds
	 *
	 * @param   int     $cnnId           Connection id to use
	 * @param   string  $tbl             Table to load fields for
	 * @param   string  $incSelect       Show "please select" top option
	 * @param   bool    $incTableName    Append field name values with table name
	 * @param   string  $selectListName  Name of drop down
	 * @param   string  $selected        Selected option
	 * @param   string  $className       Class name
	 *
	 * @return  string	html to be added to DOM
	 */

	public function getFieldsDropDown($cnnId, $tbl, $incSelect, $incTableName = false, $selectListName = 'order_by', $selected = null,
		$className = "inputbox")
	{
		$this->setConnectionId($cnnId);
		$aFields = $this->getDBFields($tbl);
		$fieldNames = array();
		if ($incSelect != '')
		{
			$fieldNames[] = JHTML::_('select.option', '', $incSelect);
		}
		if (is_array($aFields))
		{
			foreach ($aFields as $oField)
			{
				if ($incTableName)
				{
					$fieldNames[] = JHTML::_('select.option', $tbl . '___' . $oField->Field, $oField->Field);
				}
				else
				{
					$fieldNames[] = JHTML::_('select.option', $oField->Field);
				}
			}
		}
		$opts = 'class="' . $className . '" size="1" ';
		$fieldDropDown = JHTML::_('select.genericlist', $fieldNames, $selectListName, $opts, 'value', 'text', $selected);
		return str_replace("\n", "", $fieldDropDown);
	}

	/**
	 * Create the RSS href link to go in the table template
	 *
	 * @return  string	RSS link
	 */

	public function getRSSFeedLink()
	{
		$app = JFactory::getApplication();
		$link = '';
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');

		if ($this->getParams()->get('rss') == '1')
		{
			// $$$ rob test fabriks own feed renderer
			$link = 'index.php?option=com_' . $package . '&view=list&listid=' . $this->getId() . "&format=fabrikfeed";
			if (!$app->isAdmin())
			{
				$link = JRoute::_($link);
			}
		}
		return $link;
	}

	/**
	 * Iterates through string to replace every
	 * {placeholder} with row data
	 * (added by hugh, does the same thing as parseMessageForPlaceHolder in parent
	 * class, but for rows instead of forms)
	 *
	 * @param   string  $msg         text to parse
	 * @param   array   &$row        of row data
	 * @param   bool    $addslashes  add slashes to the replaced data (default = false) set to true in fabrikcalc element
	 *
	 * @return  string  parsed message
	 */

	public function parseMessageForRowHolder($msg, &$row, $addslashes = false)
	{
		$this->_aRow = $row;
		if (!strstr($msg, '{'))
		{
			return $msg;
		}
		$this->_parseAddSlases = $addslashes;
		$msg = FabrikWorker::replaceWithUserData($msg);
		$msg = FabrikWorker::replaceWithGlobals($msg);
		$msg = preg_replace("/{}/", "", $msg);
		$this->rowIdentifierAdded = false;
		/* replace {element name} with form data */
		/* $$$ hugh - testing changing the regex so we don't blow away PHP structures!  Added the \s so
		 * we only match non-space chars in {}'s.  So unless you have some code like "if (blah) {foo;}", PHP
		* block level {}'s should remain unmolested.
		*/
		$msg = preg_replace_callback("/{[^}\s]+}/i", array($this, '_replaceWithRowData'), $msg);
		return $msg;
	}

	/**
	 * Called from parseMessageForRowHolder to iterate through string to replace
	 * {placeholder} with row data
	 *
	 * @param   array  $matches  found in parseMessageForRowHolder
	 *
	 * @return  string	posted data that corresponds with placeholder
	 */

	private function _replaceWithRowData($matches)
	{
		$match = $matches[0];

		// $$$ felixkat - J! plugin closings, i.e  {/foo} were getting caught here.
		if (preg_match('[{/]', $match))
		{
			return $match;
		}

		/* strip the {} */
		$match = JString::substr($match, 1, JString::strlen($match) - 2);

		// $$$ hugh - in case any {$my->foo} or {$_SERVER->FOO} paterns are left over, avoid 'undefined index' warnings
		if (preg_match('#^\$#', $match))
		{
			return '';
		}
		$match = str_replace('.', '___', $match);

		// $$$ hugh - allow use of {$rowpk} or {rowpk} to mean the rowid of the row within a table
		if ($match == 'rowpk' || $match == '$rowpk' || $match == 'rowid')
		{
			$this->rowIdentifierAdded = true;
			$match = '__pk_val';
		}
		$match = preg_replace("/ /", "_", $match);
		if ($match == 'formid')
		{
			return $this->getFormModel()->getId();
		}
		$return = JArrayHelper::getValue($this->_aRow, $match);
		if ($this->_parseAddSlases)
		{
			$return = htmlspecialchars($return, ENT_QUOTES, 'UTF-8');
		}
		return $return;
	}

	/**
	 * This is just way too confuins - view details link now always returns a view details link and not an edit link ?!!!
	 * get the link to view the records details
	 *
	 * @param   object  &$row  active list row
	 * @param   string  $view  3.0 depreciated
	 *
	 * @return  string	url of view details link
	 *
	 * @since  3.0
	 *
	 * @retun  string  link
	 */

	function viewDetailsLink(&$row, $view = null)
	{
		$app = JFactory::getApplication();
		$menuItem = $app->getMenu('site')->getActive();
		$Itemid = is_object($menuItem) ? $menuItem->id : 0;
		$keyIdentifier = $this->getKeyIndetifier($row);
		$params = $this->getParams();
		$table = $this->getTable();
		$link = '';
		$view = 'details';
		$customLink = $this->getCustomLink('url', 'details');

		if (trim($customLink) === '')
		{
			$link = '';

			// $$$ hugh - if we don't do this on feeds, links with subfolders in root get screwed up because no BASE_HREF is set
			if (JRequest::getvar('format', '') == 'fabrikfeed')
			{
				$link .= COM_FABRIK_LIVESITE;
			}
			if ($app->isAdmin())
			{
				$link .= "index.php?option=com_fabrik&task=$view.view&formid=" . $table->form_id . "&listid=" . $this->getId() . $keyIdentifier;
			}
			else
			{
				$link .= "index.php?option=com_fabrik&view=$view&formid=" . $table->form_id . $keyIdentifier;
			}
			if ($this->packageId !== 0)
			{
				$link .= '&tmpl=component';
			}
			$link = JRoute::_($link);
		}
		else
		{
			// Custom link
			$link = $this->makeCustomLink($customLink, $row);
		}
		return $link;
	}

	/**
	 * Create a custom edit/view details link
	 *
	 * @param   string  $link  link
	 * @param   object  $row   row's data
	 *
	 * @return  string  custom link
	 */

	protected function makeCustomLink($link, $row)
	{
		$link = htmlspecialchars($link);
		$keyIdentifier = $this->getKeyIndetifier($row);
		$row = JArrayHelper::fromObject($row);
		$link = $this->parseMessageForRowHolder($link, $row);
		if ($this->rowIdentifierAdded === false)
		{
			if (strstr($link, '?'))
			{
				$link .= $keyIdentifier;
			}
			else
			{
				$link .= '?' . str_replace('&', '', $keyIdentifier);
			}
		}
		$link = JRoute::_($link);
		return $link;
	}

	/**
	 * Get a custome link
	 *
	 * @param   string  $type  link type
	 * @param   string  $mode  edit/details link
	 *
	 * @return  string  link
	 */

	protected function getCustomLink($type = 'url', $mode = 'edit')
	{
		$params = $this->getParams();
		if ($type === 'url')
		{
			$str = ($mode == 'edit') ? $params->get('editurl') : $params->get('detailurl');
		}
		else
		{
			$str = ($mode == 'edit') ? $params->get('editurl_attribs') : $params->get('detailurl_attribs');
		}
		$w = new FabrikWorker;
		return $w->parseMessageForPlaceHolder($str);
	}

	/**
	 * Get the link to edit the records details
	 *
	 * @param   object  &$row  active table row
	 *
	 * @return  string  url of view details link
	 */

	function editLink(&$row)
	{
		$app = JFactory::getApplication();
		$menuItem = $app->getMenu('site')->getActive();
		$Itemid = is_object($menuItem) ? $menuItem->id : 0;
		$keyIdentifier = $this->getKeyIndetifier($row);
		$table = $this->getTable();
		$customLink = $this->getCustomLink('url', 'edit');
		if ($customLink == '')
		{
			$package = $app->getUserState('com_fabrik.package', 'fabrik');
			if ($app->isAdmin())
			{
				$url = 'index.php?option=com_' . $package . '&task=form.view&formid=' . $table->form_id . $keyIdentifier;
			}
			else
			{
				$url = 'index.php?option=com_' . $package . '&view=form&Itemid=' . $Itemid . '&formid=' . $table->form_id . $keyIdentifier . '&listid='
						. $this->getId();
			}
			$link = JRoute::_($url);
		}
		else
		{
			$link = $this->makeCustomLink($customLink, $row);
		}
		return $link;
	}

	/**
	 * Make the drop sql statement for the table
	 *
	 * @return  string  drop table sql
	 */

	public function getDropTableSQL()
	{
		$db = FabrikWorker::getDbo();
		$genTable = $this->getGenericTableName();
		$sql = "DROP TABLE IF EXISTS " . $db->quoteName($genTable);
		return $sql;
	}

	/**
	 * Convert a prefix__tablename to #__tablename
	 *
	 * @return  string  table name
	 */

	public function getGenericTableName()
	{
		$app = JFactory::getApplication();
		$table = $this->getTable();
		return str_replace($app->getCfg('dbprefix'), '#__', $table->db_table_name);
	}

	/**
	 * Make the create sql statement for the table
	 *
	 * @param   bool    $addIfNotExists  add 'if not exists' to query
	 * @param   string  $table           table to get sql for(leave out to use models table)
	 	*
	 * @return  string	sql to drop & or create table
	 */

	public function getCreateTableSQL($addIfNotExists = false, $table = null)
	{
		$addIfNotExists = $addIfNotExists ? 'IF NOT EXISTS ' : '';
		if (is_null($table))
		{
			$table = $this->getGenericTableName();
		}
		$fields = $this->getDBFields($table);
		$primaryKey = "";
		$sql = "";
		$table = FabrikString::safeColName($table);
		if (is_array($fields))
		{
			$sql .= "CREATE TABLE $addIfNotExists" . $table . " (\n";
			foreach ($fields as $field)
			{
				$field->Field = FabrikString::safeColName($field->Field);
				if ($field->Key == 'PRI' && $field->Extra == 'auto_increment')
				{
					$primaryKey = "PRIMARY KEY ($field->Field)";
				}
				$sql .= "$field->Field ";
				$sql .= ' ' . $field->Type . ' ';
				if ($field->Null == '')
				{
					$sql .= " NOT NULL ";
				}
				if ($field->Default != '' && $field->Key != 'PRI')
				{
					if ($field->Default == 'CURRENT_TIMESTAMP')
					{
						$sql .= "DEFAULT $field->Default";
					}
					else
					{
						$sql .= "DEFAULT '$field->Default'";
					}
				}
				$sql .= $field->Extra . ",\n";
			}
			if ($primaryKey == '')
			{
				$sql = rtrim($sql, ",\n");
			}
			$sql .= $primaryKey . ");";
		}
		return $sql;
	}

	/**
	 * Make the create sql statement for inserting the table data
	 * used in package export
	 *
	 * @param   object  $oExporter  exporter
	 *
	 * @deprecated - not used?
	 *
	 * @return  string	sql to drop & or create table
	 */

	public function getInsertRowsSQL($oExporter)
	{
		@set_time_limit(300);
		$table = $this->getTable();
		$memoryLimit = ini_get('memory_limit');
		$db = $this->getDb();
		/*
		 * dont load in all the table data as on large tables this gives a memory error
		* in fact this wasnt the problem, but rather the $sql var becomes too large to hold in memory
		* going to try saving to a file on the server and then compressing that and sending it as a header for download
		*/
		$query = $db->getQuery(true);
		$query->select($table->db_primary_key)->from($table->db_table_name);
		$db->setQuery($query);
		$keys = $db->loadColumn();
		$sql = "";
		$query = $db->getQuery(true);
		$dump_buffer_len = 0;
		if (is_array($keys))
		{
			foreach ($keys as $id)
			{
				$query->clear();
				$query->select('*')->from($table->db_table_name)->where($table->db_primary_key = $id);
				$db->setQuery($query);
				$row = $db->loadObject();
				$fmtsql = "\t<query>INSERT INTO " . $table->db_table_name . " ( %s ) VALUES ( %s )</query>";
				$values = array();
				$fields = array();
				foreach ($row as $k => $v)
				{
					$fields[] = $db->quoteName($k);
					$values[] = $db->quote($v);
				}
				$sql .= sprintf($fmtsql, implode(",", $fields), implode(",", $values));
				$sql .= "\n";

				$dump_buffer_len += JString::strlen($sql);
				if ($dump_buffer_len > $memoryLimit)
				{
					$oExporter->writeExportBuffer($sql);
					$sql = "";
					$dump_buffer_len = 0;
				}
				unset($values);
				unset($fmtsql);
			}
		}
		$oExporter->writeExportBuffer($sql);
	}

	/**
	 * Get a row of data from the table
	 *
	 * @param   int   $id        id
	 * @param   bool  $format    the data
	 * @param   bool  $loadJoin  load the rows joined data @since 2.0.5 (used in J Content plugin)
	 *
	 * @return  object	row
	 */

	public function getRow($id, $format = false, $loadJoin = false)
	{
		if (is_null($this->rows))
		{
			$this->rows = array();
		}
		$sig = $id . '.' . $format . '.' . $loadJoin;
		if (array_key_exists($sig, $this->rows))
		{
			return $this->rows[$sig];
		}
		$fabrikDb = $this->getDb();
		$formModel = $this->getFormModel();
		$formModel->_rowId = $id;
		unset($formModel->query);
		$sql = $formModel->_buildQuery();
		$fabrikDb->setQuery($sql);
		if (!$loadJoin)
		{
			if ($format == true)
			{
				$row = $fabrikDb->loadObject();
				$row = array($row);
				$this->formatData($row);
				/* $$$ hugh - if table is grouped, formatData will have turned $row into an
				 * assoc array, so can't assume 0 is first key.
				* $this->rows[$sig] = $row[0][0];
				*/
				$row = JArrayHelper::getValue($row, FArrayHelper::firstKey($row), array());
				$this->rows[$sig] = JArrayHelper::getValue($row, 0, new stdClass);
			}
			else
			{
				$this->rows[$sig] = $fabrikDb->loadObject();
			}
			if ($fabrikDb->getErrorNum())
			{
				JError::raiseError(500, $fabrikDb->getErrorMsg());
			}
		}
		else
		{
			$rows = $fabrikDb->loadObjectList();
			if ($fabrikDb->getErrorNum())
			{
				JError::raiseError(500, $fabrikDb->getErrorMsg());
			}
			$formModel->setJoinData($rows);
			if ($format == true)
			{
				$this->formatData($rows);
				/* $$$ hugh - if list is grouped, formatData will have re-index as assoc array,
				 /* so can't assume 0 is first key.
				*/
				$this->rows[$sig] = JArrayHelper::getValue($rows, FArrayHelper::firstKey($rows), array());
			}
			else
			{
				$this->rows[$sig] = JArrayHelper::getValue($rows, 0, array());
			}
		}
		return $this->rows[$sig];
	}

	/**
	 * Find a row in the table that matches " key LIKE '%val' "
	 *
	 * @param   string  $key     key
	 * @param   string  $val     value
	 * @param   bool    $format  format the row
	 *
	 * @return  object	row
	 */

	public function findRow($key, $val, $format = false)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$usekey = $input->get('usekey');
		$usekey_comparison = $input->get('usekey_comparison');
		$input->set('usekey', $key);
		$input->set('usekey_comparison', 'like');
		$row = $this->getRow($val, $format);
		$input->set('usekey', $usekey);
		$input->set('usekey_comparison', $usekey_comparison);
		return $row;
	}

	/**
	 * Ajax get record specified by row id
	 *
	 * @param   string  $mode  mode
	 *
	 * @return  string  json encoded row
	 */

	public function xRecord($mode = 'table')
	{
		$fabrikDb = $this->getDb();
		$cursor = JRequest::getInt('cursor', 1);
		$this->getConnection();
		$this->outPutFormat = 'json';
		$nav = $this->getPagination(1, $cursor, 1);
		if ($mode == 'table')
		{
			$query = $this->_buildQuery();
			$this->setBigSelects();
			$fabrikDb->setQuery($query, $this->limitStart, $this->limitLength);
			$data = $fabrikDb->loadObjectList();
		}
		else
		{
			// Get the row id
			$table = $this->getTable();
			$query = $db->getQuery(true);
			$query->select($table->db_primary_key)->from($table->db_table_name);
			$query = $this->_buildQueryJoin($query);
			$query = $this->_buildQueryOrder($query);
			$fabrikDb->setQuery($query, $nav->limitstart, $nav->limit);
			$rowid = $fabrikDb->loadResult();
			JRequest::setVar('rowid', $rowid);
			$app = JFactory::getApplication();
			$formid = JRequest::getInt('formid');
			$app->redirect('index.php?option=com_fabrik&view=form&formid=' . $formid . '&rowid=' . $rowid . '&format=raw');
		}
		return json_encode($data);
	}

	/**
	 * Ajax get next record
	 *
	 * @return  string  json object representing record/row
	 */

	public function nextRecord()
	{
		$cursor = JRequest::getInt('cursor', 1);
		$this->getConnection();
		$this->outPutFormat = 'json';
		$nav = $this->getPagination(1, $cursor, 1);
		$data = $this->getData();
		echo json_encode($data);
	}

	/**
	 * Ajax get previous record
	 *
	 * @return  string json  object representing record/row
	 */

	public function previousRecord()
	{
		$cursor = JRequest::getInt('cursor', 1);
		$this->getConnection();
		$this->outPutFormat = 'json';
		$nav = $this->getPagination(1, $cursor - 2, 1);
		$data = $this->getData();
		return json_encode($data);
	}

	/**
	 * Ajax get first record
	 *
	 * @return  string  json object representing record/row
	 */

	public function firstRecord()
	{
		$cursor = JRequest::getInt('cursor', 1);
		$this->getConnection();
		$this->outPutFormat = 'json';
		$nav = $this->getPagination(1, 0, 1);
		$data = $this->getData();
		return json_encode($data);
	}

	/**
	 * Ajax get last record
	 *
	 * @return  string  json object representing record/row
	 */

	public function lastRecord()
	{
		$total = JRequest::getInt('total', 0);
		$this->getConnection();
		$this->outPutFormat = 'json';
		$nav = $this->getPagination(1, $total - 1, 1);
		$data = $this->getData();
		return json_encode($data);
	}

	/**
	 * Get a single column of data from the table, test for element filters
	 *
	 * @param   mixed  $col       Column to grab. Element full name or id
	 * @param   bool   $distinct  Select distinct values only
	 * @param   array  $opts      Options: filterLimit bool - should limit to filter_list_max global param (default true)
	 *
	 * @return  array  Values for the column - empty array if no results found
	 */

	public function getColumnData($col, $distinct = true, $opts = array())
	{
		if (!array_key_exists($col, $this->columnData))
		{
			$fbConfig = JComponentHelper::getParams('com_fabrik');
			$cache = FabrikWorker::getCache($this);
			$res = $cache->call(array(get_class($this), 'columnData'), $this->getId(), $col, $distinct, $opts);
			if (is_null($res))
			{
				JError::raiseNotice(500, 'list model getColumn Data for ' . $col . ' failed');
			}
			if ((int) $fbConfig->get('filter_list_max', 100) == count($res))
			{
				JError::raiseNotice(500, JText::sprintf('COM_FABRIK_FILTER_LIST_MAX_REACHED', $col));
			}
			if (is_null($res))
			{
				$res = array();
			}

			$this->columnData[$col] = $res;
		}
		return $this->columnData[$col];
	}

	/**
	 * Cached method to grab a colums' data, called from getColumnData()
	 *
	 * @param   int    $listId    List id
	 * @param   mixed  $col       Column to grab. Element full name or id
	 * @param   bool   $distinct  Select distinct values only
	 * @param   array  $opts      Options: filterLimit bool - should limit to filter_list_max global param (default true)
	 *
	 * @since   3.0.7
	 *
	 * @return  array  column's values
	 */

	public static function columnData($listId, $col, $distinct = true, $opts = array())
	{
		$listModel = JModel::getInstance('List', 'FabrikFEModel');
		$listModel->setId($listId);
		$table = $listModel->getTable();
		$fbConfig = JComponentHelper::getParams('com_fabrik');
		$db = $listModel->getDb();
		$el = $listModel->getFormModel()->getElement($col, true);
		$col = FabrikString::safeColName($el->getFullName(false, false, false));
		$el->encryptFieldName($col);
		$tablename = $table->db_table_name;
		$tablename = FabrikString::safeColName($tablename);
		$query = $db->getQuery(true);
		$col = $distinct ? 'DISTINCT(' . $col . ')' : $col;
		$query->select($col)->from($tablename);
		$query = $listModel->_buildQueryJoin($query);
		$query = $listModel->_buildQueryWhere(false, $query);
		$query = $listModel->pluginQuery($query);
		$filterLimit = JArrayHelper::getValue($opts, 'filterLimit', true);
		if ($filterLimit) {
			$db->setQuery($query, 0, $fbConfig->get('filter_list_max', 100));
		}
		else
		{
			$db->setQuery($query);
		}

		$res = $db->loadColumn(0);
		return $res;
	}

	/**
	 * Determine how the model does filtering and navigation
	 *
	 * @return  bool  ajax true /post false; default post
	 */

	public function isAjax()
	{
		$params = $this->getParams();
		if (is_null($this->ajax))
		{
			// $$$ rob 11/07/2011 if post method set to ajax in request use that over the list_nav option
			if (JRequest::getVar('ajax', false) == '1')
			{
				$this->ajax = true;
			}
			else
			{
				$this->ajax = $params->get('list_ajax', JRequest::getBool('ajax', false));
			}
		}
		return (bool) $this->ajax;
	}

	/**
	 * Model edit/add links can be set separately to the ajax option
	 *
	 * @return  bool
	 */

	protected function isAjaxLinks()
	{
		$params = $this->getParams();
		$ajax = $this->isAjax();
		return (bool) $params->get('list_ajax_links', $ajax);
	}

	/**
	 * Get an array of the table's elements that match a certain plugin type
	 *
	 * @param   string  $plugin  name
	 *
	 * @return  array	matched element models
	 */

	public function getElementsOfType($plugin)
	{
		$found = array();
		$groups = $this->getFormGroupElementData();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getMyElements();
			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();
				if ($element->plugin == $plugin)
				{
					$found[] = $elementModel;
				}
			}
		}
		return $found;
	}

	/**
	 * Get all the elements in the list
	 *
	 * @param   string  $key            key to key returned array on, currently accepts null, '', 'id', or 'filtername'
	 * @param   bool    $showInTable    show in table default true
	 * @param   bool    $onlyPublished  return only published elements
	 *
	 * @return  array	table element models
	 */

	public function getElements($key = 0, $showInTable = true, $onlyPublished = true)
	{
		if (!isset($this->elements))
		{
			$this->elements = array();
		}
		$sig = $key . '.' . (int) $showInTable;
		if (!array_key_exists($sig, $this->elements))
		{
			$this->elements[$sig] = array();
			$found = array();
			$groups = $this->getFormGroupElementData();
			foreach (array_keys($groups) as $gid)
			{
				$groupModel = $groups[$gid];
				$elementModels = $groupModel->getMyElements();
				foreach ($elementModels as $elementModel)
				{
					$element = $elementModel->getElement();
					if ($element->published == 0 && $onlyPublished)
					{
						continue;
					}
					$dbkey = $key == 'filtername' ? trim($elementModel->getFilterFullName()) : trim($elementModel->getFullName(false, true, false));
					switch ($key)
					{
						case 'safecolname':
							// Deprecated (except for querystring filters and inline edit)
						case 'filtername':
							// $$$ rob hack to ensure that querystring filters dont use the concat string when getting the
							// Dbkey for the element, otherwise related data doesn't work
							$origconcat = $elementModel->getParams()->get('join_val_column_concat');
							$elementModel->getParams()->set('join_val_column_concat', '');

							// $$$ rob if prefilter was using _raw field then we need to assign the model twice to both possible keys
							if ($elementModel->getElement()->plugin == 'fabrikdatabasejoin')
							{
								$dbkey2 = FabrikString::safeColName($elementModel->getFullName(false, false, false));
								$this->elements[$sig][$dbkey2] = $elementModel;
							}
							$elementModel->getParams()->set('join_val_column_concat', $origconcat);
							$this->elements[$sig][$dbkey] = $elementModel;
							break;
						case 'id':
							$this->elements[$sig][$element->id] = $elementModel;
							break;
						default:
							$this->elements[$sig][] = $elementModel;
							break;
					}
				}
			}
		}
		return $this->elements[$sig];
	}

	/**
	 * Does the list need to include the slimbox js code
	 *
	 * @return  bool
	 */

	public function requiresSlimbox()
	{
		$fbConfig = JComponentHelper::getParams('com_fabrik');
		if ($fbConfig->get('include_lightbox_js', 1) == 2)
		{
			return true;
		}
		$form = $this->getFormModel();
		$groups = $form->getGroupsHiarachy();
		foreach ($groups as $group)
		{
			$elements = $group->getPublishedElements();
			foreach ($elements as $elementModel)
			{
				$element = $elementModel->getElement();
				if ($element->show_in_list_summary && $elementModel->requiresLightBox())
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Get pluginmanager (get reference to form's plugin manager
	 *
	 * @deprecated - use FabrikWorker::getPluginManager() instead since 3.0b
	 *
	 * @return  object  plugin manager model
	 */

	public function getPluginManager()
	{
		return FabrikWorker::getPluginManager();
	}

	/**
	 * Called via advanced search to load in a given element filter
	 *
	 * @return string html for filter
	 */

	public function getAdvancedElementFilter()
	{
		$app = JFactory::getApplication();
		$element = JRequest::getVar('element');
		$elementid = JRequest::getVar('elid');
		$pluginManager = FabrikWorker::getPluginManager();
		$className = JRequest::getVar('plugin');
		$plugin = $pluginManager->getPlugIn($className, 'element');
		$plugin->setId($elementid);
		$el = $plugin->getElement();
		if ($app->input->get('context') == 'visualization')
		{
			$container = $app->input->get('parentView');
		}
		else
		{
			$container = 'listform_' . $this->getRenderContext();
		}
		$script = $plugin->filterJS(false, $container);
		FabrikHelperHTML::addScriptDeclaration($script);
		echo $plugin->getFilter(JRequest::getInt('counter', 0), false);
	}

	/**
	 * Build the table's add record link
	 * if a querystring filter has been passed in to the table then apply this to the link
	 * this means that table->faceted table->add will auto select the data you browsed on
	 *
	 * @return string  url
	 */

	public function getAddRecordLink()
	{
		$qs = array();
		$w = new FabrikWorker;
		$app = JFactory::getApplication();
		$menuItem = $app->getMenu('site')->getActive();
		$Itemid = is_object($menuItem) ? $menuItem->id : 0;
		$params = $this->getParams();
		$addurl_url = $params->get('addurl', '');
		$addlabel = $params->get('addlabel', '');
		$filters = $this->getRequestData();
		$keys = JArrayHelper::getValue($filters, 'key', array());
		$vals = JArrayHelper::getValue($filters, 'value', array());
		$types = JArrayHelper::getValue($filters, 'search_type', array());
		for ($i = 0; $i < count($keys); $i++)
		{
			if (JArrayHelper::getValue($types, $i, '') === 'querystring')
			{
				$qs[FabrikString::safeColNameToArrayKey($keys[$i]) . '_raw'] = $vals[$i];
			}
		}
		$addurl_qs = array();
		if (!empty($addurl_url))
		{
			$addurl_parts = explode('?', $addurl_url);
			if (count($addurl_parts) > 1)
			{
				$addurl_url = $addurl_parts[0];
				foreach (explode('&', $addurl_parts[1]) as $urlvar)
				{
					$key_value = explode('=', $urlvar);
					$addurl_qs[$key_value[0]] = $key_value[1];
				}
			}
		}
		// $$$ rob needs the item id for when sef urls are turned on
		if (JRequest::getCmd('option') !== 'com_fabrik')
		{
			if (!array_key_exists('Itemid', $addurl_qs))
			{
				$qs['Itemid'] = $Itemid;
			}
		}
		if (empty($addurl_url))
		{
			/*  $$$ rob set this options in the js - so if we want to open a
			 * link normaly we can right click and open the page as a standard J view
			* if ($this->isAjaxLinks())
			{
			$qs['ajax'] = '1';
			} */
			$formModel = $this->getFormModel();
			$formid = $formModel->getForm()->id;
			/* if ($this->packageId !== 0 || $this->isAjaxLinks())
			 {
			$qs['tmpl'] = 'component';
			} */

			$qs['option'] = 'com_fabrik';
			if ($app->isAdmin())
			{
				$qs['task'] = 'form.view';
			}
			else
			{
				$qs['view'] = 'form';
			}
			$qs['formid'] = $this->getTable()->form_id;
			$qs['rowid'] = '0';

			/* $$$ hugh - testing social profile session hash, which may get set by things like
			 * the CB or JomSocial plugin.  Needed so things like the 'user' element can derive the
			* user ID of the profile being viewed, to which a record is being added.
			*/
			if (JRequest::getVar('fabrik_social_profile_hash', '') != '')
			{
				$qs['fabrik_social_profile_hash'] = JRequest::getCmd('fabrik_social_profile_hash', '');
			}
		}
		$qs = array_merge($qs, $addurl_qs);
		$qs_args = array();
		foreach ($qs as $key => $val)
		{
			$qs_args[] = $key . '=' . $val;
		}
		$qs = implode('&', $qs_args);
		$qs = $w->parseMessageForPlaceHolder($qs, JRequest::get('request'));
		return !empty($addurl_url) ? JRoute::_($addurl_url . '?' . $qs) : JRoute::_('index.php?' . $qs);
	}

	/**
	 * Insert into the head a series of JS to load element list JS
	 *
	 * @return  void
	 */

	public function getElementJs()
	{
		$form = $this->getFormModel();
		$script = '';
		$groups = $form->getGroupsHiarachy();
		$run = array();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();
				if (!in_array($element->plugin, $run))
				{
					$run[] = $element->plugin;
					$elementModel->tableJavascriptClass();
				}
				$script .= $elementModel->elementListJavascript();
			}
		}
		if ($script !== '')
		{
			$script = "head.ready(function() {\n" . $script . "});\n";
			FabrikHelperHTML::addScriptDeclaration($script);
		}
	}

	/**
	 * Return the url for the list form - this url is used when submitting searches, and ordering
	 *
	 * @return  string  action url
	 */

	public function getTableAction()
	{
		if (isset($this->tableAction))
		{
			return $this->tableAction;
		}
		$option = JRequest::getCmd('option');

		// Get the router
		$app = JFactory::getApplication();
		$router = $app->getRouter();

		$uri = clone (JURI::getInstance());
		/* $$$ rob force these to be 0 once the menu item has been loaded for the first time
		 * subsequent loads of the link should have this set to 0. When the menu item is re-clicked
		* rest filters is set to 1 again
		*/
		$router->setVar('resetfilters', 0);
		if ($option !== 'com_fabrik')
		{
			// $$$ rob these can't be set by the menu item, but can be set in {fabrik....}
			$router->setVar('clearordering', 0);
			$router->setVar('clearfilters', 0);
		}
		$queryvars = $router->getVars();
		$form = $this->getFormModel();
		$page = 'index.php?';
		foreach ($queryvars as $k => $v)
		{
			$rawK = FabrikString::rtrimword($k, '_raw');
			$el = $form->getElement($k);
			if ($el === false)
			{
				$el = $form->getElement($rawK);
			}
			if (is_array($v))
			{
				/* $$$ rob if you were using URL filters such as
				 *
				* &jos_fabble_activity___create_date[value][]=now
				* &jos_fabble_activity___create_date[value][]=%2B2%20week&jos_fabble_activity___create_date[condition]=BETWEEN
				*
				* then we don't want to re-add them to the table action.
				* Instead they are aded to the filter sessions and reapplied that way
				* otherwise we ended up with elementname=Array in the query string
				*/
				if ($el === false)
				{
					$qs[] = $k . '=' . $v;
				}
			}
			else
			{
				if ($el === false)
				{
					$qs[] = $k . '=' . $v;
				}
				else
				{
					/* $$$ e-kinst
					 * let's keep &id for com_content - in other case in Content Plugin
					* we have incorrect action in form and as a result bad pagination URLS.
					* In any case this will not be excessive (I suppose)
					*/
					if ($k == 'id' && $option == 'com_content')
					{
						// At least. May be even  $option != 'com_fabrik'
						$qs[] = $k . '=' . $v;
					}
					// Check if its a tag element if it is we want to clear that when we clear the form
					// (if the filter was set via the url we generally want to keep it though

					/* 28/12/2011 $$$ rob testing never keeping querystring filters in the qs but instead always
					 * adding them to the filters (if no filter set up in element settings then hidden fields added anyway
					 		* this is to try to get round issue of related data (countries->regions) filter region from country list,
					 		* then clear filters (ok) but then if you go to 2nd page of results country url filter re-applied
					 		*/
					/* if($el->getElement()->plugin !== 'textarea' && $el->getParams()->get('textarea-tagify') !== true) {
					 $qs[] = "$k=$v";
					} */
				}
			}
		}
		$action = $page . implode('&amp;', $qs);
		$action = preg_replace("/limitstart{$this->getId()}=(\d+)?(&amp;|)/", '', $action);

		$action = FabrikString::removeQSVar($action, 'fabrik_incsessionfilters');
		$action = FabrikString::rtrimword($action, '&');
		$this->tableAction = JRoute::_($action);
		return $this->tableAction;
	}

	/**
	 * Allow plugins to add arbitrary WHERE clauses.  Gets checked in buildQueryWhere().
	 *
	 * @param   string  $pluginName   plugin name
	 * @param   string  $whereClause  where clause (WITHOUT prepended where/and etc)
	 *
	 * @return  bool
	 */

	public function setPluginQueryWhere($pluginName, $whereClause)
	{
		// Strip any prepended conditions off
		$whereClause = preg_replace('#(^where |^and |^or )#', '', $whereClause);
		/* only do anything if it's a different clause ...
		 * if it's the same, no need to clear the table data, can use cached
		*/
		if (!array_key_exists($pluginName, $this->_pluginQueryWhere) || $whereClause != $this->_pluginQueryWhere[$pluginName])
		{
			// Set the internal data, which will get used in _buildQueryWhere
			$this->_pluginQueryWhere['chart'] = $whereClause;
			/* as we are modifying the main getData query, we need to make sure and
			 * clear table data, forcing next getData() to do the query again, no cache
			*/
			$this->set('_data', null);
		}
		// Return true just for the heck of it
		return true;
	}

	/**
	 * Plugins sometimes need to clear their where clauses
	 *
	 * @param   string  $pluginName  plugin name
	 *
	 * @return  bool
	 */

	public function unsetPluginQueryWhere($pluginName)
	{
		if (array_key_exists($pluginName, $this->_pluginQueryWhere))
		{
			unset($this->_pluginQueryWhere[$pluginName]);
		}
		return true;
	}

	/**
	 * If all filters are set to read only then don't return a clear button
	 * otherwised do
	 	*
	 * @return  string	clear filter button link
	 */

	public function getClearButton()
	{
		$filters = $this->getFilters('listform_' . $this->getRenderContext(), 'list');
		$params = $this->getParams();
		if (count($filters) > 0 || $params->get('advanced-filter'))
		{
			$table = $this->getTable();
			$tmpl = $this->getTmpl();
			$title = '<span>' . JText::_('COM_FABRIK_CLEAR') . '</span>';
			$opts = array('alt' => JText::_('COM_FABRIK_CLEAR'), 'class' => 'fabrikTip', 'opts' => "{notice:true}", 'title' => $title);
			$img = FabrikHelperHTML::image('filter_delete.png', 'list', $tmpl, $opts);
			return '<a href="#" class="clearFilters">' . $img . '</a>';
		}
		else
		{
			return '';
		}
	}

	/**
	 * Get the join display mode - merge, normal or reduce
	 *
	 * @return  string	1 if merge, 2 if reduce, 0 if no merge or reduce
	 */

	public function mergeJoinedData()
	{
		$params = $this->getParams();
		$display = $params->get('join-display', '');
		switch ($display)
		{
			case 'merge':
				$merge = 1;
				break;
			case 'reduce':
				$merge = 2;
				break;
			default:
				$merge = 0;
				break;
		}
		return $merge;
	}

	/**
	 * Ask each element to preFormatFormJoins() for $data
	 *
	 * @param   array  &$data  to preformat
	 *
	 * @return  void
	 */

	protected function preFormatFormJoins(&$data)
	{
		$profiler = JProfiler::getInstance('Application');
		$form = $this->getFormModel();
		$tableParams = $this->getParams();
		$table = $this->getTable();
		$pluginManager = FabrikWorker::getPluginManager();
		$method = 'renderListData_' . $this->outPutFormat;
		$this->_aLinkElements = array();

		// $$$ hugh - temp foreach fix
		$groups = $form->getGroupsHiarachy();
		$ec = count($data);
		foreach ($groups as $groupModel)
		{
			/* if (($tableParams->get('group_by_template', '') !== '' && $this->getGroupBy() != '') || $this->outPutFormat == 'csv'
			 || $this->outPutFormat == 'feed')
			{
			$elementModels = $groupModel->getPublishedElements();
			}
			else
			{
			$elementModels = $groupModel->getPublishedListElements();
			} */

			/*
			 * $$$ rob 29/10/2012 - see http://fabrikar.com/forums/showthread.php?t=28830
			* Calc may be set to show in list via menu item, but groupModel::getPublishedListElements() doesn't know
			* this. Seems best to run all calcs regardless of whether they are set to show in list.
			*/
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$col = $elementModel->getFullName(false, true, false);
				if (!empty($data) && array_key_exists($col, $data[0]))
				{
					for ($i = 0; $i < $ec; $i++)
					{
						$thisRow = $data[$i];
						$coldata = $thisRow->$col;
						$data[$i]->$col = $elementModel->preFormatFormJoins($coldata, $thisRow);
					}
				}
			}
		}
	}

	/**
	 * $$$ rob 19/10/2011 now called before formatData() from getData() as otherwise element tips (created in element->renderListData())
	 * only contained first merged records data and not all merged records
	 *
	 * Collapses 'repeated joined' rows into a single row.
	 * If a group is not repeating we just use the first row's data (as subsequent rows will contain the same data
	 * Otherwise if the group is repeating we append each repeated record's data into the first row's data
	 * All rows execpt the first row for each group are then unset (as unique subsequent row's data will be contained within
	 * the first row)
	 *
	 * @param   array  &$data  list data
	 *
	 * @return  void
	 */

	protected function formatForJoins(&$data)
	{
		$merge = $this->mergeJoinedData();
		if (empty($merge))
		{
			return;
		}
		$listid = $this->getTable()->id;
		$dbprimaryKey = FabrikString::safeColNameToArrayKey($this->getTable()->db_primary_key);
		$formModel = $this->getFormModel();
		$db = $this->getDb();
		FabrikHelperHTML::debug($data, 'render:before formatForJoins');
		$count = count($data);

		$last_pk = '';
		$last_i = 0;
		$count = count($data);
		$can_repeats = array();
		$can_repeats_tables = array();
		$can_repeats_keys = array();
		$can_repeats_pk_vals = array();
		$remove = array();

		if (empty($data))
		{
			return;
		}
		/* First, go round first row of data, and prep some stuff.
		 * Basically, if doing a "reduce data" merge (merge == 2), we need to know what the
		* PK element is for each joined group (well, for each element, really)
		*/
		foreach ($data[0] as $key => $val)
		{
			$origKey = $key;
			$tmpkey = FabrikString::rtrimword($key, '_raw');
			/* $$$ hugh - had to cache this stuff, because if you have a lot of rows and a lot of elements,
			 * doing this many hundreds of times causes huge slowdown, exceeding max script execution time!
			* And we really only need to do it once for the first row.
			*/
			if (!isset($can_repeats[$tmpkey]))
			{
				$elementModel = $formModel->getElement($tmpkey);

				// $$$ rob - testing for linking join which is repeat but linked join which is not - still need separate info from linked to join
				// $can_repeats[$tmpkey] = $elementModel ? ($elementModel->getGroup()->canRepeat()) : 0;
				if ($merge == 2 && $elementModel)
				{
					if ($elementModel->getGroup()->canRepeat() || $elementModel->getGroup()->isJoin())
					{
						// We need to work out the PK of the joined table.
						// So first, get the table name.
						$group = $elementModel->getGroup();
						$join = $group->getJoinModel()->getJoin();
						$join_table_name = $join->table_join;

						// We have the table name, so see if we already have it cached ...
						if (!isset($can_repeats_tables[$join_table_name]))
						{
							// We don't have it yet, so grab the PK
							$keys = $this->getPrimaryKeyAndExtra($join_table_name);
							if (!empty($keys) && array_key_exists('key', $keys[0]))
							{
								// OK, now we have the PK for the table
								$can_repeats_tables[$join_table_name] = $keys[0];
							}
							else
							{
								// $$$ hugh - might be a view, so Hail Mary attempt to get PK
								$query = $db->getQuery(true);
								$query->select('db_primary_key')->from('#__{package}_lists')
								->where('db_table_name = ' . $db->quote($join_table_name));
								$db->setQuery($query);
								$join_pk = $db->loadResult();
								if (!empty($join_pk))
								{
									$can_repeats_tables[$join_table_name] = array('colname' => FabrikString::shortColName($join_pk));
								}
							}
						}
						// Hopefully we now have the PK
						if (isset($can_repeats_tables[$join_table_name]))
						{
							$can_repeats_keys[$tmpkey] = $join_table_name . '___' . $can_repeats_tables[$join_table_name]['colname'];
						}
						// Create the array if it doesn't exist
						if (!isset($can_repeats_pk_vals[$can_repeats_keys[$tmpkey]]))
						{
							$can_repeats_pk_vals[$can_repeats_keys[$tmpkey]] = array();
						}
						// Now store the
						if (!isset($can_repeats_pk_vals[$can_repeats_keys[$tmpkey]][0]))
						{
							$can_repeats_pk_vals[$can_repeats_keys[$tmpkey]][0] = $data[0]->$can_repeats_keys[$tmpkey];
						}
					}
				}
				$can_repeats[$tmpkey] = $elementModel ? ($elementModel->getGroup()->canRepeat() || $elementModel->getGroup()->isJoin()) : 0;
			}
		}

		for ($i = 0; $i < $count; $i++)
		{
			// $$$rob if rendering J article in PDF format __pk_val not in pdf table view
			$next_pk = isset($data[$i]->__pk_val) ? $data[$i]->__pk_val : $data[$i]->$dbprimaryKey;
			if (!empty($last_pk) && ($last_pk == $next_pk))
			{
				foreach ($data[$i] as $key => $val)
				{
					$origKey = $key;
					$tmpkey = FabrikString::rtrimword($key, '_raw');
					if ($can_repeats[$tmpkey])
					{
						if ($merge == 2 && !isset($can_repeats_pk_vals[$can_repeats_keys[$tmpkey]][$i]))
						{
							$can_repeats_pk_vals[$can_repeats_keys[$tmpkey]][$i] = $data[$i]->$can_repeats_keys[$tmpkey];
						}
						if ($origKey == $tmpkey)
						{
							/* $$$ rob - this was just appending data with a <br> but as we do thie before the data is formatted
							 * it was causing all sorts of issues for list rendering of links, dates etc. So now turn the data into
							* an array and at the end of this method loop over the data to encode the array into a json object.
							*/
							$do_merge = true;
							if ($merge == 2)
							{
								$pk_vals = array_count_values(array_filter($can_repeats_pk_vals[$can_repeats_keys[$tmpkey]]));
								if ($data[$i]->$can_repeats_keys[$tmpkey] != '')
								{
									if ($pk_vals[$data[$i]->$can_repeats_keys[$tmpkey]] > 1)
									{
										$do_merge = false;
									}
								}
							}
							if ($do_merge)
							{
								/* The raw data is not altererd at the moment - not sure that that seems correct but can't see any issues
								 * with it currently
								* $$$ hugh - added processing of raw data, needed for _raw placeholders
								* in things like custom links
								*/
								$data[$last_i]->$key = (array) $data[$last_i]->$key;
								array_push($data[$last_i]->$key, $val);
								$rawkey = $key . '_raw';
								$rawval = $data[$i]->$rawkey;
								$data[$last_i]->$rawkey = (array) $data[$last_i]->$rawkey;
								array_push($data[$last_i]->$rawkey, $rawval);
							}
						}
						else
						{
							/* $$$ hugh - don't think we need this, now we're processing _raw data?
							 if (!is_array($data[$last_i]->$origKey)) {
							$json= $val;
							$data[$last_i]->$origKey = json_encode($json);
							}
							*/
						}
					}

				}
				$remove[] = $i;
				continue;
			}
			else
			{
				if ($merge == 2)
				{
					foreach ($data[$i] as $key => $val)
					{
						$origKey = $key;
						$tmpkey = FabrikString::rtrimword($key, '_raw');
						if ($can_repeats[$tmpkey] && !isset($can_repeats_pk_vals[$can_repeats_keys[$tmpkey]][$i]))
						{
							$can_repeats_pk_vals[$can_repeats_keys[$tmpkey]][$i] = $data[$i]->$can_repeats_keys[$tmpkey];
						}
					}
				}
				$last_i = $i;

				// $$$rob if rendering J article in PDF format __pk_val not in pdf table view
				$last_pk = $next_pk;
			}
			// $$$ rob ensure that we have a sequental set of keys otherwise ajax json will turn array into object
			$data = array_values($data);
		}
		for ($c = count($remove) - 1; $c >= 0; $c--)
		{
			unset($data[$remove[$c]]);
		}
		// $$$ rob loop over any data that was merged into an array and turn that into a json object
		foreach ($data as $gkey => $d)
		{
			foreach ($d as $k => $v)
			{
				if (is_array($v))
				{
					foreach ($v as &$v2)
					{
						$v2 = FabrikWorker::JSONtoData($v2);
					}
					$v = json_encode($v);
					$data[$gkey]->$k = $v;
				}
			}
		}
		$data = array_values($data);
	}

	/**
	 * Does the list model have an associated table (can occur when form model
	 * which does not store in db, gets its list model)
	 *
	 * @return boolean
	 */

	public function noTable()
	{
		$id = $this->getId();
		return (bool) empty($id);
	}

	/**
	 * Save an individual element value to the fabrik db
	 *
	 * @param   string  $rowId  row id
	 * @param   string  $key    key
	 * @param   string  $value  value
	 *
	 * @return  void
	 */

	public function storeCell($rowId, $key, $value)
	{
		$data[$key] = $value;

		// Ensure the primary key is set in $data
		$primaryKey = FabrikString::shortColName($this->getTable()->db_primary_key);
		$primaryKey = str_replace("`", "", $primaryKey);
		if (!isset($data[$primaryKey]))
		{
			$data[$primaryKey] = $rowId;
		}
		$this->storeRow($data, $rowId);
	}

	/**
	 * Increment a value in a cell
	 *
	 * @param   string  $rowId  row's id
	 * @param   string  $key    field to increment
	 * @param   string  $dir    -1/1 etc
	 *
	 * @return  bool
	 */

	public function incrementCell($rowId, $key, $dir)
	{
		$db = $this->getDb();
		$table = $this->getTable();
		$query = "UPDATE $table->db_table_name SET $key = COALESCE($key, 0)  + $dir WHERE $table->db_primary_key = " . $db->quote($rowId);
		$db->setQuery($query);
		return $db->query();
	}

	/**
	 * Set model sate
	 *
	 * @return  void
	 */

	protected function populateState()
	{
		$app = JFactory::getApplication('site');
		if (!$app->isAdmin())
		{
			// Load the menu item / component parameters.
			$params = $app->getParams();
			$this->setState('params', $params);

			// Load state from the request.
			$pk = JRequest::getInt('listid', $params->get('listid'));
		}
		else
		{
			$pk = JRequest::getInt('listid');
		}
		$this->setState('list.id', $pk);
		$offset = JRequest::getInt('limitstart');
		$this->setState('list.offset', $offset);
	}

	/**
	 * Get the output format
	 *
	 * @return  string	outputformat
	 */

	public function getOutPutFormat()
	{
		return $this->outPutFormat;
	}

	/**
	 * Set the list output format
	 *
	 * @param   string  $f  format html/pdf/raw/csv
	 *
	 * @return  void
	 */
	public function setOutPutFormat($f)
	{
		$this->outPutFormat = $f;
	}

	/**
	 * Update a series of rows with a key = val , works across joined tables
	 *
	 * @param   array   $ids  pk values to update
	 * @param   string  $col  key to update should be in format 'table.element'
	 * @param   string  $val  val to set to
	 *
	 * @return  void
	 */

	public function updateRows($ids, $col, $val)
	{
		if ($col == '')
		{
			return;
		}
		if (empty($ids))
		{
			return;
		}
		$db = $this->getDb();
		$nav = $this->getPagination(1, 0, 1);
		$data = $this->getData();

		// $$$ rob dont unshift as this messes up for grouped data
		// $data = array_shift($data);
		$table = $this->getTable();

		$update = $col . ' = ' . $db->quote($val);
		$colbits = explode('.', $col);
		$tbl = array_shift($colbits);

		$joinFound = false;
		JArrayHelper::toInteger($ids);
		$ids = implode(',', $ids);
		$dbk = $k = $table->db_primary_key;

		$joins = $this->getJoins();

		// If the update element is in a join replace the key and table name with the join table's name and key
		foreach ($joins as $join)
		{
			if ($join->table_join == $tbl)
			{
				$joinFound = true;
				$db->setQuery('DESCRIBE ' . $tbl);
				$fields = $db->loadObjectList('Key');
				$k = $tbl . '___' . $fields['PRI']->Field;
				$dbk = $tbl . '.' . $fields['PRI']->Field;
				$db_table_name = $tbl;
				$ids = array();
				foreach ($data as $groupdata)
				{
					foreach ($groupdata as $d)
					{
						$v = $d->{$k . '_raw'};
						if ($v != '')
						{
							$ids[] = $v;
						}
					}
				}
				if (!empty($ids))
				{
					$query = $db->getQuery(true);
					$ids = implode(',', $ids);
					$query->update($db_table_name)->set($update)->where($dbk . ' IN (' . $ids . ')');
					$db->setQuery($query);
					$db->query();
				}
			}
		}
		if (!$joinFound)
		{
			$db_table_name = $table->db_table_name;
			$query = $db->getQuery(true);
			$query->update($db_table_name)->set($update)->where($dbk . ' IN (' . $ids . ')');
			$db->setQuery($query);
			$db->query();
		}
	}

	/**
	 * unset a series of model properties
	 *
	 * @return  void
	 */

	public function reset()
	{
		unset($this->_whereSQL);
		unset($this->_table);
		unset($this->filters);
		unset($this->prefilters);
		unset($this->_params);
		unset($this->viewfilters);

		// $$$ hugh - added some more stuff to clear, as per:
		// http://fabrikar.com/forums/showthread.php?p=115122#post115122
		unset($this->asfields);
		unset($this->_oForm);
		unset($this->filterModel);
		unset($this->searchAllAsFields);
		unset($this->_joinsSQL);
		unset($this->_aJoins);
		unset($this->_joinsNoCdd);
		unset($this->elements);
		unset($this->_data);
	}

	/**
	 * Get the table template
	 *
	 * @since 3.0
	 *
	 * @return string template name
	 */

	public function getTmpl()
	{
		if (!isset($this->tmpl))
		{
			$app = JFactory::getApplication();
			$input = $app->input;
			$item = $this->getTable();
			$params = $this->getParams();
			$document = JFactory::getDocument();
			if ($app->isAdmin())
			{
				//$this->tmpl = JRequest::getVar('layout', $params->get('admin_template'));
				$this->tmpl = $input->get('layout', $params->get('admin_template'));
			}
			else
			{
				//$this->tmpl = JRequest::getVar('layout', $item->template);
				$this->tmpl = $input->get('layout', $item->template);
				if ($app->scope !== 'mod_fabrik_list')
				{
					$this->tmpl = FabrikWorker::getMenuOrRequestVar('fabriklayout', $this->tmpl, $this->isMambot);
					/* $$$ rob 10/03/2012 changed menu param to listlayout to avoid the list menu item
					 * options also being used for the form/details view template
					*/
					$this->tmpl = FabrikWorker::getMenuOrRequestVar('listlayout', $this->tmpl, $this->isMambot);
				}
			}
			if ($this->tmpl == '')
			{
				$this->tmpl = 'default';
			}

			// If we are mobilejoomla.com system plugin to detect smartphones
			if (JRequest::getVar('mjmarkup') == 'iphone')
			{
				$this->tmpl = 'iwebkit';
			}
			if ($document->getType() === 'pdf')
			{
				$this->tmpl = $params->get('pdf_template', $this->tmpl);
			}
		}
		return $this->tmpl;
	}

	/**
	 * Set the lists elements' tempate to that of the list's
	 *
	 * @return  void
	 */

	protected function setElementTmpl()
	{
		$tmpl = $this->getTmpl();
		$groups = $this->getFormModel()->getGroupsHiarachy();
		$params = $this->getParams();
		foreach ($groups as $groupModel)
		{
			if (($params->get('group_by_template', '') !== '' && $this->getGroupBy() != '') || $this->outPutFormat == 'csv'
					|| $this->outPutFormat == 'feed')
			{
				$elementModels = $groupModel->getPublishedElements();
			}
			else
			{
				$elementModels = $groupModel->getPublishedListElements();
			}
			foreach ($elementModels as $elementModel)
			{
				$elementModel->tmpl = $tmpl;
			}
		}
	}

	public function inJDb()
	{
		$config = JFactory::getConfig();
		$cnn = $this->getConnection()->getConnection();
		/* if the table database is not the same as the joomla database then
		 * we should simply return a hidden field with the user id in it.
		*/
		return $config->get('db') == $cnn->database;
	}

	/**
	 * @since 3.0 loads lists's css files
	 * Checks : J template html override css file then fabrik list tmpl template css file. Including them if found
	 */

	public function getListCss()
	{
		$tmpl = $this->getTmpl();
		$app = JFactory::getApplication();
		/* check for a form template file (code moved from view) */
		if ($tmpl != '')
		{
			$qs = '?c=' . $this->getRenderContext();

			// $$$rob need &amp; for pdf output which is parsed through xml parser otherwise fails
			$qs .= '&amp;buttoncount=' . $this->rowActionCount;
			$overRide = 'templates/' . $app->getTemplate() . '/html/com_fabrik/list/' . $tmpl . '/template_css.php' . $qs;
			if (!FabrikHelperHTML::stylesheetFromPath($overRide))
			{
				FabrikHelperHTML::stylesheetFromPath('components/com_fabrik/views/list/tmpl/' . $tmpl . '/template_css.php' . $qs);
			}
			/* $$$ hugh - as per Skype convos with Rob, decided to re-instate the custom.css convention.  So I'm adding two files:
			 * custom.css - for backward compat with existing 2.x custom.css
			* custom_css.php - what we'll recommend people use for custom css moving foward.
			*/
			if (!FabrikHelperHTML::stylesheetFromPath('templates/' . $app->getTemplate() . '/html/com_fabrik/list/' . $tmpl . '/custom.css' . $qs))
			{
				FabrikHelperHTML::stylesheetFromPath('components/com_fabrik/views/list/tmpl/' . $tmpl . '/custom.css');
			}
			if (!FabrikHelperHTML::stylesheetFromPath('templates/' . $app->getTemplate() . '/html/com_fabrik/list/' . $tmpl . '/custom_css.php' . $qs))
			{
				FabrikHelperHTML::stylesheetFromPath('components/com_fabrik/views/list/tmpl/' . $tmpl . '/custom_css.php' . $qs);
			}
		}
	}

	public function getRenderContext()
	{
		if ($this->renderContext === '')
		{
			$this->setRenderContext($this->getId());
		}
		return $this->getId() . $this->renderContext;
	}

	/**
	 * lists can be rendered in articles, as components and in modules
	 * we need to set a unique reference for them to avoid conflicts
	 *
	 * @param   int  $id  module/component list id
	 *
	 * @return  void
	 */

	public function setRenderContext($id = null)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$task = $input->getCmd('task');
		if (strstr($task, '.'))
		{
			$task = explode('.', $task);
			$task = array_pop($task);
		}

		// $$$ rob if admin filter task = filter and not list.filter
		if ($task == 'filter' || ($app->isAdmin() && JRequest::getVar('task') == 'filter'))
		{
			$this->setRenderContextFromRequest();
		}
		else
		{
			if (((JRequest::getVar('task') == 'list.view' || JRequest::getVar('task') == 'list.delete') && JRequest::getVar('format') == 'raw')
					|| JRequest::getVar('layout') == '_advancedsearch' || JRequest::getVar('task') === 'list.elementFilter'
					|| JRequest::getVar('setListRefFromRequest') == 1)
			{
				// Testing for ajax nav in content plugin or in advanced search
				$this->setRenderContextFromRequest();
			}
			else
			{
				$this->renderContext = '_' . JFactory::getApplication()->scope . '_' . $id;
			}
		}
		if ($this->renderContext == '')
		{
			$this->renderContext = '_' . JFactory::getApplication()->scope . '_' . $id;
		}
	}

	/**
	 * When dealing with ajax requests filtering etc we want to take the listref from the
	 * request array
	 *
	 * @return  string	listref
	 */

	protected function setRenderContextFromRequest()
	{
		$listref = JRequest::getVar('listref', '');
		if ($listref === '')
		{
			$this->renderContext = '';
		}
		else
		{
			$listref = explode('_', $listref);
			array_shift($listref);
			$this->renderContext = '_' . implode('_', $listref);
		}
		return $this->renderContext;
	}

	/**
	 * Get lists group by headings
	 *
	 * @return   array  heading names
	 */

	public function getGroupByHeadings()
	{
		$base = JURI::getInstance();
		$base = $base->toString(array('scheme', 'user', 'pass', 'host', 'port', 'path'));
		//$base .= JString::strpos($base, '?') ? '&' : '?';
		$qs = JRequest::getVar('QUERY_STRING', '', 'server');
		if (JString::stristr($qs, 'group_by'))
		{
			$qs = FabrikString::removeQSVar($qs, 'group_by');
			$qs = FabrikString::ltrimword($qs, '?');
		}
		$url = $base;
		if (!empty($qs))
		{
			$url .= JString::strpos($url, '?') !== false ? '&amp;' : '?';
			$url .= $qs;
		}
		$url .= JString::strpos($url, '?') !== false ? '&amp;' : '?';
		$a = array();
		list($h, $x, $b, $c) = $this->getHeadings();
		$a[$url . 'group_by=0'] = JText::_('COM_FABRIK_NONE');
		foreach ($h as $key => $v)
		{
			if (!in_array($key, array('fabrik_select', 'fabrik_edit', 'fabrik_view', 'fabrik_delete', 'fabrik_actions')))
			{
				$thisurl = $url . 'group_by=' . $key;
				$a[$thisurl] = strip_tags($v);
			}
		}
		return $a;
	}

	/**
	 * Get a list of elements to export in the csv file.
	 *
	 * @since 3.0b
	 *
	 * @return array full element names.
	 */

	public function getCsvFields()
	{
		$params = $this->getParams();
		$formModel = $this->getFormModel();
		$csvFields = array();

		if ($params->get('csv_which_elements', 'selected') == 'visible')
		{
			$csvIds = $this->getAllPublishedListElementIDs();
		}
		else if ($params->get('csv_which_elements', 'selected') == 'all')
		{
			// Export code will export all, if list is empty
			$csvIds = array();
		}
		else if ($params->get('csv_elements') == '' || $params->get('csv_elements') == 'null')
		{
			$csvIds = array();
		}
		else
		{
			$csvIds = json_decode($params->get('csv_elements'))->show_in_csv;
		}
		foreach ($csvIds as $id)
		{
			if ($id !== '')
			{
				$elementModel = $formModel->getElement($id, true);
				if ($elementModel !== false)
				{
					$csvFields[$elementModel->getFullName(false, true, false)] = 1;
				}

			}
		}
		return $csvFields;
	}

	/**
	 * Helper function for view to determine if filters should be shown
	 *
	 * @return  bool
	 */

	public function getShowFilters()
	{
		$filters = $this->getFilters('listform_' . $this->getRenderContext());
		$params = $this->getParams();
		$filterMode = (int) $params->get('show-table-filters');
		return (count($filters) > 0 && $filterMode !== 0) && JRequest::getVar('showfilters', 1) == 1 ? true : false;
	}

	/**
	 * Get the number of buttons that are rendered for the list
	 *
	 * @return  number
	 */

	protected function getButtonCount()
	{
		$buttonCount = 0;
		return $buttonCount;
	}

	/**
	 * Helper view function to determine if any buttons are shown
	 *
	 * @return  bool
	 */

	public function getHasButtons()
	{
		$params = $this->getParams();
		if (($this->canAdd() && $params->get('show-table-add')) || $this->getShowFilters() || $this->getAdvancedSearchLink() || $this->canGroupBy() || $this->canCSVExport()
				|| $this->canCSVImport() || $params->get('rss') || $params->get('pdf') || $this->canEmpty())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Compacts the ordering sequence of the selected records
	 *
	 * @param   string  $colid  column name to order on
	 * @param   string  $where  additional where query to limit ordering to a particular subset of records
	 *
	 * @since   3.0.5
	 *
	 * @return  bool
	 */

	public function reorder($colid, $where = '')
	{
		$elementModel = $this->getFormModel()->getElement($colid, true);
		$asfields = array();
		$fields = array();
		$elementModel->getAsField_html($asfields, $fields);
		$col = $asfields[0];
		$field = array_shift(explode("AS", $col));
		$db = $this->getDb();
		$k = $this->getTable()->db_primary_key;
		$shortKey = FabrikString::shortColName($k);
		$tbl = $this->getTable()->db_table_name;

		$query = $db->getQuery(true);
		$query->select(array($k, $col))->from($tbl);
		if ($where !== '')
		{
			$query->where($where);
		}
		$query = $this->_buildQueryOrder($query);

		$dir = JString::strtolower(JArrayHelper::getValue($this->orderDirs, 0, 'asc'));
		$db->setQuery($query);
		if (!($orders = $db->loadObjectList()))
		{
			$this->setError($db->getErrorMsg());
			return false;
		}
		$kk = trim(FabrikString::safeColNameToArrayKey($field));

		// Compact the ordering numbers
		for ($i = 0, $n = count($orders); $i < $n; $i++)
		{
			$o = $orders[$i];
			$neworder = ($dir == 'asc') ? $i + 1 : $n - $i;
			$orders[$i]->$kk = $neworder;
			$query->clear();
			$query->update($tbl)->set($field . ' = ' . (int) $orders[$i]->$kk)->where($k . ' = ' . $this->_db->quote($orders[$i]->$shortKey));
			$db->setQuery($query);
			$db->query();
		}
		return true;
	}

	/**
	 * Load the JS files into the document
	 *
	 * @param   array  &$srcs  reference: js script srcs to load in the head
	 *
	 * @return  null
	 */

	public function getCustomJsAction(&$srcs)
	{
		if (JFile::exists(COM_FABRIK_FRONTEND . '/js/table_' . $this->getId() . '.js'))
		{
			$srcs[] = 'components/com_fabrik/js/table_' . $this->getId() . '.js';
		}
		if (JFile::exists(COM_FABRIK_FRONTEND . '/js/list_' . $this->getId() . '.js'))
		{
			$srcs[] = 'components/com_fabrik/js/list_' . $this->getId() . '.js';
		}
	}

	/**
	 * When saving an element it can effect the list parameters, update them here.
	 *
	 * @param   object  $elementModel  element model
	 *
	 * @since 3.0.6
	 *
	 * @return  void
	 */

	public function updateFromElement($elementModel)
	{
		$elParams = $elementModel->getParams();
		$add = $elParams->get('inc_in_search_all');
		$params = $this->getParams();
		$p = json_decode($params->get('list_search_elements'));
		$elementId = $elementModel->getId();
		if (is_object($p) && is_array($p->search_elements))
		{
			if ($add)
			{
				if (!in_array($elementId, $p->search_elements))
				{
					$p->search_elements[] = (string) $elementId;
				}
			}
			else
			{
				$k = array_search($elementId, $p->search_elements);
				if ($k !== false)
				{
					unset($p->search_elements[$k]);
				}
			}
			$params->set('list_search_elements', json_encode($p));
		}
		$item = $this->getTable();
		$item->params = (string) $params;
		$item->store();
	}

	/**
	 * Get / set formatAll, which forces formatData() to ignore 'show in table'
	 * and just format everything, needed by things like the table email plugin.
	 * If called without an arg, just returns current setting.
	 *
	 * $$$ hugh - doesn't work, now that finesseData() is called via call_user_func().
	 *
	 * @param   bool  $format_all  optional arg to set format
	 *
	 * @return  bool
	 */

	public function formatAll($format_all = null)
	{
		if (isset($format_all))
		{
			$this->_format_all = $format_all;
		}
		return $this->_format_all;
	}

	/**
	 * Copy rows
	 *
	 * @param   mixed  $ids  array or string of row ids to copy
	 *
	 * @since	3.0.6
	 *
	 * @return  bool	all rows copied (true) or false if a row copy fails.
	 */

	public function copyRows($ids)
	{
		$ids = (array) $ids;
		$formModel = $this->getFormModel();
		$formModel->copyingRow(true);
		$state = true;
		foreach ($ids as $id)
		{
			$formModel->_rowId = $id;
			$formModel->unsetData();
			$row = $formModel->getData();
			$row['Copy'] = '1';
			$row['fabrik_copy_from_table'] = '1';
			$formModel->_formData = $row;
			if (!$formModel->process())
			{
				$state = false;
			}
		}
		return $state;
	}

	/**
	 * Return an array of element ID's of all published and visible list elements
	 * Created to call from GetCsvFields()
	 *
	 * @return   array  array of element IDs
	 */
	public function getAllPublishedListElementIDs()
	{
		$ids = array();
		$form = $this->getFormModel();
		$groups = $form->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			/*
			if ($groupModel->canView() === false)
			{
				continue;
			}
			*/
			$elementModels = $groupModel->getPublishedListElements();
			foreach ($elementModels as $key => $elementModel)
			{
				$ids[] = $elementModel->getId();
			}
		}
		return $ids;
	}

	/**
	 * Return an array of elements which are set to always render
	 *
	 * @param   bool  not_shown_only  Only return elements which have 'always render' enabled, AND are not displayed in the list
	 *
	 * @return   bool  array of element models
	 */

	public function getAlwaysRenderElements($not_shown_only = true)
	{
		$form = $this->getFormModel();
		$alwaysRender = array();
		$groups = $form->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				if ($elementModel->isAlwaysRender($not_shown_only))
				{
					$alwaysRender[] = $elementModel;
				}
			}
		}
		return $alwaysRender;
	}

	/**
	 * Does the list have any 'always render' elements?
	 *
	 * @return   bool
	 */
	public function hasAlwaysRenderElements()
	{
		$alwaysRender = $this->getAlwaysRenderElements();
		return !empty($alwaysRender);
	}
}
