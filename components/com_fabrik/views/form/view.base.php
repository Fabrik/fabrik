<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */


// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

/**
 * Base Form view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0.6
 */

class FabrikViewFormBase extends JView
{

	/**
	 * Is the view rendering inside the Fabrik Joomla content plugin
	 * @var  bool
	 */

	public $isMambot = null;

	/**
	 * Viewing access level
	 * @var  int
	 */

	public $access = null;

	/**
	 * main setup routine for displaying the form/detail view
	 *
	 * @param   string  $tpl  template
	 *
	 * @return  void
	 */

	public function display($tpl = null)
	{
		$profiler = JProfiler::getInstance('Application');
		$app = JFactory::getApplication();
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
		list($this->plugintop, $this->pluginbottom, $this->pluginend) = $this->get('FormPluginHTML');

		$listModel = $model->getlistModel();
		$table = $listModel->noTable() ? null : $listModel->getTable();
		if (!$model->canPublish())
		{
			if (!$app->isAdmin())
			{
				echo JText::_('COM_FABRIK_FORM_NOT_PUBLISHED');
				return false;
			}
		}
		$this->assign('rowid', $model->_rowId);
		$this->assign('access', $model->checkAccessFromListSettings());
		if ($this->access == 0)
		{
			return JError::raiseWarning(500, JText::_('JERROR_ALERTNOAUTHOR'));
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
		$params->set('popup', (JRequest::getVar('tmpl') == 'component') ? 1 : 0);

		$this->editable = $model->_editable;

		$form->label = $this->get('label');
		$form->intro = $this->get('Intro');
		$form->outro = $params->get('outro');
		$form->action = $this->get('Action');
		$form->formid = $model->_editable ? "form_" . $model->getId() : 'details_' . $model->getId();
		$form->name = 'form_' . $model->getId();

		if ($form->error === '')
		{
			$form->error = JText::_('COM_FABRIK_FAILED_VALIDATION');
		}
		$form->origerror = $form->error;
		$form->error = $model->hasErrors() ? $form->error : '';

		JDEBUG ? $profiler->mark('form view before validation classes loaded') : null;

		$tmpl = $this->get('tmpl');
		$this->assign('tmpl', $tmpl);

		$this->_addButtons();
		JDEBUG ? $profiler->mark('form view before group view got') : null;

		$this->groups = $model->getGroupView($tmpl);
		JDEBUG ? $profiler->mark('form view after group view got') : null;
		$this->assignRef('data', $model->data);
		$this->assignRef('modeldata', $model->_data);
		$this->assignRef('params', $params);
		$this->assign('tipLocation', $params->get('tiplocation'));
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

		if ($model->_editable)
		{
			$form->startTag = '<form action="' . $form->action . '" class="fabrikForm" method="post" name="' . $form->name . '" id="' . $form->formid
				. '" enctype="' . $model->getFormEncType() . '">';
			$form->endTag = '</form>';
			$form->fieldsetTag = 'fieldset';
			$form->legendTag = 'legend';
		}
		else
		{
			$form->startTag = '<div class="fabrikForm fabrikDetails" id="' . $form->formid . '">';
			$form->endTag = '</div>';
			$form->fieldsetTag = 'div';
			$form->legendTag = 'h3';
		}
		$this->assignRef('form', $form);
		JDEBUG ? $profiler->mark('form view: form assigned as ref') : null;
		$list = new stdClass;
		$list->id = $form->record_in_database ? $model->getListModel()->getTable()->id : 0;
		$this->assignRef('list', $list);
		JDEBUG ? $profiler->mark('form view: before getRelatedTables()') : null;
		$this->assignRef('linkedTables', $this->get('RelatedTables'));
		JDEBUG ? $profiler->mark('form view: after getRelatedTables()') : null;
		$this->setMessage();

		$this->addTemplatePath($this->_basePath . '/' . $this->_name . '/tmpl/' . $tmpl);

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
		$w = new FabrikWorker;
		$text = $this->loadTemplate();
		$model = $this->getModel();
		$params = $model->getParams();
		if ($params->get('process-jplugins') == 1 || ($params->get('process-jplugins') == 2 && $model->_editable === false))
		{
			$opt = JRequest::getVar('option');
			JRequest::setVar('option', 'com_content');
			jimport('joomla.html.html.content');
			$text .= '{emailcloak=off}';
			$text = JHTML::_('content.prepare', $text);
			$text = preg_replace('/\{emailcloak\=off\}/', '', $text);
			JRequest::setVar('option', $opt);
		}

		// Allows you to use {placeholders} in form template.
		$text = $w->parseMessageForPlaceHolder($text, $model->_data);
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
		if (!$model->isMultiPage())
		{
			$this->assign('message', '');
			return;
		}
		$message = '';
		if ($model->sessionModel)
		{
			$this->message = $model->sessionModel->status;

			// See http://fabrikar.com/forums/showpost.php?p=73833&postcount=14
			// if ($model->sessionModel->statusid == _FABRIKFORMSESSION_LOADED_FROM_COOKIE) {
			if ($model->sessionModel->last_page > 0)
			{
				$message .= ' <a href="#" class="clearSession">' . JText::_('COM_FABRIK_CLEAR') . '</a>';
			}
		}
		$this->assign('message', $message);
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
		$app = JFactory::getApplication();
		$title = '';
		if ($app->getName() !== 'administrator')
		{
			$menus = JSite::getMenu();
			$menu = $menus->getActive();

			// If there is a menu item available AND the form is not rendered in a content plugin or module
			if (is_object($menu) && !$this->isMambot)
			{
				$menu_params = new JRegistry($menu->params);
				$params->set('page_title', $menu_params->get('page_title', ''));
				$params->set('show_page_title', $menu_params->get('show_page_title', 0));
			}
			else
			{
				$params->set('show_page_title', JRequest::getInt('show_page_title', 0));
				$params->set('page_title', JRequest::getVar('title', $title));
				$params->set('show-title', JRequest::getInt('show-title', $params->get('show-title')));
			}
			if (!$this->isMambot)
			{
				$titleData = array_merge($_REQUEST, $model->_data);
				$title = $w->parseMessageForPlaceHolder($params->get('page_title'), $titleData, false);
				$params->set('page_title', $title);
			}
		}
		else
		{
			$params->set('page_title', $title);
			$params->set('show_page_title', 0);
		}
		$model = $this->getModel();
		if (!$this->isMambot)
		{
			$title = $model->getPageTitle($params->get('page_title'));
			$document->setTitle($w->parseMessageForPlaceHolder($title, $_REQUEST));
		}
	}

	/**
	 * Add buttons to the view e.g. print, pdf
	 *
	 * @return  void
	 */

	protected function _addButtons()
	{
		$fbConfig = JComponentHelper::getParams('com_fabrik');
		$model = $this->getModel();
		$params = $model->getParams();
		$this->showEmail = $params->get('email', $fbConfig->get('form_email', 0));
		$this->emailLink = '';
		$this->printLink = '';
		$this->pdfLink = '';
		$this->showPrint = $params->get('print', $fbConfig->get('form_print', 0));
		if ($this->showPrint)
		{
			$text = JHTML::_('image.site', 'printButton.png', '/images/', null, null, JText::_('Print'));
			$this->printLink = '<a href="#" class="printlink" onclick="window.print();return false;">' . $text . '</a>';
		}
		if (JRequest::getVar('tmpl') != 'component')
		{
			if ($this->showEmail)
			{
				$this->emailLink = FabrikHelperHTML::emailIcon($model, $params);
			}
			if ($this->showPrint)
			{
				$this->printLink = FabrikHelperHTML::printIcon($model, $params, $model->_rowId);
			}
		}
		$this->showPDF = $params->get('pdf', $fbConfig->get('form_pdf', false));

		$buttonProperties = array('class' => 'fabrikTip', 'opts' => "{notice:true}",
						'title' => '<span>' . JText::_('COM_FABRIK_PDF') . '</span>', 'alt' => JText::_('COM_FABRIK_PDF'));

		if ($this->showPDF)
		{
			if (!FabrikWorker::canPdf())
			{
				JError::raiseNotice(500, JText::_('COM_FABRIK_NOTICE_DOMPDF_NOT_FOUND'));
			}
			else
			{
				$this->pdfLink = '<a href="' . JRoute::_('index.php?option=com_fabrik&view=details&format=pdf&formid=' . $model->getId())
				. '&rowid=' . $this->rowid . '">' . FabrikHelperHTML::image('pdf.png', 'list', $this->tmpl, $buttonProperties) . '</a>';
			}
		}
	}

	/**
	 * Append the form javascript into the document head
	 *
	 * @param   int  $tableId  table id
	 *
	 * @return  void
	 */

	protected function _addJavascript($tableId)
	{
		$app = JFactory::getApplication();
		$document = JFactory::getDocument();
		$model = $this->getModel();

		$aLoadedElementPlugins = array();
		$jsActions = array();
		$jsControllerKey = $model->_editable ? 'form_' . $model->getId() : 'details_' . $model->getId();

		$srcs = FabrikHelperHTML::framework();
		if (!defined('_JOS_FABRIK_FORMJS_INCLUDED'))
		{
			define('_JOS_FABRIK_FORMJS_INCLUDED', 1);
			FabrikHelperHTML::slimbox();
			$srcs[] = 'media/com_fabrik/js/form.js';
			$srcs[] = 'media/com_fabrik/js/element.js';
			$srcs[] = 'media/com_fabrik/js/lib/form_placeholder/Form.Placeholder.js';
		}

		$aWYSIWYGNames = array();

		// $$$ hugh - yeat another one where if we =, the $groups array pointer get buggered up and it
		// skips a group
		$groups = $model->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$res = $elementModel->useEditor();
				if ($res !== false)
				{
					$aWYSIWYGNames[] = $res;
				}
				$eparams = $elementModel->getParams();
				/* load in once the element js class files
				 * $$$ hugh - only needed getParent when we weren't saving changes to parent params to child
				 * which we should now be doing ... and getParent() causes an extra table lookup for every child
				 * element on the form.
				 * $element = $elementModel->getParent();
				 */
				$element = $elementModel->getElement();
				if (!in_array($element->plugin, $aLoadedElementPlugins))
				{
					/* $$$ hugh - certain elements, like fileupload, need to load different JS files
					 * on a per-element basis, so as a test fix, I modified the fileupload's formJavaScriptClass to return false,
					 * and test for that here, so as to not add it to aLoadedElementPlugins[].  The existing 'static' tests in
					 * formJavascriptClass() should still prevent scripts being added twice.
					 */
					if ($elementModel->formJavascriptClass($srcs) !== false)
					{
						$aLoadedElementPlugins[] = $element->plugin;
					}

				}
				$eventMax = ($groupModel->repeatTotal == 0) ? 1 : $groupModel->repeatTotal;
				for ($c = 0; $c < $eventMax; $c++)
				{
					$jsActions[] = $elementModel->getFormattedJSActions($jsControllerKey, $c);
				}
			}
		}

