/**
 * Calendar Visualization
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var fabrikFullcalendar = new Class({
	Implements: [Options],
	options: {
	},

	initialize: function (ref, options) {
		this.el  = document.id(ref);
		this.setOptions(options);
		this.date = new Date();
		this.clickdate = null;
		
		this.windowopts = {
				'id': 'addeventwin',
				title: 'add/edit event',
				loadMethod: 'xhr',
				minimizable: false,
				evalScripts: true,
				width: 380,
				height: 320,
				onContentLoaded: function (win) {
					win.fitToContent();
				}.bind(this)
			};
		
		if (typeOf(this.el.getElement('.addEventButton')) !== 'null') {
			this.el.getElement('.addEventButton').addEvent('click', function (e) {
				this.openAddEvent(e);
			}.bind(this));
		}

		Fabrik.addEvent('fabrik.form.submitted', function (form, json) {
			//Fabrik.Windows['chooseeventwin'].close();
			//this.addEvForm(json);
			//this.ajax.updateEvents.send();
			jQuery('#calendar').fullCalendar( 'refetchEvents' );
			Fabrik.Windows.addeventwin.close();
		}.bind(this));
		
		var eventSources = [];
		var urls = this.options.url;
		
		this.options.eventLists.each(function (eventList, eventListKey) {
			eventSources.push({
				events: new Function ("start", "end", "tz", "callback",
						"new Request({" +
							"'url': '" + this.options.url.add + "&listid=" + eventList.value + "&eventListKey=" + eventListKey + "'," +
							"'evalScripts': true," +
							"'onSuccess': function (e, json) {\n" +
								"if (typeOf(json) !== 'null') {\n" +
									/*"var json = r.stripScripts(true);" +*/
									"this.processEvents(json, callback);\n" +
								"}" +
							"}.bind(this, callback)" +
						"}).send();"
					).bind(this),
				color: eventList.colour
			});
		}.bind(this));
		
		var self = this;
		var rightbuttons = "";
		if (this.options.show_week !== false) {
			rightbuttons += 'agendaWeek';
		}
		if (this.options.show_day !== false) {
			if (rightbuttons.length > 0)
				rightbuttons += ',';
			rightbuttons += 'agendaDay';
		}
		if (rightbuttons.length > 0)
			rightbuttons = 'month,'+ rightbuttons;
		var dView = 'month';
		switch(this.options.default_view) {
			case 'monthView':
				break;
			case 'weekView':
				if (this.options.show_week !== false)
					dView = 'agendaWeek';
				break;
			case 'dayView':
				if (this.options.show_day !== false)
					dView = 'agendaDay';
				break;
			default:
				break;
		}
			
		var slotMoment = null, slotView = null;
    
		function dayClickCallback(date, e, view){
			slotMoment = date;
			slotView = view.name;
			jQuery("#calendar").on("mousemove", forgetSlot);
		}

		function forgetSlot(){
			slotMoment = slotView = null;
			jQuery("#calendar").off("mousemove", forgetSlot);
		}

		jQuery("#calendar").dblclick(function(e) {
			if(slotMoment){
	        	self.openAddEvent(e, slotView,  slotMoment);
			}
		});

	    jQuery('#calendar').fullCalendar({
			header: {
				left: 'prev,next today',
				center: 'title',
				right: rightbuttons
			},
			fixedWeekCount: false,
			timeFormat: this.options.time_format,
			defaultView: dView,
			nextDayThreshold: "00:00:00",
			firstDay: this.options.first_week_day,
	    	eventSources: eventSources,
	        // put your options and callbacks here
	        eventClick: function (calEvent, jsEvent, view) {
	        	self.viewEntry(calEvent);
	        	return false;
	        },
			defaultTimedEventDuration: this.options.minDuration,
			dayClick: dayClickCallback,
			viewRender:function(view, e) {
				if (view.name == 'month' && self.options.greyscaledweekend == true) {
					jQuery("td.fc-sat").css('background',"#f2f2f2");
					jQuery("td.fc-sun").css('background',"#f2f2f2");
				}
			},
			minTime: this.options.open, // a start time (10am in this example)
			maxTime : this.options.close // an end time (6pm in this example)

		});
		
	},
	
	processEvents: function (json, callback) {
		json = $H(JSON.decode(json));
		var events = [];
		json.each(function (e) {
			events.push(
				{
					title: e.label,
					start: e.startdate_locale,
					end: e.enddate_locale,
					url: e.link,
					listid: e._listid,
					rowid: e.__pk_val,
					formid: e._formid
				}
			)
		}.bind(events));
		callback(events);
	},
	
	/**
	 * Create window for add event form
	 * 
	 * @param  object  o
	 */
	addEvForm: function (o)
	{
		if (typeof(jQuery) !== 'undefined') {
			jQuery(this.popOver).popover('hide');
		}
		
		this.windowopts.id = 'addeventwin';
		var url = 'index.php?option=com_fabrik&controller=visualization.fullcalendar&view=visualization&task=addEvForm&format=raw&listid=' + o.listid + '&rowid=' + o.rowid;
		url += '&jos_fabrik_calendar_events___visualization_id=' + this.options.calendarId;
		url += '&visualizationid=' + this.options.calendarId;
		
		if (o.nextView) {
			url += '&nextview=' + o.nextView;
		}
		
		url += '&fabrik_window_id=' + this.windowopts.id;
		if (this.clickdate !== null) {
			/* First add the default minimum duration to the end date */
			var minDur = jQuery('#calendar').fullCalendar('option', 'defaultTimedEventDuration').split(":");
			var endDate = moment(this.clickdate).add({h:minDur[0], m:minDur[1], s:minDur[2]}).format('YYYY-MM-DD HH:mm:ss');
			url += '&start_date=' + this.clickdate + '&end_date=' + endDate;
		}
		this.windowopts.type = 'window';
		this.windowopts.contentURL = url;
		var f = this.options.filters;
	
		this.windowopts.onContentLoaded = function (win)
		{
			f.each(function (o) {
				if (document.id(o.key)) {
					switch (document.id(o.key).get('tag')) {
					case 'select':
						document.id(o.key).selectedIndex = o.val;
						break;
					case 'input':
						document.id(o.key).value = o.val;
						break;
					}
				}
			});
			win.fitToContent(false);
		}.bind(this);
		
		Fabrik.getWindow(this.windowopts);
	},
	
	viewEntry: function (calEvent) {
		var o = {};
		o.id = calEvent.formid;
		o.rowid = calEvent.rowid;
		o.listid = calEvent.listid;
		o.nextView = 'details';
		this.addEvForm(o);
	},
	
	/**
	 * Open the add event form.
	 * 
	 * @param e    Event
	 * @param view The view which triggered the opening
	 */
	openAddEvent: function (e, view, theMoment)
	{
		var rawd, day, hour, min, m, y, o, now, theDay;

		if (this.options.canAdd === false) {
			return;
		}
		
		if (view == 'month' && this.options.readonlyMonth === true) {
			return;
		}

		switch (e.type) {
			case 'dblclick':
				theDay = theMoment;
				break;
			case 'click':
				e.stop();
				theDay = moment();
				break;
			default:
				alert('Unknown event in OpenAddEvent: ' + e.type);
				return;
		}
		if (view == 'month') {
			hour = min = "00";
		} else {
			/* in week/day views use the time where the mouse was clicked */
			hour = ((hour = theDay.hour()) < 10) ? "0" + hour : hour;
			min = ((min = theDay.minute()) < 10) ? "0" + min : min;
		}
		day = ((day = theDay.date()) < 10) ? "0" + day : day;
		m = ((m = (theDay.month()+1)) < 10) ? "0" + m : m;
		y = theDay.year();

		this.clickdate = y + "-" + m + "-" + day + " " + hour + ":" + min + ":00";

		if (e.type == 'dblclick' && !this.dateInLimits(this.clickdate)) {
			return;
		}

		if (this.options.eventLists.length > 1) {
			this.openChooseEventTypeForm(this.clickdate, rawd);
		} else {
			o = {};
			o.rowid = '';
			o.id = '';
//			d = '&' + this.options.eventLists[0].startdate_element + '=' + this.clickdate;
			o.listid = this.options.eventLists[0].value;
			this.clickdate = null;
			this.addEvForm(o);
		}
	},
	
	dateInLimits: function (date) {
		var d = new moment(date);
		
		if (this.options.dateLimits.min !== '') {
			var min = new moment(this.options.dateLimits.min);
			if (d.isBefore(min)) {
				alert(Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_DATE_ADD_TOO_EARLY'));
				return false;
			}
		}
		
		if (this.options.dateLimits.max !== '') {
			var max = new moment(this.options.dateLimits.max);
			if (d.isAfter(max)) {
				alert(Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_DATE_ADD_TOO_LATE'));
				return false;
			}
		}
		
		return true;
	},
	
	openChooseEventTypeForm: function (d, rawd)
	{
		// Rowid is the record to load if editing
		var url = 'index.php?option=com_fabrik&tmpl=component&view=visualization&controller=visualization.fullcalendar&task=chooseaddevent&id=' + this.options.calendarId + '&d=' + d + '&rawd=' + rawd;

		// Fix for renderContext when rendered in content plugin
		url += '&renderContext=' + this.el.id.replace(/visualization_/, '');
		this.windowopts.contentURL = url;
		this.windowopts.id = 'chooseeventwin';
		this.windowopts.onContentLoaded = function ()
		{
			var myfx = new Fx.Scroll(window).toElement('chooseeventwin');
		};
		Fabrik.getWindow(this.windowopts);
	},

})
