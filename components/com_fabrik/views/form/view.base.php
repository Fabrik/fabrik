<?php
/**
 * Base Form view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * Base Form view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0.6
 */

class FabrikViewFormBase extends JViewLegacy
{
	/**
	 * Is the view rendering inside the Fabrik Joomla content plugin
	 *
	 * @var  bool
	 */
	public $isMambot = null;

	/**
	 * Viewing access level
	 *
	 * @var  int
	 */
	public $access = null;

	/**
	 * Main setup routine for displaying the form/detail view
	 *
	 * @param   string  $tpl  template
	 *
	 * @return  void
	 */

	public function display($tpl = null)
	{
		$profiler = JProfiler::getInstance('Application');
		$app = JFactory::getApplication();
		$input = $app->input;
		$w = new FabrikWorker;
		$config = JFactory::getConfig();
		$model = $this->getModel('form');
		$document = JFactory::getDocument();
		$model->isMambot = $this->isMambot;
		$form = $model->getForm();

		if ($model->render() === false)
		{
			return false;
		}

		$this->isMultiPage = $model->isMultiPage();
		list($this->plugintop, $this->pluginbottom, $this->pluginend) = $model->getFormPluginHTML();
		$listModel = $model->getlistModel();
		$table = $listModel->noTable() ? null : $listModel->getTable();

		if (!$model->canPublish())
		{
			if (!$app->isAdmin())
			{
				echo FText::_('COM_FABRIK_FORM_NOT_PUBLISHED');

				return false;
			}
		}

		$this->rowid = $model->getRowId();
		$this->access = $model->checkAccessFromListSettings();

		if ($this->access == 0)
		{
			JError::raiseWarning(500, FText::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}

		JDEBUG ? $profiler->mark('form view before join group ids got') : null;

		if (!$listModel->noTable())
		{
			$joins = $listModel->getJoins();
			$model->getJoinGroupIds($joins);
		}

		$params = $model->getParams();
		$this->setTitle($w, $params, $model);
		FabrikHelperHTML::debug($params->get('note'), 'note');
		$params->def('icons', $app->getCfg('icons'));
		$params->set('popup', ($input->get('tmpl') == 'component') ? 1 : 0);

		$this->editable = $model->isEditable();

		$form->label = $model->getLabel();
		$form->intro = $model->getIntro();
		$form->outro = $model->getOutro();
		$form->action = $model->getAction();
		$form->class = $model->getFormClass();
		$form->formid = $model->isEditable() ? 'form_' . $model->getId() : 'details_' . $model->getId();
		$form->name = 'form_' . $model->getId();

		if ((string) $this->rowid !== '')
		{
			$form->formid .= '_' . $this->rowid;
		}

		$form->error = $form->error === '' ? FText::_('COM_FABRIK_FAILED_VALIDATION') : FText::_($form->error);
		$form->origerror = $form->error;
		$form->error = $model->hasErrors() ? $form->error : '';
		JDEBUG ? $profiler->mark('form view before validation classes loaded') : null;

		$tmpl = $model->getTmpl();
		$this->tmpl = $tmpl;

		$this->_addButtons();
		JDEBUG ? $profiler->mark('form view before group view got') : null;

		$this->groups = $model->getGroupView($tmpl);
		JDEBUG ? $profiler->mark('form view after group view got') : null;
		$this->data = $model->tmplData;
		$this->params = $params;
		$this->tipLocation = $params->get('tiplocation');

		FabrikHelperHTML::debug($this->groups, 'form:view:groups');

		// Cck in admin?
		$this->cck();
		JDEBUG ? $profiler->mark('form view: after cck') : null;

		// Force front end templates
		$this->_basePath = COM_FABRIK_FRONTEND . '/views';

		$this->_addJavascript($listModel->getId());
		JDEBUG ? $profiler->mark('form view: after add js') : null;
		$this->_loadTmplBottom($form);
		JDEBUG ? $profiler->mark('form view: after tmpl bottom loaded') : null;

		$form->attribs = ' class="' . $form->class . '" name="' . $form->name . '" id="' . $form->formid . '" enctype="' . $model->getFormEncType() . '"';

		$this->form = $form;
		JDEBUG ? $profiler->mark('form view: form assigned as ref') : null;
		$list = new stdClass;
		$list->id = $form->record_in_database ? $model->getListModel()->getTable()->id : 0;
		$this->list = $list;
		JDEBUG ? $profiler->mark('form view: before getRelatedTables()') : null;
		$this->linkedTables = $model->getRelatedTables();
		JDEBUG ? $profiler->mark('form view: after getRelatedTables()') : null;
		$this->setMessage();

		$jTmplFolder = FabrikWorker::j3() ? 'tmpl' : 'tmpl25';
		$folder = $model->isEditable() ? 'form' : 'details';
		$this->addTemplatePath($this->_basePath . '/' . $folder . '/' . $jTmplFolder . '/' . $tmpl);

		$root = $app->isAdmin() ? JPATH_ADMINISTRATOR : JPATH_SITE;
		$this->addTemplatePath($root . '/templates/' . $app->getTemplate() . '/html/com_fabrik/form/' . $tmpl);
		JDEBUG ? $profiler->mark('form view before template load') : null;
	}

	/**
	 * Finally output the HTML, running Joomla content plugins if needed
	 *
	 * @return  void
	 */

	public function output()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$w = new FabrikWorker;
		$text = $this->loadTemplate();
		$model = $this->getModel();
		$params = $model->getParams();

		if ($params->get('process-jplugins', 2) == 1 || ($params->get('process-jplugins', 2) == 2 && $model->isEditable() === false))
		{
			FabrikHelperHTML::runContentPlugins($text);
		}

		// Allows you to use {placeholders} in form template.
		$text = $w->parseMessageForPlaceHolder($text, $model->data);
		echo $text;
	}