		$actions = trim(implode("\n", $jsActions));
		$params = $model->getParams();
		$listModel = $model->getlistModel();
		$table = $listModel->getTable();
		$form = $model->getForm();
		FabrikHelperHTML::mocha();

		$bkey = $model->_editable ? 'form_' . $model->getId() : 'details_' . $model->getId();

		FabrikHelperHTML::tips('.hasTip', array(), "$('$bkey')");
		$key = FabrikString::safeColNameToArrayKey($table->db_primary_key);

		$this->get('FormCss');

		$start_page = isset($model->sessionModel->last_page) ? (int) $model->sessionModel->last_page : 0;
		if ($start_page !== 0)
		{
			$app->enqueueMessage(JText::_('COM_FABRIK_RESTARTING_MUTLIPAGE_FORM'));
		}
		else
		{
			// Form submitted but fails validation - needs to go to the last page
			$start_page = JRequest::getInt('currentPage', 0);
		}

		$opts = new stdClass;

		$opts->admin = $app->isAdmin();
		$opts->ajax = $model->isAjax();
		$opts->ajaxValidation = (bool) $params->get('ajax_validations');
		$opts->primaryKey = $key;
		$opts->error = @$form->origerror;
		$opts->pages = $model->getPages();
		$opts->plugins = array();
		$opts->multipage_save = (int) $model->saveMultiPage();
		$opts->editable = $model->_editable;
		$opts->start_page = $start_page;
		$opts->inlineMessage = (bool) $this->isMambot;

