/**
 * Date Element Filter
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var JDateFilter = new Class({

	Implements: [Options],

	options: {
	},

	initialize: function (opts) {
		this.setOptions(opts);
		this.cals = {};
		for (var i = 0; i < this.options.ids.length; i ++) {
			this.makeCalendar(this.options.ids[i], this.options.buttons[i]);
		}
	},

	getDateField: function(id) {
         return document.id(id);
	},

	makeCalendar: function (id, button) {
		this.cals[id] = null;

        this.getDateField(id).onchange = function (id) {
            this.calSelect(id);
        }.bind(this);

		return this.cals[id];
	},

	/**
	 * run when calendar poped up - goes over each date and should return true if you dont want the date to be
	 * selectable
	 */
	dateSelect: function (date)
	{
		return false;
	},

	calSelect: function (id) {
		if (event.target.calendar) {
            this.getJCal(event.target.calendar.inputField.id);
        }
	},

    /**
     * Get the associated JoomlaCalendar
     *
     * @return  JoomlaCalendar
     */
    getJCal: function (id) {
        this.cals[id] = JoomlaCalendar.getCalObject(this.getDateField(id))._joomlaCalendar;

        return this.cals[id];
    },


	update: function (calendar, date) {
		if (date) {
			if (typeOf(date) === 'string') {
				date = Date.parse(date);
			}
			calendar.params.inputField.value = date.format(this.options.calendarSetup.ifFormat);
		}
	},

	onSubmit: function () {
    	var self = this;
		jQuery.each(this.cals, function (id, c) {
			var cal = self.getJCal(id);
			if (cal) {
                if (cal.inputField.value !== '') {
                    cal.inputField.value = cal.date.format('db');
                }
            }
		});
	},

	onUpdateData: function () {
    	var self = this;
		jQuery.each(this.cals, function (id, c) {
            var cal = self.getJCal(id);
			if (cal.inputField.value !== '') {
				this.update(cal, cal.date);
			}
		});
	}
});