<?php
/**
 * Fabrik Chart Viz Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.chart
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/visualization.php';

/**
 * Fabrik Chart Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.chart
 * @since       3.0
 */

class FabrikModelChart extends FabrikFEModelVisualization
{
	/**
	 * Google charts api url
	 *
	 * @var string
	 */
	protected $url = 'http://chart.apis.google.com/chart';

	/**
	 * Get min and max values form totals
	 *
	 * @param   array  $totals  Totals
	 *
	 * @return  array
	 */
	private function _getMinMax($totals)
	{
		// $min will only go lower if data is negative!
		$max = 0;
		$min = 0;

		foreach ($totals as $tots)
		{
			if (max($totals) > $max)
			{
				$max = max($totals);
			}

			if (min($totals) < $min)
			{
				$min = min($totals);
			}
		}

		return array('min' => $min, 'max' => $max);
	}

	/**
	 * Get min and max values form totals
	 *
	 * @param   array  $gdata  Grouped data
	 * @param   array  $gsums  Sums
	 *
	 * @return  array
	 */

	protected function getMinMax($gdata, $gsums)
	{
		$calcfound = $this->getCalcFound();

		// @TODO if showing a chart that is displaying a summed element then the max is returned as the total
		// of that elements summed values which is incorrect
		$minmax = $calcfound ? $this->_getMinMax($gsums) : $this->_getMinMax(explode(",", $gdata[0]));

		return $minmax;
	}

	/**
	 * Get the chart
	 *
	 * @return string
	 */
	public function getChart()
	{
		$params = $this->getParams();
		$this->calc_prefixmap = array('sum___' => 'sums', 'avg___' => 'avgs', 'med___' => 'medians', 'cnt___' => 'count');
		$w = (int) $params->get('chart_width', 200);
		$h = (int) $params->get('chart_height', 200);
		$graph = $params->get('graph_type');

		$fillGraphs = $params->get('fill_line_graph');

		$x_axis_label = (array) $params->get('x_axis_label');
		$chartElements = (array) $params->get('chart_elementList');
		$legends = $params->get('graph_show_legend', '');
		$chxl_override = $params->get('chart_chxl', '');
		$chxl_override = trim(str_replace(',', '|', $chxl_override), ',');
		$chds_override = $params->get('chart_chds', '');
		$chds_override = trim(str_replace('|', ',', $chds_override), '|');
		$chg_override = $params->get('chart_chg', '');
		$chm_override = $params->get('chart_chm', '');
		$chma_override = $params->get('chart_chma', '');
		$c = 0;
		$gdata = array();
		$glabels = array();

		$max = 0;
		$min = 0;

		$calculationData = array();
		$tableDatas = $this->getTableData();

		foreach ($tableDatas as $tableData)
		{
			$alldata = $tableData['data'];
			$cals = $tableData['cals'];
			$column = $chartElements[$c];
			$listModel = $tableData['model'];
			$pref = substr($column, 0, 6);

			$label = FArrayHelper::getValue($x_axis_label, $c, '');
			$tmpgdata = array();
			$calcfound = $this->getCalcFound();

			if ($calcfound)
			{
				$column = JString::substr($column, 6);
			}

			$elements = $listModel->getElements('filtername');
			$safename = FabrikString::safeColName($column);
			$colElement = $elements[$safename];

			if ($calcfound)
			{
				$calckey = $this->calc_prefixmap[$pref];
				/*
				 * you shouldn't mix calculation elements with normal elements when creating the chart
				 * so if ONE calculation element is found we use the calculation data rather than normal element data
				 * this is because a calculation element only generates one value, if want to compare two averages then
				 * they get rendered as two groups of data and on bar charts this overlays one average over the other, rather than next to it
				 */
				// $calcfound = true;

				$caldata = $cals[$calckey][$column . '_obj'];

				if (is_array($caldata))
				{
					foreach ($caldata as $k => $o)
					{
						if ($k !== 'Total')
						{
							$calculationData[] = $colElement->getCalculationValue($o->value);
						}
					}
				}

				$gdata[$c] = implode(',', $tmpgdata);

				// $$$ hugh - playing around with pie charts
				$gsums[$c] = array_sum($calculationData);
			}
			else
			{
				$origColumn = $column;

				// _raw fields are most likely to contain the value
				$column .= "_raw";

				foreach ($alldata as $group)
				{
					foreach ($group as $row)
					{
						if (!array_key_exists($column, $row))
						{
							// Didn't find a _raw column - revert to orig
							$column = $origColumn;

							if (!array_key_exists($column, $row))
							{
								JError::raiseWarning(E_NOTICE, $column . ': NOT FOUND - PLEASE CHECK IT IS PUBLISHED');
								continue;
							}
						}

						if (trim($row->$column) == '')
						{
							$tmpgdata[] = -1;
						}
						else
						{
							$tmpgdata[] = $colElement->getCalculationValue($row->$column);
						}
					}

					$gdata[$c] = implode(',', $tmpgdata);

					// $$$ hugh - playing around with pie charts
					$gsums[$c] = array_sum($tmpgdata);
				}
			}

			$c++;
		}

		if ($calcfound)
		{
			$gdata = array(implode(',', $calculationData));
		}
		/*
		 * $$$ hugh - pie chart data has to be summed - the API only takes a
		 * single dataset for pie charts.  And it doesn't make sense trying to
		 * chart individual row data for multiple elements in a pie chart.
		 */
		switch ($graph)
		{
			case 'p':
			case 'p3':
				list($chd, $chxl, $chds, $fillGraphs) = $this->pieChart($c, $gdata, $gsums);
				break;
			case 'bhs':
				list($chd, $chxl, $chds) = $this->horizontalBarChart($c, $gdata, $gsums);
				break;
			default:
				list($chd, $chxl, $chds) = $this->defaultChart($c, $gdata, $gsums);

				break;
		}

		list($colours, $fills) = $this->getColours();

		$return = '<img src="' . $this->url . '?';
		$qs = 'chs=' . $w . 'x' . $h;
		$qs .= '&amp;chd=t:' . $chd;
		$qs .= '&amp;cht=' . $graph;
		$qs .= '&amp;chco=' . $colours;
		$qs .= '&amp;chxt=x,y';
		$qs .= '&amp;chxl=' . $chxl;
		$qs .= '&amp;chds=' . $chds;

		if (!empty($chm_override))
		{
			$qs .= '&amp;chm=' . $chm_override;
		}

		if (!empty($chma_override))
		{
			$qs .= '&amp;chma=' . $chma_override;
		}
		elseif ($fillGraphs)
		{
			$qs .= '&amp;chm=' . implode('|', $fills);
		}

		if ($legends)
		{
			$qs .= '&amp;chdl=' . implode('|', $this->getAxisLabels($c));
		}

		if (!empty($chg_override))
		{
			$qs .= '&amp;chg=' . $chg_override;
		}
		elseif ($fillGraphs)
		{
			$qs .= '&amp;chm=' . implode('|', $fills);
		}

		$qs .= '&amp;' . $params->get('chart_custom');
		$return .= $qs . '" alt="' . $this->getRow()->label . '" />';
		$this->image = $return;

		return $return;
	}

