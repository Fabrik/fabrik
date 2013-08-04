<?php
/**
 * Fabrik Gannt Chart Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.fusionganntchart
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/visualization.php';

/**
 * Fabrik Gannt Chart Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.fusionganntchart
 * @since       3.0
 */

class FabrikModelFusion_gantt_chart extends FabrikFEModelVisualization
{

	/**
	 * Create the gannt chart
	 *
	 * @return string
	 */

	public function getChart()
	{
		$app = JFactory::getApplication();

		// Include PHP Class
		if (!class_exists('FusionCharts'))
		{
			require_once JPATH_SITE . '/plugins/fabrik_visualization/fusion_gantt_chart/lib/FCclass/FusionCharts_Gen.php';
		}
		// Add JS to page header
		$document = JFactory::getDocument();
		$document->addScript($this->srcBase . "fusion_gantt_chart/lib/FCCharts/FusionCharts.js");

		$params = $this->getParams();
		$w = $params->get('fusion_gantt_chart_width');
		$h = $params->get('fusion_gantt_chart_height');

		// Create new chart
		$this->fc = new FusionCharts("GANTT", "$w", "$h");

		// Define path to FC's SWF
		$this->fc->setSWFPath(COM_FABRIK_LIVESITE . $this->srcBase . 'fusion_gantt_chart/lib/FCCharts/');

		$this->fc->setChartParam('dateFormat', 'yyyy-mm-dd');
		$this->fc->setChartParam('showTaskNames', 1);

		$chartP = 'ganttWidthPercent=70;gridBorderAlpha=100;canvasBorderColor=333333;canvasBorderThickness=0;hoverCapBgColor=FFFFFF;'
			. 'hoverCapBorderColor=333333;extendcategoryBg=0;ganttLineColor=99cc00;ganttLineAlpha=20;baseFontColor=333333;gridBorderColor=99cc00';
		$this->fc->setChartParams($chartP);

		// Setting Param string
		$listid = $params->get('fusion_gantt_chart_table');
		$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
		$listModel->setId($listid);
		$formModel = $listModel->getFormModel();
		$db = $listModel->getDB();
		$table = $listModel->getTable()->db_table_name;
		$process = (string) $params->get('fusion_gantt_chart_process');
		$processraw = $process . '_raw';
		$start = $params->get('fusion_gantt_chart_startdate');
		$startraw = $start . '_raw';
		$end = $params->get('fusion_gantt_chart_enddate');
		$endraw = $end . '_raw';
		$label = $params->get('fusion_gantt_chart_label');
		$hover = $params->get('fusion_gantt_chart_hover');
		$milestone = $params->get('fusion_gantt_chart_milestone');
		$milestoneraw = $milestone . '_raw';
		$connector = $params->get('fusion_gantt_chart_connector');
		$connectorraw = $connector . '_raw';
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
		$nav = $listModel->getPagination(0, 0, 0);
		$data = $listModel->getData();
		if (empty($data))
		{
			$app->enqueueMessage('No data found for gantt chart');
			return;
		}
		$groupByKeys = array_keys($data);

		$mindate = null;
		$maxdate = null;
		$usedProcesses = array();
		$milestones = array();
		$connectors = array();
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
				$hovertext = $hover == '' ? '' : $this->prepData($d->$hover);
				$processid = $process == '' ? $c : $this->prepData($d->$processraw);
				if ($d->$startraw == $db->getNullDate())
				{
					continue;
				}
				$startdate = JFactory::getDate($d->$startraw);
				$enddate = isset($d->$endraw) ? JFactory::getDate($d->$endraw) : $startdate;

				$strParam = "start=" . $startdate->format('Y/m/d') . ";end=" . $enddate->format('Y/m/d') . ";";
				$strParam .= "processId={$processid};";
				$strParam .= "id={$d->__pk_val};color=99cc00;alpha=60;topPadding=19;hoverText={$hovertext};";
				$strParam .= "link={$d->fabrik_view_url};";

				$l =  isset($d->$label) ? $this->prepData($d->$label) : '';
				$this->fc->addGanttTask($l, $strParam);

				if ($milestone !== '' && $d->$milestoneraw == 1)
				{
					$thisEndD = $enddate->toFormat('%Y/%m/%d');
					$mileStone = "date=" . $thisEndD . ";radius=10;color=333333;shape=Star;numSides=5;borderThickness=1";
					$this->fc->addGanttMilestone($d->__pk_val, $mileStone);
				}

				if ($connector !== '' && $d->$connectorraw !== '')
				{
					$this->fc->addGanttConnector($d->$connectorraw, $d->__pk_val, "color=99cc00;thickness=2;");
				}
				// Apply processes
				if (!in_array($processid, $usedProcesses))
				{
					$usedProcesses[] = $processid;
					$processLabel = $process == '' ? '' : $this->prepData($d->$process);
					$this->fc->addGanttProcess($processLabel, "id={$processid};");
				}
				// Increaes max/min date range
				if (is_null($mindate))
				{
					$mindate = $startdate;
					$maxdate = $enddate;
				}
				else
				{
					if (JFactory::getDate($d->$startraw)->toUnix() < $mindate->toUnix())
					{
						$mindate = JFactory::getDate($d->$startraw);
					}

					if ($enddate->toUnix() > $maxdate->toUnix())
					{
						$maxdate = $enddate;
					}

				}
				$c++;
			}
		}
		$startyear = $mindate ? $mindate->format('Y') : date('Y');
		$endyear = $maxdate ? $maxdate->format('Y') : 0;

		$monthdisplay = $params->get('fusion_gannt_chart_monthdisplay');
		$this->fc->addGanttCategorySet("bgColor=333333;fontColor=99cc00;isBold=1;fontSize=14");
		for ($y = $startyear; $y <= $endyear; $y++)
		{
			$firstmonth = ($y == $startyear) ? (int) $mindate->format('m') : 1;
			$lastmonth = ($y == $endyear) ? $maxdate->format('m') + 1 : 13;

			$start = date('Y/m/d', mktime(0, 0, 0, $firstmonth, 1, $y));
			$end = date('Y/m/d', mktime(0, 0, 0, $lastmonth, 0, $y));

			$strParam = "start=" . $start . ";end=" . $end . ";";
			$this->fc->addGanttCategory($y, $strParam);
		}
		$this->fc->addGanttCategorySet("bgColor=99cc00;fontColor=333333;isBold=1;fontSize=10;bgAlpha=40;align=center");
		for ($y = $startyear; $y <= $endyear; $y++)
		{
			$lastmonth = ($y == $endyear) ? $maxdate->format('m') + 1 : 13;
			$firstmonth = ($y == $startyear) ? (int) $mindate->format('m') : 1;
			for ($m = $firstmonth; $m < $lastmonth; $m++)
			{
				$starttime = mktime(0, 0, 0, $m, 1, $y);
				$start = date('Y/m/d', $starttime);

				// Use day = 0 to load last day of next month
				$end = date('Y/m/d', mktime(0, 0, 0, $m + 1, 0, $y));
				$m2 = $monthdisplay == 'str' ? JText::_(date('M', $starttime)) : $m;
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