	/**
	 * Set template message when using session multipages
	 *
	 * @return  void
	 */

	private function setMessage()
	{
		$model = $this->getModel();
		$message = '';

		if (!$model->isMultiPage())
		{
			$this->message = '';

			return;
		}

		if ($model->sessionModel)
		{
			$this->message = $model->sessionModel->status;

			// See http://fabrikar.com/forums/showpost.php?p=73833&postcount=14
			// if ($model->sessionModel->statusid == _FABRIKFORMSESSION_LOADED_FROM_COOKIE) {
			if ($model->sessionModel->last_page > 0)
			{
				$message .= ' <a href="#" class="clearSession">' . FText::_('COM_FABRIK_CLEAR') . '</a>';
			}
		}

		$this->message = $message;
	}

	/**
	 * Set the page title
	 *
	 * @param   object  $w        parent worker
	 * @param   object  &$params  parameters
	 * @param   object  $model    form model
	 *
	 * @return  void
	 */

	protected function setTitle($w, &$params, $model)
	{
		$document = JFactory::getDocument();
		$model = $this->getModel();
		$app = JFactory::getApplication();
		$input = $app->input;
		$title = '';

		if ($app->getName() !== 'administrator')
		{
			$menus = $app->getMenu();
			$menu = $menus->getActive();

			// If there is a menu item available AND the form is not rendered in a content plugin or module
			if (is_object($menu) && !$this->isMambot)
			{
				$menu_params = is_a($menu->params, 'JRegistry') ? $menu->params : new JRegistry($menu->params);
				$params->set('page_heading', $menu_params->get('page_heading', ''));
				$params->set('show_page_heading', $menu_params->get('show_page_heading', 0));
				$browserTitle = $model->getPageTitle($menu_params->get('page_title'));
				$document->setTitle($w->parseMessageForPlaceHolder($browserTitle, $_REQUEST));
			}
			else
			{
				$params->set('show_page_heading', $input->getInt('show_page_heading', 0));
				$params->set('page_heading', $input->get('title', $title, 'string'));
				$params->set('show-title', $input->getInt('show-title', $params->get('show-title')));
			}

			if (!$this->isMambot)
			{
				$titleData = array_merge($_REQUEST, $model->data);
				$title = $w->parseMessageForPlaceHolder($params->get('page_heading'), $titleData, false);
				$params->set('page_heading', $title);
			}
		}
		else
		{
			$params->set('page_heading', $title);
			$params->set('show_page_heading', 0);
		}
	}

	/**
	 * Add buttons to the view e.g. print, pdf
	 *
	 * @return  void
	 */

	protected function _addButtons()
	{
		$app = JFactory::getApplication();

		if ($app->input->get('format') === 'pdf')
		{
			// If we're rendering as PDF, no point showing any buttons
			$this->showEmail = false;
			$this->showPrint = false;
			$this->showPDF = false;

			return;
		}

		$fbConfig = JComponentHelper::getParams('com_fabrik');
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$input = $app->input;
		$model = $this->getModel();
		$params = $model->getParams();
		$this->showEmail = $params->get('email', $fbConfig->get('form_email', 0));
		$this->emailLink = '';
		$this->printLink = '';
		$this->pdfLink = '';
		$this->pdfURL = '';
		$this->emailURL = '';
		$this->printURL = '';
		$this->showPrint = $params->get('print', $fbConfig->get('form_print', 0));

		if ($this->showPrint)
		{
			$text = FabrikHelperHTML::image('print.png');
			$this->printLink = '<a href="#" class="printlink" onclick="window.print();return false;">' . $text . '</a>';
		}

		if ($input->get('tmpl') != 'component')
		{
			if ($this->showEmail)
			{
				$this->emailLink = FabrikHelperHTML::emailIcon($model, $params);
				$this->emailURL = FabrikHelperHTML::emailURL($model);
			}

			if ($this->showPrint)
			{
				$this->printLink = FabrikHelperHTML::printIcon($model, $params, $model->getRowId());
				$this->printURL = FabrikHelperHTML::printURL($model);
			}
		}

		$this->showPDF = $params->get('pdf', $fbConfig->get('form_pdf', false));

		$buttonProperties = array('class' => 'fabrikTip', 'opts' => "{notice:true}", 'title' => '<span>' . FText::_('COM_FABRIK_PDF') . '</span>',
				'alt' => FText::_('COM_FABRIK_PDF'));

		if ($this->showPDF)
		{
			FabrikWorker::canPdf();

			if ($app->isAdmin())
			{
				$this->pdfURL = 'index.php?option=com_' . $package . '&task=details.view&format=pdf&formid=' . $model->getId() . '&rowid=' . $model->getRowId();
			}
			else
			{
				$this->pdfURL = 'index.php?option=com_' . $package . '&view=details&formid=' . $model->getId() . '&rowid=' . $model->getRowId() . '&format=pdf';
			}

			$this->pdfURL = JRoute::_($this->pdfURL);
			$this->pdfLink = '<a href="' . $this->pdfURL . '">' . FabrikHelperHTML::image('pdf.png', 'list', $this->tmpl, $buttonProperties) . '</a>';
		}
	}