	/**
	 * Get the chart colours
	 *
	 * @return array(colours, fills)
	 */

	protected function getColours()
	{
		$params = $this->getParams();
		$fillGraphs = $params->get('fill_line_graph');
		$chartColours = (array) $params->get('chart_colours');
		$gcolours = array();
		$fills = array();
		$calcfound = $this->getCalcFound();
		$tableDatas = $this->getTableData();

		for ($c = 0; $c < count($tableDatas); $c++)
		{
			$colour = FArrayHelper::getValue($chartColours, $c, '');
			$colour = str_replace("#", '', $colour);

			if ($fillGraphs)
			{
				$c2 = $c + 1;
				$fills[] = 'b,' . $colour . "," . $c . "," . $c2 . ",0";
			}

			$gcolours[] = $colour;
		}

		$colours = implode(($calcfound ? '|' : ','), $gcolours);

		return array($colours, $fills);
	}

	/**
	 * Get code to form horizontal bar chart
	 *
	 * @param   int    $c      Total data sets
	 * @param   array  $gdata  Grouped data
	 * @param   array  $gsums  Summed data
	 *
	 * @return array
	 */

	protected function horizontalBarChart($c, $gdata, $gsums)
	{
		$params = $this->getParams();
		$chartWidth = (int) $params->get('chart_width', 200);
		$chds_override = $params->get('chart_chds', '');
		$chds_override = trim(str_replace('|', ',', $chds_override), '|');
		$axisLabels = implode("|", $this->getAxisLabels($c));
		$calcfound = $this->getCalcFound();
		$measurement_unit = FArrayHelper::getValue($measurement_units, $c, '');
		$minmax = $this->getMinMax($gdata, $gsums);

		$chd = implode('|', $gdata);

		if (!empty($chds_override))
		{
			$chds = $chds_override;
		}
		else
		{
			$chds = $minmax['min'] . ',' . $minmax['max'];
		}

		// Set the bar heights to auto so that they scale to fit inside chart area.
		$chd .= '&chbh=a';

		// $$$ hugh - we have to reverse the labels for horizontal bar charts
		$axisLabels = implode('|', array_reverse(explode('|', $axisLabels)));

		if (empty($chxl_override))
		{
			$chxl = '0:|' . $minmax['min'] . '|' . $minmax['max'] . $measurement_unit . '|' . '1:|' . $axisLabels;
		}
		else
		{
			$chxl = '0:|' . $chxl_override . '|' . '1:|' . $axisLabels;
		}

		return array($chd, $chxl, $chds);
	}

