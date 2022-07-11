/**
 * Timeline Visualization
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbVisTimeline = new Class({

	Implements: [Options],

	options: {
		dateFormat: '%c',
		orientation: '0',
		urlfilters: []
	},

	initialize : function (json, options) {
		this.json = eval(json);
		this.setOptions(options);

		this.resizeTimerID = null;
		this.tl = null;
		var dateFormat = this.options.dateFormat;

		Timeline.GregorianDateLabeller.prototype.labelPrecise = function (date)
		{
			// Crazy hackery to reset the label time to the correct one.
			// means the Z time format will not give you the correct tz
			var newdate = new Date(date.getTime() + date.getTimezoneOffset() * 60000);
			return newdate.format(dateFormat);
		};

		this.eventSource = new Timeline.DefaultEventSource();

		// TODO: theme the viz in admin
		var theme = Timeline.ClassicTheme.create();
		theme.event.bubble.width = 320;
		theme.event.bubble.height = 520;
		theme.event.track.height = 11.5;
		theme.event.track.gap = 0.1;
		theme.ether.backgroundColors = [ "#000000", "red" ];

		theme.ether.highlightColor = 'red';

		Timeline.setDefaultTheme(theme);

		var bandBase = {
				trackGap : 0.2,
				width : "70%",
				intervalUnit : Timeline.DateTime.DAY,
				intervalPixels : 50
			};

		var bandTracks = [];

		for (var b = 0; b < json.bands.length; b ++) {
			var bandClone = Object.clone(bandBase);
			bandClone.width = json.bands[b].width;
			bandClone.intervalUnit = json.bands[b].intervalUnit;
			bandClone.overview = json.bands[b].overview;
			bandClone.eventSource = this.eventSource;
			bandClone.theme = theme;
			bandTracks.push(Timeline.createBandInfo(bandClone));
		}

		// Sync the bands to scroll together
		for (b = 1; b < json.bands.length; b ++) {
			bandTracks[b].syncWith = 0;
			bandTracks[b].highlight = true;
		}

		SimileAjax.History.enabled = false;
		this.tl = Timeline.create(document.id("my-timeline"), bandTracks, this.options.orientation);

		// this.eventSource.loadJSON(this.json, '');

		this.start = 0;

		//http://dev.ecicultuurfabriek.nl/administrator/index.php?currentList=43&format=raw&option=com_fabrik&task=visualization.ajax_getEvents&visualizationid=3

		var data = {
			'option': 'com_fabrik',
			'format': 'raw',
			'task': 'ajax_getEvents',
			'view': 'visualization',
			'visualizationid': this.options.id,
			'currentList': this.options.currentList,
			setListRefFromRequest: 1,
			listref: this.options.listRef
		};
		// Add the filters the the data array.
		data = Object.merge(data, this.options.urlfilters);
		
		if (this.options.admin) {
			data.task = 'visualization.ajax_getEvents';
		} else {
			data.controller = 'visualization.timeline';
		}
		this.start = 0;
		this.counter = new Element('div.timelineTotals').inject(document.id("my-timeline"), 'before');
		this.counter.set('text', 'loading');
		this.ajax = new Request.JSON({
			url: 'index.php',
			data: data,
			onSuccess: function (json) {
				this.start = this.start + this.options.step;
				if (this.start >= json.fabrik.total) {
					this.counter.set('text', 'loaded ' + json.fabrik.total);
				} else {
					this.counter.set('text', 'loading ' + this.start + ' / ' + json.fabrik.total);
				}

				this.eventSource.loadJSON(json.timeline.events, '');
				if (json.fabrik.done.toInt() === 0) {
					this.ajax.options.data.start = json.fabrik.next;
					this.ajax.options.data.currentList = json.fabrik.currentList;
					this.ajax.send();
				}
			}.bind(this),
			onFailure: function (xhr) {
				alert(xhr.status + ': ' + xhr.statusText);
			}
		});

		Fabrik.addEvent('fabrik.advancedSearch.submit', function (e) {
			console.log('cancel ajax');
			this.ajax.cancel();
		}.bind(this));

		this.ajax.send();

		window.addEvent('resize', function () {
			if (this.resizeTimerID === null) {
				this.resizeTimerID = window.setTimeout(function () {
					this.resizeTimerID = null;
					this.tl.layout();
				}.bind(this), 500);
			}
		}.bind(this));

		this.watchDatePicker();
	},

	watchDatePicker: function () {
		var dateEl = document.id('timelineDatePicker');
		if (typeOf(dateEl) === 'null') {
			return;
		}
		var params = {'eventName': 'click',
				'ifFormat': this.options.dateFormat,
				'daFormat': this.options.dateFormat,
				'singleClick': true,
				'align': "Br",
				'range': [1900, 2999],
				'showsTime': false,
				'timeFormat': '24',
				'electric': true,
				'step': 2,
				'cache': false,
				'showOthers': false,
				'advanced': false };
		var dateFmt = this.options.dateFormat;
		params.date = Date.parseDate(dateEl.value || dateEl.innerHTML, dateFmt);
		params.onClose = function (cal) {
			cal.hide();
		};
		params.onSelect = function () {
			if (this.cal.dateClicked || this.cal.hiliteToday) {
				this.cal.callCloseHandler();
				dateEl.value = this.cal.date.format(dateFmt);
				this.tl.getBand(0).setCenterVisibleDate(this.cal.date);
			}
		}.bind(this);

		params.inputField = dateEl;
		params.button = document.id('timelineDatePicker_cal_img');
		params.align = "Tl";
		params.singleClick = true;

		this.cal = new Calendar(0,
				params.date,
				params.onSelect,
				params.onClose);

		this.cal.showsOtherMonths = params.showOthers;
		this.cal.yearStep = params.step;
		this.cal.setRange(params.range[0], params.range[1]);
		this.cal.params = params;

		this.cal.setDateFormat(dateFmt);
		this.cal.create();
		this.cal.refresh();
		this.cal.hide();

		if (typeOf(params.button) !== 'null') {
			params.button.addEvent('click', function (e) {
				this.cal.showAtElement(params.button);
				this.cal.show();
			}.bind(this));
		}
		dateEl.addEvent('blur', function (e) {
			this.updateFromField();
		}.bind(this));

		dateEl.addEvent('keyup', function (e) {
			if (e.key === 'enter') {
				this.updateFromField();
			}
		}.bind(this));

	},

	updateFromField: function () {
		var dateStr = document.id('timelineDatePicker').value;
		d = Date.parseDate(dateStr, this.options.dateFormat);
		this.cal.date = d;
		var newdate = new Date(this.cal.date.getTime() - (this.cal.date.getTimezoneOffset() * 60000));
		//this.tl.getBand(0).setCenterVisibleDate(newdate);
		this.tl.getBand(0).scrollToCenter(newdate);

	},

	/**
	 * Ajax advanced search filter called
	 * @TODO implement this
	 */
	submit: function () {

	}
});