	/**
	 * Append the form javascript into the document head
	 *
	 * @param   int  $listId  table id
	 *
	 * @return  void
	 */

	protected function _addJavascript($listId)
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$input = $app->input;
		$document = JFactory::getDocument();
		$model = $this->getModel();
		$aLoadedElementPlugins = array();
		$jsActions = array();
		$bkey = $model->jsKey();
		$srcs = FabrikHelperHTML::framework();
		$shim = array();

		if (!defined('_JOS_FABRIK_FORMJS_INCLUDED'))
		{
			define('_JOS_FABRIK_FORMJS_INCLUDED', 1);
			FabrikHelperHTML::slimbox();

			$dep = new stdClass;
			$dep->deps = array('fab/element', 'lib/form_placeholder/Form.Placeholder', 'fab/encoder');
			$shim['fabrik/form'] = $dep;

			$deps = new stdClass;
			$deps->deps = array('fab/fabrik', 'fab/element', 'fab/form-submit');
			$framework['fab/elementlist'] = $deps;

			$srcs[] = 'media/com_fabrik/js/lib/form_placeholder/Form.Placeholder.js';
			FabrikHelperHTML::addToFrameWork($srcs, 'media/com_fabrik/js/form');
			FabrikHelperHTML::addToFrameWork($srcs, 'media/com_fabrik/js/form-submit');
			FabrikHelperHTML::addToFrameWork($srcs, 'media/com_fabrik/js/element');
		}

		$aWYSIWYGNames = array();

		// $$$ hugh - yet another one where if we =, the $groups array pointer get buggered up and it
		// skips a group
		$groups = $model->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$res = $elementModel->useEditor();

				if ($res !== false && $elementModel->canUse() && $model->isEditable())
				{
					$aWYSIWYGNames[] = $res;
				}

				$eparams = $elementModel->getParams();

				// Load in once the element js class files
				$element = $elementModel->getElement();

				if (!in_array($element->plugin, $aLoadedElementPlugins))
				{
					/* $$$ hugh - certain elements, like fileupload, need to load different JS files
					 * on a per-element basis, so as a test fix, I modified the fileupload's formJavaScriptClass to return false,
					* and test for that here, so as to not add it to aLoadedElementPlugins[].  The existing 'static' tests in
					* formJavascriptClass() should still prevent scripts being added twice.
					*/
					if ($elementModel->formJavascriptClass($srcs, '', $shim) !== false)
					{
						$aLoadedElementPlugins[] = $element->plugin;
					}
				}

				$eventMax = ($groupModel->repeatTotal == 0) ? 1 : $groupModel->repeatTotal;

