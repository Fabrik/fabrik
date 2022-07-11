<?php
/**
 * Insert Fabrik Content into Joomla Articles
 *
 * @package     Joomla.Plugin
 * @subpackage  Content.fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

/**
 * Fabrik content plugin - renders forms, lists and visualizations
 *
 * @package     Joomla.Plugin
 * @subpackage  Content.fabrik
 * @since       1.5
 */
class PlgContentFabrik extends JPlugin
{
	/**
	 *  Prepare content method
	 *
	 * Method is called by the view
	 *
	 * @param   string $context The context of the content being passed to the plugin.
	 * @param   object &$row    The article object.  Note $article->text is also available
	 * @param   object &$params The article params
	 * @param   int    $page    The 'page' number
	 *
	 * @return  void
	 */
	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		jimport('joomla.html.parameter');
		jimport('joomla.filesystem.file');

		// Load fabrik language
		$lang = JFactory::getLanguage();
		$lang->load('com_fabrik', JPATH_BASE . '/components/com_fabrik');

		// Don't throw an exception, just don't do nuthin' if system plugin hasn't run
		if (!defined('COM_FABRIK_FRONTEND'))
		{
			//throw new RuntimeException(JText::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'), 400);
			return true;
		}

		// Get plugin info
		$plugin = JPluginHelper::getPlugin('content', 'fabrik');

		// $$$ hugh had to rename this, it was stomping on com_content and friends $params
		// $$$ which is passed by reference to us!
		$fParams = new JRegistry($plugin->params);

		// Simple performance check to determine whether bot should process further
		$botRegex = $fParams->get('botRegex') != '' ? $fParams->get('botRegex') : 'fabrik';

		if (JString::strpos($row->text, '{' . $botRegex) === false)
		{
			return true;
		}

		/* $$$ hugh - hacky fix for nasty issue with IE, which (for gory reasons) doesn't like having our JS content
		 * wrapped in P tags.  But the default WYSIWYG editor in J! will automagically wrap P tags around everything.
		 * So let's just look for obvious cases of <p>{fabrik ...}</p>, and replace the P's with DIV's.
		 * Yes, it's hacky, but it'll save us a buttload of support work.
		 */
		$pregex    = "/<p>\s*{" . $botRegex . "\s*.*?}\s*<\/p>/i";
		$row->text = preg_replace_callback($pregex, array($this, 'preplace'), $row->text);

