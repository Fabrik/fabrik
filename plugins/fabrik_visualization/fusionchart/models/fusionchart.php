<?php
/**
 * Fabrik Fusion Chart Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.fusionchart
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use FusionExport\ExportManager;
use FusionExport\ExportConfig;
use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/visualization.php';
require_once JPATH_ROOT . '/plugins/fabrik_visualization/fusionchart/vendor/autoload.php';

/**
 * Fabrik Fusion Chart Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.fusionchart
 * @since       3.0
 */
class FabrikModelFusionchart extends FabrikFEModelVisualization
{
	private $chartData = array();

	/**
	 * Get the chart parameters
	 *
	 * @return  string
	 */
	protected function getChartParams()
	{
		$params = $this->getParams();
		$w = new FabrikWorker;
		$caption = $w->parseMessageForPlaceHolder($params->get('fusionchart_caption', ''));
		$strParam = 'caption=' . $caption;

		// Graph attributes

		if ($params->get('fusionchart_theme'))
		{
			$strParam .= ';theme=' . $params->get('fusionchart_theme', '');
		}
		else
		{
			$strParam .= ';palette=' . $params->get('fusionchart_chart_palette', 1);

			if ($params->get('fusionchart_palette_colors'))
			{
				$strParam .= ';paletteColors=' . $params->get('fusionchart_palette_colors', '');
			}
		}

		if ($params->get('fusionchart_bgcolor'))
		{
			$strParam .= ';bgcolor=' . $params->get('fusionchart_bgcolor', '');
		}

		if ($params->get('fusionchart_bgalpha'))
		{
			$strParam .= ';bgalpha=' . $params->get('fusionchart_bgalpha', '');
		}

		if ($params->get('fusionchart_bgimg'))
		{
			$strParam .= ';bgSWF=' . $params->get('fusionchart_bgimg', '');
		}
		// Canvas properties
		if ($params->get('fusionchart_cnvcolor'))
		{
			$strParam .= ';canvasBgColor=' . $params->get('fusionchart_cnvcolor', '');
		}

		if ($params->get('fusionchart_cnvalpha'))
		{
			$strParam .= ';canvasBgAlpha=' . $params->get('fusionchart_cnvalpha', '');
		}

		if ($params->get('fusionchart_bordercolor'))
		{
			$strParam .= ';canvasBorderColor=' . $params->get('fusionchart_bordercolor', '');
		}

		if ($params->get('fusionchart_borderthick'))
		{
			$strParam .= ';canvasBorderThickness=' . $params->get('fusionchart_borderthick', '');
		}
		// Chart and Axis Title, except caption
		if ($params->get('fusionchart_subcaption'))
		{
			$strParam .= ';subcaption=' . $params->get('fusionchart_subcaption', '');
		}

		if ($params->get('fusionchart_xaxis_name'))
		{
			$strParam .= ';xAxisName=' . $params->get('fusionchart_xaxis_name', '');
		}

		if ($params->get('fusionchart_yaxis_name'))
		{
			$strParam .= ';yAxisName=' . $params->get('fusionchart_yaxis_name', '');
		}
		// Chart Limits
		if ($params->get('fusionchart_yaxis_minvalue'))
		{
			$strParam .= ';yAxisMinValue=' . $params->get('fusionchart_yaxis_minvalue', '');
		}

		if ($params->get('fusionchart_yaxis_maxvalue'))
		{
			$strParam .= ';yAxisMaxValue=' . $params->get('fusionchart_yaxis_maxvalue', '');
		}
		// General Properties

		if ($params->get('fusionchart_labelstep', '') !== '')
		{
			$strParam .= ';labelStep=' . $params->get('fusionchart_labelstep', '');
		}

		if ($params->get('fusionchart_shownames') == '0')
		{
			// Default = 1
			$strParam .= ';shownames=' . $params->get('fusionchart_shownames', '');
		}

		if ($params->get('fusionchart_showvalues') == '0')
		{
			// Default = 1
			$strParam .= ';showValues=' . $params->get('fusionchart_showvalues', '');
		}

		if ($params->get('fusionchart_showlimits') == '0')
		{
			// Default = 1
			$strParam .= ';showLimits=' . $params->get('fusionchart_showlimits', '');
		}

		if ($params->get('fusionchart_rotatenames') == '1')
		{
			// Default = 0
			$strParam .= ';rotateNames=' . $params->get('fusionchart_rotatenames', '');

			if ($params->get('fusionchart_slantlabels') == '1')
			{
				// Default = 0
				$strParam .= ';slantLabels=' . $params->get('fusionchart_slantlabels', '');
			}
		}

		if ($params->get('fusionchart_rotatevalues') == '1')
		{
			// Default = 0
			$strParam .= ';rotateValues=' . $params->get('fusionchart_rotatevalues', '');
		}

		if ($params->get('fusionchart_values_inside') == '1')
		{
			// Default = 0
			$strParam .= ';placeValuesInside=' . $params->get('fusionchart_values_inside', '');
		}

		if ($params->get('fusionchart_animation') == '0')
		{
			// Default = 1
			$strParam .= ';animation=' . $params->get('fusionchart_animation', '');
		}

		if ($params->get('fusionchart_colshadow') == '0')
		{
			// Default = 1
			$strParam .= ';showColumnShadow=' . $params->get('fusionchart_colshadow', '');
		}
		// Font Properties
		if ($params->get('fusionchart_basefont') != '0')
		{
			$strParam .= ';baseFont=' . $params->get('fusionchart_basefont', '');
		}

		if ($params->get('fusionchart_basefont_size'))
		{
			$strParam .= ';baseFontSize=' . $params->get('fusionchart_basefont_size', '');
		}

		if ($params->get('fusionchart_basefont_color'))
		{
			$strParam .= ';baseFontColor=' . $params->get('fusionchart_basefont_color', '');
		}

		if ($params->get('fusionchart_outcnv_basefont') != '0')
		{
			$strParam .= ';outCnvBaseFont=' . $params->get('fusionchart_outcnv_basefont', '');
		}

		if ($params->get('fusionchart_outcnv_basefont_color'))
		{
			$strParam .= ';outCnvBaseFontColor=' . $params->get('fusionchart_outcnv_basefont_color', '');
		}

		if ($params->get('fusionchart_outcnv_basefont_size'))
		{
			$strParam .= ';outCnvBaseFontSize=' . $params->get('fusionchart_outcnv_basefont_size', '');
		}
		// Number Formatting Options
		if ($params->get('fusionchart_num_prefix'))
		{
			$strParam .= ';numberPrefix=' . $params->get('fusionchart_num_prefix', '');
		}

		if ($params->get('fusionchart_num_suffix'))
		{
			$strParam .= ';numberSuffix=' . $params->get('fusionchart_num_suffix', '');
		}

		$strParam .= ';formatNumber=' . $params->get('fusionchart_formatnumber', '');
		$strParam .= ';formatNumberScale=' . $params->get('fusionchart_formatnumberscale', '');

		if ($params->get('fusionchart_decimal_sep'))
		{
			$strParam .= ';decimalSeparator=' . $params->get('fusionchart_decimal_sep', '');
		}

		if ($params->get('fusionchart_thousand_sep'))
		{
			$strParam .= ';thousandSeparator=' . $params->get('fusionchart_thousand_sep', '');
		}

		if ($params->get('fusionchart_decimal_precision'))
		{
			$strParam .= ';decimalPrecision=' . $params->get('fusionchart_decimal_precision', '');
		}

		if ($params->get('fusionchart_divline_decimal_precision'))
		{
			$strParam .= ';divLineDecimalPrecision=' . $params->get('fusionchart_divline_decimal_precision', '');
		}

		if ($params->get('fusionchart_limits_decimal_precision'))
		{
			$strParam .= ';limitsDecimalPrecision=' . $params->get('fusionchart_limits_decimal_precision', '');
		}
		// Zero Plane
		if ($params->get('fusionchart_zero_thick'))
		{
			$strParam .= ';zeroPlaneThickness=' . $params->get('fusionchart_zero_thick', '');
		}

		if ($params->get('fusionchart_zero_color'))
		{
			$strParam .= ';zeroPlaneColor=' . $params->get('fusionchart_zero_color', '');
		}

		if ($params->get('fusionchart_zero_alpha'))
		{
			$strParam .= ';zeroPlaneAlpha=' . $params->get('fusionchart_zero_alpha', '');
		}
		// Divisional Lines Horizontal
		if ($params->get('fusionchart_divline_number'))
		{
			$strParam .= ';numDivLines=' . $params->get('fusionchart_divline_number', '');
		}
		else
		{
			$strParam .= ';adjustDiv=0';
		}

		if ($params->get('fusionchart_divline_color'))
		{
			$strParam .= ';divLineColor=' . $params->get('fusionchart_divline_color', '');
		}

		if ($params->get('fusionchart_divline_thick'))
		{
			$strParam .= ';divLineThickness=' . $params->get('fusionchart_divline_thick', '');
		}

		if ($params->get('fusionchart_divline_alpha'))
		{
			$strParam .= ';divLineAlpha=' . $params->get('fusionchart_divline_alpha', '');
		}

		if ($params->get('fusionchart_divline_showvalue') != '1')
		{
			// Default = 1
			$strParam .= ';showDivLineValue=' . $params->get('fusionchart_divline_showvalue', '');
		}

		if ($params->get('fusionchart_divline_alt_hgrid_color'))
		{
			$strParam .= ';showAlternateHGridColor=1';
			$strParam .= ';alternateHGridColor=' . $params->get('fusionchart_divline_alt_hgrid_color', '');
			$strParam .= ';alternateHGridAlpha=' . $params->get('fusionchart_divline_alt_hgrid_alpha', '');
		}
		else
		{
			$strParam .= ';showAlternateHGridColor=0';
		}

		// Divisional Lines Vertical
		if ($params->get('fusionchart_vdivline_number'))
		{
			$strParam .= ';numVDivLines=' . $params->get('fusionchart_vdivline_number', '');
		}

		if ($params->get('fusionchart_vdivline_color'))
		{
			$strParam .= ';VDivLineColor=' . $params->get('fusionchart_vdivline_color', '');
		}

		if ($params->get('fusionchart_vdivline_thick'))
		{
			$strParam .= ';VDivLineThickness=' . $params->get('fusionchart_vdivline_thick', '');
		}

		if ($params->get('fusionchart_vdivline_alpha'))
		{
			$strParam .= ';VDivLineAlpha=' . $params->get('fusionchart_vdivline_alpha', '');
		}

		if ($params->get('fusionchart_divline_alt_vgrid_color'))
		{
			$strParam .= ';showAlternateVGridColor=1';
			$strParam .= ';alternateVGridColor=' . $params->get('fusionchart_divline_alt_vgrid_color', '');
			$strParam .= ';alternateVGridAlpha=' . $params->get('fusionchart_divline_alt_vgrid_alpha', '');
		}

		// Hover Caption Properties
		if ($params->get('fusionchart_show_hovercap') != '1')
		{
			$strParam .= ';showhovercap=' . $params->get('fusionchart_show_hovercap', '');
		}

		if ($params->get('fusionchart_hovercap_bgcolor'))
		{
			$strParam .= ';hoverCapBgColor=' . $params->get('fusionchart_hovercap_bgcolor', '');
		}

		if ($params->get('fusionchart_hovercap_bordercolor'))
		{
			$strParam .= ';hoverCapBorderColor=' . $params->get('fusionchart_hovercap_bordercolor', '');
		}

		if ($params->get('fusionchart_hovercap_sep'))
		{
			$strParam .= ';hoverCapSepChar=' . $params->get('fusionchart_hovercap_sep', '');
		}

		// Chart Margins
		if ($params->get('fusionchart_chart_leftmargin'))
		{
			$strParam .= ';chartLeftMargin=' . $params->get('fusionchart_chart_leftmargin', '');
		}

		if ($params->get('fusionchart_chart_rightmargin'))
		{
			$strParam .= ';chartRightMargin=' . $params->get('fusionchart_chart_rightmargin', '');
		}

		if ($params->get('fusionchart_chart_topmargin'))
		{
			$strParam .= ';chartTopMargin=' . $params->get('fusionchart_chart_topmargin', '');
		}

		if ($params->get('fusionchart_chart_bottommargin'))
		{
			$strParam .= ';chartBottomMargin=' . $params->get('fusionchart_chart_bottommargin', '');
		}

		if ($params->get('fusionchart_connect_nulldata'))
		{
			$strParam .= ';connectNullData=' . $params->get('fusionchart_connect_nulldata', 1);
		}

		return $strParam;
	}

