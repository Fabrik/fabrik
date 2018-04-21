/**
 * Admin Namespace
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

(function () {
	if (typeOf(window.FabrikAdmin) === 'object') {
		return;
	}
	FabrikAdmin = {};

	// Various Joomla element plugins used to control JForm elements
	FabrikAdmin.model = {'fields': {'element': {}}};

	// Function to apply tips to page, after ajax call has loaded a plugin's form
	FabrikAdmin.reTip = function () {
		$$('.hasTip').each(function (el) {
			var title = el.get('title');
			if (title) {
				var parts = title.split('::', 2);
				el.store('tip:title', parts[0]);
				el.store('tip:text', parts[1]);
			}
		});
		var JTooltips = new Tips($$('.hasTip'), { maxTitleChars: 50, fixed: false});

		// Joomla3.2
		if (typeof(jQuery) !== 'undefined') {
			jQuery('.hasTooltip').tooltip({'html': true, 'container': 'body'});
			jQuery(document).popover({selector: '.hasPopover', trigger: 'hover'});
		}
	};

	window.fireEvent('fabrik.admin.namespace');
}());

if (typeof(jQuery) !== 'undefined') {
	// Relay radio button group clicks for content added via ajax calls
	(function ($) {
		$(document).on('click', '.btn-group label:not(.active)', null, function (event) {
			var label = $(this);
			var input = $('#' + label.attr('for'));
			if (!input.prop('checked')) {
				label.closest('.btn-group').find("label").removeClass('active btn-success btn-danger btn-primary');
				if (input.val() === '') {
					label.addClass('active btn-primary');
				} else if (input.val().toInt() === 0) {
					label.addClass('active btn-danger');
				} else {
					label.addClass('active btn-success');
				}
				input.prop('checked', true);
				input.trigger('change');
			}
		});
	})(jQuery);
}

// On Joomla repeatable subform add events, run elements cloned
if (typeof(jQuery) !== 'undefined') {
	// Relay radio button group clicks for content added via ajax calls
	jQuery(document).on('subform-row-add', function(event, row) {
		var base = row.getAttribute('data-base-name');
		var group = row.getAttribute('data-group');
		if (!group.startsWith(base)) {
			fconsole('Fabrik namespace: new subform row, but data-group=', group, 'does not start with data-base-name=', base);
			return;
		}
		var idx = group.slice(base.length);
		var chgEvent = new Event('change');
		row.getElements('select').each(function (i) {
			// Reset all select dropdowns to default for cloned item.
			i.set('value', i.getElement('option').get('value'));
			i.dispatchEvent(chgEvent);
		});
		row.getElements('input, select, textarea').each(function (i) {
			if (i.id) {
				var newId = i.id;
				var oldId = newId.replace(idx + '__', 'X__');
				$H(FabrikAdmin.model.fields).each(function (plugins, type) {
					// We need to locate either lower numbered subform or use X_ if zero
					if (typeOf(FabrikAdmin.model.fields[type][oldId]) !== 'null') {
						var newPlugin = Object.clone(FabrikAdmin.model.fields[type][oldId]);
						if (newPlugin !== false) {
							newPlugin.cloned(newId, idx);
							FabrikAdmin.model.fields[type][newId] = newPlugin;
						}
					}
				});
			}
		});
	});
}
