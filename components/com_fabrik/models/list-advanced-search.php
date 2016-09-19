<?php
/**
 * List Advanced Search Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

/**
 * List Advanced Search Class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.3.4
 */
class FabrikFEModelAdvancedSearch extends FabModel
{
	/**
	 * @var FabrikFEModelList
	 */
	protected $model;

	/**
	 * Previously submitted advanced search data
	 *
	 * @var array
	 */
	protected $advancedSearchRows = null;

	/**
	 * Set list model
	 *
	 * @param   FabrikFEModelList  $model
	 *
	 * @return void
	 */
	public function setModel($model)
	{
		$this->model = $model;
	}
	/**
	 * Called from index.php?option=com_fabrik&view=list&layout=_advancedsearch&tmpl=component&listid=4
	 * advanced search popup view
	 *
	 * @return  object	advanced search options
	 */
	public function opts()
	{
		$model = $this->model;
		$params = $model->getParams();
		$opts = new stdClass;

		// $$$ rob - 20/208/2012 if list advanced search off return nothing
		if ($params->get('advanced-filter') == 0)
		{
			return $opts;
		}

		$defaultStatement = $params->get('advanced-filter-default-statement', '<>');
		$opts->defaultStatement = $defaultStatement;

		$list = $model->getTable();
		$listRef = $model->getRenderContext();
		$opts->conditionList = FabrikHelperHTML::conditionList($listRef, '');
		list($fieldNames, $firstFilter) = $this->getAdvancedSearchElementList();
		$statements = $this->getStatementsOpts();
		$opts->elementList = JHTML::_('select.genericlist', $fieldNames, 'fabrik___filter[list_' . $listRef . '][key][]',
			'class="inputbox key" size="1" ', 'value', 'text');
		$opts->statementList = JHTML::_('select.genericlist', $statements, 'fabrik___filter[list_' . $listRef . '][condition][]',
			'class="inputbox" size="1" ', 'value', 'text', $defaultStatement);
		$opts->listid = $list->id;
		$opts->listref = $listRef;
		$opts->ajax = $model->isAjax();
		$opts->counter = count($this->getadvancedSearchRows()) - 1;
		$elements = $model->getElements();
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
	 * Get a list of elements that are included in the advanced search drop-down list
	 *
	 * @return  array  list of fields names and which is the first filter
	 */
	private function getAdvancedSearchElementList()
	{
		$model = $this->model;
		$first = false;
		$firstFilter = false;
		$fieldNames[] = JHTML::_('select.option', '', FText::_('COM_FABRIK_PLEASE_SELECT'));
		$elementModels = $model->getElements();

		foreach ($elementModels as $elementModel)
		{
			if (!$elementModel->canView('list'))
			{
				continue;
			}

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

				$fieldNames[] = JHTML::_('select.option', $elName, strip_tags(FText::_($element->label)));
			}
		}

		return array($fieldNames, $firstFilter);
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

		$model = $this->model;
		$statements = $this->getStatementsOpts();
		$input = $this->app->input;
		$rows = array();
		$elementModels = $model->getElements();
		list($fieldNames, $firstFilter) = $this->getAdvancedSearchElementList();
		$prefix = 'fabrik___filter[list_' . $model->getRenderContext() . '][';
		$type = '<input type="hidden" name="' . $prefix . 'search_type][]" value="advanced" />';
		$grouped = '<input type="hidden" name="' . $prefix . 'grouped_to_previous][]" value="0" />';
		$filters = $this->filterValues();
		$counter = 0;

		if (array_key_exists('key', $filters))
		{
			foreach ($filters['key'] as $key)
			{
				foreach ($elementModels as $elementModel)
				{
					$testKey = FabrikString::safeColName($elementModel->getFullName(false, false));

					if ($testKey == $key)
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
					case 'NOTEMPTY':
						$jsSel = 'NOTEMPTY';
						break;
					case 'EMPTY':
						$jsSel = 'EMPTY';
						break;
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
							case '%':
								$jsSel = ($lastChar == '%') ? 'CONTAINS' : $jsSel = 'ENDS WITH';
								break;
							default:
								if ($lastChar == '%')
								{
									$jsSel = 'BEGINS WITH';
								}
								break;
						}
						break;
				}

				if (is_string($value))
				{
					$value = trim(trim($value, '"'), '%');
				}

				if ($counter == 0)
				{
					$join = FText::_('COM_FABRIK_WHERE') . '<input type="hidden" value="WHERE" name="' . $prefix . 'join][]" />';
				}
				else
				{
					$join = FabrikHelperHTML::conditionList($model->getRenderContext(), $join);
				}

				$lineElName = FabrikString::safeColName($elementModel->getFullName(true, false));
				$orig = $input->get($lineElName);
				$input->set($lineElName, array('value' => $value));
				$filter = $elementModel->getFilter($counter, false);
				$input->set($lineElName, $orig);
				$key = JHTML::_('select.genericlist', $fieldNames, $prefix . 'key][]', 'class="inputbox key input-small" size="1" ', 'value', 'text', $key);
				$jsSel = JHTML::_('select.genericlist', $statements, $prefix . 'condition][]', 'class="inputbox input-small" size="1" ', 'value', 'text', $jsSel);
				$rows[] = array('join' => $join, 'element' => $key, 'condition' => $jsSel, 'filter' => $filter, 'type' => $type,
					'grouped' => $grouped);
				$counter++;
			}
		}

