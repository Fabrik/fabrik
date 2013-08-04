<?php
/**
 * Fabrik Raw Form View
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
 * Fabrik Raw Form View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikViewForm extends JViewLegacy
{

	/**
	 * Access value
	 *
	 * @var  int
	 */
	public $access = null;

	/**
	 * Inline edit view
	 *
	 * @return  void
	 */

	public function inlineEdit()
	{
		$model = $this->getModel('form');
		$document = JFactory::getDocument();
		$app = JFactory::getApplication();
		$input = $app->input;
		$j3 = FabrikWorker::j3();

		// Need to render() with all element ids in case canEditRow plugins etc use the row data.
		$elids = $input->get('elementid', array(), 'array');
		$input->set('elementid', null);

		$form = $model->getForm();
		if ($model->render() === false)
		{
			return false;
		}
		// Set back to original input so we only show the requested elements
		$input->set('elementid', $elids);
		$this->groups = $this->get('GroupView');

		// Main trigger element's id
		$elementid = $input->getInt('elid');

		$html = $j3 ? $this->inlineEditMarkUp() : $this->inlineEditMarkupJ25();
		echo implode("\n", $html);

		$srcs = array();
		$repeatCounter = 0;
		$elementids = (array) $input->get('elementid', array(), 'array');
		$eCounter = 0;
		$onLoad = array();
		$onLoad[] = "Fabrik.inlineedit_$elementid = {'elements': {}};";
		foreach ($elementids as $id)
		{
			$elementModel = $model->getElement($id, true);
			$elementModel->getElement();
			$elementModel->setEditable(true);
			$elementModel->formJavascriptClass($srcs);
			$elementJS = $elementModel->elementJavascript($repeatCounter);
			$onLoad[] = 'var o = new ' . $elementJS[0] . '("' . $elementJS[1] . '",' . json_encode($elementJS[2]) . ');';
			if ($eCounter === 0)
			{
				$onLoad[] = "o.select();";
				$onLoad[] = "o.focus();";
				$onLoad[] = "Fabrik.inlineedit_$elementid.token = '" . JSession::getFormToken() . "';";
			}
			$eCounter++;
			$onLoad[] = "Fabrik.inlineedit_$elementid.elements[$id] = o";
		}
		$onLoad[] = "Fabrik.fireEvent('fabrik.list.inlineedit.setData');";
		FabrikHelperHTML::script($srcs, implode("\n", $onLoad));
	}

	/**
	 * Create markup for boostrap inline editor
	 *
	 * @since   3.1b
	 *
	 * @return  array
	 */
	protected function inlineEditMarkUp()
	{

		$app = JFactory::getApplication();
		$input = $app->input;

		$html = array();
		$html[] = '<div class="modal">';
		$html[] = ' <div class="modal-header"><h3>' . JText::_('COM_FABRIK_EDIT') . '</h3></div>';
		$html[] = '<div class="modal-body">';
		$html[] = '<form>';
		foreach ($this->groups as $group)
		{
			foreach ($group->elements as $element)
			{
				$html[] = '<div class="control-group fabrikElementContainer ' . $element->id . '">';
				$html[] = '<label>' . $element->label . '</label>';
				$html[] = '<div class="fabrikElement">';
				$html[] = $element->element;
				$html[] = '</div>';
				$html[] = '</div>';
			}
		}
		$html[] = '</form>';
		$html[] = '</div>';
		if ($input->getBool('inlinesave') || $input->getBool('inlinecancel'))
		{
			$html[] = '<div class="modal-footer">';
			if ($input->getBool('inlinecancel') == true)
			{
				$html[] = '<a href="#" class="btn inline-cancel">';
				$html[] = FabrikHelperHTML::image('delete.png', 'list', @$this->tmpl, array('alt' => JText::_('COM_FABRIK_CANCEL')));
				$html[] = '<span>' . JText::_('COM_FABRIK_CANCEL') . '</span></a>';
			}

			if ($input->getBool('inlinesave') == true)
			{
				$html[] = '<a href="#" class="btn btn-primary inline-save">';
				$html[] = FabrikHelperHTML::image('save.png', 'list', @$this->tmpl, array('alt' => JText::_('COM_FABRIK_SAVE')));
				$html[] = '<span>' . JText::_('COM_FABRIK_SAVE') . '</span></a>';
			}
			$html[] = '</div>';
		}

		$html[] = '</div>';
		return $html;
	}

	/**
	 * Create markup for old skool 2.5 inline editor
	 *
	 * @since   3.1b
	 *
	 * @return  array
	 */
	protected function inlineEditMarkupJ25()
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		$html = array();
		$html[] = '<div class="floating-tip-wrapper inlineedit" style="position:absolute">';
		$html[] = '<div class="floating-tip" >';
		$html[] = '<ul class="fabrikElementContainer">';
		foreach ($this->groups as $group)
		{
			foreach ($group->elements as $element)
			{
				$html[] = '<li class="' . $element->id . '">' . $element->label . '</li>';
				$html[] = '<li class="fabrikElement">';
				$html[] = $element->element;
				$html[] = '</li>';
			}
		}
		$html[] = '</ul>';

		if ($input->getBool('inlinesave') || $input->getBool('inlinecancel'))
		{
			$html[] = '<ul class="">';
			if ($input->getBool('inlinecancel') == true)
			{
				$html[] = '<li class="ajax-controls inline-cancel">';
				$html[] = '<a href="#" class="">';
				$html[] = FabrikHelperHTML::image('delete.png', 'list', @$this->tmpl, array('alt' => JText::_('COM_FABRIK_CANCEL')));
				$html[] = '<span>' . JText::_('COM_FABRIK_CANCEL') . '</span></a>';
				$html[] = '</li>';
			}

			if ($input->getBool('inlinesave') == true)
			{
				$html[] = '<li class="ajax-controls inline-save">';
				$html[] = '<a href="#" class="">';
				$html[] = FabrikHelperHTML::image('save.png', 'list', @$this->tmpl, array('alt' => JText::_('COM_FABRIK_SAVE')));
				$html[] = '<span>' . JText::_('COM_FABRIK_SAVE') . '</span></a>';
				$html[] = '</li>';
			}
			$html[] = '</ul>';
		}
		$html[] = '</div>';
		$html[] = '</div>';
		return $html;
	}

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 */

	public function display($tpl = null)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$w = new FabrikWorker;
		$config = JFactory::getConfig();
		$model = $this->getModel('form');
		$document = JFactory::getDocument();

		// Get the active menu item
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$form = $model->getForm();
		$model->render();

		$listModel = $model->getListModel();
		if (!$model->canPublish())
		{
			if (!$app->isAdmin())
			{
				echo JText::_('COM_FABRIK_FORM_NOT_PUBLISHED');
				return false;
			}
		}

		$this->access = $model->checkAccessFromListSettings();
		if ($this->access == 0)
		{
			return JError::raiseWarning(500, JText::_('JERROR_ALERTNOAUTHOR'));
		}
		if (is_object($listModel))
		{
			$joins = $listModel->getJoins();
			$model->getJoinGroupIds($joins);
		}

		$params = $model->getParams();
		$params->def('icons', $app->getCfg('icons'));
		$pop = ($input->get('tmpl') == 'component') ? 1 : 0;
		$params->set('popup', $pop);

		$view = $input->get('view', 'form');
		if ($view == 'details')
		{
			$model->setEditable(false);
		}

		$groups = $model->getGroupsHiarachy();
		$gkeys = array_keys($groups);
		$JSONarray = array();
		$JSONHtml = array();

		for ($i = 0; $i < count($gkeys); $i++)
		{
			$groupModel = $groups[$gkeys[$i]];
			$groupTable = $groupModel->getGroup();
			$group = new stdClass;
			$groupParams = $groupModel->getParams();
			$aElements = array();

			// Check if group is acutally a table join
			$repeatGroup = 1;
			$foreignKey = null;

			if ($groupModel->canRepeat())
			{
				if ($groupModel->isJoin())
				{
					$joinModel = $groupModel->getJoinModel();
					$joinTable = $joinModel->getJoin();
					$foreignKey = '';
					if (is_object($joinTable))
					{
						$foreignKey = $joinTable->table_join_key;

						// $$$ rob test!!!
						if (!$groupModel->canView())
						{
							continue;
						}
						$elementModels = $groupModel->getPublishedElements();
						reset($elementModels);
						$tmpElement = current($elementModels);
						$smallerElHTMLName = $tmpElement->getFullName(true, false);
						$repeatGroup = count($model->data[$smallerElHTMLName]);
					}
				}
			}
			$groupModel->repeatTotal = $repeatGroup;
			$aSubGroups = array();
			for ($c = 0; $c < $repeatGroup; $c++)
			{
				$aSubGroupElements = array();
				$elCount = 0;
				$elementModels = $groupModel->getPublishedElements();
				foreach ($elementModels as $elementModel)
				{
					if (!$model->isEditable())
					{
						/* $$$ rob 22/03/2011 changes element keys by appending "_id" to the end, means that
						 * db join add append data doesn't work if for example the popup form is set to allow adding,
						 * but not editing records
						 * $elementModel->inDetailedView = true;
						 */
						$elementModel->setEditable(false);
					}

					// Force reload?
					$elementModel->HTMLids = null;
					$elementHTMLId = $elementModel->getHTMLId($c);
					if (!$model->isEditable())
					{
						$JSONarray[$elementHTMLId] = $elementModel->getROValue($model->data, $c);
					}
					else
					{
						$JSONarray[$elementHTMLId] = $elementModel->getValue($model->data, $c);
					}
					// Test for paginate plugin
					if (!$model->isEditable())
					{
						$elementModel->HTMLids = null;
						$elementModel->inDetailedView = true;
					}
					$JSONHtml[$elementHTMLId] = htmlentities($elementModel->render($model->data, $c), ENT_QUOTES, 'UTF-8');
				}
			}
		}
		$data = array("id" => $model->getId(), 'model' => 'table', "errors" => $model->errors, "data" => $JSONarray, 'html' => $JSONHtml,
			'post' => $_REQUEST);
		echo json_encode($data);
	}

}
