<?php
/**
 * Fabrik nvd3_chart Chart Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.nvd3_chart
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/visualization.php';

/**
 * data_mode parameter defines 2 states for the data
 *
 * 0/LABELS_IN_VALUES
 *
 * If the table has 2 columns, one for labels and one for data
 * +--------+--------+-----------+
 * | state  | Number | Age range |
 * +========+========+===========+
 * |   CA   |  33    |  0-5      |
 * +--------+--------+-----------+
 * |   TX   |  12    |  5-10     |
 * +--------+--------+-----------+
 */
define('LABELS_IN_VALUES', 0);

/**
 * 1/LABELS_IN_COLUMNS
 *
 * If the table has lines of data with n field names which contain the labels
 *
 * +--------+------+------+-------+
 * | state  | 0_5  | 5_10 | 10_15 |
 * +========+======+======+=======+
 * |   CA   |  33  |  32  |  21   |
 * +--------+------+------+-------+
 * |   TX   |  12  | 6    |   43  |
 * +--------+------+------+-------+
 * @var unknown
 */
define('LABELS_IN_COLUMNS', 1);

/**
 * Fabrik nvd3_chart Chart Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.nvd3_chart
 * @since       3.0.7
 */

class FabrikModelNvd3_Chart extends FabrikFEModelVisualization
{

	/**
	 * Chart data
	 *
	 * @var array
	 */
	protected $data = null;

	/**
	 * Chart script can set post text - shown after viz
	 *
	 * @var string
	 */
	public $postText = '';

	/**
	 * Get sales per month per subscription plan
	 *
	 * @return  array
	 */

