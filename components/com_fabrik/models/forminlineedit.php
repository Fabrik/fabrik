<?php
/**
 * Fabrik Form Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');
require_once 'fabrikmodelform.php';
require_once COM_FABRIK_FRONTEND . '/helpers/element.php';

/**
 * Fabrik Form Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikFEModelFormInlineEdit extends FabModelForm
{
	/**
	 * Render the inline edit interface
	 *
	 * @return void
	 */

	public function render()
	{
		$this->formModel = JModelLegacy::getInstance('Form', 'FabrikFEModel');
		$document = JFactory::getDocument();
		$app = JFactory::getApplication();
		$input = $app->input;
		$j3 = FabrikWorker::j3();

		// Need to render() with all element ids in case canEditRow plugins etc. use the row data.
		$elids = $input->get('elementid', array(), 'array');
		$input->set('elementid', null);

		$form = $this->formModel->getForm();
		$this->formModel->render();

		// Set back to original input so we only show the requested elements
		$input->set('elementid', $elids);
		$this->groups = $this->formModel->getGroupView();

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
			$elementModel = $this->formModel->getElement($id, true);
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
	 * Create markup for bootstrap inline editor
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
		$html[] = ' <div class="modal-header"><h3>' . FText::_('COM_FABRIK_EDIT') . '</h3></div>';
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
				$html[] = FabrikHelperHTML::image('delete.png', 'list', @$this->tmpl, array('alt' => FText::_('COM_FABRIK_CANCEL')));
				$html[] = '<span>' . FText::_('COM_FABRIK_CANCEL') . '</span></a>';
			}

			if ($input->getBool('inlinesave') == true)
			{
				$html[] = '<a href="#" class="btn btn-primary inline-save">';
				$html[] = FabrikHelperHTML::image('save.png', 'list', @$this->tmpl, array('alt' => FText::_('COM_FABRIK_SAVE')));
				$html[] = '<span>' . FText::_('COM_FABRIK_SAVE') . '</span></a>';
			}

			$html[] = '</div>';
		}

		$html[] = '</div>';

		return $html;
	}

	/**
	 * Create markup for old school 2.5 inline editor
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
				$html[] = FabrikHelperHTML::image('delete.png', 'list', @$this->tmpl, array('alt' => FText::_('COM_FABRIK_CANCEL')));
				$html[] = '<span>' . FText::_('COM_FABRIK_CANCEL') . '</span></a>';
				$html[] = '</li>';
			}

			if ($input->getBool('inlinesave') == true)
			{
				$html[] = '<li class="ajax-controls inline-save">';
				$html[] = '<a href="#" class="">';
				$html[] = FabrikHelperHTML::image('save.png', 'list', @$this->tmpl, array('alt' => FText::_('COM_FABRIK_SAVE')));
				$html[] = '<span>' . FText::_('COM_FABRIK_SAVE') . '</span></a>';
				$html[] = '</li>';
			}

			$html[] = '</ul>';
		}

		$html[] = '</div>';
		$html[] = '</div>';

		return $html;
	}

	/**
	 * Set form model
	 *
	 * @param   JModel  $model  Front end form model
	 *
	 * @return  void
	 */
	public function setFormModel($model)
	{
		$this->formModel = $model;
	}

	/**
	 * Inline edit show the edited element
	 *
	 * @return string
	 */

	public function showResults()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$listModel = $this->formModel->getListModel();
		$listid = $listModel->getId();
		$listModel->clearCalculations();
		$listModel->doCalculations();
		$elementid = $input->getInt('elid');

		if ($elementid === 0)
		{
			return;
		}

		$elmentModel = $this->formModel->getElement($elementid, true);

		if (!$elmentModel)
		{
			return;
		}

		$rowid = $input->get('rowid');
		$listModel->setId($listid);

		// If the inline edit stored a element join we need to reset back the table
		$listModel->clearTable();
		$listModel->getTable();
		$data = $listModel->getRow($rowid);

		// For a change in the element which means its no longer shown in the list due to prefilter. We may want to remove the row from the list as well?
		if (!is_object($data))
		{
			$data = new stdClass;
		}

		$key = $input->get('element');
		$html = '';
		$html .= $elmentModel->renderListData($data->$key, $data);
		$listRef = 'list_' . $input->get('listref');
		$doCalcs = "\nFabrik.blocks['" . $listRef . "'].updateCals(" . json_encode($listModel->getCalculations()) . ")";
		$html .= '<script type="text/javascript">';
		$html .= $doCalcs;
		$html .= "</script>\n";

		return $html;
	}
}