	/**
	 * Get some data for the default chart types
	 *
	 * @param   int    $c      Total data sets
	 * @param   array  $gdata  Data
	 * @param   array  $gsums  Calcs
	 *
	 * @return array
	 */

	protected function defaultChart($c, $gdata, $gsums)
	{
		$params = $this->getParams();
		$minmax = $this->getMinMax($gdata, $gsums);
		$measurement_unit = FArrayHelper::getValue($measurement_units, $c, '');
		$chds_override = $params->get('chart_chds', '');

		if (preg_match('#^\d+,$#', $chds_override))
		{
			$chds_override .= $minmax['max'];
		}

		$chds_override = trim(str_replace('|', ',', $chds_override), '|');
		$axisLabels = implode("|", $this->getAxisLabels($c));
		$chxl_override = $params->get('chart_chxl', '');
		$chxl_override = trim(str_replace(',', '|', $chxl_override), ',');

		if (empty($chxl_override) && !empty($chds_override))
		{
			$chxl_override = str_replace(',', '|', $chds_override);
		}

		$chd = implode('|', $gdata);

		if (empty($chxl_override))
		{
			$chxl = '0:|' . $axisLabels . '|1:|' . $minmax['min'] . '|' . $minmax['max'] . $measurement_unit;
		}
		else
		{
			$chxl = '0:|' . $axisLabels . '|1:|' . $chxl_override;
		}

		if (!empty($chds_override))
		{
			$chds = $chds_override;
		}
		else
		{
			$chds = $minmax['min'] . ',' . $minmax['max'];
		}

		return array($chd, $chxl, $chds);
	}

	/**
	 * Grab the tables and get their data, calculations etc.
	 *
	 * @return array table info and data.
	 */

	private function getTableData()
	{
		if (!isset($this->tableData))
		{
			$tmodels = array();
			$this->tableData = array();
			$params = $this->getParams();
			$listid = (array) $params->get('chart_table');
			$chartWheres = (array) $params->get('chart_where');
			$c = 0;

			foreach ($listid as $lid)
			{
				if (!array_key_exists($lid, $tmodels))
				{
					$listModel = null;
					$listModel = JModelLegacy::getInstance('List', 'FabrikFEModel');
					$listModel->setId($lid);
					$tmodels[$lid] = $listModel;
				}
				else
				{
					$listModel = $tmodels[$lid];
				}

				$list = $listModel->getTable();
				$form = $listModel->getForm();

				// $$$ hugh - testing hack to let plugins add WHERE clauses
				if (array_key_exists($c, $chartWheres) && !empty($chartWheres[$c]))
				{
					$listModel->setPluginQueryWhere('chart', $chartWheres[$c]);
				}
				else
				{
					// If no where clause, explicitly clear any previously set clause
					$listModel->unsetPluginQueryWhere('chart');
				}

				// Remove filters?

				// $$$ hugh - pagination must be done BEFORE calling render(), to avoid caching issues from session data.
				$listModel->setLimits(0, 0);
				$listModel->getPagination(0, 0, 0);
				$listModel->render();
				$alldata = $listModel->getData();
				$cals = $listModel->getCalculations();
				$this->tableData[$c] = array('id' => $lid, 'data' => $alldata, 'cals' => $cals, 'model' => $listModel);
				$c++;
			}
		}

		return $this->tableData;
	}

	/**
	 * Get Axis Labels
	 *
	 * @param   int  $total  Total?
	 *
	 * @return array axis labels
	 */