		// $$$rob dont int this as keys may be string
		$opts->rowid = (string) $model->_rowId;

		// 3.0 needed for ajax requests
		$opts->listid = (int) $this->get('ListModel')->getId();

		$imgs = new stdClass;
		$imgs->alert = FabrikHelperHTML::image('alert.png', 'form', $this->tmpl, '', true);
		$imgs->action_check = FabrikHelperHTML::image('action_check.png', 'form', $this->tmpl, '', true);
		$imgs->ajax_loader = FabrikHelperHTML::image('ajax-loader.gif', 'form', $this->tmpl, '', true);
		$opts->images = $imgs;

		// $$$rob if you are loading a table in a window from a form db join select record option
		// then we want to know the id of the window so we can set its showSpinner() method

		// 3.0 changed to fabrik_window_id (automatically appended by Fabrik.Window xhr request to load window data
		$opts->fabrik_window_id = JRequest::getVar('fabrik_window_id', '');
		$opts->submitOnEnter = (bool) $params->get('submit_on_enter', false);

		// For editing groups with joined data and an empty joined record (ie no joined records)
		$hidden = array();
		$maxRepeat = array();
		$showMaxRepeats = array();
		foreach ($this->groups as $g)
		{
			$hidden[$g->id] = $g->startHidden;
			$maxRepeat[$g->id] = $g->maxRepeat;
			$showMaxRepeats[$g->id] = $g->showMaxRepeats;
		}
		$opts->hiddenGroup = $hidden;
		$opts->maxRepeat = $maxRepeat;
		$opts->showMaxRepeats = $showMaxRepeats;

