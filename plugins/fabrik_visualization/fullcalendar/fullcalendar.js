/**
 * Calendar Visualization
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(['jquery', 'fab/fabrik', 'fullcalendar'], function (jQuery, Fabrik, fc) {

    var FullCalendar = new Class({
        Implements: [Options],
        options   : {
            canAdd           : false,
            show_week        : false,
            show_day         : false,
            default_view     : 'dayView',
            time_format      : '',
            first_week_day   : 1,
            minDuration      : 0,
            greyscaledweekend: false,
            calOptions       : {},
            startOffset      : 0,
            url              : {
                'del': 'index.php?option=com_fabrik&controller=visualization.fullcalendar&view=visualization&' +
                'task=deleteEvent&format=raw'
            }
        },

        initialize: function (ref, options) {
            var script, self = this,
                rightButtons = '',
                eventSources = [];
            this.el = jQuery('#' + ref);
            this.calendar = this.el.find('*[data-role="calendar"]');
            this.setOptions(options);
            this.date = new Date();
            this.clickdate = null;
            this.ajax = {};

            this.windowopts = {
                'id'       : 'addeventwin',
                title      : '',
                loadMethod : 'xhr',
                minimizable: false,
                evalScripts: true
            };

            this.el.find('.addEventButton').on('click', function (e) {
                e.preventDefault();
                self.openAddEvent(e);
            });

            Fabrik.addEvent('fabrik.form.submitted', function (form, json) {
                self.calendar.fullCalendar('refetchEvents');
                Fabrik.Windows.addeventwin.close();
            });

            this.options.eventLists.each(function (eventList, eventListKey) {
                eventSources.push({
                    events: function (start, end, timezone, callback) {
                        new Request({
                            url        : this.options.url.add + '&listid=' + eventList.value + '&eventListKey=' +
                            eventListKey,
                            evalScripts: true,
                            onSuccess  : function (e, json) {
                                if (typeOf(json) !== 'null') {
                                    this.processEvents(json, callback);
                                }
                            }.bind(this, callback)
                        }).send();
                    }.bind(this),
                    color : eventList.colour
                });
            }.bind(this));

            if (this.options.show_week !== false) {
                rightButtons += 'agendaWeek';
            }
            if (this.options.show_day !== false) {
                if (rightButtons.length > 0) {
                    rightButtons += ',';
                }
                rightButtons += 'agendaDay';
            }
            if (rightButtons.length > 0) {
                rightButtons = 'month,' + rightButtons;
            }
            var dView = 'month';
            switch (this.options.default_view) {
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

            function dayClickCallback(date, e, view) {
                slotMoment = date;
                slotView = view.name;
                self.calendar.on('mousemove', forgetSlot);
            }

            function forgetSlot() {
                slotMoment = slotView = null;
                self.calendar.off('mousemove', forgetSlot);
            }

            this.calendar.dblclick(function (e) {
                if (slotMoment) {
                    self.openAddEvent(e, slotView, slotMoment);
                }
            });

            /* below are the standard options we support, any extras or overrides should be in
             * the calendar override option of the visualization
             */
            this.calOptions = {
                header                   : {
                    left  : 'prev,next today',
                    center: 'title',
                    right : rightButtons
                },
                fixedWeekCount           : false,
                timeFormat               : this.options.time_format,
                defaultView              : dView,
                nextDayThreshold         : '00:00:00',
                firstDay                 : this.options.first_week_day,
                eventSources             : eventSources,
                defaultTimedEventDuration: this.options.minDuration,
                minTime                  : this.options.open, // a start time (10am in this example)
                maxTime                  : this.options.close, // an end time (6pm in this example)
				weekends				 : this.options.showweekends,
                eventClick               : function (calEvent, jsEvent, view) {
                    jsEvent.stopPropagation();
                    jsEvent.preventDefault();
                    self.clickEntry(calEvent);
                    return false;
                },
                dayClick                 : dayClickCallback,
                viewRender               : function (view, e) {
                    if (self.options.greyscaledweekend === true) {
                        jQuery('td.fc-sat').css('background', '#f2f2f2');
                        jQuery('td.fc-sun').css('background', '#f2f2f2');
                    }
               },
                eventRender              : function (event, element) {
                    element.find('.fc-title').html(event.title);
                },
                loading                  : function (start) {
                    if (!start) {
//                        jQuery('.fc-view-container').delegate('.popover button.jclose', 'click', function () {
//                            var popover = jQuery(this).data('popover');
//                            jQuery('#' + popover).popover('hide');
//                        });
                    }
                }
            };
            /* Now merge any calendar overrides/additions from the visualisation */
            jQuery.extend(true, this.calOptions, JSON.parse(self.options.calOptions));
            this.calendar.fullCalendar(this.calOptions);

            document.addEvent('click:relay(button[data-task=viewCalEvent], a[data-task=viewCalEvent])',
                function (event) {
                    event.preventDefault();
                    var id = jQuery(event.target).closest('.calEventButtonsID')[0].id;
                    id = id.replace(/_buttons/, '');
                    var calEvent = self.calendar.fullCalendar('clientEvents', id)[0];
                    jQuery('#fabrikEvent_modal').modal('hide');
                    self.viewEntry(calEvent);
                });

            document.addEvent('click:relay(button[data-task=editCalEvent], a[data-task=editCalEvent])',
                function (event) {
                    event.preventDefault();
                    var id = jQuery(event.target).closest('.calEventButtonsID')[0].id;
                    id = id.replace(/_buttons/, '');
                    var calEvent = self.calendar.fullCalendar('clientEvents', id)[0];
                    jQuery('#fabrikEvent_modal').modal('hide');
                    self.editEntry(calEvent);
                });

            document.addEvent('click:relay(button[data-task=deleteCalEvent], a[data-task=deleteCalEvent])',
                function (event) {
                    event.preventDefault();
                    var id = jQuery(event.target).closest('.calEventButtonsID')[0].id;
                    id = id.replace(/_buttons/, '');
                    var calEvent = self.calendar.fullCalendar('clientEvents', id)[0];
                    jQuery('#fabrikEvent_modal').modal('hide');
                    self.deleteEntry(calEvent);
                });

            this.ajax.deleteEvent = new Request({
                url         : this.options.url.del,
                'data'      : {
                    'visualizationid': this.options.calendarId
                },
                'onComplete': function () {
                    self.calendar.fullCalendar('refetchEvents');
                }
            });

            function reposition() {
                var modal = jQuery(this);
                modal.css('display', 'block');
                modal.css('margin-top', Math.max(0, (jQuery(window).height() - modal.height()) / 2));
                //modal.css('margin-left', Math.max(0, (jQuery(window).width() - modal.width()) / 2));
                var modalWidth = modal.width(),
                    modalMargin = '-' + (modalWidth/2) + 'px!important';
                modal.css('margin-left',modalMargin);
            }

            // Reposition when a modal is shown
            jQuery('.modal').on('show.bs.modal', reposition);
            // Reposition when the window is resized
            jQuery(window).on('resize', function() {
                jQuery('.modal:visible').each(reposition);
            });
        },

        processEvents: function (json, callback) {
            json = $H(JSON.decode(json));
            var events = [], dispStartTime, dispEndTime, buttons, width, bDelete, bEdit, bView,
                dispStartDate, dispEndDate, popup, id, body, mStartDate, mEndDate;
            json.each(function (e) {
                popup = jQuery(Fabrik.jLayouts['fabrik-visualization-fullcalendar-event-popup'])[0];
                id = e._listid + '_' + e.id;
                popup.id = 'fabrikevent_' + id;
                body = jQuery(Fabrik.jLayouts['fabrik-visualization-fullcalendar-viewevent'])[0];
                mStartDate = moment(e.startdate);
                mEndDate = moment(e.enddate);
                dispStartDate = dispEndDate = '';
                if (moment(mEndDate.format('YYYY-MM-DD')) > moment(mStartDate.format('YYYY-MM-DD')) ||
                    (e.startShowTime === false && e.endShowTime === false)) {
                    dispStartDate = mStartDate.format('MMM DD') + ' ';
                    dispEndDate = mEndDate.format('MMM DD') + ' ';
                }
                dispStartTime = dispEndTime = '';
                if (e.startShowTime === true && e.endShowTime === true) {
                    dispStartTime = mStartDate.format('hh.mm A');
                    dispEndTime = mEndDate.format('hh.mm A');
                }
                body.getElement('#viewstart').innerHTML = dispStartDate + dispStartTime;
                body.getElement('#viewend').innerHTML = dispEndDate + dispEndTime;
                jQuery(popup).attr('data-content', jQuery(body).prop('outerHTML'));

                buttons = jQuery(Fabrik.jLayouts['fabrik-visualization-fullcalendar-viewbuttons']);
                buttons[0].id = 'fabrikevent_buttons_' + id;

                // Hide the buttons the user cannot see or add the tooltip text if button is visible
                bDelete = buttons.find('.popupDelete');
                e._canDelete === false ? bDelete.remove()
                    : bDelete.attr('title', Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_DELETE'));
                bEdit = buttons.find('.popupEdit');
                e._canEdit === false ? bEdit.remove()
                    : bEdit.attr('title', Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_EDIT'));
                bView = buttons.find('.popupView');
                e._canView === false ? bView.remove()
                    : bView.attr('title', Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_VIEW'));
                jQuery(popup).attr('data-buttons', buttons.prop('outerHTML'));

//                width = (dispStartDate === '' ? 'auto' : '200px');
                jQuery(popup).attr('data-title', e.label);
                jQuery(popup).append(e.label);
				
                events.push(
                    {
                        id        : popup.id,
                        title     : jQuery(popup).prop('outerHTML'),
                        start     : e.startdate,
                        end       : e.enddate,
                        url       : e.link,
                        customURL : e.customLink,
                        className : e.status,
                        allDay    : e.allday,
                        listid    : e._listid,
                        rowid     : e.__pk_val,
                        formid    : e._formid
                    }
                );
            }.bind(events));

            callback(events);
        },

        /**
         * Create window for add event form
         *
         * @param {object}  o
         */
        addEvForm: function (o) {
            var self = this;
            if (typeof(jQuery) !== 'undefined') {
                jQuery(this.popOver).popover('hide');
            }

            this.windowopts.id = 'addeventwin';
            var url = 'index.php?option=com_fabrik&controller=visualization.fullcalendar' +
                '&view=visualization&task=addEvForm&listid=' + o.listid + '&rowid=' + o.rowid;
            //	url += '&jos_fabrik_calendar_events___visualization_id=' + this.options.calendarId;
            url += '&visualizationid=' + this.options.calendarId;
            url += '&format=partial';

            if (o.nextView) {
                url += '&nextview=' + o.nextView;
            }

            url += '&fabrik_window_id=' + this.windowopts.id;
            if (this.clickdate !== null) {
                /* Add offset to start date */
                this.clickdate = moment(this.clickdate).add({h:this.options.startOffset}).format('YYYY-MM-DD HH:mm:ss')
                /* Add the default minimum duration to the end date */
                var minDur = self.calendar.fullCalendar('option', 'defaultTimedEventDuration').split(':');
                var endDate = moment(this.clickdate).add({
                    h: minDur[0],
                    m: minDur[1],
                    s: minDur[2]
                }).format('YYYY-MM-DD HH:mm:ss');
                url += '&start_date=' + this.clickdate + '&end_date=' + endDate;
            }
            this.windowopts.type = 'window';
            this.windowopts.contentURL = url;
            this.windowopts.title = o.title;
            this.windowopts.modalId = 'fullcalendar_addeventwin';
            var f = this.options.filters;

            this.windowopts.onContentLoaded = function () {
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
                this.fitToContent(false);
            };

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
            if (window.confirm(Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_CONF_DELETE'))) {
                this.ajax.deleteEvent.options.data = {'id': calEvent.rowid, 'listid': calEvent.listid};
                this.ajax.deleteEvent.send();
            }
        },

        clickEntry: function (calEvent) {
            if (calEvent.customURL !== '') {
                window.open(calEvent.customURL, '_blank');
            }
            else if (this.options.showFullDetails === false) {
                var feModal = jQuery('#fabrikEvent_modal.modal');
				feModal.find('.modal-title').html(jQuery('#' + calEvent.id).attr('data-title'));
				feModal.find('.modal-body').html(jQuery('#' + calEvent.id).attr('data-content'));
				feModal.find('.modal-footer .calEventButtons').html(jQuery('#' + calEvent.id).attr('data-buttons'));
                feModal.modal('show');
            } else {
                this.viewEntry(calEvent);
            }
        },

        /**
         * Open the add event form.
         *
         * @param {event} e    JQuery Event
         * @param {string} view The view which triggered the opening
         * @param {moment} theMoment
         */
        openAddEvent: function (e, view, theMoment) {
            var rawd, day, hour, min, m, y, o, now, theDay;

            if (this.options.canAdd === false) {
                return;
            }

            if (view === 'month' && this.options.readonlyMonth === true) {
                return;
            }

            switch (e.type) {
                case 'dblclick':
                    theDay = theMoment;
                    break;
                case 'click':
                    theDay = moment();
                    break;
                default:
                    window.alert('Unknown event in OpenAddEvent: ' + e.type);
                    return;
            }
            if (view === 'month') {
                hour = min = '00';
            } else {
                /* in week/day views use the time where the mouse was clicked */
                hour = ((hour = theDay.hour()) < 10) ? '0' + hour : hour;
                min = ((min = theDay.minute()) < 10) ? '0' + min : min;
            }
            day = ((day = theDay.date()) < 10) ? '0' + day : day;
            m = ((m = (theDay.month() + 1)) < 10) ? '0' + m : m;
            y = theDay.year();

            this.clickdate = y + '-' + m + '-' + day + ' ' + hour + ':' + min + ':00';

            if (e.type === 'dblclick' && !this.dateInLimits(this.clickdate)) {
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
                this.addEvForm(o);
            }
        },

        dateInLimits: function (date) {
            var d = new moment(date);

            if (this.options.dateLimits.min !== '') {
                var min = new moment(this.options.dateLimits.min);
                if (d.isBefore(min)) {
                    window.alert(Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_DATE_ADD_TOO_EARLY'));
                    return false;
                }
            }

            if (this.options.dateLimits.max !== '') {
                var max = new moment(this.options.dateLimits.max);
                if (d.isAfter(max)) {
                    window.alert(Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_DATE_ADD_TOO_LATE'));
                    return false;
                }
            }

            return true;
        },

        openChooseEventTypeForm: function (d, rawd) {
            // Rowid is the record to load if editing
            var url = 'index.php?option=com_fabrik&tmpl=component&view=visualization&' +
                'controller=visualization.fullcalendar&task=chooseAddEvent&format=partial&id=' +
                this.options.calendarId + '&d=' + d + '&rawd=' + rawd;

            // Fix for renderContext when rendered in content plugin
            url += '&renderContext=' + this.el.prop('id').replace(/visualization_/, '');
            this.windowopts.contentURL = url;
            this.windowopts.id = 'chooseeventwin';
            this.windowopts.modalId = 'fullcalendar_!chooseeventwin';
            Fabrik.getWindow(this.windowopts);
        }

    });

    return FullCalendar;
});