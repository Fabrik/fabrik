/**
 * Timer Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
    window.FbTimer = new Class({

        options: {
            defaultVal    : '',
            editable      : false,
            startCrono    : '15:00',
            endCrono      : '00:00',
            div           : false,
            stopOnComplete: true,
            onComplete    : function () {
            },
            onEveryMinute : function () {
            },
            onEveryHour   : function () {
            }
        },

        Extends: FbElement,

        initialize: function (element, options) {
            this.setPlugin('fabriktimer');
            this.parent(element, options);
            var b = document.id(this.options.element + '_button');
            this.seg = 0;
            this.min = 0;
            this.hour = 0;
            if (this.options.autostart === true) {
                if (typeOf(b) !== 'null') {
                    b.getElement('span').set('text', Joomla.JText._('PLG_ELEMENT_TIMER_STOP'));
                }
                this.start();
            } else {
                this.state = 'paused';
            }

            this.incremental = 1;
            if (typeOf(b) !== 'null') {
                b.addEvent('click', function (e) {
                    e.stop();
                    if (this.state === 'started') {
                        this.pause();
                        //b.value = Joomla.JText._('PLG_ELEMENT_TIMER_START');
                        b.getElement('span').set('text', Joomla.JText._('PLG_ELEMENT_TIMER_START'));
                    } else {
                        var v = this.element.value.split(':');
                        switch (v.length) {
                            case 3:
                                this.hour = (v[0] === '') ? 0 : v[0].toInt();
                                this.min = (v[1] === '') ? 0 : v[1].toInt();
                                this.seg = (v[2] === '') ? 0 : v[2].toInt();
                                break;
                            case 2:
                                this.min = (v[0] === '') ? 0 : v[0].toInt();
                                this.seg = (v[1] === '') ? 0 : v[1].toInt();
                                break;
                            case 1:
                                this.seg = (v[0] === '') ? 0 : v[0].toInt();
                                break;
                        }
                        this.start();
                        //b.value = Joomla.JText._('PLG_ELEMENT_TIMER_STOP');
                        b.getElement('span').set('text', Joomla.JText._('PLG_ELEMENT_TIMER_STOP'));
                    }
                }.bind(this));
            }
        },

        start: function () {
            if (this.state !== 'started') {
                this.timer = this.count.periodical(1000, this);
                this.state = 'started';
            }
        },

        pause: function () {
            if (this.state !== 'paused') {
                clearInterval(this.timer);
                this.state = 'paused';
            }
        },

        count: function () {
            this.seg += this.incremental;
            if ((this.seg === -1) || (this.seg === 60)) {
                this.seg = (this.incremental > 0) ? 0 : 59;
                this.min += this.incremental;
                //this.fireEvent('onEveryMinute', '');
                if (this.min === -1 || this.min === 60) {
                    this.min = (this.incremental > 0) ? 0 : 59;
                    this.hour += this.incremental;
                    //this.fireEvent('onEveryHour', '');
                }
            }
            this.element.value = this.time();
            if ((this.min === this.endMin) && (this.seg === this.endSeg)) {
                this.fireEvent('onComplete', '');
                if (this.options.stopOnComplete) {
                    this.pause();
                }
            }
        },

        time: function () {
            var time_to_show = (this.hour < 10) ? '0' + this.hour : this.hour;
            time_to_show += ((this.min < 10) ? ':0' : ':') + this.min;
            time_to_show += ((this.seg < 10) ? ':0' : ':') + this.seg;
            return time_to_show;
        },

        reset: function () {
            //reset time to initial values
            var start_array = this.options.startCrono.split(':'),
                end_array = this.options.endCrono.split(':');

            this.startMin = start_array[0].toInt();
            this.startSeg = start_array[1].toInt();

            this.endMin = end_array[0].toInt();
            this.endSeg = end_array[1].toInt();

            if (this.endMin !== this.startMin) {
                this.incremental = (this.endMin > this.startMin) ? 1 : -1;
            } else {
                this.incremental = (this.endSeg > this.startSeg) ? 1 : -1;
            }
            this.min = this.startMin;
            this.seg = this.startSeg;

            if (this.options.div !== false) {
                document.id(this.options.div).set('text', this.time());
            }
        }
    });

    return window.FbTimer;
});