	public function getData()
	{
		if (isset($this->data))
		{
			return $this->data;
		}
		$params = $this->getParams();
		$script = $params->get('script', '');
		$fullPath = JPATH_ROOT . '/plugins/fabrik_visualization/nvd3_chart/scripts/' . $script;
		if ($script != '' && JFile::exists($fullPath))
		{
			require $fullPath;
		}
		else
		{
			$chartType = $params->get('chart');
			if ($chartType === 'multiBarHorizontalChart' || $chartType === 'multiBarChart')
			{
				$this->data = $this->multiChartData();
				return $this->data;
			}
			$this->data = new stdClass;
			$this->data->key = 'todo2';
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);

			$tbl = $params->get('tbl', '');
			$value = $params->get('value_field');
			$label = $params->get('label_field');
			$query->select($label . ' AS label, ' . $value . ' AS value')->from($tbl);
			$db->setQuery($query);
			$this->data->values = $db->loadObjectList();
			$this->data = array($this->data);
		}
		return $this->data;
	}

	/**
	 * Helper function for custom scripts (may be that we incorporate this as the default handler when its
	 * more mature
	 *
	 * @param   int  $elementId  Element id to get data for
	 *
	 * @return  array  Chart data
	 */
	function getElementData($elementId)
	{
		$pluginManager = FabrikWorker::getPluginManager();

		$elementModel = $pluginManager->getPluginFromId($elementId);
		$params = $elementModel->getParams();
		$element = $elementModel->getElement();

		// Get labels
		$sub_options = $params->get('sub_options');
		$sub_values = $sub_options->sub_values;
		$sub_labels = $sub_options->sub_labels;
		$labels = array_combine($sub_values, $sub_labels);

		// Get the column data
		$listModel = $elementModel->getListModel();
		$opts = array();
		$opts['filterLimit'] = false;
		$rows = $listModel->getColumnData($elementId, false, $opts);

		$total = count($rows);

		switch ($element->plugin)
		{
			case 'checkbox':
				$data = $this->checkbox($rows, $labels);
				break;
			default:
				$data = $this->radio($rows, $labels);
				break;
		}
		return $this->elementDataToNvs3($data);
	}

	public function elementDataToNvs3($data)
	{
		// Set to nvd3 format, ready for json encoding
		$this->data = new stdClass;
		$this->data->key = 'todo2';
		$this->data->values = array();

		foreach ($data as $d)
		{
			$this->data->values[] = $d;
		}
		$this->data = array($this->data);
		return $this->data;
	}

	/**
	 * Helper function to build data set from checkbox element data (json encoded)
	 *
	 * @param   array  $rows    Element data
	 * @param   array  $labels  Element sub option labels keyed on sub option values
	 *
	 * @return  multitype:stdClass
	 */

	protected function checkbox($rows, $labels)
	{
		$data = array();
		foreach ($rows as $row)
		{
			$vals = json_decode($row);
			foreach ($vals as $val)
			{
				if (!is_null($val))
				{
					if (!array_key_exists($val, $data))
					{
						$o = new stdClass;
						$o->label = $labels[$val];
						$o->value = 1;
						$data[$val] = $o;
					}
					else
					{
						$data[$val]->value ++;
					}
				}
			}
		}
		return $data;
	}

	/**
	 * Build data for the mutli Chart types
	 * Current only works
	 *
	 * @return stdClass
	 */

	protected function multiChartData()
	{
		$params = $this->getParams();
		//echo "<pre>";print_r($params);echo "</pre>";;
		if ($params->get('data_mode') == 0)
		{
			$labelColumns = $params->get('value_field');
		}
		else
		{
			$labelColumns = explode(',', $params->get('label_columns'));
		}
		$table = $params->get('tbl');
		$split = $params->get('split', '');
		$groupBy = $params->get('group_by');

		$db = FabrikWorker::getDbo(false, $params->get('conn_id'));
		$query = $db->getQuery(true);
		$query->select($labelColumns)->from($table);

		//test
		$split = 'date';
		if ($split !== '')
		{
			$query->select($split . ' AS ' . $db->nameQuote('key'));
		}
		else
		{
			if ($params->get('data_mode') == 0)
			{
				//$query->select($params->get('label_field') . ' AS ' . $db->nameQuote('key'));
				$query->select('date AS ' . $db->nameQuote('key'));
			}
		}
		$db->setQuery($query);
		if (!$rows = $db->loadObjectList())
		{
			throw new RuntimeException('Fabrik: nv3d viz data load error: ' . $db->getErrorMsg());
		}
		$labelAxisValues = $params->get('label_axis_values', 'label_columns');
		if ($labelAxisValues === 'label_columns')
		{
			if ($split === '')
			{
				return $this->multiChartLabelsNoSplit($rows);
			}
			else
			{
				return $this->multiChartLabels($rows);
			}
		}
		else
		{
			return $this->multiChartSplit($rows);
		}
	}

	/**
	 * Organise data for showing in a multichart when the columns are used as the labels and no split
	 * value has been assigned. Will basically group all the data into one record (as no split supplied)
	 *
	 * @param   array  $rows  Chart data
	 *
	 * @return stdClass
	 */
	protected function  multiChartLabelsNoSplit($rows)
	{
		$o = new stdClass;
		$o->values = array();
		echo "<pre>";print_r($rows);echo "</pre>";
		foreach ($rows as $d)
		{
			foreach ($d as $k => $v)
			{
				if (!array_key_exists($k, $o->values))
				{
					$thisV = new stdClass;
					$thisV->label = $k;
					$thisV->value = (float) $v;
					$o->values[$k] = $thisV;
				}
				else
				{
					$o->values[$k]->value += (float) $v;
				}

			}
		}
		$o->values = array_values($o->values);
		$data[] = $o;
		return $data;
	}
	/**
	 * Organise data for showing in a multichart when the columns are used as the labels and a split
	 * value has been assigned.
	 *
	 * @param   array  $rows  Chart data
	 *
	 * @return stdClass
	 */
	protected function multiChartLabels($rows)
	{
		$data = array();
		foreach ($rows as $d)
		{
			$o = new stdClass;
			$o->key = $d->key;
			$values = array();
			foreach ($d as $k => $v)
			{
				if ($k != 'key')
				{
					$thisV = new stdClass;
					$thisV->label = $k;
					$thisV->value = (float) $v;
					$values[] = $thisV;
				}
			}
			$o->values = $values;
			$data[] = $o;
		}
		return $data;
	}

	/**
	 * Organise data for showing in a multichart when the split column is used for the axis labels
	 *
	 * @param   array  $rows  Chart data
	 *
	 * @return stdClass
	 */
	protected function multiChartSplit($rows)
	{
		$data = array();
		if (empty($rows))
		{
			return $data;
		}
		$firstRow = $rows[0];
		$labelColumns = array_keys(get_object_vars($firstRow));
		foreach ($labelColumns as $chartKey)
		{
			if ($chartKey !== 'key')
			{
			$o = new stdClass;
			$o->key = $chartKey;
			$o->values = array();
			foreach ($rows as $d)
			{
				$thisV = new stdClass;
				$thisV->label = $d->key;
				$thisV->value = (float) $d->$chartKey;
				$o->values[] = $thisV;
			}
			$data[] = $o;
			}
		}
		return $data;
	}

	/**
	 * Helper function to build data set from radio/default element data (plain text)
	 *
	 * @param   array  $rows    Element data
	 * @param   array  $labels  Element sub option labels keyed on sub option values
	 *
	 * @return  multitype:stdClass
	 */

	protected function radio($rows, $labels)
	{
		$suggestions = array();
		$data = array();
		foreach ($rows as $val)
		{
			if (!is_null($val))
			{
				if (array_key_exists($val, $labels))
				{
					if (!array_key_exists($val, $data))
					{
						$o = new stdClass;
						$o->label = $labels[$val];
						$o->value = 1;
						$data[$val] = $o;
					}
					else
					{
						$data[$val]->value ++;
					}
				}
				else
				{
					if (trim($val) !== '')
					{
						$suggestions[] = str_replace('u00e9', 'é', $val);
					}
				}
			}
		}
		$this->suggestions = $suggestions;
		return $data;
	}

	/**
	 * Build a Pie / Donut Chart Specific Options
	 *
	 * @return string js
	 */

	protected function pieOpts()
	{
		$params = $this->getParams();
		$str = array();
		$str[] = $params->get('pie_labels', true) ? '.showLabels(true)' : '.showLabels(false)';
		$str[] = $params->get('donut', false) ?  '.donut(true)' : '.donut(false)';
		return implode("\n", $str);
	}

	/**
	 * Standard bar chart options
	 *
	 * @return string js
	 */

	protected function discreteBarChartOpts()
	{
		$params = $this->getParams();
		$str = array();
		$str[] = '.showValues(true)';

		// $str[] = '.staggerLabels(true);';
		// Rotate the labels otherwise they get merged together
		$rotate = (int) $params->get('rotate_labels', 0);
		if ($rotate !== 0)
		{
			$str[] = 'chart.xAxis.rotateLabels(-' . $rotate . ');';
		}
		// Additonal margin needed for rotated labels
		if ($params->get('margin', '') !== '')
		{
			$str[] = 'chart.margin({bottom: 160, left: 60});';
		}
		return implode("\n", $str);
	}

	/**
	 * Should the chart show controls for swapping between views
	 *
	 * @param   array  &$str  JS output
	 *
	 * @return void
	 */

	protected function showControls(&$str)
	{
		$allowed = array('stackedAreaChart', 'multiBarChart', 'lineWithFocusChart', 'multiBarHorizontalChart');
		$params = $this->getParams();
		$chart = $params->get('chart', 'pieChart');
		$controls = $params->get('controls', 0);
		if ($controls == 0 && in_array($chart, $allowed))
		{
			$str[] = 'chart.showControls(false);';
		}
	}

	/**
	 * Get chart js code
	 *
	 * @return string
	 */

	public function js()
	{
		$id = $this->getContainerId();
		$rawData = $this->getData();
		$data = json_encode($rawData);
		$params = $this->getParams();
		$chart = $params->get('chart', 'pieChart');
		$str[] = 'window.addEvent("domready", function () {';

		$str[] = 'nv.addGraph(function() {';
		$str[] = 'var chart = nv.models.' . $chart . '()';

		if (!empty($rawData) && !isset($rawData[0]->color))
		{
			// $colors = explode(',', $params->get('colours', 'red,green,blue,orange'));
			$colors = explode(',', $params->get('colours', '#B9C872,#88B593,#388093,#994B89,#ED5FA2,#4D1018,#8F353E,#D35761,#43574E,#14303C'));
			if (!empty($colors))
			{
				$str[] = '.color(' . json_encode($colors) . ')';
			}
		}

		switch ($chart)
		{
			case 'pieChart':
				$str[] = '.x(function(d) { return d.label })';
				$str[] = '.y(function(d) { return d.value })';
				$str[] = $this->pieOpts();
				break;
			case 'discreteBarChart':

				$str[] = '.x(function(d) { return d.label })';
				$str[] = '.y(function(d) { return d.value })';
				$str[] = $this->discreteBarChartOpts();
				break;

				// Test: was the same as stackedAreaChart (was the same as stackedAreaChart)
			case 'multiBarChart':
				$str[] = '.x(function(d) { return d.label })';
				$str[] = '.y(function(d) { return d.value })';
				break;
			case 'stackedAreaChart':
			case 'lineWithFocusChart':
				$str[] = '.x(function(d) { return d[0] })';


				$str[] = '.y(function(d) { return d[1] })';
				$str[] = '.clipEdge(true)';
				break;
			case 'multiBarHorizontalChart':
				$str[] = '.x(function(d) { return d.label })';
				$str[] = '.y(function(d) { return d.value })';
				break;

		}

		$margin = $params->get('margin', '');
		if ($margin !== '')
		{
			$margin = explode(',', $margin);
			if (count($margin) == 4)
			{
				$str[] = 'chart.margin({top: ' . (int) $margin[0] . ',right: ' . (int) $margin[1] . ', bottom: ' . (int) $margin[2] . ', left: ' . (int) $margin[3] . '});';
			}
		}

		$this->showControls($str);

		$id = $this->getContainerId();
		$str[] = 'd3.select("#' . $id . ' svg")';

		$str[] = '.datum(' . $data . ')';

		$str[] = '.transition().duration(1200)';
		$str[] = '.call(chart);';

		$rotate = (int) $params->get('rotate_labels', 45);
		if ($rotate !== 0)
		{
			// Rotate x axis labels without stoopid offset
			$str[] = "d3.select('#" . $id . " .nv-x.nv-axis > g').selectAll('g').selectAll('text').attr('transform', function(d,i,j) {";
			$str[] = "return 'translate (-10, 20) rotate(-" . $rotate . " 0,0)'";
			$str[] = "}) ;";

		}
		$str[] = 'return chart;';
		$str[] = '});';
		$str[] = '});';
		// $str[] = 'console.log(' . $data . ');';
		return implode("\n", $str);
		/**

		 */
		$ref = $this->getJSRenderContext();

		$chart = json_encode($chart);

		$script = "
				console.log('this');
		 nv.addGraph(function() {
		 var chart = nv.models.multiBarChart()
                .x(function(d) { return d[0] })
                .y(function(d) {
                return d[1] })
                .color(d3.scale.category10().range());

	chart.xAxis.tickFormat(function(d) {
		return d3.time.format('%x')(new Date(d))
	});

	chart.yAxis
	.axisLabel('Sales (€)')
	.tickFormat(function(d) {
		return d;
	});


  d3.select('$ref svg')
      .datum(" . ($chart) . ")
    .transition().duration(500)
      .call(chart);


  //TODO: Figure out a good way to do this automatically
  nv.utils.windowResize(chart.update);

  return chart;
  });
  ";
return $script;
	}

}
