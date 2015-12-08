/**
 * List Toggle
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbListToggle = new Class({

	initialize: function (form) {

		// Stop dropdown closing on click
		jQuery('#' + form.id + ' .togglecols .dropdown-menu a, #' + form.id + ' .togglecols .dropdown-menu li').click(function (e) {
			e.stopPropagation();
		});

		// Set up toggle events for elements
		form.addEvent('mouseup:relay(a[data-toggle-col])', function (e, btn) {
			var state = btn.get('data-toggle-state');
			var col = btn.get('data-toggle-col');
			this.toggleColumn(col, state, btn);
		}.bind(this));

		// Toggle events for groups (toggles all elements in group)
		var groups = form.getElements('a[data-toggle-group]');
		form.addEvent('mouseup:relay(a[data-toggle-group])', function (e, group) {
			var state = group.get('data-toggle-state'), muted,
			groupName = group.get('data-toggle-group'),
			links = document.getElements('a[data-toggle-parent-group=' + groupName + ']');

			links.each(function (btn) {
				var col = btn.get('data-toggle-col');
				this.toggleColumn(col, state, btn);
			}.bind(this));

			state = state === 'open' ? 'close' : 'open';
			muted = state === 'open' ? '' : ' muted';
			group.getElement('i').className = 'icon-eye-' + state + muted;
			group.set('data-toggle-state', state);

		}.bind(this));
	},

	/**
	 * Toggle column
	 *
	 * @param col   Element name
	 * @param state Open/closed
	 * @param btn   Button/link which initiated the toggle
	 */
	toggleColumn: function (col, state, btn) {
		var muted;
		state = state === 'open' ? 'close' : 'open';

		if (state === 'open') {
			document.getElements('.fabrik___heading .' + col).show();
			document.getElements('.fabrikFilterContainer .' + col).show();
			document.getElements('.fabrik_row  .' + col).show();
			document.getElements('.fabrik_calculations  .' + col).show();
			muted = '';
		} else {
			document.getElements('.fabrik___heading .' + col).hide();
			document.getElements('.fabrikFilterContainer .' + col).hide();
			document.getElements('.fabrik_row  .' + col).hide();
			document.getElements('.fabrik_calculations  .' + col).hide();
			muted = ' muted';
		}

		btn.getElement('i').className = 'icon-eye-' + state + muted;
		btn.set('data-toggle-state', state);
	}
});