	/**
	 * Set the chart messsages
	 *
	 * @return null
	 */
	protected function setChartMessages()
	{
		$params = $this->getParams();

		// Graph Messages
		if ($params->get('fusionchart_message_loading'))
		{
			$this->FC->setChartMessage("PBarLoadingText=" . $params->get('fusionchart_message_loading', 'Please Wait.The chart is loading...'));
		}

		if ($params->get('fusionchart_message_parsing_data'))
		{
			$this->FC->setChartMessage("ParsingDataText=" . $params->get('fusionchart_message_parsing_data', 'Reading Data. Please Wait'));
		}

		if ($params->get('fusionchart_message_nodata'))
		{
			$this->FC->setChartMessage("ChartNoDataText=" . $params->get('fusionchart_message_nodata', 'No data to display.'));
		}
	}

	/**
	 * Replace placeholders in $msg with request variables
	 *
	 * @param   string  $msg  source string
	 *
	 * @return  string  replaced string
	 */
	private function _replaceRequest($msg)
	{
		$db = $this->_db;
		$filter = JFilterInput::getInstance();
		$request = $filter->clean($_REQUEST, 'array');

		foreach ($request as $key => $val)
		{
			if (is_string($val))
			{
				// $$$ hugh - escape the key so preg_replace won't puke if key contains /
				$key = str_replace('/', '\/', $key);
				$msg = preg_replace("/\{$key\}/", $db->quote(urldecode($val)), $msg);
			}
		}

		return $msg;
	}

