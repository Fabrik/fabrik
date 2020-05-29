<?php
/**
 * Fabrik Gantt Chart Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.fusionganttchart
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/visualization.php';

/**
 * Fabrik Gantt Chart Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.fusionganttchart
 * @since       3.0
 */
class FabrikModelFusion_Gantt_Chart extends FabrikFEModelVisualization
{
	/**
	 * Create the Gantt chart
	 *
	 * @return string
	 */
	public function getChart()
	{
		// Include PHP Class
		if (!class_exists('FusionCharts'))
		{
			require_once JPATH_SITE . '/plugins/fabrik_visualization/fusion_gantt_chart/libs/FCclass/FusionCharts_Gen.php';
		}

		// Add JS to page header
		$document = JFactory::getDocument();
		$document->addScript($this->srcBase . "fusion_gantt_chart/libs/FCCharts/FusionCharts.js");

		$params = $this->getParams();
		$w = $params->get('fusion_gantt_chart_width');
		$h = $params->get('fusion_gantt_chart_height');

		// Create new chart
		$this->fc = new FusionCharts("GANTT", "$w", "$h");

		// Define path to FC's SWF
		$this->fc->setSWFPath(COM_FABRIK_LIVESITE . $this->srcBase . 'fusion_gantt_chart/libs/FCCharts/');

		$this->fc->setChartParam('dateFormat', 'yyyy-mm-dd');
		$this->fc->setChartParam('showTaskNames', 1);

		$chartP = 'ganttWidthPercent=70;gridBorderAlpha=100;canvasBorderColor=333333;canvasBorderThickness=0;hoverCapBgColor=FFFFFF;'
			. 'hoverCapBorderColor=333333;extendcategoryBg=0;ganttLineColor=99cc00;ganttLineAlpha=20;baseFontColor=333333;gridBorderColor=99cc00';
		$this->fc->setChartParams($chartP);

		// Setting Param string
		$listId = $params->get('fusion_gantt_chart_table');
		$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
		$listModel->setId($listId);
		$formModel = $listModel->getFormModel();
		$db = $listModel->getDB();
		$process = (string) $params->get('fusion_gantt_chart_process');
		$process = FabrikString::safeColNameToArrayKey($process);
		$processRaw = $process . '_raw';
		$start = $params->get('fusion_gantt_chart_startdate');
		$start = FabrikString::safeColNameToArrayKey($start);
		$startRaw = $start . '_raw';
		$end = $params->get('fusion_gantt_chart_enddate');
		$end = FabrikString::safeColNameToArrayKey($end);
		$endRaw = $end . '_raw';
		$label = $params->get('fusion_gantt_chart_label');
		$label = FabrikString::safeColNameToArrayKey($label);
		$hover = $params->get('fusion_gantt_chart_hover');
		$hover = FabrikString::safeColNameToArrayKey($hover);
		$milestone = $params->get('fusion_gantt_chart_milestone');
		$milestone = FabrikString::safeColNameToArrayKey($milestone);
		$milestoneRaw = $milestone . '_raw';
		$connector = $params->get('fusion_gantt_chart_connector');
		$connector = FabrikString::safeColNameToArrayKey($connector);
		$connectorRaw = $connector . '_raw';
		$fields = array();
		$names = array();
		$uses = array($process, $start, $end, $label, $hover, $milestone, $connector);

		foreach ($uses as $use)
		{
			if ($use != '')
			{
				$formModel->getElement($use)->getAsField_html($fields, $names);
			}
		}

		$listModel->asfields = $fields;
		$listModel->getPagination(0, 0, 0);
		$data = $listModel->getData();

		if (empty($data))
		{
			$this->app->enqueueMessage('No data found for gantt chart');

			return;
		}

		$groupByKeys = array_keys($data);
		$minDate = null;
		$maxDate = null;
		$usedProcesses = array();
		$processLabel = $params->get('fusion_gantt_chart_process_label');
		$p = "positionInGrid=right;align=center;headerText=$processLabel;fontColor=333333;fontSize=11;"
			. "isBold=1;isAnimated=1;bgColor=99cc00;headerbgColor=333333;headerFontColor=99cc00;headerFontSize=16;bgAlpha=40";
		$this->fc->setGanttProcessesParams($p);
		$c = 0;

		foreach ($groupByKeys as $groupByKey)
		{
			$groupedData = $data[$groupByKey];

			foreach ($groupedData as $d)
			{
				$hoverText = $hover == '' ? '' : $this->prepData($d->$hover);
				$processId = $process == '' ? $c : $this->prepData($d->$processRaw);

				if ($d->$startRaw == $db->getNullDate())
				{
					continue;
				}

				$startDate = JFactory::getDate($d->$startRaw);
				$endDate = isset($d->$endRaw) ? JFactory::getDate($d->$endRaw) : $startDate;

				$strParam = "start=" . $startDate->format('Y/m/d') . ";end=" . $endDate->format('Y/m/d') . ";";
				$strParam .= "processId={$processId};";
				$strParam .= "id={$d->__pk_val};color=99cc00;alpha=60;topPadding=19;hoverText={$hoverText};";
				$strParam .= "link={$d->fabrik_view_url};";

				$l = isset($d->$label) ? $this->prepData($d->$label) : '';
				$this->fc->addGanttTask($l, $strParam);

				if ($milestone !== '' && $d->$milestoneRaw == 1)
				{
					$thisEndD = $endDate->format('Y/m/d');
					$mileStone = "date=" . $thisEndD . ";radius=10;color=333333;shape=Star;numSides=5;borderThickness=1";
					$this->fc->addGanttMilestone($d->__pk_val, $mileStone);
				}

				if ($connector !== '' && $d->$connectorRaw !== '')
				{
					$this->fc->addGanttConnector($d->$connectorRaw, $d->__pk_val, "color=99cc00;thickness=2;");
				}

				// Apply processes
				if (!in_array($processId, $usedProcesses))
				{
					$usedProcesses[] = $processId;
					$processLabel = $process == '' ? '' : $this->prepData($d->$process);
					$this->fc->addGanttProcess($processLabel, "id={$processId};");
				}

				// Increases max/min date range
				if (is_null($minDate))
				{
					$minDate = $startDate;
					$maxDate = $endDate;
				}
				else
				{
					if (JFactory::getDate($d->$startRaw)->toUnix() < $minDate->toUnix())
					{
						$minDate = JFactory::getDate($d->$startRaw);
					}

					if ($endDate->toUnix() > $maxDate->toUnix())
					{
						$maxDate = $endDate;
					}
				}

				$c++;
			}
		}

		$startYear = $minDate ? $minDate->format('Y') : date('Y');
		$endYear = $maxDate ? $maxDate->format('Y') : 0;

		$monthDisplay = $params->get('fusion_gannt_chart_monthdisplay');
		$this->fc->addGanttCategorySet("bgColor=333333;fontColor=99cc00;isBold=1;fontSize=14");

		for ($y = $startYear; $y <= $endYear; $y++)
		{
			$firstMonth = ($y == $startYear) ? (int) $minDate->format('m') : 1;
			$lastMonth = ($y == $endYear) ? $maxDate->format('m') + 1 : 13;

			$start = date('Y/m/d', mktime(0, 0, 0, $firstMonth, 1, $y));
			$end = date('Y/m/d', mktime(0, 0, 0, $lastMonth, 0, $y));

			$strParam = "start=" . $start . ";end=" . $end . ";";
			$this->fc->addGanttCategory($y, $strParam);
		}

		$this->fc->addGanttCategorySet("bgColor=99cc00;fontColor=333333;isBold=1;fontSize=10;bgAlpha=40;align=center");

		for ($y = $startYear; $y <= $endYear; $y++)
		{
			$lastMonth = ($y == $endYear) ? $maxDate->format('m') + 1 : 13;
			$firstMonth = ($y == $startYear) ? (int) $minDate->format('m') : 1;

			for ($m = $firstMonth; $m < $lastMonth; $m++)
			{
				$startTime = mktime(0, 0, 0, $m, 1, $y);
				$start = date('Y/m/d', $startTime);

				// Use day = 0 to load last day of next month
				$end = date('Y/m/d', mktime(0, 0, 0, $m + 1, 0, $y));
				$m2 = $monthDisplay == 'str' ? FText::_(date('M', $startTime)) : $m;
				$this->fc->addGanttCategory($m2, "start=" . $start . ";end=" . $end . ";");
			}
		}

		// Render Chart
		return $this->fc->renderChart(false, false);
	}

	/**
	 * Prepare the data for sending to the chart
	 *
	 * @param   string  $d  data
	 *
	 * @return  string
	 */
	protected function prepData($d)
	{
		$d = str_replace(array("\r\n", "\r", "\n", "\t"), '', $d);
		$d = $this->fc->encodeSpecialChars($d);

		return $d;
	}

	/**
	 * Set the list ids that we use to render the viz
	 *
	 * @return  void
	 */
	protected function setListIds()
	{
		if (!isset($this->listids))
		{
			$params = $this->getParams();
			$this->listids = (array) $params->get('fusion_gantt_chart_table');
		}
	}
}
