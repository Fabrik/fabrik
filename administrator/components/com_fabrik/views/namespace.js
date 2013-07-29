(function () {
	if (typeOf(window.FabrikAdmin) === 'object') {
		return;
	}
	FabrikAdmin = {};
	// Various Joomla element plugins used to control JForm elements
	FabrikAdmin.model = {'fields': {'fabriktable': {}, 'element': {}}};
	
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
	};
}());