	/**
	 * Set the axis labels
	 *
     * $row  array|object  row data
     * $axisKey  int  dataset index
     *
	 * @return  void
	 */
	protected function setAxisLabel($row, $axisKey)
	{
		$worker = new FabrikWorker;
		$this->axisLabels[$axisKey] = $worker->parseMessageForPlaceholder(
            $this->axisLabels[$axisKey],
            $row,
            false
        );
	}

    /**
     * Set the axis labels
     *
     * $row  array|object  row data
     * $axisKey  int  dataset index
     *
     * @return  void
     */
    protected function getAxisLabel($axisKey)
    {
        return \Fabrik\Helpers\ArrayHelper::getValue($this->axisLabels, $axisKey, '');
    }


    /**
     * Set default axis labels
     *
     * @return  void
     */
    protected function setAxisLabels()
    {
        $worker = new FabrikWorker;
        $params = $this->getParams();
        $this->axisLabels = (array) $params->get('fusionchart_axis_labels');

        foreach ($this->axisLabels as $axis_key => $axis_val)
        {
            $this->axisLabels[$axis_key] = (string)$worker->parseMessageForPlaceholder(
	            (string)$axis_val,
                null,
                true
            );
        }
    }

    /**
     * Set the axis labels
     *
     * $row  array|object  row data
     * $axisKey  int  dataset index
     *
     * @return  void
     */
    protected function cleanAxisLabels()
    {
        $worker = new FabrikWorker;

        foreach ($this->axisLabels as $axis_key => $axis_val)
        {
            $this->axisLabels[$axis_key] = $worker->parseMessageForPlaceholder(
                $this->axisLabels[$axis_key],
                null,
                false
            );
        }
    }

