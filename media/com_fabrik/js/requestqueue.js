/**
 * Fabrik back-end ajax request queue
 *
 * @copyright: Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/* This object was first used to serialise ajax requests so that they are not all fired at the same time
   pending inclusion of https://github.com/joomla/joomla-platform/pull/1209/ in Joomla.
   This PR was first included in J3.4.0 in 2015, and since J3.3 has been out of support for some time,
   this original code is no longer required.

   This module has therefore been repurposed as an ajax request / response caching mechanism for use in complex
   backend forms esp. visualisation which can have tens of fabriktables / listfields fields which are element lists
   which run ajax to get the element list when the list name field is changed by the user.

   In many cases these run identical ajax request several times, so this module also consolidates these
   into a single ajax request and relays the callbacks to each of the original requests.

   It is anticipated that this will result in significant performance improvements
   for fabriktables / listfields fields, where the elements dropdown for a list may be requested several times.

   Note 1: Because data is cached until user refreshes entire page by e.g. clicking Save,
   this will not take into account changes to server data made by another user or in another window.
   This is not anticipated to be a problem for its intended usage only in Fabrik back-end,
   where its primary use is to avoid repeated ajax calls when the user changes e.g. a list selection dropdown,
   however if this turns out to be an issue, clearing cache results (say 60 seconds) after last event has been handled would resolve this.

   Note 2: Ajax calls are currently all Mootools, however this module has been structured
   to distinguish between Mootools and other ajax calls and so can be easily extended to provide
   similar functionality for jQuery or native or other lightweight ajax functionality.

   Note 3: At present requests identical except for different Mootools Request timeout option values
   are considered different requests - therefore onTimeout will run consistently based
   on the time since original request was sent. In the future if needed, timeouts could be handled by timers.

   Note 4: Since all callbacks are run in order to relay them to calling fields, this module
   includes functionality to allow for default callbacks if a callback is not defined in the request.
   So for example, error messages can be logged or an ajax request which failed due to a temporary
   network glitch could be retried, however in the initial release of this rewritten module
   such error handlers are not included.

   Similarly pre- and post-callback callbacks are also provided for - and these are used to avoid caching failed requests.

   Sophist March 2018
*/

