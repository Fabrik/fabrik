<?php
/**
 * Mutate the list data into a pivot table
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.pivot
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Mutate the list data into a pivot table
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.pivot
 * @since       3.1
 */

class PlgFabrik_ListPivot extends PlgFabrik_List
{
	/**
	 * Inject the select sum() fields into the list query JDatabaseQuery object
	 *
	 * @param   array  $args  Plugin call arguements
	 *
	 * @return  void
	 */
	public function onBuildQuerySelect($args)
	{
		if (!$query = $this->hasQuery($args))
		{
			return;
		}

		$sum = $this->sums();
		$query->select($sum);
	}

	/**
	 * Do the plugin arguements have a JDatabaseQuery among them
	 *
	 * @param   array  $args  Plugin call arguements
	 *
	 * @return  mixed  false if no JDatabaseQuery found otherwise returns JDatabaseQuery object
	 */
	private function hasQuery($args)
	{
		foreach ($args as $arg)
		{
			if (is_object($arg) && is_a($arg, 'JDatabaseQuery'))
			{
				return $arg;
			}
		}

		return false;
	}

	/**
	 * Inject the group by statement into the query object
	 *
	 * @param   array  $args  Plugin arguements
	 *
	 * @return  void
	 */
	public function onBuildQueryGroupBy($args)
	{
		if (!$query = $this->hasQuery($args))
		{
			return;
		}

		$query->clear('group');
		$query->group($this->group());
	}

	/**
	 * Build the group by sql statement
	 *
	 * @return string
	 */
	private function group()
	{
		$params = $this->getParams();
		$groups = explode(',', $params->get('pivot_group'));

		foreach ($groups as &$group)
		{
			$group = trim($group);
			$group = FabrikString::safeColName($group);
		}

		$group = implode(', ', $groups);

		return $group;
	}

	/**
	 * Build the sums() sql statement
	 *
	 * @return string
	 */
	private function sums()
	{
		$params = $this->getParams();
		$sums = explode(',', $params->get('pivot_sum'));
		$db = $this->model->getDb();

		foreach ($sums as &$sum)
		{
			$sum = trim($sum);
			$as = FabrikString::safeColNameToArrayKey($sum);

			$statement = 'SUM(' . FabrikString::safeColName($sum) . ')';
			$statement .= ' AS ' . $db->quoteName($as);
			$statement .= ', SUM(' . FabrikString::safeColName($sum) . ')';
			$statement .= ' AS ' . $db->quoteName($as . '_raw');

			$sum = $statement;
		}

		$sum = implode(', ', $sums);

		return $sum;
	}

	private function getCols()
	{
		$params = $this->getParams();
		$xCol = $params->get('pivot_xcol', '');
		$yCol = $params->get('pivot_ycol', '');

		if ($xCol === '' || $yCol === '')
		{
			throw new UnexpectedValueException(FText::_('PLG_LIST_PIVOT_ERROR_X_AND_Y_COL_MUST_BE_SELECTED'));
		}
		//pivot___date

		return array($xCol, $yCol);
	}

	public function onGetPluginRowHeadings(&$args)
	{
		list($xCol, $yCol) = $this->getCols();
		$args =& $args[0];
		$yColLabel = $args['tableHeadings'][$yCol];
		$yColHeadingClass = $args['headingClass'][$yCol];
		$yColCellClas = $args['cellClass'][$yCol];

		$headings = array();

		$headings[$yCol] = $yColLabel;

		$data = $args['data'];
		$headingClass = $args['headingClass'][$xCol];
		$cellClass = $args['cellClass'][$xCol];
		$args['headingClass'] = array();
		$args['cellClass'] = array();

		$args['headingClass'][$yCol] = $yColHeadingClass;
		$args['cellClass'][$yCol] =  $yColCellClas;

		$group = array_shift($data);
		$row = array_shift($group);

		foreach ($row as $k => $v)
		{
			if ($k !== $yCol)
			{
				$headings[$k] = $k;
				$args['headingClass'][$k] = $headingClass;
				$args['cellClass'][$k] = $cellClass;
			}
		}

		$headings['pivot_total'] = FText::_('PLG_LIST_PIVOT_LIST_X_TOTAL');
		$args['headingClass']['pivot_total'] = $headingClass;
		$args['cellClass']['pivot_total'] = $cellClass;

		$args['tableHeadings'] = $headings;
	}

	/**
	 * Set the list to use an unconstrained query in getData()
	 *
	 */
	public function onPreLoadData()
	{
		$params = $this->params;

		// Set the list query to be unconstrained
		$this->model->setLimits(0, -1);
		$app = JFactory::getApplication();

		// Hide the list nav as we are running an unconstrained query
		$app->input->set('fabrik_show_nav', 0);
	}

	/**
	 * Try to cache the list data
	 */
	public function onBeforeListRender()
	{
		if (!$this->params->get('pivot_cache'))
		{
			return;
		}

		$cache = FabrikWorker::getCache();
		$cache->setCaching(1);
		$res = $cache->call(array(get_class($this), 'cacheResults'), $this->model->getId());

		$this->model->set('data', $res);

	}

	public static function cacheResults($listId)
	{
		$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
		$listModel->setId($listId);
		$data = $listModel->getData();

		return $data;
	}

	/**
	 * List model has loaded its data, lets pivot it!
	 *
	 * @param   &$args  Array  Additional options passed into the method when the plugin is called
	 *
	 * @return bool currently ignored
	 */

	public function onLoadData(&$args)
	{
		$data =& $args[0]->data;
		$params = $this->getParams();
		$sums = $params->get('pivot_sum');
		list($xCol, $yCol) = $this->getCols();
		$rawSums = $sums . '_raw';

		// Get distinct areas?
		$xCols = array();

		foreach ($data as $group)
		{
			foreach ($group as $row)
			{
				if (!in_array($row->$xCol, $xCols))
				{
					$xCols[] = $row->$xCol;
				}
			}
		}

		// Order headings
		asort($xCols);

		// Get distinct dates
		$yCols = array();

		foreach ($data as $group)
		{
			foreach ($group as $row)
			{
				if (!in_array($row->$yCol, $yCols))
				{
					$yCols[] = $row->$yCol;
				}
			}
		}

		$new = array();

		foreach ($yCols as $yColData)
		{
			$newRow = new stdClass();
			$newRow->$yCol = $yColData;
			$total = 0;

			// Set default values
			foreach ($xCols as $xColData)
			{
				$newRow->$xColData = '';
			}

			foreach ($data as $group)
			{
				foreach ($group as $row)
				{
					foreach ($xCols as $xColData)
					{
						if ($row->$xCol === $xColData && $row->$yCol === $yColData)
						{
							$newRow->$xColData = $row->$sums;
							// $total += (float) $row->$sums;
							$total += (float) $row->$rawSums;
						}
					}
				}
			}

			$newRow->pivot_total = $total;
			$new[] = $newRow;
		}

		// Add totals @ bottom
		$yColTotals = new stdClass;
		$yColTotals->$yCol = FText::_('PLG_LIST_PIVOT_LIST_Y_TOTAL');
		$total = 0;

		foreach ($xCols as $x)
		{
			$c = JArrayHelper::getColumn($new, $x);
			$yColTotals->$x = array_sum($c);
			$total += (float) $yColTotals->$x;
		}

		$yColTotals->pivot_total = $total;
		$new[] = $yColTotals;

		$data[0] = $new;

		return true;
	}
}