	/**
	 * Load the Fusion chart lib
	 *
	 * @return  string
	 */
	public function getFusionchart()
	{
		$this->cantTrendLine = array();
		$document = JFactory::getDocument();
		$params = $this->getParams();
		$worker = new FabrikWorker;
		$xtLibPath = $params->get('fusionchart_library', 'fusioncharts-suite-xt');
		$xt    = $this->pathBase . 'fusionchart/libs/' . $xtLibPath . '/integrations/php/fusioncharts-wrapper/fusioncharts.php';

		if (JFile::exists($xt))
		{
			require_once $xt;
			//$document->addScript($this->srcBase . "fusionchart/libs/fusioncharts-suite-xt/js/fusioncharts.js");
		}
		else
		{
			return false;
		}

		$calc_prefixes = array('sum___', 'avg___', 'med___', 'cnt___');
		$calc_prefixmap = array('sum___' => 'sums', 'avg___' => 'avgs', 'med___' => 'medians', 'cnt___' => 'count');
		$w = $params->get('fusionchart_width');
		$h = $params->get('fusionchart_height');

		$chartType = $params->get('fusionchart_type', '');

		if ($chartType == '')
		{
			throw new InvalidArgumentException('Not chart type selected');
		}

		//$this->setChartMessages();

		// Setting Param string
		$strParam = $this->getChartParams();

		$x_axis_label = (array) $params->get('fusion_x_axis_label');
		$x_axis_sort = (array) $params->get('fusion_x_axis_sort');
		$chartElements = (array) $params->get('fusionchart_elementList');
		$chartColours = (array) $params->get('fusionchart_elcolour');
        $chartMSGroupBy = (array) $params->get('fusionchart_ms_group_by');
		$listid = (array) $params->get('fusionchart_table');
		$chartCumulatives = (array) $params->get('fusionchart_cumulative');
		$elTypes = (array) $params->get('fusionchart_element_type');
		$this->setAxisLabels();

		$dual_y_parents = $params->get('fusionchart_dual_y_parent');
		$chartWheres = (array) $params->get('fusionchart_where');
		$limits = (array) $params->get('fusionchart_limit');
		$this->c = 0;
		$gdata = array();
		$glabels = array();
		$gsorts = array();
		$gmsgroupby = array();
		$gaxislabels = array();
		$gcolours = array();
		$gfills = array();
		$this->max = array();
		$this->min = array();
		$calculationLabels = array();
		$calculationData = array();
		$calcfound = false;
		$tmodels = array();

		foreach ($listid as $tid)
		{
			$this->min[$this->c] = 0;
			$this->max[$this->c] = 0;

			if (!array_key_exists($tid, $tmodels))
			{
				$listModel = null;
				$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
				$listModel->setId($tid);
				$tmodels[$tid] = $listModel;
			}
			else
			{
				$listModel = $tmodels[$tid];
			}

			$table = $listModel->getTable();
			$form = $listModel->getForm();

			// $$$ hugh - adding plugin query, 2012-02-08
			if (array_key_exists($this->c, $chartWheres) && !empty($chartWheres[$this->c]))
			{
				$chartWhere = $this->_replaceRequest($chartWheres[$this->c]);
				$chartWhere = $worker->replaceWithUserData($chartWhere);
				$listModel->setPluginQueryWhere('fusionchart', $chartWhere);
			}
			else
			{
				// If no where clause, explicitly clear any previously set clause
				$listModel->unsetPluginQueryWhere('fusionchart');
			}

			/* $$$ hugh - remove pagination BEFORE calling render().  Otherwise render() applies
			 * session state/defaults when it calls getPagination, which is then returned as a cached
			* object if we call getPagination after render().  So call it first, then render() will
			* get our cached pagination, rather than vice versa.
			*/

			$cals = array();

			$alldata = $this->getQueryData();

			if (!$alldata)
			{
				$limit = (int) FArrayHelper::getValue($limits, $this->c, 0);
				$listModel->setLimits(0, $limit);
				$nav = $listModel->getPagination(0, 0, $limit);
				$listModel->render();
				$alldata = $listModel->getData();
				$cals = $listModel->getCalculations();
			}

			$column = $chartElements[$this->c];
            $msGroupBy = FArrayHelper::getValue($chartMSGroupBy, $this->c, '');
			$pref = substr($column, 0, 6);

			$label = FArrayHelper::getValue($x_axis_label, $this->c, '');
			$sort  = FArrayHelper::getValue($x_axis_sort, $this->c, $label);

			if (empty($sort))
			{
				$sort = $label;
			}

			$tmpgdata = array();
			$tmpglabels = array();
			$tmpgsorts = array();
			$tmpgmsgroupby = array();
            $tmpaxislabels = array();
			$colour = array_key_exists($this->c, $chartColours) ? str_replace("#", '', $chartColours[$this->c]) : '';

			$gcolours[] = $colour;

			if (in_array($pref, $calc_prefixes))
			{
				/* you shouldn't mix calculation elements with normal elements when creating the chart
				 * so if ONE calculation element is found we use the calculation data rather than normal element data
				* this is because a calculation element only generates one value, if want to compare two averages then
				* they get rendered as tow groups of data and on bar charts this overlays one average over the other, rather than next to it
				*/
				$calcfound = true;
				$column = JString::substr($column, 6);
				$calckey = $calc_prefixmap[$pref];
				$caldata = FArrayHelper::getValue($cals[$calckey], $column . '_obj');

				if (is_array($caldata))
				{
					foreach ($caldata as $k => $o)
					{
						if ($k !== 'Total')
						{
							$calculationData[] = (float) $o->value;
							$calculationLabels[] = trim(strip_tags($o->label));
						}
					}
				}

				if (!empty($calculationData))
				{
					$this->max[$this->c] = max($calculationData);
					$this->min[$this->c] = min($calculationData);
				}

				$gdata[$this->c] = $tmpgdata;
				$glabels[$this->c] = $tmpglabels;

				/* $$$ hugh - playing around with pie charts
				 * $gsums[$this->c] = array_sum($tmpgdata);
				*/
				$gsums[$this->c] = array_sum($calculationData);
			}
			else
			{
				$origColumn = $column;

				// _raw fields are most likely to contain the value
				$column = $column . '_raw';

				foreach ($alldata as $group) {
                    foreach ($group as $row) {
                        $this->setAxisLabel($row, $this->c);

                        if (!array_key_exists($column, $row)) {
                            // Didn't find a _raw column - revert to orig
                            $column = $origColumn;

                            if (!array_key_exists($column, $row)) {
                                JError::raiseWarning(E_NOTICE, $column . ': NOT FOUND - PLEASE CHECK IT IS PUBLISHED');
                                continue;
                            }
                        }

                        $tmpgdata[] = (trim($row->$column) == '') ? null : (float)$row->$column;
                        $tmpgmsgroupby[] = empty($msGroupBy) || (trim($row->$msGroupBy) == '') ? null : $row->$msGroupBy;
                        $tmpglabels[] = !empty($label) ? trim(html_entity_decode(strip_tags($row->$label))) : '';
	                    $tmpgsorts[] = !empty($sort) ? html_entity_decode(strip_tags($row->$sort)) : '';
                        $tmpaxislabels[] = $this->getAxisLabel($this->c);
                    }
                }

                if (!empty($tmpgdata))
                {
                    $this->max[$this->c] = max($tmpgdata);
                    $this->min[$this->c] = min($tmpgdata);
                }

                $gdata[$this->c] = $tmpgdata;
                $glabels[$this->c] = $tmpglabels;
				$gsorts[$this->c] = $tmpgsorts;
                $gaxislabels[$this->c] = $tmpaxislabels;
                $gmsgroupby[$this->c] = $tmpgmsgroupby;

                // $$$ hugh - playing around with pie charts
                $gsums[$this->c] = array_sum($tmpgdata);

                // $$$ hugh - playing with 'cumulative' option
                $this->gcumulatives[$this->c] = array();

                while (!empty($tmpgdata))
                {
                    $this->gcumulatives[$this->c][] = array_sum($tmpgdata);
                    array_pop($tmpgdata);
                }

                $this->gcumulatives[$this->c] = array_reverse($this->gcumulatives[$this->c]);

			}

			$this->c++;
		}

		if (!empty($msGroupBy))
        {
            $tmps = array();
            $tmpaxislabels = array();

            foreach ($gmsgroupby[0] as $key => $groupby)
            {
            	$groupby = (string)$groupby;

                if (!array_key_exists($groupby, $tmps))
                {
                    $tmps[$groupby] = array(
                        'data' => array(),
                        'labels' => array(),
                        'sorts' => array(),
                        'axislabels' => array()
                    );
                }

                $tmps[$groupby]['data'][] = $gdata[0][$key];
                $tmps[$groupby]['labels'][] = $glabels[0][$key];
                $tmps[$groupby]['sorts'][] = $gsorts[0][$key];
                $tmps[$groupby]['axislabels'][] = $gaxislabels[0][$key];
            }

            unset($gdata);
            unset($glabels);
            unset($gsorts);
            unset($gaxislabels);

            $key = 0;
            foreach ($tmps as $tmp)
            {
                $gdata[] = $tmp['data'];
                $glabels[] = $tmp['labels'];
                $gsorts[] = $tmp['sorts'];
                $gaxislabels[] = $tmp['axislabels'];
                $elTypes[$key] = $elTypes[0];
                $key++;
            }

            $params->set('fusionchart_element_type', $elTypes);
            $params->set('fusionchart_axis_labels', array_keys($tmps));
            $this->setAxisLabels();
            $this->c = count($tmps);
        }

		if ($calcfound)
		{
			$calculationLabels = array_reverse($calculationLabels);
			$glabels = array_reverse($calculationLabels);
			$gdata = $calculationData;
		}

		// get rid of any that had placeholders but no data
        $this->cleanAxisLabels();

		/* $$$ hugh - pie chart data has to be summed - the API only takes a
		 * single dataset for pie charts.  And it doesn't make sense trying to
		* chart individual row data for multiple elements in a pie chart.
		* Also, labels need to be axisLabels, not $glabels
		*/
		switch ($chartType)
		{
			// Single Series Charts
			case 'MAPS':
			case 'AREA2D':
			case 'BAR2D':
			case 'COLUMN2D':
			case 'COLUMN3D':
			case 'DOUGHNUT2D':
			case 'DOUGHNUT3D':
			case 'LINE':
            case 'FUNNEL2D':
            case 'FUNNEL3D':
			case 'PIE2D':
			case 'PIE3D':
			case 'SCATTER':
			case 'SPLINE':
			case 'SPLINEAREA':
				// Adding specific params for some chart types
				if ($chartType == 'PIE2D' || $chartType == 'PIE3D')
				{
					$strParam .= ';pieBorderThickness=' . $params->get('fusionchart_borderthick', '');
					$strParam .= ';pieBorderAlpha=' . $params->get('fusionchart_cnvalpha', '');
					$alphas = $params->get('fusionchart_elalpha', array());
					$strParam .= ';pieFillAlpha=' . FArrayHelper::getValue($alphas,0);
				}

				if ($chartType == 'FUNNEL2D')
                {
                    $strParam .= ';is2D=1';
                    $chartType = 'FUNNEL';
                }
				else if ($chartType == 'FUNNEL3D') {
                    $strParam .= ';is2D=0';
                    $chartType = 'FUNNEL';
                }
				else if ($chartType === 'MAPS')
				{
					$this->setChartColorRange();
				}


				$datasets = 0;
				$datasetKey = 0;

				foreach ($elTypes as $index => $elType)
				{
					if ($elType === 'dataset')
					{
						$datasets++;
						$datasetKey = $index;
					}
				}

				if ($datasets > 1)
				{
					$arrCatNames = array();

					foreach ($this->axisLabels as $alkey => $al)
					{
						$arrCatNames[] = $al;
					}

					$arrData = array();
					$i = 0;

					foreach ($gsums as $gd)
					{
						$attrs = array('label' => $this->axisLabels[$i]);

						if (!empty($gcolours[$i]))
						{
							$attrs['color'] = $gcolours[$i];
						}

						$arrData[$i][0] = $this->axisLabels[$i];
						$arrData[$i][1] = $gd;
						$this->addChartData($gd, $attrs);
						$i++;
					}

					//$this->FC->addChartDataFromArray($arrData, $arrCatNames);
				}
				else
				{
					// Single table/elements, so use the row data
					$labels = $glabels[$datasetKey];
					$gsums = FArrayHelper::getValue($chartCumulatives, $datasetKey, '0') == '0' ? $gdata[$datasetKey] : $this->gcumulatives[$datasetKey];

					$sums = array();
					foreach ($labels as $lkey => $label)
					{
						if (!array_key_exists($label, $sums))
						{
							$sums[$label] = 0;
						}

						$sums[$label] += $gsums[$lkey];
					}

					// Scale to percentages
					$tot_sum = array_sum($gsums);
					$arrData = array();

					if ($elTypes[$datasetKey] == 'trendonly')
					{
						$str_params = array();
						$min = min($sums);
						$max = max($sums);
						list($min, $max) = $this->getTrendMinMax($min, $max, 0);
						$this->addChartData($min, $str_params);
						$this->addChartData($max, $str_params);
					}
					else
					{
						$data_count = 0;

						foreach ($sums as $label => $value)
						{
							$data_count++;

							//$label = $labels[$key];
							$keyName = $chartType === 'MAPS' ? 'id' : 'label';
							$str_params = array(
								$keyName => $label
							);

							$this->addChartData($value, $str_params);
						}
					}
				}
				break;
            case 'STACKEDAREA2D':
            case 'STACKEDBAR2D':
            case 'STACKEDCOLUMN2D':
            case 'STACKEDCOLUMN3D':
                if ($this->c > 0)
                {
                    $labelPos = array();
                    $allLabels = array();

                    foreach ($glabels as $glabel)
                    {
                        $allLabels = array_unique(array_merge($allLabels, $glabel));
                    }

                    foreach ($allLabels as $catLabel) {
                        $catParams = array();
                        $this->addCategory($catLabel, $catParams);
                    }

                    $allData = array();

                    foreach ($gdata as $key => $chartdata)
                    {
                        $chartlabels = $glabels[$key];
                        $datasetLabel = $this->axisLabels[$key];
                        $extras = array();

                        $color = FArrayHelper::getValue($gcolours, $key, '');

                        if (!empty($color))
                        {
                            $extras['color'] = $color;
                        }

                        $dataset = array();

                        foreach ($chartdata as $ckey => $value)
                        {
                            $allLabelsKey = array_search($chartlabels[$ckey], $allLabels);
                            $dataset[$allLabelsKey] = $this->makeChartData($value);
                        }

                        foreach ($allLabels as $allLabelsKey => $label)
                        {
                            if (!array_key_exists($allLabelsKey, $dataset))
                            {
                                $dataset[$allLabelsKey] = null;
                            }
                        }

                        ksort($dataset);
                        $this->addDataset($dataset, $datasetLabel, $extras);
                    }
                }
                break;

			case 'MSBAR2D':
			case 'MSBAR3D':
			case 'MSCOLUMN2D':
			case 'MSCOLUMN3D':
			case 'MSLINE':
			case 'MSSPLINE':
			case 'MSAREA2D':
			case 'MSSTACKEDCOLUMN2D':
			case 'MSCOMBIDY2D':
			case 'MULTIAXISLINE':
			case 'SCROLLAREA2D':
			case 'SCROLLCOLUMN2D':
			case 'SCROLLLINE2D':
			case 'SCROLLCOMBI2D':
			case 'SCROLLCOMBIDY2D':
			case 'SCROLLSTACKEDCOLUMN2D':
				if ($this->c > 0)
				{
					if ($chartType == 'SCROLLAREA2D' || $chartType == 'SCROLLCOLUMN2D' || $chartType == 'SCROLLLINE2D')
					{
						$strParam .= ';numVisiblePlot=' . $params->get('fusionchart_scroll_numvisible', 0);
					}

					// $$$ hugh - Dual-Y types
					if ($chartType == 'MSCOMBIDY2D' || $chartType == 'MULTIAXISLINE' || $chartType == 'SCROLLCOMBIDY2D')
					{
						$p_parents = array();
						$s_parents = array();

						foreach ($dual_y_parents as $dual_y_key => $dual_y_parent)
						{
							if ($dual_y_parent == "P")
							{
								$p_parents[] = $this->axisLabels[$dual_y_key];
							}
							else
							{
								$s_parents[] = $this->axisLabels[$dual_y_key];
							}
						}

						$strParam .= ';PYAxisName=' . implode(' ', $p_parents);
						$strParam .= ';SYaxisName=' . implode(' ', $s_parents);
					}

					$allLabels = array();
					$allSorts = array();

					foreach ($glabels as $glabel)
					{
						$allLabels = array_unique(array_merge($allLabels, $glabel));
					}

					foreach ($gsorts as $gsort)
					{
						$allSorts = array_unique(array_merge($allSorts, $gsort));
					}

					array_multisort($allSorts, SORT_ASC, $allLabels);
					//sort($allLabels);
					$data_count = 0;

					foreach ($allLabels as $catLabel) {
						$data_count++;
						$catParams = array();

						$this->addCategory($catLabel, $catParams);
					}

					foreach ($gdata as $key => $chartdata)
					{
						$cdata = FArrayHelper::getValue($chartCumulatives, $key, '0') == '0' ? $gdata[$key] : $this->gcumulatives[$key];
						$datasetLabel = $this->axisLabels[$key];

                        if ($chartType == 'MSCOMBIDY2D' || $chartType == 'MULTIAXISLINE' || $chartType == 'SCROLLCOMBIDY2D') {
                            $extras = array(
                                'parentYAxis' => $dual_y_parents[$key]
                            );
                        }
                        else
                        {
                            $extras = array();
                        }

						$color = FArrayHelper::getValue($gcolours, $key, '');

						if (!empty($color))
						{
							$extras['color'] = $color;
						}

						$dataset = array();

						if ($elTypes[$key] == 'trendonly')
						{
							$str_params = array();
							$strParam .= ';connectNullData=1';
							$min = min($cdata);
							$max = max($cdata);
							list($min, $max) = $this->getTrendMinMax($min, $max, $key);
							$max_datapoints = $this->getMaxDatapoints($gdata);
							$dataset[] = $this->makeChartData($min, $str_params);

							for ($x = 0; $x < $max_datapoints - 2; $x++)
							{
								$dataset[] = $this->makeChartData('', $str_params);
							}

							$dataset[] = $this->makeChartData($max, $str_params);
						}
						else
						{
							$chartlabels = $glabels[$key];

							foreach ($cdata as $ckey => $value)
							{
								$allLabelsKey = array_search($chartlabels[$ckey], $allLabels);
								$dataset[$allLabelsKey] = $this->makeChartData($value);
							}

							foreach ($allLabels as $allLabelsKey => $label)
							{
								if (!array_key_exists($allLabelsKey, $dataset))
								{
									$dataset[$allLabelsKey] = null;
								}
							}
						}

						$this->addDataset($dataset, $datasetLabel, $extras);
					}
				}
				break;
			case 'ZOOMLINE':
			case 'ZOOMLINEDY':
				$strParam .= ";dataseparator=|";
				$strParam .= ";compactdatamode=1";
		        $strParam .= ";pixelsPerPoint=40";

				$allLabels = array();

				foreach ($glabels as $glabel)
				{
					$allLabels = array_unique(array_merge($allLabels, $glabel));
				}

				$catParams = array();
				$this->addZoomCategory(implode('|', $allLabels), $catParams);

				foreach ($gdata as $key => $chartdata)
				{
					$datasetLabel = $this->axisLabels[$key];

					if ($chartType == 'ZOOMLINEDY') {
						$extras = array(
							'parentYAxis' => $dual_y_parents[$key]
						);
					}
					else
					{
						$extras = array();
					}

					$color = FArrayHelper::getValue($gcolours, $key, '');

					if (!empty($color))
					{
						$extras['color'] = $color;
					}

					$dataset = array();
					$chartlabels = $glabels[$key];

					foreach ($chartdata as $ckey => $value)
					{
						$allLabelsKey = array_search($chartlabels[$ckey], $allLabels);
						$dataset[$allLabelsKey] = $value;
					}

					foreach ($allLabels as $allLabelsKey => $label)
					{
						if (!array_key_exists($allLabelsKey, $dataset))
						{
							$dataset[$allLabelsKey] = null;
						}
					}

					$this->addDataset(implode('|', $dataset), $datasetLabel, $extras);
				}
				break;
		}

		$this->c > 1 ? $this->trendLine($gdata) : $this->trendLine();
		$colours = implode(($calcfound ? '|' : ','), $gcolours);

		// Set chart attributes
		if ($params->get('fusionchart_custom_attributes', ''))
		{
			$strParam .= ';' . trim($params->get('fusionchart_custom_attributes'));
		}

		$strParam = "$strParam";
		$this->setChartParams($strParam);

		$doExport = $params->get('fusionchart_export', '0') === '1';

		if ($doExport)
		{
			$exportPath = $params->get('fusionchart_export_path', '.');
			FabrikWorker::replaceRequest($exportPath);

			if (substr($exportPath, 0 , 1) !== DIRECTORY_SEPARATOR)
			{
				$exportPath = JPATH_ROOT . '/' . $exportPath;
			}

			$exportFile = $params->get('fusionchart_export_filename', '');
			FabrikWorker::replaceRequest($exportFile);

			$exportJSON             = new StdClass;
			$exportJSON->type       = strtolower($chartType);
			$exportJSON->renderAt   = 'chart-container';
			$exportJSON->width      = 600;
			$exportJSON->height     = 450;
			$exportJSON->dataFormat = "json";
			$exportJSON->dataSource = $this->chartData;
			$exportArray            = array(
				$exportJSON
			);
			$exportJSON             = json_encode($exportArray);

			// Instantiate the ExportConfig class and add the required configurations
			$exportConfig = new ExportConfig();
			// Provide path of the chart configuration which we have defined above.  // You can also pass the same object as serialized JSON.
			$exportConfig->set('chartConfig', $exportJSON);

			if (!empty($exportPath))
			{
				$exportConfig->set('outputFile', $exportFile);
			}


			// Instantiate the ExportManager class
			$exportManager = new ExportManager();

			// Call the export() method with the exportConfig and the respective callbacks
			try
			{
				$exportManager->export($exportConfig, $outputDir = $exportPath, $unzip = true);
			}
			catch (Exception $e)
			{
				// meh
			}
		}

		// Create new chart
		/*
		$this->FC = new FusionCharts(
			strtolower($chartType),
			'FusionChart',
			$w,
			$h,
			'chart-container',
			'json',
			$this->getChartData()
		);

		return $this->FC->render();
		*/
		return $this->getChartData();
	}

