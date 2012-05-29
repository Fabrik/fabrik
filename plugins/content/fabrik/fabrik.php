<?php
/**
 * @package		Joomla
 * @subpackage	Fabik
 * @copyright	Copyright (C) 2005 - 2008 Pollen 8 Design Ltd. All rights reserved.
 * @license		GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die();

jimport( 'joomla.plugin.plugin');

/**
 * Fabrik content plugin - renders forms and tables
 *
 * @package		Joomla
 * @subpackage	Content
 * @since 		1.5
 */

class plgContentFabrik extends JPlugin
{

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param object $params  The object that holds the plugin parameters
	 * @since 1.5
	 */

	public function plgContentFabrik(&$subject, $params = null)
	{
		parent::__construct($subject, $params);
	}

	/**
	 *  prepare content method
	 *
	 * Method is called by the view
	 *
	 * @param	string	The context of the content being passed to the plugin.
	 * @param 	object		The article object.  Note $article->text is also available
	 * @param 	object		The article params
	 * @param 	int			The 'page' number
	 */

	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		jimport('joomla.html.parameter');
		jimport('joomla.filesystem.file');

		//load fabrik language
		$lang = JFactory::getLanguage();
		$lang->load('com_fabrik', JPATH_BASE . '/components/com_fabrik');

		if (!defined('COM_FABRIK_FRONTEND'))
		{
			JError::raiseError(400, JText::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'));
		}

		// Get plugin info
		$plugin = JPluginHelper::getPlugin('content', 'fabrik');
		// $$$ hugh had to rename this, it was stomping on com_content and friends $params
		// $$$ which is passed by reference to us!
		$fparams = new JParameter($plugin->params);

		// simple performance check to determine whether bot should process further
		$botRegex = $fparams->get('Botregex') != '' ? $fparams->get('Botregex') : 'fabrik';

		if (JString::strpos($row->text, $botRegex) === false)
		{
			return true;
		}

		require_once(COM_FABRIK_FRONTEND . '/helpers/parent.php');
		// $$$ hugh - hacky fix for nasty issue with IE, which (for gory reasons) doesn't like having our JS content
		// wrapped in P tags.  But the default WYSIWYG editor in J! will automagically wrap P tags around everything.
		// So let's just look for obvious cases of <p>{fabrik ...}</p>, and replace the P's with DIV's.
		// Yes, it's hacky, but it'll save us a buttload of support work.
		$pregex = "/<p>\s*{" .$botRegex ."\s*.*?}\s*<\/p>/i";
		$row->text = preg_replace_callback($pregex, array($this, 'preplace'), $row->text);

		// $$$ hugh - having to change this to use {[]}
		$regex = "/{" .$botRegex ."\s*.*?}/i";
		$row->text = preg_replace_callback($regex, array($this, 'replace'), $row->text);

	}

	protected function preplace($match)
	{
		$match = $match[0];
		$match = str_ireplace('<p>', '<div>', $match);
		$match = str_ireplace('</p>', '</div>', $match);
		return $match;
	}

	protected function parse($match)
	{
		$match = $match[0];
		require_once(COM_FABRIK_FRONTEND . '/helpers/parent.php');
		$w =new FabrikWorker();
		$match = preg_replace('/\s+/', ' ', $match);
		// $$$ hugh - only replace []'s in value, not key, so we handle
		// ranged filters and 'complex' filters
		$match2 = array();
		foreach (explode(" ", $match) as $m)
		{
			if (strstr($m, '='))
			{
				list($key, $val) = explode('=', $m);
				$val = str_replace('[','{', $val);
				$val = str_replace(']', '}', $val);
				$match2[] = $key . '=' . $val;
			}
			else
			{
				$match2[] = $m;
			}
		}
		$match = implode(' ', $match2);
		$w->replaceRequest($match);
		// stop [] for ranged filters from being removed
		//$match = str_replace('{}', '[]', $match);
		$match = $w->parseMessageForPlaceHolder($match);
		return $match;
	}

	/**
	 * the function called from the preg_replace_callback - replace the {} with the correct HTML
	 *
	 * @param	string	plug-in match
	 * @return	unknown
	 */

