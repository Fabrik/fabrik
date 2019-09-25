/**
 * Created by rob on 21/03/2016.
 */

define(['jquery'], function (jQuery) {
    /**
     * This class is temporarily required until this patch makes it into the CMS
     * code: https://github.com/joomla/joomla-platform/pull/1209/files Its purpose
     * is to queue ajax requests so they are not all fired at the same time - which
     * result in db session errors.
     *
     * Currently this is called from: fabriktables.js
     *
     */
    var RequestQueue = new Class({

        Binds: [],

        queue: {}, // object of xhr objects

        initialize: function () {
            this.periodical = this.processQueue.periodical(500, this);
        },

        add: function (xhr) {
            var k = xhr.options.url + Object.toQueryString(xhr.options.data) + Math.random();
            if (!this.queue[k]) {
                this.queue[k] = xhr;
            }
        },

        processQueue: function () {
            if (Object.keys(this.queue).length === 0) {
                return;
            }
            var running = false;

            // Remove successfully completed xhr
            $H(this.queue).each(function (xhr, k) {
                if (xhr.isSuccess()) {
                    delete (this.queue[k]);
                    running = false;
                } else {
                    if (xhr.status === 500) {
                        console.log('Fabrik Request Queue: 500 ' + xhr.xhr.statusText);
                        delete (this.queue[k]);
                        running = false;
                    }
                }
            }.bind(this));

            // Find first xhr not run and completed to run
            $H(this.queue).each(function (xhr, k) {
                if (!xhr.isRunning() && !xhr.isSuccess() && !running) {
                    xhr.send();
                    running = true;
                }
            });
        },

        empty: function () {
            return Object.keys(this.queue).length === 0;
        }
    });
    return RequestQueue;
});