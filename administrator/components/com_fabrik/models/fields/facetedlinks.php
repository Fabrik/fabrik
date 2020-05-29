<?php
/**
 * Renders a table of options for controlling the facet / related data links
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');

/**
 * Renders a table of options for controlling the facet / related data links
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */

class JFormFieldFacetedlinks extends JFormFieldList
{
	/**
	 * Element name
	 * @var		string
	 */
	protected $name = 'Facetedlinks';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string	The field input markup.
	 */

	protected function getInput()
	{
		$feListModel = $this->form->model->getFEModel();
		$joins = $feListModel->getJoinsToThisKey();

		if (empty($joins))
		{
			return '<i>' . FText::_('COM_FABRIK_NO_RELATED_DATA') . '</i>';
		}

		$listParams = $feListModel->getParams();
		$formOrder = json_decode($listParams->get('faceted_form_order'));
		$listOrder = json_decode($listParams->get('faceted_list_order'));
		$this->value = (array) $this->value;
		$linkedLists = FArrayHelper::getValue($this->value, 'linkedlist', array());
		$linkedForms = FArrayHelper::getValue($this->value, 'linkedform', array());

		if (empty($listOrder) || is_null($listOrder))
		{
			$listOrder = array_keys($linkedLists);
		}

		if (empty($formOrder) || is_null($formOrder))
		{
			$formOrder = array_keys($linkedForms);
		}

		// Newly added related elements
		foreach ($joins as $linkedList)
		{
			$key = $linkedList->list_id . '-' . $linkedList->form_id . '-' . $linkedList->element_id;

			if (!in_array($key, $listOrder))
			{
				$listOrder[] = $key;
			}

			if (!in_array($key, $formOrder))
			{
				$formOrder[] = $key;
			}
		}

		$listHeaders = FArrayHelper::getValue($this->value, 'linkedlistheader', array());
		$formHeaders = FArrayHelper::getValue($this->value, 'linkedformheader', array());
		$formLinkTypes = FArrayHelper::getValue($this->value, 'linkedform_linktype', array());
		$listLinkTypes = FArrayHelper::getValue($this->value, 'linkedlist_linktype', array());
		$listLinkTexts = FArrayHelper::getValue($this->value, 'linkedlisttext', array());
		$formLinkTexts = FArrayHelper::getValue($this->value, 'linkedformtext', array());

		$this->linkedlists = array();
		$f = 0;
		$listReturn = array();
		$formReturn = array();
		$listReturn[] = '<h4>' . FText::_('COM_FABRIK_LISTS')
			. '</h4><table class="adminlist linkedLists table table-striped">
					<thead>
					<tr>
						<th></th>
						<th>' . FText::_('COM_FABRIK_LIST') . '</th>
						<th>' . FText::_('COM_FABRIK_LINK_TO_LIST') . '</th>
						<th>' . FText::_('COM_FABRIK_HEADING') . '</th>
						<th>' . FText::_('COM_FABRIK_BUTTON_TEXT') . '</th>
						<th>' . FText::_('COM_FABRIK_POPUP') . '</th>
					</tr>
				</thead>
				<tbody>';
		$formReturn[] = '<h4>' . FText::_('COM_FABRIK_FORMS')
			. '</h4><table class="adminlist linkedForms table table-striped">
					<thead>
					<tr>
						<th></th>
						<th>' . FText::_('COM_FABRIK_LIST') . '</th>
						<th>' . FText::_('COM_FABRIK_LINK_TO_FORM') . '</th>
						<th>' . FText::_('COM_FABRIK_HEADING') . '</th>
						<th>' . FText::_('COM_FABRIK_BUTTON_TEXT') . '</th>
						<th>' . FText::_('COM_FABRIK_POPUP') . '</th>
					</tr>
				</thead>
				<tbody>';

		foreach ($listOrder as $order)
		{
			$linkedList = $this->findJoin($joins, $order);

			if ($linkedList === false)
			{
				continue;
			}

			$key = $linkedList->list_id . '-' . $linkedList->form_id . '-' . $linkedList->element_id;
			$label = str_replace(array("\n", "\r", '<br>', '</br>'), '', $linkedList->listlabel);
			$hover = FText::_('ELEMENT') . ': ' . $linkedList->element_label . ' [' . $linkedList->plugin . ']';

			$listReturn[] = '<tr class="row' . ($f % 2) . '">';
			$listReturn[] = '<td class="handle"></td>';
			$listReturn[] = '<td>' . JHTML::_('tooltip', $hover, $label, 'tooltip.png', $label);

			$yesChecked = FArrayHelper::getValue($linkedLists, $key, 0) != '0' ? 'checked="checked"' : '';
			$noChecked = $yesChecked == '' ? 'checked="checked"' : '';

			$listReturn[] = '<td>';
			$listReturn[] = '<label><input name="' . $this->name . '[linkedlist][' . $key . ']" value="0" ' . $noChecked . ' type="radio" />'
				. FText::_('JNO') . '</label>';
			$listReturn[] = '<label><input name="' . $this->name . '[linkedlist][' . $key . ']" value="' . $key . '" ' . $yesChecked
				. ' type="radio" />' . FText::_('JYES') . '</label>';
			$listReturn[] = '</td>';

			$listReturn[] = '<td>';
			$listReturn[] = '<input type="text" name="' . $this->name . '[linkedlistheader][' . $key . ']" value="' . @$listHeaders[$key] . '" size="16" />';
			$listReturn[] = '</td>';

			$listReturn[] = '<td>';
			$listReturn[] = '<input type="text" name="' . $this->name . '[linkedlisttext][' . $key . ']" value="' . @$listLinkTexts[$key] . '" size="16" />';
			$listReturn[] = '</td>';

			$yesChecked = FArrayHelper::getValue($listLinkTypes, $key, 0) != '0' ? 'checked="checked"' : '';
			$noChecked = $yesChecked == '' ? 'checked="checked"' : '';

			$listReturn[] = '<td>';
			$listReturn[] = '<label><input name="' . $this->name . '[linkedlist_linktype][' . $key . ']" value="0" ' . $noChecked
				. ' type="radio" />' . FText::_('JNO') . '</label>';
			$listReturn[] = '<label><input name="' . $this->name . '[linkedlist_linktype][' . $key . ']" value="' . $key . '" ' . $yesChecked
				. ' type="radio" />' . FText::_('JYES') . '</label>';
			$listReturn[] = '</td>';
			$listReturn[] = '</tr>';
		}

		foreach ($formOrder as $order)
		{
			$linkedList = $this->findJoin($joins, $order);

			if ($linkedList === false)
			{
				continue;
			}

			$key = $linkedList->list_id . '-' . $linkedList->form_id . '-' . $linkedList->element_id;
			$label = str_replace(array("\n", "\r", '<br>', '</br>'), '', $linkedList->listlabel);
			$hover = FText::_('ELEMENT') . ': ' . $linkedList->element_label . ' [' . $linkedList->plugin . ']';

			$yesChecked = FArrayHelper::getValue($linkedForms, $key, 0) != '0' ? 'checked="checked"' : '';
			$noChecked = $yesChecked == '' ? 'checked="checked"' : '';

			$formReturn[] = '<tr class="row' . ($f % 2) . '">';
			$formReturn[] = '<td class="handle"></td>';
			$formReturn[] = '<td>' . JHTML::_('tooltip', $hover, $label, 'tooltip.png', $label);
			$formReturn[] = '<td>';
			$formReturn[] = '<label><input name="' . $this->name . '[linkedform][' . $key . ']" value="0" ' . $noChecked . ' type="radio" />'
				. FText::_('JNO') . '</label>';
			$formReturn[] = '<label><input name="' . $this->name . '[linkedform][' . $key . ']" value="' . $key . '" ' . $yesChecked
				. ' type="radio" />' . FText::_('JYES') . '</label>';
			$formReturn[] = '</td>';

			$formReturn[] = '<td>';
			$formReturn[] = '<input type="text" name="' . $this->name . '[linkedformheader][' . $key . ']" value="' . @$formHeaders[$key] . '" size="16" />';
			$formReturn[] = '</td>';

			$formReturn[] = '<td>';
			$formReturn[] = '<input type="text" name="' . $this->name . '[linkedformtext][' . $key . ']" value="' . @$formLinkTexts[$key] . '" size="16" />';
			$formReturn[] = '</td>';

			$yesChecked = FArrayHelper::getValue($formLinkTypes, $key, 0) != '0' ? 'checked="checked"' : '';
			$noChecked = $yesChecked == '' ? 'checked="checked"' : '';

			$formReturn[] = '<td>';
			$formReturn[] = '<label><input name="' . $this->name . '[linkedform_linktype][' . $key . ']" value="0" ' . $noChecked
				. ' type="radio" />' . FText::_('JNO') . '</label>';
			$formReturn[] = '<label><input name="' . $this->name . '[linkedform_linktype][' . $key . ']" value="' . $key . '" ' . $yesChecked
				. ' type="radio" />' . FText::_('JYES') . '</label>';
			$formReturn[] = '</td>';
			$formReturn[] = '</tr>';

			$f++;
		}

		$listReturn[] = '</tbody></table>';
		$formReturn[] = '</tbody></table>';
		$return = array_merge($listReturn, $formReturn);
		$facetedFormOrder = htmlspecialchars($listParams->get('faceted_form_order'));
		$return[] = '<input name="jform[params][faceted_form_order]" type="hidden" value="' . $facetedFormOrder . '" />';
		$facetedListOrder = htmlspecialchars($listParams->get('faceted_list_order'));
		$return[] = '<input name="jform[params][faceted_list_order]" type="hidden" value="' . $facetedListOrder . '" />';

		return implode("\n", $return);
	}

	/**
	 * Find a join based on composite key
	 *
	 * @param   array   $joins      Joins
	 * @param   string  $searchKey  Key
	 *
	 * @return  mixed   False if not found, join object if found
	 */
	protected function findJoin($joins, $searchKey)
	{
		foreach ($joins as $join)
		{
			$key = $join->list_id . '-' . $join->form_id . '-' . $join->element_id;

			if ($searchKey === $key)
			{
				return $join;
			}
		}

		return false;
	}
}
