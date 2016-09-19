/**
 * Chart Visualization
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var fabrikGraph = new Class({
	Implements: [Options],
	options: {
		legend: false,
		label: '',
		aChartKeys: {},
		axis_label: '',
		json: {},
		chartType: 'barChart',
		xticks: []
	},

	initialize: function (el, d, options) {
		this.setOptions(options);
		//todo doesnt seem to work with 1 record of data
		this.el = el;
		this.json = d;
		this.render();
	},

	render: function () {
		switch (this.options.chartType) {
		case 'BarChart':
			this.graph = new Plotr.BarChart(this.el, this.options);
			break;
		case 'PieChart':
			this.graph = new Plotr.PieChart(this.el, this.options);
			break;
		case 'LineChart':
			this.graph = new Plotr.LineChart(this.el, this.options);
			break;
		}
		this.graph.addDataset(this.json);
		this.graph.render();
		if (this.options.legend === '1') {
			this.graph.addLegend(this.el);
		}
	}
});