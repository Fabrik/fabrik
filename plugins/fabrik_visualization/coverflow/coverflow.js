/**
 * Coverflow Visualization
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbVisCoverflow = new Class({
	Implements: [Options],
	options: {},
	initialize: function (json, options) {
		json = eval(json);
		this.setOptions(options);

		widget = Runway.createOrShowInstaller(
			document.getElementById("coverflow"),
			{
				// examples of initial settings
				// slideSize: 200,
				// backgroundColorTop: "#fff",

				// event handlers
				onReady: function () {
					widget.setRecords(json);
				}
			}
		);
	}
});
