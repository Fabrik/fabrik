/**
 * Calendar Visualization
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var fabrikFullcalendar = new Class({
	Implements: [Options],
	options: {
		url: {
			'del': 'index.php?option=com_fabrik&controller=visualization.fullcalendar&view=visualization&task=deleteEvent&format=raw'
		},
	},

	initialize: function (ref, options) {
		this.el  = document.id(ref);
		this.setOptions(options);
		this.date = new Date();
		this.clickdate = null;
		this.ajax = {};
		
		this.windowopts = {
				'id': 'addeventwin',
				title: '',
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
			if (rightbuttons.length > 0) {
				rightbuttons += ',';
			}
			rightbuttons += 'agendaDay';
		}
		if (rightbuttons.length > 0) {
			rightbuttons = 'month,'+ rightbuttons;
		}
		var dView = 'month';
		switch(this.options.default_view) {
			case 'monthView':
				break;
			case 'weekView':
				if (this.options.show_week !== false) {	
					dView = 'agendaWeek'; 
				}
				break;
			case 'dayView':
				if (this.options.show_day !== false) { 
					dView = 'agendaDay'; 
				}
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

		/* below are the standard options we support, any extras or overrides should be in 
		 * the calendar override option of the visualization 
		 */
		var calOptions = {
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
			defaultTimedEventDuration: this.options.minDuration,
			minTime: this.options.open, // a start time (10am in this example)
			maxTime : this.options.close, // an end time (6pm in this example)
	        eventClick: function (calEvent, jsEvent, view) {
	        	self.clickEntry(calEvent);
	        	return false;
	        },
			dayClick: dayClickCallback,
			viewRender:function(view, e) {
				if (view.name == 'month' && self.options.greyscaledweekend === true) {
					jQuery("td.fc-sat").css('background',"#f2f2f2");
					jQuery("td.fc-sun").css('background',"#f2f2f2");
				}
			},
			eventRender: function (event, element) {
			    element.find('.fc-title').html(event.title);
			},
			loading: function(start) {
				if (!start){
					jQuery('.fc-view-container').delegate('.popover button.jclose', 'click', function() {
						var popover = jQuery(this).data('popover');
						jQuery('#'+popover).popover('hide');
					});
				}
			}
		};
		/* Now merge any calendar overrides/additions from the visualixation */
		jQuery.extend(true, calOptions, JSON.parse(self.options.calOptions));
	    jQuery('#calendar').fullCalendar(calOptions);
		
		document.addEvent('click:relay(button[data-task=viewCalEvent], a[data-task=viewCalEvent])', function (event, target) {
			event.preventDefault();
			var id = event.target.findClassUp('calEventButtons').id;
			id = id.replace(/_buttons/, '');
			var calEvent = jQuery('#calendar').fullCalendar('clientEvents', id)[0];
			jQuery('#' + id).popover('hide');
			this.viewEntry(calEvent);
		}.bind(this));
		
		document.addEvent('click:relay(button[data-task=editCalEvent], a[data-task=editCalEvent])', function (event, target) {
			event.preventDefault();
			var id = event.target.findClassUp('calEventButtons').id;
			id = id.replace(/_buttons/, '');
			var calEvent = jQuery('#calendar').fullCalendar('clientEvents', id)[0];
			jQuery('#' + id).popover('hide');
			this.editEntry(calEvent);
		}.bind(this));
		
		document.addEvent('click:relay(button[data-task=deleteCalEvent], a[data-task=deleteCalEvent])', function (event, target) {
			event.preventDefault();
			var id = event.target.findClassUp('calEventButtons').id;
			id = id.replace(/_buttons/, '');
			var calEvent = jQuery('#calendar').fullCalendar('clientEvents', id)[0];
			jQuery('#' + id).popover('hide');
			this.deleteEntry(calEvent);
		}.bind(this));
		
		jQuery(document).on('click', '.popover .jclose', function (event, target) {
			event.preventDefault();
			var id = jQuery(event.target).attr("data-popover");
			jQuery('#' + id).popover('hide');
		}.bind(this));

		this.ajax.deleteEvent = new Request({
			url: this.options.url.del,
			'data': {
				'visualizationid': this.options.calendarId
			},
			'onComplete': function () {
				jQuery('#calendar').fullCalendar( 'refetchEvents' );
			}.bind(this)
		});
	},
	
	processEvents: function (json, callback) {
		json = $H(JSON.decode(json));
		var events = [];
		json.each(function (e) {
			var popup = jQuery(Fabrik.jLayouts['fabrik-visualization-fullcalendar-event-popup'])[0];
			var id = e._listid + "_" + e.id;
			popup.id = "fabrikevent_" + id;

			var body = jQuery(Fabrik.jLayouts['fabrik-visualization-fullcalendar-viewevent'])[0];
			var mStartDate = moment(e.startdate_locale); 
			var mEndDate = moment(e.enddate_locale);
			var dispStartDate = dispEndDate = "";
			if (moment(mEndDate.format("YYYY-MM-DD")) > moment(mStartDate.format("YYYY-MM-DD"))
				|| (e.startShowTime === false && e.endShowTime === false) ) {
				dispStartDate = mStartDate.format("MMM DD") + " ";
				dispEndDate = mEndDate.format("MMM DD") + " ";
			}
			var dispStartTime = dispEndTime = "";
			if (e.startShowTime === true && e.endShowTime === true) {
				dispStartTime = mStartDate.format("hh.mm A");
				dispEndTime = mEndDate.format("hh.mm A");
			}
			body.getElement("#viewstart").innerHTML = dispStartDate + dispStartTime;
			body.getElement("#viewend").innerHTML = dispEndDate + dispEndTime;

			var buttons = jQuery(Fabrik.jLayouts['fabrik-visualization-fullcalendar-viewbuttons'])[0];
			jQuery(buttons)[0].id = "fabrikevent_buttons_" + id;

			// Hide the buttons the user cannot see or add the tooltip text if button is visible
			var bDelete = buttons.getElement(".popupDelete");
			e._canDelete === false ? bDelete.destroy() 
				: bDelete.setProperty('title', Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_DELETE'));
			var bEdit = buttons.getElement(".popupEdit");
			e._canEdit === false ? bEdit.destroy() 
				: bEdit.setProperty('title', Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_EDIT'));
			var bView = buttons.getElement(".popupView");
			e._canView === false ? bView.destroy()
				: bView.setProperty('title', Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_VIEW'));
				
			jQuery(popup).attr('data-content', jQuery(body).prop('outerHTML')+jQuery(buttons).prop('outerHTML'));
			
			var width = (dispStartDate == "" ? "auto" : "200px");
			jQuery(popup).attr('data-title',  '<div style="width:' + width + 
				'"><div style="float:left;"><button class="btn jclose" data-popover="' + popup.id + 
				'" data-toggle="tooltip" title="' + Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_CLOSE') +
				'"><i class="icon-delete"></i></button></div><div style="text-align:center;">' + e.label + '</div></div>');
			jQuery(popup).append(e.label);
			
			events.push(
				{
					id: popup.id,
					title: jQuery(popup).prop('outerHTML'),
					start: e.startdate_locale,
					end: e.enddate_locale,
					url: e.link,
					listid: e._listid,
					rowid: e.__pk_val,
					formid: e._formid
				}
			);
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
		this.windowopts.title = o.title;
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
					default:
						break;
					}
				}
			});
			win.fitToContent(false);
		}.bind(this);
		
		Fabrik.getWindow(this.windowopts);
	},
	
	viewEntry: function (calEvent) {
		this.clickdate = null;
		var o = {};
		o.id = calEvent.formid;
		o.rowid = calEvent.rowid;
		o.listid = calEvent.listid;
		o.nextView = 'details';
		o.title = Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_VIEW_EVENT');
		this.addEvForm(o);
	},
	
	editEntry: function (calEvent) {
		this.clickdate = null;
		var o = {};
		o.id = calEvent.formid;
		o.rowid = calEvent.rowid;
		o.listid = calEvent.listid;
		o.nextView = 'form';
		o.title = Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_EDIT_EVENT');
		this.addEvForm(o);
	},
	
	deleteEntry: function (calEvent) {
		if (confirm(Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_CONF_DELETE'))) {
			this.ajax.deleteEvent.options.data = {'id': calEvent.rowid, 'listid': calEvent.listid};
			this.ajax.deleteEvent.send();
		}
	},

	clickEntry: function (calEvent) {
		if (this.options.showFullDetails === false) {
			var popoverId = 'fabrikevent_' + calEvent.listid + '_' + calEvent.rowid;
			jQuery('#' + popoverId).popover('show');
		} else {
			this.viewEntry(calEvent);
		}
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
			o.listid = this.options.eventLists[0].value;
			o.title = Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_ADD_EVENT');
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
	}

})
