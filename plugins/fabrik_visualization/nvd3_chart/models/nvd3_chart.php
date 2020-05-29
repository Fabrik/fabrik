<?php
/**
 * Fabrik nvd3_chart Chart Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.nvd3_chart
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/visualization.php';

/**
 * data_mode parameter defines 2 states for the data
 *
 * 0/LABELS_IN_VALUES
 *
 * If the table has 2 columns, one for labels and one for data (labels in data)
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

		$params   = $this->getParams();
		$script   = $params->get('script', '');
		$fullPath = JPATH_ROOT . '/plugins/fabrik_visualization/nvd3_chart/scripts/' . $script;

		if ($script != '' && JFile::exists($fullPath))
		{
			require $fullPath;
		}
		else
		{
			$chartType = $params->get('chart');

			if ($chartType === 'scatterChart')
			{
				$this->data = $this->scatterChartData();

				return $this->data;
			}

			// @TODO - make distinct render classes for each chart type. MUCH easier to manage.
			$chartFile = JPATH_SITE . '/plugins/fabrik_visualization/nvd3_chart/charts/' . strtolower($chartType) . '.php';

			if (JFile::exists($chartFile))
			{
				require_once $chartFile;
				$cls    = JStringNormalise::toCamelCase($chartType);
				$render = new $cls($params);

				return $render->render($params);
			}

			if ($chartType === 'multiBarHorizontalChart' || $chartType === 'multiBarChart')
			{
				$this->data = $this->multiChartData();

				return $this->data;
			}
			else
			{
				$this->data = $this->singleLineData();

				return $this->data;
			}

			$this->data      = new stdClass;
			$this->data->key = 'todo2';
			$db              = $this->_db;
			$query           = $db->getQuery(true);

			$tbl   = $params->get('tbl', '');
			$value = $params->get('value_field');
			$label = $params->get('label_field');
			$query->select($db->qn($label) . ' AS label, ' . $db->qn($value) . ' AS value')->from($tbl);
			$db->setQuery($query);
			$this->data->values = $db->loadObjectList();
			$this->data         = array($this->data);
		}

		return $this->data;
	}

	/**
	 * Helper function for custom scripts (may be that we incorporate this as the default handler when its
	 * more mature
	 *
	 * @param   int $elementId Element id to get data for
	 *
	 * @return  array  Chart data
	 */
	public function getElementData($elementId)
	{
		$pluginManager = FabrikWorker::getPluginManager();

		$elementModel = $pluginManager->getPluginFromId($elementId);
		$params       = $elementModel->getParams();
		$element      = $elementModel->getElement();

		// Get labels
		$sub_options = $params->get('sub_options');
		$sub_values  = $sub_options->sub_values;
		$sub_labels  = $sub_options->sub_labels;
		$labels      = array_combine($sub_values, $sub_labels);

		// Get the column data
		$listModel           = $elementModel->getListModel();
		$opts                = array();
		$opts['filterLimit'] = false;
		$rows                = $listModel->getColumnData($elementId, false, $opts);

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

	/**
	 * Set to nvd3 format, ready for json encoding
	 *
	 * @param   array $data Data
	 *
	 * @return multitype:
	 */

	public function elementDataToNvs3($data)
	{
		$this->data         = new stdClass;
		$this->data->key    = 'todo2';
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
	 * @param   array $rows   Element data
	 * @param   array $labels Element sub option labels keyed on sub option values
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
						$o          = new stdClass;
						$o->label   = $labels[$val];
						$o->value   = 1;
						$data[$val] = $o;
					}
					else
					{
						$data[$val]->value++;
					}
				}
			}
		}

		return $data;
	}

	protected function getLabelColums()
	{
		$params = $this->getParams();

		if ($params->get('data_mode') == 0)
		{
			$valueField = $params->get('value_field', '');

			if ($valueField === '')
			{
				throw new UnexpectedValueException('Chart has Data mode set to "Labels in data" but no element selected for the "Value field" option');
			}

			$labelColumns = $valueField;
		}
		else
		{
			$labelColumns = explode(',', $params->get('label_columns'));
		}

		return $labelColumns;
	}

	/**
	 * Single line
	 * Uses its own fieldset jform options
	 *
	 * @return multitype:stdClass
	 */
	protected function singleLineData()
	{
		$params = $this->getParams();
		$table  = $params->get('tbl');
		$split  = $params->get('single_line_split', '');

		$db    = FabrikWorker::getDbo(false, $params->get('conn_id'));
		$query = $db->getQuery(true);

		$xCol = $params->get('single_line_x_column', '');
		$yCol = $params->get('single_line_y_column', '');

		if ($xCol === '')
		{
			throw new UnexpectedValueException('Single line chart must specify an x column');
		}

		if ($yCol === '')
		{
			throw new UnexpectedValueException('Single line chart must specify an y column');
		}

		$query->select(array($db->qn($xCol) . ' AS x ', $db->qn($yCol) . ' AS y'))->from($table);

		if ($split !== '')
		{
			$query->select($split . ' AS ' . $db->qn('key'));
		}

		$db->setQuery($query);
		$rows   = $db->loadObjectList();
		$colors = explode(',', $params->get('colours', '#B9C872,#88B593,#388093,#994B89,#ED5FA2,#4D1018,#8F353E,#D35761,#43574E,#14303C'));
		$return = array();
		$i      = 0;

		if ($split !== '')
		{
			// Split the data out into one line per group of data
			$rows = ArrayHelper::pivot($rows, 'key');

			foreach ($rows as $key => $row)
			{
				// Single item after pivot is an object - wrap inside an array
				if (is_object($row))
				{
					$row = array($row);
				}

				foreach ($row as &$r)
				{
					$r->x = (float) $r->x;
					$r->y = (float) $r->y;
					unset($r->key);
				}

				$entry         = new stdClass;
				$entry->values = $row;
				$entry->key    = $key;
				$entry->color  = $colors[$i];
				$return[]      = $entry;
				$i++;
			}
		}
		else
		{
			// No key so create a single line
			$entry         = new stdClass;
			$entry->values = $rows;
			$entry->key    = $params->get('singleline_key');
			$entry->color  = $colors[$i];
			$return[]      = $entry;
		}

		return $return;
	}

	/**
	 * Scatter chart
	 *
	 * @return multitype:
	 */
	protected function scatterChartData()
	{
		$rows         = $this->mulitLines();
		$labelColumns = $this->getLabelColums();
		$data         = array();
		$o            = new stdClass;
		$o->values    = array();

		foreach ($rows as $d)
		{
			/*if (!array_key_exists($d->key, $data))
			{
				$data[$d->key] = new stdClass;
				$data[$d->key]->key = $d->key;
				$data[$d->key]->values = array();
			}*/

			$point                   = new stdClass;
			$point->x                = (float) $d->x;
			$point->y                = (float) $d->y;
			$point->size             = is_null($d->size) ? 0.5 : (float) $d->size;
			$data[$d->key]->values[] = $point;
		}

		$data = array_values($data);

		return $data;
	}

	/**
	 * Multi line
	 *
	 * @throws RuntimeException
	 *
	 * @return array
	 */
	protected function mulitLines()
	{
		$params       = $this->getParams();
		$labelColumns = $this->getLabelColums();
		$table        = $params->get('tbl');
		$split        = $params->get('split', '');

		$db    = FabrikWorker::getDbo(false, $params->get('conn_id'));
		$query = $db->getQuery(true);
		$query->select($db->qn($labelColumns))->from($table);

		if ($split !== '')
		{
			$query->select($db->qn($split) . ' AS ' . $db->qn('key'));
		}
		else
		{
			if ($params->get('data_mode') == 0)
			{
				$query->select($db->qn('date') . ' AS ' . $db->qn('key'));
			}
		}

		$db->setQuery($query);

		if (!$rows = $db->loadObjectList())
		{
			throw new RuntimeException('Fabrik: nv3d viz data load error: ' . $db->getErrorMsg());
		}

		return $rows;
	}

	/**
	 * Build data for the multi Chart types
	 * Current only works
	 *
	 * @return stdClass
	 */

	protected function multiChartData()
	{
		$params          = $this->getParams();
		$rows            = $this->mulitLines();
		$labelAxisValues = $params->get('label_axis_values', 'label_columns');
		$split           = $params->get('split', '');

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
	 * @param   array $rows Chart data
	 *
	 * @return stdClass
	 */
	protected function multiChartLabelsNoSplit($rows)
	{
		$o         = new stdClass;
		$o->values = array();

		foreach ($rows as $d)
		{
			foreach ($d as $k => $v)
			{
				if (!array_key_exists($k, $o->values))
				{
					$thisV         = new stdClass;
					$thisV->label  = $k;
					$thisV->value  = (float) $v;
					$o->values[$k] = $thisV;
				}
				else
				{
					$o->values[$k]->value += (float) $v;
				}
			}
		}

		$o->values = array_values($o->values);
		$data[]    = $o;

		return $data;
	}

	/**
	 * Organise data for showing in a multichart when the columns are used as the labels and a split
	 * value has been assigned.
	 *
	 * @param   array $rows Chart data
	 *
	 * @return stdClass
	 */
	protected function multiChartLabels($rows)
	{
		$data = array();

		foreach ($rows as $d)
		{
			$o      = new stdClass;
			$o->key = $d->key;
			$values = array();

			foreach ($d as $k => $v)
			{
				if ($k != 'key')
				{
					$thisV        = new stdClass;
					$thisV->label = $k;
					$thisV->value = (float) $v;
					$values[]     = $thisV;
				}
			}

			$o->values = $values;
			$data[]    = $o;
		}

		return $data;
	}

	/**
	 * Organise data for showing in a multichart when the split column is used for the axis labels
	 *
	 * @param   array $rows Chart data
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

		$firstRow     = $rows[0];
		$labelColumns = array_keys(get_object_vars($firstRow));

		foreach ($labelColumns as $chartKey)
		{
			if ($chartKey !== 'key')
			{
				$o         = new stdClass;
				$o->key    = $chartKey;
				$o->values = array();

				foreach ($rows as $d)
				{
					$thisV        = new stdClass;
					$thisV->label = $d->key;
					$thisV->value = (float) $d->$chartKey;
					$o->values[]  = $thisV;
				}

				$data[] = $o;
			}
		}

		return $data;
	}

	/**
	 * Helper function to build data set from radio/default element data (plain text)
	 *
	 * @param   array $rows   Element data
	 * @param   array $labels Element sub option labels keyed on sub option values
	 *
	 * @return  multitype:stdClass
	 */

	protected function radio($rows, $labels)
	{
		$suggestions = array();
		$data        = array();

		foreach ($rows as $val)
		{
			if (!is_null($val))
			{
				if (array_key_exists($val, $labels))
				{
					if (!array_key_exists($val, $data))
					{
						$o          = new stdClass;
						$o->label   = $labels[$val];
						$o->value   = 1;
						$data[$val] = $o;
					}
					else
					{
						$data[$val]->value++;
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
		$str    = array();
		$str[]  = $params->get('pie_labels', true) ? '.showLabels(true)' : '.showLabels(false)';
		$str[]  = $params->get('donut', false) ? '.donut(true)' : '.donut(false)';

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
		$str    = array();
		$str[]  = '.showValues(true)';

		// $str[] = '.staggerLabels(true);';
		// Rotate the labels otherwise they get merged together
		$rotate = (int) $params->get('rotate_labels', 0);

		if ($rotate !== 0)
		{
			$str[] = 'chart.xAxis.rotateLabels(-' . $rotate . ');';
		}

		return implode("\n", $str);
	}

	/**
	 * Add chart margins
	 *
	 * @return  string  chart.margin option
	 */

	protected function margins()
	{
		$str    = '';
		$params = $this->getParams();

		if ($params->get('margin', '') !== '')
		{
			$margins      = explode(',', $params->get('margin', ''));
			$marg         = new stdClass;
			$marg->top    = 10;
			$marg->bottom = 160;
			$marg->right  = 10;
			$marg->left   = 80;

			if (count($margins) == 2)
			{
				$marg->top  = $marg->bottom = $margins[0];
				$marg->left = $marg->right = $margins[1];
			}
			elseif (count($margins) == 4)
			{
				$marg->top    = $margins[0];
				$marg->right  = $margins[1];
				$marg->bottom = $margins[2];
				$marg->left   = $margins[3];
			}

			$str = 'chart.margin(' . json_encode($marg) . ');';
		}

		return $str;
	}

	/**
	 * Should the chart show controls for swapping between views
	 *
	 * @param   array &$str JS output
	 *
	 * @return void
	 */

	protected function showControls(&$str)
	{
		$allowed  = array('stackedAreaChart', 'multiBarChart', 'lineWithFocusChart', 'multiBarHorizontalChart');
		$params   = $this->getParams();
		$chart    = $params->get('chart', 'pieChart');
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
		$id      = $this->getContainerId();
		$rawData = $this->getData();
		$data    = json_encode($rawData);
		$params  = $this->getParams();
		$chart   = $params->get('chart', 'pieChart');
		$str[]   = 'window.addEvent("domready", function () {';
		$str[]   = 'nv.addGraph(function() {';
		$str[]   = 'var chart = nv.models.' . $chart . '()';

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
			case 'scatterChart':
				$str[] = '.showDistX(true)';
				$str[] = '.showDistY(true)';
				$str[] = ".tooltipContent(function(key, x, y, obj) {
					// console.log(arguments);
					return '<h3><a href=\"http://fabrikar.com\">test</a></h3>';
		});";
				break;
			case 'pieChart':
				$str[] = '.x(function(d) { return d.label })';
				$str[] = '.y(function(d) { return d.value })';
				$str[] = $this->pieOpts();
				break;
			case 'discreteBarChart':

				$str[] = '.x(function(d) { return d.label })';
				$str[] = '.y(function(d) { return d.value })';
				break;

			// Test: was the same as stackedAreaChart
			case 'multiBarChart':
				$str[] = '.x(function(d) { return d.label })';
				$str[] = '.y(function(d) { return d.value })';

				// $str[] = $this->margins();
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

		$str[] = $this->margins();

		switch ($chart)
		{
			case 'lineChart':
				$str[] = 'chart.showYAxis(true);';
				$str[] = 'chart.showXAxis(true)  ;';
				$str[] = 'chart.xAxis.axisLabel(\'' . $params->get('single_line_x_axis_label') . '\');';
				$str[] = 'chart.yAxis.axisLabel(\'' . $params->get('single_line_y_axis_label') . '\');';
				break;
			case 'discreteBarChart':
				$str[] = 'chart.yAxis.tickFormat(d3.format("d"));';
				$str[] = 'chart.tooltips(true).showValues(true)';
				break;
		}

		$this->showControls($str);
		$id    = $this->getContainerId();
		$str[] = 'd3.select("#' . $id . ' svg")';

		$str[] = '.datum(' . $data . ')';

		$str[] = '.transition().duration(1200)';
		$str[] = '.call(chart);';

		$str[] = 'd3.selectAll("circle.nv-point").on("mouseover", function(d, i) {
			console.log(d.data);
			console.log(arguments);
	});';

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

		return implode("\n", $str);
	}
}