				for ($c = 0; $c < $eventMax; $c++)
				{
					$jsAct = $elementModel->getFormattedJSActions($bkey, $c);

					if (!empty($jsAct))
					{
						$jsActions[] = $jsAct;
					}
				}
			}
		}

		FabrikHelperHTML::iniRequireJS($shim);
		$actions = trim(implode("\n", $jsActions));
		$listModel = $model->getlistModel();
		$table = $listModel->getTable();
		$form = $model->getForm();
		FabrikHelperHTML::windows('a.fabrikWin');
		FabrikHelperHTML::tips('.hasTip', array(), "$('$bkey')");
		$model->getFormCss();
		$opts = $this->jsOpts();
		$opts = json_encode($opts);

		if (!FabrikHelperHTML::inAjaxLoadedPage())
		{
			JText::script('COM_FABRIK_VALIDATING');
			JText::script('COM_FABRIK_SUCCESS');
			JText::script('COM_FABRIK_NO_REPEAT_GROUP_DATA');
			JText::script('COM_FABRIK_VALIDATION_ERROR');
			JText::script('COM_FABRIK_CONFIRM_DELETE_1');
		}

		JText::script('COM_FABRIK_FORM_SAVED');

		// $$$ rob don't declare as var $bkey, but rather assign to window, as if loaded via ajax window the function is wrapped
		// inside an anonymous function, and therefore $bkey wont be available as a global var in window
		$script = array();
		/* $script[] = "\twindow.$bkey = new FbForm(" . $model->getId() . ", $opts);";
		$script[] = "\tif(typeOf(Fabrik) !== 'null') {";
		$script[] = "\t\tFabrik.addBlock('$bkey', $bkey);";
		$script[] = "\t}"; */

		$script[] = "\t\tvar $bkey = Fabrik.form('$bkey', " . $model->getId() . ", $opts);";

		// Instantiate js objects for each element
		$vstr = "\n";
		$groups = $model->getGroupsHiarachy();
		$script[] = "\tFabrik.blocks['{$bkey}'].addElements(";
		$groupedJs = new stdClass;

		foreach ($groups as $groupModel)
		{
			$groupId = $groupModel->getGroup()->id;
			$groupedJs->$groupId = array();

			if (!$groupModel->canView('form'))
			{
				continue;
			}

			$elementJs = array();
			$elementModels = $groupModel->getPublishedElements();

			// $$$ rob if repeatTotal is 0 we still want to add the js objects as the els are only hidden
			$max = $groupModel->repeatTotal > 0 ? $groupModel->repeatTotal : 1;

			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();

				if ($element->published == 0)
				{
					continue;
				}

				$fullName = $elementModel->getFullName();
				$id = $elementModel->getHTMLId();

				// If the view is a form then we should always add the js as long as the element is editable or viewable
				// if the view is details then we should only add the js if the element is viewable.
				if (($elementModel->canUse() && $model->isEditable()) || $elementModel->canView())
				{
					for ($c = 0; $c < $max; $c++)
					{
						$ref = $elementModel->elementJavascript($c);

						if (!empty($ref))
						{
							$elementJs[] = $ref;
						}

						$validations = $elementModel->validator->findAll();

						if (!empty($validations) && $elementModel->isEditable())
						{
							$watchElements = $elementModel->getValidationWatchElements($c);

							foreach ($watchElements as $watchElement)
							{
								$vstr .= "\tFabrik.blocks['$bkey'].watchValidation('" . $watchElement['id'] . "', '" . $watchElement['triggerEvent'] . "');\n";
							}
						}
					}
				}
			}

			$groupedJs->$groupId = $elementJs;
		}

		$script[] = json_encode($groupedJs);
		$script[] = "\t);";
		$script[] = $actions;
		$script[] = $vstr;

		// Placeholder
		$script[] = "\tnew Form.Placeholder('.fabrikForm input');";
		$this->_addJavascriptSumbit($script, $listId, $aWYSIWYGNames);

		if (FabrikHelperHTML::inAjaxLoadedPage())
		{
			$tipOpts = FabrikHelperHTML::tipOpts();
			$script[] = "new FloatingTips('#" . $bkey . " .fabrikTip', " . json_encode($tipOpts) . ");";
		}

		$pluginManager = FabrikWorker::getPluginManager();
		$res = $pluginManager->runPlugins('onJSReady', $model);

		if (in_array(false, $res))
		{
			return false;
		}

		$str = implode("\n", $script);
		$model->getCustomJsAction($srcs);

		$pluginManager->runPlugins('onAfterJSLoad', $model);

		// 3.1 call form js plugin code within main require method
		$srcs = array_merge($srcs, $model->formPluginShim);
		$str .= $model->formPluginJS;
		FabrikHelperHTML::script($srcs, $str);
	}

	/**
	 * Load the JavaScript ini options
	 *
	 * @since  3.1b
	 *
	 * @return stdClass
	 */

	protected function jsOpts()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = $this->getModel();
		$fbConfig = JComponentHelper::getParams('com_fabrik');
		$form = $model->getForm();
		$params = $model->getParams();
		$listModel = $model->getlistModel();
		$table = $listModel->getTable();
		$opts = new stdClass;
		$opts->admin = $app->isAdmin();
		$opts->ajax = $model->isAjax();
		$opts->ajaxValidation = (bool) $params->get('ajax_validations');
		$opts->showLoader = (bool) $params->get('show_loader_on_submit', '0');
		$key = FabrikString::safeColNameToArrayKey($table->db_primary_key);
		$opts->primaryKey = $key;
		$opts->error = @$form->origerror;
		$opts->pages = $model->getPages();
		$opts->plugins = array();
		$opts->multipage_save = (int) $model->saveMultiPage();
		$opts->editable = $model->isEditable();
		$opts->print = (bool) $input->getInt('print');
		$start_page = isset($model->sessionModel->last_page) ? (int) $model->sessionModel->last_page : 0;

		if ($start_page !== 0)
		{
			$app->enqueueMessage(FText::_('COM_FABRIK_RESTARTING_MULTIPAGE_FORM'));
		}
		else
		{
			// Form submitted but fails validation - needs to go to the last page
			$start_page = $input->getInt('currentPage', 0);
		}

		$opts->start_page = $start_page;
		$opts->inlineMessage = (bool) $this->isMambot;

		// $$$rob don't int this as keys may be string
		$opts->rowid = (string) $model->getRowId();

		// 3.0 needed for ajax requests
		$opts->listid = (int) $this->get('ListModel')->getId();

		$errorIcon = FabrikWorker::j3() ? $fbConfig->get('error_icon', 'exclamation-sign') . '.png' : 'alert.png';
		$this->errorIcon = FabrikHelperHTML::image($errorIcon, 'form', $this->tmpl);

		$imgs = new stdClass;
		$imgs->alert = FabrikHelperHTML::image($errorIcon, 'form', $this->tmpl, '', true);
		$imgs->action_check = FabrikHelperHTML::image('action_check.png', 'form', $this->tmpl, '', true);

		$imgs->ajax_loader = FabrikHelperHTML::image('ajax-loader.gif', 'form', $this->tmpl, '', true);
		$imgs->ajax_loader = '<i class="icon-spinner icon-spin"></i>';
		$opts->images = $imgs;

		// $$$rob if you are loading a list in a window from a form db join select record option
		// then we want to know the id of the window so we can set its showSpinner() method

		// 3.0 changed to fabrik_window_id (automatically appended by Fabrik.Window xhr request to load window data
		$opts->fabrik_window_id = $input->get('fabrik_window_id', '');
		$opts->submitOnEnter = (bool) $params->get('submit_on_enter', false);

		// For editing groups with joined data and an empty joined record (i.e. no joined records)
		$hidden = array();
		$maxRepeat = array();
		$minRepeat = array();
		$showMaxRepeats = array();

		foreach ($this->groups as $g)
		{
			$hidden[$g->id] = $g->startHidden;
			$maxRepeat[$g->id] = $g->maxRepeat;
			$minRepeat[$g->id] = $g->minRepeat;
			$showMaxRepeats[$g->id] = $g->showMaxRepeats;
		}

		$opts->hiddenGroup = $hidden;
		$opts->maxRepeat = $maxRepeat;
		$opts->minRepeat = $minRepeat;
		$opts->showMaxRepeats = $showMaxRepeats;

		// $$$ hugh adding these so calc element can easily find joined and repeated join groups
		// when it needs to add observe events ... don't ask ... LOL!
		$opts->join_group_ids = array();
		$opts->group_repeats = array();
		$opts->group_joins_ids = array();
		$groups = $model->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			if ($groupModel->getGroup()->is_join)
			{
				$joinParams = $groupModel->getJoinModel()->getJoin()->params;

				if (!is_a($joinParams, 'JRegistry'))
				{
					$joinParams = new JRegistry($joinParams);
				}

				$opts->group_pk_ids[$groupModel->getGroup()->id] = FabrikString::safeColNameToArrayKey($joinParams->get('pk'));
				$opts->join_group_ids[$groupModel->getGroup()->join_id] = (int) $groupModel->getGroup()->id;
				$opts->group_join_ids[$groupModel->getGroup()->id] = (int) $groupModel->getGroup()->join_id;
				$opts->group_repeats[$groupModel->getGroup()->id] = $groupModel->canRepeat();
				$opts->group_copy_element_values[$groupModel->getGroup()->id] = $groupModel->canCopyElementValues();
			}
		}

		return $opts;
	}

	/**
	 * Append JS code for form submit
	 *
	 * @param   array  &$script        Scripts
	 * @param   int    $listId         List id
	 * @param   array  $aWYSIWYGNames  WYSIWYG editor names
	 *
	 * @since   3.1b
	 * @return  void
	 */

	protected function _addJavascriptSumbit(&$script, $listId, $aWYSIWYGNames)
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$script[] = "\tfunction submit_form() {";

		if (!empty($aWYSIWYGNames))
		{
			jimport('joomla.html.editor');
			$editor = JFactory::getEditor();
			$script[] = $editor->save('label');

			foreach ($aWYSIWYGNames as $parsedName)
			{
				$script[] = $editor->save($parsedName);
			}
		}

		$script[] = "\treturn false;";
		$script[] = "}";
		$script[] = "function submitbutton(button) {";
		$script[] = "\tif (button==\"cancel\") {";
		$script[] = "\t\tdocument.location = '" . JRoute::_('index.php?option=com_' . $package . '&task=viewTable&cid=' . $listId) . "';";
		$script[] = "\t}";
		$script[] = "\tif (button == \"cancelShowForm\") {";
		$script[] = "\t\treturn false;";
		$script[] = "\t}";
		$script[] = "}";
	}

	/**
	 * Create the form bottom hidden fields
	 *
	 * @param   object  &$form  Object containing form view properties
	 *
	 * @return  void
	 */

	protected function _loadTmplBottom(&$form)
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$input = $app->input;
		$Itemid = FabrikWorker::itemId();
		$model = $this->getModel();
		$listModel = $model->getListModel();
		$row = JArrayHelper::toObject($model->data);
		$canDelete = $listModel->canDelete($row);
		$params = $model->getParams();
		$task = 'form.process';
		$refer = $input->server->get('HTTP_REFERER', '', 'string');

		// $$$rob - if returning from a failed validation then we should use the fabrik_referrer post var
		$refer = str_replace('&', '&amp;', $input->get('fabrik_referrer', $refer, 'string'));

		$this_rowid = is_array($model->getRowId()) ? implode('|', $model->getRowId()) : $model->getRowId();
		$fields = array();
		$fields[] = '<input type="hidden" name="listid" value="' . $listModel->getId() . '" />';
		$fields[] = '<input type="hidden" name="listref" value="' . $listModel->getId() . '" />';
		$fields[] = '<input type="hidden" name="rowid" value="' . $this_rowid . '" />';
		$fields[] = '<input type="hidden" name="Itemid" value="' . $Itemid . '" />';
		$fields[] = '<input type="hidden" name="option" value="com_' . $package . '" />';
		$fields[] = '<input type="hidden" name="task" value="' . $task . '" />';
		$fields[] = '<input type="hidden" name="isMambot" value="' . $this->isMambot . '" />';
		$fields[] = '<input type="hidden" name="formid" value="' . $model->get('id') . '" />';
		$fields[] = '<input type="hidden" name="returntoform" value="0" />';
		$fields[] = '<input type="hidden" name="fabrik_referrer" value="' . $refer . '" />';
		$fields[] = '<input type="hidden" name="fabrik_ajax" value="' . (int) $model->isAjax() . '" />';
		$fields[] = '<input type="hidden" name="package" value="' . $app->getUserState('com_fabrik.package', 'fabrik') . '" />';
		$fields[] = '<input type="hidden" name="packageId" value="' . $model->packageId . '" />';

		if ($usekey = FabrikWorker::getMenuOrRequestVar('usekey', ''))
		{
			// $$$rob v's been set from -1 to the actual row id - so ignore usekey not sure if we should comment this out
			// see http://fabrikar.com/forums/showthread.php?t=10297&page=5

			$fields[] = '<input type="hidden" name="usekey" value="' . $usekey . '" />';
			$pk_val = JArrayHelper::getValue($model->data, FabrikString::safeColNameToArrayKey($listModel->getTable()->db_primary_key));

			if (empty($pk_val))
			{
				$fields[] = '<input type="hidden" name="usekey_newrecord" value="1" />';
			}
		}

		/** $$$ hugh - testing a fix for pagination issue when submitting a 'search form'.
		 * If this is a search form, we need to clear 'limitstart', otherwise ... say we
		 * were last on page 4 of the (unfiltered) target table, and the search yields less than 4 pages,
		 * we end up with a blank table 'cos the wrong LIMIT's are applied to the query
		 */
		$save_insessions = $params->get('save_insession', '');

		if (is_array($save_insessions))
		{
			foreach ($save_insessions as $save_insession)
			{
				if ($save_insession == '1')
				{
					$fields[] = '<input type="hidden" name="limitstart" value="0" />';
					break;
				}
			}
		}

		$fields[] = JHTML::_('form.token');
		$resetLabel = FText::_($params->get('reset_button_label'));
		$resetIcon = $params->get('reset_icon', '');
		$copyLabel = FText::_($params->get('copy_button_label'));
		$copyIcon = $params->get('copy_icon', '');
		$applyLabel = FText::_($params->get('apply_button_label'));
		$applyIcon = $params->get('apply_icon', '');
		$deleteLabel = FText::_($params->get('delete_button_label', 'Delete'));
		$deleteIcon = $params->get('delete_icon', '');
		$goBackLabel = FText::_($params->get('goback_button_label'));
		$goBackIcon = $params->get('goback_icon', '');

		if ($resetIcon !== '')
		{
			$resetIcon = '<i class="' . $resetIcon . '"></i>';
			$before = $params->get('reset_icon_location', 'before') == 'before';
			$resetLabel = $before ? $resetIcon . '&nbsp;' . $resetLabel : $resetLabel . '&nbsp;' . $resetIcon;
		}

		$form->resetButton = $params->get('reset_button', 0) && $this->editable == "1"
			? '<button type="reset" class="btn btn-warning button" name="Reset">' . $resetLabel . '</button>' : '';

		if ($copyIcon !== '')
		{
			$copyIcon = '<i class="' . $copyIcon . '"></i>';
			$copyLabel = $params->get('copy_icon_location', 'before') == 'before' ? $copyIcon . '&nbsp;' . $copyLabel : $copyLabel . '&nbsp;' . $copyIcon;
		}

		$form->copyButton = $params->get('copy_button', 0) && $this->editable && $model->getRowId() != ''
			? '<button type="submit" class="btn button" name="Copy">' . $copyLabel . '</button>' : '';

		if ($applyIcon !== '')
		{
			$applyIcon = '<i class="' . $applyIcon . '"></i>';
			$before = $params->get('apply_icon_location', 'before') == 'before';
			$applyLabel = $before ? $applyIcon . '&nbsp;' . $applyLabel : $applyLabel . '&nbsp;' . $applyIcon;
		}

		$applyButtonType = $model->isAjax() ? 'button' : 'submit';
		$form->applyButton = $params->get('apply_button', 0) && $this->editable
			? '<button type="' . $applyButtonType . '" class="btn button" name="apply">' . $applyLabel . '</button>' : '';

		if ($deleteIcon !== '')
		{
			$deleteIcon = '<i class="' . $deleteIcon . '"></i>';
			$before = $params->get('delete_icon_location', 'before') == 'before';
			$deleteLabel = $before ? $deleteIcon . '&nbsp;' . $deleteLabel : $deleteLabel . '&nbsp;' . $deleteIcon;
		}

		$form->deleteButton = $params->get('delete_button', 0) && $canDelete && $this->editable && $this_rowid != ''
			? '<button type="submit" class="btn btn-danger button" name="delete">' . $deleteLabel . '</button>' : '';

		if ($goBackIcon !== '')
		{
			$goBackIcon = '<i class="' . $goBackIcon . '"></i>';
			$before = $params->get('goback_icon_location', 'before') == 'before';
			$goBackLabel = $before ? $goBackIcon . '&nbsp;' . $goBackLabel : $goBackLabel . '&nbsp;' . $goBackIcon;
		}

		$goBack = $model->isAjax() ? '' : FabrikWorker::goBackAction();
		$form->gobackButton = $params->get('goback_button', 0)
			? '<button type="button" class="btn button" name="Goback" ' . $goBack . '>' . $goBackLabel . '</button>' : '';

		if ($model->isEditable() && $params->get('submit_button', 1))
		{
			$button = $model->isAjax() ? "button" : "submit";
			$submitClass = FabrikString::clean($form->submit_button_label);
			$submitIcon = $params->get('save_icon', '');
			$submitLabel = FText::_($form->submit_button_label);

			if ($submitIcon !== '')
			{
				$submitIcon = '<i class="' . $submitIcon . '"></i>';
				$before = $params->get('save_icon_location', 'before') == 'before';
				$submitLabel = $before ? $submitIcon . '&nbsp;' . $submitLabel : $submitLabel . '&nbsp;' . $submitIcon;
			}

			$form->submitButton = '<button type="' . $button . '" class="btn btn-primary button ' . $submitClass . '" name="Submit">'
				. $submitLabel . '</button>';
		}
		else
		{
			$form->submitButton = '';
		}

		if ($this->isMultiPage)
		{
			$form->prevButton = '<button type="button" class="btn fabrikPagePrevious button" name="fabrikPagePrevious"><i class="icon-previous"></i>&nbsp;'
				. FText::_('COM_FABRIK_PREV') . '</button>';
			$form->nextButton = '<button type="button" class="btn fabrikPageNext button" name="fabrikPageNext">'
				. FText::_('COM_FABRIK_NEXT') . '&nbsp;<i class="icon-next"></i></button>';
		}
		else
		{
			$form->nextButton = '';
			$form->prevButton = '';
		}

		// $$$ hugh - hide actions section is we're printing, or if not actions selected
		$noButtons = (empty($form->nextButton) && empty($form->prevButton) && empty($form->submitButton) && empty($form->gobackButton)
				&& empty($form->deleteButton) && empty($form->applyButton) && empty($form->copyButton) && empty($form->resetButton));

		if ($input->get('print', '0') == '1' || $noButtons)
		{
			$this->hasActions = false;
		}
		else
		{
			$this->hasActions = true;
		}

		$format = $model->isAjax() ? 'raw' : 'html';
		$fields[] = '<input type="hidden" name="format" value="' . $format . '" />';
		$groups = $model->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$group = $groupModel->getGroup();
			$c = $groupModel->repeatTotal;

			// Used for validations
			$fields[] = '<input type="hidden" name="fabrik_repeat_group[' . $group->id . ']" value="' . $c . '" id="fabrik_repeat_group_'
					. $group->id . '_counter" />';
		}

		// $$$ hugh - testing social_profile_hash stuff
		if ($input->get('fabrik_social_profile_hash', '') != '')
		{
			$fields[] = '<input type="hidden" name="fabrik_social_profile_hash" value="' . $input->get('fabrik_social_profile_hash', '')
			. '" id="fabrik_social_profile_hash" />';
		}

		$this->_cryptQueryString($fields);
		$this->_cryptViewOnlyElements($fields);
		$this->hiddenFields = implode("\n", $fields);
	}

	/**
	 * Store all fabrik querystring vars as encrypted hidden fields
	 * this is used when you have a table with a "Tables with database join elements linking to this table" link to a form.
	 * and when the form's pk element (found in the link) is set to read only
	 * OR if you are filtering from an url?
	 *
	 * @param   array  &$fields  hidden fields
	 *
	 * @return  void
	 */

	protected function _cryptQueryString(&$fields)
	{
		$crypt = FabrikWorker::getCrypt();
		$formModel = $this->getModel();
		$filter = JFilterInput::getInstance();
		$get = $filter->clean($_GET, 'array');

		foreach ($get as $key => $input)
		{
			// 	$$$ rob test if passing in _raw value via qs -used in fabsubs
			if (!$formModel->hasElement($key))
			{
				$key = FabrikString::rtrimword($key, '_raw');
			}

			if ($formModel->hasElement($key))
			{
				$elementModel = $formModel->getElement($key);

				if (!$elementModel->canUse())
				{
					$input = (is_array($input) && array_key_exists('value', $input)) ? $input['value'] : $input;
					/** $$$ hugh - need to check if $value is an array, 'cos if it isn't, like when presetting
					 * a new form element with &table___element=foo, getValue was chomping it down to just first character
					 * see http://fabrikar.com/forums/showthread.php?p=82726#post82726
					 */

					if (is_array($input))
					{
						$input = JArrayHelper::getValue($input, 'raw', $input);
					}

					// $$$ hugh - the aptly named SimpleCrypt encrypt is going to barf and toss a warning if we try
					// and encrypt a null or empty string
					if (empty($input))
					{
						$input = '';
					}
					else
					{
						$input = $crypt->encrypt($input);
					}

					$fields[] = '<input type="hidden" name="fabrik_vars[querystring][' . $key . ']" value="' . $input . '" />';
				}
			}
		}
	}

	/**
	 * Encrypt view only elements
	 *
	 * @param   array  &$aHiddenFields  Hidden fields
	 *
	 * @return  void
	 */

	protected function _cryptViewOnlyElements(&$aHiddenFields)
	{
		$model = $this->getModel();
		$crypt = FabrikWorker::getCrypt();
		$formModel = $this->getModel();
		$fields = array();
		$ro = $model->getReadOnlyVals();

		foreach ($ro as $key => $pair)
		{
			$repeatGroup = $pair['repeatgroup'];
			$isJoin = $pair['join'];
			$input = $pair['data'];

			// $$$ rob not sure this is correct now as I modified the readOnlyVals structure to contain info about if its in a group
			// and it now contains the repeated group data
			$input = (is_array($input) && array_key_exists('value', $input)) ? $input['value'] : $input;

			if ($repeatGroup)
			{
				$ar = array();
				$input = (array) $input;

				foreach ($input as $i)
				{
					if (is_array($i))
					{
						// Elements with sub options in repeat group
						$i = json_encode($i);
					}

					$ar[] = $i;
				}

				$input = $isJoin ? $ar : json_encode($ar);
			}
			else
			{
				if (is_array($input))
				{
					// Elements with sub options not in repeat group
					$input = json_encode($input);
				}
			}

			if (is_array($input))
			{
				for ($x = 0; $x < count($input); $x++)
				{
					if (trim($input[$x]) !== '')
					{
						$input[$x] = $crypt->encrypt($input[$x]);
					}
				}
			}
			else
			{
				if (trim($input) !== '')
				{
					$input = $crypt->encrypt($input);
				}
			}

			$safeKey = FabrikString::rtrimword($key, "[]");

			// $$$ rob - no don't do below as it will strip out join names join[x][fullname] => join
			// $key = preg_replace("/\[(.*)\]/", '', $key);
			if (!array_key_exists($safeKey, $fields))
			{
				$fields[$safeKey] = $input;
			}
			else
			{
				$fields[$safeKey] = (array) $fields[$safeKey];
				$fields[$safeKey][] = $input;
			}
		}

		foreach ($fields as $key => $input)
		{
			if (is_array($input))
			{
				for ($c = 0; $c < count($input); $c++)
				{
					$i = $input[$c];
					$fields[] = '<input type="hidden" name="fabrik_vars[querystring][' . $key . '][' . $c . ']" value="' . $i . '" />';
				}

				unset($fields[$key]);
			}
			else
			{
				$fields[$key] = '<input type="hidden" name="fabrik_vars[querystring][' . $key . ']" value="' . $input . '" />';
			}
		}

		$aHiddenFields = array_merge($aHiddenFields, array_values($fields));
	}

	/**
	 * Load up the cck view
	 *
	 * @return void
	 */

	protected function cck()
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		if ($input->get('task') === 'cck')
		{
			$model = $this->getModel();
			$params = $model->getParams();
			$row = $model->getForm();
			JHTML::script('admincck.js', 'administrator/components/com_fabrik/views/', true);
			$document = JFactory::getDocument();
			$opts = new stdClass;
			$opts->livesite = JURI::base();
			$opts->ename = $input->get('e_name');
			$opts->catid = $input->getInt('catid');
			$opts->section = $input->getInt('section');
			$opts->formid = $row->id;

			$tmpl = ($row->form_template == '') ? "default" : $row->form_template;
			$tmpl = $input->get('cck_layout', $tmpl);

			$opts->tmplList = FabrikHelperAdminHTML::templateList('form', 'fabrik_cck_template', $tmpl);

			$views = array();
			$views[] = JHTML::_('select.option', 'form');
			$views[] = JHTML::_('select.option', 'details');
			$selView = $input->get('cck_view');
			$opts->viewList = JHTML::_('select.radiolist', $views, 'fabrik_cck_view', 'class="inputbox"', 'value', 'text', $selView);

			$opts = json_encode($opts);
			$script = "window.addEvent('fabrik.loaded', function() {
			new adminCCK($opts);
		});";
			FabrikHelperHTML::addScriptDeclaration($script);
		}
	}
}
