/**
 * Tooltips
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FloatingTips = new Class({
	Implements: [Options, Events],

	options: {
		fxProperties: {transition: Fx.Transitions.linear, duration: 500},
		position: 'top',
		'showOn': 'mouseenter',
		'hideOn': 'mouseleave',
		'content': 'title',
		'distance': 50,
		'tipfx': 'Fx.Transitions.linear',
		'duration': 500,
		'fadein': false,
		showFn: function (e) {
			e.stop();
			return true;
		},
		hideFn: function (e) {
			e.stop();
			return true;
		}
	},

	initialize: function (elements, options) {
		this.setOptions(options);
		this.options.fxProperties = {transition: eval(this.options.tipfx), duration: this.options.duration};
		//any tip (not necessarily in this instance has asked for all other tips to be hidden.
		window.addEvent('tips.hideall', function (e, trigger) {
			if (typeOf(e) === 'element') {
				trigger = e;
			}
			this.hideOthers(trigger);
		}.bind(this));
		if (elements) {
			this.attach(elements);
		}
	},

	attach: function (elements) {
		this.elements = $$(elements);
		this.elements.each(function (trigger) {
			var tmpOpts = {};
			// Tip text in gmap viz bubble not decodable so test if json is valid first
			if (trigger.get('opts', '{}').opts && JSON.validate(trigger.get('opts', '{}').opts)) {
				tmpOpts = JSON.decode(trigger.get('opts', '{}').opts);
			}

			var opts = Object.merge(Object.clone(this.options), tmpOpts);
			var optStore = trigger.retrieve('opts', {});
			trigger.erase('opts');
			if (!optStore[opts.showOn]) {
				optStore[opts.showOn] = opts;
				trigger.store('opts', optStore);
				var content = this.getTipContent(trigger, opts.showOn);
				var tipContent = new Element('div.floating-tip.tip' + opts.position);
				var tip = new Element('div.floating-tip-wrapper');
				if (typeOf(content) === 'string') {
					content = Encoder.htmlDecode(content);
					tipContent.set('html', content);
				} else {
					tipContent.adopt(content);
				}
				tip.adopt(tipContent);
				tip.inject(document.body).hide();
				tip.addEvent('mouseleave', function (e) {
					if (opts.hideOn === 'mouseleave') {
						tip.hide();
					}
				}.bind(this));

				var store = trigger.retrieve('tip', {});
				store[opts.showOn] = tip;
				trigger.store('tip', store);

				var complete = {
						'onComplete': function (e) {
							if (this.hideMe) {
								this.tip.hide();
							}
						},
						'onStart': function (e) {
							this.hideMe = false;
						}
					};

				var fxOpts = Object.merge(complete, Object.clone(this.options.fxProperties));
				var fx = new Fx.Morph(tip, fxOpts);
				fx.tip = tip;
				var fxs = trigger.retrieve('fx', {});
				fxs[opts.showOn] = fx;
				trigger.store('fxs', fxs);
				this.addStartEvent(trigger, opts.showOn);
				this.addEndEvent(trigger, opts.showOn);
			}
		}.bind(this));
	},

	addStartEvent: function (trigger, evnt) {
		var opts = trigger.retrieve('opts');
		opts = opts[evnt];
		trigger.addEvent(opts.showOn, function (e) {

			/*
			 * Think this if statement may not be needed - I believe it was trying to fix the issue where in IE 8 the checkbox
			 * was not checked with a click event - now in list.js I've changed the showOn option to 'change' works
			 */
			if (opts.showOn === 'click') {
				//toggling
				var active = trigger.retrieve('active', false);
				var newActive = active ? false : true;
				trigger.store('active', newActive);
				if (active) {
					return;
				}
			}
			if (opts.showFn(e, trigger)) {
				window.fireEvent('tips.hideall', [trigger]);
				this.show(trigger, evnt);
			}
		}.bind(this));
	},

	addEndEvent: function (trigger, evnt) {
		var opts = trigger.retrieve('opts');
		opts = opts[evnt];
		trigger.addEvent(opts.hideOn, function (e) {
			var tips = trigger.retrieve('tip');
			var tip = tips[opts.showOn];
			if (opts.hideFn(e)) {
				this.hide(trigger, evnt);
			}
		}.bind(this));
	},

	getTipContent: function (trigger, evnt) {
		var c;
		var opts = trigger.retrieve('opts');
		opts = opts[evnt];
		var content = opts.content;
		switch (typeOf(content)) {
		case 'string':
			c = trigger.get(content);
			trigger.set(content, '');
			break;
		case 'element':
			c = content;
			break;
		default:
			c = content(trigger);
			break;
		}
		return c;
	},

	show: function (trigger, evnt) {
		var tips = trigger.retrieve('tip');
		var opts = trigger.retrieve('opts');
		opts = opts[evnt];
		var tip = tips[opts.showOn];
		if (tip.getStyle('opacity') === 1 && tip.getStyle('display') !== 'none' && typeOf(tip.getParent()) !== 'null') {
			//already shown don't reanimate
			return;
		}
		tip.setStyle('opacity', 0);
		tip.show();
		if (typeOf(opts.position) === 'null') {
			opts.position = 'left';
		}
		var offsetDistance = opts.distance;
		switch (opts.position) {
		case 'top':
			var padding = tip.getStyle('border-top').toInt() + tip.getStyle('border-bottom').toInt();
			var offset = {x: 0, y: -1 * offsetDistance - padding * 2};
			edge = 'top';
			break;
		case 'bottom':
			edge = 'top';
			padding = tip.getStyle('border-top').toInt() + tip.getStyle('border-bottom').toInt();
			offset = {x: 0, y: offsetDistance + padding};
			break;
		case 'right':
			padding = tip.getStyle('border-left').toInt() + tip.getStyle('border-right').toInt();
			offset = {x: offsetDistance + padding, y: 0};
			edge = 'left';
			break;
		default:
		case 'left':
			padding = tip.getStyle('border-left').toInt() + tip.getStyle('border-right').toInt();
			offset = {x: -1 * offsetDistance - padding, y: 0};
			edge = 'right';
			break;
		}

		var pos = {relativeTo: trigger,
				position: opts.position,
				edge: edge,
				offset: offset
			};
		//position the tip in its start animation location.
		tip.position(pos);
		// if no fade in show the tip, as its now positioned in its start location
		if (!this.options.fadein) {
			tip.setStyle('opacity', 1);
		}
		var morph = this.options.fadein ? {'opacity': [0, 1]} : {};
		var tipDims = tip.getCoordinates();
		var triggerDims = trigger.getCoordinates();
		//work out where to morph to
		switch (opts.position) {
		case 'top':
			var t = tip.getStyle('top').toInt() - tip.getStyle('height').toInt();
			morph.top = [t, t + offsetDistance];
			break;
		case 'bottom':
			t = tip.getStyle('top').toInt();
			morph.top = [t, t - offsetDistance];
			break;
		case 'right':
			l = tip.getStyle('left').toInt();
			morph.left = [l, l - offsetDistance];
			break;
		case 'left':
			l = tip.getStyle('left').toInt();
			morph.left = [l, l + offsetDistance ];
			break;
		}

		var fxs = trigger.retrieve('fxs');
		var mover = fxs[opts.showOn];
		if (!mover.isRunning()) {
			mover.start(morph);
		}
	},

	hide: function (trigger, evnt) {
		var opts = trigger.retrieve('opts');
		opts = opts[evnt];
		var tips = trigger.retrieve('tip');
		var tip = tips[opts.showOn];
		var fxs = trigger.retrieve('fxs');
		var fx = fxs[opts.showOn];
		this.hideOthers(trigger);
		if (fx.isRunning() && opts.showOn !== "mouseenter" && opts.hideOn !== "mouseleave") {
			return;
		}
		fx.hideMe = true;
		tip.hide();
		trigger.store('active', false);
	},

	hideOthers: function (except) {
		if (this.element) {
			this.elements.each(function (element) {
				if (element !== except) {
					var tips = element.retrieve('tip');
					$H(tips).each(function (tip) {
						tip.hide();
					});
				}
			});
		}
	},

	hideAll: function () {
		this.elements.each(function (element) {
			var tips = element.retrieve('tip');
			$H(tips).each(function (tip) {
				tip.hide();
			});
		});
	}

});