	protected function replace($match)
	{

		$match = $match[0];
		$match = trim($match, "{");
		$match = trim($match, "}");
		$ref = preg_replace('/[^A-Z|a-z|0-9]/', '_', $match);
		$match = $this->parse(array($match));
		$match = explode(" ", strip_tags($match));
		array_shift($match);
		$user = JFactory::getUser();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$unused = array();
		$element = false; // special case if we are wanting to write in an element's data
		$repeatcounter = 0;
		$showfilters = JRequest::getVar('showfilters', 1);
		$clearfilters = JRequest::getVar('clearfilters', 0);
		$resetfilters = JRequest::getVar('resetfilters', 0);
		$this->origRequestVars = array();
		$id = 0;
		$origLayout= JRequest::getVar('layout');
		$origFFlayout = JRequest::getVar('flayout');
		$layoutFound = false;
		$rowid = 0;
		$usekey = '';
		$session = JFactory::getSession();
		$usersConfig->set('rowid', 0);
		
		foreach ($match as $m)
		{
			$m = explode("=", $m);
			// $$$ hugh - deal with %20 as space in arguments
			$m[1] = urldecode($m[1]);
			switch ($m[0])
			{
				case 'view':
					$viewName = strtolower($m[1]);
					break;
				case 'id':
					$id = $m[1];
					break;
				case 'layout':
					$layoutFound = true;
					$layout = $m[1];
					$origLayout = JRequest::getVar('layout');
					JRequest::setVar('layout', $layout);
					break;
				case 'row':
				case 'rowid':
					$row = $m[1];
					//when printing the content the rowid can't be passed in the querystring so don't set here
					if ($row !== '{rowid}')
					{
						if ($row == -1)
						{
							$row = $user->get('id');
						}
						$usersConfig->set('rowid', $row);
						// set the rowid in the session so that print pages can grab it again
						$session->set('fabrik.plgcontent.rowid', $row);
						$rowid = $row;
					}
					break;
				case 'element':
					//{fabrik view=element list=3 rowid=364 element=fielddatatwo}
					$viewName = 'list';
					$element = $m[1];
					break;
				case 'table':
				case 'list':
					$listid = $m[1];
					break;
				case 'usekey':
					$usekey = $m[1];
					break;
				case 'repeatcounter':
					$repeatcounter = $m[1];
					break;
				case 'showfilters':
					$showfilters = $m[1];
					break;

					// $$$ rob for these 2 grab the qs var in priority over the plugin settings
				case 'clearfilters':
					$clearfilters = JRequest::getVar('clearfilters', $m[1]);
					break;
				case 'resetfilters':
					$resetfilters = JRequest::getVar('resetfilters', $m[1]);
					break;
				default:
					if (array_key_exists(1, $m))
					{
						//$unused[trim($m[0])] = $m[1];//these are later set as jrequest vars if present in list view
						$unused[] = trim($m[0]) . '=' . $m[1];
					}
			}
		}
		//get the rowid in the session so that print pages can use it
		$rowid = $session->get('fabrik.plgcontent.rowid', $rowid);
		if ($viewName == 'table')
		{
			//some backwards compat with fabrik 2
			$viewName = 'list';
		}
		//moved out of switch as otherwise first plugin to use this will effect all subsequent plugins
		JRequest::setVar('usekey', $usekey);
		//$$$rob for list views in category blog layouts when no layout specified in {} the blog layout
		// was being used to render the list - which was not found which gave a 500 error
		if (!$layoutFound)
		{
			if (JRequest::getVar('option') === 'com_content' && JRequest::getVar('layout') === 'blog')
			{
				$layout = 'default';
				JRequest::setVar('layout', $layout);
			}
		}
		// $$$ hugh - added this so the fabrik2article plugin can arrange to have form CSS
		// included when the article is rendered by com_content, by inserting ...
		// {fabrik view=form_css id=X layout=foo}
		// ... at the top of the article.
		if ($viewName == 'form_css')
		{
			// the getFormCss() call blows up if we don't do this
			jimport('joomla.filesystem.file');
			$this->generalIncludes('form');
			$document = JFactory::getDocument();
			$viewType	= $document->getType();
			$controller = $this->_getController('form', $id);
			$view = $this->_getView($controller, 'form', $id);
			$model = $this->_getModel($controller, 'form', $id);
			if (!$model)
			{
				return;
			}
			$model->setId($id);
			$model->_editable = false;
			$form = $model->getForm();
			$listModel = $model->getListModel();
			$table = $listModel->getTable();
			$layout = !empty($layout) ? $layout : 'default';
			$view->setModel($model, true);
			$model->getFormCss($layout);
			return '';
		}
		$this->generalIncludes($viewName);
		if ($element !== false)
		{
			//special case for rendering element data
			$controller = $this->_getController('list', $listid);
			//$view =& $this->_getView($controller, $viewName, $listid);
			$model = $this->_getModel($controller, 'list', $listid);
			if (!$model)
			{
				return;
			}
			$model->setId($listid);
			$formModel = $model->getFormModel();
			$groups = $formModel->getGroupsHiarachy();
			foreach ($groups as $groupModel)
			{
				$elements = $groupModel->getMyElements();
				foreach ($elements as &$elementModel)
				{
					// $$$ rob 26/05/2011 changed it so that you can pick up joined elements without specifying plugin
					// param 'element' as joinx[x][fullname] but simpy 'fullname'
					if ($element == $elementModel->getFullName(false, true, false))
					{
						$activeEl = $elementModel;
						continue 2;
					}
				}
			}
			// $$$ hugh in case they have a typo in their elementname
			if (empty($activeEl))
			{
				JError::raiseNotice(500, 'You are trying to embed an element called ' . $element . ' which is not present in the list');
				return;
			}
			$row = $model->getRow($rowid, false, true);

			if (substr($element, strlen($element) - 4, strlen($element)) !== '_raw')
			{
				$element = $element . '_raw';
			}
			//$elval = is_object( $row ) ? $row->$element : '';
			//$defaultdata = array( $name => $elval);
			// $$$ hugh - need to pass all row data, or calc elements that use {placeholders} won't work
			$defaultdata = get_object_vars($row);
			// $$$ hugh - if we don't do this, our passed data gets blown away when render() merges the form data
			// not sure why, but apparently if you do $foo =& $bar and $bar is NULL ... $foo ends up NULL
			$activeEl->_form->_data = $defaultdata;
			$activeEl->_editable = false;
			//set row id for things like user element
			$origRowid = JRequest::getVar('rowid');
			JRequest::setVar('rowid', $rowid);

			$defaultdata = (array)$defaultdata;
			unset($activeEl->defaults);
			$res = $activeEl->render($defaultdata, $repeatcounter);
			JRequest::setVar('rowid', $origRowid);
			return $res;
		}

		if (!isset($viewName))
		{
			return;
		}

		$origid = JRequest::getVar('id');
		$origView = JRequest::getVar('view');

		//for fabble
		JRequest::setVar('origid', $origid);
		JRequest::setVar('origview', $origView);
		//end for fabble

		JRequest::setVar('id', $id);
		JRequest::setVar('view', $viewName);
		// $$$ hugh - at least make the $origid available for certain corner cases, like ...
		// http://fabrikar.com/forums/showthread.php?p=42960#post42960
		JRequest::setVar('origid', $origid, 'GET', false);

		$document = JFactory::getDocument();
		$viewType = $document->getType();
		$controller = $this->_getController($viewName, $id);
		$view = $this->_getView($controller, $viewName, $id);
		$model = $this->_getModel($controller, $viewName, $id);
		if (!$model)
		{
			return;
		}

		if (!JError::isError($model))
		{
			$view->setModel($model, true);
		}

		// Display the view
		$view->assign('error', $controller->getError());
		$view->isMambot = true;
		$displayed = false;
		// do some view specific code
		switch ($viewName)
		{
			case 'form_css':
				$model->getFormCss();
				break;
			case 'form':
			case 'details':
				if ($id === 0)
				{
					JError::raiseWarning(500, 'No id set in fabrik plugin declaration');
					return;
				}
				//$view->setId($id); not for 3.0
				$model->ajax = true;
				$model->setId($id);

				//unset($model->groupView);
				unset($model->groups);
				//set default values set in plugin declaration
				// - note cant check if the form model has the key' as its not yet loaded
				$this->_setRequest($unused);
				//$$$ rob - flayout is used in form/details view when _isMamot = true
				JRequest::setVar('flayout', JRequest::getVar('layout'));
				JRequest::setVar('rowid', $rowid);
				break;
			case 'csv':
			case 'table':
			case 'list':

				/// $$$ rob 15/02/2011 addded this as otherwise when you filtered on a table with multiple filter set up subsequent tables were showing
				//the first tables data
				if (JRequest::getVar('activelistid') == '')
				{
					JRequest::setVar('activelistid', JRequest::getInt('listid'));
				}
				JRequest::setVar('listid', $id);
				$this->_setRequest($unused);
				JRequest::setVar('showfilters', $showfilters);
				JRequest::setVar('clearfilters', $clearfilters);
				JRequest::setVar('resetfilters', $resetfilters);

				if ($id === 0)
				{
					JError::raiseWarning(500, 'No id set in fabrik plugin declaration');
					return;
				}
				$model->setId($id);
				$model->ajax = 1;
				$task = JRequest::getVar('task');
				if (method_exists($controller, $task) && JRequest::getInt('activetableid') == $id)
				{
					//enable delete() of rows
					//table controller deals with display after tasks is called
					//set $displayed to true to stop controller running twice
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
				JRequest::setVar('showfilters', $showfilters);
				JRequest::setVar('clearfilters', $clearfilters);
				JRequest::setVar('resetfilters', $resetfilters);
				$this->_setRequest($unused);
				break;
		}
		//hack for gallery viz as it may not use the default view
		$controller->isMambot = true;
		if (!$displayed)
		{
			ob_start();
			if (method_exists($model, 'reset'))
			{
				$model->reset();
				// $$$ rob erm $ref is a regex?! something not right here (caused js error in cb plugin)
				//$model->setRenderContext($ref);
			}
			$controller->display($model);
			$result = ob_get_contents();
			ob_end_clean();
		}
		JRequest::setVar('id', $origid);
		JRequest::setVar('view', $origView);

		if ($origLayout != '')
		{
			JRequest::setVar('layout', $origLayout);
		}
		if ($origFFlayout != '')
		{
			JRequest::setVar('flayout', $origFFlayout);
		}
		$this->resetRequest();
		return $result;
	}

	protected function _setRequest($unused)
	{
		// $$$ hugh - in order to allow complex filters to work in lists, like ...
		// foo___bar[value][]=1 foo___bar[value[]=9 foo___bar[condition]=BETWEEN
		// we have to build a qs style array structure, using parse_str().
		$qs_arr = array();
		$qs_str = implode('&', $unused);
		parse_str($qs_str, $qs_arr);
		$this->origRequestVars = array();
		foreach ($qs_arr as $k => $v)
		{
			$origVar = JRequest::getVar($k);
			$this->origRequestVars[$k] = $origVar;
			JRequest::setVar($k, $v);
		}
		// $$$ rob set this array here - we will use in the tablefilter::getQuerystringFilters()
		//code to determine if the filter is a querystring filter or one set from the plugin
		//if its set from here it becomes sticky and is not cleared from the session. So we basically
		//treat all filters set up inside {fabrik.....} as prefilters
		JRequest::setVar('fabrik_sticky_filters', array_keys($qs_arr));
	}

	protected function resetRequest()
	{
		foreach ($this->origRequestVars as $k => $v)
		{
			if (!is_null($v))
			{
				JRequest::setVar($k, $v);
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
	 * get the model
	 * @param	object	controller
	 * @param	string	$viewName
	 * @param	int		id
	 * @return	mixed	model or false
	 */

	protected function _getModel(&$controller, $viewName, $id)
	{
		if ($viewName == 'visualization') {
			$viewName = $this->_getPluginVizName($id);
		}
		if ($viewName == 'details')
		{
			$viewName = 'form';
		}
		if ($viewName == 'csv')
		{
			$viewName = 'list';
		}
		$prefix= '';
		if ($viewName == 'form' || $viewName == 'list')
		{
			$prefix = 'FabrikFEModel';
		}
		if (!isset($controller->_model)) {
			$modelpaths = JModel::addIncludePath(COM_FABRIK_FRONTEND . '/models', $prefix);
			if(!$controller->_model = $controller->getModel($viewName, $prefix))
			{
				JError::raiseNotice(500, 'Fabrik Content Plug-in: could not create model');
				return false;
			}
		}
		return $controller->_model;
	}

	/**
	 * get a view
	 * @param	object	controller
	 * @param	string	$viewName
	 * @param	int		id
	 */

	protected function _getView(&$controller, $viewName, $id)
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
	 * get the viz plugin name
	 *
	 * @param	int		$id
	 * @return	string	viz plugin name
	 */

	protected function _getPluginVizName($id)
	{
		if (!isset($this->pluginVizName))
		{
			$this->pluginVizName = array();
		}
		if (!array_key_exists($id, $this->pluginVizName))
		{
			$db = FabrikWorker::getDbo(true);
			$query = $db->getQuery(true);
			$query->select('plugin')->from('#__{package}_visualizations')->where('id = ' . (int) $id);
			$db->setQuery($query);
			$this->pluginVizName[$id] = $db->loadResult();
		}
		return $this->pluginVizName[$id];
	}

	/**
	 * get the controller
	 *
	 * @param	string	$viewName
	 * @param	int		$id
	 * @return	object	controller
	 */

	protected function _getController($viewName, $id)
	{
		if (!isset($this->controllers))
		{
			$this->controllers = array();
		}
		switch ($viewName)
		{
			case 'visualization':
				$controller = new FabrikControllerVisualization();
				break;
			case 'form':
				$controller = new FabrikControllerForm();
				break;
			case 'details':
				$controller = new FabrikControllerDetails();
				break;
			case 'list':
				// $$$ hugh - had to add [$id] for cases where we have multiple plugins with different tableid's
				if (array_key_exists('list', $this->controllers))
				{
					if (!array_key_exists($id, $this->controllers['list']))
					{
						$this->controllers['list'][$id] = new FabrikControllerList();
					}
				}
				else
				{
					$this->controllers['list'][$id] = new FabrikControllerList();
				}
				$controller = $this->controllers['list'][$id];
				break;
			case 'package':
				$controller = new FabrikControllerPackage();
				break;
			default:
				$controller = new FabrikController();
				break;
		}
		//set a cacheId so that the controller grabs/creates unique caches for each form/table rendered
		$controller->cacheId = $id;
		return $controller;
	}

	/**
	 * load the required fabrik files
	 * @param	string	$view
	 */

	protected function generalIncludes($view)
	{
		require_once(COM_FABRIK_FRONTEND . '/controller.php');
		require_once(COM_FABRIK_FRONTEND . '/controllers/form.php');
		require_once(COM_FABRIK_FRONTEND . '/controllers/details.php');
		require_once(COM_FABRIK_FRONTEND . '/controllers/package.php');
		require_once(COM_FABRIK_FRONTEND . '/controllers/list.php');
		require_once(COM_FABRIK_FRONTEND . '/controllers/visualization.php');
		require_once(COM_FABRIK_FRONTEND . '/models/parent.php');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
		JModel::addIncludePath(COM_FABRIK_FRONTEND . '/models');
		JModel::addIncludePath(COM_FABRIK_FRONTEND . '/models', 'FabrikFEModel');
		if ($view == 'details')
		{
			$view = 'form';
		}
		if ($view == '')
		{
			JError::raiseError(500, 'Please specify a view in your fabrik {} code');
		}
		//$$$rob looks like including the view does something to the layout variable
		$layout = JRequest::getVar('layout', 'default');
		require_once(COM_FABRIK_FRONTEND . '/views/' . $view . '/view.html.php');
		if (!is_null($layout))
		{
			JRequest::setVar('layout', $layout);
		}
	}

}