	/**
	 * Add a trend line to the chart - not all chart types support rendering trendlines
	 *
	 * @param   array  &$gdata  data
	 *
	 * @return  void
	 */

	protected function trendLine(&$gdata = null)
	{
		$params = $this->getParams();
		$chartType = $params->get('fusionchart_type');
		$eltype = $params->get('fusionchart_element_type', 'dataset');
		$trendtypes = (array) $params->get('fusionchart_trend_type');
		$cumulatives = (array) $params->get('fusionchart_cumulative');
		$found = false;
		$trendstart = $params->get('fusionchart_trendstartvalue', '');
		$trendend = $params->get('fusionchart_trendendvalue', '');
		$merge = $params->get('fusionchart_trendmerge', '');
		$thisMin = null;
		$thisMax = null;
		$thisNbe = 0;

		for ($nbe = 0; $nbe < $this->c; $nbe++)
		{
			if ($eltype[$nbe] != 'dataset')
			{
				$thisNbe = $nbe;
				$trendtype = FArrayHelper::getValue($trendtypes, $nbe, 'minmax');

				// Trendline Start & End values
				if ($trendstart)
				{
					$found = true;
					$startval = $trendstart;
					$endval = $trendend;
				}
				elseif ($eltype[$nbe] == 'trendline')
				{
					$found = true;
					$min = $this->min[$nbe];
					$max = $this->max[$nbe];
					$cumulative = FArrayHelper::getValue($cumulatives, $nbe, '0');

					if ($cumulative == '1')
					{
						// Using cumulative values, so need to reset min & max to use those
						$min = min($this->gcumulatives[$nbe]);
						$max = max($this->gcumulatives[$nbe]);
					}
					// If Start & End values are not specifically defined, use the element's min & max values
					switch ($trendtype)
					{
						case 'zeromax':
							$startval = 0;
							$endval = $max;
							break;
						case 'maxzero':
							$startval = $max;
							$endval = 0;
							break;
						case 'maxmin':
							$startval = $max;
							$endval = $min;
							break;
						case 'minmax':
						default:
							$startval = $min;
							$endval = $max;
							break;
					}
				}

				if ($found)
				{
					if ($merge)
					{
						if (!isset($thisMin) || $startval < $thisMin)
						{
							$thisMin = $startval;
						}

						if (!isset($thisMax) || $endval > $thisMax)
						{
							$thisMax = $endval;
						}
					}
					else
					{
						$this->buildTrendLine($startval, $endval, $nbe);
					}

					if (is_array($gdata))
					{
						unset($this->axisLabels[$nbe]);
						unset($gdata[$nbe]);
						unset($this->chartData['dataset'][$nbe]);
					}
				}
			}
		}

		if ($found && $merge)
		{
			switch ($trendtype)
			{
				case 'zeromax':
					$thisMin = 0;
					break;
				case 'maxzero':
					$thisMin = $thisMax;
					$thisMax = 0;
					break;
				case 'maxmin':
					$tmp = $thisMin;
					$thisMin = $thisMax;
					$thisMax = $tmp;
					break;
				case 'minmax':
				default:
					break;
			}

			$this->buildTrendLine($thisMin, $thisMax, $thisNbe);
		}
		else if (!$found && ($trendstart != '' && $trendend != ''))
		{
			$this->buildTrendLine($trendstart, $trendend, $thisNbe);
		}
	}