define(['jquery'], function (jQuery) {
	var RequestQueue = new Class({

		cache: {}, // store request events and event result data for ajax requests

		initialize: function () {
		},

		isMootoolsRequest: function (request) {
			return Request && request instanceof Request;
		},

		isMootoolsRequestHTML: function (request) {
			return Request.HTML && request instanceof Request.HTML;
		},

		isMootoolsRequestJSON: function (request) {
			return Request.JSON && request instanceof Request.JSON;
		},

		add: function (request) {
			// Fabrik currently only uses Mootools xhr but this is designed to be extended to support e.g. jQuery or native ajax calls.
			if (this.isMootoolsRequest(request)) {
				this.addMootoolsRequest(request);
			} else if (Fabrik.debug) {
				fconsole('fabrik requestqueue: Unknown ajax request type', request);
			}
		},

		/* Algorithm is as follows:
		   1. Calculate hash on request options with all (callback) functions removed.
		   2. If hash does not exist in cache, add to cache and send ajax request
		   3. If hash already exists in cache, execute all callback(s) that have already been executed using previous response.
		   Note: Opportunity to provide debugging messages for error situations not handled by callbacks.
		*/

		// Callbacks taken from https://mootools.net/core/docs/1.6.0/Request/Request
		mootoolsRequestCallbacks: [
			'request',
			'loadstart',
			'progress',
			'complete',
			'cancel',
			'success',
			'failure',
			'error',
			'exception',
			'timeout',
		],

		addMootoolsRequest: function (request) {
			var hash = JSON.stringify(request.options);

			// Fabrik should not create synchronous requests, but if one is received, we need to run it immediately.
			// There is also currently an issue with Request.HTML not working if multi-plexed.
			if (request.options.async === false || this.isMootoolsRequestHTML(request)) {
				hash += Math.floor(Math.random() * 1000000);
				var cacheEntry = {
					'metaRequest': request,
					'requests': [],
					'hash': hash,
				};
				this.cache[hash] = cacheEntry;
				request.send();
				return;
			}

			var hash = JSON.stringify(request.options);
			if (this.cache.hasOwnProperty(hash)) {
				// Add this request to the cache entry and callback any callbacks already executed.
				var cacheEntry = this.cache[hash];
				if (Fabrik.debug) {
					fconsole('fabrik requestqueue: Adding ajax request: Repeat', cacheEntry.requests.length, request);
				}
				cacheEntry.requests.push(request);
				var i = 0;
				this.mootoolsRequestCallbacks.forEach(function(callbackName) {
					if (cacheEntry.hasOwnProperty(callbackName) && request.$events.hasOwnProperty(callbackName)) {
						var xhr = cacheEntry[callbackName][0];
						var args = cacheEntry[callbackName][1];
						if (Fabrik.debug) {
							fconsole('fabrik requestqueue: Ajax', 'on' + callbackName, 'callback: Repeat', i);
						}
						var events = request.$events[callbackName];
						events.forEach(function(callback) {
							callback.apply(xhr, args);
						});
					}
					i++;
				});
			} else {
				if (Fabrik.debug) {
					fconsole('fabrik requestqueue: Adding ajax request: Repeat 0', request);
				}

				var cacheEntry = {
					'requests': [request],
					'hash': hash,
				};
				this.cache[hash] = cacheEntry;

				// Parse has rather than use request.options in order to eliminate prototype functions
				var options = JSON.parse(hash);
				this.mootoolsRequestCallbacks.forEach(function(callbackName) {
					options['on' + callbackName.charAt(0).toUpperCase() + callbackName.slice(1)] = this.createMootoolsCallback(callbackName);
				}.bind(this));

				cacheEntry.metaRequest = new Request(options);
				cacheEntry.metaRequest.requestqueue = this;
				cacheEntry.metaRequest.cacheEntry = this.cache[hash];
				cacheEntry.metaRequest.send();
			}
		},

		createMootoolsCallback: function (callbackName) {
			return function() {
				/* when called "this" is the metaRequest object so
				   this.requestqueue is the requestqueue object and
				   this.cacheEntry is the cache entry for all request options exactly like this that contain the real callbacks. */
				var args = Array.prototype.slice.call(arguments);
				this.cacheEntry[callbackName] = [this, args];
				if (this.requestqueue.__proto__.hasOwnProperty('mootoolsPreCallback_' + callbackName)) {
					// Call a default error handler if it exists
					this.requestqueue.__proto__['mootoolsPreCallback_' + callbackName].apply(this, args);
				}
				var defaultCalled = false;
				var i = 0;
				this.cacheEntry.requests.forEach(function(request) {
					if (request.$events.hasOwnProperty(callbackName)) {
						if (Fabrik.debug) {
							fconsole('fabrik requestqueue: Ajax', 'on' + callbackName.charAt(0).toUpperCase() + callbackName.slice(1), 'callback: Repeat', i);
						}
						request.$events[callbackName].forEach(function(callback) {
							callback.apply(this, args);
						});
					} else if (!defaultCalled && this.requestqueue.__proto__.hasOwnProperty('mootoolsDefault_' + callbackName)) {
						// Call a default error handler if it exists
						defaultCalled = true;
						this.requestqueue.__proto__['mootoolsDefault_' + callbackName].apply(this, args);
					}
					i++;
				}.bind(this));
				if (this.requestqueue.__proto__.hasOwnProperty('mootoolsPostCallback_' + callbackName)) {
					// Call a default error handler if it exists
					this.requestqueue.__proto__['mootoolsPostCallback_' + callbackName].apply(this, args);
				}
			};
		},

		// If request failed or was cancelled, after complete event has been relayed, we need to delete the cache entry
		// so that if request is repeated, it will be retried rather than the cancel / fail being repeated.
		mootoolsPostCallback_cancel: function () {
			this.requestqueue.mootoolsPostCallback_clearCache('cancel');
		},

		mootoolsPostCallback_failure: function () {
			this.requestqueue.mootoolsPostCallback_clearCache('failure');
		},

		mootoolsPostCallback_exception: function () {
			this.requestqueue.mootoolsPostCallback_clearCache('exception');
		},

		mootoolsPostCallback_timeout: function () {
			this.requestqueue.mootoolsPostCallback_clearCache('timeout');
		},

		mootoolsPostCallback_clearCache: function (callbackName) {
			if (Fabrik.debug) {
				fconsole('fabrik requestqueue: Deleting', callbackName, 'request from cache:', this);
			}
			delete this.requestqueue.cache[this.hash];
		},

		cancel: function (request) {
			this.cache.forEach(function (cacheEntry) {
				if (cacheEntry.requests.includes(request)) {
					if (this.isMootoolsRequest(cacheEntry.metaRequest)) {
						this.cancelMootoolsRequest(cacheEntry, request);
					} else if (Fabrik.debug) {
						fconsole('fabrik requestqueue: Cannot cancel unknown request type', cacheEntry);
					}
				}
			}.bind(this));
		},

		cancelMootoolsRequest: function (cacheEntry, request) {
			if ('complete' in cacheEntry || 'cancel' in cacheEntry) {
				return;
			}
			if (cacheEntry.requests.length === 1) {
				if (Fabrik.debug) {
					fconsole('fabrik requestqueue: Cancelling metarequest', cacheEntry.metaRequest);
				}
				cacheEntry.metaRequest.cancel();
			} else {
				if (Fabrik.debug) {
					fconsole('fabrik requestqueue: Cancelling sub-request', request);
				}
				request.cancel();
				var i = cacheEntry.requests.indexOf(request);
				cacheEntry.requests.splice(i, 1);
			}
		},

		empty: function () {
			// Check whether all requests are complete
			for (var hash in this.cache) {
				if (this.cache.hasOwnProperty(hash)) {
					if (this.cache[hash].metaRequest.isRunning()) {
						return false;
					}
				}
			}
			return true;
		},

		cancelAll: function () {
			// Cancel all currently running xhr and empty the cache
			for (var hash in this.cache) {
				if (this.cache.hasOwnProperty(hash)) {
					var cacheEntry = this.cache[hash];
					if (this.isMootoolsRequest(cacheEntry.metaRequest)) {
						this.cancelMootoolMetaRequest(cacheEntry);
					} else if (Fabrik.debug) {
						fconsole('fabrik requestqueue: Cannot cancel unknown request type', cacheEntry);
					}
				}
			}
			this.cache = {};
		},

		cancelMootoolsMetaRequest: function (cacheEntry) {
			if (!cacheEntry.metaRequest.isRunning()) {
				return;
			}
			if (Fabrik.debug) {
				fconsole('fabrik requestqueue: Cancelling metarequest', cacheEntry.metaRequest);
			}
			cacheEntry.metaRequest.cancel();
		},
	});

	return RequestQueue;
});