	private function getAxisLabels($total)
	{
		if (isset($this->axisLabels))
		{
			return $this->axisLabels;
		}

		$params = $this->getParams();
		$graph = $params->get('graph_type');
		$chartElements = (array) $params->get('chart_elementList');
		$x_axis_label = (array) $params->get('x_axis_label');
		$tableDatas = $this->getTableData();
		$calculationLabels = array();
		$c = 0;

		foreach ($tableDatas as $tableData)
		{
			$alldata = $tableData['data'];
			$cals = $tableData['cals'];
			$column = $chartElements[$c];
			$listModel = $tableData['model'];
			$pref = substr($column, 0, 6);
			$label = FArrayHelper::getValue($x_axis_label, $c, '');
			$tmpglabels = array();
			$calcfound = $this->getCalcFound();

			if ($calcfound)
			{
				$column = JString::substr($column, 6);
			}

			if ($calcfound)
			{
				$calckey = $this->calc_prefixmap[$pref];
				$caldata = $cals[$calckey][$column . '_obj'];

				if (is_array($caldata))
				{
					foreach ($caldata as $k => $o)
					{
						$calculationLabels[] = trim(strip_tags($o->label));
					}
				}

				$glabels[$c] = implode('|', $calculationLabels);
			}
			else
			{
				// _raw fields are most likely to contain the value
				$column = $column . "_raw";

				foreach ($alldata as $group)
				{
					foreach ($group as $row)
					{
						$tmpglabels[] = !empty($label) ? strip_tags($row->$label) : '';
					}

					$glabels[$c] = implode('|', $tmpglabels);
				}
			}

			$c++;
		}

		if ($calcfound)
		{
			if (!empty($calculationLabels))
			{
				$calculationLabels = array_reverse($calculationLabels);
				$glabels = array(implode('|', array_reverse($calculationLabels)));
			}
		}

		switch ($graph)
		{
			case 'p':
			case 'p3':
				$legends = $params->get('graph_show_legend', '');

				if ($total > 1)
				{
					$axisLabels = (array) $params->get('chart_axis_labels');
				}
				else
				{
					$axisLabels = explode('|', $glabels[0]);
				}
				break;
			default:
				if ($calcfound)
				{
					$axisLabels = explode('|', $glabels[0]);
				}
				else
				{
					$axisLabels = explode('|', $glabels[0]);
				}
		}

		$this->axisLabels = $axisLabels;

		return $axisLabels;
	}

	/**
	 * Test if calculations exist
	 *
	 * @return boolean
	 */
	private function getCalcFound()
	{
		if (!isset($this->calcfound))
		{
			$this->calcfound = false;
			$params = $this->getParams();
			$chartElements = (array) $params->get('chart_elementList');
			$listid = (array) $params->get('chart_table');
			$calc_prefixes = array('sum___', 'avg___', 'med___', 'cnt___');

			for ($c = 0; $c < count($listid); $c++)
			{
				$column = $chartElements[$c];
				$pref = substr($column, 0, 6);

				if (in_array($pref, $calc_prefixes))
				{
					$this->calcfound = true;
					break;
				}
			}
		}

		return $this->calcfound;
	}

	/**
	 * Make a pie chart
	 *
	 * @param   int    $c      Total data sets
	 * @param   array  $gdata  Data
	 * @param   array  $gsums  Calcs
	 *
	 * @return  array
	 */

	protected function pieChart($c, $gdata, $gsums)
	{
		$params = $this->getParams();
		$legends = $params->get('graph_show_legend', '');
		$fillGraphs = false;
		$axisLabels = $this->getAxisLabels($c);

		if ($c > 1)
		{
			/*
			 * multiple table/elements, so use the sums
			 * need to scale our data into percentages
			 */
			$tot_sum = array_sum($gsums);
			$percs = array();

			foreach ($gsums as $sum)
			{
				$percs[] = sprintf('%01.2f', ($sum / $tot_sum) * 100);
			}

			$chd = implode(',', $percs);

			if ($legends)
			{
				$chxl = '0:|' . implode('|', $gsums);
			}
			else
			{
				$chxl = '0:|' . implode('|', $axisLabels);
			}

			$chds = '';
		}
		else
		{
			// Single table/elements, so use the row data
			$gsums = explode(',', $gdata[0]);

			// Scale to percentages
			$tot_sum = array_sum($gsums);
			$percs = array();

			foreach ($gsums as $sum)
			{
				if ($tot_sum > 0)
				{
					$percs[] = sprintf('%01.2f', ($sum / $tot_sum) * 100);
				}
				else
				{
					$percs[] = 0.00;
				}
			}

			$chd = implode(',', $percs);

			if ($legends)
			{
				$chxl = '0:|' . implode('|', $gsums);
			}
			else
			{
				$chxl = '0:|' . implode('|', $axisLabels);
			}

			$chds = '';
		}

		$chds = '0,360';

		return array($chd, $chxl, $chds, $fillGraphs);
	}

	/**
	 * Set an array of list id's whose data is used inside the visualization
	 *
	 * @return  void
	 */

	protected function setListIds()
	{
		if (!isset($this->listids))
		{
			$params = $this->getParams();
			$this->listids = (array) $params->get('chart_table');
		}
	}
}