	/**
	 * Build the trendline
	 *
	 * @param   int     $startval  trendline startValue
	 * @param   int     $endval    trendline endValue
	 * @param   string  $nbe       key used to get trendline display value from $this->axixLables
	 *
	 * @return  void
	 */

	protected function buildTrendLine($startval, $endval, $nbe = null)
	{
		$params = $this->getParams();
		$trendParams = array();
		$trendParams['startValue'] = $startval;
		$trendParams['endValue'] = $endval;

		if (isset($nbe))
		{
			$trendParams['displayvalue'] = FArrayHelper::getValue($this->axisLabels, $nbe, $params->get('fusionchart_trendlabel', ''));
		}

		$trendParams['showOnTop'] = $params->get('fusionchart_trendshowontop', '1');

		if ($startval < $endval)
		{
			$trendParams['isTrendZone'] = $params->get('fusionchart_trendiszone', '0');
		}

		$elcolour = (array) $params->get('fusionchart_elcolour', '');
		$elalpha = (array) $params->get('fusionchart_elalpha', '');
		$trendParams['color'] = FArrayHelper::getValue($elcolour, $nbe, '333333');
		$trendParams['alpha'] = FArrayHelper::getValue($elalpha, $nbe, 50);
		$trendParams['thickness'] = '3';
		$this->addTrendLine($trendParams);
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
			$this->listids = (array) $params->get('fusionchart_table');
		}
	}

	/**
	 * Get the trend line min and max values
	 *
	 * @param   int     $min  trendline startValue
	 * @param   int     $max  trendline endValue
	 * @param   string  $nbe  key used to get trendline display value from $this->axixLables
	 *
	 * @return  array  (start, end)
	 */

	protected function getTrendMinMax($min, $max, $nbe)
	{
		$params = $this->getParams();
		$trendtypes = (array) $params->get('fusionchart_trend_type');
		$trendtype = FArrayHelper::getValue($trendtypes, $nbe, 'minmax');

		switch ($trendtype)
		{
			case 'zeromax':
				$startval = 0;
				$endval = $max;
				break;
			case 'maxzero':
				$startval = $max;
				$endval = 0;
				break;
			case 'maxmin':
				$startval = $max;
				$endval = $min;
				break;
			case 'minmax':
			default:
				$startval = $min;
				$endval = $max;
				break;
		}

		return array($startval, $endval);
	}

	/**
	 * Returns maximum data points used by any dataset.
	 * Used by 'trendonly' graphs, to work out how many null
	 * data points to insert between min and max.
	 *
	 * @param   array  $data  data
	 *
	 * @since	3.0.6
	 *
	 * @return number
	 */

	protected function getMaxDatapoints($data)
	{
		$max_datapoints = 0;

		foreach ($data as $d)
		{
			$datapoints = count(explode(',', $d));

			if ($datapoints > $max_datapoints)
			{
				$max_datapoints = $datapoints;
			}
		}

		return $max_datapoints;
	}

	private function addTrendLine($params)
	{
		if (!isset($this->chartData['trendlines']))
		{
			$this->chartData['trendlines'] = array();
			//$this->chartData['trendlines'] = array();
		}

		$this->chartData['trendlines'][0]['line'][] = $params;
	}

	private function addCategory($label, $params)
	{
		if (!isset($this->chartData['categories']))
		{
			$this->chartData['categories'] = array();
			//$this->chartData['categories'] = array();
		}

		$this->chartData['categories'][0]['category'][] = array_merge(
			array('label' => $label),
			$params
		);
	}

	private function addZoomCategory($labels, $params)
	{
		if (!isset($this->chartData['categories']))
		{
			$this->chartData['categories'] = array();
		}

		$this->chartData['categories'][0]['category'] = $labels;
	}

	private function addDataset($dataset, $label, $params = array())
	{
		if (!isset($this->chartData['dataset']))
		{
			$this->chartData['dataset'] = array();
		}

		array_push($this->chartData['dataset'],
			array_merge(
				array(
					'seriesname' => $label,
					'data' => $dataset
				),
				$params
			)
		);
	}

	private function makeChartData($value, $params = array())
	{
		if ($value === null)
		{
			if (empty($params))
			{
				return null;
			}
			else
			{
				return $params;
			}
		}
		else
		{
			return array_merge(
				array(
					'value' => (string)$value
				),
				$params
			);
		}
	}

	private function addChartData($value, $params = array())
	{
		if (!isset($this->chartData['data']))
		{
			$this->chartData['data'] = array();
		}

		array_push($this->chartData['data'], $this->makeChartData($value, $params));
	}

	private function setChartParams($strParams = '')
	{
		$chartParams = array();

		if (!is_array($strParams))
		{
			$chartStrParams = explode(';', $strParams);

			foreach ($chartStrParams as $strParam)
			{
				list($key,$value) = explode('=', $strParam);
				$chartParams[$key] = $value;
			}
		}

		$this->chartData['chart'] = $chartParams;
	}

	private function setChartColorRange()
	{
		$params = $this->getParams();

		$colorrange = [
			'minvalue' => $params->get('fusionchart_map_color_minvalue', "0"),
			"startlabel" => $params->get('fusionchart_map_color_startlabel', "Low"),
	        "endlabel" => $params->get('fusionchart_map_color_endlabel', "High"),
	        "code" => $params->get('fusionchart_map_color_code', "#34baeb"),
	        "gradient" => $params->get('fusionchart_map_color_gradient', "1")
		];

		$this->chartData['colorrange'] = $colorrange;
	}

	private function getChartData()
	{
		$chartData = json_encode($this->chartData);

		return $chartData;
	}

	/**
	 * Ajax call to get the json encoded string of map markers
	 *
	 * @return  string
	 */
	public function onAjax_getFusionchart()
	{
		echo json_encode($this->getFusionchart());
	}

    /**
     * Converts our chart type the the one FC expects
     *
     * @param $chartType
     * @return string
     */
    public function getRealChartType($chartType)
    {
    	$params = $this->getParams();

        switch ($chartType)
        {
            case 'FUNNEL2D':
            case 'FUNNEL3D':
                $chartType = 'FUNNEL';
                break;
	        case 'MAPS':
	        	$chartType = strtolower($chartType . '/' . $params->get('fusionchart_map', 'world'));
	        	break;
            default:
                break;
        }

        return strtolower($chartType);
    }

    private function getQueryData()
    {
    	$params = $this->getParams();
    	$query = $params->get('fusionchart_query', array());
    	$query = \Joomla\Utilities\ArrayHelper::getValue((array)$query, $this->c, '');
		$data = array();

    	if (empty($query))
	    {
	    	return $this->getPHPData();
	    }

	    $connection = $params->get('fusionchart_connection');
	    $connection = \Joomla\Utilities\ArrayHelper::getValue($connection, $this->c);
	    $db = FabrikWorker::getDbo(false, $connection);
		$db->setQuery($query);

		try {
			$data = $db->loadObjectList();
		}
		catch (Exception $e)
		{
			// meh
		}

		return array($data);
    }

    private function getPHPData()
    {
	    $params = $this->getParams();
	    $code = $params->get('fusionchart_php', array());
	    $code = \Joomla\Utilities\ArrayHelper::getValue((array)$code, $this->c);

	    if (empty($code))
	    {
	    	return false;
	    }

	    FabrikWorker::clearEval();
	    $data = FabrikHelperHTML::isDebug() ? eval($code) : @eval($code);
	    FabrikWorker::logEval(false, 'Eval exception : fusionchart plugin : %s');

	    return $data;
    }
}
