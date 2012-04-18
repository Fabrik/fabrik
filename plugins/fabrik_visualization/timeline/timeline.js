//move time line to given date:
//timeline.tl.getBand(1).setCenterVisibleDate(new Date('2009', '9', '12'))
//timeline.tl.getBand(1).scrollToCenter(new Date('2009', '9', '12'))

var FbVisTimeline = new Class({
	
	Implements: [Options],
	
	options: {},
	
	initialize : function (json, options) {
		this.json = eval(json);
		this.setOptions(options);

		this.resizeTimerID = null;
		this.tl = null;

		var eventSource = new Timeline.DefaultEventSource();
		// TODO: theme the viz in admin
		var theme = Timeline.ClassicTheme.create();
		theme.event.bubble.width = 320;
		theme.event.bubble.height = 220;
		theme.event.track.height = 1.5;
		theme.event.track.gap = 0.1;
		theme.ether.backgroundColors = [ "#FFFFFF", "#CFCFCF" ];

		// create the timeline
		var bandInfos = [ Timeline.createBandInfo({
			trackGap : 0.2,
			width : "70%",
			intervalUnit : Timeline.DateTime.DAY,
			intervalPixels : 50,
			eventSource : eventSource,
			theme : theme

		}), Timeline.createBandInfo({
			showEventText : false,
			trackHeight : 0.5,
			trackGap : 0.2,
			width : "30%",
			intervalUnit : Timeline.DateTime.MONTH,
			intervalPixels : 150,
			eventSource : eventSource,
			theme : theme

		}) ];
		bandInfos[1].syncWith = 0;
		bandInfos[1].highlight = true;

		this.tl = Timeline.create($("my-timeline"), bandInfos);
		eventSource.loadJSON(this.json, '');

		window.addEvent('resize', function () {
			if (this.resizeTimerID === null) {
				this.resizeTimerID = window.setTimeout(function () {
					this.resizeTimerID = null;
					this.tl.layout();
				}.bind(this), 500);
			}
		}.bind(this));

	}

});