		// $$$ hugh - having to change this to use {[]}
		$regex     = "/{" . $botRegex . "\s+.*?}/i";
		$row->text = preg_replace_callback($regex, array($this, 'replace'), $row->text);
	}

	/**
	 * Unwrap placeholder text from possible <p> tags
	 *
	 * @param   array $match preg matched {fabrik} tag
	 *
	 * @return  string
	 */
	protected function preplace($match)
	{
		$match = $match[0];
		$match = JString::str_ireplace('<p>', '<div>', $match);
		$match = JString::str_ireplace('</p>', '</div>', $match);

		return $match;
	}

	/**
	 * Parse the {fabrik} tag
	 *
	 * @param   array $match {fabrik} preg match
	 *
	 * @return  string
	 */
	protected function parse($match)
	{
		$match = $match[0];

		// $$$ hugh - see if we can remove formatting added by WYSIWYG editors
		$match = strip_tags($match);
		$w     = new FabrikWorker;
		$match = preg_replace('/\s+/', ' ', $match);
		/* $$$ hugh - only replace []'s in value, not key, so we handle
		 * ranged filters and 'complex' filters
		 */
		$match2 = array();

		foreach (explode(" ", $match) as $m)
		{
			if (strstr($m, '='))
			{
				list($key, $val) = explode('=', $m);
				$val      = str_replace('[', '{', $val);
				$val      = str_replace(']', '}', $val);
				$match2[] = $key . '=' . $val;
			}
			else
			{
				$match2[] = $m;
			}
		}

		$match = implode(' ', $match2);
		$w->replaceRequest($match);

		// Stop [] for ranged filters from being removed
		// $match = str_replace('{}', '[]', $match);
		$match = $w->parseMessageForPlaceHolder($match);

		return $match;
	}

	/**
	 * the function called from the preg_replace_callback - replace the {} with the correct HTML
	 *
	 * @param   string $match plug-in match
	 *
	 * @return  void
	 */
	protected function replace($match)
	{
		$app   = JFactory::getApplication();
		$input = $app->input;
		$match = $match[0];
		$match = trim($match, "{");
		$match = trim($match, "}");
		$ref   = preg_replace('/[^A-Z|a-z|0-9]/', '_', $match);
		$match = $this->parse(array($match));
		$match = explode(" ", $match);
		array_shift($match);
		$user        = JFactory::getUser();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$unused      = array();

		// Special case if we are wanting to write in an element's data
		$element       = false;
		$repeatCounter = 0;

		// Was defaulting to 1 but that forced filters to show in cal viz even with showfilter=no option turned on
		$showFilters           = $input->get('showfilters', null);
		$clearFilters          = $input->get('clearfilters', 0);
		$resetFilters          = $input->get('resetfilters', 0);
		$this->origRequestVars = array();
		$id                    = 0;
		$origLayout            = $input->get('layout');
		$origFLayout           = $input->get('flayout');
		$layoutFound           = false;
		$rowId                 = '';
		$useKey                = '';
		$limit                 = false;
		$ajax                  = true;
		$session               = JFactory::getSession();
		$usersConfig->set('rowid', 0);
		$viewName = '';

		foreach ($match as $m)
		{
			$m = explode('=', $m);

			// $$$ hugh - deal with %20 as space in arguments
			$m[1] = urldecode(FArrayHelper::getValue($m, 1));

			switch ($m[0])
			{
				case 'view':
					$viewName = JString::strtolower($m[1]);
					break;
				case 'id':
				case 'formid':
				case 'listid':
					// Cast to int in case there are two spaces after value.
					$id = (int) $m[1];
					break;
				case 'layout':
					$layoutFound = true;
					$layout      = $m[1];
					$input->set('layout', $layout);
					break;
				case 'row':
				case 'rowid':
					$row = $m[1];

					// When printing the content the rowid can't be passed in the querystring so don't set here
					if ($row !== '{rowid}')
					{
						if ($row == -1)
						{
							$row = $user->get('id');
						}

						$usersConfig->set('rowid', $row);

						// Set the rowid in the session so that print pages can grab it again
						$session->set('fabrik.plgcontent.rowid', $row);
						$rowId = $row;
					}
					break;
				case 'element':
					// {fabrik view=element list=3 rowid=364 element=fielddatatwo}
					$viewName = 'list';
					$element  = $m[1];
					break;
				case 'table':
				case 'list':
					$listId = $m[1];
					break;
				case 'limit':
					$limit = $m[1];
					break;
				case 'usekey':
					$useKey = $m[1];
					break;
				case 'repeatcounter':
					$repeatCounter = $m[1];
					break;
				case 'showfilters':
					$showFilters = $m[1];
					break;
				case 'ajax':
					$ajax = (bool) $m[1];
					break;
				// $$$ rob for these 2 grab the qs var in priority over the plugin settings
				case 'clearfilters':
					$clearFilters = $input->get('clearfilters', $m[1]);
					break;
				case 'resetfilters':
					$resetFilters = $input->get('resetfilters', $m[1]);
					break;
				default:
					if (array_key_exists(1, $m))
					{
						/**
						 * These are later set as app->input vars if present in list view
						 * html_entity_decode to allow for content plugin values to contain &nbsp;
						 * Urlencode the value for plugin statements such as: asylum_events___start_date[condition]=>
						 */
						$unused[] = trim($m[0]) . '=' . urlencode(html_entity_decode($m[1], ENT_NOQUOTES));
					}
			}
		}
		// Get the rowid in the session so that print pages can use it

		// Commented out - was messing with element rendering and seems to general to be correct. IE what if more than one content plugin being used
		// $rowId = $session->get('fabrik.plgcontent.rowid', $rowId);
		if ($viewName == 'table')
		{
			// Some backwards compat with fabrik 2
			$viewName = 'list';
		}

		// Moved out of switch as otherwise first plugin to use this will effect all subsequent plugins
		$input->set('usekey', $useKey);

		// If no layout defined - set it
		if (!$layoutFound)
		{
			$thisLayout = $input->get('layout');

			// Also test for saving an article
			if ($input->get('option') === 'com_content' && ($thisLayout === 'blog' || ($app->isAdmin() && $thisLayout === 'edit')))
			{
				$layout = 'default';
				$input->set('layout', $layout);
			}
		}

		/* $$$ hugh - added this so the fabrik2article plugin can arrange to have form CSS
		 * included when the article is rendered by com_content, by inserting ...
		 * {fabrik view=form_css id=X layout=foo}
		 * ... at the top of the article.
		 */
		if ($viewName == 'form_css')
		{
			// The getFormCss() call blows up if we don't do this
			jimport('joomla.filesystem.file');
			$this->generalIncludes('form');
			$controller = $this->getController('form', $id);
			$view       = $this->getView($controller, 'form', $id);
			$model      = $this->getModel($controller, 'form', $id);

			if (!$model)
			{
				return;
			}

			$model->setId($id);
			$model->setEditable(false);
			$layout = !empty($layout) ? $layout : 'default';
			$view->setModel($model, true);
			$model->getFormCss($layout);

			return '';
		}

		$this->generalIncludes($viewName);

		$origRowId = $input->get('rowid');
		// Allow plugin to reference the origin rowid in the URL
		$input->set('origRowId', $origRowId);

		if ($element !== false)
		{
			// Special case for rendering element data
			$controller = $this->getController('list', $listId);
			$model      = $this->getModel($controller, 'list', $listId);

			if (!$model)
			{
				return;
			}

			$model->setId($listId);
			$formModel = $model->getFormModel();
			$groups    = $formModel->getGroupsHiarachy();

			foreach ($groups as $groupModel)
			{
				$elements = $groupModel->getMyElements();

				foreach ($elements as &$elementModel)
				{
					// $$$ rob 26/05/2011 changed it so that you can pick up joined elements without specifying plugin
					// param 'element' as joinx[x][fullname] but simply 'fullname'
					if ($element == $elementModel->getFullName(true, false))
					{
						$activeEl = $elementModel;
						continue 2;
					}
				}
			}

			// $$$ hugh in case they have a typo in their elementname
			if (empty($activeEl) || !$activeEl->isPublished())
			{
				throw new RuntimeException('You are trying to embed an element called ' . $element . ' which is not present in the list or has been unpublished');
			}

			if ($rowId === '')
			{
				$rows  = $model->getData();
				$group = array_shift($rows);
				$row   = array_shift($group);

				if (empty($row))
				{
					return;
				}

				$res   = $row->$element;
			}
			else
			{
				$this->_setRequest($unused);
				$row = $model->getRow($rowId, false, true);

				if (substr($element, JString::strlen($element) - 4, JString::strlen($element)) !== '_raw')
				{
					$element = $element . '_raw';
				}
				// $$$ hugh - need to pass all row data, or calc elements that use {placeholders} won't work
				//$defaultData = is_object($row) ? get_object_vars($row) : $row;
				$defaultData = is_object($row) ? FArrayHelper::fromObject($row, true) : $row;

				/* $$$ hugh - if we don't do this, our passed data gets blown away when render() merges the form data
				 * not sure why, but apparently if you do $foo =& $bar and $bar is NULL ... $foo ends up NULL
				 */
				$activeEl->getFormModel()->data = $defaultData;
				$activeEl->editable             = false;

				// Set row id for things like user element
				$input->set('rowid', $rowId);
				
				// Set detail view for things like youtube element
				$origView = $input->get('view');
				$input->set('view', 'details');

				$defaultData = (array) $defaultData;
				unset($activeEl->defaults);

				if ($repeatCounter === 'all')
				{
					$repeat = $activeEl->getGroupModel()->repeatCount();
					$res    = array();

					for ($j = 0; $j < $repeat; $j++)
					{
						$res[] = $activeEl->render($defaultData, $j);
					}

					$res = count($res) > 1 ? '<ul><li>' . implode('</li><li>', $res) . '</li></ul>' : $res[0];
				}
				else
				{
					$res = $activeEl->render($defaultData, $repeatCounter);
					$ref = $activeEl->elementJavascript($repeatCounter);
				}

				$input->set('rowid', $origRowId);
				$input->set('view', $origView);
				$input->set('origRowId', '');
				$this->resetRequest();
			}

			return $res;
		}

		if (!isset($viewName))
		{
			return;
		}

		$origId   = $input->get('id', '', 'string');
		$origView = $input->get('view');

		// Allow for random=1 (which has to map to fabrik_random for list views)
		$origRandom = $input->get('fabrik_random');

		// For fabble
		$input->set('origid', $origId);
		$input->set('origview', $origView);

		$input->set('id', $id);
		$input->set('view', $viewName);

		/*
		 * $$$ hugh - at least make the $origId available for certain corner cases, like ...
		 * http://fabrikar.com/forums/showthread.php?p=42960#post42960
		 */
		$input->set('origid', $origId, 'GET', false);
		$cacheKey = $id;

		if ($rowId !== '')
		{
			$cacheKey .= '.' . $rowId;
		}

		$controller = $this->getController($viewName, $cacheKey);
		$view       = $this->getView($controller, $viewName, $cacheKey);

		if ($model = $this->getModel($controller, $viewName, $cacheKey))
		{
			$view->setModel($model, true);
		}

		// Display the view
		$view->error    = $controller->getError();
		$view->isMambot = true;
		$displayed      = false;

		// Do some view specific code
		switch ($viewName)
		{
			case 'form_css':
				$model->getFormCss();
				break;
			case 'form':
			case 'details':
				if ($id === 0)
				{
					$app->enqueueMessage('No id set in fabrik plugin declaration', 'warning');

					return;
				}

				$model->ajax = $ajax;
				$model->setId($id);

				unset($model->groups);

				// Set default values set in plugin declaration
				// - note cant check if the form model has the key' as its not yet loaded
				$this->_setRequest($unused);

				// $$$ rob - flayout is used in form/details view when _isMamot = true
				$input->set('flayout', $input->get('layout'));
				$input->set('rowid', $rowId);
				break;
			case 'csv':
			case 'table':
			case 'list': /* $$$ rob 15/02/2011 added this as otherwise when you filtered on a table
						  * with multiple filter set up subsequent tables were showing
						  * the first tables data
						  */
				if ($input->get('activelistid') == '')
				{
					$input->set('activelistid', $input->getId('listid'));
				}

				$input->set('listid', $id);

				// Allow for simple limit=2 in plugin declaration
				if ($limit)
				{
					$limitKey                         = 'limit' . $id;
					$this->origRequestVars[$limitKey] = $input->get($limitKey);
					$input->set($limitKey, $limit);
				}

				$this->_setRequest($unused);

				$input->set('fabrik_random', $input->get('random', $origRandom));

				$input->set('showfilters', $showFilters);
				$input->set('clearfilters', $clearFilters);
				$input->set('resetfilters', $resetFilters);

				if ($id === 0)
				{
					$app->enqueueMessage('No id set in fabrik plugin declaration', 'warning');

					return;
				}

				$model->setId($id);
				$model->isMambot = true;

				/**
				 *
				 * Reset this otherwise embedding a list in a list menu page, the embedded list takes the show in list fields from the menu list
				 *
				 * $$$ hugh - nasty little hack to reduce 'emptyish' array, 'cos if no 'elements' in the request, the following ends up setting
				 * returning an array with a single empty string.  This ends up meaning that we render a list with no
				 * elements in it.  We've run across this before, so we have a FArrayHelper:;emptyish() to detect it.
				 */

				$show_in_list = explode('|', $input->getString('elements', ''));

				if (FArrayHelper::emptyIsh($show_in_list, true))
				{
					$show_in_list = array();
				}

				$input->set('fabrik_show_in_list', $show_in_list);
				$model->ajax = $ajax;
				$task        = $input->get('task');

				if (method_exists($controller, $task) && $input->getInt('activetableid') == $id)
				{
					/*
					 * Enable delete() of rows
					 * list controller deals with display after tasks is called
					 * set $displayed to true to stop controller running twice
					 */
					$displayed = true;
					ob_start();
					$controller->$task();
					$result = ob_get_contents();
					ob_end_clean();
				}

				$model->setOrderByAndDir();
				$formModel = $model->getFormModel();
				break;

			case 'visualization':
				$input->set('showfilters', $showFilters);
				$input->set('clearfilters', $clearFilters);
				$input->set('resetfilters', $resetFilters);
				$this->_setRequest($unused);
				break;
		}

		// Hack for gallery viz as it may not use the default view
		$controller->isMambot = true;

		if (!$displayed)
		{
			ob_start();

			if (method_exists($model, 'reset'))
			{
				$model->reset();
			}

			$controller->display($model);
			$result = ob_get_contents();
			ob_end_clean();
		}

		$input->set('id', $origId);
		$input->set('view', $origView);

		if ($origLayout != '')
		{
			$input->set('layout', $origLayout);
		}

		if ($origFLayout != '')
		{
			$input->set('flayout', $origFLayout);
		}

		if ($origRandom)
		{
			$input->set('fabrik_random', $origRandom);
		}

		$this->resetRequest();

		return $result;
	}

	/**
	 * Set the input state
	 *
	 * @param   array $unused values to inject into the application input
	 *
	 * @return  void
	 */
	protected function _setRequest($unused)
	{
		$app   = JFactory::getApplication();
		$input = $app->input;

		// Ensure &gt; conditions set in {fabrik} are converted to >
		foreach ($unused as &$v)
		{
			if (is_string($v))
			{
				$v = htmlspecialchars_decode($v);
			}
		}

		/*
		 * $$$ hugh - in order to allow complex filters to work in lists, like ...
		 * foo___bar[value][]=1 foo___bar[value[]=9 foo___bar[condition]=BETWEEN
		 *we have to build a qs style array structure, using parse_str().
		 */
		$qs_arr = array();
		$qs_str = implode('&', $unused);
		parse_str($qs_str, $qs_arr);
		$this->origRequestVars = array();

		foreach ($qs_arr as $k => $v)
		{
			$origVar                   = $input->get($k, '', 'string');
			$this->origRequestVars[$k] = $origVar;
			$_GET[$k]                  = $v;
			$input->set($k, $v);
		}

		/*
		 * $$$ rob set this array here - we will use in the listfilter::getQuerystringFilters()
		 * code to determine if the filter is a querystring filter or one set from the plugin
		 * if its set from here it becomes sticky and is not cleared from the session. So we basically
		 * treat all filters set up inside {fabrik.....} as prefilters
		 */
		$input->set('fabrik_sticky_filters', array_keys($qs_arr));
	}

	/**
	 * Reset the application input
	 *
	 * @return  void
	 */
	protected function resetRequest()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;

		foreach ($this->origRequestVars as $k => $v)
		{
			if (!is_null($v))
			{
				$input->set($k, $v);
			}
			else
			{
				// $$$ rob 13/04/2012 clear rather than setting to '' as subsequent list plugins with fewer filters
				// will contain the previous plugins filter, even if not included in the current plugin declaration
				unset($_GET[$k]);
				unset($_REQUEST[$k]);
			}
		}
	}

	/**
	 * Get the model
	 *
	 * @param   JControllerLegacy &$controller Controller
	 * @param   string            $viewName    View name
	 * @param   int               $id          Item id
	 *
	 * @return  mixed    JModel or false
	 */
	protected function getModel(&$controller, $viewName, $id)
	{
		if ($viewName == 'visualization')
		{
			$viewName = $this->getPluginVizName($id);
		}

		if ($viewName == 'details')
		{
			$viewName = 'form';
		}

		if ($viewName == 'csv')
		{
			$viewName = 'list';
		}

		$prefix = '';

		if ($viewName == 'form' || $viewName == 'list')
		{
			$prefix = 'FabrikFEModel';
		}

		if (!isset($controller->_model))
		{
			JModelLegacy::addIncludePath(COM_FABRIK_FRONTEND . '/models', $prefix);

			if (!$controller->_model = $controller->getModel($viewName, $prefix))
			{
				throw new RuntimeException('Fabrik Content Plug-in: could not create model');

				return false;
			}
		}

		return $controller->_model;
	}

	/**
	 * Get a view
	 *
	 * @param   object &$controller controller
	 * @param   string $viewName    view name
	 * @param   int    $id          item id
	 *
	 * @return  JView
	 */
	protected function getView(&$controller, $viewName, $id)
	{
		$viewType = JFactory::getDocument()->getType();

		if ($viewName == 'details')
		{
			$viewName = 'form';
		}

		$view = $controller->getView($viewName, $viewType);

		return $view;
	}

	/**
	 * Get the viz plugin name
	 *
	 * @param   int $id viz id
	 *
	 * @return  string    viz plugin name
	 */
	protected function getPluginVizName($id)
	{
		if (!isset($this->pluginVizName))
		{
			$this->pluginVizName = array();
		}

		if (!array_key_exists($id, $this->pluginVizName))
		{
			$db    = FabrikWorker::getDbo(true);
			$query = $db->getQuery(true);
			$query->select('plugin')->from('#__{package}_visualizations')->where('id = ' . (int) $id);
			$db->setQuery($query);
			$this->pluginVizName[$id] = $db->loadResult();
		}

		return $this->pluginVizName[$id];
	}

	/**
	 * Get the controller
	 *
	 * @param   string $viewName view name
	 * @param   int    $id       item id
	 *
	 * @return  object  controller
	 */
	protected function getController($viewName, $id)
	{
		if (!isset($this->controllers))
		{
			$this->controllers = array();
		}

		switch ($viewName)
		{
			case 'visualization':
				$controller = new FabrikControllerVisualization;
				break;
			case 'form':
				$controller = new FabrikControllerForm;
				break;
			case 'details':
				$controller = new FabrikControllerDetails;
				break;
			case 'list':
				// $$$ hugh - had to add [$id] for cases where we have multiple plugins with different tableids
				if (array_key_exists('list', $this->controllers))
				{
					if (!array_key_exists($id, $this->controllers['list']))
					{
						$this->controllers['list'][$id] = new FabrikControllerList;
					}
				}
				else
				{
					$this->controllers['list'][$id] = new FabrikControllerList;
				}

				$controller = $this->controllers['list'][$id];
				break;
			case 'package':
				$controller = new FabrikControllerPackage;
				break;
			default:
				$controller = new FabrikController;
				break;
		}

		// Set a cacheId so that the controller grabs/creates unique caches for each form/table rendered
		$controller->cacheId = $id;

		return $controller;
	}

	/**
	 * Load the required fabrik files
	 *
	 * @param   string $view view name
	 *
	 * @return  void
	 */
	protected function generalIncludes($view)
	{
		if ($view == 'details')
		{
			$view = 'form';
		}

		if (!FabrikWorker::isViewType($view))
		{
			throw new RuntimeException('Please specify a valid view type in your fabrik {} code: ' . $view, 500);
		}

		require_once COM_FABRIK_FRONTEND . '/controller.php';

		// If in admin form or details view and embedding a list - this gave an error - if only needed for 3.0.x

		if ($view !== 'list')
		{
			require_once COM_FABRIK_FRONTEND . '/controllers/form.php';
			require_once COM_FABRIK_FRONTEND . '/controllers/details.php';
		}

		require_once COM_FABRIK_FRONTEND . '/controllers/package.php';
		require_once COM_FABRIK_FRONTEND . '/controllers/list.php';
		require_once COM_FABRIK_FRONTEND . '/controllers/visualization.php';
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
		JModelLegacy::addIncludePath(COM_FABRIK_FRONTEND . '/models');
		JModelLegacy::addIncludePath(COM_FABRIK_FRONTEND . '/models', 'FabrikFEModel');

		// $$$rob looks like including the view does something to the layout variable
		require_once COM_FABRIK_FRONTEND . '/views/' . $view . '/view.html.php';
	}
}