		// $$$ rob 26/04/2011 joomfish translations of password validation error messages
		// $opts->lang = FabrikWorker::getJoomfishLang();

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
				$opts->join_group_ids[$groupModel->getGroup()->join_id] = (int) $groupModel->getGroup()->id;
				$opts->group_join_ids[$groupModel->getGroup()->id] = (int) $groupModel->getGroup()->join_id;
				$opts->group_repeats[$groupModel->getGroup()->id] = $groupModel->canRepeat();
			}
		}

		$opts = json_encode($opts);

		if (!FabrikHelperHTML::inAjaxLoadedPage())
		{
			JText::script('COM_FABRIK_VALIDATING');
			JText::script('COM_FABRIK_SUCCESS');
			JText::script('COM_FABRIK_NO_REPEAT_GROUP_DATA');
			JText::script('COM_FABRIK_VALIDATION_ERROR');
			JText::script('COM_FABRIK_FORM_SAVED');
			Jtext::script('COM_FABRIK_CONFIRM_DELETE');
		}

		// $$$ rob dont declare as var $bkey, but rather assign to window, as if loaded via ajax window the function is wrapped
		// inside an anoymous function, and therefore $bkey wont be available as a global var in window
		$script = array();
		$script[] = "window.$bkey = new FbForm(" . $model->getId() . ", $opts);";
		$script[] = "if(typeOf(Fabrik) !== 'null') {";
		$script[] = "Fabrik.addBlock('$bkey', $bkey);";
		$script[] = "}";

		// Instantaite js objects for each element
		$vstr = "\n";

		$groups = $model->getGroupsHiarachy();

		$script[] = "{$bkey}.addElements({";
		$gs = array();
		foreach ($groups as $groupModel)
		{
			$showGroup = $groupModel->getParams()->get('repeat_group_show_first');
			if ($showGroup == -1 || ($showGroup == 2 && $model->_editable))
			{
				// $$$ rob unpublished group so dont include the element js
				continue;
			}
			$aObjs = array();
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
				$elementModel->_editable = ($model->_editable);

				// If the view is a form then we should always add the js as long as the element is editable or viewable
				// if the view is details then we should only add hte js if the element is viewable.
				if (($elementModel->canUse() && $model->_editable) || $elementModel->canView())
				{
					for ($c = 0; $c < $max; $c++)
					{
						// $$$ rob ensure that some js code has been returned otherwise dont add empty data to array
						$ref = trim($elementModel->elementJavascript($c));
						if ($ref !== '')
						{
							$aObjs[] = $ref;
						}
						$validations = $elementModel->getValidations();
						if (!empty($validations) && $elementModel->_editable)
						{
							$watchElements = $elementModel->getValidationWatchElements($c);
							foreach ($watchElements as $watchElement)
							{
								$vstr .= "$bkey.watchValidation('" . $watchElement['id'] . "', '" . $watchElement['triggerEvent'] . "');\n";
							}
						}
					}
				}
			}
			$gs[] = $groupModel->getGroup()->id . ':[' . implode(",\n", $aObjs) . ']';
		}
		$script[] = implode(", ", $gs);
		$script[] = '});';
		$script[] = $actions;
		$script[] = $vstr;

		// Placholder test
		$script[] = "new Form.Placeholder('.fabrikForm input');";

		$script[] = "function submit_form() {";
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
		$script[] = "\t\tdocument.location = '" . JRoute::_('index.php?option=com_fabrik&task=viewTable&cid=' . $tableId) . "';";
		$script[] = "\t}";
		$script[] = "\tif (button == \"cancelShowForm\") {";
		$script[] = "\t\treturn false;";
		$script[] = "\t}";
		$script[] = "}";

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
		$srcs[] = 'media/com_fabrik/js/encoder.js';
		FabrikHelperHTML::script($srcs, $str);
		$pluginManager->runPlugins('onAfterJSLoad', $model);
	}

	/**
	 * Create the fom bottom hidden fields
	 *
	 * @param   object  &$form  object containg form view properties
	 *
	 * @return  void
	 */

	protected function _loadTmplBottom(&$form)
	{
		$app = JFactory::getApplication();
		$menuItem = $app->getMenu('site')->getActive();
		$Itemid = $menuItem ? $menuItem->id : 0;
		$model = $this->getModel();
		$listModel = $model->getListModel();
		$canDelete = $listModel->canDelete($model->_data);
		$params = $model->getParams();
		$task = 'form.process';
		$reffer = JRequest::getVar('HTTP_REFERER', '', 'server');

		// $$$rob - if returning from a failed validation then we should use the fabrik_referrer post var
		$reffer = str_replace('&', '&amp;', JRequest::getVar('fabrik_referrer', $reffer));

		$this_rowid = is_array($model->_rowId) ? implode('|', $model->_rowId) : $model->_rowId;
		$fields = array('<input type="hidden" name="listid" value="' . $listModel->getId() . '" />',
			'<input type="hidden" name="listref" value="' . $listModel->getId() . '" />',
			'<input type="hidden" name="rowid" value="' . $this_rowid . '" />', '<input type="hidden" name="Itemid" value="' . $Itemid . '" />',
			'<input type="hidden" name="option" value="com_fabrik" />', '<input type="hidden" name="task" value="' . $task . '" />',
			'<input type="hidden" name="isMambot" value="' . $this->isMambot . '" />',
			'<input type="hidden" name="formid" value="' . $model->get('id') . '" />', '<input type="hidden" name="returntoform" value="0" />',
			'<input type="hidden" name="fabrik_referrer" value="' . $reffer . '" />',
			'<input type="hidden" name="fabrik_ajax" value="' . (int) $model->isAjax() . '" />');

		$fields[] = '<input type="hidden" name="_packageId" value="' . $model->packageId . '" />';

		if ($usekey = FabrikWorker::getMenuOrRequestVar('usekey', ''))
		{

			// $$$rob v's been set from -1 to the actual row id - so ignore usekyey not sure if we should comment this out
			// see http://fabrikar.com/forums/showthread.php?t=10297&page=5

			$fields[] = '<input type="hidden" name="usekey" value="' . $usekey . '" />';
			$pk_val = JArrayHelper::getValue($model->_data, FabrikString::safeColNameToArrayKey($listModel->getTable()->db_primary_key));
			if (empty($pk_val))
			{
				$fields[] = '<input type="hidden" name="usekey_newrecord" value="1" />';
			}
		}
		/* $$$ hugh - testing a fix for pagination issue when submitting a 'search form'.
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

		$form->resetButton = $params->get('reset_button', 0) && $this->editable == "1"
			? '<input type="reset" class="button" name="Reset" value="' . $params->get('reset_button_label') . '" />' : '';
		$form->copyButton = $params->get('copy_button', 0) && $this->editable && $model->_rowId != ''
			? '<input type="submit" class="button" name="Copy" value="' . $params->get('copy_button_label') . '" />' : '';
		$applyButtonType = $model->isAjax() ? 'button' : 'submit';
		$form->applyButton = $params->get('apply_button', 0) && $this->editable
			? '<input type="' . $applyButtonType . '" class="button" name="apply" value="' . $params->get('apply_button_label') . '" />' : '';
		$form->deleteButton = $params->get('delete_button', 0) && $canDelete && $this->editable && $this_rowid != 0
			? '<input type="submit" value="' . $params->get('delete_button_label', 'Delete') . '" class="button" name="delete" />' : '';
		$form->gobackButton = $params->get('goback_button', 0) == "1"
			? '<input type="button" class="button" name="Goback" ' . FabrikWorker::goBackAction() . ' value="' . $params->get('goback_button_label')
				. '" />' : '';
		if ($model->_editable && $params->get('submit_button', 1))
		{
			$button = $model->isAjax() ? "button" : "submit";
			$submitClass = FabrikString::clean($form->submit_button_label);
			$form->submitButton = '<input type="' . $button . '" class="button ' . $submitClass . '" name="submit" value="'
				. $form->submit_button_label . '" />';
		}
		else
		{
			$form->submitButton = '';
		}
		if ($this->isMultiPage)
		{
			$form->prevButton = '<input type="button" class="fabrikPagePrevious button" name="fabrikPagePrevious" value="'
				. JText::_('COM_FABRIK_PREVIOUS') . '" />';
			$form->nextButton = '<input type="button" class="fabrikPageNext button" name="fabrikPageNext" value="' . JText::_('COM_FABRIK_NEXT')
				. '" />';
		}
		else
		{
			$form->nextButton = '';
			$form->prevButton = '';
		}

		// $$$ hugh - hide actions section is we're printing, or if not actions selected
		$noButtons = (empty($form->nextButton) && empty($form->prevButton) && empty($form->submitButton) && empty($form->gobackButton)
				&& empty($form->deleteButton) && empty($form->applyButton) && empty($form->copyButton) && empty($form->resetButton));
		if (JRequest::getVar('print', '0') == '1' || $noButtons)
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
		if (JRequest::getVar('fabrik_social_profile_hash', '') != '')
		{
			$fields[] = '<input type="hidden" name="fabrik_social_profile_hash" value="' . JRequest::getCmd('fabrik_social_profile_hash', '')
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
		jimport('joomla.utilities.simplecrypt');
		jimport('joomla.utilities.utility');
		$crypt = new JSimpleCrypt;
		$formModel = $this->getModel();
		$get = JRequest::get('get');
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
					/* $$$ hugh - need to check if $value is an array, 'cos if it isn't, like when presetting
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
	 * @param   array  &$aHiddenFields  hidden fields
	 *
	 * @return  void
	 */

	protected function _cryptViewOnlyElements(&$aHiddenFields)
	{
		jimport('joomla.utilities.simplecrypt');
		jimport('joomla.utilities.utility');
		$crypt = new JSimpleCrypt;
		$formModel = $this->getModel();
		$fields = array();
		$ro = $this->get('readOnlyVals');
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

			// $$$ rob - no dont do below as it will strip out join names join[x][fullname] => join
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
		if (JRequest::getVar('task') === 'cck')
		{
			$model = $this->getModel();
			$params = $model->getParams();
			$row = $model->getForm();
			JHTML::script('admincck.js', 'administrator/components/com_fabrik/views/', true);
			$document = JFactory::getDocument();
			$opts = new stdClass;
			$opts->livesite = JURI::base();
			$opts->ename = JRequest::getVar('e_name');
			$opts->catid = JRequest::getInt('catid');
			$opts->section = JRequest::getInt('section');
			$opts->formid = $row->id;

			$tmpl = ($row->form_template == '') ? "default" : $row->form_template;
			$tmpl = JRequest::getVar('cck_layout', $tmpl);

			$opts->tmplList = FabrikHelperAdminHTML::templateList('form', 'fabrik_cck_template', $tmpl);

			$views = array();
			$views[] = JHTML::_('select.option', 'form');
			$views[] = JHTML::_('select.option', 'details');
			$selView = JRequest::getVar('cck_view');
			$opts->viewList = JHTML::_('select.radiolist', $views, 'fabrik_cck_view', 'class="inputbox"', 'value', 'text', $selView);

			$opts = json_encode($opts);
			$script = "head.ready(function() {
		new adminCCK($opts);
		});";
			$document->addScriptDeclaration($script);
		}
	}

}