		if ($counter == 0)
		{
			$params = $model->getParams();
			$join = FText::_('COM_FABRIK_WHERE') . '<input type="hidden" name="' . $prefix . 'join][]" value="WHERE" />';
			$key = JHTML::_('select.genericlist', $fieldNames, $prefix . 'key][]', 'class="inputbox key" size="1" ', 'value', 'text', '');
			$defaultStatement = $params->get('advanced-filter-default-statement', '<>');
			$jsSel = JHTML::_('select.genericlist', $statements, $prefix . 'condition][]', 'class="inputbox" size="1" ', 'value', 'text', $defaultStatement);
			$rows[] = array('join' => $join, 'element' => $key, 'condition' => $jsSel, 'filter' => $firstFilter, 'type' => $type,
				'grouped' => $grouped);
		}

		$this->advancedSearchRows = $rows;

		return $rows;
	}

	/**
	 * Get a list of submitted advanced filters
	 *
	 * @return array advanced filter values
	 */
	public function filterValues()
	{
		$model = $this->model;
		$filters = $model->getFilterArray();
		$advanced = array();
		$iKeys = array_keys(FArrayHelper::getValue($filters, 'key', array()));

		foreach ($iKeys as $i)
		{
			$searchType = FArrayHelper::getValue($filters['search_type'], $i);

			if (!is_null($searchType) && $searchType == 'advanced')
			{
				foreach (array_keys($filters) as $k)
				{
					if (array_key_exists($k, $advanced))
					{
						$advanced[$k][] = FArrayHelper::getValue($filters[$k], $i, '');
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
	 * Build the advanced search link
	 *
	 * @return  string  <a href...> link
	 */
	public function link()
	{
		$model = $this->model;
		$params = $model->getParams();

		if ($params->get('advanced-filter', '0'))
		{
			$displayData = new stdClass;
			$displayData->url = $this->url();
			$displayData->tmpl = $model->getTmpl();
			$layout = FabrikHelperHTML::getLayout('list.fabrik-advanced-search-button');

			return $layout->render($displayData);
		}
		else
		{
			return '';
		}
	}

	/**
	 * Get the URL used to open the advanced search window
	 *
	 * @return  string
	 */
	public function url()
	{
		$model = $this->model;
		$table = $model->getTable();
		$url = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $this->package .
			'&amp;format=partial&amp;view=list&amp;layout=_advancedsearch&amp;tmpl=component&amp;listid='
			. $table->id . '&amp;nextview=' . $this->app->input->get('view', 'list');

		// Defines if we are in a module or in the component.
		$url .= '&amp;scope=' . $this->app->scope;
		$url .= '&amp;tkn=' . JSession::getFormToken();

		return $url;
	}

	/**
	 * Called via advanced search to load in a given element filter
	 *
	 * @return string html for filter
	 */
	public function elementFilter()
	{
		$model = $this->model;
		$input = $this->app->input;
		$elementId = $input->getId('elid');
		$pluginManager = FabrikWorker::getPluginManager();
		$className = $input->get('plugin');
		$plugin = $pluginManager->getPlugIn($className, 'element');
		$plugin->setId($elementId);
		$plugin->getElement();

		if ($input->get('context') == 'visualization')
		{
			$container = $input->get('parentView');
		}
		else
		{
			$container = 'listform_' . $model->getRenderContext();
		}

		$script = $plugin->filterJS(false, $container);
		FabrikHelperHTML::addScriptDeclaration($script);

		echo $plugin->getFilter($input->getInt('counter', 0), false);
	}

	/**
	 * Get a list of advanced search options
	 *
	 * @return array of JHTML options
	 */
	protected function getStatementsOpts()
	{
		$statements = array();
		$statements[] = JHTML::_('select.option', '=', FText::_('COM_FABRIK_EQUALS'));
		$statements[] = JHTML::_('select.option', '<>', FText::_('COM_FABRIK_NOT_EQUALS'));
		$statements[] = JHTML::_('select.option', 'BEGINS WITH', FText::_('COM_FABRIK_BEGINS_WITH'));
		$statements[] = JHTML::_('select.option', 'CONTAINS', FText::_('COM_FABRIK_CONTAINS'));
		$statements[] = JHTML::_('select.option', 'ENDS WITH', FText::_('COM_FABRIK_ENDS_WITH'));
		$statements[] = JHTML::_('select.option', '>', FText::_('COM_FABRIK_GREATER_THAN'));
		$statements[] = JHTML::_('select.option', '<', FText::_('COM_FABRIK_LESS_THAN'));
		$statements[] = JHTML::_('select.option', 'EMPTY', FText::_('COM_FABRIK_IS_EMPTY'));
		$statements[] = JHTML::_('select.option', 'NOTEMPTY', FText::_('COM_FABRIK_IS_NOT_EMPTY'));

		return $statements;
	}
}